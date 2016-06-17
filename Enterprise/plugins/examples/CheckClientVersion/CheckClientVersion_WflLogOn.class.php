<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Class for checking the client version.
 * This plug-in is provided "as is" without warranty of WoodWing Software.
 **/

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class CheckClientVersion_WflLogOn extends WflLogOn_EnterpriseConnector
{
	// Determine how we want to get called by core server
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFORE; }

	final public function runBefore( WflLogOnRequest &$req )
	{
		$preventLogOn = false;		
		
		switch ($req->ClientAppName) {
			case 'Content Station':
				// Content Station version format: v7.0 build 47
				if ( $this->compareVersion( $req->ClientAppVersion, CONTENT_STATION_MIN_VERSION ) < 0 ) {
					$preventLogOn = true;
				}				break;				
			case 'InDesign':
				// InDesign version format: v7.0.0 build 48
				if ( $this->compareVersion($req->ClientAppVersion, INDESIGN_MIN_VERSION) < 0 ) {
					$preventLogOn = true;
				}
				break;
			case 'InCopy':
				// InCopy version format: v7.0.0 build 48
				if ( $this->compareVersion($req->ClientAppVersion, INCOPY_MIN_VERSION) < 0 ) {
					$preventLogOn = true;	
				}
				break;
			case 'SmartBrowser':
				// SmartBrowser version format: v6.1 Build 16
				if ( $this->compareVersion($req->ClientAppVersion, SMART_BROWSER_MIN_VERSION) < 0) {
					$preventLogOn = true;
				}
				break;					
		}

		if ( $preventLogOn ) {
			throw new BizException( 'ERR_ERROR', 'Client', 'Please update your client version' );
		}
	}

	// Not called. See getRunMode().
	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp )
	{
		$req = $req;
		$resp = $resp;
	}

	// Not called. See getRunMode().
	final public function runOverruled( WflLogOnRequest $req )
	{
		$req = $req;
	}

	/**
	 * Compare 2 versions 
	 *
	 * @param string $version1
	 * @param string $version2
	 * @return < 0 if $version1 is lower, > 0 if $version1 is higher, 0 if equal 
	 */
	protected function compareVersion( $version1, $version2 )
	{
		$longVersion1 = $this->getLongVersion( $version1 );
		$longVersion2 = $this->getLongVersion( $version2 );
		
		return version_compare( $longVersion1, $longVersion2 );
	}

	/**
	 * Function to convert the version from format 'vx.x build x' or vx.x.x build x' to x.x.x.x
	 *
	 * @param string $version
	 * @return string
	 */
	protected function getLongVersion( $version )
	{
		// TODO: This function is quicky written to make it work.
		// TODO: Rewrite this function with good code

		// Get the Major version		
		$pos1 = strpos( $version, '.' );
		$cmpVersion = substr($version, 1, $pos1-1);

		// Check if there is a revision number
		$pos2 = strpos( $version, '.', $pos1 + 1);
		if ( $pos2 == FALSE) {
			// The version is vx.x build xx (ie v7.0 build 48)

			// Get the minor version
			$pos2 = strpos( $version, ' ', $pos1 );
			$cmpVersion .= substr($version, $pos1, $pos2 - $pos1);

			// Revision number not given, add '.0'
			$cmpVersion .= '.0';

			$pos1 = $pos2;		// UGLY!!!!
		}
		else {
			// The version is vx.x.x build xx (ie v7.0.0 build 48)

			// Get the minor version
			$cmpVersion .= substr($version, $pos1, $pos2 - $pos1);

			// Get the revision number
			$pos3 = strpos( $version, ' ', $pos2 );
			$cmpVersion .= substr($version, $pos1, $pos3 - $pos2);

			$pos1 = $pos3;		// UGLY!!!!
		}

		// Get the build number (look for the next ' ' which is after build)
		$cmpVersion .= '.';
		$pos2 = strpos( $version, ' ', $pos1 + 1);
		$cmpVersion .= substr($version, $pos2 + 1, strlen($version) - $pos2);

		return $cmpVersion;
	}
}