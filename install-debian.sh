#!/bin/bash

# AI客服系统 (ai-kefu) 一键安装脚本 - Debian/Ubuntu版
# 版本: v1.0
# 支持系统: Debian 11/12, Ubuntu 20.04/22.04
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
    
    if [[ -f /etc/debian_version ]]; then
        local version=$(cat /etc/debian_version)
        log_info "检测到 Debian $version"
        
        # 检查是否为Ubuntu
        if [[ -f /etc/lsb-release ]]; then
            local ubuntu_version=$(lsb_release -rs 2>/dev/null || echo "unknown")
            log_info "检测到 Ubuntu $ubuntu_version"
        fi
        
        log_info "系统版本支持: Debian $version ✓"
    elif [[ -f /etc/lsb-release ]]; then
        local ubuntu_version=$(lsb_release -rs)
        log_info "检测到 Ubuntu $ubuntu_version"
        
        # 检查Ubuntu版本是否支持
        if [[ "$ubuntu_version" == "20.04" || "$ubuntu_version" == "22.04" || "$ubuntu_version" == "24.04" ]]; then
            log_info "系统版本支持: Ubuntu $ubuntu_version ✓"
        else
            log_warn "非标准Ubuntu版本，安装可能需要手动调整"
        fi
    else
        log_error "仅支持Debian/Ubuntu系统"
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
    
    # 更新系统包列表
    log_info "更新系统软件包列表..."
    apt update
    
    # 升级系统包
    log_info "升级系统软件包..."
    apt upgrade -y
    
    # 安装基础依赖
    local packages=(
        wget
        curl
        git
        vim
        unzip
        zip
        build-essential
        software-properties-common
        apt-transport-https
        ca-certificates
        gnupg
        lsb-release
    )
    
    log_info "安装基础包: ${packages[*]}"
    apt install -y "${packages[@]}"
    
    # 安装PHP依赖
    log_info "安装PHP开发依赖..."
    apt install -y \
        libapache2-dev \
        libssl-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libzip-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        autoconf \
        bison \
        re2c
    
    log_info "系统依赖安装完成"
}

# 安装并配置MySQL
install_mysql() {
    log_step "安装MySQL数据库..."
    
    # 检查是否已安装MySQL
    if dpkg -l | grep -q mysql-server; then
        log_info "MySQL已安装，跳过安装步骤"
    else
        # 安装MySQL
        log_info "安装MySQL服务器..."
        apt install -y mysql-server mysql-client mysql-common
        
        # 启动并启用MySQL
        systemctl start mysql
        systemctl enable mysql
        
        # 设置MySQL安全设置
        log_info "配置MySQL安全设置..."
        mysql_secure_installation &>/dev/null || {
            log_warn "MySQL安全配置失败，将使用默认设置"
            # 设置默认密码（如果MySQL需要密码）
            systemctl restart mysql
        }
    fi
    
    log_info "MySQL安装配置完成"
}

# 安装并配置Apache
install_apache() {
    log_step "安装Apache Web服务器..."
    
    # 检查是否已安装Apache
    if dpkg -l | grep -q apache2; then
        log_info "Apache已安装，跳过安装步骤"
    else
        # 安装Apache
        log_info "安装Apache服务器..."
        apt install -y apache2
        
        # 启用Apache模块
        a2enmod rewrite
        a2enmod ssl
        a2enmod headers
        
        # 启动并启用Apache
        systemctl start apache2
        systemctl enable apache2
    fi
    
    log_info "Apache安装配置完成"
}

# 安装并配置PHP
install_php() {
    log_step "安装PHP..."
    
    # 检查PHP版本
    local php_version="8.1"
    
    # 检查是否已安装PHP
    if php --version &>/dev/null; then
        local current_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        log_info "PHP已安装，版本: $current_version"
        
        if [[ $(echo "$current_version >= 8.0" | bc -l) -eq 1 ]]; then
            log_info "PHP版本满足要求，跳过安装"
            install_php_extensions
            return
        fi
    fi
    
    # 添加PHP仓库
    log_info "添加PHP $php_version 仓库..."
    add-apt-repository -y ppa:ondrej/php
    
    # 更新包列表
    apt update
    
    # 安装PHP和扩展
    install_php_extensions
    
    # 配置PHP
    log_info "配置PHP..."
    local php_ini="/etc/php/$php_version/apache2/php.ini"
    if [[ -f "$php_ini" ]]; then
        # 修改PHP配置
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' "$php_ini"
        sed -i 's/post_max_size = .*/post_max_size = 50M/' "$php_ini"
        sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$php_ini"
        sed -i 's/max_input_time = .*/max_input_time = 300/' "$php_ini"
        sed -i 's/memory_limit = .*/memory_limit = 256M/' "$php_ini"
        sed -i 's/;date.timezone =.*/date.timezone = Asia\/Shanghai/' "$php_ini"
    fi
    
    # 重启Apache使PHP生效
    systemctl restart apache2
    
    log_info "PHP安装配置完成"
}

# 安装PHP扩展
install_php_extensions() {
    log_step "安装PHP扩展..."
    
    local php_version="8.1"
    
    local extensions=(
        php${php_version}
        php${php_version}-apache2
        php${php_version}-cli
        php${php_version}-fpm
        php${php_version}-mysql
        php${php_version}-curl
        php${php_version}-gd
        php${php_version}-mbstring
        php${php_version}-xml
        php${php_version}-zip
        php${php_version}-intl
        php${php_version}-bcmath
        php${php_version}-json
        php${php_version}-openssl
    )
    
    log_info "安装PHP扩展: ${extensions[*]}"
    apt install -y "${extensions[@]}"
    
    log_info "PHP扩展安装完成"
}

# 配置防火墙
configure_firewall() {
    log_step "配置防火墙..."
    
    # 检查是否安装了ufw
    if command -v ufw &>/dev/null; then
        log_info "配置UFW防火墙..."
        
        # 启用UFW
        ufw --force enable
        
        # 允许SSH
        ufw allow ssh
        
        # 允许HTTP
        ufw allow 80/tcp
        
        # 允许HTTPS
        ufw allow 443/tcp
        
        log_info "防火墙配置完成"
    else
        log_warn "UFW未安装，跳过防火墙配置"
    fi
}

# 创建数据库
create_database() {
    log_step "创建数据库..."
    
    # 数据库配置
    local DB_NAME="api"
    local DB_USER="api"
    local DB_PASS=$(openssl rand -base64 12)
    
    # 创建数据库和用户
    mysql -e "
        CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
        GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
        FLUSH PRIVILEGES;
    "
    
    # 保存数据库配置
    local config_file="/root/aikefu_db_config.txt"
    cat > "$config_file" << EOF
# AI客服系统数据库配置
数据库名: $DB_NAME
用户名: $DB_USER
密码: $DB_PASS
主机: localhost
端口: 3306

安装时间: $(date)
EOF
    
    log_info "数据库创建完成"
    log_info "数据库配置已保存到: $config_file"
    log_warn "请妥善保存数据库密码: $DB_PASS"
}

# 部署项目代码
deploy_project() {
    log_step "部署项目代码..."
    
    local INSTALL_DIR="/var/www/html/ai-kefu"
    local PROJECT_REPO="https://github.com/xinghe778/ai-kefu.git"
    
    # 创建安装目录
    mkdir -p "$INSTALL_DIR"
    
    # 如果目录已存在，先备份
    if [[ -d "$INSTALL_DIR/.git" ]]; then
        log_info "检测到现有项目，更新代码..."
        cd "$INSTALL_DIR"
        git pull origin main || git pull origin master
    else
        log_info "从GitHub克隆项目..."
        git clone "$PROJECT_REPO" "$INSTALL_DIR"
        cd "$INSTALL_DIR"
    fi
    
    # 设置文件权限
    log_info "设置文件权限..."
    chown -R www-data:www-data "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    chmod -R 777 "$INSTALL_DIR"/uploads
    chmod -R 777 "$INSTALL_DIR"/admin/uploads
    chmod -R 777 "$INSTALL_DIR"/logs
    
    log_info "项目代码部署完成"
}

# 配置Apache虚拟主机
configure_apache() {
    log_step "配置Apache虚拟主机..."
    
    local INSTALL_DIR="/var/www/html/ai-kefu"
    
    # 创建虚拟主机配置
    local vhost_file="/etc/apache2/sites-available/ai-kefu.conf"
    cat > "$vhost_file" << EOF
<VirtualHost *:80>
    ServerName localhost
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
    a2ensite ai-kefu.conf
    
    # 禁用默认站点（可选）
    # a2dissite 000-default.conf
    
    # 重启Apache
    systemctl reload apache2
    
    log_info "Apache虚拟主机配置完成"
}

# 初始化数据库
init_database() {
    log_step "初始化数据库..."
    
    local INSTALL_DIR="/var/www/html/ai-kefu"
    local db_config="/root/aikefu_db_config.txt"
    
    # 提取数据库配置
    if [[ -f "$db_config" ]]; then
        local DB_PASS=$(grep "密码:" "$db_config" | cut -d':' -f2 | tr -d ' ')
        
        # 运行数据库初始化脚本
        if [[ -f "$INSTALL_DIR/install/database_init.sql" ]]; then
            log_info "执行数据库初始化脚本..."
            mysql -u api -p"$DB_PASS" api < "$INSTALL_DIR/install/database_init.sql"
        fi
        
        log_info "数据库初始化完成"
    else
        log_error "未找到数据库配置文件"
    fi
}

# 验证安装
verify_installation() {
    log_step "验证安装..."
    
    # 检查服务状态
    log_info "检查MySQL状态..."
    systemctl is-active --quiet mysql && log_info "MySQL: 运行中 ✓" || log_error "MySQL: 未运行 ✗"
    
    log_info "检查Apache状态..."
    systemctl is-active --quiet apache2 && log_info "Apache: 运行中 ✓" || log_error "Apache: 未运行 ✗"
    
    # 检查PHP
    log_info "检查PHP版本..."
    php --version | head -n1
    
    # 检查项目文件
    local INSTALL_DIR="/var/www/html/ai-kefu"
    if [[ -d "$INSTALL_DIR" && -f "$INSTALL_DIR/index.php" ]]; then
        log_info "项目文件: 存在 ✓"
    else
        log_error "项目文件: 缺失 ✗"
    fi
    
    log_info "安装验证完成"
}

# 显示安装信息
show_install_info() {
    log_step "安装完成！"
    
    local INSTALL_DIR="/var/www/html/ai-kefu"
    local db_config="/root/aikefu_db_config.txt"
    
    echo
    echo -e "${GREEN}================================${NC}"
    echo -e "${GREEN}  AI客服系统安装完成${NC}"
    echo -e "${GREEN}================================${NC}"
    echo
    echo -e "${CYAN}📁 安装目录:${NC} $INSTALL_DIR"
    echo -e "${CYAN}🌐 访问地址:${NC} http://localhost"
    echo -e "${CYAN}⚙️  管理后台:${NC} http://localhost/admin/"
    
    if [[ -f "$db_config" ]]; then
        echo -e "${CYAN}🗄️  数据库信息:${NC}"
        echo -e "   数据库名: $(grep "数据库名:" "$db_config" | cut -d':' -f2 | tr -d ' ')"
        echo -e "   用户名: $(grep "用户名:" "$db_config" | cut -d':' -f2 | tr -d ' ')"
        echo -e "   密码: $(grep "密码:" "$db_config" | cut -d':' -f2 | tr -d ' ')"
        echo -e "   配置文件: $db_config"
    fi
    
    echo
    echo -e "${YELLOW}📝 重要提醒:${NC}"
    echo -e "1. 请妥善保存数据库配置文件: $db_config"
    echo -e "2. 如需修改数据库密码，请编辑配置文件"
    echo -e "3. 建议定期备份数据库和项目文件"
    echo
    echo -e "${GREEN}安装成功！您可以开始使用AI客服系统了。${NC}"
    echo
}

# 清理函数
cleanup() {
    log_warn "安装过程中发生错误，正在清理..."
    # 可以在这里添加清理逻辑
}

# 设置错误处理
trap cleanup ERR

# 主函数
main() {
    echo
    echo -e "${PURPLE}================================${NC}"
    echo -e "${PURPLE}  AI客服系统一键安装脚本${NC}"
    echo -e "${PURPLE}  Debian/Ubuntu 版本${NC}"
    echo -e "${PURPLE}================================${NC}"
    echo
    
    # 执行安装步骤
    check_root
    check_system
    check_resources
    install_dependencies
    install_mysql
    install_apache
    install_php
    configure_firewall
    create_database
    deploy_project
    configure_apache
    init_database
    verify_installation
    show_install_info
}

# 执行主函数
main "$@"