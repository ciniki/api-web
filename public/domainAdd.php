<?php
//
// Description
// -----------
// This method will add a new domain to a business.  
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_web_domainAdd($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'domain'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No domain specified'), 
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'errmsg'=>'No flags specified'), 
		'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'errmsg'=>'No status specified'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/web/private/checkAccess.php');
	$ac = ciniki_web_checkAccess($ciniki, $args['business_id'], 'ciniki.web.domainAdd');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Start transaction
	//
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'web');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add site to web_sites table
	// FIXME: Add ability to set modules when site is added, right now default to most apps on
	//
	$strsql = "INSERT INTO ciniki_web_domains (business_id, "
		. "domain, flags, status, "
		. "date_added, last_updated ) VALUES ( "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['domain']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'web');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'web');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'web');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'619', 'msg'=>'Unable to add domain'));
	}
	$domain_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'domain',
		'flags',
		'status',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddChangeLog($ciniki, 'web', $args['business_id'], 
				'ciniki_web_domains', $domain_id, $field, $args[$field]);
		}
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'web');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$domain_id);
}
?>
