<?php
include '../config/config.php';
include GLOBAL_FUNC;
include CONNECT_PATH;
include API_DATA;
include API_HELPER;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $json_input = $api->getJsonInput();
    $system_access = trim($json_input->system_access);

    $api->setKeys($system_access);
    $data = $api->curlResponse();

    if (empty($system_access) || $system_access != "EAMS") {
        echo json_encode([
            'response_status' => 0,
            'error' => 'Invalid System Access'
        ]);
        exit();
    }

    if (empty($data)) {
        echo json_encode([
            'response_status' => 0,
            'error' => 'Invalid Data'
        ]);
        exit();
    }

    if (isset($data['action']) && $data['action'] == 'get_password') {
        $email_address = isset($data['email_address']) ? trim($data['email_address']) : '';
        $password = isset($data['password']) ? trim($data['password']) : '';

        if (empty($email_address)) {
            echo json_encode([
                'response_status' => 0,
                'error' => 'Invalid Email Address',
            ]);
            exit();
        }

        $sql = "SELECT l.password, u.email_address, l.`status` FROM login AS l LEFT JOIN (SELECT id,email as email_address FROM users) AS u ON l.user_id = u.id WHERE u.email_address = ? AND l.system_type = ? LIMIT 1";

        $stmt = $db_connect->prepare($sql);
        // if (!$stmt) {
        //     die("Prepare failed: " . $db_connect->error);
        // }

        $stmt->bind_param("ss", $email_address, $system_access);
        $stmt->execute();
        // if (!$stmt->execute()) {
        //     die("Execute failed: " . $stmt->error);
        // }

        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $db_password = $row['password'];

            ## check account status
            if ($row['status'] != '0') {
                echo json_encode([
                    'response_status' => 0,
                    'error' => 'Account Deactivated',
                ]);
                exit();
            }

            ## encrypt input password
            $password = set_password($password);

            ## compare password
            if ($password !== $db_password) {
                echo json_encode([
                    'response_status' => 0,
                    'error' => 'Incorrect Password',
                ]);
                exit();
            }

            ## response if exist
            $payload = [
                'db_password' => $db_password,
                'password' => $password,
            ];
            
            // $payload = [
            //     'status' => 1,
            //     'response' => 1,
            //     'data' => [
            //         'db_password' => $db_password,
            //         'password' => $password,
            //     ],
            // ];

            ## encrypt response
            $en = $api->encryptApiData(json_encode($payload));

            echo json_encode([
                'response_status' => 1,
                'error' => '',
                'curl_response' => $en
            ]);
            exit();
        }

        ## No user found
        echo json_encode([
            'response_status' => 0,
            'error' => 'Account not found',
        ]);
        exit();

        // if ($default_query = call_mysql_query("SELECT l.username,l.password,u.online,l.status FROM login AS l LEFT JOIN (SELECT id,online,email_address FROM users) AS u ON l.user_id = u.id  WHERE u.email_address = '" . escape($db_connect, $email_address) . "' AND l.system_type = '" . escape($db_connect, $system_access) . "'")) {
        //     if ($num_row = call_mysql_num_rows($default_query)) {
        //         if ($row = call_mysql_fetch_array($default_query)) {
        //             $db_password = $row['password'];
        //             if ($row['status'] != '0') {
        //                 echo json_encode([
        //                     'response_status' => 0,
        //                     'error' => 'Account Deactivated',
        //                 ]);
        //                 exit();
        //             }

        //             $password = set_password($password);
        //             if ($password == $db_password) {
        //             } else {
        //                 echo json_encode([
        //                     'response_status' => 0,
        //                     'error' => 'Incorrect Password',
        //                 ]);
        //                 exit();
        //             }

        //             $payload = [
        //                 'status' => 1,
        //                 'response' => 1,
        //                 'data' => [
        //                     'db_password' => $db_password,
        //                     'password' => $password,
        //                 ],
        //             ];

        //             $en = encrypted_data($secret_key, json_encode($payload));
        //             // $jwt_encode = JWT::encode($payload, $secret_key, JWT_ALG);
        //             echo json_encode([
        //                 'response_status' => 1,
        //                 'error' => '',
        //                 'curl_response' => $en
        //             ]);

        //             exit();
        //         }
        //     }
        // }

        echo json_encode([
            'response_status' => 0,
            'error' => 'API DB Error',
        ]);

        exit();
    }
}


echo json_encode([
    'response_status' => 0
]);
