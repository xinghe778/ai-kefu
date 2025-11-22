<?php
require 'auth.php';
checkAuth();

// 仅允许管理员访问
if ($_SESSION['user']['role'] !== 'admin') {
    die('无权访问此页面');
}

require 'db.php';

// 分页处理
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 获取用户总数
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total = $stmt->fetchColumn();

// 获取用户列表
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>用户管理</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* 标题样式 */
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.6em;
            font-weight: 600;
        }

        /* 表格样式 */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        thead {
            background-color: #f8f9fa;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            font-weight: 500;
            color: #34495e;
            white-space: nowrap;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* 操作列 */
        .actions a {
            display: inline-block;
            margin-right: 10px;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9em;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .actions a:first-child {
            background: #3498db;
            color: white;
        }

        .actions a:last-child {
            background: #e74c3c;
            color: white;
        }

        .actions a:hover {
            opacity: 0.9;
        }

        /* 分页样式 */
        .pagination {
            margin-top: 25px;
            text-align: center;
        }

        .pagination a, 
        .pagination strong {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 0.95em;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .pagination a {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
        }

        .pagination strong {
            background: #3498db;
            color: white;
            font-weight: normal;
            min-width: 30px;
            text-align: center;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
                margin: 20px auto;
            }

            th {
                display: none;
            }

            td {
                display: block;
                padding: 12px 20px;
                border-bottom: 1px solid #eee;
            }

            td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #7f8c8d;
                display: inline-block;
                width: 100px;
                margin-right: 10px;
            }

            .actions a {
                display: block;
                margin: 5px 0;
            }

            .pagination a,
            .pagination strong {
                margin: 0 2px;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h2>用户管理</h2>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>角色</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 40px 0;">
                                暂无用户数据
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-label="ID"><?= $user['id'] ?></td>
                            <td data-label="用户名"><?= htmlspecialchars($user['username']) ?></td>
                            <td data-label="角色">
                                <?= $user['role'] === 'admin' ? '管理员' : '普通用户' ?>
                                
                                
                                
                                
                            </td>
                            <td data-label="创建时间"><?= $user['created_at'] ?></td>
                            <td class="actions" data-label="操作">
                            <a href="user-edit.php?id=<?= $user['id'] ?>" class="btn btn-edit">编辑</a>
                            <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
                              <a href="user-delete.php?id=<?= $user['id'] ?>" 
                              onclick="return confirm('确定删除用户 <?= htmlspecialchars($user['username']) ?> 吗？')" 
                                  class="btn btn-delete">
                                    删除
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页导航 -->
        <div class="pagination">
            <?= paginate($total, $page, $limit) ?>
        </div>
    </div>
</body>
</html>

<?php

// 分页函数
function paginate($totalItems, $currentPage, $itemsPerPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    if ($totalPages <= 1) return '';
    
    $html = '<div>';
    
    // 上一页
    if ($currentPage > 1) {
        $html .= '<a href="?page=1">首页</a>';
        $html .= '<a href="?page='.($currentPage-1).'">←</a>';
    }
    
    // 页码
    for ($i=1; $i<=$totalPages; $i++) {
        if ($i == $currentPage) {
            $html .= "<strong>$i</strong>";
        } else {
            $html .= "<a href=\"?page=$i\">$i</a>";
        }
    }
    
    // 下一页
    if ($currentPage < $totalPages) {
        $html .= '<a href="?page='.($currentPage+1).'">→</a>';
        $html .= '<a href="?page='.$totalPages.'">末页</a>';
    }
    
    $html .= '</div>';
    return $html;
}