<?php
// 邀请码管理API
// 路径: admin/invitecode.php

session_start();

// 启用 CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 引入依赖文件
require_once 'db.php';
require_once 'auth.php';

// 检查管理员权限
checkAuth();
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['error' => '权限不足，需要管理员权限']));
}

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 获取操作类型
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'generate':
            generateInviteCode($pdo);
            break;
        case 'list':
            listInviteCodes($pdo);
            break;
        case 'delete':
            deleteInviteCode($pdo);
            break;
        case 'validate':
            validateInviteCode($pdo);
            break;
        default:
            throw new Exception('未知的操作类型');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * 生成邀请码
 */
function generateInviteCode($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('仅支持POST请求');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $code = $data['code'] ?? '';
    $description = $data['description'] ?? '';
    $max_uses = (int)($data['max_uses'] ?? 1);
    $expires_days = (int)($data['expires_days'] ?? 30);
    
    if (empty($code)) {
        // 如果没有提供代码，自动生成
        $code = generateRandomCode();
    }
    
    // 验证邀请码格式
    if (!preg_match('/^[A-Z0-9]{6,20}$/', $code)) {
        throw new Exception('邀请码格式不正确，只能包含大写字母和数字，长度6-20位');
    }
    
    // 检查邀请码是否已存在
    $stmt = $pdo->prepare("SELECT id FROM invite_codes WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        throw new Exception('邀请码已存在');
    }
    
    // 计算过期时间
    $expires_at = $expires_days > 0 ? date('Y-m-d H:i:s', strtotime("+{$expires_days} days")) : null;
    
    // 创建邀请码
    $stmt = $pdo->prepare("
        INSERT INTO invite_codes (code, created_by, description, max_uses, expires_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $code,
        $_SESSION['user']['id'],
        $description,
        $max_uses,
        $expires_at
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '邀请码生成成功',
        'code' => $code
    ]);
}

/**
 * 获取邀请码列表
 */
function listInviteCodes($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // 搜索条件
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $where = ['1=1'];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "code LIKE ?";
        $params[] = "%{$search}%";
    }
    
    if (!empty($status)) {
        $where[] = "status = ?";
        $params[] = $status;
    }
    
    // 获取总数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invite_codes WHERE " . implode(' AND ', $where));
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    // 获取列表
    $sql = "
        SELECT ic.*, u.username as created_by_username, u2.username as used_by_username
        FROM invite_codes ic
        LEFT JOIN users u ON ic.created_by = u.id
        LEFT JOIN users u2 ON ic.used_by = u2.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY ic.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 处理数据
    foreach ($codes as &$code) {
        // 检查是否过期
        if ($code['expires_at'] && strtotime($code['expires_at']) < time()) {
            $code['status'] = 'expired';
        }
        // 检查是否用完
        if ($code['used_count'] >= $code['max_uses']) {
            $code['status'] = 'used';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $codes,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

/**
 * 删除邀请码
 */
function deleteInviteCode($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        throw new Exception('仅支持DELETE请求');
    }
    
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception('无效的邀请码ID');
    }
    
    $stmt = $pdo->prepare("DELETE FROM invite_codes WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '邀请码删除成功']);
    } else {
        throw new Exception('邀请码不存在或已被删除');
    }
}

/**
 * 验证邀请码（供注册使用）
 */
function validateInviteCode($pdo) {
    $code = $_GET['code'] ?? '';
    
    if (empty($code)) {
        throw new Exception('请提供邀请码');
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM invite_codes 
        WHERE code = ? AND status = 'active'
    ");
    $stmt->execute([$code]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invite) {
        throw new Exception('邀请码无效或已失效');
    }
    
    // 检查是否过期
    if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
        throw new Exception('邀请码已过期');
    }
    
    // 检查使用次数
    if ($invite['used_count'] >= $invite['max_uses']) {
        throw new Exception('邀请码使用次数已满');
    }
    
    echo json_encode([
        'success' => true,
        'valid' => true,
        'code' => $invite['code'],
        'description' => $invite['description']
    ]);
}

/**
 * 消耗邀请码（用户注册时调用）
 */
function consumeInviteCode($pdo, $code, $user_id) {
    try {
        $pdo->beginTransaction();
        
        // 锁定并验证邀请码
        $stmt = $pdo->prepare("
            SELECT * FROM invite_codes 
            WHERE code = ? AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute([$code]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invite) {
            throw new Exception('邀请码无效');
        }
        
        if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
            throw new Exception('邀请码已过期');
        }
        
        if ($invite['used_count'] >= $invite['max_uses']) {
            throw new Exception('邀请码使用次数已满');
        }
        
        // 更新邀请码状态
        $stmt = $pdo->prepare("
            UPDATE invite_codes 
            SET used_count = used_count + 1, 
                used_by = ?, 
                used_at = NOW(),
                status = CASE 
                    WHEN used_count + 1 >= max_uses THEN 'used' 
                    ELSE 'active' 
                END
            WHERE id = ?
        ");
        $stmt->execute([$user_id, $invite['id']]);
        
        $pdo->commit();
        
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * 生成随机邀请码
 */
function generateRandomCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}
?>