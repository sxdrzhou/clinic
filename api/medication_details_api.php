<?php
// 药品详情API

// 引入配置文件
require_once '../config.php';

// 确保这是一个GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '只支持GET请求']);
    exit;
}

// 确保提供了visitId参数
if (!isset($_GET['visitId']) || empty($_GET['visitId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '就诊ID不能为空']);
    exit;
}

// 记录请求日志
$log = "[" . date('Y-m-d H:i:s') . "] 请求药品详情: " . $_GET['visitId'] . "\n";
file_put_contents('../medication_query.log', $log, FILE_APPEND);

// 确保输出是纯JSON，没有任何HTML标签
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// 使用配置文件中的数据库连接信息
$servername = $config['db']['servername'] ?? 'localhost';
$username = $config['db']['username'] ?? 'root';
$password = $config['db']['password'] ?? '';
$dbname = $config['db']['dbname'] ?? 'test';
$charset = $config['db']['charset'] ?? 'utf8mb4';

try {
    // 验证配置
    if (empty($servername) || empty($username) || empty($dbname)) {
        throw new Exception('数据库配置不完整');
    }

    // 创建PDO连接
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);

    $visitId = $_GET['visitId'];

    // 从zs_xfsp表中查询药品信息，包含单位字段
    $sql = "SELECT `名称`, `申请数量`, `单位`, `中药副数` FROM `zs_xfsp` WHERE `流水id` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$visitId]);
    $medications = $stmt->fetchAll();

    // 确保所有值都是字符串类型
    foreach ($medications as &$medication) {
        $medication['名称'] = (string)$medication['名称'];
        $medication['申请数量'] = (string)$medication['申请数量'];
        $medication['中药副数'] = (string)$medication['中药副数'];
    }

    // 返回JSON格式的数据
    $response = [
        'success' => !empty($medications),
        'medications' => $medications
    ];

    if (empty($medications)) {
        $response['error'] = '未找到药品信息';
        $log .= "未找到药品信息\n";
    } else {
        $log .= "找到 " . count($medications) . " 条药品信息\n";
    }

    // 记录响应日志
    file_put_contents('../medication_query.log', $log, FILE_APPEND);

    // 确保JSON编码正确
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
    if ($jsonResponse === false) {
        throw new Exception('JSON编码失败: ' . json_last_error_msg());
    }

    echo $jsonResponse;
} catch (Exception $e) {
    // 记录错误日志
    $log .= "错误: " . $e->getMessage() . "\n";
    file_put_contents('../medication_query.log', $log, FILE_APPEND);

    // 返回错误信息
    http_response_code(500);
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage()
    ];

    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
}