<?php
/**
 * @package    	Enterprise
 * @subpackage HealthCheck2
 * @since       v9.7.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * The C_KICKER field is introduced since 9.7. Before the 'slugline' was used to set the kicker info on a dossier.
 * During the migration the content of the 'slugline' is used to fill the C_KICKER custom metadata field. The 'slugline'
 * itself will be made empty after the migration. This is only done for dossiers that have a target for an Adobe Dps
 * channel.
 */
class MigrateKickerContent
{
	const UPDATEFLAG = 'adobedps_kicker_migration';

	/**
	 * Handles the migration from the kicker info from the 'slugline' to the custom property 'C_KICKER'.
	 * If the custom property C_KICKER is not yet added to the model it is added automatically. After that the content
	 * of the 'slugline' property is copied to the custom property and the slugline is cleaned.
	 * The 'smart_config' table is updated to indicate that the migration is done.
	 *
	 * @return bool Migration was successful.
	 */
	static public function doMigrateKickerContent()
	{
		if( self::isUpdated() ) {
			return true;
		}

		$result = true;
		self::setMigrateKickerPropFlag( '0' ); // To indicate that the conversion process has started.

		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		do {
			if ( !self::isKickerPropertySet() ) {
				if ( !self::addKickerToModel() ) {
					$result = false;
					break;
				}
			}

			if( !self::mapFromOldToNewPropety() ) {
				$result = false;
				break;
			}

			self::setMigrateKickerPropFlag( '1' ); // To indicate that the conversion process has completed successfully.
		} while( false );

		return $result;
	}

	/**
	 * Checks if 'adobedps_kicker_migration' flag already exists in smart_config table.
	 * When the flag exists, meaning the conversion has been taken place, hence returns True to indicate it has been
	 * updated; False otherwise.
	 * In case no dossier object has been targeted for a Dps channel, e.g. after a new installation or
	 * when the Adobe Dps plug-in is just activated, the flag will be set and this is seen as conversion is
	 * done.
	 *
	 * @return bool True when the update has been done; False otherwise.
	 */
	static public function isUpdated()
	{
		$isUpdated = false;
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		$row = DBConfig::getRow( DBConfig::TABLENAME, 'name = ?', '*', array(self::UPDATEFLAG) );
		if ( $row ) {
			$isUpdated = ($row['value'] == '1');
		}

		if ( !$isUpdated && !self::getDpsDossierWithKicker( 1 ) ) { // Flag is not set, see if there are dossiers to update.
			return self::setMigrateKickerPropFlag( 1 );
		}

		return $isUpdated;
	}

	/**
	 * Stores a variable in the database to denote if the conversion has been started
	 * or has been completed.
	 *
	 * @param string flag '0' means busy, '1' means completed.
	 * @return bool Whether or not the conversion flag was set correctly.
	 */
	static public function setMigrateKickerPropFlag( $flag )
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::UPDATEFLAG , $flag );
	}

	/**
	 * Adds the custom property 'C_KICKER' to the model.
	 *
	 * @return bool true property is added, else false.
	 */
	static public function addKickerToModel()
	{
		$values = array(
			'publication' => 0,
			'objtype' => '',
			'name' => 'C_KICKER',
			'dispname' => 'Kicker',
			'category' => '',
			'type' => 'list',
			'defaultvalue' => '',
			'valuelist' => '',
			'minvalue' => '',
			'maxvalue' => '',
			'maxlen' => 0,
			'dbupdated' => 0
		);

		try {
			require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
			$id = BizProperty::addProperty( $values );
			if ( $id ) {
				require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
				BizCustomField::insertFieldAtModel( 'objects', 'C_KICKER', 'list' );
			}
			return true;
		} catch ( BizException $e ) {
			return false;
		}
	}

	/**
	 * Gets the dossiers (ids) and the slugline of the dossiers with an object target for an Adobe Dps channel.
	 * Only dossiers with a filled 'slugline' are returned as these dossiers are candidates for migrating the kicker
	 * info. All will be returned if no limit is given else the limit.
	 *
	 * @param mixed $limit Number of dossier rows to return.
	 * @return array Array with database rows or an empty array if nothing is found.
	 * @throws BizException
	 */
	static private function getDpsDossierWithKicker( $limit=null )
	{
		$dbDriver = DBDriverFactory::gen();
		$objectTable = $dbDriver->tablename( 'objects' );
		$channelTable = $dbDriver->tablename( 'channels' );
		$issueTable = $dbDriver->tablename( 'issues' );
		$targetTable = $dbDriver->tablename( 'targets' );

		$sql =  'SELECT o.`id`, o.`slugline` '.
				'FROM '.$objectTable.' o '.
				'INNER JOIN '.$targetTable.' tar ON ( tar.`objectid` = o.`id` ) '.
				'INNER JOIN '.$issueTable.' iss ON ( iss.`id` = tar.`issueid` ) '.
				'INNER JOIN '.$channelTable.' chn ON ( chn.`id` = iss.`channelid` ) '.
				'WHERE o.`type` = ? '.
				'AND chn.`publishsystem` = ? '.
				'AND o.`slugline` != ? ';
		if ( $limit ) {
			$sql = $dbDriver->limitquery( $sql, 0, $limit );
		}
		$params = array( 'Dossier', 'AdobeDps', '' );
		$sth = $dbDriver->query( $sql, $params );
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$rows = DBBase::fetchResults( $sth, 'id' );

		if ( $rows ) {
			return  $rows;
		}

		return array();
	}

	/**
	 * Checks if the custom property 'C_KICKER' is set.
	 *
	 * @return bool true if custom property is set else false.
	 */
	static private function isKickerPropertySet()
	{
		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$where = 'name = ? ';
		$params = array( 'C_KICKER' );
		return (bool) DBBase::getRow( 'properties', $where, array('id'), $params );
	}

	/**
	 * Retrieves all database rows for dossiers targeted for an Adobe Dps channel. But only those for which the
	 * slugline is not empty. Next the value of the slugline is copied to the custom property C_KICKER. The slugline
	 * itself is made empty.
	 *
	 * @return bool True if the database is updated succesfully. Else false.
	 */
	static private function mapFromOldToNewPropety()
	{
		$dpsDossiers = self::getDpsDossierWithKicker();

		try {
			if ( $dpsDossiers ) foreach ( $dpsDossiers as $dpsDossier ) {
				require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
				$values = array('C_KICKER' => $dpsDossier['slugline'], 'slugline' => '');
				$where = '`id` = ' . $dpsDossier['id'];
				DBBase::updateRow( 'objects', $values, $where );
			}
		} catch ( BizException $e ) {
			return false;
		}

		return true;
	}
}