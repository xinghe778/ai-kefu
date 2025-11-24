#!/bin/bash

# =============================================================================
# AI客服系统 (ai-kefu) 快速安装脚本 - Debian/Ubuntu版
# 适用于 Debian 11/12, Ubuntu 20.04/22.04 - 最小化安装版本
# 项目地址: https://github.com/xinghe778/ai-kefu
# 使用方法: curl -sSL https://raw.githubusercontent.com/xinghe778/ai-kefu/install-quick-debian.sh | bash
# =============================================================================

set -e
set -u

# 配置
PROJECT_REPO="https://github.com/xinghe778/ai-kefu.git"
WEB_USER="www-data"
WEB_GROUP="www-data"
DEFAULT_DOMAIN="localhost"
INSTALL_DIR="/var/www/html/ai-kefu"
PHP_VERSION="8.1"

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
    
    # 检查Debian/Ubuntu
    if [[ -f /etc/debian_version ]]; then
        print_success "检测到 Debian 系统"
    elif [[ -f /etc/lsb-release ]]; then
        print_success "检测到 Ubuntu 系统"
    else
        print_error "仅支持Debian/Ubuntu系统"; exit 1
    fi
    
    # 检查内存
    local mem=$(free -m | awk '/^Mem:/{print $2}')
    [[ $mem -ge 1024 ]] || { print_error "内存不足，需要至少1GB"; exit 1; }
    
    print_success "系统检查通过"
}

# 检查并安装LAMP环境
check_and_install_lamp() {
    print_info "检查LAMP环境..."
    
    local need_install=false
    
    # 检查Apache
    if ! dpkg -l | grep -q apache2; then
        print_info "Apache未安装，开始安装..."
        apt update
        apt install -y apache2
        a2enmod rewrite ssl headers
        systemctl enable apache2
        need_install=true
    else
        print_success "Apache已安装"
    fi
    
    # 检查MySQL
    if ! dpkg -l | grep -q mysql-server; then
        print_info "MySQL未安装，开始安装..."
        apt install -y mysql-server mysql-client
        systemctl enable mysql
        need_install=true
    else
        print_success "MySQL已安装"
    fi
    
    # 检查PHP
    if ! php --version &>/dev/null; then
        print_info "PHP未安装，开始安装..."
        # 添加PHP仓库
        add-apt-repository -y ppa:ondrej/php
        apt update
        
        # 安装PHP和扩展
        apt install -y \
            php${PHP_VERSION} \
            php${PHP_VERSION}-apache2 \
            php${PHP_VERSION}-mysql \
            php${PHP_VERSION}-curl \
            php${PHP_VERSION}-gd \
            php${PHP_VERSION}-mbstring \
            php${PHP_VERSION}-xml \
            php${PHP_VERSION}-zip
        need_install=true
    else
        print_success "PHP已安装"
    fi
    
    if [[ "$need_install" == "true" ]]; then
        systemctl restart apache2
        print_success "LAMP环境安装完成"
    else
        print_info "LAMP环境已配置，跳过安装"
    fi
}

# 创建数据库
create_database_quick() {
    print_info "创建数据库..."
    
    # 检查数据库是否已存在
    if mysql -e "USE api;" &>/dev/null; then
        print_warning "数据库api已存在，跳过创建"
        return
    fi
    
    # 生成随机密码
    local DB_PASS=$(openssl rand -base64 12)
    
    # 创建数据库和用户
    mysql -e "
        CREATE DATABASE IF NOT EXISTS api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS 'api'@'localhost' IDENTIFIED BY '$DB_PASS';
        GRANT ALL PRIVILEGES ON api.* TO 'api'@'localhost';
        FLUSH PRIVILEGES;
    " || {
        print_error "数据库创建失败"
        exit 1
    }
    
    # 保存数据库配置
    cat > /root/aikefu_db_config.txt << EOF
# AI客服系统数据库配置
数据库名: api
用户名: api
密码: $DB_PASS
主机: localhost
端口: 3306

安装时间: $(date)
EOF
    
    print_success "数据库创建完成"
    print_warning "数据库密码已保存到: /root/aikefu_db_config.txt"
}

# 快速部署项目
quick_deploy() {
    print_info "部署项目代码..."
    
    # 创建安装目录
    mkdir -p "$INSTALL_DIR"
    
    # 如果目录已存在，更新代码
    if [[ -d "$INSTALL_DIR/.git" ]]; then
        print_info "更新现有项目..."
        cd "$INSTALL_DIR"
        git pull origin main 2>/dev/null || git pull origin master
    else
        print_info "克隆项目代码..."
        git clone "$PROJECT_REPO" "$INSTALL_DIR"
    fi
    
    # 设置权限
    chown -R $WEB_USER:$WEB_GROUP "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    chmod -R 777 "$INSTALL_DIR"/uploads 2>/dev/null || true
    chmod -R 777 "$INSTALL_DIR"/admin/uploads 2>/dev/null || true
    chmod -R 777 "$INSTALL_DIR"/logs 2>/dev/null || true
    
    print_success "项目部署完成"
}

# 配置虚拟主机
configure_vhost_quick() {
    print_info "配置虚拟主机..."
    
    # 创建虚拟主机配置
    cat > /etc/apache2/sites-available/ai-kefu.conf << EOF
<VirtualHost *:80>
    ServerName $DEFAULT_DOMAIN
    DocumentRoot $INSTALL_DIR
    
    <Directory $INSTALL_DIR>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/ai-kefu_error.log
    CustomLog \${APACHE_LOG_DIR}/ai-kefu_access.log combined
</VirtualHost>
EOF
    
    # 启用站点
    a2ensite ai-kefu.conf 2>/dev/null || true
    
    # 重启Apache
    systemctl reload apache2
    
    print_success "虚拟主机配置完成"
}

# 快速数据库初始化
quick_db_init() {
    print_info "初始化数据库..."
    
    local db_config="/root/aikefu_db_config.txt"
    
    if [[ ! -f "$db_config" ]]; then
        print_error "数据库配置文件不存在"
        return 1
    fi
    
    local DB_PASS=$(grep "密码:" "$db_config" | cut -d':' -f2 | tr -d ' ')
    
    # 运行初始化脚本
    if [[ -f "$INSTALL_DIR/install/database_init.sql" ]]; then
        mysql -u api -p"$DB_PASS" api < "$INSTALL_DIR/install/database_init.sql" 2>/dev/null || {
            print_warning "数据库初始化脚本执行失败，跳过"
            return 1
        }
        print_success "数据库初始化完成"
    else
        print_warning "数据库初始化脚本不存在，跳过"
    fi
}

# 快速验证
quick_verify() {
    print_info "快速验证安装..."
    
    # 检查服务状态
    if systemctl is-active --quiet apache2; then
        print_success "Apache运行正常"
    else
        print_error "Apache未运行"
        systemctl status apache2
    fi
    
    if systemctl is-active --quiet mysql; then
        print_success "MySQL运行正常"
    else
        print_error "MySQL未运行"
        systemctl status mysql
    fi
    
    # 检查项目文件
    if [[ -f "$INSTALL_DIR/index.php" ]]; then
        print_success "项目文件完整"
    else
        print_error "项目文件缺失"
    fi
    
    # 检查PHP
    if php --version &>/dev/null; then
        print_success "PHP运行正常"
        php --version | head -n1
    else
        print_error "PHP异常"
    fi
}

# 显示安装结果
show_result() {
    echo
    echo -e "${GREEN}================================${NC}"
    echo -e "${GREEN}  快速安装完成！${NC}"
    echo -e "${GREEN}================================${NC}"
    echo
    echo -e "${BLUE}📁 安装目录:${NC} $INSTALL_DIR"
    echo -e "${BLUE}🌐 访问地址:${NC} http://localhost"
    echo -e "${BLUE}⚙️  管理后台:${NC} http://localhost/admin/"
    
    if [[ -f "/root/aikefu_db_config.txt" ]]; then
        echo -e "${BLUE}🗄️  数据库配置:${NC} /root/aikefu_db_config.txt"
    fi
    
    echo
    echo -e "${YELLOW}💡 提示:${NC}"
    echo -e "• 如果是首次安装，请先配置数据库信息"
    echo -e "• 详细安装指南请查看: README_INSTALL.md"
    echo -e "• 故障排除请运行: ./troubleshoot-debian.sh"
    echo
}

# 主函数
main() {
    echo
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  AI客服系统快速安装${NC}"
    echo -e "${BLUE}  Debian/Ubuntu 版本${NC}"
    echo -e "${BLUE}================================${NC}"
    echo
    
    # 执行安装步骤
    quick_system_check
    check_and_install_lamp
    create_database_quick
    quick_deploy
    configure_vhost_quick
    quick_db_init
    quick_verify
    show_result
}

# 执行主函数
main "$@"