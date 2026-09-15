<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';
$g_fullname = $g_fullname ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
// set_time_limit(0);
// ini_set('max_execution_time', '0');
// ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
# ===================================================================================

## Access validation
$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);

if (!($g_user_role == "ADMIN") && !($system_auth_login == $g_public_key)) {
    echo json_encode(["msg_status" => false, "msg_response" => "Request Error"]);
    exit();
}

if (!in_array(trim($_SERVER['REQUEST_METHOD']), ['POST', 'GET'], true)) {
    echo json_encode(["msg_status" => false, "msg_response" => "Request Error"]);
    exit();
}

## Limit birthdate
$limit_birth_date = date('Y-m-d', strtotime('-15 years'));

## Trim all values in POST/GET
if (!empty($_POST)) {
    $_POST = $helper->cleanArray($_POST);
}
if (!empty($_GET)) {
    $_GET = $helper->cleanArray($_GET);
}

## Defaults
$table        = "system_access";
$login_table  = "login";
$users_table  = "users";
$access_tag   = "EMPLOYEE";
$output       = ["msg_status" => false, "msg_response" => "Request Error, please try again"];

## Filter System Access
$system_access = isset(ROLE_PERMISSION[$g_user_role]) ? $helper->filterSystemAccess(SYSTEM_ACCESS, ROLE_PERMISSION[$g_user_role]) : [];

# ==========================================
# NEW EMPLOYEE ACCOUNT SYSTEM
if (isset($_POST['actionSubmitEmployeeSystem']) && $_POST['actionSubmitEmployeeSystem'] == 'submitUserEmployeeSystem') {
    $id           = isset($_POST['user_id']) ? trim($_POST['user_id']) : 0;
    $employee_id  = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
    $username     = isset($_POST['username']) ? trim($_POST['username']) : '';
    $system_names = $_POST['system_name'] ?? [];
    $system_roles = $_POST['system_role'] ?? [];

    if ($helper->required($_POST, ['user_id', 'username'])) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Required Field(s) cannot be empty";
        echo json_encode($output);
        exit();
    }

    if (empty($system_names) || empty($system_roles) || count($system_names) !== count($system_roles)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Please add at least one complete System Access and User Role assignment";
        echo json_encode($output);
        exit();
    }

    if ($helper->isEmpty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data Structure";
        echo json_encode($output);
        exit();
    }

    $user_record = $helper->getTableData("users", [['id', '=', $id]]);
    if ($helper->isArrayEmpty($user_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "User Information does not exist";
        echo json_encode($output);
        exit();
    }
    $user_record = $user_record[0];
    $user_id     = $user_record['id'] ?? 0;

    if ($helper->isEmpty($user_id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "User Information does not exist";
        echo json_encode($output);
        exit();
    }

    $employee_record = $helper->getTableData("employee", [['user_id', '=', $id]]);
    if ($helper->isArrayEmpty($employee_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Employee Information does not exist";
        echo json_encode($output);
        exit();
    }
    $employee_record = $employee_record[0];

    if ($helper->selectDuplicate($table, [["user_id", "=", $id]])) {
        $output['msg_status']   = false;
        $output['msg_response'] = "System Account(s) already exists";
        echo json_encode($output);
        exit();
    }

    if ($helper->selectDuplicate($login_table, [["username", "=", $username]])) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Username already exists";
        echo json_encode($output);
        exit();
    }

    $seen_combinations  = [];
    $system_role_counts = [];

    foreach ($system_names as $index => $sys_name) {
        $sys_name = trim($sys_name);
        $sys_role = isset($system_roles[$index]) ? trim($system_roles[$index]) : '';

        if (empty($sys_name) || empty($sys_role)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "All added System Access and Role entries must be filled.";
            echo json_encode($output);
            exit();
        }

        if (!array_key_exists($sys_name, $system_access)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Invalid System Choice: " . htmlspecialchars($sys_name);
            echo json_encode($output);
            exit();
        }

        $allowed_roles   = $system_access[$sys_name]['role'] ?? [];
        $valid_role_keys = is_array($allowed_roles) ? array_map('strval', array_keys($allowed_roles)) : [];

        if (!in_array((string)$sys_role, $valid_role_keys, true)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Invalid user role assigned for " . htmlspecialchars($system_access[$sys_name]['name']);
            echo json_encode($output);
            exit();
        }

        $pair_key = $sys_name . "::" . $sys_role;
        if (in_array($pair_key, $seen_combinations, true)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Duplicate system role assignment detected for " . htmlspecialchars($system_access[$sys_name]['name']);
            echo json_encode($output);
            exit();
        }
        $seen_combinations[] = $pair_key;

        if (!isset($system_role_counts[$sys_name])) {
            $system_role_counts[$sys_name] = 0;
        }
        $system_role_counts[$sys_name]++;

        if ($system_role_counts[$sys_name] > count($valid_role_keys)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "You have exceeded the maximum available roles for " . htmlspecialchars($system_access[$sys_name]['name']);
            echo json_encode($output);
            exit();
        }
    }

    $db_connect->begin_transaction();
    try {
        $text_password = $helper->generate_password();
        $password      = $helper->set_password($text_password);
        $date_validity = date('Y-m-d', strtotime(DATE_NOW . "+30 days"));

        $login_fields = [
            'user_id'        => $user_id,
            'username'       => $username,
            'password'       => $password,
            'recovery_email' => $user_record['personal_email'] ?? '',
            'status'         => 0,
            'locked'         => 0,
            'flag_update'    => 0,
            'flag_validity'  => 0,
            'date_modify'    => DATE_TIME,
            'date_username'  => $date_validity,
            'date_password'  => $date_validity
        ];

        # Insert Login Data
        $insert = $helper->insertData($login_table, $login_fields);
        if (!$insert) {
            throw new Exception("An error occurred while saving login information details.");
        }

        $api_queue = [];

        # Prepare sanitized copies for API payload without mutating primary source
        $payload_user = $user_record;
        $payload_user['ref_id'] = $user_id;
        unset($payload_user['id'], $payload_user['middle_initial'], $payload_user['flag_update'], $payload_user['date_modify']);

        $payload_emp = $employee_record;
        unset($payload_emp['id'], $payload_emp['user_id'], $payload_emp['flag_update'], $payload_emp['date_modify']);

        foreach ($system_names as $index => $sys_name) {
            $sys_name_clean = trim($sys_name);
            $submitted_role = trim($system_roles[$index]);
            $sys_role_key   = $submitted_role;

            if (isset($system_access[$sys_name_clean]['role'])) {
                $allowed_roles = $system_access[$sys_name_clean]['role'];
                $searched_key  = array_search($submitted_role, $allowed_roles);
                if ($searched_key !== false) {
                    $sys_role_key = (string)$searched_key;
                }
            }

            $fields = [
                'user_id'         => $user_id,
                'ref_id'          => 0,
                'system_type'     => $sys_name_clean,
                'system_role'     => $sys_role_key,
                'access_tag'      => $access_tag,
                'student_update'  => 0,
                'employee_update' => 0,
                'flag_access'     => 0,
                'date_modify'     => DATE_TIME
            ];

            # Insert System Access Link
            $insert_system = $helper->insertData($table, $fields);
            if (!$insert_system) {
                throw new Exception("Failed to authorize assignment link for: " . $sys_name_clean);
            }

            # Filter systems for API synchronization
            if (!in_array($sys_name_clean, ['E-GURO++', 'LMS'], true)) {
                $api_queue[$sys_name_clean][] = [
                    'ref_id'   => $user_id,
                    'users'    => $payload_user,
                    'employee' => $payload_emp,
                    'student'  => [],
                    'login'    => [
                        'username'       => $username,
                        'password'       => $password,
                        'recovery_email' => $user_record['personal_email'] ?? '',
                        'status'         => 0,
                        'locked'         => 0,
                        'flag_update'    => 0,
                        'flag_validity'  => 0,
                        'date_username'  => $date_validity,
                        'date_password'  => $date_validity
                    ],
                    'system_access' => [
                        'system_type'     => $sys_name_clean,
                        'system_role'     => $sys_role_key,
                        'access_tag'      => $access_tag,
                        'student_update'  => 0,
                        'employee_update' => 0,
                        'flag_access'     => 0,
                    ]
                ];
            }
        }

        $fields['assigned_access_systems'] = array_combine($system_names, $system_roles);
        activity_log_new("ADDED EMPLOYEE SYSTEM ACCESS :: Details: " . json_encode($fields));
        $db_connect->commit();

        if (!empty($api_queue)) {
            foreach ($api_queue as $sys_type => $items) {
                $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
                if (!empty($api_url)) {
                    $api->syncAccountData($api_url, $sys_type, $items, 'add_account', true);
                }
            }
        }

        $output['msg_status']   = true;
        $output['msg_response'] = "Added Successfully";
        $output['password'] = $text_password;
    } catch (Exception $exception) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $exception->getMessage();
    }

    echo json_encode($output);
    exit();
}

# ==========================================
# ADDITIONAL EMPLOYEE ACCOUNT SYSTEM
if (isset($_POST['actionSubmitEmployeeAccountSystem']) && $_POST['actionSubmitEmployeeAccountSystem'] == 'submitUserEmployeeAccountSystem') {
    $id           = isset($_POST['a-user_id']) ? trim($_POST['a-user_id']) : 0;
    $employee_id  = isset($_POST['a-employee_id']) ? trim($_POST['a-employee_id']) : '';
    $username     = isset($_POST['a-username']) ? trim($_POST['a-username']) : '';
    $system_names = $_POST['a-system_name'] ?? [];
    $system_roles = $_POST['a-system_role'] ?? [];

    if (empty($system_names) || empty($system_roles) || count($system_names) !== count($system_roles)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Please add at least one complete System Access and User Role assignment";
        echo json_encode($output);
        exit();
    }

    if ($helper->isEmpty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data Structure";
        echo json_encode($output);
        exit();
    }

    $user_record = $helper->getTableData("users", [['id', '=', $id]]);
    if ($helper->isArrayEmpty($user_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "User Information does not exist";
        echo json_encode($output);
        exit();
    }
    $user_record = $user_record[0];

    $employee_record = $helper->getTableData("employee", [['user_id', '=', $id]]);
    if ($helper->isArrayEmpty($employee_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Employee Information does not exist";
        echo json_encode($output);
        exit();
    }
    $employee_record = $employee_record[0];

    $login_record = $helper->getTableData("login", [['user_id', '=', $id]]);
    if ($helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Credentials Information does not exist";
        echo json_encode($output);
        exit();
    }
    $login_record = $login_record[0];

    $seen_combinations  = [];
    $system_role_counts = [];

    foreach ($system_names as $index => $sys_name) {
        $sys_name = trim($sys_name);
        $sys_role = isset($system_roles[$index]) ? trim($system_roles[$index]) : '';

        if (empty($sys_name) || empty($sys_role)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "All added System Access and Role entries must be filled.";
            echo json_encode($output);
            exit();
        }

        if (!array_key_exists($sys_name, $system_access)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Invalid System Choice: " . htmlspecialchars($sys_name);
            echo json_encode($output);
            exit();
        }

        $allowed_roles   = $system_access[$sys_name]['role'] ?? [];
        $valid_role_keys = is_array($allowed_roles) ? array_map('strval', array_keys($allowed_roles)) : [];

        if (!in_array((string)$sys_role, $valid_role_keys, true)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Invalid user role assigned for " . htmlspecialchars($system_access[$sys_name]['name']);
            echo json_encode($output);
            exit();
        }

        $existing_count = 0;
        $check_stmt     = $db_connect->prepare("SELECT COUNT(*) FROM `$table` WHERE `user_id` = ? AND `system_type` = ? AND `system_role` = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("iss", $id, $sys_name, $sys_role);
            $check_stmt->execute();
            $check_stmt->bind_result($existing_count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($existing_count > 0) {
                $output['msg_status']   = false;
                $output['msg_response'] = "The user already has access to " . htmlspecialchars($system_access[$sys_name]['name']) . " with that specific role in the system.";
                echo json_encode($output);
                exit();
            }
        }

        $pair_key = $sys_name . "::" . $sys_role;
        if (in_array($pair_key, $seen_combinations, true)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Duplicate system role assignment detected for " . htmlspecialchars($system_access[$sys_name]['name']);
            echo json_encode($output);
            exit();
        }
        $seen_combinations[] = $pair_key;

        if (!isset($system_role_counts[$sys_name])) {
            $system_role_counts[$sys_name] = 0;
        }
        $system_role_counts[$sys_name]++;

        if ($system_role_counts[$sys_name] > count($valid_role_keys)) {
            $output['msg_status']   = false;
            $output['msg_response'] = "You have exceeded the maximum available roles for " . htmlspecialchars($system_access[$sys_name]['name']);
            echo json_encode($output);
            exit();
        }
    }

    $db_connect->begin_transaction();
    try {
        $api_queue = [];
        foreach ($system_names as $index => $sys_name) {
            $sys_name_clean = trim($sys_name);
            $submitted_role = trim($system_roles[$index]);
            $sys_role_key   = $submitted_role;

            if (isset($system_access[$sys_name_clean]['role'])) {
                $allowed_roles = $system_access[$sys_name_clean]['role'];
                $searched_key  = array_search($submitted_role, $allowed_roles);
                if ($searched_key !== false) {
                    $sys_role_key = (string)$searched_key;
                }
            }

            $fields = [
                'user_id'         => $id,
                'ref_id'          => 0,
                'system_type'     => $sys_name_clean,
                'system_role'     => $sys_role_key,
                'access_tag'      => $access_tag,
                'student_update'  => 0,
                'employee_update' => 0,
                'flag_access'     => 0,
                'date_modify'     => DATE_TIME
            ];

            $insert_system = $helper->insertData($table, $fields);
            if (!$insert_system) {
                throw new Exception("Failed to authorize assignment link for: " . $sys_name_clean);
            }

            # Filter systems for API synchronization
            if (!in_array($sys_name_clean, ['E-GURO++', 'LMS'], true)) {
                $api_queue[$sys_name_clean][] = [
                    'ref_id'   => $id,
                    'users'    => $user_record,
                    'employee' => $employee_record,
                    'student'  => [],
                    'login'    => $login_record,
                    'system_access' => [
                        'system_type'     => $sys_name_clean,
                        'system_role'     => $sys_role_key,
                        'access_tag'      => $access_tag,
                        'student_update'  => 0,
                        'employee_update' => 0,
                        'flag_access'     => 0,
                    ]
                ];
            }
        }

        $fields['assigned_access_systems'] = array_combine($system_names, $system_roles);
        activity_log_new("ADDED EMPLOYEE ACCOUNT SYSTEM ACCESS :: Details: " . json_encode($fields));
        $db_connect->commit();

        if (!empty($api_queue)) {
            foreach ($api_queue as $sys_type => $items) {
                $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
                if (!empty($api_url)) {
                    $api->syncAccountData($api_url, $sys_type, $items, 'add_account');
                }
            }
        }

        $output['msg_status']   = true;
        $output['msg_response'] = "Added Successfully";
    } catch (Exception $exception) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $exception->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// ACTION 3: UPDATE ACCOUNT INFORMATION
// ==========================================
if (isset($_POST['actionUpdateAccountSystem']) && $_POST['actionUpdateAccountSystem'] == 'submitUpdateAccountSystem') {
    $id             = isset($_POST['u-account-id']) ? trim($_POST['u-account-id']) : '';
    $username       = isset($_POST['u-account-username']) ? trim($_POST['u-account-username']) : '';
    $account_status = isset($_POST['u-account-status']) ? trim($_POST['u-account-status']) : 0;
    $personal_email = isset($_POST['u-account-personal_email']) ? trim($_POST['u-account-personal_email']) : '';

    if ($helper->required($_POST, ['u-account-username'])) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Required Field(s) cannot be empty";
        echo json_encode($output);
        exit();
    }

    if ($helper->isEmpty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    if (!array_key_exists($account_status, ACCOUNT_STATUS)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Account Status";
        echo json_encode($output);
        exit();
    }

    $login_record = $helper->getTableData($login_table, [['user_id', '=', $id]], 'username,status', null, 1);
    if ($helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Account Information does not exist";
        echo json_encode($output);
        exit();
    }
    $login_record = $login_record[0];

    $users_record = $helper->getTableData($users_table, [['id', '=', $id]], 'personal_email', null, 1);
    if (!$users_record) {
        $output['msg_status']   = false;
        $output['msg_response'] = "User Information does not exist";
        echo json_encode($output);
        exit();
    }
    $users_record = $users_record[0];

    $_records = array_merge($login_record, $users_record);
    $new_record = [
        'username'       => $username,
        'status'         => $account_status,
        'personal_email' => $personal_email
    ];

    $_changes = get_array_changes($_records, $new_record);
    if ($helper->isArrayEmpty($_changes)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "You haven't changed any of the information";
        echo json_encode($output);
        exit();
    }

    if ($helper->selectDuplicate($login_table, [["username", "=", $username], ["user_id", "!=", $id]])) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Username already exists";
        echo json_encode($output);
        exit();
    }

    if (!empty($personal_email)) {
        if ($helper->selectDuplicate($users_table, [["personal_email", "=", $personal_email], ["id", "!=", $id]])) {
            $output['msg_status']   = false;
            $output['msg_response'] = "Personal Email already exists";
            echo json_encode($output);
            exit();
        }
    }

    $db_connect->begin_transaction();
    try {
        $fields = [
            'username'    => $username,
            'status'      => $account_status,
            'date_modify' => DATE_TIME,
        ];
        $where = ['user_id' => $id];

        if (!$helper->updateData($login_table, $fields, $where)) {
            throw new Exception("Error occurred in login information");
        }

        $_fields = [
            'personal_email' => $personal_email,
            'date_modify'    => DATE_TIME,
        ];
        $_where = ['id' => $id];

        if (!$helper->updateData($users_table, $_fields, $_where)) {
            throw new Exception("Error occurred in user information");
        }

        activity_log_new("UPDATED ACCOUNT INFORMATION :: Details: " . json_encode($_changes));
        $db_connect->commit();

        // Dispatch updated account information across all active systems for this user
        $user_full_record  = $helper->getTableData($users_table, [['id', '=', $id]]);
        $user_full_record  = !$helper->isArrayEmpty($user_full_record) ? $user_full_record[0] : [];

        $emp_full_record   = $helper->getTableData("employee", [['user_id', '=', $id]]);
        $emp_full_record   = !$helper->isArrayEmpty($emp_full_record) ? $emp_full_record[0] : [];

        $login_full_record = $helper->getTableData($login_table, [['user_id', '=', $id]]);
        $login_full_record = !$helper->isArrayEmpty($login_full_record) ? $login_full_record[0] : [];

        $active_systems    = $helper->getTableData($table, [['user_id', '=', $id], ['flag_access', '=', 0]]);
        $api_queue         = [];

        if (!empty($active_systems)) {
            foreach ($active_systems as $sys_data) {
                $sys_type = $sys_data['system_type'];
                if ($sys_type !== 'E-GURO++') {
                    $api_queue[$sys_type][] = array_merge(
                        $user_full_record,
                        $emp_full_record,
                        $login_full_record,
                        [
                            'system_type' => $sys_type,
                            'system_role' => $sys_data['system_role'],
                            'access_tag'  => $sys_data['access_tag'] ?? $access_tag
                        ]
                    );
                }
            }
        }

        if (!empty($api_queue)) {
            foreach ($api_queue as $sys_type => $items) {
                $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
                if (!empty($api_url)) {
                    $api->syncAccountData($api_url, $sys_type, $items, 'add_account');
                }
            }
        }

        $output['msg_status']   = true;
        $output['msg_response'] = "Updated Successfully";
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// ACTION 4: LOCK ACCOUNT
// ==========================================
if (isset($_POST['actionAccountLock']) && $_POST['actionAccountLock'] == 'submitAccountLock') {
    $id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

    if (empty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $login_record = $helper->getTableData($login_table, [['user_id', '=', $id]], 'username');
    if ($helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Account Information does not exist";
        echo json_encode($output);
        exit();
    }

    $fields = ['locked' => 1];
    $where  = ['user_id' => $id];

    $db_connect->begin_transaction();
    try {
        if (!$helper->updateData($login_table, $fields, $where)) {
            throw new Exception("Error occurred in user information");
        }
        activity_log_new("LOCKED ACCOUNT :: Details: " . json_encode($login_record));
        $db_connect->commit();

        $output['msg_status']   = true;
        $output['msg_response'] = "Locked Successfully";
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// ACTION 5: UNLOCK ACCOUNT
// ==========================================
if (isset($_POST['actionAccountUnlock']) && $_POST['actionAccountUnlock'] == 'submitAccountUnlock') {
    $id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';

    if (empty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $login_record = $helper->getTableData($login_table, [['user_id', '=', $id]], 'username');
    if ($helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Account Information does not exist";
        echo json_encode($output);
        exit();
    }

    $fields = ['locked' => 0];
    $where  = ['user_id' => $id];

    $db_connect->begin_transaction();
    try {
        if (!$helper->updateData($login_table, $fields, $where)) {
            throw new Exception("Error occurred in user information");
        }
        activity_log_new("UNLOCK ACCOUNT :: Details: " . json_encode($login_record));
        $db_connect->commit();

        $output['msg_status']   = true;
        $output['msg_response'] = "Unlock Successfully";
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// ACTION 6: RESET PASSWORD
// ==========================================
if (isset($_POST['actionAcoountReset']) && $_POST['actionAcoountReset'] == 'submitAcoountReset') {
    $id            = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $date_validity = isset($_POST['date_validity']) ? trim($_POST['date_validity']) : '';

    if (empty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $login_record = $helper->getTableData($login_table, [['user_id', '=', $id]], 'username');
    if ($helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Account Information does not exist";
        echo json_encode($output);
        exit();
    }

    if (empty($date_validity) || !$helper->isDigit($date_validity) || !in_array((int)$date_validity, [14, 90], true)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Password Validity";
        echo json_encode($output);
        exit();
    }

    $date_validity     = date('Y-m-d', strtotime(DATE_NOW . " +{$date_validity} days"));
    $password_generate = $helper->generate_password();
    $password          = $helper->set_password($password_generate);

    $fields = [
        'locked'        => 0,
        'password'      => $password,
        'date_password' => $date_validity
    ];
    $where = ['user_id' => $id];

    $db_connect->begin_transaction();
    try {
        if (!$helper->updateData($login_table, $fields, $where)) {
            throw new Exception("Error occurred in user information");
        }
        activity_log_new("RESET ACCOUNT PASSWORD :: Details: " . json_encode($login_record));
        $db_connect->commit();

        // Dispatch Password Reset via ApiHelper to Subsystems
        $active_systems = $helper->getTableData($table, [['user_id', '=', $id], ['flag_access', '=', 0]], 'system_type,system_role,ref_id');
        $api_queue      = [];

        if (!empty($active_systems)) {
            foreach ($active_systems as $sys_data) {
                $sys_type = $sys_data['system_type'];
                $api_queue[$sys_type][] = [
                    'user_id'  => $id,
                    'ref_id'   => $sys_data['ref_id'],
                    'password' => $password_generate
                ];
            }
        }

        // if (!empty($api_queue)) {
        //     foreach ($api_queue as $sys_type => $items) {
        //         $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
        //         if (!empty($api_url)) {
        //             $api->syncAccountData($api_url, $sys_type, $items, 'reset_password');
        //         }
        //     }
        // }

        $output['msg_status']   = true;
        $output['msg_password'] = $password_generate;
        $output['msg_response'] = "Reset Successfully";
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// ACTION 7: DEACTIVATE SYSTEM ACCESS
// ==========================================
if (isset($_POST['actionDeactivateAccountSystem']) && $_POST['actionDeactivateAccountSystem'] == 'submitDeactivateAccountSystem') {
    $id = isset($_POST['access_id']) ? trim($_POST['access_id']) : '';

    if (empty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $system_record = $helper->getTableData($table, [['id', '=', $id]], 'user_id,ref_id,system_type,system_role', null, 1);
    if ($helper->isArrayEmpty($system_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "System Access does not exist";
        echo json_encode($output);
        exit();
    }
    $system_record = $system_record[0];

    $users_record = $helper->getTableData($users_table, [['id', '=', $system_record['user_id']]], 'email', null, 1);
    $login_record = $helper->getTableData($login_table, [['user_id', '=', $system_record['user_id']]], 'username', null, 1);

    if ($helper->isArrayEmpty($users_record) || $helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Account Information does not exist";
        echo json_encode($output);
        exit();
    }

    $users_record = $users_record[0];
    $login_record = $login_record[0];

    $logs = [
        'email'       => $users_record['email'],
        'username'    => $login_record['username'],
        'system_name' => $system_record['system_type'],
        'system_role' => $system_access[$system_record['system_type']]['role'][$system_record['system_role']] ?? $system_record['system_role'],
    ];

    $fields = ['flag_access' => 1];
    $where  = ['id' => $id];

    $db_connect->begin_transaction();
    try {
        if (!$helper->updateData($table, $fields, $where)) {
            throw new Exception("Error occurred in user information");
        }
        activity_log_new("DEACTIVATE SYSTEM ACCESS :: Details: " . json_encode($logs));
        $db_connect->commit();

        // Dispatch status update to destination API
        $sys_type  = $system_record['system_type'];
        $api_queue = [
            $sys_type => [[
                'user_id'     => $system_record['user_id'],
                'ref_id'      => $system_record['ref_id'],
                'flag_access' => 1
            ]]
        ];

        if (!empty($api_queue)) {
            foreach ($api_queue as $sys_type => $items) {
                $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
                if (!empty($api_url)) {
                    $api->syncAccountData($api_url, $sys_type, $items, 'deactivate_access');
                }
            }
        }

        $output['msg_status']   = true;
        $output['msg_response'] = "Deactivated Successfully";
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// ACTION 8: ACTIVATE SYSTEM ACCESS
// ==========================================
if (isset($_POST['actionActivateAccountSystem']) && $_POST['actionActivateAccountSystem'] == 'submitActivateAccountSystem') {
    $id = isset($_POST['access_id']) ? trim($_POST['access_id']) : '';

    if (empty($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);
    if (!$helper->isDigit($id)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $system_record = $helper->getTableData($table, [['id', '=', $id]], 'user_id,ref_id,system_type,system_role', null, 1);
    if ($helper->isArrayEmpty($system_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "System Access does not exist";
        echo json_encode($output);
        exit();
    }
    $system_record = $system_record[0];

    $users_record = $helper->getTableData($users_table, [['id', '=', $system_record['user_id']]], 'email', null, 1);
    $login_record = $helper->getTableData($login_table, [['user_id', '=', $system_record['user_id']]], 'username', null, 1);

    if ($helper->isArrayEmpty($users_record) || $helper->isArrayEmpty($login_record)) {
        $output['msg_status']   = false;
        $output['msg_response'] = "Account Information does not exist";
        echo json_encode($output);
        exit();
    }

    $users_record = $users_record[0];
    $login_record = $login_record[0];

    $logs = [
        'email'       => $users_record['email'],
        'username'    => $login_record['username'],
        'system_name' => $system_record['system_type'],
        'system_role' => $system_access[$system_record['system_type']]['role'][$system_record['system_role']] ?? $system_record['system_role'],
    ];

    $fields = ['flag_access' => 0];
    $where  = ['id' => $id];

    $db_connect->begin_transaction();
    try {
        if (!$helper->updateData($table, $fields, $where)) {
            throw new Exception("Error occurred in user information");
        }
        activity_log_new("ACTIVATE SYSTEM ACCESS :: Details: " . json_encode($logs));
        $db_connect->commit();

        // Dispatch status update to destination API
        $sys_type  = $system_record['system_type'];
        $api_queue = [
            $sys_type => [[
                'user_id'     => $system_record['user_id'],
                'ref_id'      => $system_record['ref_id'],
                'flag_access' => 0
            ]]
        ];

        if (!empty($api_queue)) {
            foreach ($api_queue as $sys_type => $items) {
                $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
                if (!empty($api_url)) {
                    $api->syncAccountData($api_url, $sys_type, $items, 'activate_access');
                }
            }
        }

        $output['msg_status']   = true;
        $output['msg_response'] = "Activated Successfully";
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_status']   = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

// ==========================================
// DATA FETCHING: FETCH EMPLOYEE SYSTEM ADD
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'fetchEmployeeSystemAdd') {
    try {
        $employee_records  = $helper->getTableData("employee", [['service_status', '=', 0]], 'user_id,employee_id');
        $employee_ids      = array_column($employee_records, 'employee_id', 'user_id');
        $employee_user_ids = array_keys($employee_ids);

        $system_records = $helper->getTableData("system_access", [['user_id', 'IN', $employee_user_ids]], 'user_id');
        $system_user_ids = array_column($system_records, 'user_id');

        $conditions = [
            ['flag_status', '=', 0],
            ['id', 'IN', $employee_user_ids],
            ['id', 'NOT IN', $system_user_ids]
        ];

        $users = $helper->getTableData('users', $conditions, 'id AS user_id, last_name, CONCAT_WS(" ", first_name, NULLIF(middle_name, " "), last_name, NULLIF(suffix, " ")) AS name, email', 'last_name ASC');

        foreach ($users as &$user) {
            $user['employee_id'] = $employee_ids[$user['user_id']] ?? '';
            $user['user_id']     = encrypted_string($user['user_id']);
            unset($user['last_name']);
        }
        unset($user);

        header('Content-Type: application/json');
        echo json_encode($users);
        exit();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

$output['msg_status']   = false;
$output['msg_response'] = "Request Error";
echo json_encode($output);
exit();
