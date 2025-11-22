<?php
require 'auth.php';
checkAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    die('无权访问此页面');
}

require 'db.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邀请码管理 - YiZi AI</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #7c3aed;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f8fafc;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .container-fluid {
            padding: 2rem;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .btn-gradient {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }
        
        .btn-outline-danger {
            border-color: var(--danger);
            color: var(--danger);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger);
            border-color: var(--danger);
            color: white;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: rgba(0, 0, 0, 0.05);
            border-top: none;
            font-weight: 600;
            color: var(--dark);
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-used {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-expired {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .form-control {
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .modal-content {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
        }
        
        .stat-label {
            color: var(--dark);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .table-responsive {
                border-radius: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="h3 mb-0">
                                    <i class="fas fa-key me-2"></i>
                                    邀请码管理
                                </h1>
                                <p class="mb-0 opacity-75">管理和生成用户邀请码</p>
                            </div>
                            <div>
                                <button class="btn btn-light" onclick="location.reload()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- 统计数据 -->
                        <div class="row mb-4" id="statsContainer">
                            <div class="col-md-3 col-sm-6">
                                <div class="stats-card">
                                    <div class="stat-item">
                                        <span class="stat-number" id="totalCodes">0</span>
                                        <span class="stat-label">总邀请码</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="stats-card">
                                    <div class="stat-item">
                                        <span class="stat-number text-success" id="activeCodes">0</span>
                                        <span class="stat-label">可用</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="stats-card">
                                    <div class="stat-item">
                                        <span class="stat-number text-warning" id="usedCodes">0</span>
                                        <span class="stat-label">已使用</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="stats-card">
                                    <div class="stat-item">
                                        <span class="stat-number text-danger" id="expiredCodes">0</span>
                                        <span class="stat-label">已过期</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 操作栏 -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control" id="searchInput" placeholder="搜索邀请码..." style="width: 250px;">
                                <select class="form-control" id="statusFilter" style="width: 120px;">
                                    <option value="">全部状态</option>
                                    <option value="active">可用</option>
                                    <option value="used">已使用</option>
                                    <option value="expired">已过期</option>
                                </select>
                            </div>
                            <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#generateModal">
                                <i class="fas fa-plus me-2"></i>生成邀请码
                            </button>
                        </div>
                        
                        <!-- 邀请码列表 -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>邀请码</th>
                                        <th>描述</th>
                                        <th>状态</th>
                                        <th>使用次数</th>
                                        <th>创建者</th>
                                        <th>使用者</th>
                                        <th>创建时间</th>
                                        <th>过期时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="codesTableBody">
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">加载中...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- 分页 -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div id="paginationInfo" class="text-muted"></div>
                            <nav>
                                <ul class="pagination mb-0" id="pagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 生成邀请码模态框 -->
    <div class="modal fade" id="generateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>生成邀请码
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="generateForm">
                        <div class="mb-3">
                            <label class="form-label">邀请码</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codeInput" placeholder="留空自动生成" style="text-transform: uppercase;">
                                <button class="btn btn-outline-secondary" type="button" onclick="generateRandomCode()">
                                    <i class="fas fa-random"></i>
                                </button>
                            </div>
                            <div class="form-text">大写字母和数字，长度6-20位</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">描述</label>
                            <input type="text" class="form-control" id="descriptionInput" placeholder="邀请码用途说明">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">最大使用次数</label>
                                <select class="form-control" id="maxUsesSelect">
                                    <option value="1" selected>1次（单次使用）</option>
                                    <option value="5">5次</option>
                                    <option value="10">10次</option>
                                    <option value="100">100次</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">有效期（天）</label>
                                <select class="form-control" id="expiresDaysSelect">
                                    <option value="30" selected>30天</option>
                                    <option value="7">7天</option>
                                    <option value="90">90天</option>
                                    <option value="365">365天</option>
                                    <option value="0">永不过期</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-gradient" onclick="generateCode()">生成</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentPage = 1;
        const pageSize = 20;
        
        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadInviteCodes();
            setupEventListeners();
        });
        
        // 设置事件监听器
        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('input', debounce(loadInviteCodes, 300));
            document.getElementById('statusFilter').addEventListener('change', loadInviteCodes);
            
            // 表单回车提交
            document.getElementById('generateForm').addEventListener('submit', function(e) {
                e.preventDefault();
                generateCode();
            });
        }
        
        // 防抖函数
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // 加载邀请码列表
        async function loadInviteCodes(page = 1) {
            currentPage = page;
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            
            try {
                const params = new URLSearchParams({
                    action: 'list',
                    page: page,
                    limit: pageSize,
                    search: search,
                    status: status
                });
                
                const response = await fetch(`invitecode.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    renderCodesTable(data.data);
                    renderPagination(data.total, page);
                    updateStats(data.data);
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('加载邀请码失败:', error);
                showAlert('加载邀请码失败: ' + error.message, 'danger');
            }
        }
        
        // 渲染邀请码表格
        function renderCodesTable(codes) {
            const tbody = document.getElementById('codesTableBody');
            
            if (codes.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            暂无邀请码
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = codes.map(code => `
                <tr>
                    <td>
                        <code class="badge bg-light text-dark fs-6">${code.code}</code>
                    </td>
                    <td>${code.description || '-'}</td>
                    <td>
                        <span class="status-badge status-${code.status}">${getStatusText(code.status)}</span>
                    </td>
                    <td>
                        ${code.used_count}/${code.max_uses}
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar" style="width: ${(code.used_count / code.max_uses) * 100}%"></div>
                        </div>
                    </td>
                    <td>${code.created_by_username || '-'}</td>
                    <td>${code.used_by_username || '-'}</td>
                    <td>${formatDateTime(code.created_at)}</td>
                    <td>${code.expires_at ? formatDateTime(code.expires_at) : '永不过期'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCode(${code.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // 渲染分页
        function renderPagination(total, current) {
            const totalPages = Math.ceil(total / pageSize);
            const pagination = document.getElementById('pagination');
            const info = document.getElementById('paginationInfo');
            
            info.textContent = `显示 ${(current - 1) * pageSize + 1}-${Math.min(current * pageSize, total)} 条，共 ${total} 条`;
            
            let paginationHTML = '';
            
            // 上一页
            paginationHTML += `
                <li class="page-item ${current === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadInviteCodes(${current - 1})">上一页</a>
                </li>
            `;
            
            // 页码
            const startPage = Math.max(1, current - 2);
            const endPage = Math.min(totalPages, current + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <li class="page-item ${i === current ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadInviteCodes(${i})">${i}</a>
                    </li>
                `;
            }
            
            // 下一页
            paginationHTML += `
                <li class="page-item ${current === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadInviteCodes(${current + 1})">下一页</a>
                </li>
            `;
            
            pagination.innerHTML = paginationHTML;
        }
        
        // 更新统计数据
        function updateStats(codes) {
            const total = codes.length;
            const active = codes.filter(c => c.status === 'active').length;
            const used = codes.filter(c => c.status === 'used').length;
            const expired = codes.filter(c => c.status === 'expired').length;
            
            document.getElementById('totalCodes').textContent = total;
            document.getElementById('activeCodes').textContent = active;
            document.getElementById('usedCodes').textContent = used;
            document.getElementById('expiredCodes').textContent = expired;
        }
        
        // 生成随机邀请码
        function generateRandomCode() {
            const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('codeInput').value = code;
        }
        
        // 生成邀请码
        async function generateCode() {
            const code = document.getElementById('codeInput').value.trim();
            const description = document.getElementById('descriptionInput').value.trim();
            const maxUses = parseInt(document.getElementById('maxUsesSelect').value);
            const expiresDays = parseInt(document.getElementById('expiresDaysSelect').value);
            
            if (code && !/^[A-Z0-9]{6,20}$/.test(code)) {
                showAlert('邀请码格式不正确，只能包含大写字母和数字，长度6-20位', 'warning');
                return;
            }
            
            try {
                const response = await fetch('invitecode.php?action=generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: code,
                        description: description,
                        max_uses: maxUses,
                        expires_days: expiresDays
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`邀请码 ${data.code} 生成成功！`, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('generateModal')).hide();
                    document.getElementById('generateForm').reset();
                    loadInviteCodes();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('生成邀请码失败:', error);
                showAlert('生成邀请码失败: ' + error.message, 'danger');
            }
        }
        
        // 删除邀请码
        async function deleteCode(id) {
            if (!confirm('确定要删除这个邀请码吗？此操作不可撤销。')) {
                return;
            }
            
            try {
                const response = await fetch(`invitecode.php?action=delete&id=${id}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('邀请码删除成功', 'success');
                    loadInviteCodes();
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                console.error('删除邀请码失败:', error);
                showAlert('删除邀请码失败: ' + error.message, 'danger');
            }
        }
        
        // 工具函数
        function getStatusText(status) {
            const statusMap = {
                'active': '可用',
                'used': '已使用',
                'expired': '已过期'
            };
            return statusMap[status] || status;
        }
        
        function formatDateTime(datetime) {
            return new Date(datetime).toLocaleString('zh-CN');
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>