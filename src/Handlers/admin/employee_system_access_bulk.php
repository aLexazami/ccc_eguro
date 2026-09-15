<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';
$g_fullname = $g_fullname ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
require_once UPLOAD_HANDLER;
# ===================================================================================

$g_user_role = $g_user_role ?? '';
$g_fullname  = $g_fullname ?? '';

## Access validation
$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);
if (!($g_user_role == "ADMIN") && !($system_auth_login == $g_public_key)) {
    $result = json_encode(["success" => false, "error" => "ErrorAccess"]);
    echo $result;
    exit();
}

function upload_log($file_path, $log)
{
    file_put_contents($file_path, $log, FILE_APPEND);
}

/**
 * Validates password rules or hashes new password using $helper->set_password().
 * Policy: Minimum 8 characters long
 */
function password_validate(string $password, $helper): array
{
    $password = trim($password);

    if ($password === '****') {
        return ['status' => true, 'action' => 'NO_CHANGE', 'value' => null];
    }

    if (strlen($password) < 8) {
        return [
            'status' => false,
            'error'  => 'Password does not conform to security policy. Minimum length is 8 characters.'
        ];
    }

    return [
        'status' => true,
        'action' => 'HASHED',
        'value'  => $helper->set_password($password)
    ];
}

## Check text logs directory
if (!is_dir(STORAGE_LOGS_PATH)) {
    mkdir(STORAGE_LOGS_PATH, 0755, true);
}

$uploader = new UploaderHandler();
$uploader->allowedExtensions = array('csv');
$uploader->sizeLimit         = CSV_SIZE;
$uploader->uploadDirectory   = STORAGE_CSV_PATH;
$uploader->inputFileName     = "import_employee_sytem_access";

$method = get_request_method();
$session_class->session_close();

function get_request_method()
{
    global $HTTP_RAW_POST_DATA;
    if (isset($HTTP_RAW_POST_DATA)) {
        parse_str($HTTP_RAW_POST_DATA, $_POST);
    }
    if (isset($_POST["_method"]) && $_POST["_method"] != null) {
        return $_POST["_method"];
    }
    return $_SERVER["REQUEST_METHOD"];
}

$separator = "^";
$new_line  = "\r\n";

# Tables definition
$table          = "system_access";
$login_table    = "login";
$user_table     = "users";
$employee_table = "employee";
$access_tag     = "EMPLOYEE";

## System Access configuration filter
$system_access = isset(ROLE_PERMISSION[$g_user_role]) ? $helper->filterSystemAccess(SYSTEM_ACCESS, ROLE_PERMISSION[$g_user_role]) : array();

if ($method == "POST") {
    header("Content-Type: text/plain");
    $result               = $uploader->handleFileUpload();
    $result["uploadName"] = $uploader->getUploadName();

    if (!empty($result["error"])) {
        $result['total']          = 0;
        $result['success_insert'] = 0;
        $result['success_update'] = 0;
        $result['error_id']       = [];
        unset($result['success']);
        echo json_encode($result);
        exit();
    }

    if ((isset($result["success"])) || ($result["uploadName"] != "")) {
        $return_error   = array();
        $time_id        = "IMPORT_EMPLOYEE_SYSTEM_ACCESS_" . time();
        $file_path      = STORAGE_LOGS_PATH . $time_id . ".txt";
        $file           = $uploader->getTargetFilePath();

        if (($handle = fopen($file, "r")) !== FALSE) {
            $file_logs       = "";
            $total_count     = 1;
            $skipped_count   = 0;
            $success_count   = 0;
            $success_insert  = 0;
            $success_update  = 0;
            $success_remain  = 0;
            $user_header     = array();
            $no_record       = true;
            $required_header = array('EMAIL ADDRESS', 'USERNAME', 'PASSWORD', 'SYSTEM NAME', 'SYSTEM ROLE');
            $not_required    = array('FIRST NAME', 'MIDDLE NAME', 'LAST NAME', 'SUFFIX NAME', 'EMPLOYEE ID NUMBER');
            $fixed_header    = array_merge($required_header, $not_required);

            $grouped_users = [];

            while (($column = fgetcsv($handle, 0, ",")) !== FALSE) {
                $error_found = array("msg" => "", "id" => "row_" . $total_count);
                $blank       = false;

                foreach ($column as $index => $value) {
                    $val_trim = trim($value);
                    if (!mb_check_encoding($val_trim, 'UTF-8')) {
                        $encoding = mb_detect_encoding($val_trim, ['ISO-8859-1', 'Windows-1252', 'UTF-8'], true);
                        $column[$index] = mb_convert_encoding($val_trim, 'UTF-8', $encoding ?: 'ISO-8859-1');
                    } else {
                        $column[$index] = $val_trim;
                    }
                }

                if ($total_count == 2) {
                    $file_logs = "File Line No." . ($total_count) . " : SKIPPED" . $new_line;
                    upload_log($file_path, $file_logs);
                    $skipped_count++;
                    $total_count++;
                    continue;
                }

                $column = $helper->cleanArray($column);
                $column = $helper->array_encoding($column);

                if ($total_count == 1) {
                    $column             = array_map('strtoupper', $column);
                    $error_header       = false;
                    $found_header_error = [];

                    foreach ($fixed_header as $header) {
                        $key = array_search($header, $column, true);
                        if ($key !== false) {
                            $user_header[$header] = $key;
                        } elseif (in_array($header, $required_header)) {
                            $error_header         = true;
                            $found_header_error[] = $header;
                        } else {
                            $user_header[$header] = false;
                        }
                    }

                    if ($error_header) {
                        $result['total']          = $total_count;
                        $result['success_insert'] = 0;
                        $result['success_update'] = 0;
                        $result['error_id']       = [];
                        $result['error']          = "FILE CSV HEADER INVALID - NOT FOUND [" . implode(",", $found_header_error) . "]";
                        upload_log($file_path, "File Line No." . $total_count . " : " . $result['error'] . $new_line);
                        unset($result['success']);
                        echo json_encode($result);
                        exit();
                    }

                    upload_log($file_path, "File Line No." . $total_count . " : " . json_encode($user_header) . " : HEADER" . $new_line);
                    $skipped_count++;
                    $total_count++;
                    continue;
                }

                $user_data = [];
                foreach ($user_header as $header => $key) {
                    $user_data[$header] = ($key === false || !isset($column[$key])) ? '' : $column[$key];
                }
                $column = $user_data;

                foreach ($column as $index => $value) {
                    if (in_array($index, $not_required)) {
                        continue;
                    } elseif (trim($value) == "") {
                        $blank              = true;
                        $error_found['msg'] = "File Line No." . $total_count . " :  Missing required data [" . $index . "]" . $new_line;
                        $return_error[]     = $error_found;
                        break;
                    }
                }

                if ($blank) {
                    upload_log($file_path, "File Line No." . $total_count . " : " . $error_found['msg'] . " : FAILED" . $new_line);
                    $total_count++;
                    continue;
                }

                $no_record    = false;
                $email        = trim($column['EMAIL ADDRESS']);
                $username     = trim($column['USERNAME']);
                $raw_password = trim($column['PASSWORD']);

                $password_check = password_validate($raw_password, $helper);
                if (!$password_check['status']) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : " . $password_check['error'] . " : FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[] = $error_found;
                    $total_count++;
                    continue;
                }

                if (!$helper->isEmailDomain($email)) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : Invalid Email Format:FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[]     = $error_found;
                    $total_count++;
                    continue;
                }

                $user_record = $helper->getTableData($user_table, [['email', '=', $email]], '*', null, 1);
                $user_id     = $user_record[0]['id'] ?? 0;
                if (empty($user_id)) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : User Information does not exist:FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[]     = $error_found;
                    $total_count++;
                    continue;
                }

                $employee_record = $helper->getTableData($employee_table, [['user_id', '=', $user_id]], '*', null, 1);
                $employee_id     = $employee_record[0]['id'] ?? 0;
                if (empty($employee_id)) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : Employee Information does not exist:FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[]     = $error_found;
                    $total_count++;
                    continue;
                }

                if ($helper->selectDuplicate($table, [["user_id", "=", $user_id]])) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : System Account(s) already exists:FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[]     = $error_found;
                    $total_count++;
                    continue;
                }

                $system_name     = trim($column['SYSTEM NAME']);
                $system_role     = strtoupper(str_replace(' ', '_', trim($column['SYSTEM ROLE'])));
                $system_name_key = null;

                foreach ($system_access as $key => $config) {
                    if (
                        strcasecmp($key, $system_name) === 0 ||
                        (isset($config['name']) && strcasecmp($config['name'], $system_name) === 0)
                    ) {
                        $system_name_key = $key;
                        break;
                    }
                }

                if ($system_name_key === null) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : Invalid System Assignment Selection [{$system_name}]:FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[]     = $error_found;
                    $total_count++;
                    continue;
                }

                $allowed_roles   = $system_access[$system_name_key]['role'] ?? [];
                $system_role_key = array_search($system_role, array_map('strtoupper', $allowed_roles));

                if ($system_role_key === false) {
                    $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : Invalid Role value [{$system_role}] for system [{$system_name}]:FAILED" . $new_line;
                    upload_log($file_path, $error_found['msg']);
                    $return_error[]     = $error_found;
                    $total_count++;
                    continue;
                }

                $system_role_key = (string)$system_role_key;

                $is_duplicate = false;
                if (isset($grouped_users[$email])) {
                    foreach ($grouped_users[$email]['systems'] as $index => $existing_system) {
                        $existing_role = $grouped_users[$email]['roles'][$index];
                        if ($existing_system === $system_name_key && $existing_role === $system_role_key) {
                            $error_found['msg'] = "File Line No." . $total_count . " : " . $email . " : Duplicate system access with identical role combination recorded [{$system_name}->{$system_role}]:FAILED" . $new_line;
                            upload_log($file_path, $error_found['msg']);
                            $return_error[]     = $error_found;
                            $is_duplicate       = true;
                            break;
                        }
                    }
                } else {
                    $grouped_users[$email] = [
                        'user_id'         => $user_id,
                        'username'        => $username,
                        'raw_password'    => $raw_password,
                        'employee_id'     => trim($column['EMPLOYEE ID NUMBER']),
                        'user_record'     => $user_record[0],
                        'employee_record' => $employee_record[0],
                        'systems'         => [],
                        'roles'           => [],
                        'rows'            => []
                    ];
                }

                if ($is_duplicate) {
                    $total_count++;
                    continue;
                }

                $grouped_users[$email]['systems'][] = $system_name_key;
                $grouped_users[$email]['roles'][]   = $system_role_key;
                $grouped_users[$email]['rows'][]    = $total_count;
                $total_count++;
            }

            fclose($handle);

            // --- DATABASE TRANSACTION & API SYNC STAGE ---
            if (!empty($grouped_users)) {
                foreach ($grouped_users as $email => $payload) {
                    $transaction_status = "FAILED";
                    $log_msg            = "";
                    $api_queue          = [];
                    $db_connect->begin_transaction();

                    try {
                        $raw_password  = $payload['raw_password'];
                        $password_eval = password_validate($raw_password, $helper);
                        $user_id       = $payload['user_id'];
                        $username      = $payload['username'];
                        $password      = $password_eval['value'];
                        $date_validity = DATE_TIME;

                        # Prepare sanitized copies for API payload without mutating primary source
                        $payload_user = $payload['user_record'];
                        $payload_user['ref_id'] = $user_id;
                        unset($payload_user['id'], $payload_user['middle_initial'], $payload_user['flag_update'], $payload_user['date_modify']);

                        $payload_emp = $payload['employee_record'];
                        unset($payload_emp['id'], $payload_emp['user_id'], $payload_emp['flag_update'], $payload_emp['date_modify']);

                        $fields = [
                            'user_id'        => $user_id,
                            'username'       => $username,
                            'recovery_email' => $payload['user_record']['personal_email'] ?? '',
                            'status'         => 0,
                            'locked'         => 0,
                            'flag_update'    => 0,
                            'flag_validity'  => 0,
                            'date_modify'    => DATE_TIME,
                            'date_username'  => DATE_TIME,
                            'date_password'  => DATE_TIME
                        ];

                        // Only insert password field if not ****
                        if ($raw_password !== '****' && !empty($password)) {
                            $fields['password'] = $password;
                        }

                        $insert_id = $helper->insertData($login_table, $fields);
                        if (!$insert_id) {
                            throw new Exception("Error occurred writing system configuration root parameters.");
                        }

                        foreach ($payload['systems'] as $index => $sys_name_clean) {
                            $sys_role_key  = $payload['roles'][$index];
                            $system_fields = [
                                'user_id'         => $user_id,
                                'ref_id'          => 0,
                                'system_type'     => $sys_name_clean,
                                'system_role'     => $sys_role_key,
                                'access_tag'      => $access_tag,
                                'student_update'  => 0,
                                'employee_update' => 0,
                                'flag_access'     => 0,
                                'date_modify'     => DATE_TIME,
                            ];

                            if (!$helper->insertData($table, $system_fields)) {
                                throw new Exception("Failed authorization payload loop handling for: " . $sys_name_clean);
                            }

                            // Filter systems for API synchronization using sanitized payloads
                            if (!in_array($sys_name_clean, ['E-GURO++', 'LMS'], true)) {
                                $login_payload = [
                                    'username'       => $username,
                                    'recovery_email' => $payload['user_record']['personal_email'] ?? '',
                                    'status'         => 0,
                                    'locked'         => 0,
                                    'flag_update'    => 0,
                                    'flag_validity'  => 0,
                                    'date_username'  => $date_validity,
                                    'date_password'  => $date_validity
                                ];

                                // Include password in API payload only if not ****
                                if ($raw_password !== '****' && !empty($password)) {
                                    $login_payload['password'] = $password;
                                }

                                $api_queue[$sys_name_clean][] = [
                                    'ref_id'   => $user_id,
                                    'users'    => $payload_user,
                                    'employee' => $payload_emp,
                                    'student'  => [],
                                    'login'    => $login_payload,
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

                            $success_insert++;
                            $success_count++;
                        }

                        $assigned_access = [];
                        foreach ($payload['systems'] as $idx => $sys) {
                            $assigned_access[] = [
                                'system' => $sys,
                                'role'   => $payload['roles'][$idx]
                            ];
                        }
                        $fields['assigned_access_systems'] = $assigned_access;

                        activity_log_new("ADDED SYSTEM PERMISSIONS BULK IMPORT :: Details: " . json_encode($fields));

                        $db_connect->commit();
                        $transaction_status = "SUCCESS";
                        $log_msg            = "Successfully linked system credentials.";

                        // Execute queued API synchronization requests
                        if (!empty($api_queue)) {
                            foreach ($api_queue as $sys_type => $items) {
                                $api_url = $system_access[$sys_type]['link']['system_access'] ?? '';
                                if (!empty($api_url)) {
                                    $api->syncAccountData($api_url, $sys_type, $items, 'add_account', true);
                                }
                            }
                        }
                    } catch (Exception $exception) {
                        $db_connect->rollback();
                        $transaction_status = "FAILED";
                        $log_msg            = $exception->getMessage();
                        $return_error[]     = [
                            "id"  => "rows_" . implode('_', $payload['rows']),
                            "msg" => "Bulk process error block mapping: " . $log_msg
                        ];
                    }

                    foreach ($payload['rows'] as $row_num) {
                        $file_logs = "File Line No." . $row_num . " : " . $email . " : " . $log_msg . " : " . $transaction_status . $new_line;
                        upload_log($file_path, $file_logs);
                    }
                }
            }

            $result['total']          = $total_count - 1;
            $result['skipped']        = $skipped_count;
            $result['success_insert'] = $success_insert;
            $result['success_update'] = $success_update;
            $result['success_remain'] = $success_remain;
            $result['error_id']       = $return_error;

            if ($no_record) {
                $result['error'] = 'File has no Record';
                unset($result['success']);
            } elseif ($success_count > 0) {
                activity_log_new("IMPORT EMPLOYEE INFORMATION :: [" . $time_id . "]");
                update_summary_logs(IMPORT_EMPLOYEE_LOG, $time_id, $g_fullname);
            }
        }

        echo json_encode($result);
        exit();
    }
} else {
    include HTTP_401;
    exit();
}
