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
$title_page   = "Employee Information";
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
$path = IMPORT_EMPLOYEE_LOG;
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

$action_buttons = [
    [
        'label' => 'Add Employee Information',
        'icon'  => 'bi bi-plus-circle',
        'class' => 'add-employee-btn',
        'type'  => 'button'
    ],
    [
        'label' => 'Import Bulk Employee',
        'icon'  => 'bi bi-arrow-bar-up bulk',
        'class' => 'bulk-employee-btn',
        'type'  => 'button'
    ]
];

$my_links = [
    [
        'label'  => 'Download Template CSV File',
        'icon'   => 'bi bi-download',
        'href'   => BASE_URL . 'download?attach=IMP_BLK_EMPINF',
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
                                                    Employee Information
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
                                    <h5 class="modal-title" id="exampleModalToggleLabel">ADD EMPLOYEE INFORMATION</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="employee_information_form">
                                    <div class="modal-body">

                                        <div class="row p-2">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Basic Information (BI)</strong></h5>
                                                    <hr>

                                                    <input type="hidden" name="user_id" id="user_id">
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="name"><b>Full Name</b></label>
                                                        <select id="full_name" name="name" placeholder="Select Full Name" required>
                                                            <option value=""></option>
                                                        </select>

                                                        <!-- <input type="text" name="name" class="form-control" id="name" placeholder="Full Name"> -->
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="email" class="form-control" id="email" placeholder="CCC Email Address" disabled>
                                                    </div>



                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Employment Information (EI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="employee_id"><b>Employee ID number</b></label>
                                                        <input type="text" name="employee_id" class="form-control" id="employee_id" placeholder="Employee ID number">
                                                        <small class="text-muted"></small>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="service_status"><b>Service Status</b></label>
                                                        <select name="service_status" id="service_status" class="form-control" required>
                                                            <option value="" selected disabled>Service Status</option>
                                                            <?php generateSelectOptions(EMPLOYMENT_SERVICE, true); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="personnel_classification"><b>Personnel Classification</b></label>
                                                        <select name="personnel_classification" id="personnel_classification" class="form-control" required>
                                                            <option value="" selected disabled>Personnel Classification </option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_CLASSIFICATION); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="employment_status"><b>Employment Status</b></label>
                                                        <select name="employment_status" id="employment_status" class="form-control" required>
                                                            <option value="" selected disabled>Employement Status</option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_STATUS); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="employment_basis"><b>Employment Basis</b></label>
                                                        <select name="employment_basis" id="employment_basis" class="form-control">
                                                            <option value="" selected disabled>Employment Basis</option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_BASIS); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-12 mb-2">
                                                        <label class="form-label" for="position"><b>Position</b></label>
                                                        <input type="text" name="position" class="form-control" id="position" placeholder="Position">
                                                    </div>

                                                    <!-- <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="employment_basis"><b>Employment Basis</b></label>
                                            <select name="employment_basis" id="employment_basis" class="form-control">
                                                <option value="" selected disabled>Employment Basis</option>
                                                <option value=""></option>
                                                <?php generateSelectOptions(EMPLOYMENT_BASIS); ?>
                                            </select>
                                        </div> -->
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
                                    <h6 class="modal-title" id="exampleModalToggleLabel">UPDATE EMPLOYEE INFORMATION</span></h6>
                                    <button type="button" id="close" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="update_information_form" name="update_information_form" method="post" action="/">

                                    <div class="modal-body">

                                        <div class="row p-2">
                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Basic Information (BI)</strong></h5>
                                                    <hr>

                                                    <input type="hidden" name="u-id" id="u-id">
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="u-name"><b>Full Name</b></label>
                                                        <input type="text" name="u-name" class="form-control" id="u-name" placeholder="Full Name" readonly>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-email"><b>CCC Email Address</b></label>
                                                        <input type="email" name="u-email" class="form-control" id="u-email" placeholder="CCC Email Address" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 border p-3 mb-3">
                                                <div class="row">
                                                    <h5><strong>Employment Information (EI)</strong></h5>
                                                    <hr>
                                                    <div class="col-lg-8 mb-2">
                                                        <label class="form-label" for="u-employee_id"><b>Employee ID number</b></label>
                                                        <input type="text" name="u-employee_id" class="form-control" id="u-employee_id" placeholder="Employee ID number">
                                                        <small class="text-muted"></small>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-service_status"><b>Service Status</b></label>
                                                        <select name="u-service_status" id="u-service_status" class="form-control" required>
                                                            <option value="" selected disabled>Service Status</option>
                                                            <?php generateSelectOptions(EMPLOYMENT_SERVICE, true); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-personnel_classification"><b>Personnel Classification</b></label>
                                                        <select name="u-personnel_classification" id="u-personnel_classification" class="form-control" required>
                                                            <option value="" selected disabled>Personnel Classification </option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_CLASSIFICATION); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-employment_status"><b>Employment Status</b></label>
                                                        <select name="u-employment_status" id="u-employment_status" class="form-control" required>
                                                            <option value="" selected disabled>Employement Status</option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_STATUS); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-employment_basis"><b>Employment Basis</b></label>
                                                        <select name="u-employment_basis" id="u-employment_basis" class="form-control">
                                                            <option value="" selected disabled>Employment Basis</option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_BASIS); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-lg-12 mb-2">
                                                        <label class="form-label" for="u-position"><b>Position</b></label>
                                                        <input type="text" name="u-position" class="form-control" id="u-position" placeholder="Position">
                                                    </div>

                                                    <!-- <div class="col-lg-4 mb-2">
                                                        <label class="form-label" for="u-employment_basis"><b>Employment Basis</b></label>
                                                        <select name="u-employment_basis" id="u-employment_basis" class="form-control">
                                                            <option value="" selected disabled>Employment Basis</option>
                                                            <option value=""></option>
                                                            <?php generateSelectOptions(EMPLOYMENT_BASIS); ?>
                                                        </select>
                                                    </div> -->
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
                    <div class="modal fade" id="add_bulk_employee" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkSchedBackdropLabel">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-light border-bottom py-3 px-4">
                                    <h5 class="modal-title" id="bulkSchedBackdropLabel">Import Bulk Employee Information</h5>
                                    <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form autocomplete="off" id="bulk_employee_form" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <?php render_action_links($my_links); ?>
                                        <h5 class="mb-1">
                                            <div id="employee_bulk_err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></div>
                                        </h5>

                                        <input type="file" id="import_employee_information" class="bulk_dropify" styles="height:500px" data-default-file="" name="import_employee_information" accept="text/csv" required>
                                        <small class="text-muted" style="font-size:small;"><b class="text-primary">NOTE:</b> Only users with existing user information can be added. Bulk imports are strictly used to add or update employee information and rely entirely on the <b class="text-primary">INSTITUTIONAL EMAIL</b>. All optional columns may be left blank.</small>
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

    <?php include_once ADMIN_FOOTER_PATH; ?>
</body>

<?php include_once ADMIN_SCRIPT_PATH; ?>

<script>
    (function() {
        let tabulator = null;

        /* Tabulator Table Setup */
        function initTabulatorTable() {
            const remoteTableUrl = "<?php echo BASE_URL; ?>table/employee-information-table";

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

                // Map JS parameters to match PHP $_GET expectations
                dataSendParams: {
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

                dataReceiveParams: {
                    "last_page": "last_page",
                    "data": "data"
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
                            let classAction = ``;

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
                        title: "Service Status",
                        field: "service_status",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All Statuses",
                                ...<?php echo json_encode(EMPLOYMENT_SERVICE); ?>
                            }
                        },
                        formatter: function(cell, formatterParams, onRendered) {
                            var key = cell.getValue();
                            var mapping = <?php echo json_encode(EMPLOYMENT_SERVICE); ?>;
                            return mapping[key] !== undefined ? mapping[key] : key;
                        },
                        accessorDownload: function(value, data, type, accessorParams, column) {
                            var mapping = <?php echo json_encode(EMPLOYMENT_SERVICE); ?>;
                            return mapping[value] !== undefined ? mapping[value] : value;
                        },
                    },
                    {
                        title: "Employee ID",
                        field: "employee_id",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
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
                        title: "Suffix Name",
                        field: "suffix",
                        visible: false,
                        print: false,
                        download: true,
                    },
                    {
                        title: "Personnel Classification",
                        field: "personnel_classification",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All Classifications",
                                ...<?php echo json_encode(array_combine(EMPLOYMENT_CLASSIFICATION, EMPLOYMENT_CLASSIFICATION)); ?>
                            }
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Employment Status",
                        field: "employment_status",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All Statuses",
                                ...<?php echo json_encode(array_combine(EMPLOYMENT_STATUS, EMPLOYMENT_STATUS)); ?>
                            }
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Employment Basis",
                        field: "employment_basis",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "select",
                        headerFilterLiveFilter: false,
                        headerFilterParams: {
                            values: {
                                "": "All Bases",
                                ...<?php echo json_encode(array_combine(EMPLOYMENT_BASIS, EMPLOYMENT_BASIS)); ?>
                            }
                        },
                        formatter: 'textarea',
                    },
                    {
                        title: "Position",
                        field: "position",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'center',
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
                        formatter: 'textarea',
                    },
                    {
                        title: "CCC Email Address",
                        field: "email",
                        minWidth: 170,
                        vertAlign: 'middle',
                        hozAlign: 'left',
                        headerFilter: "input",
                        headerFilterLiveFilter: false,
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
                        formatter: 'textarea',
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
                    tabulator.clearHeaderFilter();
                    tabulator.setData(remoteTableUrl, {
                        load_all: 0
                    });
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

        /*** add employee modal ***/
        $('.add-employee-btn').on('click', function() {
            $('#add_information_modal').modal('show');
            $('#add_information_modal').find('form').trigger('reset');
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
                    $('#email').val(''); // Clear if option is deselected
                    return;
                }

                // Grab the full data object of the selected item from Selectize memory
                var selectedItem = this.options[value];

                if (selectedItem && selectedItem.email) {
                    $('#email').val(selectedItem.email);
                    $('#user_id').val(selectedItem.user_id);
                } else {
                    $('#email').val('');
                    $('#user_id').val('');
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
        $('#add_information_modal').on('shown.bs.modal', function() {
            selectizeControl.clear();
            selectizeControl.clearOptions();
            $('#email').val(''); // Clear the email input field on modal open

            selectizeControl.load(function(callback) {
                $.ajax({
                    url: "<?php echo BASE_URL; ?>ajax/employee-information-process?action=fetchEmployee",
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

        /*** submit the form ***/
        $("#employee_information_form").on('submit', async function(e) {
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

            let formData = jQuery("#employee_information_form").serializeArray();
            let newData = [{
                name: "actionSubmitEmployee",
                value: "submitUserEmployee"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/employee-information-process",
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
                },
            });
        });

        function openUpdateModal(data) { // for updating the 
            // Set the values of the form
            document.getElementById("update_information_modal").querySelector("#u-id").value = data.id;
            document.getElementById("update_information_modal").querySelector("#u-name").value = data.name;
            document.getElementById("update_information_modal").querySelector("#u-email").value = data.email;
            document.getElementById("update_information_modal").querySelector("#u-employee_id").value = data.employee_id;
            document.getElementById("update_information_modal").querySelector("#u-service_status").value = data.service_status;
            document.getElementById("update_information_modal").querySelector("#u-personnel_classification").value = data.personnel_classification;
            document.getElementById("update_information_modal").querySelector("#u-employment_status").value = data.employment_status;
            document.getElementById("update_information_modal").querySelector("#u-employment_basis").value = data.employment_basis;
            document.getElementById("update_information_modal").querySelector("#u-position").value = data.position;

            $("#update_information_modal").modal("show");
        };

        /*** submit update form ***/
        $('#update_information_form').on('submit', async function(e) {
            e.preventDefault();

            const isConfirmed = await notifyConfirm(
                'Confirm Submission',
                'Are you sure you want to update this employee information?',
                'Yes, save it!',
                'question'
            );
            if (!isConfirmed) return;

            const $form = $(this);
            const $submitBtn = $form.find(':submit');
            const $inputs = $form.find(':input, :button');

            let formData = jQuery("#update_information_form").serializeArray();
            let newData = [{
                name: "actionUpdateEmployee",
                value: "submitUpdateEmployee"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/employee-information-process",
                type: 'POST',
                data: postData,
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
                    if (output.msg_status === true) {
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
                },
            });
        });

        /*** add bulk modal ***/
        $('.bulk-employee-btn').on('click', function() {
            $('.dropify-clear').click();

            $('#add_bulk_employee').find('form').trigger('reset');
            $('#add_bulk_employee').modal('show');
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

        /*** submit the bulk schedule ***/
        $("#bulk_employee_form").on('submit', async function(e) {
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
                url: "<?php echo BASE_URL; ?>ajax/employee-information-bulk",
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
                        $('#add_bulk_employee').modal('hide');

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
        
    })();
</script>

</html>