<?php
require 'auth.php';
checkAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    die('无权访问');
}

require 'db.php';

// 获取用户ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die('用户不存在');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    
    // 验证用户名唯一性（排除当前用户）
    if ($username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            $error = '该用户名已被使用';
        }
    }
    
    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        if ($stmt->execute([$username, $role, $id])) {
            $success = '更新成功';
            $user['username'] = $username;
            $user['role'] = $role;
        } else {
            $error = '更新失败';
        }
        
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>编辑用户</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* 页面布局 */
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* 标题样式 */
        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.8em;
            font-weight: 600;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }

        /* 表单卡片 */
        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            position: relative;
            transition: all 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-5px);
        }

        /* 表单组 */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus,
        select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
            outline: none;
        }

        /* 按钮样式 */
        button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        /* 提示信息 */
        .success, .error {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            animation: fadeIn 0.5s ease-out;
        }

        .success {
            background: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        /* 动画效果 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 响应式设计 */
        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .form-card {
                padding: 20px 15px;
            }

            input[type="text"],
            select {
                font-size: 0.95em;
            }

            button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>编辑用户</h2>
        
        <div class="form-card">
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            
            <form method="post">
                <!-- 用户名 -->
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" 
                           value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>"
                           required
                           placeholder="请输入用户名">
                </div>
                
                <!-- 角色 -->
                <div class="form-group">
                    <label>角色</label>
                    <select name="role">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>
                            普通用户
                        </option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
                            管理员
                        </option>
                    </select>
                </div>
                
                <button type="submit">保存修改</button>
            </form>
        </div>
    </div>
</body>
</html>