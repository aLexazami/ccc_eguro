<?php
require '../config/config.php';
require CONNECT_PATH;
require GLOBAL_FUNC;
require CL_SESSION_PATH;

$session_class->dropValue(SYSTEM_ACCESS['E-GURO++']['auth']);
header('Location: ' . BASE_URL . 'app/index.php');
exit();
?>
