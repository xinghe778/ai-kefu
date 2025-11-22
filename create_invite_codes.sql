-- 邀请码管理数据库表结构
-- 数据库版本: v3.1
-- 创建时间: 2025-11-23

USE `api`;

-- 创建邀请码表
CREATE TABLE IF NOT EXISTS `invite_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT '邀请码',
  `created_by` int(11) NOT NULL COMMENT '创建者ID（管理员）',
  `used_by` int(11) DEFAULT NULL COMMENT '使用者ID',
  `used_at` timestamp NULL DEFAULT NULL COMMENT '使用时间',
  `status` enum('active','used','expired') DEFAULT 'active' COMMENT '状态',
  `max_uses` int(11) DEFAULT 1 COMMENT '最大使用次数',
  `used_count` int(11) DEFAULT 0 COMMENT '已使用次数',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code` (`code`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_used_by` (`used_by`),
  KEY `idx_status` (`status`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='邀请码表';

-- 添加外键约束
ALTER TABLE `invite_codes` ADD CONSTRAINT `fk_invite_codes_created_by` 
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `invite_codes` ADD CONSTRAINT `fk_invite_codes_used_by` 
  FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 为 users 表添加邀请码字段（可选，用于记录注册使用的邀请码）
ALTER TABLE `users` ADD COLUMN `invite_code_used` varchar(50) DEFAULT NULL COMMENT '注册使用的邀请码';

-- 插入示例邀请码（供测试使用）
INSERT INTO `invite_codes` (`code`, `created_by`, `description`) VALUES
('ADMIN2025', 1, '管理员测试邀请码'),
('TEST001', 1, '测试用户邀请码001'),
('VIP2025', 1, 'VIP用户邀请码');

-- 创建索引优化
CREATE INDEX idx_invite_codes_status_expires ON invite_codes(status, expires_at);

-- 显示创建结果
SELECT '邀请码表创建完成' as message;
SELECT COUNT(*) as invite_code_count FROM invite_codes;