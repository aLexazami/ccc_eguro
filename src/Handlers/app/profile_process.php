<?php

# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_id  = $g_user_id ?? 0;

# Server Execution Limits (Uncomment ONLY for long-running scripts)
// set_time_limit(0);
// ini_set('max_execution_time', '0');
// ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
# ===================================================================================

if (!in_array(trim($_SERVER['REQUEST_METHOD']), array('POST', 'GET'))) {
    include HTTP_401;
    exit();
}

## update username and/or recovery_email
if (isset($_POST['update_profile']) && $_POST['update_profile'] == "ProfileUpdate") {
    $general_id = isset($_POST['general_id']) ? trim($_POST['general_id']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $recovery_email = isset($_POST['recovery_email']) ? trim($_POST['recovery_email']) : '';
    $user_id = '';
    $username_old = '';
    $recovery_email_old = '';
    $output = array("msg_status" => "msg_error", "msg_span" => "_msg", "msg_response" => "Request Error, please try again.");

    ## check if the general_id is empty [if true, return error]
    if (empty($general_id)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg";
        $output['msg_response'] = "Invalid default data, please reload the page.";
        echo json_encode($output);
        exit();
    }

    ## check if the username is empty [if true, return error]
    if (empty($username)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_username";
        $output['msg_response'] = "Username cannot be empty.";
        echo json_encode($output);
        exit();
    }

    ## check if the general_id is exists [true - fetch data , false - return error]
    $default_query_id = "SELECT id,general_id,recovery_email FROM users WHERE general_id = '" . escape($db_connect, $general_id) . "' ";
    if ($query_id = call_mysql_query($default_query_id)) {
        if ($num_id = call_mysql_num_rows($query_id)) {
            while ($data_id = call_mysql_fetch_array($query_id)) {
                $user_id = $data_id['id'];
                $recovery_email_old = $data_id['recovery_email'] == null ? '' : $data_id['recovery_email'];
            }
        } else {
            $output['msg_status'] = "msg_error";
            $output['msg_span'] = "_msg";
            $output['msg_response'] = "Invalid default data, please reload the page.";
            echo json_encode($output);
            exit();
        }
    }

    ## fetch the username [old] 
    $default_query_user = "SELECT user_id,username FROM login WHERE user_id = '" . escape($db_connect, $user_id) . "' ";
    if ($query_user = call_mysql_query($default_query_user)) {
        if ($num_user = call_mysql_num_rows($query_user)) {
            while ($data_user = call_mysql_fetch_array($query_user)) {
                $username_old = $data_user['username'];
            }
        }
        mysqli_free_result($query_user);
    }

    ## check if the username and recovery_email are not changed [if true, return error]
    if ($username == $username_old && $recovery_email == $recovery_email_old) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg";
        $output['msg_response'] = "You haven't changed any of the information.";
        echo json_encode($output);
        exit();
    }

    ## check if the recovery_email is not valid email format [if true, return error]
    if (!preg_match('/^\\S+@\\S+\\.\\S+$/', $recovery_email) && !empty($recovery_email)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_recovery_email";
        $output['msg_response'] = "Invalid Recovery Email Address.";
        echo json_encode($output);
        exit();
    }

    ## check if the username is already exists [if true, return error]
    $default_query_username = "SELECT user_id,username FROM login WHERE username = '" . escape($db_connect, $username) . "' AND user_id != '" . escape($db_connect, $user_id) . "'";
    if ($query_username = call_mysql_query($default_query_username)) {
        if ($num_username = call_mysql_num_rows($query_username)) {
            $output['msg_status'] = "msg_error";
            $output['msg_span'] = "_username";
            $output['msg_response'] = "Username already exists.";
            echo json_encode($output);
            exit();
        }
        mysqli_free_result($query_username);
    }

    ## check if the recovery_email is not empty
    if (!empty($recovery_email)) {
        ## check if the recovery_email is already exists [if true, return error]
        $default_query_email = "SELECT general_id,recovery_email FROM users WHERE (recovery_email = '" . escape($db_connect, $recovery_email) . "' OR email = '" . escape($db_connect, $recovery_email) . "') AND general_id != '" . escape($db_connect, $general_id) . "'";
        if ($query_email = call_mysql_query($default_query_email)) {
            if ($num_email = call_mysql_num_rows($query_email)) {
                $output['msg_status'] = "msg_error";
                $output['msg_span'] = "_recovery_email";
                $output['msg_response'] = "Recovery Email Address already exists.";
                echo json_encode($output);
                exit();
            }
            mysqli_free_result($query_email);
        }
    }

    ## update
    ## start the transaction
    $db_connect->begin_transaction();
    try {
        ## update on users
        $sql1 = "UPDATE users SET recovery_email = '" . escape($db_connect, $recovery_email) . "' WHERE general_id = '" . escape($db_connect, $general_id) . "'";
        $query1 = call_mysql_query($sql1);

        ## update on login
        $sql2 = "UPDATE login SET username = '" . escape($db_connect, $username) . "' WHERE user_id = '" . escape($db_connect, $user_id) . "'";
        $query2 = call_mysql_query($sql2);

        $log = json_encode(array('USER_ID' => $user_id, 'GENERAL_ID' => $general_id, 'USERNAME' => $username, 'RECOVERY_EMAIL' => $recovery_email, 'DATE_TIME' => DATE_NOW . ' ' . TIME_NOW));
        activity_log_new("SUCCESS - UPDATING PROFILE [USER_ID - " . $user_id . "] - Details::" . $log);

        $output['msg_status'] = "msg_success";
        $output['msg_span'] = "";
        $output['msg_response'] = "Updated Successfully";
    } catch (Exception $e) {
        ## if an error occurs, roll back the transaction
        $db_connect->rollback();

        $log = json_encode(array('USER_ID' => $user_id, 'GENERAL_ID' => $general_id, 'USERNAME' => $username, 'RECOVERY_EMAIL' => $recovery_email, 'DATE_TIME' => DATE_NOW . ' ' . TIME_NOW));
        activity_log_new("FAILED - UPDATING PROFILE [USER_ID - " . $user_id . "] - Details::" . $log);

        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg";
        $output['msg_response'] = "Updating failed, please try again.";
    }

    ## if everything is successful, commit the transaction
    $db_connect->commit();

    echo json_encode($output);
    exit();
}

## change password
if (isset($_POST['change_password']) && $_POST['change_password'] == "changePassword") {
    $general_id = isset($_POST['general_id']) ? trim($_POST['general_id']) : '';
    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $user_id = '';
    $db_password = '';
    $output = array("msg_status" => "msg_error", "msg_span" => "_msg_password", "msg_response" => "Request Error, please try again.");

    ## check if the general_id is empty [if true, return error]
    if (empty($general_id)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg_password";
        $output['msg_response'] = "Invalid default data, please reload the page.";
        echo json_encode($output);
        exit();
    }

    ## check if the old_password, new_password, and/or confirm_password  is empty [if true, return error]
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg_password";
        $output['msg_response'] = "Invalid Required Fields.";
        echo json_encode($output);
        exit();
    }

    ## check if the general_id is exists [true - fetch data , false - return error]
    $default_query_id = "SELECT id,general_id FROM users WHERE general_id = '" . escape($db_connect, $general_id) . "' ";
    if ($query_id = call_mysql_query($default_query_id)) {
        if ($num_id = call_mysql_num_rows($query_id)) {
            while ($data_id = call_mysql_fetch_array($query_id)) {
                $user_id = $data_id['id'];
            }
        } else {
            $output['msg_status'] = "msg_error";
            $output['msg_span'] = "_msg_password";
            $output['msg_response'] = "Invalid default data, please reload the page.";
            echo json_encode($output);
            exit();
        }
    }

    // ## check if the new_password is not valid password format [if true, return error]
    // if (!preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%&*_?]$/', $new_password)) {
    //     $output['msg_status'] = "msg_error";
    //     $output['msg_span'] = "_new_password";
    //     $output['msg_response'] = "New password does not meet the requirements.";
    //     echo json_encode($output);
    //     exit();
    // }

    ## VALIDATION: 
    if (strlen($new_password) < 8) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_new_password";
        $output['msg_response'] = "Password must be at least 8 characters long.";
        echo json_encode($output);
        exit();
    }

    ## VALIDATION: 
    if (!preg_match('/[\W_]/', $new_password)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_new_password";
        $output['msg_response'] = "Password must contain at least 1 special character.";
        echo json_encode($output);
        exit();
    }

    ## VALIDATION: 
    if (!preg_match('/[a-z]/', $new_password)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_new_password";
        $output['msg_response'] = "Password must contain at least 1 lowercase letter.";
        echo json_encode($output);
        exit();
    }

    ## VALIDATION: 
    if (!preg_match('/[A-Z]/', $new_password)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_new_password";
        $output['msg_response'] = "Password must contain at least 1 uppercase letter.";
        echo json_encode($output);
        exit();
    }

    ## VALIDATION: 
    if (!preg_match('/[0-9]/', $new_password)) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_new_password";
        $output['msg_response'] = "Password must contain at least 1 number.";
        echo json_encode($output);
        exit();
    }

    ## encrypt password(s)
    $old_password = set_password($old_password);
    $new_password = set_password($new_password);
    $confirm_password = set_password($confirm_password);

    ## check if the password and user_id are exists [if false, return error]
    $default_query_password = "SELECT user_id,password FROM login WHERE password = '" . escape($db_connect, $old_password) . "' AND user_id = '" . escape($db_connect, $user_id) . "' LIMIT 1";
    if ($query_password = call_mysql_query($default_query_password)) {
        if ($num_password = call_mysql_num_rows($query_password)) {
        } else {
            $output['msg_status'] = "msg_error";
            $output['msg_span'] = "_old_password";
            $output['msg_response'] = "Incorrect Current Password.";
            echo json_encode($output);
            exit();
        }
    }

    ## check if the new_password is not equal to confirm_password [if true, return error]
    if ($new_password != $confirm_password) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg_password";
        $output['msg_response'] = "Password Not Match.";
        echo json_encode($output);
        exit();
    }

    ## check if the new_password is equal to old_password [if true, return error]
    if ($new_password == $old_password) {
        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg_password";
        $output['msg_response'] = "Please choose a password that you haven't used before.";
        echo json_encode($output);
        exit();
    }

    ## check if the password is already used by the user
    $default_password = "SELECT user_id,password FROM password_history WHERE user_id = '" . escape($db_connect, $user_id) . "' AND password = '" . escape($db_connect, $new_password) . "'";
    if ($query_password = call_mysql_query($default_password)) {
        if ($num_password = call_mysql_num_rows($query_password)) {
            $output['msg_status'] = "msg_error";
            $output['msg_span'] = "_msg_password";
            $output['msg_response'] = "Please choose a password that you haven't used before.";
            echo json_encode($output);
            exit();
        }
        mysqli_free_result($query_password);
    }

    ## update :: insert
    ## start the transaction
    $db_connect->begin_transaction();
    try {
        ## update on login
        $sql1 = "UPDATE login SET password = '" . escape($db_connect, $new_password) . "' WHERE user_id = '" . escape($db_connect, $user_id) . "'";
        $query1 = call_mysql_query($sql1);

        ## insert on password_history
        $sql2 = "INSERT INTO password_history (`user_id`,`password`) VALUES ('" . escape($db_connect, $user_id) . "','" . escape($db_connect, $new_password) . "')";
        $query2 = call_mysql_query($sql2);

        $log = json_encode(array('USER_ID' => $user_id, 'GENERAL_ID' => $general_id, 'DATE_TIME' => DATE_NOW . ' ' . TIME_NOW));
        activity_log_new("SUCCESS - CHANGE PASSWORD [USER_ID - " . $user_id . "] - Details::" . $log);

        $output['msg_status'] = "msg_success";
        $output['msg_span'] = "";
        $output['msg_response'] = "Password Updated Successfully";
    } catch (Exception $e) {
        ## if an error occurs, roll back the transaction
        $db_connect->rollback();

        error_log('Error CHANGE_PASSWORD : ' . escape($db_connect, $e));

        $log = json_encode(array('USER_ID' => $user_id, 'GENERAL_ID' => $general_id, 'DATE_TIME' => DATE_NOW . ' ' . TIME_NOW));
        activity_log_new("FAILED - CHANGE PASSWORD [USER_ID - " . $user_id . "] - Details::" . $log);

        $output['msg_status'] = "msg_error";
        $output['msg_span'] = "_msg_password";
        $output['msg_response'] = "Updating Password Failed, please try again.";
    }

    ## if everything is successful, commit the transaction
    $db_connect->commit();

    echo json_encode($output);
    exit();
}
