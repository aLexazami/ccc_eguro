<?php
$first_name = $first_name ?? '';
$link_reset = $link_reset ?? '';
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
																		<td align="center" style="padding-top:0;padding-bottom:20px"><img src="' . LOGO . '" height="130" style="vertical-align:middle" data-image-whitelisted="" class="CToWUd" data-bit="iit"></td>
																	</tr>
																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px;padding-bottom:20px;text-align:center;">
																			<h3 style="margin-top:0;margin-bottom:0;font-family:Montserrat,Helvetica,Arial,sans-serif!important;font-weight:700;font-size:20px;line-height:30px;color:#222">Account Password Reset Request</h3>
																		</td>
																	</tr>

																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;font-weight:600;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> Hi ' . $first_name . ',</td>
																	</tr>

																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px">We received a request to reset the password for your account. You can do this by clicking the link below:.</td>
																	</tr>

																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:40px;padding-bottom:20px">
																			<table style="text-align:center" width="100%" border="0" cellspacing="0" cellpadding="0">
																				<tbody>
																					<tr>
																						<td>
																							<div style="text-align:center;margin:0 auto"> <a rel="nofollow" style="background-color: rgba(0, 16, 77, 1);border:2px solid rgba(0, 16, 77, 1);border-radius:2px;color:#ffffff;white-space:nowrap;font-weight:bold;display:block;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:36px;text-align:center;text-decoration:none" href="' . $link_reset . '" target="_blank">Reset Password</a> </div>
																						</td>
																					</tr>
																				</tbody>
																			</table>
																		</td>
																	</tr>

																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px">Note: For your security, this link will expire in 4 hours.</td>
																	</tr>
																	
																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> If you did not make this request, you can safely ignore this email—your account remains secure.</td>
																	</tr>
																	
																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:16px;line-height:24px;padding-left:20px;padding-right:20px;padding-top:30px">
																			<div style="padding-top:10px">Best Regards,<br><span style="font-weight:600;">' . SYSTEM_TEAM . '</span></div>
																		</td>
																	</tr>

																	<tr>
																		<td style="font-family:Helvetica,Arial,sans-serif!important;color:black;font-size:14px;font-style:italic;line-height:24px;padding-left:20px;padding-right:20px;padding-top:20px"> This is a system-generated e-mail. Please do not reply. </td>
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
							<div style="font-family:Helvetica,Arial,sans-serif!important">Copyright &copy;' .  YEAR_CREATED . ' ' . SCHOOL_NAME . '. All Rights Reserved.</div>
						</td>
					</tr>
				</tbody>
			</table>
			</div>';
