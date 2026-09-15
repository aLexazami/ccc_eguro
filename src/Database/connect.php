<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));

# ======================================================================
# 1. DATABASE ENVIRONMENT CREDENTIALS RESOLUTION
# ======================================================================
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'e_e_dev_eguro';

$db_connect = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (mysqli_connect_errno()) {
    $error = "Failed to connect to Database: " . mysqli_connect_error();
    error_log($error);
    if (defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV') {
        echo $error;
    } else {
        echo "An internal configuration database error occurred.";
    }
    exit();
}

# ======================================================================
# 2. LEGACY COMPATIBILITY wrappers
# ======================================================================
function escape($con = "", $str = "")
{
    global $db_connect;
    return mysqli_real_escape_string($db_connect, $str ?? '');
}

function db_close()
{
    global $db_connect;
    mysqli_close($db_connect);
}

function call_mysql_query($query, $connect = '')
{
    global $db_connect;
    $connect = empty($connect) ? $db_connect : $connect;
    if (empty($query)) { return false; }
    return mysqli_query($connect, $query);
}

function call_mysql_fetch_array($query, $resulttype = MYSQLI_ASSOC, $connect = '')
{
    return mysqli_fetch_array($query, $resulttype);
}

function call_mysql_num_rows($query)
{
    return $query ? mysqli_num_rows($query) : 0;
}

function call_mysql_affected_rows($connect = '')
{
    global $db_connect;
    $connect = empty($connect) ? $db_connect : $connect;
    return mysqli_affected_rows($connect);
}

function mysqli_query_return($sql_query, $connect = "")
{
    global $db_connect;
    $connect = empty($connect) ? $db_connect : $connect;
    $rdata = array();
    if (empty($sql_query)) { return $rdata; }

    if ($query = mysqli_query($connect, $sql_query)) {
        while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
            array_push($rdata, $data);
        }
    }
    return $rdata;
}

function mysqliquery_return($sql_query, $connect = "", $type = MYSQLI_ASSOC)
{
    global $db_connect;
    $connect = (empty($connect)) ? $db_connect : $connect;
    $rdata = array();

    if ($query = mysqli_query($connect, $sql_query)) {
        while ($data = mysqli_fetch_array($query, $type)) {
            $rdata[] = $data;
        }
    }
    return $rdata;
}

# ======================================================================
# 3. HARDENED AUDIT SYSTEMS (PHP 8.x Optimized Prepared Statements)
# ======================================================================
function activity_log_new($action)
{
    // FIX: Bring $session_class into local scope explicitly to prevent PHP 8.x structural errors
    global $db_connect, $session_class;
    
    $date_now = date('Y-m-d H:i:s');
    $user_id = $session_class->getValue('user_id');
    $role_txt = $session_class->getValue('user_role');
    $fingerprint = $session_class->getValue('browser_fingerprint');

    $role_map = ["ADMIN" => 1, "REGISTRAR" => 2, "VPAA" => 3, "OFFICIAL" => 4, "FACULTY" => 5, "STUDENT" => 6];
    $role_id = $role_map[$role_txt] ?? 0;

    if (!empty($user_id) && trim($action) != "") {
        $stmt = mysqli_prepare($db_connect, "INSERT INTO activity_log (user_id, action, date_log, session_id, user_level) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssi", $user_id, $action, $date_now, $fingerprint, $role_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $success;
        }
    }
    return false;
}

function data_log_new($action, $user_id, $system_access = "", $jwt_data = "", $process_flag = "")
{
    global $db_connect;
    $date_now = date('Y-m-d H:i:s');

    if (!empty($user_id) && trim($action) != '') {
        if ($action == 'INSERT_DATA' && !empty($jwt_data) && $process_flag != '' && !empty($system_access)) {
            $stmt = mysqli_prepare($db_connect, "INSERT INTO log (user_id, system_access, data_log, process_flag, action_flag, date_log) VALUES (?, ?, ?, ?, '0', ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssss", $user_id, $system_access, $jwt_data, $process_flag, $date_now);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $success;
            }
        } elseif ($action == 'UPDATE_DATA') {
            $stmt = mysqli_prepare($db_connect, "UPDATE log SET action_flag = '1' WHERE log_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $user_id);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $success;
            }
        }
    }
    return false;
}

function user_log($action, $agents = array())
{
    // FIX: Explicitly reference global $session_class here as well
    global $db_connect, $session_class;
    
    $date_now = date('Y-m-d H:i:s');
    $g_user_id = $session_class->getValue('user_id');
    $fingerprint = $session_class->getValue('browser_fingerprint');
    $ip = get_ip();
    $device = json_encode($agents);

    if (!empty($g_user_id) && trim($action) != "") {
        if ($action == "LOGIN") {
            $stmt = mysqli_prepare($db_connect, "INSERT INTO user_log (login_date, action, user_id, session_id, ip_address, device) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssss", $date_now, $action, $g_user_id, $fingerprint, $ip, $device);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $success;
            }
        } elseif ($action == 'LOGOUT') {
            $stmt = mysqli_prepare($db_connect, "UPDATE user_log SET logout_date = NOW(), action = CONCAT(action, '::LOGOUT') WHERE session_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $fingerprint);
                $success = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $success;
            }
        }
    }
    return false;
}

function get_profile_pic($user_id, $field, $table)
{
    global $db_connect;
    $path = "";
    if (trim($user_id ?? '') == "" || trim($field ?? '') == "" || trim($table ?? '') == "") { return ""; }
    
    $field = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $query = "SELECT location FROM {$table} WHERE {$field} = ? LIMIT 1";
    if ($stmt = mysqli_prepare($db_connect, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($data = mysqli_fetch_assoc($result)) {
            $path = empty($data['location']) ? "" : BASE_URL . $data['location'];
        }
        mysqli_stmt_close($stmt);
    }
    return $path;
}

function isduplicate_where($table_name, $select_column, $sql_where)
{
    global $db_connect;
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $table_name);
    $select_column = preg_replace('/[^a-zA-Z0-9_]/', '', $select_column);

    $default_query = "SELECT {$select_column} FROM {$table_name} WHERE {$sql_where} LIMIT 1";
    if ($query = mysqli_query($db_connect, $default_query)) {
        return mysqli_num_rows($query) > 0;
    }
    return false;
}