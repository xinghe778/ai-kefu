<?php
session_start();
require 'db.php';
require 'auth.php';


// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    
    try {
        if ($action === 'create' || $action === 'update') {
            // 验证输入
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            $category_id = $_POST['category_id'] ?? null;
            
            if (empty($title)) {
                throw new Exception('标题不能为空');
            }
            
            // 处理文件上传
            $file_path = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                $uploadDir = '../uploads/kb/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                    $file_path = 'uploads/kb/' . $fileName;
                } else {
                    throw new Exception('文件上传失败');
                }
            }
            
            if ($action === 'create') {
                // 创建新条目
                $stmt = $pdo->prepare("INSERT INTO kb_entries 
                                      (title, content, category_id, file_path) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $category_id, $file_path]);
                $_SESSION['success'] = '知识库条目已创建';
            } else {
                // 更新现有条目
                $id = $_POST['id'] ?? 0;
                $updateFields = [];
                $params = [];
                
                if ($file_path) {
                    $updateFields[] = "file_path = ?";
                    $params[] = $file_path;
                }
                
                $updateFields[] = "title = ?, content = ?, category_id = ?";
                $params = array_merge($params, [$title, $content, $category_id, $id]);
                
                $stmt = $pdo->prepare("UPDATE kb_entries SET " . implode(', ', $updateFields) . " WHERE id = ?");
                $stmt->execute($params);
                $_SESSION['success'] = '知识库条目已更新';
            }
            
            header('Location: kb-manager.php');
            exit;
            
        } elseif ($_POST['action'] === 'delete') {
            // 删除条目
            $id = $_POST['id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM kb_entries WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = '知识库条目已删除';
            
            header('Location: kb-manager.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// 获取所有分类
$stmt = $pdo->query("SELECT * FROM kb_categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取所有知识库条目
$stmt = $pdo->query("SELECT e.*, c.name as category_name 
                    FROM kb_entries e
                    LEFT JOIN kb_categories c ON e.category_id = c.id
                    ORDER BY e.updated_at DESC");
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取单个条目用于编辑
$edit_entry = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kb_entries WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_entry = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>知识库管理 - YiZi AI</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            padding-top: 60px;
        }
        .kb-card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">YiZi AI 管理后台</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">欢迎，<?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">退出</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- 左侧表单区域 -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= $edit_entry ? '编辑知识库条目' : '添加新知识库条目' ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                                <?php unset($_SESSION['error']) ?>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                                <?php unset($_SESSION['success']) ?>
                            <?php endif; ?>
                            
                            <input type="hidden" name="action" value="<?= $edit_entry ? 'update' : 'create' ?>">
                            <?php if ($edit_entry): ?>
                                <input type="hidden" name="id" value="<?= $edit_entry['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">标题</label>
                                <input type="text" name="title" class="form-control" required
                                       value="<?= $edit_entry['title'] ?? '' ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">分类</label>
                                <select name="category_id" class="form-select">
                                    <option value="">请选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= 
                                            (isset($edit_entry['category_id']) && $edit_entry['category_id'] == $category['id']) ? 'selected' : '' 
                                        ?>><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">内容</label>
                                <textarea name="content" class="form-control" rows="8"><?= 
                                    $edit_entry['content'] ?? '' 
                                ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">文件上传</label>
                                <input type="file" name="file" class="form-control">
                                <div class="form-text">支持txt、pdf、doc、docx格式，最大10MB</div>
                            </div>
                            
                            <?php if (!empty($edit_entry['file_path'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">当前文件</label>
                                    <div class="alert alert-info">
                                        <i class="fas fa-file me-2"></i>
                                        <a href="../<?= $edit_entry['file_path'] ?>" target="_blank">
                                            <?= basename($edit_entry['file_path']) ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $edit_entry ? '更新条目' : '创建条目' ?>
                                </button>
                                <?php if ($edit_entry): ?>
                                    <a href="kb-manager.php" class="btn btn-secondary flex-fill">取消</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- 右侧列表区域 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">知识库条目列表</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" data-bs-toggle="collapse" href="#filterPanel">
                                <i class="fas fa-filter"></i> 过滤
                            </button>
                            <a href="kb_categories.php" class="btn btn-light btn-sm">
                                <i class="fas fa-sitemap"></i> 分类管理
                            </a>
                        </div>
                    </div>
                    
                    <!-- 过滤面板 -->
                    <div class="collapse" id="filterPanel">
                        <div class="card-body border-bottom">
                            <form class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" placeholder="按标题搜索...">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select">
                                        <option>全部分类</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100">应用</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 条目列表 -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>标题</th>
                                        <th>分类</th>
                                        <th>最后更新</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $entry): ?>
                                        <tr class="kb-card">
                                            <td><?= htmlspecialchars($entry['title']) ?></td>
                                            <td><?= $entry['category_name'] ?? '-' ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($entry['updated_at'])) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="kb-manager.php?edit=<?= $entry['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" onsubmit="return confirm('确定要删除吗？')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 分页 -->
    <div class="d-flex justify-content-center mt-4">
        <nav>
            <ul class="pagination">
                <li class="page-item disabled"><a class="page-link" href="#">上一页</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">下一页</a></li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>