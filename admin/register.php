<?php
// register.php
session_start();
require 'db.php';

// 如果已经登录则跳转到首页
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'];
    $invite_code = trim($_POST['invite_code'] ?? '');
    
    // 验证输入
    if (empty($username) || empty($password) || empty($invite_code)) {
        $error = '用户名、密码和邀请码都是必填的';
    } elseif (strlen($password) < 6) {
        $error = '密码至少需要6个字符';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = '该用户名已被注册';
        } else {
            // 检查邮箱是否已存在（如果提供了邮箱）
            if (!empty($email)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = '该邮箱已被注册';
                    return;
                }
            }
            
            try {
                // 开始事务
                $pdo->beginTransaction();
                
                // 验证邀请码
                $stmt = $pdo->prepare("
                    SELECT * FROM invite_codes 
                    WHERE code = ? AND status = 'active'
                    FOR UPDATE
                ");
                $stmt->execute([$invite_code]);
                $invite = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$invite) {
                    throw new Exception('邀请码无效或已失效');
                }
                
                // 检查邀请码是否过期
                if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
                    throw new Exception('邀请码已过期');
                }
                
                // 检查使用次数
                if ($invite['used_count'] >= $invite['max_uses']) {
                    throw new Exception('邀请码使用次数已满');
                }
                
                // 插入新用户
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, email, role, invite_code_used) 
                    VALUES (?, ?, ?, 'user', ?)
                ");
                
                if ($stmt->execute([$username, $hashed_password, $email, $invite_code])) {
                    $user_id = $pdo->lastInsertId();
                    
                    // 更新邀请码状态
                    $stmt = $pdo->prepare("
                        UPDATE invite_codes 
                        SET used_count = used_count + 1, 
                            used_by = ?, 
                            used_at = NOW(),
                            status = CASE 
                                WHEN used_count + 1 >= max_uses THEN 'used' 
                                ELSE 'active' 
                            END
                        WHERE id = ?
                    ");
                    $stmt->execute([$user_id, $invite['id']]);
                    
                    $pdo->commit();
                    $success = '注册成功！正在跳转登录页面...';
                    header("refresh:3;url=login.php");
                } else {
                    throw new Exception('注册失败，请重试');
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = '注册失败：' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>管理员注册</title>
    <style>
        :root {
            --primary-color: #3B82F6;
            --secondary-color: #6366F1;
            --accent-color: #8B5CF6;
            --bg-gradient: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background: 
                radial-gradient(circle at top right, #f3f4f6 0%, #e5e7eb 100%),
                var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .register-container {
            background: #fff;
            border-radius: 1rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        h2 {
            text-align: center;
            color: #1F2937;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--bg-gradient);
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4B5563;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #F9FAFB;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .error, .success {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            animation: fadeIn 0.3s ease-in;
        }

        .error {
            background-color: #FEF2F2;
            color: #DC2626;
            border-left: 4px solid #EF4444;
        }

        .success {
            background-color: #F0FDF4;
            color: #16A34A;
            border-left: 4px solid #48BB78;
        }

        button {
            width: 100%;
            padding: 0.8rem;
            background: var(--bg-gradient);
            border: none;
            border-radius: 0.5rem;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.95rem;
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #6D28D9;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-10px);}
            to {opacity: 1; transform: translateY(0);}
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 2rem 1.5rem;
                border-radius: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>管理员注册</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>邮箱（可选）</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>密码（至少6位）</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>邀请码</label>
                <input type="text" name="invite_code" value="<?= htmlspecialchars($invite_code ?? '') ?>" required 
                       placeholder="请输入邀请码" style="text-transform: uppercase;">
            </div>
            
            <button type="submit">立即注册</button>
        </form>
        
        <p class="login-link">
            已有账号？<a href="login.php">立即登录</a>
        </p>
    </div>
</body>
</html>