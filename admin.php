<?php
require('common.php');

function redirect($url) {
    header("Location: $url");
    exit();
}

if (empty($_GET['a'])) {
    if (check_login()) {
        redirect('./admin.php?a=invitation_code');
    } else {
        require('login.html');
        exit();
    }
}

switch ($_GET['a']) {
    case 'login':
		$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
		$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
	
		// 验证用户名和密码
		if ($username === $admin['username'] && password_verify($password, $admin['password'])) {
            $_SESSION['username'] = $admin['username'];
            redirect('./admin.php?a=invitation_code');
        } else {
            response(1, "登录失败");
        }
		break;

    case 'invitation_code':
		if (!check_login()) {
			require('login.html');
			exit();
		}
		require('invitation_code.html');
		exit();

    case 'invitation_code_list':
        if (!check_login()) {
            response(1, "登录已失效");
        }
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, intval($_GET['limit'] ?? 10));
        $status = intval($_GET['status'] ?? 0);
        $keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);

        $where = '1';
        $params = [];
        $types = '';

        if ($status === 1) {
            $where .= ' AND status=1';
        } elseif ($status === 2) {
            $where .= ' AND status=0';
        }
        if (!empty($keyword)) {
            $where .= " AND (`code` LIKE ? OR `email` LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $types .= 'ss';
        }

        // 分页参数
        $params[] = ($page - 1) * $limit;
        $params[] = $limit;
        $types .= 'ii';

        $sql = "SELECT * FROM invitation_code WHERE $where LIMIT ?, ?";
        $conn = mysql_conn();
        $stmt = $conn->prepare($sql);

        // 动态绑定参数
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
            $row['update_time'] = date('Y-m-d H:i:s', $row['update_time']);
            $data[] = $row;
        }

        // 统计总数
        $count_sql = "SELECT COUNT(*) AS count FROM invitation_code WHERE $where";
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($keyword)) {
            $count_stmt->bind_param('ss', "%$keyword%", "%$keyword%");
        }
        $count_stmt->execute();
        $count = $count_stmt->get_result()->fetch_assoc()['count'];

        response(0, "获取邀请码列表成功", $data, $count);
        break;

    case 'invitation_code_create':
        if (!check_login()) {
            response(1, "登录已失效");
        }
        $num = max(1, intval($_POST['num'] ?? 0));
        $conn = mysql_conn();

        $success = 0;
        $error = 0;
        for ($i = 0; $i < $num; $i++) {
            $code = get_rand_number($admin['invitation_code_num']);
            $time = time();
            $stmt = $conn->prepare("INSERT INTO invitation_code (code, create_time, update_time, status) VALUES (?, ?, ?, 0)");
            $stmt->bind_param('sii', $code, $time, $time);
            if ($stmt->execute()) {
                $success++;
            } else {
                $error++;
            }
        }

        $data = [
            'total' => $num,
            'success' => $success,
            'error' => $error,
        ];
        response(0, '生成成功', $data);
        break;

    case 'invitation_code_delete':
        if (!check_login()) {
            response(1, "登录已失效");
        }
        $token = get_ms_token($tenant_id, $client_id, $client_secret);
        $user_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $id = intval($_POST['id'] ?? 0);

        $conn = mysql_conn();
        $stmt = $conn->prepare("DELETE FROM invitation_code WHERE id = ?");
        $stmt->bind_param('i', $id);
        $resultsql = $stmt->execute();

        $resultaccount = $user_email ? account_delete($user_email, $token) : false;

        if ($resultsql && $resultaccount) {
            response(0, "邀请码删除成功,用户账户删除成功");
        } elseif ($resultsql) {
            response(0, "邀请码删除成功,无用户或无权限删除账户");
        } else {
            response(1, "删除失败");
        }
        break;

    case 'invitation_code_account_enable':
        if (!check_login()) {
            response(1, "登录已失效");
        }
        $token = get_ms_token($tenant_id, $client_id, $client_secret);
        $user_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        $result = account_enable($user_email, $token);
        if ($result) {
            response(0, "$user_email 允许失败");
        } else {
            response(1, "$user_email 允许成功");
        }
        break;

    case 'invitation_code_account_disable':
        if (!check_login()) {
            response(1, "登录已失效");
        }
        $token = get_ms_token($tenant_id, $client_id, $client_secret);
        $user_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        $result = accountin_disable($user_email, $token);
        if ($result) {
            response(0, "$user_email 禁用失败");
        } else {
            response(1, "$user_email 成功禁用");
        }
        break;

    default:
        response(1, "无效的操作");
        break;
}