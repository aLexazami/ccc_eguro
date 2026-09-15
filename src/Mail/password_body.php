<?php
$first_name    = $first_name ?? '';
$username      = $username ?? '';
$text_password = $text_password ?? '';
$msg_raw  = $msg_raw ?? '';
$msg_raw .= '<div style="max-width:600px;margin:0 auto;font-size:16px;line-height:24px">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tbody>
                        <tr>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                    <tbody>
                                                        <tr>
                                                            <td style="background-color:white;padding-top:30px;padding-bottom:30px">
                                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td align="center" style="padding-top:0;padding-bottom:20px"><img src="' . DISPLAY_LOGO . '" height="130" style="vertical-align:middle" data-image-whitelisted="" class="CToWUd" data-bit="iit"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px;padding-bottom:20px;text-align:center;">
                                                                                <h3 style="margin-top:0;margin-bottom:0;font-family:Montserrat,Helvetica,Arial,sans-serif!important;font-weight:700;font-size:20px;line-height:30px;color:#222"> Account Password Successfully Changed </h3>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> Hi ' . $first_name . ',</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> Your account password has been successfully changed. You can now log in to your account in the ' . SYSTEM_NAME . ' Portal. Please use the following credentials to log in to your account:<br><br> <strong>Username: ' . $username . '</strong><br> <strong>Password: ' . $text_password . '</strong> </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:40px;padding-bottom:20px">
                                                                                <table style="text-align:center" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                                                    <tbody>
                                                                                        <tr>
                                                                                            <td>
                                                                                                <div style="text-align:center;margin:0 auto"> <a rel="nofollow" style="background-color:#37a000;border:2px solid #37a000;border-radius:2px;color:#ffffff;white-space:nowrap;font-weight:bold;display:block;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:36px;text-align:center;text-decoration:none" href="' . BASE_URL . '" target="_blank">Go to Login page</a> </div>
                                                                                            </td>
                                                                                        </tr>
                                                                                    </tbody>
                                                                                </table>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> Please do not share this information with others.</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> This is a system-generated e-mail. Please do not reply. </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:30px">
                                                                                <div style="padding-top:10px">Thanks for your time,<br>The ' . SYSTEM_TEAM . '</div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tbody>
                        <tr>
                            <td align="center" width="100%" style="color:#656565;font-size:12px;line-height:24px;padding-bottom:30px;padding-top:30px">
                                <div style="font-family:Helvetica,Arial,sans-serif!important">' . (YEAR <= YEAR_CREATED ? ('&copy; ' . YEAR . ' ' . SYSTEM_NAME . ' - ' . SCHOOL_NAME) : (' &copy; ' . YEAR_CREATED . ' - ' . YEAR . ' ' . SYSTEM_NAME . ' - ' . SCHOOL_NAME)) . '</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>';
