<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>患者信息查询系统 - 登录</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .login-btn {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-btn:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>患者信息查询系统</h2>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="请输入密码">
            </div>
            <button type="submit" class="login-btn">登录</button>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">用户名或密码错误</div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
<?php
// 引入配置文件
require 'config.php';

// 检查是否已初始化
if ($config['app']['initialized'] != 1) {
    // 未初始化，重定向到初始化页面
    header('Location: init.php');
    exit();
}

// 启动会话
session_start();

// 检查是否已登录
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: patient_query.php');
    exit;
}

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // 连接到数据库
    $conn = new mysqli($config['db']['servername'], $config['db']['username'], $config['db']['password'], $config['db']['dbname']);

    // 检查连接
    if ($conn->connect_error) {
        die('数据库连接失败: ' . $conn->connect_error);
    }

    // 确保admins表有user_group字段
    $checkColumnSql = "SHOW COLUMNS FROM admins LIKE 'user_group'";
    $columnResult = $conn->query($checkColumnSql);
    
    if ($columnResult->num_rows == 0) {
        // 添加user_group字段
        $addColumnSql = "ALTER TABLE admins ADD COLUMN user_group ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER nickname";
        $conn->query($addColumnSql);
        
        // 更新现有管理员为admin组
        $updateAdminSql = "UPDATE admins SET user_group = 'admin'";
        $conn->query($updateAdminSql);
    }
    
    // 准备查询
    $stmt = $conn->prepare("SELECT id, username, password, nickname FROM admins WHERE username = ?");
    
    // Check if prepared statement failed
    if ($stmt === false) {
        die('准备语句失败: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // 获取管理员信息
        $admin = $result->fetch_assoc();

        // 验证密码
        if (password_verify($password, $admin['password'])) {
            // 登录成功，设置会话
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $admin['username'];
            $_SESSION['nickname'] = $admin['nickname'];
            $_SESSION['admin_id'] = $admin['id'];

            // 重定向到查询页面
            header('Location: patient_query.php');
            exit;
        }
    }

    // 登录失败，重定向回登录页面并显示错误
    header('Location: login.php?error=1');
    exit;
}