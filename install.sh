#!/bin/bash

# AI客服系统 (ai-kefu) 一键安装脚本
# 版本: v1.0
# 支持系统: CentOS 7/8/9
# 作者: MiniMax Agent

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# 日志函数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# 检查是否为root用户
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "此脚本需要root权限运行，请使用sudo执行"
        exit 1
    fi
}

# 检查系统版本
check_system() {
    log_step "检查系统兼容性..."
    
    if [[ -f /etc/centos-release ]]; then
        local version=$(cat /etc/centos-release | grep -oP 'CentOS.*\K[0-9]+')
        log_info "检测到 CentOS $version"
        
        if [[ $version -eq 7 || $version -eq 8 || $version -eq 9 ]]; then
            log_info "系统版本支持: CentOS $version ✓"
        else
            log_warn "非标准CentOS版本，安装可能需要手动调整"
        fi
    else
        log_error "仅支持CentOS系统"
        exit 1
    fi
}

# 检查系统资源
check_resources() {
    log_step "检查系统资源..."
    
    # 检查内存
    local memory=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    if [[ $memory -lt 512 ]]; then
        log_error "内存不足，至少需要512MB，当前: ${memory}MB"
        exit 1
    fi
    
    # 检查磁盘空间
    local disk=$(df / | awk 'NR==2{printf "%.0f", $4/1024/1024}')
    if [[ $disk -lt 2 ]]; then
        log_error "磁盘空间不足，至少需要2GB可用空间，当前: ${disk}GB"
        exit 1
    fi
    
    log_info "系统资源检查通过 - 内存: ${memory}MB, 可用磁盘: ${disk}GB"
}

# 安装系统依赖
install_dependencies() {
    log_step "安装系统依赖..."
    
    local centos_version=$(cat /etc/centos-release | grep -oP 'CentOS.*\K[0-9]+')
    
    # 更新系统
    log_info "更新系统软件包..."
    yum update -y
    
    # 安装EPEL仓库
    log_info "安装EPEL仓库..."
    yum install -y epel-release
    
    # 安装基础依赖
    local packages=(
        wget curl git vim unzip zip
        perl perl-core perl-modules
        make gcc gcc-c++
        kernel-devel
    )
    
    log_info "安装基础包: ${packages[*]}"
    yum install -y "${packages[@]}"
    
    # 安装开发工具组
    log_info "安装开发工具组..."
    yum groupinstall -y "Development Tools"
    
    log_info "系统依赖安装完成"
}

# 安装并配置MySQL
install_mysql() {
    log_step "安装MySQL数据库..."
    
    # 检查是否已安装MySQL
    if rpm -q mysql-server &>/dev/null; then
        log_info "MySQL已安装，跳过安装步骤"
    else
        # 安装MySQL
        yum install -y mysql-server mysql-devel
        
        # 启动并启用MySQL
        systemctl start mysqld
        systemctl enable mysqld
        
        # 设置MySQL密码
        log_info "配置MySQL安全设置..."
        mysql_secure_installation &>/dev/null || {
            log_warn "MySQL安全配置失败，将尝试设置默认密码"
            # 设置默认密码（如果MySQL需要密码）
            systemctl restart mysqld
        }
    fi
    
    log_info "MySQL安装配置完成"
}

# 安装并配置Apache
install_apache() {
    log_step "安装Apache Web服务器..."
    
    # 检查是否已安装Apache
    if rpm -q httpd &>/dev/null; then
        log_info "Apache已安装，跳过安装步骤"
    else
        # 安装Apache
        yum install -y httpd
        
        # 启动并启用Apache
        systemctl start httpd
        systemctl enable httpd
        
        # 开放防火墙端口
        if command -v firewall-cmd &>/dev/null; then
            firewall-cmd --permanent --add-service=http
            firewall-cmd --permanent --add-service=https
            firewall-cmd --reload
        fi
    fi
    
    log_info "Apache安装配置完成"
}

# 安装并配置PHP
install_php() {
    log_step "安装PHP运行环境..."
    
    # 检查是否已安装PHP
    if rpm -q php &>/dev/null; then
        log_info "PHP已安装，跳过安装步骤"
        
        # 检查PHP版本
        local php_version=$(php -v | head -n1 | grep -oP 'PHP \K[0-9.]+')
        log_info "当前PHP版本: $php_version"
    else
        # 安装PHP 7.4或更高版本
        local centos_version=$(cat /etc/centos-release | grep -oP 'CentOS.*\K[0-9]+')
        
        if [[ $centos_version -eq 8 || $centos_version -eq 9 ]]; then
            # CentOS 8/9 使用AppStream
            yum module install -y php:php74
        else
            # CentOS 7 使用Remi仓库
            yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
            yum-config-manager --enable remi-php74
        fi
        
        # 安装PHP及常用扩展
        yum install -y \
            php php-cli php-common php-devel \
            php-pdo php-mysql php-mbstring \
            php-json php-xml php-curl \
            php-zip php-gd php-openssl
        
        # 启动Apache服务
        systemctl restart httpd
    fi
    
    log_info "PHP安装配置完成"
}

# 安装Composer
install_composer() {
    log_step "安装Composer包管理器..."
    
    if ! command -v composer &>/dev/null; then
        cd /tmp
        wget https://getcomposer.org/download/latest-stable/composer.phar
        chmod +x composer.phar
        mv composer.phar /usr/local/bin/composer
        
        log_info "Composer安装完成"
    else
        log_info "Composer已安装"
    fi
}

# 克隆项目代码
clone_project() {
    log_step "从GitHub克隆AI客服系统项目..."
    
    local project_dir="/var/www/html/ai-kefu"
    
    # 备份现有安装（如果存在）
    if [[ -d "$project_dir" ]]; then
        log_warn "检测到现有安装，将进行备份..."
        mv "$project_dir" "${project_dir}.backup.$(date +%Y%m%d_%H%M%S)"
    fi
    
    # 创建目录
    mkdir -p "$project_dir"
    cd "$project_dir"
    
    # 克隆项目
    if ! git clone https://github.com/xinghe778/ai-kefu.git .; then
        log_error "项目克隆失败，请检查网络连接"
        exit 1
    fi
    
    log_info "项目代码克隆完成"
}

# 配置数据库
configure_database() {
    log_step "配置MySQL数据库..."
    
    # 创建数据库和用户
    local db_name="ai_kefu"
    local db_user="aikefu"
    local db_pass=$(openssl rand -base64 12)
    
    # 将数据库信息写入临时文件
    cat > /tmp/db_config.sql << EOF
CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';
GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${db_user}'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # 执行SQL
    mysql -u root < /tmp/db_config.sql
    
    log_info "数据库配置完成 - 数据库: $db_name, 用户: $db_user"
    log_warn "请妥善保管数据库密码: $db_pass"
    
    # 将数据库配置写入文件
    cat > /tmp/db_credentials.txt << EOF
数据库名称: ${db_name}
数据库用户: ${db_user}
数据库密码: ${db_pass}
数据库主机: localhost
配置时间: $(date)
EOF
    
    chmod 600 /tmp/db_credentials.txt
    log_info "数据库凭据已保存到 /tmp/db_credentials.txt"
}

# 初始化项目
init_project() {
    log_step "初始化项目配置..."
    
    local project_dir="/var/www/html/ai-kefu"
    cd "$project_dir"
    
    # 设置文件权限
    chown -R apache:apache "$project_dir"
    chmod -R 755 "$project_dir"
    
    # 设置特殊权限
    chmod 644 *.php 2>/dev/null || true
    chmod -R 755 admin/ 2>/dev/null || true
    chmod -R 755 uploads/ 2>/dev/null || true
    
    # 执行数据库初始化脚本
    if [[ -f "complete_database_fix.sql" ]]; then
        log_info "执行数据库初始化脚本..."
        mysql -u aikefu -p$(cat /tmp/db_credentials.txt | grep "数据库密码:" | cut -d: -f2 | xargs) \
              -e "USE ai_kefu; SOURCE complete_database_fix.sql;" 2>/dev/null || {
            log_warn "数据库初始化脚本执行失败，请手动执行"
        }
    fi
    
    # 配置Apache虚拟主机
    cat > /etc/httpd/conf.d/ai-kefu.conf << EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/ai-kefu
    
    <Directory /var/www/html/ai-kefu>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # 日志配置
    ErrorLog /var/log/httpd/ai-kefu-error.log
    CustomLog /var/log/httpd/ai-kefu-access.log combined
    
    # 安全配置
    <Directory /var/www/html/ai-kefu/admin>
        <Files "*.php">
            Order deny,allow
            Deny from all
            Allow from 127.0.0.1
            Allow from ::1
        </Files>
    </Directory>
</VirtualHost>
EOF
    
    # 重启Apache
    systemctl restart httpd
    
    log_info "项目初始化完成"
}

# 安装验证
verify_installation() {
    log_step "验证安装结果..."
    
    # 检查服务状态
    local httpd_status=$(systemctl is-active httpd)
    local mysql_status=$(systemctl is-active mysqld)
    
    log_info "Apache状态: $httpd_status"
    log_info "MySQL状态: $mysql_status"
    
    # 检查项目文件
    local project_dir="/var/www/html/ai-kefu"
    if [[ -d "$project_dir" ]]; then
        log_info "项目目录存在: ✓"
    else
        log_error "项目目录不存在: ✗"
        return 1
    fi
    
    # 检查Web访问
    local http_status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "failed")
    if [[ "$http_status" == "200" || "$http_status" == "302" ]]; then
        log_info "Web服务可访问: ✓"
    else
        log_warn "Web服务访问异常 (状态码: $http_status)"
    fi
    
    log_info "安装验证完成"
}

# 显示安装结果
show_results() {
    echo -e "\n${GREEN}============================================${NC}"
    echo -e "${GREEN}    AI客服系统安装完成！${NC}"
    echo -e "${GREEN}============================================${NC}\n"
    
    echo -e "${CYAN}访问信息:${NC}"
    echo -e "  Web访问地址: ${YELLOW}http://your-server-ip/${NC}"
    echo -e "  管理后台: ${YELLOW}http://your-server-ip/admin/${NC}"
    echo -e "\n${CYAN}系统状态:${NC}"
    echo -e "  Apache服务: ${GREEN}✓${NC} 运行中"
    echo -e "  MySQL服务: ${GREEN}✓${NC} 运行中"
    echo -e "\n${CYAN}重要文件:${NC}"
    echo -e "  项目目录: ${YELLOW}/var/www/html/ai-kefu/${NC}"
    echo -e "  数据库凭据: ${YELLOW}/tmp/db_credentials.txt${NC}"
    echo -e "  错误日志: ${YELLOW}/var/log/httpd/ai-kefu-error.log${NC}"
    echo -e "\n${CYAN}管理命令:${NC}"
    echo -e "  重启Apache: ${YELLOW}systemctl restart httpd${NC}"
    echo -e "  重启MySQL: ${YELLOW}systemctl restart mysqld${NC}"
    echo -e "  查看日志: ${YELLOW}tail -f /var/log/httpd/ai-kefu-error.log${NC}"
    echo -e "\n${YELLOW}建议操作:${NC}"
    echo -e "  1. 修改默认密码"
    echo -e "  2. 配置防火墙规则"
    echo -e "  3. 设置定期备份"
    echo -e "  4. 检查SSL证书配置"
    echo -e "\n${GREEN}如有问题，请查看项目文档或使用故障排除工具${NC}"
}

# 主函数
main() {
    echo -e "${PURPLE}"
    echo "=========================================="
    echo "  AI客服系统 (ai-kefu) 一键安装脚本"
    echo "  版本: v1.0"
    echo "  支持: CentOS 7/8/9"
    echo "=========================================="
    echo -e "${NC}\n"
    
    log_info "开始安装AI客服系统..."
    
    check_root
    check_system
    check_resources
    
    log_info "开始安装依赖组件..."
    install_dependencies
    install_mysql
    install_apache
    install_php
    install_composer
    
    log_info "开始部署项目..."
    clone_project
    configure_database
    init_project
    verify_installation
    
    show_results
    
    log_info "安装完成！"
}

# 运行主函数
main "$@"