<?php
/**
 * Adds a new profile feature "Open for Edit (Unplaced)" to all existing access profiles in the database.
 *
 * @since 		v10.1.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/dbupgrades/Module.class.php';

class WW_DbScripts_DbUpgrades_OpenForEditProfileEntry extends WW_DbScripts_DbUpgrades_Module
{
	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name
	 */
	protected function getUpdateFlag()
	{
		return 'add_open_for_edit_unplaced'; // Important: Never change this name.
	}

	/**
	 * Runs the DB migration procedure.
	 *
	 * @return bool True if the upgrade was successful, false if it was not.
	 */
	public function run()
	{
		require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';

		try {
			$dbDriver = DBDriverFactory::gen();
			$dbp = $dbDriver->tablename('profiles');
			$dbpf = $dbDriver->tablename('profilefeatures');
			$openForEditCode = BizAccessFeatureProfiles::FILE_OPENEDIT;
			$openForEditUnplacedCode = BizAccessFeatureProfiles::FILE_OPENEDIT_UNPLACED;
			$result = $dbDriver->query("SELECT DISTINCT p.`id` FROM {$dbp} p, {$dbpf} pf WHERE p.`id` = pf.`profile` AND pf.`feature` = {$openForEditCode}");

			require_once BASEDIR.'/server/dbclasses/DBBase.class.php';
			while( $row = $dbDriver->fetch($result) ) {
				DBBase::insertRow( 'profilefeatures',
					array('profile' => $row['id'],
							'feature' => $openForEditUnplacedCode,
							'value' => 'Yes') );
			}
			return true;
		} catch( Exception $ex ) {
			return false;
		}
	}

	public function introduced()
	{
		return '10.1';
	}
}