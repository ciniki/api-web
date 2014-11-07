<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the collection image to.
//
// Returns
// -------
//
function ciniki_web_collectionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'collection_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Collection'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'checkAccess');
    $rc = ciniki_web_checkAccess($ciniki, $args['business_id'], 'ciniki.web.collectionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Load the object
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectGet');
	$rc = ciniki_core_objectGet($ciniki, $args['business_id'], 'ciniki.web.collection', $args['collection_id']);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2058', 'msg'=>'Unable to find the collection image you requested.', 'err'=>$rc['err']));
	}
	$collection = $rc['collection'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'collectionObjSettingsGet');
	$rc = ciniki_web_collectionObjSettingsGet($ciniki, $args['business_id'], $args['collection_id']);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$collection = array_merge($collection, $rc['settings']);
	}

	return array('stat'=>'ok', 'collection'=>$collection);
}
?>
