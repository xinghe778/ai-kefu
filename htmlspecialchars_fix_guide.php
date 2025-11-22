<?php
/**
 * PHP 8.1+ htmlspecialchars null 错误修复指南
 */

// 问题说明
/*
在PHP 8.1+版本中，htmlspecialchars()函数不再接受null值作为参数。
这会导致 "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated" 错误。
*/

// 错误示例（不要这样做）
function badExample($data) {
    // ❌ 这样会导致错误
    return htmlspecialchars($data); // 如果$data是null，会产生弃用警告
}

// 正确示例（推荐）
function goodExample($data) {
    // ✅ 检查null值并提供默认值
    return htmlspecialchars($data ?? '');
}

// 或者使用更复杂的默认值
function betterExample($data, $default = '') {
    return htmlspecialchars($data ?? $default);
}

// 在HTML模板中的使用
function renderHtml($username, $message = '') {
    echo "<div class='user-info'>";
    echo "<span class='username'>" . htmlspecialchars($username ?? '匿名用户') . "</span>";
    echo "<span class='message'>" . htmlspecialchars($message ?? '') . "</span>";
    echo "</div>";
}

// 修复前后对比
echo "<h2>修复示例</h2>";

// ❌ 修复前（会产生错误）
$nullValue = null;
try {
    $result = htmlspecialchars($nullValue);
    echo "修复前: 成功（但有警告）<br>";
} catch (Exception $e) {
    echo "修复前: 失败 - " . $e->getMessage() . "<br>";
}

// ✅ 修复后（安全）
$result = htmlspecialchars($nullValue ?? '');
echo "修复后: " . $result . "<br>";

echo "<h2>常见修复模式</h2>";

// 1. 基础修复
// 修复前: <?= htmlspecialchars($value) ?>
// 修复后: <?= htmlspecialchars($value ?? '') ?>

// 2. 带有默认值的修复
// 修复前: <?= htmlspecialchars($username) ?>
// 修复后: <?= htmlspecialchars($username ?? '系统用户') ?>

// 3. 在表单值中的应用
// 修复前: <input value="<?= htmlspecialchars($search) ?>">
// 修复后: <input value="<?= htmlspecialchars($search ?? '') ?>">

// 4. 在数据库字段中的应用
// 修复前: <?= htmlspecialchars($log['action']) ?>
// 修复后: <?= htmlspecialchars($log['action'] ?? '') ?>

echo "<p><strong>总结:</strong> 始终在使用htmlspecialchars()之前检查null值，使用 null 合并操作符 (??) 提供安全的默认值。</p>";
?>