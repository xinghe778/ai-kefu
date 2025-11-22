#!/bin/bash

# =============================================================================
# YiZi AI V3.0 快速安装脚本
# 适用于 CentOS 7/8 - 最小化安装版本
# 使用方法: curl -sSL https://raw.githubusercontent.com/your-repo/install-quick.sh | bash
# =============================================================================

set -e
set -u

# 配置
QUICK_INSTALL_URL="https://api.github.com/repos/your-username/yizi-ai/releases/latest"
WEB_USER="apache"
WEB_GROUP="apache"
DEFAULT_DOMAIN="localhost"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}[✓]${NC} $1"; }
print_error() { echo -e "${RED}[✗]${NC} $1"; }
print_info() { echo -e "${BLUE}[i]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[!]${NC} $1"; }

# 快速系统检查
quick_system_check() {
    print_info "快速系统检查..."
    
    # 检查root权限
    [[ $EUID -eq 0 ]] || { print_error "需要root权限"; exit 1; }
    
    # 检查CentOS
    [[ -f /etc/centos-release ]] || { print_error "仅支持CentOS系统"; exit 1; }
    
    # 检查内存
    local mem=$(free -m | awk '/^Mem:/{print $2}')
    [[ $mem -ge 1024 ]] || { print_error "内存不足，需要至少1GB"; exit 1; }
    
    print_success "系统检查通过"
}

# 一键安装核心组件
one_click_install() {
    print_info "开始一键安装..."
    
    # 更新系统（静默）
    print_info "更新系统包..."
    yum update -y -q
    
    # 安装必要组件
    print_info "安装核心组件..."
    yum install -y -q epel-release yum-utils wget curl git
    
    # 安装MySQL
    print_info "安装MySQL 8.0..."
    yum install -y -q mysql-server mysql
    systemctl enable --now mysqld
    
    # 安装Apache
    print_info "安装Apache 2.4..."
    yum install -y -q httpd
    systemctl enable --now httpd
    
    # 安装PHP 8.1+
    print_info "安装PHP 8.1+..."
    yum install -y -q yum-utils
    yum install -y -q http://rpms.remirepo.net/enterprise/remi-release-8.rpm
    
    # 启用PHP模块
    if command -v dnf &> /dev/null; then
        dnf module reset php -y -q
        dnf module enable php:remi-8.1 -y -q
    fi
    
    yum install -y -q php php-mysql php-mysqli php-mbstring php-gd php-zip php-curl
    
    print_success "核心组件安装完成"
}

# 快速部署
quick_deploy() {
    print_info "快速部署应用..."
    
    local install_dir="/var/www/yizi-ai"
    
    # 创建目录结构
    mkdir -p "$install_dir"/{admin,css,js,images,logs,uploads}
    
    # 创建基本文件
    cat > "$install_dir/index.php" << 'EOF'
<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>YiZi AI V3.0</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='text-center'>
        <h1>🎉 YiZi AI V3.0 安装成功！</h1>
        <p class='lead'>感谢使用 YiZi AI 智能聊天系统</p>
        <a href='/admin/login.php' class='btn btn-primary btn-lg'>进入管理后台</a>
        <hr>
        <p><strong>下一步：</strong></p>
        <ol class='text-start' style='max-width: 500px; margin: 0 auto;'>
            <li>访问管理后台并设置API密钥</li>
            <li>测试聊天功能</li>
            <li>邀请用户注册使用</li>
        </ol>
    </div>
</div>
</body>
</html>";
?>
EOF

    # 创建数据库配置
    cat > "$install_dir/config.php" << EOF
<?php
// YiZi AI V3.0 配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'api');
define('DB_USER', 'api');
define('DB_PASS', '$(openssl rand -base64 16 | tr -d "=+/" | cut -c1-16)');
define('SITE_URL', 'http://$DEFAULT_DOMAIN');
define('DEBUG_MODE', true);
?>
EOF

    # 创建数据库连接
    cat > "$install_DIR/db.php" << 'EOF'
<?php
require_once 'config.php';
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}
?>
EOF

    # 创建登录页面
    mkdir -p "$install_dir/admin"
    cat > "$install_dir/admin/login.php" << 'EOF'
<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>管理员登录 - YiZi AI</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-dark'>
<div class='container'>
    <div class='row justify-content-center'>
        <div class='col-md-6 col-lg-4' style='margin-top: 20vh;'>
            <div class='card'>
                <div class='card-body p-4'>
                    <h3 class='text-center mb-4'>YiZi AI 管理员</h3>
                    <form method='post'>
                        <div class='mb-3'>
                            <label class='form-label'>用户名</label>
                            <input type='text' class='form-control' name='username' value='admin' readonly>
                        </div>
                        <div class='mb-3'>
                            <label class='form-label'>密码</label>
                            <input type='password' class='form-control' name='password' required>
                        </div>
                        <div class='d-grid'>
                            <button class='btn btn-primary'>登录</button>
                        </div>
                    </form>
                    <div class='text-center mt-3'>
                        <small class='text-muted'>默认密码: admin</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
EOF

    # 设置权限
    chown -R $WEB_USER:$WEB_GROUP "$install_dir"
    chmod -R 755 "$install_dir"
    chmod -R 644 "$install_dir"/*.php
    chmod -R 644 "$install_dir/admin"/*.php
    
    print_success "应用部署完成"
}

# 快速数据库初始化
quick_db_init() {
    print_info "初始化数据库..."
    
    # 获取临时MySQL密码
    sleep 5
    local temp_pass=$(grep 'temporary password' /var/log/mysqld.log 2>/dev/null | tail -1 | awk '{print $NF}' || echo "")
    
    # 设置数据库
    mysql -uroot << EOF 2>/dev/null || {
        print_warning "设置MySQL密码..."
        mysql --connect-expired-password -uroot -p"$temp_pass" << 'INNER_EOF'
ALTER USER 'root'@'localhost' IDENTIFIED BY 'root123';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';
FLUSH PRIVILEGES;
INNER_EOF
    }
    
    # 创建应用数据库
    local db_pass=$(grep "define('DB_PASS'" /var/www/yizi-ai/config.php | grep -o "'.*'" | tr -d "'")
    mysql -uroot -proot123 << EOF
CREATE DATABASE IF NOT EXISTS api CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS 'api'@'localhost' IDENTIFIED BY '$db_pass';
GRANT ALL ON api.* TO 'api'@'localhost';
FLUSH PRIVILEGES;
EOF

    # 导入基本表结构
    mysql -uapi -p"$db_pass" api << 'EOF'
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    email VARCHAR(100),
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    message TEXT,
    response TEXT,
    model_used VARCHAR(100),
    tokens_used INT,
    response_time DECIMAL(10,3),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255),
    api_url VARCHAR(255) DEFAULT 'https://api.spanstar.cn',
    prompt TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO settings (api_key, prompt) VALUES 
('', '你是一个有用的AI助手，请用友好、专业的方式回答用户的问题。');
EOF

    print_success "数据库初始化完成"
}

# 配置Web服务器
quick_web_config() {
    print_info "配置Web服务器..."
    
    # 创建虚拟主机配置
    cat > /etc/httpd/conf.d/yizi-ai.conf << 'EOF'
<VirtualHost *:80>
    DocumentRoot /var/www/yizi-ai
    ServerName localhost
    
    <Directory /var/www/yizi-ai>
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory /var/www/yizi-ai/logs>
        Deny from all
    </Directory>
</VirtualHost>
EOF

    # 重启服务
    systemctl restart httpd
    systemctl restart mysqld
    
    # 配置防火墙
    if command -v firewall-cmd &> /dev/null; then
        systemctl enable firewalld
        systemctl start firewalld
        firewall-cmd --permanent --add-service=http 2>/dev/null || true
        firewall-cmd --reload
    fi
    
    print_success "Web服务器配置完成"
}

# 最终检查
final_check() {
    print_info "执行最终检查..."
    
    local errors=0
    
    # 检查服务状态
    systemctl is-active --quiet httpd || { print_error "Apache未运行"; ((errors++)); }
    systemctl is-active --quiet mysqld || { print_error "MySQL未运行"; ((errors++)); }
    
    # 检查网站访问
    if curl -f -s http://localhost >/dev/null 2>&1; then
        print_success "网站可正常访问"
    else
        print_warning "网站可能无法从外部访问"
    fi
    
    if [[ $errors -eq 0 ]]; then
        print_success "安装完成！"
    else
        print_error "发现 $errors 个问题，请检查配置"
        exit 1
    fi
}

# 显示安装结果
show_result() {
    echo
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🎉 YiZi AI V3.0 安装成功！                     ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo
    echo -e "${YELLOW}访问信息:${NC}"
    echo -e "  🌐 网站地址: ${BLUE}http://localhost${NC}"
    echo -e "  🔑 管理后台: ${BLUE}http://localhost/admin/login.php${NC}"
    echo -e "  📧 默认用户: ${GREEN}admin${NC}"
    echo -e "  🔐 默认密码: ${GREEN}admin${NC}"
    echo
    echo -e "${YELLOW}重要提醒:${NC}"
    echo "  1. 请立即修改默认管理员密码"
    echo "  2. 在设置中配置您的API密钥"
    echo "  3. 测试聊天功能"
    echo
    echo -e "${YELLOW}服务管理:${NC}"
    echo "  重启Apache: systemctl restart httpd"
    echo "  重启MySQL:  systemctl restart mysqld"
    echo "  查看日志:   tail -f /var/log/httpd/error_log"
    echo
}

# 主程序
main() {
    echo -e "${BLUE}"
    cat << 'EOF'
╔════════════════════════════════════════════════════════════╗
║                   YiZi AI V3.0 快速安装                    ║
║                      一键部署版本                           ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
    
    # 执行安装步骤
    quick_system_check
    one_click_install
    quick_deploy
    quick_db_init
    quick_web_config
    final_check
    show_result
}

# 运行主程序
main "$@" 2>&1 | tee /var/log/yizi-ai-quick-install.log