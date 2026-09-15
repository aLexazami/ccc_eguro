<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
# ===================================================================================

header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Unknown error', 'url' => '', 'token' => '');

if (!in_array(trim($_SERVER['REQUEST_METHOD']), array('POST', 'GET'))) {
    $response = array('success' => false, 'message' => 'Request Error, please try again.', 'url' => '', 'token' => '');
    echo json_encode($response);
    exit();
}

$csrf = new CSRF($session_class);
$token_1 = $csrf->validate('token_login_system', $_POST['token_login_system'] ?? '');
$general_token = $csrf->string('token_login_system', 3600, 1);

if (!$token_1) {
    $response = array('success' => false, 'message' => 'Invalid Auth-Token.', 'url' => '', 'token' => $general_token);
    echo json_encode($response);
    exit();
}

if (isset($_POST['actionSubmit']) && $_POST['actionSubmit'] === 'submitLogin') {
    $system_type = isset($_POST['system_type']) ? trim($_POST['system_type']) : '';
    $system_role = isset($_POST['system_role']) ? trim($_POST['system_role']) : '';
    $password = isset($_POST['profilePassword']) ? trim($_POST['profilePassword']) : '';
    $agents = isset($_POST['agents']) ? json_decode($_POST['agents']) : array();
    $ip = get_ip();

    $system_link = "";
    $status = false;
    $device = json_encode($agents);

    if (empty($system_type) || empty($system_role) || empty($password)) {
        $response = array('success' => false, 'message' => 'Missing essential payload values', 'url' => '', 'token' => $general_token);
        echo json_encode($response);
        exit();
    }

    if (!isset(SYSTEM_ACCESS[$system_type])) {
        $response = array('success' => false, 'message' => 'Invalid System Access', 'url' => '', 'token' => $general_token);
        echo json_encode($response);
        exit();
    }

    $api = new \Src\Api\ApiHelper($db_connect);
    $api->setKeys($system_type);
    $g_public_key = $api->get_public_key();

    $_data = [];
    $query = "SELECT tbl_user.*, 
                -- login
                tbl_login.username, tbl_login.password, tbl_login.recovery_email, tbl_login.status, tbl_login.locked,
                -- system access
                tbl_access.ref_id, tbl_access.system_type, tbl_access.system_role, tbl_access.access_tag, tbl_access.employee_update, tbl_access.student_update, tbl_access.flag_access,
                -- employee
                tbl_employee.employee_id, tbl_employee.personnel_classification, tbl_employee.employment_status, tbl_employee.employment_basis, tbl_employee.position, tbl_employee.employment_date, tbl_employee.office_id, tbl_employee.department_id, tbl_employee.room_id, tbl_employee.service_status,
                -- student
                tbl_student.student_id

                FROM users AS tbl_user
                LEFT JOIN login AS tbl_login ON tbl_login.user_id = tbl_user.id 
                LEFT JOIN system_access AS tbl_access ON tbl_access.user_id = tbl_user.id 
                LEFT JOIN employee AS tbl_employee ON tbl_employee.user_id = tbl_user.id 
                LEFT JOIN student AS tbl_student ON tbl_student.user_id = tbl_user.id 
                WHERE tbl_user.id = ? AND tbl_access.system_type = ? AND tbl_access.system_role = ? LIMIT 1";

    if ($stmt = mysqli_prepare($db_connect, $query)) {
        mysqli_stmt_bind_param($stmt, "isi", $g_user_id, $system_type, $system_role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($data = mysqli_fetch_assoc($result)) {
            $_data = $data;
        }
        mysqli_stmt_close($stmt);
    }

    if (empty($_data)) {
        $response = array('success' => false, 'message' => 'System Access Denied, not registered', 'url' => '', 'token' => $general_token);
        echo json_encode($response);
        exit();
    }

    if ((int)$_data['status'] !== 0 || (int)$_data['flag_access'] !== 0 || (int)$_data['locked'] === 1) {
        $response = array('success' => false, 'message' => 'Account blocked or inactive.', 'url' => '', 'token' => $general_token);
        echo json_encode($response);
        exit();
    }

    if (!$helper->verify_password($password, $_data['password'])) {
        $response = array('success' => false, 'message' => 'Invalid Password', 'url' => '', 'token' => $general_token);
        echo json_encode($response);
        exit();
    }

    $db_system_type = $_data['system_type'];
    $db_system_role = $_data['system_role'];

    $system_link = (($db_system_type == "LMS" && ($db_system_role == "2" || $db_system_role == "3"))) ? SYSTEM_ACCESS[$db_system_type]['link']['second'] : SYSTEM_ACCESS[$db_system_type]['link']['main'];

    if ($db_system_type == "E-GURO++") {
        $system_role_name = SYSTEM_ACCESS[$db_system_type]['role'][$db_system_role];
        $session_class->setValue('user_role_id', $db_system_role);
        $session_class->setValue('user_role', $system_role_name);
        $session_class->setValue(SYSTEM_ACCESS[$db_system_type]['auth'], $g_public_key);

        $response = array('success' => true, 'message' => '', 'url' => BASE_URL . "user-information", 'token' => $general_token);
        echo json_encode($response);
        exit();
    }

    $token_id = $csrf->string($system_type, TIME_STRING, MAX_HASHES_STRING);

    $system_access = $helper->getTableData("system_access", [["user_id", "=", $_data['id']], ["system_type", "=", $db_system_type]]);

    if ((int)$_data['ref_id'] === 0) {
        $api_url = SYSTEM_ACCESS[$db_system_type]['link']['system_access'];

        $encoded_data = array([
            'ref_id'        => $_data['id'] ?? 0,
            'users'         => [
                'first_name'     => $_data['first_name'] ?? '',
                'middle_name'    => $_data['middle_name'] ?? '',
                'last_name'      => $_data['last_name'] ?? '',
                'suffix'         => $_data['suffix'] ?? '',
                'birth_date'     => $_data['birth_date'] ?? '',
                'sex'            => $_data['sex'] ?? '',
                'email'          => $_data['email'] ?? '',
                'personal_email' => $_data['personal_email'] ?? '',
                'civil_status'   => $_data['civil_status'] ?? '',
                'nationality'    => $_data['nationality'] ?? '',
                'birth_place'    => $_data['birth_place'] ?? '',
                'contact_no'     => $_data['contact_no'] ?? '',
                'brgy'           => $_data['brgy'] ?? '',
                'city'           => $_data['city'] ?? '',
                'province'       => $_data['province'] ?? '',
                'home_address'   => $_data['home_address'] ?? '',
                'e_name'         => $_data['e_name'] ?? '',
                'e_relationship' => $_data['e_relationship'] ?? '',
                'e_contact'      => $_data['e_contact'] ?? '',
                'e_address'      => $_data['e_address'] ?? '',
                'flag_status'    => $_data['flag_status'] ?? '',
            ],
            'employee'      => [
                'employee_id'              => $_data['employee_id'] ?? '',
                'personnel_classification' => $_data['personnel_classification'] ?? '',
                'employment_status'        => $_data['employment_status'] ?? '',
                'employment_basis'         => $_data['employment_basis'] ?? '',
                'position'                 => $_data['position'] ?? '',
                'employment_date'          => $_data['employment_date'] ?? '',
                'office_id'                => $_data['office_id'] ?? '',
                'department_id'            => $_data['department_id'] ?? '',
                'room_id'                  => $_data['room_id'] ?? '',
                'service_status'           => $_data['service_status'] ?? '',
            ],
            'student'       => [],
            'login'         => [
                'username'       => $_data['username'] ?? '',
                'password'       => $_data['password'] ?? '',
                'recovery_email' => $_data['recovery_email'] ?? '',
                'status'         => $_data['status'] ?? '',
                'locked'         => $_data['locked'] ?? '',
            ],
            'system_access' => [
                'system_type' => $_data['system_type'] ?? '',
                'system_role' => $_data['system_role'] ?? '',
                'access_tag'  => $_data['access_tag'] ?? '',
            ]
        ]);

        // Execute API synchronization through syncAccountData helper
        $syncResult = $api->syncAccountData($api_url, $db_system_type, $encoded_data, 'add_account', true);

        if (!$syncResult['success']) {
            $response = array('success' => false, 'message' => $syncResult['message'], 'url' => '', 'token' => $general_token);
            echo json_encode($response);
            exit();
        }

        // Extract mapped ref_id
        $_data['ref_id'] = isset($syncResult['data'][0]['system_ref_id']) ? trim($syncResult['data'][0]['system_ref_id']) : 0;
        if (empty($_data['ref_id'])) {
            $response = array('success' => false, 'message' => 'Invalid Remote Mapping ID Target returned, please contact administrator', 'url' => '', 'token' => $general_token);
            echo json_encode($response);
            exit();
        }

        $status = true;
    }

    if ((int)$_data['ref_id'] !== 0 || $status === true) {
        $payload_string = $_data['ref_id'] . "," . $db_system_role . "," . date("Y-m-d H:i:s", strtotime(DATE_TIME . "+5 minutes")) . "," . SYSTEM_ACCESS[$db_system_type]['auth'] . "," . $token_id . "," . base64_encode($device) . "," . base64_encode($ip);

        $secure_token_parameter = $api->encryptApiData($payload_string);
        $targetUrl = $system_link . "?token=" . urlencode($secure_token_parameter);

        $response = array('success' => true, 'message' => '', 'url' => $targetUrl, 'token' => $general_token);
        echo json_encode($response);
        exit();
    }
}

$response = array('success' => false, 'message' => 'Request error, please try again', 'url' => '', 'token' => $general_token);
echo json_encode($response);
exit();
