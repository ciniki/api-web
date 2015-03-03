<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageTutorials($ciniki, $settings) {

	$content = '';

	//
	// Check if a file was specified to be downloaded
	//
	$download_err = '';
	if( isset($ciniki['business']['modules']['ciniki.tutorials'])
		&& isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'download'
		&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != ''
		&& isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' 
		&& preg_match("/^(.*)\.pdf$/", $ciniki['request']['uri_split'][2], $matches)
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'web', 'downloadPDF');
		$rc = ciniki_tutorials_web_downloadPDF($ciniki, $settings, $ciniki['request']['business_id'], 
			$matches[1], array('layout'=>$ciniki['request']['uri_split'][1]));
		if( $rc['stat'] == 'ok' ) {
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			$file = $rc['file'];
			if( $file['extension'] == 'pdf' ) {
				header('Content-Type: application/pdf');
			}
			header('Content-Length: ' . strlen($file['binary_content']));
			header('Cache-Control: max-age=0');

			print $file['binary_content'];
			exit;
		}
		
		//
		// If there was an error locating the files, display generic error
		//
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2252', 'msg'=>'The file you requested does not exist.'));
	}

	//
	// Store the content created by the page
	//
	$page_content = '';

	//
	// FIXME: Check if anything has changed, and if not load from cache
	//
		

	$page_title = "Tutorials";
	if( isset($ciniki['business']['modules']['ciniki.tutorials']) ) {
		$pkg = 'ciniki';
		$mod = 'tutorials';
	} else {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2253', 'msg'=>'Page not found.'));
	}

	$tags = array();
	$ciniki['response']['head']['og']['url'] = $ciniki['request']['domain_base_url'] . '/tutorials';
	$base_url = $ciniki['request']['base_url'] . '/tutorials';
	$page_title = 'Tutorials';
	$article_title = 'Tutorials';

	//
	// Generate the list of categories
	//
//	print "<pre>" . print_r($ciniki['business']['modules'], true) . "</pre>";
	if( (!isset($ciniki['request']['uri_split'][0]) || $ciniki['request']['uri_split'][0] == '') // nothing specified
//		&& ($ciniki['business']['modules']['ciniki.tutorials']['flags']&0x02) > 0 // categories on
		) {
		$page_content .= "<article class='page tutorials'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>$article_title</h1></header>\n"
			. "<div class='entry-content'>\n";

		if( isset($settings['page-tutorials-image']) && $settings['page-tutorials-image'] != '' && $settings['page-tutorials-image'] != 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
			$rc = ciniki_web_getScaledImageURL($ciniki, $settings['page-tutorials-image'], 'original', '500', 0);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= "<aside><div class='image-wrap'>"
				. "<div class='image'><img title='' src='" . $rc['url'] . "' /></div>";
			if( isset($settings['page-tutorials-image-caption']) && $settings['page-tutorials-image-caption'] != '' ) {
				$page_content .= "<div class='image-caption'>" . $settings['page-tutorials-image-caption'] . "</div>";
			}
			$page_content .= "</div></aside>";
		}

		$page_content .= "<div class='entry-content'>";
		if( isset($settings['page-tutorials-content']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
			$rc = ciniki_web_processContent($ciniki, $settings['page-tutorials-content']);	
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		}

		//
		// Load the list of tutorials to display
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'web', 'tutorialList');
		$rc = ciniki_tutorials_web_tutorialList($ciniki, $settings, $ciniki['request']['business_id'], array());
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['categories']) ) {
			$page_content .= "<table class='blist'>\n";
			foreach($rc['categories'] as $cname => $category) {
				if( isset($category['list']) ) {
					$page_content .= "<tr><th>"
						. "<span class='blist-category'>" . $category['name'] . "</span></th>"
						. "<td>";
					$page_content .= "<div class='button-list'>";
					$page_content .= "<div class='button-list-wrap'>";
					foreach($category['list'] as $tid => $tutorial ) {
						$page_content .= "<div class='button-list-button'>"
							. "<a href='$base_url/" . $tutorial['permalink'] . "'>"
							. "<span>" . $tutorial['title'] . "</span>"
							. "</a></div>";
					}
					$page_content .= "</div></div>";
					$page_content .= "</td></tr>";
				}
			}
			$page_content .= "</table>";
		} else {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2254', 'msg'=>"I'm sorry, but there are no tutorials available."));
		}
		$page_content .= "</div></article>\n";	
	}

	//
	// Get the list of tutorials for every category, or a specific category
	//
/*	elseif( 
		((!isset($ciniki['request']['uri_split'][0]) || $ciniki['request']['uri_split'][0] == '') // Nothing specified
			&& ($ciniki['business']['modules']['ciniki.tutorials']['flags']&0x02) == 0  // categories off
		)	
		|| (isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != 'category' // specific category
			&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != ''  // category specified
			&& ($ciniki['business']['modules']['ciniki.tutorials']['flags']&0x02) > 0 // categories on
			)
		) {
		
		if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' ) { // category specified
			$category_permalink = $ciniki['request']['uri_split'][1];
		}
		//
		// Load the list of tutorials to display
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'web', 'tutorialDetails');
		$rc = ciniki_tutorials_web_tutorialList($ciniki, $settings, $ciniki['request']['business_id'], $category_permalink);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['categories']) ) {
			
		} else {
			$tutorials = $rc['tutorials'];
		}
		
	}*/
	elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' 
		) {
		$tutorial_permalink = $ciniki['request']['uri_split'][0];

		//
		// Load the tutorial to get all the details, and the list of images.
		// It's one query, and we can find the requested image, and figure out next
		// and prev from the list of images returned
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'tutorials', 'web', 'tutorialDetails');
		$rc = ciniki_tutorials_web_tutorialDetails($ciniki, $settings, 
			$ciniki['request']['business_id'], $tutorial_permalink);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$tutorial = $rc['tutorial'];

		//
		// Setup social sharing info
		//
		$ciniki['response']['head']['og']['url'] .= '/' . $tutorial_permalink;
		$ciniki['response']['head']['og']['description'] = strip_tags($tutorial['content']);
		$base_url = $ciniki['request']['base_url'] . '/tutorials/' . $tutorial_permalink;

		//
		// Display step
		//
		if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'step' 
			&& isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' 
			&& isset($tutorial['steps']) 
			) {
			$step_permalink = $ciniki['request']['uri_split'][2];
			$first = NULL;
			$last = NULL;
			$cur_step = NULL;
			$prev = NULL;
			$next = NULL;
			$num_steps = 0;
			$tutorial['list'] = array();
			foreach($tutorial['steps'] as $sid => $step) {
				$num_steps++;
				$step['number'] = $num_steps;
				$step['permalink'] = $base_url . '/step/' . $num_steps;
				$step['title'] = 'Step ' . $num_steps . ' - ' . $step['title'];
				$tutorial['list'][] = array('name'=>'Step ' . $num_steps, 'list'=>array($step));
				if( $first == NULL ) {
					$first = $step;
				}
				if( $step['number'] == $step_permalink ) {
					$cur_step = $step;
				} elseif( $next == NULL && $cur_step != NULL ) {
					$next = $step;
				} elseif( $cur_step == NULL ) {
					$prev = $step;
				}
				$last = $step;
			}
			if( !isset($tutorial['steps']) || count($tutorial['steps']) < 1 ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2246', 'msg'=>'Unable to find step'));
			}
			if( count($tutorial['steps']) == 1 ) {
				$prev = NULL;
				$next = NULL;
			}
			$page_title = $tutorial['title'];
			$article_title = "<a href='$base_url'>" . $tutorial['title'] . '</a>'; //: Step ' . $cur_step['number'] . ' - ' . $cur_step['title'];

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
				. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$article_title</h1></header>\n"
				. "<div class='entry-content'>\n"
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
			if( isset($cur_step['description']) && $cur_step['description'] != '' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
				$rc = ciniki_web_processContent($ciniki, $cur_step['description']);	
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$step_description = $rc['content'];
			}

			//
			// Load the image
			//
			if( isset($cur_step['image_id']) && $cur_step['image_id'] > 0 ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
				$rc = ciniki_web_getScaledImageURL($ciniki, $cur_step['image_id'], 'original', 0, 800, 80);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$img_url = $rc['url'];
				$ciniki['response']['head']['og']['image'] = $rc['domain_url'];
				$page_content .= "<div id='gallery-image' class='gallery-image'>";
				$page_content .= "<div id='gallery-image-details' class='gallery-image-details gallery-image-details-top'>"
					. "<span class='image-title'>" . $cur_step['title'] . '</span>';
//					. "<span class='image-details'></span>";
				if( isset($step_description) && $step_description != '' ) {
					$page_content .= "<span class='image-description'>$step_description</span>";
				}
				$page_content .= "</div>";
				$page_content .= "<div id='gallery-image-wrap' class='gallery-image-wrap'>";
				if( $prev != null ) {
					$page_content .= "<a id='gallery-image-prev' class='gallery-image-prev' href='" . $prev['permalink'] . "'><div id='gallery-image-prev-img'></div></a>";
				}
				if( $next != null ) {
					$page_content .= "<a id='gallery-image-next' class='gallery-image-next' href='" . $next['permalink'] . "'><div id='gallery-image-next-img'></div></a>";
				}
				$page_content .= "<img id='gallery-image-img' title='" . $cur_step['title'] . "' alt='" . $cur_step['title'] . "' src='" . $img_url . "' onload='javascript: gallery_resize_arrows();' />";
				$page_content .= "</div><br/>";
//				$page_content .= "<div id='gallery-image-details' class='gallery-image-details'>"
//					. "<span class='image-title'>" . $cur_step['title'] . '</span>'
//					. "<span class='image-details'></span>";
//				if( isset($cur_step['content']) && $cur_step['content'] != '' ) {
//					$page_content .= "<span class='image-description'>$step_description</span>";
//				}
//				$page_content .= "</div>";
				$page_content .= "</div>";
			} else {
				$page_content .= "<p>$step_description</p>";
			}
			$page_content .= "</div></article>";
		}
		//
		// Display Tutorial
		//
		else {
			$tutorial['list'] = array();
			if( isset($tutorial['steps']) ) {
				$num_steps = 0;
				foreach($tutorial['steps'] as $sid => $step) {
					$num_steps++;
					$step['number'] = $num_steps;
					$step['permalink'] = 'step/' . $num_steps;
					$step['is_details'] = 'yes';
					$tutorial['list'][] = array('name'=>'Step ' . $num_steps, 'list'=>array($step));
				}
			}
			$page_title = $tutorial['title'];
			$page_content .= "<article class='page'>\n"
				. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
				. "<div class='entry-content'>\n"
				. "";
			if( isset($tutorial['primary_image_id']) && $tutorial['primary_image_id'] != '' && $tutorial['primary_image_id'] != 0 ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
				$rc = ciniki_web_getScaledImageURL($ciniki, $tutorial['primary_image_id'], 'original', '500', 0);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= "<aside><div class='image-wrap'>"
					. "<div class='image'><img title='' src='" . $rc['url'] . "' /></div>";
				if( isset($tutorial['image_caption']) && $tutorial['image_caption'] != '' ) {
					$page_content .= "<div class='image-caption'>" . $tutorial['image_caption'] . "</div>";
				}
				$page_content .= "</div></aside>";
			}

			if( isset($tutorial['content']) ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
				$rc = ciniki_web_processContent($ciniki, $tutorial['content']);	
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];

				$page_content .= "<p><b>Download Options</b><br/>"
					. "<a target='_blank' href='" . $ciniki['request']['base_url'] . "/tutorials/download/single/" . $tutorial['permalink'] . ".pdf'>Printable PDF (1 step/page, large images)</a><br/>"
					. "<a target='_blank' href='" . $ciniki['request']['base_url'] . "/tutorials/download/double/" . $tutorial['permalink'] . ".pdf'>Printable PDF (2 steps/page, smaller images)</a><br/>";
			}

//	print "<pre>" . print_r($tutorial, true) . "</pre>";
			if( isset($tutorial['steps']) ) {
				//
				// Display the list of children
				//
				if( isset($tutorial['steps']) && count($tutorial['steps']) > 0 ) {
					$page_content .= "<br style='clear:both;'/>";
					$page_content .= "<h2>Steps</h2>";
					if( count($tutorial['steps']) > 0 ) {
						ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
						$list_args = array();
						$rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $tutorial['list'], $list_args);
						if( $rc['stat'] != 'ok' ) {
							return $rc;
						}
						$page_content .= $rc['content'];
					} else {
						$page_content .= "";
					}
				}
				
			}

			$page_content .= "</div></article>";
		}

	}
	
	//
	// Error nothing specified
	//
	else {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2255', 'msg'=>"I'm sorry, but there are no tutorials available."));
	}

	//
	// The submenu 
	//
	$submenu = array();

	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title, $submenu);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	//
	// Build the page content
	//
	$content .= "<div id='content'>\n";

	if( $page_content != '' ) {
		$content .= $page_content;
	}

	$content .= "</div>";

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
