<?php
/**
 * 数据库字段修复验证脚本
 * 访问方式：http://121.4.54.239/admin/verify_fix.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>数据库修复验证工具</h2>";
echo "<hr>";

// 数据库连接配置
$config = [
    'host' => 'localhost',
    'dbname' => 'api',
    'username' => 'api',
    'password' => 'bW2TehrNw8PprGe8'
];

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p style='color: green;'>✅ 数据库连接成功</p>";
    
    // 1. 检查chat_logs表结构
    echo "<h3>1. 检查 chat_logs 表结构</h3>";
    $stmt = $pdo->query("DESCRIBE chat_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>字段名</th><th>类型</th><th>是否为空</th><th>默认值</th></tr>";
    
    $found_action = false;
    $found_description = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'action') {
            $found_action = true;
        }
        if ($col['Field'] === 'description') {
            $found_description = true;
        }
    }
    echo "</table>";
    
    // 2. 检查关键字段
    echo "<h3>2. 关键字段检查</h3>";
    if ($found_action) {
        echo "<p style='color: green;'>✅ action 字段存在</p>";
    } else {
        echo "<p style='color: red;'>❌ action 字段缺失</p>";
    }
    
    if ($found_description) {
        echo "<p style='color: green;'>✅ description 字段存在</p>";
    } else {
        echo "<p style='color: red;'>❌ description 字段缺失</p>";
    }
    
    // 3. 测试关键查询
    echo "<h3>3. 测试关键查询</h3>";
    
    try {
        // 测试action查询（这是引起错误的查询）
        $stmt = $pdo->query("SELECT DISTINCT action FROM chat_logs LIMIT 1");
        echo "<p style='color: green;'>✅ DISTINCT action 查询成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ DISTINCT action 查询失败: " . $e->getMessage() . "</p>";
    }
    
    try {
        // 测试description查询
        $stmt = $pdo->query("SELECT DISTINCT description FROM chat_logs LIMIT 1");
        echo "<p style='color: green;'>✅ DISTINCT description 查询成功</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ DISTINCT description 查询失败: " . $e->getMessage() . "</p>";
    }
    
    // 4. 数据统计
    echo "<h3>4. 数据统计</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_logs");
    $total_logs = $stmt->fetchColumn();
    echo "<p>chat_logs 表总记录数: <strong>{$total_logs}</strong></p>";
    
    if ($total_logs > 0) {
        // 显示一些示例数据
        $stmt = $pdo->query("SELECT id, username, action, description, created_at FROM chat_logs ORDER BY id DESC LIMIT 3");
        $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($recent_logs) {
            echo "<h4>最近3条记录:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>用户名</th><th>操作</th><th>描述</th><th>时间</th></tr>";
            foreach ($recent_logs as $log) {
                echo "<tr>";
                echo "<td>{$log['id']}</td>";
                echo "<td>{$log['username']}</td>";
                echo "<td>" . ($log['action'] ?: 'NULL') . "</td>";
                echo "<td>" . ($log['description'] ?: 'NULL') . "</td>";
                echo "<td>{$log['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // 5. 总结
    echo "<h3>5. 修复状态总结</h3>";
    if ($found_action && $found_description) {
        echo "<div style='padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h4 style='color: #155724; margin: 0;'>🎉 修复成功！</h4>";
        echo "<p style='color: #155724; margin: 5px 0 0 0;'>数据库字段已修复，现在可以正常访问admin/logs.php了。</p>";
        echo "</div>";
        echo "<p><strong>建议：</strong>可以尝试访问 <a href='logs.php' target='_blank'>admin/logs.php</a> 确认修复效果。</p>";
    } else {
        echo "<div style='padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<h4 style='color: #721c24; margin: 0;'>⚠️ 修复未完成</h4>";
        echo "<p style='color: #721c24; margin: 5px 0 0 0;'>请执行以下SQL语句来修复数据库:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-radius: 3px;'>";
        echo "ALTER TABLE `chat_logs` \n";
        echo "ADD COLUMN `action` varchar(100) DEFAULT NULL COMMENT '操作类型',\n";
        echo "ADD COLUMN `description` text COMMENT '操作描述';\n";
        echo "</pre>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 数据库连接失败: " . $e->getMessage() . "</p>";
    echo "<p>请检查数据库连接配置是否正确。</p>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 12px;'>";
echo "验证工具执行时间: " . date('Y-m-d H:i:s') . "<br>";
echo "版本: V3.0 数据库修复验证工具";
echo "</p>";
?>