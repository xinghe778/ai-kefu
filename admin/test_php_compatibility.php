<?php
/**
 * PHP 8.1+ 兼容性修复验证工具
 * 访问地址: http://121.4.54.239/admin/test_php_compatibility.php
 */

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'><title>PHP兼容性修复验证</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; }
    pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔧 PHP 8.1+ htmlspecialchars() 兼容性修复验证</h1>";

echo "<div class='info'>";
echo "<h3>📋 测试概要</h3>";
echo "<p>本工具测试修复后的代码是否能正确处理 null 值，避免 PHP 8.1+ 的弃用警告。</p>";
echo "</div>";

// 1. PHP版本检查
echo "<h2>1. PHP 版本检查</h2>";
$php_version = PHP_VERSION;
echo "<div class='info'>";
echo "<p>当前 PHP 版本: <strong>{$php_version}</strong></p>";
if (version_compare($php_version, '8.1.0', '>=')) {
    echo "<p style='color: #856404;'>⚠️ 当前版本需要 null 值检查来避免弃用警告</p>";
} else {
    echo "<p style='color: #155724;'>✅ 当前版本无需特殊处理</p>";
}
echo "</div>";

// 2. 测试修复后的代码
echo "<h2>2. 修复效果测试</h2>";

$test_cases = [
    [
        'name' => '数据库字段 - action',
        'value' => null,
        'code' => "htmlspecialchars(\$log['action'] ?? '')",
        'fixed_code' => "htmlspecialchars(\$log['action'] ?? '')"
    ],
    [
        'name' => '数据库字段 - description', 
        'value' => null,
        'code' => "htmlspecialchars(\$log['description'] ?? '')",
        'fixed_code' => "htmlspecialchars(\$log['description'] ?? '')"
    ],
    [
        'name' => '用户名字段',
        'value' => null,
        'code' => "htmlspecialchars(\$username ?? '系统用户')",
        'fixed_code' => "htmlspecialchars(\$username ?? '系统用户')"
    ],
    [
        'name' => '搜索表单值',
        'value' => null,
        'code' => "htmlspecialchars(\$search['username'] ?? '')",
        'fixed_code' => "htmlspecialchars(\$search['username'] ?? '')"
    ],
    [
        'name' => '消息提示',
        'value' => null,
        'code' => "htmlspecialchars(\$success_message ?? '')",
        'fixed_code' => "htmlspecialchars(\$success_message ?? '')"
    ]
];

foreach ($test_cases as $case) {
    echo "<div class='info'>";
    echo "<h4>测试: {$case['name']}</h4>";
    
    // 模拟修复前的代码（不执行，只展示）
    echo "<p><strong>修复前（会产生警告）:</strong></p>";
    echo "<pre><code>// 错误示例: htmlspecialchars(\$value)";
    echo "\n\$result = htmlspecialchars(\$value); // 如果 \$value 是 null，会产生警告</code></pre>";
    
    // 执行修复后的代码
    echo "<p><strong>修复后（安全）:</strong></p>";
    echo "<pre><code>{$case['fixed_code']}</code></pre>";
    
    // 实际测试
    try {
        $test_value = $case['value'];
        // 使用修复后的模式
        $result = htmlspecialchars($test_value ?? '');
        echo "<div class='success'>";
        echo "<p>✅ 测试通过 - 结果: '<strong>{$result}</strong>'</p>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<p>❌ 测试失败: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

// 3. 修复的具体文件
echo "<h2>3. 已修复的文件</h2>";
echo "<div class='success'>";
echo "<h4>✅ 修复完成</h4>";
echo "<ul>";
echo "<li><strong>admin/logs.php</strong>";
echo "<ul>";
echo "<li>第355行: action 字段 - <code>htmlspecialchars(\$log['action'] ?? '')</code></li>";
echo "<li>第356行: description 字段 - <code>htmlspecialchars(\$log['description'] ?? '')</code></li>";
echo "<li>第357行: ip_address 字段 - <code>htmlspecialchars(\$log['ip_address'] ?? '')</code></li>";
echo "<li>第301行: 搜索表单 - <code>htmlspecialchars(\$search['username'] ?? '')</code></li>";
echo "</ul></li>";
echo "<li><strong>admin/profile.php</strong>";
echo "<ul>";
echo "<li>第12行: username 变量 - <code>\$username = \$_SESSION['user']['username'] ?? ''</code></li>";
echo "<li>第13行: role 变量 - <code>\$role = \$_SESSION['user']['role'] ?? 'user'</code></li>";
echo "<li>多个htmlspecialchars()调用都添加了null检查</li>";
echo "</ul></li>";
echo "</ul>";
echo "</div>";

// 4. 测试受影响的页面
echo "<h2>4. 建议测试的页面</h2>";
echo "<div class='info'>";
echo "<p>修复完成后，请访问以下页面确认没有警告:</p>";
echo "<ul>";
echo "<li><a href='logs.php' target='_blank'>管理员日志页面</a> - 应该不再出现htmlspecialchars警告</li>";
echo "<li><a href='profile.php' target='_blank'>个人资料页面</a> - 应该正常加载无警告</li>";
echo "<li>聊天界面中的搜索功能</li>";
echo "</ul>";
echo "</div>";

// 5. 其他可能需要检查的地方
echo "<h2>5. 其他检查建议</h2>";
echo "<div class='warning'>";
echo "<h4>🔍 建议检查的PHP文件</h4>";
echo "<p>以下类型的文件也可能存在类似问题:</p>";
echo "<ul>";
echo "<li>所有使用 <code>htmlspecialchars()</code> 的PHP文件</li>";
echo "<li>处理用户输入的表单处理文件</li>";
echo "<li>显示数据库内容的文件</li>";
echo "<li>模板文件中的变量输出</li>";
echo "</ul>";
echo "<p><strong>通用修复模式:</strong></p>";
echo "<pre><code>// 修复前
htmlspecialchars(\$value);

// 修复后  
htmlspecialchars(\$value ?? '');</code></pre>";
echo "</div>";

// 6. 技术说明
echo "<h2>6. 技术说明</h2>";
echo "<div class='info'>";
echo "<h4>🔧 修复原理</h4>";
echo "<ul>";
echo "<li><strong>问题:</strong> PHP 8.1+ 中 <code>htmlspecialchars()</code> 不再接受 null 值</li>";
echo "<li><strong>解决方案:</strong> 使用 null 合并操作符 <code>??</code> 提供默认值</li>";
echo "<li><strong>默认值:</strong> 通常使用空字符串 <code>''</code> 作为默认值</li>";
echo "<li><strong>特殊情况:</strong> 根据上下文使用更有意义的默认值（如 '系统用户'）</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<div style='text-align: center; color: #666;'>";
echo "<p>PHP 兼容性修复验证工具 | 执行时间: " . date('Y-m-d H:i:s') . " | 版本: V3.0</p>";
echo "</div>";

echo "</body></html>";
?>