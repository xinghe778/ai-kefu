<?php
// 确保 session 已启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 安全获取用户信息
$user = $_SESSION['user'] ?? null;
$username = $user['username'] ?? '游客'; // 默认值兜底
$isAdmin = ($user && ($user['role'] ?? '') === 'admin');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>管理后台</title>
    <!-- 引入 Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* 基础变量 */
        :root {
            --primary: #3498db;
            --primary-light: #2980b9;
            --background-light: #f8f9fa;
            --background-dark: #1a252f;
            --text-color: #ffffff;
            --hover-opacity: 0.9;
            --transition-time: 0.3s;
        }

        /* 移动端适配样式 */
        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--background-light);
        }

        /* 响应式Header样式 */
        .header {
            background: linear-gradient(90deg, #2c3e50, var(--background-dark));
            padding: 1rem 1.5rem;
            color: var(--text-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Logo 样式 */
        .logo {
            font-size: 1.4rem;
            font-weight: 600;
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.3s ease;
            white-space: nowrap;
        }

        .logo:hover {
            transform: scale(1.05) rotate(1deg);
        }

        /* 主导航容器 */
        .main-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* 桌面菜单 */
        .desktop-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        /* 移动端汉堡按钮 */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 24px;
            height: 21px;
            cursor: pointer;
            z-index: 1001;
        }

        .hamburger span {
            height: 3px;
            width: 100%;
            background: var(--text-color);
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* 移动菜单样式 */
        .mobile-menu {
            position: fixed;
            top: 56px;
            left: -100%;
            width: 100%;
            max-width: 320px;
            height: calc(100vh - 56px);
            background: var(--background-dark);
            box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
            padding: 1.5rem 1rem;
            z-index: 999;
            transition: left 0.3s ease;
            overflow-y: auto;
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .mobile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .mobile-username {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-color);
        }

        .mobile-menu ul {
            list-style: none;
            margin-top: 1.5rem;
            padding: 0;
        }

        .mobile-menu li {
            margin-bottom: 1.2rem;
        }

        .mobile-menu a {
            color: var(--text-color);
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            display: block;
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            transition: background-color 0.3s;
        }

        .mobile-menu a:hover {
            background-color: rgba(255,255,255,0.1);
        }

        /* 遮罩层 */
        .overlay {
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        /* 响应式断点 */
        @media (max-width: 768px) {
            .desktop-menu {
                display: none;
            }
            
            .hamburger {
                display: flex;
            }
            
            .main-nav {
                justify-content: space-between;
                width: 100%;
            }
        }

        /* 动画效果 */
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* 按钮悬停效果 */
        .desktop-menu li a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* 用户信息下拉菜单 */
        .user-dropdown {
            position: relative;
            cursor: pointer;
        }

        .user-dropdown:hover .dropdown-content,
        .user-dropdown:focus-within .dropdown-content {
            display: block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 160px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            z-index: 1000;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
            display: block;
            transition: background 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f1f1f1;
        }

        /* 移动端优化 */
        @media (max-width: 480px) {
            .logo {
                font-size: 1.2rem;
            }
            
            .mobile-avatar {
                width: 36px;
                height: 36px;
            }
            
            .mobile-username {
                font-size: 0.95rem;
            }
            
            .mobile-menu a {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="main-nav">
                <div class="logo">智能客服管理后台</div>
                
                <!-- 桌面菜单 -->
                <ul class="desktop-menu">
                    <?php if ($user): ?>
                        <li class="user-dropdown">
                            <div class="user-info d-flex align-items-center">
                                <span class="ms-2"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="dropdown-content">
                                <a class="dropdown-item" href="profile.php">个人资料</a>
                                <a class="dropdown-item" href="settings.php">设置</a>
                                <a class="dropdown-item text-danger" href="logout.php">退出登录</a>
                            </div>
                        </li>
                        
                        <li><a href="index.php">数据看板</a></li>
                        <li><a href="settings.php">系统设置</a></li>
                        
                        <!-- 仅管理员显示用户管理 -->
                        <?php if ($isAdmin): ?>
                            <li><a href="users.php">用户管理</a></li>
                            <li><a href="invite-manager.php">邀请码管理</a></li>
                        <?php endif; ?>
                        
                        <li><a href="logs.php">日志查看</a></li>
                    <?php else: ?>
                        <li><a href="login.php">登录</a></li>
                        <li><a href="register.php">注册</a></li>
                    <?php endif; ?>
                    
                </ul>
                
                <!-- 移动汉堡按钮 -->
                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </header>

    <!-- 移动菜单 -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-user">
            <div class="mobile-avatar"><?= substr($username, 0, 1) ?></div>
            <div class="mobile-username"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        
        <ul>
            <?php if ($user): ?>
                <li><a href="profile.php">个人资料</a></li>
                <li><a href="index.php">数据看板</a></li>
                <li><a href="settings.php">系统设置</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="users.php">用户管理</a></li>
                    <li><a href="invite-manager.php">邀请码管理</a></li>
                <?php endif; ?>
                <li><a href="logs.php">日志查看</a></li>
                <li><a href="logout.php">退出登录</a></li>
            <?php else: ?>
                <li><a href="login.php">登录</a></li>
                <li><a href="register.php">注册</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- 遮罩层 -->
    <div class="overlay" id="overlay"></div>

    <script>
        // 移动端菜单控制
        document.addEventListener('DOMContentLoaded', function () {
            const hamburger = document.getElementById('hamburger');
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.getElementById('overlay');

            // 切换菜单
            hamburger.addEventListener('click', function () {
                mobileMenu.classList.toggle('active');
                overlay.classList.toggle('active');
                this.classList.toggle('active');
            });

            // 点击遮罩关闭菜单
            overlay.addEventListener('click', function () {
                mobileMenu.classList.remove('active');
                this.classList.remove('active');
                hamburger.classList.remove('active');
            });

            // 点击外部区域关闭菜单
            document.addEventListener('click', function (event) {
                if (!mobileMenu.contains(event.target) && 
                    !hamburger.contains(event.target) &&
                    window.innerWidth <= 768) {
                    mobileMenu.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            });

            // 窗口大小变化时重置状态
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    mobileMenu.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>