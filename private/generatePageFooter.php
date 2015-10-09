<?php
//
// Description
// -----------
// This function will generate the footer to be displayed at the bottom
// of every web page.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageFooter($ciniki, $settings) {
	global $start_time;

	//
	// Store the content
	//
	$content = '';

	// Generate the footer content
	$content .= "<hr class='section-divider footer-section-divider' />\n";
	$content .= "<footer>";
	$content .= "<div class='footer-wrapper'>";

	//
	// Check for social media icons
	//
	$social = '';
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'socialIcons');
	$rc = ciniki_web_socialIcons($ciniki, $settings, 'footer');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['social']) && $rc['social'] != '' ) {
		$social = $rc['social'];
	}

	//
	// Check for copyright information
	//
	$copyright = '';
	if( isset($settings['theme']['footer-copyright-message']) && $settings['theme']['footer-copyright-message'] != '' ) {
		$copyright .= "<span class='copyright'>" . $settings['theme']['footer-copyright-message'] . "</span><br/>";
	} else {
		$copyright .= "<span class='copyright'>All content &copy; Copyright " . date('Y') . " by " . ((isset($settings['site-footer-copyright-name']) && $settings['site-footer-copyright-name'] != '')?$settings['site-footer-copyright-name']:$ciniki['business']['details']['name']) . ".</span><br/>";
	}
	if( isset($settings['site-footer-copyright-message']) && $settings['site-footer-copyright-message'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
		$rc = ciniki_web_processContent($ciniki, $settings['site-footer-copyright-message'], 'copyright');	
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$copyright .= $rc['content'];
	}

	//
	// Check for theme copyrights
	//
	if( file_exists($ciniki['request']['theme_dir'] . '/' . $settings['site-theme'] . '/copyright.html') ) {
		$copyright .= "<span class='copyright'>" . file_get_contents($ciniki['request']['theme_dir'] . '/' . $settings['site-theme'] . '/copyright.html') . "</span><br/>";
	}

	if( isset($ciniki['config']['ciniki.web']['poweredby.url']) && $ciniki['config']['ciniki.web']['poweredby.url'] != '' && $ciniki['config']['ciniki.core']['master_business_id'] != $ciniki['request']['business_id'] ) {
		$copyright .= "<span class='poweredby'>Powered by <a href='" . $ciniki['config']['ciniki.web']['poweredby.url'] . "'>" . $ciniki['config']['ciniki.web']['poweredby.name'] . "</a></span>";
	}

	//
	// Check if any links should be added to the footer
	//
	$links = '';
	if( isset($settings['site-footer-subscription-agreement']) && $settings['site-footer-subscription-agreement'] == 'yes' ) {
		$links .= "<a href='/'>Subscription Agreement</a>";
	}
	if( isset($settings['site-footer-privacy-policy']) && $settings['site-footer-privacy-policy'] == 'yes' ) {
		$links .= ($links!=''?' | ':'') . "<a href='/'>Privacy Policy</a>";
	}

	//
	// Decide how the footer should be laid out
	//
	if( isset($settings['theme']['footer-layout']) && $settings['theme']['footer-layout'] == 'copyright-links-social' ) {
		$content .= "<div class='copyright'>" . $copyright . "</div>";

		if( $links != '' ) {
			$content .= "<div class='links'>" . $links . "</div>";
		}

		if( $social != '' ) {
			$content .= "<div class='social-icons'>" . $social . "</div>";
		}

	} else {
		if( $social != '' ) {
			$content .= "<div class='social-icons'>" . $social . "</div>";
		}

		if( $links != '' ) {
			$content .= "<div class='links'>" . $links . "</div>";
		}

		$content .= "<div class='copyright'>";
		$content .= $copyright;
		$content .= "</div>";
	}

	//
	// Extra information for the bottom of the page, error messages, debug info, etc
	//
	$content .= "<div class='x-info'>";
	// If there was an error page generated, see if we should put the error code in the footer for debug purposes.
	// This keeps it out of the way, but easy to tell people what to look for.
	if( isset($ciniki['request']['error_codes_msg']) && $ciniki['request']['error_codes_msg'] != '' ) {
		$content .= "<br/><span class='error_msg'>" . $ciniki['request']['error_codes_msg'] . "</span>";
	}
	$content .= "<span class='x-stats' style='display:none;'>Execution: " . sprintf("%.4f", ((microtime(true)-$start_time)/60)) . "seconds</span>";
	$content .= "</div>";
	$content .= "</div>";

	$content .= "</footer>"
		. "";

	// Close page-container
	$content .= "</div>\n";

	$content .= "</body>"
		. "</html>"
		. "";

	return array('stat'=>'ok', 'content'=>$content);
}
?>
