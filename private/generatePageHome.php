<?php
//
// Description
// -----------
// This function will generate the home page for the website.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageHome($ciniki, $settings) {

	//
	// Store the content created by the page
	// Make sure everything gets generated ok before returning the content
	//
	$content = '';
	$page_content = '';

	//
	// FIXME: Check if anything has changed, and if not load from cache
	//
	

	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, 'Home');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	if( isset($settings['page-home-image']) && $settings['page-home-image'] != '' && $settings['page-home-image'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
		$rc = ciniki_web_getScaledImageURL($ciniki, $settings['page-home-image'], 'original', '500', 0);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$page_content .= "<aside><div class='image'><img title='' alt='" . $ciniki['business']['details']['name'] . "' src='" . $rc['url'] . "' /></div></aside>";
	}

	//
	// Generate the content of the page
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', $ciniki['request']['business_id'], 'ciniki.web', 'content', 'page-home');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( isset($rc['content']['page-home-content']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
		$rc = ciniki_web_processContent($ciniki, $rc['content']['page-home-content']);	
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$page_content .= $rc['content'];
	}

	$content .= "<div id='content'>\n"
		. "";
	if( $page_content != '' ) {
		$content .= "<article class='page'>\n"
			. "<div class='entry-content'>\n"
			. $page_content
			. "</div>"
			. "</article>"
			. "";
	}

	//
	// List the latest work
	//
	if( isset($ciniki['business']['modules']['ciniki.artcatalog']) 
		&& $settings['page-gallery-active'] == 'yes' ) {

		ciniki_core_loadMethod($ciniki, 'ciniki', 'artcatalog', 'web', 'latestImages');
		$rc = ciniki_artcatalog_web_latestImages($ciniki, $settings, $ciniki['request']['business_id'], 6);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$images = $rc['images'];

		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
		$img_base_url = $ciniki['request']['base_url'] . "/gallery/latest";
		$rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $rc['images'], 150);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>Latest Work</h1></header>\n"
			. "<div class='image-gallery'>" . $rc['content'] . "</div>"
			. "</article>\n"
			. "";
	}

	//
	// List any upcoming events
	//
	if( isset($ciniki['business']['modules']['ciniki.events']) 
		&& $settings['page-events-active'] == 'yes' ) {
		//
		// Load and parse the events
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'web', 'list');
		$rc = ciniki_events_web_list($ciniki, $ciniki['request']['business_id'], 'upcoming', 3);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$number_of_events = count($rc['events']);
		if( isset($rc['events']) && $number_of_events > 0 ) {
			$events = $rc['events'];
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processEvents');
			$rc = ciniki_web_processEvents($ciniki, $settings, $events, 2);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$content .= "<article class='page'>\n"
				. "<header class='entry-title'><h1 class='entry-title'>Upcoming Events</h1></header>\n"
				. $rc['content']
				. "";
			if( $number_of_events > 2 ) {
				$content .= "<div class='events-more'><a href='" . $ciniki['request']['base_url'] . "/events'>... more events</a></div>";
			}
			$content .= "</article>\n"
				. "";
		}
	}

	$content .= "</div>"
		. "";

	//
	// Add the footer
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageFooter');
	$rc = ciniki_web_generatePageFooter($ciniki, $settings);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	return array('stat'=>'ok', 'content'=>$content);
}
?>
