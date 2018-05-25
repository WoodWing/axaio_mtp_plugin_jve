<?php

/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';

class iPhone_WflGetObjects extends WflGetObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflGetObjectsRequest &$req )
	{
		$req=$req;
		// not called
	}
	
	final public function runAfter( WflGetObjectsRequest $req, WflGetObjectsResponse &$resp ) 
	{
		// Only do this for iPhone clients:
		$app = DBTicket::DBappticket( BizSession::getTicket() );
		if( $app == 'iPhone' ) {
			LogHandler::Log( 'iPhone', 'DEBUG', 'Filtering output for iPhone app' );

			// Remove all stuff not needed by iPhone app.
			// Note: files is most important
			foreach( $resp->Objects as $object ) {
				foreach( $object->Pages as $page ) {
					require_once dirname(__FILE__) . '/config.php';
					require_once BASEDIR.'/server/utils/ImageUtils.class.php';
					$preview = '';  $ret=false;
					$ret = ImageUtils::ResizeJPEG( MAXRESOLUTION, 			// max for both width/height
												   $page->Files[0]->FilePath,	$page->Files[0]->FilePath,
												   80, 				// quality
												   null, null 		// height, width max
											   );
				}
				
				$object->Files = null;
				$object->MetaData->ContentMetaData	= null;
				$object->MetaData->RightsMetaData	= null;
				$object->MetaData->SourceMetaData	= null;
				$object->MetaData->WorkflowMetaData	= null;
				$object->Relations = null;
				$object->Targets = null;
				$object->Messages = null;
			}
		}
	}
	
	final public function runOverruled( WflGetObjectsRequest $req ) 
	{
		$req=$req;
		// not called
	}
}
