# 🚀 YiZi AI V3.0 CentOS 一键安装完整指南

## 📋 目录

1. [概述](#概述)
2. [系统要求](#系统要求)
3. [安装方法](#安装方法)
4. [详细安装步骤](#详细安装步骤)
5. [安装后配置](#安装后配置)
6. [验证安装](#验证安装)
7. [故障排除](#故障排除)
8. [管理命令](#管理命令)
9. [卸载指南](#卸载指南)
10. [更新升级](#更新升级)
11. [常见问题](#常见问题)
12. [技术支持](#技术支持)

---

## 概述

**YiZi AI V3.0** 是一款基于PHP 8.1+、MySQL 8.0和Apache的智能聊天系统。本指南提供了完整的CentOS一键安装解决方案，支持CentOS 7和8。

### ✨ 主要特性

- 🤖 **智能聊天**: 支持多种AI模型接入
- 👥 **用户管理**: 完整的用户注册、权限控制系统
- 🎫 **邀请码系统**: 邀请码生成和管理
- 📊 **数据统计**: 聊天记录、用户统计
- 🎨 **现代化界面**: 响应式设计，支持主题切换
- 🔒 **安全机制**: CSRF保护、密码加密、权限控制
- 🛡️ **PHP 8.1+兼容**: 兼容最新PHP版本

---

## 系统要求

### 最低要求

| 组件 | 最低版本 | 建议版本 |
|------|---------|---------|
| **操作系统** | CentOS 7 | CentOS 8 |
| **内存** | 1GB RAM | 2GB+ RAM |
| **磁盘空间** | 2GB | 5GB+ |
| **CPU** | 1核心 | 2核心+ |

### 软件依赖

- **PHP**: 8.1+ (必需扩展: pdo_mysql, mysqli, mbstring, gd, curl, zip)
- **MySQL**: 8.0+
- **Apache**: 2.4+
- **Composer**: 最新版本

### 网络要求

- **端口**: 80 (HTTP), 443 (HTTPS可选)
- **互联网**: 安装期间需要下载软件包
- **防火墙**: 需要允许HTTP/HTTPS访问

---

## 安装方法

### 🎯 方法1: 一键快速安装（推荐）

**适用于**: 新手用户，快速部署

```bash
# 执行快速安装
curl -sSL https://raw.githubusercontent.com/your-repo/install-quick.sh | bash
```

**特点**:
- ✅ 安装时间最短（5-10分钟）
- ✅ 自动配置基本设置
- ✅ 适合学习和测试
- ❌ 配置选项有限

### 🔧 方法2: 完整安装

**适用于**: 生产环境，完整功能

```bash
# 下载完整安装脚本
wget https://raw.githubusercontent.com/your-repo/install-centos.sh

# 赋予执行权限
chmod +x install-centos.sh

# 执行安装（需要root权限）
sudo ./install-centos.sh
```

**特点**:
- ✅ 完整的配置选项
- ✅ 生产环境就绪
- ✅ 自动生成安全密钥
- ✅ 安装时间较长（15-30分钟）

### 📦 方法3: 离线安装

**适用于**: 内网环境，无外网访问

```bash
# 1. 在有网络的机器上下载安装包
wget https://github.com/your-repo/yizi-ai/releases/latest/download/yizi-ai-offline.tar.gz

# 2. 传输到目标服务器
scp yizi-ai-offline.tar.gz user@target-server:/tmp/

# 3. 在目标服务器上解压并安装
cd /tmp
tar -xzf yizi-ai-offline.tar.gz
cd yizi-ai-offline
chmod +x install-offline.sh
sudo ./install-offline.sh
```

---

## 详细安装步骤

### 步骤1: 系统准备

```bash
# 检查系统版本
cat /etc/centos-release

# 更新系统包
sudo yum update -y

# 安装基础工具
sudo yum install -y epel-release yum-utils wget curl git vim
```

### 步骤2: 数据库安装

```bash
# 安装MySQL 8.0
sudo yum install -y mysql-server mysql

# 启动并设置开机自启
sudo systemctl enable --now mysqld

# 安全初始化（可选，建议在生产环境使用）
sudo mysql_secure_installation
```

### 步骤3: Web服务器安装

```bash
# 安装Apache 2.4
sudo yum install -y httpd

# 启动并设置开机自启
sudo systemctl enable --now httpd

# 配置防火墙
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --reload
```

### 步骤4: PHP安装

```bash
# 安装Remi仓库
sudo yum install -y yum-utils
sudo yum install -y http://rpms.remirepo.net/enterprise/remi-release-8.rpm

# 启用PHP 8.1模块
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.1 -y

# 安装PHP及扩展
sudo yum install -y php php-fpm php-mysql php-mysqli php-mbstring php-gd php-zip php-curl php-xml php-pear php-bcmath php-intl
```

### 步骤5: 应用部署

```bash
# 创建安装目录
sudo mkdir -p /var/www/yizi-ai

# 下载项目文件
sudo cd /var/www/yizi-ai
sudo wget https://github.com/your-repo/yizi-ai/archive/main.tar.gz
sudo tar -xzf main.tar.gz --strip-components=1

# 设置权限
sudo chown -R apache:apache /var/www/yizi-ai
sudo chmod -R 755 /var/www/yizi-ai
```

### 步骤6: 数据库初始化

```sql
-- 创建数据库和用户
CREATE DATABASE yizi_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'yizi_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON yizi_ai.* TO 'yizi_user'@'localhost';
FLUSH PRIVILEGES;

-- 导入数据库结构
SOURCE /var/www/yizi-ai/database/schema.sql;
```

### 步骤7: Apache配置

```bash
# 创建虚拟主机配置
sudo tee /etc/httpd/conf.d/yizi-ai.conf << 'EOF'
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/yizi-ai
    
    <Directory /var/www/yizi-ai>
        AllowOverride All
        Require all granted
    </Directory>
    
    # 安全配置
    <Directory /var/www/yizi-ai/logs>
        Deny from all
    </Directory>
    
    # PHP配置
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/var/run/php-fpm/www.sock|fcgi://localhost"
    </FilesMatch>
    
    # 日志配置
    ErrorLog /var/log/httpd/yizi-ai-error.log
    CustomLog /var/log/httpd/yizi-ai-access.log combined
</VirtualHost>
EOF

# 测试配置
sudo httpd -t

# 重启Apache
sudo systemctl restart httpd
```

---

## 安装后配置

### 基本配置

1. **访问管理后台**
   ```
   http://your-domain.com/admin/login.php
   ```

2. **默认管理员账户**
   - 用户名: `admin`
   - 密码: `admin` (首次登录后请修改)

3. **API配置**
   - 登录管理后台
   - 进入"系统设置"
   - 配置AI API密钥和接口地址

### 高级配置

#### SSL证书配置（推荐）

```bash
# 安装Certbot
sudo yum install -y certbot python3-certbot-apache

# 获取SSL证书
sudo certbot --apache -d your-domain.com

# 自动续期
sudo crontab -e
# 添加: 0 12 * * * /usr/bin/certbot renew --quiet
```

#### 性能优化

```bash
# PHP OPcache配置
sudo tee /etc/php.d/10-opcache.conf << 'EOF'
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
EOF

# MySQL优化
sudo tee -a /etc/my.cnf << 'EOF'
[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 32M
max_connections = 200
EOF

# 重启服务
sudo systemctl restart httpd mysqld
```

---

## 验证安装

### 自动验证

```bash
# 使用验证工具
curl -sSL https://raw.githubusercontent.com/your-repo/verify-install.sh | bash
```

### 手动验证

1. **检查服务状态**
   ```bash
   sudo systemctl status httpd
   sudo systemctl status mysqld
   ```

2. **测试网站访问**
   ```bash
   curl -I http://localhost
   ```

3. **检查数据库连接**
   ```bash
   mysql -u your_user -p -e "USE yizi_ai; SHOW TABLES;"
   ```

4. **查看错误日志**
   ```bash
   sudo tail -f /var/log/httpd/error_log
   sudo tail -f /var/log/mysqld.log
   ```

---

## 故障排除

### 常见问题

#### 1. 安装失败

**问题**: 安装脚本执行失败
```bash
# 解决方案
# 1. 检查日志
sudo tail -f /var/log/yizi-ai-install.log

# 2. 运行诊断工具
curl -sSL https://raw.githubusercontent.com/your-repo/troubleshoot.sh | bash

# 3. 手动修复常见问题
sudo systemctl restart httpd mysqld
sudo httpd -t
```

#### 2. 数据库连接失败

**问题**: 无法连接到MySQL数据库

```bash
# 检查MySQL服务
sudo systemctl status mysqld

# 检查MySQL端口
sudo netstat -tuln | grep 3306

# 检查用户权限
mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User='yizi_user';"

# 重置密码（如果需要）
mysql -u root -p -e "ALTER USER 'yizi_user'@'localhost' IDENTIFIED BY 'new_password';"
```

#### 3. 页面显示错误

**问题**: 网站显示500错误或白屏

```bash
# 检查Apache错误日志
sudo tail -50 /var/log/httpd/error_log

# 检查PHP错误日志
sudo tail -50 /var/log/php_errors.log

# 检查文件权限
ls -la /var/www/yizi-ai/

# 修复权限
sudo chown -R apache:apache /var/www/yizi-ai
sudo chmod -R 755 /var/www/yizi-ai
```

#### 4. 权限问题

**问题**: SELinux阻止访问

```bash
# 检查SELinux状态
sudo getenforce

# 如果是Enforcing模式，设置正确的上下文
sudo restorecon -R /var/www/yizi-ai
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_connect_db 1
```

### 诊断工具

```bash
# 一键诊断
curl -sSL https://raw.githubusercontent.com/your-repo/troubleshoot.sh | bash

# 检查系统信息
sudo bash -c '
echo "=== 系统信息 ==="
cat /etc/centos-release
free -h
df -h

echo "=== 服务状态 ==="
systemctl is-active httpd
systemctl is-active mysqld

echo "=== 端口监听 ==="
netstat -tuln | grep -E ":80|:443|:3306"

echo "=== 最近的错误 ==="
tail -10 /var/log/httpd/error_log
tail -10 /var/log/mysqld.log
'
```

---

## 管理命令

### 服务管理

```bash
# Apache管理
sudo systemctl start httpd     # 启动
sudo systemctl stop httpd      # 停止
sudo systemctl restart httpd   # 重启
sudo systemctl status httpd    # 状态
sudo systemctl enable httpd    # 开机自启

# MySQL管理
sudo systemctl start mysqld    # 启动
sudo systemctl stop mysqld     # 停止
sudo systemctl restart mysqld  # 重启
sudo systemctl status mysqld   # 状态
sudo systemctl enable mysqld   # 开机自启

# PHP-FPM管理
sudo systemctl start php-fpm   # 启动
sudo systemctl restart php-fpm # 重启
sudo systemctl status php-fpm  # 状态
```

### 备份管理

```bash
# 自动备份（已配置在定时任务中）
# 手动备份
sudo /var/www/yizi-ai/backup.sh

# 数据库备份
mysqldump -u yizi_user -p yizi_ai > backup_$(date +%Y%m%d).sql

# 文件备份
tar -czf yizi_ai_files_$(date +%Y%m%d).tar.gz /var/www/yizi-ai
```

### 监控管理

```bash
# 查看系统资源
htop
free -h
df -h

# 查看服务状态
systemctl list-units --type=service | grep -E "httpd|mysqld"

# 查看网络连接
netstat -tuln | grep -E ":80|:443|:3306"

# 查看实时日志
tail -f /var/log/httpd/access_log
tail -f /var/log/httpd/error_log
tail -f /var/log/mysqld.log
```

---

## 卸载指南

### 自动卸载

```bash
# 使用一键卸载脚本
curl -sSL https://raw.githubusercontent.com/your-repo/uninstall.sh | bash
```

### 手动卸载

```bash
# 1. 停止服务
sudo systemctl stop httpd mysqld

# 2. 删除网站文件
sudo rm -rf /var/www/yizi-ai

# 3. 删除数据库
mysql -u root -p -e "DROP DATABASE IF EXISTS yizi_ai; DROP USER IF EXISTS 'yizi_user'@'localhost';"

# 4. 删除Apache配置
sudo rm -f /etc/httpd/conf.d/yizi-ai.conf

# 5. 清理定时任务
sudo crontab -l | grep -v yizi-ai | sudo crontab -

# 6. 清理日志文件
sudo rm -f /var/log/yizi-ai-*.log

# 7. 重启服务
sudo systemctl restart httpd mysqld
```

---

## 更新升级

### 在线更新

```bash
# 下载更新脚本
wget https://raw.githubusercontent.com/your-repo/update.sh

# 执行更新
chmod +x update.sh
sudo ./update.sh
```

### 手动更新

```bash
# 1. 备份当前系统
sudo /var/www/yizi-ai/backup.sh

# 2. 下载新版本
cd /var/www
sudo wget https://github.com/your-repo/yizi-ai/archive/v3.1.0.tar.gz
sudo tar -xzf v3.1.0.tar.gz --strip-components=1

# 3. 更新数据库
mysql -u yizi_user -p yizi_ai < database/updates/v3.1.0.sql

# 4. 重新设置权限
sudo chown -R apache:apache /var/www/yizi-ai
sudo chmod -R 755 /var/www/yizi-ai

# 5. 重启服务
sudo systemctl restart httpd mysqld
```

---

## 常见问题

### Q: 安装后无法访问网站？
**A**: 
1. 检查服务状态: `sudo systemctl status httpd`
2. 检查防火墙: `sudo firewall-cmd --list-services`
3. 检查端口监听: `sudo netstat -tuln | grep :80`

### Q: 数据库连接失败？
**A**:
1. 确认MySQL服务运行: `sudo systemctl status mysqld`
2. 检查用户权限: `mysql -u root -p -e "SELECT User, Host FROM mysql.user WHERE User='yizi_user';"`
3. 检查配置文件中的数据库信息

### Q: PHP版本不兼容？
**A**:
1. 检查PHP版本: `php -v`
2. 确保安装PHP 8.1+: `sudo dnf module enable php:remi-8.1`
3. 重启Apache: `sudo systemctl restart httpd`

### Q: 如何修改默认管理员密码？
**A**:
1. 登录管理后台
2. 进入"个人资料"页面
3. 修改密码并保存

### Q: 如何配置SSL证书？
**A**:
1. 安装Certbot: `sudo yum install -y certbot python3-certbot-apache`
2. 获取证书: `sudo certbot --apache -d your-domain.com`
3. 自动续期: 配置定时任务

### Q: 系统性能优化建议？
**A**:
1. 启用OPcache
2. 配置MySQL缓存
3. 使用CDN加速静态资源
4. 定期清理日志文件
5. 监控系统资源使用

### Q: 如何备份和恢复数据？
**A**:
```bash
# 备份
sudo /var/www/yizi-ai/backup.sh

# 恢复
mysql -u yizi_user -p yizi_ai < backup_file.sql
tar -xzf website_backup.tar.gz -C /
```

---

## 技术支持

### 获取帮助

1. **查看文档**: 本安装指南
2. **运行诊断**: 使用故障诊断工具
3. **查看日志**: `/var/log/yizi-ai-install.log`
4. **GitHub Issues**: [提交问题](https://github.com/your-repo/yizi-ai/issues)

### 联系信息

- **邮箱**: support@yi-zi.com
- **QQ群**: 123456789
- **微信群**: 扫描二维码
- **官网**: https://www.yi-zi.com

### 报告问题时请提供

1. **系统信息**: `cat /etc/centos-release`
2. **错误日志**: 相关错误信息
3. **安装日志**: `/var/log/yizi-ai-install.log`
4. **诊断报告**: 运行诊断工具生成的报告

---

## 更新历史

| 版本 | 日期 | 更新内容 |
|------|------|----------|
| v3.0.0 | 2025-11-23 | 初始发布，支持CentOS一键安装 |
| v3.0.1 | - | 修复PHP 8.1兼容性问题 |
| v3.0.2 | - | 优化安装脚本，增强错误处理 |

---

## 许可证

本软件遵循MIT许可证。详情请查看LICENSE文件。

---

**© 2025 YiZi AI Team. All Rights Reserved.**