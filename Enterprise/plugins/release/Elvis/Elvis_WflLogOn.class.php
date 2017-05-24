<?php
/**
 * @package    Elvis
 * @subpackage ServerPlugins
 * @since      v4
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
		require_once dirname(__FILE__).'/util/ElvisSessionUtil.php';
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		
		if (ElvisUtils::isInDesignServer()) {
			require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
			$actingUser = BizSession::getShortUserName();
			$usernamePassword = ElvisSessionUtil::retrieveUsernamePasswordFromCredentials( $actingUser );
			if( !$usernamePassword ) {
				// When the credentials are not found, fall back to super user.
				// This should be very theoretical: When an IDS job is created and exists in the queue, meaning
				// the user should have already logged into Enterprise before to trigger the job creation in the queue.
				// Therefore the credentials should be available in the smart_settings table, but just in case ( for
				// some reason ) the credentials are not found, fall back to super user.
				ElvisSessionUtil::saveCredentials( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
				ElvisSessionUtil::setSessionVar( 'restricted', false );
				LogHandler::Log( __CLASS__, 'INFO', 'Preparing Elvis credentials ( for IDS ): '.
					'No credentials found for acting user '.$actingUser.', using Elvis superuser:' . ELVIS_SUPER_USER );
			} else {
				LogHandler::Log( __CLASS__, 'INFO', 'Preparing Elvis credentials ( for IDS ): '.
					'Using credentials from acting user '.$actingUser .' (elvisusername=' . $usernamePassword[0] .')');
			}
		}
		else {
			$this->setUserType( $req->User, $req->Password );
		}

		// Add the feature to the feature set of the logon response.
		// When a client requests for 'ticket only' the feature set is -not- provided by
		// the core server, so we need to handle with care.
		if( isset($resp->ServerInfo->FeatureSet) ) {
			require_once dirname(__FILE__).'/config.php'; // Load the Elvis settings
			// Pass the ElvisServerUrl option in the feature set, as used by Content Station.
			$feature = new Feature( 'ElvisServerUrl', ELVIS_CLIENT_URL );
			array_push( $resp->ServerInfo->FeatureSet, $feature );

			$imageRestoreLocation = new Feature( 'ImageRestoreLocation', IMAGE_RESTORE_LOCATION );
			array_push( $resp->ServerInfo->FeatureSet, $imageRestoreLocation );
		}
	}

	/**
	 * There are two types of users. Users who are known within Enterprise and within Elvis and those only know
	 * within Enterprise. The last group borrows the credentials from the configured super user to log on to Elvis.
	 * These users get the same rights as the super user but there is one exception. They are not allowed to open Elvis
	 * objects to edit them. To make this distinction a session variable is set, 'restricted = true'.
	 * Users who are able to log on to Elvis with their own credentials are not restricted. Their rights are checked
	 * by the Elvis application. See EN-36871.
	 * If the log on fails ultimately the session variable with the credentials is set as if the user is an Elvis user.
	 * Only if both log on attempts fail a warning is logged.
	 * Note that the password can be empty!!! This is the case if log on is initiated by a third application. Example:
	 * - Open a layout from within Content Station.
	 * - InDesign is started and the user is logged on to InDesign without password.
	 * In this case the logon request of InDesign contains the ticket issued to Content Station. This ticket is used
	 * to validate the user and issue a new ticket for the InDesign application. But as the user is already logged on
	 * to the third application (Content Station) the password can be retrieved from the credentials stored during that
	 * logon process. See also EN-88533.
	 *
	 * @param string $user Acting user.
	 * $param string $password Password of the acting user.
	 * @throws BizException
	 */
	private function setUserType( $user, $password )
	{
		if( !$password ) {
			$usernamePassword = ElvisSessionUtil::retrieveUsernamePasswordFromCredentials( $user );
			if( $usernamePassword ) {
				$username = $usernamePassword[0];
				$password = $usernamePassword[1];
				LogHandler::Log( __CLASS__, 'INFO',
					'No password supplied. Retrieved password from previously stored credentials for acting user '.$user.': ' .
					"(elvisusername={$username})" );
				ElvisSessionUtil::saveCredentials( $username, $password );
			} else {
				LogHandler::Log( __CLASS__, 'WARN', 'No password supplied. Continue without password.' );
			}
		} else {
			ElvisSessionUtil::saveCredentials( $user, $password );
		}

		try {
			require_once dirname(__FILE__).'/logic/ElvisAMFClient.php';
			$map = new BizExceptionSeverityMap( array( 'S1053' => 'INFO' ) ); // Suppress warnings for the HealthCheck.
			ElvisAMFClient::login();
			ElvisSessionUtil::setSessionVar( 'restricted', false );
		} catch ( BizException $e ) {
			LogHandler::Log( __CLASS__, 'INFO', 'Log on to Elvis Content Source with normal user credentials failed.');
			ElvisSessionUtil::saveCredentials( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
			try {
				LogHandler::Log( __CLASS__, 'INFO', 'Try to log on to Elvis Content Source with super user credentials.');
				ElvisAMFClient::login();
				LogHandler::Log( __CLASS__, 'INFO', 'Log on to Elvis Content Source with super user credentials is successful.');
				ElvisSessionUtil::setSessionVar( 'restricted', true );
				LogHandler::Log( __CLASS__, 'INFO', 'Access rights of the user for Elvis are set to restricted.');
			} catch ( BizException $e ) {
				LogHandler::Log( __CLASS__, 'WARN', 'Log on to Elvis Content Source failed.');
				LogHandler::Log( __CLASS__, 'WARN', 'Please check your configuration and run the Health Check .');
				ElvisSessionUtil::saveCredentials( $user, $password );
				ElvisSessionUtil::setSessionVar( 'restricted', false );
			}
		}
	}

	// Not called.
	final public function runOverruled( WflLogOnRequest $req ) 
	{
	}
}
