<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
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

	//
	// Check if we are at the main page or a category or year gallery
	//
	if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' 
		&& ((($ciniki['request']['uri_split'][0] == 'category' || $ciniki['request']['uri_split'][0] == 'year')
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
		$strsql = "SELECT ciniki_artcatalog.id, name, permalink, image_id, type, catalog_number, category, year, flags, webflags, "
			. "IF((ciniki_artcatalog.flags&0x01)=1, 'yes', 'no') AS forsale, "
			. "IF((ciniki_artcatalog.flags&0x02)=2, 'yes', 'no') AS sold, "
			. "IF((ciniki_artcatalog.webflags&0x01)=1, 'yes', 'no') AS hidden, "
			. "media, size, framed_size, price, location, awards, notes, "
			. "date_added, last_updated "
			. "FROM ciniki_artcatalog "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $image_permalink) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'artcatalog', 'piece');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['piece']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'651', 'msg'=>'Unable to find artwork'));
		}
		$img = $rc['piece'];
		$page_title = $img['name'];

		//
		// Requested photo from within a gallery, which may be a category or year or latest
		// Latest category is special, and doesn't contain the keyword category, is also shortened url
		//
		if( $ciniki['request']['uri_split'][0] == 'latest' ) {
			$image_permalink = $ciniki['request']['uri_split'][1];
			//
			// Get the position of the image in the gallery.
			// Count the number of items before the specified image, then use
			// that number to LIMIT a query
			//
			$strsql = "SELECT COUNT(*) AS pos_num FROM ciniki_artcatalog "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['business_id']) . "' "
				. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $image_permalink) . "' "
				. "AND (webflags&0x01) = 0 "
				. "AND date_added > '" . ciniki_core_dbQuote($ciniki, $img['date_added']) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'artcatalog', 'position');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($rc['position']['pos_num']) ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'652', 'msg'=>'Unable to load image'));
			}
			$offset = $rc['position']['pos_num'];
			//
			// Get the previous and next photos
			//
			$strsql = "SELECT id, name, permalink "
				. "FROM ciniki_artcatalog "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['business_id']) . "' "
				. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $image_permalink) . "' "
				. "AND (webflags&0x01) = 0 "
				. "ORDER BY ciniki_artcatalog.date_added DESC ";
			if( $offset == 0 ) {
				$strsql .= "LIMIT 3 ";
			} elseif( $offset > 0 ) {
				$strsql .= "LIMIT " . ($offset-1) . ", 3";
			} else {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'653', 'msg'=>'Unable to load image'));
			}
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'artcatalog', 'next');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$prev = NULL;
			if( isset($rc['row'][0]) ) {
				$prev = $rc['row'][0];
			}
			$next = NULL;
			if( isset($rc['row'][2]) ) {
				$prev = $rc['row'][2];
			}
			//
			// If the image requested is at the end of the gallery, then
			// get the first image
			//
			if( $rc['num_rows'] < 3 ) {
			}
			//
			// If the image is at begining of the gallery, then get the last image
			//
			if( $offset == 0 ) {
			}

		} elseif( $ciniki['request']['uri_split'][0] == 'image' ) {
			$image_permalink = $ciniki['request']['uri_split'][1];
			//
			// Get the previous and next photos
			//
		} else {
			$image_permalink = $ciniki['request']['uri_split'][2];
			//
			// Get the previous and next photos
			//
			$strsql = "SELECT id, name, permalink, image_id "
				. "FROM ciniki_artcatalog "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['business_id']) . "' "
				. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $image_permalink) . "' "
				. "ORDER BY ciniki_artcatalog.date_added "
				. "";

		}

		//
		// Load the image
		//
		require_once($ciniki['config']['core']['modules_dir'] . '/web/private/getScaledImageURL.php');
		$rc = ciniki_web_getScaledImageURL($ciniki, $img['image_id'], 'original', 0, 600);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$page_content .= "<div class='gallery-image'><div class='gallery-image-wrap'>"
			. "<img title='$name' alt='$name' src='" . $rc['url'] . "' />"
			. "</div>"
			. "<div class='gallery-image-details'>"
			. "<span class='image-title'>$name</span>"
			. "</div></div>";
	
	} elseif( isset($ciniki['request']['uri_split'][0]) 
		&& $ciniki['request']['uri_split'][0] != '' 
		&& ($ciniki['request']['uri_split'][0] == 'category' || $ciniki['request']['uri_split'][0] == 'year')
		&& $ciniki['request']['uri_split'][1] != '' ) {
		$page_title = urldecode($ciniki['request']['uri_split'][1]);

		//
		// Get the gallery for the specified category
		//
		require_once($ciniki['config']['core']['modules_dir'] . '/artcatalog/web/categoryImages.php');
		$rc = ciniki_artcatalog_web_categoryImages($ciniki, $settings, $ciniki['request']['business_id'], 
			$ciniki['request']['uri_split'][0], urldecode($ciniki['request']['uri_split'][1]));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$images = $rc['images'];

		require_once($ciniki['config']['core']['modules_dir'] . '/web/private/generatePageGalleryThumbnails.php');
		$img_base_url = $ciniki['request']['base_url'] . "/gallery/category/" . $ciniki['request']['uri_split'][1];
		$rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $rc['images'], 125);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";

	} else {
		//
		// Get any user specified content for the gallery page
		//
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQueryDash.php');
		$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', $ciniki['request']['business_id'], 'web', 'content', 'page-gallery');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		if( isset($rc['content']['page-gallery-content']) ) {
			require_once($ciniki['config']['core']['modules_dir'] . '/web/private/processContent.php');
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
		require_once($ciniki['config']['core']['modules_dir'] . '/artcatalog/web/categories.php');
		$rc = ciniki_artcatalog_web_categories($ciniki, $settings, $ciniki['request']['business_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['categories']) ) {
			//
			// No categories specified, just show thumbnails of all artwork
			//
			$page_title = 'Gallery';
			require_once($ciniki['config']['core']['modules_dir'] . '/artcatalog/web/categoryImages.php');
			$rc = ciniki_artcatalog_web_categoryImages($ciniki, $settings, $ciniki['request']['business_id'], 'category', '');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$images = $rc['images'];

			require_once($ciniki['config']['core']['modules_dir'] . '/web/private/generatePageGalleryThumbnails.php');
			$img_base_url = $ciniki['request']['base_url'] . "/gallery/image";
			$rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $rc['images'], 150, 0);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
		} else {
			$page_title = 'Galleries';
			$page_content .= "<div class='image-categories'>";
			foreach($rc['categories'] AS $cnum => $category) {
				$name = $category['category']['name'];
				require_once($ciniki['config']['core']['modules_dir'] . '/web/private/getScaledImageURL.php');
				$rc = ciniki_web_getScaledImageURL($ciniki, $category['category']['image_id'], 'thumbnail', '240', 0);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$page_content .= "<div class='image-categories-thumbnail'>"
					. "<a href='" . $ciniki['request']['base_url'] . "/gallery/category/" . urlencode($name) . "' "
						. "title='" . $name . "'>"
					. "<img title='$name' alt='$name' src='" . $rc['url'] . "' />"
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
	require_once($ciniki['config']['core']['modules_dir'] . '/web/private/generatePageHeader.php');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	//
	// Build the page content
	//
	$content .= "<div id='content'>\n"
		. "<article class='page'>\n"
		. "<header class='entry-title'><h1 class='entry-title'>$page_title</h1></header>\n"
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
	require_once($ciniki['config']['core']['modules_dir'] . '/web/private/generatePageFooter.php');
	$rc = ciniki_web_generatePageFooter($ciniki, $settings);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	return array('stat'=>'ok', 'content'=>$content);
}
?>
