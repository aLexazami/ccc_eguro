<!-- <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up"></i></a> -->

<!-- Vendor JS Files -->
<script type="text/javascript" src="<?php echo BASE_VENDOR_ASSETS_PATH; ?>bootstrap/bootstrap.bundle.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- Main JS Files -->
<script type="text/javascript" src="<?php echo BASE_MAIN_ASSETS_PATH; ?>js/jquery.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_MAIN_ASSETS_PATH; ?>js/main.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_MAIN_ASSETS_PATH; ?>js/app.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_MAIN_ASSETS_PATH; ?>js/moment.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_MAIN_ASSETS_PATH; ?>js/moment-timezone-with-data.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- Template Main JS File -->
<script type="text/javascript" src="<?php echo BASE_MAIN_ASSETS_PATH; ?>js/main-script.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- ADMIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>js/plugin/sweetalert/sweetalert.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>js/bootstrap-datetimepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>js/jquery.timepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- <script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>js/tabulator.min.js?v=<?php echo FILE_VERSION; ?>"></script> 
<script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>assets/js/xlsx.full.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>assets/js/dropify.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>assets/js/selectize.min.js?v=<?php echo FILE_VERSION; ?>"></script> -->

<!-- <script type="text/javascript" src="<?php echo BASE_ADMIN_ASSETS_PATH; ?>assets/js/clipboard.min.js?v=<?php echo FILE_VERSION; ?>"></script> -->

<!-- Scripts -->
<script>
    /** agents for logoutSubmit **/
    $("#logout_form").submit(function(eventObj) {
        var json = {};

        json['device'] = platform.name;
        json['version'] = platform.version;
        json['layout'] = platform.layout;
        json['os'] = platform.os;
        json['description'] = platform.description;

        $("<input />").attr("type", "hidden")
            .attr("name", "agents")
            .attr("value", JSON.stringify(json))
            .appendTo("#logout_form");
        return true;
    });

    var set_server_time = <?php echo "'" . DATE_TIME . "';\r\n"; ?>
    var serverOffset = moment(set_server_time).diff(new Date());
    var clock_id = datetime();

    function datetime() {
        setInterval(function() {
            if (document.getElementById('now')) {
                var now_server = moment();
                now_server.add(serverOffset, 'milliseconds');
                var timeNow = now_server.format('ddd | MMMM DD, YYYY h:mm:ss A');
                $('#now').html(timeNow);
                $('#printTime').html('Date Printed : ' + timeNow);
            } else {
                clearInterval(clock_id);
            }
        }, 1000);
    }

    function msg_modal(title, msg, type) {
        Swal.fire(title, msg, type);
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    function error_notif(value = "", options) {
        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#f27474",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }

        Toast.fire({
            icon: 'error',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    function success_notif(value = "", options) {
        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#a5dc86",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }

        Toast.fire({
            icon: 'success',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    function warning_notif(value = "", options) {

        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#f8bb86",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }
        Toast.fire({
            icon: 'warning',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    function info_notif(value = "", options) {
        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#3fc3ee",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }
        Toast.fire({
            icon: 'info',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        });
    }

    function question_notif(value = "", options) {

        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#87adbd",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }
        Toast.fire({
            icon: 'info',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    // app_onsite main sweetalert
    function msg_alert(title, icon) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-right',
            iconColor: 'white',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            },
            customClass: {
                popup: 'colored-toast'
            }
        })
        Toast.fire({
            title: title,
            icon: icon
        })
    }

    function msg_html(img, name, position, remark, time = "") {
        var time = time == '' ? 3000 : time;
        var footer_class = 'swal-footer-timein';
        if (remark == 'Successfully Time In') {
            footer_class = 'swal-footer-timein';
        } else if (remark == 'Successfully Break Out') {
            footer_class = 'swal-footer-breakout';
        } else if (remark == 'Successfully Break In') {
            footer_class = 'swal-footer-breakin';
        } else if (remark == 'Successfully Time Out') {
            footer_class = 'swal-footer-timeout';
        }

        Swal.fire({
            html: `<div class="row">
                    <div class="col-12">` + img + `</div>
                    <div class="row">
                        <div class="col-12 fw-bold fs-3 text-dark">` + name + `</div>
                        <div class="col-12">` + position + `</div>
                    </div>
                </div>`,
            footer: remark,
            showConfirmButton: false,
            timerProgressBar: true,
            width: 450,
            padding: '1em 0 0',
            customClass: {
                popup: 'swal-popup-modal',
                footer: footer_class,
            },
            timer: time
        });
    }

    function msg_error(title, msg, time = "") {
        var time = time == '' ? 3000 : time;
        Swal.fire({
            title: title,
            text: msg,
            icon: 'error',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: time
        });
    }

    function msg_warning(title, msg, time = "") {
        var time = time == '' ? 3000 : time;
        Swal.fire({
            title: title,
            text: msg,
            icon: 'warning',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: time
        });
    }

    function msg_success(title, msg, time = "") {
        var time = time == '' ? 3000 : time;
        Swal.fire({
            title: title,
            text: msg,
            icon: 'success',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: time
        });
    }

    function msg_login(title) {
        Swal.fire({
            html: `<div class="d-flex align-items-center" style="padding: 1.8em 0 1.8em ;">
                        <div style="padding: 0 1.8em;">
                            <img src="<?php echo BASE_URL; ?>assets/img/logo-light.png" alt="" height="110px">
                        </div>
                        <div style="font-size: 1.5em;">
                            <strong>` + title + `<strong>
                        </div>
                    </div>`,
            timerProgressBar: true,
            showConfirmButton: false,
            padding: 0,
            timer: 2000
        });
    }
</script>