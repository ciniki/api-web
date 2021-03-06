<?php
//
// Description
// -----------
// DEPRECATED: NO LONGER USED. This function will generate the courses page for the tenant.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_generatePageCourses($ciniki, $settings) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
    //
    // Check if a file was specified to be downloaded
    //
    $download_err = '';
    if( isset($ciniki['tenant']['modules']['ciniki.courses'])
        && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'download'
        && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'fileDownload');
        $rc = ciniki_courses_web_fileDownload($ciniki, $ciniki['request']['tnid'], $ciniki['request']['uri_split'][1]);
        if( $rc['stat'] == 'ok' ) {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            $file = $rc['file'];
            if( $file['extension'] == 'pdf' ) {
                header('Content-Type: application/pdf');
            }
//          header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
            header('Content-Length: ' . strlen($file['binary_content']));
            header('Cache-Control: max-age=0');

            print $file['binary_content'];
            exit;
        }
        
        //
        // If there was an error locating the files, display generic error
        //
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.30', 'msg'=>'The file you requested does not exist.'));
    }

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
    // Check if there should be a submenu
    //
    $submenu = array();
    $first_course_cat = '';
    $first_course_type = '';
    if( isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        if( isset($settings['page-courses-submenu-categories']) && $settings['page-courses-submenu-categories'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'categories');
            $rc = ciniki_courses_web_categories($ciniki, $settings, $ciniki['request']['tnid']);
            if( $rc['stat'] == 'ok' ) {
                if( count($rc['categories']) > 1 ) {
                    foreach($rc['categories'] as $cid => $cat) {
                        if( $first_course_cat == '' ) {
                            $first_course_cat = $cat['name'];
                        }
                        if( $cat['name'] != '' ) {
                            $submenu[$cid] = array('name'=>$cat['name'], 'url'=>$ciniki['request']['base_url'] . "/courses/" . urlencode($cat['name']));
                        }
                    }
                } elseif( count($rc['categories']) == 1 ) {
                    $first_cat = array_pop($rc['categories']);
                    $first_course_cat = $first_type['name'];
                }
            }
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'courseTypes');
            $rc = ciniki_courses_web_courseTypes($ciniki, $settings, $ciniki['request']['tnid']);
            if( $rc['stat'] == 'ok' ) {
                if( count($rc['types']) > 1 ) {
                    foreach($rc['types'] as $cid => $type) {
                        if( $first_course_type == '' ) {
                            $first_course_type = $type['name'];
                        }
                        if( $type != '' ) {
                            $submenu[$cid] = array('name'=>$type['name'], 'url'=>$ciniki['request']['base_url'] . "/courses/" . urlencode($type['name']));
                        }
                    }
                } elseif( count($rc['types']) == 1 ) {
                    $first_type = array_pop($rc['types']);
                    $first_course_type = $first_type['name'];
                }
            }
        }
        if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x02) == 0x02 ) {
            $submenu['instructors'] = array('name'=>'Instructors', 'url'=>$ciniki['request']['base_url'] . '/courses/instructors');
        }
        if( isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes' ) {
            $submenu['registration'] = array('name'=>'Registration', 'url'=>$ciniki['request']['base_url'] . '/courses/registration');
        }
    }

    //
    // Check if we are to display the gallery image for an members
    //
    //
    // Check if we are to display an image, from the gallery, or latest images
    //
    if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'instructor' 
        && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
        && isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] == 'gallery' 
        && isset($ciniki['request']['uri_split'][3]) && $ciniki['request']['uri_split'][3] != '' 
        ) {
        $instructor_permalink = $ciniki['request']['uri_split'][1];
        $image_permalink = $ciniki['request']['uri_split'][3];
        $gallery_url = $ciniki['request']['base_url'] . "/courses/instructor/" . $instructor_permalink . "/gallery";

        //
        // Load the member to get all the details, and the list of images.
        // It's one query, and we can find the requested image, and figure out next
        // and prev from the list of images returned
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorDetails');
        $rc = ciniki_courses_web_instructorDetails($ciniki, $settings, 
            $ciniki['request']['tnid'], $instructor_permalink);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.31', 'msg'=>"I'm sorry, but we can't seem to find the image you requested.", $rc['err']));
        }
        $instructor = $rc['instructor'];

        if( !isset($instructor['images']) || count($instructor['images']) < 1 ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.32', 'msg'=>"I'm sorry, but we can't seem to find the image you requested."));
        }

        $first = NULL;
        $last = NULL;
        $img = NULL;
        $next = NULL;
        $prev = NULL;
        foreach($instructor['images'] as $iid => $image) {
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

        if( count($instructor['images']) == 1 ) {
            $prev = NULL;
            $next = NULL;
        } elseif( $prev == NULL ) {
            // The requested image was the first in the list, set previous to last
            $prev = $last;
        } elseif( $next == NULL ) {
            // The requested image was the last in the list, set previous to last
            $next = $first;
        }
    
        if( $img['title'] != '' ) {
            $page_title = $instructor['name'] . ' - ' . $img['title'];
        } else {
            $page_title = $instructor['name'];
        }

        if( $img == NULL ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.33', 'msg'=>"I'm sorry, but we can't seem to find the image you requested."));
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
        $page_content .= "<article class='page'>\n"
            . "<header class='entry-title'><h1 id='entry-title' class='entry-title'>$page_title</h1></header>\n"
            . "<div class='entry-content'>\n"
            . "";
        $page_content .= "<div id='gallery-image' class='gallery-image'>";
        $page_content .= "<div id='gallery-image-wrap' class='gallery-image-wrap'>";
        if( $prev != null ) {
            $page_content .= "<a id='gallery-image-prev' class='gallery-image-prev' href='$gallery_url/" . $prev['permalink'] . "'><div id='gallery-image-prev-img'></div></a>";
        }
        if( $next != null ) {
            $page_content .= "<a id='gallery-image-next' class='gallery-image-next' href='$gallery_url/" . $next['permalink'] . "'><div id='gallery-image-next-img'></div></a>";
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
    // Check if we are to display an instructor page
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'instructor'
        && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' ) {

        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorDetails');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
        //
        // Get the instructor information
        //
        $instructor_permalink = $ciniki['request']['uri_split'][1];
        $rc = ciniki_courses_web_instructorDetails($ciniki, $settings, 
            $ciniki['request']['tnid'], $instructor_permalink);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.web.34', 'msg'=>"I'm sorry, but we can't find the instructor you requested.", $rc['err']));
        }
        $instructor = $rc['instructor'];
        $page_title = $instructor['name'];
        $page_content .= "<article class='page'>\n"
            . "<header class='entry-title'><h1 class='entry-title'>" . $instructor['name'] . "</h1></header>\n"
            . "";

        //
        // Add primary image
        //
        if( isset($instructor['image_id']) && $instructor['image_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
            $rc = ciniki_web_getScaledImageURL($ciniki, $instructor['image_id'], 'original', '500', 0);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<aside><div class='image-wrap'><div class='image'>"
                . "<img title='' alt='" . $instructor['name'] . "' src='" . $rc['url'] . "' />"
                . "</div></div></aside>";
        }
        
        //
        // Add description
        //
        if( isset($instructor['full_bio']) ) {
            $rc = ciniki_web_processContent($ciniki, $settings, $instructor['full_bio']);   
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= $rc['content'];
        }

        if( isset($instructor['url']) ) {
            $rc = ciniki_web_processURL($ciniki, $instructor['url']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $url = $rc['url'];
            $display_url = $rc['display'];
        } else {
            $url = '';
        }

        if( $url != '' ) {
            $page_content .= "<br/>Website: <a class='members-url' target='_blank' href='" . $url . "' title='" . $instructor['name'] . "'>" . $display_url . "</a>";
        }
        $page_content .= "</article>";

        if( isset($instructor['images']) && count($instructor['images']) > 0 ) {
            $page_content .= "<article class='page'>"   
                . "<header class='entry-title'><h1 class='entry-title'>Gallery</h1></header>\n"
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
            $img_base_url = $ciniki['request']['base_url'] . "/courses/instructor/" . $instructor['permalink'] . "/gallery";
            $rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $instructor['images'], 125);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
            $page_content .= "</article>";
        }
    }

    //
    // Check if we are to display a list of instructors
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'instructors' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorList');
        $rc = ciniki_courses_web_instructorList($ciniki, $settings, $ciniki['request']['tnid'], 0);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $instructors = $rc['instructors'];

        $page_content .= "<article class='page'>\n"
            . "<header class='entry-title'><h1 class='entry-title'>Instructors</h1></header>\n"
            . "<div class='entry-content'>\n"
            . "";

        if( count($instructors) > 0 ) {
            foreach($instructors as $inum => $instructor) {
                $page_content .= "<table class='cilist'><tbody><tr><th><span class='cilist-category'>" . $instructor['name'] . "</span></th><td>\n";
                $page_content .= "<table class='cilist-categories'><tbody>\n";
                $instructor_url = $ciniki['request']['base_url'] . "/courses/instructor/" . $instructor['permalink'];

                // Setup the instructor image
                if( isset($instructor['is_details']) && $instructor['is_details'] == 'yes' ) {
                    $page_content .= "<tr><td class='cilist-image' rowspan='2'>";
                } else {
                    $page_content .= "<tr><td class='cilist-image'>";
                }
                if( isset($instructor['image_id']) && $instructor['image_id'] > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                    $rc = ciniki_web_getScaledImageURL($ciniki, $instructor['image_id'], 'thumbnail', '150', 0);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page_content .= "<div class='image-cilist-thumbnail'>"
                        . "<a href='$instructor_url' title='" . $instructor['name'] . "'><img title='' alt='" . $instructor['name'] . "' src='" . $rc['url'] . "' /></a>"
                        . "</div></aside>";
                }
                $page_content .= "</td>";

                // Setup the details
                $page_content .= "<td class='cilist-details'>";
                if( isset($instructor['short_bio']) && $instructor['short_bio'] != '' ) {
                    $page_content .= "<p class='cilist-description'>" . $instructor['short_bio'] . "</p>";
                }
                $page_content .= "</td></tr>";
                if( isset($instructor['is_details']) && $instructor['is_details'] == 'yes' ) {
                    $page_content .= "<tr><td class='cilist-more'><a href='$instructor_url'>... more</a></td></tr>";
                }
                $page_content .= "</tbody></table>";
                $page_content .= "</td></tr>\n</tbody></table>\n";
            }

        } else {
            $page_content .= "<p>Currently no instructors.</p>";
        }

        $page_content .= "</div>\n"
            . "</article>\n"
            . "";

    }

    //
    // Check if we are to display a course detail page
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'course'
        && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != '' 
        && isset($ciniki['request']['uri_split'][2]) && $ciniki['request']['uri_split'][2] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'courseOfferingDetails');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');

        //
        // Get the course information
        //
        $course_permalink = $ciniki['request']['uri_split'][1];
        $offering_permalink = $ciniki['request']['uri_split'][2];
        $rc = ciniki_courses_web_courseOfferingDetails($ciniki, $settings, 
            $ciniki['request']['tnid'], $course_permalink, $offering_permalink);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering = $rc['offering'];
        $page_title = $offering['name'];
        if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x01) == 0x01 && $offering['code'] != '' ) {
            $page_title = $offering['code'] . ' - ' . $offering['name'];
        } elseif( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x20) == 0x20 && $offering['offering_code'] != '' ) {
            $page_title = $offering['offering_code'] . ' - ' . $offering['name'];
        }
        if( isset($settings['page-courses-level-display']) 
            && $settings['page-courses-level-display'] == 'yes' 
            && isset($offering['level']) && $offering['level'] != ''
            ) {
            $page_title .= ' - ' . $offering['level'];
        }
        $page_content .= "<article class='page'>\n"
            . "<header class='entry-title'><h1 class='entry-title'>" . $page_title . "</h1></header>\n"
            . "";

        //
        // Add primary image
        //
        if( isset($offering['image_id']) && $offering['image_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
            $rc = ciniki_web_getScaledImageURL($ciniki, $offering['image_id'], 'original', '500', 0);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<aside><div class='image-wrap'><div class='image'>"
                . "<img title='' alt='" . $offering['name'] . "' src='" . $rc['url'] . "' />"
                . "</div></div></aside>";
        }
        
        //
        // Add description
        //
        if( isset($offering['long_description']) ) {
            $rc = ciniki_web_processContent($ciniki, $settings, $offering['long_description']); 
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<div class='entry-content'>" 
                . $rc['content'];
        }

        //
        // List the prices for the course
        //
        if( isset($offering['prices']) && count($offering['prices']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'cartSetupPrices');
            $rc = ciniki_web_cartSetupPrices($ciniki, $settings, $ciniki['request']['tnid'], $offering['prices']);
            if( $rc['stat'] != 'ok' ) {
                error_log("Error in formatting prices.");
            } else {
                $page_content .= $rc['content'];
            }
/*          $page_content .= "<h2>Price</h2><p>";
            foreach($offering['prices'] as $pid => $price) {
                if( $price['name'] != '' ) {
                    $page_content .= $price['name'] . " - " . $price['unit_amount_display'] . "<br/>";
                } else {
                    $page_content .= $price['unit_amount_display'] . "<br/>";
                }
            }
            $page_content .= "</p>"; */
        }

        //
        // The classes for a course offering
        //
        if( isset($offering['classes']) && count($offering['classes']) > 1 ) {
            $page_content .= "<h2>Classes</h2><p>";
            foreach($offering['classes'] as $cid => $class) {
                $page_content .= $class['class_date'] . " " . $class['start_time'] . " - " . $class['end_time'] . "<br/>";
            }
            $page_content .= "</p>";
        } elseif( isset($offering['classes']) && count($offering['classes']) == 1 ) {
            $page_content .= "<h2>Date</h2><p>";
            $page_content .= "<p>" . $offering['condensed_date'] . "</p>";
        }

        //
        // The files for a course offering
        //
        if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x08) == 0x08 ) {
            if( isset($offering['files']) ) {
                $page_content .= "<h2>Files</h2>";
                foreach($offering['files'] as $fid => $file) {
//                  $page_content .= $file['name'];
//              $file = $file['file'];
                    $url = $ciniki['request']['base_url'] . '/courses/download/' . $file['permalink'] . '.' . $file['extension'];
                    $page_content .= "<p><a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a></p>";
                }
            }
        }
        $page_content .= "</div>";

        //
        // The instructors for a course offering
        //
        if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x02) == 0x02 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'instructorList');
            $rc = ciniki_courses_web_instructorList($ciniki, $settings, $ciniki['request']['tnid'], $offering['id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $instructors = $rc['instructors'];
            
            $page_content .= "<div class='entry-content clearboth'>";
            if( count($instructors) > 1 ) {
                $page_content .= "<h2>Instructors</h2>";
            } else {
                $page_content .= "<h2>Instructor</h2>";
            }
            $page_content .= "<table class='cilist'><tbody>";
            foreach($instructors as $iid => $instructor) {
                $page_content .= "<tr><th><span class='cilist-category'>" . $instructor['name'] . "</span></th><td>\n";
                $page_content .= "<table class='cilist-categories'><tbody>\n";
                $instructor_url = $ciniki['request']['base_url'] . "/courses/instructor/" . $instructor['permalink'];

                // Setup the instructor image
                if( isset($instructor['is_details']) && $instructor['is_details'] == 'yes' ) {
                    $page_content .= "<tr><td class='cilist-image' rowspan='2'>";
                } else {
                    $page_content .= "<tr><td class='cilist-image'>";
                }
                if( isset($instructor['image_id']) && $instructor['image_id'] > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                    $rc = ciniki_web_getScaledImageURL($ciniki, $instructor['image_id'], 'thumbnail', '150', 0);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page_content .= "<div class='image-cilist-thumbnail'>"
                        . "<a href='$instructor_url' title='" . $instructor['name'] . "'><img title='' alt='" . $instructor['name'] . "' src='" . $rc['url'] . "' /></a>"
                        . "</div></aside>";
                }
                $page_content .= "</td>";

                // Setup the details
                $page_content .= "<td class='cilist-details'>";
                if( isset($instructor['short_bio']) && $instructor['short_bio'] != '' ) {
                    $page_content .= "<p class='cilist-description'>" . $instructor['short_bio'] . "</p>";
                }
                $page_content .= "</td></tr>";
                if( isset($instructor['is_details']) && $instructor['is_details'] == 'yes' ) {
                    $page_content .= "<tr><td class='cilist-more'><a href='$instructor_url'>... more</a></td></tr>";
                }
                $page_content .= "</tbody></table>";
                $page_content .= "</td></tr>\n";
            }
            $page_content .= "</tbody></table>\n";
            $page_content .= "</div>\n";
        }

        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x0200) && isset($offering['images']) ) {
            $page_content .= "<h2>Gallery</h2>";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageGalleryThumbnails');
            $img_base_url = $ciniki['request']['base_url'] . "/courses/course/" . $course_permalink . '/' . $offering_permalink . "/gallery";
            $rc = ciniki_web_generatePageGalleryThumbnails($ciniki, $settings, $img_base_url, $offering['images'], 125);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= "<div class='image-gallery'>" . $rc['content'] . "</div>";
        }

        $page_content .= "</article>";
    }

    //
    // Check if we are to display a registration detail page
    //
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'registration' 
        && isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes'
        ) {
        //
        // Check if membership info should be displayed here
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'registrationDetails');
        $rc = ciniki_courses_web_registrationDetails($ciniki, $settings, $ciniki['request']['tnid']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration = $rc['registration'];
        if( $registration['details'] != '' ) {
            $page_content .= "<article class='page'>\n"
                . "<header class='entry-title'><h1 class='entry-title'>Registration</h1></header>\n"
                . "<div class='entry-content'>\n"
                . "";
            if( isset($settings["page-courses-registration-image"]) && $settings["page-courses-registration-image"] != '' && $settings["page-courses-registration-image"] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                $rc = ciniki_web_getScaledImageURL($ciniki, $settings["page-courses-registration-image"], 'original', '500', 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= "<aside><div class='image-wrap'>"
                    . "<div class='image'><img title='' alt='" . $ciniki['tenant']['details']['name'] . "' src='" . $rc['url'] . "' /></div>";
                if( isset($settings["page-courses-registration-image-caption"]) && $settings["page-courses-registration-image-caption"] != '' ) {
                    $page_content .= "<div class='image-caption'>" . $settings["page-courses-registration-image-caption"] . "</div>";
                }
                $page_content .= "</div></aside>";
            }
            $rc = ciniki_web_processContent($ciniki, $settings, $registration['details']);  
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $page_content .= $rc['content'];

            foreach($registration['files'] as $fid => $file) {
                $file = $file['file'];
                $url = $ciniki['request']['base_url'] . '/courses/download/' . $file['permalink'] . '.' . $file['extension'];
                $page_content .= "<p><a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a></p>";
            }
            
            if( isset($registration['more-details']) && $registration['more-details'] != '' ) {
                $rc = ciniki_web_processContent($ciniki, $settings, $registration['more-details']); 
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];
            }
            $page_content .= "</div>\n"
                . "</article>";
        }
    }

    //
    // Generate the list of courses upcoming, current, past
    //
    else {
        $coursecategory = '';
        $coursetype = '';
        if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' ) {
            $coursetype = urldecode($ciniki['request']['uri_split'][0]);
//      } elseif( $first_course_type != '' ) {
//          $coursetype = $first_course_type;
        }
        // Setup default settings
        if( !isset($settings['page-courses-upcoming-active']) ) {
            $settings['page-courses-upcoming-active'] = 'yes';
        }
        if( !isset($settings['page-courses-current-active']) ) {
            $settings['page-courses-current-active'] = 'no';
        }
        if( !isset($settings['page-courses-past-active']) ) {
            $settings['page-courses-past-active'] = 'no';
        }
        //
        //
        // Check for content in settings
        //
        if( $coursetype != '' ) {
            $type_name = '-' . preg_replace('/[^a-z0-9]/', '', strtolower($coursetype));
        } else {
            $type_name = '';
        }
        // Load any content for this page
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_content', 'tnid', $ciniki['request']['tnid'], 'ciniki.web', 'content', "page-courses$type_name");
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $cnt = $rc['content'];

        if( isset($settings['page-courses' . $type_name . '-image']) 
            || isset($cnt['page-courses' . $type_name . '-content']) 
            ) {
            $page_content .= "<article class='page'>\n"
//              . "<header class='entry-title'><h1 class='entry-title'>Registration</h1></header>\n"
                . "<div class='entry-content'>\n"
                . "";
            
            // Check if there are files to be displayed on the main page
            $program_url = '';
            if( $type_name == '' && (isset($settings['page-courses-catalog-download-active']) 
                    && $settings['page-courses-catalog-download-active'] == 'yes' )
    //          || ()   -- future files
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'files');
                $rc = ciniki_courses_web_files($ciniki, $settings, $ciniki['request']['tnid']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['files']) ) {
                    $reg_files = $rc['files'];
                    // Check if program brochure download and link to image
                    if( count($reg_files) == 1 && isset($reg_files[0]['file']['permalink']) && $reg_files[0]['file']['permalink'] != '' ) {
                        $program_url = $ciniki['request']['base_url'] . '/courses/download/' . $reg_files[0]['file']['permalink'] . '.' . $reg_files[0]['file']['extension'];
                        $program_url_title = $reg_files[0]['file']['name'];
                    }
                } else {
                    $reg_files = array();
                }
            }
            if( isset($settings["page-courses" . $type_name . "-image"]) && $settings["page-courses" . $type_name . "-image"] != '' && $settings["page-courses" . $type_name . "-image"] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                $rc = ciniki_web_getScaledImageURL($ciniki, $settings["page-courses" . $type_name . "-image"], 'original', '500', 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= "<aside><div class='image-wrap'>"
                    . "<div class='image'>";
                if( $program_url != '' ) {
                    $page_content .= "<a target='_blank' href='$program_url' title='$program_url_title'>";
                }
                $page_content .= "<img title='' alt='" . $ciniki['tenant']['details']['name'] . "' src='" . $rc['url'] . "' />";
                if( $program_url != '' ) {
                    $page_content .= "</a>";
                }
                $page_content .= "</div>";
                if( isset($settings["page-courses" . $type_name . "-image-caption"]) && $settings["page-courses" . $type_name . "-image-caption"] != '' ) {
                    $page_content .= "<div class='image-caption'>" . $settings["page-courses" . $type_name . "-image-caption"] . "</div>";
                }
                $page_content .= "</div></aside>";
            }
            if( isset($cnt['page-courses' . $type_name . '-content']) ) {
                $rc = ciniki_web_processContent($ciniki, $settings, $cnt['page-courses' . $type_name . '-content']);    
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];
            }

            // Check if there are files to be displayed on the main page
            if( $type_name == '' && (isset($settings['page-courses-catalog-download-active']) 
                    && $settings['page-courses-catalog-download-active'] == 'yes' )
    //          || ()   -- future files
                ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'files');
                $rc = ciniki_courses_web_files($ciniki, $settings, $ciniki['request']['tnid']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['files']) ) {
                    foreach($rc['files'] as $f => $file) {
                        $file = $file['file'];
                        $url = $ciniki['request']['base_url'] . '/courses/download/' 
                            . $file['permalink'] . '.' . $file['extension'];
                        $page_content .= "<p>"
                            . "<a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" 
                            . $file['name'] . "</a></p>";
                    }
                }
            }
        
            $page_content .= "</div>\n"
                . "</article>";
        }

        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'courseList');
        foreach(array('current', 'upcoming', 'past') as $type) {
            if( $settings["page-courses-$type-active"] != 'yes' ) {
                continue;
            }
            if( $type == 'past' ) {
                if( $settings['page-courses-current-active'] == 'yes' ) {
                    // If displaying the current list, then show past as purely past.
                    $rc = ciniki_courses_web_courseList($ciniki, $settings, $ciniki['request']['tnid'], $coursetype, $type);
                } else {
                    // Otherwise, include current courses in the past
                    $rc = ciniki_courses_web_courseList($ciniki, $settings, $ciniki['request']['tnid'], $coursetype, 'currentpast');
                }
            } else {
                $rc = ciniki_courses_web_courseList($ciniki, $settings, $ciniki['request']['tnid'], $coursetype, $type);
            }
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $categories = $rc['categories'];

            if( isset($settings["page-courses-$type-name"]) && $settings["page-courses-$type-name"] != '' ) {
                $name = $settings["page-courses-$type-name"];
            } else {
                $name = ucwords($type . "");
            }
            $page_content .= "<article class='page'>\n"
                . "<header class='entry-title'><h1 class='entry-title'>$name</h1></header>\n"
                . "<div class='entry-content'>\n"
                . "";

            if( count($categories) > 0 ) {
                $page_content .= "<table class='clist'>\n"
                    . "";
                $prev_category = NULL;
                $num_categories = count($categories);
                foreach($categories as $cnum => $c) {
                    if( $prev_category != NULL ) {
                        $page_content .= "</td></tr>\n";
                    }
                    $hide_dates = 'no';
                    if( isset($c['name']) && $c['name'] != '' ) {
                        $page_content .= "<tr><th>"
                            . "<span class='clist-category'>" . $c['name'] . "</span></th>"
                            . "<td>";
                        // $content .= "<h2>" . $c['cname'] . "</h2>";
                    } elseif( $num_categories == 1 && count($c) > 0) {
                        // Only the blank category
                        $offering = reset($c['offerings']);
                        $page_content .= "<tr><th>"
                            . "<span class='clist-category'>" . $offering['condensed_date'] . "</span></th>"
                            . "<td>";
                        $hide_dates = 'yes';
                    } else {
                        $page_content .= "<tr><th>"
                            . "<span class='clist-category'></span></th>"
                            . "<td>";
                    }
                    foreach($c['offerings'] as $onum => $offering) {
                        if( isset($offering['is_details']) && $offering['is_details'] == 'yes' ) {
                            $offering_url = $ciniki['request']['base_url'] . '/courses/course/' . $offering['course_permalink'] . '/' . $offering['permalink'];
                        } else {
                            $offering_url = '';
                        }
                        if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x01) == 0x01 && $offering['code'] != '' ) {
                            $offering_name = $offering['code'] . ' - ' . $offering['name'];
                        } elseif( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x20) == 0x20 && $offering['offering_code'] != '' ) {
                            $offering_name = $offering['offering_code'] . ' - ' . $offering['name'];
                        } else {
                            $offering_name = $offering['name'];
                        }
                        if( isset($settings['page-courses-level-display']) 
                            && $settings['page-courses-level-display'] == 'yes' 
                            && isset($offering['level']) && $offering['level'] != ''
                            ) {
                            $offering_name .= ' - ' . $offering['level'];
                        }

                        if( $offering_url != '' ) {
                            $page_content .= "<a href='$offering_url'><p class='clist-title'>" . $offering_name . "</p></a>";
                        } else {
                            $page_content .= "<p class='clist-title'>" . $offering_name . "</p>";
                        }
                        if( $hide_dates != 'yes' ) {
                            $page_content .= "<p class='clist-subtitle'>" . $offering['condensed_date'] . "</p>";
                        }
                        $rc = ciniki_web_processContent($ciniki, $settings, $offering['short_description'], 'clist-description');   
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $page_content .= $rc['content'];
                        // $page_content .= "<p class='clist-description'>" . $rc['content'] . "</p>";
                        if( $offering_url != '' ) {
                            $page_content .= "<p class='clist-url clist-more'><a href='" . $offering_url . "'>... more</a></p>";
                        }
                    }
                }
            } else {
                $page_content .= "<p>No " . strtolower($name) . " found</p>";
            }
            $page_content .= "</td></tr>\n</table>\n";
            $page_content .= "</div>\n"
                . "</article>\n"
                . "";
        }
        //
        // Check if no submenu going to be displayed, then need to display registration information here
        //
        if( count($submenu) == 1 
            && isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'web', 'registrationDetails');
            $rc = ciniki_courses_web_registrationDetails($ciniki, $settings, $ciniki['request']['tnid']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $registration = $rc['registration'];
            if( $registration['details'] != '' ) {
                // Check for a programs pdf, and link to image if it exists
                $program_url = '';
                if( isset($registration['files']) && count($registration['files']) == 1 ) {
                    if( isset($registration['files'][0]['file']['permalink']) && $registration['files'][0]['file']['permalink'] != '' ) {
                        $program_url = $ciniki['request']['base_url'] . '/courses/download/' . $registration['files'][0]['file']['permalink'] . '.' . $registration['files'][0]['file']['extension'];
                        $program_url_title = $registration['files'][0]['file']['name'];
                    }
                }
                $page_content .= "<article class='page'>\n"
                    . "<header class='entry-title'><h1 class='entry-title'>Registration</h1></header>\n"
                    . "<div class='entry-content'>\n"
                    . "";
                if( isset($settings["page-courses-registration-image"]) && $settings["page-courses-registration-image"] != '' && $settings["page-courses-registration-image"] > 0 ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
                    $rc = ciniki_web_getScaledImageURL($ciniki, $settings["page-courses-registration-image"], 'original', '500', 0);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page_content .= "<aside><div class='image-wrap'>"
                        . "<div class='image'>";
                    if( $program_url != '' ) {
                        $page_content .= "<a target='_blank' href='$program_url' title='$program_url_title'>";
                    }
                    $page_content .= "<img title='' alt='" . $ciniki['tenant']['details']['name'] . "' src='" . $rc['url'] . "' />";
                    if( $program_url != '' ) {
                        $page_content .= "</a>";
                    }

                    $page_content .= "</div>";
                    if( isset($settings["page-courses-registration-image-caption"]) && $settings["page-courses-registration-image-caption"] != '' ) {
                        $page_content .= "<div class='image-caption'>" . $settings["page-courses-registration-image-caption"] . "</div>";
                    }
                    $page_content .= "</div></aside>";
                }
                $rc = ciniki_web_processContent($ciniki, $settings, $registration['details']);  
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $page_content .= $rc['content'];

                foreach($registration['files'] as $fid => $file) {
                    $file = $file['file'];
                    $url = $ciniki['request']['base_url'] . '/courses/download/' . $file['permalink'] . '.' . $file['extension'];
                    $page_content .= "<p><a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a></p>";
                }
                
                if( isset($registration['more-details']) && $registration['more-details'] != '' ) {
                    $rc = ciniki_web_processContent($ciniki, $settings, $registration['more-details']); 
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $page_content .= $rc['content'];
                }
                $page_content .= "</div>\n"
                    . "</article>";
            }

            $page_content .= "</td></tr>\n</table>\n";
            $page_content .= "</div>\n"
                . "</article>\n"
                . "";
        }
    }

    if( count($submenu) == 1 
        && isset($settings['page-courses-registration-active']) && $settings['page-courses-registration-active'] == 'yes' ) {
        $submenu = array();
    }

    //
    // Generate the complete page
    //

    //
    // Add the header
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
    $rc = ciniki_web_generatePageHeader($ciniki, $settings, $page_title, $submenu);
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
