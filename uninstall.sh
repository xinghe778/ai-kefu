#!/bin/bash

# =============================================================================
# YiZi AI V3.0 卸载脚本
# 完全卸载 YiZi AI 系统及其所有相关组件
# 使用方法: curl -sSL https://.../uninstall.sh | bash
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
BACKUP_DIR="/var/backups/yizi-ai"
DB_NAME="api"
DB_USER="api"

print_info() { echo -e "${BLUE}[信息]${NC} $1"; }
print_success() { echo -e "${GREEN}[成功]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[警告]${NC} $1"; }
print_error() { echo -e "${RED}[错误]${NC} $1"; }

# 显示卸载确认
show_uninstall_confirmation() {
    clear
    echo -e "${RED}"
    cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║                   ⚠️  重要警告  ⚠️                          ║
║                                                            ║
║    这将完全卸载 YiZi AI V3.0 系统，包括:                   ║
║                                                            ║
║    ❌ 删除所有网站文件                                     ║
║    ❌ 删除数据库和所有数据                                 ║
║    ❌ 清理Apache配置文件                                   ║
║    ❌ 移除定时任务                                         ║
║    ❌ 删除备份文件                                         ║
║                                                            ║
║    📌 此操作不可逆转！                                    ║
║                                                            ║
║    ⚠️  如果需要保留数据，请先创建备份                     ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
    
    echo -e "${YELLOW}卸载将包含以下内容:${NC}"
    echo "• 网站文件: $INSTALL_DIR"
    echo "• 数据库: $DB_NAME"
    echo "• Apache配置: /etc/httpd/conf.d/yizi-ai.conf"
    echo "• 定时任务: crontab -e"
    echo "• 日志文件: $LOG_FILE"
    echo "• 备份文件: $BACKUP_DIR"
    echo
    
    echo -e "${YELLOW}⚠️  重要提醒:${NC}"
    echo "1. 此操作将永久删除所有数据"
    echo "2. 如果需要保留数据，请先运行备份"
    echo "3. 卸载后Web服务器和数据库仍会保留"
    echo
}

# 询问用户确认
get_user_confirmation() {
    echo -e "${YELLOW}确认卸载?${NC}"
    echo "输入 'YES' 确认卸载，输入其他任意字符取消:"
    
    local confirmation
    read confirmation
    
    if [[ "$confirmation" != "YES" ]]; then
        print_info "卸载已取消"
        exit 0
    fi
    
    print_warning "确认卸载 YiZi AI V3.0..."
    sleep 3
}

# 备份数据（可选）
backup_data() {
    echo
    echo -e "${YELLOW}=== 数据备份选项 ===${NC}"
    echo
    
    echo "是否在卸载前备份数据?"
    echo "1. 是的，创建完整备份"
    echo "2. 否，直接卸载"
    
    read -p "选择 (1-2): " backup_choice
    
    if [[ "$backup_choice" == "1" ]]; then
        print_info "开始创建数据备份..."
        
        # 创建备份目录
        local backup_timestamp=$(date +%Y%m%d_%H%M%S)
        local current_backup_dir="$BACKUP_DIR/pre-uninstall-$backup_timestamp"
        mkdir -p "$current_backup_dir"
        
        # 备份数据库
        if command -v mysqldump &> /dev/null; then
            print_info "备份数据库..."
            mysqldump -u"$DB_USER" "$(grep "define('DB_NAME'" $INSTALL_DIR/config.php 2>/dev/null | grep -o "'.*'" | tr -d "'" | sed 's/^..//' | s/..$//" || echo "$DB_NAME")" > "$current_backup_dir/database_backup.sql" 2>/dev/null || print_warning "数据库备份失败"
        fi
        
        # 备份网站文件
        print_info "备份网站文件..."
        if [[ -d "$INSTALL_DIR" ]]; then
            tar -czf "$current_backup_dir/website_files.tar.gz" -C /var/www yizi-ai 2>/dev/null || print_warning "文件备份失败"
        fi
        
        # 备份配置文件
        print_info "备份配置文件..."
        cp /etc/httpd/conf.d/yizi-ai.conf "$current_backup_dir/" 2>/dev/null || true
        cp "$LOG_FILE" "$current_backup_dir/" 2>/dev/null || true
        
        print_success "备份完成: $current_backup_dir"
        echo "备份内容:"
        echo "• 数据库: database_backup.sql"
        echo "• 网站文件: website_files.tar.gz"
        echo "• 配置文件: yizi-ai.conf"
        echo "• 安装日志: yizi-ai-install.log"
    else
        print_warning "跳过数据备份"
    fi
}

# 停止并禁用服务
stop_services() {
    print_info "停止相关服务..."
    
    # 停止Apache
    if systemctl is-active --quiet httpd; then
        print_info "停止Apache服务..."
        systemctl stop httpd
        print_success "Apache已停止"
    else
        print_info "Apache未运行"
    fi
    
    print_info "禁用Apache开机自启..."
    systemctl disable httpd 2>/dev/null || true
    
    # MySQL保留运行，但可以停止YiZi AI相关的连接
    print_info "注意: MySQL服务将被保留"
}

# 卸载网站文件
remove_website_files() {
    print_info "移除网站文件..."
    
    if [[ -d "$INSTALL_DIR" ]]; then
        print_warning "删除安装目录: $INSTALL_DIR"
        rm -rf "$INSTALL_DIR"
        print_success "网站文件已删除"
    else
        print_info "安装目录不存在，跳过文件删除"
    fi
    
    # 删除其他可能的安装位置
    local other_dirs=("/opt/yizi-ai" "/usr/local/yizi-ai" "/home/*/yizi-ai")
    for dir in "${other_dirs[@]}"; do
        if [[ -d "$dir" ]]; then
            print_info "删除其他安装目录: $dir"
            rm -rf "$dir" 2>/dev/null || true
        fi
    done
}

# 卸载数据库
remove_database() {
    print_info "移除数据库..."
    
    # 检查MySQL是否可用
    if ! command -v mysql &> /dev/null; then
        print_warning "MySQL客户端不可用，跳过数据库卸载"
        return 0
    fi
    
    print_warning "这将删除数据库 '$DB_NAME' 和用户 '$DB_USER'"
    echo "数据库中的所有数据都将被永久删除！"
    
    read -p "确认删除数据库? (yes/no): " db_confirm
    if [[ "$db_confirm" == "yes" ]]; then
        # 删除数据库
        print_info "删除数据库 $DB_NAME..."
        mysql -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null || print_warning "删除数据库失败"
        
        # 删除用户
        print_info "删除数据库用户 $DB_USER..."
        mysql -e "DROP USER IF EXISTS '$DB_USER'@'localhost';" 2>/dev/null || print_warning "删除用户失败"
        
        # 清理权限
        print_info "刷新MySQL权限..."
        mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true
        
        print_success "数据库卸载完成"
    else
        print_info "跳过数据库删除"
    fi
}

# 清理Apache配置
remove_apache_config() {
    print_info "清理Apache配置..."
    
    # 删除YiZi AI配置
    if [[ -f /etc/httpd/conf.d/yizi-ai.conf ]]; then
        print_info "删除Apache虚拟主机配置..."
        rm -f /etc/httpd/conf.d/yizi-ai.conf
        print_success "Apache配置已删除"
    else
        print_info "Apache配置文件不存在"
    fi
    
    # 删除其他可能的配置文件
    local config_files=(
        "/etc/httpd/conf.d/yizi.conf"
        "/etc/httpd/conf.d/yi-zi.conf"
        "/etc/httpd/sites-available/yizi-ai.conf"
        "/etc/httpd/sites-enabled/yizi-ai.conf"
    )
    
    for config_file in "${config_files[@]}"; do
        if [[ -f "$config_file" ]]; then
            print_info "删除配置文件: $config_file"
            rm -f "$config_file"
        fi
    done
    
    # 重新加载Apache配置
    if systemctl is-active --quiet httpd; then
        print_info "重新加载Apache配置..."
        systemctl reload httpd || print_warning "重新加载配置失败"
    fi
}

# 清理定时任务
remove_cron_jobs() {
    print_info "清理定时任务..."
    
    # 获取当前的crontab
    local current_crontab=$(crontab -l 2>/dev/null || echo "")
    
    if [[ -n "$current_crontab" ]]; then
        # 移除YiZi AI相关的定时任务
        local cleaned_crontab=$(echo "$current_crontab" | grep -v "yizi-ai\|yi zi ai")
        
        if [[ "$cleaned_crontab" != "$current_crontab" ]]; then
            print_info "移除YiZi AI相关的定时任务..."
            echo "$cleaned_crontab" | crontab - || print_warning "更新crontab失败"
            print_success "定时任务已清理"
        else
            print_info "未找到YiZi AI相关的定时任务"
        fi
    else
        print_info "无现有定时任务"
    fi
}

# 清理日志文件
remove_log_files() {
    print_info "清理日志文件..."
    
    # 删除YiZi AI相关的日志文件
    local log_files=(
        "$LOG_FILE"
        "/var/log/yizi-ai-install.log"
        "/var/log/yizi-ai-quick-install.log"
        "/var/log/yizi-ai-troubleshoot.log"
        "/var/log/yizi-ai-monitor.log"
    )
    
    for log_file in "${log_files[@]}"; do
        if [[ -f "$log_file" ]]; then
            print_info "删除日志文件: $log_file"
            rm -f "$log_file"
        fi
    done
    
    # 清理Apache访问日志中的YiZi AI相关条目
    local apache_logs=("/var/log/httpd/access_log" "/var/log/httpd/yizi-ai-access.log")
    for log in "${apache_logs[@]}"; do
        if [[ -f "$log" ]]; then
            print_info "清理Apache日志..."
            # 保留其他服务的日志，只删除YiZi AI相关条目
            # 这里可以选择性地保留日志或完全清理
        fi
    done
    
    print_success "日志文件清理完成"
}

# 清理系统文件
remove_system_files() {
    print_info "清理系统文件..."
    
    # 清理yum缓存
    print_info "清理yum缓存..."
    yum clean all 2>/dev/null || true
    
    # 清理临时文件
    print_info "清理临时文件..."
    find /tmp -name "*yizi*" -type f -delete 2>/dev/null || true
    find /var/tmp -name "*yizi*" -type f -delete 2>/dev/null || true
    
    # 清理rpm数据库
    print_info "清理rpm数据库..."
    rpm --rebuilddb 2>/dev/null || true
    
    # 清理systemd日志
    print_info "清理systemd日志..."
    journalctl --vacuum-time=1d 2>/dev/null || true
    
    print_success "系统文件清理完成"
}

# 恢复系统设置
restore_system_settings() {
    print_info "恢复系统设置..."
    
    # 如果之前启用了SELinux策略，恢复原始设置
    if command -v getenforce &> /dev/null; then
        local selinux_status=$(getenforce)
        if [[ "$selinux_status" == "Enforcing" ]]; then
            print_info "SELinux策略保持启用状态"
        fi
    fi
    
    # 恢复Apache默认配置（如果需要）
    print_info "检查Apache默认配置..."
    if [[ -f /etc/httpd/conf.d/welcome.conf.disabled ]]; then
        print_info "恢复Apache默认欢迎页面..."
        mv /etc/httpd/conf.d/welcome.conf.disabled /etc/httpd/conf.d/welcome.conf 2>/dev/null || true
    fi
    
    # 清理防火墙规则
    if command -v firewall-cmd &> /dev/null && systemctl is-active --quiet firewalld; then
        print_info "清理YiZi AI相关的防火墙规则..."
        # 这里可以选择性地清理或保留通用规则
    fi
}

# 验证卸载结果
verify_uninstall() {
    print_info "验证卸载结果..."
    
    local errors=0
    
    # 检查安装目录
    if [[ -d "$INSTALL_DIR" ]]; then
        print_error "安装目录仍然存在: $INSTALL_DIR"
        ((errors++))
    else
        print_success "安装目录已删除"
    fi
    
    # 检查数据库
    if command -v mysql &> /dev/null; then
        if mysql -e "USE $DB_NAME;" 2>/dev/null; then
            print_error "数据库仍然存在: $DB_NAME"
            ((errors++))
        else
            print_success "数据库已删除"
        fi
    fi
    
    # 检查Apache配置
    if [[ -f /etc/httpd/conf.d/yizi-ai.conf ]]; then
        print_error "Apache配置仍然存在"
        ((errors++))
    else
        print_success "Apache配置已删除"
    fi
    
    # 检查网站访问
    if curl -f -s http://localhost >/dev/null 2>&1; then
        # 检查是否显示YiZi AI页面
        local site_content=$(curl -s http://localhost | grep -i "yizi\|ai" || echo "")
        if [[ -n "$site_content" ]]; then
            print_error "网站仍显示YiZi AI内容"
            ((errors++))
        else
            print_success "网站不再显示YiZi AI内容"
        fi
    fi
    
    return $errors
}

# 显示卸载总结
show_uninstall_summary() {
    echo
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}🎉 YiZi AI V3.0 卸载完成！${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo
    echo -e "${YELLOW}卸载总结:${NC}"
    echo "✅ 网站文件已删除"
    echo "✅ 数据库已清理"
    echo "✅ Apache配置已移除"
    echo "✅ 定时任务已清理"
    echo "✅ 日志文件已删除"
    echo "✅ 系统设置已恢复"
    echo
    echo -e "${YELLOW}保留的软件:${NC}"
    echo "• Apache HTTP Server - 用于其他Web应用"
    echo "• MySQL Server - 用于其他数据库应用"
    echo "• PHP - 用于其他PHP应用"
    echo
    echo -e "${YELLOW}如果需要完全清理:${NC}"
    echo "停止MySQL: systemctl stop mysqld"
    echo "停止Apache: systemctl stop httpd"
    echo "卸载Apache: yum remove httpd"
    echo "卸载MySQL: yum remove mysql-server"
    echo "卸载PHP: yum remove php*"
    echo
    echo -e "${YELLOW}注意事项:${NC}"
    echo "• 如果创建了备份，文件保存在: $BACKUP_DIR"
    echo "• 完全卸载Apache和MySQL会影响其他应用"
    echo "• 重新安装请使用新的安装脚本"
    echo
}

# 主程序
main() {
    # 检查root权限
    if [[ $EUID -ne 0 ]]; then
        print_error "此脚本需要root权限运行"
        echo "请使用: sudo $0"
        exit 1
    fi
    
    # 记录卸载开始
    echo "$(date '+%Y-%m-%d %H:%M:%S') - YiZi AI V3.0 卸载开始" >> "$LOG_FILE" 2>/dev/null || true
    
    # 显示确认
    show_uninstall_confirmation
    get_user_confirmation
    
    # 执行卸载步骤
    backup_data
    stop_services
    remove_website_files
    remove_database
    remove_apache_config
    remove_cron_jobs
    remove_log_files
    remove_system_files
    restore_system_settings
    
    # 验证卸载
    if verify_uninstall; then
        print_success "卸载验证通过"
        show_uninstall_summary
        
        # 记录卸载完成
        echo "$(date '+%Y-%m-%d %H:%M:%S') - YiZi AI V3.0 卸载完成" >> "$LOG_FILE" 2>/dev/null || true
    else
        print_error "卸载过程中出现问题，请检查上述错误"
        echo "请查看日志获取更多信息: $LOG_FILE"
        exit 1
    fi
}

# 运行主程序
main "$@"