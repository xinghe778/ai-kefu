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

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

/**
 * 知识库条目创建
 */
if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        validateKbData($data);
        $kbId = addKbEntry($pdo, $data);
        echo json_encode([
            'success' => true, 
            'id' => $kbId, 
            'message' => '知识条目已创建'
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
 * 知识库条目更新
 */
if ($action === 'update' && $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少ID参数']);
        exit;
    }
    
    try {
        validateKbData($data);
        updateKbEntry($pdo, $id, $data);
        echo json_encode([
            'success' => true, 
            'message' => '知识条目已更新'
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
 * 知识库条目删除
 */
if ($action === 'delete' && $method === 'DELETE') {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少ID参数']);
        exit;
    }
    
    try {
        deleteKbEntry($pdo, $id);
        echo json_encode([
            'success' => true, 
            'message' => '知识条目已删除'
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
 * 知识库条目搜索
 */
if ($action === 'search' && $method === 'GET') {
    $query = $_GET['q'] ?? '';
    $category = $_GET['category'] ?? '';
    $limit = min(intval($_GET['limit'] ?? 10), 50);
    $offset = max(intval($_GET['offset'] ?? 0), 0);
    
    try {
        $results = searchKbEntries($pdo, $query, $category, $limit, $offset);
        echo json_encode([
            'success' => true, 
            'data' => $results,
            'total' => count($results)
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
 * 获取知识库条目列表
 */
if ($action === 'list' && $method === 'GET') {
    $category = $_GET['category'] ?? '';
    $limit = min(intval($_GET['limit'] ?? 20), 100);
    $offset = max(intval($_GET['offset'] ?? 0), 0);
    
    try {
        $entries = getKbEntries($pdo, $category, $limit, $offset);
        echo json_encode([
            'success' => true, 
            'data' => $entries
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
 * 获取单个知识库条目
 */
if ($action === 'detail' && $method === 'GET') {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少ID参数']);
        exit;
    }
    
    try {
        $entry = getKbEntry($pdo, $id);
        if ($entry) {
            // 更新查看次数
            updateKbViewCount($pdo, $id);
            echo json_encode([
                'success' => true, 
                'data' => $entry
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'error' => '知识条目不存在'
            ]);
        }
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
 * 批量导入知识库条目
 */
if ($action === 'import' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '无效的导入数据']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        $imported = 0;
        
        foreach ($data['entries'] as $entry) {
            validateKbData($entry);
            addKbEntry($pdo, $entry);
            $imported++;
        }
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => "成功导入 {$imported} 条记录"
        ]);
    } catch (Exception $e) {
        $pdo->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * 获取知识库统计信息
 */
if ($action === 'stats' && $method === 'GET') {
    try {
        $stats = getKbStats($pdo);
        echo json_encode([
            'success' => true, 
            'data' => $stats
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

// 如果没有匹配到任何操作，返回错误
http_response_code(400);
echo json_encode(['success' => false, 'error' => '不支持的操作']);

/**
 * 验证知识库数据
 */
function validateKbData($data) {
    if (empty($data['title'])) {
        throw new Exception('标题不能为空');
    }
    
    if (empty($data['content'])) {
        throw new Exception('内容不能为空');
    }
    
    if (strlen($data['title']) > 255) {
        throw new Exception('标题长度不能超过255字符');
    }
    
    if (strlen($data['content']) > 1000000) { // 1MB
        throw new Exception('内容过长，请分段保存');
    }
}

/**
 * 添加知识库条目
 */
function addKbEntry($pdo, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO kb_entries (title, content, summary, category_id, tags, file_path, file_type, file_size)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        trim($data['title']),
        trim($data['content']),
        trim($data['summary'] ?? ''),
        $data['category_id'] ?? null,
        trim($data['tags'] ?? ''),
        $data['file_path'] ?? null,
        $data['file_type'] ?? null,
        $data['file_size'] ?? null
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * 更新知识库条目
 */
function updateKbEntry($pdo, $id, $data) {
    $stmt = $pdo->prepare("
        UPDATE kb_entries 
        SET title=?, content=?, summary=?, category_id=?, tags=?, file_path=?, file_type=?, file_size=?
        WHERE id=?
    ");
    
    $stmt->execute([
        trim($data['title']),
        trim($data['content']),
        trim($data['summary'] ?? ''),
        $data['category_id'] ?? null,
        trim($data['tags'] ?? ''),
        $data['file_path'] ?? null,
        $data['file_type'] ?? null,
        $data['file_size'] ?? null,
        $id
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('知识条目不存在或无权限修改');
    }
}

/**
 * 删除知识库条目
 */
function deleteKbEntry($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM kb_entries WHERE id=?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('知识条目不存在或无权限删除');
    }
}

/**
 * 获取知识库条目列表
 */
function getKbEntries($pdo, $category = '', $limit = 20, $offset = 0) {
    $where = [];
    $params = [];
    
    if ($category) {
        $where[] = "category_id = ?";
        $params[] = $category;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $pdo->prepare("
        SELECT ke.*, kc.name as category_name
        FROM kb_entries ke
        LEFT JOIN kb_categories kc ON ke.category_id = kc.id
        {$whereClause}
        ORDER BY ke.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取单个知识库条目
 */
function getKbEntry($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT ke.*, kc.name as category_name
        FROM kb_entries ke
        LEFT JOIN kb_categories kc ON ke.category_id = kc.id
        WHERE ke.id = ?
    ");
    
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 搜索知识库条目
 */
function searchKbEntries($pdo, $query, $category = '', $limit = 10, $offset = 0) {
    $where = [];
    $params = [];
    
    if ($query) {
        $where[] = "MATCH(ke.title, ke.content, ke.summary) AGAINST(? IN NATURAL LANGUAGE MODE)";
        $params[] = $query;
    }
    
    if ($category) {
        $where[] = "ke.category_id = ?";
        $params[] = $category;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $stmt = $pdo->prepare("
        SELECT ke.*, kc.name as category_name,
               MATCH(ke.title, ke.content, ke.summary) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
        FROM kb_entries ke
        LEFT JOIN kb_categories kc ON ke.category_id = kc.id
        {$whereClause}
        ORDER BY relevance DESC, ke.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params = array_merge([$query], $params, [$limit, $offset]);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 更新查看次数
 */
function updateKbViewCount($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE kb_entries SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$id]);
}

/**
 * 获取知识库统计信息
 */
function getKbStats($pdo) {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_entries,
            COUNT(DISTINCT category_id) as total_categories,
            AVG(CHAR_LENGTH(content)) as avg_content_length,
            MAX(created_at) as last_entry_date
        FROM kb_entries
    ");
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>