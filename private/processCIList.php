<?php
//
// Description
// -----------
// This function will process a list of events, and format the html.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
// events:			The array of events as returned by ciniki_events_web_list.
// limit:			The number of events to show.  Only 2 events are shown on the homepage.
//
// Returns
// -------
//
function ciniki_web_processCIList(&$ciniki, $settings, $base_url, $categories, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');

	$page_limit = 0;
	if( isset($args['limit']) ) {
		$page_limit = $args['limit'];
	}

	$content = "<table class='cilist'><tbody>";
	$count = 0;
	foreach($categories as $cid => $category) {
		if( $page_limit > 0 && $count >= $page_limit ) { $count++; break; }
		// If no titles, then highlight the title in the category
		if( isset($args['notitle']) && $args['notitle'] == 'yes' ) {
			$title_url = '';
			if( count($category['list']) == 1 ) {
				// Check if category should be linked
				$item = array_slice($category['list'], 0, 1);
				$item = $item['0'];
				if( isset($item['is_details']) && $item['is_details'] == 'yes' ) {
					$title_url = $base_url . '/' . $item['permalink'];
				}
			}
			$content .= "\n<tr><th><span class='cilist-title'>" 
				. ($title_url!=''?"<a href='$title_url' title='" . $item['title'] . "'>":'')
				. (isset($category['name'])?$category['name']:'') 
				. ($title_url!=''?'</a>':'')
				. "</span></th><td>\n";
		} else {
			$content .= "\n<tr><th><span class='cilist-category'>" . (isset($category['name'])?$category['name']:'') . "</span></th><td>\n";
		}
		$content .= "<table class='cilist-categories'><tbody>\n";

		foreach($category['list'] as $iid => $item) {
			if( $page_limit > 0 && $count >= $page_limit ) { $count++; break; }
			$url = '';
			$url_display = '... more';
			$javascript_onclick = '';
			if( isset($item['is_details']) && $item['is_details'] == 'yes' 
				&& isset($item['permalink']) && $item['permalink'] != '' ) {
				$url = $base_url . "/" . $item['permalink'];
				$javascript_onclick = " onclick='javascript:location.href=\"$url\";' ";
			} elseif( isset($item['url']) && $item['url'] != '' ) {
				$rc = ciniki_web_processURL($ciniki, $item['url']);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$url = $rc['url'];
				$url_display = $rc['display'];
			}

			// Setup the item image
			if( isset($args['notitle']) && $args['notitle'] == 'yes' ) {
				$content .= "<tr><td class='cilist-image' rowspan='2'>";
			} else {
				$content .= "<tr><td class='cilist-image' rowspan='3'>";
			}
			if( isset($item['image_id']) && $item['image_id'] > 0 ) {
				$rc = ciniki_web_getScaledImageURL($ciniki, $item['image_id'], 'thumbnail', '150', 0);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( $url != '' ) {
					$content .= "<div class='image-cilist-thumbnail'>"
						. "<a href='$url' title='" . $item['title'] . "'>"
						. "<img title='' alt='" . $item['title'] . "' src='" . $rc['url'] . "' /></a>"
						. "</div>";
				} else {
					$content .= "<div class='image-cilist-thumbnail'>"
						. "<img title='' alt='" . $item['title'] . "' src='" . $rc['url'] . "' />"
						. "</div>";
				}
			} elseif( isset($category['noimage']) && $category['noimage'] != '' ) {
				if( $url != '' ) {
					$content .= "<div class='image-cilist-thumbnail'>"
						. "<a href='$url' title='" . $item['title'] . "'>"
						. "<img title='' alt='" . $item['title'] . "' src='" . $category['noimage'] . "' /></a>"
						. "</div></aside>";
				} else {
					$content .= "<div class='image-cilist-thumbnail'>"
						. "<img title='' alt='" . $item['title'] . "' src='" . $category['noimage'] . "' />"
						. "</div></aside>";
				}
			}
			$content .= "</td>";
			
			// Setup the details
			if( isset($args['notitle']) && $args['notitle'] == 'yes' ) {
				$content .= "";
			} else {
				$content .= "<td class='cilist-title'>";
				$content .= "<p class='cilist-title'>";
				if( $url != '' ) {
					$content .= "<a href='$url' title='" . $item['title'] . "'>" . $item['title'] . "</a>";
				} else {
					$content .= $item['title'];
				}
				$content .= "</p>";
				$content .= "</td></tr>";
				$content .= "<tr>";
			}
			$content .= "<td $javascript_onclick class='cilist-details'>";

			if( isset($item['description']) && $item['description'] != '' ) {
				$rc = ciniki_web_processContent($ciniki, $item['description'], 'cilist-description');
				if( $rc['stat'] == 'ok' ) {
					$content .= $rc['content'];
				}
			} elseif( isset($item['short_description']) && $item['short_description'] != '' ) {
				$rc = ciniki_web_processContent($ciniki, $item['short_description'], 'cilist-description');
				if( $rc['stat'] == 'ok' ) {
					$content .= $rc['content'];
				}
			} else {
				$content .= "<br/>";
			}
			$content .= "</tr>";
		
			if( $url != '' ) {
				$content .= "<tr><td class='cilist-more'><a href='$url'>$url_display</a></td></tr>";
			} elseif( isset($item['urls']) && count($item['urls']) > 0 ) {
				$content .= "<tr><td class='cilist-more'>";
				$urls = '';
				foreach($item['urls'] as $url) {
					$rc = ciniki_web_processURL($ciniki, $url);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					if( $rc['url'] != '' ) {
						$urls .= ($urls!='')?'<br/>':'';
						if( isset($url['title']) && $url['title'] != '' ) {
							$urls .= "<a href='" . $rc['url'] . "' target='_blank'>" . $url['title'] . "</a>";
						} else {
							$urls .= "<a href='" . $rc['url'] . "' target='_blank'>" . $rc['display'] . "</a>";
						}
					}
					$url = $rc['url'];
					$url_display = $rc['display'];
				}
				$content .= $urls . "</td></tr>";
			} else {
				$content .= "<tr><td class='cilist-more'></td></tr>";
				
			}
			$count++;
		}
		$content .= "</tbody></table>";
		$content .= "</td></tr>";
	}
	$content .= "</tbody></table>\n";

	//
	// Check if we need prev and next buttons
	//
	$nav_content = '';
	if( $page_limit > 0 && isset($args['base_url']) && $args['base_url'] != '' ) {
		$prev = '';
		if( isset($args['page']) && $args['page'] > 1 ) {
			if( isset($args['base_url']) ) {
				$prev .= "<a href='" . $args['base_url'] . "?page=" . ($args['page']-1) . "'>";
				array_push($ciniki['response']['head']['links'], array('rel'=>'prev', 'href'=>$args['base_url'] . "?page=" . ($args['page']-1)));
				if( isset($args['prev']) && $args['prev'] != '' ) {
					$prev .= $args['prev'];
				} else {
					$prev .= 'Prev';
				}
				$prev .= "</a>";
			}
		}
		$next = '';
		if( $count > $page_limit ) {
			if( isset($args['base_url']) ) {
				$next .= "<a href='" . $args['base_url'] . "?page=" . ($args['page']+1) . "'>";
				array_push($ciniki['response']['head']['links'], array('rel'=>'next', 'href'=>$args['base_url'] . "?page=" . ($args['page']+1)));
				if( isset($args['prev']) && $args['prev'] != '' ) {
					$next .= $args['next'];
				} else {
					$next .= 'Next';
				}
				$next .= "</a>";
			}
		}
		if( $next != '' || $prev != '' ) {
			$nav_content = "<nav class='content-nav'>"
				. "<span class='prev'>$prev</span>"
				. "<span class='next'>$next</span>"
				. "</nav>"
				. "";
		}
	}

	return array('stat'=>'ok', 'content'=>$content, 'nav'=>$nav_content);
}
?>

