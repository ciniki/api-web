<?php
//
// Description
// -----------
// This function will lookup the client domain in the database, and return the business id.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_web_settings($ciniki, $business_id) {
	//
	// Load settings from the database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');
	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'web', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['settings']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'622', 'msg'=>'No settings found, site not configured.'));
	}
	$settings = $rc['settings'];

	//
	// Make sure the required defaults have been set
	//
	if( !isset($settings['site-layout']) || $settings['site-layout'] == '' ) {
		if( isset($ciniki['config']['web']['default-layout']) && $ciniki['config']['web']['default-layout'] != '' ) {
			$settings['site-layout'] = $ciniki['config']['web']['default-layout'];
		} else {
			$settings['site-layout'] = 'default';
		}
	}
	if( !isset($settings['site-theme']) || $settings['site-theme'] == '' ) {
		if( isset($ciniki['config']['web']['default-theme']) && $ciniki['config']['web']['default-theme'] != '' ) {
			$settings['site-theme'] = $ciniki['config']['web']['default-theme'];
		} else {
			$settings['site-theme'] = 'default';
		}
	}
	
	return array('stat'=>'ok', 'settings'=>$settings);
}
?>
