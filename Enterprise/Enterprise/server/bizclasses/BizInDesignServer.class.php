<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v6.5
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Enables the system admin to configure InDesign Server instances.
 */

class BizInDesignServer
{
	/**
	 * Returns list of InDesign Server configuration objects.
	 *
	 * @return InDesignServer[]
	 */
	public static function listInDesignServers()
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		$servers = DBInDesignServer::listInDesignServers();
		foreach( $servers as &$server ) {
			self::enrichServerObject( $server );
		}
		return $servers;
	}

	/**
	 * Retrieves one InDesign Server configuration (object) from DB.
	 *
	 * @param integer $serverId
	 * @return InDesignServer
	 */
	public static function getInDesignServer( $serverId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		$server = DBInDesignServer::getInDesignServer( $serverId );
		self::enrichServerObject( $server );
		return $server;
	}

	/**
	 * Returns a new default InDesign Server configuration (object), NOT from DB.
	 *
	 * @return InDesignServer
	 */
	public static function newInDesignServer()
	{
		require_once BASEDIR.'/server/dataclasses/InDesignServer.class.php';
		$defaultVersion = self::getMaxSupportedVersion();
		$server = new InDesignServer();
		$server->Id = 0;
		$server->HostName = '';
		$server->PortNumber = 0;
		$server->Description = '';
		$server->Active = true;
		$server->Prio1 = true;
		$server->Prio2 = true;
		$server->Prio3 = true;
		$server->Prio4 = true;
		$server->Prio5 = true;
		$server->ServerVersion = $defaultVersion;
		self::enrichServerObject( $server );
		return $server;
	}

	/**
	 * Removes one InDesign Server configuration (object) from DB.
	 *
	 * @param integer $serverId
	 * @throws BizException Throws BizException on DB error
	 */
	public static function deleteInDesignServer( $serverId )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		DBInDesignServer::deleteInDesignServer( $serverId );
	}
	
	/**
	 * Updates one InDesign Server configuration (object) at DB.
	 *
	 * @param InDesignServer $server
	 * @return InDesignServer
	 * @throws BizException Throws BizException on DB error
	 */
	public static function updateInDesignServer( InDesignServer $server )
	{
		// Check whether InDesign Server exist in the DB
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		$idsFound = DBInDesignServer::getInDesignServerByHostAndPort( $server->HostName, $server->PortNumber );
		if( $idsFound && $idsFound->Id != $server->Id ) {
			$errorString = $server->HostName . ':' . $server->PortNumber;
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client', null, null, array( '{IDS_INDSERVER}', $errorString ));
		}
		if( $server->Id == 0 ) {
			$retObj = DBInDesignServer::createInDesignServer( $server );
		} else {
			$retObj = DBInDesignServer::updateInDesignServer( $server );
		}
		self::enrichServerObject( $server );
		return $retObj;
	}
	
	/**
	 * Walks through an InDesign Server configuration objects (at DB) for which the version 
	 * is undetermined yet. Those versions are marked with "-1" at DB. Nevertheless, the
	 * returned object has a repaired version, which is best guess. This version might be
	 * or might NOT be the correct version. Use autoDetectServerVersion function to detect.
	 * 
	 * @param integer $iterId Server id used for iteration. Set to zero for first call. Keep passing updated version for next calls.
	 * @return InDesignServer The IDS configuration object. Null when no more found (all walked through).
	 */
	public static function nextIDSWithUnknownVersion( &$iterId )
	{
		if( is_null($iterId) ) {
			return null;
		}
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		$iterId = DBInDesignServer::nextIDSWithUnknownVersion( $iterId );
		if( is_null($iterId) ) {
			return null;
		}
		return self::getInDesignServer( $iterId );
	}

	/**
	 * Calls a given InDesign Server to determines the ServerVersion property runtime.
	 *
	 * @param InDesignServer $server
	 * @throws BizException Throws BizException on DB error
	 */
	public static function autoDetectServerVersion( InDesignServer &$server )
	{
		require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
		$prodInfoPathInDesignServer = WEBEDITDIRIDSERV.'idsdetectversion.dat';
		$prodInfoPath = WEBEDITDIR.'idsdetectversion.dat';
		if( file_exists( $prodInfoPath ) ) {
			unlink( $prodInfoPath ); // clear previous runs
		}
		BizInDesignServerJobs::createAndRunJob(
			file_get_contents( BASEDIR.'/server/admin/idsdetectversion.js' ),
			array( 'respfile' => $prodInfoPathInDesignServer ),
			'Auto detect IDS version', null, $server, // job type, object id, ids obj
			null, null, // min ids version, max ids version
			'InDesign Server admin page' // context
		);
		if( file_exists( $prodInfoPath ) ) {
			$idsVersion = file_get_contents($prodInfoPath);
			$verInfo = array();
			$adobeVersions = unserialize( ADOBE_VERSIONS);
			preg_match( '/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/', $idsVersion, $verInfo );
			if( count( $verInfo ) >= 4 ) { // major, minor, patch, build
				$idsVersion = "$verInfo[1].$verInfo[2]"; // major/minor
				// EN-85231 - When IDS version '8.1' is running, reset it to '8.0'
				// The reason for it is to match back CS6 official release version which is '8.0'
				if( $idsVersion == '8.1' ) {
					$idsVersion = '8.0';
				}
				if( $idsVersion != $server->ServerVersion ) {
					if( in_array($idsVersion, $adobeVersions )) {
						$server->ServerVersion = $idsVersion;
					}
				}
			}
		}
	}

	/**
	 * Lists the InDesign Server versions that are supported by current Enterprise Server.
	 * Typically used to fill combo boxes to let user pick configured version.
	 *
	 * @param InDesignServer $idObj
	 * @return array Keys are internal versions. Values are display versions with CS/CC prefix.
	 */
	public static function supportedServerVersions( InDesignServer $idObj )
	{
		$adobeVersions = unserialize( ADOBE_VERSIONS );
		$idsVersions = array();
		if( !is_null($idObj) && $idObj->ServerVersion == -1 ) {
			$idsVersions[-1] = '???'; // needed to show undetermined versions
		}
		foreach( $adobeVersions as $adobeVersion ) {
			$idsVersions[$adobeVersion] = self::convertInternalVersionToExternal($adobeVersion); 
		}
		return $idsVersions;
	}

	/**
	 * Converts the internal Adobe version number to the public know CS/CC version.
	 * Note that also versions that are unsupported by Enterprise can be converted.
	 *
	 * @param string $internalVersion
	 * @return string externalversion
	 */
	public static function convertInternalVersionToExternal( $internalVersion )
	{
		if( version_compare( $internalVersion, '8.0', '<=' ) ) {
			$internalVersion = floatval($internalVersion);
			$externalVersion = $internalVersion - 2.0;
			$externalVersionStr = 'CS'.$externalVersion; // v5=CS3, v6=CS4, v7=CS5, v7.5=CS5.5 v8=CS6
		} else {
			$versionMap = unserialize( ADOBE_VERSIONS_ALL );
			$externalVersionStr = array_search( $internalVersion, $versionMap );
			if( $externalVersionStr !== false ) {
				$externalVersionStr .= ' (v'.$internalVersion.')'; // e.g: CC2014 (v10.0)
			} else { // e.g. 10.3 should be mapped to 10.0 to resolve it to CC2014
				list( $major, $minor ) = explode( '.', $internalVersion );
				if( $minor !== '0' ) {
					$roundedVersion = $major.'.0';
					$externalVersionStr = array_search( $roundedVersion, $versionMap );
					if( $externalVersionStr !== false ) {
						$externalVersionStr .= ' (v'.$internalVersion.')'; // e.g: CC2014 (v10.3)
					} else { // could not resolve, let's take it literally
						$externalVersionStr = $internalVersion;
					}
				} else { // could not resolve, let's take it literally
					$externalVersionStr = $internalVersion;
				}
			}
 		}
		return $externalVersionStr;
	}
	
	/**
	 * Derives the min- and max IDS instance versions (to assign jobs to) for a given doc version.
	 *
	 * For example, when '10.3' is given, array( '10.0', '10.9' ) is returned.
	 *
	 * @since 9.7.0
	 * @param string $domVersion Internal document version in major.minor notation.
	 * @return string[] Min- and max versions
	 */
	public static function getServerMinMaxVersionForDocumentVersion( $domVersion )
	{
		list( $majorServerVersion, $minorServerVersion ) = explode( '.', $domVersion );
		if( $majorServerVersion == 7 ) { // CS5 or CS5.5?
			if( $minorServerVersion < 5 ) {
				$minServerVersion = $majorServerVersion.'.0';
				$maxServerVersion = $majorServerVersion.'.4';
			} else {
				$minServerVersion = $majorServerVersion.'.5';
				$maxServerVersion = $majorServerVersion.'.9';
			}
		} else {
			$minServerVersion = $majorServerVersion.'.0';
			$maxServerVersion = $majorServerVersion.'.9';
		}
		return array( $minServerVersion, $maxServerVersion );
	}
	
	/**
	 * Validates the server version and repairs it to fit within supported range of CS versions.
	 * The version reparation is NOT reflected to DB yet (on purpose).
	 * And, it enriches the given InDesign Server configuration object with two extra properties:
	 * - ServerURL       => Full URL to server (including host name and port number).
	 * - DisplayVersion  => Server version translated for displaying purposes.
	 *
	 * @param InDesignServer $server
	 */
	public static function enrichServerObject( InDesignServer &$server )
	{
		$server->ServerURL = self::createURL( $server->HostName.':'.$server->PortNumber );
		$server->Name = empty($server->Description) ? $server->ServerURL : $server->Description.' ('.$server->ServerURL.')';

		if ( $server->ServerVersion == -1 ) {
			$server->DisplayVersion = '???';
		} else {
			$server->DisplayVersion = self::convertInternalVersionToExternal( $server->ServerVersion );
		}
	}

	/** 
	 * Returns the maximum version number of supported Adobe versions.
	 *
	 * @return string version number in major.minor format.
	 */
	public static function getMaxSupportedVersion()
	{
		$versions = unserialize( ADOBE_VERSIONS );
		sort( $versions, SORT_NUMERIC );
		$maxSupportedVersion = array_pop( $versions );			
		return $maxSupportedVersion;
	}

	/** 
	 * Returns the minimum version number of supported Adobe versions.
	 *
	 * @return string version number in major.minor format.
	 */
	public static function getMinSupportedVersion()
	{
		$versions = unserialize( ADOBE_VERSIONS );
		sort( $versions, SORT_NUMERIC );
		$minSupportedVersion = $versions[0];
		return $minSupportedVersion;
	}

	/**
	 * Composes a display version string with minimum and maximum required IDS version info.
	 *
	 * Typically used for displaying purpose of defined version info for an IDS job.
	 * If no minimum version is provided (0.0), the minimum supported version defined for 
	 * the system is assumed. Same for the maximum version. When minimum and maximum versions 
	 * are the same, instead of composing a range, the minimum version is composed.
	 *
	 * @since 9.7.0
	 * @param string $minVersion Minimum required version in "major.minor" notation.
	 * @param string $maxVersion Maximum required version in "major.minor" notation.
	 * @return string Range with the minimum and maximum required version.
	 */
	public static function composeRequiredVersionInfo( $minVersion, $maxVersion )
	{
		$minVersion = $minVersion == '0.0' ? self::getMinSupportedVersion() : $minVersion;
		$maxVersion = $maxVersion == '0.0' ? self::getMaxSupportedVersion() : $maxVersion;
		$retVersion = self::convertInternalVersionToExternal( $minVersion );
		if( $minVersion != $maxVersion ) {
			$retVersion .= ' - '.self::convertInternalVersionToExternal( $maxVersion );
		}
		return $retVersion;
	}

	/**
	 * Checks if InDesign Server is responsive.
	 *
	 * @since 9.7.0 Moved from BizInDesignServerJob class.
	 * @param InDesignServer $server
	 * @return bool - responsive or not
	 */	
	public static function isResponsive( InDesignServer $server )
	{
		$urlParts = @parse_url( $server->ServerURL );
		$host = $urlParts['host'];
		$port = $urlParts['port'];
		$errorNumber = 0;
		$errorString = '';
		$socket = fsockopen( $host, $port, $errorNumber, $errorString, 3 );
		if( $socket ) {
			fclose( $socket );
			LogHandler::Log( 'idserver', 'DEBUG', "InDesign Server [{$server->Description}] is responding at URL [{$server->ServerURL}]" );
			$retVal = true;
		} else {
			LogHandler::Log( 'idserver', 'DEBUG', "InDesign Server [{$server->Description}] is NOT responding at URL [{$server->ServerURL}]" );
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Checks if InDesign Server handles requests.
	 *
	 * @since 9.7.0 Moved from BizInDesignServerJob class.
	 * @param InDesignServer $server
	 * @return bool - handling requests or not
	 */	
	public static function isHandlingJobs( $server )
	{
		require_once BASEDIR.'/server/protocols/soap/IdsSoapClient.php';
		
		$options = array( 'location' => $server->ServerURL, 'connection_timeout' => 5 ); // time out 5 seconds
		$soapclient = new WW_SOAP_IdsSoapClient( null, $options );
		$scriptParams = array('scriptLanguage' => 'javascript');
		$scriptParams['scriptText'] = "
			// if InDesign Server has documents open.... it is still working on something
			function checkBusy () {
				app.consoleout('Server instances has -> [' + app.documents.length + '] documents open.');
				if ( app.documents.length > 0 ) {
					return 'BUSY';
				}
				
				return 'NOT BUSY';
			}
			checkBusy();
			";
		$soapParams = array('runScriptParameters' => $scriptParams );
		$isBusy = true;
		try {
			$jobResult = $soapclient->RunScript( $soapParams );
			$jobResult = (array)$jobResult; // let's act like it was before (v6.1 or earlier)
			if( $jobResult['errorNumber'] == 0 ) {
				if ( $jobResult['scriptResult'] == 'BUSY' ) {
					LogHandler::Log( 'idserver', 'INFO', 'InDesign Server ['.$server->ServerURL.'] is still busy' );
				} else {
					LogHandler::Log( 'idserver', 'INFO', 'InDesign Server ['.$server->ServerURL.'] is not busy' );
					$isBusy = false;
				}
			} else {
				LogHandler::Log('idserver', 'INFO', 'Assume InDesign Server ['.$server->ServerURL.'] is still busy [ error code: '.$jobResult['errorNumber'].']' );
			}
		} catch( SoapFault $e ) {
			LogHandler::Log('idserver', 'ERROR', 'Script failed on InDesign Server ['.$server->ServerURL.']: '.$e->getMessage() );
		} catch( Exception $e ) {
			LogHandler::Log('idserver', 'INFO', 'Assume InDesign Server ['.$server->ServerURL.'] is still busy [exception: '.$e.']' );
		}
		// L> On fatal errors, we assume that IDS is busy (since we could not talk successfully).

		return $isBusy;
	}

	/**
	 * Returns InDesign Servers available to execute a job. The InDesign Server version
	 * should match the IDS version specified in the job. This can be no version, a minimum 
	 * version, a maximum version, or both. If it is know that certain servers are non-responsive, 
	 * their ids can be passed to keep them out of the result.
	 *
	 * @param string $jobId
	 * @param array $nonResponsiveServers
	 * @param integer[] $excludePrios Exclude IDS instances that serve these queues (job prios).
	 * @param boolean $includeBusy TRUE to include busy IDS instances as well (test mode), FALSE to exclude (production mode).
	 * @return integer[] List of available IDS server ids.
	 * @throws BizException When invalid params given or fatal SQL error occurs.
	 */
	static public function getAvailableServerIdsForJob( 
		$jobId, array $nonResponsiveServers, array $excludePrios, $includeBusy )
	{
		require_once BASEDIR.'/server/dbclasses/DBInDesignServer.class.php';
		return DBInDesignServer::getAvailableServerIdsForJob( 
			$jobId, $nonResponsiveServers, $excludePrios, $includeBusy );
	}
	
	// - - - - - - - - - - - - - - - - PRIVATE FUNCTIONS - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Adds http:// to a url if it is not yet starting with http
	 *
	 * @param string $url - URL including ('http:' or 'https://') or excluding 'http:' prefix
	 * @return string $url - URL including 'http://' or 'https://'
	 */	
	private static function createURL( $url ) 
	{
		return ( substr($url,0,4) != 'http') ? 'http://' . $url : $url;
	}

	/**
	 * Check if all the Priorities are covered
	 *
	 * @since 9.6.0
	 * @return string|null Return a localized string if there is one or more priorities uncovered. Return null when every priority is covered.
	 */
	public static function checkCoveredPriorities()
	{
		// Check configured InDesign Servers
		require_once BASEDIR.'/server/bizclasses/BizInDesignServer.class.php';
		$servers = BizInDesignServer::listInDesignServers();

		$prio1 = false;
		$prio2 = false;
		$prio3 = false;
		$prio4 = false;
		$prio5 = false;

		// Check if all the prios are covered at least at 1 of the InDesign Servers.
		foreach( $servers as $server ) {
			if( $server->Active ) {
				if( $server->Prio1 ) {
					$prio1 = true;
				}
				if( $server->Prio2 ) {
					$prio2 = true;
				}
				if( $server->Prio3 ) {
					$prio3 = true;
				}
				if( $server->Prio4 ) {
					$prio4 = true;
				}
				if( $server->Prio5 ) {
					$prio5 = true;
				}
				if( $prio1 && $prio2 && $prio3 && $prio4 && $prio5 ){
					break;
				}
			}
		}

		// Create the error string
		$unprocessedPrios = array();
		if( !$prio1 ){
			$unprocessedPrios[] = BizResources::localize( 'IDS_PRIO_1' );
		}
		if( !$prio2 ){
			$unprocessedPrios[] = BizResources::localize( 'IDS_PRIO_2' );
		}
		if( !$prio3 ){
			$unprocessedPrios[] = BizResources::localize( 'IDS_PRIO_3' );
		}
		if( !$prio4 ){
			$unprocessedPrios[] = BizResources::localize( 'IDS_PRIO_4' );
		}
		if( !$prio5 ){
			$unprocessedPrios[] = BizResources::localize( 'IDS_PRIO_5' );
		}

		$retVal = null;
		if( $unprocessedPrios ){
			$retVal = BizResources::localize('IDS_NO_MATCHING_PRIO', true, array( implode(', ', $unprocessedPrios) ));
		}

		return $retVal;
	}
}