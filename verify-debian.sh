#!/bin/bash

# AI客服系统安装验证脚本 - Debian/Ubuntu版
# 检查Debian/Ubuntu系统上的安装是否成功

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${BLUE}AI客服系统安装验证工具 (Debian/Ubuntu)${NC}"
echo "========================================"

# 检查是否为root用户
if [[ $EUID -ne 0 ]]; then
    log_error "此脚本需要root权限运行，请使用sudo执行"
    exit 1
fi

# 检查系统类型
if [[ -f /etc/debian_version ]]; then
    log_info "检测到 Debian 系统: $(cat /etc/debian_version)"
elif [[ -f /etc/lsb-release ]]; then
    log_info "检测到 Ubuntu 系统: $(lsb_release -rs)"
else
    log_error "此脚本仅适用于Debian/Ubuntu系统"
    exit 1
fi

# 检查Apache服务
log_info "检查Apache服务状态..."
if systemctl is-active --quiet apache2; then
    echo -e "  Apache状态: ${GREEN}✓ 运行中${NC}"
    echo -e "  Apache版本: $(apache2 -v | head -n1)"
else
    echo -e "  Apache状态: ${RED}✗ 未运行${NC}"
    echo -e "  Apache错误日志: /var/log/apache2/error.log"
fi

# 检查MySQL服务
log_info "检查MySQL服务状态..."
if systemctl is-active --quiet mysql; then
    echo -e "  MySQL状态: ${GREEN}✓ 运行中${NC}"
    echo -e "  MySQL版本: $(mysql --version)"
else
    echo -e "  MySQL状态: ${RED}✗ 未运行${NC}"
    echo -e "  MySQL错误日志: /var/log/mysql/error.log"
fi

# 检查项目目录
log_info "检查项目文件..."
INSTALL_DIR="/var/www/html/ai-kefu"
if [[ -d "$INSTALL_DIR" ]]; then
    echo -e "  项目目录: ${GREEN}✓ 存在${NC}"
    cd "$INSTALL_DIR"
    echo -e "  项目文件: $(ls -1 | wc -l) 个"
    
    # 检查关键文件
    if [[ -f "index.php" ]]; then
        echo -e "  主入口文件: ${GREEN}✓ index.php${NC}"
    else
        echo -e "  主入口文件: ${RED}✗ index.php${NC}"
    fi
    
    if [[ -d "admin" ]]; then
        echo -e "  管理后台: ${GREEN}✓ admin/ 目录${NC}"
    else
        echo -e "  管理后台: ${RED}✗ admin/ 目录${NC}"
    fi
else
    echo -e "  项目目录: ${RED}✗ 不存在${NC}"
fi

# 检查PHP环境
log_info "检查PHP环境..."
if command -v php &>/dev/null; then
    echo -e "  PHP状态: ${GREEN}✓ 已安装${NC}"
    echo -e "  PHP版本: $(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
    
    # 检查PHP扩展
    local php_extensions=("mysql" "curl" "gd" "mbstring" "zip" "json" "openssl")
    echo -e "  PHP扩展检查:"
    
    for ext in "${php_extensions[@]}"; do
        if php -m | grep -q "^${ext}$"; then
            echo -e "    ${ext}: ${GREEN}✓${NC}"
        else
            echo -e "    ${ext}: ${RED}✗${NC}"
        fi
    done
else
    echo -e "  PHP状态: ${RED}✗ 未安装${NC}"
fi

# 检查数据库配置
log_info "检查数据库配置..."
DB_CONFIG="/root/aikefu_db_config.txt"
if [[ -f "$DB_CONFIG" ]]; then
    echo -e "  数据库配置文件: ${GREEN}✓ 存在${NC}"
    
    # 检查数据库连接
    local DB_PASS=$(grep "密码:" "$DB_CONFIG" | cut -d':' -f2 | tr -d ' ')
    if mysql -u api -p"$DB_PASS" -e "USE api;" &>/dev/null; then
        echo -e "  数据库连接: ${GREEN}✓ 正常${NC}"
        
        # 检查表结构
        local table_count=$(mysql -u api -p"$DB_PASS" api -e "SHOW TABLES;" 2>/dev/null | wc -l)
        echo -e "  数据表数量: $((table_count - 1)) 个"
    else
        echo -e "  数据库连接: ${RED}✗ 失败${NC}"
    fi
else
    echo -e "  数据库配置文件: ${RED}✗ 不存在${NC}"
fi

# 检查Apache虚拟主机配置
log_info "检查Apache虚拟主机..."
if [[ -f "/etc/apache2/sites-available/ai-kefu.conf" ]]; then
    echo -e "  虚拟主机配置: ${GREEN}✓ 存在${NC}"
    
    if a2ensite ai-kefu.conf &>/dev/null; then
        echo -e "  站点状态: ${GREEN}✓ 已启用${NC}"
    else
        echo -e "  站点状态: ${YELLOW}⚠ 未启用${NC}"
    fi
else
    echo -e "  虚拟主机配置: ${RED}✗ 不存在${NC}"
fi

# 检查文件权限
log_info "检查文件权限..."
if [[ -d "$INSTALL_DIR" ]]; then
    local upload_dirs=("uploads" "admin/uploads" "logs")
    for dir in "${upload_dirs[@]}"; do
        if [[ -d "$INSTALL_DIR/$dir" ]]; then
            local perms=$(stat -c "%a" "$INSTALL_DIR/$dir" 2>/dev/null || echo "000")
            if [[ "$perms" == "777" ]]; then
                echo -e "  $dir: ${GREEN}✓ 权限正确 (777)${NC}"
            else
                echo -e "  $dir: ${YELLOW}⚠ 权限异常 ($perms)${NC}"
            fi
        fi
    done
fi

# 检查端口监听
log_info "检查端口监听..."
if netstat -tlnp | grep -q ":80 "; then
    echo -e "  HTTP端口(80): ${GREEN}✓ 监听中${NC}"
else
    echo -e "  HTTP端口(80): ${RED}✗ 未监听${NC}"
fi

if netstat -tlnp | grep -q ":3306 "; then
    echo -e "  MySQL端口(3306): ${GREEN}✓ 监听中${NC}"
else
    echo -e "  MySQL端口(3306): ${RED}✗ 未监听${NC}"
fi

# 检查系统资源
log_info "检查系统资源..."
local memory=$(free -m | awk 'NR==2{printf "%.0f", $2}')
local disk=$(df / | awk 'NR==2{printf "%.0f", $4/1024/1024}')
echo -e "  内存: ${memory}MB"
echo -e "  可用磁盘: ${disk}GB"

# 测试Web访问
log_info "测试Web访问..."
if command -v curl &>/dev/null; then
    local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
    if [[ "$response" == "200" ]]; then
        echo -e "  Web访问测试: ${GREEN}✓ HTTP 200${NC}"
    else
        echo -e "  Web访问测试: ${YELLOW}⚠ HTTP $response${NC}"
    fi
else
    echo -e "  Web访问测试: ${YELLOW}⚠ curl未安装${NC}"
fi

# 生成检查报告
echo
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}安装验证完成${NC}"
echo -e "${BLUE}========================================${NC}"

# 计算整体状态
local error_count=0
local warning_count=0

# 简单统计错误和警告（这里简化处理）
echo -e "${GREEN}验证完成！${NC}"
echo -e "详细的安装日志和错误信息请查看:"
echo -e "• Apache日志: /var/log/apache2/"
echo -e "• MySQL日志: /var/log/mysql/"
echo -e "• 系统日志: /var/log/syslog"
echo
echo -e "${BLUE}如有问题请运行故障排除脚本: ./troubleshoot-debian.sh${NC}"