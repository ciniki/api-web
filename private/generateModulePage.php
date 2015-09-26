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
function ciniki_web_generateModulePage($ciniki, $settings, $business_id, $module) {

	//
	// Process the module request
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processModuleRequest');
	$rc = ciniki_web_processModuleRequest($ciniki, $settings, $ciniki['request']['business_id'], $module, 
		array(
			'uri_split'=>$ciniki['request']['uri_split'],
			'base_url'=>$ciniki['request']['base_url'] . '/' . $ciniki['request']['page'],
			'domain_base_url'=>$ciniki['request']['domain_base_url'] . '/' . $ciniki['request']['page'],
			'page_title'=>'',
			'article_title'=>'',
			'breadcrumbs'=>array(),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$pg = $rc;

	//
	// Generate the page
	//
	$content = '';

	//
	// Add the header
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'generatePageHeader');
	$rc = ciniki_web_generatePageHeader($ciniki, $settings, 
		(isset($pg['page_title'])?$pg['page_title']:''),
		(isset($pg['submenu'])?$pg['submenu']:array())
		);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$content .= $rc['content'];

	//
	// Build the page content
	//
	$content .= "<div id='content'>\n";

	if( isset($pg['content']) && $pg['content'] != '' ) {
		$content .= $pg['content'];
	}

	$content .= "</div>";

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