<?php

require_once dirname(__FILE__).'/../../config.php';
require_once dirname(__FILE__).'/GettyImages_ContentSource.class.php';

// get param's
$ticket	= isset($_REQUEST['ticket'])? $_REQUEST['ticket'] : 0;
$ids	= isset($_REQUEST['ids'])	? $_REQUEST['ids'] : '';
$idArray = explode( ",", $ids );

// Get the first object id from the ids array, ignore the rest
$id = $idArray[0];

if( strpos( $id, GI_CONTENTSOURCEPREFIX ) === false ) { // Enterprise object
	try {
		// Perform GetObject on the object ID to retrieve the Getty Image ID from MetaData->BasicMetaData->DocumentID
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest( $ticket, array($id), false, 'thumb', array() );
		$service = new WflGetObjectsService();
	
		$response = $service->execute( $request );
		if( $response->Objects ) {
			$object = $response->Objects[0];
			$gettyImageId = $object->MetaData->BasicMetaData->DocumentID;
			if( empty($gettyImageId) ) {
				throw new BizException( '', 'Server', '', 'Getty Image Id not found.' );
			} else {
				header( 'Location: http://www.gettyimages.com/detail/' . $gettyImageId );
			}
		}
	} catch( BizException $e ) {
		print $e->getMessage();
	}
} else { // Alien object
	$gettyImageId = substr( $id, strlen(GI_CONTENTSOURCEPREFIX) );
	if( empty($gettyImageId) ) {
		throw new BizException( '', 'Server', '', 'Getty Image Id not found.' );
	} else {
		header( 'Location: http://www.gettyimages.com/detail/' . $gettyImageId );
	}
}



