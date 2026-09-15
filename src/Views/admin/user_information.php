<?php
# Core Config Dependencies
$db_connect = $db_connect ?? null;
$g_user_role = $g_user_role ?? '';

# Server Execution Limits [uncomment ONLY for long-running scripts like reports/imports]
// set_time_limit(0);
// ini_set('max_execution_time', '0');
// ini_set('memory_limit', '1024M');

require_once HELPER;
require_once ISLOGIN;
require_once API_CONNECT;

# Page Helpers
require_once ADMIN_PAGE_HEADER_PATH;
# Page Header Initialization
$title_page   = "User Information";
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
$path = IMPORT_USER_LOG;
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

$action_buttons = [
    [
        'label' => 'Add User Information',
        'icon'  => 'bi bi-plus-circle',
        'class' => 'add-user-btn',
        'type'  => 'button'
    ],
    [
        'label' => 'Import Bulk User',
        'icon'  => 'bi bi-arrow-bar-up bulk',
        'class' => 'bulk-user-btn',
        'type'  => 'button'
    ]
];


$my_links = [
    [
        'label'  => 'Download Template CSV File',
        'icon'   => 'bi bi-download',
        'href'   => BASE_URL . 'download?attach=IMP_BLK_USRINF',
        'target' => '_blank',
        'class'  => 'text-decoration-none text-primary'
    ],
    [
        'label'  => 'View Uploaded Logs',
        'icon'   => 'bi bi-eye',
        'href'   => '#',
        'id'     => 'view_log',
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
                                                    User Information
                                                </div>
                                                <div class="d-flex align-items-center text-muted small mt-0.5" style="font-size: 0.75rem;">
                                                    <i class="bi bi-info-circle me-1"></i> dansda
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-start align-items-md-end gap-2 ms-md-auto">
                                            <?php render_header_button($action_buttons); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
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
                    <!-- add user modal -->
                    <div class="modal fade" id="add_information_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h5 class="modal-title" id="exampleModalToggleLabel">Create User Information</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="user_information_form">
                                    <div class="modal-body">

                                        <div class="row p-3">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Basic Information (BI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="first_name"><b>First Name</b></label>
                                                        <input type="text" name="first_name" class="form-control" id="first_name" placeholder="First name" required>
                                                    </div>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="middle_name"><b>Middle Name</b></label>
                                                        <input type="text" name="middle_name" class="form-control" id="middle_name" placeholder="Middle name">
                                                    </div>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="last_name"><b>Last Name</b></label>
                                                        <input type="text" name="last_name" class="form-control" id="last_name" placeholder="Last name" required>
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
                                                    </div>


                                                    <div class="col-lg-2 mb-2">
                                                        <label for="sex" class="form-label"><b>Sex</b></label>
                                                        <select name="sex" id="sex" class="form-control">
                                                            <option value="" selected disabled>Select Sex</option>
                                                            <?php
                                                            foreach (SEX as $sex) {
                                                                echo '<option value="' . $sex . '">' . ucfirst($sex) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-5 mb-2">
                                                        <label for="birth_date" class="form-label"><b>Birth Date</b></label>
                                                        <input type="text" name="birth_date" class="form-control" id="birth_date" placeholder="Birth Date" autocomplete="off">
                                                        <small class="text-muted">e.g., 2000-05-30</small>
                                                    </div>

                                                    <div class="col-lg-5 mb-2">
                                                        <label for="text" class="form-label"><b>Birth Place</b></label>
                                                        <input type="text" name="birth_place" class="form-control" id="birth_place" placeholder="Birth Place">
                                                    </div>


                                                    <div class="col-lg-6 mb-2">
                                                        <label for="text" class="form-label"><b>Civil Status</b></label>
                                                        <select name="civil_status" id="civil_status" class="form-control">
                                                            <option value="" selected disabled>Select Civil Status</option>
                                                            <?php
                                                            foreach (CIVIL_STATUS as $civil_status) {
                                                                echo '<option value="' . $civil_status . '">' . ucfirst($civil_status) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-6 mb-2">
                                                        <label for="text" class="form-label"><b>Nationality</b></label>
                                                        <input type="text" name="nationality" class="form-control" id="nationality" placeholder="Nationality">
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Contact Information (CI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="contact_no"><b>Contact Number</b></label>
                                                        <input type="text" name="contact_no" class="form-control" id="contact_no" placeholder="Contact Number" pattern="09[0-9]{9}" maxlength="11">
                                                        <small class="text-muted">e.g., 09XXXXXXXXX</small>
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="email" class="form-control" id="email" placeholder="CCC Email Address" required>
                                                        <small class="text-muted">e.g., xxxxxx@ccc.edu.ph</small><br>
                                                        <small class="text-muted" style="font-size: x-small;">Note: This is your primary institutional email address.</small>
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="personal_email"><b>Personal Email Address</b></label>
                                                        <input type="email" name="personal_email" class="form-control" id="personal_email" placeholder="Personal Email Address">
                                                        <small class="text-muted">e.g., xxxxxx@gmail.com</small><br>
                                                        <small class="text-muted" style="font-size: x-small;">Note: This will serve as your backup recovery email address.</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Address Information (AI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-12 mb-2">
                                                        <label class="form-label" for="home_address"><b>Home Address</b></label>
                                                        <input type="text" name="home_address" class="form-control" id="home_address" placeholder="Address">
                                                        <small class="text-muted">House No./Street, Subdivision/Sitio</small>
                                                    </div>


                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="brgy"><b>Barangay</b></label>
                                                        <input type="text" name="brgy" class="form-control" id="brgy" placeholder="Barangay">
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="city"><b>City</b></label>
                                                        <input type="text" name="city" class="form-control" id="city" placeholder="City">
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="province"><b>Province</b></label>
                                                        <input type="text" name="province" class="form-control" id="province" placeholder="Province">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Emergency Contact Information (ECI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-6 mb-2">
                                                        <label class="form-label" for="e_name"><b>Full Name</b></label>
                                                        <input type="text" name="e_name" class="form-control" id="e_name" placeholder="Full Name">
                                                    </div>
                                                    <div class="col-lg-6 mb-2">
                                                        <label class="form-label" for="e_relationship"><b>Relationship</b></label>
                                                        <input type="text" name="e_relationship" class="form-control" id="e_relationship" placeholder="Middle name">
                                                    </div>


                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="e_contact"><b>Contact Number</b></label>
                                                        <input type="text" name="e_contact" class="form-control" id="e_contact" placeholder="Contact Number" pattern="09[0-9]{9}" maxlength="11">
                                                        <small class="text-muted">e.g., 09XXXXXXXXX</small>
                                                    </div>
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="e_address"><b>Address</b></label>
                                                        <input type="text" name="e_address" class="form-control" id="e_address" placeholder="Home Address">
                                                        <small class="text-muted">House No./Street, Subdivision/Sitio, Barangay, City, Province</small>
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

                    <!-- update information modal -->
                    <div class="modal fade" id="update_information_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h6 class="modal-title" id="exampleModalToggleLabel">Update User Information</span></h6>
                                    <button type="button" id="close" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="update_information_form" name="update_information_form" method="post" action="/">

                                    <div class="modal-body">

                                        <div class="row p-3">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Basic Information (BI)</strong></h5>
                                                    <hr>

                                                    <input type="hidden" name="u_id" id="u_id">
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="u_first_name"><b>First Name</b></label>
                                                        <input type="text" name="u_first_name" class="form-control" id="u_first_name" placeholder="First name" required>
                                                    </div>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="u_middle_name"><b>Middle Name</b></label>
                                                        <input type="text" name="u_middle_name" class="form-control" id="u_middle_name" placeholder="Middle name">
                                                    </div>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="u_last_name"><b>Last Name</b></label>
                                                        <input type="text" name="u_last_name" class="form-control" id="u_last_name" placeholder="Last name" required>
                                                    </div>
                                                    <div class="col-lg-3 mb-2">
                                                        <label class="form-label" for="u_suffix"><b>Suffix</b></label>
                                                        <select name="u_suffix" id="u_suffix" class="form-control">
                                                            <option value="" selected disabled>Select Suffix</option>
                                                            <option value=""></option>
                                                            <?php
                                                            foreach (SUFFIX as $suffix) {
                                                                echo '<option value="' . $suffix . '">' . $suffix . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>


                                                    <div class="col-lg-2 mb-2">
                                                        <label for="u_sex" class="form-label"><b>Sex</b></label>
                                                        <select name="u_sex" id="u_sex" class="form-control">
                                                            <option value="" selected disabled>Select Sex</option>
                                                            <?php
                                                            foreach (SEX as $sex) {
                                                                echo '<option value="' . $sex . '">' . ucfirst($sex) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-5 mb-2">
                                                        <label for="u_birth_date" class="form-label"><b>Birth Date</b></label>
                                                        <input type="text" name="u_birth_date" class="form-control" id="u_birth_date" placeholder="Birth Date" autocomplete="off">
                                                        <small class="text-muted">e.g., 2000-05-30</small>
                                                    </div>

                                                    <div class="col-lg-5 mb-2">
                                                        <label for="text" class="form-label"><b>Birth Place</b></label>
                                                        <input type="text" name="u_birth_place" class="form-control" id="u_birth_place" placeholder="Birth Place">
                                                    </div>


                                                    <div class="col-lg-6 mb-2">
                                                        <label for="text" class="form-label"><b>Civil Status</b></label>
                                                        <select name="u_civil_status" id="u_civil_status" class="form-control">
                                                            <option value="" selected disabled>Select Civil Status</option>
                                                            <?php
                                                            foreach (CIVIL_STATUS as $civil_status) {
                                                                echo '<option value="' . $civil_status . '">' . ucfirst($civil_status) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-6 mb-2">
                                                        <label for="u_nationality" class="form-label"><b>Nationality</b></label>
                                                        <input type="text" name="u_nationality" class="form-control" id="u_nationality" placeholder="Nationality">
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Contact Information (CI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_contact_no"><b>Contact Number</b></label>
                                                        <input type="text" name="u_contact_no" class="form-control" id="u_contact_no" placeholder="Contact Number" pattern="09[0-9]{9}" maxlength="11">
                                                        <small class="text-muted">e.g., 09XXXXXXXXX</small>
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="u_email" class="form-control" id="u_email" placeholder="CCC Email Address" required>
                                                        <small class="text-muted">e.g., xxxxxx@ccc.edu.ph</small><br>
                                                        <small class="text-muted" style="font-size: x-small;">Note: This is your primary institutional email address.</small>
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_personal_email"><b>Personal Email Address</b></label>
                                                        <input type="email" name="u_personal_email" class="form-control" id="u_personal_email" placeholder="Personal Email Address">
                                                        <small class="text-muted">e.g., xxxxxx@gmail.com</small><br>
                                                        <small class="text-muted" style="font-size: x-small;">Note: This will serve as your backup recovery email address.</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Address Information (AI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-12 mb-2">
                                                        <label class="form-label" for="u_home_address"><b>Home Address</b></label>
                                                        <input type="text" name="u_home_address" class="form-control" id="u_home_address" placeholder="Home Address">
                                                        <small class="text-muted">House No./Street, Subdivision/Sitio</small>
                                                    </div>


                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_brgy"><b>Barangay</b></label>
                                                        <input type="text" name="u_brgy" class="form-control" id="u_brgy" placeholder="Barangay">
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_city"><b>City</b></label>
                                                        <input type="text" name="u_city" class="form-control" id="u_city" placeholder="City">
                                                    </div>
                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_province"><b>Province</b></label>
                                                        <input type="text" name="u_province" class="form-control" id="u_province" placeholder="Province">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Emergency Contact Information (ECI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-6 mb-2">
                                                        <label class="form-label" for="u_e_name"><b>Full Name</b></label>
                                                        <input type="text" name="u_e_name" class="form-control" id="u_e_name" placeholder="Full Name">
                                                    </div>
                                                    <div class="col-lg-6 mb-2">
                                                        <label class="form-label" for="e_relationship"><b>Relationship</b></label>
                                                        <input type="text" name="u_e_relationship" class="form-control" id="u_e_relationship" placeholder="Relationship">
                                                    </div>


                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u_e_contact"><b>Contact Number</b></label>
                                                        <input type="text" name="u_e_contact" class="form-control" id="u_e_contact" placeholder="Contact Number" pattern="09[0-9]{9}" maxlength="11">
                                                        <small class="text-muted">e.g., 09XXXXXXXXX</small>
                                                    </div>
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="u_e_address"><b>Address</b></label>
                                                        <input type="text" name="u_e_address" class="form-control" id="u_e_address" placeholder="Address">
                                                        <small class="text-muted">House No./Street, Subdivision/Sitio, Barangay, City, Province</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger rounded-3" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-secondary rounded-3" id="btn_info" name="actionInfo" value="updateInfo">Update Information</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- add bulk user modal -->
                    <div class="modal fade" id="add_bulk_user" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkSchedBackdropLabel">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h5 class="modal-title" id="bulkSchedBackdropLabel">Bulk User Information</h5>
                                    <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form autocomplete="off" id="bulk_user_form" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <?php render_action_links($my_links); ?>
                                        <h5 class="mb-1">
                                            <div id="user_bulk_err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></div>
                                        </h5>
                                        <div id="bulk_data">
                                            <input type="file" id="import_user_information" class="bulk_dropify" styles="height:500px" data-default-file="" name="import_user_information" accept="text/*" required>
                                            <small class="text-muted" style="font-size:small;"><b class="text-primary">NOTE:</b> Bulk imports are strictly used to add or update information. Duplicate <b class="text-primary">INSTITUTIONAL EMAIL</b> will overwrite existing information. All optional columns may be left blank.</small>
                                        </div>
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
        /** set server time **/
        var set_server_time = <?php echo "'" . DATE_TIME . "';\r\n"; ?>
        var serverOffset = moment(set_server_time).diff(new Date());
        var now_server = moment();
        now_server.add(serverOffset, 'milliseconds');
        now_server.subtract(15, 'year');
        var dateLimit = now_server.format('YYYY-MM-DD');

        let tabulator = null;

        /* Tabulator Table Setup */
        function initTabulatorTable() {
            const remoteTableUrl = "<?php echo BASE_URL; ?>table/user-information-table";

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

                dataReceiveParams: {
                    "last_page": "last_page",
                    "data": "data",
                    "last_row": "last_row"
                },

                downloadRowRange: "all",

                height: "600px",
                headerHozAlign: 'center',
                layout: "fitColumns",
                placeholder: "No Record Found",

                printAsHtml: true,
                printFormatter: false,
                printConfig: {
                    columnGroups: false,
                    rowGroups: false,
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
                            let classAction = ``; // FIX: Explicitly declare variable

                            if (status === '0') {
                                label = "Update Information";
                                icon = "bi-pencil-square";
                                classAction = "btn-update-row";

                                buttons += `<button type="button" class="${classBtn} ${classAction}" title="${label}"><i class="bi ${icon}"></i><span style="font-size: 0.75rem;">${label}</span></button>`;
                            }

                            return buttons;
                        },
                        cellClick: function(e, cell) {
                            if (e.target.closest('.btn-update-row')) {
                                openUpdateModal(cell.getRow().getData());
                            }
                        }
                    },
                    {
                        title: "Name",
                        field: "name",
                        minWidth: 250,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterFunc: "like",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            allowEmpty: true
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "First Name",
                        field: "first_name",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Middle Name",
                        field: "middle_name",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Last Name",
                        field: "last_name",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Extension Name",
                        field: "suffix",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Sex",
                        field: "sex",
                        minWidth: 150,
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
                        title: "Birth Date",
                        field: "birth_date",
                        minWidth: 150,
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
                        title: "Birth Place",
                        field: "birth_place",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Civil Status",
                        field: "civil_status",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Nationality",
                        field: "nationality",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Contact Number",
                        field: "contact_no",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "CCC Email Address",
                        field: "email",
                        minWidth: 150,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterFunc: "like",
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
                        headerFilterFunc: "like",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            allowEmpty: true
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Home Address",
                        field: "home_address",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Barangay",
                        field: "brgy",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "City",
                        field: "city",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Province",
                        field: "province",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Full Name (ECI)",
                        field: "e_name",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Relationship (ECI)",
                        field: "e_relationship",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Contact Number (ECI)",
                        field: "e_contact",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Address (ECI)",
                        field: "e_address",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Date Added/Modify",
                        field: "date_modify",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        print: false,
                        download: false,
                        formatter: 'textarea',
                    }
                ]
            });

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
                const tableTitle = 'User Information Report';
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
                    tabulator.clearHeaderFilter();

                    tabulator.setData(remoteTableUrl, {
                        load_all: 0,
                    });
                });
            }
        }

        /* Initialize Tabulator Table */
        initTabulatorTable();

        /* Date Pickers */
        const birth_datePicker = document.getElementById("birth_date");
        $(birth_datePicker).datetimepicker({
            format: 'YYYY-MM-DD',
            maxDate: dateLimit
        });
        const u_birth_date = document.getElementById("u_birth_date");
        $(u_birth_date).datetimepicker({
            format: 'YYYY-MM-DD',
            maxDate: dateLimit
        });

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

        /*** add user modal ***/
        $('.add-user-btn').on('click', function() {
            $('#add_information_modal').modal('show');
            $('#add_information_modal').find('form').trigger('reset');
        });

        /*** submit the user form ***/
        $("#user_information_form").on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to save this user information?',
                'Yes, save it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            let formData = $form.serializeArray();
            let newData = [{
                name: "actionSubmit",
                value: "submitUser"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/user-information-process",
                method: "POST",
                data: postData,
                dataType: "json",
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
                    if (output.msg_status === true) {
                        if (tabulator && typeof tabulator.setData === 'function') tabulator.setData();
                        $("#add_information_modal").modal('hide');
                        showSuccess(output.msg_response);
                    } else if (output.msg_status === false) {
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

        function openUpdateModal(data) {
            document.getElementById("update_information_modal").querySelector("#u_id").value = data.id;
            document.getElementById("update_information_modal").querySelector("#u_first_name").value = data.first_name;
            document.getElementById("update_information_modal").querySelector("#u_middle_name").value = data.middle_name;
            document.getElementById("update_information_modal").querySelector("#u_last_name").value = data.last_name;
            document.getElementById("update_information_modal").querySelector("#u_suffix").value = data.suffix;
            document.getElementById("update_information_modal").querySelector("#u_sex").value = data.sex;
            document.getElementById("update_information_modal").querySelector("#u_birth_date").value = data.birth_date;
            document.getElementById("update_information_modal").querySelector("#u_birth_place").value = data.birth_place;
            document.getElementById("update_information_modal").querySelector("#u_civil_status").value = data.civil_status;
            document.getElementById("update_information_modal").querySelector("#u_nationality").value = data.nationality;
            document.getElementById("update_information_modal").querySelector("#u_contact_no").value = data.contact_no;
            document.getElementById("update_information_modal").querySelector("#u_email").value = data.email;
            document.getElementById("update_information_modal").querySelector("#u_personal_email").value = data.personal_email;
            document.getElementById("update_information_modal").querySelector("#u_home_address").value = data.home_address;
            document.getElementById("update_information_modal").querySelector("#u_brgy").value = data.brgy;
            document.getElementById("update_information_modal").querySelector("#u_city").value = data.city;
            document.getElementById("update_information_modal").querySelector("#u_province").value = data.province;
            document.getElementById("update_information_modal").querySelector("#u_e_name").value = data.e_name;
            document.getElementById("update_information_modal").querySelector("#u_e_relationship").value = data.e_relationship;
            document.getElementById("update_information_modal").querySelector("#u_e_contact").value = data.e_contact;
            document.getElementById("update_information_modal").querySelector("#u_e_address").value = data.e_address;

            $("#update_information_modal").modal("show");
        };

        /*** submit update user form ***/
        $('#update_information_form').on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to update this user information?',
                'Yes, update it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            let formData = $form.serializeArray();
            formData.push({
                name: "actionUpdate",
                value: "submitUpdate"
            });

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/user-information-process",
                type: 'POST',
                data: formData,
                dataType: "json",
                beforeSend: function() {
                    showLoader('Updating information...');
                    $submitBtn.data('orig-text', $submitBtn.html());
                    $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading..');
                    $inputs.prop("disabled", true);
                },
                complete: function() {
                    hideLoader();
                    $submitBtn.html($submitBtn.data('orig-text') || 'Update Information');
                    $inputs.prop("disabled", false);
                },
                success: function(output) {
                    if (output && output.msg_status === true) {
                        if (tabulator && typeof tabulator.setData === 'function') tabulator.setData();
                        $("#update_information_modal").modal('hide');
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

        /*** add user modal ***/
        $('#upload_log').on('click', function() {
            $('#upload_log_modal').modal('show');
        });

        /*** add bulk user modal ***/
        $('.bulk-user-btn').on('click', function() {
            $('.dropify-clear').click();

            $('#add_bulk_user').find('form').trigger('reset');
            $('#add_bulk_user').modal('show');
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
                showError("Invalid file format");
                $('.dropify-clear').click();
                return false;
            }
        });

        /*** submit the bulk schedule ***/
        $("#bulk_user_form").on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to import this bulk user information?',
                'Yes, proceed!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');

            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/user-information-bulk",
                data: formData,
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                beforeSend: function() {
                    showLoader('Saving information...');
                    $submitBtn.data('orig-text', $submitBtn.html());
                    $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading..');
                    $(".close_csv_upload").prop("disabled", true);
                },
                complete: function() {
                    hideLoader();
                    $submitBtn.html($submitBtn.data('orig-text') || 'Submit');
                    $(".close_csv_upload").prop("disabled", false);
                },
                success: function(output) {
                    if (output && (output.success || (output.error_id && output.error_id.length > 0))) {
                        $('.dropify-clear').click();
                        $('#add_bulk_user').modal('hide');

                        const inserted = output.success_insert || 0;
                        const updated = output.success_update || 0;
                        const remained = output.success_remain || 0;

                        const skipped = output.skipped || 0;
                        const total = (output.total || 0) - skipped;

                        const total_success = inserted + updated + remained;
                        const not_process = total - total_success;

                        const error_list = output.error_id || [];
                        let error_txt = "";

                        if (error_list.length > 0) {
                            error_txt = `<div style="max-height: 200px; overflow-y: auto;" class="mt-2">
                            <table class="table table-responsive table-bordered text-start mb-0">
                                <thead>
                                    <tr>
                                        <th style="border-width:1px;">Row No.</th>
                                        <th style="border-width:1px;">Error Message</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                            error_list.forEach(function(msg) {
                                let text = msg.msg || "";
                                error_txt += `<tr>
                                <td style="border-width:1px;">${msg.id}</td>
                                <td style="border-width:1px;">${text.replace(/\^/g, '<br>')}</td>
                            </tr>`;
                            });

                            error_txt += `</tbody></table></div>`;
                        }

                        let swal_html = `
                        <div class="card-body">
                            <div style="text-align: left!important">
                                <strong>SUMMARY</strong>
                                <ul>
                                    <li><b>Total Rows         : ${total}</b></li>
                                    <li><b>Total Processed    : ${total_success}</b></li>
                                    <li><b>Total Not Processed: ${not_process}</b></li>
                                </ul>
                            </div>
                            <table class="table table-responsive table-bordered w-100">
                                <thead>
                                    <tr><th style="border-width:1px;">Status</th><th style="border-width:1px;">Count</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td style="border-width:1px;">Total Inserted:</td><td style="border-width:1px;"><b>${inserted}</b></td></tr>
                                    <tr><td style="border-width:1px;">Total Updated:</td><td style="border-width:1px;"><b>${updated}</b></td></tr>
                                    <tr><td style="border-width:1px;">Total No Changes:</td><td style="border-width:1px;"><b>${remained}</b></td></tr>
                                </tbody>
                            </table>
                            ${error_txt}
                        </div>`;

                        Swal.fire({
                            title: "Import Status",
                            html: swal_html,
                            allowOutsideClick: false,
                            confirmButtonText: 'Close',
                            width: '800px',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (typeof tabulator !== 'undefined' && tabulator && typeof tabulator.setData === 'function') {
                                    tabulator.setData();
                                }
                            }
                        });
                    } else {
                        $('.dropify-clear').click();
                        const errMsgContainer = document.getElementById("user_bulk_err_msg");
                        if (errMsgContainer) {
                            errMsgContainer.style.display = "block";
                            errMsgContainer.innerHTML = (output && output.error) ? output.error : "Request Error, please try again.";
                        }
                    }
                },
                error: function(xhr, status, error) {
                    $('.dropify-clear').click();
                    const errMsgContainer = document.getElementById("user_bulk_err_msg");
                    if (errMsgContainer) {
                        errMsgContainer.style.display = "block";
                        errMsgContainer.innerHTML = status + "::" + error;
                    }
                }
            });
        });

    })();
</script>

</html>