-- 完整数据库修复脚本
-- 修复日期: 2025-11-23
-- 版本: V3.0 完整修复版

-- 1. 确保 chat_logs 表存在且结构正确
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `message` longtext NOT NULL,
  `response` longtext NOT NULL,
  `model_used` varchar(100) DEFAULT NULL,
  `tokens_used` int(11) DEFAULT NULL,
  `response_time` decimal(10,3) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `action` varchar(100) DEFAULT NULL COMMENT '操作类型',
  `description` text COMMENT '操作描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_model` (`model_used`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聊天记录表';

-- 2. 如果表已存在但缺少字段，则添加缺失的字段
ALTER TABLE `chat_logs` 
ADD COLUMN IF NOT EXISTS `action` varchar(100) DEFAULT NULL COMMENT '操作类型',
ADD COLUMN IF NOT EXISTS `description` text COMMENT '操作描述';

-- 3. 确保其他核心表存在
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 4. 邀请码表
CREATE TABLE IF NOT EXISTS `invite_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE COMMENT '邀请码',
  `max_uses` int(11) DEFAULT 1 COMMENT '最大使用次数',
  `used_count` int(11) DEFAULT 0 COMMENT '已使用次数',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `status` enum('active','inactive','expired') DEFAULT 'active' COMMENT '状态',
  `created_by` int(11) DEFAULT NULL COMMENT '创建者ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_by` int(11) DEFAULT NULL COMMENT '使用者ID',
  `used_at` timestamp NULL DEFAULT NULL COMMENT '使用时间',
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `invite_codes_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invite_codes_used_by_fk` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邀请码表';

-- 5. 设置表
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `prompt` text,
  `kb_enabled` tinyint(1) DEFAULT 0,
  `kb_threshold` decimal(3,2) DEFAULT 0.70,
  `kb_max_results` int(11) DEFAULT 5,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- 6. 知识库表
CREATE TABLE IF NOT EXISTS `knowledge_base` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_title` (`title`),
  FULLTEXT KEY `idx_content` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='知识库表';

-- 7. 文件上传表
CREATE TABLE IF NOT EXISTS `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  CONSTRAINT `file_uploads_uploaded_by_fk` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件上传表';

-- 8. 插入默认数据（如果不存在）
INSERT IGNORE INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yizi.com', 'admin');

INSERT IGNORE INTO `settings` (`api_key`, `api_url`, `prompt`, `kb_enabled`, `kb_threshold`, `kb_max_results`) VALUES
('', 'https://api.spanstar.cn', '你是一个有用的AI助手，请用友好、专业的方式回答用户的问题。', 1, 0.70, 5);

-- 9. 创建一些示例邀请码
INSERT IGNORE INTO `invite_codes` (`code`, `max_uses`, `expires_at`, `status`, `created_by`) VALUES
('ADMIN2025001', 1, DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 1),
('ADMIN2025002', 1, DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', 1),
('DEMO12345', 1, DATE_ADD(NOW(), INTERVAL 7 DAY), 'active', 1);

-- 完成提示
SELECT '数据库修复完成！' AS status, NOW() AS completed_at;