<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
# ===================================================================================

function clean_array_null(array $array): array
{
    return array_map(function ($value) {
        return $value ?? "";
    }, $array);
}

$session_class->session_close();
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_401;
    exit();
}

$g_user_role = $g_user_role ?? '';
$db_connect = $db_connect ?? '';

$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);
if (!($g_user_role == "ADMIN") && !($system_auth_login == $g_public_key)) {
    $output =  json_encode(["last_page" => 1, "data" => "", "total_record" => 0]);
    echo $output;
    exit();
}

$query_limit = QUERY_LIMIT;

$table_name = "employee AS tbl_e";
$field_query = '*';
$pages = 0;
$start = 0;
$size = 0;

$sorters = array();
$orderby = "id DESC";
$sql_where = "";
$sql_conds = "";
$sql_where_array = array();
$to_encode = array();
$output = "";
$total_query = 0;

$dbfield = array('tbl_e.*', 'tbl_u.first_name', 'tbl_u.middle_name', 'tbl_u.last_name', 'tbl_u.suffix', 'tbl_u.name', 'tbl_u.email', 'tbl_u.personal_email');
$dborig = array('id', 'user_id', 'name', 'employee_id', 'service_status', 'personnel_classification', 'employment_status', 'employment_basis', 'position', 'office_id', 'department_id', 'room_id', 'flag_status', 'flag_update', 'date_modify');
$left_join = 'LEFT JOIN (SELECT id, first_name, middle_name, last_name, suffix, email, personal_email, CONCAT_WS(" ",first_name,NULLIF(middle_name, ""),last_name,NULLIF(suffix, "")) AS name FROM users) AS tbl_u ON tbl_u.id = tbl_e.user_id';

if (isset($_GET['filters'])) {
    $filters = array();
    $sort_filters = array();
    $filters = $_GET['filters'];
    foreach ($filters as $filter) {
        if (isset($filter['field'])) {
            // if ($filter['field'] == 'user_role' || $filter['field'] == 'user_role') {
            // }

            if (is_array($filter['value'])) {
                // $filter['value'] = $filter['value'];
                continue;
            }

            if (empty($filter['value'])) {
                continue;
            }

            $id = $filter['field'];
            $sort_filters[$id] = $filter['value'];
        }
    }

    foreach ($dborig as $id) {
        if (isset($sort_filters[$id])) {
            $value = escape($db_connect, $sort_filters[$id]);
            if ($id == "name") {
                array_push($sql_where_array, 'tbl_u.name LIKE \'%' . $value . '%\'');
                continue;
            }

            if ($id == "service_status" || $id == "personnel_classification" || $id == "employment_status" || $id == "employment_basis") {
                array_push($sql_where_array, $id . " = '" . $value . "'");
                continue;
            }

            array_push($sql_where_array, $id . ' LIKE \'%' . $value . '%\'');
        }
    }
}

// array_push($sql_where_array, 'status != 2');
if (!empty($sql_where_array)) {
    $temp_arr = implode(' AND ', $sql_where_array);
    $sql_where = (empty($temp_arr)) ? '' : $temp_arr;
}

if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = array('asc', 'desc');
    if (in_array($sorters[0]['field'], $dborig) and in_array($sorters[0]['dir'], $tag)) {
        $orderby = $sorters[0]['field'] . ' ' . $sorters[0]['dir'];
    }
}

if (isset($_GET['size']) and is_digit($_GET['size'])) {
    $query_limit = ($_GET['size'] > $query_limit) ? $_GET['size'] : $query_limit;
}

//total query counter 
$field_query = 'COUNT(DISTINCT tbl_u.id) as count'; // baguhin based sa need
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $left_join . " " . $sql_conds;
if ($query = call_mysql_query($default_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $total_query = $data['count'];
        }
    }
}

$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
if (isset($_GET['page']) and is_digit($_GET['page'])) {
    $page_no = $_GET['page'] - 1;
    $start = $page_no * $query_limit;
}

$start_no = ($start >= $total_query) ? $total_query : $start;

## for deactivate status

$field_query = implode(',', $dbfield);
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where; // ichange based sa need

$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $left_join . " " . $sql_conds . " ORDER BY " . $orderby;
$limit = " LIMIT " . $start_no . "," . $query_limit;
$sql_limit = $default_query . ' ' . $limit;
if ($query = call_mysql_query($sql_limit)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = clean_array_null($data);
            $data = array_html($data);
            $data['id'] = encrypted_string($data['id']);

            // if (is_null($data['status'])) {
            //     $data['account_status'] = 'Deleted';
            // } else if ($data['status'] == '1') {
            //     $data['account_status'] = 'Deactivated';
            // } else if ($data['locked'] == '1') {
            //     $data['account_status'] = 'Locked';
            // } else if ($data['status'] == '0' && $data['locked'] == '0') {
            //     $data['account_status'] = 'Active';
            // }

            $to_encode[] = $data;
        }
        $output = json_encode(["last_page" => $pages, "data" => $to_encode, "total_record" => $total_query]);
        mysqli_free_result($query);
    } else {
        $output =  json_encode(["last_page" => 0, "data" => $to_encode, "total_record" => 0]);
    }
} else {
    $output =  json_encode(["last_page" => 0, "data" => $to_encode, "total_record" => 0]);
}

echo $output; ## output
exit();
