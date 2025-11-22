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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $message = trim($data['message'] ?? '');
        $response = trim($data['response'] ?? '');
        $model = $data['model'] ?? '';
        $tokensUsed = intval($data['tokens_used'] ?? 0);
        $responseTime = floatval($data['response_time'] ?? 0);
        
        if (empty($message) || empty($response)) {
            throw new Exception('消息和回复内容不能为空');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO chat_logs 
            (user_id, username, message, response, model_used, tokens_used, response_time, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $userId = $_SESSION['user']['id'] ?? null;
        $username = $_SESSION['user']['username'] ?? '游客';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->execute([
            $userId,
            $username,
            $message,
            $response,
            $model,
            $tokensUsed,
            $responseTime,
            $ipAddress,
            $userAgent
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '聊天记录已保存'
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            $userId = $_SESSION['user']['id'] ?? null;
            $username = $_SESSION['user']['username'] ?? '';
            
            $stmt = $pdo->prepare("
                SELECT * FROM chat_logs 
                WHERE user_id = ? OR username = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $username, $limit, $offset]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取总数
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM chat_logs 
                WHERE user_id = ? OR username = ?
            ");
            $stmt->execute([$userId, $username]);
            $total = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => $logs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } elseif ($action === 'stats') {
            $userId = $_SESSION['user']['id'] ?? null;
            
            // 获取用户统计
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_conversations,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_conversations,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_conversations,
                    AVG(tokens_used) as avg_tokens,
                    AVG(response_time) as avg_response_time
                FROM chat_logs 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        } else {
            throw new Exception('不支持的操作');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => '不支持的请求方法']);

// 日志记录函数（保留原有函数）
function log_action($pdo, $action, $description = '', $user_id = null) {
    $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    
    $user_id = $user_id ?: ($_SESSION['user']['id'] ?? null);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt->execute([
        $user_id,
        substr($action, 0, 255),
        $description,
        $ip
    ]);
}
?>