<?php
// 引入配置文件
require 'config.php';

// 检查是否已初始化
if ($config['app']['initialized'] != 1) {
    // 未初始化，重定向到初始化页面
    header('Location: init.php');
    exit();
} else {
    // 已初始化，重定向到登录页面
    header('Location: login.php');
    exit();
}
?>