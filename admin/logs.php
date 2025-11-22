<?php
require 'auth.php';
checkAuth();

if ($_SESSION['user']['role'] !== 'admin') {
    die('无权访问此页面');
}

require 'db.php';

// 搜索参数处理
$search = [
    'username' => $_GET['username'] ?? '',
    'action'   => $_GET['action'] ?? '',
    'start'    => $_GET['start'] ?? '',
    'end'      => $_GET['end'] ?? ''
];

// 分页配置
$limit = (int)($_GET['limit'] ?? 20);
$limit = max(1, min(100, $limit)); // 限制每页数量范围
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 查询条件构建
$where = ['1=1'];
$params = [];

if (!empty($search['username'])) {
    $where[] = "u.username LIKE ?";
    $params[] = "%{$search['username']}%";
}

if (!empty($search['action'])) {
    $where[] = "l.action = ?";
    $params[] = $search['action'];
}

if (!empty($search['start'])) {
    $where[] = "l.created_at >= ?";
    $params[] = $search['start'] . ' 00:00:00';
}

if (!empty($search['end'])) {
    $where[] = "l.created_at <= ?";
    $params[] = $search['end'] . ' 23:59:59';
}

// 获取日志总数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_logs l LEFT JOIN users u ON l.user_id = u.id WHERE " . implode(' AND ', $where));
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}
$stmt->execute();
$total = $stmt->fetchColumn();

// 获取日志列表（关键修复部分）
$sql = "
    SELECT l.*, u.username 
    FROM chat_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    WHERE " . implode(' AND ', $where) . " 
    ORDER BY l.id DESC 
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);

// 绑定 WHERE 条件参数
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}

// 绑定 LIMIT 和 OFFSET 参数
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);

// 执行查询
$stmt->execute();

// 获取结果
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取所有操作类型
$stmt = $pdo->query("SELECT DISTINCT action FROM chat_logs ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>日志查看</title>
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

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.6em;
            font-weight: 600;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }

        /* 搜索表单 */
        .search-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .search-form .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .search-form .form-group {
            flex: 1 1 200px;
            min-width: 200px;
        }

        .search-form label {
            display: block;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 5px;
            color: #555;
        }

        .search-form input,
        .search-form select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95em;
        }

        .search-form button {
            padding: 8px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-form button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        /* 表格容器 */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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

        /* 无数据提示 */
        .no-results {
            text-align: center;
            padding: 50px 0;
            color: #999;
            font-style: italic;
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
            .search-form .form-row {
                flex-direction: column;
            }

            .search-form .form-group {
                min-width: 100%;
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
        <h2>日志查看</h2>
        
        <!-- 搜索表单 -->
        <form method="get" class="search-form">
            <div class="form-row">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($search['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>操作类型</label>
                    <select name="action">
                        <option value="">全部类型</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= $act ?>" <?= $search['action'] === $act ? 'selected' : '' ?>>
                                <?= $act ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>开始日期</label>
                    <input type="date" name="start" value="<?= $search['start'] ?>">
                </div>
                
                <div class="form-group">
                    <label>结束日期</label>
                    <input type="date" name="end" value="<?= $search['end'] ?>">
                </div>
            </div>
            
            <div style="text-align:right;">
                <button type="submit">搜索</button>
                <a href="logs.php" style="margin-left:10px; color:#999; text-decoration:underline;">重置</a>
            </div>
        </form>
        
        <!-- 表格容器 -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>用户名</th>
                        <th>操作类型</th>
                        <th>描述</th>
                        <th>IP地址</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="no-results">暂无日志记录</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td data-label="时间"><?= $log['created_at'] ?></td>
                            <td data-label="用户名"><?= htmlspecialchars($log['username'] ?? '系统用户') ?></td>
                            <td data-label="操作类型"><?= htmlspecialchars($log['action'] ?? '') ?></td>
                            <td data-label="描述"><?= htmlspecialchars($log['description'] ?? '') ?></td>
                            <td data-label="IP地址"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页导航 -->
        <div class="pagination">
            <?= paginate($total, $page, $limit, $search) ?>
        </div>
    </div>
</body>
</html>

<?php

// 分页函数（带搜索参数）
function paginate($totalItems, $currentPage, $itemsPerPage, $searchParams) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    if ($totalPages <= 1) return '';
    
    // 构建查询参数
    $queryParams = http_build_query(array_filter($searchParams, function($v) { return $v !== ''; }));
    
    $html = '<div>';
    
    // 上一页
    if ($currentPage > 1) {
        $html .= '<a href="?page=1&'.$queryParams.'">首页</a>';
        $html .= '<a href="?page='.($currentPage-1).'&'.$queryParams.'">←</a>';
    }
    
    // 页码
    for ($i=1; $i<=$totalPages; $i++) {
        if ($i == $currentPage) {
            $html .= "<strong>$i</strong>";
        } else {
            $html .= "<a href=\"?page=$i&$queryParams\">$i</a>";
        }
    }
    
    // 下一页
    if ($currentPage < $totalPages) {
        $html .= '<a href="?page='.($currentPage+1).'&'.$queryParams.'">→</a>';
        $html .= '<a href="?page='.$totalPages.'&'.$queryParams.'">末页</a>';
    }
    
    $html .= '</div>';
    return $html;
}