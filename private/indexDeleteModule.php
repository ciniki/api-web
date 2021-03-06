<?php
//
// Description
// -----------
// This function will add/modify/delete an object in the web index.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function ciniki_web_indexDeleteModule(&$ciniki, $tnid, $module) {

    //
    // Get the current index data
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_web_index "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND object LIKE '" . ciniki_core_dbQuote($ciniki, $module) . ".%' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.web', 'object');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.web.index', $row['id'], $row['uuid'], 0x07);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
