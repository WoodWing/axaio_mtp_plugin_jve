<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Maintains Elvis authentication tokens in the database.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class Elvis_DbClasses_Token extends DBBase
{
	const TABLENAME = 'lvs_tokens';

	/**
	 * Save an Elvis OAuth access token.
	 *
	 * @param string $shortUserName short user name returned from BizSession::getShortUserName().
	 * @param string $accessToken Elvis OAuth access token.
	 */
	public static function save( $shortUserName, $accessToken )
	{
		$values = array(
			'user' => strval( $shortUserName ),
			'token' => strval( $accessToken )
		);
		//	rather update before insert because it's more likely it exists already but there's no way to see if the update
		// updated a row or not
		$insertResult = self::insertRow( self::TABLENAME, $values, true, null, false );
		if( $insertResult === false ) {
			// record already exists, update it
			self::updateRow( self::TABLENAME, $values, '`user` = ?', array( strval( $shortUserName ) ) );
		}
	}

	/**
	 * Return Elvis OAuth access token for given user.
	 *
	 * @param string $shortUserName short user name returned from BizSession::getShortUserName().
	 * @return string|null Elvis OAuth access token or null when token isn't found.
	 * @throws BizException
	 */
	public static function get( $shortUserName )
	{
		$row = self::getRow( self::TABLENAME, '`user` = ?', array( 'token' ), array( strval( $shortUserName ) ) );
		return $row ? $row['token'] : null;
	}
}
