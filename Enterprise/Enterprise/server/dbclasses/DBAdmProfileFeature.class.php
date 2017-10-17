<?php
/**
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides functions to exchange data with the profile features table in the database.
 *
 * This class encapsulates all database functionality, so other layers do not have to deal
 * with database-specific entities such as fields and rows. Instead, information is exchanged
 * in the form of data objects.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmProfileFeature extends DBBase
{
	const TABLENAME = 'profilefeatures';

	/**
	 * Updates the profile features for a single access profile
	 *
	 * @param integer $accessProfileId
	 * @param AdmProfileFeature[] $profileFeatures A list of profile features to be updated.
	 * @throws BizException on SQL error.
	 */
	public static function updateProfileFeatures( $accessProfileId, array $profileFeatures )
	{
		foreach( $profileFeatures as $profileFeatureId => $profileFeature ) {
			$row = self::objToRow( $accessProfileId, $profileFeatureId, $profileFeature );
			if( $row['value'] == 'Yes' ) {
				self::insertRow( self::TABLENAME, $row );
				if( self::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
				}
			} elseif( $row['value'] == 'No' ) {
				$where = '`profile` = ? AND `feature` = ?';
				$params = array( intval( $accessProfileId ), intval( $profileFeatureId ) );
				self::deleteRows( self::TABLENAME, $where, $params );
				if( self::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
				}
			}
		}
	}

	/**
	 * Queries all profile features from the database for one access profile.
	 *
	 * @param integer $accessProfileId
	 * @return AdmProfileFeature[]
	 * @throws BizException on SQL error.
	 */
	public static function getProfileFeatures( $accessProfileId )
	{
		$where = '`profile` = ?';
		$params = array( intval( $accessProfileId ) );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$profileFeatures = array();
		foreach( $rows as $row ) {
			$profileFeature = self::rowToObj( $accessProfileId, $row );
			$profileFeatures[$row['feature']] = $profileFeature;
		}
		return $profileFeatures;
	}

	/**
	 * Deletes all profile features from the database based
	 * on a list of access profile ids.
	 *
	 * @param integer[] $accessProfileIds
	 * @throws BizException on SQL error.
	 */
	public static function deleteProfileFeatures( $accessProfileIds )
	{
		$where = self::addIntArrayToWhereClause( 'profile', $accessProfileIds );
		if( $where ) {
			self::deleteRows( self::TABLENAME, $where );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}
	}

	/**
	 * Converts a profile feature object to a database profile feature row.
	 *
	 * @param integer $accessProfileId
	 * @param integer $profileFeatureId
	 * @param AdmProfileFeature $profileFeature
	 * @return array
	 */
	public static function objToRow( $accessProfileId, $profileFeatureId, $profileFeature )
	{
		$row = array();
		$row['profile'] = $accessProfileId;
		$row['feature'] = $profileFeatureId;
		$row['value'] = $profileFeature->Value;
		return $row;
	}

	/**
	 * Converts a database profile feature row to a profile feature object.
	 *
	 * @param integer &$accessProfileId
	 * @param array $row
	 * @return AdmProfileFeature
	 */
	public static function rowToObj( &$accessProfileId, $row )
	{
		$accessProfileId = $row['profile'];
		$profileFeature = new AdmProfileFeature();
		$profileFeature->Id = $row['feature'];
		$profileFeature->Value = $row['value'];
		return $profileFeature;
	}
}