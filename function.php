<?php

// 生成随机字符串
function get_rand_string($length = 10) {
    $str = '';
    $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

// 获取 Microsoft Graph API 的访问令牌
function get_ms_token($tenant_id, $client_id, $client_secret) {
    $url = 'https://login.microsoftonline.com/' . $tenant_id . '/oauth2/v2.0/token';
    $scope = 'https://graph.microsoft.com/.default';
    $data = [
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'scope' => $scope
    ];
    $res = curl_post($url, $data);
    $data = json_decode($res, true);
    return $data['access_token'] ?? '';
}

// 创建用户
function create_user($request, $token, $domain, $sku_id, $password) {
    $url = 'https://graph.microsoft.com/v1.0/users';
    $user_email = $request['user_name'] . '@' . $domain;
    $data = [
        "accountEnabled" => true,
        "displayName" => $request['display_name'],
        "mailNickname" => $request['user_name'],
        "passwordPolicies" => "DisablePasswordExpiration, DisableStrongPassword",
        "passwordProfile" => [
            "password" => $password,
            "forceChangePasswordNextSignIn" => true
        ],
        "userPrincipalName" => $user_email,
        "usageLocation" => "CN"
    ];
    $result = json_decode(curl_post_json($url, json_encode($data), $token), true);
    if (!empty($result['error'])) {
        if ($result['error']['message'] === 'Another object with the same value for property userPrincipalName already exists.') {
            response(1, '前缀被占用,请修改后重试');
        }
        response(1, $result['error']['message']);
    }
    addsubscribe($user_email, $token, $sku_id);
    return $user_email;
}

// 为用户分配订阅
function addsubscribe($user_email, $token, $sku_id) {
    $url = 'https://graph.microsoft.com/v1.0/users/' . $user_email . '/assignLicense';
    $data = [
        'addLicenses' => [
            [
                'disabledPlans' => [],
                'skuId' => $sku_id
            ],
        ],
        'removeLicenses' => []
    ];
    curl_post_json($url, json_encode($data), $token);
}

// 通用的 POST 请求
function curl_post($url, $post) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    $res = curl_exec($curl);
    curl_close($curl);
    return $res;
}

// 通用的 POST JSON 请求
function curl_post_json($url, $postdata, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Content-Type:application/json;', 'Authorization:Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postdata,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// 响应函数
function response($code, $msg, $data = [], $count = false) {
    $json = [
        'code' => $code,
        'msg' => $msg,
    ];
    if (!empty($data)) {
        $json['data'] = $data;
    }
    if ($count !== false) {
        $json['count'] = $count;
    }
    header('Content-Type: application/json');
    echo json_encode($json);
    exit();
}

// 获取随机数字
function get_rand_number($length = 8) {
    $str = '';
    $strPol = '0123456789';
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

// 检查账户状态
function account_status($user_email, $token) {
    $url = "https://graph.microsoft.com/v1.0/users/" . $user_email;
    $data = curl_get($url, $token);
    return $data['accountEnabled'] ?? false;
}

// 启用账户
function account_enable($user_email, $token) {
    $url = "https://graph.microsoft.com/v1.0/users/" . $user_email;
    $data = json_encode(['accountEnabled' => true]);
    return curl_patch($url, $data, $token);
}

// 禁用账户
function account_disable($user_email, $token) {
    $url = "https://graph.microsoft.com/v1.0/users/" . $user_email;
    $data = json_encode(['accountEnabled' => false]);
    return curl_patch($url, $data, $token);
}

// 删除账户
function account_delete($user_email, $token) {
    $url = "https://graph.microsoft.com/v1.0/users/" . $user_email;
    return curl_delete($url, $token);
}

// 通用的 GET 请求
function curl_get($url, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization:Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

// 通用的 PATCH 请求
function curl_patch($url, $data, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Content-Type:application/json;', 'Authorization:Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

// 通用的 DELETE 请求
function curl_delete($url, $token) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization:Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code === 204;
}