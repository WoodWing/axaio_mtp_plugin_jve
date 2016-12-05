<?php
/**
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v7.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class AdobeDps_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( WflCreateObjectsRequest &$req )
	{		
		require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
		
		if( $req->Objects ) foreach( $req->Objects as $object ) {			
			// Check if the object is a widget and the manifest isn't already set (the DPS plugin can also set this property)
			if ( AdobeDpsUtils::checkIfObjectIsWidget( $object ) && AdobeDpsUtils::isManifestSet( $object ) == false ) {
				// If it is a widget extract the manifest file
				AdobeDpsUtils::extractManifestFromWidget( $object );
			}
		}
	}

	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp )
	{
		// For dossiers targetted to DPS channels, add the dossier (id) to the dossier ordering.
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			$objectType = $object->MetaData->BasicMetaData->Type;
			if( $objectType == 'Dossier' ) {
				foreach( $object->Targets as $target ) {
					require_once BASEDIR.'/server/dbclasses/DBChannel.class.php';
					$pubChannel = DBChannel::getPubChannelObj( $target->PubChannel->Id );
					if( $pubChannel->Type == 'dps' ) {
						include_once BASEDIR.'/server/bizclasses/BizPubIssue.class.php'; 
						$bizPubIssue = new BizPubIssue();
						$bizPubIssue->suppressErrors(); // we are in 'after' and ordering is self-repairing
						$dossierId = $object->MetaData->BasicMetaData->ID;
						$bizPubIssue->addDossierToOrder( $target->Issue->Id, $dossierId );

						// Fix then order of sections when needed
						require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
						AdobeDpsUtils::fixSectionDossierOrder( $target->Issue->Id );
					}
				}
			} elseif( $objectType == 'Other' && $object->MetaData->ContentMetaData->Format == 'application/vnd.adobe.folio+zip' ) {
				AdobeDpsUtils::extractPreviewsFromFolio( $object ); // Extract preview file from folio file
			}
		}
	}

	final public function runOverruled( WflCreateObjectsRequest $req ) {}
}
