<?php
require 'auth.php';
checkAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    die('无权访问');
}

require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 防止删除自己
if ($id === $_SESSION['user']['id']) {
    die('不能删除当前账号');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    die('用户不存在');
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
if ($stmt->execute([$id])) {
    header('Location: users.php');
} else {
    die('删除失败');
}