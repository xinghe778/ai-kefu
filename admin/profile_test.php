<?php
// 个人资料页面诊断工具
// 用于测试和诊断个人资料页面的问题

echo "<h2>个人资料页面诊断工具</h2>";

echo "<h3>1. 文件检查</h3>";
echo "✅ db.php 文件: " . (file_exists('db.php') ? '存在' : '不存在') . "<br>";
echo "✅ config.php 文件: " . (file_exists('config.php') ? '存在' : '不存在') . "<br>";
echo "✅ profile.php 文件: " . (file_exists('profile.php') ? '存在' : '不存在') . "<br>";

echo "<h3>2. 数据库连接测试</h3>";
try {
    require 'config.php';
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8",
        DB_USER, 
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ 数据库连接成功<br>";
    
    // 测试查询
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "✅ 用户表查询成功，用户数量: " . $user_count . "<br>";
    
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ 用户表结构正常，包含 " . count($columns) . " 个字段<br>";
    
} catch (PDOException $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Session 检查</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user'])) {
    echo "✅ 用户已登录<br>";
    echo "用户ID: " . ($_SESSION['user']['id'] ?? '未设置') . "<br>";
    echo "用户名: " . ($_SESSION['user']['username'] ?? '未设置') . "<br>";
    echo "角色: " . ($_SESSION['user']['role'] ?? '未设置') . "<br>";
} else {
    echo "ℹ️ 用户未登录<br>";
    echo "要测试个人资料页面，请先登录系统。<br>";
}

echo "<h3>4. 测试建议</h3>";
echo "如果所有测试通过，请访问: <a href='profile.php'>个人资料页面</a><br>";
echo "如果测试失败，请检查数据库配置和网络连接。<br>";

echo "<h3>5. 快速修复</h3>";
echo "如果问题依然存在，请尝试：<br>";
echo "1. 检查数据库服务状态<br>";
echo "2. 验证数据库用户名和密码<br>";
echo "3. 确保数据库文件权限正确<br>";
echo "4. 查看Web服务器错误日志<br>";
?>