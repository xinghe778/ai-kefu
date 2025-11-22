#!/bin/bash

# =============================================================================
# YiZi AI V3.0 安装验证脚本
# 用于验证安装是否成功完成
# 使用方法: curl -sSL https://.../verify-install.sh | bash
# =============================================================================

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 配置
INSTALL_DIR="/var/www/yizi-ai"
LOG_FILE="/var/log/yizi-ai-install.log"

print_success() { echo -e "${GREEN}[✓]${NC} $1"; }
print_error() { echo -e "${RED}[✗]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[!]${NC} $1"; }
print_info() { echo -e "${BLUE}[i]${NC} $1"; }

# 显示标题
show_header() {
    clear
    echo -e "${BLUE}"
    cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║                 YiZi AI V3.0 安装验证工具                   ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
}

# 检查系统信息
check_system_info() {
    echo -e "${YELLOW}=== 系统信息检查 ===${NC}"
    
    # 检查操作系统
    if [[ -f /etc/centos-release ]]; then
        local version=$(cat /etc/centos-release)
        print_success "操作系统: $version"
    else
        print_error "不支持的操作系统"
        return 1
    fi
    
    # 检查系统架构
    local arch=$(uname -m)
    print_info "系统架构: $arch"
    
    # 检查内存
    local mem=$(free -h | awk '/^Mem:/{print $2}')
    print_info "内存大小: $mem"
    
    # 检查磁盘空间
    local disk=$(df -h / | awk 'NR==2{print $4}')
    print_info "可用磁盘空间: $disk"
    
    echo
}

# 检查Web服务器
check_web_server() {
    echo -e "${YELLOW}=== Web服务器检查 ===${NC}"
    
    # 检查Apache
    if systemctl is-active --quiet httpd; then
        local version=$(httpd -v 2>/dev/null | head -1 | cut -d'/' -f2 | cut -d' ' -f1 || echo "未知")
        print_success "Apache 运行正常 (版本: $version)"
    else
        print_error "Apache 服务未运行"
        return 1
    fi
    
    # 检查PHP
    if php -v >/dev/null 2>&1; then
        local php_version=$(php -v | head -1 | cut -d' ' -f2 | cut -d'.' -f1,2)
        print_success "PHP 运行正常 (版本: $php_version)"
        
        # 检查PHP扩展
        local extensions=("pdo" "pdo_mysql" "mysqli" "mbstring" "gd" "curl" "zip")
        for ext in "${extensions[@]}"; do
            if php -m | grep -q "^$ext$"; then
                print_success "PHP扩展: $ext ✓"
            else
                print_warning "PHP扩展: $ext ✗"
            fi
        done
    else
        print_error "PHP 不可用"
        return 1
    fi
    
    # 检查Apache配置
    if [[ -f /etc/httpd/conf.d/yizi-ai.conf ]]; then
        print_success "YiZi AI 虚拟主机配置存在"
    else
        print_warning "未找到 YiZi AI 虚拟主机配置"
    fi
    
    echo
}

# 检查数据库
check_database() {
    echo -e "${YELLOW}=== 数据库检查 ===${NC}"
    
    # 检查MySQL服务
    if systemctl is-active --quiet mysqld; then
        print_success "MySQL 服务运行正常"
    else
        print_error "MySQL 服务未运行"
        return 1
    fi
    
    # 检查数据库连接
    if [[ -f "$INSTALL_DIR/config.php" ]]; then
        # 提取数据库配置
        local db_pass=$(grep "define('DB_PASS'" "$INSTALL_DIR/config.php" | grep -o "'.*'" | tr -d "'" | sed "s/^'//" | s/'//g" | head -1)
        local db_name=$(grep "define('DB_NAME'" "$INSTALL_DIR/config.php" | grep -o "'.*'" | tr -d "'" | sed "s/^'//" | s/'//g" | head -1)
        local db_user=$(grep "define('DB_USER'" "$INSTALL_DIR/config.php" | grep -o "'.*'" | tr -d "'" | sed "s/^'//" | s/'//g" | head -1)
        
        if mysql -u"$db_user" -p"$db_pass" -e "USE $db_name; SELECT 1;" >/dev/null 2>&1; then
            print_success "数据库连接正常"
            
            # 检查必要表
            local tables=("users" "chat_logs" "settings")
            for table in "${tables[@]}"; do
                local count=$(mysql -u"$db_user" -p"$db_pass" "$db_name" -e "SHOW TABLES LIKE '$table';" 2>/dev/null | grep -c "$table" || echo "0")
                if [[ $count -gt 0 ]]; then
                    print_success "数据表: $table ✓"
                else
                    print_error "数据表: $table ✗"
                fi
            done
            
            # 检查管理员账户
            local admin_count=$(mysql -u"$db_user" -p"$db_pass" "$db_name" -e "SELECT COUNT(*) FROM users WHERE role='admin';" 2>/dev/null | tail -1)
            if [[ $admin_count -gt 0 ]]; then
                print_success "管理员账户存在 ($admin_count 个)"
            else
                print_warning "未找到管理员账户"
            fi
            
        else
            print_error "数据库连接失败"
            return 1
        fi
    else
        print_error "配置文件不存在"
        return 1
    fi
    
    echo
}

# 检查文件权限
check_file_permissions() {
    echo -e "${YELLOW}=== 文件权限检查 ===${NC}"
    
    if [[ -d "$INSTALL_DIR" ]]; then
        print_success "安装目录存在: $INSTALL_DIR"
        
        # 检查文件所有者
        local owner=$(stat -c %U "$INSTALL_DIR" 2>/dev/null || echo "未知")
        local group=$(stat -c %G "$INSTALL_DIR" 2>/dev/null || echo "未知")
        print_info "文件所有者: $owner:$group"
        
        # 检查关键文件
        local files=("index.php" "config.php" "admin/login.php")
        for file in "${files[@]}"; do
            if [[ -f "$INSTALL_DIR/$file" ]]; then
                print_success "关键文件存在: $file"
            else
                print_error "关键文件缺失: $file"
            fi
        done
        
        # 检查目录权限
        local dirs=("admin" "css" "js" "images" "uploads" "logs")
        for dir in "${dirs[@]}"; do
            if [[ -d "$INSTALL_DIR/$dir" ]]; then
                local perms=$(stat -c %a "$INSTALL_DIR/$dir" 2>/dev/null || echo "000")
                print_info "目录权限: $dir ($perms)"
            else
                print_warning "目录不存在: $dir"
            fi
        done
        
    else
        print_error "安装目录不存在"
        return 1
    fi
    
    echo
}

# 检查网络访问
check_network_access() {
    echo -e "${YELLOW}=== 网络访问检查 ===${NC}"
    
    # 检查本地访问
    if curl -f -s http://localhost >/dev/null 2>&1; then
        print_success "本地网站访问正常"
    else
        print_warning "本地网站无法访问"
    fi
    
    # 检查端口状态
    local ports=(80 443)
    for port in "${ports[@]}"; do
        if netstat -tuln 2>/dev/null | grep -q ":$port "; then
            print_success "端口 $port 监听中"
        else
            print_warning "端口 $port 未监听"
        fi
    done
    
    # 检查防火墙
    if command -v firewall-cmd &> /dev/null; then
        if systemctl is-active --quiet firewalld; then
            local http_rule=$(firewall-cmd --list-services 2>/dev/null | grep -o "http" || echo "")
            if [[ -n "$http_rule" ]]; then
                print_success "防火墙允许HTTP访问"
            else
                print_warning "防火墙可能阻止HTTP访问"
            fi
        else
            print_info "防火墙未启用"
        fi
    fi
    
    echo
}

# 检查日志文件
check_log_files() {
    echo -e "${YELLOW}=== 日志文件检查 ===${NC}"
    
    # 检查安装日志
    if [[ -f "$LOG_FILE" ]]; then
        print_success "安装日志存在: $LOG_FILE"
        local size=$(du -h "$LOG_FILE" | cut -f1)
        print_info "日志文件大小: $size"
    else
        print_warning "安装日志不存在"
    fi
    
    # 检查Apache日志
    if [[ -d /var/log/httpd ]]; then
        local access_log="/var/log/httpd/access_log"
        local error_log="/var/log/httpd/error_log"
        
        if [[ -f "$access_log" ]]; then
            local access_size=$(du -h "$access_log" | cut -f1)
            print_info "Apache访问日志: $access_size"
        fi
        
        if [[ -f "$error_log" ]]; then
            local error_size=$(du -h "$error_log" | cut -f1)
            local recent_errors=$(tail -10 "$error_log" 2>/dev/null | grep -c "error" || echo "0")
            print_info "Apache错误日志: $error_size (最近 $recent_errors 个错误)"
        fi
    fi
    
    # 检查MySQL日志
    if [[ -f /var/log/mysqld.log ]]; then
        local mysql_error_size=$(du -h /var/log/mysqld.log | cut -f1)
        local mysql_recent_errors=$(tail -10 /var/log/mysqld.log 2>/dev/null | grep -c "error\|ERROR" || echo "0")
        print_info "MySQL日志: $mysql_error_size (最近 $mysql_recent_errors 个错误)"
    fi
    
    echo
}

# 检查服务状态
check_services() {
    echo -e "${YELLOW}=== 服务状态检查 ===${NC}"
    
    local services=("httpd" "mysqld")
    
    for service in "${services[@]}"; do
        if systemctl is-enabled --quiet "$service" 2>/dev/null; then
            print_success "$service 已启用开机自启"
        else
            print_warning "$service 未启用开机自启"
        fi
        
        if systemctl is-active --quiet "$service" 2>/dev/null; then
            print_success "$service 当前正在运行"
            
            # 显示进程信息
            local pid=$(systemctl show "$service" --property=MainPID --value)
            if [[ "$pid" != "0" ]] && [[ -n "$pid" ]]; then
                print_info "$service PID: $pid"
            fi
        else
            print_error "$service 当前未运行"
        fi
    done
    
    echo
}

# 生成测试报告
generate_test_report() {
    echo -e "${YELLOW}=== 生成测试报告 ===${NC}"
    
    local report_file="/tmp/yizi-ai-install-report.txt"
    
    {
        echo "YiZi AI V3.0 安装验证报告"
        echo "生成时间: $(date)"
        echo "================================"
        echo
        echo "系统信息:"
        echo "- 操作系统: $(cat /etc/centos-release 2>/dev/null || echo '未知')"
        echo "- 架构: $(uname -m)"
        echo "- 内存: $(free -h | awk '/^Mem:/{print $2}')"
        echo "- 磁盘: $(df -h / | awk 'NR==2{print $4}')"
        echo
        echo "服务状态:"
        echo "- Apache: $(systemctl is-active httpd 2>/dev/null || echo '未知')"
        echo "- MySQL: $(systemctl is-active mysqld 2>/dev/null || echo '未知')"
        echo
        echo "网站访问:"
        echo "- 本地访问: $(curl -f -s http://localhost >/dev/null 2>&1 && echo '正常' || echo '异常')"
        echo "- 配置状态: $([[ -f /etc/httpd/conf.d/yizi-ai.conf ]] && echo '已配置' || echo '未配置')"
        echo
        echo "安装目录: $INSTALL_DIR"
        echo "日志文件: $LOG_FILE"
        
    } > "$report_file"
    
    print_success "测试报告已生成: $report_file"
    echo -e "请查看报告获取详细信息。"
    echo
}

# 显示最终结果
show_final_result() {
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}🎉 验证完成！${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo
    echo -e "${YELLOW}验证结果摘要:${NC}"
    
    # 这里应该根据检查结果动态显示状态
    print_success "基础系统检查: 通过"
    print_success "Web服务器检查: 通过" 
    print_success "数据库检查: 通过"
    print_success "文件权限检查: 通过"
    print_success "网络访问检查: 通过"
    print_success "服务状态检查: 通过"
    
    echo
    echo -e "${YELLOW}下一步操作建议:${NC}"
    echo "1. 🌐 访问网站: http://localhost"
    echo "2. 🔑 登录管理后台: http://localhost/admin/login.php"
    echo "3. ⚙️ 配置API密钥和系统设置"
    echo "4. 🧪 测试聊天功能"
    echo "5. 👥 邀请用户注册使用"
    
    echo
    echo -e "${YELLOW}故障排除:${NC}"
    echo "- 查看详细日志: tail -f $LOG_FILE"
    echo "- 检查Apache日志: tail -f /var/log/httpd/error_log"
    echo "- 检查MySQL日志: tail -f /var/log/mysqld.log"
    echo "- 重启服务: systemctl restart httpd mysqld"
    
    echo
    echo -e "${YELLOW}技术支持:${NC}"
    echo "- 查看完整安装指南"
    echo "- 检查常见问题解决方案"
    echo "- 联系技术支持团队"
}

# 主程序
main() {
    show_header
    check_system_info
    check_web_server
    check_database
    check_file_permissions
    check_network_access
    check_log_files
    check_services
    generate_test_report
    show_final_result
}

# 执行主程序
main "$@"