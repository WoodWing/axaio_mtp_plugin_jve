<?php
/**
 * @package    Elvis
 * @subpackage DbClasses
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class Elvis_DbClasses_Token extends DBBase
{
	const TABLENAME = 'lvs_tokens';

	public static function save( $user, $accessToken )
	{
		$values = array( 'user' => $user, 'token' => $accessToken );
		//	rather update before insert because it's more likely it exists already but there's no way to see if the update
		// updated a row or not
		$insertResult = self::insertRow( self::TABLENAME, $values );
		if( $insertResult === false ) {
			// record already exists, update it. Ignore if it fails?
			self::updateRow( self::TABLENAME, $values, '`user` = ?', array( $user ) );
		}
	}

	public static function get( $user )
	{
		LogHandler::Log( 'ELVIS', 'DEBUG', 'user: '.print_r( $user, true ) );
		$row = self::getRow( self::TABLENAME, '`user` = ?', array( 'token' ), array( $user ) );
		LogHandler::Log( 'ELVIS', 'DEBUG', 'row: '.print_r( $row, true ) );
		return array_key_exists( 'token', $row ) ? $row['token'] : null;
	}
}