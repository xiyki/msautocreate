<?php
// 启用错误报告以便调试（生产环境中可关闭）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 启动会话
session_start();

// 引入必要的功能文件
require('function.php');

// 加载配置文件
$config = require('config.php');

// 使用 extract 提取配置变量
$db = $config['db'];
$admin = $config['admin'];
$tenant_id = $config['tenant_id'];
$client_id = $config['client_id'];
$client_secret = $config['client_secret'];
$is_invitation_code = $config['is_invitation_code'];

// 通用函数：检查用户是否已登录
function check_login() {
    global $admin;
    return isset($_SESSION['username']) && $_SESSION['username'] === $admin['username'];
}

// 通用函数：创建数据库连接
function mysql_conn() {
    global $db;
    $conn = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}