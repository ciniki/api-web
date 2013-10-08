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
function ciniki_web_generatePageGallery($ciniki, $settings) {

	//
	// Store the content created by the page
	//
	$page_content = '';

	//
	// FIXME: Check if anything has changed, and if not load from cache
	//
		

	$page_title = "Galleries";
	if( isset($ciniki['business']['modules']['ciniki.artcatalog']) ) {
		$pkg = 'ciniki';
		$mod = 'artcatalog';
		$category_uri_component = 'category';
	} elseif( isset($ciniki['business']['modules']['ciniki.gallery']) ) {
		$pkg = 'ciniki';
		$mod = 'gallery';
		$category_uri_component = 'album';
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'267', 'msg'=>'No gallery module enabled'));
	}

	//
	// Check if we are to display an image, from the gallery, or latest images
	//
	if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' 
		&& ((($ciniki['request']['uri_split'][0] == 'album' || $ciniki['request']['uri_split'][0] == 'category' || $ciniki['request']['uri_split'][0] == 'year')
			&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
			&& isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' 
			)
			|| ($ciniki['request']['uri_split'][0] == 'latest' 
			&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
			)
			|| ($ciniki['request']['uri_split'][0] == 'image' 
			&& isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
			)
			)
		) {
		
		//
		// Get the permalink for the image requested
		//
		if( $ciniki['request']['uri_split'][0] == 'latest' ) {
			$image_permalink = $ciniki['request']['uri_split'][1];
		} elseif( $ciniki['request']['uri_split'][0] == 'image' ) {
			$image_permalink = $ciniki['request']['uri_split'][1];
		} else {
			$image_permalink = $ciniki['request']['uri_split'][2];
		}

		// 
		// Get the image details
		//
		ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'imageDetails');
		$imageDetails = $pkg . '_' . $mod . '_web_imageDetails';
		$rc = $imageDetails($ciniki, $settings, $ciniki['request']['business_id'], $image_permalink);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'1309', 'msg'=>"I'm sorry, but we can't seem to find the image your requested.", $rc['err']));
		}
		$img = $rc['image'];
		$page_title = $img['title'];
		$prev = NULL;
		$next = NULL;

		//
		// Requested photo from within a gallery, which may be a category or year or latest
		// Latest category is special, and doesn't contain the keyword category, is also shortened url
		//
		if( $ciniki['request']['uri_split'][0] == 'latest' ) {
			ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'galleryNextPrev');
			$galleryNextPrev = $pkg . '_' . $mod . '_web_galleryNextPrev';
			$rc = $galleryNextPrev($ciniki, $settings, $ciniki['request']['business_id'], $image_permalink, $img, 'latest');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$next = $rc['next'];
			$prev = $rc['prev'];
		} elseif( $ciniki['request']['uri_split'][0] == 'image' ) {
			//
			// There is no next and previous images if request is direct to the image
			//
			$next = NULL;
			$prev = NULL;
		} else {
			ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'galleryNextPrev');
			$galleryNextPrev = $pkg . '_' . $mod . '_web_galleryNextPrev';
			$rc = $galleryNextPrev($ciniki, $settings, $ciniki['request']['business_id'], $image_permalink, $img, $category_uri_component);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$next = $rc['next'];
			$prev = $rc['prev'];
		}

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
			. "<span class='image-details'>" . $img['details'] . '</span>';
		if( $img['description'] != '' ) {
			$page_content .= "<span class='image-description'>" . preg_replace('/\n/', '<br/>', $img['description']) . "</span>";
		}
		if( $img['awards'] != '' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
			$rc = ciniki_web_processContent($ciniki, $img['awards']);	
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= "<span class='image-awards-title'>Awards</span>"
				. "<span class='image-awards'>" . $rc['content'] . "</span>"
				. "";
		}
		$page_content .= "</div></div>";
	
	} 

	//
	// Generate the gallery page, showing the thumbnails
	//
	elseif( isset($ciniki['request']['uri_split'][0]) 
		&& $ciniki['request']['uri_split'][0] != '' 
		&& ($ciniki['request']['uri_split'][0] == 'album' || $ciniki['request']['uri_split'][0] == 'category' || $ciniki['request']['uri_split'][0] == 'year')
		&& $ciniki['request']['uri_split'][1] != '' ) {
		$page_title = urldecode($ciniki['request']['uri_split'][1]);

		//
		// Get the gallery for the specified album
		//
		ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'categoryImages');
		$categoryImages = $pkg . '_' . $mod . '_web_categoryImages';
		$rc = $categoryImages($ciniki, $settings, $ciniki['request']['business_id'], 
			$ciniki['request']['uri_split'][0], urldecode($ciniki['request']['uri_split'][1]));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$images = $rc['images'];

		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
		$img_base_url = $ciniki['request']['base_url'] . "/gallery/$category_uri_component/" . $ciniki['request']['uri_split'][1];
		$rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $rc['images'], 125);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
	} 

	//
	// Generate the main gallery page, showing the galleries/albums
	//
	else {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
		$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', $ciniki['request']['business_id'], 'ciniki.web', 'content', 'page-gallery');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		if( isset($rc['content']['page-gallery-content']) ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
			$rc = ciniki_web_processContent($ciniki, $rc['content']['page-gallery-content']);	
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content = $rc['content'];
		}

		//
		// List the categories the user has created in the artcatalog, 
		// OR just show all the thumbnails if they haven't created any categories
		//
		ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'categories');
		$categories = $pkg . '_' . $mod . '_web_categories';
		$rc = $categories($ciniki, $settings, $ciniki['request']['business_id']); 
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['categories']) ) {
			//
			// No categories specified, just show thumbnails of all artwork
			//
			if( isset($settings['page-gallery-name']) && $settings['page-gallery-name'] != '' ) {
				$page_title = $settings['page-gallery-name'];
			} else {
				$page_title = 'Gallery';
			}
			ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'categoryImages');
			$categoryImages = $pkg . '_' . $mod . '_web_categoryImages';
			$rc = $categoryImages($ciniki, $settings, $ciniki['request']['business_id'], $category_uri_component, '');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$images = $rc['images'];

			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
			$img_base_url = $ciniki['request']['base_url'] . "/gallery/image";
			$rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $rc['images'], 150, 0);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
		} else {
			if( isset($settings['page-gallery-name']) && $settings['page-gallery-name'] != '' ) {
				$page_title = $settings['page-gallery-name'];
			} else {
				$page_title = 'Galleries';
			}
			$page_content .= "<div class='image-categories'>";
			foreach($rc['categories'] AS $cnum => $category) {
				$name = $category['category']['name'];
				ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
				$rc = ciniki_web_getScaledImageURL($ciniki, $category['category']['image_id'], 'thumbnail', '240', 0);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= "<div class='image-categories-thumbnail-wrap'>"
					. "<a href='" . $ciniki['request']['base_url'] . "/gallery/$category_uri_component/" . urlencode($name) . "' "
						. "title='" . $name . "'>"
					. "<div class='image-categories-thumbnail'>"
					. "<img title='$name' alt='$name' src='" . $rc['url'] . "' />"
					. "</div>"
					. "<span class='image-categories-name'>$name</span>"
					. "</a></div>";
			}
			$page_content .= "</div>";
		}
	}

	$content = '';

	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title, array());
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	//
	// Build the page content
	//
	$content .= "<div id='content'>\n"
		. "<article class='page'>\n"
		. "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
		. "<div class='entry-content'>\n"
		. "";
	if( $page_content != '' ) {
		$content .= $page_content;
	}

	$content .= "</div>"
		. "</article>"
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
