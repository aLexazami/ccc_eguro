<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;

require_once HELPER;
# ===================================================================================

$page_id = "";
$error_encounter = false;

$csrf = new CSRF($session_class);
$last_user = $session_class->getValue('last_user');
$login_attempt = 0;
if (!empty($last_user)) {
    $login_attempt = $session_class->getValue('login_attempt_' . $last_user);
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include META_DATA_PATH; ?>
    <?php include LINK_DATA_PATH; ?>

    <style>
        #password {
            -webkit-text-security: disc;
            -moz-text-security: disc;
        }

        .password_show {
            -webkit-text-security: none !important;
            -moz-text-security: none !important;
        }
    </style>
</head>

<body data-layout="detached" class="body-custom">

    <header>
        <div class="container text-center px-3 pt-4 pt-md-5 pb-4">
            <img src="<?php echo DISPLAY_LOGO; ?>" alt="Image" class="d-inline-block d-md-none logo-img">
            <img src="<?php echo LOGO; ?>" alt="Image" class="d-none d-md-inline-block logo-img">
        </div>
    </header>

    <main>
        <div class="px-3 mx-auto login-form">
            <div class="content-card">
                <h2 class="content-card__heading text-center text-black mb-4" id="log_title">Sign In</h2>
                <?php
                $msg_success = $session_class->getValue('msg_success');
                if (isset($msg_success) and $msg_success != "") {
                    echo msg_alert('msg_success', $msg_success);
                    $session_class->dropValue('msg_success');
                }

                $msg_error = $session_class->getValue('msg_error');
                if (isset($msg_error) and $msg_error != "") {
                    echo msg_alert('msg_error', $msg_error);
                    $session_class->dropValue('msg_error');
                }
                ?>

                <form name="login_form" id="login_form" action="<?php echo BASE_URL; ?>login-process" method="POST">
                    <div class="input-group mb-3 shadow" style="border-radius: 25px; overflow: hidden;">
                        <span class="input-group-text border-0 py-3" style="background:rgba(255, 255, 255, 0.7);">
                            <i class="bi bi-person-circle fs-8"></i>
                        </span>
                        <input type="text" class="form-control border-0 py-3" style="background:rgba(255, 255, 255, 0.7);" name="username" id="username" placeholder="Username" required>
                    </div>

                    <div class="form-input-group mb-3" id="password_div">
                        <div class="input-group shadow" style="border-radius: 25px; overflow: hidden;">
                            <span class="input-group-text border-0 py-3" style="background:rgba(255, 255, 255, 0.7);">
                                <i class="bi bi-lock-fill fs-8"></i>
                            </span>
                            <input type="password" class="form-control border-0 py-3" style="background:rgba(255, 255, 255, 0.7);" name="password" id="password" placeholder="Password" required>
                        </div>
                        <span class="text-small attempt-indicator text-black">Login attempts: <?php echo $login_attempt; ?></span>
                    </div>

                    <div class="form-input-group">
                        <button type="submit" id="btn_submit" style="border-radius: 20px;" name="user_login" value="login" class="btn btn-primary w-100 c-button shadow py-2">Login</button>
                    </div>
                    <div class="text-link">
                        <div id="forgotlogin" class="text-center" style="display: block;">
                            <a href="#" id="forgot_action" class="text-black">Forgot Password?</a>
                        </div>
                        <div id="backlogin" style="display: none;">
                            <a href="#" id="back_login" class="d-inline-flex align-items-center gap-1">
                                <span class="leading-icon text-black"><i class="bi bi-arrow-left-short"></i></span>
                                <span class="text-black">Back to login</span>
                            </a>
                        </div>
                    </div>
                    <?php echo $csrf->input('token_login_form', 'token_login_form', 3600, 1); ?>
                </form>
            </div>
        </div>
    </main>

    <?php include FOOTER_PATH; ?>
</body>

<?php include SCRIPT_DATA_PATH; ?>

<script>
    (function() {
        var global_action = "";
        const login_form = document.getElementById('login_form'); // Form element map
        const back_login = document.getElementById('back_login');
        const div_backlogin = document.getElementById('backlogin');
        const btn_forgot = document.getElementById('forgot_action');
        const div_forgot = document.getElementById('password_div');
        const div_forgotlogin = document.getElementById('forgotlogin');
        const btn_submit = document.getElementById('btn_submit');
        const log_title = document.getElementById('log_title');
        const username = document.getElementById('username');
        const password = document.getElementById('password');

        inputObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "type") {
                    if (password.value != "X" && password.value != "") {
                        var message = '<span class="notice">Password Asterisk are protected! Refresh the page to get the field back</span>';
                        password.parentNode.innerHTML = message;
                    }
                }
            });
        });

        if (password) {
            inputObserver.observe(password, {
                attributes: true
            });
        }

        if (btn_forgot && div_backlogin && back_login && div_forgot && btn_submit && username) {
            // Click Forgot Password
            addListener(btn_forgot, 'click', function() {
                global_action = 'reset_login';
                login_form.action = "<?php echo BASE_URL; ?>reset-password"; // 🛠️ DYNAMIC FIX: Change action URL
                div_forgotlogin.style.display = "none";
                div_forgot.style.display = "none";
                btn_submit.value = global_action;
                btn_submit.name = "reset_login_action";
                btn_submit.innerHTML = "Reset";
                set_attribute('placeholder', username, 'Email Address');
                set_attribute('type', username, 'email');
                div_backlogin.style.display = "block";
                password.value = "X";
                set_attribute('type', password, 'text');
                log_title.innerHTML = "Forgot Password?";
                if (document.getElementById("alert-message")) document.getElementById("alert-message").remove();
            });

            // Click Back to Login
            addListener(back_login, 'click', function() {
                global_action = 'login';
                login_form.action = "<?php echo BASE_URL; ?>login-process"; // 🛠️ DYNAMIC FIX: Revert action URL
                div_forgotlogin.style.display = "block";
                div_forgot.style.display = "block";
                btn_submit.value = global_action;
                btn_submit.name = "user_login";
                btn_submit.innerHTML = "Login";
                set_attribute('placeholder', username, 'Username');
                set_attribute('type', password, 'password');
                password.value = "";
                div_backlogin.style.display = "none";
                set_attribute('type', username, 'text');
                log_title.innerHTML = "Sign In";

                if (document.getElementById("alert-message")) document.getElementById("alert-message").remove();
                var url = window.location.href;
                window.location.replace(url.split("?")[0]);
            });
        }

        $("#login_form").submit(function(eventObj) {
            var json = {};
            json['device'] = platform.name;
            json['version'] = platform.version;
            json['layout'] = platform.layout;
            json['os'] = platform.os;
            json['description'] = platform.description;

            $("<input />").attr("type", "hidden")
                .attr("name", "agents")
                .attr("value", JSON.stringify(json))
                .appendTo("#login_form");
            return true;
        });

        // Handle direct URL reset trigger state
        <?php
        if (isset($_GET['reset'])) {
            echo 'if(document.getElementById("forgot_action")){
                    global_action = "reset_login";
                    if(login_form) login_form.action = "' . BASE_URL . 'reset-password";
                    div_forgotlogin.style.display = "none";
                    div_forgot.style.display = "none";
                    btn_submit.value = global_action;
                    btn_submit.name = "reset_login_action";
                    btn_submit.innerHTML = "Reset";
                    set_attribute("placeholder", username, "Email Address");
                    set_attribute("type", username, "email");
                    div_backlogin.style.display = "block";
                    password.value = "X";
                    set_attribute("type", password, "text");
                    log_title.innerHTML = "Forgot password?";
                };';
        }
        ?>
    })();
</script>

</html>