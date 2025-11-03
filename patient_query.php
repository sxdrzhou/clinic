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

// 处理登出请求
if (isset($_GET['logout'])) {
    // 销毁会话
    session_destroy();
    // 重定向到登录页面
    header('Location: login.php');
    exit;
}

// 检查是否已登录
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>患者信息查询系统</title>
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
        .search-form {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
            align-items: flex-end;
        }
        .form-group {
            flex: 1;
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
        .search-btn {
            padding: 8px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: 36px;
        }
        .search-btn:hover {
            background-color: #45a049;
        }
        .reset-btn {
            padding: 8px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: 36px;
        }
        .reset-btn:hover {
            background-color: #d32f2f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .select-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
        .select-btn:hover {
            background-color: #0b7dda;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
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
        /* 响应式设计 - 移动端优化 */
        @media (max-width: 768px) {
            /* 药品详情表格样式 - 移动端 */
            .medication-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                min-width: 100%;
            }
            
            /* 在小屏幕上单列显示药品 */
            .medication-table thead {
                display: none;
            }
            
            .medication-table tbody {
                display: block;
            }
            
            .medication-table tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .medication-table td {
                display: block;
                border: none;
                border-bottom: 1px solid #eee;
                padding: 10px;
                text-align: left;
                position: relative;
            }
            
            /* 为单元格添加标签 */
            .medication-table td:nth-child(1):before,
            .medication-table td:nth-child(2):before,
            .medication-table td:nth-child(3):before,
            .medication-table td:nth-child(4):before {
                position: absolute;
                left: 10px;
                font-weight: bold;
            }
            
            .medication-table td:nth-child(1):before {
                content: '药品名称 1:';
            }
            
            .medication-table td:nth-child(2):before {
                content: '剂量 1:';
            }
            
            .medication-table td:nth-child(3):before {
                content: '药品名称 2:';
            }
            
            .medication-table td:nth-child(4):before {
                content: '剂量 2:';
            }
            
            /* 副数显示优化 */
            .total-doses {
                text-align: right;
                margin-top: 10px;
                font-weight: bold;
            }
            .container {
                padding: 10px;
                margin: 10px;
            }
            
            .search-form {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .form-group {
                width: 100%;
            }
            
            .search-btn, .reset-btn {
                width: 100%;
                margin-top: 10px;
            }
            
            /* 顶部导航栏优化 */
            .top-bar-content {
                padding: 0 10px;
            }
            
            .logo {
                font-size: 16px;
            }
            
            .user-info {
                font-size: 14px;
                gap: 5px;
            }
            
            /* 移动端表格样式优化 */
            .table-container {
                margin-top: 20px;
            }
            
            table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 10px;
            }
            
            /* 隐藏表头行和表头单元格 */
            tr:first-child,
            th {
                display: none;
            }
            
            /* 隐藏序号和就诊号列 */
            td:nth-child(1), td:nth-child(2) {
                display: none;
            }

            /* 确保姓名列正确显示 */
            td:nth-child(3) {
                display: table-cell;
                width: 20%;
            }
            
            /* 设置行样式为卡片式 */
            tr {
                display: block;
                background-color: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                margin-bottom: 15px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            
            /* 设置单元格样式 */
            td {
                display: block;
                border: none;
                padding: 5px 0;
            }
            
            /* 第一行显示：姓名、性别、就诊时年龄、现在年龄 */
            td:nth-child(3), td:nth-child(4), td:nth-child(5), td:nth-child(6) {
                display: inline-block;
                width: 49%;
                margin-bottom: 5px;
                vertical-align: top;
            }

            /* 为年龄添加前缀文本 */
            td:nth-child(5):before {
                content: '就诊时：';
                font-weight: bold;
            }

            td:nth-child(6):before {
                content: '现在：';
                font-weight: bold;
            }
            
            /* 第二行显示：电话号码和选定按钮 */
            td:nth-child(7) {
                width: 100%;
                margin-bottom: 10px;
            }
            
            td:nth-child(8) {
                width: 100%;
            }
            
            .select-btn {
                width: 100%;
                padding: 8px;
            }
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
                <span id="current-time"></span> | 
                <a href="settings.php" class="settings-link">设置</a> | 
                <a href="?logout=true" class="logout-btn">登出</a>
            </div>
        </div>
    </div>
    <div class="container">
        <h1>患者信息查询系统</h1>
        
        <!-- 查询表单部分 -->
        <form class="search-form" method="post" action="">
            <div class="form-group">
                <label for="pinyin">患者姓名拼音</label>
                <input type="text" id="pinyin" name="pinyin" placeholder="请输入患者姓名拼音">
            </div>
            <div class="form-group">
                <label for="name">患者汉字名</label>
                <input type="text" id="name" name="name" placeholder="请输入患者汉字名">
            </div>
            <button type="submit" class="search-btn">查询</button>
            <button type="button" class="reset-btn" onclick="resetForm()">重置</button>
        </form>
        
        <!-- 患者信息显示部分 -->
        <div class="patient-info">
            <div class="table-container">
            <?php
            // 药品详情功能已迁移到独立API文件: api/medication_details_api.php
            // 此处代码已删除以保持文件简洁

            // 处理AJAX请求获取患者详情
            if (isset($_GET['action']) && $_GET['action'] == 'get_patient_details' && isset($_GET['patientId'])) {
                // 确保输出是纯JSON，没有任何HTML标签
                ob_clean();
                header('Content-Type: application/json; charset=utf-8');

                // 使用配置文件中的数据库连接信息
                $servername = $config['db']['servername'];
                $username = $config['db']['username'];
                $password = $config['db']['password'];
                $dbname = $config['db']['dbname'];
                $charset = $config['db']['charset'];

                // 记录请求日志
                $log = "[" . date('Y-m-d H:i:s') . "] 请求患者详情: " . $_GET['patientId'] . "\n";
                file_put_contents('patient_query.log', $log, FILE_APPEND);

                try {
                    // 创建连接
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // 检查连接
                    if ($conn->connect_error) {
                        throw new Exception('数据库连接失败: ' . $conn->connect_error);
                    }

                    // 设置字符集
                    if (!$conn->set_charset($charset)) {
                        throw new Exception('设置字符集失败: ' . $conn->error);
                    }

                    $patientId = $conn->real_escape_string($_GET['patientId']);

                    // 查询患者基本信息
                    $patientSql = "SELECT * FROM zs_hy WHERE 就诊号 = '$patientId'";
                    $patientResult = $conn->query($patientSql);

                    if (!$patientResult) {
                        throw new Exception('患者信息查询失败: ' . $conn->error);
                    }

                    $patient = $patientResult->fetch_assoc();

                    if (!$patient) {
                        throw new Exception('未找到患者信息，就诊号: ' . $patientId);
                    }

                    // 计算现在年龄
                    if (!empty($patient["出生日期"])) {
                        try {
                            $birthDate = new DateTime($patient["出生日期"]);
                            $today = new DateTime("today");
                            $age = $birthDate->diff($today);
                            $patient["currentAge"] = $age->y . "岁";
                        } catch (Exception $e) {
                            $patient["currentAge"] = "出生日期格式错误";
                            file_put_contents('patient_query.log', "[" . date('Y-m-d H:i:s') . "] 年龄计算错误: " . $e->getMessage() . "\n", FILE_APPEND);
                        }
                    } else {
                        $patient["currentAge"] = "未知";
                    }

                    // 查询就诊记录（按流水ID分组，取最新日期）
                    $visitSql = "SELECT 流水ID, MAX(日期) as 日期 FROM zs_bllist WHERE 就诊号 = '$patientId' GROUP BY 流水ID ORDER BY 日期 DESC";
                    $visitResult = $conn->query($visitSql);

                    if (!$visitResult) {
                        throw new Exception('就诊记录查询失败: ' . $conn->error);
                    }

                    $visits = array();
                    while($visitRow = $visitResult->fetch_assoc()) {
                        $visits[] = $visitRow;
                    }

                    // 返回JSON数据
                    echo json_encode(array(
                        'success' => true,
                        'patient' => $patient,
                        'visits' => $visits
                    ));
                } catch (Exception $e) {
                    // 记录错误日志
                    $error = $e->getMessage();
                    file_put_contents('patient_query.log', "[" . date('Y-m-d H:i:s') . "] " . $error . "\n", FILE_APPEND);

                    // 返回错误信息
                    echo json_encode(array('error' => $error));
                } finally {
                    // 确保关闭连接
                    if (isset($conn)) {
                        $conn->close();
                    }
                    exit;
                }
            }

            // 默认显示提示信息
            if (!isset($_POST['pinyin']) && !isset($_POST['name'])) {
                echo '<div class="no-results">请输入查询条件并点击查询按钮</div>';
            }
            // 使用配置文件中的数据库连接信息
            $servername = $config['db']['servername'];
            $username = $config['db']['username'];
            $password = $config['db']['password'];
            $dbname = $config['db']['dbname'];
            $charset = $config['db']['charset'];

            // 创建连接
            $conn = new mysqli($servername, $username, $password, $dbname);
            
            // 检查连接
            if ($conn->connect_error) {
                die("连接失败: " . $conn->connect_error);
            }
            
            // 设置字符集
            $conn->set_charset($charset);
            
            // 获取查询参数
            $pinyin = isset($_POST['pinyin']) ? trim($_POST['pinyin']) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            
            // 只有在提交表单且有查询参数时才执行查询
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($pinyin || $name)) {
                // 构建查询条件
                $where = array();
                if ($pinyin) {
                    $where[] = "姓名拼音码 LIKE '%" . $conn->real_escape_string($pinyin) . "%'";
                }
                if ($name) {
                    $where[] = "姓名 LIKE '%" . $conn->real_escape_string($name) . "%'";
                }
                
                $whereClause = implode(" OR ", $where);
                
                // 查询数据库
                $sql = "SELECT * FROM zs_hy WHERE $whereClause";
                $result = $conn->query($sql);
                
                // 检查是否有结果
                if ($result->num_rows > 0) {
                    // 显示查询结果表格
                    echo "<table>";
                    echo "<tr>
                        <th>序号</th>
                        <th>就诊号</th>
                        <th>姓名</th>
                        <th>性别</th>
                        <th>就诊时年龄</th>
                        <th>现在年龄</th>
                        <th>电话号</th>
                        <th>操作</th>
                    </tr>";
                    
                    $index = 1;
                    // 遍历结果集
                    while($row = $result->fetch_assoc()) {
                        // 计算现在年龄
                        $currentAge = "未知";
                        if (!empty($row["出生日期"])) {
                            $birthDate = new DateTime($row["出生日期"]);
                            $today = new DateTime("today");
                            $age = $birthDate->diff($today);
                            $currentAge = $age->y . "岁";
                        }
                        
                        echo "<tr>";
                        echo "<td>". $index ."</td>";
                        echo "<td>". htmlspecialchars($row["就诊号"]) ."</td>";
                        echo "<td>". htmlspecialchars($row["姓名"]) ."</td>";
                        echo "<td>". htmlspecialchars($row["性别"]) ."</td>";
                        echo "<td>". htmlspecialchars($row["年龄"]) ."</td>";
                        echo "<td>". $currentAge ."</td>";
                        echo "<td>". htmlspecialchars($row["电话"]) ."</td>";
                        echo "<td><button class='select-btn' onclick='selectPatient(\"". htmlspecialchars($row["就诊号"])."\")'>选定</button></td>";
                        echo "</tr>";
                        $index++;
                    }
                    echo "</table>";
                } else {
                    echo "<div class='no-results'>未找到匹配的患者信息</div>";
                }
            }
            
            // 关闭连接
            $conn->close();
            ?>
        </div>
    </div>
    
    <!-- 患者详情模态框 -->
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2>患者详情</h2>
            <div id="patientInfo"></div>
            <h3>就诊记录</h3>
            <div id="visitRecords"></div>
        </div>
    </div>

    <style>
        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover, .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .patient-details {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        .visit-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        /* 移动端就诊记录表格优化 */
        @media (max-width: 768px) {
            .visit-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                min-width: 100%;
            }

            .visit-table thead {
                display: none;
            }

            .visit-table tbody {
                display: block;
            }

            .visit-table tr {
                display: block;
                margin-bottom: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                padding: 5px;
            }

            /* 确保所有单元格在一行显示 */
            .visit-table td {
                display: inline-block;
                border: none;
                padding: 8px 5px;
                vertical-align: middle;
                box-sizing: border-box;
            }

            /* 调整列宽比例 */
            .visit-table td:nth-child(1) {
                width: 10%;
                margin-right: 1%;
                text-align: center;
            }

            .visit-table td:nth-child(2) {
                width: 50%;
                margin-right: 1%;
                text-align: left;
            }

            .visit-table td:nth-child(3) {
                width: 40%;
                text-align: right;
            }

            /* 按钮自适应大小 */
            .visit-table .select-btn {
                width: 100%;
                padding: 8px 0;
                box-sizing: border-box;
            }
        }
        .visit-table th, .visit-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .visit-table th {
            background-color: #f2f2f2;
        }
    </style>

    <!-- 引入改进后的患者查询JavaScript -->
    <script src="patient_query_improved.js"></script>

    <script>
        // 重置表单函数
        function resetForm() {
            // 清除输入框的值
            document.getElementById('pinyin').value = '';
            document.getElementById('name').value = '';
            
            // 清除查询结果
            const patientInfoDiv = document.querySelector('.patient-info');
            patientInfoDiv.innerHTML = '<div class="no-results">请输入查询条件并点击查询按钮</div>';
        }

        // 实时更新时间函数
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            document.getElementById('current-time').textContent = timeString;
        }

        // 初始化时间并设置定时器
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>