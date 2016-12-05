<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/appservices/textconverters/InCopyTextUtils.php';

class InCopyHTMLConversion_WflGetObjects extends WflGetObjects_EnterpriseConnector
{
	final public function getPrio ()
	{
		return self::PRIO_DEFAULT;
	}

	final public function getRunMode ()
	{
		return self::RUNMODE_AFTER;
	}

	final public function runBefore( WflGetObjectsRequest &$req ) {}
	
	final public function runOverruled( WflGetObjectsRequest $req ) {}

	final public function runAfter (WflGetObjectsRequest $req, WflGetObjectsResponse &$resp)
	{
		// Not asked for rendition, means nothing to do (BZ#10948)
		if( $req->Rendition == 'none' ) {
			return;
		}
		
		if (isset($resp->Objects) && count($resp->Objects) > 0) {
			// check if requesting application is InCopy or InDesign	
			$app = DBTicket::DBappticket($req->Ticket);
			LogHandler::Log('InCopyHTMLConversion', 'DEBUG', 'Requesting application is ' . $app );
			if ( stristr($app, 'incopy') || stristr($app, 'indesign') ) {
				foreach ($resp->Objects as &$object) {
					// check for article
					if( $object->MetaData->BasicMetaData->Type == 'Article' ) {
						$format = $object->MetaData->ContentMetaData->Format;
						// find the right converter for each supported format
						switch( $format ) {
							case 'text/html':
							case 'text/wwea':
								require_once dirname(__FILE__) . '/InCopyHTMLConversion.class.php';
								InCopyHTMLConversion::convertHTMLArticle( $object );
								LogHandler::Log('InCopyHTMLConversion', 'DEBUG', 'Article has been converted' );
								break;
							
							default:
								return;
							break;
						}
					}
				}
			}
			else {
				LogHandler::Log('InCopyHTMLConversion', 'INFO', 'Article is not converted. Unsupported application: '.print_r($app, true).'. Please use InCopy, InDesign or InDesign Server ' );
			}
		}
	}
}
