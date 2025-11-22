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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YiZi AI- 智能客服</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 主题系统 */
        :root {
            --primary: #3b82f6;
            --primary-light: #93c5fd;
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --light: #f8fafc;
            --gray: #94a3b8;
            --surface-0: #ffffff;
            --surface-1: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --transition: all 0.3s ease;
        }

        [data-bs-theme="dark"] {
            --primary: #60a5fa;
            --primary-light: #bfdbfe;
            --secondary: #a78bfa;
            --accent: #fcd34d;
            --success: #34d399;
            --danger: #f87171;
            --warning: #fcd34d;
            --dark: #1e293b;
            --light: #1e293b;
            --gray: #94a3b8;
            --surface-0: #1e293b;
            --surface-1: #0f172a;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }

        body {
            background-color: var(--surface-1);
            color: var(--text-primary);
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            transition: background-color 0.3s;
        }

        /* 布局系统 */
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .main-layout {
            flex: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* 侧边栏 */
        .sidebar {
            width: 280px;
            background: linear-gradient(145deg, var(--surface-0), var(--surface-1));
            border-right: 1px solid rgba(0,0,0,0.1);
            overflow-y: auto;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        /* 聊天区域 */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--surface-0);
            position: relative;
        }

        /* 消息历史 */
        .message-history {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            scroll-behavior: smooth;
        }

        /* 消息气泡 */
        .message-bubble {
            max-width: 70%;
            margin: 1.2rem 0;
            padding: 1rem 1.2rem;
            border-radius: 1.25rem;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            animation: fadeInUp 0.5s ease-out;
            word-break: break-word;
            transition: transform 0.2s;
        }

        .message-bubble:hover {
            transform: translateX(-5px);
        }

        .user-bubble {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            margin-left: auto;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 1rem 100%, 0 calc(100% - 1rem));
        }

        .assistant-bubble {
            background: var(--surface-0);
            border: 1px solid rgba(0,0,0,0.05);
            margin-right: auto;
            clip-path: polygon(0 0, 100% 0, calc(100% - 1rem) 100%, 0 100%);
        }

        /* 输入区域 */
        .input-area {
            border-top: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
            background: var(--surface-0);
        }

        /* 工具按钮 */
        .tool-button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: var(--surface-1);
            color: var(--text-secondary);
            transition: all 0.3s;
        }

        .tool-button:hover {
            background: var(--primary);
            color: white;
        }

        /* 模型状态徽章 */
        .model-badge {
            background: linear-gradient(45deg, var(--primary), var(--primary-light));
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .model-badge i {
            font-size: 0.75rem;
        }

        /* 卡片组件 */
        .card-component {
            background: var(--surface-0);
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .card-component:hover {
            transform: translateY(-5px);
        }

        /* 移动端优化 */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
            }

            .sidebar {
                position: fixed;
                top: 0;
                right: -100%;
                bottom: 0;
                z-index: 1050;
                width: 80%;
                max-width: 300px;
                transition: right 0.3s ease-in-out;
                box-shadow: -2px 0 12px rgba(0,0,0,0.1);
            }

            .sidebar.active {
                right: 0;
            }

            .mobile-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: var(--surface-0);
                display: flex;
                justify-content: space-around;
                align-items: center;
                z-index: 1000;
                border-top: 1px solid rgba(0,0,0,0.1);
            }

            .message-bubble {
                max-width: 85%;
            }

            .input-area {
                position: fixed;
                bottom: 60px;
                left: 0;
                right: 0;
                padding: 1rem;
                z-index: 999;
            }

            .message-history {
                padding-bottom: 120px;
            }
        }

        /* 动画 */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 打字光标 */
        .cursor {
            display: inline-block;
            width: 4px;
            height: 1.2em;
            background: var(--primary);
            margin-left: 2px;
            animation: blink 1s step-end infinite;
        }

        @keyframes blink {
            50% { opacity: 0; }
        }

        /* 文件上传 */
        .file-upload {
            border: 2px dashed rgba(59, 130, 246, 0.3);
            border-radius: 1rem;
            padding: 1.2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .file-upload:hover {
            background-color: rgba(59, 130, 246, 0.05);
            border-color: var(--primary);
        }

        /* 主题切换按钮 */
        .theme-toggle {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: var(--surface-1);
            color: var(--text-secondary);
            transition: all 0.3s;
        }

        .theme-toggle:hover {
            background: var(--primary);
            color: white;
        }

        /* 示例按钮 */
        .example-btn {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            border-radius: 1rem;
            padding: 0.5rem 1rem;
            transition: all 0.3s;
        }

        .example-btn:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <!-- 移动端侧边栏遮罩 -->
    <div class="sidebar-backdrop d-none" onclick="toggleSidebar()"></div>

    <!-- 移动端底部工具栏 -->
    <div class="mobile-footer d-flex d-md-none justify-content-around align-items-center">
        <button class="btn btn-outline-primary rounded-pill" onclick="document.getElementById('userInput').focus()">
            <i class="fas fa-plus"></i> 新问题
        </button>
        <button class="btn btn-outline-primary rounded-pill" onclick="toggleSidebar()">
            <i class="fas fa-sliders-h"></i> 设置
        </button>
    </div>

    <!-- 主体布局 -->
    <div class="app-container">
        <!-- 顶部导航栏 -->
        <nav class="navbar navbar-dark" style="background: var(--dark);">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <div class="logo me-2" style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 8px;"></div>
                    <span class="fw-bold fs-5">YiZi AI</span>
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="model-badge" id="modelStatus">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>加载中...</span>
                    </div>
                    <button class="theme-toggle" onclick="toggleTheme()">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- 主要内容区域 -->
        <div class="main-layout">
            <!-- 侧边栏 -->
            <aside class="sidebar" id="sidebar">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-bold">设置</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary rounded-circle" onclick="saveSettings()">
                            <i class="fas fa-save"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary rounded-circle d-md-none" onclick="toggleSidebar()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">模型选择</label>
                    <select class="form-select rounded-pill" id="modelSelector" aria-label="选择模型">
                        <option value="">加载中...</option>
                    </select>
                </div>

                <div class="mb-4">
                    <div class="card-component p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold">知识库</h6>
                            <button class="tool-button" title="刷新">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>
                        <ul class="list-unstyled mb-0" id="kbList">
                            <li class="placeholder-shimmer rounded-pill py-2 mb-2"></li>
                            <li class="placeholder-shimmer rounded-pill py-2 mb-2"></li>
                            <li class="placeholder-shimmer rounded-pill py-2"></li>
                        </ul>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">主题模式</label>
                    <div class="btn-group w-100" role="group" aria-label="主题切换">
                        <input type="radio" class="btn-check" name="themeOptions" id="themeLight" autocomplete="off" checked>
                        <label class="btn btn-outline-primary rounded-pill" for="themeLight">
                            <i class="fas fa-sun me-1"></i> 浅色
                        </label>
                        
                        <input type="radio" class="btn-check" name="themeOptions" id="themeDark" autocomplete="off">
                        <label class="btn btn-outline-primary rounded-pill" for="themeDark">
                            <i class="fas fa-moon me-1"></i> 深色
                        </label>
                    </div>
                </div>

                <div class="card-component p-3 mb-4">
                    <h6 class="card-title mb-3 fw-semibold">快捷操作</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger rounded-pill" onclick="clearHistory()">
                            <i class="fas fa-trash me-2"></i> 清除记录
                        </button>
                        <button class="btn btn-outline-success rounded-pill" onclick="exportConversation()">
                            <i class="fas fa-download me-2"></i> 导出对话
                        </button>
                    </div>
                </div>

                <div class="text-center text-muted small">
                    <p class="mb-0">YiZi AI v1.0</p>
                    <p class="mb-0">© 2023-2024</p>
                </div>
            </aside>

            <!-- 聊天主区域 -->
            <div class="chat-container">
                <!-- 消息历史 -->
                <div class="message-history" id="chatWindow">
                    <div class="welcome-message text-center py-5">
                        <div class="mb-4" style="font-size: 3.5rem">🚀</div>
                        <h2 class="mb-3 fw-semibold">欢迎使用 YiZi</h2>
                        <p class="text-muted">您的智能生产力伙伴</p>
                        <div class="mt-4">
                            <button class="example-btn me-2 mb-2" onclick="showExamples()">查看示例问题</button>
                        </div>
                    </div>
                </div>

                <!-- 输入区域 -->
                <div class="input-area">
                    <div class="file-upload mb-3" 
                         onclick="document.getElementById('fileUpload').click()"
                         ondragover="event.preventDefault(); this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')"
                         ondrop="handleFileDrop(event)">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        拖放文件或点击上传
                        <input type="file" id="fileUpload" class="d-none" accept=".txt,.pdf,.docx,.md,.csv">
                    </div>
                    
                    <div class="input-group">
                        <input type="text" id="userInput" class="form-control rounded-pill py-3" placeholder="输入您的问题..." 
                               onkeypress="if(event.keyCode == 13) sendMessage()">
                        <button class="btn btn-primary rounded-circle ms-2 p-3" onclick="sendMessage()" title="发送消息">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 示例问题模态框 -->
    <div class="modal fade" id="examplesModal" tabindex="-1" aria-labelledby="examplesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title" id="examplesModalLabel">示例问题</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary rounded-pill" onclick="useExample(this)" data-example="解释相对论的基本原理">解释相对论的基本原理</button>
                        <button class="btn btn-outline-primary rounded-pill" onclick="useExample(this)" data-example="帮我写一篇关于气候变化的演讲稿">帮我写一篇关于气候变化的演讲稿</button>
                        <button class="btn btn-outline-primary rounded-pill" onclick="useExample(this)" data-example="列出Python中常用的机器学习库及其用途">列出Python中常用的机器学习库及其用途</button>
                        <button class="btn btn-outline-primary rounded-pill" onclick="useExample(this)" data-example="分析这份销售数据中的关键趋势">分析这份销售数据中的关键趋势</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 初始化主题
        document.addEventListener('DOMContentLoaded', function () {
            const theme = localStorage.getItem('theme') || 
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            applyTheme(theme);
            
            // 初始化主题切换按钮
            document.querySelector('input[name="themeOptions"][value="'+theme+'"]')?.click();
            document.querySelectorAll('input[name="themeOptions"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    applyTheme(this.id === 'themeLight' ? 'light' : 'dark');
                });
            });
        });

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
        }

        // 侧边栏控制
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.querySelector('.sidebar-backdrop');
            sidebar.classList.toggle('active');
            backdrop.classList.toggle('d-none');
        }

        // 主题切换
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
            document.querySelector(`input[name="themeOptions"][value="${newTheme}"]`).checked = true;
        }

        // 消息处理示例
        const ChatHandler = {
            sendMessage() {
                const input = document.getElementById('userInput');
                const message = input.value.trim();
                if (!message) return;
                
                const chatWindow = document.getElementById('chatWindow');
                const userMsg = document.createElement('div');
                userMsg.className = 'message-bubble user-bubble';
                userMsg.innerHTML = `<div class="message-content">${message}</div>`;
                chatWindow.appendChild(userMsg);
                input.value = '';
                
                const assistantMsg = document.createElement('div');
                assistantMsg.className = 'message-bubble assistant-bubble';
                assistantMsg.innerHTML = `
                    <div class="message-content">
                        正在思考...
                        <span class="cursor"></span>
                    </div>
                `;
                chatWindow.appendChild(assistantMsg);
                
                chatWindow.scrollTop = chatWindow.scrollHeight;
                
                setTimeout(() => {
                    assistantMsg.querySelector('.cursor').remove();
                    assistantMsg.querySelector('.message-content').innerHTML = `这是对您问题的回复示例。`;
                }, 2000);
            },
            clearHistory() {
                if (confirm('确定要清除聊天记录吗？')) {
                    document.getElementById('chatWindow').innerHTML = '';
                }
            }
        };

        // UI处理
        const UIHandler = {
            toggleTheme,
            showExamples() {
                new bootstrap.Modal(document.getElementById('examplesModal')).show();
            },
            useExample(btn) {
                document.getElementById('userInput').value = btn.getAttribute('data-example');
                document.getElementById('examplesModal').querySelector('.btn-close').click();
                ChatHandler.sendMessage();
            },
            exportConversation() {
                alert('导出功能待实现');
            },
            saveSettings() {
                alert('保存设置功能待实现');
            }
        };
    </script>
</body>
</html>