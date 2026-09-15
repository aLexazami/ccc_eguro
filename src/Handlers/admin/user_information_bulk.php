<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';
$g_fullname = $g_fullname ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;
require_once UPLOAD_HANDLER;
# ===================================================================================

## access validation
$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);
if (!($g_user_role == "ADMIN") && !($system_auth_login == $g_public_key)) {
    $result =  json_encode(["success" => false, "error" => "Error Access"]);
    echo $result;
    exit();
}

function upload_log($file_path, $log)
{
    file_put_contents($file_path, $log, FILE_APPEND);
}

## check the text logs folder
if (!is_dir(STORAGE_LOGS_PATH)) {
    mkdir(STORAGE_LOGS_PATH, 0755);
}

$uploader = new UploaderHandler();
$uploader->allowedExtensions = array('csv'); // all files types allowed by default
$uploader->sizeLimit = CSV_SIZE; ## Specify max file size in bytes.
$uploader->uploadDirectory = STORAGE_CSV_PATH; ## Specify directory upload file.
$uploader->inputFileName = "import_user_information"; // matches Fine Uploader's default inputName value by default
$method = get_request_method();

$session_class->session_close();

function get_request_method()
{
    global $HTTP_RAW_POST_DATA;

    if (isset($HTTP_RAW_POST_DATA)) {
        parse_str($HTTP_RAW_POST_DATA, $_POST);
    }

    if (isset($_POST["_method"]) && $_POST["_method"] != null) {
        return $_POST["_method"];
    }

    return $_SERVER["REQUEST_METHOD"];
}

$separator = "^";
$new_line = "\r\n";

# main table
$table = "users";

if ($method == "POST") {
    header("Content-Type: text/plain");

    $result = $uploader->handleFileUpload();

    ## To return a name used for uploaded file you can use the following line.
    $result["uploadName"] = $uploader->getUploadName();

    if (!empty($result["error"])) {
        $result["error"] = $result["error"];
        $result['total'] = 0;
        $result['success_insert'] =  0;
        $result['success_update'] =  0;
        $result['error_id'] = [];
        unset($result['success']);

        echo json_encode($result);
        exit();
    }

    ## na finish na iupload both chunked and not
    if ((isset($result["success"])) || ($result["uploadName"] != "")) {

        # initial values
        $return_error = array();
        $time_id = "IMPORT_USER_INFORMATION_" . time();
        $file_path = STORAGE_LOGS_PATH . $time_id . ".txt";

        $file = $uploader->getTargetFilePath();
        if (($handle = fopen($file, "r")) !== FALSE) {
            $file_logs = "";
            $bulk_process = false;

            ## limit date birth date
            $limit_birth_date = date('Y-m-d', strtotime('-15 years'));


            $total_count = 1;
            $row_number = 0;
            $success_count = 0;
            $success_insert = 0;
            $success_update = 0;
            $success_remain = 0;
            $error_count = 0;
            $skipped_count = 0;

            $collect_ids = array();
            $duplicates = array();
            $counter = array();
            $credentials_log = array();
            $error_found = array();
            $user_header = array();

            $error_header =  false;
            $error_process = false;
            $no_record = true;

            $required_header = array('EMAIL ADDRESS', 'FIRST NAME',  'LAST NAME');
            $not_required = array('MIDDLE NAME', 'SUFFIX NAME', 'SEX', 'BIRTH DATE', 'BIRTH PLACE', 'CIVIL STATUS', 'NATIONALITY', 'CONTACT NUMBER', 'PERSONAL EMAIL', 'HOME ADDRESS', 'BARANGAY', 'CITY', 'PROVINCE', 'FULL NAME (ECI)', 'RELATIONSHIP (ECI)', 'CONTACT NUMBER (ECI)', 'ADDRESS (ECI)');
            $fixed_header = array_merge($required_header, $not_required);

            ## 

            $collect_user = [];
            while (($column = fgetcsv($handle, 0, ",")) !== FALSE) {
                $transaction_status = "FAILED";
                $error_found = array("msg" => "", "id" => "");
                $num = 0;
                $blank = false;
                $error = false;
                $duplicate_record = false;
                $data = array();
                $user_data = array(); //header set
                $data_clean = array();
                $user_clean = array();
                $db_update = array();
                $found_header_error = array();

                foreach ($column as $index => $value) {
                    $column[$index] = trim($value);
                    if (!mb_check_encoding($column, 'UTF-8')) {
                        $encoding = mb_detect_encoding($value, ['ISO-8859-1', 'Windows-1252', 'UTF-8'], true);
                        $column[$index] = mb_convert_encoding($value, 'UTF-8', $encoding ?: 'ISO-8859-1');
                    }
                }

                if (($total_count == 2)) { //skipped rows 1-2 start in row 3
                    $file_logs = "File Line No. " . ($total_count) . " : SKIPPED " . $new_line;
                    upload_log($file_path, $file_logs);
                    $skipped_count++;
                    $total_count++;
                    continue;
                }

                $column = $helper->cleanArray($column);
                $column = $helper->array_encoding($column);

                if ($total_count == 1) {
                    $column = array_map('strtoupper', $column);
                    foreach ($fixed_header as $index => $header) {
                        $key =  array_search($header, $column, true); //search column no. 
                        if ($key !== false) { //if found in csv header
                            $user_header[$header] = $key;
                        } else if (in_array($header, $required_header)) { //check for required column
                            $error_header = true;
                            array_push($found_header_error, $header);
                        } else { //assign blank value for unimportant column
                            $user_header[$header] = false;
                        }
                    }

                    if ($error_header) {  //header error found
                        $result['total'] = $total_count;
                        $result['success_insert'] =  0;
                        $result['success_update'] =  0;
                        $result['success_remain'] =  0;
                        $result['error_id'] = [];
                        $result['error'] = "FILE CSV HEADER INVALID - NOT FOUND [" . implode(",", $found_header_error) . "]";

                        $file_logs = "File Line No. " . ($total_count) . " : " . $result['error'] . " : HEADER" . $new_line;
                        upload_log($file_path, $file_logs);

                        unset($result['success']);
                        echo json_encode($result);
                        exit();
                    }

                    $file_logs = "File Line No. " . ($total_count) . " : " . json_encode($user_header, JSON_INVALID_UTF8_SUBSTITUTE) . " : HEADER" . $new_line;
                    upload_log($file_path, $file_logs);
                    $skipped_count++;
                    $total_count++;
                    continue;
                }


                foreach ($user_header as $header => $key) {
                    $user_data[$header] = ($key === false) ? '' : $column[$key]; // assign row to correct column header
                }

                $column = array();
                $column = $user_data;

                unset($user_data);

                foreach ($column as $index => $value) {
                    // if ($index == 'SECTION') {
                    //     $column[$index] = strtolower($column[$index]);
                    // }

                    // if ($index == 'COURSE') {
                    //     $column[$index] = mb_strtoupper($column[$index]);
                    // }

                    if (in_array($index, $not_required)) {
                        continue;
                    } else if (trim($value) == "") {
                        $blank = true;
                        $error_found['msg'] = "File Line No. " . ($total_count) . " : Missing a required data [" . $index . "]" . $separator;
                        $error_found['id'] = $error_found['id'] = "row_" . $total_count;
                        $return_error[] = $error_found;
                        break;
                    }
                }

                if ($blank == true) {
                    $file_logs = "File Line No. " . ($total_count) . " : " . $column['EMAIL ADDRESS'] . " : " . $error_found['msg'] . " : " . $transaction_status . $new_line;
                    upload_log($file_path, $file_logs);
                    $total_count++;
                    continue;
                }

                $error_found['id'] = "row_" . $total_count;

                $no_record = false;

                # START PROCESS

                ## check if the email is not email format [if true, return error]
                if (!($helper->isEmailDomain($column['EMAIL ADDRESS']))) {
                    $error = true;
                    $error_found['msg'] .= "Invalid Email Address " . $separator;

                    $file_logs = "File Line No. " . ($total_count) . " : " . $column['EMAIL ADDRESS'] . " : " . $error_found['msg'] . " : " . $transaction_status . $new_line;
                    upload_log($file_path, $file_logs);

                    $error_found['msg'] = $file_logs;
                    $return_error[] = $error_found;
                    $total_count++;
                    continue;
                }

                ## check account if exist
                $information_id = 0;
                $query = "SELECT id, email FROM $table WHERE email = ? LIMIT 1";
                if ($stmt = mysqli_prepare($db_connect, $query)) {
                    mysqli_stmt_bind_param($stmt, "s", $column['EMAIL ADDRESS']);
                    mysqli_stmt_execute($stmt);
                    $_result = mysqli_stmt_get_result($stmt);

                    if ($_data = mysqli_fetch_assoc($_result)) {
                        $information_id = $_data['id'];
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = true;
                    $error_found['msg'] .= "Error Processing Data " . $separator;
                }

                ## check if the suffix is not in SUFFIX array AND not empty [if true, return error]
                if (!$helper->isEmpty($column['SUFFIX NAME']) && !in_array($column['SUFFIX NAME'], SUFFIX)) {
                    $error = true;
                    $error_found['msg'] .= "Invalid Suffix Name " . $separator;
                }

                ## check if the sex is not in SEX array [if true, return error]
                if (!$helper->isEmpty($column['SEX']) && !in_array($column['SEX'], SEX)) {
                    $error = true;
                    $error_found['msg'] .= "Invalid Sex " . $separator;
                }

                if (!$helper->isEmpty($column['BIRTH DATE'])) {
                    if ($column['BIRTH DATE'] == "0000-00-00") {
                    } else {
                        ## check if the birth_date is not valid date format [if true, return error]
                        ## check if the birth_date is greater than the limit_birth_date (-15Y) [if true, return error]
                        if (!$helper->validateDate($column['BIRTH DATE'], 'Y-m-d') || $column['BIRTH DATE'] > $limit_birth_date) {
                            $error = true;
                            $error_found['msg'] .= "Invalid Birth Date " . $separator;
                        }
                    }
                }

                ## check if the civil status is not in CIVIL_STATUS array [if true, return error]
                if (!$helper->isEmpty($column['CIVIL STATUS']) && !in_array($column['CIVIL STATUS'], CIVIL_STATUS)) {
                    $error = true;
                    $error_found['msg'] .= "Invalid Civil Status " . $separator;
                }

                ## validate contact number
                if (!$helper->isEmpty($column['CONTACT NUMBER'])) {
                    ## check if the contact_no is not contact_no format [if true, return error]
                    if (!($helper->isPhone($column['CONTACT NUMBER']))) {
                        $error = true;
                        $error_found['msg'] .= "Invalid Contact Number " . $separator;
                    }

                    ## check if the contact number is exists [if true, return error]
                    if ($information_id) {
                        if ($helper->selectDuplicate($table, [["contact_no", "=", $column['CONTACT NUMBER']], ['id', '!=', $information_id]])) {
                            $error = true;
                            $error_found['msg'] .= "Contact Number already exist " . $separator;
                        }
                    } else {
                        if ($helper->selectDuplicate($table, [["contact_no", "=", $column['CONTACT NUMBER']]])) {
                            $error = true;
                            $error_found['msg'] .= "Contact Number already exist " . $separator;
                        }
                    }
                }

                ## validate personal email
                if (!$helper->isEmpty($column['PERSONAL EMAIL'])) {
                    ## check if the personal_email is not personal_email format [if true, return error]s
                    if (!($helper->isEmail($column['PERSONAL EMAIL']))) {
                        $error = true;
                        $error_found['msg'] .= "Invalid Personal Email Address " . $separator;
                    }

                    ## check if the personal_email is exists [if true, return error]
                    if ($information_id) {
                        if ($helper->selectDuplicate($table, [["personal_email", "=", $column['PERSONAL EMAIL']], ['id', '!=', $information_id]])) {
                            $error = true;
                            $error_found['msg'] .= "Invalid Personal Email Address " . $separator;
                        }
                    } else {
                        if ($helper->selectDuplicate($table, [["personal_email", "=", $column['PERSONAL EMAIL']]])) {
                            $error = true;
                            $error_found['msg'] .= "Invalid Personal Email Address " . $separator;
                        }
                    }
                }

                ## valid emergency contact number
                if (!$helper->isEmpty($column['CONTACT NUMBER (ECI)']) && !$helper->isPhone($column['CONTACT NUMBER (ECI)'])) {
                    $error = true;
                    $error_found['msg'] .= "Invalid Emergency Contact Number " . $separator;
                }

                $column['FIRST NAME'] = $helper->upper($column['FIRST NAME']);
                $column['MIDDLE NAME'] = $helper->upper($column['MIDDLE NAME']);
                $column['LAST NAME'] = $helper->upper($column['LAST NAME']);
                $column['SUFFIX NAME'] = $helper->upper($column['SUFFIX NAME']);

                ## check if the user account is exists [based on full name, sex, birth date] [if true, return error]
                if ($information_id) {
                    if ($helper->selectDuplicate($table, [["first_name", "=", $column['FIRST NAME']], ["middle_name", "=", $column['MIDDLE NAME']], ["last_name", "=", $column['LAST NAME']], ["suffix", "=", $column['SUFFIX NAME']], ["sex", "=", $column['SEX']], ["birth_date", "=", $column['BIRTH DATE']], ['id', '!=', $information_id]])) {
                        $error = true;
                        $error_found['msg'] .= "User Information Already exist " . $separator;
                    }
                } else {
                    if ($helper->selectDuplicate($table, [["first_name", "=", $column['FIRST NAME']], ["middle_name", "=", $column['MIDDLE NAME']], ["last_name", "=", $column['LAST NAME']], ["suffix", "=", $column['SUFFIX NAME']], ["sex", "=", $column['SEX']], ["birth_date", "=", $column['BIRTH DATE']]])) {
                        $error = true;
                        $error_found['msg'] .= "User Information Already exist " . $separator;
                    }
                }

                # ENCOUNTERED ERROR
                if ($error === true) {
                    $file_logs = "File Line No. " . ($total_count) . " : " . $column['EMAIL ADDRESS'] . " : " . $error_found['msg'] . " : " . $transaction_status . $new_line;
                    upload_log($file_path, $file_logs);

                    $error_found['msg'] = $file_logs;
                    $return_error[] = $error_found;
                    $total_count++;
                    continue;
                }

                $fields = [
                    'first_name' => $column['FIRST NAME'],
                    'middle_name' => $column['MIDDLE NAME'],
                    'last_name' => $column['LAST NAME'],
                    'suffix' => $column['SUFFIX NAME'],
                    'sex' => $column['SEX'],
                    'birth_date' => $column['BIRTH DATE'],
                    'birth_place' => $column['BIRTH PLACE'],
                    'civil_status' => $column['CIVIL STATUS'],
                    'nationality' => $column['NATIONALITY'],
                    'contact_no' => $column['CONTACT NUMBER'],
                    'email' => $column['EMAIL ADDRESS'],
                    'personal_email' => $column['PERSONAL EMAIL'],
                    'home_address' => $column['HOME ADDRESS'],
                    'brgy' => $column['BARANGAY'],
                    'city' => $column['CITY'],
                    'province' => $column['PROVINCE'],
                    'e_name' => $column['FULL NAME (ECI)'],
                    'e_relationship' => $column['RELATIONSHIP (ECI)'],
                    'e_contact' => $column['CONTACT NUMBER (ECI)'],
                    'e_address' => $column['ADDRESS (ECI)'],
                ];

                # DATABASE PROCESS
                ## start the transaction
                $db_connect->begin_transaction();
                try {
                    if ($information_id) {
                        $changes = $helper->detectChanges("users", "id", $information_id, $fields);
                        if ($changes['changed']) {
                            $changed = $changes['changes'];

                            $fields['date_modify'] = DATE_TIME;

                            $where = [
                                'id' => $information_id
                            ];

                            ## update data in users db
                            $update = $helper->updateData($table, $fields, $where);
                            if (!$update) {
                                throw new Exception("Error occured in user information");
                            } else {
                                $transaction_status = "SUCCESS";
                                $error_found['msg'] .= 'UPDATE';
                                $success_update++;
                                $success_count++;
                            }

                            $log = json_encode($changes['changes']);
                            activity_log_new("UPDATED USER INFORMATION :: Details:" . $log);
                        } else {
                            $transaction_status = "SUCCESS";
                            $error_found['msg'] .= 'REMAIN';
                            $success_remain++;
                            $success_count++;
                        }
                    } else {
                        $fields['date_modify'] = DATE_TIME;

                        ## insert data in users db
                        $insert = $helper->insertData($table, $fields);
                        if (!$insert) {
                            throw new Exception("Error occured in user information");
                        } else {
                            $transaction_status = "SUCCESS";
                            $error_found['msg'] .= 'INSERT';
                            $success_insert++;
                            $success_count++;
                        }

                        $user_id_last = $insert;

                        $log = json_encode($fields); ## set data log
                        activity_log_new("ADDED USER INFORMATION :: Details:" . $log); ## insert activity log
                    }

                    ## if everything is successful, commit the transaction
                    $db_connect->commit();
                } catch (Exception $exception) {
                    ## if an error occurs, roll back the transaction
                    $db_connect->rollback();

                    $transaction_status = "FAILED";
                    $error_found['msg'] .= $exception->getMessage();
                }

                $file_logs = "File Line No. " . ($total_count) . " : " . $column['EMAIL ADDRESS'] . " : " . $error_found['msg'] . " : " . $transaction_status . $new_line;
                upload_log($file_path, $file_logs);
                $error_found['msg'] = $file_logs;
                if ($transaction_status === 'FAILED') {
                    $return_error[] = $error_found;
                }

                $total_count++;
            }

            fclose($handle);
        }


        $result['total'] =  $total_count - 1;
        $result['skipped'] =  $skipped_count;
        $result['success_insert'] =  $success_insert;
        $result['success_update'] =  $success_update;
        $result['success_remain'] =  $success_remain;
        $result['error_id'] = $return_error;

        if ($no_record) {
            $result['error'] = 'File has no Record';
            unset($result['success']);
        } else if ($success_count == 0) {
        } else {
            activity_log_new("IMPORT USERS INFORMATION :: [" . $time_id . "]");
            $path = IMPORT_USER_LOG;
            $name = $g_fullname;
            update_summary_logs($path, $time_id, $name);
        }
    }

    echo json_encode($result);
    exit();
} else if ($method == "DELETE") { ## for delete file requests
    //result = $uploader->handleDelete(join(DIRECTORY_SEPARATOR,array(DOMAIN_PATH,'upload','csv')));
    //echo json_encode($result);
} else {
    include HTTP_401;
    exit();
}
