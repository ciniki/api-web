<?php
//
// Description
// -----------
// This function will generate the exhibitors page for the business.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageExhibitors($ciniki, $settings) {

	//
	// Store the content created by the page
	// Make sure everything gets generated ok before returning the content
	//
	$content = '';
	$page_content = '';
	$page_title = 'Exhibitors';

	//
	// FIXME: Check if anything has changed, and if not load from cache
	//

	//
	// Check if we are to display the gallery image for an exhibitor
	//
	//
	// Check if we are to display an image, from the gallery, or latest images
	//
	if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' 
		&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'gallery' 
		&& isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' 
		) {
		$exhibitor_permalink = $ciniki['request']['uri_split'][0];
		$image_permalink = $ciniki['request']['uri_split'][2];

		//
		// Load the participant to get all the details, and the list of images.
		// It's one query, and we can find the requested image, and figure out next
		// and prev from the list of images returned
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'exhibitions', 'web', 'participantDetails');
		$rc = ciniki_exhibitions_web_participantDetails($ciniki, $settings, 
			$ciniki['request']['business_id'], 
			$settings['page-exhibitions-exhibition'], $exhibitor_permalink);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$participant = $rc['participant'];

		if( !isset($participant['images']) || count($participant['images']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'490', 'msg'=>'Unable to find image'));
		}

		$first = NULL;
		$last = NULL;
		$img = NULL;
		$next = NULL;
		$prev = NULL;
		foreach($participant['images'] as $iid => $image) {
			if( $first == NULL ) {
				$first = $image;
			}
			if( $image['permalink'] == $image_permalink ) {
				$img = $image;
			} elseif( $next == NULL && $img != NULL ) {
				$next = $image;
			} elseif( $img == NULL ) {
				$prev = $image;
			}
			$last = $image;
		}

		if( count($participant['images']) == 1 ) {
			$prev = NULL;
			$next = NULL;
		} elseif( $prev == NULL ) {
			// The requested image was the first in the list, set previous to last
			$prev = $last;
		} elseif( $next == NULL ) {
			// The requested image was the last in the list, set previous to last
			$next = $first;
		}
		
		$page_title = $participant['name'] . ' - ' . $img['title'];
	
		//
		// Load the image
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
		$rc = ciniki_web_getScaledImageURL($ciniki, $img['image_id'], 'original', 0, 600);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$img_url = $rc['url'];

		//
		// Set the page to wide if possible
		//
		$ciniki['request']['page-container-class'] = 'page-container-wide';

		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generateGalleryJavascript');
		$rc = ciniki_web_generateGalleryJavascript($ciniki, $next, $prev);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$ciniki['request']['inline_javascript'] = $rc['javascript'];

		$ciniki['request']['onresize'] = "gallery_resize_arrows();";
		$ciniki['request']['onload'] = "scrollto_header();";
		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";
		$page_content .= "<div id='gallery-image' class='gallery-image'>";
		$page_content .= "<div id='gallery-image-wrap' class='gallery-image-wrap'>";
		if( $prev != null ) {
			$page_content .= "<a id='gallery-image-prev' class='gallery-image-prev' href='" . $prev['permalink'] . "'><div id='gallery-image-prev-img'></div></a>";
		}
		if( $next != null ) {
			$page_content .= "<a id='gallery-image-next' class='gallery-image-next' href='" . $next['permalink'] . "'><div id='gallery-image-next-img'></div></a>";
		}
		$page_content .= "<img id='gallery-image-img' title='" . $img['title'] . "' alt='" . $img['title'] . "' src='" . $img_url . "' onload='javascript: gallery_resize_arrows();' />";
		$page_content .= "</div><br/>"
			. "<div id='gallery-image-details' class='gallery-image-details'>"
			. "<span class='image-title'>" . $img['title'] . '</span>'
			. "<span class='image-details'></span>";
		if( $img['description'] != '' ) {
			$page_content .= "<span class='image-description'>" . preg_replace('/\n/', '<br/>', $img['description']) . "</span>";
		}
		$page_content .= "</div></div>";
		$page_content .= "</div></article>";
	}

	//
	// Check if we are to display an exhibitor
	//
	elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'exhibitions', 'web', 'participantDetails');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

		//
		// Get the exhibitor information
		//
		$exhibitor_permalink = $ciniki['request']['uri_split'][0];
		$rc = ciniki_exhibitions_web_participantDetails($ciniki, $settings, 
			$ciniki['request']['business_id'], 
			$settings['page-exhibitions-exhibition'], $exhibitor_permalink);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$participant = $rc['participant'];
		$page_title = $participant['name'];
		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>" . $participant['name'] . "</h1></header>\n"
			. "";

		//
		// Add primary image
		//
		if( isset($participant['image_id']) && $participant['image_id'] > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
			$rc = ciniki_web_getScaledImageURL($ciniki, $participant['image_id'], 'original', '500', 0);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= "<aside><div class='image'>"
				. "<img title='' alt='" . $participant['name'] . "' src='" . $rc['url'] . "' />"
				. "</div></aside>";
		}
		
		//
		// Add description
		//
		if( isset($participant['description']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
			$rc = ciniki_web_processContent($ciniki, $participant['description']);	
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		}

		if( isset($participant['url']) ) {
			$rc = ciniki_web_processURL($ciniki, $participant['url']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$url = $rc['url'];
			$display_url = $rc['display'];
		} else {
			$url = '';
		}

		if( $url != '' ) {
			$page_content .= "<br/>Website: <a class='exhibitors-url' target='_blank' href='" . $url . "' title='" . $participant['name'] . "'>" . $display_url . "</a>";
		}
		$page_content .= "</article>";

		if( isset($participant['images']) && count($participant['images']) > 0 ) {
			$page_content .= "<article class='page'>"	
				. "<header class='entry-title'><h1 class='entry-title'>Gallery</h1></header>\n"
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
			$img_base_url = $ciniki['request']['base_url'] . "/exhibitors/" . $participant['permalink'] . "/gallery";
			$rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $participant['images'], 125);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
			$page_content .= "</article>";
		}
	}

	//
	// Display the list of exhibitors if a specific one isn't selected
	//
	else {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'exhibitions', 'web', 'participantList');
		$rc = ciniki_exhibitions_web_participantList($ciniki, $settings, $ciniki['request']['business_id'], $settings['page-exhibitions-exhibition'], 'exhibitor');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$participants = $rc['categories'];

		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>Exhibitors</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";

		if( count($participants) > 0 ) {
			$page_content .= "<table class='exhibitors-list'><tbody>\n"
				. "";
			$prev_category = NULL;
			foreach($participants as $cnum => $c) {
				if( $prev_category != NULL ) {
					$page_content .= "</td></tr>\n";
				}
				if( isset($c['category']['name']) && $c['category']['name'] != '' ) {
					$page_content .= "<tr><th>"
						. "<span class='exhibitors-category'>" . $c['category']['name'] . "</span></th>"
						. "<td>";
				} else {
					$page_content .= "<tr><th>"
						. "<span class='exhibitors-category'></span></th>"
						. "<td>";
				}
				$page_content .= "<table class='exhibitors-category-list'><tbody>\n";
				foreach($c['category']['participants'] as $pnum => $participant) {
					$participant = $participant['participant'];
					$participant_url = $ciniki['request']['base_url'] . "/exhibitors/" . $participant['permalink'];

					// Setup the exhibitor image
					$page_content .= "<tr><td class='exhibitors-image' rowspan='3'>";
					if( isset($participant['image_id']) && $participant['image_id'] > 0 ) {
						ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
						$rc = ciniki_web_getScaledImageURL($ciniki, $participant['image_id'], 'thumbnail', '150', 0);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
						$page_content .= "<div class='image-exhibitors-thumbnail'>"
							. "<a href='$participant_url' title='" . $participant['name'] . "'><img title='' alt='" . $participant['name'] . "' src='" . $rc['url'] . "' /></a>"
							. "</div></aside>";
					}
					$page_content .= "</td>";

					// Setup the details
					$page_content .= "<td class='exhibitors-details'>";
					$page_content .= "<span class='exhibitors-title'>";
					$page_content .= "<a href='$participant_url' title='" . $participant['name'] . "'>" . $participant['name'] . "</a>";
					$page_content .= "</span>";
					$page_content .= "</td></tr>";
					$page_content .= "<tr><td class='exhibitors-description'>";
					if( isset($participant['description']) && $participant['description'] != '' ) {
						$page_content .= "<span class='exhibitors-description'>" . $participant['description'] . "</span>";
					}
					$page_content .= "</td></tr>";
					$page_content .= "<tr><td class='exhibitors-more'><a href='$participant_url'>... more</a></td></tr>";
				}
				$page_content .= "</tbody></table>";
			}

			$page_content .= "</td></tr>\n</tbody></table>\n";
		} else {
			$page_content .= "<p>Currently no exhibitors for this event.</p>";
		}

		$page_content .= "</div>\n"
			. "</article>\n"
			. "";
	}

	//
	// Generate the complete page
	//

	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	$content .= "<div id='content'>\n"
		. $page_content
		. "</div>"
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