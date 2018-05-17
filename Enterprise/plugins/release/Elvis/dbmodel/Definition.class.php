<?php

/**
 * @package    Elvis
 * @subpackage DBScripts
 * @since      10.5
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbmodel/Provider.class.php';

class Elvis_DbModel_Definition extends WW_DbModel_Provider
{
	/**
	 * @inheritdoc
	 */
	public function getTablePrefix()
	{
		return DBPREFIX.'lvs_';
	}

	/**
	 * All Database Model versions made in history for this server plugin.
	 *
	 * @return string[] with versions.
	 */
	public function getVersions()
	{
		return array(
			'1.0', // feature X
			// Add new versions at the -end- of list!
		);
	}

	public function __construct()
	{
		$struct = array(
			'tables' => array(
				array( 'v' => '1.0', 'name' => DBPREFIX.'lvs_tokens',
					'comment' => '...',
					'fields' => array(
						array( 'v' => '1.0', 'name' => 'user', 'type' => 'varchar(40)', 'default' => "''",
							'comment' => '...' ),
						array( 'v' => '1.0', 'name' => 'token', 'type' => 'varchar(1024)', 'default' => "''",
							'comment' => '...' ),
					),
					'indexes' => array(
						array( 'v' => '1.0', 'name' => 'primary', 'fields' => 'user', 'primary' => true ),
					),
					'inserts' => array(),
					'lookups' => array(),
				),
			),
		);
		$patches = array();
		parent::__construct( $struct, $patches );
	}

	/**
	 * @inheritdoc
	 */
	public function getCategorizedTableNames()
	{
		return array(
			'Elvis - Token store' => array( DBPREFIX.'lvs_tokens' ),
		);
	}

	public function getTablesWithoutAutoIncrement()
	{
		return array( DBPREFIX.'lvs_tokens' );
	}


}