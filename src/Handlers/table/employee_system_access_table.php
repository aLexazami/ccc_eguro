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

## System Acccess
$system_access = isset(ROLE_PERMISSION[$g_user_role]) ? $helper->filterSystemAccess(SYSTEM_ACCESS, ROLE_PERMISSION[$g_user_role]) : array();

$query_limit = QUERY_LIMIT;

$table_name = "system_access AS tbl_access";
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

$dbfield = array('tbl_access.*', 'tbl_u.first_name', 'tbl_u.middle_name', 'tbl_u.last_name', 'tbl_u.suffix', 'tbl_u.name', 'tbl_u.email', 'tbl_u.personal_email', 'tbl_l.login_id', 'tbl_l.username', 'tbl_l.status', 'tbl_l.locked', 'tbl_l.date_username', 'tbl_l.date_password', 'tbl_e.employee_id', 'tbl_e.personnel_classification', 'tbl_e.employment_status', 'tbl_e.employment_basis', 'tbl_e.service_status');

// Field Definitions (Replaced invalid 'tbl_u.name' with dynamic SQL expression)
$dbfield = array(
    'tbl_access.*',

    'tbl_u.first_name',
    'tbl_u.middle_name',
    'tbl_u.last_name',
    'tbl_u.suffix',
    "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, '')) AS name",
    'tbl_u.email',
    'tbl_u.personal_email',

    'tbl_l.login_id',
    'tbl_l.username',
    'tbl_l.status',
    'tbl_l.locked',
    'tbl_l.date_username',
    'tbl_l.date_password',

    'tbl_e.employee_id',
    'tbl_e.personnel_classification',
    'tbl_e.employment_status',
    'tbl_e.employment_basis',
    'tbl_e.service_status',
);

// Explicit Tabulator Field -> Database Column / Expression Mapping
$column_aliases = array(
    'id'                       => 'tbl_access.id',
    'user_id'                  => 'tbl_access.user_id',
    'ref_id'                   => 'tbl_access.ref_id',
    'system_type'              => 'tbl_access.system_type',
    'system_role'              => 'tbl_access.system_role',
    'access_tag'               => 'tbl_access.access_tag',
    'employee_update'          => 'tbl_access.employee_update',
    'student_update'           => 'tbl_access.student_update',
    'flag_access'              => 'tbl_access.flag_access',
    'date_modify'              => 'tbl_access.date_modify',

    'name'                     => "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, ''))",
    'first_name'               => 'tbl_u.first_name',
    'last_name'                => 'tbl_u.last_name',
    'suffix'                   => 'tbl_u.suffix',
    'email'                    => 'tbl_u.email',
    'personal_email'           => 'tbl_u.personal_email',

    // 'login_id'           => 'tbl_l.login_id',
    'username'           => 'tbl_l.username',
    'status'             => 'tbl_l.status',
    'locked'             => 'tbl_l.locked',
    // 'date_username'      => 'tbl_l.date_username',
    // 'date_password'      => 'tbl_l.date_password',

    'employee_id'              => 'tbl_e.employee_id',
    'service_status'           => 'tbl_e.service_status',
    'personnel_classification' => 'tbl_e.personnel_classification',
    'employment_status'        => 'tbl_e.employment_status',
    'employment_basis'         => 'tbl_e.employment_basis',
    'position'                 => 'tbl_e.position',
);

$dborig = array('flag_access', 'system_type', 'system_role', 'system_role_access', 'employee_id', 'name', 'username', 'status', 'locked', 'service_status', 'personnel_classification', 'employment_status', 'employment_basis', 'position');

$left_join = "";
$left_join .= 'LEFT JOIN (SELECT id, first_name, middle_name, last_name, suffix, email, personal_email, CONCAT_WS(" ",first_name,NULLIF(middle_name, ""),last_name,NULLIF(suffix, "")) AS name FROM users) AS tbl_u ON tbl_u.id = tbl_access.user_id';
$left_join .= ' LEFT JOIN (SELECT id AS login_id, user_id, username, password, status, locked, date_username, date_password FROM login) AS tbl_l ON tbl_l.user_id = tbl_access.user_id';
$left_join .= ' LEFT JOIN (SELECT user_id, employee_id, personnel_classification, employment_status, employment_basis, service_status, position FROM employee) AS tbl_e ON tbl_e.user_id = tbl_access.user_id';

// Parse Raw Inputs (Supports GET arrays, strings, or JSON payloads)
$raw_filters = $_GET['filters'] ?? $_GET['filter'] ?? [];
if (is_string($raw_filters)) {
    $raw_filters = json_decode($raw_filters, true) ?? [];
}

$raw_sorters = $_GET['sorters'] ?? $_GET['sort'] ?? [];
if (is_string($raw_sorters)) {
    $raw_sorters = json_decode($raw_sorters, true) ?? [];
}

// --- HELPER: System Role Condition Builder ---
$get_role_conditions = function ($search_value) use ($system_access, $db_connect) {
    $search_role = strtoupper(trim($search_value));
    $search_role = str_replace(' ', '_', $search_role);

    if (empty($search_role) || !isset($system_access) || !is_array($system_access)) {
        return [];
    }

    $combined_roles = [];
    foreach ($system_access as $system_key => $system) {
        if (isset($system['role']) && is_array($system['role'])) {
            foreach ($system['role'] as $role_id => $role_name) {
                $norm_role_name = strtoupper(trim($role_name));
                $combined_roles[$norm_role_name][$system_key] = $role_id;
            }
        }
    }

    $role_conditions = [];
    foreach ($combined_roles as $role_name => $systems) {
        if (strpos($role_name, $search_role) !== false) {
            foreach ($systems as $system_name => $role_id) {
                $escaped_sys  = escape($db_connect, $system_name);
                $escaped_role = escape($db_connect, $role_id);
                $role_conditions[] = "(tbl_access.system_type = '" . $escaped_sys . "' AND tbl_access.system_role = '" . $escaped_role . "')";
            }
        }
    }

    return $role_conditions;
};


// --- GLOBAL SEARCH HANDLER ---
if (isset($_GET['search']) && trim((string)$_GET['search']) !== '') {
    $raw_search = trim($_GET['search']);
    $search_val = escape($db_connect, $raw_search);

    // 1. Text & Partial Matches
    $global_conditions = [
        "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, '')) LIKE '%{$search_val}%'",
        "tbl_u.email LIKE '%{$search_val}%'",
        "tbl_u.personal_email LIKE '%{$search_val}%'",
        "tbl_l.username LIKE '%{$search_val}%'",
        "tbl_e.employee_id LIKE '%{$search_val}%'",
        "tbl_e.personnel_classification LIKE '%{$search_val}%'",
        "tbl_e.employment_status LIKE '%{$search_val}%'",
        "tbl_e.employment_basis LIKE '%{$search_val}%'",
        "tbl_e.position LIKE '%{$search_val}%'",
        "tbl_access.system_type LIKE '%{$search_val}%'",
    ];

    // Status Maps Logic (Text label / ID translation)
    $status_configs = [
        'flag_access'    => SYSTEM_STATUS ?? [],
        'status'         => ACCOUNT_STATUS ?? [],
        'locked'         => LOGIN_STATUS ?? [],
        'service_status' => EMPLOYMENT_SERVICE ?? [],
    ];

    foreach ($status_configs as $field_key => $status_map) {
        $col_ref = $column_aliases[$field_key] ?? $field_key;

        // Exact match if the user typed the key ID directly
        if (array_key_exists($raw_search, $status_map)) {
            $global_conditions[] = "{$col_ref} = '{$search_val}'";
        }

        // Fuzzy match if user typed status label text (e.g., "Active", "Locked")
        foreach ($status_map as $key => $label) {
            if (stripos((string)$label, $raw_search) !== false) {
                $escaped_key = escape($db_connect, $key);
                $global_conditions[] = "{$col_ref} = '{$escaped_key}'";
            }
        }
    }

    // System Role Access Logic
    $role_conds = $get_role_conditions($raw_search);

    if (!empty($role_conds)) {
        if (is_array($role_conds)) {
            $global_conditions[] = "(" . implode(" OR ", $role_conds) . ")";
        } elseif (is_string($role_conds) && trim($role_conds) !== '') {
            $global_conditions[] = $role_conds;
        }
    }

    // Filter out any empty conditions to prevent broken SQL
    $global_conditions = array_filter($global_conditions, fn($cond) => trim((string)$cond) !== '');

    if (!empty($global_conditions)) {
        $sql_where_array[] = "(" . implode(" OR ", $global_conditions) . ")";
    }
}


// --- COLUMN FILTERS HANDLER ---
if (!empty($raw_filters) && is_array($raw_filters)) {
    foreach ($raw_filters as $filter) {
        if (!isset($filter['field'])) continue;

        $field = trim($filter['field']);
        $value = $filter['value'] ?? '';

        if (is_array($value)) {
            $value = implode(',', array_filter($value));
        }

        $value = trim((string)$value);
        if ($value === '') {
            continue;
        }

        // 1. Global Search Payload inside filter array
        if ($field === 'global' || $field === 'search') {
            $escaped_global = escape($db_connect, $value);
            $role_conditions = $get_role_conditions($escaped_global);

            $global_conditions = [
                "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, '')) LIKE '%{$escaped_global}%'",

                "tbl_u.email LIKE '%{$escaped_global}%'",
                "tbl_u.personal_email LIKE '%{$escaped_global}%'",

                "tbl_l.username LIKE '%{$escaped_global}%'",

                "tbl_e.employee_id LIKE '%{$escaped_global}%'",
                "tbl_e.position LIKE '%{$escaped_global}%'",
                "tbl_access.system_type = '{$escaped_global}'",
                $role_conditions
            ];

            $sql_where_array[] = "(" . implode(" OR ", $global_conditions) . ")";
            continue;
        }

        // 2. Custom Role Mapping Filter Handler
        if ($field === "system_role_access") {
            $role_conditions = $get_role_conditions($value);

            if (!empty($role_conditions)) {
                $sql_where_array[] = "(" . implode(" OR ", $role_conditions) . ")";
            } else {
                $sql_where_array[] = "1 = 0";
            }
            continue;
        }

        // 3. Status Mapping Filters
        if (in_array($field, ["flag_access", "status", "locked", "service_status"], true)) {
            $_status = [
                'flag_access'    => SYSTEM_STATUS ?? [],
                'status'         => ACCOUNT_STATUS ?? [],
                'locked'         => LOGIN_STATUS ?? [],
                'service_status' => EMPLOYMENT_SERVICE ?? [],
            ];

            $col_ref = $column_aliases[$field] ?? $field;

            // Match by array label; fallback to key checking
            $matched_key = array_search($value, $_status[$field], false);

            if ($matched_key !== false) {
                $sql_where_array[] = "{$col_ref} = '" . escape($db_connect, $matched_key) . "'";
            } else {
                $sql_where_array[] = "{$col_ref} = '" . escape($db_connect, $value) . "'";
            }
            continue;
        }

        // 4. Exact Match Classification / ID Dropdowns
        if (in_array($field, ["personnel_classification", "employment_status", "employment_basis"], true)) {
            $col_ref = $column_aliases[$field] ?? $field;
            $sql_where_array[] = "{$col_ref} = '" . escape($db_connect, $value) . "'";
            continue;
        }

        // 5. Standard Mapped Field Filters
        if (array_key_exists($field, $column_aliases)) {
            $escaped_val = escape($db_connect, $value);
            $col_ref     = $column_aliases[$field];

            if ($field === "name") {
                $sql_where_array[] = "CONCAT_WS(' ', NULLIF(tbl_u.first_name, ''), NULLIF(tbl_u.middle_name, ''), NULLIF(tbl_u.last_name, ''), NULLIF(tbl_u.suffix, '')) LIKE '%{$escaped_val}%'";
                continue;
            }

            // Default LIKE search for text fields
            $sql_where_array[] = "{$col_ref} LIKE '%{$escaped_val}%'";
        }
    }
}

// Compile final SQL WHERE clause string
$sql_where = !empty($sql_where_array) ? implode(' AND ', $sql_where_array) : '';

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
            $data['user_id'] = encrypted_string($data['user_id']);
            $data['login_id'] = encrypted_string($data['login_id']);
            $data['system_role_access'] = str_replace('_', ' ', SYSTEM_ACCESS[$data['system_type']]['role'][$data['system_role']]);

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
