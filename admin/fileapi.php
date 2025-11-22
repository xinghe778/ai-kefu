<?php
session_start();

// 启用 CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 引入依赖文件
require_once 'db.php';
require_once 'auth.php';

// 检查登录状态
checkAuth();

$action = $_GET['action'] ?? 'upload';
$method = $_SERVER['REQUEST_METHOD'];

/**
 * 文件上传处理
 */
if ($action === 'upload' && $method === 'POST') {
    try {
        // 检查文件是否存在
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('文件上传失败');
        }
        
        $file = $_FILES['file'];
        $userId = $_SESSION['user']['id'] ?? null;
        
        // 验证文件
        validateFile($file);
        
        // 生成文件名
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
        
        // 创建上传目录
        $uploadDir = '../uploads/files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = $uploadDir . $fileName;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('文件保存失败');
        }
        
        // 解析文件内容
        $contentPreview = extractFileContent($filePath, $fileExtension);
        
        // 保存到数据库
        $stmt = $pdo->prepare("
            INSERT INTO file_uploads 
            (user_id, original_name, file_name, file_path, file_size, file_type, mime_type, content_preview, upload_ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $file['name'],
            $fileName,
            'uploads/files/' . $fileName,
            $file['size'],
            $fileExtension,
            getMimeType($file['name']),
            $contentPreview,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        
        $uploadId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'id' => $uploadId,
            'file_name' => $fileName,
            'original_name' => $file['name'],
            'file_size' => $file['size'],
            'file_type' => $fileExtension,
            'message' => '文件上传成功'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 获取文件列表
 */
if ($action === 'list' && $method === 'GET') {
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("
            SELECT fu.*, u.username 
            FROM file_uploads fu
            LEFT JOIN users u ON fu.user_id = u.id
            ORDER BY fu.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取总数
        $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads");
        $total = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => $files,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 删除文件
 */
if ($action === 'delete' && $method === 'DELETE') {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('缺少文件ID');
        }
        
        // 获取文件信息
        $stmt = $pdo->prepare("SELECT file_path FROM file_uploads WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            throw new Exception('文件不存在');
        }
        
        // 删除物理文件
        $physicalPath = '../' . $file['file_path'];
        if (file_exists($physicalPath)) {
            unlink($physicalPath);
        }
        
        // 删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM file_uploads WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => '文件删除成功'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 下载文件
 */
if ($action === 'download' && $method === 'GET') {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('缺少文件ID');
        }
        
        // 获取文件信息
        $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            throw new Exception('文件不存在');
        }
        
        $physicalPath = '../' . $file['file_path'];
        
        if (!file_exists($physicalPath)) {
            throw new Exception('文件不存在');
        }
        
        // 设置下载头
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($physicalPath));
        
        // 输出文件
        readfile($physicalPath);
        exit;
        
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 文件内容预览
 */
if ($action === 'preview' && $method === 'GET') {
    try {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('缺少文件ID');
        }
        
        // 获取文件信息
        $stmt = $pdo->prepare("SELECT content_preview, file_type FROM file_uploads WHERE id = ?");
        $stmt->execute([$id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            throw new Exception('文件不存在');
        }
        
        echo json_encode([
            'success' => true,
            'content' => $file['content_preview'],
            'file_type' => $file['file_type']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 验证上传文件
 */
function validateFile($file) {
    // 文件大小限制 (10MB)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('文件大小不能超过10MB');
    }
    
    // 允许的文件类型
    $allowedTypes = [
        'txt', 'md', 'csv',
        'pdf', 'doc', 'docx',
        'xls', 'xlsx'
    ];
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception('不支持的文件类型');
    }
    
    // 检查文件是否真的上传了
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('文件上传验证失败');
    }
}

/**
 * 提取文件内容
 */
function extractFileContent($filePath, $fileExtension) {
    $maxPreviewLength = 5000; // 预览最大长度
    
    switch (strtolower($fileExtension)) {
        case 'txt':
        case 'md':
        case 'csv':
            $content = file_get_contents($filePath);
            return mb_substr($content, 0, $maxPreviewLength, 'UTF-8');
            
        case 'pdf':
            // 这里可以集成PDF解析库，如PDFParser
            return '[PDF文件] 需要PDF解析器来提取内容';
            
        case 'doc':
        case 'docx':
            // 这里可以集成文档解析库
            return '[Word文档] 需要文档解析器来提取内容';
            
        case 'xls':
        case 'xlsx':
            // 这里可以集成Excel解析库
            return '[Excel文件] 需要表格解析器来提取内容';
            
        default:
            return '[二进制文件] 无法预览内容';
    }
}

/**
 * 获取MIME类型
 */
function getMimeType($fileName) {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'csv' => 'text/csv',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
?>