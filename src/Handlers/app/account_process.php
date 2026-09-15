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

// Set JSON response header for AJAX compatibility
header('Content-Type: application/json; charset=utf-8');

$csrf = new CSRF($session_class);
$response = ['success' => false, 'available' => false, 'message' => '', 'token' => ''];

// 1. Session Authorization Guard
if (!$session_class->getValue('login') || $session_class->getValue('login') !== 'success') {
    $response['message'] = 'Unauthorized access session. Please login.';
    echo json_encode($response);
    exit();
}

$user_id = $g_user_id;
$user_type = $session_class->getValue('user_type') ?? $session_class->getValue('type') ?? 'user';

## Limit date birthdate (Must be at least 15 years old)
$limit_birth_date = date('Y-m-d', strtotime('-15 years'));

// Determine action mode from POST or GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    # =========================================================================
    # MODE: UPDATE/REMOVE USER AVATAR / PROFILE PICTURE
    # =========================================================================
    case 'update_general_profile':
        $table = "users";
        $response['token'] = $csrf->string('token_update_general_profile');
        $token_input = $_POST['token_update_general_profile'] ?? '';

        if (!$csrf->validate('token_update_general_profile', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $action_type = $_POST['action_type'] ?? $_POST['avatarActionType'] ?? 'upload';

        // --- REMOVE AVATAR ACTION ---
        if ($action_type === 'remove') {
            try {
                $currentUser = $helper->selectDataExists($table, ['id' => $user_id], ['profile_pic']);
                if (!empty($currentUser['profile_pic'])) {
                    $existingFilePath = UPLOAD_PROFILE_USER_PATH . $currentUser['profile_pic'];
                    if (file_exists($existingFilePath) && is_file($existingFilePath)) {
                        @unlink($existingFilePath);
                    }
                }

                if ($helper->updateData($table, ['profile_pic' => null], ['id' => $user_id])) {
                    if (function_exists('activity_log_new')) {
                        activity_log_new("REMOVED PROFILE [GENERAL] :: Removed profile picture");
                    }
                    $response['success'] = true;
                    $response['image_url'] = '';
                    $response['message'] = 'Profile picture removed successfully!';
                } else {
                    $response['message'] = 'Failed to remove avatar image from database.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
            break;
        }

        // --- UPLOAD AVATAR ACTION ---
        if (!isset($_FILES['profile_pic']) || empty($_FILES['profile_pic']['name'])) {
            $response['message'] = 'No image file selected for upload.';
            echo json_encode($response);
            exit();
        }

        try {
            $uploadResult = $helper->saveFileWithConversion($_FILES['profile_pic'], UPLOAD_PROFILE_USER_PATH);
            $relativePath = $uploadResult['filePath'];

            if ($helper->updateData($table, ['profile_pic' => $relativePath], ['id' => $user_id])) {
                if (function_exists('activity_log_new')) {
                    activity_log_new("UPDATED PROFILE [GENERAL] :: Updated profile image to " . $relativePath);
                }
                $response['success'] = true;
                $response['image_url'] = BASE_UPLOAD_PROFILE_USER_PATH . $relativePath;
                $response['message'] = 'Profile picture uploaded and converted successfully!';
            } else {
                $response['message'] = 'Failed to save avatar image to database.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # MODE: UPDATE/REMOVE EMPLOYEE AVATAR
    # =========================================================================
    case 'update_employee_avatar':
        $table = "employee";
        $response['token'] = $csrf->string('token_update_emp_avatar');
        $token_input = $_POST['token_update_emp_avatar'] ?? '';

        if (!$csrf->validate('token_update_emp_avatar', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $avatar_action_type = $_POST['avatar_action_type'] ?? 'upload';

        // --- REMOVE EMPLOYEE AVATAR ---
        if ($avatar_action_type === 'remove') {
            try {
                $currentUser = $helper->selectDataExists($table, ['id' => $user_id], ['profile_pic']);
                if (!empty($currentUser['profile_pic'])) {
                    $existingFilePath = UPLOAD_PROFILE_EMP_PATH . $currentUser['profile_pic'];
                    if (file_exists($existingFilePath) && is_file($existingFilePath)) {
                        @unlink($existingFilePath);
                    }
                }

                if ($helper->updateData($table, ['profile_pic' => null], ['id' => $user_id])) {
                    if (function_exists('activity_log_new')) {
                        activity_log_new("UPDATED PROFILE [EMPLOYEE] :: Removed employee profile picture");
                    }
                    $response['success'] = true;
                    $response['image_url'] = '';
                    $response['message'] = 'Employee profile picture removed successfully!';
                } else {
                    $response['message'] = 'Failed to remove employee picture from database.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
            break;
        }

        // --- UPLOAD EMPLOYEE AVATAR ---
        if (!isset($_FILES['emp_profile_pic']) || empty($_FILES['emp_profile_pic']['name'])) {
            $response['message'] = 'No image file selected for upload.';
            echo json_encode($response);
            exit();
        }

        try {
            $uploadResult = $helper->saveFileWithConversion($_FILES['emp_profile_pic'], UPLOAD_PROFILE_EMP_PATH);
            $relativePath = $uploadResult['filePath'];

            if ($helper->updateData($table, ['profile_pic' => $relativePath], ['user_id' => $user_id])) {
                if (function_exists('activity_log_new')) {
                    activity_log_new("UPDATED PROFILE [EMPLOYEE] :: Updated employee image to " . $relativePath);
                }
                $response['success'] = true;
                $response['image_url'] = BASE_UPLOAD_PROFILE_EMP_PATH . $relativePath;
                $response['message'] = 'Employee profile picture uploaded successfully!';
            } else {
                $response['message'] = 'Failed to save employee profile image to database.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # MODE: UPDATE/REMOVE EMPLOYEE COVER PHOTO
    # =========================================================================
    case 'update_employee_cover':
        $table = "employee";
        $response['token'] = $csrf->string('token_update_emp_cover');
        $token_input = $_POST['token_update_emp_cover'] ?? '';

        if (!$csrf->validate('token_update_emp_cover', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $cover_action_type = $_POST['cover_action_type'] ?? 'upload_cover';

        // --- REMOVE EMPLOYEE COVER PHOTO ---
        if ($cover_action_type === 'remove_cover') {
            try {
                $currentUser = $helper->selectDataExists($table, ['user_id' => $user_id], ['cover_photo']);
                if (!empty($currentUser['cover_photo'])) {
                    $existingFilePath = UPLOAD_COVER_EMP_PATH . $currentUser['cover_photo'];
                    if (file_exists($existingFilePath) && is_file($existingFilePath)) {
                        @unlink($existingFilePath);
                    }
                }


                if ($helper->updateData($table, ['cover_photo' => null], ['user_id' => $user_id])) {
                    if (function_exists('activity_log_new')) {
                        activity_log_new("REMOVED COVER PHOTO [EMPLOYEE] :: Removed cover photo");
                    }
                    $response['success'] = true;
                    $response['image_url'] = '';
                    $response['message'] = 'Employee cover photo removed successfully!';
                } else {
                    $response['message'] = 'Failed to remove cover photo from database.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
            break;
        }

        // --- UPLOAD EMPLOYEE COVER PHOTO ---
        if (!isset($_FILES['emp_cover_photo']) || empty($_FILES['emp_cover_photo']['name'])) {
            $response['message'] = 'No cover photo selected for upload.';
            echo json_encode($response);
            exit();
        }

        try {
            $uploadResult = $helper->saveFileWithConversion($_FILES['emp_cover_photo'], UPLOAD_COVER_EMP_PATH);
            $relativePath = $uploadResult['filePath'];

            if ($helper->updateData($table, ['cover_photo' => $relativePath], ['user_id' => $user_id])) {
                if (function_exists('activity_log_new')) {
                    activity_log_new("UPDATED COVER PHOTO [EMPLOYEE] :: Updated cover photo to " . $relativePath);
                }
                $response['success'] = true;
                $response['image_url'] = BASE_UPLOAD_COVER_EMP_PATH . $relativePath;
                $response['message'] = 'Employee cover photo uploaded successfully!';
            } else {
                $response['message'] = 'Failed to save cover photo to database.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # MODE: UPDATE/REMOVE STUDENT AVATAR
    # =========================================================================
    case 'update_student_avatar':
        $table = "student";
        $response['token'] = $csrf->string('token_update_std_avatar');
        $token_input = $_POST['token_update_std_avatar'] ?? '';

        if (!$csrf->validate('token_update_std_avatar', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $avatar_action_type = $_POST['avatar_action_type'] ?? 'upload';

        // --- REMOVE STUDENT AVATAR ---
        if ($avatar_action_type === 'remove') {
            try {
                $currentUser = $helper->selectDataExists($table, ['id' => $user_id], ['profile_pic']);
                if (!empty($currentUser['profile_pic'])) {
                    $existingFilePath = UPLOAD_PROFILE_STD_PATH . $currentUser['profile_pic'];
                    if (file_exists($existingFilePath) && is_file($existingFilePath)) {
                        @unlink($existingFilePath);
                    }
                }

                if ($helper->updateData($table, ['profile_pic' => null], ['id' => $user_id])) {
                    if (function_exists('activity_log_new')) {
                        activity_log_new("UPDATED PROFILE [STUDENT] :: Removed student profile picture");
                    }
                    $response['success'] = true;
                    $response['image_url'] = '';
                    $response['message'] = 'Student profile picture removed successfully!';
                } else {
                    $response['message'] = 'Failed to remove student picture from database.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
            break;
        }

        // --- UPLOAD STUDENT AVATAR ---
        if (!isset($_FILES['std_profile_pic']) || empty($_FILES['std_profile_pic']['name'])) {
            $response['message'] = 'No image file selected for upload.';
            echo json_encode($response);
            exit();
        }

        try {
            $uploadResult = $helper->saveFileWithConversion($_FILES['std_profile_pic'], UPLOAD_PROFILE_STD_PATH);
            $relativePath = $uploadResult['filePath'];

            if ($helper->updateData($table, ['profile_pic' => $relativePath], ['user_id' => $user_id])) {
                if (function_exists('activity_log_new')) {
                    activity_log_new("UPDATED PROFILE [STUDENT] :: Updated student image to " . $relativePath);
                }
                $response['success'] = true;
                $response['image_url'] = BASE_UPLOAD_PROFILE_STD_PATH . $relativePath;
                $response['message'] = 'Student profile picture uploaded successfully!';
            } else {
                $response['message'] = 'Failed to save student profile image to database.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # MODE: UPDATE/REMOVE STUDENT COVER PHOTO
    # =========================================================================
    case 'update_student_cover':
        $table = "student";
        $response['token'] = $csrf->string('token_update_std_cover');
        $token_input = $_POST['token_update_std_cover'] ?? '';

        if (!$csrf->validate('token_update_std_cover', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $cover_action_type = $_POST['cover_action_type'] ?? 'upload_cover';

        // --- REMOVE STUDENT COVER PHOTO ---
        if ($cover_action_type === 'remove_cover') {
            try {
                $currentUser = $helper->selectDataExists($table, ['user_id' => $user_id], ['cover_photo']);
                if (!empty($currentUser['cover_photo'])) {
                    $existingFilePath = UPLOAD_COVER_STD_PATH . $currentUser['cover_photo'];
                    if (file_exists($existingFilePath) && is_file($existingFilePath)) {
                        @unlink($existingFilePath);
                    }
                }


                if ($helper->updateData($table, ['cover_photo' => null], ['user_id' => $user_id])) {
                    if (function_exists('activity_log_new')) {
                        activity_log_new("REMOVED COVER PHOTO [STUDENT] :: Removed cover photo");
                    }
                    $response['success'] = true;
                    $response['image_url'] = '';
                    $response['message'] = 'Student cover photo removed successfully!';
                } else {
                    $response['message'] = 'Failed to remove cover photo from database.';
                }
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
            }
            break;
        }

        // --- UPLOAD STUDENT COVER PHOTO ---
        if (!isset($_FILES['std_cover_photo']) || empty($_FILES['std_cover_photo']['name'])) {
            $response['message'] = 'No cover photo selected for upload.';
            echo json_encode($response);
            exit();
        }

        try {
            $uploadResult = $helper->saveFileWithConversion($_FILES['std_cover_photo'], UPLOAD_COVER_STD_PATH);
            $relativePath = $uploadResult['filePath'];

            if ($helper->updateData($table, ['cover_photo' => $relativePath], ['user_id' => $user_id])) {
                if (function_exists('activity_log_new')) {
                    activity_log_new("UPDATED COVER PHOTO [STUDENT] :: Updated cover photo to " . $relativePath);
                }
                $response['success'] = true;
                $response['image_url'] = BASE_UPLOAD_COVER_STD_PATH . $relativePath;
                $response['message'] = 'Student cover photo uploaded successfully!';
            } else {
                $response['message'] = 'Failed to save cover photo to database.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # UPDATE GENERAL PROFILE INFO
    # =========================================================================
    case 'update_general_info':
        $table = "users";
        $response['token'] = $csrf->string('token_update_general_info');
        $token_input = $_POST['token_update_general_info'] ?? '';

        if (!$csrf->validate('token_update_general_info', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $post_nominal = $helper->clean($_POST['post_nominal'] ?? '');
        $sex = $helper->clean($_POST['sex'] ?? '');
        $birth_date = $helper->clean($_POST['birth_date'] ?? '');
        $birth_place = $helper->clean($_POST['birth_place'] ?? '');
        $civil_status = $helper->clean($_POST['civil_status'] ?? '');
        $nationality = $helper->clean($_POST['nationality'] ?? '');
        $contact_no = $helper->clean($_POST['contact_no'] ?? '');
        $home_address = $helper->clean($_POST['home_address'] ?? '');
        $brgy = $helper->clean($_POST['brgy'] ?? '');
        $city = $helper->clean($_POST['city'] ?? '');
        $province = $helper->clean($_POST['province'] ?? '');
        $e_name = $helper->clean($_POST['e_name'] ?? '');
        $e_relationship = $helper->clean($_POST['e_relationship'] ?? '');
        $e_contact = $helper->clean($_POST['e_contact'] ?? '');
        $e_address = $helper->clean($_POST['e_address'] ?? '');

        $required_fields = $helper->required($_POST, array('sex', 'birth_date', 'contact_no'));
        if ($required_fields) {
            $response['message'] = "Required Field(s) cannot be empty";
            echo json_encode($response);
            exit();
        }

        if (!$helper->isEmpty($sex) && !in_array($sex, SEX)) {
            $response['message'] = "Invalid Sex";
            echo json_encode($response);
            exit();
        }

        if (!$helper->isEmpty($birth_date)) {
            if ($birth_date != "0000-00-00") {
                if (!$helper->validateDate($birth_date, 'Y-m-d')) {
                    $response['message'] = "Invalid Birth Date";
                    echo json_encode($response);
                    exit();
                }
                if ($birth_date > $limit_birth_date) {
                    $response['message'] = "Invalid Birth Date";
                    echo json_encode($response);
                    exit();
                }
            }
        }

        if (!$helper->isEmpty($civil_status) && !in_array($civil_status, CIVIL_STATUS)) {
            $response['message'] = "Invalid Civil Status";
            echo json_encode($response);
            exit();
        }

        if (!$helper->isEmpty($contact_no)) {
            if (!($helper->isPhone($contact_no))) {
                $response['message'] = "Invalid Contact Number";
                echo json_encode($response);
                exit();
            }
            if ($helper->selectDuplicate($table, [["contact_no", "=", $contact_no], ['id', '!=', $user_id]])) {
                $response['message'] = "Contact Number already exists";
                echo json_encode($response);
                exit();
            }
        }

        $fields = [
            'post_nominal' => $post_nominal,
            'sex' => $sex,
            'birth_date' => $birth_date,
            'birth_place' => $birth_place,
            'civil_status' => $civil_status,
            'nationality' => $nationality,
            'contact_no' => $contact_no,
            'home_address' => $home_address,
            'brgy' => $brgy,
            'city' => $city,
            'province' => $province,
            'e_name' => $e_name,
            'e_relationship' => $e_relationship,
            'e_contact' => $e_contact,
            'e_address' => $e_address,
        ];

        $where = ['id' => $user_id];
        $changes = $helper->detectChanges($table, "id", $user_id, $fields);

        if (!$changes['changed']) {
            $response['success'] = false;
            $response['message'] = "You haven't changed any of the information.";
            echo json_encode($response);
            exit();
        }

        $db_connect->begin_transaction();
        try {
            $fields['date_modify'] = DATE_TIME;
            $update = $helper->updateData($table, $fields, $where);

            if (!$update) {
                throw new Exception("Error occurred while updating user information");
            }

            $log = json_encode($changes['changes']);
            if (function_exists('activity_log_new')) {
                activity_log_new("UPDATED GENERAL INFO :: Updated user profile info :: Details: " . $log);
            }

            $db_connect->commit();
            $response['success'] = true;
            $response['message'] = 'General information updated successfully.';
        } catch (Exception $e) {
            $db_connect->rollback();
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # MODE: CHECK USERNAME AVAILABILITY
    # =========================================================================
    case 'check_username':
        $new_username = $helper->clean($_POST['username'] ?? '');

        if (!$helper->validateUsername($new_username, 6, 20) || !preg_match('/^[a-zA-Z0-9_-]+$/', $new_username)) {
            $response['message'] = 'Invalid username format.';
            echo json_encode($response);
            exit();
        }

        try {
            $conditions = ['username' => $new_username, ['user_id', '!=', $user_id]];
            if ($helper->selectDuplicate('login', $conditions)) {
                $response['available'] = false;
                $response['message'] = 'Username is already taken.';
            } else {
                $response['available'] = true;
                $response['success'] = true;
                $response['message'] = 'Username is available!';
            }
        } catch (Exception $e) {
            $response['message'] = 'Database error during lookup.';
        }
        break;

    # =========================================================================
    # MODE: UPDATE USERNAME
    # =========================================================================
    case 'update_username':
        $response['token'] = $csrf->string('token_update_username');
        $token_input = $_POST['token_update_username'] ?? '';

        if (!$csrf->validate('token_update_username', $token_input)) {
            $response['message'] = 'Invalid or expired security token. Please try again.';
            echo json_encode($response);
            exit();
        }

        $new_username = $helper->clean($_POST['newUsername'] ?? $_POST['username'] ?? '');

        if (empty($new_username)) {
            $response['message'] = 'Username cannot be blank.';
            echo json_encode($response);
            exit();
        }

        if (!$helper->validateUsername($new_username, 6, 20)) {
            $response['message'] = 'Username length must be between 6 and 20 characters.';
            echo json_encode($response);
            exit();
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $new_username)) {
            $response['message'] = 'Username contains invalid characters.';
            echo json_encode($response);
            exit();
        }

        try {
            $conditions = ['username' => $new_username, ['user_id', '!=', $user_id]];
            if ($helper->selectDuplicate('login', $conditions)) {
                $response['message'] = 'The chosen username is already taken.';
                echo json_encode($response);
                exit();
            }

            if ($helper->updateData('login', ['username' => $new_username], ['user_id' => $user_id])) {
                $session_class->setValue('general_id', $new_username);
                if (function_exists('activity_log_new')) {
                    activity_log_new("USERNAME_UPDATE :: Username changed to " . $new_username);
                }
                $response['success'] = true;
                $response['message'] = 'Your username has been successfully updated.';
            } else {
                $response['message'] = 'Failed to execute username update.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    # =========================================================================
    # MODE: UPDATE RECOVERY EMAIL
    # =========================================================================
    case 'update_email':
        $response['token'] = $csrf->string('token_update_email');
        $token_input = $_POST['token_update_email'] ?? '';

        if (!$csrf->validate('token_update_email', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        $new_email = $helper->clean($_POST['email'] ?? $_POST['newEmail'] ?? '');

        if (!$helper->isEmail($new_email)) {
            $response['message'] = 'Please supply a structurally valid email address.';
            echo json_encode($response);
            exit();
        }

        $check_sql = "SELECT id FROM `users` WHERE (LOWER(personal_email) = LOWER(?) OR LOWER(email) = LOWER(?)) AND id != ? LIMIT 1";
        $existing = $helper->selectData($check_sql, [$new_email, $new_email, $user_id]);

        if (!empty($existing)) {
            $response['message'] = 'This email address is already registered to another user account.';
            echo json_encode($response);
            exit();
        }

        mysqli_begin_transaction($db_connect);
        try {
            $helper->updateData('users', ['personal_email' => $new_email], ['id' => $user_id]);
            $helper->updateData('login', ['recovery_email' => $new_email], ['user_id' => $user_id]);
            mysqli_commit($db_connect);

            if (function_exists('activity_log_new')) {
                activity_log_new("EMAIL_UPDATE :: Recovery email changed to " . $new_email);
            }
            $response['success'] = true;
            $response['message'] = 'Your recovery email address has been updated successfully.';
        } catch (Exception $e) {
            mysqli_rollback($db_connect);
            error_log("Email update error: " . $e->getMessage());
            $response['message'] = 'An internal system error occurred while updating email.';
        }
        break;

    # =========================================================================
    # MODE: UPDATE PASSWORD
    # =========================================================================
    case 'update_password':
        $response['token'] = $csrf->string('token_update_password');
        $token_input = $_POST['token_update_password'] ?? '';

        if (!$csrf->validate('token_update_password', $token_input)) {
            $response['message'] = 'Invalid or expired security token. Refresh and retry.';
            echo json_encode($response);
            exit();
        }

        $currentPassword = (string)($_POST['currentPassword'] ?? '');
        $newPassword = (string)($_POST['newPassword'] ?? '');
        $confirmPassword = (string)($_POST['confirmPassword'] ?? '');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $response['message'] = 'All password fields are required.';
            echo json_encode($response);
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            $response['message'] = 'New password and confirmation password do not match.';
            echo json_encode($response);
            exit();
        }

        $hasLength = strlen($newPassword) >= 8;
        $hasCase = preg_match('/[a-z]/', $newPassword) && preg_match('/[A-Z]/', $newPassword);
        $hasSymbol = preg_match('/[0-9!@#$%^&*(),.?":{}|<>]/', $newPassword);

        if (!$hasLength || !$hasCase || !$hasSymbol) {
            $response['message'] = 'Password does not meet required strength criteria.';
            echo json_encode($response);
            exit();
        }

        $userRecord = $helper->selectDataExists('login', ['user_id' => $user_id], ['password']);
        $stored_hash = $userRecord['password'] ?? '';

        if (empty($stored_hash)) {
            $response['message'] = 'User account record not found.';
            echo json_encode($response);
            exit();
        }

        if (!$helper->verify_password($currentPassword, $stored_hash)) {
            $response['message'] = 'The current password entered is incorrect.';
            echo json_encode($response);
            exit();
        }

        if ($helper->verify_password($newPassword, $stored_hash)) {
            $response['message'] = 'Your new password cannot be the same as your current password.';
            echo json_encode($response);
            exit();
        }

        $historyRecords = $helper->getTableData('password_history', ['user_id' => $user_id], 'password');
        foreach ($historyRecords as $hist_row) {
            if ($helper->verify_password($newPassword, $hist_row['password'])) {
                $response['message'] = 'You cannot re-use a previously used password. Please choose a new password.';
                echo json_encode($response);
                exit();
            }
        }

        $new_hash = $helper->set_password($newPassword);

        mysqli_begin_transaction($db_connect);
        try {
            $helper->updateData('login', ['password' => $new_hash], ['user_id' => $user_id]);
            $helper->insertData('password_history', [
                'user_id' => $user_id,
                'user_type' => $user_type,
                'password' => $new_hash
            ]);
            mysqli_commit($db_connect);

            if (function_exists('activity_log_new')) {
                activity_log_new("PASSWORD_UPDATE :: Password changed successfully.");
            }
            $response['success'] = true;
            $response['message'] = 'Your password has been changed successfully.';
        } catch (Exception $e) {
            mysqli_rollback($db_connect);
            error_log("Password update error: " . $e->getMessage());
            $response['message'] = 'An error occurred while updating your password. Please try again.';
        }
        break;

    #=========================================================================
    # MODE: UPDATE DIGITAL BUSINESS CARD / EMPLOYEE PROFILE
    #=========================================================================
    case 'update_business_card':
        $table = "employee_profile";
        $response['token'] = $csrf->string('token_update_card');
        $token_input = $_POST['token_update_card'] ?? '';

        if (!$csrf->validate('token_update_card', $token_input)) {
            $response['message'] = 'Invalid or expired security token.';
            echo json_encode($response);
            exit();
        }

        // Sanitize incoming fields matching your exact DB column names
        $card_uid           = $helper->clean($_POST['card_uid'] ?? strtoupper(bin2hex(random_bytes(4))));
        $card_layout        = $helper->clean($_POST['card_layout'] ?? 'classic');
        $card_theme         = $helper->clean($_POST['card_theme'] ?? 'emerald');
        $card_template      = $helper->clean($_POST['card_template'] ?? 'default');
        $name_format        = $helper->clean($_POST['name_format'] ?? 'full');
        $post_nominal_flag  = $helper->clean($_POST['show_post_nominal'] ?? 0);
        $title              = $helper->clean($_POST['custom_title'] ?? $_POST['title'] ?? '');
        $company            = $helper->clean($_POST['company'] ?? '');
        $bio                = $helper->clean($_POST['bio'] ?? '');
        $website            = $helper->clean($_POST['website'] ?? '');
        $address            = $helper->clean($_POST['address'] ?? '');
        $hours              = $helper->clean($_POST['hours'] ?? $_POST['working_hours'] ?? '');

        // Sanitize nested social options array
        $raw_socials = $_POST['social_options'] ?? [];
        $clean_socials = [];

        if (is_array($raw_socials)) {
            foreach ($raw_socials as $soc_key => $soc_val) {
                $clean_key = $helper->clean($soc_key);
                $clean_socials[$clean_key] = [
                    'enabled' => !empty($soc_val['enabled']) ? "1" : "0",
                    'url'     => isset($soc_val['url']) ? $helper->clean($soc_val['url']) : ''
                ];
            }
        }

        $social_options_json = json_encode($clean_socials);

        $fields = [
            'card_layout'       => $card_layout,
            'card_theme'        => $card_theme,
            'card_template'     => $card_template,
            'name_format'       => $name_format,
            'post_nominal_flag' => $post_nominal_flag,
            'title'             => $title,
            'company'           => $company,
            'bio'               => $bio,
            'website'           => $website,
            'address'           => $address,
            'hours'             => $hours,
            'social_options'    => $social_options_json,
            'date_modified'     => DATE_TIME
        ];

        mysqli_begin_transaction($db_connect);
        try {
            $cardExists = $helper->selectDataExists($table, ['user_id' => $user_id], ['id']);

            if (!empty($cardExists)) {
                $update = $helper->updateData($table, $fields, ['user_id' => $user_id]);
            } else {
                $fields['user_id']  = $user_id;
                $fields['card_uid'] = $card_uid;
                $update = $helper->insertData($table, $fields);
            }

            if (!$update) {
                throw new Exception("Failed to update card details in database.");
            }

            mysqli_commit($db_connect);

            if (function_exists('activity_log_new')) {
                activity_log_new("EMPLOYEE_PROFILE_UPDATE :: Digital business card updated successfully.");
            }

            $response['success'] = true;
            $response['message'] = 'Digital business card updated successfully.';
        } catch (Exception $e) {
            mysqli_rollback($db_connect);
            error_log("Card Update Error: " . $e->getMessage());
            $response['message'] = $e->getMessage();
        }
        break;

    default:
        $response['message'] = 'Invalid process request mode.';
        break;
}

echo json_encode($response);
exit();
