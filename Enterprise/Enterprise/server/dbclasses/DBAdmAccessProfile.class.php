<?php

/**
 * @package    Enterprise
 * @subpackage DBClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides functions to exchange data with the access profiles table in the database.
 *
 * This class encapsulates all database functionality, so other layers do not have to deal
 * with database-specific entities such as fields and rows. Instead, information is exchanged
 * in the form of data objects.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBAdmAccessProfile extends DBBase
{
	const TABLENAME = 'profiles';

	/**
	 * Checks if an access profile exists in the database.
	 *
	 * It uses the id of an access profile to see if there is already one
	 * in existence.
	 *
	 * @param AdmAccessProfile $accessProfile
	 * @return AdmAccessProfile|null
	 * @throws BizException on SQL error.
	 */
	public static function accessProfileNameExists( $accessProfile )
	{
		$where = '`profile` = ?';
		$params = array( strval( $accessProfile->Name ) );
		if( $accessProfile->Id ) { //for modify requests
			$where .= ' AND `id` <> ?';
			$params[] = intval( $accessProfile->Id );
		}
		$row = self::getRow( self::TABLENAME, $where, '*', $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Creates an access profile in de database.
	 *
	 * @param AdmAccessProfile $accessProfile
	 * @return boolean|integer The id of the created access profile.
	 * @throws BizException on SQL error.
	 */
	public static function createAccessProfile( $accessProfile )
	{
		$accessProfile->Id = null;
		$row = self::objToRow( $accessProfile );
		$retVal = self::insertRow( self::TABLENAME, $row );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $retVal;
	}

	/**
	 * Modifies an access profile in the database.
	 *
	 * @param AdmAccessProfile $accessProfile The access profile to be updated.
	 * @return bool Returns true if the update worked and false if the update failed.
	 * @throws BizException on SQL error.
	 */
	public static function modifyAccessProfile( $accessProfile )
	{
		$row = self::objToRow( $accessProfile );
		unset( $row['id'] );
		$where = '`id` = ?';
		$params = array( intval( $accessProfile->Id ) );
		$retVal =  self::updateRow( self::TABLENAME, $row, $where, $params );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}
		return $retVal;
	}

	/**
	 * Queries the requested access profiles from the database by id.
	 *
	 * @param integer[]|null $accessProfileIds access profile (ids) to retrieve. NULL to retrieve all.
	 * @return AdmAccessProfile[] A list containing the requested access profiles.
	 * @throws BizException on SQL error.
	 */
	public static function getAccessProfiles( $accessProfileIds )
	{
		$where = self::addIntArrayToWhereClause( 'id', $accessProfileIds );
		$params = array();
		$orderBy = array( 'code' => true, 'id' => true );
		$rows = self::listRows( self::TABLENAME, null, null, $where, '*', $params, $orderBy );
		if( self::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
		}

		$accessProfiles = array();
		foreach( $rows as $row ) {
			$accessProfiles[] = self::rowToObj( $row );
		}
		return $accessProfiles;
	}

	/**
	 * Deletes access profiles from the database.
	 *
	 * It also deletes the profile features belonging to the access profile
	 * if the access profile delete was successful.
	 *
	 * @param integer[] $accessProfileIds
	 * @throws BizException on SQL error.
	 */
	public static function deleteAccessProfiles( $accessProfileIds )
	{
		$success = false;
		$where = self::addIntArrayToWhereClause( 'id', $accessProfileIds );
		if( $where ) {
			$success = self::deleteRows( self::TABLENAME, $where );
			if( self::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
			}
		}

		if( $success ) {
			//cascade delete profile features linked to access profile
			require_once BASEDIR.'/server/dbclasses/DBAdmProfileFeature.class.php';
			DBAdmProfileFeature::deleteProfileFeatures( $accessProfileIds );

			//cascade delete authorizations made for access profile
			if( $accessProfileIds ) {
				$where = self::addIntArrayToWhereClause( 'profile', $accessProfileIds );
				if( $where ) {
					self::deleteRows( 'authorizations', $where );
					if( self::hasError() ) {
						throw new BizException( 'ERR_DATABASE', 'Server', self::getError() );
					}
				}
			}
		}
	}

	/**
	 * Converts an access profile object to a database access profile array.
	 *
	 * @param AdmAccessProfile $obj
	 * @return array
	 */
	public static function objToRow( $obj )
	{
		$row = array();
		if( !is_null( $obj->Id ) ) $row['id'] = intval( $obj->Id );
		if( !is_null( $obj->Name ) ) $row['profile'] = strval( $obj->Name );
		if( !is_null( $obj->SortOrder ) ) $row['code'] = intval( $obj->SortOrder );
		if( !is_null( $obj->Description ) ) $row['description'] = strval( $obj->Description );

		return $row;
	}

	/**
	 * Converts a database access profile array to an access profile object.
	 *
	 * @param array $row
	 * @return AdmAccessProfile
	 */
	public static function rowToObj( $row )
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		$obj = new AdmAccessProfile();
		$obj->Id = intval($row['id']);
		$obj->Name = strval($row['profile']);
		$obj->SortOrder = intval($row['code']);
		$obj->Description = strval($row['description']);

		return $obj;
	}
}