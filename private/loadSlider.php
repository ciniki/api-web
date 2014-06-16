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
function ciniki_web_loadSlider(&$ciniki, $settings, $business_id, $slider_id) {

	$strsql = "SELECT ciniki_web_sliders.id, "
		. "ciniki_web_sliders.size, "
		. "ciniki_web_sliders.effect, "
		. "ciniki_web_slider_images.id AS slider_image_id, "
		. "ciniki_web_slider_images.image_id, "
		. "ciniki_web_slider_images.caption, "
		. "ciniki_web_slider_images.url "
		. "FROM ciniki_web_sliders "
		. "LEFT JOIN ciniki_web_slider_images ON ("
			. "ciniki_web_sliders.id = ciniki_web_slider_images.slider_id "
			. "AND ciniki_web_slider_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_web_sliders.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_web_sliders.id = '" . ciniki_core_dbQuote($ciniki, $slider_id) . "' "
		. "ORDER BY ciniki_web_slider_images.sequence "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.web', array(
		array('container'=>'sliders', 'fname'=>'id',
			'fields'=>array('size', 'effect')),
		array('container'=>'images', 'fname'=>'slider_image_id',
			'fields'=>array('image_id', 'caption', 'url')),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}

	if( !isset($rc['sliders'][$slider_id]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1763', 'msg'=>'Slider not found'));
	}

	return array('stat'=>'ok', 'slider'=>$rc['sliders'][$slider_id]);
}
?>
