-- YiZi AI 智能客服系统数据库初始化脚本
-- 数据库版本: v3.0
-- 创建时间: 2025-11-23

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `api` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `api`;

-- 删除可能存在的表（重新安装时）
DROP TABLE IF EXISTS `chat_logs`;
DROP TABLE IF EXISTS `file_uploads`;
DROP TABLE IF EXISTS `kb_entries`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

-- 创建用户表
CREATE TABLE `users` (
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
CREATE TABLE `settings` (
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

-- 创建知识库表
CREATE TABLE `kb_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '标题',
  `content` longtext NOT NULL COMMENT '内容',
  `summary` text COMMENT '摘要',
  `category_id` int(11) DEFAULT NULL COMMENT '分类ID',
  `tags` varchar(500) DEFAULT '' COMMENT '标签',
  `file_path` varchar(255) DEFAULT NULL COMMENT '关联文件路径',
  `file_type` varchar(50) DEFAULT NULL COMMENT '文件类型',
  `file_size` int(11) DEFAULT NULL COMMENT '文件大小',
  `view_count` int(11) DEFAULT 0 COMMENT '查看次数',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `idx_content` (`title`,`content`,`summary`),
  KEY `idx_category` (`category_id`),
  KEY `idx_tags` (`tags`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库条目表';

-- 创建知识库分类表
CREATE TABLE `kb_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `description` text COMMENT '分类描述',
  `sort_order` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识库分类表';

-- 创建聊天日志表
CREATE TABLE `chat_logs` (
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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_model` (`model_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天日志表';

-- 创建文件上传表
CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '上传用户ID',
  `original_name` varchar(255) NOT NULL COMMENT '原始文件名',
  `file_name` varchar(255) NOT NULL COMMENT '存储文件名',
  `file_path` varchar(500) NOT NULL COMMENT '文件路径',
  `file_size` int(11) NOT NULL COMMENT '文件大小',
  `file_type` varchar(50) NOT NULL COMMENT '文件类型',
  `mime_type` varchar(100) NOT NULL COMMENT 'MIME类型',
  `content_preview` longtext COMMENT '内容预览',
  `processed` tinyint(1) DEFAULT 0 COMMENT '是否已处理',
  `upload_ip` varchar(45) DEFAULT NULL COMMENT '上传IP',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件上传表';

-- 插入默认设置
INSERT INTO `settings` (`api_key`, `api_url`, `prompt`, `kb_enabled`, `kb_threshold`, `kb_max_results`) VALUES
('', 'https://api.spanstar.cn', '你是一个有用的AI助手，请用友好、专业的方式回答用户的问题。', 1, 0.70, 5);

-- 插入默认知识库分类
INSERT INTO `kb_categories` (`name`, `description`, `sort_order`) VALUES
('使用指南', '系统使用说明和常见问题', 1),
('技术支持', '技术问题和解决方案', 2),
('产品介绍', '产品功能和特性说明', 3);

-- 插入示例知识库条目
INSERT INTO `kb_entries` (`title`, `content`, `summary`, `category_id`, `tags`) VALUES
('如何使用AI聊天功能', 
'您可以通过以下步骤使用AI聊天功能：\n1. 在输入框中输入您的问题\n2. 选择合适的AI模型\n3. 点击发送按钮或按回车键\n4. 等待AI回复\n5. 可以继续对话或清空历史记录',
'介绍了AI聊天功能的基本使用方法', 
1, 
'使用指南,聊天,AI'),
('文件上传功能说明',
'系统支持上传多种文件类型：\n- 文本文件(.txt, .md)\n- PDF文档(.pdf)\n- Word文档(.docx)\n- CSV数据文件(.csv)\n\n上传后，AI可以基于文件内容回答问题。',
'说明文件上传功能和支持的文件类型',
1,
'文件上传,文档,支持格式');

-- 插入默认管理员账户
-- 默认密码: admin123 (建议安装后立即修改)
INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yizi.com', 'admin');

-- 创建索引优化查询性能
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_chat_logs_user_time ON chat_logs(user_id, created_at);
CREATE INDEX idx_kb_entries_created ON kb_entries(created_at);

-- 添加外键约束
ALTER TABLE `kb_entries` ADD CONSTRAINT `fk_kb_entries_category` FOREIGN KEY (`category_id`) REFERENCES `kb_categories` (`id`) ON DELETE SET NULL;
ALTER TABLE `chat_logs` ADD CONSTRAINT `fk_chat_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `file_uploads` ADD CONSTRAINT `fk_file_uploads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;