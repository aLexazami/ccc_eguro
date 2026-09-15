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
# ===================================================================================

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
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include_once ADMIN_META_DATA_PATH; ?>
    <?php include_once ADMIN_LINK_PATH; ?>

    <style>
        .selectize-dropdown {
            z-index: 9999 !important;
        }

        .selectize-input {
            z-index: 1000;
        }
    </style>
</head>

<body class="d-flex flex-column h-100vh body-module">
    <?php include_once ADMIN_HEADER_PATH; ?>
    <?php include_once ADMIN_SIDEBAR_PATH; ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <!-- card header -->
                <?php
                $action_buttons = [
                    [
                        'label' => 'Add Student Information',
                        'icon'  => 'bi bi-plus-circle',
                        'class' => 'add-student-btn',
                        'type'  => 'button'
                    ],
                    [
                        'label' => 'Import Bulk Student',
                        'icon'  => 'bi bi-arrow-bar-up bulk',
                        'class' => 'bulk-student-btn',
                        'type'  => 'button'
                    ]
                ];

                render_card_header('Student Information', 'bi bi-person-bounding-box', $action_buttons);
                ?>

                <div class="card-body mt-3 bg-white">
                    <div id="student-table" class="table table-bordered tabulator" style="min-height: 600px;"></div>
                    <div>
                        <button type="button" class="btn btn-blue btn-sm" id="student-download-csv">Download CSV</button>
                        <button type="button" class="btn btn-blue btn-sm" id="student-download-xlsx">Download XLSX</button>
                        <button type="button" class="btn btn-blue btn-sm" id="student-print-table">Print</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- modal -->
        <!-- add user modal -->
        <div class="modal fade" id="add_information_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h5 class="modal-title" id="exampleModalToggleLabel">ADD STUDENT INFORMATION</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="student_information_form">
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
                                            <label class="form-label" for="employee_id"><b>Employee ID number</b> <span class="required-field"></span></label>
                                            <input type="text" name="employee_id" class="form-control" id="employee_id" placeholder="Employee ID number" required>
                                            <small class="text-muted"></small>
                                        </div>

                                        <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="service_status"><b>Service Status</b> <span class="required-field"></span></label>
                                            <select name="service_status" id="service_status" class="form-control" required>
                                                <option value="" selected disabled>Service Status</option>
                                                <?php generateSelectOptions(EMPLOYMENT_SERVICE, true); ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="personnel_classification"><b>Personnel Classification</b> <span class="required-field"></span></label>
                                            <select name="personnel_classification" id="personnel_classification" class="form-control" required>
                                                <option value="" selected disabled>Personnel Classification </option>
                                                <option value=""></option>
                                                <?php generateSelectOptions(EMPLOYMENT_CLASSIFICATION); ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="employment_status"><b>Employment Status</b> <span class="required-field"></span></label>
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
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-blue" id="btn_submit" name="actionSubmit" value="submitUser">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- update information modal -->
        <div class="modal fade" id="update_information_modal" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="exampleModalToggleLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h6 class="modal-title" id="exampleModalToggleLabel">UPDATE STUDENT INFORMATION</span></h6>
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
                                            <label class="form-label" for="u-employee_id"><b>Employee ID number</b> <span class="required-field"></span></label>
                                            <input type="text" name="u-employee_id" class="form-control" id="u-employee_id" placeholder="Employee ID number" required>
                                            <small class="text-muted"></small>
                                        </div>

                                        <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="u-service_status"><b>Service Status</b> <span class="required-field"></span></label>
                                            <select name="u-service_status" id="u-service_status" class="form-control" required>
                                                <option value="" selected disabled>Service Status</option>
                                                <?php generateSelectOptions(EMPLOYMENT_SERVICE, true); ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="u-personnel_classification"><b>Personnel Classification</b> <span class="required-field"></span></label>
                                            <select name="u-personnel_classification" id="u-personnel_classification" class="form-control" required>
                                                <option value="" selected disabled>Personnel Classification </option>
                                                <option value=""></option>
                                                <?php generateSelectOptions(EMPLOYMENT_CLASSIFICATION); ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 mb-2">
                                            <label class="form-label" for="u-employment_status"><b>Employment Status</b> <span class="required-field"></span></label>
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
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-blue" id="btn_info" name="actionInfo" value="updateInfo">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- add bulk user modal -->
        <div class="modal fade" id="add_bulk_student" aria-hidden="true" data-bs-backdrop="static" aria-labelledby="bulkSchedBackdropLabel">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header color-accent-blue-bg text-white">
                        <h5 class="modal-title" id="bulkSchedBackdropLabel">Import Bulk Student Information</h5>
                        <button type="button" class="btn-close" id="close_csv_upload" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form autocomplete="off" id="bulk_student_form" enctype="multipart/form-data">
                        <div class="modal-body">
                            <?php
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

                            // Call the function anywhere you want just the action row
                            render_action_links($my_links);
                            ?>
                            <h5 class="mb-1">
                                <div id="student_bulk_err_msg" class="badge bg-danger text-white rounded-1 p-2 mt-1 text-sm-start mx-2 text-wrap"></div>
                            </h5>

                            <input type="file" id="import_student_information" class="bulk_dropify" styles="height:500px" data-default-file="" name="import_student_information" accept="text/csv" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-blue" id="submit_bulk">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include_once ADMIN_FOOTER_PATH; ?>
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

        /** set table variables **/
        let table = null;
        let total_record = 0;

        function record_details(values, data, calcParams) {
            if (values && values.length) return values.length + ' of ' + total_record;
        }

        /** user information table **/
        table = new Tabulator("#student-table", {
            height: "800px",
            layout: "fitDataStretch",
            headerHozAlign: 'center',
            tooltips: true,
            movableColumns: true,
            selectable: true,

            placeholder: "No Data Found",
            headerFilterPlaceholder: "Search",
            ajaxSorting: true,
            ajaxFiltering: true,
            ajaxURL: "<?php echo BASE_URL; ?>table/student-information-table",
            ajaxParams: {
                load_all: 0
            },
            ajaxProgressiveLoad: "scroll",
            ajaxProgressiveLoadScrollMargin: 1,
            ajaxLoader: true,
            ajaxLoaderLoading: 'Fetching data from Database..',

            selectableRollingSelection: false,
            paginationSize: <?php echo QUERY_LIMIT; ?>,

            printAsHtml: true,
            printConfig: {
                columnGroups: false,
                rowGroups: false,
            },
            downloadConfig: {
                columnHeaders: true,
                columnGroups: false,
                rowGroups: false,
                formatCells: true
            },
            columns: [{
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
                {
                    title: "Service Status",
                    field: "service_status",
                    headerFilter: "select",
                    headerFilterParams: {
                        "": "",
                        ...<?php echo json_encode(array_combine(EMPLOYMENT_SERVICE, EMPLOYMENT_SERVICE)); ?>
                    },
                    headerFilterLiveFilter: false,
                    formatter: function(cell, formatterParams, onRendered) {
                        var key = cell.getValue();
                        var mapping = <?php echo json_encode(EMPLOYMENT_SERVICE); ?>;
                        return mapping[key] !== undefined ? mapping[key] : key;
                    },
                    accessorDownload: function(value, data, type, accessorParams, column) {
                        var mapping = <?php echo json_encode(EMPLOYMENT_SERVICE); ?>;
                        return mapping[value] !== undefined ? mapping[value] : value;
                    },
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170
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
                    bottomCalc: record_details,
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
                    hozAlign: 'center',
                    minWidth: 170,
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
                    headerFilter: "select",
                    headerFilterParams: {
                        "": "",
                        ...<?php echo json_encode(array_combine(EMPLOYMENT_CLASSIFICATION, EMPLOYMENT_CLASSIFICATION)); ?>
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170
                },
                {
                    title: "Employment Status",
                    field: "employment_status",
                    headerFilter: "input",
                    headerFilter: "select",
                    headerFilterParams: {
                        "": "",
                        ...<?php echo json_encode(array_combine(EMPLOYMENT_STATUS, EMPLOYMENT_STATUS)); ?>
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170
                },
                {
                    title: "Employment Basis",
                    field: "employment_basis",
                    headerFilter: "input",
                    headerFilter: "select",
                    headerFilterParams: {
                        "": "",
                        ...<?php echo json_encode(array_combine(EMPLOYMENT_BASIS, EMPLOYMENT_BASIS)); ?>
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170
                },
                {
                    title: "Position",
                    field: "position",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170
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
                    hozAlign: 'center',
                    minWidth: 170
                },
                {
                    title: "Personal Email",
                    field: "personal_email",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    headerFilterLiveFilter: false,
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170
                },
                {
                    title: "Date Added/Modify",
                    field: "date_modify",
                    formatter: 'textarea',
                    vertAlign: 'middle',
                    hozAlign: 'center',
                    minWidth: 170,
                    print: false,
                    download: false,
                },

            ],
            ajaxResponse: function(url, params, response) {
                if (response.total_record) total_record = response.total_record;
                //url - the URL of the request
                //params - the parameters passed with the request
                //response - the JSON object returned in the body of the response.
                return response; //return the tableData property of a response json object
            },
            // renderComplete: function() {
            //     // Now it's safe to get the updated row count
            //     const visible_rows = table.getDataCount();
            //     document.getElementById("footer-total").innerHTML = `${visible_rows} of ${total_record}`;
            // },
        });

        /** export user data table **/
        addListener(document.getElementById('student-download-csv'), "click", function() {
            table.getGroups().forEach(x => x._group.show());
            table.download("csv", "student_information_" + getFormattedTime() + ".csv", {
                bom: true
            });
            table.getGroups().forEach(x => x._group.hide());
        });

        addListener(document.getElementById('student-download-xlsx'), "click", function() {
            table.download("xlsx", "student_information_" + getFormattedTime() + ".xlsx");
        });

        addListener(document.getElementById('student-print-table'), "click", function() {
            table.print(false, true);
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

        // /** date picker **/
        // const birth_datePicker = document.getElementById("birth_date");
        // const u-birth_date = document.getElementById("u-birth_date");

        // $(u-birth_date).datetimepicker({
        //     format: 'YYYY-MM-DD',
        //     maxDate: dateLimit
        // });

        // $(birth_datePicker).datetimepicker({
        //     format: 'YYYY-MM-DD',
        //     maxDate: dateLimit
        // });


        /*** add modal ***/
        $('.add-student-btn').on('click', function() {
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
                    url: "<?php echo BASE_URL; ?>ajax/student-information-process?action=fetchStudent",
                    type: 'GET',
                    dataType: 'json',
                    error: function() {
                        console.log('Error fetching student data.');
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
        $("#student_information_form").on('submit', function(e) {
            e.preventDefault();

            let formData = jQuery("#student_information_form").serializeArray();
            let newData = [{
                name: "actionSubmitStudent",
                value: "submitUserStudent"
            }];
            let postData = formData.concat(newData);

            $.ajax({
                url: "<?php echo BASE_URL; ?>ajax/student-information-process",
                method: "POST",
                data: postData,
                dataType: "json",
                beforeSend: function() {
                    $('#student_information_form :submit').html('<span class="spinner-border spinner-border-sm"></span>Loading..');
                    $("#student_information_form :input").prop("disabled", true);
                    $("#student_information_form :button").prop("disabled", true);
                },
                complete: function() {
                    $('#student_information_form :submit').html('Submit');
                    $("#student_information_form :input").prop("disabled", false);
                    $("#student_information_form :button").prop("disabled", false);
                },
                success: function(output) {
                    if (output.msg_status === false) {
                        error_notif(output.msg_response);
                    } else if (output.msg_status === true) {
                        table.setData();
                        $('#add_information_modal').modal('hide');
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
                            // password_modal(output.password);
                        });
                    } else {
                        error_notif("Request error, please try again");
                    }
                },
                error: function(xhr, status, error) {
                    error_notif(status + "::" + error);
                },
                // async: false
            });
        });

        function btnAction(cell, formatterParams, onRendered) { // for updating the 
            var cellEl = cell.getElement(); //get cell DOM element
            var actionBut = document.createElement("span");
            var row = cell.getRow();
            var data = row.getData();
            var edit_btn = document.createElement("button");
            var delete_btn = document.createElement("button");

            edit_btn.classList.add("btn", "btn-sm", "btn-outline-primary", "btn-rounded", "m-1");
            edit_btn.style.fontSize = "small";
            edit_btn.innerHTML = '<i class="bi bi-pencil-square"></i>&ensp;Update Information';
            edit_btn.addEventListener("click", function() {
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
            });

            actionBut.appendChild(edit_btn);

            // actionBut.appendChild(delete_btn);
            return cellEl.appendChild(actionBut);
        };

        /*** submit update form ***/
        $('#update_information_form').on('submit', function(e) {
            e.preventDefault();
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
                    $('#update_information_form :submit').html('<span class="spinner-border spinner-border-sm"></span>Loading..');
                    $("#update_information_form :button").prop("disabled", true);
                },
                complete: function() {
                    $('#update_information_form :submit').html('Submit');
                    $("#update_information_form :button").prop("disabled", false);
                },
                success: function(output) {
                    if (output.msg_status === false) {
                        error_notif(output.msg_response);
                    } else if (output.msg_status === true) {
                        user_table.setData();
                        $("#update_information_modal").modal('hide');
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
                        });
                    } else {
                        error_notif("Request error, please try again");
                    }
                },
                error: function(xhr, status, error) {
                    error_notif(status + "::" + error);
                },
                // async: false
            });
        });

        /*** add bulk modal ***/
        $('.bulk-student-btn').on('click', function() {
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
        $("#bulk_employee_form").on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: "POST",
                url: "<?php echo BASE_URL; ?>ajax/employee-information-bulk",
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
                                table.setData();
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