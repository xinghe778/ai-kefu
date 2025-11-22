<?php
// admin/kb_categories.php
session_start();
require 'db.php';
require 'auth.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    
    try {
        if ($action === 'create' || $action === 'update') {
            // 验证输入
            $name = trim($_POST['name'] ?? '');
            $description = $_POST['description'] ?? '';
            
            if (empty($name)) {
                throw new Exception('分类名称不能为空');
            }
            
            // 检查分类名称是否唯一
            if ($action === 'create') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM kb_categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('分类名称已存在');
                }
            }

            if ($action === 'create') {
                // 创建新分类
                $stmt = $pdo->prepare("INSERT INTO kb_categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $_SESSION['success'] = '知识库分类已创建';
            } else {
                // 更新现有分类
                $id = $_POST['id'] ?? 0;
                $stmt = $pdo->prepare("UPDATE kb_categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                $_SESSION['success'] = '知识库分类已更新';
            }
            
            header('Location: kb_categories.php');
            exit;
            
        } elseif ($_POST['action'] === 'delete') {
            // 删除分类
            $id = $_POST['id'] ?? 0;
            
            // 检查是否有知识库条目关联
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM kb_entries WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('无法删除包含知识库条目的分类');
            }
            
            $stmt = $pdo->prepare("DELETE FROM kb_categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = '知识库分类已删除';
            
            header('Location: kb_categories.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// 获取所有分类
$stmt = $pdo->query("SELECT * FROM kb_categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取单个分类用于编辑
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kb_categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>知识库分类管理 - YiZi AI</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            padding-top: 60px;
        }
        .category-card:hover {
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
            <!-- 表单区域 -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= $edit_category ? '编辑知识库分类' : '添加新知识库分类' ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                            <?php unset($_SESSION['error']) ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                            <?php unset($_SESSION['success']) ?>
                        <?php endif; ?>
                        
                        <form method="post">
                            <input type="hidden" name="action" value="<?= $edit_category ? 'update' : 'create' ?>">
                            <?php if ($edit_category): ?>
                                <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">分类名称</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= $edit_category['name'] ?? '' ?>">
                                <div class="form-text">用于对知识库条目进行分类</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">分类描述</label>
                                <textarea name="description" class="form-control" rows="3"><?= 
                                    $edit_category['description'] ?? '' 
                                ?></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $edit_category ? '更新分类' : '创建分类' ?>
                                </button>
                                <?php if ($edit_category): ?>
                                    <a href="kb_categories.php" class="btn btn-secondary flex-fill">取消</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- 分类列表 -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">知识库分类列表</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light btn-sm" data-bs-toggle="collapse" href="#filterPanel">
                                <i class="fas fa-filter"></i> 过滤
                            </button>
                            <a href="kb-manager.php" class="btn btn-light btn-sm">
                                <i class="fas fa-book-open me-1"></i> 知识库管理
                            </a>
                        </div>
                    </div>
                    
                    <!-- 过滤面板 -->
                    <div class="collapse" id="filterPanel">
                        <div class="card-body border-bottom">
                            <form class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" placeholder="按名称搜索...">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100">应用</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- 分类列表 -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>分类名称</th>
                                        <th>条目数量</th>
                                        <th>描述</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): 
                                        // 获取分类下的知识库条目数量
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kb_entries WHERE category_id = ?");
                                        $stmt->execute([$category['id']]);
                                        $entry_count = $stmt->fetchColumn();
                                    ?>
                                        <tr class="category-card">
                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                            <td><?= $entry_count ?></td>
                                            <td><?= htmlspecialchars($category['description']) ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="kb_categories.php?edit=<?= $category['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="post" onsubmit="return confirm('确定要删除吗？')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
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