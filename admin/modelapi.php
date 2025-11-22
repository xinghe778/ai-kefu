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

// 加载系统配置
try {
    $stmt = $pdo->query("SELECT 
        api_key, 
        prompt, 
        api_url,
        kb_enabled,
        kb_threshold,
        kb_max_results
        FROM settings LIMIT 1");
        
    $db_config = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$db_config) {
        throw new Exception("未找到系统配置，请先在管理后台初始化设置");
    }

    $config = [
        'api_key' => $db_config['api_key'] ?? '',
        'prompt' => $db_config['prompt'] ?? "你是一个有用的助手",
        'api_url' => rtrim($db_config['api_url'] ?? '', '/'),
        'kb_enabled' => (bool)($db_config['kb_enabled'] ?? true),
        'kb_threshold' => (float)($db_config['kb_threshold'] ?? 0.7),
        'kb_max_results' => (int)($db_config['kb_max_results'] ?? 5)
    ];
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => '数据库连接失败']));
}

// 处理模型列表请求
if (isset($_GET['action']) && $_GET['action'] === 'models') {
    try {
        $response = getAvailableModels($config, $_SESSION['user'] ?? null);
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => '获取模型列表失败: ' . $e->getMessage()]);
    }
    exit;
}

// 获取请求数据
$data = json_decode(file_get_contents('php://input'), true);
$user_input = $data['message'] ?? '';
$selected_model = $data['model'] ?? '';
$history = $data['history'] ?? [];

if (empty($user_input)) {
    echo json_encode(['reply' => '请输入您的问题']);
    exit;
}

try {
    // 构建请求体
    $request_data = [
        'model' => $selected_model ?: 'gpt-3.5-turbo',
        'temperature' => 0.7
    ];

    // 构建消息链
    $messages = [];

    // 添加系统提示词
    $messages[] = [
        'role' => 'system',
        'content' => sanitizePrompt($config['prompt'])
    ];

    // 添加知识库上下文（如果启用）
    if ($config['kb_enabled']) {
        $kbContext = getKnowledgeContext($pdo, $user_input, $config);
        if (!empty($kbContext)) {
            $messages[] = [
                'role' => 'system',
                'content' => "以下是从知识库中检索到的相关信息：\n\n" . $kbContext
            ];
        }
    }

    // 添加历史记录
    foreach ($history as $msg) {
        if (in_array($msg['role'], ['user', 'assistant'])) {
            $messages[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
    }

    // 添加当前用户输入
    $messages[] = [
        'role' => 'user',
        'content' => $user_input
    ];

    $request_data['messages'] = $messages;

    // 发起 API 请求
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$config['api_url']}/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        throw new Exception('API请求失败: ' . curl_error($ch));
    }

    curl_close($ch);

    // 解析响应
    $response_data = json_decode($response, true);

    if ($http_code != 200) {
        throw new Exception('API错误: ' . ($response_data['error']['message'] ?? '未知错误'));
    }

    $reply = $response_data['choices'][0]['message']['content'] ?? '未能获取回复';
    echo json_encode(['reply' => $reply]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("API错误: " . $e->getMessage());
    echo json_encode([
        'reply' => '系统繁忙，请稍后再试。错误代码：E500'
    ]);
}

// 获取可用模型列表
function getAvailableModels($config, $user = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$config['api_url']}/v1/models",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200 || !$response) {
        return ['success' => false, 'error' => '无法访问模型列表'];
    }

    $data = json_decode($response, true);
    $models = $data['data'] ?? [];

    if (empty($models)) {
        return ['success' => false, 'error' => '未找到可用模型'];
    }

    $modelNames = array_map(fn($model) => $model['id'] ?? '', $models);
    $allModels = array_filter($modelNames);

    // 根据用户权限返回不同的模型列表
    $defaultModels = ['gpt-3.5-turbo', 'gpt-3.5-turbo-16k'];
    $premiumModels = ['gpt-4', 'gpt-4-1106-preview', 'gpt-4-turbo', 'gpt-4-vision-preview'];
    
    if ($user && $user['role'] && $user['role'] !== 'guest') {
        // 注册用户可以看到所有模型
        $availableModels = $allModels;
    } else {
        // 游客只能使用默认模型
        $availableModels = array_intersect($allModels, $defaultModels);
    }

    return [
        'success' => true,
        'models' => $availableModels,
        'user_type' => $user && $user['role'] !== 'guest' ? 'registered' : 'guest',
        'default_models' => $defaultModels,
        'premium_models' => $premiumModels
    ];
}

// 获取知识库内容
function getKnowledgeContext($pdo, $query, $config) {
    try {
        // 使用全文搜索
        $stmt = $pdo->prepare("SELECT * FROM kb_entries 
                              WHERE MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)
                              ORDER BY updated_at DESC LIMIT ?");
        $stmt->execute([$query, $config['kb_max_results']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) return '';

        // 构建上下文字符串
        $context = "根据您的问题，找到以下相关知识：\n\n";
        foreach ($results as $i => $entry) {
            $context .= "[文档" . ($i + 1) . "]\n";
            $context .= "标题: {$entry['title']}\n";
            $context .= "内容摘要: " . substr(strip_tags($entry['content']), 0, 200) . "...\n";
            $context .= "文档链接: /kb/{$entry['id']}\n\n";
        }

        return $context;
    } catch (PDOException $e) {
        error_log("知识库检索错误: " . $e->getMessage());
        return '';
    }
}

// 安全净化 Prompt 内容
function sanitizePrompt($content) {
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}