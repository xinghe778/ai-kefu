<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        try {
            require_once 'db.php';
            
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // 登录成功
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'email' => $user['email']
                ];
                
                // 更新登录信息
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
            }
        } catch (Exception $e) {
            $error = '登录失败，请稍后重试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - YiZi AI</title>
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

        .login-container {
            background: #fff;
            border-radius: 1rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-10px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 3rem;
            background: var(--bg-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        .input-group {
            position: relative;
        }

        .input-group-text {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
            z-index: 1;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.5rem;
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

        .btn-login {
            width: 100%;
            padding: 0.9rem;
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: none;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #FEE2E2;
            color: #DC2626;
            border-left: 4px solid #DC2626;
        }

        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .links a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #6D28D9;
        }

        .loading {
            display: none;
            color: white;
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* 响应式设计 */
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
                border-radius: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-robot"></i>
        </div>
        <h2>管理员登录</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" 
                           name="username" 
                           placeholder="用户名"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" 
                           name="password" 
                           placeholder="密码"
                           required>
                </div>
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                <span class="login-text">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    登录
                </span>
                <span class="loading">
                    <i class="fas fa-spinner me-2"></i>
                    登录中...
                </span>
            </button>
            
            <div class="links">
                <a href="register.php">
                    <i class="fas fa-user-plus me-1"></i>
                    注册新用户
                </a>
                <a href="../index.php">
                    <i class="fas fa-home me-1"></i>
                    返回首页
                </a>
            </div>
        </form>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('请填写完整的登录信息');
                return;
            }
            
            // 添加加载效果
            const loginBtn = document.getElementById('loginBtn');
            const loginText = loginBtn.querySelector('.login-text');
            const loading = loginBtn.querySelector('.loading');
            
            loginText.style.display = 'none';
            loading.style.display = 'inline';
            loginBtn.disabled = true;
        });
        
        // 回车键登录
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html>