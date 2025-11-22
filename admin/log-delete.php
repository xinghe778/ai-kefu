<?php
require 'auth.php';
checkAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    die('无权访问此页面');
}

require 'db.php';

// 删除历史日志（保留最近30天）
$threshold = date('Y-m-d H:i:s', strtotime('-30 days'));

$stmt = $pdo->prepare("DELETE FROM chat_logs WHERE created_at < ?");
$stmt->execute([$threshold]);

header('Location: logs.php');
exit;