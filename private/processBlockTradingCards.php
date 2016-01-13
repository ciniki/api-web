<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_web_processBlockTradingCards($ciniki, $settings, $business_id, $block) {

	if( !isset($block['cards']) ) {
		return array('stat'=>'ok', 'content'=>'');
	}

	if( !isset($block['base_url']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3046', 'msg'=>'Unable to process request'));
	}

	$content = "";

	$content .= "<div class='trading-cards'>";
	foreach($block['cards'] as $cid => $card) {
        if( isset($card['name']) ) {
            $name = $card['name'];
        } elseif( isset($card['title']) ) {
            $name = $card['title'];
        } else {
            $name = '';
        }
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'getScaledImageURL');
		$rc = ciniki_web_getScaledImageURL($ciniki, $card['image_id'], 'thumbnail', '240', 0);
		if( $rc['stat'] != 'ok' ) {
			$img_url = '/ciniki-web-layouts/default/img/noimage_240.png';
		} else {
			$img_url = $rc['url'];
		}
		$content .= "<div class='trading-card-wrap'>"
            . "<div class='trading-card'>"
			. "<a href='" . $block['base_url'] . '/' . $card['permalink'] . "' " . "title='$name'>"
			. "<div class='trading-card-thumbnail'>"
			. "<img title='$name' alt='$name' src='$img_url' />"
			. "</div>"
            . "<div class='trading-card-text'>"
			. "<span class='trading-card-title'>$name</span>";
        if( isset($card['subname']) && $card['subname'] != '' ) {
            $content .= "<span class='trading-card-subtitle'>" . $card['subname'] . "</span>";
        }
		$content .= "</div></a></div></div>";
	}
	$content .= "</div>";

	return array('stat'=>'ok', 'content'=>$content);
}
?>