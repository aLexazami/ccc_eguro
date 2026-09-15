<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 3));
require_once DOMAIN_PATH . '/config/config.php';
require_once GLOBAL_FUNC;
require_once CL_SESSION_PATH;
require_once CONNECT_PATH;
// require_once VALIDATOR_PATH;
require_once ISLOGIN;
require_once API_CONNECT;

$g_user_role = $g_user_role ?? '';

$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);
if (!($system_auth_login == $g_public_key)) {
    header("location: " . SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['link']['main']);
    exit();
}

$path = STORAGE_LOGS_PATH . "summary_users.log";
$result = tailCustom($path, 20);
$record = array();
if (!empty($result)) {
    $record = explode("\n", $result);
}

if ($g_user_role == "REGISTRAR") {
    $system_access = REGISTRAR_SYSTEM_ACCESS;
} else if ($g_user_role == "VPAA") {
    $system_access = VPAA_SYSTEM_ACCESS;
} else {
    $system_access = SYSTEM_ACCESS;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php'; //meta
    include_once DOMAIN_PATH . '/global/include_top.php'; //links
    ?>
</head>

<body class="d-flex flex-column h-100vh">
    <?php
    include_once DOMAIN_PATH . "/global/header.php"; ## header 
    include_once DOMAIN_PATH . '/global/sidebar.php'; //sidebar
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header color-accent-blue-bg text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="font-size: large;">
                    <div>
                        <i class="bi bi-people-fill"></i>&ensp;User Management
                    </div>
                    <div class="mt-3 mt-sm-0 mr-auto">
                        <button id="upload_log" class="btn btn-outline-light btn-rounded btn-sm mx-1"><i class="bi bi-cloud-arrow-up"></i> Upload Logs</button>
                        <button id="add_new_user" class="btn btn-outline-light btn-rounded btn-sm mx-1"><i class="bi bi-plus-circle"></i> Add User</button>
                        <button type="button" id="bulk_new_user" class="btn btn-outline-light btn-rounded btn-sm mx-1"><i class="bi bi-arrow-bar-up bulk"></i>&ensp;Add Bulk User</button>
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div id="user-table" class="table table-bordered tabulator"></div>
                    <div id="footer-total" style="text-align:right; padding: 10px; font-weight:bold;"></div>
                    <div>
                        <button type="button" class="btn btn-blue btn-sm" id="user-download-csv">Download CSV</button>
                        <button type="button" class="btn btn-blue btn-sm" id="user-download-xlsx">Download XLSX</button>
                        <button type="button" class="btn btn-blue btn-sm" id="user-print-table">Print</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- modal -->
        <!-- add user modal -->
        <div class="modal fade" id="add_user_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h5 class="modal-title" id="exampleModalToggleLabel">Add User Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="user_form">
                        <div class="modal-body">
                            <h5><strong>System Access Information</strong></h5>
                            <h5 class="mb-3">
                                <span id="err_system" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></span>
                            </h5>
                            <div class="table_add_container" style="overflow:auto">
                                <table class="table table-bordered" id="crud_user_table">
                                    <thead>
                                        <tr>
                                            <th>System Access</th>
                                            <th>System User Role</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="row_sched">
                                            <td style="min-width: 220px;">
                                                <select name="system_access" class="form-control systemAccess" required>
                                                    <option value="" selected disabled>Select System Access</option>
                                                    <?php
                                                    foreach ($system_access as $key => $data) {
                                                        echo '<option value="' . var_html($key) . '">' . var_html($data['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td style="min-width: 200px;">
                                                <select name="system_roles" class="form-control systemUserRole" disabled>
                                                    <option value="" selected disabled>Select System User Role</option>
                                                </select>
                                            </td>
                                            <td style="min-width: 120px;" class="text-center"><button type="button" id="add_row_user" class="btn btn-outline-success btn-sm btn-rounded" data-row="1"><i class="bi bi-plus-circle"></i> Add Row</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                            <h5><strong>User Information</strong></h5>
                            <h5 class="mb-3">
                                <span id="err_users" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></span>
                            </h5>
                            <div class="row">
                                <div class="col-lg-6 mb-2">
                                    <label class="form-label" for="general_id"><b>General ID</b> <span class="required-field"></span></label>
                                    <input type="text" name="general_id" class="form-control" id="general_id" placeholder="General ID" required>
                                    <span id="err_general_id" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <label for="username" class="form-label"><b>Username</b> <span class="required-field"></span></label>
                                    <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                                    <span id="err_username" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 mb-2">
                                    <label class="form-label" for="first_name"><b>First Name</b> <span class="required-field"></span></label>
                                    <input type="text" name="first_name" class="form-control" id="first_name" placeholder="First name" required>
                                    <span id="err_first_name" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label class="form-label" for="middle_name"><b>Middle Name</b></label>
                                    <input type="text" name="middle_name" class="form-control" id="middle_name" placeholder="Middle name">
                                    <span id="err_middle_name" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label class="form-label" for="last_name"><b>Last Name</b> <span class="required-field"></span></label>
                                    <input type="text" name="last_name" class="form-control" id="last_name" placeholder="Last name" required>
                                    <span id="err_last_name" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <label class="form-label" for="suffix"><b>Suffix</b></label>
                                    <select name="suffix" id="suffix" class="form-control">
                                        <option value="" selected disabled>Select Suffix</option>
                                        <option value=""></option>
                                        <?php
                                        foreach (SUFFIX as $suffix) {
                                            echo '<option value="' . $suffix . '">' . $suffix . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span id="err_suffix" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb-2">
                                    <label for="birth_date" class="form-label"><b>Birth Date</b> <span class="required-field"></span></label>
                                    <input type="text" name="birth_date" class="form-control" id="birth_date" placeholder="Birth Date" autocomplete="off" required>
                                    <span id="err_birth_date" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <label for="sex" class="form-label"><b>Sex</b> <span class="required-field"></span></label>
                                    <select name="sex" id="sex" class="form-control" required>
                                        <option value="" selected disabled>Select Sex</option>
                                        <?php
                                        foreach (SEX as $key_sex => $sex) {
                                            echo '<option value="' . $key_sex . '">' . $sex . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span id="err_sex" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb-2">
                                    <label for="email" class="form-label"><b>Email Address</b> <span class="required-field"></span></label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="Email Address" required>
                                    <span id="err_email" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <label for="position" class="form-label"><b>Position</b> <span class="required-field"></span></label>
                                    <select name="position" id="position" class="form-control" required>
                                        <option value="Teaching Personnel">Teaching Personnel</option>
                                        <option value="Non-Teaching Personnel">Non-Teaching Personnel</option>
                                    </select>
                                    <span id="err_position" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-blue" id="btn_submit" name="actionSubmit" value="submitUser">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- add bulk user modal -->
        <div class="modal fade" id="add_bulk_user" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkSchedBackdropLabel">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h5 class="modal-title" id="bulkSchedBackdropLabel">Add Bulk User</h5>
                        <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5><strong>User Bulk</strong></h5>
                            <div>
                                <a id="template" href="<?php echo BASE_URL; ?>admin/download.php?path=<?php echo USER_TEMPLATE_LIST; ?>">
                                    <button class="btn btn-outline-primary btn-rounded alignToTitle btn-sm mb-2"><i class="bi bi-download"></i>&ensp;Download Template</button>
                                </a>
                            </div>
                        </div>
                        <h5 class="mb-1">
                            <div id="user_bulk_err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></div>
                        </h5>
                        <form autocomplete="off" id="bulk_user_form" enctype="multipart/form-data">
                            <div id="bulk_data">
                                <input type="hidden" value="" name="master_bulk_reference" id="master_bulk_reference">
                                <input type="file" id="addBulkUser" class="bulk_dropify" styles="height:500px" data-default-file="" name="addBulkUser" accept="text/*" required>
                            </div>
                            <div id="bulk_table" style="overflow:auto;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-blue" id="submit_bulk">Submit</button>
                        <button type="button" class="btn btn-blue" id="upload_other_bulk" disabled>Upload Another File</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- update information modal -->
        <div class="modal fade" id="update_info" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h6 class="modal-title" id="exampleModalToggleLabel">Update Information - <span id="account_name"></span></h6>
                        <button type="button" id="close" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="updateForm" name="updateForm" method="post" action="/">
                        <div class="modal-body">
                            <h5 class="mb-3">
                                <span id="err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></span>
                            </h5>
                            <div class="row">
                                <input type="hidden" name="user_idInfo" id="user_idInfo">
                                <div class="col-md-6 mb-2">
                                    <label for="generalIDInfo" class="form-label"><b>General ID</b> <span class="required-field"></span></label>
                                    <input type="text" name="generalIDInfo" value="" class="form-control" id="generalIDInfo">
                                    <span id="err_generalIDInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="userNameInfo" class="form-label"><b>Username</b> <span class="required-field"></span></label>
                                    <input type="text" name="userNameInfo" class="form-control" id="userNameInfo">
                                    <span id="err_userNameInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-6 mb-2">
                                    <label for="fNameInfo" class="form-label"><b>First Name</b> <span class="required-field"></span></label>
                                    <input type="text" name="fNameInfo" value="" class="form-control" id="fNameInfo">
                                    <span id="err_fNameInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-2">
                                    <label for="mNameInfo" class="form-label"><b>Middle Name</b></label>
                                    <input type="text" name="mNameInfo" value="" class="form-control" id="mNameInfo">
                                    <span id="err_mNameInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-2">
                                    <label for="lNameInfo" class="form-label"><b>Last Name</b> <span class="required-field"></span></label>
                                    <input type="text" name="lNameInfo" value="" class="form-control" id="lNameInfo">
                                    <span id="err_lNameInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-2">
                                    <label for="suffInfo" class="form-label"><b>Suffix</b></label>
                                    <select name="suffInfo" id="suffInfo" class="form-control">
                                        <option value=""></option>
                                        <?php
                                        foreach (SUFFIX as $suffix) {
                                            echo '<option value="' . var_html($suffix) . '">' . var_html($suffix) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span id="err_suffInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label for="birthDateInfo" class="form-label"><b>Birth Date</b> <span class="required-field"></span></label>
                                    <input type="text" name="birthDateInfo" class="form-control" id="birthDateInfo">
                                    <span id="err_birthDateInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="sexInfo" class="form-label"><b>Sex</b> <span class="required-field"></span></label>
                                    <select name="sexInfo" id="sexInfo" class="form-control">
                                        <option value="">Select Sex</option>
                                        <?php
                                        foreach (SEX as $key_sex => $sex) {
                                            echo '<option value="' . var_html($key_sex) . '">' . var_html($sex) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span id="err_sexInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="posInfo" class="form-label"><b>Position</b> <span class="required-field"></span></label>
                                    <select name="posInfo" id="posInfo" required>
                                        <option value="Teaching Personnel">Teaching Personnel</option>
                                        <option value="Non-Teaching Personnel">Non-Teaching Personnel</option>
                                    </select>
                                    <span id="err_posInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label for="emailAddressInfo" class="form-label"><b>Email Address</b> <span class="required-field"></span></label>
                                    <input type="text" name="emailAddress" class="form-control" id="emailAddressInfo">
                                    <span id="err_emailAddressInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start"></span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="recoveryEmailInfo" class="form-label"><b>Recovery Email Address</b></label>
                                    <input type="text" name="recoveryEmailInfo" class="form-control" id="recoveryEmailInfo">
                                    <span id="err_recoveryEmailInfo" class="badge bg-danger text-white rounded-1 p-1 mt-1 text-sm-start text-wrap"></span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success" id="btn_reset" name="actionReset" value="resetPass">Reset Password</button>
                            <button type="submit" class="btn btn-warning text-white" id="btn_unlock" name="actionUnlock" value="unlockAcc">Unlock Account</button>
                            <button type="submit" class="btn btn-blue" id="btn_info" name="actionInfo" value="updateInfo">Update Information</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- add user modal -->
        <div class="modal fade" id="add_account" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h5 class="modal-title" id="exampleModalToggleLabel">Add System Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="add_acc_form">
                        <div class="modal-body">
                            <h5><strong>System Access Information</strong></h5>
                            <h5 class="mb-3">
                                <span id="err_systems" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></span>
                            </h5>
                            <div class="table_add_container" style="overflow:auto">
                                <input type="hidden" name="user_idinfo" id="user_idinfo">
                                <table class="table table-bordered" id="add_user_table">
                                    <thead>
                                        <tr>
                                            <th>System Access</th>
                                            <th>System User Role</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="row_sched">
                                            <td style="min-width: 220px;">
                                                <select name="system_name" class="form-control systemAccess" required>
                                                    <option value="" selected disabled>Select System Access</option>
                                                    <?php
                                                    foreach ($system_access as $key => $data) {
                                                        echo '<option value="' . $key . '">' . $data['name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td style="min-width: 200px;">
                                                <select name="system_role" class="form-control systemUserRole" disabled>
                                                    <option value="" selected disabled>Select System User Role</option>
                                                </select>
                                            </td>
                                            <td style="min-width: 120px;" class="text-center"><button type="button" id="add_row_acc" class="btn btn-outline-success btn-sm btn-rounded" data-row="1"><i class="bi bi-plus-circle"></i> Add Row</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="btn_addAcc" name="actionAddacc" value="submitAddacc">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- upload logs -->
        <div class="modal fade" id="upload_log_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h5 class="modal-title" id="exampleModalToggleLabel">Upload Logs</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="upload_log_table" class="table table-bordered"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php
    include_once DOMAIN_PATH . '/global/footer.php'; ## footer
    include_once DOMAIN_PATH . '/global/include_bottom.php'; ## scripts
    ?>

</body>

<script>
    (function() {
        /*** hide span badges error ***/
        function hideError() {
            const error = document.querySelectorAll('.badge');
            error.forEach((err) => {
                err.style.display = 'none';
            });
        }
        hideError();

        $('#position').selectize({
            create: true,
            sortField: 'text',
            placeholder: 'Select or type the position',
        });

        var pos_update = $('#posInfo').selectize({
            create: true,
            sortField: 'text',
            placeholder: 'Select or type the position',
        });

        var selectize_pos = pos_update[0].selectize;

        // $("#user_credential_modal").modal("show");

        function statusClass(cell, formatterParams, onRendered) {
            const span = document.createElement("span");
            const row = cell.getRow();
            const data = row.getData();
            if (data.account_status == 'Active') {
                span.classList.add("status-green");
                span.style.fontSize = "small";
                span.innerHTML = "Active";
            } else if (data.account_status == 'Locked') {
                span.classList.add("status-red");
                span.style.fontSize = "small";
                span.innerHTML = "Locked";
            } else if (data.account_status == 'Deactivated') {
                span.classList.add("status-orange");
                span.style.fontSize = "small";
                span.innerHTML = "Deactivated";
            } else {
                span.classList.add("status-red");
                span.style.fontSize = "small";
                span.innerHTML = data.account_status;
            }
            return span;
        };

        function btnInfo(value, count, data, group) { //for updating information
            var span = document.createElement("div");
            var edit_btn = document.createElement("button");
            var add_btn = document.createElement("button");
            var locked = data[0].locked;
            var status = data[0].status;
            var deact_status = data[0].deact_status;

            span.innerHTML = value + ' ';
            span.classList.add("d-lg-inline", "d-md-inline", "d-sm-inline-block");
            add_btn.classList.add("btn", "btn-sm", "btn-outline-success", "btn-rounded", "m-1");
            add_btn.style.fontSize = "small";
            add_btn.innerHTML = '<i class="bi bi-person-plus-fill"></i>&ensp;Add Account';
            add_btn.addEventListener("click", function() {
                $("#add_account").modal("show");

                document.getElementById("add_acc_form").querySelector("#user_idinfo").value = data[0].id;
            });


            edit_btn.classList.add("btn", "btn-sm", "btn-outline-primary", "btn-rounded", "m-1");
            edit_btn.style.fontSize = "small";
            edit_btn.innerHTML = '<i class="bi bi-pencil-square"></i>&ensp;Update Information';
            edit_btn.addEventListener("click", function() {

                $("#update_info").modal("show");
                // Set the values of the form
                document.getElementById("update_info").querySelector("#user_idInfo").value = data[0].id;
                document.getElementById("update_info").querySelector("#generalIDInfo").value = data[0].general_id;
                document.getElementById("update_info").querySelector("#userNameInfo").value = data[0].username;
                document.getElementById("update_info").querySelector("#fNameInfo").value = data[0].first_name;
                document.getElementById("update_info").querySelector("#mNameInfo").value = data[0].middle_name;
                document.getElementById("update_info").querySelector("#lNameInfo").value = data[0].last_name;
                document.getElementById("update_info").querySelector("#suffInfo").value = data[0].suffix;
                document.getElementById("update_info").querySelector("#birthDateInfo").value = data[0].birth_date;
                document.getElementById("update_info").querySelector("#sexInfo").value = data[0].sex;
                document.getElementById("update_info").querySelector("#emailAddressInfo").value = data[0].email;
                document.getElementById("update_info").querySelector("#recoveryEmailInfo").value = data[0].recovery_email;
                document.getElementById("update_info").querySelector("#account_name").innerHTML = value;


                selectize_pos.addOption({
                    value: data[0].position,
                    text: data[0].position
                });
                selectize_pos.addItem(data[0].position);


                if (deact_status == 1) {
                    document.getElementById("update_info").querySelector("#user_idInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#generalIDInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#userNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#fNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#mNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#lNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#suffInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#birthDateInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#sexInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#emailAddressInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#recoveryEmailInfo").disabled = true;
                    // document.getElementById("update_info").querySelector("#posInfo").disabled = true;
                    selectize_pos.disable();

                    document.getElementById("btn_unlock").style.display = "none";
                    document.getElementById("btn_info").style.display = "none";
                    document.getElementById("btn_reset").style.display = "none";
                } else if (status == 0) {
                    document.getElementById("update_info").querySelector("#user_idInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#generalIDInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#userNameInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#fNameInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#mNameInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#lNameInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#suffInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#birthDateInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#sexInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#emailAddressInfo").disabled = false;
                    document.getElementById("update_info").querySelector("#recoveryEmailInfo").disabled = false;
                    // document.getElementById("update_info").querySelector("#posInfo").disabled = false;
                    selectize_pos.enable();

                    document.getElementById("btn_unlock").style.display = "none";
                    document.getElementById("btn_info").style.display = "block";
                    document.getElementById("btn_reset").style.display = "block";
                } else {
                    document.getElementById("update_info").querySelector("#user_idInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#generalIDInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#userNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#fNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#mNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#lNameInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#suffInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#birthDateInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#sexInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#emailAddressInfo").disabled = true;
                    document.getElementById("update_info").querySelector("#recoveryEmailInfo").disabled = true;
                    // document.getElementById("update_info").querySelector("#posInfo").disabled = true;
                    selectize_pos.disable();

                    document.getElementById("btn_unlock").style.display = "none";
                    document.getElementById("btn_info").style.display = "none";
                    document.getElementById("btn_reset").style.display = "none";
                }
                // Show or hide the "Unlock Account" button based on the value of `locked`
                if (locked == 1) {
                    document.getElementById("btn_unlock").style.display = "block";
                    document.getElementById("btn_info").style.display = "none";
                } else if (locked == 0) {
                    document.getElementById("btn_unlock").style.display = "none";
                }

                // clear any error messages in the form
                hideError();
            });

            span.appendChild(add_btn);
            span.appendChild(edit_btn);
            return span;
        }

        var total_record = 0;

        function record_details(values, data, calcParams) {
            if (values && values.length) {
                return values.length + ' of ' + total_record;
            }
        }

        function record_footer_details(total) {
            const visible_rows = user_table.getDataCount(); // how many rows are currently displayed (page size)
            document.getElementById("footer-total").innerHTML = `${visible_rows} of ${total ?? 0}`;
        }

        /** user table **/
        const user_table = new Tabulator("#user-table", {
            ajaxSorting: true,
            ajaxFiltering: true,
            height: "700px",
            groupStartOpen: false,
            printAsHtml: true,
            printFormatter: false,
            headerFilterPlaceholder: "Search",
            layout: "fitDataStretch",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: true,
            groupBy: function(data) {
                return " [" + data.general_id + "] " + data.name; //groups by general id and name
            },
            groupHeaderPrint: function(value, count, data, group) {
                return value + "<span style='color:#d00; margin-left:10px;'></span>";
            },
            groupHeader: btnInfo,
            groupUpdateOnCellEdit: true,
            printConfig: {
                formatCells: false
            },
            downloadConfig: {
                columnHeaders: true,
                columnGroups: false,
                rowGroups: false,
                formatCells: false
            },
            ajaxURL: "<?php echo BASE_URL; ?>admin/user_table.php",
            paginationSize: <?php echo QUERY_LIMIT; ?>,
            ajaxLoader: true,
            ajaxLoaderLoading: 'Fetching data from Database..',
            pagination: "remote",
            paginationSizeSelector: [100, 500, 1000, true],
            selectableRollingSelection: false,
            headerHozAlign: 'center',
            columns: [{
                    title: "General ID",
                    field: "general_id",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 150,
                    hozAlign: "center",
                    // bottomCalc: record_details
                },
                {
                    title: "Name",
                    field: "name",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 170
                },
                {
                    title: "Birth Date",
                    field: "birth_date",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 150
                },
                {
                    title: "Sex",
                    field: "sex",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 150,
                    hozAlign: "center"
                },
                {
                    title: "Email Address",
                    field: "email",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 170
                },
                {
                    title: "Recovery Email",
                    field: "recovery_email",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 170
                },
                {
                    title: "Position",
                    field: "position",
                    formatter: 'textarea',
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 120
                },
                {
                    title: "Username",
                    field: "username",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 150,
                    hozAlign: "center"
                },
                {
                    title: "System Access",
                    field: "system_type",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 170,
                    hozAlign: "center"
                },
                {
                    title: "System Role",
                    field: "system_role",
                    // headerFilter: "input",
                    // headerFilterFunc: "like",
                    // headerFilterParams: {
                    //     allowEmpty: true
                    // },
                    // headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 170,
                    hozAlign: "center"
                },
                {
                    title: "Status",
                    field: "account_status",
                    formatter: statusClass,
                    download: false,
                    print: false,
                    vertAlign: 'middle',
                    minWidth: 100
                },
                {
                    title: "Action",
                    field: "user_id",
                    formatter: btnAction,
                    headerSort: false,
                    headerFilter: false,
                    download: false,
                    print: false,
                    vertAlign: 'middle',
                    minWidth: 200,
                    cellClick: function(e, cell) {
                        cell.getRow().toggleSelect();
                    }
                },
            ],
            ajaxResponse: function(url, params, response) {
                //url - the URL of the request
                //params - the parameters passed with the request
                //response - the JSON object returned in the body of the response.
                total_record = response.total_records ?? 0;
                return response; //return the tableData property of a response json object
            },
            // renderComplete: function() {
            //     // Now it's safe to get the updated row count
            //     const visible_rows = user_table.getDataCount();
            //     document.getElementById("footer-total").innerHTML = `${visible_rows} of ${total_record}`;
            // },
        });

        /** export user data table **/
        addListener(document.getElementById('user-download-csv'), "click", function() {
            user_table.getGroups().forEach(x => x._group.show());
            user_table.download("csv", "user_information_" + getFormattedTime() + ".csv", {
                bom: true
            });
            user_table.getGroups().forEach(x => x._group.hide());
        });

        addListener(document.getElementById('user-download-xlsx'), "click", function() {
            user_table.getGroups().forEach(x => x._group.show());
            user_table.download("xlsx", "user_information_" + getFormattedTime() + ".xlsx");
            user_table.getGroups().forEach(x => x._group.hide());
        });

        addListener(document.getElementById('user-print-table'), "click", function() {
            user_table.getGroups().forEach(x => x._group.show());
            user_table.print(false, true);
            user_table.getGroups().forEach(x => x._group.hide());
        });

        function btnAction(cell, formatterParams, onRendered) { // for updating the 
            var cellEl = cell.getElement(); //get cell DOM element
            var actionBut = document.createElement("span");
            var row = cell.getRow();
            var data = row.getData();
            var edit_btn = document.createElement("button");
            var delete_btn = document.createElement("button");


            if (data.account_status == 'Active') {
                edit_btn.classList.add("btn", "btn-sm", "btn-outline-secondary", "btn-rounded", "m-1");
                edit_btn.style.fontSize = "small";
                edit_btn.innerHTML = '<i class="bi bi-person-bounding-box"></i>&ensp;Deactivate';
            } else if (data.account_status == 'Deactivated') {
                edit_btn.classList.add("btn", "btn-sm", "btn-outline-success", "btn-rounded", "m-1");
                edit_btn.style.fontSize = "small";
                edit_btn.innerHTML = '<i class="bi bi-person-bounding-box"></i>&ensp;Activate';
            } else {
                return '';
            }

            edit_btn.addEventListener("click", function() {
                if (data.account_status == 'Active') {
                    swal.fire({
                        title: 'Are you sure you want to deactivate this account?',
                        showConfirmButton: true,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No',
                        showCancelButton: true,
                        cancelButtonColor: '#bd362f',
                        allowOutsideClick: false,
                        customClass: {
                            container: 'popup-modal-container',
                            title: 'title-ff',
                            popup: 'popup-modal',
                            icon: 'popup-modal-icon',
                            actions: 'popup-modal-actions',
                            confirmButton: 'actions-confirm',
                            cancelButton: 'actions-cancel',
                            htmlContainer: 'popup-modal-description'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            var user_id = data.user_id;
                            var system_type = data.system_type;
                            var system_role = data.system_role;
                            formData.append("actionDeactivate", 'submitDeactivate');
                            formData.append("user_id", user_id);
                            formData.append("system_type", system_type);
                            formData.append("system_role", system_role);
                            $.ajax({
                                url: '<?php echo BASE_URL; ?>admin/user_process.php',
                                type: 'post',
                                data: formData,
                                dataType: "json",
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    if (response.msg_status === true) {
                                        user_table.setData();
                                        success_notif(response.msg_response);
                                    } else if (data.msg_status === false) {
                                        error_notif(response.msg_response);
                                    }
                                }
                            });
                        }
                    })
                } else if (data.account_status == 'Deactivated') {
                    swal.fire({
                        title: 'Are you sure you want to Activate this account?',
                        showConfirmButton: true,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No',
                        showCancelButton: true,
                        cancelButtonColor: '#bd362f',
                        allowOutsideClick: false,
                        customClass: {
                            container: 'popup-modal-container',
                            title: 'title-ff',
                            popup: 'popup-modal',
                            icon: 'popup-modal-icon',
                            actions: 'popup-modal-actions',
                            confirmButton: 'actions-confirm',
                            cancelButton: 'actions-cancel',
                            htmlContainer: 'popup-modal-description'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const formData = new FormData();
                            var user_id = data.user_id;
                            var system_type = data.system_type;
                            var system_role = data.system_role;
                            formData.append("actionActivate", 'submitActivate');
                            formData.append("user_id", user_id);
                            formData.append("system_type", system_type);
                            formData.append("system_role", system_role);
                            $.ajax({
                                url: '<?php echo BASE_URL; ?>admin/user_process.php',
                                type: 'post',
                                data: formData,
                                dataType: "json",
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    if (response.msg_status === true) {
                                        user_table.setData();
                                        success_notif(response.msg_response);
                                    } else if (data.msg_status === false) {
                                        error_notif(response.msg_response);
                                    }
                                }
                            });
                        }
                    })
                }

            });

            delete_btn.classList.add("btn", "btn-sm", "btn-outline-danger", "btn-rounded", "m-1");
            delete_btn.style.fontSize = "small";
            delete_btn.innerHTML = '<i class="bi bi-trash"></i>&ensp;Delete';
            delete_btn.addEventListener("click", function() {
                swal.fire({
                    title: 'Are you sure you want to delete this account?',
                    showConfirmButton: true,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    showCancelButton: true,
                    cancelButtonColor: '#bd362f',
                    allowOutsideClick: false,
                    customClass: {
                        container: 'popup-modal-container',
                        title: 'title-ff',
                        popup: 'popup-modal',
                        icon: 'popup-modal-icon',
                        actions: 'popup-modal-actions',
                        confirmButton: 'actions-confirm',
                        cancelButton: 'actions-cancel',
                        htmlContainer: 'popup-modal-description'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        var user_id = data.user_id;
                        var system_type = data.system_type;
                        var system_role = data.system_role;
                        formData.append("actionDelete", 'submitDelete');
                        formData.append("user_id", user_id);
                        formData.append("system_type", system_type);
                        formData.append("system_role", system_role);
                        $.ajax({
                            url: '<?php echo BASE_URL; ?>admin/user_process.php',
                            type: 'post',
                            data: formData,
                            dataType: "json",
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.msg_status === true) {
                                    user_table.setData();
                                    success_notif(response.msg_response);
                                } else if (data.msg_status === false) {
                                    error_notif(response.msg_response);
                                }
                            }
                        });
                    }
                })
            });

            if (data.account_status != 'Locked') {
                actionBut.appendChild(edit_btn);
            }

            actionBut.appendChild(delete_btn);
            return cellEl.appendChild(actionBut);
        };

        $('#btn_reset').on('click', function(e) {
            e.preventDefault();
            swal.fire({
                title: 'Are you sure you want to reset password this account?',
                showConfirmButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                showCancelButton: true,
                cancelButtonColor: '#bd362f',
                allowOutsideClick: false,
                customClass: {
                    container: 'popup-modal-container',
                    title: 'title-ff',
                    popup: 'popup-modal',
                    icon: 'popup-modal-icon',
                    actions: 'popup-modal-actions',
                    confirmButton: 'actions-confirm',
                    cancelButton: 'actions-cancel',
                    htmlContainer: 'popup-modal-description'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    var user_id = $('#user_idInfo').val();
                    // var system_type = data.system_type;
                    formData.append("actionReset", 'submitReset');
                    formData.append("user_id", user_id);
                    // formData.append("system_type", system_type);
                    $.ajax({
                        url: '<?php echo BASE_URL; ?>admin/user_process.php',
                        type: 'post',
                        data: formData,
                        dataType: "json",
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.msg_status === true) {
                                $("#update_info").modal('hide');
                                user_table.setData();
                                password_modal(response.msg_password);
                            } else if (data.msg_status === false) {
                                error_notif(response.msg_response);
                            }
                        }
                    });
                }
            })

        });

        $('#btn_unlock').on('click', function(e) {
            e.preventDefault();
            swal.fire({
                title: 'Are you sure you want to Unlock this account?',
                showConfirmButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                showCancelButton: true,
                cancelButtonColor: '#bd362f',
                allowOutsideClick: false,
                customClass: {
                    container: 'popup-modal-container',
                    title: 'title-ff',
                    popup: 'popup-modal',
                    icon: 'popup-modal-icon',
                    actions: 'popup-modal-actions',
                    confirmButton: 'actions-confirm',
                    cancelButton: 'actions-cancel',
                    htmlContainer: 'popup-modal-description'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    var user_id = $('#user_idInfo').val();
                    formData.append("actionUnlock", 'submitUnlock');
                    formData.append("user_id", user_id);
                    $.ajax({
                        url: '<?php echo BASE_URL; ?>admin/user_process.php',
                        type: 'post',
                        data: formData,
                        dataType: "json",
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.msg_status === true) {
                                $("#update_info").modal('hide');
                                user_table.setData();
                                success_notif(response.msg_response);
                            } else if (data.msg_status === false) {
                                error_notif(response.msg_response);
                            }
                        }

                    });
                }
            })

        });

        $('#updateForm').on('submit', function(e) {
            e.preventDefault();
            hideError();
            let formData = jQuery("#updateForm").serializeArray();
            let newData = [{
                name: "update_info",
                value: "update_info"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: '<?php echo BASE_URL; ?>admin/user_process.php',
                type: 'POST',
                data: postData,
                dataType: "json",
                success: function(output) {
                    if (output.msg_status === false) {
                        document.getElementById("err" + output.msg_span).style.display = "block";
                        document.getElementById("err" + output.msg_span).innerHTML = output.msg_response;
                    } else if (output.msg_status === true) {
                        $("#update_info").modal('hide');
                        user_table.setData();
                        success_notif(output.msg_response);
                    } else {
                        document.getElementById("err_system").style.display = "block";
                        document.getElementById("err_system").innerHTML = "Request error, please try again.";
                    }
                },
                async: false
            });
        });

        var data_record = <?php echo json_encode($record) . ";\r\n"; ?>

        /*** split the data_record ***/
        var splitData = [];
        for (var i = 0; i < data_record.length; i++) {
            var entry = data_record[i].split('|');
            var recordData = {
                timestamp: entry[0],
                gen_id: entry[1],
                user: entry[2],
                event: entry[3]
            };
            splitData.push(recordData);
        }

        /*** upload logs table ***/
        const upload_log_table = new Tabulator("#upload_log_table", {
            height: "500px",
            layout: "fitDataStretch",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: true,
            data: splitData,
            columns: [{
                    title: "Date & Time",
                    field: "timestamp",
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 180,
                    hozAlign: "center"
                },
                {
                    title: "General ID",
                    field: "gen_id",
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 180,
                    hozAlign: "center"
                },
                {
                    title: "User",
                    field: "user",
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    minWidth: 200,
                    hozAlign: "center"
                },
                {
                    title: "Uploaded File Status",
                    field: "event",
                    formatter: eventFormatter,
                    vertAlign: 'middle',
                    minWidth: 250,
                    hozAlign: "center"
                }
            ],
            initialSort: [{
                column: "timestamp",
                dir: "desc"
            }]
        });

        function eventFormatter(cell, formatterParams, onRendered) {
            var value = cell.getValue();
            var fileName = value.trim() + '.txt';
            var downloadLink = '<a href="<?php echo BASE_URL; ?>upload/logs/' + fileName + '" target="_blank" download="' + fileName + '" title="DOWNLOAD"><i class="fas fa-download"></i> ' + value + '</a>';
            return downloadLink;
        }

        const birthDatePicker = document.getElementById("birthDateInfo");
        const birth_datePicker = document.getElementById("birth_date");
        var set_server_time = <?php echo "'" . DATE_TIME . "';\r\n"; ?>
        var serverOffset = moment(set_server_time).diff(new Date());
        var now_server = moment();
        now_server.add(serverOffset, 'milliseconds');
        now_server.subtract(15, 'year');
        var dateLimit = now_server.format('YYYY-MM-DD');

        const div_bulk_data = $('#bulk_data');
        const submit_bulk = $('#submit_bulk');
        const upload_other_bulk = $('#upload_other_bulk');
        upload_other_bulk.hide();

        $(birthDatePicker).datetimepicker({
            format: 'YYYY-MM-DD',
            maxDate: dateLimit
        });

        $(birth_datePicker).datetimepicker({
            format: 'YYYY-MM-DD',
            maxDate: dateLimit
        });

        /*** remove the last row added on crud_user_table ***/
        function deleteRow() {
            var tableDefaultRowCount = 2;
            var table = document.getElementById('crud_user_table');
            var rowCount = table.rows.length;
            for (var i = tableDefaultRowCount; i < rowCount; i++) {
                table.deleteRow(tableDefaultRowCount);
            }
        }

        /*** add user modal ***/
        $('#upload_log').on('click', function() {
            $('#upload_log_modal').modal('show');
        });

        /*** add user modal ***/
        $('#add_new_user').on('click', function() {
            $('#add_user_modal').modal('show');
            $('#add_user_modal').find('form').trigger('reset');
            hideError();
            deleteRow();
        });

        // remove the last row added on add_user_table
        function deleteRows() {
            var tableDefaultRowCount = 2;
            var table = document.getElementById('add_user_table');
            var rowCount = table.rows.length;
            for (var i = tableDefaultRowCount; i < rowCount; i++) {
                table.deleteRow(tableDefaultRowCount);
            }
        }

        // add user modal
        $('#add_account').on('show.bs.modal', function() {
            $('#add_acc_form').trigger('reset');
            hideError();
            deleteRows();
        });

        /** listen to the systemAccess select field changes **/
        $(document).on('change', '.systemAccess', function() {
            var row = $(this).closest('tr');
            var systemAccess = $(this).val();
            var systemUserRoleSelect = row.find('.systemUserRole');

            /** reset the systemUserRole select field **/
            systemUserRoleSelect.empty();
            systemUserRoleSelect.append('<option value="" selected disabled>Select System User Role</option>');
            systemUserRoleSelect.prop('disabled', true);

            $.ajax({
                url: '<?php echo BASE_URL; ?>admin/user_process.php',
                type: 'POST',
                data: {
                    "fetch_user_role": systemAccess
                },
                dataType: 'json',
                success: function(response) {
                    if (response.msg_status == "error") {
                        document.getElementById("err_system").innerHTML = response.msg_response;
                        document.getElementById("err_system").style.display = "block";
                    } else if (response.msg_status == "success") {
                        /** populate the systemUserRole select field **/
                        $.each(response.msg_response, function(key, value) {
                            systemUserRoleSelect.append('<option value="' + key + '">' + value + '</option>');
                        });
                        /** enable the systemUserRole select field **/
                        systemUserRoleSelect.prop('disabled', false);
                        systemUserRoleSelect.prop('required', true);
                    } else {
                        document.getElementById("err_system").innerHTML = "Request error, please try again.";
                        document.getElementById("err_system").style.display = "block";
                    }
                },
                async: false
            });
        });

        row_count = 0;

        // add row on add_user_table
        $('#add_row_acc').click(function() {
            row_count = row_count + 1;
            const html_row_user = `<tr id="` + row_count + `" class="">
                                <td style="min-width: 220px;">
                                    <select name="" class="form-control systemAccess" required>
                                        <option value="" selected disabled>Select System Access</option>
                                        <?php
                                        foreach ($system_access as $key => $data) {
                                            echo '<option value="' . $key . '">' . $data['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td style="min-width: 200px;">
                                    <select name="" class="form-control systemUserRole" disabled>
                                        <option value="" selected disabled>Select System User Role</option>
                                    </select>
                                </td>
                                <td style="min-width: 120px;" class="text-center"><button type="button" name="remove_row_sched" id="remove_row_sched" data-row="` + row_count + `" class="btn btn-outline-danger btn-sm remove_row_sched"><i class='bi bi-x-circle'></i> Remove</button></td>
                            </tr>`;
            $('#add_user_table').append(html_row_user);
        });

        /*** add row on crud user table ***/
        $('#add_row_user').click(function() {
            row_count = row_count + 1;
            const html_row_user = `<tr id="` + row_count + `" class="">
                                        <td style="min-width: 220px;">
                                            <select name="" class="form-control systemAccess" required>
                                                <option value="" selected disabled>Select System Access</option>
                                                <?php
                                                foreach ($system_access as $key => $data) {
                                                    echo '<option value="' . $key . '">' . $data['name'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td style="min-width: 200px;">
                                            <select name="" class="form-control systemUserRole" disabled>
                                                <option value="" selected disabled>Select System User Role</option>
                                            </select>
                                        </td>
                                        <td style="min-width: 120px;" class="text-center"><button type="button" name="remove_row_sched" id="remove_row_sched" data-row="` + row_count + `" class="btn btn-outline-danger btn-sm remove_row_sched"><i class='bi bi-x-circle'></i> Remove</button></td>
                                    </tr>`;
            $('#crud_user_table').append(html_row_user);
        });

        /*** remove row on crud user table ***/
        $(document).on('click', '#remove_row_sched', function() {
            var delete_row = $(this).data("row");
            $('#' + delete_row).remove();
        });

        /*** submit the user form ***/
        $("#user_form").on('submit', function(e) {
            e.preventDefault();
            $('#crud_user_table').find('tbody tr').removeClass("table-danger");
            hideError();
            var content = [];
            var row = [];
            var table = document.getElementById('crud_user_table');
            var rows = table.rows;
            var datas = [];
            for (var i = 0; i < rows.length; i++) {
                if (i == 0) continue;
                var rowTr = rows[i];
                rows[i].setAttribute('id', i);
                rows[i].querySelector('button').setAttribute('data-row', i);
                var temp = {};
                temp['systemAccess'] = "";
                temp['systemUserRole'] = "";

                var systemAccess = rowTr.getElementsByClassName('systemAccess')[0].value;
                var systemUserRole = rowTr.getElementsByClassName('systemUserRole')[0].value;

                if (systemAccess) temp['systemAccess'] = systemAccess;
                if (systemUserRole) temp['systemUserRole'] = systemUserRole;

                datas.push(temp);
            }

            // var reference = $('#master_reference').val();
            // var employee_id = $('#employee_name').val();
            const system_info = JSON.stringify(datas);

            let formData = jQuery("#user_form").serializeArray();
            let newData = [{
                name: "actionSubmit",
                value: "submitUser"
            }, {
                name: "system_info",
                value: system_info
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>admin/user_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: function() {
                    $('#user_form :submit').html('<span class="spinner-border spinner-border-sm"></span>Loading..');
                    $("#user_form :input").prop("disabled", true);
                    $("#user_form :button").prop("disabled", true);
                },
                complete: function() {
                    $('#user_form :submit').html('Submit');
                    $("#user_form :input").prop("disabled", false);
                    $("#user_form :button").prop("disabled", false);
                },
                success: function(output) {
                    if (output.row_count != "") {
                        $(output.row_count).addClass("table-danger");
                    }

                    if (output.msg_status == "msg_error") {
                        document.getElementById("err" + output.msg_span).style.display = "block";
                        document.getElementById("err" + output.msg_span).innerHTML = output.msg_response;
                    } else if (output.msg_status == "msg_success") {
                        swal.fire({
                            title: output.msg_response,
                            icon: 'success',
                            showConfirmButton: false,
                            timerProgressBar: true,
                            width: 450,
                            footer: `    `,
                            padding: '1em 0 0',
                            customClass: {
                                popup: 'swal-popup-modal',
                                footer: 'swal-footer-success',
                            },
                            timer: 2000
                        }).then(function() {
                            user_table.setData("<?php echo BASE_URL; ?>admin/user_table.php", {
                                table: "users"
                            });
                            $('#add_user_modal').modal('hide');
                            password_modal(output.password);
                        });
                    } else {
                        document.getElementById("err_system").style.display = "block";
                        document.getElementById("err_system").innerHTML = "Request error, please try again.";
                    }
                },
                error: function(xhr, status, error) {
                    document.getElementById("err_system").style.display = "block";
                    document.getElementById("err_system").innerHTML = status + "::" + error;
                },
                async: false
            });
        });

        $("#add_acc_form").on('submit', function(e) {
            e.preventDefault();
            $('#add_user_table').find('tbody tr').removeClass("table-danger");
            hideError();
            var content = [];
            var row = [];
            var table = document.getElementById('add_user_table');
            var rows = table.rows;
            var datas = [];
            for (var i = 0; i < rows.length; i++) {
                if (i == 0) continue;
                var rowTr = rows[i];
                rows[i].setAttribute('id', i);
                rows[i].querySelector('button').setAttribute('data-row', i);
                var temp = {};
                temp['systemAccess'] = "";
                temp['systemUserRole'] = "";

                var systemAccess = rowTr.getElementsByClassName('systemAccess')[0].value;
                var systemUserRole = rowTr.getElementsByClassName('systemUserRole')[0].value;

                if (systemAccess) temp['systemAccess'] = systemAccess;
                if (systemUserRole) temp['systemUserRole'] = systemUserRole;

                datas.push(temp);
            }
            const system_info = JSON.stringify(datas);

            let formData = jQuery("#add_acc_form").serializeArray();
            let newData = [{
                name: "actionAddacc",
                value: "submitAddacc"
            }, {
                name: "system_info",
                value: system_info
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>admin/user_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: function() {
                    $('#add_acc_form :submit').html('<span class="spinner-border spinner-border-sm"></span>Loading..');
                    $("#add_acc_form :input").prop("disabled", true);
                    $("#add_acc_form :button").prop("disabled", true);
                },
                complete: function() {
                    $('#add_acc_form :submit').html('Submit');
                    $("#add_acc_form :input").prop("disabled", false);
                    $("#add_acc_form :button").prop("disabled", false);
                },
                success: function(output) {
                    if (output.row_count != "") {
                        $(output.row_count).addClass("table-danger");
                    }

                    if (output.msg_status == false) {
                        document.getElementById("err" + output.msg_span).style.display = "block";
                        document.getElementById("err" + output.msg_span).innerHTML = output.msg_response;
                    } else if (output.msg_status == true) {
                        user_table.setData();
                        $('#add_account').modal('hide');
                        swal.fire({
                            title: output.msg_response,
                            icon: 'success',
                            text: output.msg_span,
                            showConfirmButton: false,
                            timerProgressBar: true,
                            width: 450,
                            footer: `    `,
                            padding: '1em 0 0',
                            customClass: {
                                popup: 'swal-popup-modal',
                                footer: 'swal-footer-success',
                            },
                            timer: 5000
                        })
                    } else {
                        document.getElementById("err_systems").style.display = "block";
                        document.getElementById("err_systems").innerHTML = "Request error, please try again.";
                    }
                },
                async: false
            });
        });

        /*** add bulk user modal ***/
        $('#bulk_new_user').on('click', function() {
            $('#add_bulk_user').modal('show');
            $('#add_bulk_user').find('form').trigger('reset');
            hideError();
            upload_other_bulk.hide();
            upload_other_bulk.attr('disabled', true);
            $('#bulk_table').html("");
            div_bulk_data.show();
            submit_bulk.show();
            submit_bulk.attr('disabled', false);
            $('.dropify-clear').click();
        });

        /*** field dropify for bulk user ***/
        $('.bulk_dropify').dropify({
            messages: {
                'default': 'Drag and drop your CSV file here.',
                'replace': 'Drag and drop, or click to replace.',
                'remove': 'Remove',
                'error': 'Ooops, something wrong happended.'
            }
        });

        /*** data for bulk ***/
        var addBulkSchedule = $('#addBulkSchedule').dropify({});
        addBulkSchedule = addBulkSchedule.data('addBulkSchedule');
        $("#addBulkSchedule").change(function() {
            var file = this.files[0];
            var fileType = file.type;
            var match = ['text/csv'];
            if (!((fileType == match[0]) || (fileType == match[1]) || (fileType == match[2]))) {
                error_notif("Invalid file format");
                $('.dropify-clear').click();
                return false;
            }
        });

        /***  submit the bulk schedule ***/
        $("#bulk_user_form").on('submit', function(e) {
            e.preventDefault();
            hideError();
            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>admin/user_bulk_process.php",
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    $("#submit_bulk").attr("disabled", true);
                    $('#submit_bulk').html(' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
                    $(".close_csv_upload").attr("disabled", true);
                },
                complete: function() {
                    $('#submit_bulk').html('Submit');
                    $('#submit_bulk').removeAttr('disabled');
                    $(".close_csv_upload").removeAttr('disabled');
                },
                success: function(output) {
                    if (output.status == false) {
                        document.getElementById("user_bulk_err_msg").style.display = "block";
                        document.getElementById("user_bulk_err_msg").innerHTML = output.msg_error;
                        $('.dropify-clear').click();
                    } else if (output.status == true) {
                        /*** hide the form and submit button ***/
                        $('#add_bulk_user').modal('hide');
                        user_table.setData("<?php echo BASE_URL; ?>admin/user_table.php");

                        var upload_data = JSON.parse(output.upload_record);

                        /*** split the upload_data ***/
                        var upload_log_record = [];
                        for (var i = 0; i < upload_data.length; i++) {
                            var entryLog = upload_data[i].split('|');
                            var recordLogData = {
                                timestamp: entryLog[0],
                                gen_id: entryLog[1],
                                user: entryLog[2],
                                event: entryLog[3]
                            };
                            upload_log_record.push(recordLogData);
                        }

                        upload_log_table.setData(upload_log_record);
                        $('#upload_log_modal').modal('show');
                    } else {
                        $('.dropify-clear').click();
                        document.getElementById("user_bulk_err_msg").style.display = "block";
                        document.getElementById("user_bulk_err_msg").innerHTML = "Request Error.";
                    }
                },
            });

        });

        /*** hide the upload_other_bulk, then show bulk form and button ***/
        $("#upload_other_bulk").on('click', function() {
            upload_other_bulk.hide();
            upload_other_bulk.attr('disabled', true);
            $('#bulk_table').html("");
            div_bulk_data.show();
            submit_bulk.show();
            submit_bulk.attr('disabled', false);
            $('.dropify-clear').click();
        });

        /*** clipboard password ***/
        function password_modal(content_msg) {
            swal.fire({
                title: 'Password',
                html: `<p style="font-size:16px;">Click on the button to copy the text from the text field.</p>
                    <div class="input-group outline-secondary">
                        <input type="text" id="copyPassword" value="` + content_msg + `" autofocus="false" class="form-control" readonly>
                        <div class="input-group-append">
                            <button class="btn copyPasswordBtn" data-clipboard-target="#copyPassword" data-bs-original-title="Copy"><i class="bi bi-back" style="font-size:26px;"></i></button>
                        </div>
                    </div>`,
                showConfirmButton: false,
                cancelButtonText: 'Close',
                showCancelButton: true,
                cancelButtonColor: '#bd362f',
                allowOutsideClick: false,
                customClass: {
                    container: 'popup-modal-container',
                    title: 'title-ff',
                    popup: 'popup-modal',
                    icon: 'popup-modal-icon',
                    actions: 'popup-modal-actions',
                    confirmButton: 'actions-confirm',
                    cancelButton: 'actions-cancel',
                    htmlContainer: 'popup-modal-description'
                }
            })
        }

        $('.copyPasswordBtn').tooltip({
            trigger: 'click',
            placement: 'bottom'
        });

        function setTooltip(message) {
            $('.copyPasswordBtn').tooltip('hide')
                .attr('data-bs-original-title', message)
                .tooltip('show');
        }

        function hideTooltip() {
            setTimeout(function() {
                $('.copyPasswordBtn').tooltip('hide');
            }, 1000);
        }

        var clipboardPassword = new ClipboardJS('.copyPasswordBtn');

        clipboardPassword.on('success', function(e) {
            setTooltip('Copied!');
            hideTooltip();

        });

        clipboardPassword.on('error', function(e) {
            setTooltip('Failed!');
            hideTooltip();

        });
    })();

    <?php ## sweetalert msg session
    $msg_success = $session_class->getValue('msg_success');
    if (isset($msg_success) && $msg_success != "") {
        echo "success_notif('" . $msg_success . "');";
        $session_class->dropValue('msg_success');
    }
    $msg_error = $session_class->getValue('msg_error');
    if (isset($msg_error) && $msg_error != "") {
        echo "error_notif('" . $msg_error . "');";
        $session_class->dropValue('msg_error');
    }
    $msg_password = $session_class->getValue('msg_password');
    if (isset($msg_password) && $msg_password != "") {
        echo "password_modal('" . $msg_password['title'] . "','" . $msg_password['content_msg'] . "');";
        $session_class->dropValue('msg_password');
    }
    ?>
</script>

</html>