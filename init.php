<?php
// 引入配置文件
require 'config.php';

$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 获取表单数据
    $db_servername = $_POST['db_servername'] ?? $config['db']['servername'];
    $db_username = $_POST['db_username'] ?? $config['db']['username'];
    $db_password = $_POST['db_password'] ?? $config['db']['password'];
    $db_name = $_POST['db_name'] ?? '';
    $admin_username = $_POST['admin_username'] ?? 'admin';
    $admin_password = $_POST['admin_password'] ?? 'admin123';
    $admin_nickname = $_POST['admin_nickname'] ?? '管理员';
    
    // 验证必填字段
    if (empty($db_name)) {
        $error = '数据库名不能为空';
    } else {
        // 更新配置
        $config['db']['servername'] = $db_servername;
        $config['db']['username'] = $db_username;
        $config['db']['password'] = $db_password;
        $config['db']['dbname'] = $db_name;
        $config['app']['initialized'] = 1;
        
        // 更新配置（不包含管理员信息）
        $config['db']['servername'] = $db_servername;
        $config['db']['username'] = $db_username;
        $config['db']['password'] = $db_password;
        $config['db']['dbname'] = $db_name;
        $config['app']['initialized'] = 1;
        
        // 将更新后的配置写回文件（不包含管理员信息）
        $configContent = "<?php

// 服务器配置信息
\$config = array(
    'db' => array(
        'servername' => '{$db_servername}',
        'username' => '{$db_username}',
        'password' => '{$db_password}',
        'dbname' => '{$db_name}',
        'charset' => 'utf8mb4'
    ),
    'app' => array(
        'name' => '{$config['app']['name']}',
        'version' => '{$config['app']['version']}',
        'initialized' => 1 // 已初始化
    )
);
?>";
        
        if (file_put_contents('config.php', $configContent)) {
            // 连接数据库
            $conn = new mysqli($db_servername, $db_username, $db_password, $db_name);
            
            // 检查连接
            if ($conn->connect_error) {
                $error = '数据库连接失败: ' . $conn->connect_error;
            } else {
                // 创建管理员表（如果不存在）
                $sql = "CREATE TABLE IF NOT EXISTS admins (
                    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(30) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    nickname VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                if ($conn->query($sql) === TRUE) {
                    // 哈希密码
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    
                    // 插入管理员信息
                    $sql = "INSERT INTO admins (username, password, nickname) VALUES ('{$admin_username}', '{$hashed_password}', '{$admin_nickname}')";
                    
                    if ($conn->query($sql) === TRUE) {
                        // 重定向到登录页面
                        header('Location: login.php');
                        exit();
                    } else {
                        $error = '保存管理员信息失败: ' . $conn->error;
                    }
                } else {
                    $error = '创建管理员表失败: ' . $conn->error;
                }
                
                $conn->close();
            }
        } else {
            $error = '保存配置文件失败，请检查文件权限';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统初始化</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        h2 {
            color: #444;
            margin-top: 30px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.2);
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fff0f0;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
            margin-top: 20px;
        }
        button:hover {
            background-color: #45a049;
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .default-note {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>系统初始化</h1>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <p>欢迎使用患者信息查询系统。请完成以下初始化配置：</p>
        <form method="post" action="init.php" id="initForm">
            <div class="form-section">
                <h2>数据库配置</h2>
                <div class="form-group">
                    <label for="db_servername">服务器地址</label>
                    <input type="text" id="db_servername" name="db_servername" value="<?php echo $config['db']['servername'] ?? 'localhost'; ?>" placeholder="输入服务器地址">
                </div>
                <div class="form-group">
                    <label for="db_username">用户名</label>
                    <input type="text" id="db_username" name="db_username" value="<?php echo $config['db']['username'] ?? 'root'; ?>" placeholder="输入数据库用户名">
                </div>
                <div class="form-group">
                    <label for="db_password">密码</label>
                    <input type="password" id="db_password" name="db_password" value="<?php echo $config['db']['password'] ?? ''; ?>" placeholder="输入数据库密码">
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名 <span style="color: red;">*</span></label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo $config['db']['dbname'] ?? 'sydb'; ?>" placeholder="输入数据库名">
                    <div class="default-note">必填项</div>
                </div>
            </div>
            <div class="form-section">
                <h2>管理员账号配置</h2>
                <div class="form-group">
                    <label for="admin_username">管理员账号</label>
                    <input type="text" id="admin_username" name="admin_username" value="admin" placeholder="输入管理员账号">
                </div>
                <div class="form-group">
                    <label for="admin_password">管理员密码</label>
                    <input type="password" id="admin_password" name="admin_password" value="admin123" placeholder="输入管理员密码">
                </div>
                <div class="form-group">
                    <label for="admin_nickname">昵称</label>
                    <input type="text" id="admin_nickname" name="admin_nickname" value="管理员" placeholder="输入管理员昵称">
                </div>
            </div>
            <button type="submit" id="submitBtn" disabled>保存配置</button>
        </form>
    </div>
    <script>
        // 获取表单元素
        const form = document.getElementById('initForm');
        const submitBtn = document.getElementById('submitBtn');
        const dbNameInput = document.getElementById('db_name');
        
        // 验证函数
        function validateForm() {
            // 检查数据库名是否填写
            if (dbNameInput.value.trim() !== '') {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // 监听数据库名输入事件
        dbNameInput.addEventListener('input', validateForm);
        
        // 初始验证
        validateForm();
    </script>
</body>
</html>