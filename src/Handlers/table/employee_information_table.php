<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';

# Server Execution Limits & Headers
set_time_limit(60);
ini_set('memory_limit', '256M');
header('Content-Type: application/json; charset=utf-8');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
# ===================================================================================

/**
 * Ensures null values in array are converted to empty strings safely.
 */
function clean_array_null(array $array): array
{
    return array_map(static function ($value) {
        return $value ?? "";
    }, $array);
}

// Close session writes early to prevent lock contention across concurrent AJAX requests
if (isset($session_class) && method_exists($session_class, 'session_close')) {
    $session_class->session_close();
}

// Enforce AJAX-only access
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    if (defined('HTTP_401')) {
        include HTTP_401;
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized access"]);
    }
    exit();
}

// Authentication Check
$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth'] ?? '');
if ($g_user_role !== "ADMIN" && $system_auth_login !== $g_public_key) {
    echo json_encode(["last_page" => 1, "data" => [], "total_record" => 0]);
    exit();
}

$query_limit = defined('QUERY_LIMIT') ? QUERY_LIMIT : 20;
$table_name  = "employee AS tbl_e";
$pages       = 0;
$start       = 0;
$sql_where_array = array();
$to_encode   = array();
$total_query = 0;
$orderby     = "tbl_e.id DESC";

// Field Definitions (Replaced invalid 'tbl_u.name' with dynamic SQL expression)
$dbfield = array(
    'tbl_e.*',
    'tbl_u.first_name',
    'tbl_u.middle_name',
    'tbl_u.last_name',
    'tbl_u.suffix',
    "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, '')) AS name",
    'tbl_u.email',
    'tbl_u.personal_email'
);

// Explicit Tabulator Field -> Database Column / Expression Mapping
$column_aliases = array(
    'id'                       => 'tbl_e.id',
    'user_id'                  => 'tbl_e.user_id',
    'name'                     => "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, ''))",
    'first_name'               => 'tbl_u.first_name',
    'last_name'                => 'tbl_u.last_name',
    'suffix'                   => 'tbl_u.suffix',
    'employee_id'              => 'tbl_e.employee_id',
    'service_status'           => 'tbl_e.service_status',
    'personnel_classification' => 'tbl_e.personnel_classification',
    'employment_status'        => 'tbl_e.employment_status',
    'employment_basis'         => 'tbl_e.employment_basis',
    'position'                 => 'tbl_e.position',
    'office_id'                => 'tbl_e.office_id',
    'department_id'            => 'tbl_e.department_id',
    'room_id'                  => 'tbl_e.room_id',
    'flag_status'              => 'tbl_e.flag_status',
    'flag_update'              => 'tbl_e.flag_update',
    'date_modify'              => 'tbl_e.date_modify',
    'email'                    => 'tbl_u.email',
    'personal_email'           => 'tbl_u.personal_email'
);

// Directly JOIN physical users table
$left_join = ' LEFT JOIN users AS tbl_u ON tbl_u.id = tbl_e.user_id';

// Parse Raw Inputs (Supports GET arrays, strings, or JSON payloads)
$raw_filters = $_GET['filters'] ?? $_GET['filter'] ?? [];
if (is_string($raw_filters)) {
    $raw_filters = json_decode($raw_filters, true) ?? [];
}

$raw_sorters = $_GET['sorters'] ?? $_GET['sort'] ?? [];
if (is_string($raw_sorters)) {
    $raw_sorters = json_decode($raw_sorters, true) ?? [];
}

// --- GLOBAL SEARCH HANDLER ---
if (isset($_GET['search']) && trim((string)$_GET['search']) !== '') {
    $search_val = escape($db_connect, trim($_GET['search']));
    $sql_where_array[] = "(CONCAT_WS(' ', tbl_u.first_name, tbl_u.middle_name, tbl_u.last_name) LIKE '%{$search_val}%' OR tbl_e.employee_id LIKE '%{$search_val}%' OR tbl_u.email LIKE '%{$search_val}%' OR tbl_u.personal_email LIKE '%{$search_val}%' OR tbl_e.position LIKE '%{$search_val}%')";
}

// --- COLUMN FILTERS HANDLER ---
if (!empty($raw_filters) && is_array($raw_filters)) {
    foreach ($raw_filters as $filter) {
        if (!isset($filter['field'])) {
            continue;
        }

        $field = trim($filter['field']);
        $value = $filter['value'] ?? '';

        if (is_array($value)) {
            $value = implode(',', array_filter($value));
        }

        $value = trim((string)$value);
        if ($value === '') {
            continue;
        }

        // Global Search Payload inside filter array
        if ($field === 'global' || $field === 'search') {
            $escaped_global = escape($db_connect, $value);
            $sql_where_array[] = "(CONCAT_WS(' ', tbl_u.first_name, tbl_u.middle_name, tbl_u.last_name) LIKE '%{$escaped_global}%' OR tbl_e.employee_id LIKE '%{$escaped_global}%' OR tbl_u.email LIKE '%{$escaped_global}%' OR tbl_u.personal_email LIKE '%{$escaped_global}%' OR tbl_e.position LIKE '%{$escaped_global}%')";
            continue;
        }

        // Standard mapped field filters
        if (array_key_exists($field, $column_aliases)) {
            $escaped_val = escape($db_connect, $value);
            $col_ref     = $column_aliases[$field];

            if ($field === "name") {
                $sql_where_array[] = "CONCAT_WS(' ', tbl_u.first_name, tbl_u.middle_name, tbl_u.last_name) LIKE '%" . $escaped_val . "%'";
                continue;
            }

            // Exact match for dropdown code selections
            if (in_array($field, ["service_status", "personnel_classification", "employment_status", "employment_basis", "office_id", "department_id", "room_id", "flag_status"], true)) {
                $sql_where_array[] = "{$col_ref} = '{$escaped_val}'";
                continue;
            }

            // LIKE search for standard text entries
            $sql_where_array[] = "{$col_ref} LIKE '%{$escaped_val}%'";
        }
    }
}

$sql_where = !empty($sql_where_array) ? 'WHERE ' . implode(' AND ', $sql_where_array) : '';

// --- SORTING HANDLER ---
if (!empty($raw_sorters) && is_array($raw_sorters)) {
    $sorter = $raw_sorters[0] ?? null;
    $valid_dirs = array('asc', 'desc');

    if ($sorter && isset($sorter['field'], $sorter['dir'])) {
        $sort_field = trim($sorter['field']);
        $sort_dir   = strtolower(trim($sorter['dir']));

        if (array_key_exists($sort_field, $column_aliases) && in_array($sort_dir, $valid_dirs, true)) {
            $col_ref = $column_aliases[$sort_field];
            $orderby = $col_ref . ' ' . strtoupper($sort_dir);
        }
    }
}

// Pagination Limit
if (isset($_GET['size']) && is_numeric($_GET['size'])) {
    $query_limit = ((int)$_GET['size'] > 0) ? (int)$_GET['size'] : $query_limit;
}

// Count Total Records Query
$count_query = "SELECT COUNT(tbl_e.id) as count FROM {$table_name} {$left_join} {$sql_where}";

if ($query = call_mysql_query($count_query)) {
    if ($data = call_mysql_fetch_array($query)) {
        $total_query = (int)$data['count'];
    }
    if (function_exists('mysqli_free_result')) {
        @mysqli_free_result($query);
    }
}

$pages = ($total_query === 0) ? 1 : (int)ceil($total_query / $query_limit);
$page_no = (isset($_GET['page']) && is_numeric($_GET['page'])) ? max(0, (int)$_GET['page'] - 1) : 0;
$start = $page_no * $query_limit;
$start_no = ($start >= $total_query) ? 0 : $start;

// Main Data Fetch Query
$field_query = implode(',', $dbfield);
$data_query = "SELECT {$field_query} FROM {$table_name} {$left_join} {$sql_where} ORDER BY {$orderby} LIMIT {$start_no}, {$query_limit}";

if ($query = call_mysql_query($data_query)) {
    if (call_mysql_num_rows($query) > 0) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = clean_array_null($data);
            if (function_exists('array_html')) {
                $data = array_html($data);
            }

            // Secure Encrypted Key Handling
            if (function_exists('encrypted_string')) {
                $data['id']      = encrypted_string($data['id']);
                $data['user_id'] = encrypted_string($data['user_id']);
            }

            $to_encode[] = $data;
        }
        if (function_exists('mysqli_free_result')) {
            @mysqli_free_result($query);
        }
    }
}

echo json_encode([
    "last_page"    => $pages,
    "data"         => $to_encode,
    "total_record" => $total_query
]);
exit();