<?php
/**
 * Maintains Elvis authentication tokens in the database.
 *
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class Elvis_DbClasses_Token extends DBBase
{
	const TABLENAME = 'lvs_tokens';

	/**
	 * Save an Elvis OAuth access token.
	 *
	 * @param Elvis_DataClasses_Token $accessToken
	 */
	public static function save( Elvis_DataClasses_Token $accessToken )
	{
		$values = self::objToRow( $accessToken );
		//	rather update before insert because it's more likely it exists already but there's no way to see if the update
		// updated a row or not
		$insertResult = self::insertRow( self::TABLENAME, $values, true, null, false );
		if( $insertResult === false ) {
			// record already exists, update it
			self::updateRow( self::TABLENAME, $values, '`entuser` = ?', array( strval( $accessToken->enterpriseUser ) ) );
		}
	}

	/**
	 * Return Elvis OAuth access token for given user.
	 *
	 * @param string $enterpriseUser Short user name returned from BizSession::getShortUserName().
	 * @return Elvis_DataClasses_Token|null Elvis OAuth access token or null when token isn't found.
	 */
	public static function get( string $enterpriseUser )
	{
		$row = self::getRow( self::TABLENAME, '`entuser` = ?', array( 'entuser', 'elvisuser', 'token' ), array( $enterpriseUser ) );
		return $row ? self::rowToObj( $row ) : null;
	}

	/**
	 * Remove the OAuth access token for given user.
	 *
	 * @param string $enterpriseUser Short user name returned from BizSession::getShortUserName().
	 * @return bool
	 */
	public static function delete( string $enterpriseUser )
	{
		return (bool)self::deleteRows( self::TABLENAME, '`entuser` = ?', array( $enterpriseUser ) );
	}

	/**
	 * Convert a data object to a DB row.
	 *
	 * @param Elvis_DataClasses_Token $obj
	 * @return array DB row
	 */
	public static function objToRow( $obj )
	{
		return array(
			'entuser'  => $obj->enterpriseUser,
			'elvisuser'=> $obj->elvisUser,
			'token'    => $obj->accessToken
		);
	}

	/**
	 * Convert a DB row to a data object.
	 *
	 * @param array $row
	 * @return Elvis_DataClasses_Token
	 */
	public static function rowToObj( $row )
	{
		$obj = new Elvis_DataClasses_Token(
			strval( $row['entuser'] ),
			strval( $row['elvisuser'] ),
			strval( $row['token'] )
		);
		return $obj;
	}
}