<?php
/**
 * Updates hidden properties in smart_properties to be hidden in the admin ui.
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/DbUpgradeModule.class.php';
 
class DBUpgradeHiddenProperties extends DbUpgradeModule
{
	const NAME = 'UpgradeHiddenProperties';
	
	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_properties_admin_ui'; // Important: never change this name
	}
	
	/**
	 * Correctly sets the smart_properties adminui column for hidden properties.
	 *
	 * @return bool Whether or not the conversion was succesful.
	 */
	public function run()
	{
		$result = $this->updatePropertyTable();
		if( !$result ) {
			LogHandler::Log( self::NAME, 'ERROR', 'Properties table could not be updated correctly.' );
		}
		return $result;
	}

	public function introduced()
	{
		return '9.0';
	}

	/**
	 * Updates the smart_properties table.
	 *
	 * Sets the field `adminui` to empty for any property starting with `C_HIDDEN_`.
	 *
	 * @return bool Whether or not the conversion was succesful.
	 */
	private function updatePropertyTable()
	{
		$dbh = DBDriverFactory::gen();
		$dba = $dbh->tablename( "properties" );
		$sql = "UPDATE $dba SET `adminui`='' WHERE `name` LIKE 'C_HIDDEN_%'";
		$sth = $dbh->query( $sql );
		return (bool)$sth;
	}
}
