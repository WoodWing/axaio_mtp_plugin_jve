<?php
/**
 * @since      4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Hooks into the LogOn workflow web service.
 * Called when an end-user does logon to Enterprise (typically using SC or CS).
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class Elvis_WflLogOn extends WflLogOn_EnterpriseConnector {

	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_AFTER; }
	
	// Not called.
	final public function runBefore( WflLogOnRequest &$req )
	{
	}

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp ) 
	{
		if( $req->User && $req->Password ) {
			require_once __DIR__.'/util/ElvisSessionUtil.php';
			ElvisSessionUtil::setRestricted( false );
			// L> since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
		}

		// Add the feature to the feature set of the logon response.
		// When a client requests for 'ticket only' the feature set is -not- provided by
		// the core server, so we need to handle with care.
		if( isset($resp->ServerInfo->FeatureSet) ) {
			require_once __DIR__.'/config.php'; // Load the Elvis settings
			// Pass the ElvisServerUrl option in the feature set, as used by Content Station.
			$resp->ServerInfo->FeatureSet[] = new Feature( 'ElvisServerUrl', ELVIS_CLIENT_URL );

			// Pass the ImageRestoreLocation option in the feature set, as used by Smart Connection.
			if( IMAGE_RESTORE_LOCATION == 'Elvis_Copy' && ELVIS_CREATE_COPY == 'Copy_To_Production_Zone' ) {
				// For this specific configuration combination, we 'ask' SC to simply to create a shadow object(*),
				// but skip explicitly making a copy by itself. This is needed because the Elvis content connector
				// makes a copy to the Production Zone already. In this situation, SC will not raise a dialog and
				// ask the user to fill in the Elvis folder to copy to but simply leaves it up the the connector.
				// In fact, we fool SC to keep the logic server side as much as possible. [EN-88325]
				// (*) Note that the Elvis content source connector acts on the CreateObjectRelations service as called by SC
				//     when e.g. placing an image on a layout.
				$imageRestoreLocation = 'Elvis_Original';
			} else {
				$imageRestoreLocation = IMAGE_RESTORE_LOCATION;
			}
			$resp->ServerInfo->FeatureSet[] = new Feature( 'ImageRestoreLocation', $imageRestoreLocation );

			// Pass the Elvis Create Copy setting to Elvis, for backwards compatibility with Elvis AIR Client (EN-88464)
			$resp->ServerInfo->FeatureSet[] = new Feature( 'ElvisCreateCopy', ELVIS_CREATE_COPY );
		}
	}

	// Not called.
	final public function runOverruled( WflLogOnRequest $req ) 
	{
	}
}
