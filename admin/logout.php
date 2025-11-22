<?php
// logout.php
session_start();

// 清除所有会话数据
$_SESSION = [];

// 如果要彻底销毁会话，建议同时删除 session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 最后销毁会话
session_destroy();

// 跳转到登录页
header('Location: login.php');
exit;