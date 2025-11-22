#!/bin/bash

# 数据库字段错误快速修复脚本
# 版本: V3.0
# 日期: 2025-11-23

echo "==============================================="
echo "    YiZi AI V3.0 数据库字段错误修复工具"
echo "==============================================="
echo ""

# 数据库配置
DB_HOST="localhost"
DB_NAME="api"
DB_USER="api"
DB_PASS="bW2TehrNw8PprGe8"

echo "📋 当前配置:"
echo "   数据库: $DB_NAME"
echo "   用户: $DB_USER"
echo "   主机: $DB_HOST"
echo ""

# 检查数据库连接
echo "🔍 正在检查数据库连接..."
if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ 数据库连接成功"
else
    echo "❌ 数据库连接失败"
    echo "请检查数据库配置和用户权限"
    exit 1
fi
echo ""

# 检查chat_logs表当前结构
echo "🔍 检查当前 chat_logs 表结构..."
echo ""

# 创建临时文件用于SQL查询
cat > /tmp/check_table.sql << 'EOF'
USE api;
DESCRIBE chat_logs;
EOF

# 执行表结构查询
echo "当前字段列表:"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/check_table.sql
echo ""

# 检查是否缺少action和description字段
echo "🔍 检查缺失的字段..."
MISSING_COLUMNS=""

# 检查action字段
if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT action FROM chat_logs LIMIT 1;" > /dev/null 2>&1; then
    MISSING_COLUMNS="$MISSING_COLUMNS action"
    echo "❌ action 字段缺失"
else
    echo "✅ action 字段存在"
fi

# 检查description字段
if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT description FROM chat_logs LIMIT 1;" > /dev/null 2>&1; then
    MISSING_COLUMNS="$MISSING_COLUMNS description"
    echo "❌ description 字段缺失"
else
    echo "✅ description 字段存在"
fi

echo ""

if [ -z "$MISSING_COLUMNS" ]; then
    echo "🎉 所有字段都已存在，无需修复！"
    echo ""
    echo "🔍 测试关键查询..."
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT DISTINCT action FROM chat_logs LIMIT 1;" > /dev/null 2>&1; then
        echo "✅ DISTINCT action 查询成功"
    else
        echo "❌ DISTINCT action 查询失败"
    fi
else
    echo "⚠️ 发现缺失的字段: $MISSING_COLUMNS"
    echo ""
    echo "🔧 开始修复..."
    
    # 创建修复SQL
    cat > /tmp/fix_table.sql << EOF
USE api;
ALTER TABLE chat_logs 
ADD COLUMN \`action\` varchar(100) DEFAULT NULL COMMENT '操作类型',
ADD COLUMN \`description\` text COMMENT '操作描述';

-- 添加索引
ALTER TABLE chat_logs 
ADD KEY IF NOT EXISTS \`idx_action\` (\`action\`);

-- 显示修复结果
DESCRIBE chat_logs;
EOF
    
    # 执行修复
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /tmp/fix_table.sql; then
        echo ""
        echo "🎉 数据库字段修复成功！"
        echo ""
        echo "📊 修复后的表结构:"
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE chat_logs;"
        echo ""
        
        # 测试修复结果
        echo "🧪 测试修复结果..."
        if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT DISTINCT action FROM chat_logs LIMIT 1;" > /dev/null 2>&1; then
            echo "✅ DISTINCT action 查询测试成功"
        else
            echo "❌ DISTINCT action 查询测试失败"
        fi
        
        if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT DISTINCT description FROM chat_logs LIMIT 1;" > /dev/null 2>&1; then
            echo "✅ DISTINCT description 查询测试成功"
        else
            echo "❌ DISTINCT description 查询测试失败"
        fi
        
    else
        echo "❌ 数据库修复失败"
        echo "请手动检查数据库权限或联系技术支持"
        exit 1
    fi
fi

echo ""
echo "==============================================="
echo "修复完成！您现在可以正常访问:"
echo "• http://121.4.54.239/admin/logs.php"
echo "• http://121.4.54.239/admin/profile.php"
echo "==============================================="

# 清理临时文件
rm -f /tmp/check_table.sql /tmp/fix_table.sql

echo ""
echo "执行时间: $(date '+%Y-%m-%d %H:%M:%S')"
