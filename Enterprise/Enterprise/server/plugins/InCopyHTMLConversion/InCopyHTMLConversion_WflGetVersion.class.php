<?php

/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetVersion_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/appservices/textconverters/InCopyTextUtils.php';

class InCopyHTMLConversion_WflGetVersion extends WflGetVersion_EnterpriseConnector
{

	final public function getPrio ()
	{
		return self::PRIO_DEFAULT;
	}

	final public function getRunMode ()
	{
		return self::RUNMODE_AFTER;
	}

	final public function runBefore( WflGetVersionRequest &$req ) {}
	
	final public function runOverruled( WflGetVersionRequest $req ) {}

	final public function runAfter( WflGetVersionRequest $req, WflGetVersionResponse &$resp )
	{
		// Not asked for rendition, means nothing to do (BZ#10948)
		if( $resp->VersionInfo->File && $resp->VersionInfo->File->Rendition == 'none' ) {
			return;
		}
		
		if (isset($resp->VersionInfo->Object) && strlen($resp->VersionInfo->Object) > 0) {
			// check if requesting application is InCopy or InDesign
			$app = DBTicket::DBappticket($req->Ticket);
			LogHandler::Log('InCopyHTMLConversion', 'DEBUG', 'Requesting application is ' . $app );
			if ( stristr($app, 'incopy') || stristr($app, 'indesign') ) {
				// Get the current version object, where the object properties is needed in the later conversion
				require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
				$objReq 	= new WflGetObjectsRequest( $req->Ticket, array($req->ID), false, 'native', null );
				$service 	= new WflGetObjectsService();
				$objResp 	= $service->execute( $objReq );
				$objects 	= $objResp->Objects;
				$object 	= $objects[0];
				if(!is_null($object)) {
					// check for article
					if( $object->MetaData->BasicMetaData->Type == 'Article' ) {
						$format = $resp->VersionInfo->File->Type;
						// find the right converter for each supported format
						switch( $format ) {
							case 'text/html':
							case 'text/wwea':
								// Set the current object format to html format
								$object->MetaData->ContentMetaData->Format = $format;
								// Set the attachment to the object version attachment which in html format
								$wweaPath = $resp->VersionInfo->File->FilePath;
								$object->Files[0]->FilePath = $wweaPath;

								require_once dirname(__FILE__) . '/InCopyHTMLConversion.class.php';
								InCopyHTMLConversion::convertHTMLArticle( $object );
								LogHandler::Log('InCopyHTMLConversion', 'DEBUG', 'Article has been converted' );
								
								// BZ#18602 - Set the converted InCopy attachment as the GetVersion response attachment,
								// and InCopy will able to show the article without crash.
								$resp->VersionInfo->File = $object->Files[0];
							break;
							
							default:
								return;
							break;
						}
					}
				}
			}
			else {
				LogHandler::Log('SERVER', 'INFO', 'Article is not converted. Unsupported application: '.print_r($app, true).'. Please use InCopy, InDesign or InDesign Server ' );
			}
		}
	}
}
