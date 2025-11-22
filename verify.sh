#!/bin/bash

# AI客服系统快速验证脚本
# 检查安装是否成功

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

echo -e "${BLUE}AI客服系统安装验证工具${NC}"
echo "========================================"

# 检查Apache服务
log_info "检查Apache服务状态..."
if systemctl is-active --quiet httpd; then
    echo -e "  Apache状态: ${GREEN}✓ 运行中${NC}"
else
    echo -e "  Apache状态: ${RED}✗ 未运行${NC}"
fi

# 检查MySQL服务
log_info "检查MySQL服务状态..."
if systemctl is-active --quiet mysqld; then
    echo -e "  MySQL状态: ${GREEN}✓ 运行中${NC}"
else
    echo -e "  MySQL状态: ${RED}✗ 未运行${NC}"
fi

# 检查项目目录
log_info "检查项目文件..."
if [[ -d "/var/www/html/ai-kefu" ]]; then
    echo -e "  项目目录: ${GREEN}✓ 存在${NC}"
    cd /var/www/html/ai-kefu
    echo -e "  项目文件: $(ls -1 | wc -l) 个"
else
    echo -e "  项目目录: ${RED}✗ 不存在${NC}"
fi

# 检查PHP环境
log_info "检查PHP环境..."
if command -v php &>/dev/null; then
    local php_version=$(php -v | head -n1 | grep -oP 'PHP \K[0-9.]+')
    echo -e "  PHP版本: ${GREEN}✓ $php_version${NC}"
    
    # 检查PHP扩展
    local extensions=("pdo" "mysql" "mbstring" "json" "curl")
    for ext in "${extensions[@]}"; do
        if php -m | grep -q "$ext"; then
            echo -e "    $ext扩展: ${GREEN}✓${NC}"
        else
            echo -e "    $ext扩展: ${RED}✗${NC}"
        fi
    done
else
    echo -e "  PHP状态: ${RED}✗ 未安装${NC}"
fi

# 检查数据库连接
log_info "检查数据库连接..."
if [[ -f "/tmp/db_credentials.txt" ]]; then
    echo -e "  数据库配置: ${GREEN}✓ 存在${NC}"
    
    # 读取数据库配置
    local db_pass=$(grep "数据库密码:" /tmp/db_credentials.txt | cut -d: -f2 | xargs)
    
    if mysql -u aikefu -p"$db_pass" -e "USE ai_kefu; SELECT 1;" &>/dev/null; then
        echo -e "  数据库连接: ${GREEN}✓ 成功${NC}"
    else
        echo -e "  数据库连接: ${RED}✗ 失败${NC}"
    fi
else
    echo -e "  数据库配置: ${RED}✗ 未找到${NC}"
fi

# 检查Web访问
log_info "检查Web访问..."
local http_code=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
if [[ "$http_code" == "200" || "$http_code" == "302" ]]; then
    echo -e "  Web访问: ${GREEN}✓ 正常${NC} (HTTP $http_code)"
else
    echo -e "  Web访问: ${RED}✗ 异常${NC} (HTTP $http_code)"
fi

# 检查文件权限
log_info "检查文件权限..."
if [[ -d "/var/www/html/ai-kefu" ]]; then
    local owner=$(stat -c '%U:%G' /var/www/html/ai-kefu)
    echo -e "  文件所有者: ${GREEN}✓ $owner${NC}"
    
    if [[ -r "/var/www/html/ai-kefu/admin" ]]; then
        echo -e "  管理后台: ${GREEN}✓ 可访问${NC}"
    else
        echo -e "  管理后台: ${YELLOW}⚠ 权限检查${NC}"
    fi
fi

# 检查系统资源
log_info "检查系统资源..."
local memory=$(free -m | awk 'NR==2{printf "%.0f", $2}')
local disk=$(df / | awk 'NR==2{printf "%.0f", $4/1024/1024}')
echo -e "  内存使用: ${memory}MB"
echo -e "  可用磁盘: ${disk}GB"

# 检查防火墙
log_info "检查防火墙状态..."
if command -v firewall-cmd &>/dev/null; then
    if firewall-cmd --state &>/dev/null; then
        echo -e "  防火墙: ${YELLOW}⚠ 运行中${NC}"
        echo "    建议开放80端口: firewall-cmd --permanent --add-port=80/tcp"
    else
        echo -e "  防火墙: ${GREEN}✓ 未运行${NC}"
    fi
else
    echo -e "  防火墙: ${YELLOW}⚠ 无状态信息${NC}"
fi

echo ""
echo -e "${GREEN}验证完成！${NC}"
echo ""
echo "如需进行更详细的故障排查，请运行: ./troubleshoot.sh"