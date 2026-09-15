<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';
$g_fullname = $g_fullname ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
// set_time_limit(0);
// ini_set('max_execution_time', '0');
// ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
# ===================================================================================

## access validation
$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);
if (!($g_user_role == "ADMIN") && !($system_auth_login == $g_public_key)) {
    $output =  json_encode(["msg_status" => false, "msg_response" => "Request Error"]);
    echo $output;
    exit();
}

if (!in_array(trim($_SERVER['REQUEST_METHOD']), array('POST', 'GET'))) {
    $output =  json_encode(["msg_status" => false, "msg_response" => "Request Error"]);
    echo $output;
    exit();
}

## limit date birth date
$limit_birth_date = date('Y-m-d', strtotime('-15 years'));

## trim all value in POST/GET
if (!empty($_POST)) $_POST = $helper->cleanArray($_POST);
if (!empty($_GET)) $_GET = $helper->cleanArray($_GET);

## defaults
$table = "users";
$fields = [];

## add user information
if (isset($_POST['actionSubmit']) && $_POST['actionSubmit'] == 'submitUser') {
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : '';
    $sex = isset($_POST['sex']) ? trim($_POST['sex']) : '';
    $birth_date = isset($_POST['birth_date']) ? trim($_POST['birth_date']) : '';
    $birth_place = isset($_POST['birth_place']) ? trim($_POST['birth_place']) : '';
    $civil_status = isset($_POST['civil_status']) ? trim($_POST['civil_status']) : '';
    $nationality = isset($_POST['nationality']) ? trim($_POST['nationality']) : '';

    $contact_no = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $personal_email = isset($_POST['personal_email']) ? trim($_POST['personal_email']) : '';

    $home_address = isset($_POST['home_address']) ? trim($_POST['home_address']) : '';
    $brgy = isset($_POST['brgy']) ? trim($_POST['brgy']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $province = isset($_POST['province']) ? trim($_POST['province']) : '';

    $e_name = isset($_POST['e_name']) ? trim($_POST['e_name']) : '';
    $e_relationship = isset($_POST['e_relationship']) ? trim($_POST['e_relationship']) : '';
    $e_contact = isset($_POST['e_contact']) ? trim($_POST['e_contact']) : '';
    $e_address = isset($_POST['e_address']) ? trim($_POST['e_address']) : '';

    $output = array("msg_status" => false, "msg_response" => "Request Error, please try again.");
    $user_id_last = "";

    $required_fields = $helper->required($_POST, array('first_name', 'last_name', 'email'));
    ## check if the required input(s) is empty [if true, return error]
    if ($required_fields) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Required Field(s) cannot be empty";
        echo json_encode($output);
        exit();
    }

    ## check if the suffix is not in SUFFIX array AND not empty [if true, return error]
    if (!$helper->isEmpty($suffix) && !in_array($suffix, SUFFIX)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Extension Name";
        echo json_encode($output);
        exit();
    }

    ## check if the sex is not in SEX array [if true, return error]
    if (!$helper->isEmpty($sex) && !in_array($sex, SEX)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Sex";
        echo json_encode($output);
        exit();
    }

    if (!$helper->isEmpty($birth_date)) {
        if ($birth_date == "0000-00-00") {
        } else {
            ## check if the birth_date is not valid date format [if true, return error]
            if (!$helper->validateDate($birth_date, 'Y-m-d')) {
                $output['msg_status'] = false;
                $output['msg_response'] = "Invalid Birth Date";
                echo json_encode($output);
                exit();
            }

            ## check if the birth_date is greater than the limit_birth_date (-15Y) [if true, return error]
            if ($birth_date > $limit_birth_date) {
                $output['msg_status'] = false;
                $output['msg_response'] = "Invalid Birth Date";
                echo json_encode($output);
                exit();
            }
        }
    }

    ## check if the civil_status is not in civil_status array [if true, return error]
    if (!$helper->isEmpty($civil_status) && !in_array($civil_status, CIVIL_STATUS)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Civil Status";
        echo json_encode($output);
        exit();
    }

    ## validate contact number
    if (!$helper->isEmpty($contact_no)) {
        ## check if the contact_no is not contact_no format [if true, return error]
        if (!($helper->isPhone($contact_no))) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Invalid Contact Number";
            echo json_encode($output);
            exit();
        }

        ## check if the contact number is exists [if true, return error]
        if ($helper->selectDuplicate($table, [["contact_no", "=", $contact_no]])) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Contact Number already exists";
            echo json_encode($output);
            exit();
        }
    }

    ## check if the email is not email format [if true, return error]
    if (!($helper->isEmailDomain($email))) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid CCC Email Address";
        echo json_encode($output);
        exit();
    }

    ## check if the email is exists [if true, return error]
    if ($helper->selectDuplicate($table, [["email", "=", $email]])) {
        $output['msg_status'] = false;
        $output['msg_response'] = "CCC Email Address already exists";
        echo json_encode($output);
        exit();
    }

    ## validate personal email
    if (!$helper->isEmpty($personal_email)) {
        ## check if the personal_email is not personal_email format [if true, return error]s
        if (!($helper->isEmail($personal_email))) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Invalid Personal Email Address";
            echo json_encode($output);
            exit();
        }

        ## check if the personal_email is exists [if true, return error]
        if ($helper->selectDuplicate($table, [["personal_email", "=", $personal_email]])) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Personal Email Address already exists";
            echo json_encode($output);
            exit();
        }
    }


    ## valid emergency contact number
    if (!$helper->isEmpty($e_contact) && !$helper->isPhone($e_contact)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Emergency Contact Number";
        echo json_encode($output);
        exit();
    }

    $first_name = $helper->upper($first_name);
    $middle_name = $helper->upper($middle_name);
    $last_name = $helper->upper($last_name);
    $suffix = $helper->upper($suffix);

    ## check if the user account is exists [based on full name, sex, birth date] [if true, return error]
    if ($helper->selectDuplicate($table, [["first_name", "=", $first_name], ["middle_name", "=", $middle_name], ["last_name", "=", $last_name], ["suffix", "=", $suffix]])) {
        $output['msg_status'] = false;
        $output['msg_response'] = "User Information already exists";
        echo json_encode($output);
        exit();
    }

    ## fields
    $fields = [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'suffix' => $suffix,
        'sex' => $sex,
        'birth_date' => $birth_date,
        'birth_place' => $birth_place,
        'civil_status' => $civil_status,
        'nationality' => $nationality,
        'contact_no' => $contact_no,
        'email' => $email,
        'personal_email' => $personal_email,
        'home_address' => $home_address,
        'brgy' => $brgy,
        'city' => $city,
        'province' => $province,
        'e_name' => $e_name,
        'e_relationship' => $e_relationship,
        'e_contact' => $e_contact,
        'e_address' => $e_address
    ];

    ## start the transaction
    $db_connect->begin_transaction();
    try {
        ## insert data in users db
        $insert = $helper->insertData($table, $fields);
        if (!$insert) {
            throw new Exception("Error occured in user information");
        }

        $user_id_last = $insert;

        $log = json_encode($fields); ## set data log
        activity_log_new("ADDED USER INFORMATION :: Details:" . $log); ## insert activity log

        $output['msg_status'] = true;
        $output['msg_response'] = "Added Successfully";

        ## if everything is successful, commit the transaction
        $db_connect->commit();
    } catch (Exception $exception) {
        ## if an error occurs, roll back the transaction
        $db_connect->rollback();

        $output['msg_status'] = false;
        $output['msg_response'] = $exception->getMessage();
    }

    echo json_encode($output);
    exit();
}

## update account information
if (isset($_POST['actionUpdate']) && $_POST['actionUpdate'] == 'submitUpdate') {
    $user_id = isset($_POST['u_id']) ? trim($_POST['u_id']) : 0;

    $first_name = isset($_POST['u_first_name']) ? trim($_POST['u_first_name']) : '';
    $middle_name = isset($_POST['u_middle_name']) ? trim($_POST['u_middle_name']) : '';
    $last_name = isset($_POST['u_last_name']) ? trim($_POST['u_last_name']) : '';
    $suffix = isset($_POST['u_suffix']) ? trim($_POST['u_suffix']) : '';
    $sex = isset($_POST['u_sex']) ? trim($_POST['u_sex']) : '';
    $birth_date = isset($_POST['u_birth_date']) ? trim($_POST['u_birth_date']) : '';
    $birth_place = isset($_POST['u_birth_place']) ? trim($_POST['u_birth_place']) : '';
    $civil_status = isset($_POST['u_civil_status']) ? trim($_POST['u_civil_status']) : '';
    $nationality = isset($_POST['u_nationality']) ? trim($_POST['u_nationality']) : '';

    $contact_no = isset($_POST['u_contact_no']) ? trim($_POST['u_contact_no']) : '';
    $email = isset($_POST['u_email']) ? trim($_POST['u_email']) : '';
    $personal_email = isset($_POST['u_personal_email']) ? trim($_POST['u_personal_email']) : '';

    $home_address = isset($_POST['u_home_address']) ? trim($_POST['u_home_address']) : '';
    $brgy = isset($_POST['u_brgy']) ? trim($_POST['u_brgy']) : '';
    $city = isset($_POST['u_city']) ? trim($_POST['u_city']) : '';
    $province = isset($_POST['u_province']) ? trim($_POST['u_province']) : '';

    $e_name = isset($_POST['u_e_name']) ? trim($_POST['u_e_name']) : '';
    $e_relationship = isset($_POST['u_e_relationship']) ? trim($_POST['u_e_relationship']) : '';
    $e_contact = isset($_POST['u_e_contact']) ? trim($_POST['u_e_contact']) : '';
    $e_address = isset($_POST['u_e_address']) ? trim($_POST['u_e_address']) : '';

    $output = array("msg_status" => false, "msg_response" => "Request Error, please try again.");


    $required_fields = $helper->required($_POST, array('u_first_name', 'u_last_name', 'u_email'));
    ## check if the required input(s) is empty [if true, return error]
    if ($required_fields) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Required Field(s) cannot be empty";
        echo json_encode($output);
        exit();
    }

    ## validate default data
    if ($helper->isEmpty($user_id)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $user_id = decrypted_string($user_id);

    if (!$helper->isDigit($user_id)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    ## check if the suffix is not in SUFFIX array AND not empty [if true, return error]
    if (!$helper->isEmpty($suffix) && !in_array($suffix, SUFFIX)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Extension Name";
        echo json_encode($output);
        exit();
    }

    ## check if the sex is not in SEX array [if true, return error]
    if (!$helper->isEmpty($sex) && !in_array($sex, SEX)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Sex";
        echo json_encode($output);
        exit();
    }

    if (!$helper->isEmpty($birth_date)) {
        if ($birth_date == "0000-00-00") {
        } else {
            ## check if the birth_date is not valid date format [if true, return error]
            if (!$helper->validateDate($birth_date, 'Y-m-d')) {
                $output['msg_status'] = false;
                $output['msg_response'] = "Invalid Birth Date";
                echo json_encode($output);
                exit();
            }

            ## check if the birth_date is greater than the limit_birth_date (-15Y) [if true, return error]
            if ($birth_date > $limit_birth_date) {
                $output['msg_status'] = false;
                $output['msg_response'] = "Invalid Birth Date";
                echo json_encode($output);
                exit();
            }
        }
    }

    ## check if the sex is not in SEX array [if true, return error]
    if (!$helper->isEmpty($civil_status) && !in_array($civil_status, CIVIL_STATUS)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Civil Status";
        echo json_encode($output);
        exit();
    }

    ## validate contact number
    if (!$helper->isEmpty($contact_no)) {
        ## check if the contact_no is not contact_no format [if true, return error]
        if (!($helper->isPhone($contact_no))) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Invalid Contact Number";
            echo json_encode($output);
            exit();
        }

        ## check if the contact number is exists [if true, return error]
        if ($helper->selectDuplicate($table, [["contact_no", "=", $contact_no], ['id', '!=', $user_id]])) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Contact Number already exists";
            echo json_encode($output);
            exit();
        }
    }

    ## check if the email is not email format [if true, return error]
    if (!($helper->isEmailDomain($email))) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid CCC Email Address";
        echo json_encode($output);
        exit();
    }

    ## check if the email is exists [if true, return error]
    if ($helper->selectDuplicate($table, [["email", "=", $email], ['id', '!=', $user_id]])) {
        $output['msg_status'] = false;
        $output['msg_response'] = "CCC Email Address already exists";
        echo json_encode($output);
        exit();
    }

    ## validate personal email
    if (!$helper->isEmpty($personal_email)) {
        ## check if the personal_email is not personal_email format [if true, return error]s
        if (!($helper->isEmail($personal_email))) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Invalid Personal Email Address";
            echo json_encode($output);
            exit();
        }

        ## check if the personal_email is exists [if true, return error]
        if ($helper->selectDuplicate($table, [["personal_email", "=", $personal_email], ['id', '!=', $user_id]])) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Personal Email Address already exists";
            echo json_encode($output);
            exit();
        }
    }

    ## valid emergency contact number
    if (!$helper->isEmpty($e_contact) && !$helper->isPhone($e_contact)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Emergency Contact Number";
        echo json_encode($output);
        exit();
    }

    $first_name = $helper->upper($first_name);
    $middle_name = $helper->upper($middle_name);
    $last_name = $helper->upper($last_name);
    $suffix = $helper->upper($suffix);

    ## check if the user account is exists [based on full name, sex, birth date] [if true, return error]
    if ($helper->selectDuplicate($table, [["first_name", "=", $first_name], ["middle_name", "=", $middle_name], ["last_name", "=", $last_name], ["suffix", "=", $suffix], ['id', '!=', $user_id]])) {
        $output['msg_status'] = false;
        $output['msg_response'] = "User Information already exists";
        echo json_encode($output);
        exit();
    }

    $fields = [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'suffix' => $suffix,
        'sex' => $sex,
        'birth_date' => $birth_date,
        'birth_place' => $birth_place,
        'civil_status' => $civil_status,
        'nationality' => $nationality,
        'contact_no' => $contact_no,
        'email' => $email,
        'personal_email' => $personal_email,
        'home_address' => $home_address,
        'brgy' => $brgy,
        'city' => $city,
        'province' => $province,
        'e_name' => $e_name,
        'e_relationship' => $e_relationship,
        'e_contact' => $e_contact,
        'e_address' => $e_address
    ];

    $where = [
        'id' => $user_id
    ];

    $changes = $helper->detectChanges("users", "id", $user_id, $fields);
    if (!$changes['changed']) {
        $output['msg_status'] = false;
        $output['msg_response'] = "You haven't changed any of the information.";
        echo json_encode($output);
        exit();
    }

    $changed = $changes['changes'];

    ## update
    ## start the transaction
    $db_connect->begin_transaction();
    try {
        ## date modify    
        $fields['date_modify'] = DATE_TIME;

        ## update data in users db
        $update = $helper->updateData($table, $fields, $where);
        if (!$update) {
            throw new Exception("Error occured in user information");
        }

        $log = json_encode($changes['changes']);
        activity_log_new("UPDATED USER INFORMATION :: Details:" . $log);

        ## if everything is successful, commit the transaction
        $db_connect->commit();

        $output['msg_status'] = true;
        $output['msg_response'] = "Updated Successfully";
    } catch (Exception $e) {
        ## if an error occurs, roll back the transaction
        $db_connect->rollback();

        $output['msg_status'] = false;
        $output['msg_response'] = $e->getMessage();
    }

    echo json_encode($output);
    exit();
}

$output['msg_status'] = false;
$output['msg_response'] = "Request Error";
echo json_encode($output);
exit();
