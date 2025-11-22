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
    <title>YiZi AI - 智能客服</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 主题变量 */
        :root {
            --primary: #2563eb;
            --primary-dark: #1e3b8a;
            --secondary: #7c3aed;
            --accent: #fb923c;
            --surface-0: #ffffff;
            --surface-1: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 12px 24px rgba(0,0,0,0.1);
        }
        
        /* 暗色模式变量 */
        [data-bs-theme="dark"] {
            --primary: #4f79e5;
            --primary-dark: #314e8c;
            --secondary: #9d5ef0;
            --accent: #fca54c;
            --surface-0: #1e1e1e;
            --surface-1: #2d2d2d;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
        }
        
        body {
            background-color: var(--surface-1);
            color: var(--text-primary);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            transition: background-color 0.3s ease;
            overscroll-behavior-y: none; /* 防止页面滚动冲突 */
        }
        
        /* 布局系统 */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-history {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            scroll-behavior: smooth;
            background-color: var(--surface-0);
        }
        
        .input-panel {
            border-top: 1px solid rgba(0,0,0,0.1);
            padding: 1rem;
            background: var(--surface-0);
        }
        
        /* 卡片式消息气泡 */
        .message-bubble {
            max-width: 75%;
            margin: 1rem 0;
            padding: 1rem 1.25rem;
            border-radius: 1.25rem;
            position: relative;
            box-shadow: var(--shadow-sm);
            animation: fadeInUp 0.4s ease-out;
            transition: transform 0.2s;
            word-break: break-word;
        }
        
        .message-bubble:hover {
            transform: translateX(-5px);
        }
        
        .user-message {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            margin-left: auto;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 1rem 100%, 0 calc(100% - 1rem));
        }
        
        .assistant-message {
            background-color: var(--surface-0);
            border: 1px solid rgba(0,0,0,0.1);
            margin-right: auto;
            clip-path: polygon(0 0, 100% 0, calc(100% - 1rem) 100%, 0 100%);
        }
        
        /* Markdown 样式优化 */
        .message-content {
            line-height: 1.6;
        }
        
        .message-content code {
            padding: 0.2em 0.4em;
            margin: 0;
            font-size: 0.9em;
            background-color: rgba(0,0,0,0.05);
            border-radius: 0.375rem;
            font-family: monospace;
        }
        
        .message-content pre {
            padding: 1em;
            overflow-x: auto;
            background-color: rgba(0,0,0,0.05);
            border-radius: 0.5rem;
            margin: 1em 0;
        }
        
        .message-content blockquote {
            border-left: 4px solid var(--primary);
            margin-left: 0;
            padding-left: 1em;
            opacity: 0.8;
        }
        
        .message-content ul {
            padding-left: 1.5em;
        }
        
        .message-content ol {
            padding-left: 1.5em;
        }
        
        .message-content a {
            color: var(--primary);
            text-decoration: underline;
        }
        
        /* 时间戳样式 */
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.5rem;
            text-align: right;
        }
        
        /* 控制面板 */
        .control-panel {
            width: 300px;
            min-width: 300px;
            background-color: var(--surface-0);
            border-left: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
        }
        
        /* 移动端优化 */
        @media (max-width: 768px) {
            .chat-history {
                padding: 1rem;
            }
            
            .input-panel {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: var(--surface-0);
                z-index: 1000;
                box-shadow: 0 -2px 12px rgba(0,0,0,0.1);
                padding: 0.75rem 1rem;
            }
            
            .chat-history {
                padding-bottom: 80px;
            }
            
            .mobile-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: var(--surface-0);
                border-top: 1px solid rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-around;
                align-items: center;
                z-index: 999;
            }
            
            /* 优化移动端按钮样式 */
            .form-control {
                font-size: 1.1rem;
                padding: 0.75rem 1.25rem;
            }
            
            .btn-gradient {
                padding: 0.65rem 1.25rem;
                font-size: 1.1rem;
            }
            
            /* 侧边栏伸缩效果 */
            .control-panel {
                position: fixed;
                top: 0;
                bottom: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                z-index: 1050;
                box-shadow: -2px 0 12px rgba(0,0,0,0.1);
                transform: translateX(100%);
            }
            
            .control-panel.visible {
                transform: translateX(0);
            }
            
            /* 遮罩层样式 */
            .offcanvas-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            
            /* 动画效果 */
            @keyframes slideIn {
                from { transform: translateX(100%); }
                to { transform: translateX(0); }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); }
                to { transform: translateX(100%); }
            }
            
            .floating-btn {
                position: fixed;
                bottom: 1rem;
                right: 1rem;
                z-index: 1060;
            }
        }
        
        /* 动画效果 */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* 打字机光标 */
        .typing-cursor {
            display: inline-block;
            width: 8px;
            height: 1.2em;
            background: var(--primary);
            margin-left: 2px;
            animation: blink 1s step-end infinite;
        }
        
        @keyframes blink {
            50% { opacity: 0; }
        }
        
        /* 文件上传区 */
        .file-dropzone {
            border: 2px dashed rgba(37,99,235,0.2);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        
        .file-dropzone:hover {
            border-color: var(--primary);
            background-color: rgba(37,99,235,0.03);
        }
        
        /* 按钮样式 */
        .btn-gradient {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border: none;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,99,235,0.3);
        }
        
        /* 暗色模式增强 */
        [data-bs-theme="dark"] .card {
            background-color: var(--surface-0);
        }
        
        [data-bs-theme="dark"] .form-control {
            background-color: #2d2d2d;
            border-color: #4a4a4a;
            color: var(--text-primary);
        }
        
        [data-bs-theme="dark"] .form-control::placeholder {
            color: var(--text-secondary);
        }
        
        [data-bs-theme="dark"] .message-bubble {
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        [data-bs-theme="dark"] .user-message {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
        }
        
        [data-bs-theme="dark"] .assistant-message {
            background-color: var(--surface-0);
            border-color: #4a4a4a;
            color: var(--text-primary);
        }
        
        [data-bs-theme="dark"] .message-time {
            opacity: 0.6;
        }
        
        [data-bs-theme="dark"] .message-content code {
            background-color: rgba(255,255,255,0.05);
        }
        
        [data-bs-theme="dark"] .message-content pre {
            background-color: rgba(255,255,255,0.05);
        }
        
        /* 主题切换过渡 */
        .theme-transition {
            transition: background-color 0.5s ease, color 0.5s ease;
        }
        
        /* 用户徽章样式 */
        .user-badge {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: white;
            flex-shrink: 0;
        }
        
        /* 浮动按钮样式 */
        .floating-btn {
            position: fixed;
            bottom: 4rem;
            right: 1rem;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: float 2s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0); }
        }
    </style>
</head>
<body data-bs-theme="light" class="theme-transition">
    <!-- 移动端侧边栏触发 -->
    <button class="btn btn-primary rounded-circle d-md-none floating-btn" 
            onclick="toggleSidebar()" aria-label="控制面板">
        <i class="fas fa-sliders-h"></i>
    </button>
    
    <!-- 遮罩层 -->
    <div class="offcanvas-backdrop fade" id="sidebarOverlay" style="display: none;"></div>
    
    <!-- 头部导航 -->
    <nav class="navbar navbar-dark" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fas fa-robot me-2"></i>
                <span class="fw-bold fs-5">YiZi AI</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <div id="modelStatus" class="badge bg-light text-primary px-3 py-2 rounded-pill">
                    模型未选择
                </div>
                <button class="btn btn-icon text-white" onclick="UIHandler.toggleTheme()" aria-label="切换主题">
                    <i class="fas fa-moon"></i>
                </button>
                <div class="user-card d-none d-md-flex align-items-center gap-2">
                    <div class="user-badge"><?= strtoupper(substr($username, 0, 1)) ?></div>
                    <span class="user-name"><?= htmlspecialchars($username) ?></span>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- 主体内容 -->
    <div class="chat-container">
        <div class="main-content">
            <!-- 主聊天区 -->
            <div class="chat-area">
                <div class="chat-history" id="chatWindow">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center p-5">
                            <div style="font-size: 3.5rem; margin-bottom: 1rem;">🚀</div>
                            <h2 class="fw-bold mb-3">欢迎使用 YiZi</h2>
                            <p class="text-muted mb-4">您的智能生产力伙伴</p>
                            <button class="btn btn-gradient" onclick="UIHandler.showExamples()">
                                <i class="fas fa-smile me-2"></i> 查看示例问题
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 输入面板 -->
                <div class="input-panel">
                    <div class="file-dropzone mb-3" 
                         onclick="document.getElementById('fileUpload').click()"
                         ondragover="event.preventDefault(); this.classList.add('dragover')"
                         ondragleave="this.classList.remove('dragover')"
                         ondrop="handleFileDrop(event)">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        拖放文件或点击上传
                        <input type="file" id="fileUpload" class="d-none" accept=".txt,.pdf,.docx,.md,.csv">
                        <div id="fileName" class="mt-2 small"></div>
                    </div>
                    <div class="position-relative">
                        <textarea id="messageInput" class="form-control ps-4 pe-5 py-3" 
                                  placeholder="输入消息..." rows="3"
                                  style="border-radius: 1.5rem;"></textarea>
                        <button class="btn btn-gradient position-absolute" 
                                style="right: 0.75rem; bottom: 0.75rem;" 
                                onclick="ChatHandler.sendMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- 控制面板 -->
            <div class="control-panel" id="controlPanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="fas fa-cogs me-2"></i>AI 引擎</h5>
                    <button class="btn btn-sm btn-close d-md-none" onclick="toggleSidebar()"></button>
                </div>
                <div class="mb-4">
                    <select id="modelSelect" class="form-select mb-3" 
                            onchange="ModelHandler.updateModelStatus(this.value)">
                        <option value="">加载模型中...</option>
                    </select>
                    <div class="form-text text-muted">选择最适合您需求的模型版本</div>
                </div>
                <div class="border-top pt-3">
                    <h5 class="fw-bold mb-3"><i class="fas fa-tools me-2"></i>工具集</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger" 
                                onclick="ChatHandler.clearHistory()">
                            <i class="fas fa-trash me-2"></i> 清空对话记录
                        </button>
                        <button class="btn btn-outline-warning" 
                                onclick="UIHandler.exportConversation()">
                            <i class="fas fa-file-export me-2"></i> 导出对话
                        </button>
                        <button class="btn btn-outline-info" 
                                onclick="UIHandler.showSettings()">
                            <i class="fas fa-cog me-2"></i> 高级设置
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 移动端底部操作栏 -->
    <div class="d-md-none d-flex justify-content-around align-items-center position-fixed bottom-0 start-0 end-0 py-2" 
         style="background: var(--surface-0); border-top: 1px solid rgba(0,0,0,0.1); z-index: 1000;">
        <button class="btn btn-outline-primary" onclick="ChatHandler.clearHistory()">
            <i class="fas fa-trash"></i>
        </button>
        <button class="btn btn-outline-primary" onclick="UIHandler.exportConversation()">
            <i class="fas fa-file-export"></i>
        </button>
        <button class="btn btn-outline-primary" onclick="toggleSidebar()">
            <i class="fas fa-sliders-h"></i>
        </button>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- 第三方库 -->
    <script src="https://cdn.bootcdn.net/ajax/libs/marked/5.0.0/marked.min.js"></script>
    <script>
        // 版本控制
        const STORAGE_VERSION = 'v1.1';
        if (localStorage.getItem('storage_version') !== STORAGE_VERSION) {
            localStorage.clear();
            localStorage.setItem('storage_version', STORAGE_VERSION);
        }

        // 类型写入器（打字机效果）
        class TypeWriter {
            constructor(element) {
                this.element = element;
                this.queue = [];
                this.isTyping = false;
                this.speed = 20;
            }
            async type(content) {
                this.queue.push(content);
                if (!this.isTyping) this.processQueue();
            }
            async processQueue() {
                if (this.queue.length === 0) return;
                this.isTyping = true;
                const content = this.queue.shift();
                this.element.innerHTML = '<span class="typing-cursor"></span>';
                this.cursor = this.element.querySelector('.typing-cursor');
                for (let i = 0; i < content.length; i++) {
                    if (!this.isTyping) break;
                    const char = content[i];
                    this.appendCharacter(char);
                    await new Promise(r => setTimeout(r, this.getDelay(char)));
                }
                this.isTyping = false;
                this.cursor?.remove();
                this.processQueue();
            }
            appendCharacter(char) {
                const span = document.createElement('span');
                span.textContent = char;
                this.element.insertBefore(span, this.cursor);
            }
            getDelay(char) {
                if (/[.,;!?]/.test(char)) return this.speed * 4;
                if (char === ' ') return this.speed * 0.5;
                return this.speed;
            }
            stop() {
                this.isTyping = false;
                this.queue = [];
            }
        }
        
        // 聊天处理器
        const ChatHandler = {
            get history() {
                return JSON.parse(localStorage.getItem('chatHistory') || '[]');
            },
            set history(value) {
                localStorage.setItem('chatHistory', JSON.stringify(value));
            },
            currentFile: null,
            async sendMessage() {
                const input = document.getElementById('messageInput');
                const message = input.value.trim();
                if (!message && !this.currentFile) return;
                
                try {
                    UIHandler.toggleLoading(true);
                    
                    let finalMessage = message;
                    
                    // 如果有文件，先上传文件
                    if (this.currentFile) {
                        await this.uploadFile();
                        // 将文件信息添加到消息中
                        finalMessage = message ? `${message}\n\n[附件: ${this.currentFile.original_name}]` : `[附件: ${this.currentFile.original_name}]`;
                    }
                    
                    if (finalMessage) {
                        await UIHandler.addMessage('user', finalMessage);
                        this.history = [...this.history, { role: 'user', content: finalMessage }];
                    }
                    
                    const response = await fetch('admin/modelapi.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: finalMessage,
                            history: this.history,
                            file: this.currentFile ? {
                                name: this.currentFile.original_name,
                                content: this.currentFile.content,
                                type: this.currentFile.type
                            } : null,
                            model: document.getElementById('modelSelect').value
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.reply) {
                        await UIHandler.addMessage('assistant', data.reply);
                        this.history = [...this.history, { role: 'assistant', content: data.reply }];
                        
                        // 记录聊天日志
                        await this.logChat(finalMessage, data.reply);
                    } else if (data.error) {
                        await UIHandler.addMessage('assistant', `错误: ${data.error}`);
                    } else {
                        await UIHandler.addMessage('assistant', '抱歉，暂时无法获取回复，请稍后重试。');
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
                    } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
                        errorMessage = '网络连接失败，请检查服务器状态';
                    }
                    
                    await UIHandler.addMessage('assistant', `❌ ${errorMessage}`);
                    UIHandler.showToast(errorMessage, 'danger');
                } finally {
                    input.value = '';
                    this.currentFile = null;
                    UIHandler.updateFileDisplay();
                    UIHandler.toggleLoading(false);
                }
            },
            
            async uploadFile() {
                if (!this.currentFile || !this.currentFile.file) return;
                
                const formData = new FormData();
                formData.append('file', this.currentFile.file);
                
                try {
                    const response = await fetch('admin/fileapi.php?action=upload', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.error || '文件上传失败');
                    }
                    
                    const result = await response.json();
                    this.currentFile.uploadId = result.id;
                    this.currentFile.content = result.content || '';
                    
                    UIHandler.showToast(`文件 ${this.currentFile.original_name} 上传成功`, 'success');
                } catch (error) {
                    console.error('File upload error:', error);
                    UIHandler.showToast(`文件上传失败: ${error.message}`, 'danger');
                    throw error;
                }
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
            clearHistory() {
                if (confirm('确定要清空对话记录吗？')) {
                    this.history = [];
                    this.currentFile = null;
                    document.getElementById('chatWindow').innerHTML = `
                        <div class="card shadow-sm mb-4">
                            <div class="card-body text-center p-5">
                                <div style="font-size: 3.5rem; margin-bottom: 1rem;">✨</div>
                                <h2 class="fw-bold mb-3">对话已重置</h2>
                                <p class="text-muted mb-4">开始新的对话吧</p>
                            </div>
                        </div>`;
                    UIHandler.showToast('对话记录已清空', 'success');
                }
            }
        };
        
        // 模型管理器
        const ModelHandler = {
            async loadModels() {
                try {
                    const response = await fetch('admin/modelapi.php?action=models');
                    const data = await response.json();
                    const select = document.getElementById('modelSelect');
                    select.innerHTML = data.models.map(m => 
                        `<option value="${m}">${m}</option>`
                    ).join('');
                    const savedModel = localStorage.getItem('selectedModel');
                    if (savedModel) select.value = savedModel;
                    this.updateModelStatus(select.value);
                } catch (error) {
                    UIHandler.showToast('模型加载失败', 'danger');
                }
            },
            updateModelStatus(model) {
                const statusElem = document.getElementById('modelStatus');
                if (model) {
                    statusElem.textContent = `当前模型: ${model}`;
                    statusElem.className = 'badge bg-success-subtle text-success-emphasis px-3 py-2 rounded-pill';
                } else {
                    statusElem.textContent = '模型未选择';
                    statusElem.className = 'badge bg-light text-primary px-3 py-2 rounded-pill';
                }
                localStorage.setItem('selectedModel', model || '');
            }
        };
        
        // UI交互管理器
        const UIHandler = {
            async addMessage(role, content, isImmediate = false) {
                const chatWindow = document.getElementById('chatWindow');
                const welcome = chatWindow.querySelector('.card');
                if (welcome) welcome.remove();
                
                const messageDiv = document.createElement('div');
                messageDiv.className = `message-group`;
                messageDiv.innerHTML = `
                    <div class="message-bubble ${role}-message">
                        <div class="message-content"></div>
                        <div class="message-time">${new Date().toLocaleTimeString()}</div>
                    </div>`;
                chatWindow.appendChild(messageDiv);
                
                const contentDiv = messageDiv.querySelector('.message-content');

                // 移动端立即显示
                if (isImmediate || /Android|iPhone/i.test(navigator.userAgent)) {
                    contentDiv.innerHTML = marked.parse(content);
                } else {
                    // PC端打字机效果
                    const writer = new TypeWriter(contentDiv);
                    await writer.type(content);
                    contentDiv.innerHTML = marked.parse(content);
                }

                // 添加复制按钮
                const copyBtn = document.createElement('button');
                copyBtn.className = 'btn btn-sm btn-secondary position-absolute top-0 end-0 m-1';
                copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                copyBtn.title = '复制内容';
                copyBtn.onclick = () => {
                    navigator.clipboard.writeText(contentDiv.textContent)
                        .then(() => this.showToast('已复制到剪贴板', 'success'));
                };
                messageDiv.appendChild(copyBtn);

                // 自动滚动到底部
                if (!chatWindow._isUserScrolling) {
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                }
            },
            updateFileDisplay() {
                const fileName = document.getElementById('fileName');
                const fileUpload = document.getElementById('fileUpload');
                
                if (ChatHandler.currentFile) {
                    fileName.innerHTML = `<i class="fas fa-file me-2"></i>${ChatHandler.currentFile.original_name}`;
                    fileName.className = 'text-info small mt-1';
                } else {
                    fileName.innerHTML = '';
                }
            },
            clearFile() {
                ChatHandler.currentFile = null;
                document.getElementById('fileUpload').value = '';
                this.updateFileDisplay();
            },
            toggleLoading(show) {
                const sendBtn = document.querySelector('.btn-gradient');
                sendBtn.disabled = show;
                sendBtn.innerHTML = show ? '<i class="fas fa-spinner fa-spin"></i>' : '<i class="fas fa-paper-plane"></i>';
            },
            showToast(message, type = 'primary') {
                const toastContainer = document.getElementById('toastContainer');
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0`;
                toast.role = 'alert';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>`;
                toastContainer.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
                bsToast.show();
                
                // 自动清理
                toast.addEventListener('hidden.bs.toast', () => {
                    toast.remove();
                });
            },
            toggleTheme() {
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                this.applyTheme(newTheme);
            },
            applyTheme(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.body.classList.add('theme-transition');
                setTimeout(() => {
                    document.body.classList.remove('theme-transition');
                }, 500);
            },
            exportConversation() {
                const text = ChatHandler.history
                    .map(msg => `${msg.role === 'user' ? '[用户]' : '[助手]'}: ${msg.content}`)
                    .join('\n');
                const blob = new Blob([text], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `YiZi-chat-${new Date().toISOString().slice(0,10)}.txt`;
                a.click();
                URL.revokeObjectURL(url);
                this.showToast('对话已导出为文本文件', 'success');
            }
        };
        
        // 页面初始化
        document.addEventListener('DOMContentLoaded', () => {
            // 初始化主题
            const savedTheme = localStorage.getItem('theme') || 'light';
            UIHandler.applyTheme(savedTheme);
            
            // 初始化模型
            ModelHandler.loadModels();
            
            // 恢复历史记录
            const history = ChatHandler.history;
            if (history.length > 0) {
                document.getElementById('chatWindow').innerHTML = '';
                for (const msg of history) {
                    UIHandler.addMessage(msg.role, msg.content, true);
                }
            }
            
            // 文件上传处理
            document.getElementById('fileUpload').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) {
                    ChatHandler.currentFile = null;
                    UIHandler.updateFileDisplay();
                    return;
                }
                
                // 文件验证
                const allowedTypes = ['txt', 'md', 'pdf', 'doc', 'docx', 'csv'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(fileExtension)) {
                    UIHandler.showToast('不支持的文件格式', 'warning');
                    this.value = '';
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    UIHandler.showToast('文件大小不能超过10MB', 'warning');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = e => {
                    ChatHandler.currentFile = {
                        file: file,
                        original_name: file.name,
                        type: file.type,
                        size: file.size,
                        content: e.target.result.split(',')[1]
                    };
                    UIHandler.updateFileDisplay();
                    UIHandler.showToast(`已选择文件: ${file.name}`);
                };
                reader.readAsDataURL(file);
            });
            
            // 清除文件按钮
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('clear-file-btn')) {
                    UIHandler.clearFile();
                }
            });
            
            // 输入快捷键
            document.getElementById('messageInput').addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    ChatHandler.sendMessage();
                }
            });
            
            // 检测用户滚动
            const chatWindow = document.getElementById('chatWindow');
            let scrollTimeout;
            chatWindow.addEventListener('scroll', () => {
                const atBottom = chatWindow.scrollHeight - chatWindow.scrollTop <= chatWindow.clientHeight + 10;
                chatWindow._isUserScrolling = !atBottom;
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    chatWindow._isUserScrolling = false;
                }, 1000);
            });
        });
        
        // 移动端侧边栏控制
        function toggleSidebar() {
            const panel = document.getElementById('controlPanel');
            const isOpen = panel.classList.contains('visible');
            panel.classList.toggle('visible');
            document.querySelector('.offcanvas-backdrop').style.display = isOpen ? 'none' : 'block';
        }
    </script>
    <!-- Toast容器 -->
    <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>
</body>
</html>