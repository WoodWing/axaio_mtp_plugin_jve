<?php
/**
 * @package    	Enterprise
 * @subpackage HealthCheck2
 * @since       v7.6.11
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 * ConvertFreePaidPerDevice class handles the conversion of the Free/Paid flag per issue to a flag per issue/edition.
 * The issue/edition combination at Enterprise side links to a published issue at Adobe Dps side. 
 *
 */
class SyncFreePaidPerDevice
{
	const UPDATEFLAG = 'adobedps_freepaid_sync'; 

	/**
	 * This function tries to synchronize the Free/Paid setting between Adobe Dps and the information stored in the
	 * database. All issues for the different Adobe Dps accounts are retrieved from the Adobe system. Per issue the
	 * DpsStore value (noChargeStore/appleStore) is compared with the value of that issue in the database. If they
	 * differ the value in the database is updated. A configuration setting is set to indicate that the process has run. 
	 * If the run was successful the setting is set to '1'.
	 *  
	 * @return Success true/false
	 */
	static public function doSync()
	{
		if ( !self::isUpdateNeeded() ) {
			return true;
		}	

		self::setSyncFlag( '0' ); // By setting the flag we know that the sync process has run.
		$dpsConfigs = self::getAccountsAdobeDps();
		if ( empty( $dpsConfigs )) {
			LogHandler::Log( 'AdobeDps', 'WARN', 'Synchronization of the Free/Paid setting is not possible. No Adobe Dps account is found.' );
			return false;
		}

		$result = true;
		$dbPublFieldsByExtId = self::getStoredPublishedFields();
		require_once BASEDIR.'/server/utils/DigitalPublishingSuiteClient.class.php';
		foreach( $dpsConfigs as $dpsConfig) {
			// Create client proxy that connects to Adobe DPS server.
			$dpsService = new WW_Utils_DigitalPublishingSuiteClient( $dpsConfig['serverurl'], $dpsConfig['username'] );
			try {
				$dpsService->signIn( $dpsConfig['username'], $dpsConfig['password'] );
			} catch ( BizException $e) {
				LogHandler::Log( 'AdobeDps', 'ERROR', "Sign in to the Adobe Dps system failed. Error: $e" );
				$result = false;
				continue;
			}
			try {
				$dpsIssueInfos = self::getIssueInfos( $dpsService );
			} catch ( BizException $e ) {
				LogHandler::Log( 'AdobeDps', 'ERROR', "Retrieving issue information from the Adobe Dps system failed. Error: $e" );
				$result = false;
				continue;
			}
			$interimResult = self::updateFreePaidFlagsOfPublishedIssues( $dpsIssueInfos, $dbPublFieldsByExtId );
			$result = $result === false ? false : $interimResult;
		}

		if ( $result ) {
			self::setSyncFlag('1');
			$result = self::cleanUpFreePaidOffIssue();
		}

		return $result;
	}

	/**
	 * Reads the accounts as configured.
	 * @return array with unique Adobe Dps accounts
	 */
	static private function getAccountsAdobeDps()
	{
		require_once BASEDIR.'/config/config_dps.php';
		$dpsConfigs = unserialize( ADOBEDPS_ACCOUNTS );
		$dpsAccounts = array();
		$accountNames = array();

		foreach( $dpsConfigs as $dpsConfigChannel ) {
			foreach( $dpsConfigChannel as $dpsConfigEdition) {
				if ( !array_key_exists( $dpsConfigEdition['username'], $accountNames )) {
					$dpsAccounts[] = $dpsConfigEdition;
					$accountNames[$dpsConfigEdition['username']] = true;
				}
			}
		}

		return $dpsAccounts;
	}

	/**
	 * Checks if an update is needed. 
	 * @return boolean update is needed true/false
	 */
	static private function isUpdateNeeded()
	{
		if ( self::isUpdated() ) {
			LogHandler::Log( 'AdobeDps', 'INFO', 'Synchronization of the Free/Paid setting is already done.' );
			return false;
		}

		$where = '';
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		if ( !DBBase::getRow( 'pubpublishedissues', $where, array('id') )) {
			// No publish issues found means executed successfully.
			self::setSyncFlag('1');
			LogHandler::Log( 'AdobeDps', 'INFO', 'Synchronization of the Free/Paid setting is not needed. No published issues are found.' );
			return false;
		}

		return true;
	}

	/**
	 * Stores a variable in the database to denote that the conversion was done successfully.
	 * @return bool Whether or not the updated flag was set correctly.
	 */
	static private function setSyncFlag( $flag )
	{
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::UPDATEFLAG , $flag );
	}			

	/**
	 * Returns whether or not the conversion has already been completed or not.
	 * @return bool Whether or not the Conversion has been completed.
	 */
	static public function isUpdated()
	{
		$isUpdated = false;
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		$row = DBConfig::getRow( DBConfig::TABLENAME, 'name = ?', '*', array( self::UPDATEFLAG ) );

		if ( $row ) {
			$isUpdated = ($row['value'] == '1');
		}
		return $isUpdated;
	}		

	/**
	 * Request Adobe DPS server for information of all issues that are currently online.
	 *
	 * @param WW_Utils_DigitalPublishingSuiteClient $dpsService
	 * @return array of DPS issue infos. Index = DPS issue id, values = issue info (array).
	 */
	static private function getIssueInfos( WW_Utils_DigitalPublishingSuiteClient $dpsService )
	{
		// Collect list of available issues at Adobe DPS.
		$allIssues = true;        // TRUE to get test and production issues. FALSE to get production issues only.
		$title = null;            // Magazine title. If specified the list is restricted to issues matching that publication.
		$includeDisabled = true;  // TRUE to include disabled issues also. Only relevant when $allIssues is TRUE.
		$includeTest = true;      // TRUE to include 'test' issues also. Only relevant when $allIssues is FALSE.
		// If a dimension value is provided, then include only issues with the specified target dimension. 
		// If "all", then include all dimensions. If parameter is not provided, then only issues with 
		// default iPad dimension ("1024x768") are included.
		$targetDimension = 'all';

		return $dpsService->getIssueInfos( $allIssues, $title, $includeDisabled, $includeTest, $targetDimension );
	}	

	/**
	 * Based on the Free/Paid setting retrieved from the Adobe system the setting stored in the database is updated.
	 * An update is needed if the setting differs. 
	 * @param array $dpsIssueInfos All issue information retrieved from Adobe Dps.
	 * @return bool True if no error else false.
	 */
	static private function updateFreePaidFlagsOfPublishedIssues( $dpsIssueInfos, $dbPublFieldsByExtId )
	{
		if ( $dpsIssueInfos) foreach( $dpsIssueInfos as $externalid => $dpsIssueInfo) {
			$dpsPaidFreeSetting = $dpsIssueInfo['broker'];
			if ( array_key_exists($externalid, $dbPublFieldsByExtId)) {
				$dbPublFields = $dbPublFieldsByExtId[ $externalid];	
				if ( $dbPublFields) foreach ( $dbPublFields as $dbPublField) {
					if ( $dbPublField->Key == 'DpsStore' && $dbPublField->Values[0] !== $dpsPaidFreeSetting ) {
						$dbPublField->Values[0] = $dpsPaidFreeSetting;
						return self::updateDBPublishedFields( $externalid, $dbPublFields );
					}
				}
			} else {
				$message  = "Link between Adobe Dps system and Enterprise does not exists. ";
				$message .=	"No publish information is stored for issue $externalid.";
				LogHandler::Log( 'AdobeDps', 'WARN', $message );
				// Just log a warning as it is not blocking if the issue is unknow at Enterprise side.	
			}
		}

		return true;
	}	

	/**
	 * Reads the published fields per external Id (Adobe Dps issue Id) from the database. Published fields are
	 * unserialized.  
	 * @return array with published fields as values an the external id as key.
	 */
	static private function getStoredPublishedFields()
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$where = '`externalid` != ?';
		$params = array( '' );
		$rows = DBBase::listRows('pubpublishedissues', 'externalid', '', $where, array('fields', 'externalid'), $params);
		$result = array();
		foreach( $rows as $externalId => $columns) {
			$result[ $externalId ] = unserialize($columns['fields']);			
		}

		return $result;
	}

	/**
	 * Updates the publish fields of an issue/device.
	 * @param type $externalid
	 * @param type $dbPublishedFields
	 */
	static private function updateDBPublishedFields( $externalid, $dbPublishedFields )
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$where = "`externalid` = ? ";
		$params = array( $externalid );
		$blob = serialize( $dbPublishedFields);
		return DBBase::updateRow('pubpublishedissues', array( 'fields' => '#BLOB#'), $where, $params, $blob );
	}	

	/**
	 * Cleans the channeldata table for the Free/Paid setting.
	 * @return bool Success true/false
	 */
	static private function cleanUpFreePaidOffIssue()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$where = '`name` = ?';
		$params = array('C_DPS_IS_FREE');
		$result = DBBase::deleteRows('channeldata', $where, $params);
		return $result === true ? true : false;
	}	
}