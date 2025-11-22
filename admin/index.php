<?php
require 'auth.php';
checkAuth();
require 'db.php';

// 获取统计数据
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$user_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM chat_logs WHERE DATE(created_at) = CURDATE()");
$log_count = $stmt->fetchColumn();

// 示例数据（根据实际业务替换）
$chat_stats = [
    'total' => 1500,
    'today' => 42,
    'avg_response' => '3.2s',
    'success_rate' => '98.7%'
];

// 图表数据
$chart_data = json_encode([
    'labels' => ['周一', '周二', '周三', '周四', '周五', '周六', '周日'],
    'data' => [12, 19, 3, 5, 2, 3, 8]
]);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>数据看板</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-title {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="dashboard">
        <h2>系统数据概览</h2>
        
        <!-- 统计卡片 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">注册用户数</div>
                <div class="stat-value"><?= $user_count ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">今日日志数</div>
                <div class="stat-value"><?= $log_count ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">总聊天记录</div>
                <div class="stat-value"><?= $chat_stats['total'] ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">今日会话量</div>
                <div class="stat-value"><?= $chat_stats['today'] ?></div>
            </div>
        </div>
        
        <!-- 图表区域 -->
        <div class="chart-container">
            <h3>周活跃趋势</h3>
            <canvas id="weeklyChart" height="100"></canvas>
        </div>
    </div>

    <script>
        // 图表初始化
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        const chartData = <?= $chart_data ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: '会话数量',
                    data: chartData.data,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    </script>
    <!-- 添加柱状图示例 -->
<canvas id="barChart" height="100"></canvas>
<script>
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: ['API调用', '错误率', '响应时间'],
        datasets: [{
            label: '性能指标',
            data: [<?=$api_calls?>, <?=$error_rate?>, <?=$response_time?>],
            backgroundColor: ['#2ecc71', '#e74c3c', '#3498db']
        }]
    }
});
</script>
</body>
</html>