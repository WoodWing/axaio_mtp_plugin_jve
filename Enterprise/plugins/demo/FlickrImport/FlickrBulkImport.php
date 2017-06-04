<?php

// Init
require_once '../../../config/config.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
require_once dirname(__FILE__) . '/configImport.php';
require_once dirname(__FILE__) . '/FlickrImport.class.php';
$session = array();
require_once BASEDIR.'/server/protocols/soap/AdmClient.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
$admClient = new WW_SOAP_AdmClient();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// LogOn
try
{
	require_once BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php';
	$logon = new AdmLogOnRequest();
	$logon->AdminUser 		= FLICKR_WW_USERNAME;
	$logon->Password 		= FLICKR_WW_USERPWD;
	$logon->ClientName 		= 'Flickr Importer';
	$logon->ClientAppName 	= 'Flickr Bulk Import'; 
	$logon->ClientAppVersion = 'v'.SERVERVERSION;
	$return = $admClient->logOn( $logon );
	$session['ticket'] = $return->Ticket;
	
} catch( SoapFault $e ){
	$msg = $e->getMessage();
	throw new BizException( '', 'Server', $msg, $msg );
}

try
{
	require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php';
	$Publication 			= new AdmGetPublicationsRequest();
	$Publication->Ticket 	= $session['ticket'];
	$Publication->RequestModes = array();

	$resp = $admClient->GetPublications( $Publication );
	foreach( $resp->Publications as $Pub ) {
		if( $Pub->Name == FLICKR_WW_BRAND ) {
			$session['Publication'] = $Pub;
			break;
		}
	}
	if( $session['Publication'] ) {
		require_once BASEDIR.'/server/interfaces/services/adm/AdmGetSectionsRequest.class.php';
		$Section				= new AdmGetSectionsRequest();
		$Section->Ticket 		= $session['ticket'];
		$Section->RequestModes	= array();
		$Section->IssueId		= null;
		$Section->PublicationId = $session['Publication']->Id;

		$resp = $admClient->GetSections( $Section );
		foreach( $resp->Sections as $section ) {
			if( $section->Name == FLICKR_WW_CATEGORY) {
				$session['Section'] = $section;
				break;
			}
		}
		if( $session['Section'] ) {
			require_once BASEDIR.'/server/protocols/soap/WflClient.php';
			$wflClient = new WW_SOAP_WflClient();

			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesRequest.class.php';
			$state 				= new WflGetStatesRequest();
			$state->Ticket 		= $session['ticket'] ;
			$state->Publication = $session['Publication'];
			$state->Section		= $session['Section'];
			$state->Type		= 'Image';
			$resp = $wflClient->GetStates( $state );
		
			if( $resp->States ) {
				foreach( $resp->States as $status) {
					if( $status->Name == FLICKR_WW_STATUS ) {
						$session['State'] = $status;
						break;
					}
				}
				if( !$session['State'] ) {
					$msg = 'Defined State not exists';
					throw new BizException( '', 'Server', $msg, $msg );
				}
			}
		}
		else {
			$msg = 'Defined Category not exists';
			throw new BizException( '', 'Server', $msg, $msg );
		}
	}
	else {
		$msg = 'Defined Publication not exists';
		throw new BizException( '', 'Server', $msg, $msg );
	}
} catch ( SoapFault  $e ){
	$msg = $e->getMessage();
	throw new BizException( '', 'Server', $msg, $msg );
}

$min_upload_date = null;

// Query the latest modified date of Flickr photo
$queryParams = array();
$queryParams[] = new QueryParam ('PublicationId', 	'=', $session['Publication']->Id);
$queryParams[] = new QueryParam ('SectionId', 		'=', $session['Section']->Id);
$queryParams[] = new QueryParam ('Source', 			'=', FLICKR_SOURCEID);
$queryParams[] = new QueryParam ('Type', 			'=', 'Image');

$reqProps = array('ID', 'Modified');
//$reqPropKeys = array_flip($reqProps);

require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
$service = new WflQueryObjectsService();
$resp = $service->execute( new WflQueryObjectsRequest( 
							$session['ticket'], 
							$queryParams, 
							null,
							1,
							null,
							BizQuery::getQueryOrder( 'Modified', 'desc' ), // Order
							null,
							$reqProps) );

if( isset($resp->Rows) && count($resp->Rows)> 0) {
	$min_upload_date = $resp->Rows[0][1];
}
else {
	$min_upload_date = FLICKR_UPLOAD_DATE . 'T00:00:00';
}
							
$Flickr = new FlickrImport($session['ticket'], $session['Publication']->Id, $session['Section']->Id, $session['State']->Id);

$Flickr->search($min_upload_date);


