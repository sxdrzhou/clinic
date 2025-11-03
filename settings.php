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
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// 获取当前用户的用户组信息
$servername = $config['db']['servername'];
$username = $config['db']['username'];
$password = $config['db']['password'];
$dbname = $config['db']['dbname'];
$charset = $config['db']['charset'];

$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset($charset);

// 获取用户组信息
$stmt = $conn->prepare("SELECT user_group FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$_SESSION['user_group'] = $admin['user_group'];

$conn->close();

// 连接数据库
$servername = $config['db']['servername'];
$username = $config['db']['username'];
$password = $config['db']['password'];
$dbname = $config['db']['dbname'];
$charset = $config['db']['charset'];

$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset($charset);

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

// 处理表单提交
$error = '';
$success = '';

// 更改密码和昵称表单提交
if (isset($_POST['update_profile'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $newNickname = $_POST['new_nickname'];

    // 验证当前密码
    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if (!password_verify($currentPassword, $admin['password'])) {
        $error = '当前密码不正确';
    } else if (!empty($newPassword) && $newPassword != $confirmPassword) {
        $error = '两次输入的新密码不一致';
    } else {
        // 更新密码和昵称
        $updateData = array();

        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateData[] = "password = '$hashedPassword'";
        }

        if (!empty($newNickname)) {
            $updateData[] = "nickname = '" . $conn->real_escape_string($newNickname) . "'";
            // 更新会话中的昵称
            $_SESSION['nickname'] = $newNickname;
        }

        if (!empty($updateData)) {
            $updateSql = "UPDATE admins SET " . implode(", ", $updateData) . " WHERE id = " . $_SESSION['admin_id'];
            if ($conn->query($updateSql) === TRUE) {
                $success = '个人信息更新成功';
            } else {
                $error = '更新失败: ' . $conn->error;
            }
        } else {
            $success = '没有进行任何修改';
        }
    }
}

// 添加用户表单提交
if (isset($_POST['add_user'])) {
    $newUsername = $_POST['new_username'];
    $newUserPassword = $_POST['new_user_password'];
    $confirmUserPassword = $_POST['confirm_user_password'];
    $newUserNickname = $_POST['new_user_nickname'];
    $userGroup = $_POST['user_group'];

    // 验证表单
    if (empty($newUsername) || empty($newUserPassword) || empty($confirmUserPassword) || empty($newUserNickname)) {
        $error = '所有字段都是必填的';
    } else if ($newUserPassword != $confirmUserPassword) {
        $error = '两次输入的密码不一致';
    } else {
        // 检查用户名是否已存在
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->bind_param("s", $newUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = '用户名已存在';
        } else {
            // 哈希密码
            $hashedPassword = password_hash($newUserPassword, PASSWORD_DEFAULT);

            // 插入新用户
            $insertSql = "INSERT INTO admins (username, password, nickname, user_group) VALUES ('" . $conn->real_escape_string($newUsername) . "', '$hashedPassword', '" . $conn->real_escape_string($newUserNickname) . "', '$userGroup')";

            if ($conn->query($insertSql) === TRUE) {
                $success = '用户添加成功';
                // 清空表单
                $_POST['new_username'] = '';
                $_POST['new_user_password'] = '';
                $_POST['confirm_user_password'] = '';
                $_POST['new_user_nickname'] = '';
            } else {
                $error = '添加用户失败: ' . $conn->error;
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - 患者信息查询系统</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
            margin-top: 70px; /* 增加上边距，避免被顶部固定栏遮挡 */
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        h2 {
            color: #444;
            border-bottom: 2px solid #f2f2f2;
            padding-bottom: 10px;
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
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            padding: 8px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
        }
        .form-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        /* 顶部固定栏样式 */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #333;
            color: white;
            height: 50px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .top-bar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 18px;
            font-weight: bold;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        .settings-link {
            color: white;
            text-decoration: none;
        }
        .settings-link:hover {
            text-decoration: underline;
        }
        .back-link {
            color: #2196F3;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- 顶部固定栏 -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="logo">患者信息查询系统</div>
            <div class="user-info">
                <span id="username"><?php echo htmlspecialchars($_SESSION['nickname']); ?></span> | 
                <a href="patient_query.php" class="settings-link">返回查询</a> | 
                <a href="?logout=true" class="logout-btn">登出</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>系统设置</h1>

        <a href="patient_query.php" class="back-link">← 返回患者查询</a>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- 第一部分：更改密码和昵称 -->
        <div class="form-container">
            <h2>个人信息设置</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="current_password">当前密码</label>
                    <input type="password" id="current_password" name="current_password" required placeholder="请输入当前密码">
                </div>
                <div class="form-group">
                    <label for="new_password">新密码 (选填)</label>
                    <input type="password" id="new_password" name="new_password" placeholder="不修改请留空">
                </div>
                <div class="form-group">
                    <label for="confirm_password">确认新密码 (选填)</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="不修改请留空">
                </div>
                <div class="form-group">
                    <label for="new_nickname">新昵称</label>
                    <input type="text" id="new_nickname" name="new_nickname" required placeholder="请输入新昵称">
                </div>
                <button type="submit" name="update_profile" class="btn">保存修改</button>
            </form>
        </div>

        <?php if ($_SESSION['user_group'] == 'admin'): ?>
        <!-- 第二部分：添加用户 (仅管理员可见) -->
        <div class="form-container">
            <h2>添加用户</h2>
            <form method="post" action="">
                <div class="form-group">
                    <label for="new_username">用户名</label>
                    <input type="text" id="new_username" name="new_username" required placeholder="请输入用户名">
                </div>
                <div class="form-group">
                    <label for="new_user_password">密码</label>
                    <input type="password" id="new_user_password" name="new_user_password" required placeholder="请输入密码">
                </div>
                <div class="form-group">
                    <label for="confirm_user_password">确认密码</label>
                    <input type="password" id="confirm_user_password" name="confirm_user_password" required placeholder="请再次输入密码">
                </div>
                <div class="form-group">
                    <label for="new_user_nickname">昵称</label>
                    <input type="text" id="new_user_nickname" name="new_user_nickname" required placeholder="请输入昵称">
                </div>
                <div class="form-group">
                    <label for="user_group">用户组</label>
                    <select id="user_group" name="user_group" required>
                        <option value="admin">管理员组</option>
                        <option value="user">普通用户组</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn">添加用户</button>
            </form>
        </div>
        <?php else: ?>
        <!-- 普通用户看不到添加用户表单 -->
        <div class="form-container">
            <h2>添加用户</h2>
            <p>您没有权限添加新用户。</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>