<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require API_DATA; // API data
require VALIDATOR_PATH;
require ISLOGIN;

$system_auth_login = $session_class->getValue(SYSTEM_ACCESS['E-GURO++']['auth']);
if (!($system_auth_login == PUBLIC_KEY)) {
    header("location: " . SYSTEM_ACCESS['E-GURO++']['link']['main']);
    exit();
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
        <!-- <div class="pagetitle">
            <h1>Activity Log</h1>
        </div> -->

        <section class="section">
            <div class="card">
                <div class="card-header color-accent-blue-bg text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="font-size: large;">
                    <div>
                        <i class="bi bi-person-circle"></i>&ensp;Activity Log
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">

                    <div id="activity-log-table" class="table table-bordered"></div>

                </div>
            </div>
        </section>
    </main>

    <?php
    include_once DOMAIN_PATH . '/global/footer.php'; ## footer
    include_once DOMAIN_PATH . '/global/include_bottom.php'; ## scripts
    ?>

</body>
<script>
    (function() {
        /** user table **/
        const user_table = new Tabulator("#activity-log-table", {
            ajaxSorting: false,
            ajaxFiltering: false,
            height: "700px",
            printAsHtml: true,
            headerFilterPlaceholder: "Search",
            layout: "fitDataStretch",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: true,
            // groupBy: function(data) {
            //     return " [" + data.general_id + "] " + data.name; //groups by general id and name
            // },
            // groupHeader: btnInfo,
            // groupUpdateOnCellEdit: true,
            pagination: "remote",
            ajaxURL: "<?php echo BASE_URL; ?>admin/log_process.php",
            ajaxParams: {
                table: 'activity_log'
            },
            paginationSize: <?php echo QUERY_LIMIT; ?>,
            printConfig: {
                columnGroups: false,
                rowGroups: false,
            },
            ajaxLoader: true,
            ajaxLoaderLoading: 'Fetching data from Database..',
            selectableRollingSelection: false,
            headerHozAlign: 'center',
            paginationSizeSelector: [100, 500, 1000, true],

            columns: [{
                    title: "Date & Time",
                    field: "date_log",
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
                    minWidth: 150
                },
                {
                    title: "ACTION",
                    field: "action",
                    titlePrint: "Action",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false,
                    formatter: function(cell, formatterParams, onRendered) {
                        var string = cell.getValue();
                        var result = cell.getValue();
                        if (string) {
                            var postion = string.toLowerCase().indexOf('upload_');
                            var first_string = "";
                            var second_string = "";
                            if (postion !== -1) {
                                first_string = string.substring(0, postion);
                                second_string = string.substring(postion);
                                result = first_string + '<a href="<?php echo BASE_URL; ?>upload/logs/' + second_string.slice(0, -1).trim() + '.txt" target="_blank" download="" title="DOWNLOAD"><i class="fas fa-download"></i> ' + second_string + '</a>'
                            } else {
                                result = cell.getValue();
                            }
                            cell.getElement().style.whiteSpace = "pre-wrap";
                        }
                        return result; //return the contents of the cell;
                    }
                }

            ],
            ajaxResponse: function(url, params, response) {
                //url - the URL of the request
                //params - the parameters passed with the request
                //response - the JSON object returned in the body of the response.
                return response; //return the tableData property of a response json object
            },
        });

    })();
</script>

</html>