<?php
/**
 * Hooks into the LogOn workflow web service.
 * Called when an end-user does logon to Enterprise (typically using SC or CS).
 *
 * @since      4
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class Elvis_WflLogOn extends WflLogOn_EnterpriseConnector {

	final public function getPrio() { return self::PRIO_DEFAULT; }
	final public function getRunMode() { return self::RUNMODE_AFTER; }

	// Not called.
	final public function runBefore( WflLogOnRequest &$req )
	{
	}

	/**
	 * @inheritdoc
	 */
	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp ) 
	{
		require_once BASEDIR.'/config/config_elvis.php'; // auto-loading

		if( $req->User && $req->Password ) {
			// Only users known to Elvis are allowed to edit native Elvis files. Other users are restricted to read-only.
			// The user who is about to logon could be added to (or removed from) Elvis by the system administrator.
			// When that happens, the user may (or may not) be logged on to Enterprise and he/she will find out that the
			// restriction is still in place. Their first reaction will be to re-login and so the logon will be be called.
			// In this logon hook, we take that moment to forget whether or not this Enterprise user is unknown to Elvis
			// and to clear the restriction. Doing so, the Enterprise username will initially be used to authorize the
			// trusted backend connection. That works if the user is/became known to Elvis. If that fails, the restricted
			// fallback user (ELVIS_DEFAULT_USER) will be used as fallback for which the restricted flag will be raised again.
			Elvis_BizClasses_UserSetting::clearRestricted();
			// L> since 10.1.4 this setting is no longer stored in the PHP session but in the DB instead [EN-89334].
			Elvis_DbClasses_Token::delete( BizSession::getShortUserName() );
			// At the first modify ( property or asset ) action, the 'cache' is refreshed, EN-90272.
			require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
			DBUserSetting::deleteSettingsByName( BizSession::getShortUserName(), 'ElvisContentSource', array( 'EditableFields' ) );
		}

		// Add the feature to the feature set of the logon response.
		// When a client requests for 'ticket only' the feature set is -not- provided by
		// the core server, so we need to handle with care.
		if( isset($resp->ServerInfo->FeatureSet) ) {
			require_once BASEDIR.'/config/config_elvis.php'; // Load the Elvis settings
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
