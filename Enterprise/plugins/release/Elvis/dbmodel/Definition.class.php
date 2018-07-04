<?php
/**
 * Elvis DB model definition.
 *
 * @since      10.5.0
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
	 * @inheritdoc
	 */
	public function getVersions()
	{
		return array(
			'1.0', // OAuth 2 authentication
			// Add new versions at the -end- of list!
		);
	}

	/**
	 * Elvis_DbModel_Definition constructor.
	 */
	public function __construct()
	{
		$struct = array(
			'tables' => array(
				array( 'v' => '1.0', 'name' => DBPREFIX.'lvs_tokens',
					'comment' => 'Elvis access tokens.',
					'fields' => array(
						array( 'v' => '1.0', 'name' => 'entuser', 'type' => 'varchar(40)', 'default' => "''",
							'comment' => 'Short name of the acting Enterprise user.' ),
						array( 'v' => '1.0', 'name' => 'elvisuser', 'type' => 'varchar(40)', 'default' => "''",
							'comment' => 'Elvis user name used to authenticate the Enterprise connection with Elvis. '.
								'Could be either the acting user or the configured fallback user (ELVIS_SUPER_USER).' ),
						array( 'v' => '1.0', 'name' => 'token', 'type' => 'varchar(1024)', 'default' => "''",
							'comment' => 'Elvis OAuth access token.' ),
					),
					'indexes' => array(
						array( 'v' => '1.0', 'name' => 'primary', 'fields' => 'entuser', 'primary' => true ),
					),
					'inserts' => array(),
					'lookups' => array(
						array('v' => '1.0', 'field' => 'entuser', 'reftable' => 'smart_users', 'reffield' => 'user'),
					),
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

	/**
	 * @inheritdoc
	 */
	public function getTablesWithoutAutoIncrement()
	{
		return array( DBPREFIX.'lvs_tokens' );
	}
}
