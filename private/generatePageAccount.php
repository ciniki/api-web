<?php
//
// Description
// -----------
// This function will generate a customers account page.  This page allows the customer
// to login to their account, change their password and subscribe/unsubscribe to public
// subscriptions (newsletters).
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageAccount($ciniki, $settings) {

	//
	// Store the content created by the page
	// Make sure everything gets generated ok before returning the content
	//
	$content = '';
	$subscription_err_msg = '';
	$chgpwd_err_msg = '';

	//
	// Load the business modules
	//
	$modules = array();
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'getActiveModules');
	$rc = ciniki_businesses_getActiveModules($ciniki, $ciniki['request']['business_id']);
	if( $rc['stat'] == 'ok' ) {
		$modules = $rc['modules'];
	}

	//
	// Check if a form was submitted
	//
	$err_msg = '';
	$display_form = 'login';
	if( isset($_POST['action']) ) {
		if( $_POST['action'] == 'logout' ) {
			$ciniki['session']['customer'] = array();
			$ciniki['session']['user'] = array();
			$ciniki['session']['change_log_id'] = '';
			unset($_SESSION['customer']);
		}
		elseif( $_POST['action'] == 'signin' ) {
			//
			// Check the referrer and that cookies are enabled
			//
			if( !isset($_SESSION['loginform']) ) {
				$err_msg = "It appears that you do not have cookies enabled in your browser.  They are "
					. "required for you to login.  Please check your browser settings and try again.  <br/><br/>Here is a link to help: "
					. "<a target='_blank' href='http://support.google.com/accounts/bin/answer.py?hl=en&answer=61416'>How to enable cookies</a>."
					. "";
				$display_form = 'login';
			}

			// Verify the customer and create a session
			elseif( isset($_POST['email']) && $_POST['email'] != '' 
				&& isset($_POST['password']) && $_POST['password'] != '' 
				) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'auth');
				$rc = ciniki_customers_web_auth($ciniki, $ciniki['request']['business_id'], $_POST['email'], $_POST['password']);
				if( $rc['stat'] != 'ok' ) {
					$err_msg = "Unable to authenticate, please try again or click Forgot your password to get a new one";
					$display_form = 'login';
				} else {
					$display_form = 'no';
				}
			}
		}
		elseif( $_POST['action'] == 'forgot' ) {
			// Set the forgot password notification
			if( isset($_POST['email']) && $_POST['email'] != '' ) {
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $ciniki['request']['base_url'] . '/account/reset';
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'passwordRequestReset');
				$rc = ciniki_customers_web_passwordRequestReset($ciniki, $ciniki['request']['business_id'], $_POST['email'], $url);
				if( $rc['stat'] != 'ok' ) {
					$err_msg = 'You must enter a valid email address to get a new password.';
					$display_form = 'forgot';
				} else {
					$display_form = 'no';

					$content .= "<div id='content'>\n"
						. "<article class='page'>\n"
						. "<header class='entry-title'><h1 class='entry-title'>Account</h1></header>\n";
					$content .= "<div class='entry-content'>"
						. "<p>A link has been sent to your email to get a new password.</p>\n"
						. "</div>";
					$content .= "</article>\n"
						. "</div>\n";
				}
			} else {
				$err_msg = 'You must enter a valid email address to get a new password.';
				$display_form = 'forgot';
			}
		}
		//
		// Set a new password after using forgot password form
		//
		elseif( $_POST['action'] == 'reset' ) {
			if( !isset($_POST['newpassword']) || strlen($_POST['newpassword']) < 8 ) {
				$err_msg = 'Your new password must be at least 8 characters long.';
				$display_form = 'reset';
			} elseif( !isset($_POST['email']) || $_POST['email'] == '' ) {
				$err_msg = 'Invalid email address.';
				$display_form = 'reset';
			} elseif( !isset($_POST['temppassword']) || $_POST['temppassword'] == '' ) {
				$err_msg = 'Invalid link.';
				$display_form = 'reset';
			} else {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'changeTempPassword');
				$rc = ciniki_customers_web_changeTempPassword($ciniki, $ciniki['request']['business_id'], 
					$_POST['email'], $_POST['temppassword'], $_POST['newpassword']);
				if( $rc['stat'] != 'ok' ) {
					$err_msg = "Unable to set your new password, please try again.";
					$display_form = 'reset';
				} else {
					$err_msg = "Your password has been set, you may now sign in.";
					$display_form = 'login';
				}
			}
		}
		//
		// Update subscriptions, or password
		//
		elseif( $_POST['action'] == 'accountupdate' 
			&& isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {

			if( isset($modules['ciniki.subscriptions']) ) {
				//
				// Pull in subscription list for user
				//
				ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'list');
				ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'subscribe');
				ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'unsubscribe');
				$rc = ciniki_subscriptions_web_list($ciniki, $settings, $ciniki['request']['business_id']);
				if( $rc['stat'] == 'ok' ) {
					$subscriptions = $rc['subscriptions'];
					foreach($subscriptions as $snum => $subscription) {
						$sid = $subscription['subscription']['id'];
						// Check if the subscribed to the subscription
						if( isset($_POST["subscription-$sid"]) && $_POST["subscription-$sid"] == $sid ) {
							if( $subscription['subscription']['subscribed'] == 'no' ) {
								ciniki_subscriptions_web_subscribe($ciniki, $settings, $ciniki['request']['business_id'], $sid);
								$subscription_err_msg = 'Your subscriptions have been updated.';
							}
						} else {
							if( $subscription['subscription']['subscribed'] == 'yes' ) {
								ciniki_subscriptions_web_unsubscribe($ciniki, $settings, $ciniki['request']['business_id'], $sid);
								$subscription_err_msg = 'Your subscriptions have been updated.';
							}
						}
					}
				}
			}

			//
			// Check if customer wants to change their password
			//
			if( isset($_POST['oldpassword']) && $_POST['oldpassword'] != '' 
				&& isset($_POST['newpassword']) && $_POST['newpassword'] != '' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'changePassword');
				$rc = ciniki_customers_web_changePassword($ciniki, $ciniki['request']['business_id'], 
					$_POST['oldpassword'], $_POST['newpassword']);
				if( $rc['stat'] != 'ok' ) {
					$chgpwd_err_msg = "Unable to set your new password, please try again.";
				} else {
					$chgpwd_err_msg = "Your password has been updated.";
				}
			}
		}
	}

	//
	// Check if user submitted a new password
	//
	if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'newpassword' ) {
		// Require old password, and new password
		$content .= 'set new password';
	}


	//
	// Check if this page was directed to from the recovery password email link
	// The second argument should be the customer uuid
	// The third argument should be the temp_password
	//
	if( (isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'reset' 
		&& isset($_GET['email']) && $_GET['email'] != ''
		&& isset($_GET['pwd']) && $_GET['pwd'] != '' )
		|| $display_form == 'reset'
		) {

		$content .= "<div id='content'>\n"
			. "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>Account</h1></header>\n";
		$content .= "<div class='entry-content'>";
		$content .= "<p>Please enter a new password.  It must be at least 8 characters long.</p>";
		$content .= "<div id='reset-form'>\n"
			. "<form method='POST' action='http://" . $_SERVER['HTTP_HOST'] . $ciniki['request']['base_url'] . "/account'>";
		if( $err_msg != '' ) {
			$content .= "<p class='formerror'>$err_msg</p>\n";
		}
		$content .="<input type='hidden' name='action' value='reset'>\n";
		if( isset($_GET['email']) ) {
			$content .= "<input type='hidden' name='email' value='" . $_GET['email'] . "'>\n";
		} else {
			$content .= "<input type='hidden' name='email' value='" . $_POST['email'] . "'>\n";
		}
		if( isset($_GET['email']) ) {
			$content .= "<input type='hidden' name='temppassword' value='" . $_GET['pwd'] . "'>\n";
		} else {
			$content .= "<input type='hidden' name='temppassword' value='" . $_POST['temppassword'] . "'>\n";
		}
		$content .= "<div class='input'><label for='password'>New Password</label><input id='password' type='password' class='text' maxlength='100' name='newpassword' value='' /></div>\n"
			. "<div class='submit'><input type='submit' class='submit' value='Set Password' /></div>\n"
			. "</form>"
			. "</div>\n"
			. "</div>";
		$content .= "</article>\n"
			. "</div>\n";
		$display_form = 'no';
	}

	//
	// Check if the customer is logged in or not
	//
	elseif( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
		//
		// Get any content for the account page
		//
		require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/core/private/dbDetailsQueryDash.php');
		$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', $ciniki['request']['business_id'], 'ciniki.web', 'content', 'page-account');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		$content_details = array();
		if( isset($rc['content']) ) {
			$content_details = $rc['content'];
		}

		//
		// Start building the html output
		//
		$content .= "<div id='content'>\n"
			. "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>Account</h1></header>\n";
		
		if( isset($content_details['page-account-content']) ) {
			require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/web/private/processContent.php');
			$rc = ciniki_web_processContent($ciniki, $content_details['page-account-content']);	
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$content .= $rc['content'];
		}

		$content .= "<form action='' method='POST'>";
		$content .= "<input type='hidden' name='action' value='accountupdate'/>";

		if( isset($modules['ciniki.subscriptions']) ) {
			//
			// Pull in subscription list
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'list');
			$rc = ciniki_subscriptions_web_list($ciniki, $settings, $ciniki['request']['business_id']);
			if( $rc['stat'] == 'ok' && isset($rc['subscriptions']) ) {
				$subscriptions = $rc['subscriptions'];
				$content .= "<h1 class='entry-title'>Subscriptions</h1>";
				// Check for any content the business provided
				if( isset($content_details['page-account-content-subscriptions']) ) {
					require_once($ciniki['config']['ciniki.core']['modules_dir'] . '/web/private/processContent.php');
					$rc = ciniki_web_processContent($ciniki, $content_details['page-account-content-subscriptions']);	
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$content .= $rc['content'];
				}

				if( $subscription_err_msg != '' ) {
					$content .= "<p class='formerror'>$subscription_err_msg</p>";
				}

				foreach($subscriptions as $snum => $subscription) {
					$sid = $subscription['subscription']['id'];
					$content .= "<input id='subscription-$sid' type='checkbox' class='checkbox' name='subscription-$sid' value='$sid' ";
					if( $subscription['subscription']['subscribed'] == 'yes' ) {
						$content .= " checked";
					}
					$content .= "/>";
					$content .= " <label class='checkbox' for='subscription-$sid'>" . $subscription['subscription']['name'] . "</label><br/>";
				}
				$content .= "<br/><br/>";
			}
		}

		//
		// Allow user to change password
		//
		$content .= "<h1 class='entry-title'>Change Password</h1>"
			. "<p>If you would like to change your password, enter your old password followed by a new one</p>"
			. "";

		if( $chgpwd_err_msg != '' ) {
			$content .= "<p class='formerror'>$chgpwd_err_msg</p>";
		}

		$content .= "<label for='oldpassword'>Old Password:</label><input class='text' id='oldpassword' type='password' name='oldpassword' />";
		$content .= "<label for='newpassword'>New Password:</label><input class='text' id='newpassword' type='password' name='newpassword' />";


		$content .= "<div class='submit'><input type='submit' class='submit' value='Save Changes'></div>\n";
		$content .= "</form>";

		$content .= "<form action='' method='POST'>\n"
			. "<input type='hidden' name='action' value='logout'>\n"
			. "<div class='submit'><input type='submit' class='submit' value='Logout'></div>\n"
			. "</form>"
			. "";

		$content .= "</article>\n"
			. "</div>\n";

		$display_form = 'no';
	}

	//
	// Display login form
	//
	if( $display_form == 'login' || $display_form == 'forgot' ) {
		//
		// Set a session variable, to test for cookies being turned on
		//
		$_SESSION['loginform'] = 'yes';
		$post_email = '';
		if( isset($_POST['email']) ) {
			$post_email = $_POST['email'];
		}
		$ciniki['request']['inline_javascript'] = "<script type='text/javascript'>\n"
			. "	function swapLoginForm(l) {\n"
			. "		if( l == 'forgotpassword' ) {\n"
			. "			document.getElementById('signin-form').style.display = 'none';\n"
			. "			document.getElementById('forgotpassword-form').style.display = 'block';\n"
			. "			document.getElementById('forgotemail').value = document.getElementById('email').value;\n"
			. "		} else {\n"
			. "			document.getElementById('signin-form').style.display = 'block';\n"
			. "			document.getElementById('forgotpassword-form').style.display = 'none';\n"
			. "		}\n"
			. "		return true;\n"
			. "	}\n"
			. "</script>"
			. "";
		$content .= "<div id='content'>\n"
			. "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>Account</h1></header>\n";
		$content .= "<div class='entry-content'>";
		$content .= "<div id='signin-form' style='display:";
		if( $display_form == 'login' ) { $content .= "block;"; } else { $content .= "none;"; }
		$content .= "'>\n"
			. "<form method='POST' action=''>";
		if( $err_msg != '' ) {
			$content .= "<p class='formerror'>$err_msg</p>\n";
		}
		$content .="<input type='hidden' name='action' value='signin'>\n"
			. "<div class='input'><label for='email'>Email</label><input id='email' type='email' class='text' maxlength='250' name='email' value='$post_email' /></div>\n" 
			. "<div class='input'><label for='password'>Password</label><input id='password' type='password' class='text' maxlength='100' name='password' value='' /></div>\n"
			. "<div class='submit'><input type='submit' class='submit' value='Sign In' /></div>\n"
			. "</form>"
			. "<br/>"
			. "<div id='forgot-link'><p><a class='color' href='javascript: swapLoginForm(\"forgotpassword\");'>Forgot your password?</a></p></div>\n"
			. "</div>\n"
			. "<div id='forgotpassword-form' style='display:";
		if( $display_form == 'forgot' ) { $content .= "block;"; } else { $content .= "none;"; }
		$content .= "'>\n"
			. "<p>Please enter your email address and you will receive a link to create a new password.</p>"
			. "<form method='POST' action=''>";
		if( $err_msg != '' ) {
			$content .= "<p class='formerror'>$err_msg</p>\n";
		}
		$content .= "<input type='hidden' name='action' value='forgot'>\n"
			. "<div class='input'><label for='forgotemail'>Email </label><input id='forgotemail' type='email' class='text' maxlength='250' name='email' value='$post_email' /></div>\n" 
			. "<div class='submit'><input type='submit' class='submit' value='Get New Password' /></div>\n"
			. "</form>"
			. "<br/>"
			. "<div id='forgot-link'><p><a class='color' href='javascript: swapLoginForm(\"signin\"); return false;'>Sign In</a></p></div>\n"
			. "</div>\n"
			. "</div>";
		$content .= "</article>\n"
			. "</div>\n";
		// Include forgot password form, and use javascript to swap forms.
	}




	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, 'Account');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$page_content = $rc['content'];
	
	if( $content != '' ) {
		$page_content .= $content;
	}

	//
	// Add the footer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageFooter');
	$rc = ciniki_web_generatePageFooter($ciniki, $settings);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$page_content .= $rc['content'];

	return array('stat'=>'ok', 'content'=>$page_content);
}
?>
