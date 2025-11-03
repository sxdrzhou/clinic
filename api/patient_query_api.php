<?php
// 设置错误报告级别
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// 启用输出缓冲
ob_start();
ob_clean();

// 设置JSON响应头
header('Content-Type: application/json');

// 引入配置文件
require '../config.php';

// 检查应用是否已初始化
if (!isset($config['app']['initialized']) || $config['app']['initialized'] != 1) {
    echo json_encode(array('success' => false, 'error' => '应用未初始化'));
    exit();
}

// 启动会话并检查登录状态
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(array('success' => false, 'error' => '未登录'));
    exit();
}

// 初始化数据库连接变量
$conn = null;

// 主程序逻辑
try {
    // 连接数据库
    $conn = new mysqli($config['db']['servername'], $config['db']['username'], $config['db']['password'], $config['db']['dbname']);

    // 检查连接
    if ($conn->connect_error) {
        throw new Exception('数据库连接失败: ' . $conn->connect_error);
    }
    
    // 设置字符集以避免乱码
    $conn->set_charset('utf8mb4');

    // 根据请求参数执行不同的查询操作
    if (isset($_GET['patient_id'])) {
        // 根据患者ID查询单个患者信息及就诊记录
        try {
            $patient_id = $_GET['patient_id'];

            // 使用正确的患者表名 zs_hy 和主键列名 '就诊号'
            $patient_sql = "SELECT * FROM zs_hy WHERE 就诊号 = ?";
            $patient_stmt = $conn->prepare($patient_sql);
            if (!$patient_stmt) {
                throw new Exception('准备患者查询语句失败: ' . $conn->error);
            }
            
            // '就诊号' 是 varchar 类型，使用 's' 作为参数类型
            $patient_stmt->bind_param('s', $patient_id);
            if (!$patient_stmt->execute()) {
                throw new Exception('执行患者查询失败: ' . $patient_stmt->error);
            }
            
            $patient_result = $patient_stmt->get_result();
            if (!$patient_result) {
                throw new Exception('获取患者查询结果失败: ' . $patient_stmt->error);
            }

            if ($patient_result->num_rows > 0) {
                $patient = $patient_result->fetch_assoc();

                // 使用正确的就诊记录表名 zs_bllist
                // 假设就诊记录表中关联字段是 '就诊号'，日期字段是 '就诊日期'
                // 如果实际字段名不同，请修改为正确的字段名
                $visits_sql = "SELECT * FROM zs_bllist WHERE 就诊号 = ? ORDER BY 日期 DESC";
                $visits_stmt = $conn->prepare($visits_sql);
                if (!$visits_stmt) {
                    throw new Exception('准备就诊记录查询语句失败: ' . $conn->error);
                }
                
                $visits_stmt->bind_param('s', $patient_id);
                if (!$visits_stmt->execute()) {
                    throw new Exception('执行就诊记录查询失败: ' . $visits_stmt->error);
                }
                
                $visits_result = $visits_stmt->get_result();
                if (!$visits_result) {
                    throw new Exception('获取就诊记录查询结果失败: ' . $visits_stmt->error);
                }

                $visits = array();
                while ($visit = $visits_result->fetch_assoc()) {
                    $visits[] = $visit;
                }

                echo json_encode(array(
                    'success' => true,
                    'patient' => $patient,
                    'visits' => $visits
                ));
            } else {
                echo json_encode(array('success' => false, 'error' => '未找到患者信息'));
            }

            // 关闭语句
            $patient_stmt->close();
            if (isset($visits_stmt)) {
                $visits_stmt->close();
            }
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'error' => '获取患者信息失败: ' . $e->getMessage()));
        }
    } else {
        // 分页查询患者列表
        try {
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $offset = ($page - 1) * $limit;

            $search = '';
            $search_sql = '';
            $search_param = '';
            
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
                // 替换不存在的 'id' 列为 '就诊号'
            $search_sql = "WHERE name LIKE ? OR 就诊号 LIKE ?";
            $search_param = "%$search%";
            }

            // 查询总记录数 - 使用正确的患者表名 zs_hy
            $count_sql = "SELECT COUNT(*) as total FROM zs_hy" . ($search ? $search_sql : '');
            $count_stmt = $conn->prepare($count_sql);
            if (!$count_stmt) {
                throw new Exception('准备计数查询语句失败: ' . $conn->error);
            }

            if ($search) {
                if (!$count_stmt->bind_param('ss', $search_param, $search_param)) {
                    throw new Exception('绑定计数查询参数失败: ' . $count_stmt->error);
                }
            }

            if (!$count_stmt->execute()) {
                throw new Exception('执行计数查询失败: ' . $count_stmt->error);
            }
            
            $count_result = $count_stmt->get_result();
            if (!$count_result) {
                throw new Exception('获取计数查询结果失败: ' . $count_stmt->error);
            }
            
            $count = $count_result->fetch_assoc()['total'];

            // 查询患者列表 - 使用正确的患者表名 zs_hy
            // 将排序字段从不存在的 'id' 改为 '就诊号'
            $patients_sql = "SELECT * FROM zs_hy" . ($search ? $search_sql : '') . " ORDER BY 就诊号 LIMIT ? OFFSET ?";
            $patients_stmt = $conn->prepare($patients_sql);
            if (!$patients_stmt) {
                throw new Exception('准备患者列表查询语句失败: ' . $conn->error);
            }

            if ($search) {
                if (!$patients_stmt->bind_param('ssii', $search_param, $search_param, $limit, $offset)) {
                    throw new Exception('绑定患者列表查询参数失败: ' . $patients_stmt->error);
                }
            } else {
                if (!$patients_stmt->bind_param('ii', $limit, $offset)) {
                    throw new Exception('绑定患者列表查询参数失败: ' . $patients_stmt->error);
                }
            }

            if (!$patients_stmt->execute()) {
                throw new Exception('执行患者列表查询失败: ' . $patients_stmt->error);
            }
            
            $patients_result = $patients_stmt->get_result();
            if (!$patients_result) {
                throw new Exception('获取患者列表查询结果失败: ' . $patients_stmt->error);
            }

            $patients = array();
            while ($patient = $patients_result->fetch_assoc()) {
                $patients[] = $patient;
            }

            echo json_encode(array(
                'success' => true,
                'total' => $count,
                'patients' => $patients,
                'page' => $page,
                'limit' => $limit
            ));

            // 关闭语句
            $count_stmt->close();
            $patients_stmt->close();
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'error' => '获取患者列表失败: ' . $e->getMessage()));
        }
    }
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => $e->getMessage()));
} finally {
    // 确保数据库连接总是被关闭
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

// 刷新输出缓冲
ob_end_flush();
?>