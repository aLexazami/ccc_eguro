<?php
# ======================================================================
# GLOBAL CSS & HEAD ASSETS INCLUSION FILE
# Holds all foundational CSS and asset dependencies included within 
# the <head> element. Files are ordered by cascade priority:
# Core Framework -> Icon Fonts -> Plugins -> Base Layout -> Overrides
# ======================================================================
?>

<?php if (ACTIVE_PAGE != "digital_profile"): ?>
    <!-- Favicons & Web Icons -->
    <link rel="icon" type="image/x-icon" href="<?php echo FAVICON; ?>">
    <link rel="apple-touch-icon" href="<?php echo FAVICON; ?>">
<?php endif; ?>

<!-- Root  -->
<link rel="stylesheet" type="text/css" href="<?php echo BASE_MAIN_ASSETS_PATH; ?>css/root.css?v=<?php echo FILE_VERSION; ?>">

<!-- Vendor Bootstrap Files -->
<link rel="stylesheet" type="text/css" href="<?php echo BASE_VENDOR_ASSETS_PATH; ?>bootstrap/bootstrap.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_VENDOR_ASSETS_PATH; ?>bootstrap-icons/bootstrap-icons.css?v=<?php echo FILE_VERSION; ?>">

<!-- Icon Fonts (Font Awesome via CDN) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<!-- <link rel="stylesheet" type="text/css" href="<?php echo BASE_VENDOR_ASSETS_PATH; ?>font-awesome/all.min.css?v=<?php echo FILE_VERSION; ?>"> -->

<!-- Typography & Google Fonts (Optional) -->
<link rel="preconnect" href="https://fonts.gstatic.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">

<!-- MAIN CSS Files -->
<link rel="stylesheet" type="text/css" href="<?php echo BASE_MAIN_ASSETS_PATH; ?>css/custom-style.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_MAIN_ASSETS_PATH; ?>css/main-style.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_MAIN_ASSETS_PATH; ?>css/digital-profile.css?v=<?php echo FILE_VERSION; ?>">

<!-- ADMIN CSS Files -->
<link rel="stylesheet" type="text/css" href="<?php echo BASE_ADMIN_ASSETS_PATH; ?>css/custom-sweetalert.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_ADMIN_ASSETS_PATH; ?>css/selectize.bootstrap5.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_ADMIN_ASSETS_PATH; ?>css/bootstrap-datetimepicker.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_ADMIN_ASSETS_PATH; ?>css/jquery.timepicker.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_ADMIN_ASSETS_PATH; ?>css/dropify/dropify.css?v=<?php echo FILE_VERSION; ?>">

<!-- Form & Input Component Plugins -->

<!-- UI Data & Alert Plugins -->
<!-- <link rel="stylesheet" type="text/css" href="<?php echo BASE_MAIN_ASSETS_PATH; ?>assets/css/tabulator_bootstrap.min.css?v=<?php echo FILE_VERSION; ?>"> -->
<!-- <link rel="stylesheet" type="text/css" href="<?php echo BASE_MAIN_ASSETS_PATH; ?>assets/css/custom-sweetalert.css?v=<?php echo FILE_VERSION; ?>"> -->

<!-- Application Base & Theme Layouts -->