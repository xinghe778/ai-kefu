#!/bin/bash

# =============================================================================
# AI客服系统 (ai-kefu) 故障诊断和修复脚本 - Debian/Ubuntu版
# 用于诊断常见问题并提供自动修复方案
# 适配项目: https://github.com/xinghe778/ai-kefu
# 使用方法: curl -sSL https://.../troubleshoot-debian.sh | bash
# =============================================================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

# 配置
INSTALL_DIR="/var/www/html/ai-kefu"
LOG_DIR="/var/log"
SERVICE_LOG_DIR="/var/log/apache2"
DB_CONFIG="/root/aikefu_db_config.txt"

print_info() { echo -e "${BLUE}[诊断]${NC} $1"; }
print_success() { echo -e "${GREEN}[成功]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[警告]${NC} $1"; }
print_error() { echo -e "${RED}[错误]${NC} $1"; }
print_fix() { echo -e "${PURPLE}[修复]${NC} $1"; }

# 检查权限
check_permissions() {
    if [[ $EUID -ne 0 ]]; then
        print_error "此脚本需要root权限运行，请使用sudo执行"
        exit 1
    fi
}

# 检查系统类型
check_system() {
    print_info "检查系统兼容性..."
    
    if [[ -f /etc/debian_version ]]; then
        print_success "检测到 Debian 系统: $(cat /etc/debian_version)"
    elif [[ -f /etc/lsb-release ]]; then
        print_success "检测到 Ubuntu 系统: $(lsb_release -rs)"
    else
        print_error "此脚本仅适用于Debian/Ubuntu系统"
        exit 1
    fi
}

# 显示标题
show_header() {
    clear
    echo -e "${BLUE}"
    cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║                AI客服系统故障诊断工具                      ║
║                   Debian/Ubuntu 版本                      ║
║                    智能诊断与自动修复                       ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
}

# 检查并修复MySQL服务
fix_mysql_service() {
    print_info "检查MySQL服务状态..."
    
    if ! systemctl is-active --quiet mysql; then
        print_warning "MySQL服务未运行，尝试启动..."
        
        # 启动MySQL
        if systemctl start mysql; then
            print_success "MySQL服务已启动"
        else
            print_error "MySQL启动失败，检查错误日志:"
            tail -20 /var/log/mysql/error.log 2>/dev/null || echo "无法读取MySQL日志"
            return 1
        fi
    else
        print_success "MySQL服务运行正常"
    fi
    
    # 检查MySQL端口
    if netstat -tuln 2>/dev/null | grep -q ":3306"; then
        print_success "MySQL端口3306监听正常"
    else
        print_warning "MySQL端口3306未监听，可能影响连接"
    fi
    
    # 检查数据库配置
    if [[ -f "$DB_CONFIG" ]]; then
        local DB_PASS=$(grep "密码:" "$DB_CONFIG" | cut -d':' -f2 | tr -d ' ')
        if mysql -u api -p"$DB_PASS" -e "USE api;" &>/dev/null; then
            print_success "数据库连接正常"
        else
            print_warning "数据库连接失败，尝试重新配置..."
            # 可以在这里添加数据库修复逻辑
        fi
    else
        print_warning "数据库配置文件不存在: $DB_CONFIG"
    fi
}

# 检查并修复Apache服务
fix_apache_service() {
    print_info "检查Apache服务状态..."
    
    if ! systemctl is-active --quiet apache2; then
        print_warning "Apache服务未运行，尝试启动..."
        
        if systemctl start apache2; then
            print_success "Apache服务已启动"
        else
            print_error "Apache启动失败，检查错误日志:"
            systemctl status apache2 --no-pager
            return 1
        fi
    else
        print_success "Apache服务运行正常"
    fi
    
    # 检查Apache配置语法
    if apache2ctl configtest >/dev/null 2>&1; then
        print_success "Apache配置语法正确"
    else
        print_warning "Apache配置有语法错误"
        print_fix "检查Apache配置:"
        apache2ctl configtest
    fi
    
    # 检查虚拟主机配置
    if [[ -f "/etc/apache2/sites-available/ai-kefu.conf" ]]; then
        print_success "虚拟主机配置存在"
        
        # 检查站点是否启用
        if a2ensite ai-kefu.conf &>/dev/null; then
            print_success "虚拟主机已启用"
        else
            print_warning "虚拟主机未启用"
            print_fix "启用虚拟主机..."
            a2ensite ai-kefu.conf
            systemctl reload apache2
        fi
    else
        print_error "虚拟主机配置不存在: /etc/apache2/sites-available/ai-kefu.conf"
        return 1
    fi
    
    # 检查必需的Apache模块
    local modules=("rewrite" "ssl" "headers")
    for module in "${modules[@]}"; do
        if apache2ctl -M 2>/dev/null | grep -q "${module}_module"; then
            print_success "Apache模块 $module 已启用"
        else
            print_warning "Apache模块 $module 未启用"
            print_fix "启用Apache模块 $module..."
            a2enmod "$module"
            systemctl reload apache2
        fi
    done
}

# 检查文件权限问题
fix_file_permissions() {
    print_info "检查文件权限..."
    
    if [[ ! -d "$INSTALL_DIR" ]]; then
        print_error "安装目录不存在: $INSTALL_DIR"
        return 1
    fi
    
    # 设置基本权限
    print_fix "设置项目文件权限..."
    chown -R www-data:www-data "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    
    # 设置上传目录权限
    local upload_dirs=("uploads" "admin/uploads" "logs" "cache")
    for dir in "${upload_dirs[@]}"; do
        if [[ -d "$INSTALL_DIR/$dir" ]]; then
            chmod -R 777 "$INSTALL_DIR/$dir"
            print_success "设置 $dir 目录权限为777"
        else
            print_warning "目录不存在: $dir，创建中..."
            mkdir -p "$INSTALL_DIR/$dir"
            chmod 777 "$INSTALL_DIR/$dir"
        fi
    done
    
    # 检查关键文件
    local key_files=("index.php" "config.php")
    for file in "${key_files[@]}"; do
        if [[ -f "$INSTALL_DIR/$file" ]]; then
            print_success "关键文件存在: $file"
        else
            print_warning "关键文件缺失: $file"
        fi
    done
}

# 检查PHP环境
fix_php_environment() {
    print_info "检查PHP环境..."
    
    # 检查PHP是否安装
    if ! command -v php &>/dev/null; then
        print_error "PHP未安装"
        print_fix "安装PHP..."
        apt update
        add-apt-repository -y ppa:ondrej/php
        apt update
        apt install -y php8.1 php8.1-apache2 php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip
        return
    fi
    
    print_success "PHP已安装: $(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
    
    # 检查PHP扩展
    local required_extensions=("mysql" "curl" "gd" "mbstring" "zip" "json" "openssl")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^${ext}$"; then
            print_success "PHP扩展 $ext 已安装"
        else
            print_warning "PHP扩展 $ext 未安装"
            missing_extensions+=("$ext")
        fi
    done
    
    # 安装缺失的扩展
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        print_fix "安装缺失的PHP扩展..."
        apt update
        apt install -y php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-json php8.1-openssl
        systemctl reload apache2
    fi
    
    # 检查PHP配置
    local php_ini="/etc/php/8.1/apache2/php.ini"
    if [[ -f "$php_ini" ]]; then
        print_success "PHP配置文件存在"
        
        # 检查关键配置
        local memory_limit=$(php -r "echo ini_get('memory_limit');")
        local upload_max_filesize=$(php -r "echo ini_get('upload_max_filesize');")
        local post_max_size=$(php -r "echo ini_get('post_max_size');")
        
        print_info "PHP配置检查:"
        echo "  memory_limit: $memory_limit"
        echo "  upload_max_filesize: $upload_max_filesize"
        echo "  post_max_size: $post_max_size"
        
        # 如果配置有问题，可以在这里修复
    fi
}

# 检查网络和端口
check_network() {
    print_info "检查网络和端口..."
    
    # 检查80端口
    if netstat -tuln 2>/dev/null | grep -q ":80 "; then
        print_success "HTTP端口(80)正在监听"
    else
        print_error "HTTP端口(80)未监听，Apache可能未启动"
    fi
    
    # 检查3306端口
    if netstat -tuln 2>/dev/null | grep -q ":3306 "; then
        print_success "MySQL端口(3306)正在监听"
    else
        print_warning "MySQL端口(3306)未监听，可能影响数据库连接"
    fi
    
    # 测试本地连接
    if command -v curl &>/dev/null; then
        local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
        if [[ "$response" == "200" ]]; then
            print_success "Web访问测试成功 (HTTP $response)"
        else
            print_error "Web访问测试失败 (HTTP $response)"
        fi
    else
        print_warning "curl未安装，跳过Web访问测试"
    fi
}

# 检查日志文件
check_logs() {
    print_info "检查系统日志..."
    
    # 检查Apache错误日志
    if [[ -f "$SERVICE_LOG_DIR/error.log" ]]; then
        local error_count=$(tail -100 "$SERVICE_LOG_DIR/error.log" 2>/dev/null | grep -c "error\|Error\|ERROR" || echo "0")
        print_info "Apache错误日志 (最近100行): $error_count 个错误"
        
        if [[ $error_count -gt 0 ]]; then
            print_warning "最近的Apache错误:"
            tail -5 "$SERVICE_LOG_DIR/error.log" | grep "error\|Error\|ERROR" 2>/dev/null || true
        fi
    else
        print_warning "Apache错误日志不存在"
    fi
    
    # 检查MySQL错误日志
    if [[ -f "/var/log/mysql/error.log" ]]; then
        local mysql_error_count=$(tail -100 "/var/log/mysql/error.log" 2>/dev/null | grep -c "error\|Error\|ERROR" || echo "0")
        print_info "MySQL错误日志 (最近100行): $mysql_error_count 个错误"
        
        if [[ $mysql_error_count -gt 0 ]]; then
            print_warning "最近的MySQL错误:"
            tail -5 "/var/log/mysql/error.log" | grep "error\|Error\|ERROR" 2>/dev/null || true
        fi
    else
        print_warning "MySQL错误日志不存在"
    fi
}

# 重新初始化数据库
reinit_database() {
    print_info "重新初始化数据库..."
    
    if [[ ! -f "$INSTALL_DIR/install/database_init.sql" ]]; then
        print_error "数据库初始化脚本不存在"
        return 1
    fi
    
    if [[ ! -f "$DB_CONFIG" ]]; then
        print_error "数据库配置文件不存在"
        return 1
    fi
    
    local DB_PASS=$(grep "密码:" "$DB_CONFIG" | cut -d':' -f2 | tr -d ' ')
    
    print_warning "将重新创建数据库，这会清除现有数据！"
    read -p "是否继续？(y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_info "重新创建数据库..."
        mysql -u api -p"$DB_PASS" -e "DROP DATABASE IF EXISTS api; CREATE DATABASE api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        
        print_info "执行数据库初始化脚本..."
        mysql -u api -p"$DB_PASS" api < "$INSTALL_DIR/install/database_init.sql"
        
        print_success "数据库重新初始化完成"
    else
        print_info "取消数据库重新初始化"
    fi
}

# 重启所有服务
restart_services() {
    print_info "重启所有服务..."
    
    print_fix "重启MySQL..."
    systemctl restart mysql
    sleep 2
    
    print_fix "重启Apache..."
    systemctl restart apache2
    sleep 2
    
    print_success "所有服务重启完成"
}

# 生成诊断报告
generate_report() {
    local report_file="/tmp/aikefu_diagnostic_report.txt"
    
    print_info "生成诊断报告..."
    
    cat > "$report_file" << EOF
AI客服系统诊断报告
生成时间: $(date)
系统信息: $(uname -a)

服务状态:
- MySQL: $(systemctl is-active mysql 2>/dev/null || echo "未知")
- Apache: $(systemctl is-active apache2 2>/dev/null || echo "未知")

文件检查:
- 安装目录: $([[ -d "$INSTALL_DIR" ]] && echo "存在" || echo "不存在")
- 数据库配置: $([[ -f "$DB_CONFIG" ]] && echo "存在" || echo "不存在")
- 虚拟主机配置: $([[ -f "/etc/apache2/sites-available/ai-kefu.conf" ]] && echo "存在" || echo "不存在")

网络检查:
- HTTP端口(80): $(netstat -tuln 2>/dev/null | grep -q ":80 " && echo "监听" || echo "未监听")
- MySQL端口(3306): $(netstat -tuln 2>/dev/null | grep -q ":3306 " && echo "监听" || echo "未监听")

系统资源:
- 内存: $(free -m | awk 'NR==2{print $2}')MB
- 可用磁盘: $(df / | awk 'NR==2{printf "%.1f", $4/1024/1024}')GB

详细日志请查看:
- Apache: $SERVICE_LOG_DIR/
- MySQL: /var/log/mysql/
- 系统: /var/log/syslog
EOF
    
    print_success "诊断报告已保存到: $report_file"
    echo -e "${BLUE}报告内容:${NC}"
    cat "$report_file"
}

# 主菜单
show_menu() {
    echo
    echo -e "${BLUE}请选择诊断操作:${NC}"
    echo "1. 全面诊断"
    echo "2. 修复MySQL服务"
    echo "3. 修复Apache服务"
    echo "4. 修复文件权限"
    echo "5. 修复PHP环境"
    echo "6. 检查网络端口"
    echo "7. 检查日志文件"
    echo "8. 重新初始化数据库"
    echo "9. 重启所有服务"
    echo "10. 生成诊断报告"
    echo "0. 退出"
    echo
    read -p "请输入选项 [0-10]: " choice
}

# 全面诊断
full_diagnostic() {
    print_info "开始全面诊断..."
    fix_mysql_service
    fix_apache_service
    fix_file_permissions
    fix_php_environment
    check_network
    check_logs
    print_success "全面诊断完成"
}

# 主函数
main() {
    show_header
    check_permissions
    check_system
    
    while true; do
        show_menu
        case $choice in
            1) full_diagnostic ;;
            2) fix_mysql_service ;;
            3) fix_apache_service ;;
            4) fix_file_permissions ;;
            5) fix_php_environment ;;
            6) check_network ;;
            7) check_logs ;;
            8) reinit_database ;;
            9) restart_services ;;
            10) generate_report ;;
            0) print_info "退出诊断工具"; break ;;
            *) print_error "无效选项，请重新选择" ;;
        esac
        
        echo
        read -p "按回车键继续..."
        show_header
    done
}

# 如果直接运行脚本，显示菜单
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi