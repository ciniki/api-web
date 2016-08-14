<?php
//
// Description
// -----------
// This function will generate the gallery page for the website
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageGallery(&$ciniki, $settings) {

    //
    // Store the content created by the page
    //
    $page_content = '';

    $page_title = "Galleries";
    $artcatalog_type = 0;
    $last_change = 0;
    $cache_file = '';
    $base_url = $ciniki['request']['base_url'] . "/gallery";
    $tags = array();
    if( isset($ciniki['business']['modules']['ciniki.artcatalog']) ) {
        $ciniki['response']['head']['og']['url'] = $ciniki['request']['domain_base_url'] . '/gallery';
        if( isset($settings['page-gallery-artcatalog-split']) 
            && $settings['page-gallery-artcatalog-split'] == 'yes' ) {
            if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' ) {
                switch($ciniki['request']['uri_split'][0]) {
                    case 'paintings': $artcatalog_type = 1; break;
                    case 'photographs': $artcatalog_type = 2; break;
                    case 'jewelry': $artcatalog_type = 3; break;
                    case 'sculptures': $artcatalog_type = 4; break;
                    case 'fibrearts': $artcatalog_type = 5; break;
                    case 'printmaking': $artcatalog_type = 6; break;
                    case 'pottery': $artcatalog_type = 8; break;
                }
                if( $artcatalog_type > 0 ) {
                    $ciniki['response']['head']['og']['url'] .= '/' . $ciniki['request']['uri_split'][0];
                    $atype = array_shift($ciniki['request']['uri_split']);
                    $base_url .= '/' . $atype;
                }
            }
        } 
        $pkg = 'ciniki';
        $mod = 'artcatalog';
        $category_uri_component = 'category';
        $last_change = $ciniki['business']['modules']['ciniki.artcatalog']['last_change'];
    } elseif( isset($ciniki['business']['modules']['ciniki.gallery']) ) {
        $pkg = 'ciniki';
        $mod = 'gallery';
        $category_uri_component = 'album';
        $ciniki['response']['head']['og']['url'] = $ciniki['request']['domain_base_url'] . '/gallery';
        $last_change = $ciniki['business']['modules']['ciniki.gallery']['last_change'];
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'267', 'msg'=>'No gallery module enabled'));
    }

    //
    // Check if anything has changed in other modules that may change the menu
    // or images used.  Not the most accurate way to determine if the cache needs to be refreshed,
    // but better than missing something.
    //
    if( isset($ciniki['business']['modules']['ciniki.images']['last_change']) 
        && $ciniki['business']['modules']['ciniki.images']['last_change'] > $last_change ) {
        $last_change = $ciniki['business']['modules']['ciniki.images']['last_change'];
    }
    if( isset($ciniki['business']['modules']['ciniki.web']['last_change']) 
        && $ciniki['business']['modules']['ciniki.web']['last_change'] > $last_change ) {
        $last_change = $ciniki['business']['modules']['ciniki.web']['last_change'];
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
            $gallery_url = $base_url . '/latest';
        } elseif( $ciniki['request']['uri_split'][0] == 'image' ) {
            $image_permalink = $ciniki['request']['uri_split'][1];
            $gallery_url = $base_url . '/image';
        } else {
            $image_permalink = $ciniki['request']['uri_split'][2];
            $gallery_url = $base_url . '/' . $ciniki['request']['uri_split'][0] . '/' . $ciniki['request']['uri_split'][1];
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
        if( $img['image_id'] == 0 ) {
            return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2121', 'msg'=>"I'm sorry, but we can't seem to find the image your requested."));
        }
        $page_title = $img['title'];
        $ciniki['response']['head']['og']['url'] .= '/' . $category_uri_component . '/' . $img['category_permalink'] . '/' . $img['permalink'];
        $tags[] = preg_replace('/[^A-Za-z0-9_-]/', '', $img['category']);
        
//      $page_content .= '<pre>' . print_r($img, true) . '</pre>';
        if( isset($img['type']) ) {
            switch($img['type']) {
                case 1:
                    $tags[] = 'art';
                    $tags[] = 'painting';
                    break;
                case 2:
                    $tags[] = 'art';
                    $tags[] = 'photograph';
                    break;
                case 3:
                    $tags[] = 'jewelry';
                    break;
                case 4:
                    $tags[] = 'sculpture';
                    break;
                case 5:
                    $tags[] = 'fibreart';
                    break;
                case 6:
                    $tags[] = 'printmaking';
                    break;
                case 8:
                    $tags[] = 'pottery';
                    break;
            }
        }

//      ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'shortenURL');
//      $surl = ciniki_web_shortenURL($ciniki, $settings, $ciniki['request']['business_id'], 
//          $ciniki['response']['head']['og']['url']);
//      
        //
        // Get the album details
        //
        ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'albumDetails');
        $albumDetails = $pkg . '_' . $mod . '_web_albumDetails';
        $rc = $albumDetails($ciniki, $settings, $ciniki['request']['business_id'], array(
            'type'=>$ciniki['request']['uri_split'][0], 
            'type_name'=>urldecode($ciniki['request']['uri_split'][1]), // Permalink for ciniki.gallery
            'artcatalog_type'=>$artcatalog_type));
        if( $rc['stat'] == 'ok' && isset($rc['album']['name']) && $rc['album']['name'] != '' ) {
            $album = $rc['album'];
            $article_title = "<a href='" . $ciniki['request']['base_url'] . '/gallery'
                . '/' . $ciniki['request']['uri_split'][0]
                . '/' . $ciniki['request']['uri_split'][1] 
                . "'>" . $album['name'] . "</a>";
            if( isset($img['title']) && $img['title'] != '' ) {
                $article_title .= ' - ' . $img['title'];    
            }
        } else {
            $article_title = $img['title'];
        }
        $prev = NULL;
        $next = NULL;

        //
        // Requested photo from within a gallery, which may be a category or year or latest
        // Latest category is special, and doesn't contain the keyword category, is also shortened url
        //
        if( $ciniki['request']['uri_split'][0] == 'latest' ) {
            ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'galleryNextPrev');
            $galleryNextPrev = $pkg . '_' . $mod . '_web_galleryNextPrev';
            $rc = $galleryNextPrev($ciniki, $settings, $ciniki['request']['business_id'], array(
                'permalink'=>$image_permalink,
                'img'=>$img,
                'type'=>'latest',
                'artcatalog_type'=>$artcatalog_type));
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
            $rc = $galleryNextPrev($ciniki, $settings, $ciniki['request']['business_id'], array(
                'permalink'=>$image_permalink,
                'img'=>$img,
                'type'=>$category_uri_component,
                'artcatalog_type'=>$artcatalog_type));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $prev = $rc['prev'];
            $next = $rc['next'];
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
        $ciniki['response']['head']['og']['image'] = $rc['domain_url'];
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
            $page_content .= "<a id='gallery-image-prev' class='gallery-image-prev' href='$gallery_url/" . $prev['permalink'] . "'><div id='gallery-image-prev-img'></div></a>";
        }
        if( $next != null ) {
            $page_content .= "<a id='gallery-image-next' class='gallery-image-next' href='$gallery_url/" . $next['permalink'] . "'><div id='gallery-image-next-img'></div></a>";
        }
        $page_content .= "<img id='gallery-image-img' title='" . $img['title'] . "' alt='" . $img['title'] . "' src='" . $img_url . "' onload='javascript: gallery_resize_arrows();' />";
        $page_content .= "</div><br/>";
        $page_content .= "<div id='gallery-image-details' class='gallery-image-details'>";

        $page_content .= "<span class='image-title'>" . $img['title'] . '</span>'
            . "<span class='image-details'><p>" . $img['details'] . '</p></span>';
        if( $img['description'] != '' && (!isset($img['webflags']) || ($img['webflags']&0x0100) > 0) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
            $rc = ciniki_web_processContent($ciniki, $settings, $img['description']);
            $page_content .= "<span class='image-description'>" . $rc['content'] . "</span>";
            $ciniki['response']['head']['og']['description'] = strip_tags($img['description']);
        }
        if( isset($img['inspiration']) && $img['inspiration'] != '' && isset($img['webflags']) && ($img['webflags']&0x0200) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
            $rc = ciniki_web_processContent($ciniki, $settings, $img['inspiration']);   
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<span class='image-awards-title'>Inspiration</span>"
                . "<span class='image-awards'>" . $rc['content'] . "</span>"
                . "";
        }
        if( isset($img['awards']) && $img['awards'] != '' && isset($img['webflags']) && ($img['webflags']&0x0400) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
            $rc = ciniki_web_processContent($ciniki, $settings, $img['awards']);    
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<span class='image-awards-title'>Awards</span>"
                . "<span class='image-awards'>" . $rc['content'] . "</span>"
                . "";
        }
        if( isset($img['publications']) && $img['publications'] != '' && isset($img['webflags']) && ($img['webflags']&0x4000) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
            $rc = ciniki_web_processContent($ciniki, $settings, $img['publications']);    
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<span class='image-awards-title'>Publications</span>"
                . "<span class='image-awards'>" . $rc['content'] . "</span>"
                . "";
        }
        //
        // Check for additional images for the artwork to be displayed
        //
        if( isset($img['additionalimages']) && count($img['additionalimages']) > 0 ) {
            $page_content .= "<span class='sub-title'>Additional Images</span>";
            array_unshift($img['additionalimages'], array(
                'id'=>0,
                'image_id'=>$img['image_id'],
                'title'=>$img['title'],
                'last_updated'=>$img['last_updated'],
                ));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryAdditionalThumbnails');
            $img_base_url = $base_url . "/$category_uri_component/" . $ciniki['request']['uri_split'][1];
            $rc = ciniki_web_generatePageGalleryAdditionalThumbnails($ciniki, $settings, $img_base_url, $img['additionalimages'], 75);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<div class='additional-image-gallery'>" . $rc['content'] . "</div>";
        }

        if( !isset($settings['page-gallery-share-buttons']) 
            || $settings['page-gallery-share-buttons'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processShareButtons');
            $rc = ciniki_web_processShareButtons($ciniki, $settings, array(
                'title'=>$page_title,
                'tags'=>$tags,
                ));
            if( $rc['stat'] == 'ok' ) {
                $page_content .= $rc['content'];
            }
        }
        $page_content .= "</div>";
        $page_content .= "</div>";
        
        //
        // Check for products to be displayed
        //
        if( isset($img['products']) && count($img['products']) > 0 ) {
            $page_content .= "<h2>Products</h2>";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processCIList');
//            print "<pre>" . print_r($img['products'], true) . "</pre>";
            $rc = ciniki_web_processCIList($ciniki, $settings, $base_url, 
                array('0'=>array('name'=>'', 'noimage'=>'/ciniki-web-layouts/default/img/noimage_240.png', 'list'=>$img['products'])), 
                array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= $rc['content'];
        }
    } 

    //
    // Generate the gallery page, showing the thumbnails
    //
    elseif( isset($ciniki['request']['uri_split'][0]) 
        && $ciniki['request']['uri_split'][0] != '' 
        && ($ciniki['request']['uri_split'][0] == 'album' || $ciniki['request']['uri_split'][0] == 'category' || $ciniki['request']['uri_split'][0] == 'year')
        && $ciniki['request']['uri_split'][1] != '' ) {
        $page_title = urldecode($ciniki['request']['uri_split'][1]);
        $article_title = urldecode($ciniki['request']['uri_split'][1]);

        //
        // Get the gallery for the specified album
        //
        ciniki_core_loadMethod($ciniki, $pkg, $mod, 'web', 'categoryImages');
        $categoryImages = $pkg . '_' . $mod . '_web_categoryImages';
        $rc = $categoryImages($ciniki, $settings, $ciniki['request']['business_id'], array(
            'type'=>$ciniki['request']['uri_split'][0], 
            'type_name'=>urldecode($ciniki['request']['uri_split'][1]), // Permalink for ciniki.gallery
            'artcatalog_type'=>$artcatalog_type));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        
        $images = $rc['images'];
        if( isset($rc['album']) ) {
            $album = $rc['album'];
            $page_title = $album['name'];
            $article_title = $album['name'];
        } else {
            $album = array('name'=>'', 'description'=>'');
            if( isset($rc['album_name']) && $rc['album_name'] != '' ) {
                $page_title = $rc['album_name'];
                $article_title = $rc['album_name'];
                $album['name'] = $rc['album_name'];
            }
        }

        if( isset($album['description']) && $album['description'] != '' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
            $rc = ciniki_web_processContent($ciniki, $settings, $album['description'], 'wide');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content = $rc['content'];
//          $page_content .= "<p class='wide'>" . $album['description'] . "</p>";
        }

        if( isset($images) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
            $img_base_url = $base_url . "/$category_uri_component/" . $ciniki['request']['uri_split'][1];
            $rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $images, 125);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
        }
    } 

    //
    // Generate the main gallery page, showing the galleries/albums
    //
    else {
        //
        // Check for cached content
        //
        if( isset($ciniki['business']['cache_dir']) && $ciniki['business']['cache_dir'] != '' ) {
            $cache_file = $ciniki['business']['cache_dir'] . '/ciniki.web/gallery';
            if( isset($atype) && $atype != '' ) {
                $cache_file .= '-' . $atype;
            }
            $utc_offset = date_offset_get(new DateTime);
            // Check if no changes have been made since last cache file write
            if( file_exists($cache_file) && (filemtime($cache_file) - $utc_offset) > $last_change ) {
////                $content = file_get_contents($cache_file);
////                if( $content != '' ) {
//                  error_log("WEB-CACHE: using cached $cache_file");
////                    return array('stat'=>'ok', 'content'=>$content);
////                }
            }
        }

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'business_id', 
            $ciniki['request']['business_id'], 'ciniki.web', 'content', 'page-gallery');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( isset($rc['content']['page-gallery-content']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
            $rc = ciniki_web_processContent($ciniki, $settings, $rc['content']['page-gallery-content']);    
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
        $rc = $categories($ciniki, $settings, $ciniki['request']['business_id'], 
            array('artcatalog_type'=>$artcatalog_type)); 
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
            $rc = $categoryImages($ciniki, $settings, $ciniki['request']['business_id'], array(
                'type'=>$category_uri_component, 'type_name'=>'', 
                'artcatalog_type'=>$artcatalog_type));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['images']) || count($rc['images']) < 1 ) {
                $page_content .= "<p>Sorry, there doesn't seem to be anything in this gallery yet.  Please try again later.</p>";
            } else {
                $images = $rc['images'];
                
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
                $img_base_url = $base_url . "/image";
                $rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $rc['images'], 150, 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
            }
        } elseif( count($rc['categories']) == 1 ) {
            // If only one album, then open album
            $category = array_pop($rc['categories']);
            if( isset($category['permalink']) && $category['permalink'] != '' ) {
                header('Location: ' . $base_url . '/' . $category_uri_component . '/' . $category['permalink']);
            } else {
                header('Location: ' . $base_url . '/' . $category_uri_component . '/' . urlencode($category['name']));
            }
            exit;
        } else {
            if( isset($settings['page-gallery-name']) && $settings['page-gallery-name'] != '' ) {
                $page_title = $settings['page-gallery-name'];
            } else {
                $page_title = 'Galleries';
            }
            if( $mod == 'artcatalog' && isset($settings['page-gallery-artcatalog-format']) 
                && $settings['page-gallery-artcatalog-format'] == 'list'
                ) {
                $base_url .= '/category';

                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processBlockImageList');
                $rc = ciniki_web_processBlockImageList($ciniki, $settings, $ciniki['request']['business_id'], array(
                    'type'=>'imagelist',
                    'base_url'=>$base_url,
                    'notitle'=>'yes',
                    'list'=>$rc['categories'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];

            } else {
                $page_content .= "<div class='image-categories'>";
                foreach($rc['categories'] AS $cnum => $category) {
                    $name = $category['name'];
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                    if( isset($category['image_id']) ) {
                        $rc = ciniki_web_getScaledImageURL($ciniki, $category['image_id'], 'thumbnail', '240', 0);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $img_url = $rc['url'];
                    } else {
                        $img_url = '/ciniki-web-layouts/default/img/noimage_240.png';
                    }
                    $page_content .= "<div class='image-categories-thumbnail-wrap'>"
                        . "<a href='" . $base_url . "/$category_uri_component/";
                    if( isset($category['permalink']) && $category['permalink'] != '' ) {
                        $page_content .= $category['permalink'];
                    } else {
                        $page_content .= urlencode($name);
                    }
                    $page_content .= "' title='" . $name . "'>"
                        . "<div class='image-categories-thumbnail'>"
                        . "<img title='$name' alt='$name' src='" . $img_url . "' />"
                        . "</div>"
                        . "<span class='image-categories-name'>$name</span>"
                        . "</a></div>";
                }
                $page_content .= "</div>";
            }
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

    if( !isset($article_title) ) {
        $article_title = $page_title;
    }

    //
    // Build the page content
    //
    $content .= "<div id='content'>\n"
        . "<article class='page'>\n"
        . "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$article_title</h1></header>\n"
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

    //
    // Save the cache file
    //
    if( $cache_file != '' ) {
        if( !file_exists(dirname($cache_file)) && mkdir(dirname($cache_file), 0755, true) === FALSE ) {
            error_log('WEB-CACHE: Failed to create dir for $cache_file');
        } 
        elseif( file_put_contents($cache_file, $content) === FALSE ) {
            error_log('WEB-CACHE: Failed to write $cache_file');
        }
    }

    return array('stat'=>'ok', 'content'=>$content);
}
?>
