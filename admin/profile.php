<?php
session_start();
require_once 'db.php';

// 检查用户是否登录
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'] ?? '';
$role = $_SESSION['user']['role'] ?? 'user';
$email = $_SESSION['user']['email'] ?? '';

// 处理表单提交
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 获取表单数据
        $new_email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // 验证邮箱格式
        if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('邮箱格式不正确');
        }
        
        // 检查密码相关操作
        if (!empty($new_password) || !empty($confirm_password) || !empty($current_password)) {
            if (empty($current_password)) {
                throw new Exception('请输入当前密码');
            }
            
            if (empty($new_password) || empty($confirm_password)) {
                throw new Exception('请填写新密码和确认密码');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('新密码和确认密码不一致');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('新密码长度至少6位');
            }
            
            // 验证当前密码
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                throw new Exception('当前密码错误');
            }
        }
        
        // 更新用户信息
        if (!empty($new_password)) {
            // 更新邮箱和密码
            if (!empty($new_email)) {
                $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_email, password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
            }
        } elseif (!empty($new_email)) {
            // 仅更新邮箱
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$new_email, $user_id]);
        }
        
        // 更新session中的信息
        $_SESSION['user']['email'] = $new_email;
        $email = $new_email;
        
        $success_message = '资料更新成功！';
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 获取用户详细信息
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch();
    
    if (!$user_info) {
        throw new Exception('用户信息不存在');
    }
    
    // 如果session中没有email信息，从数据库获取
    if (empty($_SESSION['user']['email']) && !empty($user_info['email'])) {
        $_SESSION['user']['email'] = $user_info['email'];
        $email = $user_info['email'];
    }
    
} catch (Exception $e) {
    $error_message = '获取用户信息失败：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人资料 - <?php echo htmlspecialchars($site_title ?? 'YiZi AI'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            color: white;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-username {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .profile-role {
            font-size: 16px;
            opacity: 0.9;
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: inline-block;
        }
        
        .profile-body {
            padding: 40px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }
        
        .btn {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .password-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-top: 4px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 0 16px;
            }
            
            .profile-header,
            .profile-body {
                padding: 24px;
            }
            
            .profile-username {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-card">
            <!-- 头部信息 -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="profile-username"><?php echo htmlspecialchars($username ?? ''); ?></div>
                <div class="profile-role">
                    <i class="bi bi-<?php echo $role === 'admin' ? 'shield-check' : 'person'; ?> me-2"></i>
                    <?php echo $role === 'admin' ? '管理员' : '用户'; ?>
                </div>
            </div>
            
            <!-- 主体内容 -->
            <div class="profile-body">
                <!-- 消息提示 -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message ?? ''); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message ?? ''); ?>
                    </div>
                <?php endif; ?>
                
                <!-- 用户统计 -->
                <div class="section-title">
                    <i class="bi bi-graph-up"></i>账户统计
                </div>
                <div class="user-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo date('Y-m-d', strtotime($user_info['created_at'] ?? 'now')); ?></div>
                        <div class="stat-label">注册日期</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_logs WHERE user_id = ?");
                                $stmt->execute([$user_id]);
                                echo number_format($stmt->fetchColumn());
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="stat-label">总对话次数</div>
                    </div>
                    <?php if ($role === 'admin'): ?>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
                                echo number_format($stmt->fetchColumn());
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                        <div class="stat-label">系统用户数</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- 个人资料设置 -->
                <form method="POST" action="">
                    <div class="section-title">
                        <i class="bi bi-person-gear"></i>个人资料
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">用户名</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($username ?? ''); ?>" readonly>
                                <small class="text-muted">用户名不可修改</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">邮箱地址</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                       placeholder="请输入邮箱地址">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>保存资料
                        </button>
                        <a href="<?php echo $role === 'admin' ? 'admin/index.php' : 'index_v2.php'; ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>返回
                        </a>
                    </div>
                </form>
                
                <!-- 密码修改 -->
                <div class="password-section">
                    <div class="section-title">
                        <i class="bi bi-shield-lock"></i>安全设置
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">当前密码 <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" 
                                   placeholder="请输入当前密码" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">新密码</label>
                                    <input type="password" name="new_password" class="form-control" 
                                           placeholder="至少6位字符" minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">确认新密码</label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           placeholder="再次输入新密码">
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>密码安全提示：</strong>
                            <ul class="mb-0 mt-2">
                                <li>密码长度至少6位</li>
                                <li>建议使用字母、数字和符号组合</li>
                                <li>定期更换密码以确保安全</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-2"></i>更新密码
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 表单验证
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const newPassword = form.querySelector('input[name="new_password"]');
                const confirmPassword = form.querySelector('input[name="confirm_password"]');
                
                if (newPassword && confirmPassword && (newPassword.value || confirmPassword.value)) {
                    if (newPassword.value !== confirmPassword.value) {
                        e.preventDefault();
                        alert('新密码和确认密码不一致！');
                        return false;
                    }
                    
                    if (newPassword.value.length < 6) {
                        e.preventDefault();
                        alert('新密码长度至少6位！');
                        return false;
                    }
                }
            });
        });
        
        // 自动隐藏成功消息
        setTimeout(() => {
            const successAlerts = document.querySelectorAll('.alert-success');
            successAlerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>