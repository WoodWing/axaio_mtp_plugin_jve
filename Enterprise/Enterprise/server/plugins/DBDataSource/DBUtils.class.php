<?php
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

class DBUtils extends DBBase
{
	public static function gen( $settings )
	{
		try {
			$driver = DBDriverFactory::gen( $settings["type"], $settings["hostname"], $settings["username"], $settings["password"], $settings["database"] );
		} catch( BizException $e ) {
			throw new BizException( 'ERR_ERROR', 'SERVER', $e->getMessage() );
		}
		return $driver;
	}
	
	public static function executeQuery( $settings, $query )
	{
		self::clearError();
		
		$dbdriver = self::gen( $settings );
		$params = array();
		
		$sth = $dbdriver->query($query, $params);
		
		if( !$sth ) {
			throw new BizException( 'ERR_ERROR', 'Database', $dbdriver->error() );
		}
		
		return self::fetchResults($sth);
	}
	
	public static function executeUpdate( $settings, $query, $params )
	{
		self::clearError();
		
		$dbdriver = self::gen( $settings );
		
		$sth = $dbdriver->query($query, $params);
		
		if( !$sth ) {
			throw new BizException( 'ERR_ERROR', 'Database', $dbdriver->error() );
		}
	}
}
