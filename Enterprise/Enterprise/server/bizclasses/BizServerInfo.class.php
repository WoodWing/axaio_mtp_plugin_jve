<?php
/**
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class BizServerInfo
{
	/**
	  * Returns this application server info
	  * @return ServerInfo object
	  */
	static public function getServerInfo()
	{
		return new ServerInfo( SERVERNAME, SERVERURL, SERVERDEVELOPER, SERVERIMPLEMENTATION, SERVERTECHNOLOGY,
							   SERVERVERSION, unserialize(SERVERFEATURES), 
							   defined('ENCRYPTION_PUBLICKEY_PATH') ? ENCRYPTION_PUBLICKEY_PATH : null );
	}

	/**
	 * Returns a list of application servers, to let user choose one in login dialog.
	 * Per server, some info is given, including a public encryption key used for client side password encryption.
	 * It return the servers as defined in APPLICATION_SERVERS at configserver.php file.
	 * If this is not defined, it return 'this' application server info (that is only one entry) as defined in serverinfo.php file.
	 *
	 * @throws BizException
	 * @return array List of ServerInfo
	 */
	static public function getServers()
	{
		require_once BASEDIR.'/server/dbclasses/DBLog.class.php';
		DBlog::logService( '', 'GetServers' );
		
		// Create complete Server Info record:
		if( defined('APPLICATION_SERVERS') ) {
			$servers = unserialize(APPLICATION_SERVERS);
		} else { // Fallback; when nothing configured, this AS returns its own details
			$servers = self::getServerInfo();
		}
		
		$serverInfos = array(); // Array of servers that we will return
		if( $servers ) foreach( $servers as $serverInfo ) {
			$cryptkey = '';
			if( isset($cryptkey) && strlen($cryptkey) > 0 ) {
				$serverInfo->CryptKey = $cryptkey;
			} else {
				$serverInfo->CryptKey = null;
			}
			
			// Add this server to return array
			$serverInfos[] = $serverInfo;
		}
		return $serverInfos;
	}
}
