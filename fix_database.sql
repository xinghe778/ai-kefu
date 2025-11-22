-- 修复 YiZi AI 数据库表名问题
-- 此脚本将创建缺失的 chat_logs 表和修复表结构

USE api;

-- 检查是否存在 chat_logs 表，如果不存在则创建
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `message` longtext NOT NULL COMMENT '用户消息',
  `response` longtext NOT NULL COMMENT 'AI回复',
  `model_used` varchar(100) DEFAULT NULL COMMENT '使用的模型',
  `tokens_used` int(11) DEFAULT NULL COMMENT '使用的token数',
  `response_time` decimal(10,3) DEFAULT NULL COMMENT '响应时间(秒)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text COMMENT '用户代理',
  `action` varchar(100) DEFAULT NULL COMMENT '操作类型',
  `description` text COMMENT '操作描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_model` (`model_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天日志表';

-- 检查并创建其他必要的表
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码哈希',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `role` enum('admin','user','guest') DEFAULT 'user' COMMENT '用户角色',
  `status` enum('active','inactive','banned') DEFAULT 'active' COMMENT '账户状态',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `last_login` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `login_count` int(11) DEFAULT 0 COMMENT '登录次数',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- 创建设置表
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(500) NOT NULL DEFAULT '' COMMENT 'API密钥',
  `api_url` varchar(255) NOT NULL DEFAULT 'https://api.spanstar.cn' COMMENT 'API地址',
  `prompt` text COMMENT '系统提示词',
  `kb_enabled` tinyint(1) DEFAULT 1 COMMENT '是否启用知识库',
  `kb_threshold` decimal(3,2) DEFAULT 0.70 COMMENT '知识库检索阈值',
  `kb_max_results` int(11) DEFAULT 5 COMMENT '知识库最大结果数',
  `max_file_size` int(11) DEFAULT 10485760 COMMENT '最大文件上传大小(字节)',
  `allowed_file_types` text COMMENT '允许的文件类型',
  `chat_history_limit` int(11) DEFAULT 1000 COMMENT '聊天记录保存数量',
  `theme` varchar(20) DEFAULT 'light' COMMENT '默认主题',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统设置表';

-- 检查是否存在默认管理员账户，如果不存在则创建
INSERT IGNORE INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yizi.com', 'admin');

-- 检查是否已有默认设置，如果不存在则插入
INSERT IGNORE INTO `settings` (`api_key`, `api_url`, `prompt`, `kb_enabled`, `kb_threshold`, `kb_max_results`) VALUES
('', 'https://api.spanstar.cn', '你是一个有用的AI助手，请用友好、专业的方式回答用户的问题。', 1, 0.70, 5);

-- 显示修复结果
SELECT '数据库表结构修复完成' as message;
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as chat_logs_count FROM chat_logs;
SELECT COUNT(*) as settings_count FROM settings;