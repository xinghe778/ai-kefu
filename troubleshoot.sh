#!/bin/bash

# =============================================================================
# YiZi AI V3.0 故障诊断和修复脚本
# 用于诊断常见问题并提供自动修复方案
# 使用方法: curl -sSL https://.../troubleshoot.sh | bash
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
INSTALL_DIR="/var/www/yizi-ai"
LOG_DIR="/var/log"
SERVICE_LOG_DIR="/var/log/httpd"

print_info() { echo -e "${BLUE}[诊断]${NC} $1"; }
print_success() { echo -e "${GREEN}[成功]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[警告]${NC} $1"; }
print_error() { echo -e "${RED}[错误]${NC} $1"; }
print_fix() { echo -e "${PURPLE}[修复]${NC} $1"; }

# 显示标题
show_header() {
    clear
    echo -e "${BLUE}"
    cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║                 YiZi AI V3.0 故障诊断工具                  ║
║                    智能诊断与自动修复                       ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
}

# 检查并修复MySQL服务
fix_mysql_service() {
    print_info "检查MySQL服务状态..."
    
    if ! systemctl is-active --quiet mysqld; then
        print_warning "MySQL服务未运行，尝试启动..."
        
        # 启动MySQL
        if systemctl start mysqld; then
            print_success "MySQL服务已启动"
        else
            print_error "MySQL启动失败，检查错误日志:"
            tail -20 /var/log/mysqld.log 2>/dev/null || echo "无法读取MySQL日志"
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
}

# 检查并修复Apache服务
fix_apache_service() {
    print_info "检查Apache服务状态..."
    
    if ! systemctl is-active --quiet httpd; then
        print_warning "Apache服务未运行，尝试启动..."
        
        if systemctl start httpd; then
            print_success "Apache服务已启动"
        else
            print_error "Apache启动失败，检查错误日志:"
            systemctl status httpd --no-pager
            return 1
        fi
    else
        print_success "Apache服务运行正常"
    fi
    
    # 检查Apache配置语法
    if httpd -t >/dev/null 2>&1; then
        print_success "Apache配置语法正确"
    else
        print_warning "Apache配置有语法错误"
        print_fix "检查Apache配置:"
        httpd -t
    fi
}

# 检查文件权限问题
fix_file_permissions() {
    print_info "检查文件权限..."
    
    if [[ ! -d "$INSTALL_DIR" ]]; then
        print_error "安装目录不存在: $INSTALL_DIR"
        return 1
    fi
    
    # 设置正确的文件权限
    print_fix "设置文件权限..."
    
    # 设置目录权限
    find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
    
    # 设置文件权限
    find "$INSTALL_DIR" -type f -exec chmod 644 {} \;
    
    # 设置特殊权限
    chmod -R 777 "$INSTALL_DIR/uploads" 2>/dev/null || true
    chmod -R 777 "$INSTALL_DIR/logs" 2>/dev/null || true
    
    # 设置所有者
    chown -R apache:apache "$INSTALL_DIR" 2>/dev/null || true
    
    print_success "文件权限已修复"
}

# 修复数据库连接问题
fix_database_connection() {
    print_info "检查数据库连接..."
    
    if [[ ! -f "$INSTALL_DIR/config.php" ]]; then
        print_error "配置文件不存在: $INSTALL_DIR/config.php"
        return 1
    fi
    
    # 检查配置文件权限
    if [[ -f "$INSTALL_DIR/config.php" ]]; then
        local config_perms=$(stat -c %a "$INSTALL_DIR/config.php" 2>/dev/null || echo "000")
        if [[ "$config_perms" == "644" ]] || [[ "$config_perms" == "600" ]]; then
            print_success "配置文件权限正常"
        else
            print_fix "修复配置文件权限..."
            chmod 644 "$INSTALL_DIR/config.php"
        fi
    fi
    
    # 尝试数据库连接测试
    print_fix "测试数据库连接..."
    
    # 这里需要从配置文件中提取数据库信息进行测试
    # 简化版测试
    if mysql -e "SELECT 1;" >/dev/null 2>&1; then
        print_success "MySQL连接正常"
    else
        print_warning "MySQL连接可能有问题，请检查用户权限"
    fi
}

# 修复PHP配置问题
fix_php_configuration() {
    print_info "检查PHP配置..."
    
    # 检查PHP版本
    if ! php -v >/dev/null 2>&1; then
        print_error "PHP不可用"
        return 1
    fi
    
    local php_version=$(php -v | head -1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    print_info "PHP版本: $php_version"
    
    # 检查必要的PHP扩展
    local required_extensions=("pdo" "pdo_mysql" "mysqli" "mbstring" "gd" "curl")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        print_warning "缺少PHP扩展: ${missing_extensions[*]}"
        print_fix "建议安装缺少的扩展:"
        echo "yum install -y php-${missing_extensions[0]} php-${missing_extensions[1]}"
    else
        print_success "所有必要的PHP扩展都存在"
    fi
    
    # 检查PHP配置
    local upload_max=$(php -r "echo ini_get('upload_max_filesize');")
    local post_max=$(php -r "echo ini_get('post_max_size');")
    local max_exec=$(php -r "echo ini_get('max_execution_time');")
    
    print_info "PHP配置: 上传限制=$upload_max, POST限制=$post_max, 执行时间=$max_exec"
}

# 检查端口和防火墙
check_port_and_firewall() {
    print_info "检查端口和防火墙..."
    
    # 检查80端口
    if netstat -tuln 2>/dev/null | grep -q ":80 "; then
        print_success "端口80 (HTTP) 正在监听"
    else
        print_warning "端口80未监听"
        print_fix "检查Apache是否正确配置和启动"
    fi
    
    # 检查443端口（SSL）
    if netstat -tuln 2>/dev/null | grep -q ":443 "; then
        print_success "端口443 (HTTPS) 正在监听"
    else
        print_info "端口443未监听（正常，如果未配置SSL）"
    fi
    
    # 检查防火墙状态
    if command -v firewall-cmd &> /dev/null; then
        if systemctl is-active --quiet firewalld; then
            print_info "防火墙已启用"
            
            # 检查HTTP服务是否允许
            if firewall-cmd --list-services 2>/dev/null | grep -q "http"; then
                print_success "防火墙允许HTTP访问"
            else
                print_fix "添加HTTP防火墙规则..."
                firewall-cmd --permanent --add-service=http
                firewall-cmd --reload
                print_success "已添加HTTP防火墙规则"
            fi
        else
            print_info "防火墙未启用"
        fi
    else
        print_info "防火墙命令不可用"
    fi
}

# 检查磁盘空间
check_disk_space() {
    print_info "检查磁盘空间..."
    
    local root_usage=$(df / | awk 'NR==2{print $5}' | sed 's/%//')
    local root_available=$(df / | awk 'NR==2{print $4}')
    
    print_info "根目录使用率: ${root_usage}%"
    print_info "可用空间: $(echo $root_available | awk '{printf "%.1f GB\n", $1/1024/1024}')"
    
    if [[ $root_usage -gt 90 ]]; then
        print_error "磁盘空间不足！使用率: ${root_usage}%"
        print_fix "建议清理日志文件或扩展磁盘空间"
    elif [[ $root_usage -gt 80 ]]; then
        print_warning "磁盘空间较少。使用率: ${root_usage}%"
    else
        print_success "磁盘空间充足"
    fi
}

# 检查内存使用
check_memory_usage() {
    print_info "检查内存使用..."
    
    local mem_total=$(free -m | awk 'NR==2{print $2}')
    local mem_used=$(free -m | awk 'NR==2{print $3}')
    local mem_free=$(free -m | awk 'NR==2{print $4}')
    local mem_usage=$((mem_used * 100 / mem_total))
    
    print_info "内存总大小: ${mem_total}MB"
    print_info "已使用: ${mem_used}MB"
    print_info "可用: ${mem_free}MB"
    print_info "使用率: ${mem_usage}%"
    
    if [[ $mem_usage -gt 90 ]]; then
        print_error "内存使用率过高！"
    elif [[ $mem_usage -gt 80 ]]; then
        print_warning "内存使用率较高"
    else
        print_success "内存使用正常"
    fi
}

# 检查日志文件
check_log_files() {
    print_info "检查日志文件..."
    
    # 检查错误日志
    local error_log="$SERVICE_LOG_DIR/error_log"
    if [[ -f "$error_log" ]]; then
        local recent_errors=$(tail -50 "$error_log" 2>/dev/null | grep -c "error\|Error\|ERROR" || echo "0")
        print_info "Apache错误日志: 最近 ${recent_errors} 个错误"
        
        if [[ $recent_errors -gt 0 ]]; then
            print_fix "最近的错误:"
            tail -5 "$error_log" 2>/dev/null | grep "error\|Error\|ERROR" | tail -3
        fi
    else
        print_warning "未找到Apache错误日志"
    fi
    
    # 检查MySQL日志
    local mysql_log="/var/log/mysqld.log"
    if [[ -f "$mysql_log" ]]; then
        local mysql_errors=$(tail -50 "$mysql_log" 2>/dev/null | grep -c "error\|Error\|ERROR" || echo "0")
        print_info "MySQL日志: 最近 ${mysql_errors} 个错误"
    else
        print_warning "未找到MySQL日志"
    fi
    
    # 检查应用日志
    local app_log="$LOG_DIR/yizi-ai-install.log"
    if [[ -f "$app_log" ]]; then
        print_success "应用安装日志存在"
    else
        print_warning "未找到应用安装日志"
    fi
}

# 检查SELinux状态
check_selinux() {
    print_info "检查SELinux状态..."
    
    if command -v getenforce &> /dev/null; then
        local selinux_status=$(getenforce)
        print_info "SELinux状态: $selinux_status"
        
        if [[ "$selinux_status" == "Enforcing" ]]; then
            print_warning "SELinux处于强制模式，可能会影响Web应用"
            print_fix "如果遇到权限问题，可以临时设置:"
            echo "setenforce 0"
            echo "或者配置正确的SELinux策略"
        elif [[ "$selinux_status" == "Permissive" ]]; then
            print_info "SELinux处于宽容模式"
        else
            print_info "SELinux已禁用"
        fi
    else
        print_info "SELinux未安装或不可用"
    fi
}

# 自动修复常见问题
auto_fix_common_issues() {
    print_info "开始自动修复常见问题..."
    
    # 重启服务
    print_fix "重启MySQL服务..."
    systemctl restart mysqld || print_warning "MySQL重启失败"
    
    print_fix "重启Apache服务..."
    systemctl restart httpd || print_warning "Apache重启失败"
    
    # 清理临时文件
    print_fix "清理临时文件..."
    find /tmp -name "*yizi*" -type f -delete 2>/dev/null || true
    
    # 检查并修复Apache配置
    print_fix "检查Apache配置..."
    if httpd -t >/dev/null 2>&1; then
        print_success "Apache配置语法正确"
    else
        print_error "Apache配置语法错误，请手动检查"
    fi
}

# 生成诊断报告
generate_diagnostic_report() {
    print_info "生成诊断报告..."
    
    local report_file="/tmp/yizi-ai-diagnostic-report.txt"
    
    {
        echo "YiZi AI V3.0 故障诊断报告"
        echo "生成时间: $(date)"
        echo "=========================================="
        echo
        
        echo "系统信息:"
        echo "- 操作系统: $(cat /etc/centos-release 2>/dev/null || echo '未知')"
        echo "- 内核版本: $(uname -r)"
        echo "- 架构: $(uname -m)"
        echo
        
        echo "服务状态:"
        echo "- Apache: $(systemctl is-active httpd 2>/dev/null || echo '未知')"
        echo "- MySQL: $(systemctl is-active mysqld 2>/dev/null || echo '未知')"
        echo "- 防火墙: $(systemctl is-active firewalld 2>/dev/null || echo '未知')"
        echo
        
        echo "端口状态:"
        netstat -tuln 2>/dev/null | grep -E ":80|:443|:3306" || echo "无法获取端口信息"
        echo
        
        echo "磁盘使用:"
        df -h / 2>/dev/null || echo "无法获取磁盘信息"
        echo
        
        echo "内存使用:"
        free -h 2>/dev/null || echo "无法获取内存信息"
        echo
        
        echo "PHP信息:"
        php -v 2>/dev/null | head -2 || echo "PHP不可用"
        echo
        
        echo "最近错误 (Apache):"
        tail -10 /var/log/httpd/error_log 2>/dev/null | grep -i error || echo "无错误信息"
        echo
        
        echo "最近错误 (MySQL):"
        tail -10 /var/log/mysqld.log 2>/dev/null | grep -i error || echo "无错误信息"
        
    } > "$report_file"
    
    print_success "诊断报告已生成: $report_file"
}

# 显示最终建议
show_final_recommendations() {
    echo
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}🎯 诊断完成！${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo
    echo -e "${YELLOW}快速检查清单:${NC}"
    echo "✅ Web服务: $(systemctl is-active httpd 2>/dev/null && echo '运行中' || echo '停止')"
    echo "✅ 数据库: $(systemctl is-active mysqld 2>/dev/null && echo '运行中' || echo '停止')"
    echo "✅ 端口80: $(netstat -tuln 2>/dev/null | grep -q ':80' && echo '监听中' || echo '未监听')"
    echo "✅ PHP扩展: $(php -m | grep -c 'pdo_mysql') 个核心扩展"
    echo
    echo -e "${YELLOW}如果仍然遇到问题:${NC}"
    echo "1. 📋 查看完整诊断报告: cat $report_file"
    echo "2. 🔍 检查错误日志: tail -f /var/log/httpd/error_log"
    echo "3. 🌐 测试网站访问: curl http://localhost"
    echo "4. 🔄 重启所有服务: systemctl restart httpd mysqld"
    echo "5. 💾 检查磁盘空间: df -h"
    echo
    echo -e "${YELLOW}常用故障排除命令:${NC}"
    echo "• 查看Apache状态: systemctl status httpd"
    echo "• 查看MySQL状态: systemctl status mysqld"
    echo "• 测试Apache配置: httpd -t"
    echo "• 检查网络连接: netstat -tuln | grep :80"
    echo "• 查看系统日志: journalctl -xe"
    echo
    echo -e "${YELLOW}联系技术支持时，请提供:${NC}"
    echo "• 诊断报告文件: $report_file"
    echo "• 操作系统版本: $(cat /etc/centos-release 2>/dev/null || echo '未知')"
    echo "• PHP版本: $(php -v 2>/dev/null | head -1 | cut -d' ' -f2 || echo '未知')"
    echo
}

# 主程序
main() {
    show_header
    
    # 执行各项检查
    fix_mysql_service
    fix_apache_service
    fix_file_permissions
    fix_database_connection
    fix_php_configuration
    check_port_and_firewall
    check_disk_space
    check_memory_usage
    check_log_files
    check_selinux
    
    # 自动修复
    auto_fix_common_issues
    
    # 生成报告
    generate_diagnostic_report
    
    # 显示建议
    show_final_recommendations
}

# 执行主程序
main "$@" 2>&1 | tee /var/log/yizi-ai-troubleshoot.log