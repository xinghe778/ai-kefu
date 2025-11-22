#!/bin/bash

# PHP 8.1+ htmlspecialchars 兼容性快速检查脚本
# 版本: V3.0
# 日期: 2025-11-23

echo "==============================================="
echo "    PHP 兼容性修复检查工具"
echo "==============================================="
echo ""

# 检查PHP版本
PHP_VERSION=$(php -v | head -n 1 | cut -d' ' -f2)
echo "📋 当前PHP版本: $PHP_VERSION"

if [ "$(printf '%s\n' "8.1.0" "$PHP_VERSION" | sort -V | head -n1)" = "8.1.0" ]; then
    echo "✅ 检测到PHP 8.1+，需要null值检查"
else
    echo "ℹ️ PHP版本低于8.1，无需特殊处理"
fi
echo ""

# 检查修复的文件
echo "🔍 检查已修复的文件..."
echo ""

# 检查admin/logs.php
if [ -f "/workspace/admin/logs.php" ]; then
    echo "✅ admin/logs.php 文件存在"
    
    # 检查修复内容
    if grep -q "htmlspecialchars(\$log\['action'\] ?? '')" "/workspace/admin/logs.php"; then
        echo "  ✅ action 字段修复正常"
    else
        echo "  ❌ action 字段未修复"
    fi
    
    if grep -q "htmlspecialchars(\$log\['description'\] ?? '')" "/workspace/admin/logs.php"; then
        echo "  ✅ description 字段修复正常"
    else
        echo "  ❌ description 字段未修复"
    fi
    
    if grep -q "htmlspecialchars(\$search\['username'\] ?? '')" "/workspace/admin/logs.php"; then
        echo "  ✅ 搜索表单修复正常"
    else
        echo "  ❌ 搜索表单未修复"
    fi
else
    echo "❌ admin/logs.php 文件不存在"
fi

echo ""

# 检查admin/profile.php
if [ -f "/workspace/admin/profile.php" ]; then
    echo "✅ admin/profile.php 文件存在"
    
    # 检查变量初始化
    if grep -q "\$username = \$_SESSION\['user'\]\['username'\] ?? '';" "/workspace/admin/profile.php"; then
        echo "  ✅ username 变量修复正常"
    else
        echo "  ❌ username 变量未修复"
    fi
    
    # 检查htmlspecialchars修复
    FIXED_COUNT=$(grep -c "htmlspecialchars.*?? " "/workspace/admin/profile.php")
    echo "  ✅ profile.php 中有 $FIXED_COUNT 处htmlspecialchars修复"
else
    echo "❌ admin/profile.php 文件不存在"
fi

echo ""

# 检查验证工具
if [ -f "/workspace/admin/test_php_compatibility.php" ]; then
    echo "✅ 在线验证工具存在"
    echo "  🌐 访问地址: http://121.4.54.239/admin/test_php_compatibility.php"
else
    echo "❌ 在线验证工具不存在"
fi

echo ""

# 生成测试建议
echo "📋 修复验证建议:"
echo "==============================================="
echo ""
echo "1. 在线验证（推荐）:"
echo "   🌐 访问: http://121.4.54.239/admin/test_php_compatibility.php"
echo ""
echo "2. 功能测试:"
echo "   📄 访问管理员日志页面:"
echo "      http://121.4.54.239/admin/logs.php"
echo "   👤 访问个人资料页面:"
echo "      http://121.4.54.239/admin/profile.php"
echo ""
echo "3. 浏览器检查:"
echo "   - 打开开发者工具 (F12)"
echo "   - 查看Console标签"
echo "   - 应该没有htmlspecialchars相关的警告"
echo ""
echo "4. 如果仍有警告:"
echo "   - 清除浏览器缓存"
echo "   - 检查其他PHP文件是否有类似问题"
echo "   - 使用搜索命令: grep -r 'htmlspecialchars(' /path/to/project"
echo ""

echo "==============================================="
echo "检查完成! 执行时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "==============================================="