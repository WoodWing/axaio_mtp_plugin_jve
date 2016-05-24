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
		$req = $req; // keep code analyzer happy
	}

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp ) 
	{
		require_once dirname(__FILE__).'/util/ElvisSessionUtil.php';
		require_once dirname(__FILE__).'/util/ElvisUtils.class.php';
		
		if (ElvisUtils::isInDesignServer()) {
			// HACK LVS-3497: For InDesign server, use SUPERUSER for logging into Elvis, ignoring 
			// the current user. This hack is required as InDesign Server performs a special 
			// forked login only specifying the username, not the password. Which means we cannot
			// login the normal user. Super user has full access (no restriction).
			ElvisSessionUtil::saveCredentials( ELVIS_SUPER_USER, ELVIS_SUPER_USER_PASS );
			ElvisSessionUtil::setSessionVar( 'restricted', false );
		}
		else {
			$this->setUserType( $req->User, $req->Password );
		}

		// Pass the ElvisServerUrl option in the feature set, as used by Content Station.
		// When a client requests for 'ticket only' the feature set is -not- provided by 
		// the core server, so we need to handle with care.
		if( isset($resp->ServerInfo->FeatureSet) ) {
			// Add the feature to the feature set of the logon response.
			require_once dirname(__FILE__).'/config.php'; // get ELVIS_CLIENT_URL
			$feature = new Feature( 'ElvisServerUrl', ELVIS_CLIENT_URL );
			array_push( $resp->ServerInfo->FeatureSet, $feature );
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
	 *
	 * @param string $user Acting user.
	 * $param string $password Password of the acting user.
	 * @throws BizException
	 */
	private function setUserType( $user, $password )
	{
		ElvisSessionUtil::saveCredentials( $user, $password );

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
		$req = $req; // keep code analyzer happy
	}
}
