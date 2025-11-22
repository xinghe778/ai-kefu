#!/bin/bash
# YiZi AI V3.0 部署配置脚本
# 作者: MiniMax Agent
# 版本: 1.0
# 日期: 2025-11-23

set -e

echo "🚀 YiZi AI V3.0 部署配置脚本"
echo "=================================="

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日志函数
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查必要条件
check_prerequisites() {
    log_info "检查系统环境..."
    
    # 检查 PHP
    if ! command -v php &> /dev/null; then
        log_error "PHP 未安装，请先安装 PHP 7.4+"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log_info "PHP 版本: $PHP_VERSION"
    
    # 检查 MySQL
    if ! command -v mysql &> /dev/null; then
        log_warning "MySQL 命令行工具未找到，将跳过数据库初始化"
        MYSQL_AVAILABLE=false
    else
        MYSQL_AVAILABLE=true
        log_success "MySQL 可用"
    fi
    
    # 检查文件权限
    if [ ! -w "." ]; then
        log_error "当前目录没有写入权限"
        exit 1
    fi
    
    log_success "环境检查完成"
}

# 配置数据库
configure_database() {
    log_info "配置数据库连接..."
    
    if [ "$MYSQL_AVAILABLE" = false ]; then
        log_warning "跳过数据库配置，请手动配置 admin/config.php"
        return
    fi
    
    echo -n "请输入数据库主机 [localhost]: "
    read -r DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    
    echo -n "请输入数据库名 [api]: "
    read -r DB_NAME
    DB_NAME=${DB_NAME:-api}
    
    echo -n "请输入数据库用户名: "
    read -r DB_USER
    
    echo -n "请输入数据库密码: "
    read -s DB_PASS
    echo
    
    # 测试数据库连接
    log_info "测试数据库连接..."
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" &> /dev/null; then
        log_success "数据库连接成功"
    else
        log_error "数据库连接失败，请检查配置"
        exit 1
    fi
    
    # 更新配置文件
    log_info "更新数据库配置..."
    sed -i.bak "s/'host' => '.*'/'host' => '$DB_HOST'/g" admin/config.php
    sed -i.bak "s/'database' => '.*'/'database' => '$DB_NAME'/g" admin/config.php
    sed -i.bak "s/'username' => '.*'/'username' => '$DB_USER'/g" admin/config.php
    sed -i.bak "s/'password' => '.*'/'password' => '$DB_PASS'/g" admin/config.php
    
    log_success "数据库配置完成"
}

# 初始化数据库
init_database() {
    log_info "初始化数据库..."
    
    if [ "$MYSQL_AVAILABLE" = false ]; then
        log_warning "跳过数据库初始化，请手动执行 SQL 脚本"
        return
    fi
    
    # 创建数据库
    log_info "创建数据库..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # 执行初始化脚本
    log_info "执行数据库初始化脚本..."
    if [ -f "user_input_files/install/database_init.sql" ]; then
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < user_input_files/install/database_init.sql
    else
        log_warning "数据库初始化脚本不存在"
    fi
    
    # 创建邀请码表
    log_info "创建邀请码表..."
    if [ -f "create_invite_codes.sql" ]; then
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < create_invite_codes.sql
    else
        log_warning "邀请码初始化脚本不存在"
    fi
    
    log_success "数据库初始化完成"
}

# 设置文件权限
set_permissions() {
    log_info "设置文件权限..."
    
    # 设置基本权限
    chmod 755 admin/*.php
    chmod 644 *.php
    
    # 创建必要目录
    mkdir -p uploads/{kb,chat,temp}
    
    # 设置目录权限
    chmod 777 uploads
    chmod 777 uploads/*
    
    # 设置日志权限
    mkdir -p logs
    chmod 755 logs
    
    log_success "文件权限设置完成"
}

# 配置系统设置
configure_system() {
    log_info "配置系统设置..."
    
    # 生成随机密钥
    SECRET_KEY=$(openssl rand -hex 32)
    
    echo -n "请输入 API 密钥: "
    read -s API_KEY
    echo
    
    echo -n "请输入 API 地址 [https://api.spanstar.cn]: "
    read -r API_URL
    API_URL=${API_URL:-https://api.spanstar.cn}
    
    echo -n "请输入系统提示词 [你是一个有用的AI助手，请用友好、专业的方式回答用户的问题。]: "
    read -r SYSTEM_PROMPT
    SYSTEM_PROMPT=${SYSTEM_PROMPT:-你是一个有用的AI助手，请用友好、专业的方式回答用户的问题。}
    
    # 更新系统设置（这里只是示例，实际需要修改 PHP 文件）
    log_info "请手动配置以下设置到数据库或 admin/settings.php:"
    log_info "API 密钥: $API_KEY"
    log_info "API 地址: $API_URL"
    log_info "系统提示词: $SYSTEM_PROMPT"
    log_info "安全密钥: $SECRET_KEY"
    
    log_success "系统配置指导完成"
}

# 测试系统
test_system() {
    log_info "测试系统功能..."
    
    # 测试 PHP 扩展
    log_info "检查 PHP 扩展..."
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "curl" "json" "mbstring")
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            log_success "$ext 扩展已安装"
        else
            log_warning "$ext 扩展未安装"
        fi
    done
    
    # 测试文件权限
    log_info "测试文件权限..."
    if [ -w "uploads" ]; then
        log_success "uploads 目录可写"
    else
        log_error "uploads 目录不可写"
    fi
    
    if [ -r "admin/config.php" ]; then
        log_success "配置文件可读"
    else
        log_error "配置文件不可读"
    fi
    
    log_success "系统测试完成"
}

# 生成管理员账户
create_admin() {
    log_info "创建默认管理员账户..."
    
    cat << EOF
请使用以下默认管理员账户登录：
用户名: admin
密码: admin123

⚠️ 重要提醒：
1. 首次登录后请立即修改密码
2. 建议生成邀请码供用户注册
3. 配置 AI API 密钥以启用对话功能

后台访问地址: $(pwd)/admin/
EOF
    
    log_success "管理员账户创建完成"
}

# 显示完成信息
show_completion() {
    echo
    echo "🎉 YiZi AI V3.0 部署完成！"
    echo "=================================="
    echo
    echo "📋 后续步骤："
    echo "1. 访问后台管理: ./admin/"
    echo "2. 使用管理员账户登录"
    echo "3. 修改默认密码"
    echo "4. 配置 AI API 密钥"
    echo "5. 生成邀请码"
    echo "6. 测试系统功能"
    echo
    echo "📖 详细文档："
    echo "- 更新说明: ./更新说明_V3.0_完整版.md"
    echo "- 安装指南: ./user_input_files/install/安装指南.md"
    echo
    echo "🆘 技术支持："
    echo "- 检查日志文件: ./logs/"
    echo "- 查看错误信息: ./admin/logs.php"
    echo
    echo "感谢使用 YiZi AI V3.0！✨"
}

# 主函数
main() {
    echo "开始部署配置..."
    echo
    
    check_prerequisites
    echo
    
    configure_database
    echo
    
    init_database
    echo
    
    set_permissions
    echo
    
    configure_system
    echo
    
    test_system
    echo
    
    create_admin
    echo
    
    show_completion
}

# 执行主函数
main "$@"