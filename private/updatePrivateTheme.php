<?php
//
// Description
// -----------
// This function updates the theme files in the cache for the tenant. It will also
// update any settings if required.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_web_updatePrivateTheme(&$ciniki, $tnid, $settings, $theme_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    //
    // Setup the cache directory
    //
    if( !isset($settings['site-privatetheme-permalink']) || $settings['site-privatetheme-permalink'] == '' ) {
        $strsql = "SELECT id, permalink "
            . "FROM ciniki_web_themes "
            . "WHERE ciniki_web_themes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_web_themes.id = '" . ciniki_core_dbQuote($ciniki, $theme_id) . "' "
            . "ORDER BY date_added DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.web', 'theme');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['theme']) ) {
            $theme_cache_dir = $ciniki['tenant']['web_cache_dir'] . '/theme-' . $rc['theme']['permalink'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.web.129', 'msg'=>'No theme specified'));
        }
    } else {
        $theme_cache_dir = $ciniki['tenant']['web_cache_dir'] . '/theme-' . $settings['site-privatetheme-permalink'];
    }
    if( !file_exists($theme_cache_dir) ) {
        mkdir($theme_cache_dir, 0755, true);
    }

    //
    // Load the list of javascript and css content from the database
    //
    $strsql = "SELECT id, content_type, media, content "
        . "FROM ciniki_web_theme_content "
        . "WHERE ciniki_web_theme_content.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_web_theme_content.theme_id = '" . ciniki_core_dbQuote($ciniki, $theme_id) . "' "
        . "AND ciniki_web_theme_content.status = 10 "
        . "AND (content_type = 'css' OR content_type = 'js') "
        . "ORDER BY content_type, media, sequence "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.web', array(
        array('container'=>'types', 'fname'=>'content_type',
            'fields'=>array('content_type')),
        array('container'=>'media', 'fname'=>'media',
            'fields'=>array('media')),
        array('container'=>'content', 'fname'=>'id',
            'fields'=>array('id', 'media', 'content')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Join the content into files
    //
    $allcontent = array(
        'site-privatetheme-css-all'=>array('filename'=>'style.css', 'content'=>''),
        'site-privatetheme-css-print'=>array('filename'=>'print.css', 'content'=>''),
        'site-privatetheme-js'=>array('filename'=>'code.js', 'content'=>''),
        );
    if( isset($rc['types']) ) {
        foreach($rc['types'] as $type) {
            if( isset($type['media']) ) {
                foreach($type['media'] as $media) {
                    if( $type['content_type'] == 'css' && $media['media'] == 'all' ) {
                        $setting = 'site-privatetheme-css-all';
                    } elseif( $type['content_type'] == 'css' && $media['media'] == 'print' ) {
                        $setting = 'site-privatetheme-css-print';
                    } elseif( $type['content_type'] == 'js' ) {
                        $setting = 'site-privatetheme-js';
                    } else {
                        // Ignore unknown content
                        continue;
                    }
                    if( isset($media['content']) ) {
                        foreach($media['content'] as $type_media_content) {
                            $allcontent[$setting]['content'] .= $type_media_content['content'] . "\n";
                        }
                    }
                }
            }
        }
    }
    
    //
    // Save the content to the cache directory
    //
    foreach($allcontent as $setting => $content) {
        if( $content['content'] != '' ) {
            // 
            // Write the content to the cache directory
            //
            if( !file_put_contents($theme_cache_dir . '/' . $content['filename'], $content['content']) ) {
                error_log('WEB-ERR: Unable to write cache theme file: ' . $theme_cache_dir . '/' . $content['filename']);
            }
        } else {
            //
            // Remove the file if it exists
            //
            if( file_exists($theme_cache_dir . '/' . $content['filename']) ) {
                if( !unlink($theme_cache_dir . '/' . $content['filename']) ) {
                    error_log('WEB-ERR: Unable to remove cache theme file: ' . $theme_cache_dir . '/' . $content['filename']);
                }
            }
        }
    }

    //
    // Load the list of images from the database
    //
    $strsql = "SELECT ciniki_images.id, ciniki_web_theme_images.name, ciniki_images.original_filename, ciniki_images.type, "
        . "ciniki_images.image, "
        . "UNIX_TIMESTAMP(ciniki_images.last_updated) AS last_updated "
        . "FROM ciniki_web_theme_images, ciniki_images "
        . "WHERE ciniki_web_theme_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_web_theme_images.theme_id = '" . ciniki_core_dbQuote($ciniki, $theme_id) . "' "
        . "AND ciniki_web_theme_images.image_id = ciniki_images.id "
        . "AND ciniki_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.web', 'image');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $img) {
            $img_file = $theme_cache_dir . '/' . $img['name'];
            if( !file_exists($img_file)
                || filemtime($img_file) < $img['last_updated']
                ) {
                //
                // Load the image from the database
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
                $rc = ciniki_images_loadImage($ciniki, $ciniki['request']['tnid'], $img['id'], 'original');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $image = $rc['image'];

                //
                // Write the file
                //
                $h = fopen($img_file, 'w');
                if( $h ) {
                    fwrite($h, $image->getImageBlob());
                    fclose($h);
                } else {
                    error_log('WEB-ERR: Unable to load image: $img_file');
                }
            }
        }
    }

    //
    // Update the directory timestamp
    //
    // Set the filemtime to the proper UTC timestamp, don't rely on the filesystem to be correct
    $dt = new DateTime('now', new DateTimeZone('UTC'));
    touch($theme_cache_dir, $dt->getTimestamp());

    return array('stat'=>'ok');
}
?>
