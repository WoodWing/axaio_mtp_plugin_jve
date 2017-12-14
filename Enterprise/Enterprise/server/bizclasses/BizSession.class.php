<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizServices
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * There are PHP sessions and web service ‘sessions', which are quite different.
 * A PHP session is started to handle an incoming service request. However,
 * that request might want to call another service request. If that request is
 * fired with the help of a HTTP client, another PHP session is started, and
 * handled in the very same manner. But, if a service implementation calls
 * another service -directly- (by simply calling the service layer), that
 * service is handled within the very same PHP session. This session class
 * allows you to do so, whereby service sessions are ‘stacked' onto each other
 * (with help of the $sessionCounter and $sessionCache). However, this is only
 * allowed as long as the ticket is the same, which is to avoid over complicating
 * the session class, because the same ticket implies that the acting user is the
 * same and so the access rights model is the same. Nevertheless, the service name
 * differs and it therefore pushed onto the stack.
 */

class BizSession
{
	private static $userRow;
	private static $userName;	// Set after logon and when ticket is checked
	/**
	 * BZ#22501.
	 * To keep track how many sessions are running.
	 * This is to ensure that session ticket is not cleared as long as the
	 * same session is still running ($sessionCounter >0)
	 *
	 * @var int
	 */
	private static $sessionCounter = 0;
	/**
	 * Indicates if a BizSession session has been started
	 *
	 * @var bool
	 */
	private static $sessionStarted = false;
	/**
	 * Current session ticket
	 *
	 * @var string
	 */
	private static $ticket = '';
	/**
	 * A stack that holds web service session information. See module header for more info.
	 * Each session is tracked with the $sessionCounter, when endSession() is called,
	 * the stack that holds the 'going-to-end' session information should be cleared.
	 *
	 * Currently, it only has 'servicename' information.
	 *
	 * @var array
	 */
	private static $sessionCache = array();
	/**
	 * The namespace used by BizSession in $_SESSION
	 *
	 */
	const SESSION_NAMESPACE = 'WW_Biz_Session';

	// These run mode options match with DefaultConnector:
	const RUNMODE_SYNCHRON    = 'Synchron';
	const RUNMODE_BACKGROUND  = 'Background';
	static private $runMode = self::RUNMODE_SYNCHRON;

	/**
	 * @var bool
	 */
	static private $directCommit = false;

	/**
	 * getShortUserName
	 *
	 * @return string	short user name
	 */
	public static function getShortUserName()
	{
		return self::$userName;
	}

	/**
	 * Returns the session workspace
	 *
	 * @return string
	 */
	public static function getSessionWorkspace()
	{
		if ( self::$sessionStarted ) {
			$workspace = SESSIONWORKSPACE . '/' . self::$ticket . '/';
			require_once BASEDIR . '/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir($workspace);
			return $workspace;
		}

		return "";
	}

	/**
	 * Purges the session workspace folders for the given tickets.
	 *
	 * @param array $purgedTickets
	 * @return void
	 */
	public static function purgeSessionWorkspaces( $purgedTickets )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';

		foreach( $purgedTickets as $purgedTicket ) {
			$dir = SESSIONWORKSPACE . '/' . $purgedTicket . '/';
			if ( file_exists($dir) ) {
				FolderUtils::cleanDirRecursive($dir, true);
			}
		}
	}

	/**
	 * Get user's info value given the info key ($key).
	 *
	 * @param string $key Property to get, can be: 'id', 'user', 'fullname', 'pass', 'disable', 'email' etc. see smart_users
	 * @throws BizException
	 * @return string
	 *
	 */
	public static function getUserInfo( $key )
	{
		// get user info from DB if we don't have it yet
		if( !isset(self::$userRow) || self::getShortUserName() != self::$userRow['user'] ) { // EN-84724
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$row = DBUser::getUser( self::getShortUserName() );
			if( !$row ) { // Whether FALSE (empty) or NULL (error), bail out if we couldn't get user info:
				throw new BizException( 'ERR_NOTFOUND', 'Client', self::getShortUserName() );
			}
			self::$userRow = $row;
		}
		switch( $key )
		{
			case 'language':
			case 'pass':
			case 'startdate':
			case 'enddate':
			case 'expirepassdate':
			case 'disabled':
			case 'email':
			case 'emailgrp':
			case 'emailusr':
			case 'fixedpass':
				return trim( self::$userRow[$key]);
			default:
				return self::$userRow[$key];
		}
	}

	/**
	 * Returns the User who is currently logged in.
	 *
	 * @throws BizException
	 * @return User
	 */
	public static function getUser()
	{
		// get user info from DB if we don't have it yet
		if( !isset(self::$userRow) || self::getShortUserName() != self::$userRow['user'] ) { // EN-84724
			require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
			$row = DBUser::getUser( self::getShortUserName() );
			if( !$row ) { // Whether FALSE (empty) or NULL (error), bail out if we couldn't get user info:
				throw new BizException( 'ERR_NOTFOUND', 'Client', self::getShortUserName() );
			}
			self::$userRow = $row;
		}

		// Remove the # prefix from color.
		$trackChangesColor = self::$userRow['trackchangescolor'];
		if( strlen( $trackChangesColor ) > 0 ) {
			$trackChangesColor = substr( $trackChangesColor, 1 );
		} else { // when not available, will assign with the default one. 
			self::$userRow['trackchangescolor'] = DEFAULT_USER_COLOR;
			$trackChangesColor = substr( DEFAULT_USER_COLOR, 1 );
		}

		// Build the User object from DB row.
		$user = new User();
		$user->UserID = self::$userRow['user'];
		$user->FullName = self::$userRow['fullname'];
		$user->TrackChangesColor = $trackChangesColor;
		$user->EmailAddress = self::$userRow['email'];
		return $user;
	}

	/**
	 * Check the username and password
	 * Generate an exception in case the user can not be validated
	 * Call this method BEFORE calling logon()!
	 *
	 * @param string $user Can be the full name or the short user id
	 * @param string $password
	 * @throws BizException
	 * @return string Short user id is returned (even when fullname ($user) has been passed)
	 */
	public static function validateUser( $user, $password )
	{
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		require_once BASEDIR.'/server/bizclasses/BizLDAP.class.php';

		// Decode the user typed password
		// Although server has given public encryption key to client application, this does *not* imply
		// client did (or supports) encryption. That means we might receive encrypted- or just plain text passwords here!
		if( defined('ENCRYPTION_PRIVATEKEY_PATH') ) {
			require_once BASEDIR.'/server/utils/PasswordEncryption.class.php';
			$pe = new PasswordEncryption( null, ENCRYPTION_PRIVATEKEY_PATH );
			if( $pe->ValidatePrivateKey() === FALSE ) {
				throw new BizException( $pe->GetLastError(), 'Server', '' );
			}
			$password = $pe->DecryptPrivatePassword( $password );
		}

		//TODO check for external authentication plug-ins, for now, use LDAP
		// When LDAP configured, create user when needed and add/remove his/her groups
		if( BizLDAP::isInstalled() === true ) {
			$ldap = new BizLDAP();
			$admUser = $ldap->authenticate( $user, $password );
			$user = $admUser->Name;
		}

		// check user/pass against DB
		// Note that the $user variable will be updated here (and set to the short user id) 
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		self::$userRow = DBUser::checkUser( $user ); // normalizes $user ! 
		self::$userName = $user;
		DBlog::logService( $user, 'LogOn' ); // log after normalization

		// if empty pass, create std password, to avoid differences in crypt
		$pass = self::getUserInfo('pass');
		if( $pass == '') {
			$pass = ww_crypt("ww", null, true);
			DBUser::setPassword( $user,$pass );
		}

		if (ww_crypt($password, $pass) != $pass) {
			throw new BizException( 'ERR_WRONGPASS', 'Client', '' );
		} else {
			// BZ#20845 - If the database has been installed the required version,
			// and password validated with Standard Des hash, thrown exception to client,
			// force user to change password and store the password with new SHA-512 hash
			// This checking is needed when upgrade from v7/v8 version to v9 or higher version,
			// else user will always failed to change the password due to database not upgrade to required version
			require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
			$installedVersion = DBConfig::getSCEVersion();
			$isRequiredVersionInstalled = (SCENT_DBVERSION === $installedVersion);
			if( $isRequiredVersionInstalled && substr($pass,0,3) != '$6$' ) {
				throw new BizException( 'WARN_PASSWORD_EXPIRED', 'Client', 'SCEntError_PasswordExpired' );
			}
		}

		// check login window
		if( BizLDAP::isInstalled() === false) {
			// MS returns empty string with space, so trim:
			$startdate = self::getUserInfo('startdate');
			$enddate = self::getUserInfo('enddate');
			$expiredate = self::getUserInfo('expirepassdate');
			$now = date('Y-m-d\TH:i:s');
			if ( !empty($startdate) && strncmp($startdate, $now, 19) > 0 ) { // $now < $startdate
				throw new BizException( 'ERR_USER_BEGINDATE', 'Client', '' );
			}
			if( !empty($enddate) ) {
				require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
				$enddate_t = DateTimeFunctions::iso2time( $enddate );
				if (date("His", $enddate_t) == "000000") // if no time, assume end of day
					$enddate_t += 24*3600;
				$enddate = date('Y-m-d\TH:i:s', $enddate_t);
				if ($now > $enddate) {
					throw new BizException( 'ERR_USER_ENDDATE', 'Client', '' );
				}
			}
			if ( !empty($expiredate) && strncmp($expiredate, $now, 19) < 0 ) { // $expiredate < $now
				// Password expired, return specific SOAP fault:
				throw new BizException( 'WARN_PASSWORD_EXPIRED', 'Client', 'SCEntError_PasswordExpired' ); //see apps/login.php
			}
		}

		//Return the short user! 
		//In case the caller passed the fullname, the short user id will be returned now
		return $user;
	}

	/**
	 * Get the client identifier from the request.
	 *
	 * The client identifier is used to select the correct ticket cookie in the request.
	 * Clients can send this identifier in a customer HTTP header ("X-WoodWing-Application") or
	 * in the URL parameters ("ww-app").
	 *
	 * @since 10.2.0
	 * @return null|string
	 */
	private static function getClientIdentifierFromRequest()
	{
		$clientIdentifier = null;
		do{
			if( isset( $_SERVER['HTTP_X_WOODWING_APPLICATION']) && !empty($_SERVER['HTTP_X_WOODWING_APPLICATION']) ) {
				$clientIdentifier = $_SERVER['HTTP_X_WOODWING_APPLICATION'];
				LogHandler::Log( 'BizSession', 'DEBUG', 'Detected a client identifier in headers: ' . $clientIdentifier );
				break;
			}
			require_once BASEDIR.'/server/utils/HttpRequest.class.php';
			$requestParams = WW_Utils_HttpRequest::getHttpParams( 'GP' ); // GET and POST only
			if(  isset($requestParams['ww-app']) && !empty($requestParams['ww-app']) ) {
				$clientIdentifier = urldecode($requestParams['ww-app']);
				LogHandler::Log( 'BizSession', 'DEBUG', 'Detected a client identifier in URL parameters: ' . $clientIdentifier );
				break;
			}
			LogHandler::Log( 'BizSession', 'DEBUG', 'No client identifier detected.' );
		} while(false);

		return $clientIdentifier;
	}

	/**
	 * Sets or updates the ticket cookie for the webservices.
	 *
	 * @since 10.2.0
	 * @param string $ticket
	 */
	public static function setTicketCookieForClientIdentifier( $ticket )
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
		require_once BASEDIR.'/server/secure.php';
		$clientIdentifier = DBTicket::DBappticket( $ticket );
		setLogCookie( 'tickets['.urlencode( $clientIdentifier ).']', $ticket );
	}

	/**
	 * Returns the ticket for the client identifier that is given in the request.
	 *
	 * The tickets cookies are returned as an array in PHP. To select the correct
	 * ticket the client identifier can be added to the request by the client.
	 *
	 * @since 10.2.0
	 * @return null|string
	 */
	public static function getTicketForClientIdentifier()
	{
		$clientIdentifier = self::getClientIdentifierFromRequest();
		require_once BASEDIR.'/server/secure.php';
		$tickets = getOptionalCookie( 'tickets' );
		return $clientIdentifier && isset( $tickets[ $clientIdentifier ] ) ? $tickets[ $clientIdentifier ] : null;
	}

	/**
	 * Logon the given user (short user id) to the given application.
	 * Be sure to call 'validateUser' first!
	 * Generate an exception in case the user can not logon
	 * Returns a ticket
	 *
	 * @param string $orguser: either the fullname or the short user id (as entered by the end user)
	 * @param string $shortUser: the short user id, returned by the validateUser call!
	 * @param string $ticket
	 * @param string $server
	 * @param string $clientname
	 * @param string $domain
	 * @param string $appname
	 * @param string $appversion
	 * @param string $appserial
	 * @param string $appproductcode
	 * @param string[]|null $requestInfo [9.7.0] See LogOn service in SCEnterprise.wsdl for supported options. NULL to resolve all.
	 * @param string $masterTicket [9.7.0] In case the same application does logon twice (e.g. IDS for DPS) this refers to the ticket of the first logon.
	 * @param string $password [10.0.0] User typed password
	 * @return WflLogOnResponse
	 * @throws BizException
	 */
	public static function logOn( $orguser, $shortUser,
		/** @noinspection PhpUnusedParameterInspection */ $ticket,
		                          $server, $clientname,
		/** @noinspection PhpUnusedParameterInspection */ $domain,
		$appname, $appversion, $appserial, $appproductcode, $requestInfo, $masterTicket, $password )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';

		if( is_null($requestInfo) ) { // null means all
			$requestInfo = array(
				'Publications', 'NamedQueries', 'ServerInfo', 'Settings',
				'Users', 'UserGroups', 'Membership', 'ObjectTypeProperties', 'ActionProperties',
				'Terms', 'FeatureProfiles', 'Dictionaries', 'MessageList', 'CurrentUser' );
				// MessageQueueConnection is not listed here since only 10.0+ clients may want this feature.
		}

		global $sLanguage_code;
		$sLanguage_code = BizUser::getLanguage($shortUser);
		BizResources::getResourceTable(true);

		try {
			require_once BASEDIR.'/server/bizclasses/BizTicket.class.php';
			$bizTicket = new BizTicket();
			$bizTicket->deleteExpiredTickets();
		} catch ( BizException $e ) {
			// Do nothing, error is already added to the log, no need to stop the log on.
		}

		// Make up new ticket		
		$errorMessage = '';
		$userLimit = false;
		$ticketid = DBTicket::genTicket( $orguser, $shortUser, $server, $clientname, $appname,
			$appversion, $appserial, $appproductcode, $userLimit,
			$errorMessage, $masterTicket );

		if ( !$ticketid )  {
			throw new BizException( $userLimit ? 'WARN_USER_LIMIT' : 'ERR_LICENSE', 'Client', $errorMessage );
		}

		// Start session here (since deeply nested functions after this point use BizSession::getTicket or session_id for $ticket)
		self::startSession($ticketid);
		self::setRunMode( self::RUNMODE_SYNCHRON );

		// Store username that is accessible via public getShortUserName:
		if( !isset(self::$userName) ) {
			self::$userName = $shortUser;
		}
		$userId = self::getUserInfo('id');

		// Support cookie enabled sessions for JSON clients that run multiple web applications which need to share the
		// same ticket. Client side this can be implemented by simply letting the web browser round-trip cookies. [EN-88910]
		if( $ticketid ) {
			BizSession::setTicketCookieForClientIdentifier($ticketid);
		}

		// Return LogOnResponse to client application
		require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
		$ret = new WflLogOnResponse();
		$ret->Ticket = $ticketid;
		$ret->FeatureSet    = array();
		$ret->LimitationSet = array();

		$pubRequestInfo = array_filter( $requestInfo, function( $requestProp ) { return substr( $requestProp, 0, 14 ) == 'Publications->'; } );
		if( in_array( 'Publications', $requestInfo ) || $pubRequestInfo ) {
			if( in_array( 'Publications', $requestInfo ) || !$pubRequestInfo ) {
				$pubRequestInfo = null; // get all
			} else {
				$stripPubPrefixes = function( $requestProp ) { return substr( $requestProp, 14 ); }; // remove 'Publication->' prefixes
				$pubRequestInfo = array_map( $stripPubPrefixes, $pubRequestInfo );
			}
			require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
			$ret->Publications = BizPublication::getPublicationInfosByRequestInfo( $shortUser, $pubRequestInfo );
		}
		if( in_array( 'NamedQueries', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizNamedQuery.class.php';
			$ret->NamedQueries  = BizNamedQuery::getNamedQueries( false );
		}
		if( in_array( 'ServerInfo', $requestInfo ) ) {
			$ret->ServerInfo    = self::getServerInfo( $ticket );
		}
		if( in_array( 'Settings', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$ret->Settings      = BizUser::getSettings( $shortUser, $appname );
		}
		if( in_array( 'Users', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$ret->Users         = BizUser::getUsersWithCommonAuthorization( $userId );
		}
		if( in_array( 'UserGroups', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$ret->UserGroups    = BizUser::getUserGroups();
		}
		if( in_array( 'Membership', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
			$ret->Membership    = BizUser::getMemberships( $userId );
		}
		if( in_array( 'ObjectTypeProperties', $requestInfo ) ) {
			require_once BASEDIR.'/server/dbclasses/DBMetaData.class.php';
			$ret->ObjectTypeProperties = DBMetaData::getObjectProperties();
		}
		if( in_array( 'ActionProperties', $requestInfo ) ) {
			require_once BASEDIR.'/server/dbclasses/DBMetaData.class.php';
			$ret->ActionProperties     = DBMetaData::getActionProperties();
		}
		if( in_array( 'Terms', $requestInfo ) ) {
			$ret->Terms                = getUiTerms();
		}
		if( in_array( 'FeatureProfiles', $requestInfo ) ) {
			require_once BASEDIR.'/server/dbclasses/DBFeature.class.php';
			$ret->FeatureProfiles      = DBFeature::getFeatureProfiles();
		}
		if( in_array( 'Dictionaries', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizSpelling.class.php';
			$ret->Dictionaries         = BizSpelling::getDictionariesForPublication( 0 ); // 0 = system-wide
		}
		if( in_array( 'MessageList', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			$ret->MessageList          = BizMessage::getMessagesForUser( $userId, $shortUser ); // Messages that are pending for this user
		}
		if( in_array( 'CurrentUser', $requestInfo ) ) {
			$user = self::getUser();
			$ret->TrackChangesColor    = $user->TrackChangesColor; // obsoleted but still here to support 7.6 clients.
			$ret->CurrentUser          = $user;
		}
		if( in_array( 'MessageQueueConnections', $requestInfo ) ) {
			require_once BASEDIR.'/server/bizclasses/BizMessageQueue.class.php';
			BizMessageQueue::setupMessageQueueConnectionsForLogOn( $ret, $userId, $password );
		}

		// fire event
		$fullname = self::getUserInfo('fullname');
		require_once BASEDIR.'/server/smartevent.php';
		new smartevent_logon( $ticketid, $shortUser, $fullname, $server );

		return $ret;
	}

	/**
	 *
	 *
	 * @param string $ticket
	 * @return ServerInfo
	 */
	private static function getServerInfo( $ticket )
	{
		// Let user overrule the company language to tell client apps what language this user is speaking
		require_once BASEDIR.'/server/bizclasses/BizServerInfo.class.php';
		$serverInfo = BizServerInfo::getServerInfo();
		$compLangFeature = null;
		foreach( $serverInfo->FeatureSet as $feature ) { // Search through featureset for company language setting
			if( $feature->Key == 'CompanyLanguage' ) {
				$compLangFeature = $feature;
				break;
			}
		}

		if( $compLangFeature ) {
			if( trim($compLangFeature->Value) == '' ) {
				$compLangFeature->Value = 'enUS'; // Empty (bad) comp lang configured; take English as default
			}
		} else { // No comp lang configured at all; take English as default
			$compLangFeature = new Feature( 'CompanyLanguage', 'enUS' );
			$serverInfo->FeatureSet[] = $compLangFeature;
		}
		if( self::getUserInfo('language') != '') {
			$compLangFeature->Value = self::getUserInfo('language'); // Let user overrule
		}

		// Add the server feature FileUploadUrl (if proper define is set).
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$transferServer->addFeatures( $serverInfo );

		// Determine whether or not the user works from remote location.
		require_once BASEDIR.'/server/utils/IpAddressRange.class.php';
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$clientIP = WW_Utils_UrlUtils::getClientIP();
		$isRemote = WW_Utils_IpAddressRange::isIpIncluded( $clientIP,
			unserialize( REMOTE_LOCATIONS_EXCLUDE ), unserialize( REMOTE_LOCATIONS_INCLUDE ) ) ? 'true' : 'false';
		$serverInfo->FeatureSet[] = new Feature( 'IsRemoteUser', $isRemote );
		$locationStr = $isRemote == 'true' ? 'remote' : 'local';
		LogHandler::Log( 'BizSession', 'INFO', 'The client IP '.$clientIP.' is a '.$locationStr. ' address '.
			'according to REMOTE_LOCATIONS_EXCLUDE and REMOTE_LOCATIONS_INCLUDE options. Therefore '.
			'adding IsRemoteUser='.$isRemote.' option to ServerInfo->FeatureSet in LogOnResponse. ' );

		// Add the Labels feature to the FeatureSet.
		self::addFeatureLabels( $serverInfo );

		// Add the Client features (CLIENTFEATURES) to the FeatureSet.
		self::addFeaturesForClient( $serverInfo, $isRemote == 'true', $ticket );

		// Add the AutomatedPrintWorkflow feature to the FeatureSet.
		self::addFeatureForAutomatedPrintWorkflow( $serverInfo );

		// Add the ContentSourceFileLinks feature to the FeatureSet.
		self::addFeatureForContentSourceFileLinks( $serverInfo );

		// Add Output Devices to the FeatureSet.
		require_once BASEDIR.'/server/bizclasses/BizAdmOutputDevice.class.php';
		$bizDevice = new BizAdmOutputDevice();
		$bizDevice->addFeatureOutputDevices( $serverInfo );

		// add ExtensionMap feature.
		// NOTE: Might change in the future, added in v6.1 for Content Station
		self::addFeatureExtensionMap( $serverInfo );

		foreach( $serverInfo->FeatureSet as $feature ) {
			if ( !is_null($feature->Value) && !is_string($feature->Value) ) {
				if ( is_bool($feature->Value) ) {
					// Only bool values can't are currently convert as:
					// true = 1, false = <empty string>
					// To be compliant with the validator convert as such
					$feature->Value = $feature->Value ? "1" : "";
				} else {
					$feature->Value = strval($feature->Value);
				}
			}
		}

		// Populate Enterprise System ID to ServerInfo
		$serverInfo->EnterpriseSystemId = self::getEnterpriseSystemId();
		return $serverInfo;
	}

	public static function logOff( $ticket, $savesettings=null, $settings=null, $messageList=null )
	{
		require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';

		// check ticket (and get user)
		$shortUserName = self::checkTicket( $ticket, 'LogOff' );

		// Handle messages read/deleted by user.
		if( $messageList ) {
			if( !is_a( $messageList, 'MessageList' ) ) { // detect 7.x customizations that are not ported to 8.0 (used to be $messageIds)
				throw new BizException( 'ERR_INVALID_OPERATION', 'Server',
					'Since 8.0, the BizSession::logOff() function no longer accepts an array of message ids. '.
					'Make sure you pass in a MessageList data object at the 4th parameter. ' );
			}
			require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
			BizMessage::sendMessagesForUser( $shortUserName, $messageList );
		}

		// Fire event (n-cast the logoff operation to notify client apps).
		require_once BASEDIR.'/server/smartevent.php';
		new smartevent_logoff( $ticket, $shortUserName );  // fire event now, while ticket is still valid
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( $shortUserName, 'LogOff' );

		//get the appname before deleting the ticket...
		$appname = DBTicket::DBappticket($ticket);

		try {
			require_once BASEDIR.'/server/bizclasses/BizTicket.class.php';
			$bizTicket = new BizTicket();
			$bizTicket->deleteTicket( $ticket );
		} catch( BizException $e ) {
			// Do nothing, error is already added to the log, no need to stop the log off.
		}

		// settings
		if( $savesettings ) {
			$dbDriver = DBDriverFactory::gen();
			require_once BASEDIR.'/server/dbclasses/DBUserSetting.class.php';
			$sth = DBUserSetting::purgeSettings( $shortUserName , $appname );
			if( !$sth ) {
				throw new BizException( 'ERR_DATABASE', 'Server',  $dbDriver->error() );
			}

			if( $settings ) foreach( $settings as $setting ) {
				$sth = DBUserSetting::addSetting( $shortUserName, $setting->Setting, $setting->Value, $appname );
				if( !$sth ) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
			}
		}
	}

	/**
	 * Returns the language of the user who has just logged on. <br>
	 * When language of user is not set, the configured CompanyLanguage is taken.
	 * If both not set, the default language 'enUS' is returned.
	 * Note: The function {@link logOn} should be called first.
	 *
	 * @return string
	 */
	public static function getUserLanguage()
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		global $sLanguage_code;
		$sLanguage_code = self::getUserInfo('language');

		// Check user language, if unknown, assign the default company language (or English when not configured)
		$sLanguage_code = BizUser::validUserLanguage( $sLanguage_code );
		return $sLanguage_code;
	}

	/**
	 * Validates the ticket and returns the user.
	 *
	 * Throws an exception in case ticket is invalid, with detail set to 'SCEntError_InvalidTicket'.
	 *
	 * @param string $ticket Ticket to validate
	 * @param string $service Service to validate the ticket for, default ''.
	 * @param bool $extend Since 10.2. Whether or not the ticket lifetime should be implicitly extended (when valid).
	 *                     Pass FALSE when e.g. frequently called and so the expensive DB update could be skipped.
	 * @return string Short user name of the active user of the session.
	 * @throws BizException When ticket not valid.
	 */
	public static function checkTicket( $ticket, $service='', $extend = true )
	{
		// All web applications validate their ticket before they start operating,
		// but most of them do not start a session. Here we do a lazy start to avoid
		// connectors being called through ticketExpirationReset() without valid session.
		if( !self::isStarted() ) {
			self::startSession( $ticket );
		}

		// Throw error when ticket is not (or no longer) valid.
		require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
		self::$userName = DBTicket::checkTicket( $ticket, $service, $extend );
		if( !self::$userName ) {
			throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket');
		}

		// Language was not correctly loaded when called from soap-client, so making sure 
		// language is correctly loaded here.
		self::loadUserLanguage(self::$userName);

		if( $service && !self::isFeatherLightService( $service ) ) {
			// Ask connectors if they have a valid ticket for their integrated system as well.
			$connectorTicketsValid = true;
			require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
			$connRetVals = array();
			BizServerPlugin::runDefaultConnectors( 'Session', null,
				'ticketExpirationReset', array( $ticket, self::$userName ), $connRetVals );
			if( $connRetVals ) foreach( $connRetVals as $connName => $connRetVal ) {
				if( $connRetVal === false ) {
					LogHandler::Log( 'BizSession', 'INFO', 'Server Plug-in connector '.$connName.' indicates '.
						'through ticketExpirationReset() that the integrated system has no valid '.
						'ticket for the current user. Therefore the Enterprise ticket will be made '.
						'invalid as well to let the user (re)logon to obtain ticket for both systems.' );
					$connectorTicketsValid = false;
					break;
				}
			}

			// When integrated system has no ticket, make Enterprise ticket invalid as well.
			// That forces clients to (re)login and obtain a seat for both Enterprise and
			// the remote system again.
			if( !$connectorTicketsValid ) {
				try {
					require_once BASEDIR.'/server/bizclasses/BizTicket.class.php';
					$bizTicket = new BizTicket();
					$bizTicket->deleteTicket( $ticket );
				} catch( BizException $e ) {
					// Do nothing, error is already added to the log, no need to stop.
				}
				throw new BizException( 'ERR_TICKET', 'Client', 'SCEntError_InvalidTicket' );
			}
		}

		return self::$userName;
	}

	/**
	 * Loads the language of the user in to global $sLanguage_code.
	 * Needed because when called from a soap-client this was not loaded correctly.
	 *
	 * @param string $userName Short name of user.
	 * @return string Language code.
	 */
	public static function loadUserLanguage( $userName )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';

		global $sLanguage_code;
		$sLanguage_code = BizUser::getLanguage( $userName );
		return $sLanguage_code;
	}

	/**
	 * Starts a business session. Use only setSessionVariables and getSessionVariables to
	 * read and write session variables.
	 * Note: this does not start a DB transaction, use StartTransaction for that!
	 * @see BizSession::setSessionVariables()
	 * @see BizSession::getSessionVariables()
	 *
	 * @param string $ticket Ticket for the session, empty string in case there is no ticket
	 * @throws BizException
	 */
	public static function startSession( $ticket )
	{
		if( ($ticket && session_id()=='') // null when called by logon
			|| $ticket == session_id()) { // the next service is called within the same PHP session
			session_id($ticket);
			//BZ#13361 Do not start session here, to prevent locking issues
			self::$sessionStarted = true;
			self::$ticket = $ticket;
			//session_start();
		}
		if( self::$ticket && self::$ticket == $ticket ){
			self::$sessionCounter += 1;
		}
		if( $ticket && self::$ticket &&
			self::$ticket != $ticket ) {
			$message = 'Multiple session with different tickets are not supported. ';
			$message .= 'Self ticket [' . self::$ticket . '] New ticket [' . $ticket . ']';
			throw new BizException( 'ERR_TICKET', 'Server', $message );
		}
	}

	/**
	 * Ends a business session - clears our stuff. This will commit to DB (if supported by DB)
	 */
	public static function endSession()
	{
		unset( self::$sessionCache[self::$sessionCounter] ); // Clearing the 'going-to-end' session information, should be cleared before the $sessionCounter is decreased!
		self::$sessionCounter -= 1;
		if( self::$sessionCounter == 0 ) {

			self::$sessionStarted = false;
			self::$ticket = '';
			self::$sessionCache = array();
			session_id(''); //clear session id
		}
	}

	/**
	 * Starts a atomic transaction, commits via EndTransaction, rollback with CancelTransaction
	 * If DB does not support transactions, nothing will be done (up to the DB driver)
	 */
	public static function startTransaction()
	{
		$dbDriver = DBDriverFactory::gen();
		$dbDriver->beginTransaction();
	}

	/**
	 * Commit transaction (if supported by DB)
	 */
	public static function endTransaction()
	{
		$dbDriver = DBDriverFactory::gen();
		$dbDriver->commit();
	}

	/**
	 * Cancels transaction. This will rollback DB changes (if supported by DB)
	 */
	public static function cancelTransaction()
	{
		$dbDriver = DBDriverFactory::gen();
		$dbDriver->rollback();
	}

	public static function changeOnlineStatus( $user, $ids, $onlinestatus )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		require_once BASEDIR.'/server/dbclasses/DBObjectFlag.class.php';
		$dbDriver = DBDriverFactory::gen();

		$arrAccessErrors = array();
		if ($ids) foreach ($ids as $id) {
			if( trim($id) != null ) {
				DBObjectFlag::unlockObjectFlags( $id );
			}
			$bAccessError = false;
			// check for empty id
			if( !$id ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
			}

			// get object
			$sth = DBObject::getObject( $id );
			if( !$sth ) {
				throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
			}
			$curr_row = $dbDriver->fetch($sth);
			if( !$curr_row ) {
				throw new BizException( 'ERR_NOTFOUND', 'Client', $id );
			}

			require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
			DBlog::logService( $user, 'ChangeOnlineStatus', $id, $curr_row["publication"], '', $curr_row["section"],
				$curr_row["state"], '', '', '', $curr_row['type'], $curr_row['routeto'], '', $curr_row['version']);

			// Determine the first best issue which the object is assigned to.
			require_once BASEDIR.'/server/dbclasses/DBTarget.class.php';
			$targets = DBTarget::getTargetsByObjectId( $id );
			$issueId = $targets && count($targets) ? $targets[0]->Issue->Id : 0;

			// check "Keep Locked" access (=> 'K')
			require_once BASEDIR.'/server/bizclasses/BizAccess.class.php';
			if( !BizAccess::checkRightsForObjectRow(
				$user, 'K', BizAccess::DONT_THROW_ON_DENIED,
				$curr_row, $issueId )
			) {
				// collect access errors and continue
				$bAccessError = true;
				$arrAccessErrors[] = $curr_row['name'];
			}

			$bKeepLockForOffline = $onlinestatus == 'TakeOffline';
			if( $bAccessError === false ) { // user has access?
				// change online status
				require_once BASEDIR.'/server/dbclasses/DBObjectLock.class.php';
				$sth = DBObjectLock::changeOnlineStatus( $id, $user, $bKeepLockForOffline );
				if( !$sth ) {
					throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
				}
			}
			// fire event
			require_once BASEDIR.'/server/smartevent.php';
			new smartevent_unlockobject( self::getTicket(), $id, $user, $bKeepLockForOffline, $curr_row['routeto'] );
		}

		// error once if user is not allowed to take one or more objects offline
		if( count( $arrAccessErrors ) > 0 ) {
			$sErrorMessage = BizResources::localize("ERR_AUTHORIZATION")."\n";
			foreach( $arrAccessErrors as $err  ) {
				$sErrorMessage .= "\n- ".$err;
			}
			throw new BizException( null, 'Client', 'SCEntError_UserNotAllowed', $sErrorMessage );
		}
	}

	/**
	 * Adds the "Labels" feature to the given feature set. "Labels" is the
	 * content of componentDefs.xml
	 *
	 * @param ServerInfo $serverInfo Holds a FeatureSet to add the "Labels" feature.
	 */
	private static function addFeatureLabels( ServerInfo $serverInfo )
	{
		$filePath = BASEDIR . '/config/componentDefs.xml';
		if (is_file($filePath)) {
			$contents = file_get_contents($filePath);
			if ($contents !== FALSE){
				$serverInfo->FeatureSet[] = new Feature('Labels', $contents);
			}
		}
	}

	/**
	 * Adds the "ExtensionMap" feature to the given feature set. "ExtensionMap" contains
	 * an XML tree like this:
	 * <extensions>
	 * 		<extension ext=".jpg" mime="image/jpeg" objtype="Image" default="true"/>
	 * 		<extension ext=".jpeg" mime="image/jpeg" objtype="Image" default="false"/>
	 * </extensions>
	 * Note: extensions are unique, but mime types don't have to be. The default attribute
	 * tells that this extension is the default for this mimetype.
	 *
	 * The values are read form EXTENSIONMAP defined in configserver.php
	 *
	 * @param ServerInfo $serverInfo Holds a FeatureSet to add the "ExtensionMap" feature.
	 */
	private static function addFeatureExtensionMap( ServerInfo $serverInfo )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		// Create <extensions> XML element and iterate thru EXTENSIONMAP to add
		// <extension> elements. While building this we keep track of the mimetypes
		// that we see. The first extension will be the default per mimetype
		$xmlTree = new SimpleXMLElement('<extensions/>');
		$mimeTypes[] = array();
		$extensionMap = MimeTypeHandler::getExtensionMap();
		foreach( $extensionMap as $extKey => $values ) {
			$ext = $xmlTree->addChild('extension');
			$ext->addAttribute('ext', $extKey );
			$ext->addAttribute('mime', $values[0] );
			$ext->addAttribute('objtype', $values[1] );
			if( !in_array($values[0], $mimeTypes) ) {
				// Not seen this yet, so it's default
				$ext->addAttribute('default', 'true' );
				$mimeTypes[] = $values[0];
			} else {
				// Seen this before, so it's not the default
				$ext->addAttribute('default', 'false' );
			}
		}

		$serverInfo->FeatureSet[] = new Feature('ExtensionMap', $xmlTree->asXML());
	}

	/**
	 * Adds the features configured for CLIENTFEATURES to a given set of features.
	 *
	 * Depending on the client name, client location and ticket(seal) it decides which
	 * subcollection of features (configured in CLIENTFEATURES) to add.
	 *
	 * @since 9.7.0
	 * @param ServerInfo $serverInfo Holds a FeatureSet to add the client features.
	 * @param boolean $isRemote
	 * @param string $ticket The requested ticket
	 */
	private static function addFeaturesForClient( ServerInfo $serverInfo, $isRemote, $ticket )
	{
		$clientName = BizSession::getClientName();
		if( $clientName == 'InDesign Server' ) {
			require_once BASEDIR.'/server/dbclasses/DBInDesignServerJob.class.php';
			$jobRow = $ticket ? DBInDesignServerJob::getJobByTicketSeal( $ticket ) : null;
			$subEntry = $jobRow ? $jobRow['jobtype'] : 'default';
		} else {
			$subEntry = $isRemote ? 'remote' : 'local';
		}
		$options = unserialize( CLIENTFEATURES );
		if( isset( $options[$clientName][$subEntry] ) && $options[$clientName][$subEntry] ) {
			$serverInfo->FeatureSet = array_merge( $serverInfo->FeatureSet, $options[$clientName][$subEntry] );
		}
	}

	/**
	 * Adds the ContentStationAutomatedPrintWorkflow feature when any of the plugins
	 * has the AutomatedPrintWorkflow business connector interface implemented.
	 *
	 * @since 9.8.0
	 * @param ServerInfo $serverInfo Holds the FeatureSet to update.
	 */
	private static function addFeatureForAutomatedPrintWorkflow( ServerInfo $serverInfo )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		if( BizServerPlugin::hasActivePlugins( 'AutomatedPrintWorkflow' ) ) {
			$serverInfo->FeatureSet[] = new Feature( 'ContentStationAutomatedPrintWorkflow' );
		}
	}

	/**
	 * Adds the ContentSourceFileLinks feature when any of the content source plugins
	 * has enabled this feature.
	 *
	 * @since 9.7.0
	 * @param ServerInfo $serverInfo Holds a FeatureSet to update.
	 */
	private static function addFeatureForContentSourceFileLinks( ServerInfo $serverInfo )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		BizServerPlugin::runDefaultConnectors(
			'ContentSource', null, 'isContentSourceFileLinksSupported', array(), $connRetVals );

		$contentSources = array();
		foreach( $connRetVals as $conn => $val ) {
			if( $val === true ) {
				$contentSources[] = BizServerPlugin::getPluginUniqueNameForConnector( $conn );
			}
		}
		if( count($contentSources) > 0 ) {
			$serverInfo->FeatureSet[] = new Feature( 'ContentSourceFileLinks', implode($contentSources, ',') );
		}
	}

	/**
	 * Set mulitple session variables.
	 * This function open the session, sets variables, saves and closes the
	 * session. This way an other process won't be locked.
	 *
	 * Please note: with concurrent processes the last call to
	 * setSessionVariables will overrule the earlier one
	 *
	 * @param array $variables key values pairs
	 */
	public static function setSessionVariables ($variables)
	{
		if (self::$sessionStarted) {
			session_start();
			if (! isset( $_SESSION[self::SESSION_NAMESPACE] )) {
				$_SESSION[self::SESSION_NAMESPACE] = array();
			}
			foreach ($variables as $key => $value) {
				$_SESSION[self::SESSION_NAMESPACE][$key] = $value;
			}
			session_write_close();
		}
	}

	/**
	 * Get mulitple session variables.
	 * This function open the session, gets variables and closes the
	 * session. This way an other process won't be locked.
	 *
	 * @return array key value pairs
	 */
	public static function getSessionVariables ()
	{
		$variables = array();
		if (self::$sessionStarted) {
			session_start();
			if (isset( $_SESSION[self::SESSION_NAMESPACE] )) {
				foreach ($_SESSION[self::SESSION_NAMESPACE] as $key => $value) {
					$variables[$key] = $value;
				}
			}
			session_write_close();
		}

		return $variables;
	}

	/**
	 * Return wether or not a BizSession has been started
	 *
	 * @return bool true if session has been started else false
	 */
	public static function isStarted()
	{
		return self::$sessionStarted;
	}

	/**
	 * Returns current session ticket if session has been started.
	 *
	 * @return string ticket or '' when session has not been started
	 */
	public static function getTicket()
	{
		if (self::$sessionStarted){
			return self::$ticket;
		}
		return '';
	}

	/**
	 * Returns whether or not a given client application name is acting in the current session.
	 *
	 * The given name can be a fragment of the full name. For example, when passing in 'indesign'
	 * for the $appName param, the function will return TRUE when the session app name is
	 * 'InDesign', 'InDesign Server' or 'Digital Publishing Tools InDesign'.
	 *
	 * @param null $ticket Obsoleted since 9.4. Always pass NULL.
	 * @param string $appName The (fragment of) the application name to check.
	 * @return bool
	 */
	public static function isAppInTicket( $ticket, $appName )
	{
		$clientName = self::getClientName( $ticket );
		return $clientName && (bool)stristr( $clientName, $appName );
	}

	/**
	 * Returns the client application name that using the current (or given) session / ticket.
	 *
	 * When this function is called, but there is no active session, NULL is returned.
	 *
	 * When this function is called in in context of Server Jobs (running in background mode),
	 * EMPTY is returned. This is for recurring jobs but also for normal jobs even when
	 * initiated in context of a web service with acting client. That client is NOT returned.
	 *
	 * @since 9.0.0
	 * @param null $ticket Obsoleted since 9.4. Always pass NULL.
	 * @return string|null Client name. NULL on error. EMPTY on Server Job context.
	 */
	public static function getClientName( $ticket=null )
	{
		if( !is_null($ticket) ) {
			LogHandler::Log( 'BizSession', 'ERROR',
				'BizSession::getClientName() called with the $ticket param set. Please pass in null.' );
			return null;
		}

		$clientName = null;
		$ticket = self::getTicket();
		if( $ticket ) {
			if( self::$runMode == self::RUNMODE_BACKGROUND ) { // server job calling?
				$clientName = '';
			} else { // web service client calling?
				require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
				$clientName = DBTicket::DBappticket( $ticket );
			}
		} else {
			LogHandler::Log( 'BizSession', 'WARN', 'Could not resolve name of acting client; No active session.' );
		}
		return $clientName;
	}

	/**
	 * Resolves the client application version of the current session.
	 *
	 * The version is formatted in "x.y.z.b" notation. See {@link:formatClientVersion()}.
	 * When the version could not be parsed / formatted, NULL is returned. This could happen
	 * when there is no active session or when the client does not respect the Enterprise
	 * version standard.
	 *
	 * When this function is called in in context of Server Jobs (running in background mode),
	 * EMPTY is returned. This is for recurring jobs but also for normal jobs even when
	 * initiated in context of a web service with acting client. That client is NOT returned.
	 *
	 * Since 10.1.1 this function no longer logs a warning when the client did not specify a version
	 * at the time it did logon. For example the import module of Elvis Server does login without version.
	 * Note that this function returns EMPTY for those cases.
	 *
	 * @param null $ticket Obsoleted since 9.4. Always pass NULL.
	 * @param null $clientVersion Obsoleted since 9.4. Always pass NULL.
	 * @param int $digits Number of digits to return [1...4]. For example, 2 digits returns "x.y"
	 * @return string|null Client version. NULL on error. EMPTY on Server Job context. EMPTY when client did not specify a version.
	 */
	public static function getClientVersion( $ticket=null, $clientVersion=null, $digits=4 )
	{
		if( !is_null($ticket) ) {
			LogHandler::Log( 'BizSession', 'ERROR',
				'BizSession::getClientVersion() called with the $ticket param set. Please pass in null.' );
			return null;
		}
		if( !is_null($clientVersion) ) {
			LogHandler::Log( 'BizSession', 'ERROR',
				'BizSession::getClientVersion() called with the $clientVersion param set. Please pass in null.' );
			return null;
		}

		$retVal = null;
		$ticket = self::getTicket();
		if( $ticket ) {
			if( self::$runMode == self::RUNMODE_BACKGROUND ) { // server job calling?
				$retVal = '';
			} else { // web service client calling?
				require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
				$clientVersion = DBTicket::getClientAppVersion( $ticket );
				if( $clientVersion ) {
					$retVal = self::formatClientVersion( $clientVersion, $digits );
				} else {
					$retVal = '';
				}
			}
		} else {
			LogHandler::Log( 'BizSession', 'WARN', 'Could not resolve version of acting client; No active session.' );
		}
		return $retVal;
	}

	/**
	 * Converts a client application version into "x.y.z.b" notation.
	 * The format passed by SC / CS clients is in "[v]x.y.z [DAILY] build b" notation whereby:
	 *    x = major (numeric)
	 *    y = minor (numeric)
	 *    z = patch (numeric)
	 *    b = build (numeric)
	 *    [v] = optional literal (string)
	 *    build = literal (string)
	 *    [DAILY] = optional literal (string)
	 *
	 * @param string $clientVersion Version of client application to be parsed.
	 * @param int $digits Number of digits to return [1...4]. For example, 2 digits returns "x.y"
	 * @return string|null Returns NULL when client version could not be parsed.
	 */
	public static function formatClientVersion( $clientVersion, $digits=4 )
	{
		// Convert human readable version to internal digit notation.
		$clientVersion = str_replace( ' ', '.', $clientVersion ); // For example: vx.y.z.build.b
		$parts = explode( '.', $clientVersion );
		if( count( $parts ) >= 5 ) {
			$major = $parts[0];
			$major = intval( ($major[0] == 'v') ? substr( $major, 1 ) : $major ); // remove leading 'v'
			$minor = intval($parts[1]);
			$patch = intval($parts[2]);
			$build = intval( end( $parts ) );
			$retVal = '';
			if( $digits >= 1 ) {
				$retVal .= $major;
			}
			if( $digits >= 2 ) {
				$retVal .= '.'.$minor;
			}
			if( $digits >= 3 ) {
				$retVal .= '.'.$patch;
			}
			if( $digits >= 4 ) {
				$retVal .= '.'.$build;
			}
		} else {
			LogHandler::Log( 'BizSession', 'WARN', 'Given client version has bad format: '.$clientVersion );
			$retVal = null;
		}
		return $retVal;
	}

	/**
	 * Sets the Service Name. See getServiceName() for details.
	 *
	 * @param string $serviceName
	 */
	public static function setServiceName( $serviceName )
	{
		if( !isset( self::$sessionCache[self::$sessionCounter]['servicename'] )) { // Set only when it's not set yet, otherwise the serviceName will be overwritten!
			self::$sessionCache[self::$sessionCounter]['servicename'] = $serviceName;
		}

	}

	/**
	 * Returns the Service Name that is currently handled by the system.
	 * For example: WflCreateObjects, WflCreateObjectRelations, AdmCreateUsers, etc.
	 *
	 * @return string Service Name. (Empty string when undetermined / not set.)
	 */
	public static function getServiceName()
	{
		return isset( self::$sessionCache[self::$sessionCounter]['servicename'] ) ?
			self::$sessionCache[self::$sessionCounter]['servicename'] : '';
	}


	/**
	 * Sets the current Run Mode of the server. See getRunMode() for details.
	 *
	 * @since v8.0
	 * @param string $runMode
	 */
	public static function setRunMode( $runMode )
	{
		if( $runMode == self::RUNMODE_SYNCHRON ||
			$runMode == self::RUNMODE_BACKGROUND ) {
			self::$runMode = $runMode;
		}
	}

	/**
	 * Returns the current Run Mode of the server.
	 * When clients are talking web services, the Run Mode equals BizSession::RUNMODE_SYNCHRON.
	 * When the server is picking up server jobs from the queue, it is BizSession::RUNMODE_BACKGROUND.
	 *
	 * @since v8.0
	 * @return string Run Mode
	 */
	public static function getRunMode()
	{
		return self::$runMode;
	}

	/**
	 * Sets the current Direct Commit mode of the server. See getDirectCommit() for details.
	 *
	 * @since v8.0
	 * @param bool $directCommit
	 */
	public static function setDirectCommit( $directCommit )
	{
		self::$directCommit = $directCommit;
	}

	/**
	 * Returns the current Direct Commit mode of the server. This is used for storing
	 * data in search indexing systems, such as Solr. When enabled, saved data is directly
	 * reflected and queryable, but the save operation is a bit slower. When disabled,
	 * the saved data is reflected a time fraction later, but the save operation is faster.
	 * When running test scripts, better enable this mode, especially when storing and
	 * retrieving data right after each other, e.g. to validate if saved data is reflected.
	 *
	 * @since v8.0
	 * @return bool Direct Commit
	 */
	public static function getDirectCommit()
	{
		return self::$directCommit;
	}

	/**
	 * To check whether the service passed-in is a feather light service or not.
	 *
	 * Feather light refers to light weighted service that is lightning fast.
	 * However a web service can never be faster than the footprint of the server base (because it runs on it).
	 * The footprint is the duration and the memory consumption it takes to start PHP itself and to include the
	 * minimum sets of PHP files for Enterprise Server (config.php and its dependencies).
	 * At this point, the server is ready to run any web service. The resource files are not loaded yet since they
	 * do lazy loading. Whether or not the ticket is validated depends on the service itself. To validate the ticket,
	 * a database connection is established as well.
	 *
	 * Which service is feather light is hardcoded in this function. Once listed, extra optimizations are implemented
	 * (called from several locations in the server). For example, the Session connector is not invoked and the service
	 * is not logged.
	 *
	 * @param string $serviceName
	 * @return bool True when the service passed in is Autocomplete service or OperationProgress; False otherwise.
	 */
	public static function isFeatherLightService( $serviceName )
	{
		static $services = null;
		if( !$services ) {
			// The very same service request is named differently, depending on from which layer
			// in the server architecture the logging is called. The 'raw' request arrives at the protocol
			// layer which gets mapped onto one of the 'real' PHP request classes (as generated from WSDL) when
			// stepping into the service layer. So there are two flavors:
			// - raw: from protocol layer; Request as literally defined in WSDL.
			// - real: from service layer; Request class in PHP as derived from WSDL.
			$services = array(
				'OperationProgressRequest' => true, // raw
				'PubOperationProgress'     => true, // real
				'Autocomplete'             => true, // raw
				'WflAutocomplete'          => true, // real
			);
		}
		return array_key_exists( $serviceName, $services );
	}

	/**
	 * Get the Enterprise System ID from the config table.
	 *
	 * @return string|null $flagValue Enterprise System ID, null when it is not set.
	 */
	public static function getEnterpriseSystemId()
	{
		static $enterpriseSystemId = false;
		if( $enterpriseSystemId === false ) {
			require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
			require_once BASEDIR . '/server/utils/NumberUtils.class.php';
			$enterpriseSystemId = DBConfig::getValue( 'enterprise_system_id' );
			$enterpriseSystemId = !empty( $enterpriseSystemId ) && NumberUtils::validateGUID( $enterpriseSystemId ) ? $enterpriseSystemId : null;
		}
		return $enterpriseSystemId;
	}
}
