<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.6.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteIssues_EnterpriseConnector.class.php';

class AdobeDps_AdmDeleteIssues extends AdmDeleteIssues_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_BEFOREAFTER; }

	final public function runBefore( AdmDeleteIssuesRequest &$req )
	{
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';

		$req->dpsIssueIds = array();
		foreach( $req->IssueIds as $issueId ) {
			$setup = new WW_Utils_ResolveBrandSetup();
			$setup->resolveIssuePubChannelBrand( $issueId );
			$pubChannelObj = $setup->getPubChannelInfo();

			// Get the Dps type issue id
			if( $pubChannelObj && $pubChannelObj->Type == 'dps' ) {
				$req->dpsIssueIds[] = $issueId;
			}
		}
	}

	final public function runAfter( AdmDeleteIssuesRequest $req, AdmDeleteIssuesResponse &$resp )
	{
		$resp = $resp;	// keep analyzer happy

		if( $req->dpsIssueIds ) foreach( $req->dpsIssueIds as $issueId ) {
			$this->cleanUpExportFolder( $issueId );
		}
	}

	final public function runOverruled( AdmDeleteIssuesRequest $req )
	{
		$req = $req;	// keep analyzer happy
	}

	/**
	 * Cleans up the Adobe Dps issue export folder.
	 *
	 * @param string $issueId
	 */
	private function cleanUpExportFolder( $issueId )
	{
		require_once BASEDIR.'/config/config_dps.php';
		$folder = ADOBEDPS_EXPORTDIR;
		$folder .= 'issue_'.$issueId.'/';

		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		if ( is_dir( $folder ) ) {
			$result = FolderUtils::cleanDirRecursive( $folder );
			if ( $result ) {
				LogHandler::Log( 'AdobeDps', 'INFO', "Issue export folder ($folder), is cleaned up." );
			} else {
				LogHandler::Log( 'AdobeDps', 'INFO', "Issue export folder ($folder), is partly cleaned up." );
			}
		} else {
			LogHandler::Log( 'AdobeDps', 'INFO', "Issue export folder ($folder) does not exist, nothing to be cleaned up." );
		}
	}
}
