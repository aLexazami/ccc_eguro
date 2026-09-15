<?php
require 'config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;


$csrf = new CSRF($session_class);

$random_key = $csrf->string('token_key',-1,1);
$random_string = $csrf->string('token_string',-1,1);
$secret_key = custom_encrypted_string( $random_key, $random_string );

?>
<form>
    <label>Random Key: <?php echo $random_key ?></label><br>
    <label>Public key: <?php echo $random_string ?></label><br>
    <label>Secret key: <?php echo $secret_key ?></label><br>
    <label>Password: <?php echo set_password("user_api"); ?></label><br>
</form>