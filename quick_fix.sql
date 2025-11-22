-- 快速修复脚本 - 解决 action 字段错误
-- 直接在数据库管理界面（如phpMyAdmin）中执行

-- 1. 为 chat_logs 表添加缺失的字段
ALTER TABLE `chat_logs` 
ADD COLUMN `action` varchar(100) DEFAULT NULL COMMENT '操作类型',
ADD COLUMN `description` text COMMENT '操作描述';

-- 2. 如果索引不存在则添加
ALTER TABLE `chat_logs` 
ADD KEY IF NOT EXISTS `idx_action` (`action`);

-- 3. 验证修复 - 查看表结构
DESCRIBE `chat_logs`;

-- 4. 显示修复结果
SELECT '数据库字段修复完成！' AS 修复状态;