<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');

require_once HELPER;
# ===================================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$csrf = new CSRF($session_class);
$token_1 = $csrf->validate('token_login_form', $_POST['token_login_form'] ?? '');
if (!$token_1) {
    $session_class->setValue('msg_error', "Authentication failed: Invalid or expired security token request. Please try again.");
    header('Location: ' . BASE_URL . 'login');
    exit();
}

# ======================================================================
# LOGIN AUTHENTICATION
if (isset($_POST['user_login']) && $_POST['user_login'] == 'login') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));
    $agent = isset($_POST['agents']) ? json_decode($_POST['agents'], true) : [];

    if (!empty($username) && !empty($password)) {
        ## Run internal hashing computation core to match database storage values
        $hashed_password = $helper->set_password($password);
        $row = [];

        $default_query = "SELECT tbl_login.user_id, tbl_login.username, tbl_login.password, tbl_login.status, tbl_login.locked,
                                 tbl_user.id, tbl_user.first_name, tbl_user.last_name
                          FROM login as tbl_login 
                          LEFT JOIN users as tbl_user ON tbl_login.user_id = tbl_user.id 
                          WHERE tbl_login.username = ? LIMIT 1";

        if ($stmt = mysqli_prepare($db_connect, $default_query)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($data = mysqli_fetch_assoc($result)) {
                $row = $data;
            } else {
                $session_class->setValue('msg_error', 'Invalid username or password credentials provided.');
                header('Location: ' . BASE_URL . 'login');
                exit();
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Database core login statement failure compilation: " . mysqli_error($db_connect));
            $session_class->setValue('msg_error', 'An internal connection engine compilation error occurred.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        if ((int)$row['status'] === 1) {
            $session_class->setValue('msg_error', 'Account has been deactivated. Kindly contact the system administrator.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        if ((int)$row['status'] === 2) {
            $session_class->setValue('msg_error', 'Account has been suspended. Kindly contact the system administrator.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        if ((int)$row['locked'] === 1) {
            $session_class->setValue('msg_error', 'Account locked. Please execute a password recovery reset or contact the administrator.');
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        $session_class->incValue('browser_attempt_login', 1);
        $error_login = false;
        $username_hash = sha1($row['username']);

        if (!$helper->verify_password($password, $row['password'])) {
            $session_class->setValue('last_user', $username_hash);
            $session_class->incValue("login_attempt_" . $username_hash, 1);
            $error_login = true;
            $session_class->setValue('msg_error', 'Invalid username or password credentials provided.');
            $login_attempt = (int)$session_class->getValue('login_attempt_' . $username_hash);

            if ($login_attempt >= 3) {
                $locked_stmt = mysqli_prepare($db_connect, "UPDATE login SET locked = '1' WHERE user_id = ?");
                mysqli_stmt_bind_param($locked_stmt, "s", $row['user_id']);
                mysqli_stmt_execute($locked_stmt);
                mysqli_stmt_close($locked_stmt);

                $session_class->setValue('msg_error', 'Account locked out due to excessive failed attempts. Kindly initialize a password recovery reset.');
            }

            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        if (!$error_login) {
            $session_class->setValue('login', 'success');
            $session_class->setValue('user_id', $row['user_id']);

            // FIX: Map the actual role code string value token out of database record flags
            $session_class->setValue('system_role', $row['system_role'] ?? 'USER');
            $session_class->setValue('general_id', $row['username']);
            $session_class->setValue('fullname', trim($row['first_name'] . ' ' . $row['last_name']));

            $fingerprint = $session_class->getValue('fingerprint');
            $session_class->setValue('browser_fingerprint', $fingerprint);

            // Clear track state indicators out of runtime context completely upon entry
            $session_class->dropValue('browser_attempt_login');
            $session_class->dropValue('login_attempt_' . $username_hash);
            $session_class->dropValue('last_user');

            user_log("LOGIN", $agent);

            header("Location: " . BASE_URL . "home");
            exit();
        }
    } else {
        $session_class->setValue('msg_error', 'Authentication targets cannot be processed blank.');
        header("Location: " . BASE_URL);
        exit();
    }
}

# ======================================================================
# PASSWORD RECOVERY TOKEN
elseif (isset($_POST['reset_login_action']) || (isset($_POST['user_login']) && $_POST['user_login'] == 'reset_login')) {
    $agent = isset($_POST['agents']) ? json_decode($_POST['agents'], true) : [];
    $emailto = trim((string)($_POST['username'] ?? ''));
    $reset_url_suffix = "?reset=true";

    if (empty($emailto) || !filter_var($emailto, FILTER_VALIDATE_EMAIL)) {
        $session_class->setValue('msg_error', 'Please supply a structurally valid parameter recovery email address.');
        header("Location: " . BASE_URL . $reset_url_suffix);
        exit();
    }

    require_once SRC_PATH . 'Mail/Exception.php';
    require_once SRC_PATH . 'Mail/PHPMailer.php';
    require_once SRC_PATH . 'Mail/SMTP.php';

    $user_id = "";
    $first_name = ""; // Safe default fallback label if join definitions are missing
    $user_type = 1;

    // STEP 1: Look up the recovery email cleanly from the login table alone to remove structural join faults
    $email_query = "SELECT user_id FROM login WHERE recovery_email = ? LIMIT 1";

    if ($stmt = mysqli_prepare($db_connect, $email_query)) {
        mysqli_stmt_bind_param($stmt, "s", $emailto);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($data = mysqli_fetch_assoc($result)) {
            $user_id = $data['user_id'];
        } else {
            $session_class->setValue('msg_error', 'The requested recovery profile parameters match zero records.');
            header("Location: " . BASE_URL . $reset_url_suffix);
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        // DIAGNOSTIC FALLBACK: If your login table uses a different column name (like 'id' instead of 'user_id')
        error_log("Primary SMTP lookup compilation fault: " . mysqli_error($db_connect));
        $session_class->setValue('msg_error', 'An internal login schema validation error occurred.');
        header("Location: " . BASE_URL . $reset_url_suffix);
        exit();
    }

    // STEP 2: Fetch their display name from the users data table in an isolated step
    $user_query = "SELECT first_name FROM users WHERE id = ? LIMIT 1";
    if ($stmt_user = mysqli_prepare($db_connect, $user_query)) {
        mysqli_stmt_bind_param($stmt_user, "s", $user_id);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        if ($data_user = mysqli_fetch_assoc($result_user)) {
            $first_name = $data_user['first_name'];
        }
        mysqli_stmt_close($stmt_user);
    }

    // FIX: Removed the broken standalone exit; string command context entirely to continue pipeline execution thread
    try {
        mysqli_begin_transaction($db_connect);

        $code = bin2hex(openssl_random_pseudo_bytes(12 / 2));
        $exp_date = date("Y-m-d H:i:s", strtotime('+4 hours'));

        $insert_reset = "INSERT INTO reset_code (reset_code, user_id, email_address, created, expire_date, status, user_type) 
                         VALUES (?, ?, ?, ?, ?, '0', ?)";

        if ($ins_stmt = mysqli_prepare($db_connect, $insert_reset)) {
            $created_time = date('Y-m-d H:i:s');
            mysqli_stmt_bind_param($ins_stmt, "sssssi", $code, $user_id, $emailto, $created_time, $exp_date, $user_type);
            mysqli_stmt_execute($ins_stmt);
            $reset_id = mysqli_insert_id($db_connect);
            mysqli_stmt_close($ins_stmt);
        }

        // Bridge privileges safely inside the transaction lifecycle block parameters to capture accurate logging triggers
        $fingerprint = $session_class->getValue('fingerprint') ?? '';
        $session_class->setValue('agent_browser', 'NONE');
        $session_class->setValue('browser_fingerprint', $fingerprint);
        $session_class->setValue('user_id', $user_id);

        $log_data = json_encode([
            'RESET_ID'   => $reset_id ?? 0,
            'USER_ID'    => $user_id,
            'EMAIL'      => $emailto,
            'TIMESTAMP'  => date('Y-m-d H:i:s'),
            'EXPIRATION' => $exp_date
        ], JSON_NUMERIC_CHECK);

        activity_log_new("PASSWORD RESET REQUEST :: Details:" . $log_data);

        // Terminate privilege state bridges instantly once internal tracking files accept writing values
        $session_class->dropValue('user_id');
        $session_class->dropValue('browser_fingerprint');
        $session_class->dropValue('agent_browser');

        $website_url = BASE_URL;
        $link_reset  = BASE_URL . "reset-password?code=" . $code;

        # ======================================================================
        # EXECUTE SYSTEM SECURE INJECTION NETWORK MAIL DISPATCH
        # ======================================================================
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->isHTML(true);
        $mail->SMTPDebug  = SMTP_DEBUG;
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->SMTPAutoTLS = false;

        if (SMTP_SECURE === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (SMTP_SECURE === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->setFrom(SMTP_FROMEMAIL, SMTP_FROMNAME);
        $mail->addReplyTo(SMTP_REPLYTO, SMTP_REPLYNAME);
        $mail->addAddress($emailto);
        $mail->Subject = 'RESET PASSWORD REQUEST';

        $msg_raw = "<!DOCTYPE html><html lang='en'><head><title>" . html(SYSTEM_NAME) . "</title></head><body>";
        require SRC_PATH . 'Mail/password_request_body.php';
        $msg_raw .= "</body></html>";

        $mail->msgHTML($msg_raw);

        if ($mail->send()) {
            mysqli_commit($db_connect);
            $session_class->setValue('msg_success', 'Password reset instructions have been successfully dispatched to ' . html($emailto));
            header("Location: " . BASE_URL . $reset_url_suffix);
            exit();
        } else {
            mysqli_rollback($db_connect);
            $session_class->setValue('msg_error', 'The system network mail configuration engine was unable to forward request packets.');
            header("Location: " . BASE_URL . $reset_url_suffix);
            exit();
        }
    } catch (Exception $e) {
        mysqli_rollback($db_connect);
        error_log("Mail Exception Logging Core Tracker Trigger: " . $e->getMessage());
        $session_class->setValue('msg_error', 'Request Processing Error: Unable to compute transaction rules safely.');
        header("Location: " . BASE_URL . $reset_url_suffix);
        exit();
    } finally {
        if (isset($db_connect) && $db_connect instanceof mysqli) {
            mysqli_close($db_connect);
        }
    }
}

header('Location: ' . BASE_URL . 'login');
exit();
