<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
set_time_limit(0);
ini_set('max_execution_time', '0');
ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;

# Page Helpers
require_once ADMIN_PAGE_HEADER_PATH;
# Page Header Initialization
$title_page   = "Employee System Access";
$active_links = [
    ['label' => $title_page, 'url' => '']
];
$page_header  = render_page_header((string) $title_page, $active_links) ?? [];
# ===================================================================================

# Validate Access
$system_auth_login = $session_class->getValue(SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['auth']);
if (!($g_user_role == "ADMIN") && !($system_auth_login == $g_public_key)) {
    header("location: " . SYSTEM_ACCESS[SYSTEM_ACCESS_NAME]['link']['main']);
    exit();
}

## uploaded logs
$path = IMPORT_EMPLOYEE_SYSTEM_ACCESS_LOG;
$result = tailCustom($path, 100);
$record = array();
if (!empty($result)) {
    # Break into an array and remove any empty lines
    $record = array_filter(explode("\n", $result));

    # Sort descending by the date prefix
    usort($record, function ($a, $b) {
        // Split by the pipe character '|' to isolate the date
        $dateA = explode('|', $a)[0] ?? '';
        $dateB = explode('|', $b)[0] ?? '';

        # Compare strings in reverse for descending order
        return strcmp($dateB, $dateA);
    });
}
$record = array_values($record);

/**
 * Generates HTML option tags dynamically from any array format.
 *
 * @param array $options The array to loop through.
 * @param bool $useKeysAsValue AsValue Set to true if the array keys should be the option values (e.g., for associative arrays).
 * @param string|int|null $selectedValue The value/key that should be pre-selected.
 */
function generateSelectOptions(array $options, bool $useKeysAsValue = false, $selectedValue = null)
{
    foreach ($options as $key => $value) {
        // Determine what goes into the value="" attribute
        $optionValue = $useKeysAsValue ? $key : $value;

        // Check if this option is selected (loose comparison handles string/integer matching)
        $selected = ($selectedValue !== null && $optionValue == $selectedValue) ? ' selected' : '';

        echo '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>';
        echo htmlspecialchars($value);
        echo '</option>'; // PHP_EOL for clean HTML source code output
    }
}

## System Acccess
$system_access = isset(ROLE_PERMISSION[$g_user_role]) ? $helper->filterSystemAccess(SYSTEM_ACCESS, ROLE_PERMISSION[$g_user_role]) : array();


$action_buttons = [
    [
        'label' => 'Add Employee System Access',
        'icon'  => 'bi bi-plus-circle',
        'class' => 'add-system-employee-btn',
        'type'  => 'button'
    ],
    [
        'label' => 'Import Bulk Employee System Access',
        'icon'  => 'bi bi-arrow-bar-up bulk',
        'class' => 'bulk-system-employee-btn',
        'type'  => 'button'
    ]
];

$my_links = [
    [
        'label'  => 'Download Template CSV File',
        'icon'   => 'bi bi-download',
        'href'   => BASE_URL . 'download?attach=IMP_BLK_EMPSYS',
        'target' => '_blank',
        'class'  => 'text-decoration-none text-primary'
    ],
    [
        'label'  => 'View Uploaded Logs',
        'icon'   => 'bi bi-eye',
        'href'   => '#',
        'id'     => 'view_log', // Keeps your script selector clean!
        'class'  => 'text-decoration-none text-secondary'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include_once ADMIN_META_DATA_PATH; ?>
    <?php include_once ADMIN_LINK_PATH; ?>
</head>

<body>
    <div class="wrapper">

        <?php include_once ADMIN_SIDEBAR_PATH; ?>
        <div class="main-panel">

            <?php include_once ADMIN_HEADER_PATH; ?>

            <div class="container">
                <div class="page-inner">
                    <!-- Page Header -->
                    <?php echo $page_header; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">

                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <div class="d-flex justify-content-between align-items-start align-items-md-center gap-3 w-100">

                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                                <i class="bi bi-people-fill"></i>
                                            </div>

                                            <div>
                                                <div class="text-uppercase text-muted fw-bold tracking-wide small" style="font-size: 0.95rem;">
                                                    Employee System Access
                                                </div>
                                                <div class="d-flex align-items-center text-muted small mt-0.5" style="font-size: 0.75rem;">
                                                    <i class="bi bi-info-circle me-1"></i> lorem info
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-start align-items-md-end gap-2 ms-md-auto">
                                            <?php render_header_button($action_buttons); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="my-2 justify-content-center">
                                        <small class="text-muted"><b>Legend:</b>
                                            <span class="badge bg-primary text-white rounded-1 p-2 mt-1 text-sm-start mx-1 text-wrap">Account Status</span>
                                            <span class="badge bg-info text-white rounded-1 p-2 mt-1 text-sm-start mx-1 text-wrap">Login Status</span>
                                            <span class="badge bg-warning text-white rounded-1 p-2 mt-1 text-sm-start mx-1 text-wrap">Reset Password</span>
                                            <span class="badge bg-success text-white rounded-1 p-2 mt-1 text-sm-start mx-1 text-wrap">Add System Account</span>
                                        </small>
                                    </div>

                                    <!-- Toolbar with Search and Export Buttons -->
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                        <div class="search-input-group flex-grow-1">
                                            <i class="bi bi-search search-icon"></i>
                                            <input type="text" id="global-search" class="form-control" placeholder="Search article title, category, author...">
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <!-- Export Options Dropdown -->
                                            <div class="dropdown">
                                                <button class="btn btn-action dropdown-toggle d-inline-flex align-items-center" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-download me-2"></i>Export / Download
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="exportDropdown">
                                                    <li><a class="dropdown-item text-slate-700" href="javascript:void(0)" id="export-csv"><i class="bi bi-filetype-csv text-success me-2"></i> Export CSV</a></li>
                                                    <li><a class="dropdown-item text-slate-700" href="javascript:void(0)" id="export-excel"><i class="bi bi-file-earmark-excel text-success me-2"></i> Export Excel (.xlsx)</a></li>
                                                    <li><a class="dropdown-item text-slate-700" href="javascript:void(0)" id="export-json"><i class="bi bi-filetype-json text-warning me-2"></i>Export JSON</a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item text-slate-700" href="javascript:void(0)" id="export-print"><i class="bi bi-printer text-primary me-2"></i>Print Table</a></li>
                                                </ul>
                                            </div>

                                            <button id="btn-refresh" class="btn btn-action d-inline-flex align-items-center">
                                                <i class="bi bi-arrow-clockwise me-2"></i>Refresh Data
                                            </button>
                                        </div>

                                        <div id="tabulator-container" class="table table-bordered tabulator" style="min-height: 600px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- modal -->
                    <!-- add system modal -->
                    <div class="modal fade" id="add_system_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h5 class="modal-title" id="exampleModalToggleLabel">ADD EMPLOYEE SYSTEM ACCESS</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="employee_system_form">
                                    <div class="modal-body">

                                        <div class="row p-2">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Account Information (AI)</strong></h5>
                                                    <hr>

                                                    <input type="hidden" name="user_id" id="user_id">
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="name"><b>Full Name</b> <span class="required-field"></span></label>
                                                        <select id="full_name" name="full_name" placeholder="Select Full Name" required>
                                                            <option value=""></option>
                                                        </select>
                                                        <small class="text-muted"><b>Note:</b> Only users with existing employee information can be added.</small>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="employee_id"><b>Employee ID number</b></label>
                                                        <input type="text" name="employee_id" class="form-control" id="employee_id" placeholder="Employee ID number" readonly>
                                                    </div>

                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="username"><b>Username</b> <span class="required-field"></span></label>
                                                        <input type="text" name="username" class="form-control" id="username" placeholder="Username" minlength="6" maxlength="30" required>
                                                        <small class="text-muted">Username must be between 6 and 30 characters.</small>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="email" class="form-control" id="email" placeholder="CCC Email Address" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>System Access (SA)</strong></h5>
                                                    <hr>

                                                    <div class="table_add_container" style="overflow:auto">
                                                        <input type="hidden" name="user_idinfo" id="user_idinfo">
                                                        <table class="table table-bordered" id="add_user_table" data-systems='<?php echo json_encode($system_access); ?>'>
                                                            <thead>
                                                                <tr>
                                                                    <th class="p-2">System Access <span class="required-field"></span></th>
                                                                    <th class="p-2">System User Role <span class="required-field"></span></th>
                                                                    <th class="p-2 text-center">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr class="row_sched">
                                                                    <td class="p-2" style="min-width: 220px;">
                                                                        <select name="system_name[]" class="form-control systemAccess" required>
                                                                            <option value="" selected disabled>Select System Access</option>
                                                                            <?php
                                                                            foreach ($system_access as $key => $data) {
                                                                                echo '<option value="' . $key . '">' . $data['name'] . '</option>';
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </td>
                                                                    <td class="p-2" style="min-width: 200px;">
                                                                        <select name="system_role[]" class="form-control systemUserRole" disabled required>
                                                                            <option value="" selected disabled>Select System User Role</option>
                                                                        </select>
                                                                    </td>
                                                                    <td class="align-content-center p-2 text-center" style="min-width: 120px;" class="text-center">
                                                                        <button type="button" id="add_row_acc" class="btn btn-outline-success btn-sm btn-rounded">
                                                                            <i class="bi bi-plus-circle"></i> Add Row
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger rounded-3" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-secondary rounded-3" id="btn_submit" name="actionSubmit" value="submitUser">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- add account system modal -->
                    <div class="modal fade" id="add_account_system_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h5 class="modal-title" id="exampleModalToggleLabel">ADD EMPLOYEE SYSTEM ACCESS</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="employee_account_system_form">
                                    <div class="modal-body">

                                        <div class="row p-2">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Account Information (AI)</strong></h5>
                                                    <hr>

                                                    <input type="hidden" name="a-user_id" id="a-user_id">
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="a-name"><b>Full Name</b></label>
                                                        <input type="text" name="a-name" class="form-control" id="a-name" placeholder="Employee ID number" readonly>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="a-employee_id"><b>Employee ID number</b></label>
                                                        <input type="text" name="a-employee_id" class="form-control" id="a-employee_id" placeholder="Employee ID number" readonly>
                                                    </div>

                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="a-username"><b>Username</b></label>
                                                        <input type="text" name="a-username" class="form-control" id="a-username" placeholder="Username" readonly>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="a-email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="a-email" class="form-control" id="a-email" placeholder="CCC Email Address" readonly>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>System Access (SA)</strong></h5>
                                                    <hr>

                                                    <div class="table_add_system_account_container" style="overflow:auto">
                                                        <table class="table table-bordered" id="add_system_account_table" data-systems='<?php echo json_encode($system_access); ?>'>
                                                            <thead>
                                                                <tr>
                                                                    <th class="p-2">System Access <span class="required-field"></span></th>
                                                                    <th class="p-2">System User Role <span class="required-field"></span></th>
                                                                    <th class="p-2 text-center">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr class="row_sched">
                                                                    <td class="p-2" style="min-width: 220px;">
                                                                        <select name="a-system_name[]" class="form-control a-systemAccess" required>
                                                                            <option value="" selected disabled>Select System Access</option>
                                                                            <?php
                                                                            foreach ($system_access as $key => $data) {
                                                                                echo '<option value="' . $key . '">' . $data['name'] . '</option>';
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </td>
                                                                    <td class="p-2" style="min-width: 200px;">
                                                                        <select name="a-system_role[]" class="form-control a-systemUserRole" disabled required>
                                                                            <option value="" selected disabled>Select System User Role</option>
                                                                        </select>
                                                                    </td>
                                                                    <td class="align-content-center p-2 text-center" style="min-width: 120px;" class="text-center">
                                                                        <button type="button" id="add_row_system_acc" class="btn btn-outline-success btn-sm btn-rounded">
                                                                            <i class="bi bi-plus-circle"></i> Add Row
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger rounded-3" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-secondary rounded-3" id="btn_submit" name="actionSubmit" value="submitUser">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- update account modal -->
                    <div class="modal fade" id="update_account_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h6 class="modal-title" id="exampleModalToggleLabel">UPDATE ACCOUNT INFORMATION</span></h6>
                                    <button type="button" id="close" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="update_account_form" name="update_account_form" method="post" action="/">

                                    <div class="modal-body">

                                        <div class="row p-2">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Basic Information (BI)</strong></h5>
                                                    <hr>

                                                    <input type="hidden" name="u-account-id" id="u-account-id">
                                                    <div class="col-lg-5 mb-2">
                                                        <label class="form-label" for="u-account-name"><b>Full Name</b></label>
                                                        <input type="text" name="u-name" class="form-control" id="u-account-name" placeholder="Full Name" readonly>
                                                    </div>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="u-account-employee_id"><b>Employee ID number</b></label>
                                                        <input type="email" name="u-account-employee_id" class="form-control" id="u-account-employee_id" placeholder="CCC Email Address" readonly>
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-account-email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="u-account-email" class="form-control" id="u-account-email" placeholder="CCC Email Address" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Account Information (AI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-7 mb-2">
                                                        <label class="form-label" for="u-account-username"><b>Username</b> <span class="required-field"></span></label>
                                                        <input type="text" name="u-account-username" class="form-control" id="u-account-username" placeholder="Employee ID number" required>
                                                        <small class="text-muted">Username must be between 6 and 30 characters.</small><br>
                                                    </div>
                                                    <div class="col-lg-5 mb-2">
                                                        <label class="form-label" for="u-account-status"><b>Account Status</b> <span class="required-field"></span></label>
                                                        <select name="u-account-status" id="u-account-status" class="form-control" required>
                                                            <option value="" selected disabled>Account Status</option>
                                                            <?php generateSelectOptions(ACCOUNT_STATUS, true); ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-12 mb-2">
                                                        <label class="form-label" for="u-account-personal_email"><b>Personal Email Address</b></label>
                                                        <input type="email" name="u-account-personal_email" class="form-control" id="u-account-personal_email" placeholder="Personal Email Address">
                                                        <small class="text-muted">e.g. xxxxxx@gmail.com</small><br>
                                                        <small class="text-muted" style="font-size: x-small;">Note: If provided, this will serve as your backup recovery email address.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger rounded-3" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-secondary rounded-3" id="btn_info" name="actionInfo" value="updateInfo">Update Account Information</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- add bulk user modal -->
                    <div class="modal fade" id="add_bulk_employee_system" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkSchedBackdropLabel">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h5 class="modal-title" id="bulkSchedBackdropLabel">Import Bulk Employee System Access</h5>
                                    <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form autocomplete="off" id="bulk_employee_system_form" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <?php render_action_links($my_links); ?>

                                        <h5 class="mb-1">
                                            <div id="employee_bulk_err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></div>
                                        </h5>

                                        <input type="file" id="import_employee_sytem_access" class="bulk_dropify" styles="height:500px" data-default-file="" name="import_employee_sytem_access" accept="text/csv" required>
                                        <small class="text-muted" style="font-size:small;"><b class="text-primary">NOTE:</b> Only users with existing employee information can be added. Bulk imports are strictly used for adding new system access and rely entirely on the <b class="text-primary">INSTITUTIONAL EMAIL</b>. All optional columns may be left blank.</small>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger rounded-3" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-secondary rounded-3" id="submit_bulk">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php include_once ADMIN_FOOTER_PATH; ?>
        </div>
    </div>
</body>

<?php include_once ADMIN_SCRIPT_PATH; ?>

<script>
    (function() {
        let tabulator = null;

        /* Tabulator Table Setup */
        function initTabulatorTable() {
            const remoteTableUrl = "<?php echo BASE_URL; ?>table/employee-system-access-table";

            tabulator = new Tabulator("#tabulator-container", {
                ajaxURL: remoteTableUrl,
                ajaxLoader: true,
                ajaxLoaderLoading: 'Fetching data from Database..',
                ajaxConfig: {
                    method: "GET",
                    headers: {
                        "Content-type": 'application/json; charset=utf-8',
                    },
                },
                ajaxParams: {
                    load_all: 0,
                },

                // Tabulator v5+ parameter naming (Replaced obsolete dataSendParams)
                paginationDataSent: {
                    "page": "page",
                    "size": "size",
                    "sorters": "sorters",
                    "filters": "filters"
                },

                ajaxResponse: function(url, params, response) {
                    return response.data;
                },

                filterMode: "remote",
                sortMode: "remote",
                headerFilterPlaceholder: "Search",

                pagination: "remote",
                paginationSize: 10,
                paginationSizeSelector: [10, 25, 50, 100, true],

                paginationCounter: function(pageSize, currentRow, currentPage, totalRows, totalPages) {
                    const count = totalRows || 0;
                    if (count === 0) return "Showing data 0 of 0 entries";

                    let effPageSize = (pageSize === true || pageSize >= count) ? count : pageSize;
                    let start = (currentPage - 1) * effPageSize + 1;
                    let end = Math.min(start + effPageSize - 1, count);

                    if (pageSize === true || effPageSize >= count) {
                        return `Showing all data (${count} entries)`;
                    }
                    return `Showing data ${start} to ${end} of ${count} entries`;
                },

                paginationDataReceived: {
                    "last_page": "last_page",
                    "data": "data"
                },

                downloadRowRange: "all",

                height: "800px",
                headerHozAlign: 'center',
                layout: "fitColumns",
                placeholder: "No Record Found",

                groupBy: function(data) {
                    return data.name + (!data.employee_id ? " " : " [" + data.employee_id + "] ");
                },

                groupHeaderPrint: function(value, count, data, group) {
                    // return `<strong>${value}</strong> (${count} items)`;
                    const result = btnInfo(value, count, data, group);
                    if (result instanceof HTMLElement) return result.outerHTML;
                    return result;
                },

                groupHeader: btnInfo,
                groupUpdateOnCellEdit: true,

                printAsHtml: true,
                printFormatter: false,
                printConfig: {
                    columnGroups: false,
                    rowGroups: true,
                },
                downloadConfig: {
                    columnHeaders: true,
                    columnGroups: false,
                    rowGroups: false,
                    formatCells: false
                },

                columns: [{
                        title: "Action",
                        field: "action",
                        minWidth: 180,
                        headerSort: false,
                        download: false,
                        print: false,
                        htmlOutput: false,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        formatter: function(cell) {
                            const rowData = cell.getRow().getData();
                            const status = String(rowData.flag_approved ?? '0');

                            let classBtn = "btn btn-action d-inline-flex align-items-center justify-content-center px-2 py-1 me-1 mb-2 gap-2";
                            let buttons = ``;
                            let label = ``;
                            let icon = ``;
                            let classAction = ``;

                            let system_access = String(rowData.flag_access ?? '0');
                            let access_id = rowData.id ?? '0';

                            if (system_access === '0') {
                                label = "Deactivate System Access";
                                icon = "bi-person-fill-slash";
                                classAction = "btn-deactivate-access-row";

                                buttons += `<button type="button" class="${classBtn} ${classAction}" title="${label}"><i class="bi ${icon}"></i><span style="font-size: 0.75rem;">${label}</span></button>`;
                            } else if (system_access === '1') {
                                label = "Activate System Access";
                                icon = "bi-person-fill-up";
                                classAction = "btn-activate-access-row";

                                buttons += `<button type="button" class="${classBtn} ${classAction}" title="${label}"><i class="bi ${icon}"></i><span style="font-size: 0.75rem;">${label}</span></button>`;
                            }

                            return buttons;
                        },
                        cellClick: function(e, cell) {
                            const rowData = cell.getRow().getData();
                            let access_id = rowData.id ?? '0';
                            if (e.target.closest('.btn-deactivate-access-row')) {

                                notifyConfirm(
                                    'Confirm Deactivation',
                                    'Are you sure you want to deactivate this system access?',
                                    'Yes, deactivate it!',
                                    'question'
                                ).then((isConfirmed) => {
                                    if (!isConfirmed) return;

                                    const formData = new FormData();
                                    formData.append("actionDeactivateAccountSystem", 'submitDeactivateAccountSystem');
                                    formData.append("access_id", access_id);

                                    $.ajax({
                                        url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                                        type: 'post',
                                        data: formData,
                                        dataType: "json",
                                        processData: false,
                                        contentType: false,
                                        success: function(output) {
                                            if (output && output.msg_status === true) {
                                                if (typeof tabulator !== 'undefined' && typeof tabulator.setData === 'function') tabulator.setData();
                                                showSuccess(output.msg_response);
                                            } else if (output && output.msg_status === false) {
                                                showError(output.msg_response);
                                            } else {
                                                showError("Request error, please try again");
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            showError(status + "::" + error);
                                        },
                                    });
                                });
                            } else if (e.target.closest('.btn-activate-access-row')) {
                                notifyConfirm(
                                    'Confirm Activation',
                                    'Are you sure you want to activate this system access?',
                                    'Yes, activate it!',
                                    'question'
                                ).then((isConfirmed) => {
                                    if (!isConfirmed) return;

                                    const formData = new FormData();
                                    formData.append("actionActivateAccountSystem", 'submitActivateAccountSystem');
                                    formData.append("access_id", access_id);
                                    $.ajax({
                                        url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                                        type: 'post',
                                        data: formData,
                                        dataType: "json",
                                        processData: false,
                                        contentType: false,
                                        success: function(output) {
                                            if (output && output.msg_status === true) {
                                                if (tabulator && typeof tabulator.setData === 'function') tabulator.setData();
                                                showSuccess(output.msg_response);
                                            } else if (output && output.msg_status === false) {
                                                showError(output.msg_response);
                                            } else {
                                                showError("Request error, please try again");
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            showError(status + "::" + error);
                                        },
                                    });
                                });
                            }
                        }
                    },
                    {
                        title: "System Status",
                        field: "flag_access",
                        minWidth: 150,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All",
                                ...<?php echo json_encode(array_combine(SYSTEM_STATUS, SYSTEM_STATUS)); ?>
                            },
                        },
                        formatter: function(cell) {
                            var key = cell.getValue();
                            var mapping = <?php echo json_encode(SYSTEM_STATUS ?? []); ?>;
                            return mapping[key] !== undefined ? mapping[key] : key;
                        },
                        accessorDownload: function(value) {
                            var mapping = <?php echo json_encode(SYSTEM_STATUS ?? []); ?>;
                            return mapping[value] !== undefined ? mapping[value] : value;
                        },
                    },
                    {
                        title: "System Access",
                        field: "system_type",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All",
                                ...<?php echo json_encode(array_combine(array_keys(SYSTEM_ACCESS), array_keys(SYSTEM_ACCESS))); ?>
                            }
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "System Role",
                        field: "system_role_access",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "input",
                        headerFilterFunc: "like",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            allowEmpty: true
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Name",
                        field: "name",
                        minWidth: 250,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
                        formatter: 'textarea',
                    },
                    {
                        title: "First Name",
                        field: "first_name",
                        visible: false,
                        print: false,
                        download: true
                    },
                    {
                        title: "Middle Name",
                        field: "middle_name",
                        visible: false,
                        print: false,
                        download: true
                    },
                    {
                        title: "Last Name",
                        field: "last_name",
                        visible: false,
                        print: false,
                        download: true
                    },
                    {
                        title: "Suffix Name",
                        field: "suffix",
                        visible: false,
                        print: false,
                        download: true
                    },
                    {
                        title: "Employee ID",
                        field: "employee_id",
                        headerFilter: "input",
                        headerFilterFunc: "like",
                        headerFilterParams: {
                            allowEmpty: true
                        },
                        headerFilterLiveFilter: false,
                        formatter: 'textarea',
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        minWidth: 170,
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
                        hozAlign: 'center',
                        minWidth: 170,
                    },
                    {
                        title: "CCC Email Address",
                        field: "email",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            allowEmpty: true
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Personal/Recovery Email",
                        field: "personal_email",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            allowEmpty: true
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Account Status",
                        field: "status",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All",
                                ...<?php echo json_encode(array_combine(ACCOUNT_STATUS, ACCOUNT_STATUS)); ?>
                            },
                        },
                        formatter: function(cell) {
                            var key = cell.getValue();
                            var mapping = <?php echo json_encode(ACCOUNT_STATUS); ?>;
                            return mapping[key] !== undefined ? mapping[key] : key;
                        },
                        accessorDownload: function(value) {
                            var mapping = <?php echo json_encode(ACCOUNT_STATUS); ?>;
                            return mapping[value] !== undefined ? mapping[value] : value;
                        },
                    },
                    {
                        title: "Login Status",
                        field: "locked",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All",
                                ...<?php echo json_encode(array_combine(LOGIN_STATUS, LOGIN_STATUS)); ?>
                            },
                        },
                        formatter: function(cell) {
                            var key = cell.getValue();
                            var mapping = <?php echo json_encode(LOGIN_STATUS); ?>;
                            return mapping[key] !== undefined ? mapping[key] : key;
                        },
                        accessorDownload: function(value) {
                            var mapping = <?php echo json_encode(LOGIN_STATUS); ?>;
                            return mapping[value] !== undefined ? mapping[value] : value;
                        },
                    },
                    {
                        title: "Date Added/Modify",
                        field: "date_modify",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        htmlOutput: false,
                        print: false,
                        download: false,
                        formatter: 'textarea',
                    },
                ]
            });

            // --- EXPORT EVENT HANDLERS ---
            document.getElementById('export-csv')?.addEventListener('click', () => {
                tabulator.download("csv", "user_information.csv");
            });

            document.getElementById('export-excel')?.addEventListener('click', () => {
                tabulator.download("xlsx", "user_information.xlsx", {
                    sheetName: "Users"
                });
            });

            document.getElementById('export-json')?.addEventListener('click', () => {
                tabulator.download("json", "user_information.json");
            });

            document.getElementById("export-print")?.addEventListener("click", function() {
                const tableTitle = 'Employee Information Report';
                const tableHtml = tabulator.getHtml("active", "print");
                const printWindow = window.open("", "_blank");

                printWindow.document.write(`<!DOCTYPE html><html><head><title>${tableTitle}</title><style>body {font-family: Arial, sans-serif;padding: 20px;color: #333;}table {width: 100%;border-collapse: collapse;margin-top: 10px;}th, td {border: 1px solid #ddd;padding: 8px 12px;text-align: left;font-size: 12px;}th {background-color: #f2f2f2;font-weight: bold;}tr:nth-child(even) {background-color: #f9f9f9;}@page {size: auto;margin: 15mm;}</style></head><body><h2>${tableTitle}</h2>${tableHtml}</body></html>`);

                printWindow.document.close();
                printWindow.focus();

                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            });

            // --- GLOBAL SEARCH HANDLER ---
            const searchInput = document.getElementById('global-search');
            if (searchInput) {
                let searchDebounceTimeout;
                searchInput.addEventListener('keyup', function() {
                    clearTimeout(searchDebounceTimeout);
                    const query = this.value.trim();

                    searchDebounceTimeout = setTimeout(() => {
                        tabulator.setData(remoteTableUrl, {
                            load_all: 0,
                            search: query
                        });
                    }, 300);
                });
            }

            // --- REFRESH DATA HANDLER ---
            const refreshBtn = document.getElementById('btn-refresh');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    if (searchInput) searchInput.value = '';
                    tabulator.clearFilter(true);
                    tabulator.clearHeaderFilter();
                    tabulator.replaceData();
                });
            }
        }

        /* Initialize Tabulator Table */
        initTabulatorTable();

        /*** VIEW UPLOADED LOGS ***/
        addListener(document.getElementById('view_log'), "click", function() {
            var json = <?php echo output($record); ?>;
            var generated_table = "<table class='table table-responsive table-bordered text-center'><tr style='border-width:1!important;'><th style='border-width: 1!important;'>DATE</th><th style='border-width: 1!important;'>USER</th><th style='border-width: 1!important;'>LOG</th></tr>";
            forEach(json, function(val, i) {
                var base_url = "<?php echo BASE_STORAGE_LOGS_PATH; ?>";
                var data = val.split("|");
                generated_table += "<tr style='border-width:1!important;'><td style='border-width:1!important;'>" + data[0] + "</td><td>" + data[1] + "</td><td style='border-width: 1!important;'><a href='" + base_url + data[2] + ".txt' target='_blank' download> " + data[2] + "</a></td></tr>";
            });
            generated_table += "</table>";

            var swal_html = '<div class="panel text-start"><div class="panel-heading panel-info  btn-info"> <b></b> </div> <div class="panel-body"><div style="overflow-y:auto;height:500px">' + generated_table + '</div></div></div>';
            Swal.fire({
                title: "Uploaded User Information Logs",
                html: swal_html,
                allowOutsideClick: false,
                confirmButtonText: 'Close',
                width: '800px',
            }).then((result) => {
                if (result.isConfirmed) {}
            });

        });

        /*** add modal ***/
        $('.add-system-employee-btn').on('click', function() {
            $('#add_system_modal').modal('show');
            $('#add_system_modal').find('form').trigger('reset');
        });

        var $select = $('#full_name').selectize({
            valueField: 'user_id',
            labelField: 'name',
            searchField: ['name'],
            create: false,
            createOnBlur: false,
            persist: false,
            dropdownParent: "body",
            // NEW: Listen for selection changes
            onChange: function(value) {
                if (!value) {
                    $('#user_id').val('');
                    $('#employee_id').val('');
                    $('#email').val('');
                    $('#username').val('');
                    return;
                }

                // Grab the full data object of the selected item from Selectize memory
                var selectedItem = this.options[value];

                if (selectedItem && (selectedItem.user_id && selectedItem.email)) {
                    $('#user_id').val(selectedItem.user_id);
                    $('#employee_id').val(selectedItem.employee_id);
                    $('#email').val(selectedItem.email);
                    $('#username').val(selectedItem.email);
                } else {
                    $('#user_id').val('');
                    $('#employee_id').val('');
                    $('#email').val('');
                    $('#username').val('');
                }
            },
            render: {
                option: function(item, escape) {
                    return '<div><h5 class="px-3">' + escape(item.name) + '</h5></div>';
                },
                item: function(item, escape) {
                    return '<div><span class="">' + escape(item.name) + '</span></div>';
                }
            }
        });

        var selectizeControl = $select[0].selectize;
        /*** Reset email input along with Selectize when the modal opens ***/
        $('#add_system_modal').on('shown.bs.modal', function() {
            selectizeControl.clear();
            selectizeControl.clearOptions();
            $('#user_id').val('');
            $('#employee_id').val('');
            $('#email').val('');
            $('#username').val('');

            selectizeControl.load(function(callback) {
                $.ajax({
                    url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process?action=fetchEmployeeSystemAdd",
                    type: 'GET',
                    dataType: 'json',
                    error: function() {
                        console.log('Error fetching employee data.');
                        callback();
                    },
                    success: function(res) {
                        if (Array.isArray(res)) {
                            callback(res);
                        } else {
                            callback();
                        }
                    }
                });
            });
        });

        /** System Access Selection */
        const system_table = document.getElementById("add_user_table");
        const systemtableBody = system_table.querySelector("tbody");
        const addRowBtn = document.getElementById("add_row_acc");

        // Parse the system data map passed from PHP
        const systemData = JSON.parse(system_table.getAttribute("data-systems"));

        // Helper: Normalize roles format into an array of strings
        function getRolesArray(systemKey) {
            if (!systemData[systemKey] || !systemData[systemKey]['role']) return [];
            const roles = systemData[systemKey]['role'];
            return Array.isArray(roles) ? roles : Object.keys(roles);
        }

        // Main function to synchronize system and role dropdown states
        function syncDropdownRestrictions() {
            const rows = systemtableBody.querySelectorAll("tr");

            // 1. Gather what is currently selected across the table
            const takenPairs = []; // tracks full combinations e.g., ["eams:admin"]
            const systemCounts = {}; // tracks how many roles are taken per system e.g., { eams: 1 }

            rows.forEach(row => {
                const sysVal = row.querySelector(".systemAccess").value;
                const roleVal = row.querySelector(".systemUserRole").value;

                if (sysVal) {
                    systemCounts[sysVal] = (systemCounts[sysVal] || 0) + (roleVal ? 1 : 0);
                    if (roleVal) {
                        takenPairs.push(`${sysVal}:${roleVal}`);
                    }
                }
            });

            // 2. Filter System Dropdowns
            document.querySelectorAll(".systemAccess").forEach(select => {
                const currentSysVal = select.value;

                Array.from(select.options).forEach(option => {
                    if (option.value === "") return;

                    const sysKey = option.value;
                    const totalAvailableRoles = getRolesArray(sysKey).length;
                    const takenRolesCount = systemCounts[sysKey] || 0;

                    // Condition: If all roles are taken, and it's NOT the system this row currently has selected
                    if (takenRolesCount >= totalAvailableRoles && sysKey !== currentSysVal) {
                        option.style.display = "none";
                        option.disabled = true;
                    } else {
                        option.style.display = "block";
                        option.disabled = false;
                    }
                });
            });

            // 3. Filter Role Dropdowns based on active pairs
            rows.forEach(row => {
                const sysSelect = row.querySelector(".systemAccess");
                const roleSelect = row.querySelector(".systemUserRole");
                const currentSys = sysSelect.value;
                const currentRole = roleSelect.value;

                if (!currentSys) return;

                Array.from(roleSelect.options).forEach(option => {
                    if (option.value === "") return;

                    const testPair = `${currentSys}:${option.value}`;

                    // If this pair is taken elsewhere, hide it from this dropdown
                    if (takenPairs.includes(testPair) && option.value !== currentRole) {
                        option.style.display = "none";
                        option.disabled = true;
                    } else {
                        option.style.display = "block";
                        option.disabled = false;
                    }
                });
            });
        }

        // Populate roles when system changes
        systemtableBody.addEventListener("change", function(e) {
            if (e.target.classList.contains("systemAccess")) {
                const row = e.target.closest("tr");
                const roleSelect = row.querySelector(".systemUserRole");
                const selectedSystemKey = e.target.value;

                roleSelect.innerHTML = '<option value="" selected disabled>Select System User Role</option>';

                if (selectedSystemKey) {
                    roleSelect.disabled = false;
                    const availableRoles = systemData[selectedSystemKey]['role'];

                    if (Array.isArray(availableRoles)) {
                        availableRoles.forEach(role => {
                            roleSelect.innerHTML += `<option value="${role}">${role}</option>`;
                        });
                    } else {
                        for (const [roleKey, roleName] of Object.entries(availableRoles)) {
                            roleSelect.innerHTML += `<option value="${roleKey}">${roleName}</option>`;
                        }
                    }
                } else {
                    roleSelect.disabled = true;
                }
                syncDropdownRestrictions();
            }

            if (e.target.classList.contains("systemUserRole")) {
                syncDropdownRestrictions();
            }
        });

        // Handle adding dynamic rows safely with all required attributes
        addRowBtn.addEventListener("click", function() {
            const firstRow = systemtableBody.querySelector("tr");
            const newRow = firstRow.cloneNode(true);

            const systemSelect = newRow.querySelector(".systemAccess");
            const roleSelect = newRow.querySelector(".systemUserRole");

            // Completely reset fields in the cloned row
            systemSelect.value = "";
            systemSelect.required = true; // Double-enforce requirement

            roleSelect.innerHTML = '<option value="" selected disabled>Select System User Role</option>';
            roleSelect.disabled = true;
            roleSelect.required = true; // Double-enforce requirement

            const actionTd = newRow.querySelector("td:last-child");
            actionTd.innerHTML = `<button type="button" class="btn btn-outline-danger btn-sm btn-rounded remove-row"><i class="bi bi-trash"></i>&ensp;Remove</button>`;

            systemtableBody.appendChild(newRow);
            syncDropdownRestrictions();
        });

        // Handle removing dynamic rows
        systemtableBody.addEventListener("click", function(e) {
            if (e.target.closest(".remove-row")) {
                e.target.closest("tr").remove();
                syncDropdownRestrictions();
            }
        });


        /*** submit the form ***/
        $("#employee_system_form").on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to save this employee information?',
                'Yes, save it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            let formData = jQuery("#employee_system_form").serializeArray();
            let newData = [{
                name: "actionSubmitEmployeeSystem",
                value: "submitUserEmployeeSystem"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                method: "POST",
                data: postData,
                dataType: "json",
                // REMOVED async: false so that loading indicators work perfectly
                beforeSend: function() {
                    showLoader('Saving information...');
                    $submitBtn.data('orig-text', $submitBtn.html());
                    $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading..');
                    $inputs.prop("disabled", true);
                },
                complete: function() {
                    hideLoader();
                    $submitBtn.html($submitBtn.data('orig-text') || 'Submit');
                    $inputs.prop("disabled", false);
                },
                success: function(output) {
                    if (output && output.msg_status === true) {
                        if (tabulator && typeof tabulator.setData === 'function') tabulator.setData();
                        $("#employee_system_form")[0].reset();
                        $("#add_system_modal").modal('hide');
                        showSuccess(output.msg_response);
                        password_modal(output.password);
                    } else if (output && output.msg_status === false) {
                        showError(output.msg_response);
                    } else {
                        showError("Request error, please try again");
                    }
                },
                error: function(xhr, status, error) {
                    showError(status + "::" + error);
                }
            });
        });

        function btnInfo(value, count, data, group) { //for updating information
            var span = document.createElement("div");
            let edit_btn = document.createElement("button");
            let lock_btn = document.createElement("button");
            let reset_btn = document.createElement("button");
            let add_btn = document.createElement("button");

            let account_mapping = <?php echo json_encode(ACCOUNT_STATUS); ?>;
            let login_mapping = <?php echo json_encode(LOGIN_STATUS); ?>;

            let status = account_mapping[data[0].status] !== undefined ? account_mapping[data[0].status] : '';
            let locked = login_mapping[data[0].locked] !== undefined ? login_mapping[data[0].locked] : '';
            let unique_id = data[0].user_id;

            span.innerHTML = value + `<b style='color: #555;'>(` + count + ` access)</b> <span class="badge bg-primary text-white rounded-1 p-2 mt-1 text-sm-start mx-1 text-wrap">` + status + `</span><span class="badge bg-info text-white rounded-1 p-2 mt-1 text-sm-start mx-1 text-wrap">` + locked + `</span>`;

            edit_btn.classList.add("btn", "btn-sm", "btn-outline-primary", "btn-round", "px-3", "py-1", "m-1");
            edit_btn.title = "Update Account Information";
            edit_btn.innerHTML = '<i class="bi bi-pencil-square"></i>';
            edit_btn.addEventListener("click", function() {
                // Set the values of the form
                document.getElementById("update_account_modal").querySelector("#u-account-id").value = unique_id;
                document.getElementById("update_account_modal").querySelector("#u-account-name").value = data[0].name;
                document.getElementById("update_account_modal").querySelector("#u-account-employee_id").value = data[0].employee_id;
                document.getElementById("update_account_modal").querySelector("#u-account-email").value = data[0].email;
                document.getElementById("update_account_modal").querySelector("#u-account-username").value = data[0].username;
                document.getElementById("update_account_modal").querySelector("#u-account-status").value = data[0].status;

                document.getElementById("update_account_modal").querySelector("#u-account-personal_email").value = data[0].personal_email;
                $("#update_account_modal").modal("show");
            });

            lock_btn.classList.add("btn", "btn-sm", "btn-outline-info", "btn-round", "px-3", "py-1", "m-1");
            if (locked === "Active") {
                lock_btn.title = "Lock Account";
                lock_btn.innerHTML = '<i class="bi bi-lock-fill"></i>';
                lock_btn.addEventListener("click", function() {
                    notifyConfirm(
                        'Confirm Login Status',
                        'Are you sure you want to lock this account?',
                        'Yes, lock it!',
                        'question'
                    ).then((isConfirmed) => {
                        if (!isConfirmed) return;

                        const formData = new FormData();
                        formData.append("actionAccountLock", 'submitAccountLock');
                        formData.append("user_id", unique_id);
                        $.ajax({
                            url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                            type: 'post',
                            data: formData,
                            dataType: "json",
                            processData: false,
                            contentType: false,
                            success: function(output) {
                                if (output && output.msg_status === true) {
                                    if (typeof tabulator !== 'undefined' && typeof tabulator.setData === 'function') tabulator.setData();
                                    showSuccess(output.msg_response);
                                } else if (output && output.msg_status === false) {
                                    showError(output.msg_response);
                                } else {
                                    showError("Request error, please try again");
                                }
                            },
                            error: function(xhr, status, error) {
                                showError(status + "::" + error);
                            },
                        });
                    });
                });
            } else {
                lock_btn.title = "Unlock Account";
                lock_btn.innerHTML = '<i class="bi bi-unlock-fill"></i>';
                lock_btn.addEventListener("click", function() {
                    notifyConfirm(
                        'Confirm Login Status',
                        'Are you sure you want to unlock this account?',
                        'Yes, unlock it!',
                        'question'
                    ).then((isConfirmed) => {
                        if (!isConfirmed) return;
                        const formData = new FormData();
                        formData.append("actionAccountUnlock", 'submitAccountUnlock');
                        formData.append("user_id", unique_id);
                        $.ajax({
                            url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                            type: 'post',
                            data: formData,
                            dataType: "json",
                            processData: false,
                            contentType: false,
                            success: function(output) {
                                if (output && output.msg_status === true) {
                                    if (typeof tabulator !== 'undefined' && typeof tabulator.setData === 'function') tabulator.setData();
                                    showSuccess(output.msg_response);
                                } else if (output && output.msg_status === false) {
                                    showError(output.msg_response);
                                } else {
                                    showError("Request error, please try again");
                                }
                            },
                            error: function(xhr, status, error) {
                                showError(status + "::" + error);
                            },
                        });
                    });
                });
            }

            reset_btn.classList.add("btn", "btn-sm", "btn-outline-warning", "btn-round", "px-3", "py-1", "m-1");
            reset_btn.title = "Reset Password";
            reset_btn.innerHTML = '<i class="bi bi-shield-lock-fill"></i>';
            reset_btn.addEventListener("click", function() {
                Swal.fire({
                    title: 'Are you sure you want to reset password this account?',
                    // Insert HTML for the radio buttons, defaulting 14 days to checked
                    html: `
                        <div style="margin-top: 15px; text-align: left; display: inline-block;">
                            <p style="margin-bottom: 8px; font-weight: bold;">Select Password Validity:</p>
                            <label style="margin-right: 15px; cursor: pointer;">
                                <input type="radio" name="password_validity" value="14" checked style="margin-right: 5px;"> 14 Days
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="password_validity" value="90" style="margin-right: 5px;"> 90 Days
                            </label>
                        </div>
                    `,
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
                    },
                    // Optional: Extract value safely before resolving the promise
                    preConfirm: () => {
                        const selectedValidity = document.querySelector('input[name="password_validity"]:checked').value;
                        return {
                            validity: selectedValidity
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Retrieve the selected validity days from the preConfirm result
                        const validityDay = result.value.validity;

                        const formData = new FormData();
                        formData.append("actionAcoountReset", 'submitAcoountReset');
                        formData.append("user_id", unique_id);
                        formData.append("date_validity", validityDay); // Adds '14' or '90' to your PHP backend

                        $.ajax({
                            url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                            type: 'post',
                            data: formData,
                            dataType: "json",
                            processData: false,
                            contentType: false,
                            success: function(output) {
                                // Fixed a small bug here: changed data.msg_status to output.msg_status
                                if (output.msg_status === true) {
                                    if (typeof table !== 'undefined' && typeof table.setData === 'function') {
                                        table.setData();
                                    }
                                    password_modal(output.msg_password);
                                } else if (output.msg_status === false) {
                                    showError(output.msg_response);
                                } else {
                                    showError("Request error, please try again");
                                }
                            },
                            error: function(xhr, status, error) {
                                showError(status + "::" + error);
                            },
                        });
                    }
                });
            });

            const system_account_table = document.getElementById("add_system_account_table");
            const masterSystems = JSON.parse(system_account_table.getAttribute("data-systems") || "{}");
            const system_tbody = system_account_table.querySelector("tbody");
            span.classList.add("d-lg-inline", "d-md-inline", "d-sm-inline-block");
            add_btn.classList.add("btn", "btn-sm", "btn-outline-success", "btn-round", "px-3", "py-1", "m-1");
            add_btn.title = "Add System Account";
            add_btn.innerHTML = '<i class="bi bi-person-add"></i>';
            add_btn.addEventListener("click", function() {
                // 1. Populate basic text input values using the first entry's info
                const form = document.getElementById("employee_account_system_form");
                form.querySelector("#a-user_id").value = data[0].user_id;
                form.querySelector("#a-name").value = data[0].name;
                form.querySelector("#a-employee_id").value = data[0].employee_id;
                form.querySelector("#a-username").value = data[0].username;
                form.querySelector("#a-email").value = data[0].email;

                // 2. Extract ALL active systems and roles across the entire data array
                const dbSystems = [];
                const dbRoles = [];

                data.forEach(item => {
                    if (item.system_type && item.system_role) {
                        dbSystems.push(String(item.system_type)); // e.g., "CCC-WEBSITE", "E-GURO++"
                        dbRoles.push(String(item.system_role)); // e.g., "2", "1"
                    }
                });

                // Save these database-existing arrays into the table context wrapper data attributes
                system_account_table.setAttribute("data-db-systems", JSON.stringify(dbSystems));
                system_account_table.setAttribute("data-db-roles", JSON.stringify(dbRoles));

                // 3. Reset the dynamic UI row layout down to a single clean row
                system_tbody.innerHTML = `
                        <tr class="row_sched">
                            <td class="p-2" style="min-width: 220px;">
                                <select name="a-system_name[]" class="form-control a-systemAccess" required>
                                    <option value="" selected disabled>Select System Access</option>
                                </select>
                            </td>
                            <td class="p-2" style="min-width: 200px;">
                                <select name="a-system_role[]" class="form-control a-systemUserRole" disabled required>
                                    <option value="" selected disabled>Select System User Role</option>
                                </select>
                            </td>
                            <td class="align-content-center p-2 text-center" style="min-width: 120px;">
                                <button type="button" id="add_row_system_acc" class="btn btn-outline-success btn-sm btn-rounded">
                                    <i class="bi bi-plus-circle"></i> Add Row
                                </button>
                            </td>
                        </tr>
                    `;

                // Re-bind the row creation execution handler
                system_tbody.querySelector("#add_row_system_acc").addEventListener("click", appendNewRow);

                // Run the visibility calculation loop
                updateAllDropdowns();

                // Show the modal
                $("#add_account_system_modal").modal("show");
            });

            // --- Dropdown Modification Change Events ---
            system_tbody.addEventListener("change", function(e) {
                if (e.target.classList.contains("a-systemAccess")) {
                    const row = e.target.closest("tr");
                    const roleSelect = row.querySelector(".a-systemUserRole");
                    const selectedSystem = e.target.value;

                    if (selectedSystem && masterSystems[selectedSystem]) {
                        roleSelect.innerHTML = '<option value="" selected disabled>Select System User Role</option>';
                        roleSelect.removeAttribute("disabled");
                    } else {
                        roleSelect.innerHTML = '<option value="" selected disabled>Select System User Role</option>';
                        roleSelect.setAttribute("disabled", "true");
                    }
                    updateAllDropdowns();
                }

                if (e.target.classList.contains("a-systemUserRole")) {
                    updateAllDropdowns();
                }
            });

            // --- Dynamic Row Deletion Trigger Handler ---
            system_tbody.addEventListener("click", function(e) {
                const removeBtn = e.target.closest(".remove-row-btn");
                if (removeBtn) {
                    removeBtn.closest("tr").remove();
                    updateAllDropdowns();
                }
            });

            // --- Core Master Dropdown Filtering Processor Engine ---
            function updateAllDropdowns() {
                const rows = system_tbody.querySelectorAll("tr");

                // FIXED: Changed 'table' variable target reference to 'system_account_table'
                const dbSystems = JSON.parse(system_account_table.getAttribute("data-db-systems") || "[]");
                const dbRoles = JSON.parse(system_account_table.getAttribute("data-db-roles") || "[]");

                // Step A: Collect selections currently made in the modal UI rows
                const uiSelections = [];
                rows.forEach(row => {
                    const sysVal = row.querySelector(".a-systemAccess").value;
                    const roleVal = row.querySelector(".a-systemUserRole").value;
                    if (sysVal && roleVal) {
                        uiSelections.push({
                            system: sysVal,
                            role: roleVal
                        });
                    }
                });

                // Step B: Loop through and filter the dropdown items for each row
                rows.forEach(row => {
                    const systemSelect = row.querySelector(".a-systemAccess");
                    const roleSelect = row.querySelector(".a-systemUserRole");

                    const currentSystem = systemSelect.value;
                    const currentRole = roleSelect.value;

                    // Rebuild System select options
                    let systemOptions = `<option value="" ${!currentSystem ? 'selected' : ''} disabled>Select System Access</option>`;
                    for (const [sysKey, sysData] of Object.entries(masterSystems)) {
                        systemOptions += `<option value="${sysKey}" ${currentSystem === sysKey ? 'selected' : ''}>${sysData.name}</option>`;
                    }
                    systemSelect.innerHTML = systemOptions;

                    // Filter and rebuild Role select options
                    if (currentSystem && masterSystems[currentSystem]) {
                        let roleOptions = `<option value="" ${!currentRole ? 'selected' : ''} disabled>Select System User Role</option>`;
                        const roles = masterSystems[currentSystem]['role'];

                        for (const [roleKey, roleName] of Object.entries(roles)) {

                            // 1. Check if this combination exists in the Database for this user
                            let isConflictInDB = false;
                            for (let i = 0; i < dbSystems.length; i++) {
                                if (dbSystems[i] === currentSystem && dbRoles[i] === String(roleKey)) {
                                    isConflictInDB = true;
                                    break;
                                }
                            }

                            // 2. Check if this combination is already selected in another row in the modal
                            const isConflictInUI = uiSelections.some(selection =>
                                selection.system === currentSystem &&
                                selection.role === String(roleKey) &&
                                !(currentSystem === systemSelect.value && String(roleKey) === currentRole)
                            );

                            // If it doesn't conflict with the DB or other UI rows, render the option
                            if (!isConflictInDB && !isConflictInUI) {
                                roleOptions += `<option value="${roleKey}" ${currentRole === String(roleKey) ? 'selected' : ''}>${roleName}</option>`;
                            }
                        }
                        roleSelect.innerHTML = roleOptions;
                    }
                });
            }

            // --- Append New Form Table Rows Function ---
            function appendNewRow() {
                const newRow = document.createElement("tr");
                newRow.classList.add("row_sched");
                newRow.innerHTML = `
                    <td class="p-2" style="min-width: 220px;">
                        <select name="a-system_name[]" class="form-control a-systemAccess" required>
                            <option value="" selected disabled>Select System Access</option>
                        </select>
                    </td>
                    <td class="p-2" style="min-width: 200px;">
                        <select name="a-system_role[]" class="form-control a-systemUserRole" disabled required>
                            <option value="" selected disabled>Select System User Role</option>
                        </select>
                    </td>
                    <td class="align-content-center p-2 text-center" style="min-width: 120px;">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-rounded remove-row-btn">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                `;

                system_tbody.appendChild(newRow);
                updateAllDropdowns();
            }


            span.appendChild(edit_btn);
            span.appendChild(lock_btn);
            span.appendChild(reset_btn);
            span.appendChild(add_btn);
            return span;
        }

        /*** submit the form ***/
        $("#employee_account_system_form").on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to add this account system access?',
                'Yes, save it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            let formData = jQuery("#employee_account_system_form").serializeArray();
            let newData = [{
                name: "actionSubmitEmployeeAccountSystem",
                value: "submitUserEmployeeAccountSystem"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                method: "POST",
                data: postData,
                dataType: "json",
                // REMOVED async: false so that loading indicators work perfectly
                beforeSend: function() {
                    showLoader('Saving information...');
                    $submitBtn.data('orig-text', $submitBtn.html());
                    $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading..');
                    $inputs.prop("disabled", true);
                },
                complete: function() {
                    hideLoader();
                    $submitBtn.html($submitBtn.data('orig-text') || 'Submit');
                    $inputs.prop("disabled", false);
                },
                success: function(output) {
                    if (output && output.msg_status === true) {
                        if (tabulator && typeof tabulator.setData === 'function') tabulator.setData();
                        $('#add_account_system_modal').modal('hide');
                        $("#employee_account_system_form")[0].reset();
                        showSuccess(output.msg_response);
                    } else if (output && output.msg_status === false) {
                        showError(output.msg_response);
                    } else {
                        showError("Request error, please try again");
                    }
                },
                error: function(xhr, status, error) {
                    showError(status + "::" + error);
                }
            });
        });

        /*** submit update form ***/
        $('#update_account_form').on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Update',
                'Are you sure you want to update this account system details?',
                'Yes, update it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            let formData = jQuery("#update_account_form").serializeArray();
            let newData = [{
                name: "actionUpdateAccountSystem",
                value: "submitUpdateAccountSystem"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/employee-system-access-process",
                type: 'POST',
                data: postData,
                dataType: "json",
                beforeSend: function() {
                    showLoader('Update information...');
                    $submitBtn.data('orig-text', $submitBtn.html());
                    $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading..');
                    $inputs.prop("disabled", true);
                },
                complete: function() {
                    hideLoader();
                    $submitBtn.html($submitBtn.data('orig-text') || 'Submit');
                    $inputs.prop("disabled", false);
                },
                success: function(output) {
                    if (output.msg_status === true) {
                        if (tabulator && typeof tabulator.setData === 'function') tabulator.setData();
                        $('#update_account_modal').modal('hide');
                        showSuccess(output.msg_response);
                    } else if (output.msg_status === false) {
                        showError(output.msg_response);
                    } else {
                        showError("Request error, please try again");
                    }
                },
                error: function(xhr, status, error) {
                    showError(status + "::" + error);
                },
            });
        });

        /*** add bulk modal ***/
        $('.bulk-system-employee-btn').on('click', function() {
            $('.dropify-clear').click();

            $('#add_bulk_employee_system').find('form').trigger('reset');
            $('#add_bulk_employee_system').modal('show');
        });

        /*** field dropify for bulk ***/
        $('.bulk_dropify').dropify({
            allowedFileExtensions: ['csv'],
            messages: {
                'default': 'Drag and drop your CSV file here.',
                'replace': 'Drag and drop, or click to replace.',
                'remove': 'Remove',
                'error': 'Ooops, an error occurred.'
            },
            error: {
                'fileExtension': 'Only CSV files are allowed ({{ value }} is invalid).'
            }
        });

        /*** submit the bulk ***/
        $("#bulk_employee_system_form").on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to import this bulk user information?',
                'Yes, update it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/employee-system-access-bulk",
                data: new FormData(this),
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    showLoader('Saving information...');
                    $submitBtn.data('orig-text', $submitBtn.html());
                    $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading..');
                    $(".close_csv_upload").attr("disabled", true);
                },
                complete: function() {
                    hideLoader();
                    $submitBtn.html($submitBtn.data('orig-text') || 'Submit');
                    $(".close_csv_upload").removeAttr('disabled');
                },
                success: function(output) {
                    if (output.success || output.error_id.length > 0) {
                        $('.dropify-clear').click();
                        $('#add_bulk_employee_system').modal('hide');

                        const inserted = output.success_insert;
                        const updated = output.success_update;
                        const remained = output.success_remain;

                        const skipped = output.skipped;
                        const total = output.total - (skipped);

                        const not_process = total - (inserted + updated + remained);
                        const total_success = (inserted + updated + remained);

                        const error_list = output.error_id;
                        var error_txt = `<table class="table table-responsive table-bordered" style="max-height:300px; overflow-y: scroll;">`;
                        error_txt += `<tr><th style="border-width:1;">Row No.</th><th style="border-width:1;">Error Message</th></tr>`;
                        if (error_list && error_list.length > 0) {
                            error_list.forEach(function(msg) {

                                var text = msg.msg

                                error_txt += `<tr>`;
                                error_txt += `<td style="border-width:1;">` + msg.id + `</td>`;
                                error_txt += `<td style="border-width:1;">` + text.replace(/\^/g, `<br>`) + `</td>`;
                                error_txt += `</tr>`;
                            });
                            error_txt += "</table>";
                        } else {
                            error_txt = "";
                        }

                        var swal_html =
                            `<div class="card-body">
                            <div style="text-align: left!important">
                                <strong>SUMMARY</strong>
                                <ul>
                                    <li><b>Total Rows        : ` + total + `</b></li>
                                    <li><b>Total Processed   : ` + total_success + `</b></li>
                                    <li><b>Total Not Process : ` + not_process + `</b></li>
                                </ul>
                            </div>
                            <table class="table table-responsive table-bordered w-100">
                                <thead>
                                    <tr><th style="border-width:1;">Status</th><th style="border-width:1;">Count</th><tr>
                                </thead>
                                <tbody>
                                    <tr><td style="border-width:1;">Total Inserted:</td><td style="border-width:1;"><b>` + inserted + `</b></td></tr>
                                    <tr><td style="border-width:1;">Total Updated:</td><td style="border-width:1;"><b>` + updated + `</b></td></tr>
                                    <tr><td style="border-width:1;">Total No Changes:</td><td style="border-width:1;"><b>` + remained + `</b></td></tr>
                                </tbody>
                            </table>
                            ` + error_txt + `
                        </div>`;

                        Swal.fire({
                            title: "Import Status",
                            html: swal_html,
                            allowOutsideClick: false,
                            confirmButtonText: 'Close',
                            width: '800px',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (typeof table !== 'undefined' && typeof table.setData === 'function') {
                                    table.setData();
                                }
                            }
                        })
                        // setTimeout(function() { }, 500);
                    } else if (!output.success || output.error_id.length == 0) {
                        $('.dropify-clear').click();
                        document.getElementById("employee_bulk_err_msg").style.display = "block";
                        document.getElementById("employee_bulk_err_msg").innerHTML = output.error;
                    } else {
                        $('.dropify-clear').click();
                        document.getElementById("employee_bulk_err_msg").style.display = "block";
                        document.getElementById("employee_bulk_err_msg").innerHTML = "Request Error, please try again.";
                    }
                },
            });

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
</script>

</html>