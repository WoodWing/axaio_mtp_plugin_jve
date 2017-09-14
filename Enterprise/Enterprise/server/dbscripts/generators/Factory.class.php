<?php

/**
 * Factory for SQL script generators.
 *
 * @package Enterprise
 * @subpackage DbScripts/Generators
 * @since 10.2.0
 **/

class WW_DbScripts_Generators_Factory
{
	/**
	 * Returns a script generator for a particular DBMS.
	 *
	 * @param string $dbms Name of the Database Management System.
	 * @return WW_DbScripts_Generators_Base|null
	 */
	public static function createGenerator( $dbms = DBTYPE )
	{
		$generator = null;

		switch ( $dbms ) {
			case 'mysql':
				require_once BASEDIR.'/server/dbscripts/generators/Mysql.class.php';
				$generator = new WW_DbScripts_Generators_Mysql( false );
				break;
			case 'mssql':
				require_once BASEDIR.'/server/dbscripts/generators/Mssql.class.php';
				$generator = new WW_DbScripts_Generators_Mssql( false );
				break;
		}

		return $generator;
	}

}