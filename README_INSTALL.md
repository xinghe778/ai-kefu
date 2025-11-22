# AI客服系统 (ai-kefu) 一键安装指南

## 项目简介

**AI客服系统 (ai-kefu)** 是一个基于 PHP 的智能客服解决方案，支持前后端分离架构，提供完整的后台管理系统。

- **项目地址**: https://github.com/xinghe778/ai-kefu
- **技术栈**: PHP 7.4+, MySQL, Apache, Shell脚本
- **系统支持**: CentOS 7/8/9

## 快速安装

### 方法一：一键完整安装（推荐新用户）

适用于全新环境，会自动安装 LAMP 环境并部署项目。

```bash
# 下载安装脚本
curl -sSL https://raw.githubusercontent.com/xinghe778/ai-kefu/main/install.sh -o install.sh
chmod +x install.sh

# 执行安装（需要root权限）
sudo ./install.sh
```

**安装过程**:
1. 系统兼容性检查
2. 安装系统依赖
3. 配置 LAMP 环境（Apache + MySQL + PHP）
4. 克隆项目代码
5. 配置数据库
6. 初始化项目
7. 验证安装结果

### 方法二：快速安装（适用于已有 LAMP 环境）

适用于已经配置好 Apache + MySQL + PHP 的环境。

```bash
# 下载快速安装脚本
curl -sSL https://raw.githubusercontent.com/xinghe778/ai-kefu/main/install-quick.sh -o install-quick.sh
chmod +x install-quick.sh

# 执行快速安装
sudo ./install-quick.sh
```

## 安装后配置

### 1. 访问系统

安装完成后，通过以下地址访问系统：

- **Web访问**: http://your-server-ip/
- **管理后台**: http://your-server-ip/admin/

### 2. 数据库配置

安装过程中会生成数据库凭据，保存在 `/tmp/db_credentials.txt`：

```bash
# 查看数据库配置
cat /tmp/db_credentials.txt
```

### 3. 系统验证

运行验证脚本检查安装状态：

```bash
./verify.sh
```

## 故障排除

### 常见问题

**问题1**: Apache 服务未启动
```bash
# 启动 Apache
sudo systemctl start httpd
sudo systemctl enable httpd
```

**问题2**: MySQL 服务未启动
```bash
# 启动 MySQL
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

**问题3**: 权限问题
```bash
# 修复文件权限
sudo chown -R apache:apache /var/www/html/ai-kefu
sudo chmod -R 755 /var/www/html/ai-kefu
```

**问题4**: 防火墙阻止访问
```bash
# 开放80端口
sudo firewall-cmd --permanent --add-port=80/tcp
sudo firewall-cmd --reload
```

### 自动故障排除

运行自动诊断工具：

```bash
./troubleshoot.sh
```

该工具会自动：
- 检测服务状态
- 检查配置文件
- 修复常见问题
- 生成诊断报告

## 脚本说明

### 主要脚本

1. **install.sh** - 完整安装脚本
   - 安装 LAMP 环境
   - 部署项目
   - 配置数据库
   - 设置权限

2. **install-quick.sh** - 快速安装脚本
   - 适用于已有 LAMP 环境
   - 仅部署项目
   - 快速配置

3. **verify.sh** - 验证脚本
   - 检查服务状态
   - 验证配置正确性
   - 显示系统信息

4. **troubleshoot.sh** - 故障排除脚本
   - 自动检测问题
   - 修复常见故障
   - 生成诊断报告

### 脚本特性

- **彩色输出**: 清晰的安装进度提示
- **错误处理**: 完善的错误检查和恢复
- **日志记录**: 详细的安装日志
- **安全设置**: 合理的文件权限配置
- **自动化**: 一键完成所有配置

## 系统要求

### 最低配置
- **操作系统**: CentOS 7/8/9
- **内存**: 512MB RAM
- **磁盘**: 2GB 可用空间
- **网络**: 稳定的互联网连接

### 推荐配置
- **操作系统**: CentOS 8/9
- **内存**: 1GB+ RAM
- **磁盘**: 5GB+ 可用空间
- **网络**: 高速互联网连接

### 依赖软件
- **Apache**: 2.4+
- **MySQL**: 5.7+ / 8.0+
- **PHP**: 7.4+ (推荐 8.0+)
- **Git**: 最新版本
- **Composer**: PHP 包管理器（可选）

## 目录结构

安装完成后的目录结构：

```
/var/www/html/ai-kefu/
├── admin/              # 管理后台
├── uploads/            # 文件上传目录
├── user_input_files/      # 用户文件
├── index.php           # 首页文件
├── README.md           # 项目说明
├── *.php               # 其他PHP文件
├── *.sh               # 安装维护脚本
└── *.sql              # 数据库脚本
```

## 安全建议

### 1. 防火墙配置
```bash
# 仅开放必要端口
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 2. 访问控制
- 限制管理后台访问 IP
- 设置强密码
- 定期更新系统

### 3. 文件权限
```bash
# 设置安全权限
sudo chmod 644 /var/www/html/ai-kefu/*.php
sudo chmod 755 /var/www/html/ai-kefu/admin/
sudo chmod -R 755 /var/www/html/ai-kefu/uploads/
```

## 维护命令

### 服务管理
```bash
# 重启 Apache
sudo systemctl restart httpd

# 重启 MySQL
sudo systemctl restart mysqld

# 查看服务状态
sudo systemctl status httpd
sudo systemctl status mysqld
```

### 日志查看
```bash
# Apache 错误日志
sudo tail -f /var/log/httpd/ai-kefu-error.log

# 安装日志
sudo tail -f /var/log/ai-kefu-quick-install.log

# 系统日志
sudo journalctl -u httpd -f
sudo journalctl -u mysqld -f
```

### 数据库备份
```bash
# 备份数据库
mysqldump -u aikefu -p ai_kefu > backup_$(date +%Y%m%d).sql

# 恢复数据库
mysql -u aikefu -p ai_kefu < backup_20231201.sql
```

## 卸载系统

如果需要完全卸载系统：

```bash
# 停止服务
sudo systemctl stop httpd mysqld

# 删除项目目录
sudo rm -rf /var/www/html/ai-kefu

# 删除 Apache 配置
sudo rm -f /etc/httpd/conf.d/ai-kefu.conf

# 删除数据库
mysql -u root -e "DROP DATABASE IF EXISTS ai_kefu;"
mysql -u root -e "DROP USER IF EXISTS 'aikefu'@'localhost';"

# 重启服务
sudo systemctl restart httpd
```

## 支持与帮助

### 获取帮助
- **GitHub Issues**: https://github.com/xinghe778/ai-kefu/issues
- **项目文档**: 查看项目内的 README.md 文件
- **故障排除**: 运行 `./troubleshoot.sh` 获取详细诊断

### 常见资源
- **Apache 文档**: https://httpd.apache.org/docs/
- **MySQL 文档**: https://dev.mysql.com/doc/
- **PHP 文档**: https://www.php.net/docs.php

## 版本信息

- **当前版本**: v1.0
- **更新日期**: 2025-11-23
- **兼容性**: CentOS 7/8/9
- **依赖版本**: PHP 7.4+, MySQL 5.7+

---

**安装过程中如遇问题，请优先使用故障排除脚本获取帮助。**