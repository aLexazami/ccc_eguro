<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_id  = $g_user_id ?? 0;

# Server Execution Limits (Uncomment ONLY for long-running scripts)
// set_time_limit(0);
// ini_set('max_execution_time', '0');
// ini_set('memory_limit', '1024M');

require_once HELPER;
# ===================================================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once SRC_PATH . 'Mail/Exception.php';
require_once SRC_PATH . 'Mail/PHPMailer.php';
require_once SRC_PATH . 'Mail/SMTP.php';

$page_title = "PASSWORD RESET";

$code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';
$title = "";
$message = "";
$reset_id = "";
$user_id = "";
$username = "";
$first_name = "";
$email = "";
$process = false;

if (empty($code)) {
    header("Location: " . BASE_URL);
    exit();
}

# ======================================================================
# 1. CRYPTOGRAPHIC RESET CODE VALIDATION TIMINGS
# ======================================================================
$current_time = date('Y-m-d H:i:s');
$default_query_code = "SELECT reset_id, user_id, email_address, expire_date FROM reset_code WHERE reset_code = ? AND expire_date > ? AND status = '0' LIMIT 1";

if ($stmt = mysqli_prepare($db_connect, $default_query_code)) {
    mysqli_stmt_bind_param($stmt, "ss", $code, $current_time);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($data_code = mysqli_fetch_assoc($result)) {
        $process = true;
        $reset_id = $data_code['reset_id'];
        $user_id  = $data_code['user_id'];
    } else {
        $title = "PASSWORD RESET ERROR";
        $message = "The password recovery link has expired or is invalid.";
    }
    mysqli_stmt_close($stmt);
} else {
    $title = "PASSWORD RESET ERROR";
    $message = "Internal architecture verification fault.";
}

# ======================================================================
# 2. TARGET PROFILE IDENTITY RETRIEVAL
# ======================================================================
if ($process) {
    $process = false; // Reset threshold loop control flag
    $default_query_user = "SELECT first_name FROM users WHERE id = ? LIMIT 1";
    
    if ($stmt = mysqli_prepare($db_connect, $default_query_user)) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($data_user = mysqli_fetch_assoc($result)) {
            $first_name = $data_user['first_name'];
            $process = true;
        } else {
            $title = "PASSWORD RESET ERROR";
            $message = "Target institutional identity profile does not exist.";
        }
        mysqli_stmt_close($stmt);
    }
}

if ($process) {
    $process = false;
    $default_query_login = "SELECT username, recovery_email FROM login WHERE user_id = ? LIMIT 1";
    
    if ($stmt = mysqli_prepare($db_connect, $default_query_login)) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($data_login = mysqli_fetch_assoc($result)) {
            $username = $data_login['username'];
            $email = $data_login['recovery_email'];
            $process = true;
        } else {
            $title = "PASSWORD RESET ERROR";
            $message = "Authentication parameters for target user missing.";
        }
        mysqli_stmt_close($stmt);
    }
}

# ======================================================================
# 3. CRYPTOGRAPHIC DATA TRANSFORMATION & TRANSACTION CORRECTIONS
# ======================================================================
if ($process) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $title = "PASSWORD RESET ERROR";
        $message = "Target identity profile houses an invalid recovery email mapping format.";
    } else {
        $text_password = $helper->generate_password();
        $new_password = $helper->set_password($text_password);

        try {
            // Begin secure ACID compliance database transaction layer block
            mysqli_begin_transaction($db_connect);

            $sql1 = "UPDATE reset_code SET status = '1' WHERE reset_id = ?";
            if ($stmt1 = mysqli_prepare($db_connect, $sql1)) {
                mysqli_stmt_bind_param($stmt1, "s", $reset_id);
                mysqli_stmt_execute($stmt1);
                mysqli_stmt_close($stmt1);
            }

            $sql2 = "UPDATE login SET password = ?, locked = '0' WHERE user_id = ?";
            if ($stmt2 = mysqli_prepare($db_connect, $sql2)) {
                mysqli_stmt_bind_param($stmt2, "ss", $new_password, $user_id);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            $password_history = "INSERT INTO password_history (user_id, password) VALUES (?, ?)";
            if ($stmt3 = mysqli_prepare($db_connect, $password_history)) {
                mysqli_stmt_bind_param($stmt3, "ss", $user_id, $new_password);
                mysqli_stmt_execute($stmt3);
                mysqli_stmt_close($stmt3);
            }

            // Temporarily leverage active runtime metrics to authorize audit logs writing mechanics
            $fingerprint = $session_class->getValue('fingerprint') ?? '';
            $session_class->setValue('user_id', $user_id);
            $session_class->setValue('browser_fingerprint', $fingerprint);
            $session_class->setValue('agent_browser', 'NONE');

            $log_payload = json_encode([
                'RESET_ID'  => $reset_id, 
                'USER_ID'   => $user_id, 
                'EMAIL'     => $email, 
                'TIMESTAMP' => date('Y-m-d H:i:s')
            ], JSON_NUMERIC_CHECK);
            
            activity_log_new("SUCCESS - RESET PASSWORD [EMAIL - " . $email . "] - Details::" . $log_payload);

            // Immediately destroy the temporary privilege elevation markers cleanly out of memory
            $session_class->dropValue('user_id');
            $session_class->dropValue('browser_fingerprint');
            $session_class->dropValue('agent_browser');

            # ======================================================================
            # 4. PHPMailer SECURE INJECTION DISPATCH INTEGRATION
            # ======================================================================
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->isHTML(true);
            $mail->SMTPDebug  = SMTP_DEBUG;
            $mail->Host       = SMTP_HOST;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
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

            $mail->setFrom(SMTP_FROMEMAIL, SMTP_FROMNAME);
            $mail->addReplyTo(SMTP_REPLYTO, SMTP_REPLYNAME);
            $mail->addAddress($email);
            $mail->Subject = 'PASSWORD RESET';

            $msg_raw = "<!DOCTYPE html><html lang='en'><head><title>" . html(SYSTEM_NAME) . "</title></head><body>";
            // Resolves core body layouts via your protected backend templates folder mappings safely
            require SRC_PATH . 'Mail/password_body.php';
            $msg_raw .= "</body></html>";

            $mail->msgHTML($msg_raw);

            if ($mail->send()) {
                // If email leaves the network successfully, execute transaction commit boundaries
                mysqli_commit($db_connect);
                $title = "PASSWORD RESET SUCCESSFULLY";
                $message = "Your temporary recovery credentials have been transmitted to " . html($email);
            } else {
                mysqli_rollback($db_connect);
                $title = "PASSWORD RESET ERROR";
                $message = "Network delivery engine execution error. Action rolled back.";
            }
        } catch (Exception $e) {
            mysqli_rollback($db_connect);
            error_log("PHPMailer Integration Failure Payload: " . $e->getMessage());
            $title = "PASSWORD RESET ERROR";
            $message = "Cryptographic execution engine layer processing error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include META_DATA_PATH; ?>
    <?php include LINK_DATA_PATH; ?>
</head>

<body data-layout="detached" class="body-custom bg-light">
    <header>
        <div class="container text-center px-3 pt-4 pt-md-5 pb-4">
            <img src="<?php echo html(DISPLAY_LOGO); ?>" alt="Logo" class="d-inline-block d-md-none logo-img">
            <img src="<?php echo html(LOGO); ?>" alt="Logo" class="d-none d-md-inline-block logo-img">
        </div>
    </header>

    <div class="container-fluid active">
        <div class="wrapper in">
            <div class="content-page">
                <div class="content">
                    <div class="row pt-5 justify-content-center">
                        <div class="col-xs-12 col-sm-6 col-lg-6">
                            <div class="card c-shadow border-0 shadow-sm">
                                <div class="card-body p-reset-card text-center" style="padding: 24px;">
                                    <h3 id="log_title" class="fw-bold mb-3 text-dark"><?php echo html($title); ?></h3>
                                    <div class="col-lg-12 fs-5 text-secondary"><?php echo html($message); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    include_once FOOTER_PATH; 
    // Cleanly close backend system database allocations at the final lifecycle tier safely
    if (isset($db_connect) && $db_connect instanceof mysqli) {
        mysqli_close($db_connect);
    }
    ?>
</body>
</html>