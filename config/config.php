<?php
# ======================================================================
# CORE PATH DEFAULTS & SECURE ERROR LOGGING
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__));

## Secure Diagnostic Logger Path Dispatching
## Kept outside the public web root directory space to prevent sensitive data leaks
$log_directory = DOMAIN_PATH . "/storage/logs";
if (!is_dir($log_directory)) {
    mkdir($log_directory, 0755, true);
}
ini_set("error_log", $log_directory . "/php-error.log");

# ======================================================================
# RUNTIME ENVIRONMENT DETECTOR 
$envFile = DOMAIN_PATH . '/.env'; // Anchored securely in your private env folder
$env = 'DEV'; // Safe baseline development state configuration metric

if (file_exists($envFile)) {
    // Read the environment variable seeds array into memory, skipping empty lines
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // COMMENT CHECK: Skip processing lines that act as standard comments (#)
        if (strpos($line, '#') === 0) {
            continue;
        }

        // Extract key-value mappings split by the assignment operator (=)
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Inject variables securely straight into the PHP runtime environment block
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

## Evaluate environment parameters parsing results
$configured_flag = strtoupper($_ENV['SYSTEM_FLAG'] ?? '');
if (in_array($configured_flag, ['DEV', 'PROD'])) {
    $env = $configured_flag;
} else {
    ## Fallback automated detection check if .env is missing or unreadable
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';

    // Check for localhost or local network IP ranges (127.0.0.1, 10.x.x.x, 192.168.x.x, 172.16-31.x.x)
    $is_local = ($server_name === 'localhost' || $server_addr === '127.0.0.1') || filter_var($server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

    $env = $is_local ? 'DEV' : 'PROD';
}

## Map permanent unalterable environment runtime flag metrics
define('SYSTEM_FLAG', $env);

# ======================================================================
# ERROR DISPLAY & REPORTING CONFIGURATION
if (SYSTEM_FLAG === 'DEV') {
    // Enable full on-screen error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    // Suppress errors on screen in production to prevent path exposure
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

# ======================================================================
# GLOBAL CONFIGURATIONS & COMPATIBILITY LAYER
$before_memory = 0;
$config['filesize_limit'] = 20971520; // 20MB Max payload upload file limit
$close_conn = true;
define('CSV_SIZE', (50 * 1024 * 1024));
define('QUERY_LIMIT', 20);

## Datetime zone configuration setting parameters
define("DEFAULT_TIMEZONE", 'Asia/Manila');
ini_set('date.timezone', DEFAULT_TIMEZONE);
date_default_timezone_set(DEFAULT_TIMEZONE);

define('YEAR', date('Y'));
define('MONTH', date('m'));
define('DAY', date('d'));
define('DATE_NOW', date('Y-m-d'));
define('TIME_NOW', date('H:i:s'));
define("DATE_TIME", DATE_NOW . " " . TIME_NOW);

define("LANG", 'en');
define("META_AUTHOR", 'MISD Team');
define("META_DESC", 'e-GURO++ Platform');

## Institutional data profiling constants
define('SCHOOL_NAME', 'City College of Calamba');
define('SCHOOL_ACRONYM', 'CCC');
define('SCHOOL_ADDRESS', 'Calamba City, Laguna');

define('SYSTEM_NAME', 'e-GURO++');
define('SYSTEM_SUB_NAME', 'Management Information System Department');
define('SYSTEM_TEAM', 'MISD Team');
define('YEAR_CREATED', '2023');
define('PAGE_TITLE', 'e-GURO++');
define('PAGE_SUB_TITLE', 'e-GURO++');

# VERSIONING METRIC CONSTANT
define('FILE_VERSION', getenv('FILE_VERSION'));

# Page Name
$page_name = page_name();
define("ACTIVE_PAGE", $page_name);

# ======================================================================
# SYSTEM ACCESS NAME
define("SYSTEM_ACCESS_NAME", getenv('SYSTEM_ACCESS_NAME')) ?: 'E-GURO++';

# ======================================================================
# SYSTEM HUB ROUTING DEFINITIONS
# [DEV]
$local_folder     = getenv('LOCAL_SUBFOLDER')       ?: 'e_e_dev_eguro';
$api_local_folder = getenv('API_LOCAL_SUBFOLDER')   ?: 'e_e_dev_eguro';
# [PROD]
$live_domain      = getenv('PRODUCTION_DOMAIN')     ?: 'eguro.ccc.edu.ph';
$api_live_domain  = getenv('API_PRODUCTION_DOMAIN') ?: 'eguro.ccc.edu.ph';

# SYSTEM URLs
$url     = system_url($local_folder, $live_domain);
$api_url = system_url($api_local_folder, $api_live_domain);

define("BASE_URL", $url);
define("API_URL", $api_url);

# Define Application URLs (Production vs. Staging/Dev Environment)
$is_live = ($live_domain === "ccc.edu.ph");

define("EGURO_URL", system_url("dev_e_eguro", $is_live ? "eguro.ccc.edu.ph" : "ccc.edu.ph/eguro"));
define("APP_URL",   system_url("dev_e_app", $is_live ? "app.ccc.edu.ph" : "ccc.edu.ph/app"));

# SYSTEM ACCESS CONFIGURATIONS
/**
 * Configures the system access privileges and role mappings for the primary application (e-GURO++).
 * This file serves as the central source of truth for federated system access. 
 * Any modifications to the access schemas, roles, or endpoints within this main system 
 * must be synchronized and replicated across all subordinate applications (e.g., CCC Website) 
 * to maintain consistent authentication and authorization across the environment.
 * 
 */
$system_access = [
    'E-GURO++' => [
        'name'       => 'e-GURO++',
        'short_name' => 'e-GURO++',
        'role'       => ['1' => 'ADMIN', '2' => 'REGISTRAR', '3' => 'VPAA', '4' => 'USER'],
        'link'       => [
            'main'   => BASE_URL . 'home',
            'second' => BASE_URL . 'app/index.php',
            'logout' => BASE_URL . 'logout',
            'create_user' => '',
            'update_user' => '',
        ],
        'access'     => [],
        'max_user'   => 1,
        'auth'       => 'e_eguro_auth_login'
    ],

    'E-APP' => [
        'name'       => 'e-Application',
        'short_name' => 'e-APP',
        'role'       => ['1' => 'ADMIN', '2' => 'ADMIN_STAFF'], # [change based on need]
        'admin_link' => APP_URL,
        'user_link'  => APP_URL,
        'link'       => system_links(APP_URL),
        'access'     => [],
        'max_user'   => 1,
        'auth'       => 'e_app_auth_login'
    ],
];
define('SYSTEM_ACCESS', $system_access);
define('SYSTEM_FALLBACK', 'E-GURO++');

function system_links($base_url, $include_user_mgmt = true)
{
    $links = [
        'main'   => $base_url . 'api/system-login',
        'second' => '',
        'logout' => $base_url . 'logout',
    ];

    if ($include_user_mgmt) {
        $links += [
            'system_access' => $base_url . 'api/system-access',
            'create_user'   => $base_url . 'user-management/create',
            'update_user'   => $base_url . 'user-management/update',
            'bulk_user'     => $base_url . 'user-management/bulk',
            'add_account'   => $base_url . 'user-management/add',
        ];
    }
    return $links;
}

# ======================================================================
# ROLE PERMISSION [for adding access]
$role_permission = [
    'ADMIN' => (SYSTEM_FLAG === "DEV") ? [
        'E-GURO++'    => ['1', '2',],
        'E-APP'       => ['1', '2'],
    ] : [
        'E-GURO++'    => ['1', '2',],
        'E-APP'       => ['1', '2'],
    ],
];
define('ROLE_PERMISSION', $role_permission);

# ======================================================================
# SMTP NETWORK MAIL CONFIGURATIONS
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', '587');
define('SMTP_DEBUG', 0); // Int parsing layer for explicit debug evaluation flags
define('SMTP_SECURE', 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROMEMAIL', 'donotreply@ccc.edu.ph');
define('SMTP_FROMNAME', SYSTEM_NAME);
define('SMTP_REPLYTO', 'donotreply@ccc.edu.ph');
define('SMTP_REPLYNAME', 'DONOTREPLY');

# ======================================================================
# STRUCTURAL CORE ENGINE FILE INCLUDE PATHS
define('SRC_PATH', DOMAIN_PATH . '/src/');

# API Files
define('API_HELPER', SRC_PATH . 'Api/ApiHelper.php');

# DATABASE Connection
define('CONNECT_PATH', SRC_PATH . 'Database/connect.php');

# COMMON Files
define('CL_SESSION_PATH',   SRC_PATH . 'Common/cl_session.php');
define('GLOBAL_FUNC',       SRC_PATH . 'Common/global_function.php');
define('APP_CONTEXT',       SRC_PATH . 'Common/AppContext.php');
define('UPLOAD_HANDLER',    SRC_PATH . 'Common/UploaderHandler.php');
define('DATA_HELPER',       SRC_PATH . 'Common/DataHelper.php');

define('VALIDATOR_PATH',    SRC_PATH . 'Common/validator.php');
define('ALERT_SESSION',     SRC_PATH . 'Common/alert_session.php');

# CONTROLLERS Files 
define('ISLOGIN',     SRC_PATH . 'Controllers/IsLogin.php');
define('API_CONNECT', SRC_PATH . 'Controllers/ApiConnect.php');
define('HELPER',      SRC_PATH . 'Controllers/Helper.php');

# LAYOUT Files
define('LAYOUT_PATH',           SRC_PATH . 'Views/Layouts/');
# MAIN Layouts
define('META_DATA_PATH',   LAYOUT_PATH . 'app/meta_data.php');
define('LINK_DATA_PATH',   LAYOUT_PATH . 'app/link.php');
define('SCRIPT_DATA_PATH', LAYOUT_PATH . 'app/script.php');
define('HEADER_PATH',      LAYOUT_PATH . 'app/header.php');
define('FOOTER_PATH',      LAYOUT_PATH . 'app/footer.php');

define('SIDEBAR_PATH',     LAYOUT_PATH . 'app/sidebar.php');
define('TOP_BAR_PATH',     LAYOUT_PATH . 'app/top_bar.php');
# ADMIN Layouts
define('ADMIN_META_DATA_PATH',   LAYOUT_PATH . 'admin/meta_data.php');
define('ADMIN_LINK_PATH',        LAYOUT_PATH . 'admin/link.php');
define('ADMIN_SCRIPT_PATH',      LAYOUT_PATH . 'admin/script.php');
define('ADMIN_HEADER_PATH',      LAYOUT_PATH . 'admin/header.php');
define('ADMIN_SIDEBAR_PATH',     LAYOUT_PATH . 'admin/sidebar.php');
define('ADMIN_FOOTER_PATH',      LAYOUT_PATH . 'admin/footer.php');
define('ADMIN_PAGE_HEADER_PATH', LAYOUT_PATH . 'admin/page_header.php');

# ERROR PAGE Files
define("HTTP_401", SRC_PATH . "Views/Errors/401.php");
define("HTTP_404", SRC_PATH . "Views/Errors/404.php");

# ======================================================================
# PUBLIC BASE path directories
define('BASE_PUBLIC_PATH', BASE_URL . 'public/');

# ASSETS BASE path directories
define('BASE_ASSETS_PATH', BASE_PUBLIC_PATH . 'assets/');

define('BASE_VENDOR_ASSETS_PATH', BASE_ASSETS_PATH . 'vendor/');
define('BASE_MAIN_ASSETS_PATH',   BASE_ASSETS_PATH . 'main/');
define('BASE_ADMIN_ASSETS_PATH',  BASE_ASSETS_PATH . 'admin/');

# ======================================================================
# UPLOAD FILE SYSTEM PATH DIRECTORIES (Server Paths)

# Base Server Directory
define("UPLOAD_DOMAIN_PATH",  DOMAIN_PATH . '/public/upload/');
define("STORAGE_DOMAIN_PATH", DOMAIN_PATH . '/storage/');

# Core Directories
define("UPLOAD_GUIDE_PATH", UPLOAD_DOMAIN_PATH . 'guide/');
define("UPLOAD_IMAGE_PATH", UPLOAD_DOMAIN_PATH . 'images/');

define("STORAGE_FILE_PATH", STORAGE_DOMAIN_PATH . 'files/');
define("STORAGE_LOGS_PATH", STORAGE_DOMAIN_PATH . 'logs/');
define("STORAGE_CSV_PATH",  STORAGE_DOMAIN_PATH . 'csv/');

# Image Directories
define("UPLOAD_PROFILE_PATH", UPLOAD_IMAGE_PATH . 'profile/');
define("UPLOAD_COVER_PATH",   UPLOAD_IMAGE_PATH . 'cover/');

# Profile Image Subdirectories
define("UPLOAD_PROFILE_USER_PATH", UPLOAD_PROFILE_PATH . "user/");
define("UPLOAD_PROFILE_EMP_PATH",  UPLOAD_PROFILE_PATH . "employee/");
define("UPLOAD_PROFILE_STD_PATH",  UPLOAD_PROFILE_PATH . "student/");

# Cover Image Subdirectories
define("UPLOAD_COVER_USER_PATH", UPLOAD_COVER_PATH . "user/");
define("UPLOAD_COVER_EMP_PATH",  UPLOAD_COVER_PATH . "employee/");
define("UPLOAD_COVER_STD_PATH",  UPLOAD_COVER_PATH . "student/");

# ======================================================================
# UPLOAD BASE PATH DIRECTORIES (Base URL References)

# Base URL
define("UPLOAD_BASE_PATH",  BASE_URL . 'public/upload/');
define("STORAGE_BASE_PATH", BASE_URL . 'storage/');

# Core Base URLs
define("BASE_STORAGE_FILE_PATH", STORAGE_BASE_PATH . 'files/');
define("BASE_STORAGE_LOGS_PATH", STORAGE_BASE_PATH . 'logs/');
define("BASE_STORAGE_CSV_PATH",  STORAGE_BASE_PATH . 'csv/');

# Image Base URLs
define("BASE_UPLOAD_IMAGE_PATH", UPLOAD_BASE_PATH . 'images/');

# Logo Base URLs
define("BASE_UPLOAD_LOGO_PATH", BASE_UPLOAD_IMAGE_PATH . 'logo/');

# Profile Image Base URLs
define("BASE_UPLOAD_PROFILE_USER_PATH", BASE_UPLOAD_IMAGE_PATH . "profile/user/");
define("BASE_UPLOAD_PROFILE_EMP_PATH",  BASE_UPLOAD_IMAGE_PATH . "profile/employee/");
define("BASE_UPLOAD_PROFILE_STD_PATH",  BASE_UPLOAD_IMAGE_PATH . "profile/student/");

# Cover Image Base URLs
define("BASE_UPLOAD_COVER_USER_PATH", BASE_UPLOAD_IMAGE_PATH . "cover/user/");
define("BASE_UPLOAD_COVER_EMP_PATH",  BASE_UPLOAD_IMAGE_PATH . "cover/employee/");
define("BASE_UPLOAD_COVER_STD_PATH",  BASE_UPLOAD_IMAGE_PATH . "cover/student/");


# ======================================================================
# UPLOAD LOG FILE PATHS

define('IMPORT_USER_LOG',                   STORAGE_LOGS_PATH . 'summary_import_user.log');
define('IMPORT_EMPLOYEE_LOG',               STORAGE_LOGS_PATH . 'summary_import_employee.log');
define('IMPORT_STUDENT_LOG',                STORAGE_LOGS_PATH . 'summary_import_student.log');
define('IMPORT_EMPLOYEE_SYSTEM_ACCESS_LOG', STORAGE_LOGS_PATH . 'summary_import_employee_system_access.log');


# ======================================================================
# PUBLIC SECTOR WEBPAGE ASSET TARGET DEFINITIONS

# Web Asset Base URL
define("UPLOAD_WEB_URL", BASE_URL . 'public/upload/');

# System Logos & Favicons
define("LOGO",          BASE_UPLOAD_LOGO_PATH . 'egpp-logo.png?v=' . FILE_VERSION);
define("DISPLAY_LOGO",  BASE_UPLOAD_LOGO_PATH . 'e-guro-display.png?v=' . FILE_VERSION);
define("FAVICON",       BASE_UPLOAD_LOGO_PATH . 'egpp-mobile.png?v=' . FILE_VERSION);
define("CCC_FAVICON",   BASE_UPLOAD_LOGO_PATH . 'ccc-favicon.ico?v=' . FILE_VERSION);

# White Variant Logos
define("WHITE_LOGO",         BASE_UPLOAD_LOGO_PATH . 'egpp-logo-white.png?v=' . FILE_VERSION);
define("WHITE_DISPLAY_LOGO", BASE_UPLOAD_LOGO_PATH . 'egpp-display-white.png?v=' . FILE_VERSION);

# CCC Logo
define("CCC_LOGO",  BASE_UPLOAD_LOGO_PATH . 'ccc-display.png?v=' . FILE_VERSION);
define("CCC_WHITE_LOGO",  BASE_UPLOAD_LOGO_PATH . 'ccc-display-white.png?v=' . FILE_VERSION);

# System Module Display Logos
define("APP_DISPLAY_LOGO",  BASE_UPLOAD_LOGO_PATH . 'ccc-display-white.png?v=' . FILE_VERSION);

# Static Asset Paths
define('IMG_DEFAULT', BASE_UPLOAD_IMAGE_PATH . 'profile-img.png');

# ======================================================================
# PLATFORM LAYER ENCRYPTION SEEDS & SESSIONS

# DYNAMIC SESSION CONFIGURATION
$env_session_name = getenv('SESSION_NAME');
$default_session = !empty($env_session_name) ? $env_session_name : ((defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV') ? 'e_e_dev_eguro_session' : 'eguro_session');

# Dynamically auto-detect HTTPS connection
$is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
define('DEFAULT_SESSION', $default_session);
define('SESSION_CONFIG', array(
    'name'       => DEFAULT_SESSION,
    'path'       => '/',
    'domain'     => '',
    'secure'     => $is_secure,
    'bits'       => 4,
    'length'     => 32,
    'hash'       => 'sha256',
    'decoy'      => true,
    'min'        => 14400,   // Increase minimum TTL to 4 hour
    'max'        => 43200,   // Increase maximum TTL to 12 hours
    'expiration' => 43200,   // 12 hours expiration
    'debug'      => (defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV')
));

define('SALT', '895012342025TREWPOIUYT_');
define('MAX_HASHES_STRING', 15);
define('TIME_STRING', -1);

define('CRYPT_CIPHER', 'aes-256-cbc');
define('CRYPT_DIRTY', array("+", "/", "="));
define('CRYPT_CLEAN', array("_PLUS_", "_SLASH_", "_EQUALS_"));

# ======================================================================
# DEFAULT CLASSIFICATION
define("ACCOUNT_STATUS", ["0" => "Active", "1" => "Inactive", "2" => "Suspended"]);
define("LOGIN_STATUS", ["0" => "Active", "1" => "Locked"]);
define("SYSTEM_STATUS", ["0" => "Activated", "1" => "Deactivated"]);

define('SUFFIX', ['JR.', 'SR.', 'III', 'IV', 'V', 'VI']);
define('SEX', ['male', 'female']);
define('CIVIL_STATUS', ['single', 'married', 'widowed', 'separated', 'divorced', 'annulled', 'cohabiting']);

define("EMPLOYMENT_SERVICE", ["0" => "Active", "1" => "Suspended", "2" => "Resigned", "3" => "Retired", "4" => "Transferred", "5" => "End of Contract"]);
define('EMPLOYMENT_CLASSIFICATION', ['TEACHING PERSONNEL', 'NON-TEACHING PERSONNEL']);
define('EMPLOYMENT_STATUS', ['PERMANENT', 'CONTRACT OF SERVICE', 'JOB ORDER']);
define('EMPLOYMENT_BASIS', ['PART TIME', 'FULL TIME']);

# ======================================================================
# CSV TEMPLATE
define('USER_INFORMATION_TEMPLATE', 'guide_user_information_import.csv');
define('EMPLOYEE_INFORMATION_TEMPLATE', 'guide_employee_information_import.csv');
define('STUDENT_INFORMATION_TEMPLATE', 'guide_employee_information_import.csv');
define('EMPLOYEE_SYSTEM_ACCESS_TEMPLATE', 'guide_employee_system_import.csv');

# ======================================================================
# Added Define function remove if not needed

# User Access Name [change based on need]
define('ACCESS_NAME', ['ADMIN' => "Administrator", 'ADMIN_STAFF' => "Administrator Staff"]);
# ======================================================================

# ======================================================================
# DIAGNOSTIC SYSTEM PLATFORM ROUTINES
function ifexist_ini_set($func, $key)
{
    if (!function_exists('ini_set')) {
        return;
    }
    ini_set($func, $key);
}

function mem_convert($size)
{
    if ($size <= 0) {
        return '0 b';
    }
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    $i = floor(log($size, 1024));
    if ($i >= count($unit)) {
        $i = count($unit) - 1;
    }
    return round($size / pow(1024, $i), 2) . ' ' . $unit[$i];
}

function print_mem()
{
    $mem_usage = memory_get_usage();
    $mem_peak = memory_get_peak_usage();
    return 'The script is now using: <strong>' . mem_convert($mem_usage) . '</strong> of memory.<br>Peak usage: <strong>' . mem_convert($mem_peak) . '</strong> of memory.<br><br>';
}

function page_url()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

function page_name()
{
    return basename($_SERVER['PHP_SELF'] ?? '', ".php");
}

function get_protocol()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
}

function system_url($local_domain, $web_domain)
{
    $protocol = get_protocol();
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (defined('SYSTEM_FLAG') && SYSTEM_FLAG === 'DEV') {
        return $protocol . $host . '/' . $local_domain . '/';
    } else {
        return $protocol . $web_domain . '/';
    }
}
