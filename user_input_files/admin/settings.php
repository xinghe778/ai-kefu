<?php
require 'auth.php';
checkAuth();
require 'db.php';

// 初始化默认值
$default_settings = [
    'api_key' => '',
    'prompt' => '',
    'api_url' => 'https://api.spanstar.cn'
];

$settings = $default_settings;
$error = '';
$success = '';

try {
    // 安全查询设置
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // 合并默认值与数据库数据
        $settings = array_merge($default_settings, $row);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 输入验证
        $api_key = trim($_POST['api_key'] ?? '');
        $prompt = trim($_POST['prompt'] ?? '');
        $api_url = filter_var($_POST['api_url'] ?? '', FILTER_VALIDATE_URL)
                 ? $_POST['api_url'] 
                 : $default_settings['api_url'];

        // 更新数据库
        $stmt = $pdo->prepare("UPDATE settings SET api_key=?, prompt=?, api_url=? WHERE id=1");
        if ($stmt->execute([$api_key, $prompt, $api_url])) {
            $success = '保存成功';
            // 实时更新界面显示
            $settings['api_key'] = $api_key;
            $settings['prompt'] = $prompt;
            $settings['api_url'] = $api_url;
        } else {
            $error = '保存失败';
        }
    }
} catch (PDOException $e) {
    $error = '数据库错误: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>系统设置</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* 页面布局 */
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }

        /* 标题样式 */
        h1 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.8em;
            font-weight: 600;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }

        /* 表单增强 */
        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus,
        textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        /* 按钮样式 */
        button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        /* 提示信息 */
        .success, .error {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .success {
            background: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        /* 小提示 */
        small {
            display: block;
            margin-top: 6px;
            color: #7f8c8d;
            font-size: 0.9em;
        }

        /* 响应式设计 */
        @media (max-width: 600px) {
            .container {
                padding: 20px 15px;
            }

            input[type="text"],
            textarea {
                font-size: 0.95em;
            }

            button {
                width: 100%;
                text-align: center;
            }
        }

        /* 动画效果 */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 卡片阴影 */
        .card-shadow {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container fade-in">
        <h1>系统设置</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <form method="post" class="card-shadow">
            <!-- API密钥 -->
            <div class="form-group">
                <label>API密钥</label>
                <input type="text" name="api_key" 
                       value="<?= htmlspecialchars($settings['api_key'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="请输入您的API密钥">
            </div>
            
            <!-- API地址 -->
            <div class="form-group">
                <label>API地址</label>
                <input type="text" name="api_url" 
                       value="<?= htmlspecialchars($settings['api_url'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="例如: https://api.yourdomain.com">
                <small>建议使用HTTPS地址，示例: https://api.spanstar.cn</small>
            </div>
            
            <!-- Prompt设置 -->
            <div class="form-group">
                <label>Prompt设置</label>
                <textarea name="prompt" 
                          placeholder="请输入系统提示词..."><?= htmlspecialchars($settings['prompt'], ENT_QUOTES, 'UTF-8') ?></textarea>
                <small>该提示词将作为所有模型的初始系统指令</small>
            </div>
            
            <button type="submit">保存设置</button>
        </form>
    </div>
</body>
</html>