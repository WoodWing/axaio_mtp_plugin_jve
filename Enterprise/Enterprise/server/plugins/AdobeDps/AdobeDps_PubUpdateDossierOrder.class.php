<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/services/pub/PubUpdateDossierOrder_EnterpriseConnector.class.php';

class AdobeDps_PubUpdateDossierOrder extends PubUpdateDossierOrder_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( PubUpdateDossierOrderRequest &$req ) {}

	final public function runAfter( PubUpdateDossierOrderRequest $req, PubUpdateDossierOrderResponse &$resp )
	{
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		$pubChannel = DBChannel::getPubChannelObj( $req->Target->PubChannelID );
		if( $pubChannel->Type == 'dps' ) {
			// Try to fix the order when using sections
			require_once dirname(__FILE__).'/Utils/AdobeDpsUtils.class.php';
			$newOrder = AdobeDpsUtils::fixSectionDossierOrder( $req->Target->IssueID );
			if ( $newOrder ) {
				// When the order is updated, send this to the client
				$resp->DossierIDs = $newOrder;
			}
		}
	}

	final public function runOverruled( PubUpdateDossierOrderRequest $req ) {}
}
