<?php
require('common.php');

$page_config = isset($config['page_config']) ? $config['page_config'] : [];
$sku_id = isset($config['sku_id']) ? $config['sku_id'] : [];
$domain = isset($config['domain']) ? $config['domain'] : [];
$invitation_code_buy_link = isset($config['invitation_code_buy_link']) ? $config['invitation_code_buy_link'] : '';

if (empty($_POST)) {
    require('office.html');
    exit();
}

// 验证邀请码逻辑
if ($is_invitation_code) {
    if (empty($_POST['invitation_code'])) {
        response(1, '请输入邀请码');
    }

    $conn = mysql_conn();
    $code = filter_input(INPUT_POST, 'invitation_code', FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("SELECT * FROM invitation_code WHERE `code` = ?");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $invitation_code = $result->fetch_assoc();

    if (empty($invitation_code)) {
        response(1, '邀请码不存在');
    }
    if ($invitation_code['status'] != 0) {
        response(1, '邀请码已被使用');
    }
}

// 构建用户请求数据
$request = [
    'display_name' => filter_input(INPUT_POST, 'display_name', FILTER_SANITIZE_STRING), // 显示名称
    'user_name' => filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING),      // 邮箱用户名
];

// 生成随机密码
$password = get_rand_string();

// 获取 Microsoft Graph API 的访问令牌
$token = get_ms_token($tenant_id, $client_id, $client_secret);
if (empty($token)) {
    response(1, '获取token失败,请检查参数配置是否正确');
}

// 创建用户
$email = create_user(
    $request,
    $token,
    filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_STRING),
    filter_input(INPUT_POST, 'sku_id', FILTER_SANITIZE_STRING),
    $password
);

// 更新邀请码状态
if ($is_invitation_code) {
    $stmt = $conn->prepare("UPDATE `invitation_code` SET `update_time` = ?, `status` = 1, `email` = ? WHERE `code` = ?");
    $update_time = time();
    $stmt->bind_param('iss', $update_time, $email, $code);
    $stmt->execute();
}

// 返回成功响应
response(0, '申请账号成功', ['email' => $email, 'password' => $password]);