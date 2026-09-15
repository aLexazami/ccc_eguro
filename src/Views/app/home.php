<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
# set_time_limit(0);
# ini_set('max_execution_time', '0');
# ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
# ===================================================================================

$g_username = $g_username ?? null;

$csrf = new CSRF($session_class);

$html = '';
$systemTypes = [
    'E-GURO++'  => ['logo' => WHITE_DISPLAY_LOGO],
    'E-APP'     => ['logo' => APP_DISPLAY_LOGO],
];

$system_access_role = array();
$online = 0;

# ======================================================================
# SECURE RESOURCE ACCESS CONTROL LOOKUP (Prepared Statements)
# ======================================================================
$default_query = "SELECT system_type, system_role, flag_access FROM system_access WHERE flag_access = 0 AND user_id = ?";
if ($stmt = mysqli_prepare($db_connect, $default_query)) {
    mysqli_stmt_bind_param($stmt, "s", $g_user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($data = mysqli_fetch_assoc($result)) {
        if ($data['flag_access'] == 0) {
            $id = $data['system_type'];
            if (!isset($system_access_role[$id])) {
                $system_access_role[$id] = [];
            }
            $system_access_role[$id][] = $data['system_role'];
        }
    }
    mysqli_stmt_close($stmt);
}

# ======================================================================
# GENERATE CARD COMPONENT INTERFACES DYNAMICALLY
#======================================================================
foreach ($systemTypes as $systemType => $systemDetails) {
    if (!isset($system_access_role[$systemType])) {
        continue;
    }
    $systemRoles = $system_access_role[$systemType];
    sort($systemRoles, SORT_NATURAL);
    $logo   = $systemDetails['logo'];
    $button = '';

    foreach ($systemRoles as $role) {
        $roleName = "";
        if ($role !== 'ONLINE') {
            $roleName = str_replace("_", "", SYSTEM_ACCESS[$systemType]['role'][$role] ?? '');
        }
        $roleName = ucwords(strtolower($roleName));

        if ($systemType == 'LMS') {
            if ($role == '1' || $role == '4' || $role == '5') {
                $button .= '<a href="' . SYSTEM_ACCESS[$systemType]['admin_link'] . '" target="_blank" class="action-link"><i class="bi bi-person-rolodex me-2"></i>' . html($roleName) . '</a>';
            } elseif ($role == '2' || $role == '3') {
                $button .= '<a href="' . SYSTEM_ACCESS[$systemType]['user_link'] . '" target="_blank" class="action-link"><i class="bi bi-person-rolodex me-2"></i>' . html($roleName) . '</a>';
            }
        } elseif ($systemType == 'EAMS') {
            if ($role != 'ONLINE') {
                $button .= '<a href="#!" class="action-link" data-bs-toggle="modal" data-bs-target="#systemModal" data-system-type="' . html($systemType) . '" data-system-role="' . html($role) . '"><i class="bi bi-person-rolodex me-2"></i>' . html($roleName) . '</a>';
            } elseif ($role == 'ONLINE') {
                $button .= '<a href="' . SYSTEM_ACCESS[$systemType]['link']['second'] . '" target="_blank" class="action-link"><i class="bi bi-person-bounding-box me-2"></i>Online Attendance</a>';
            }
        } else {
            $button .= '<a href="#!" class="action-link system-access-trigger" data-bs-toggle="modal" data-bs-target="#systemModal" data-system-type="' . html($systemType) . '" data-system-role="' . html($role) . '"><i class="bi bi-person-rolodex me-2"></i>' . html($roleName) . '</a>';
        }
    }

    $html .= '<div class="card-content serives">
        <div class="card-content__body">
            <div class="card-content__description">
                <h2 class="card-content__heading">' . html(SYSTEM_ACCESS[$systemType]['short_name']) . '</h2>
                <p class="card-content__subheading">Access your ' . html(SYSTEM_ACCESS[$systemType]['name']) . ' account.</p>
            </div>
            <div class="card-content__logo">
                <img src="' . html($logo) . '" alt="" loading="lazy" class="services-logo">
            </div>
        </div>
        <div class="d-flex justify-content-start align-items-center gap-2 flex-wrap card-content__action">' . $button . '</div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include META_DATA_PATH; ?>
    <?php include LINK_DATA_PATH; ?>
    <style>
        #accountTabs .nav-link {
            color: #64748b;
            transition: all 0.2s ease-in-out;
        }

        #accountTabs .nav-link.active {
            background-color: #00104c !important;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(8, 24, 74, 0.15);
        }

        #accountInfoModal .form-control:focus,
        #accountInfoModal .btn-outline-secondary:focus {
            border-color: #00104c !important;
            box-shadow: 0 0 0 0.2rem rgba(8, 24, 74, 0.15);
        }
    </style>
</head>

<body class="bg-light d-flex flex-column h-100vh body-custom">
    <div class="content-wrapper">
        <div class="content-wrapper-inner">
            <?php include HEADER_PATH; ?>
            <main class="mb-5 base-main">
                <div class="pb-5">
                    <div class="intro-section">
                        <div class="container-xl py-4 px-3">
                            <span class="font-size-s text-white"><?php echo html(SYSTEM_NAME); ?> Services</span>
                            <p class="fs-1 fw-bold text-white">Welcome to <?php echo html(SYSTEM_NAME); ?></p>
                            <p class="intro-section__description text-white">To access the appropriate system, click the button on the card below that corresponds to the page you need.</p>
                        </div>
                    </div>
                    <div class="container-xl px-3">
                        <div class="card-wrapper"><?php echo $html; ?></div>
                    </div>
                </div>
            </main>

            <!-- System Access Modal -->
            <div class="modal fade" id="systemModal" data-backdrop="static" role="dialog" aria-hidden="true" aria-labelledby="accessModalToggleLabel" style="background-color: linear-gradient(135deg, rgba(0, 16, 76, 0.6) 0%, rgba(0, 16, 76, 0.2) 100%) !important;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content card-modal">
                        <form id="form_submit" action="<?php echo BASE_URL; ?>system-login" method="post">
                            <div class="modal-header">
                                <h5 class="modal-title text-custom" id="profileModalToggleLabel"><strong><i class="bi bi-gear-fill"></i>&ensp;Access Account</strong></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="bd-callout">
                                    <i class="bi bi-info-circle-fill"></i> To access your account, please enter your <strong>password</strong>.
                                </div>
                                <h5 class="mb-1"><span id="err_system_login" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start text-wrap" style="display:none;"></span></h5>
                                <div class="row">
                                    <input type="hidden" name="system_type" id="system_type">
                                    <input type="hidden" name="system_role" id="system_role">
                                    <div class="col-6 mb-3">
                                        <label for="profileEmpID" class="form-label"><strong>Username</strong></label>
                                        <input type="text" name="profileEmpID" value="<?php echo html($g_username); ?>" id="profileEmpID" class="form-control" disabled>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label for="profileName" class="form-label"><strong>Name</strong></label>
                                        <input type="text" name="profileName" value="<?php echo html($g_fullname); ?>" id="profileName" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-12 mb-3">
                                        <label for="profilePassword" class="form-label"><strong>Password</strong></label>
                                        <input type="password" name="profilePassword" id="profilePassword" value="" class="form-control" required autocomplete="off">
                                        <span id="err_profilePassword" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-modal" id="btn_submit" name="actionSubmit" value="submitLogin">Submit</button>
                            </div>
                            <?php echo $csrf->input('token_login_system', 'token_login_system', 3600, 1); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include FOOTER_PATH; ?>
</body>
<?php include SCRIPT_DATA_PATH; ?>

<script>
    (function() {
        // Safe declaration of utility function before invoking
        function hideError() {
            const error = document.querySelectorAll('.badge');
            error.forEach((err) => {
                err.style.display = 'none';
            });
        }
        hideError();

        var modalToggle = document.getElementById('systemModal');
        if (modalToggle) {
            modalToggle.addEventListener('show.bs.modal', function(event) {
                hideError();
                var link = event.relatedTarget;
                if (link) {
                    var systemType = link.getAttribute('data-system-type') || '';
                    var systemRole = link.getAttribute('data-system-role') || '';
                    if (document.getElementById('system_type')) document.getElementById('system_type').value = systemType;
                    if (document.getElementById('system_role')) document.getElementById('system_role').value = systemRole;
                }
                if (document.getElementById('profilePassword')) document.getElementById('profilePassword').value = '';
            });
        }

        $("#form_submit").submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const errBadge = $('#err_system_login');
            const btnSubmit = $('#btn_submit');

            errBadge.hide().text('');

            $.ajax({
                type: "POST",
                url: form.attr('action'),
                data: form.serialize() + '&actionSubmit=submitLogin',
                dataType: "json",
                beforeSend: function() {
                    btnSubmit.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Submitting...').prop('disabled', true);
                },
                complete: function() {
                    btnSubmit.html('Submit').prop('disabled', false);
                },
                success: function(data) {
                    if (data.token) {
                        $("input[name='token_login_system']").val(data.token);
                    }
                    if (data.success === true || data.success === "true") {
                        if (data.url) {
                            var newWindow = window.open(data.url, '_blank');
                            if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                                window.location.href = data.url;
                            } else {
                                $('#systemModal').modal('hide');
                                $('#profilePassword').val('');
                            }
                        } else {
                            location.reload();
                        }
                    } else {
                        errBadge.text(data.message || 'Authentication error. Please try again.').show();
                        $('#profilePassword').val('').focus();
                    }
                },
                error: function() {
                    errBadge.text('Server communication error. Please try again.').show();
                }
            });
        });

        var global_token_resp = "<?php echo isset($_GET['token-response']) ? base64_decode($_GET['token-response']) : ''; ?>";
        if (global_token_resp != '') {
            swal.fire({
                title: '<span class=\"fs-4\">' + global_token_resp + '</span>',
                icon: 'error',
                showConfirmButton: false,
                timerProgressBar: true,
                width: 450,
                footer: `    `,
                padding: '1em 0 0',
                customClass: {
                    popup: 'swal-popup-modal',
                    footer: 'swal-footer-error',
                },
                timer: 4000
            }).then(function() {
                global_token_resp = '';
            });
        }
    })();
</script>

</html>