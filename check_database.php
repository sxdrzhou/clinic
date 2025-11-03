<?php
// 引入配置文件
require 'config.php';

// 连接到数据库
$conn = new mysqli($config['db']['servername'], $config['db']['username'], $config['db']['password'], $config['db']['dbname']);

// 检查连接
if ($conn->connect_error) {
    die('数据库连接失败: ' . $conn->connect_error);
}

// 检查admins表是否存在
$check_table_sql = "SHOW TABLES LIKE 'admins'";
$result = $conn->query($check_table_sql);

if ($result->num_rows > 0) {
    echo "admins表存在\n";
    
    // 检查表结构
    $describe_table_sql = "DESCRIBE admins";
    $table_structure = $conn->query($describe_table_sql);
    
    echo "表结构:\n";
    while ($row = $table_structure->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "错误: admins表不存在！\n";
    
    // 显示所有可用的表
    $show_tables_sql = "SHOW TABLES";
    $all_tables = $conn->query($show_tables_sql);
    
    echo "数据库中存在的表:\n";
    while ($row = $all_tables->fetch_row()) {
        echo $row[0] . "\n";
    }
}

$conn->close();
?>