<?php
//
// Description
// -----------
// This function will generate the dealers page for the business.
//
// The dealer page can be referenced multiple ways depending on how th user arrives at the page.
// /dealers/dealer-permalink
// /dealers/location/country/province/state/dealer-permalink
// /dealers/category/cat-permalink/dealer-permalink
// /dealers/search/string/dealer-permalink
// 
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageDealers($ciniki, $settings) {

	//
	// Check if a file was specified to be downloaded
	//
	$download_err = '';
	if( isset($ciniki['business']['modules']['ciniki.info'])
		&& isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'download'
		&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'info', 'web', 'fileDownload');
		$rc = ciniki_info_web_fileDownload($ciniki, $ciniki['request']['business_id'], $ciniki['request']['uri_split'][1]);
		if( $rc['stat'] == 'ok' ) {
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			$file = $rc['file'];
			if( $file['extension'] == 'pdf' ) {
				header('Content-Type: application/pdf');
			}
			header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
			header('Content-Length: ' . strlen($file['binary_content']));
			header('Cache-Control: max-age=0');

			print $file['binary_content'];
			exit;
		}
		
		//
		// If there was an error locating the files, display generic error
		//
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1759', 'msg'=>'We\'re sorry, but the file you requested does not exist.'));
	}
	//
	// Store the content created by the page
	// Make sure everything gets generated ok before returning the content
	//
	$content = '';
	$page_content = '';
	$page_title = 'Dealers';
	$article_title = 'Dealers';
	if( isset($settings['page-dealers-name']) && $settings['page-dealers-name'] != '' ) {
		$page_title = $settings['page-dealers-name'];
	}
	$base_url = $ciniki['request']['base_url'] . '/dealers';
	$display_categories = 'no';
	$display_category = 'no';
	$display_locations = 'no';
	$display_location = 'no';
	$display_map = 'no';
	$display_list = 'no';
	$display_profile = 'no';
	$maps = array();
	if( isset($settings['page-dealers-locations-map-names'])
		&& $settings['page-dealers-locations-map-names'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'locationNameMaps');
		$rc = ciniki_web_locationNameMaps($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$maps = $rc['maps'];
	}

	//
	// Check if anything has been updated in ciniki.customers and update the map data file
	//
	$last_change = $ciniki['business']['modules']['ciniki.customers']['last_change'];

	//
	// Check if anything has changed, and if not load from cache
	//
	$cache_file = '';
	$cache_update = 'yes';
	if( isset($ciniki['business']['cache_dir']) && $ciniki['business']['cache_dir'] != '' 
		&& (!isset($ciniki['config']['ciniki.web']['cache']) 
			|| $ciniki['config']['ciniki.web']['cache'] != 'off') ) {
		$cache_file = $ciniki['business']['cache_dir'] . '/ciniki.web/dealers/';
		$depth = 1;
		foreach($ciniki['request']['uri_split'] as $uri_index => $uri_piece) {
			if( $uri_index < $depth ) {
				$cache_file .= $uri_piece . '/';
			} elseif( $uri_index == $depth ) {
				$cache_file .= $uri_piece;
			} else {
				$cache_file .= '_' . $uri_piece;
			}
		}
		if( substr($cache_file, -1) == '/' ) {
			$cache_file .= '_index';
		}
		// Check if no changes have been made since last cache file write
		if( file_exists($cache_file) && filemtime($cache_file) > $last_change ) {
			$page_content = file_get_contents($cache_file);
			$cache_update = 'no';
			// Add the header
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
			$rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title, array());
			if( $rc['stat'] != 'ok' ) {	
				return $rc;
			}
			$content .= $rc['content'];

			$content .= "<div id='content'>\n"
				. $page_content
				. "<br style='clear:both;' />\n"
				. "</div>"
				. "";

			// Add the footer
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageFooter');
			$rc = ciniki_web_generatePageFooter($ciniki, $settings);
			if( $rc['stat'] != 'ok' ) {	
				return $rc;
			}
			$content .= $rc['content'];

			return array('stat'=>'ok', 'content'=>$content);
		}
	}

	//
	// Generate the map data.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealersMapMarkers');
	$rc = ciniki_customers_web_dealersMapMarkers($ciniki, $settings, $ciniki['request']['business_id'], array());
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['markers']) ) {
		$json = 'var gmap_data = ' . json_encode($rc['markers']) . ';';
		$filename = '/' . sprintf('%02d', ($ciniki['request']['business_id']%100)) . '/'
			. sprintf('%07d', $ciniki['request']['business_id'])
			. '/dealers/gmap_data.js';
		$data_filename = $ciniki['request']['cache_dir'] . $filename;
		if( !file_exists(dirname($data_filename)) ) {
			mkdir(dirname($data_filename), 0755, true);
		}
		file_put_contents($data_filename, $json);
		$ciniki['response']['head']['scripts'][] = array('src'=>$ciniki['request']['cache_url'] . $filename, 
			'type'=>'text/javascript');
	}

	//
	// Check if we are to display a category
	//
/*	if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'category' 
		&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
		) {
		$category_permalink = $ciniki['request']['uri_split'][1];

		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerList');
		$rc = ciniki_customers_web_dealerList($ciniki, $settings, $ciniki['request']['business_id'],
			array('category'=>$category_permalink, 'format'=>'2dlist'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$dealers = $rc['dealers'];

		$article_title = "<a href='" . $ciniki['request']['base_url'] . "/dealers'>$page_title</a>";
		if( $rc['tag_name'] != '' ) {
			$page_title .= ' - ' . $rc['tag_name'];
			$article_title .= ' - ' . $rc['tag_name'];
		}

		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>$article_title</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";

		if( count($dealers) > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
			$base_url = $ciniki['request']['base_url'] . "/dealers";
			$rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $dealers, array('notitle'=>'yes'));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		} else {
			$page_content .= "<p>We're sorry, but there doesn't appear to be any dealers in this category.</p>";
		}

		$page_content .= "</div>"
			. "</article>"
			. "";
	}
*/

	//
	// Check if we are to display a dealer
	//
	if( isset($ciniki['request']['uri_split'][0]) 
		&& $ciniki['request']['uri_split'][0] != '' 
		&& $ciniki['request']['uri_split'][0] != 'location' 
		&& $ciniki['request']['uri_split'][0] != 'category' 
		) {
		$display_profile = 'yes';
		$dealer_permalink = $ciniki['request']['uri_split'][0];
		$article_title = '';
		$base_url = $ciniki['request']['base_url'] . "/dealers/$dealer_permalink";
		// Check for gallery image
		if( isset($ciniki['request']['uri_split'][1]) 
			&& $ciniki['request']['uri_split'][1] == 'gallery'
			&& $ciniki['request']['uri_split'][2] != ''
			) {
			$image_permalink = $ciniki['request']['uri_split'][2];
			$base_url .= "/gallery/$image_permalink";
		}
	}

	//
	// Check if we are to display a dealer
	//
	elseif( isset($ciniki['request']['uri_split'][0]) 
		&& $ciniki['request']['uri_split'][0] == 'category' 
		&& isset($ciniki['request']['uri_split'][1])
		&& $ciniki['request']['uri_split'][1] == '' 
		&& isset($ciniki['request']['uri_split'][2])
		&& $ciniki['request']['uri_split'][2] == '' 
		) {
		$display_profile = 'yes';
		$category = $ciniki['request']['uri_split'][1];
		$dealer_permalink = $ciniki['request']['uri_split'][2];
		$base_url = $ciniki['request']['base_url'] . "/dealers/category/$category/$dealer_permalink";
		// Check for gallery image
		if( isset($ciniki['request']['uri_split'][3]) 
			&& $ciniki['request']['uri_split'][3] == 'gallery'
			&& isset($ciniki['request']['uri_split'][4])
			&& $ciniki['request']['uri_split'][4] != ''
			) {
			$image_permalink = $ciniki['request']['uri_split'][4];
			$ciniki['response']['head']['links'][] = array('rel'=>'canonical',
				'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink 
					. '/gallery/' . $image_permalink
				);
			$base_url .= "/gallery/$image_permalink";
		} else {
			$ciniki['response']['head']['links'][] = array('rel'=>'canonical',
				'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink
				);
		}
	}

	//
	// Check if we are to display a dealer
	//
	elseif( isset($ciniki['request']['uri_split'][0]) 
		&& $ciniki['request']['uri_split'][0] == 'location' 
		&& isset($ciniki['request']['uri_split'][1])
		&& $ciniki['request']['uri_split'][1] == '' 
		&& isset($ciniki['request']['uri_split'][2])
		&& $ciniki['request']['uri_split'][2] == '' 
		&& isset($ciniki['request']['uri_split'][3])
		&& $ciniki['request']['uri_split'][3] == '' 
		&& isset($ciniki['request']['uri_split'][4])
		&& $ciniki['request']['uri_split'][4] == '' 
		) {
		$display_profile = 'yes';
		$country = $ciniki['request']['uri_split'][1];
		$province = $ciniki['request']['uri_split'][2];
		$state = $ciniki['request']['uri_split'][3];
		$dealer_permalink = $ciniki['request']['uri_split'][4];
		$base_url = $ciniki['request']['base_url'] . "/dealers/location/$country/$province/$state/$dealer_permalink";
		// Check for gallery image
		if( isset($ciniki['request']['uri_split'][5]) 
			&& $ciniki['request']['uri_split'][5] == 'gallery'
			&& isset($ciniki['request']['uri_split'][6])
			&& $ciniki['request']['uri_split'][6] != ''
			) {
			$image_permalink = $ciniki['request']['uri_split'][6];
			$ciniki['response']['head']['links'][] = array('rel'=>'canonical',
				'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink 
					. '/gallery/' . $image_permalink
				);
			$base_url .= "/gallery/$image_permalink";
		} else {
			$ciniki['response']['head']['links'][] = array('rel'=>'canonical',
				'href'=>$ciniki['request']['domain_base_url'] . '/dealers/' . $dealer_permalink
				);
		}
	}

	//
	// Display location information
	//
	elseif( isset($ciniki['request']['uri_split'][0]) 
		&& $ciniki['request']['uri_split'][0] == 'location' 
		&& isset($ciniki['request']['uri_split'][1]) 
		&& $ciniki['request']['uri_split'][1] != '' 
		) {
		$country_permalink = $ciniki['request']['uri_split'][1];
		$country_name = rawurldecode($country_permalink);
		$country_print_name = (isset($maps[strtolower($country_name)]['name'])?$maps[strtolower($country_name)]['name']:$country_name);
		$article_title = "<a href='$base_url'>Dealers</a>";
		$base_url = $ciniki['request']['domain_base_url'] . '/dealers/location/' . $country_permalink;
		$display_locations = 'yes';
		$display_map = 'yes';
		if( isset($ciniki['request']['uri_split'][2]) 
			&& $ciniki['request']['uri_split'][2] != '' 
			) {
			$province_permalink = $ciniki['request']['uri_split'][2];
			$province_name = rawurldecode($province_permalink);
			$province_print_name = (isset($maps[strtolower($country_name)]['provinces'][strtolower($province_name)]['name'])?$maps[strtolower($country_name)]['provinces'][strtolower($province_name)]['name']:$province_name);
			if( ($province_permalink != '' && $province_permalink != '-') 
				|| (isset($ciniki['request']['uri_split'][3]) && $ciniki['request']['uri_split'][3] != '-') ) {
				$article_title .= ($article_title!=''?' - ':'') . "<a href='$base_url'>" . $country_print_name . "</a>";
			} else {
				$article_title .= ($article_title!=''?' - ':'') . $country_print_name;
			}
			$base_url .= '/' . $province_permalink;
			$display_map = 'yes';
			// Check if there is a city specified
			if( isset($ciniki['request']['uri_split'][3]) 
				&& $ciniki['request']['uri_split'][3] != '' 
				) {
				$city_permalink = $ciniki['request']['uri_split'][3];
				$city_name = rawurldecode($city_permalink);
				$city_print_name = rawurldecode($city_permalink);
				if( $city_permalink != '' && $city_permalink != '-' 
					&& $province_permalink != '' && $province_permalink != '-' ) {
					$article_title .= ($article_title!=''?' - ':'') . "<a href='$base_url'>" . $province_print_name . "</a>";
				} elseif( $province_permalink != '' && $province_permalink != '-' ) {
					$article_title .= ($article_title!=''?' - ':'') . $province_print_name;
				}
				if( $city_permalink != '' && $city_permalink != '-' ) {
					$article_title .= ($article_title!=''?' - ':'') . $city_print_name;
				}
				$base_url .= '/' . $city_permalink;
				$display_location = 'yes';
				$display_locations = 'no';
				$display_map = 'yes';
				$display_list = 'yes';
			} elseif( $province_permalink != '' && $province_permalink != '-' ) {
				$article_title .= ($article_title!=''?' - ':'') . $province_print_name;
			}
		} else {
			$article_title .= ($article_title!=''?' - ':'') . $country_print_name;
		}
	}

	//
	// Display the list of dealers if a specific one isn't selected
	//
	else {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');

		//
		// Should the dealer categories be displayed
		//
		if( isset($settings['page-dealers-categories-display']) 
			&& ($settings['page-dealers-categories-display'] == 'wordlist'
				|| $settings['page-dealers-categories-display'] == 'wordcloud' )
			&& isset($ciniki['business']['modules']['ciniki.customers']['flags']) 
			&& ($ciniki['business']['modules']['ciniki.customers']['flags']&0x20) > 0 
			) {
			$display_categories = 'yes';
		}
		//
		// Should the dealer locations be displayed
		//
		if( isset($settings['page-dealers-locations-display']) 
			&& ($settings['page-dealers-locations-display'] == 'wordlist'
				|| $settings['page-dealers-locations-display'] == 'wordcloud' )
			&& isset($ciniki['business']['modules']['ciniki.customers']['flags']) 
			&& ($ciniki['business']['modules']['ciniki.customers']['flags']&0x10) > 0 
			) {
			$display_locations = 'yes';
			$base_url .= '/location';
		}
	}

	//
	// Get the content for the page
	//

	//
	// Display the dealer profile page
	//
	if( $display_profile == 'yes' ) {
		$display_categories = 'no';
		$display_category = 'no';
		$display_locations = 'no';
		$display_location = 'no';
		$display_map = 'no';
		$display_list = 'no';
		
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'memberDetails');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

		//
		// Get the dealer information
		//
		$rc = ciniki_customers_web_dealerDetails($ciniki, $settings, 
			$ciniki['request']['business_id'], $dealer_permalink);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$dealer = $rc['dealer'];

		$page_title = $dealer['name'];
		$article_title .= ($article_title != ''?' - ':'') . $dealer['name'];
		if( isset($image_permalink) && $image_permalink != '' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processGalleryImage');
			$rc = ciniki_web_processGalleryImage($ciniki, $settings, $business_id, array(
				'item'=>$dealer,
				'article_title'=>$article_title,
				'image_permalink'=>$image_permalink,
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		} else {
			//
			// Add description
			//
			$description = '';
			if( isset($dealer['description']) ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
				$rc = ciniki_web_processContent($ciniki, $dealer['description']);	
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$description .= $rc['content'];
			}

			//
			// Add contact_info
			//
			$cinfo = '';
			if( isset($dealer['addresses']) ) {
				foreach($dealer['addresses'] as $address) {
					$addr = '';
					if( $address['address1'] != '' ) {
						$addr .= ($addr!=''?'<br/>':'') . $address['address1'];
					}
					if( $address['address2'] != '' ) {
						$addr .= ($addr!=''?'<br/>':'') . $address['address2'];
					}
					if( $address['city'] != '' ) {
						$addr .= ($addr!=''?'<br/>':'') . $address['city'];
					}
					if( $address['province'] != '' ) {
						$addr .= ($addr!=''?', ':'') . $address['province'];
					}
					if( $address['postal'] != '' ) {
						$addr .= ($addr!=''?'  ':'') . $address['postal'];
					}
					if( $addr != '' ) {
						$cinfo .= ($cinfo!=''?'<br/>':'') . "$addr";
					}
				}
			}
			if( isset($dealer['phones']) ) {
				foreach($dealer['phones'] as $phone) {
					if( $phone['phone_label'] != '' && $phone['phone_number'] != '' ) {
						$cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_label'] . ': ' . $phone['phone_number'];
					} elseif( $phone['phone_number'] != '' ) {
						$cinfo .= ($cinfo!=''?'<br/>':'') . $phone['phone_number'];
					}
				}
			}
			if( isset($dealer['emails']) ) {
				foreach($dealer['emails'] as $email) {
					if( $email['email'] != '' ) {
						$cinfo .= ($cinfo!=''?'<br/>':'') . '<a href="mailto:' . $email['email'] . '">' . $email['email'] . '</a>';
					}
				}
			}

			if( $cinfo != '' ) {
				$description .= "<h2>Contact Info</h2>\n";
				$description .= "<p>$cinfo</p>";
			}

			if( isset($dealer['links']) ) {
				$links = '';
				foreach($dealer['links'] as $link) {
					$rc = ciniki_web_processURL($ciniki, $link['url']);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$url = $rc['url'];
					$display_url = $rc['display'];
					if( $link['name'] != '' ) {
						$display_url = $link['name'];
					}
					$links .= ($links!=''?'<br/>':'') 
						. "<a class='dealer-url' target='_blank' href='" . $url . "' "
						. "title='" . $display_url . "'>" . $display_url . "</a>";
				}
				if( $links != '' ) {
					$description .= "<h2>Links</h2>\n";
					$description .= "<p>" . $links . "</p>";
				}
			}
			$dealer['content'] = $description;

			//
			// Put together the dealer as a page
			//
			$rc = ciniki_web_processPage($ciniki, $settings, $base_url, $dealer, array(
				'article_title'=>$article_title,
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		}
	} 


	
	//
	// Check if profile is not display (this could be turned off in profile not found
	// All other information is grouped in one article
	//
	if( $display_profile == 'no' ) {
		$page_content .= "<article class='page'>\n"
			. "<header class='entry-title'><h1 class='entry-title'>$article_title</h1></header>\n"
			. "<div class='entry-content'>\n"
			. "";
	}

	//
	// Display a location
	//
	if( $display_location == 'yes' ) {
		
	}
		
	//
	// Display the list of categories
	//
	if( $display_categories == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerTagCloud');
		$base_url = $ciniki['request']['base_url'] . '/dealers/category';
		$rc = ciniki_customers_web_tagCloud($ciniki, $settings, $ciniki['request']['business_id'], 60);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Process the tags
		//
		if( $settings['page-dealers-categories-display'] == 'wordlist' ) {
			if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagList');
				$rc = ciniki_web_processTagList($ciniki, $settings, $base_url, $rc['tags'], array());
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			} else {
				$page_content = "<p>I'm sorry, there are no categories for this blog</p>";
			}
		} elseif( $settings['page-dealers-categories-display'] == 'wordcloud' ) {
			if( isset($rc['tags']) && count($rc['tags']) > 0 ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagCloud');
				$rc = ciniki_web_processTagCloud($ciniki, $settings, $base_url, $rc['tags']);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			} else {
				$page_content = "<p>I'm sorry, there are no categories for this blog</p>";
			}
		}
	}

	//
	// Display the list of countries/provinces/cities
	//
	if( $display_locations == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerLocationTagCloud');
		$rc = ciniki_customers_web_dealerLocationTagCloud($ciniki, $settings, 
			$ciniki['request']['business_id'], array(
				'country'=>(isset($country_name)?$country_name:''),
				'province'=>(isset($province_name)?$province_name:''),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		if( isset($rc['countries']) ) {
			$tags = $rc['countries'];
			foreach($tags as $tid => $tag) {
				$tags[$tid]['permalink'] = rawurlencode($tag['name']);
				if( $tag['num_tags'] < 10 ) {
					$tags[$tid]['permalink'] .= '/-/-';
				}
				if( isset($maps[strtolower($tag['name'])]['name']) ) {
					$tags[$tid]['name'] = $maps[strtolower($tag['name'])]['name'];
				}
			}
			if( !isset($settings['page-dealers-location-countries-display'])
				|| $settings['page-dealers-location-countries-display'] == 'wordcloud' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagCloud');
				$rc = ciniki_web_processTagCloud($ciniki, $settings, $base_url, $tags);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			} elseif( $settings['page-dealers-location-countries-display'] == 'wordlist' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagList');
				$rc = ciniki_web_processTagList($ciniki, $settings, $base_url, $tags, array());
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			}
		} elseif( isset($rc['provinces']) ) {
			$tags = $rc['provinces'];
			foreach($tags as $tid => $tag) {
				$tags[$tid]['permalink'] = rawurlencode($tag['name']);
				if( $tag['num_tags'] < 10 ) {
					$tags[$tid]['permalink'] .= '/-';
				}
				// Map provinces/states to full names
				if( isset($maps[strtolower($country_name)]['provinces'][strtolower($tag['name'])]['name']) ) {
					$tags[$tid]['name'] = $maps[strtolower($country_name)]['provinces'][strtolower($tag['name'])]['name'];
				}
			}
			if( !isset($settings['page-dealers-location-provinces-display'])
				|| $settings['page-dealers-location-provinces-display'] == 'wordcloud' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagCloud');
				$rc = ciniki_web_processTagCloud($ciniki, $settings, $base_url, $tags);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			} elseif( $settings['page-dealers-location-provinces-display'] == 'wordlist' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagList');
				$rc = ciniki_web_processTagList($ciniki, $settings, $base_url, $tags, array());
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			}
		} elseif( isset($rc['cities']) ) {
			$tags = $rc['cities'];
			if( !isset($settings['page-dealers-location-cities-display'])
				|| $settings['page-dealers-location-cities-display'] == 'wordcloud' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagCloud');
				$rc = ciniki_web_processTagCloud($ciniki, $settings, $base_url, $tags);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			} elseif( $settings['page-dealers-location-cities-display'] == 'wordlist' ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processTagList');
				$rc = ciniki_web_processTagList($ciniki, $settings, $base_url, $tags, array());
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= $rc['content'];
			}
		} else {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1924', 'msg'=>'No dealers found for this .'));
		}
	} 

	//
	// Get the list of dealers
	//
	if( $display_map == 'yes' || $display_list == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'dealerList');
		$rc = ciniki_customers_web_dealerList($ciniki, $settings, $ciniki['request']['business_id'], 
			array('format'=>'2dlist', 
				'country'=>(isset($country_name)?$country_name:''),
				'province'=>(isset($province_name)?$province_name:''),
				'city'=>(isset($city_name)?$city_name:''),
				));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$dealers = $rc['dealers'];
	}

	//
	// Display the map of the dealers 
	//
	if( $display_map == 'yes' && isset($dealers) ) {
		// 
		// Setup the javascript to display the map
		//
		$center_addr = '';
		$center_zoom = 2;
		if( isset($country_name) && $country_name != '' ) {
			$center_addr = $country_name;
			$center_zoom = 3;
			if( isset($province_name) && $province_name != '' ) {
				$center_addr = $province_name . ', ' . $center_addr;
				$center_zoom = 5;
			} 
			if( isset($city_name) && $city_name != '' ) {
				$center_addr = $city_name . ', ' . $center_addr;
				$center_zoom = 7;
			}
		}
		$ciniki['request']['inline_javascript'] .= ''
			. '<script type="text/javascript">'
			. 'var map;'
			. 'function gmap_start() {';
		if( $center_addr != '' ) {
			$ciniki['request']['inline_javascript'] .= 'var geocoder = new google.maps.Geocoder();'
				.  'geocoder.geocode({"address":"' . $center_addr . '"}, function(results, status) {'
					. 'if(status==google.maps.GeocoderStatus.OK){'
						. 'gmap_initialize(results[0].geometry.location.lat(), results[0].geometry.location.lng(),' . $center_zoom . ',results[0].geometry.viewport);'
					. '}'
				. '});';
		} else {
			$ciniki['request']['inline_javascript'] .= 'gmap_initialize(20,0,2);';
		}
		$ciniki['request']['inline_javascript'] .= ''
			. '};'
			. 'function gmap_initialize(lat,lng,z,v) {'
					. 'var myLatLng = new google.maps.LatLng(lat,lng);'
					. 'var mapOptions = {'
					. 'zoom: z,'
					. 'center: myLatLng,'
					. 'panControl: false,'
					. 'zoomControl: true,'
					. 'scaleControl: true,'
					. 'mapTypeId: google.maps.MapTypeId.ROADMAP'
				. '};'
				. 'map = new google.maps.Map(document.getElementById("googlemap"), mapOptions);'
				. 'if(v!=null){map.fitBounds(v);};'
				. 'gmap_refresh();'
			. '};'
			. "\n"
			. 'function gmap_refresh() {'
				. 'var markers=[];'
				. 'for(i in gmap_data) {'
					. 'gmap_showMarker(gmap_data[i].y,gmap_data[i].x,gmap_data[i].t,'
						. '"<p><b>"+gmap_data[i].t+"</b></p><p>"+gmap_data[i].c+"</p>");'
				. '}'
			. '};'
			. 'function gmap_showMarker(y,x,t,c) {'
				. 'var latLng = new google.maps.LatLng(y, x);'
				. 'console.log(t);'
				. 'var marker = new google.maps.Marker({'
					. 'position:latLng,'
					. 'map: map,'
					. 'title: t,'
				. '});'
				. 'var infowindow = new google.maps.InfoWindow({'
					. 'content:c'
				. '});'
				. 'google.maps.event.addListener(marker, "click", function() { infowindow.open(map, marker);});'
			. '}'
			. "\n"
			. 'function gmap_load() {'
				. 'var script = document.createElement("script");'
				. 'script.type = "text/javascript";'
				. 'script.src = "' . ($ciniki['request']['ssl']=='yes'?'https':'http') . '://maps.googleapis.com/maps/api/js?key=' . $ciniki['config']['ciniki.web']['google.maps.api.key'] . '&sensor=false&callback=gmap_start";'
				. 'document.body.appendChild(script);'
			. '};'
			. "\n"
			. 'window.onload = gmap_load;'
			. "\n"
			. '</script>'
			. '';
		$page_content .= '<div class="googlemap" id="googlemap"></div>';
	}

	if( $display_list == 'yes' && isset($dealers) ) {
		if( count($dealers) > 0 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
			$rc = ciniki_web_processCIList($ciniki, $settings, $base_url, $dealers, array('notitle'=>'yes'));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= $rc['content'];
		} else {
			$page_content .= "<p>No dealers found for this area.</p>";
		}
	}

	if( $display_profile == 'no' ) {
		$page_content .= "</div>\n"
			. "</article>\n"
			. "";
	}

	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title, array());
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	$content .= "<div id='content'>\n"
		. $page_content
		. "<br style='clear:both;' />\n"
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

	//
	// Save the cache file
	//
	if( $cache_file != '' && $cache_update == 'yes' ) {
		if( !file_exists(dirname($cache_file)) && mkdir(dirname($cache_file), 0755, true) === FALSE ) {
			error_log("WEB-CACHE: Failed to create dir for " . dirname($cache_file));
		} 
		elseif( file_put_contents($cache_file, $page_content) === FALSE ) {
			error_log("WEB-CACHE: Failed to write $cache_file");
		} else {
			//
			// We must force the timestamp on the file, otherwise at rackspace cloudsites it's behind
			//
			touch($cache_file, time());
		}
	}
	return array('stat'=>'ok', 'content'=>$content);
}
?>
