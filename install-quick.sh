#!/bin/bash

# =============================================================================
# AI客服系统 (ai-kefu) 快速安装脚本
# 适用于 CentOS 7/8 - 最小化安装版本
# 项目地址: https://github.com/xinghe778/ai-kefu
# 使用方法: curl -sSL https://raw.githubusercontent.com/xinghe778/ai-kefu/install-quick.sh | bash
# =============================================================================

set -e
set -u

# 配置
PROJECT_REPO="https://github.com/xinghe778/ai-kefu.git"
WEB_USER="apache"
WEB_GROUP="apache"
DEFAULT_DOMAIN="localhost"
INSTALL_DIR="/var/www/html/ai-kefu"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}[✓]${NC} $1"; }
print_error() { echo -e "${RED}[✗]${NC} $1"; }
print_info() { echo -e "${BLUE}[i]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[!]${NC} $1"; }

# 快速系统检查
quick_system_check() {
    print_info "快速系统检查..."
    
    # 检查root权限
    [[ $EUID -eq 0 ]] || { print_error "需要root权限"; exit 1; }
    
    # 检查CentOS
    [[ -f /etc/centos-release ]] || { print_error "仅支持CentOS系统"; exit 1; }
    
    # 检查内存
    local mem=$(free -m | awk '/^Mem:/{print $2}')
    [[ $mem -ge 1024 ]] || { print_error "内存不足，需要至少1GB"; exit 1; }
    
    print_success "系统检查通过"
}

# 一键安装核心组件
one_click_install() {
    print_info "开始一键安装..."
    
    # 更新系统（静默）
    print_info "更新系统包..."
    yum update -y -q
    
    # 安装必要组件
    print_info "安装核心组件..."
    yum install -y -q epel-release yum-utils wget curl git
    
    # 检查并安装LAMP环境
    if ! command -v php &> /dev/null; then
        print_info "安装PHP 8.1+..."
        yum install -y -q yum-utils
        yum install -y -q http://rpms.remirepo.net/enterprise/remi-release-7.rpm || 
        yum install -y -q http://rpms.remirepo.net/enterprise/remi-release-8.rpm
        
        yum-config-manager --enable remi-php74 2>/dev/null || true
        yum install -y -q php php-mysql php-mysqli php-mbstring php-gd php-zip php-curl
    fi
    
    if ! command -v mysql &> /dev/null; then
        print_info "安装MySQL..."
        yum install -y -q mysql-server mysql
        systemctl enable --now mysqld
    fi
    
    if ! command -v httpd &> /dev/null; then
        print_info "安装Apache..."
        yum install -y -q httpd
        systemctl enable --now httpd
    fi
    
    print_success "核心组件检查完成"
}

# 快速部署
quick_deploy() {
    print_info "快速部署应用..."
    
    local install_dir=$INSTALL_DIR
    
    # 备份现有安装
    if [[ -d "$install_dir" ]]; then
        mv "$install_dir" "${install_dir}.backup.$(date +%Y%m%d_%H%M%S)"
    fi
    
    # 克隆项目
    print_info \"从GitHub克隆项目...\"
    mkdir -p "$install_dir"
    cd "$install_dir"
    git clone "$PROJECT_REPO" . || {
        print_error \"项目克隆失败，请检查网络连接\"
        exit 1
    }
EOF

    # 设置文件权限
    print_info "设置文件权限..."
    chown -R $WEB_USER:$WEB_GROUP "$install_dir"
    chmod -R 755 "$install_dir"
    chmod 644 *.php 2>/dev/null || true
    chmod -R 755 admin/ uploads/ 2>/dev/null || true


    
    print_success "应用部署完成"
}

# 快速数据库初始化
quick_db_init() {
    print_info "初始化数据库..."
    
    # 获取临时MySQL密码
    sleep 5
    local temp_pass=$(grep 'temporary password' /var/log/mysqld.log 2>/dev/null | tail -1 | awk '{print $NF}' || echo "")
    
    # 设置数据库
    mysql -uroot << EOF 2>/dev/null || {
        print_warning "设置MySQL密码..."
        mysql --connect-expired-password -uroot -p"$temp_pass" << 'INNER_EOF'
ALTER USER 'root'@'localhost' IDENTIFIED BY 'root123';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';
FLUSH PRIVILEGES;
INNER_EOF
    }
    
    # 创建应用数据库
    local db_name="ai_kefu"
    local db_user="aikefu"
    local db_pass=$(openssl rand -base64 12)
    
    mysql -uroot << EOF
CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';
GRANT ALL ON \`${db_name}\`.* TO '${db_user}'@'localhost';
FLUSH PRIVILEGES;
EOF

    # 保存数据库配置
    cat > /tmp/db_credentials.txt << EOF
数据库名称: ${db_name}
数据库用户: ${db_user}
数据库密码: ${db_pass}
数据库主机: localhost
配置时间: $(date)
EOF
    chmod 600 /tmp/db_credentials.txt
    
    # 如果项目有数据库初始化脚本，执行它
    if [[ -f "$install_dir/complete_database_fix.sql" ]]; then
        print_info "执行数据库初始化脚本..."
        mysql -u "$db_user" -p"$db_pass" "$db_name" < "$install_dir/complete_database_fix.sql" 2>/dev/null || {
            print_warning "数据库初始化脚本执行失败，请手动执行"
        }
    fi
    
    print_success "数据库创建完成"

    print_success "数据库初始化完成"
}

# 配置Web服务器
quick_web_config() {
    print_info "配置Web服务器..."
    
    # 创建虚拟主机配置
    cat > /etc/httpd/conf.d/ai-kefu.conf << EOF
<VirtualHost *:80>
    DocumentRoot $INSTALL_DIR
    ServerName localhost
    
    <Directory $INSTALL_DIR>
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory $INSTALL_DIR/admin>
        <Files "*.php">
            Order deny,allow
            Deny from all
            Allow from 127.0.0.1
            Allow from ::1
        </Files>
    </Directory>
    
    ErrorLog /var/log/httpd/ai-kefu-error.log
    CustomLog /var/log/httpd/ai-kefu-access.log combined
</VirtualHost>
EOF

    # 重启服务
    systemctl restart httpd
    systemctl restart mysqld
    
    # 配置防火墙
    if command -v firewall-cmd &> /dev/null; then
        systemctl enable firewalld
        systemctl start firewalld
        firewall-cmd --permanent --add-service=http 2>/dev/null || true
        firewall-cmd --reload
    fi
    
    print_success "Web服务器配置完成"
}

# 最终检查
final_check() {
    print_info "执行最终检查..."
    
    local errors=0
    
    # 检查服务状态
    systemctl is-active --quiet httpd || { print_error "Apache未运行"; ((errors++)); }
    systemctl is-active --quiet mysqld || { print_error "MySQL未运行"; ((errors++)); }
    
    # 检查网站访问
    if curl -f -s http://localhost >/dev/null 2>&1; then
        print_success "网站可正常访问"
    else
        print_warning "网站可能无法从外部访问"
    fi
    
    if [[ $errors -eq 0 ]]; then
        print_success "安装完成！"
    else
        print_error "发现 $errors 个问题，请检查配置"
        exit 1
    fi
}

# 显示安装结果
show_result() {
    echo
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                🎉 AI客服系统安装成功！                       ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${YELLOW}访问信息:${NC}"
    echo -e "  🌐 网站地址: ${BLUE}http://localhost${NC}"
    echo -e "  🔧 项目目录: ${BLUE}$INSTALL_DIR${NC}"
    echo -e "  📋 故障排除: ${BLUE}./troubleshoot.sh${NC}"
    
    if [[ -f "/tmp/db_credentials.txt" ]]; then
        echo -e "\n${YELLOW}数据库配置:${NC}"
        cat /tmp/db_credentials.txt | while read line; do
            echo -e "  $line"
        done
    fi
    echo
    echo -e "${YELLOW}下一步操作:${NC}"
    echo "  1. 查看项目README.md了解配置"
    echo "  2. 在管理后台配置API密钥"
    echo "  3. 测试系统功能"
    echo
    echo -e "${YELLOW}服务管理:${NC}"
    echo "  重启Apache: systemctl restart httpd"
    echo "  重启MySQL:  systemctl restart mysqld"
    echo "  查看日志:   tail -f /var/log/httpd/error_log"
    echo
}

# 主程序
main() {
    echo -e "${BLUE}"
    cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║              AI客服系统 (ai-kefu) 快速安装                  ║
║                      一键部署版本                           ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
    
    # 执行安装步骤
    quick_system_check
    one_click_install
    quick_deploy
    quick_db_init
    quick_web_config
    final_check
    show_result
}

# 运行主程序
main "$@" 2>&1 | tee /var/log/ai-kefu-quick-install.log