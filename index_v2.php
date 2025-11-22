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
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>YiZi AI - 智能助手</title>
    
    <!-- CSS框架 -->
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* 全局变量系统 */
        :root {
            /* 主色调 */
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93c5fd;
            --primary-400: #60a5fa;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-800: #1e40af;
            --primary-900: #1e3a8a;
            
            /* 渐变色 */
            --gradient-primary: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            --gradient-secondary: linear-gradient(135deg, var(--primary-400), var(--primary-600));
            --gradient-accent: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981, #059669);
            --gradient-warning: linear-gradient(135deg, #f59e0b, #d97706);
            --gradient-danger: linear-gradient(135deg, #ef4444, #dc2626);
            
            /* 表面颜色 */
            --surface-0: #ffffff;
            --surface-50: #fafafa;
            --surface-100: #f4f4f5;
            --surface-200: #e4e4e7;
            --surface-300: #d4d4d8;
            --surface-400: #a1a1aa;
            --surface-500: #71717a;
            --surface-600: #52525b;
            --surface-700: #3f3f46;
            --surface-800: #27272a;
            --surface-900: #18181b;
            
            /* 文本颜色 */
            --text-primary: var(--surface-900);
            --text-secondary: var(--surface-600);
            --text-tertiary: var(--surface-500);
            --text-inverse: var(--surface-0);
            
            /* 阴影系统 */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            /* 间距系统 */
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-10: 2.5rem;
            --space-12: 3rem;
            
            /* 边框半径 */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-full: 9999px;
            
            /* 过渡效果 */
            --transition-fast: 150ms ease-in-out;
            --transition-normal: 250ms ease-in-out;
            --transition-slow: 350ms ease-in-out;
        }
        
        /* 暗色主题 */
        [data-theme="dark"] {
            --surface-0: var(--surface-900);
            --surface-50: var(--surface-800);
            --surface-100: var(--surface-700);
            --surface-200: var(--surface-600);
            --surface-300: var(--surface-500);
            --surface-400: var(--surface-400);
            --surface-500: var(--surface-300);
            --surface-600: var(--surface-200);
            --surface-700: var(--surface-100);
            --surface-800: var(--surface-50);
            --surface-900: var(--surface-0);
            
            --text-primary: var(--surface-100);
            --text-secondary: var(--surface-300);
            --text-tertiary: var(--surface-400);
            --text-inverse: var(--surface-900);
        }
        
        /* 全局样式重置 */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0f4ff 100%);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background var(--transition-slow);
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--surface-900) 0%, var(--surface-800) 50%, var(--surface-700) 100%);
        }
        
        /* 布局容器 */
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            max-width: 100vw;
            overflow: hidden;
        }
        
        /* 顶部导航栏 */
        .top-navbar {
            background: var(--surface-0);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--surface-200);
            padding: var(--space-4) var(--space-6);
            box-shadow: var(--shadow-sm);
            z-index: 100;
            transition: all var(--transition-normal);
        }
        
        [data-theme="dark"] .top-navbar {
            background: rgba(39, 39, 42, 0.8);
            border-bottom-color: var(--surface-600);
        }
        
        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .brand-section {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        
        .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-primary);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-inverse);
            font-size: 1.25rem;
            box-shadow: var(--shadow-md);
        }
        
        .brand-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        
        .user-section {
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }
        
        .user-status {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2) var(--space-3);
            background: var(--surface-100);
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all var(--transition-normal);
        }
        
        [data-theme="dark"] .user-status {
            background: var(--surface-700);
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: var(--radius-full);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-2);
            border-radius: var(--radius-lg);
            transition: all var(--transition-normal);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--gradient-accent);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-inverse);
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: var(--shadow-md);
        }
        
        .user-name {
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .user-info {
            position: relative;
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition-normal);
        }
        
        .user-info:hover {
            background: var(--surface-100);
        }
        
        [data-theme="dark"] .user-info:hover {
            background: var(--surface-700);
        }
        
        .user-menu-arrow {
            font-size: 0.75rem;
            color: var(--text-secondary);
            transition: transform var(--transition-normal);
        }
        
        .user-info.active .user-menu-arrow {
            transform: rotate(180deg);
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: var(--space-2);
            background: var(--surface-0);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all var(--transition-normal);
            z-index: 1000;
        }
        
        .user-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown-menu .dropdown-item {
            display: flex;
            align-items: center;
            padding: var(--space-3) var(--space-4);
            color: var(--text-primary);
            text-decoration: none;
            transition: all var(--transition-fast);
            font-size: 0.875rem;
        }
        
        .user-dropdown-menu .dropdown-item:hover {
            background: var(--surface-50);
            color: var(--primary);
        }
        
        .user-dropdown-menu .dropdown-item.text-danger {
            color: var(--danger);
        }
        
        .user-dropdown-menu .dropdown-item.text-danger:hover {
            background: var(--danger-light);
            color: var(--danger);
        }
        
        [data-theme="dark"] .user-dropdown-menu {
            background: var(--surface-800);
            border-color: var(--border-dark);
        }
        
        [data-theme="dark"] .user-dropdown-menu .dropdown-item:hover {
            background: var(--surface-700);
        }
        
        .theme-toggle {
            width: 44px;
            height: 44px;
            border: none;
            background: var(--surface-100);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }
        
        [data-theme="dark"] .theme-toggle {
            background: var(--surface-700);
        }
        
        .theme-toggle:hover {
            background: var(--primary-100);
            color: var(--primary-700);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        [data-theme="dark"] .theme-toggle:hover {
            background: var(--surface-600);
        }
        
        /* 主要内容区域 */
        .main-content {
            flex: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }
        
        /* 聊天区域 */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }
        
        .chat-header {
            padding: var(--space-4) var(--space-6);
            background: var(--surface-0);
            border-bottom: 1px solid var(--surface-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
        }
        
        [data-theme="dark"] .chat-header {
            background: rgba(39, 39, 42, 0.8);
            border-bottom-color: var(--surface-600);
        }
        
        .chat-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }
        
        .model-selector {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .model-badge {
            padding: var(--space-2) var(--space-3);
            background: var(--primary-100);
            color: var(--primary-700);
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid var(--primary-200);
        }
        
        [data-theme="dark"] .model-badge {
            background: var(--surface-700);
            color: var(--primary-300);
            border-color: var(--surface-600);
        }
        
        /* 消息容器 */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: var(--space-6);
            background: transparent;
            scroll-behavior: smooth;
        }
        
        .messages-wrapper {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: var(--space-6);
        }
        
        /* 欢迎页面 */
        .welcome-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: var(--space-12) var(--space-6);
            background: var(--surface-0);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
            margin: var(--space-6) 0;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        [data-theme="dark"] .welcome-section {
            background: var(--surface-800);
        }
        
        .welcome-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: var(--radius-2xl);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-inverse);
            font-size: 2.5rem;
            margin-bottom: var(--space-6);
            box-shadow: var(--shadow-xl);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--space-3);
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-subtitle {
            color: var(--text-secondary);
            font-size: 1.125rem;
            margin-bottom: var(--space-6);
            max-width: 400px;
        }
        
        .welcome-actions {
            display: flex;
            gap: var(--space-3);
            flex-wrap: wrap;
            justify-content: center;
        }
        
        /* 消息样式 */
        .message-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
            animation: slideInUp var(--transition-normal);
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            max-width: 85%;
        }
        
        .message.user {
            margin-left: auto;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
            box-shadow: var(--shadow-md);
        }
        
        .message.user .message-avatar {
            background: var(--gradient-primary);
            color: var(--text-inverse);
        }
        
        .message.assistant .message-avatar {
            background: var(--gradient-accent);
            color: var(--text-inverse);
        }
        
        .message-bubble {
            position: relative;
            padding: var(--space-4) var(--space-5);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        .message.user .message-bubble {
            background: var(--gradient-primary);
            color: var(--text-inverse);
            border-bottom-right-radius: var(--radius-md);
        }
        
        .message.assistant .message-bubble {
            background: var(--surface-0);
            border: 1px solid var(--surface-200);
            color: var(--text-primary);
            border-bottom-left-radius: var(--radius-md);
        }
        
        [data-theme="dark"] .message.assistant .message-bubble {
            background: var(--surface-800);
            border-color: var(--surface-600);
            color: var(--text-primary);
        }
        
        .message-bubble:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }
        
        .message-content {
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .message-content code {
            background: var(--surface-200);
            padding: 0.125rem 0.25rem;
            border-radius: var(--radius-sm);
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
            font-size: 0.875em;
        }
        
        [data-theme="dark"] .message-content code {
            background: var(--surface-700);
        }
        
        .message-content pre {
            background: var(--surface-100);
            border: 1px solid var(--surface-200);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            overflow-x: auto;
            margin: var(--space-3) 0;
        }
        
        [data-theme="dark"] .message-content pre {
            background: var(--surface-800);
            border-color: var(--surface-600);
        }
        
        .message-time {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            margin-top: var(--space-2);
            text-align: right;
        }
        
        .message-actions {
            position: absolute;
            top: var(--space-2);
            right: var(--space-2);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }
        
        .message:hover .message-actions {
            opacity: 1;
        }
        
        .action-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: rgba(0, 0, 0, 0.1);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            color: inherit;
        }
        
        [data-theme="dark"] .action-btn {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .action-btn:hover {
            background: rgba(0, 0, 0, 0.2);
            transform: scale(1.1);
        }
        
        /* 输入区域 */
        .input-section {
            padding: var(--space-6);
            background: var(--surface-0);
            border-top: 1px solid var(--surface-200);
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] .input-section {
            background: rgba(39, 39, 42, 0.95);
            border-top-color: var(--surface-600);
        }
        
        .input-wrapper {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        
        .file-upload-area {
            margin-bottom: var(--space-4);
        }
        
        .dropzone {
            border: 2px dashed var(--surface-300);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            text-align: center;
            background: var(--surface-50);
            cursor: pointer;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        [data-theme="dark"] .dropzone {
            border-color: var(--surface-600);
            background: var(--surface-800);
        }
        
        .dropzone:hover,
        .dropzone.dragover {
            border-color: var(--primary-400);
            background: var(--primary-50);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        [data-theme="dark"] .dropzone:hover,
        [data-theme="dark"] .dropzone.dragover {
            border-color: var(--primary-500);
            background: rgba(37, 99, 235, 0.1);
        }
        
        .dropzone-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-3);
            color: var(--text-secondary);
        }
        
        .file-info {
            margin-top: var(--space-2);
            padding: var(--space-2) var(--space-3);
            background: var(--primary-100);
            border-radius: var(--radius-lg);
            color: var(--primary-700);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        [data-theme="dark"] .file-info {
            background: var(--surface-700);
            color: var(--primary-300);
        }
        
        .input-container {
            display: flex;
            align-items: flex-end;
            gap: var(--space-3);
            background: var(--surface-0);
            border: 2px solid var(--surface-200);
            border-radius: var(--radius-2xl);
            padding: var(--space-3);
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }
        
        [data-theme="dark"] .input-container {
            background: var(--surface-800);
            border-color: var(--surface-600);
        }
        
        .input-container:focus-within {
            border-color: var(--primary-400);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }
        
        .message-input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            resize: none;
            padding: var(--space-2) var(--space-3);
            font-size: 1rem;
            line-height: 1.5;
            max-height: 120px;
            min-height: 44px;
            color: var(--text-primary);
        }
        
        .message-input::placeholder {
            color: var(--text-tertiary);
        }
        
        .send-button {
            width: 44px;
            height: 44px;
            border: none;
            background: var(--gradient-primary);
            color: var(--text-inverse);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
        }
        
        .send-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* 侧边栏控制面板 */
        .control-sidebar {
            width: 320px;
            background: var(--surface-0);
            border-left: 1px solid var(--surface-200);
            padding: var(--space-6);
            overflow-y: auto;
            transition: transform var(--transition-normal);
        }
        
        [data-theme="dark"] .control-sidebar {
            background: var(--surface-800);
            border-left-color: var(--surface-600);
        }
        
        .sidebar-section {
            margin-bottom: var(--space-6);
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: var(--space-4);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .model-select {
            width: 100%;
            padding: var(--space-3);
            border: 1px solid var(--surface-300);
            border-radius: var(--radius-lg);
            background: var(--surface-0);
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all var(--transition-normal);
            margin-bottom: var(--space-3);
        }
        
        [data-theme="dark"] .model-select {
            background: var(--surface-700);
            border-color: var(--surface-500);
            color: var(--text-primary);
        }
        
        .model-select:focus {
            outline: none;
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-text {
            color: var(--text-tertiary);
            font-size: 0.875rem;
            margin-top: var(--space-2);
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
        }
        
        .btn-action {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-3) var(--space-4);
            border: 1px solid var(--surface-300);
            border-radius: var(--radius-lg);
            background: var(--surface-0);
            color: var(--text-primary);
            cursor: pointer;
            transition: all var(--transition-normal);
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        [data-theme="dark"] .btn-action {
            background: var(--surface-700);
            border-color: var(--surface-500);
            color: var(--text-primary);
        }
        
        .btn-action:hover {
            border-color: var(--primary-400);
            background: var(--primary-50);
            color: var(--primary-700);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        [data-theme="dark"] .btn-action:hover {
            background: var(--surface-600);
            color: var(--primary-300);
        }
        
        /* 移动端优化 */
        @media (max-width: 768px) {
            .top-navbar {
                padding: var(--space-3) var(--space-4);
            }
            
            .navbar-content {
                padding: 0;
            }
            
            .brand-text {
                font-size: 1.25rem;
            }
            
            .user-section {
                gap: var(--space-2);
            }
            
            .user-name {
                display: none;
            }
            
            .control-sidebar {
                position: fixed;
                top: 0;
                right: -100%;
                width: 85%;
                height: 100vh;
                z-index: 1000;
                box-shadow: var(--shadow-2xl);
            }
            
            .control-sidebar.open {
                right: 0;
            }
            
            .input-section {
                padding: var(--space-4);
            }
            
            .messages-container {
                padding: var(--space-4);
            }
            
            .message {
                max-width: 95%;
            }
            
            .welcome-section {
                padding: var(--space-8) var(--space-4);
                margin: var(--space-4) 0;
            }
            
            .welcome-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
        }
        
        /* 隐藏类 */
        .d-none {
            display: none !important;
        }
        
        /* 辅助类 */
        .text-center { text-align: center; }
        .text-muted { color: var(--text-tertiary); }
        
        /* 动画 */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* 加载状态 */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--surface-300);
            border-radius: 50%;
            border-top-color: var(--primary-500);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body data-theme="light">
    <!-- 主应用容器 -->
    <div class="app-container">
        <!-- 顶部导航栏 -->
        <nav class="top-navbar">
            <div class="navbar-content">
                <div class="brand-section">
                    <div class="brand-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h1 class="brand-text">YiZi AI</h1>
                </div>
                
                <div class="user-section">
                    <div class="user-status">
                        <div class="status-indicator"></div>
                        <span id="userStatusText"><?= $user ? '已登录' : '游客模式' ?></span>
                    </div>
                    
                    <div class="user-info" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?= strtoupper(substr($username, 0, 1)) ?>
                        </div>
                        <span class="user-name"><?= htmlspecialchars($username) ?></span>
                        <i class="fas fa-chevron-down user-menu-arrow"></i>
                    </div>
                    
                    <!-- 用户下拉菜单 -->
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <a href="admin/profile.php" class="dropdown-item">
                            <i class="fas fa-user me-2"></i>个人资料
                        </a>
                        <?php if ($user): ?>
                            <a href="admin/logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>退出登录
                            </a>
                        <?php else: ?>
                            <a href="admin/login.php" class="dropdown-item">
                                <i class="fas fa-sign-in-alt me-2"></i>登录
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <button class="theme-toggle" onclick="toggleTheme()" title="切换主题">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                </div>
            </div>
        </nav>
        
        <!-- 主要内容区域 -->
        <div class="main-content">
            <!-- 聊天区域 -->
            <div class="chat-area">
                <!-- 聊天标题栏 -->
                <div class="chat-header">
                    <h2 class="chat-title">智能对话</h2>
                    <div class="model-selector">
                        <div class="model-badge" id="modelStatus">
                            <i class="fas fa-cog me-1"></i>
                            模型未选择
                        </div>
                    </div>
                </div>
                
                <!-- 消息容器 -->
                <div class="messages-container" id="messagesContainer">
                    <div class="messages-wrapper" id="messagesWrapper">
                        <!-- 欢迎页面 -->
                        <div class="welcome-section fade-in">
                            <div class="welcome-icon">
                                <i class="fas fa-sparkles"></i>
                            </div>
                            <h2 class="welcome-title">欢迎使用 YiZi AI</h2>
                            <p class="welcome-subtitle">
                                您的智能助手，随时为您解答问题<br>
                                <?php if (!$user): ?>
                                    <strong class="text-warning">游客模式：只能使用基础模型</strong><br>
                                    <a href="admin/register.php" class="text-primary">注册账号</a> 解锁更多功能
                                <?php else: ?>
                                    <strong class="text-success">已登录用户：可以使用全部模型</strong>
                                <?php endif; ?>
                            </p>
                            <div class="welcome-actions">
                                <button class="btn-gradient" onclick="showExamples()">
                                    <i class="fas fa-lightbulb me-2"></i>查看示例
                                </button>
                                <button class="btn-action" onclick="clearHistory()">
                                    <i class="fas fa-refresh me-2"></i>清空对话
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 输入区域 -->
                <div class="input-section">
                    <div class="input-wrapper">
                        <!-- 文件上传区域 -->
                        <div class="file-upload-area">
                            <div class="dropzone" onclick="document.getElementById('fileInput').click()"
                                 ondragover="event.preventDefault(); this.classList.add('dragover')"
                                 ondragleave="this.classList.remove('dragover')"
                                 ondrop="handleFileDrop(event)">
                                <div class="dropzone-content">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>拖放文件或点击上传</span>
                                </div>
                                <input type="file" id="fileInput" class="d-none" 
                                       accept=".txt,.pdf,.docx,.md,.csv,.json,.xml">
                                <div id="fileInfo" class="file-info d-none">
                                    <i class="fas fa-file"></i>
                                    <span id="fileName"></span>
                                    <button type="button" onclick="clearFile()" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 输入框容器 -->
                        <div class="input-container">
                            <textarea id="messageInput" class="message-input" 
                                      placeholder="输入您的消息... (Enter发送，Shift+Enter换行)"
                                      rows="1"></textarea>
                            <button id="sendButton" class="send-button" onclick="sendMessage()" disabled>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 侧边栏控制面板 -->
            <aside class="control-sidebar" id="controlSidebar">
                <div class="sidebar-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i>
                        AI 模型配置
                    </h3>
                    <select id="modelSelect" class="model-select" onchange="updateModelStatus()">
                        <option value="">加载模型中...</option>
                    </select>
                    <div class="form-text">
                        <?php if (!$user): ?>
                            <i class="fas fa-info-circle me-1"></i>
                            游客只能使用基础模型，注册后解锁更多选项
                        <?php else: ?>
                            <i class="fas fa-check-circle me-1"></i>
                            登录用户可使用全部模型
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3 class="section-title">
                        <i class="fas fa-tools"></i>
                        操作工具
                    </h3>
                    <div class="action-buttons">
                        <button class="btn-action" onclick="clearHistory()">
                            <i class="fas fa-trash"></i>
                            <span>清空对话记录</span>
                        </button>
                        <button class="btn-action" onclick="exportConversation()">
                            <i class="fas fa-download"></i>
                            <span>导出对话记录</span>
                        </button>
                        <button class="btn-action" onclick="showSettings()">
                            <i class="fas fa-cog"></i>
                            <span>系统设置</span>
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    
    <!-- 移动端遮罩层 -->
    <div class="d-md-none" id="mobileOverlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; display: none;"></div>
    
    <!-- JavaScript 框架 -->
    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/marked/5.0.0/marked.min.js"></script>
    
    <script>
        // 全局状态管理
        let currentFile = null;
        let isLoading = false;
        let chatHistory = JSON.parse(localStorage.getItem('chatHistory') || '[]');
        
        // 工具函数
        const Utils = {
            // 防抖函数
            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            },
            
            // 格式化时间
            formatTime(date = new Date()) {
                return date.toLocaleTimeString('zh-CN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            
            // 生成唯一ID
            generateId() {
                return Date.now().toString(36) + Math.random().toString(36).substr(2);
            },
            
            // 显示提示消息
            showToast(message, type = 'info') {
                // 简化版提示，可根据需要集成更完整的Toast组件
                console.log(`[${type.toUpperCase()}] ${message}`);
                alert(message);
            }
        };
        
        // 主题管理
        const ThemeManager = {
            init() {
                const savedTheme = localStorage.getItem('theme') || 'light';
                this.setTheme(savedTheme);
            },
            
            toggle() {
                const currentTheme = document.body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                this.setTheme(newTheme);
            },
            
            setTheme(theme) {
                document.body.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                
                const themeIcon = document.getElementById('themeIcon');
                themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        };
        
        // 聊天功能管理
        const ChatManager = {
            async sendMessage() {
                const input = document.getElementById('messageInput');
                const message = input.value.trim();
                
                if (!message && !currentFile) return;
                
                if (isLoading) return;
                
                try {
                    this.setLoading(true);
                    
                    let finalMessage = message;
                    
                    // 处理文件
                    if (currentFile) {
                        await this.uploadFile();
                        finalMessage = message 
                            ? `${message}\n\n[附件: ${currentFile.original_name}]`
                            : `[附件: ${currentFile.original_name}]`;
                    }
                    
                    // 添加用户消息
                    if (finalMessage) {
                        await this.addMessage('user', finalMessage);
                        chatHistory.push({ role: 'user', content: finalMessage });
                    }
                    
                    // 发送请求
                    const response = await fetch('admin/modelapi.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: finalMessage,
                            history: chatHistory,
                            file: currentFile ? {
                                name: currentFile.original_name,
                                content: currentFile.content,
                                type: currentFile.type
                            } : null,
                            model: document.getElementById('modelSelect').value
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.reply) {
                        await this.addMessage('assistant', data.reply);
                        chatHistory.push({ role: 'assistant', content: data.reply });
                        
                        // 记录聊天
                        this.logChat(finalMessage, data.reply);
                    } else if (data.error) {
                        await this.addMessage('assistant', `错误: ${data.error}`);
                    } else {
                        await this.addMessage('assistant', '抱歉，暂时无法获取回复，请稍后重试。');
                    }
                    
                } catch (error) {
                    console.error('Chat error:', error);
                    let errorMessage = '请求失败，请检查网络连接';
                    
                    if (error.message.includes('500')) {
                        errorMessage = '服务器内部错误，请联系管理员';
                    } else if (error.message.includes('403')) {
                        errorMessage = '权限不足，请检查登录状态';
                    } else if (error.message.includes('404')) {
                        errorMessage = '请求的资源不存在';
                    }
                    
                    await this.addMessage('assistant', `❌ ${errorMessage}`);
                    Utils.showToast(errorMessage, 'error');
                } finally {
                    this.setLoading(false);
                    input.value = '';
                    this.clearFile();
                    this.saveHistory();
                }
            },
            
            async addMessage(role, content) {
                const container = document.getElementById('messagesWrapper');
                const welcomeSection = container.querySelector('.welcome-section');
                
                // 移除欢迎页面
                if (welcomeSection) {
                    welcomeSection.remove();
                }
                
                const messageGroup = document.createElement('div');
                messageGroup.className = 'message-group slide-up';
                
                const avatarChar = role === 'user' 
                    ? '<?= strtoupper(substr($username, 0, 1)) ?>'
                    : 'AI';
                
                messageGroup.innerHTML = `
                    <div class="message ${role}">
                        <div class="message-avatar">${avatarChar}</div>
                        <div class="message-bubble">
                            <div class="message-content">${marked.parse(content)}</div>
                            <div class="message-time">${Utils.formatTime()}</div>
                            <div class="message-actions">
                                <button class="action-btn" onclick="copyMessage(this)" title="复制">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(messageGroup);
                
                // 自动滚动到底部
                this.scrollToBottom();
            },
            
            scrollToBottom() {
                const container = document.getElementById('messagesContainer');
                container.scrollTop = container.scrollHeight;
            },
            
            async uploadFile() {
                if (!currentFile || !currentFile.file) return;
                
                const formData = new FormData();
                formData.append('file', currentFile.file);
                
                const response = await fetch('admin/fileapi.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || '文件上传失败');
                }
                
                const result = await response.json();
                currentFile.uploadId = result.id;
                currentFile.content = result.content || '';
            },
            
            async logChat(message, response) {
                try {
                    await fetch('admin/log.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message,
                            response,
                            model: document.getElementById('modelSelect').value
                        })
                    });
                } catch (error) {
                    console.error('Failed to log chat:', error);
                }
            },
            
            setLoading(loading) {
                isLoading = loading;
                const sendBtn = document.getElementById('sendButton');
                const input = document.getElementById('messageInput');
                
                sendBtn.disabled = loading;
                sendBtn.innerHTML = loading 
                    ? '<div class="loading"></div>' 
                    : '<i class="fas fa-paper-plane"></i>';
            },
            
            clearFile() {
                currentFile = null;
                document.getElementById('fileInput').value = '';
                const fileInfo = document.getElementById('fileInfo');
                fileInfo.classList.add('d-none');
            },
            
            saveHistory() {
                localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
            }
        };
        
        // 模型管理
        const ModelManager = {
            async loadModels() {
                try {
                    const response = await fetch('admin/modelapi.php?action=models');
                    const data = await response.json();
                    const select = document.getElementById('modelSelect');
                    
                    if (data.success && data.models) {
                        select.innerHTML = data.models.map(model => 
                            `<option value="${model}">${model}</option>`
                        ).join('');
                        
                        const savedModel = localStorage.getItem('selectedModel');
                        if (savedModel && data.models.includes(savedModel)) {
                            select.value = savedModel;
                        }
                        
                        this.updateStatus();
                    } else {
                        select.innerHTML = '<option value="">模型加载失败</option>';
                    }
                } catch (error) {
                    console.error('Failed to load models:', error);
                    document.getElementById('modelSelect').innerHTML = 
                        '<option value="">模型加载失败</option>';
                }
            },
            
            updateStatus() {
                const model = document.getElementById('modelSelect').value;
                const status = document.getElementById('modelStatus');
                
                if (model) {
                    status.innerHTML = `<i class="fas fa-check-circle me-1"></i>${model}`;
                    status.className = 'model-badge';
                    localStorage.setItem('selectedModel', model);
                } else {
                    status.innerHTML = '<i class="fas fa-cog me-1"></i>模型未选择';
                    status.className = 'model-badge';
                }
            }
        };
        
        // 全局函数
        window.toggleTheme = () => ThemeManager.toggle();
        window.sendMessage = () => ChatManager.sendMessage();
        window.clearFile = () => ChatManager.clearFile();
        window.toggleUserMenu = () => {
            const userInfo = document.querySelector('.user-info');
            const dropdownMenu = document.getElementById('userDropdownMenu');
            
            if (dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
                userInfo.classList.remove('active');
            } else {
                dropdownMenu.classList.add('show');
                userInfo.classList.add('active');
            }
        };
        window.copyMessage = (btn) => {
            const messageContent = btn.closest('.message-bubble').querySelector('.message-content');
            navigator.clipboard.writeText(messageContent.textContent);
            Utils.showToast('已复制到剪贴板', 'success');
        };
        window.showExamples = () => {
            const examples = [
                '请介绍一下人工智能的发展历程',
                '如何提高工作效率？',
                '帮我写一份工作总结',
                '解释一下区块链技术'
            ];
            const randomExample = examples[Math.floor(Math.random() * examples.length)];
            document.getElementById('messageInput').value = randomExample;
        };
        window.clearHistory = () => {
            if (confirm('确定要清空对话记录吗？')) {
                chatHistory = [];
                ChatManager.saveHistory();
                document.getElementById('messagesWrapper').innerHTML = `
                    <div class="welcome-section fade-in">
                        <div class="welcome-icon"><i class="fas fa-refresh"></i></div>
                        <h2 class="welcome-title">对话已重置</h2>
                        <p class="welcome-subtitle">开始新的对话吧</p>
                        <div class="welcome-actions">
                            <button class="btn-gradient" onclick="showExamples()">
                                <i class="fas fa-lightbulb me-2"></i>查看示例
                            </button>
                        </div>
                    </div>
                `;
                Utils.showToast('对话记录已清空', 'success');
            }
        };
        window.exportConversation = () => {
            const text = chatHistory
                .map(msg => `${msg.role === 'user' ? '[用户]' : '[助手]'}: ${msg.content}`)
                .join('\n');
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `YiZi-chat-${new Date().toISOString().slice(0,10)}.txt`;
            a.click();
            URL.revokeObjectURL(url);
            Utils.showToast('对话已导出', 'success');
        };
        window.showSettings = () => {
            Utils.showToast('功能开发中...', 'info');
        };
        window.updateModelStatus = () => ModelManager.updateStatus();
        window.handleFileDrop = (event) => {
            event.preventDefault();
            event.target.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        };
        
        // 文件处理
        function handleFileSelect(file) {
            if (!file) return;
            
            const allowedTypes = ['txt', 'md', 'pdf', 'doc', 'docx', 'csv', 'json', 'xml'];
            const fileExtension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(fileExtension)) {
                Utils.showToast('不支持的文件格式', 'error');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) { // 10MB
                Utils.showToast('文件大小不能超过10MB', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                currentFile = {
                    file: file,
                    original_name: file.name,
                    type: file.type,
                    size: file.size,
                    content: e.target.result.split(',')[1]
                };
                
                const fileInfo = document.getElementById('fileInfo');
                const fileName = document.getElementById('fileName');
                fileName.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)}MB)`;
                fileInfo.classList.remove('d-none');
                
                Utils.showToast(`已选择文件: ${file.name}`);
            };
            reader.readAsDataURL(file);
        }
        
        // 事件监听器
        document.addEventListener('DOMContentLoaded', () => {
            // 初始化主题
            ThemeManager.init();
            
            // 加载模型
            ModelManager.loadModels();
            
            // 恢复聊天历史
            if (chatHistory.length > 0) {
                chatHistory.forEach(msg => {
                    ChatManager.addMessage(msg.role, msg.content, true);
                });
            }
            
            // 文件输入监听
            document.getElementById('fileInput').addEventListener('change', (e) => {
                if (e.target.files[0]) {
                    handleFileSelect(e.target.files[0]);
                }
            });
            
            // 输入框监听
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            
            messageInput.addEventListener('input', () => {
                const hasContent = messageInput.value.trim() || currentFile;
                sendButton.disabled = !hasContent || isLoading;
                
                // 自动调整高度
                messageInput.style.height = 'auto';
                messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
            });
            
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // 拖拽区域监听
            const dropzone = document.querySelector('.dropzone');
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                });
            });
        });
        
        // 移动端适配
        window.toggleSidebar = () => {
            const sidebar = document.getElementById('controlSidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.style.display = 'none';
            } else {
                sidebar.classList.add('open');
                overlay.style.display = 'block';
            }
        };
        
        // 点击外部关闭用户菜单
        document.addEventListener('click', (event) => {
            const userInfo = document.querySelector('.user-info');
            const dropdownMenu = document.getElementById('userDropdownMenu');
            
            if (!userInfo.contains(event.target) && !dropdownMenu.contains(event.target)) {
                dropdownMenu.classList.remove('show');
                userInfo.classList.remove('active');
            }
        });
        
        document.getElementById('mobileOverlay').addEventListener('click', () => {
            toggleSidebar();
        });
        
        // 添加移动端侧边栏切换按钮
        if (window.innerWidth <= 768) {
            const brandSection = document.querySelector('.brand-section');
            const toggleButton = document.createElement('button');
            toggleButton.className = 'btn btn-outline-primary d-md-none';
            toggleButton.innerHTML = '<i class="fas fa-sliders-h"></i>';
            toggleButton.onclick = toggleSidebar;
            brandSection.appendChild(toggleButton);
        }
    </script>
</body>
</html>