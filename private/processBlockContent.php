<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_processBlockContent(&$ciniki, $settings, $tnid, $block) {

    $content = '';

    //
    // Check for a image
    //
    $image_content = '';
    if( isset($block['aside_image_id']) && $block['aside_image_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
        $rc = ciniki_web_getScaledImageURL($ciniki, $block['aside_image_id'], 'original', '500', 0);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $image_content = "<aside><div class='image-wrap'><div class='image'>"
            . "<img title='' alt='" . (isset($block['title'])?$block['title']:'') . "' src='" . $rc['url'] . "' />"
            . "</div>";
        if( isset($block['aside_image_caption']) && $block['aside_image_caption'] != '' ) {
            $image_content .= "<div class='image-caption'>" . $block['aside_image_caption'] . "</div>";
        }
        $image_content .= "</div></aside>";
    }

    //
    // Make sure there is content to edit
    //
    if( isset($block['content']) && $block['content'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
        $rc = ciniki_web_processContent($ciniki, $settings, $block['content'], ((isset($block['wide'])&&$block['wide']=='yes')?'wide':''));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['content'] != '' ) {
            if( isset($block['title']) && $block['title'] != '' ) {
                $content .= "<h2" . ((isset($block['wide'])&&$block['wide']=='yes')?" class='wide'":'') . ">" . $block['title'] . "</h2>";
            }
            $content .= $image_content;
            $content .= $rc['content'];
        }
    }
    elseif( isset($block['html']) && $block['html'] != '' ) {
        if( isset($block['title']) && $block['title'] != '' ) {
            $content .= "<h2>" . $block['title'] . "</h2>";
        }
        $content .= $image_content;
        $content .= $block['html'];
    }

    
    return array('stat'=>'ok', 'content'=>$content);
}
?>
