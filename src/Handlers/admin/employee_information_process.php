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
$table = "employee";
$fields = [];

## initial output
$output = array("msg_status" => false, "msg_response" => "Request Error, please try again");

## add employee information
if (isset($_POST['actionSubmitEmployee']) && $_POST['actionSubmitEmployee'] == 'submitUserEmployee') {
    $id = isset($_POST['user_id']) ? trim($_POST['user_id']) : 0;
    $employee_id = isset($_POST['employee_id']) ? trim($_POST['employee_id']) : '';
    $service_status = isset($_POST['service_status']) ? trim($_POST['service_status']) : '';
    $personnel_classification = isset($_POST['personnel_classification']) ? trim($_POST['personnel_classification']) : '';
    $employment_status = isset($_POST['employment_status']) ? trim($_POST['employment_status']) : '';
    $employment_basis = isset($_POST['employment_basis']) ? trim($_POST['employment_basis']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';

    ## check if the required input(s) is empty [if true, return error]
    $required_fields = $helper->required($_POST, array('service_status', 'personnel_classification', 'employment_status'));
    if ($required_fields) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Required Field(s) cannot be empty";
        echo json_encode($output);
        exit();
    }

    ## validate default data
    if ($helper->isEmpty($id)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);

    if (!$helper->isDigit($id)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array [if true, return error]
    if (!array_key_exists($service_status, EMPLOYMENT_SERVICE)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Service Status";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array [if true, return error]
    if (!in_array($personnel_classification, EMPLOYMENT_CLASSIFICATION)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Personnel Classification";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array [if true, return error]
    if (!in_array($employment_status, EMPLOYMENT_STATUS)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Employment Status";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array AND not empty [if true, return error]
    if (!$helper->isEmpty($employment_basis) && !in_array($employment_basis, EMPLOYMENT_BASIS)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Employment Basis";
        echo json_encode($output);
        exit();
    }

    if (!$helper->isEmpty($employee_id)) {
        if ($helper->selectDuplicate($table, [["employee_id", "=", $employee_id]])) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Employee ID number already exists";
            echo json_encode($output);
            exit();
        }
    }

    $fields = [
        'user_id' => $id,
        'employee_id' => $employee_id,
        'service_status' => $service_status,
        'personnel_classification' => $personnel_classification,
        'employment_status' => $employment_status,
        'employment_basis' => $employment_basis,
        'position' => $position
    ];

    ## start the transaction
    $db_connect->begin_transaction();
    try {
        ## date modify    
        $fields['date_modify'] = DATE_TIME;

        ## insert data in users db
        $insert = $helper->insertData($table, $fields);
        if (!$insert) {
            throw new Exception("Error occured in user information");
        }

        $user_id_last = $insert;

        $log = json_encode($fields); ## set data log
        activity_log_new("ADDED EMPLOYEE INFORMATION :: Details:" . $log); ## insert activity log

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

## update employee information
if (isset($_POST['actionUpdateEmployee']) && $_POST['actionUpdateEmployee'] == 'submitUpdateEmployee') {
    $id = isset($_POST['u-id']) ? trim($_POST['u-id']) : 0;
    $employee_id = isset($_POST['u-employee_id']) ? trim($_POST['u-employee_id']) : '';
    $service_status = isset($_POST['u-service_status']) ? trim($_POST['u-service_status']) : '';
    $personnel_classification = isset($_POST['u-personnel_classification']) ? trim($_POST['u-personnel_classification']) : '';
    $employment_status = isset($_POST['u-employment_status']) ? trim($_POST['u-employment_status']) : '';
    $employment_basis = isset($_POST['u-employment_basis']) ? trim($_POST['u-employment_basis']) : '';
    $position = isset($_POST['u-position']) ? trim($_POST['u-position']) : '';

    ## check if the required input(s) is empty [if true, return error]
    $required_fields = $helper->required($_POST, array('u-service_status', 'u-personnel_classification', 'u-employment_status'));
    if ($required_fields) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Required Field(s) cannot be empty";
        echo json_encode($output);
        exit();
    }

    ## validate default data
    if ($helper->isEmpty($id)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    $id = decrypted_string($id);

    if (!$helper->isDigit($id)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Default Data";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array [if true, return error]
    if (!array_key_exists($service_status, EMPLOYMENT_SERVICE)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Service Status";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array [if true, return error]
    if (!in_array($personnel_classification, EMPLOYMENT_CLASSIFICATION)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Personnel Classification";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array [if true, return error]
    if (!in_array($employment_status, EMPLOYMENT_STATUS)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Employment Status";
        echo json_encode($output);
        exit();
    }

    ## check if the value is not in array AND not empty [if true, return error]
    if (!$helper->isEmpty($employment_basis) && !in_array($employment_basis, EMPLOYMENT_BASIS)) {
        $output['msg_status'] = false;
        $output['msg_response'] = "Invalid Employment Basis";
        echo json_encode($output);
        exit();
    }

    if (!$helper->isEmpty($employee_id)) {
        if ($helper->selectDuplicate($table, [["employee_id", "=", $employee_id], ["id", "!=", $id]])) {
            $output['msg_status'] = false;
            $output['msg_response'] = "Employee ID number already exists";
            echo json_encode($output);
            exit();
        }
    }

    $fields = [
        'employee_id' => $employee_id,
        'service_status' => $service_status,
        'personnel_classification' => $personnel_classification,
        'employment_status' => $employment_status,
        'employment_basis' => $employment_basis,
        'position' => $position
    ];

    $where = [
        'id' => $id
    ];

    $changes = $helper->detectChanges($table, "id", $id, $fields);
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
        activity_log_new("UPDATED EMPLOYEE INFORMATION :: Details:" . $log);

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

## data fetching
if (isset($_GET['action']) && $_GET['action'] === 'fetchEmployee') {
    try {
        $employeeRecords = $helper->getTableData("employee", [], 'user_id');
        $employee = array_column($employeeRecords, 'user_id');

        $conditions = [
            ['flag_status', '=', 0],
            ['id', 'NOT IN', $employee]
        ];

        $users = $helper->getTableData(
            'users',
            $conditions,
            'id AS user_id, last_name, CONCAT_WS(" ",first_name,NULLIF(middle_name, ""),last_name,NULLIF(suffix, "")) AS name, email',
            'last_name ASC'
        );

        ## encrypt data
        foreach ($users as &$user) {
            unset($user['last_name']);
            $user['user_id'] = encrypted_string($user['user_id']);
        }
        unset($user);

        header('Content-Type: application/json');
        echo json_encode($users);
        exit();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

$output['msg_status'] = false;
$output['msg_response'] = "Request Error";
echo json_encode($output);
exit();
