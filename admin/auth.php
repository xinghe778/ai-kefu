<?php
session_start();

// 防止重复声明函数
if (!function_exists('checkAuth')) {
    /**
     * 检查用户是否登录
     */
    function checkAuth() {
        if (!isset($_SESSION['user'])) {
            header('Location: login.php');
            exit;
        }
    }
}

// 处理登录请求
if (isset($_POST['login'])) {
    require 'db.php';
    
    // 查询用户
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($_POST['password'], $user['password'])) {
        // 登录成功，设置会话
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        header('Location: index.php');
        exit;
    } else {
        echo "<div class='error'>用户名或密码错误</div>";
    }
}