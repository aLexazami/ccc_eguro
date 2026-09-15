<?php
include '../config/config.php';
include GLOBAL_FUNC;
include CONNECT_PATH;
include API_DATA;
include API_HELPER;
include API_HELPER;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

## response 
## 0 - access denied 
## 1 - invalid user account (no account) 
## 2 - deactivated account
## 3 - deleted account
## 4 - wrong user password
## 5 - locked account
## 6 - valid user account

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $file_data = file_get_contents("php://input", true);
    $curl_data = json_decode($file_data);
    $system_access = $curl_data->system_access;
    $system_key = system_key($system_access);
    $secret_key = $system_key['secret_key'];
    $data = jwt_decode_bearer($secret_key);

    if (empty($data)) {
        echo json_encode([
            'response_status' => 0
        ]);
        exit();
    }

    // LOGIN
    if ($data['action'] == 'login') {
        $username = escape($db_connect, $data['username']);
        $password = escape($db_connect, $data['password']);

        $password = set_password($password);
        $table = 'login';
        $dbfield = array('tbl_login.id', 'tbl_login.user_id', 'username', 'password', 'status', 'locked', 'system_type', 'system_role', 'tbl_user.general_id', 'tbl_user.first_name', 'tbl_user.middle_name', 'tbl_user.last_name', 'tbl_user.suffix', 'tbl_user.email', 'tbl_user.recovery_email', 'tbl_user.img');
        $sql_where = "";
        $sql_conds = "";
        $sql_where_array = array("username = '" . $username . "'", "system_type = '" . $system_access . "'");
        $join = "LEFT JOIN (SELECT id, general_id, first_name, middle_name, last_name, suffix, email, recovery_email, img FROM users) as tbl_user ON tbl_login.user_id = tbl_user.id";

        if (count($sql_where_array) > 0) {
            $sql_where = implode(" AND ", $sql_where_array);
        }
        $sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
        $field_query = implode(", ", $dbfield);

        $default_query = "SELECT " . $field_query . " FROM " . $table . " as tbl_login " . $join . " " . $sql_conds . "  LIMIT 1";
        if ($query  = mysqli_query($db_connect, $default_query)){

        }else{
            $payload = [
                'status' => 0,
                'response' => 1
            ];

            $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
            echo json_encode([
                'response_status' => 1,
                'curl_response' => $jwt_encode
            ]);
            exit(); 
        }

        $num_row = mysqli_num_rows($query);
        if ($num_row == 0) {
            $payload = [
                'status' => 0,
                'response' => 1
            ];

            $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
            echo json_encode([
                'response_status' => 1,
                'curl_response' => $jwt_encode
            ]);
            exit();
        } elseif ($num_row > 0) {
            $row = mysqli_fetch_array($query, MYSQLI_ASSOC);
            if ($row['status'] == 1) {
                $payload = [
                    'status' => 0,
                    'response' => 2
                ];

                $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
                echo json_encode([
                    'response_status' => 1,
                    'curl_response' => $jwt_encode
                ]);
                exit();
            } elseif ($row['status'] == 2) {
                $payload = [
                    'status' => 0,
                    'response' => 3
                ];

                $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
                echo json_encode([
                    'response_status' => 1,
                    'curl_response' => $jwt_encode
                ]);
                exit();
            } else {
                if ($row['password'] != $password) {
                    $payload = [
                        'status' => 0,
                        'response' => 4
                    ];

                    $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
                    echo json_encode([
                        'response_status' => 1,
                        'curl_response' => $jwt_encode
                    ]);
                    exit();
                } elseif ($row['locked'] == 1) {
                    $payload = [
                        'status' => 0,
                        'response' => 5
                    ];

                    $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
                    echo json_encode([
                        'response_status' => 1,
                        'curl_response' => $jwt_encode
                    ]);
                    exit();
                } else {
                    $img = BASE_URL . "assets/img/" . $row['img'] . "?v=" . FILE_VERSION;
                    $payload = [
                        'status' => 1,
                        'response' => 6,
                        'data' => [
                            'id' => $row['id'],
                            'user_id' => $row['user_id'],
                            'username' => $row['username'],
                            'general_id' => $row['general_id'],
                            'system_type' => $row['system_type'],
                            'system_role' => $row['system_role'],
                            'first_name' => $row['first_name'],
                            'middle_name' => $row['middle_name'],
                            'last_name' => $row['last_name'],
                            'suffix' => $row['suffix'],
                            'email' => $row['email'],
                            'recovery_email' => $row['recovery_email'],
                            'img' => $img
                        ],
                    ];

                    $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
                    echo json_encode([
                        'response_status' => 1,
                        'curl_response' => $jwt_encode
                    ]);
                    exit();
                }
            }
        }
    }

    // LOCK ACCOUNT
    if ($data['action'] == 'locked') {
        $username = escape($db_connect, $data['username']);
        $locked_query = mysqli_query($db_connect, 'UPDATE login SET locked = "1" WHERE username = "' . $username . '" AND system_type = "' . $system_access . '"');
        // if () {
        //     exit();
        // }
    }

    // RESET PASSWORD
    if ($data['action'] == 'reset_login') {
    }
} else {
    echo json_encode([
        'response_status' => 0
    ]);
    exit();
}
