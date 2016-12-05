<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.5
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class AdobeDps_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp)
	{
		$this->cleanUpExportFolder();

		// Only send the reader version when Content Station tries to login.
		if ( stristr($req->ClientAppName, 'Content Station') ) {
			require_once BASEDIR.'/config/config_dps.php';
			$versions = array();
			if ( defined ( 'ADOBEDPS_READER_VERSIONS' ) ) {
				$versions = unserialize( ADOBEDPS_READER_VERSIONS );
			}
			// First prepend an empty option before the list
			$valueList = array_merge( array( '' ), $versions );
			if ( !isset($resp->ServerInfo->FeatureSet) || !is_array($resp->ServerInfo->FeatureSet) ) {
				// Should never happen
				$resp->ServerInfo->FeatureSet = array();
			}
			// Add the new feature
			$resp->ServerInfo->FeatureSet[] = new Feature('DpsTargetReaderVersions', implode(',',$valueList));
		}
	}
	
	final public function runOverruled( WflLogOnRequest $req) {}
	final public function runBefore( WflLogOnRequest &$req) {}
	
	/**
	 * Cleans up the export folder for digital magazines. All files older than 1
	 * day are deleted. Export folders are created by user id.
	 */
	private function cleanUpExportFolder()
	{
		require_once BASEDIR.'/config/config_dps.php';
		$folder = ADOBEDPS_EXPORTDIR;
		$folder .= 'user_'.BizSession::getUserInfo('id').'/';
		$shortUserName = BizSession::getUserInfo('user');

		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		if ( is_dir( $folder ) ) {
			$result = FolderUtils::cleanDirRecursive( $folder, false, (time() - (60 * 60 * 24) ) );
			// Clean up files (and folders) older than 1 day
			if ( $result ) {
				LogHandler::Log( 'DigitalMagazine', 'INFO', "Export folder ($folder) of user $shortUserName, is cleaned up." );
			} else {
				LogHandler::Log( 'DigitalMagazine', 'INFO', "Export folder ($folder) of user $shortUserName, is partly cleaned up." );
			}
		} else {
				LogHandler::Log( 'DigitalMagazine', 'INFO', "No export folder of user $shortUserName, nothing to be cleaned up." );
		}
		
	}
}
