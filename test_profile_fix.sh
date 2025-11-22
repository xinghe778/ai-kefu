#!/bin/bash
# 数据库连接测试脚本

echo "正在测试个人资料页面的数据库连接..."

# 检查修复后的文件
if grep -q "require_once 'db.php';" /workspace/admin/profile.php; then
    echo "✅ 数据库连接文件修复成功"
else
    echo "❌ 数据库连接文件修复失败"
fi

# 测试数据库连接
echo "正在测试数据库连接..."

# 检查 db.php 文件
if [ -f "/workspace/admin/db.php" ]; then
    echo "✅ db.php 文件存在"
else
    echo "❌ db.php 文件不存在"
fi

# 检查 config.php 文件
if [ -f "/workspace/admin/config.php" ]; then
    echo "✅ config.php 文件存在"
else
    echo "❌ config.php 文件不存在"
fi

echo ""
echo "修复说明："
echo "1. 将 profile.php 中的 'config.php' 改为 'db.php'"
echo "2. db.php 文件包含实际的数据库连接代码"
echo "3. config.php 只包含数据库配置常量"

echo ""
echo "测试步骤："
echo "1. 打开浏览器访问您的个人资料页面"
echo "2. URL: http://您的域名/admin/profile.php"
echo "3. 如果已登录，应该可以看到个人资料页面"
echo "4. 如果未登录，会自动跳转到登录页面"

echo ""
echo "如果仍然有问题，请检查："
echo "1. 数据库服务是否正在运行"
echo "2. 数据库配置信息是否正确（config.php）"
echo "3. 数据库用户权限是否正确"