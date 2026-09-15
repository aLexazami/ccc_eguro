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

## setup API connection
require_once API_HELPER;
$api = new Src\Api\ApiHelper($db_connect);

$csrf = new CSRF($session_class);

$token_1 = $csrf->validate('token_logout_form', $_POST['token_logout_form'] ?? '');
if (!$token_1) {
    $session_class->setValue('msg_error', "Invalid Auth-Token");
    header("Location: " . BASE_URL . "home");
    exit();
}

$response = array(
    'success' => false,
    'message' => 'Unknown error',
);

$fingerprint = $session_class->getValue('fingerprint');
$g_user_id = $session_class->getValue('user_id');

if (isset($_POST['logoutSubmit']) && $_POST['logoutSubmit'] === 'submitLogout') {
    $agents = isset($_POST['agents']) ? json_decode($_POST['agents'], true) : array();
    $public_key = "";
    $secret_key = "";
    $db_system_type = "";
    $db_system_role = "";
    $system_link = "";
    $process = array();
    $device = json_encode($agents);

    ## Check if the user_id is missing or not structural
    if (empty($g_user_id) || !is_digit($g_user_id)) {
        $session_class->setValue('msg_error', 'Invalid default data, please try again');
        header("Location: " . BASE_URL . "home");
        exit();
    }

    $num = 0;
    $default_query = "SELECT user_id, ref_id, system_type, system_role FROM system_access WHERE user_id = ? GROUP BY system_type";

    if ($stmt = mysqli_prepare($db_connect, $default_query)) {
        mysqli_stmt_bind_param($stmt, "i", $g_user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num > 0) {
            while ($data = mysqli_fetch_assoc($result)) {
                $db_system_type = $data['system_type'];
                $db_system_role = $data['system_role'];
                $ref_id = $data['ref_id'];

                if ($db_system_type !== 'E-GURO++') {
                    ## Get token hashes
                    $token_ids = $csrf->getHashes($db_system_type, TIME_STRING);

                    $public_key = '';
                    $secret_key = '';

                    ## Fetch external API configuration keys safely
                    $default_query_system = "SELECT system_type, public_key, secret_key FROM system_key WHERE system_type = '" . escape($db_connect, $db_system_type) . "' LIMIT 1";
                    if ($query_system = call_mysql_query($default_query_system)) {
                        if (call_mysql_num_rows($query_system) > 0) {
                            if ($data_system = call_mysql_fetch_array($query_system)) {
                                $public_key = $data_system['public_key'];
                                $secret_key = $data_system['secret_key'];
                            }
                        }
                    }

                    ## set keys
                    $api->setKeys($db_system_type);
                    
                    $api_url = SYSTEM_ACCESS[$db_system_type]['link']['logout'];
                    $get_ip = get_ip();

                    $system_access = array(
                        'system_access' => $db_system_type
                    );

                    $encoded_data = array(
                        'user_id' => $ref_id,
                        'token_ids' => json_encode($token_ids),
                        'action' => 'logout',
                        'ip' => $get_ip
                    );


                    $response_api = $api->curlRequest($api_url, $system_access, $api->encryptApiData(json_encode($encoded_data)));

                    if (empty($response_api)) {
                        error_log('Error API: No data received');
                        $process[] = 0;
                        continue;
                    }


                    $result_json = json_decode($response_api, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('Error API JSON Match: ' . json_last_error() . " DATA: " . $response_api);
                        $process[] = 0;
                        continue;
                    }

                    if (isset($result_json['error_msg'])) {
                        error_log($result_json['error_msg']);
                        $result_json['response_status'] = 0;
                        $process[] = 0;
                        continue;
                    }

                    if (isset($result_json['response_status']) && $result_json['response_status'] == 0) {
                        $process[] = 0;
                    } elseif (isset($result_json['response_status']) && $result_json['response_status'] == 1) {
                        $process[] = 1;
                    }
                }
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Database core login statement failure compilation: " . mysqli_error($db_connect));
        $session_class->setValue('msg_error', 'An internal connection engine compilation error occurred.');
        header("Location: " . BASE_URL);
        exit();
    }

    if ($num > 0) {
        $event = isset($_GET['event']) ? "@" . $_GET['event'] : '';
        user_log('LOGOUT');
        $session_class->end(); ## Terminate local tracking
        header('Location: ' . BASE_URL); ## Redirect to base public route safely
        exit();
    }

    $session_class->setValue('msg_error', 'Request error, please try again');
    header("Location: " . BASE_URL . "home");
    exit();
}
