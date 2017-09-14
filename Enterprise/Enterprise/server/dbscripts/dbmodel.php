<?php

/**
 * Class containing structure of database (tables, fields, indexes, inserts)
 *
 * FOR DEVELOPERS ONLY!
 *
 * @package 	Enterprise
 * @subpackage 	DBScripts
 * @since 		v4.2
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

define ('MINIMALGENVERSION', '8.0');

class DBStruct
{
	protected $struct;
	protected $patches;
	private $errors;

	/**
	 * All Enterprise Server -Database Model- versions made in history.
	 * Do not confuse Model versions with Server versions.
	 * IMPORTANT: Add new versions at the -end- of list!
	 *
	 * @return String[] with versions.
	 */
	public function getVersions()
	{
		return array (
			'3.1', '3.2', '3.3', '3.4', '4.0', '4.1', '4.2', 
			'5.0', '6.0', '6.1', '7.0', '7.5', '7.6', '8.0',
			'9.0', '9.1', '9.2', '9.4', '9.5', '9.6', '9.7', '9.8',
			'10.0', '10.1', '10.2'
		);
	}
	
	/**
	 * Constructor, build whole database struct in array of arrays:
	 * tables => contains array of tableInfo
	 *			v => version
	 *			name => name of table
	 *			fields => array of fieldinfo
	 *					v => version
	 *					name => name of field
	 *					type => field type (mysql types)
	 * 					autoincrement => true if field is autoincrement
	 *					default	=> default value (including quotes!)
	 *					nullable => true if null value allowed
	 *					alters => field definitions used in older versions (needed to change fields / alter columns)
	 *			indexes => array of indexes
	 *					v => version
	 *					name => name of index
	 *					fields => field or list of fields (comma separated) in index
	 *					primary => true if this is the primary key (defaults to id)
	 *					unique => true if this index has a unique constraint
	 *			inserts => array of string for insert statements (all values, including quotes)
	 *
	 *
	 */

	/**
	 * Gathers the version numbers to use when generating SQL.
	 *
	 * Looks at the MINIMALGENVERSION, everything that is newer should be used to generate
	 * the SQL.
	 *
	 * @return String[] $genVersions The gathered versions to generate SQL for.
	 */
	public function getDbMigrationVersions()
	{
		$versions = $this->getVersions();
		$genVersions = array();
		$maxVersion = array_pop( $versions ); // pop off the last entry and use that as the max version.
		foreach( $versions as $version ) {
			if( version_compare( $version, MINIMALGENVERSION, '>=' ) && version_compare( $version, $maxVersion, '<' ) ) {
				$genVersions[] = $version;
			}
		}
		return $genVersions;
	}


	public function __construct()
	{
		// ------------ EXTREMELY IMPORTANT NOTE --------------------
		// Changing OLD field definitions is absolutely FORBIDDEN !!!
		// To change (or fix!) the type, name or even the default(!) value, it is REQUIRED to use update scripts instead.
		// Updates are done through the 'alters' field property which results into alter columns (which is the way it should be done).
		// Or else you would create two DIFFERENT database instances for the same version !!!
		//   (one DB created by the full script and one DB created by the update scripts)
		// ---- Intermediate versions ----
		// It is possible to do migration in two steps.
		// To support this an intermediate version has to be defined. This intermediate version has the version number
		// x99. The x is normally the latest version before upgrading. So the intermediate version for upgrading to version 6xx
		// is 599. In dbgen.php both versions are in the $upgradeversions array. An intermediate version can be used to when data
		// is migrated from one table to another. In the intermediate version the data is migrated to a new table and later on
		// in the final step the superfluous old table is removed.
		// ----------------------------------------------------------

		$this->struct = array (
			'tables' => array (
				array ('v' => '3.3', 'name' => 'smart_actionproperties',
						'comment' => 'Dialog and query definitions.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '3.3', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'publication', 	'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.3', 'name' => 'publication', 	'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '9.7', 'name' => 'orderid', 		'type' => 'int(11)',			'default' => "'0'",
								'alters' => array('v' => '3.3', 'name' => 'orderid', 		'type' => 'tinyint(4)',			'default' => "'0'"), ),
							array('v' => '3.3', 'name' => 'property'	, 	'type' => 'varchar(200)',		'default' => "''"),
							array('v' => '3.3', 'name' => 'edit', 			'type' => 'char(2)',			'default' => "''"),
							array('v' => '3.3', 'name' => 'mandatory',	 	'type' => 'char(2)',			'default' => "''"),
							array('v' => '3.3', 'name' => 'action', 		'type' => 'varchar(40)',		'default' => "''"),
							array('v' => '3.3', 'name' => 'type', 			'type' => 'varchar(40)',		'default' => "''"),
							array('v' => '5.0', 'name' => 'restricted',		'type' => 'char(2)',			'default' => "''"),
							array('v' => '8.0', 'name' => 'refreshonchange','type' => 'char(2)',			'default' => "''",
								'comment' => 'Whether or not to refresh the workflow dialog when the user changes the value of this dialog field.' ),
							array('v' => '9.0', 'name' => 'parentfieldid', 'type' => 'int(11)', 'default' => "'0'"),
							array('v' => '9.0', 'name' => 'documentid', 'type' => 'varchar(512)','default' => "''"),
							array('v' => '9.0', 'name' => 'initialheight', 'type' => 'int(4)', 'default' => "'0'", 'comment' => 'Initial height of the widget.'),
							array('v' => '9.2', 'name' => 'multipleobjects','type' => 'char(2)',			'default' => "''",
								'comment' => 'Whether or not the this property should be shown on the Multi Set Properties dialog.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pbac_actionproperties', 	'fields' => 'publication, action'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '3.3', 'field' => 'property', 'reftable' => 'smart_properties', 'reffield' => 'name'),
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
						),
				),
				array('v' => '3.1', 'name' => 'smart_authorizations',
						'comment' => 'Authorizations for user groups.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'grpid',		'type' => 'int(11)',		'default' => "'0'" ,
									'alters' => array('v' => '3.1', 'name' => 'grpid',		'type' => 'mediumint(9)',		'default' => "'0'" ), ),
							array('v' => '6.1', 'name' => 'publication','type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'publication','type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'section',	'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'section',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'state',		'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'state',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '3.1', 'name' => 'rights',		'type' => 'varchar(40)',		'default' => "''"),
							array('v' => '6.1', 'name' => 'issue',		'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.4', 'name' => 'issue',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'profile',	'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '4.2', 'name' => 'profile',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '10.1', 'name' => 'bundle',	'type' => 'int(11)',		'default' => "'0'",
								'comment' => 'Group id of rows that should be combined (bundled) in the admin UI.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'gipu_authorizations', 	'fields' => 'grpid, publication'),
							array('v' => '4.2', 'name' => 'gipr_authorizations', 	'fields' => 'grpid, profile'),
						),
						'inserts' => array(
							"1, 2, 1, 0, 0, 'VRWDCKSF', 0, 1, 0",
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'grpid', 'reftable' => 'smart_groups', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'profile', 'reftable' => 'smart_profiles', 'reffield' => 'id'),
						),
				),
				array('v' => '3.3', 'name' => 'smart_config',
						'comment' => 'System configuration.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '3.3', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '3.3', 'name' => 'name',		'type' => 'varchar(200)',		'default' => "''"),
							array('v' => '5.0', 'name' => 'value',		'type' => 'blob',				'default' => "",
								'alters' => array('v' => '3.3', 'name' => 'value', 'type' => 'varchar(200)')),
						),
						'indexes' => array(
						),
						'inserts' => array(
							"1, 'version', '00'",		// will be updated automatically
						),
						'lookups' => array(
						),
				),
				array ('v' => '3.1', 'name' => 'smart_deletedobjects',
						'comment' => 'Stores deleted objects until recovery or purging.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'documentid',		'type' => 'varchar(512)',		'default' => "''",
									'alters' => array('v' => '3.1', 'name' => 'documentid',		'type' => 'varchar(200)',		'default' => "''"), ),
							array('v' => '3.1', 'name' => 'type',			'type' => 'varchar(20)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'name',			'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '6.1', 'name' => 'publication',	'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'publication',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'issue', 'type' => 'int(11)',	'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'issue', 'type' => 'mediumint(9)',	'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'section',		'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'section',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'state',			'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '3.1', 'name' => 'state',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '3.1', 'name' => 'routeto',		'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'copyright',		'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'slugline',		'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'comment',		'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'author',			'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '8.0', 'name' => 'deadline',		'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
								'alters' => array('v' => '3.1', 'name' => 'deadline',	'type' => 'varchar(40)', 'default' => "''")),
							array('v' => '3.1', 'name' => 'urgency',		'type' => 'varchar(40)',		'default' => "''"),
							array('v' => '6.1', 'name' => 'format',			'type' => 'varchar(128)',		'default' => "''",
								'alters' => array('v' => '3.1', 'name' => 'format',			'type' => 'varchar(40)',		'default' => "''")),
							array('v' => '3.1', 'name' => 'width',			'type' => 'double',				'default' => "'0'"),
							array('v' => '3.1', 'name' => 'depth',			'type' => 'double',				'default' => "'0'"),
							array('v' => '10.1', 'name' => 'dpi',			'type' => 'double',				'default' => "'0'",
								'alters' => array('v' => '3.1', 'name' => 'dpi',		'type' => 'int(11)',	'default' => "'0'") ),
							array('v' => '3.1', 'name' => 'lengthwords',	'type' => 'int(11)',			'default' => "'0'"),
							array('v' => '3.1', 'name' => 'lengthchars',	'type' => 'int(11)',			'default' => "'0'"),
							array('v' => '3.1', 'name' => 'lengthparas',	'type' => 'int(11)',			'default' => "'0'"),
							array('v' => '3.1', 'name' => 'lengthlines',	'type' => 'int(11)',			'default' => "'0'"),
							array('v' => '3.1', 'name' => 'keywords',		'type' => 'blob',				'default' => ""),
							array('v' => '3.1', 'name' => 'modifier',		'type' => 'varchar(40)',		'default' => "''"),
						    array('v' => '8.0', 'name' => 'modified',		'type' => 'varchar(30)',		'default' => "''", 'nullable' => false,
							  	'comment' => 'yyyy-mm-ddThh:mm:ss', 'alters' => array('v' => '3.1', 'name' => 'modified',		'type' => 'varchar(30)',		'default' => "NULL", 'nullable' => true,	'comment' => 'yyyy-mm-ddThh:mm:ss') ),
							array('v' => '3.1', 'name' => 'creator',		'type' => 'varchar(40)',		'default' => "''"),
						    array('v' => '8.0', 'name' => 'created',		'type' => 'varchar(30)',		'default' => "''", 'nullable' => false,
							  	'comment' => 'yyyy-mm-ddThh:mm:ss',	'alters'  => array('v' => '3.1', 'name' => 'created',		'type' => 'varchar(30)',		'default' => "NULL", 'nullable' => true,	'comment' => 'yyyy-mm-ddThh:mm:ss')),
							array('v' => '8.0', 'name' => 'deletor',		'type' => 'varchar(40)',		'default' => "''"),
						    array('v' => '8.0', 'name' => 'deleted',		'type' => 'varchar(30)',		'default' => "''",
							  	'comment' => 'yyyy-mm-ddThh:mm:ss' ),
							array('v' => '3.1', 'name' => 'copyrightmarked','type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'copyrighturl',	'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'credit',			'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => 'source',			'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '8.0', 'name' => 'description',	'type' => 'blob',				'default' => "''", 'nullable' => false,
								'alters' => array('v' => '5.0', 'name' => 'description',	'type' => 'blob',				'default' => "", 'nullable' => true,
								'alters' => array('v' => '3.1', 'name' => 'description', 		'type' => 'varchar(255)') )),
							array('v' => '3.1', 'name' => 'descriptionauthor','type' => 'varchar(255)',		'default' => "''"),
							array('v' => '3.1', 'name' => '_columns',		'type' => 'int(11)',			'default' => "'0'"),
							array('v' => '3.1', 'name' => 'plaincontent',	'type' => 'blob',				'default' => ""),
							array('v' => '3.1', 'name' => 'filesize',		'type' => 'int(15)',			'default' => "'0'"),
							array('v' => '3.1', 'name' => 'colorspace',		'type' => 'varchar(20)',		'default' => "''"),
							array('v' => '4.0', 'name' => 'pagenumber',		'type' => 'int(11)',			'default' => "'0'",
								'comment' => 'Obsoleted. The page field of smart_placements table is used instead.' ),
							array('v' => '3.1', 'name' => 'types',			'type' => 'blob',				'default' => ""),
							array('v' => '3.4', 'name' => 'storename',		'type' => 'blob',				'default' => ""),
						    array('v' => '8.0', 'name' => 'pagerange',		'type' => 'varchar(50)',		'default' => "''", 'nullable' => false,
						    	'alters' => array('v' => '4.1', 'name' => 'pagerange',		'type' => 'varchar(50)',		'default' => "''", 'nullable' => true)),
						    array('v' => '8.0', 'name' => 'highresfile',	'type' => 'varchar(255)',		'default' => "''", 'nullable' => false, 
						    'alters' => array('v' => '4.1', 'name' => 'highresfile',	'type' => 'varchar(255)',		'default' => "''", 'nullable' => true)),
						    array('v' => '8.0', 'name' => 'deadlinesoft',		'type' => 'varchar(30)',	'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss' ,
								'alters' => array('v' => '6.0', 'name' => 'deadlinesoft',	'type' => 'varchar(40)', 'default' => "''")),
						    array('v' => '6.0', 'name' => 'deadlinechanged',	'type' => 'char(1)',		'default' => "''"),
						    array('v' => '8.0', 'name' => 'plannedpagerange',	'type' => 'varchar(50)',	'default' => "''", 'nullable' => false,
						    'alters' => array('v' => '5.0', 'name' => 'plannedpagerange',	'type' => 'varchar(50)',	'default' => "''", 'nullable' => true)),
						    array('v' => '6.0', 'name' => 'majorversion',		'type' => 'mediumint(9)',	'default' => "'-1'",
						  		'comment' => 'Major object version, used for permanent saves. See also minorversion field. -1 for undetermined version of migrated databases' ),
						    array('v' => '6.0', 'name' => 'minorversion',		'type' => 'mediumint(9)',	'default' => "'0'",
						  		'comment' => 'Minor object version, used for intermediate saves. See also majorversion field.' ),
							array('v' => '6.0', 'name' => 'encoding',		'type' => 'varchar(100)',		'default' => "''"),
							array('v' => '6.0', 'name' => 'compression',	'type' => 'varchar(100)',		'default' => "''"),
							array('v' => '6.0', 'name' => 'keyframeeveryframes','type' => 'mediumint(9)',	'default' => "'0'"),
							array('v' => '6.0', 'name' => 'channels',		'type' => 'varchar(100)',		'default' => "''"),
							array('v' => '6.0', 'name' => 'aspectratio',	'type' => 'varchar(100)',		'default' => "''"),
						  	array('v' => '6.0', 'name' => 'contentsource',	'type' => 'varchar(100)',		'default' => "''" ),
						  	array('v' => '7.0', 'name' => 'rating',			'type' => 'tinyint(4)',			'default' => "0", // BZ#20677: Took out '' for default value.
						  			'alters' => array('v' => '6.0', 'name' => 'rating',			'type' => 'tinyint(4)',			'default' => "'0'") ),
					      	array('v' => '6.0', 'name' => 'indexed',		'type' => 'char(2)', 			'default' => "''"),
					      	array('v' => '6.0', 'name' => 'closed',			'type' => 'char(2)', 			'default' => "''" ),
							array('v' => '10.1', 'name' => 'orientation',	'type' => 'tinyint(4)',			'default' => "'0'",
								'comment' => 'EXIF orientation flag extracted from embedded metadata. Value range [1..8]. '.
									'Zero (0) when not resolved e.g. for non-images, images without orientation or images last saved before DB migration.' ),
							array('v' => '6.1', 'name' => 'routetouserid',		'type' => 'int(11)', 			'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'routetouserid',		'type' => 'mediumint(9)', 			'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'routetogroupid',	'type' => 'int(11)', 			'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'routetogroupid',	'type' => 'mediumint(9)', 			'default' => "'0'"), ),
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '3.1', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'routeto', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '3.1', 'field' => 'routeto', 'reftable' => 'smart_groups', 'reffield' => 'name'),
							array('v' => '3.1', 'field' => 'modifier', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '3.1', 'field' => 'creator', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '8.0', 'field' => 'deletor', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '6.0', 'field' => 'routetouserid', 'reftable' => 'smart_users', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'routetogroupid', 'reftable' => 'smart_groups', 'reffield' => 'id'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_groups',
						'comment' => 'User group definitions.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',		'type' => 'int(11)', 	'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',		'type' => 'mediumint(9)', 	'autoincrement' => true), ),
						  array('v' => '7.0', 'name' => 'name',		'type' => 'varchar(100)',	'default' => "''",
						  		'alters' => array('v' => '3.1', 'name' => 'name',		'type' => 'varchar(40)',	'default' => "''"), ),
						  array('v' => '3.1', 'name' => 'descr',	'type' => 'varchar(255)',	'default' => "''"),
						  array('v' => '3.1', 'name' => 'admin',	'type' => 'char(2)',		'default' => "''"),
						  array('v' => '3.4', 'name' => 'routing',	'type' => 'char(2)',		'default' => "''"),
						  array('v' => '7.0', 'name' => 'externalid', 'type' => 'varchar(200)',	'default' => "''",
								'comment' => 'External id for the group in an external system e.g. LDAP' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'idnaro_groups', 	'fields' => 'id, name, routing'),
							array('v' => '4.2', 'name' => 'na_groups', 	'fields' => 'name'),
						),
						'inserts' => array(
							"2, 'admin', 'System Admins', 'on', '', ''",
						),
						'lookups' => array()
				),
				array ('v' =>'3.1', 'name' => 'smart_log',
						'comment' => 'Log SOAP transactions per user, object etc. for reporting purposes.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '3.1', 'name' => 'user',			'type' => 'varchar(50)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'service',		'type' => 'varchar(50)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'ip',			'type' => 'varchar(30)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'date',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss'),
						  array('v' => '6.1', 'name' => 'objectid',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'objectid',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'publication',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'publication',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'issue',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'issue',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'section',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'section',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'state',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'state',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'parent',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'parent',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '4.2', 'name' => 'lock',			'type' => 'varchar(1)',			'default' => "''"),
						  array('v' => '4.2', 'name' => 'rendition',	'type' => 'varchar(10)',		'default' => "''"),
						  array('v' => '4.2', 'name' => 'type',			'type' => 'varchar(20)',		'default' => "''"),
						  array('v' => '4.2', 'name' => 'routeto',		'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '4.2', 'name' => 'edition',		'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'minorversion',	'type' => 'mediumint(9)',		'default' => "'0'",
						  	'alters' => array( // rename version -> minorversion
						  		'v' => '4.2', 'name' => 'version',		'type' => 'mediumint(9)',		'default' => "'0'" ),
					  		'comment' => 'Minor object version at the time server was requested.' ),
						  array('v' => '6.1', 'name' => 'channelid',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '6.0', 'name' => 'channelid',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.0', 'name' => 'majorversion',	'type' => 'mediumint(9)',		'default' => "'-1'",
					  		'comment' => 'Major object version at the time server was requested. -1 for undetermined version of migrated databases. Values were never set before v6.0.' ),
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'user', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '4.2', 'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'parent', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'routeto', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '4.2', 'field' => 'edition', 'reftable' => 'smart_editions', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'channelid', 'reftable' => 'smart_channels', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'majorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'majorversion'),
							array('v' => '6.0', 'field' => 'minorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'minorversion'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_namedqueries',
						'comment' => 'Stores named (raw SQL) queries.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 	'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 	'autoincrement' => true), ),
						  array('v' => '3.1', 'name' => 'query',		'type' => 'varchar(200)',	'default' => "''"),
						  array('v' => '3.1', 'name' => 'interface', 	'type' => 'blob',			'default' => ""),
						  array('v' => '3.1', 'name' => 'sql', 			'type' => 'blob',			'default' => ""),
						  array('v' => '3.1', 'name' => 'comment', 		'type' => 'blob',			'default' => ""),
						  array('v' => '7.0', 'name' => 'checkaccess', 	'type' => 'varchar(2)',		'default' => "'on'"),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'qe_namedqueries', 	'fields' => 'query'),
						),
						'inserts' => array(
							"2, 'Templates', '', 0x2f2a53454c4543542a2f0d0a73656c656374206f2e6069646020617320604944602c206f2e607479706560206173206054797065602c206f2e606e616d656020617320604e616d65602c2073742e6073746174656020617320605374617465602c20756c2e6066756c6c6e616d656020617320604c6f636b65644279602c20702e607075626c69636174696f6e6020617320605075626c69636174696f6e602c20732e6073656374696f6e60206173206053656374696f6e602c206f2e60636f6d6d656e74602061732060436f6d6d656e74602c206f2e60726f757465746f602061732060526f757465546f602c20756e2e6066756c6c6e616d6560206173206043726561746f72602c206f2e60666f726d6174602061732060466f726d6174602c20756d2e6066756c6c6e616d656020617320604d6f646966696572602c20702e6069646020617320605075626c69636174696f6e4964602c20732e60696460206173206053656374696f6e4964602c2073742e60696460206173206053746174654964602c2073742e60636f6c6f726020617320605374617465436f6c6f72602c206c2e606c6f636b6f66666c696e656020617320604c6f636b466f724f66666c696e65600d0a2f2a46524f4d2a2f0d0a66726f6d2060736d6172745f6f626a6563747360206f0d0a2f2a4a4f494e532a2f0d0a6c656674206a6f696e2060736d6172745f7075626c69636174696f6e73602070206f6e20286f2e607075626c69636174696f6e60203d20702e60696460290d0a6c656674206a6f696e2060736d6172745f7075626c73656374696f6e73602073206f6e20286f2e6073656374696f6e60203d20732e60696460290d0a6c656674206a6f696e2060736d6172745f73746174657360207374206f6e20286f2e60737461746560203d2073742e60696460290d0a6c656674206a6f696e2060736d6172745f6f626a6563746c6f636b7360206c206f6e20286f2e60696460203d206c2e606f626a65637460290d0a6c656674206a6f696e2060736d6172745f75736572736020756c206f6e20286c2e6075737260203d20756c2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756d206f6e20286f2e606d6f64696669657260203d20756d2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756e206f6e20286f2e6063726561746f7260203d20756e2e607573657260290d0a2f2a57484552452a2f0d0a776865726520286f2e607479706560203d20274c61796f757454656d706c61746527206f72206f2e607479706560203d202741727469636c6554656d706c61746527206f72206f2e607479706560203d20274c61796f75744d6f64756c6554656d706c6174652729, 0x53686f777320616c6c206c61796f75742074656d706c617465732e, 'on'",
							"5, 'Libraries', '', 0x2f2a53454c4543542a2f0d0a73656c656374206f2e6069646020617320604944602c206f2e607479706560206173206054797065602c206f2e606e616d656020617320604e616d65602c2073742e6073746174656020617320605374617465602c20756c2e6066756c6c6e616d656020617320604c6f636b65644279602c20702e607075626c69636174696f6e6020617320605075626c69636174696f6e602c20732e6073656374696f6e60206173206053656374696f6e602c206f2e60636f6d6d656e74602061732060436f6d6d656e74602c206f2e60726f757465746f602061732060526f757465546f602c20756e2e6066756c6c6e616d6560206173206043726561746f72602c206f2e60666f726d6174602061732060466f726d6174602c20756d2e6066756c6c6e616d656020617320604d6f646966696572602c20702e6069646020617320605075626c69636174696f6e4964602c20732e60696460206173206053656374696f6e4964602c2073742e60696460206173206053746174654964602c2073742e60636f6c6f726020617320605374617465436f6c6f72602c206c2e606c6f636b6f66666c696e656020617320604c6f636b466f724f66666c696e65600d0a2f2a46524f4d2a2f0d0a66726f6d2060736d6172745f6f626a6563747360206f0d0a2f2a4a4f494e532a2f0d0a6c656674206a6f696e2060736d6172745f7075626c69636174696f6e73602070206f6e20286f2e607075626c69636174696f6e60203d20702e60696460290d0a6c656674206a6f696e2060736d6172745f7075626c73656374696f6e73602073206f6e20286f2e6073656374696f6e60203d20732e60696460290d0a6c656674206a6f696e2060736d6172745f73746174657360207374206f6e20286f2e60737461746560203d2073742e60696460290d0a6c656674206a6f696e2060736d6172745f6f626a6563746c6f636b7360206c206f6e20286f2e60696460203d206c2e606f626a65637460290d0a6c656674206a6f696e2060736d6172745f75736572736020756c206f6e20286c2e6075737260203d20756c2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756d206f6e20286f2e606d6f64696669657260203d20756d2e607573657260290d0a6c656674206a6f696e2060736d6172745f75736572736020756e206f6e20286f2e6063726561746f7260203d20756e2e607573657260290d0a2f2a57484552452a2f0d0a776865726520286f2e607479706560203d20274c6962726172792729, 0x53686f777320616c6c206c69627261726965732e, 'on'",
						),
						'lookups' => array(
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_objectlocks',
						'comment' => 'Object lock administration. Objects are locked on user id only.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'object',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.1', 'name' => 'object',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '3.1', 'name' => 'usr',			'type' => 'varchar(40)',		'default' => "''"),						  
						  array( 'v' => '7.5', 'name' => 'timestamp', 'type' => 'timestamp', 'default' => '',
						  		'alters' => array('v' => '3.1', 'name' => 'timestamp', 	'type' => 'timestamp(14)',		'default' => "") ),
						  array('v' => '8.0', 'name' => 'ip', 			'type' => 'varchar(30)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '4.1', 'name' => 'ip', 			'type' => 'varchar(30)',		'default' => "''", 'nullable' => true)),
						  array('v' => '8.0', 'name' => 'lockoffline', 	'type' => 'varchar(2)',			'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '4.1', 'name' => 'lockoffline', 	'type' => 'varchar(2)',			'default' => "''", 'nullable' => true)),
						),
						'indexes' => array(
							array('v' => '3.1', 'name' => 'ob_objectlocks', 'fields' => 'object', 'unique' => true),
							array('v' => '4.2', 'name' => 'obusr_objectlocks', 	'fields' => 'object, usr'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '3.1', 'field' => 'object', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'usr', 'reftable' => 'smart_users', 'reffield' => 'user'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_objectrelations',
						'comment' => 'Object parent-child relations, parent is typically a Layout, child is e.g. an Image or an Article.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'parent',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Typically a layout' ,
						  		'alters' => array('v' => '3.1', 'name' => 'parent',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Typically a layout' ), ),
						  array('v' => '6.1', 'name' => 'child',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Typically article, image or advert',
						  		'alters' => array('v' => '3.1', 'name' => 'child',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Typically Article, Image or Advert' ), ),
						  array('v' => '3.1', 'name' => 'type',			'type' => 'varchar(40)',		'default' => "''",
						  	'comment' => 'planned, placed, contained, related' ),
						  array('v' => '3.1', 'name' => 'subid',		'type' => 'varchar(20)',		'default' => "''", 'comment' => 'Object type of the child.'),
						  array('v' => '8.0', 'name' => 'pagerange', 	'type' => 'varchar(50)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '4.2', 'name' => 'pagerange', 	'type' => 'varchar(50)',		'default' => "''", 'nullable' => true)),
						  array('v' => '7.0', 'name' => 'rating',		'type' => 'tinyint(4)',		'default' => "0", 'nullable' => false, // BZ#20677: Took out '' for default value.
								'alters' => array( 'v' => '6.0', 'name' => 'rating',		'type' => 'tinyint(4)',		'default' => "'0'", 'nullable' => true ),
						  		'comment' => 'Rating for the object in context of this relation' ),
						  array('v' => '9.0', 'name' => 'parenttype', 'type' => 'varchar(20)', 'default' => "''", 'comment' => 'Object type of the parent object.' ),
						),
						'indexes' => array(
							array('v' => '5.0', 'name' => 'ch_objectrelations', 'fields' => 'parent, child, subid, type', 'unique' => true,
								  'alters' => array('v' => '4.2', 'name' => 'ch_objectrelations', 'unique' => true,
								   		 'alters' => array('v' => '4.1', 'name' => 'child', 'unique' => true),
								  	), ),
							array('v' => '4.2', 'name' => 'pachty_objectrelations', 	'fields' => 'parent, child, type'),
							array('v' => '5.0', 'name' => 'child_type_id', 				'fields' => 'child, type, id'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'parent', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'child', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),

				array ('v' =>'3.1', 'name' => 'smart_objects',
						'comment' => 'Stores all objects and metadata. Links to publicationstructure by id. Custom meta data properties are added to this tableautomatically, with the prefix C_.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  	'comment' => 'Autoincrement, unique ID' ,
						  		'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true,
						  			'comment' => 'Autoincrement, unique ID' ), ),
						  array('v' => '6.1', 'name' => 'documentid',		'type' => 'varchar(512)',		'default' => "''",
						  	'comment' => 'Foreign key to remote database such as high-res file system',
						  		'alters' => array('v' => '3.1', 'name' => 'documentid',		'type' => 'varchar(200)',		'default' => "''"), ),
						  array('v' => '3.1', 'name' => 'type',				'type' => 'varchar(20)',		'default' => "''",
						  	'comment' => 'Object type as defined in WSDL; Article, Layout, Image, etc' ),
						  array('v' => '3.1', 'name' => 'name',				'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Objects name, max. 26 characters' ),
						  array('v' => '6.1', 'name' => 'publication',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Publication reference' ,
						  		'alters' => array('v' => '3.1', 'name' => 'publication',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Publication reference' ), ),
						  array('v' => '6.1', 'name' => 'issue', 'type' => 'int(11)', 'default' => "'0'",
						  		'comment' => 'Issue reference' ,
								  'alters' => array('v' => '3.1', 'name' => 'issue', 'type' => 'mediumint(9)', 'default' => "'0'",
								  		'comment' => 'Issue reference' ), ),
						  array('v' => '6.1', 'name' => 'section',			'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Section reference' ,
								  'alters' => array('v' => '3.1', 'name' => 'section',			'type' => 'mediumint(9)',		'default' => "'0'",
								  	'comment' => 'Section reference' ), ),
						  array('v' => '6.1', 'name' => 'state',			'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Workflow status reference' ,
								  'alters' => array('v' => '3.1', 'name' => 'state',			'type' => 'mediumint(9)',		'default' => "'0'",
								  	'comment' => 'Workflow status reference' ), ),
						  array('v' => '3.1', 'name' => 'routeto',			'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Current routing -> link to smart_users or to smart_groups' ),
						  array('v' => '3.1', 'name' => 'copyright',		'type' => 'varchar(255)',		'default' => "''" ),
						  array('v' => '3.1', 'name' => 'slugline',			'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Snippet, UTF-8' ),
						  array('v' => '3.1', 'name' => 'comment',			'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Check-in user comment' ),
						  array('v' => '3.1', 'name' => 'author',			'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'deadline',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
						  		'alters' => array('v' => '3.1', 'name' => 'deadline',		'type' => 'varchar(40)',		'default' => "''")),
						  array('v' => '3.1', 'name' => 'urgency',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '6.1', 'name' => 'format',			'type' => 'varchar(128)',		'default' => "''", 'comment' => 'Mime type of native rendition',
						  		'alters' => array('v' => '3.1', 'name' => 'format',			'type' => 'varchar(40)',		'default' => "''" )),
						  array('v' => '3.1', 'name' => 'width',			'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'depth',			'type' => 'double',				'default' => "'0'"),
						  array('v' => '10.1', 'name' => 'dpi',				'type' => 'double',				'default' => "'0'",
						  	'alters' => array('v' => '3.1', 'name' => 'dpi',				'type' => 'int(11)',			'default' => "'0'") ),
						  array('v' => '3.1', 'name' => 'lengthwords',		'type' => 'int(11)',			'default' => "'0'",
						  	'comment' => 'Word count (of article)' ),
						  array('v' => '3.1', 'name' => 'lengthchars',		'type' => 'int(11)',			'default' => "'0'",
						  	'comment' => 'Character count (of article)' ),
						  array('v' => '3.1', 'name' => 'lengthparas',		'type' => 'int(11)',			'default' => "'0'",
						  	'comment' => 'Paragraph count (of article)' ),
						  array('v' => '3.1', 'name' => 'lengthlines',		'type' => 'int(11)',			'default' => "'0'",
						  	'comment' => 'Line count (of article)' ),
						  array('v' => '3.1', 'name' => 'keywords', 		'type' => 'blob',				'default' => ""),
						  array('v' => '3.1', 'name' => 'modifier',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'modified',			'type' => 'varchar(30)', 		'default' => "''", 'nullable' => false,
						  	'comment' => 'yyyy-mm-ddThh:mm:ss',
						  	'alters' => array('v' => '3.1', 'name' => 'modified',			'type' => 'varchar(30)', 		'default' => "NULL", 'nullable' => true,
						  	'comment' => 'yyyy-mm-ddThh:mm:ss')),
						  array('v' => '3.1', 'name' => 'creator',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'created',			'type' => 'varchar(30)', 		'default' => "''", 'nullable' => false,
						  	'comment' => 'yyyy-mm-ddThh:mm:ss',
						  	'alters' => array('v' => '3.1', 'name' => 'created',			'type' => 'varchar(30)', 		'default' => "NULL", 'nullable' => true,
						  	'comment' => 'yyyy-mm-ddThh:mm:ss' ) ),
						  array('v' => '8.0', 'name' => 'deletor',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'deleted',			'type' => 'varchar(30)', 		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss' ),
						  array('v' => '3.1', 'name' => 'copyrightmarked',	'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'copyrighturl',		'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'credit',			'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'source',			'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'description',		'type' => 'blob',				'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '5.0', 'name' => 'description',		'type' => 'blob',				'default' => "", 'nullable' => true,
							'alters' => array(
								'v' => '3.1', 'name' => 'description', 		'type' => 'varchar(255)',		'default' => "''", 'nullable' => false),)),
						  array('v' => '3.1', 'name' => 'descriptionauthor','type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '3.1', 'name' => '_columns',			'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'plaincontent', 	'type' => 'blob',				'default' => "",
						  	'comment' => 'Complete flattened article text UTF-8, for searching purposes (not native)' ),
						  array('v' => '3.1', 'name' => 'filesize',			'type' => 'int(15)',			'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'colorspace',		'type' => 'varchar(20)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'types', 			'type' => 'blob',				'default' => "",
						  	'comment' => 'Renditions stored for the object, such as preview, native, etc (PHP array)' ),
						  array('v' => '3.3', 'name' => 'pagenumber',		'type' => 'int(11)',			'default' => "'0'" ,
								'comment' => 'Obsoleted. The page field of smart_placements table is used instead.' ),
						  array('v' => '3.3', 'name' => 'storename', 		'type' => 'blob',				'default' => "",
						  	'comment' => 'Reference to file store; do never change!' ),
						  array('v' => '8.0', 'name' => 'pagerange', 		'type' => 'varchar(50)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '4.1', 'name' => 'pagerange', 		'type' => 'varchar(50)',		'default' => "''", 'nullable' => true)),
						  array('v' => '8.0', 'name' => 'highresfile', 		'type' => 'varchar(255)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '4.1', 'name' => 'highresfile', 		'type' => 'varchar(255)',		'default' => "''", 'nullable' => true)),
						  array('v' => '8.0', 'name' => 'deadlinesoft',		'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
							  'alters' => array('v' => '4.2', 'name' => 'deadlinesoft',	'type' => 'varchar(40)', 'default' => "''")),
						  array('v' => '4.2', 'name' => 'deadlinechanged',	'type' => 'char(1)',			'default' => "''"),
						  array('v' => '8.0', 'name' => 'plannedpagerange',	'type' => 'varchar(50)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '5.0', 'name' => 'plannedpagerange',	'type' => 'varchar(50)',		'default' => "''", 'nullable' => true)),
						  array('v' => '6.0', 'name' => 'majorversion',		'type' => 'mediumint(9)',		'default' => "'-1'",
						  		'comment' => 'Major object version, used for permanent saves. See also minorversion field. -1 for undetermined version of migrated databases to resolve by update script.' ),
						  array('v' => '6.0', 'name' => 'minorversion',		'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Minor object version, used for intermediate saves. See also majorversion field.' ),
						  array('v' => '6.0', 'name' => 'encoding',			'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'compression',		'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'keyframeeveryframes','type' => 'mediumint(9)',	'default' => "'0'"),
						  array('v' => '6.0', 'name' => 'channels',			'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'aspectratio',		'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'contentsource',	'type' => 'varchar(100)',		'default' => "''",
						  		'comment' => 'External content source that holds the original object, external id stored in documentid' ),
						  array('v' => '7.0', 'name' => 'rating',			'type' => 'tinyint(4)', 		'default' => "0", 'nullable' => false, // BZ#20677: Took out '' for default value.
						  		'alters' => array( 'v' => '6.0', 'name' => 'rating',			'type' => 'tinyint(4)', 		'default' => "'0'", 'nullable' => true ),
						  		'comment' => 'Rating for this object' ),
					      array('v' => '6.0', 'name' => 'indexed',			'type' => 'char(2)', 			'default' => "''",
						  		'comment' => 'Boolean if this object has been indexed since last modification' ),
					      array('v' => '6.0', 'name' => 'closed',			'type' => 'char(2)', 			'default' => "''",
						  		'comment' => 'For future usage: boolean if this object should be included when doing a normal search, if closed it will not be returned' ),
					      array('v' => '6.1', 'name' => 'routetouserid',		'type' => 'int(11)', 			'default' => "'0'",
					      		'alters' => array('v' => '6.0', 'name' => 'routetouserid',		'type' => 'mediumint(9)', 			'default' => "'0'"), ),
					      array('v' => '6.1', 'name' => 'routetogroupid',	'type' => 'int(11)', 			'default' => "'0'",
					      		'alters' => array('v' => '6.0', 'name' => 'routetogroupid',	'type' => 'mediumint(9)', 			'default' => "'0'"), ),
						   array('v' => '10.1', 'name' => 'orientation',	'type' => 'tinyint(4)',			'default' => "'0'",
							   'comment' => 'EXIF orientation flag extracted from embedded metadata. Value range [1..8]. '.
								   'Zero (0) when not resolved e.g. for non-images, images without orientation or images last saved before DB migration.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'nm_objects', 	'fields' => 'name'),
							array('v' => '6.0', 'name' => 'pbsectstate_objects', 	'fields' => 'publication, section, state, closed',
								'alters' => array('v' => '4.2', 'name' => 'pbissest_objects', 	'fields' => 'publication, issue, section, state')),
							array('v' => '6.0', 'name' => 'pubid_objects', 	'fields' => 'publication, id, closed',
								'alters' => array('v' => '4.2', 'name' => 'issest_objects', 	'fields' => 'issue, section, state')),
							array('v' => '4.2', 'name' => 'mo_objects', 	'fields' => 'modifier'),
							array('v' => '4.2', 'oraname' => 'pc_objects', 	'fields' => 'plaincontent', 'oratype' => 'INDEXTYPE IS CTXSYS.CONTEXT PARAMETERS (\'\')' ),
							array('v' => '6.0', 'name' => 'roid_objects', 	'fields' => 'routeto, id, closed'),
							array('v' => '7.0', 'name' => 'codo_objects', 	'fields' => 'contentsource, documentid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '3.1', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'routeto', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '3.1', 'field' => 'routeto', 'reftable' => 'smart_groups', 'reffield' => 'name'),
							array('v' => '3.1', 'field' => 'modifier', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '3.1', 'field' => 'creator', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '8.0', 'field' => 'deletor', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '6.0', 'field' => 'routetouserid', 'reftable' => 'smart_users', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'routetogroupid', 'reftable' => 'smart_groups', 'reffield' => 'id'),
						),
				),

				array ('v' =>'3.1', 'name' => 'smart_objectversions',
						'comment' => 'Stores object version history.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'objid',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.1', 'name' => 'objid',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.0', 'name' => 'minorversion',	'type' => 'mediumint(9)',		'default' => "'0'",
						  	'alters' => array( // since v6.0 rename version -> minorversion
						  	    'v' => '3.1', 'name' => 'version',		'type' => 'mediumint(9)',		'default' => "'0'" ),
					  		'comment' => 'Minor object version, used for intermediate saves. See also majorversion field.' ),
						  array('v' => '8.0', 'name' => 'modifier',		'type' => 'varchar(40)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '3.1', 'name' => 'modifier',		'type' => 'varchar(40)',		'default' => "NULL", 'nullable' => true)),
						  array('v' => '8.0', 'name' => 'comment', 		'type' => 'varchar(255)',		'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '3.1', 'name' => 'comment', 		'type' => 'varchar(255)',		'default' => "NULL", 'nullable' => true)),
						  array('v' => '3.1', 'name' => 'slugline',		'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'created',		'type' => 'varchar(30)', 		'default' => "''", 'nullable' => false, 'comment' => 'yyyy-mm-ddThh:mm:ss',
						  	'alters' => array('v' => '3.1', 'name' => 'created',		'type' => 'varchar(30)', 		'default' => "NULL", 'nullable' => true, 'comment' => 'yyyy-mm-ddThh:mm:ss')),
						  array('v' => '3.1', 'name' => 'types',		'type' => 'blob',				'default' => ""),
						  array('v' => '6.1', 'name' => 'format',		'type' => 'varchar(128)',		'default' => "''",
						  	'alters' => array('v' => '3.3', 'name' => 'format',		'type' => 'varchar(40)',		'default' => "''"), ),
						  array('v' => '3.3', 'name' => 'width',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'depth',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '10.1', 'name' => 'dpi',			'type' => 'double',				'default' => "'0'",
						  	'alters' => array('v' => '3.3', 'name' => 'dpi',		'type' => 'int(11)',			'default' => "'0'") ),
						  array('v' => '3.3', 'name' => 'lengthwords',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'lengthchars',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'lengthparas',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'lengthlines',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'keywords',		'type' => 'blob',				'default' => ""),
						  array('v' => '5.0', 'name' => 'description',	'type' => 'blob',				'default' => "",
							'alters' => array(
								'v' => '3.3', 'name' => 'description',	'type' => 'varchar(255)',		'default' => "''"),),
						  array('v' => '3.3', 'name' => 'descriptionauthor','type' => 'varchar(255)',	'default' => "''"),
						  array('v' => '3.3', 'name' => '_columns',		'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'plaincontent',	'type' => 'blob',				'default' => ""),
						  array('v' => '3.3', 'name' => 'filesize',		'type' => 'int(15)',			'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'colorspace',	'type' => 'varchar(20)',		'default' => "''"),
						  array('v' => '10.1', 'name' => 'orientation',	'type' => 'tinyint(4)',			'default' => "'0'",
							  'comment' => 'EXIF orientation flag extracted from embedded metadata. Value range [1..8]. '.
								  'Zero (0) when not resolved e.g. for non-images, images without orientation or images last saved before DB migration.' ),
						  array('v' => '6.1', 'name' => 'state',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'state',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.0', 'name' => 'majorversion',	'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Major object version, used for permanent saves. See also (minor)version field.' ),
						),
						'indexes' => array(
							array(	'v' => '6.0', 'name' => 'oive_objectversions', 	'fields' => 'objid, majorversion, minorversion', 'unique' => false,
								'alters' => array('v' => '4.2', 'name' => 'oive_objectversions' /*, 'fields' => 'objid, version' */ ),
								),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'modifier', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '4.2', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
						),
				),
				array ('v' =>'7.5', 'name' => 'smart_objectrenditions',
						'comment' => 'Stores references to filestore for edition specific files for an object. '.
							'The non-specific ones ("all" editions") are stored in the "types" field of smart_objects table. '.
							'Note that most fields in this table are used to lookup files at the filestore. '.
							'Aside from the "id" and "format" fields, the combination of all other fields uniquely identifies one record. ',
						'fields' => array(
							array('v' => '7.5', 'name' => 'id',           'type' => 'int(11)',      'autoincrement' => true ),
							array('v' => '7.5', 'name' => 'objid',        'type' => 'int(11)',      'default' => "'0'",
								'comment' => 'Object (ID) for which the rendition is stored.' ),
							array('v' => '7.5', 'name' => 'editionid',    'type' => 'int(11)',      'default' => "'0'",
								'comment' => 'Edition (ID) for which the rendition is stored.' ),
							array('v' => '7.5', 'name' => 'rendition',    'type' => 'varchar(10)',  'default' => "''",
								'comment' => 'File rendition, such as "native", "output", etc.' ),
							array('v' => '7.5', 'name' => 'format',       'type' => 'varchar(128)', 'default' => "''",
								'comment' => 'File format (mime-type) of the stored rendition.' ),
						    array('v' => '7.5', 'name' => 'majorversion', 'type' => 'mediumint(9)', 'default' => "'0'",
								'comment' => 'Major object version, used for permanent saves. See also minorversion field.' ),
						    array('v' => '7.5', 'name' => 'minorversion', 'type' => 'mediumint(9)', 'default' => "'0'",
								'comment' => 'Minor object version, used for intermediate saves. See also majorversion field.' ),
						),
						'inserts' => array(
						),
						'indexes' => array(
							array('v' => '7.5', 'name' => 'obed_objectrenditions', 'fields' => 'objid, editionid, rendition', 'unique' => false),
						),
						'lookups' => array(
							array('v' => '7.5', 'field' => 'objid',        'reftable' => 'smart_objects',        'reffield' => 'id'),
							array('v' => '7.5', 'field' => 'editionid',    'reftable' => 'smart_editions',       'reffield' => 'id'),
							array('v' => '7.5', 'field' => 'majorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'majorversion'),
							array('v' => '7.5', 'field' => 'minorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'minorversion'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_pages',
						'comment' => 'Define pages for layout objects (smart_objects.type == Layout).',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'objid',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Layout object id' ,
						  		'alters' => array('v' => '3.1', 'name' => 'objid',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Layout object id' ), ),
						  array('v' => '3.1', 'name' => 'width',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'height',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'pagenumber',	'type' => 'varchar(20)',		'default' => "''",
						  	'comment' => 'Human readable page number (with optional page section prefix)' ),
						  array('v' => '3.1', 'name' => 'pageorder',	'type' => 'mediumint(9)',		'default' => "'0'",
						  	'comment' => 'Sequential page order within an issue' ),
						  array('v' => '3.1', 'name' => 'nr',			'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'types',		'type' => 'blob',				'default' => ""),
						  array('v' => '6.1', 'name' => 'edition',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'edition',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '5.0', 'name' => 'master',		'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Name of master page of layout template to create page' ),
						  array('v' => '5.0', 'name' => 'instance',		'type' => 'varchar(40)',		'default' => "'Production'",
						  	'comment' => 'Page maintained by Production or Planning system' ),
						  array('v' => '5.0', 'name' => 'pagesequence',	'type' => 'mediumint(9)',		'default' => "'0'",
						  	'comment' => 'Sequential page order within a layout' ),
						  array('v' => '8.0', 'name' => 'orientation',	'type' => 'varchar(9)',		'default' => "''",
						        'comment' => 'The orientation of the page.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'obpaed_pages', 	'fields' => 'objid, pageorder, edition'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'edition', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_placements',
						'comment' => 'Define placed database objects per Layout / Page.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 			'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',			'type' => 'mediumint(9)', 			'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'parent',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Layout object id' ,
						  		'alters' => array('v' => '3.1', 'name' => 'parent',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Layout object id' ), ),
						  array('v' => '6.1', 'name' => 'child',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Placement object id, typically Articles, Images and Adverts' ,
						  		'alters' => array('v' => '3.1', 'name' => 'child',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Placement object id, typically Articles, Images and Adverts' ), ),
						  array('v' => '3.1', 'name' => 'page',			'type' => 'mediumint(9)',		'default' => "'0'",
						  	'comment' => 'Depricated: Sequential page order within an issue. Better use pagesequence instead.' ),
						  array('v' => '3.1', 'name' => 'element',		'type' => 'varchar(200)',		'default' => "''",
						  	'comment' => 'Element name' ),
						  array('v' => '3.1', 'name' => 'elementid',	'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'frameorder',	'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'frameid',		'type' => 'varchar(200)',		'default' => "''",
						  	'comment' => 'Each frame has a Unique ID. For text frames, this ID refers to the spline frame. For graphic frames, this ID refers to the image item frame (which resides inside the spline frame).' ),
						  array('v' => '3.1', 'name' => '_left',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'top',			'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'width',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'height',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '3.3', 'name' => 'overset',		'type' => 'double',				'default' => "'0'"),
						  array('v' => '6.1', 'name' => 'oversetchars',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'oversetchars',	'type' => 'mediumint(11)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'oversetlines',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'oversetlines',	'type' => 'mediumint(11)',		'default' => "'0'"), ),
						  array('v' => '3.3', 'name' => 'layer',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'content',		'type' => 'blob',				'default' => ""),
						  array('v' => '4.1', 'name' => 'type',			'type' => 'varchar(40)', 		'default' => ""),
						  array('v' => '6.1', 'name' => 'edition',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Editions' ,
						  		'alters' => array('v' => '4.2', 'name' => 'edition',		'type' => 'mediumint(9)',		'default' => "'0'",
						  	'comment' => 'Editions' ), ),
						  array('v' => '5.0', 'name' => 'contentdx',	'type' => 'double',				'default' => '0',
						  	'comment' => 'Move content relatively to frame (not to page) from left to right in points (pt). Negative for bleed. Zero for no move.' ),
						  array('v' => '5.0', 'name' => 'contentdy',	'type' => 'double',				'default' => '0',
						  	'comment' => 'Move content relatively to frame (not to page) from top to bottom in points (pt). Negative for bleed. Zero for no move.' ),
						  array('v' => '5.0', 'name' => 'scalex',		'type' => 'double',				'default' => '1',
						  	'comment' => 'Factor to scale content horizontally in its frame. Value 0.15 means 15.2%. Default 100%. Avoid Zero.' ),
						  array('v' => '5.0', 'name' => 'scaley',		'type' => 'double',				'default' => '1',
						  	'comment' => 'Factor to scale content vertically in its frame. Value 0.15 means 15.2%. Default 100%. Avoid Zero.' ),
						  array('v' => '5.0', 'name' => 'pagesequence',	'type' => 'mediumint(9)',		'default' => "'0'",
						  	'comment' => 'Sequential page order within a layout' ),
						  array('v' => '6.0', 'name' => 'pagenumber',	'type' => 'varchar(20)',		'default' => "''",
						  	'comment' => 'Human readable page number (with optional page section prefix)' ),
						  array('v' => '9.0', 'name' => 'formwidgetid',	'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '9.7', 'name' => 'frametype',	'type' => 'varchar(20)',		'default' => "''",
						  	'comment' => 'Tells what content can be placed; '.
						  		'- "text": can place article or spreadsheet content. '.
						  		'- "graphic": place image content. '.
						  		'- "unassigned": can place text or graphic. '.
						  		'- empty (""): can not place any content into frame. ' ),
						  array('v' => '9.7', 'name' => 'splineid',		'type' => 'varchar(200)',		'default' => "''",
						  	'comment' => 'Each spline frame has a Unique ID. For text frames, this is the same as frameid. For graphic frames, this identifies the spline frame (which contains the image item frame). Basically, this frame crops the image on the page.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pachty_placements', 	'fields' => 'parent, child, type'),
							array('v' => '4.2', 'name' => 'ei_placements', 	'fields' => 'elementid'),
							array('v' => '7.6', 'name' => 'chty_placements', 	'fields' => 'child, type'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '3.1', 'field' => 'parent', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'child', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '3.1', 'field' => 'page', 'reftable' => 'smart_pages', 'reffield' => 'pageorder'),
							array('v' => '3.1', 'field' => 'elementid', 'reftable' => 'smart_elements', 'reffield' => 'guid'),
							array('v' => '4.2', 'field' => 'edition', 'reftable' => 'smart_editions', 'reffield' => 'id'),
							array('v' => '5.0', 'field' => 'pagesequence', 'reftable' => 'smart_pages', 'reffield' => 'pagesequence'),
							array('v' => '9.7', 'field' => 'indesignarticleid', 'reftable' => 'smart_indesignarticles', 'reffield' => 'artuid'),
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_elements',
						'comment' => 'Decompose Article`s text components.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '4.2', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '4.2', 'name' => 'guid',			'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '4.2', 'name' => 'name',			'type' => 'varchar(200)',		'default' => "''",
						  	'comment' => 'Element tag' ),
						  array('v' => '8.0', 'name' => 'objid',	'type' => 'int(11)',	'default' => 0,	'nullable' => false,	'comment' => 'Article object id', 
						  	'alters' => array('v' => '6.1', 'name' => 'objid',	'type' => 'int(11)',		'default' => 0, 'nullable' => true,	'comment' => 'Article object id',
						  	'alters' => array('v' => '4.2', 'name' => 'objid',		'type' => 'mediumint(9)',		'default' => 0, 'nullable' => true,	'comment' => 'Article object id' ))),
						  array('v' => '4.2', 'name' => 'lengthwords',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'lengthchars',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'lengthparas',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'lengthlines',	'type' => 'int(11)',			'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'snippet',		'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '4.2', 'name' => 'version',		'type' => 'varchar(50)',		'default' => "''",
						  	'comment' => 'Element version (GUID) used to track and trigger placed content updates.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'oigu_elements', 	'fields' => 'objid, guid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),
				array ('v' =>'9.7', 'name' => 'smart_indesignarticles',
						'comment' => 'InDesign Articles listed in the Articles pallete of Adobe InDesign.',
						'fields' => array(
							array('v' => '9.7', 'name' => 'objid',		'type' => 'int(11)',			'default' => 0,	'nullable' => false,
								'comment' => 'Id of layout object that contains the InDesign Articles.' ),
							array('v' => '9.7', 'name' => 'artuid',			'type' => 'varchar(40)',	'default' => "''",
								'comment' => 'Unique id within the layout object.' ),
							array('v' => '9.7', 'name' => 'name',			'type' => 'varchar(200)',	'default' => "''",
								'comment' => 'Unique name within the layout. (User can change.)' ),
							array('v' => '9.7', 'name' => 'code',			'type' => 'int(11)',		'default' => "'0'",
						  		'comment' => 'Used to sort on creation order.' ),
						),
						'indexes' => array(
							array('v' => '9.7', 'name' => 'primary', 	'fields' => 'objid, artuid', 'primary' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '9.7', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),
				array ('v' =>'9.7', 'name' => 'smart_idarticlesplacements',
						'comment' => 'Tells which placements belong to an InDesign Article.',
						'fields' => array(
							array('v' => '9.7', 'name' => 'objid',		'type' => 'int(11)',		'default' => 0,	'nullable' => false,
								'comment' => 'Foreign key to smart_objects id.' ),
							array('v' => '9.7', 'name' => 'artuid',		'type' => 'varchar(40)',	'default' => "''",
								'comment' => 'Foreign key to smart_indesignarticles artuid.' ),
							array('v' => '9.7', 'name' => 'plcid',		'type' => 'int(11)',		'default' => 0, 'nullable' => false,
								'comment' => 'Foreign key to smart_placements id.' ),
						),
						'indexes' => array(
							array('v' => '9.7', 'name' => 'primary', 	'fields' => 'objid, artuid, plcid', 'primary' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '9.7', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '9.7', 'field' => 'artuid', 'reftable' => 'smart_indesignarticles', 'reffield' => 'artuid'),
							array('v' => '9.7', 'field' => 'plcid', 'reftable' => 'smart_placements', 'reffield' => 'id'),
						),
				),
				array ('v' =>'9.7', 'name' => 'smart_objectoperations',
						'comment' => 'Tells which operations are created for a layout object. Typically be done when layout is not open in SC/ID.',
						'fields' => array(
						  	array('v' => '9.7', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'comment' => 'Used to preserve the creation order, which is tells in what order operations should be executed.' ),
							array('v' => '9.7', 'name' => 'objid',		'type' => 'int(11)',		'default' => 0,	'nullable' => false,
								'comment' => 'Foreign key to smart_objects id. Refers to the layout object for which operations are created.' ),
							array('v' => '9.7', 'name' => 'guid',		'type' => 'varchar(40)',	'default' => "''",
								'comment' => 'Global unique id (GUID) to be provided by caller. Callers may want to identify operations they created or handled.' ),
							array('v' => '9.7', 'name' => 'type',		'type' => 'varchar(200)',	'default' => "''",
								'comment' => 'Type of the operation. See CreateObjectOperations in SCEnterprise.wsdl file for supported values.' ),
							array('v' => '9.7', 'name' => 'name',		'type' => 'varchar(200)',	'default' => "''",
								'comment' => 'Name of the operation. See CreateObjectOperations in SCEnterprise.wsdl file for supported values.' ),
							array('v' => '9.7', 'name' => 'params',		'type' => 'blob',			'default' => "",
								'comment' => 'PHP serialized list of Param data objects fed into the operation (javascript function calls) by SC/ID when opening layout.' ),
						),
						'indexes' => array(
							array('v' => '9.7', 'name' => 'objid_objectoperations', 	'fields' => 'objid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '9.7', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),
				array ('v' =>'3.3', 'name' => 'smart_properties',
						'comment' => 'Custom meta data per publication / object type. WARNING: dont modify this tables, because it is managed together with the smart_(deleted)objects table.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.3', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'publication',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'publication',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '3.3', 'name' => 'objtype',		'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'name',			'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'dispname',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'category',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'type',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'defaultvalue',	'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'valuelist',	'type' => 'blob',				'default' => ""),
						  array('v' => '3.3', 'name' => 'minvalue',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.3', 'name' => 'maxvalue',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '9.0', 'name' => 'maxlen',		'type' => 'bigint(8)',			'default' => "'0'",
						        'alters' => array('v' => '3.3', 'name' => 'maxlen',		'type' => 'tinyint(4)',			'default' => "'0'"),),
						  array('v' => '3.3', 'name' => 'dbupdated',	'type' => 'tinyint(4)',			'default' => "'0'"),
						  array('v' => '7.0', 'name' => 'entity',			'type' => 'varchar(20)',		'default' => "'Object'",
								'comment' => 'For which entity the config is made: Object, Publication, PubChannel, Issue, etc. Set to Object for manual admin configurations.' ),
						  array('v' => '7.0', 'name' => 'serverplugin',	'type' => 'varchar(64)',		'default' => "''",
								'comment' => 'Internal name of server plug-in. Only filled when owned by a plug-in. Emtpy for manual admin configurations.' ),
						  array('v' => '9.0', 'name' => 'adminui',	'type' => 'varchar(2)',		'default' => "'on'",
							      'comment' => 'Signifies if this property needs to be shown in the admin UI.' ),
						  array('v' => '9.0', 'name' => 'propertyvalues',	'type' => 'blob',			'default' => "",
							      'comment' => 'Contains PropertyValue objects.' ),
						  array('v' => '9.0', 'name' => 'minresolution',	'type' => 'varchar(200)',	'default' => "''",
							      'comment' => 'Minimum resolution if applicable.' ),
						  array('v' => '9.0', 'name' => 'maxresolution',	'type' => 'varchar(200)',		'default' => "''",
								'comment' => 'Maximum resolution if applicable.' ),
						  array('v' => '9.0', 'name' => 'publishsystem',	'type' => 'varchar(64)',	'default' => "''",
							      'comment' => 'For which Publish System this property is applicable.' ),
						  array('v' => '9.0', 'name' => 'templateid',	'type' => 'int(11)',			'default' => "0",
								'comment' => 'Defines the template for this property.' ),
						  array( 'v' => '9.1', 'name' => 'termentityid',	'type' => 'int(11)', 'default' => "'0'",
							    'comment' => 'Reference to the termentity and autocomplete provider that helps the user filling in the property.' ),
						  array( 'v' => '9.1', 'name' => 'suggestionentity',	'type' => 'varchar(200)', 'default' => "''",
								'comment' => 'Defines the suggestion entity that is used by suggestion providers.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'puob_properties', 	'fields' => 'publication, objtype'),
							array('v' => '4.2', 'name' => 'pudb_properties', 	'fields' => 'publication, dbupdated'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '7.0', 'field' => 'serverplugin', 'reftable' => 'smart_serverplugins', 'reffield' => 'uniquename'),
							array('v' => '9.0', 'field' => 'templateid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '9.1', 'field' => 'termentityid', 'reftable' => 'smart_termentities', 'reffield' => 'id'),
						),

				),
				array ('v' =>'3.3', 'name' => 'smart_publadmin',
						'comment' => 'Publication specific admin rights (by user group).',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.3', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'publication',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'publication',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'grpid',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'grpid',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pugi_publadmin', 	'fields' => 'publication, grpid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'grpid', 'reftable' => 'smart_groups', 'reffield' => 'id'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_publications',
						'comment' => 'Publication definitions.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '3.1', 'name' => 'publication',		'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Publication name' ),
						  array('v' => '3.4', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'",
						  	'comment' => 'Sorting order' ),
						  array('v' => '3.4', 'name' => 'email',			'type' => 'char(2)',			'default' => "''"),
						  array('v' => '3.4', 'name' => 'description',		'type' => 'blob',				'default' => ""),
						  array('v' => '8.0', 'name' => 'readingorderrev',	'type' => 'varchar(2)',			'default' => "''", 'nullable' => false,
						  	'comment' => 'Pages printed right to left',
						  	'alters' => array('v' => '4.1', 'name' => 'readingorderrev',	'type' => 'varchar(2)',			'default' => "''", 'nullable' => true,
						  	'comment' => 'Pages printed right to left') ),
						  array('v' => '8.0', 'name' => 'autopurge',		'type' => 'int(5)',			'default' => "0",
						  	'comment' => 'Number of days objects (owned by this brand) may live in the Trash Can. Objects getting older are permanently deleted by the Auto Purge feature. Zero (0) disables this feature. '),
						  array('v' => '6.0', 'name' => 'currentissue',	'type' => 'mediumint(9)', 'default' => "'0'",
						  	'drops' => array ('v' => '4.2', 'name' => 'currentissue', 'type' => 'mediumint(9)',	'default' => "'0'",
						  	'comment' => 'Used to query for current issue of the specified publication' ),
						  ),
						  array('v' => '6.1', 'name' => 'defaultchannelid',		'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Default channel, normally the print-channel' ,
						  		'alters' => array('v' => '6.0', 'name' => 'defaultchannelid',		'type' => 'mediumint(9)',	'default' => "'0'",
									'comment' => 'Default channel, normally the print-channel'	), ),
						  array('v' => '9.2', 'name' => 'calculatedeadlines', 'type' => 'char(2)',  'default' => "''",
							'comment' => 'A field to indicate whether the deadline should be calculated when deadline field is configured.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pb_publications', 	'fields' => 'publication'),
							array('v' => '4.2', 'name' => 'idpb_publications', 	'fields' => 'id, publication'),
						),
						'inserts' => array(
							"1, 'WW News', 0, '', '', '', 0, 1, ''",
						),
						'lookups' => array(
							// commented out; 'currentissue' field is removed since v6.0 and we need to keep dbsanitycheck happy
							//array('v' => '4.2', 'field' => 'currentissue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),
				array ('v' =>'6.0', 'name' => 'smart_publissues', 'comment' => 'Issue definitions, obsolete since v6.0.',
					'drops' => array ('v' =>'3.1', 'name' => 'smart_publissues', 'comment' => 'Issue definitions.',
						'fields' => array(
						  array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true),
						  array('v' => '3.1', 'name' => 'publication',		'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '3.1', 'name' => 'issue',			'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Issue name' ),
						  array('v' => '3.4', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'",
						  	'comment' => 'Sorting order' ),
						  array('v' => '3.4', 'name' => 'publdate',			'type' => 'varchar(200)',		'default' => "''",
						  	'comment' => 'Date to publish issue' ),
						  array('v' => '3.4', 'name' => 'deadline',			'type' => 'varchar(200)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss'),
						  array('v' => '3.4', 'name' => 'pages',			'type' => 'int(4)',				'default' => "'0'",
						  	'comment' => 'Planned page count' ),
						  array('v' => '3.4', 'name' => 'subject',			'type' => 'blob',				'default' => ""),
						  array('v' => '3.4', 'name' => 'description',		'type' => 'blob',				'default' => ""),
						  array('v' => '3.4', 'name' => 'active',			'type' => 'char(2)',			'default' => "''",
						  	'comment' => 'Listed in GUI' ),
						  array('v' => '3.4', 'name' => 'overrulepub',		'type' => 'char(2)',			'default' => "''",
						  	'comment' => 'Define workflow at issue level' ),
						  array('v' => '4.1', 'name' => 'readingorderrev',	'type' => 'varchar(2)',			'default' => "''", 'nullable' => true,
						  	'comment' => 'Pages printed right to left' ),
						  array('v' => '4.2', 'name' => 'icode',			'type' => 'int(4)',				'default' => "'0'"),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pb_publissues', 	'fields' => 'publication'),
							array('v' => '4.2', 'name' => 'is_publissues', 	'fields' => 'issue'),
						),
						'inserts' => array(
							"1, 1, '1st Issue', 10, '', '', 0, '', '', 'on', '', '', 0, 0",
							"1, 1, '2nd Issue', 20, '', '', 0, '', '', 'on', '', '', 0, 0",
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
						),
				),
				),
				array ('v' =>'3.1', 'name' => 'smart_publsections',
						'comment' => 'Section (Category) definitions. Category is since v6.0 the denomination of section',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'publication',		'type' => 'int(11)',		'default' => "'0'" ,
						  		'alters' => array('v' => '3.1', 'name' => 'publication',		'type' => 'mediumint(9)',		'default' => "'0'" ), ),
						  array('v' => '3.1', 'name' => 'section',			'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '6.1', 'name' => 'issue',			'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'If 0, section for every issue in publication' ,
						  		'alters' => array('v' => '3.4', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'If 0, section for every issue in publication' ), ),
						  array('v' => '3.4', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'"),
						  array('v' => '3.4', 'name' => 'description',		'type' => 'blob',				'default' => ""),
						  array('v' => '3.4', 'name' => 'pages',			'type' => 'int(4)',				'default' => "'0'",
						  	'comment' => 'Planned page count' ),
						  array('v' => '8.0', 'name' => 'deadline',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
							 'alters' => array('v' => '3.4', 'name' => 'deadline',			'type' => 'varchar(20)',		'default' => "''") ),
						  array('v' => '6.1', 'name' => 'deadlinerelative',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'deadlinerelative',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pbis_publsections', 	'fields' => 'publication, issue'),
							array('v' => '4.2', 'name' => 'se_publsections', 	'fields' => 'section'),
						),
						'inserts' => array(
							"1, 1, 'News', 0, 10, '', 0, '', 0 ",
							"2, 1, 'Sport', 0, 20, '', 0, '', 0 ",
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),
				array ('v' =>'6.0', 'name' => 'smart_publeditions', 'comment' => 'Publication edition definitions, obsolete since v6.0.',
					'drops' => array ('v' =>'4.2', 'name' => 'smart_publeditions', 
						'comment' => 'Publication edition definitions.',
						'fields' => array(
						  array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true),
						  array('v' => '4.2', 'name' => 'edition',			'type' => 'varchar(255)',		'default' => "''",
						  	'comment' => 'Edition name' ),
						  array('v' => '4.2', 'name' => 'publication',		'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'",
						  	'comment' => 'Sort order' ),
						  array('v' => '4.2', 'name' => 'deadlinerelative',	'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'description',		'type' => 'blob',				'default' => ""),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pbis_publeditions', 	'fields' => 'publication, issue'),
							array('v' => '4.2', 'name' => 'is_publeditions', 	'fields' => 'issue'),
							array('v' => '4.2', 'name' => 'ed_publeditions', 	'fields' => 'edition'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),
				),
				array ('v' =>'7.0', 'name' => 'smart_publobjects',
						'comment' => 'Publication specific objects rights (by user group).',
						'fields' => array(
						  array('v' => '7.0', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true),
						  array('v' => '7.0', 'name' => 'publicationid','type' => 'int(11)', 		'default' => "'0'",
						  		'comment' => 'Publication id'),
						  array('v' => '7.0', 'name' => 'issueid',		'type' => 'int(11)',		'default' => "'0'",
						  		'comment' => 'Issue id'),
						  array('v' => '7.0', 'name' => 'objectid',		'type' => 'int(11)',		'default' => "'0'",
						  		'comment' => 'Object id'),
						  array('v' => '7.0', 'name' => 'grpid',		'type' => 'int(11)',		'default' => "'0'",
						  		'comment' => 'Usergroup id'),
						),
						'indexes' => array(
							array('v' => '7.0', 'name' => 'puisobgr_publobjects', 'fields' => 'publicationid, issueid, objectid, grpid', 'unique' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '7.0', 'field' => 'publicationid', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '7.0', 'field' => 'issueid', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '7.0', 'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '7.0', 'field' => 'grpid', 'reftable' => 'smart_groups', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_issueeditions',
						'comment' => 'Issue edition definitions.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id', 			'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '4.2', 'name' => 'id', 			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'issue',			'type' => 'int(11)',		'default' => "'0'",
							  	'comment' => 'Issue reference. If 0, section for every issue in publication.' ,
									'alters' => array('v' => '4.2', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'",
							  			'comment' => 'Issue reference. If 0, section for every issue in publication.' ), ),
							array('v' => '6.1', 'name' => 'edition',		'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '4.2', 'name' => 'edition',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '8.0', 'name' => 'deadline',		'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
								'alters' => array('v' => '4.2', 'name' => 'deadline',		'type' => 'varchar(20)',		'default' => "''")),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'is_issueeditions', 'fields' => 'issue'),
							array('v' => '4.2', 'name' => 'ed_issueeditions', 'fields' => 'edition'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'edition', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
				),
				array ('v' =>'600 ', 'name' => 'smart_objecteditions', 'comment' => 'Object edition definitions, obsolete since v6.0.',
					'drops' => array ('v' =>'4.2', 'name' => 'smart_objecteditions',
						'comment' => 'Object edition definitions. Link object to 1 or more editions.',
						'fields' => array(
						  array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true),
						  array('v' => '4.2', 'name' => 'objid',			'type' => 'mediumint(9)',		'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'edition',			'type' => 'mediumint(9)',		'default' => "'0'"),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'oi_objecteditions', 'fields' => 'objid'),
							array('v' => '4.2', 'name' => 'ed_objecteditions', 'fields' => 'edition'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'edition', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
				),
				),
				array ('v' =>'3.3', 'name' => 'smart_routing',
						'comment' => 'Object routing definitions.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.3', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'publication',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'publication',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'section',			'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'section',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'state',			'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.3', 'name' => 'state',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '9.2', 'name' => 'routeto',			'type' => 'varchar(255)',		'default' => "''",
						  		'alters' => array('v' => '3.3', 'name' => 'routeto',			'type' => 'varchar(40)',		'default' => "''"), ),
						  array('v' => '6.1', 'name' => 'issue',			'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.4', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pbisse_routing', 	'fields' => 'publication, issue, section'),
							array('v' => '4.2', 'name' => 'st_routing', 	'fields' => 'state'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'routeto', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_settings',
						'comment' => 'User settings (such as User Queries, Panel Layouts, etc).',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '3.1', 'name' => 'user',				'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'setting',			'type' => 'varchar(200)',		'default' => "''"),
  						  array('v' => '8.0', 'name' => 'value',		'type' => 'mediumblob',		'default' => "",
						  		'alters' => array('v' => '3.1', 'name' => 'value',			'type' => 'blob',				'default' => ""), ),
						  array('v' => '6.0', 'name' => 'appname',			'type' => 'varchar(200)',		'default' => "''"),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'us_settings', 	'fields' => 'user'),
							array('v' => '4.2', 'name' => 'se_settings', 	'fields' => 'setting'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'user', 'reftable' => 'smart_users', 'reffield' => 'user'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_states',
						'comment' => '',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'publication',		'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.1', 'name' => 'publication',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '3.1', 'name' => 'type',				'type' => 'varchar(40)',		'default' => "''",
						  	'comment' => 'Object type as defined in WSDL; Article, Layout, Image, etc' ),
						  array('v' => '3.1', 'name' => 'state',			'type' => 'varchar(40)',		'default' => "''",
						  	'comment' => 'State name' ),
						  array('v' => '3.1', 'name' => 'produce',			'type' => 'char(2)',			'default' => "''",
						  	'comment' => 'Object ready for produce' ),
						  array('v' => '3.1', 'name' => 'color',			'type' => 'varchar(11)',		'default' => "''",
						  	'comment' => 'Status color (#RRGGBB)' ),
						  array('v' => '6.1', 'name' => 'nextstate',		'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Next state in workflow, used to send objects to next status' ,
						  		'alters' => array('v' => '3.1', 'name' => 'nextstate',		'type' => 'mediumint(9)',		'default' => "'0'",
						  			'comment' => 'Next state in workflow, used to send objects to next status' ), ),
						  array('v' => '3.3', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'",
						  	'comment' => 'Sorting order' ),
						  array('v' => '6.1', 'name' => 'issue',			'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Issue reference, 0 = for all' ,
								  'alters' => array('v' => '3.4', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'",
								  	'comment' => 'Issue reference, 0 = for all' ), ),
						  array('v' => '6.1', 'name' => 'section',			'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'Section reference, 0 = for all' ,
								  'alters' => array('v' => '3.4', 'name' => 'section',			'type' => 'mediumint(9)',		'default' => "'0'",
								  	'comment' => 'Section reference, 0 = for all' ), ),
						  array('v' => '6.1', 'name' => 'deadlinestate',	'type' => 'int(11)',		'default' => "'0'",
								  'alters' => array('v' => '4.2', 'name' => 'deadlinestate',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'deadlinerelative',	'type' => 'int(11)',		'default' => "'0'",
								  'alters' => array('v' => '4.2', 'name' => 'deadlinerelative',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.0', 'name' => 'createpermanentversion',	'type' => 'char(2)',	'default' => "''",
						  	'comment' => 'When enabled (default), major (permanent) object versions are created during save operations at this status. When disabled, minor (intermediate) versions are created.' ),
						  array('v' => '6.0', 'name' => 'removeintermediateversions','type' => 'char(2)',	'default' => "''",
						  	'comment' => 'When enabled, minor object versions are cleaned up (removed) during save operations at this status. When disabled (default), minor versions remain untouched.' ),
						  array('v' => '9.0', 'name' => 'readyforpublishing',	'type' => 'char(2)',	'default' => "''",
								'comment' => 'When enabled, the objects that reach this status will be shown in the publish manager tab when the Publish Status filter is set to "Ready to be published".' ),
						  array('v' => '6.0', 'name' => 'automaticallysendtonext',	'type' => 'char(2)',	'default' => "''",
						  	'comment' => 'When enabled, saved objects are implicitly send to the next status (as configured at workflow).' ),
						  array('v' => '9.4', 'name' => 'phase',			'type' => 'varchar(40)',		'default' => "'Production'",
							'comment' => 'Each Workflow Status belongs to a Workflow Phase. Possible values: Selection, Production, Completed, Archived. Default: Production.' ),
							array( 'v' => '10.0', 'name' => 'skipidsa', 'type' => 'char(2)', 'default' => "''",'patched' => array('9.8' => 'idsautomationskipstatus'),
								'comment' => 'When selected do not create InDesign Server Automation job.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'st_states', 	'fields' => 'state'),
							array('v' => '4.2', 'name' => 'pbistyse_states', 	'fields' => 'publication, issue, type, section'),
							array('v' => '4.2', 'name' => 'istyse_states', 	'fields' => 'issue, type, section'),
						),
						'inserts' => array(
							"1, 1, 'Article', 'Draft text', '', '#FF0000', 2, 10, 0, 0,0,0, '', '', '', '', 'Production', ''",
							"2, 1, 'Article', 'Ready', '', '#00FF00', 0, 20, 0, 0,0,0, '', '', '', '', 'Production', ''",
							"3, 1, 'Layout', 'Layouts', '', '#0000FF', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', ''",
							"4, 1, 'LayoutTemplate', 'Layout Templates', '', '#FFFF99', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', ''",
							"5, 1, 'ArticleTemplate', 'Article Templates', '', '#FFFF99', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', ''",
							"6, 1, 'Image', 'Images', '', '#FFFF00', 0, 0, 0, 0,0,0, '', '', '', '', 'Production', ''",
							"7, 1, 'Advert', 'Adverts', '', '#99CCFF', 0, 0, 0, 0, 0, 0, '', '', '', '', 'Production', ''",
							"8, 1, 'Video', 'Videos', '', '#FFFF00', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"9, 1, 'Audio', 'Audios', '', '#FFFF00', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"10, 1, 'Library', 'Libraries', '', '#888888', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"11, 1, 'Dossier', 'Dossiers', '', '#BBBBBB', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"12, 1, 'DossierTemplate', 'Dossier Templates', '', '#BBBBBB', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"13, 1, 'LayoutModule', 'Layout Modules', '', '#D7C101', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"14, 1, 'LayoutModuleTemplate', 'Layout Module Templates', '', '#FFE553', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"15, 1, 'Task', 'Assigned', '', '#AAAAAA', 15, 10, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"16, 1, 'Task', 'In progress', '', '#AAAAAA', 16, 20, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"17, 1, 'Task', 'Completed', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"18, 1, 'Hyperlink', 'Hyperlinks', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"19, 1, 'Other', 'Others', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"20, 1, 'Archive', 'Archives', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"21, 1, 'Presentation', 'Presentations', '', '#AAAAAA', 0, 30, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"22, 1, 'Spreadsheet', 'Draft', '', '#FF0000', 23, 10, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"23, 1, 'Spreadsheet', 'Ready', '', '#00FF00', 0, 20, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"24, 1, 'PublishForm', 'Publish Forms', '', '#AAAAAA', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
							"25, 1, 'PublishFormTemplate', 'Publish Form Templates', '', '#AAAAAA', 0, 0, 0, 0, 0, 0, 0, '', '', '', 'Production', ''",
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'nextstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
						),
				),
				array ('v' =>'8.0', 'name' => 'smart_storage',
						'comment' => 'Not needed since v8.0, only physical file store is used.',
						'drops' => array ('v' =>'3.2', 'name' => 'smart_storage', 'comment' => 'This tables is used to store object renditions when no physical file store is used (Oracle only).',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'oid',				'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '3.2', 'name' => 'oid',				'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '3.2', 'name' => 'fld',				'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '3.2', 'name' => 'type',				'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'minorversion',	'type' => 'mediumint(9)','default' => "'0'",
						  		'alters' => array( // rename version -> minorversion
						              	'v' => '6.0', 'name' => 'vers',		'type' => 'mediumint(9)',		'default' => "'0'",
						  					'alters' => array( // change type version varchar to int
						              				'v' => '3.2', 'name' => 'vers',		'type' => 'varchar(100)',	'default' => "''"),),),
						  array('v' => '3.2', 'name' => 'page',				'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '3.2', 'name' => 'content',			'type' => 'longblob',			'default' => ""),
						  array('v' => '7.5', 'name' => 'date',				'type' => 'timestamp',		'default' => "",
						  	'alters' => array('v' => '3.2', 'name' => 'date',				'type' => 'timestamp(14)',		'default' => "") ), 
						  array('v' => '6.1', 'name' => 'edition',			'type' => 'int(11)',		'default' => "'0'",
								  'alters' => array('v' => '4.2', 'name' => 'edition',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.0', 'name' => 'majorversion',		'type' => 'mediumint(9)',		'default' => "'-1'",
					  		'comment' => 'Major object version at the time server was requested. -1 for undetermined version of migrated databases. Values were never set before v6.0.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'oifl_storage', 	'fields' => 'oid, fld'),
						),
						'inserts' => array(),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'oid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'edition', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_tickets',
						'comment' => 'Tickets given to currently logged on users.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
								  'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '3.1', 'name' => 'ticketid',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'usr',				'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'db',				'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '6.1', 'name' => 'clientname',		'type' => 'varchar(255)',		'default' => "''",
						  		'alters' => array('v' => '3.1', 'name' => 'clientname',		'type' => 'varchar(40)',	'default' => "''")),
						  array('v' => '3.1', 'name' => 'clientip',			'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'appname',			'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'appversion',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '3.1', 'name' => 'appserial',		'type' => 'varchar(200)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'logon',			'type' => 'varchar(20)', 		'default' => "''", 'nullable' => false, 
						  	'alters' => array('v' => '3.1', 'name' => 'logon',			'type' => 'varchar(20)', 		'default' => "NULL", 'nullable' => true,)),
						  array('v' => '8.0', 'name' => 'expire',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
						  		'alters' => array('v' => '3.1', 'name' => 'expire',		'type' => 'varchar(20)',		'default' => "''")),
						  array('v' => '5.0', 'name' => 'appproductcode',	'type' => 'varchar(40)',		'default' => "''"),
						  array('v' => '9.7', 'name' => 'masterticketid',	'type' => 'varchar(40)',		'default' => "''",
						  	'comment' => 'When client (e.g. IDS for DPS) does logon twice, this field refers to the ticket of the first logon.' ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'ti_tickets', 	'fields' => 'ticketid'),
							array('v' => '4.2', 'name' => 'us_tickets', 	'fields' => 'usr'),
							array('v' => '9.7', 'name' => 'mtid_tickets', 	'fields' => 'masterticketid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'usr', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '9.7', 'field' => 'masterticketid', 'reftable' => 'smart_tickets', 'reffield' => 'ticketid'),
						),
				),
				array( 'v' =>'9.1', 'name' => 'smart_termentities',
						'comment' => 'To store all Term Entity from various Autocomplete providers.',
						'fields' => array(
							array('v' => '9.1', 'name' => 'id',        'type' => 'int(11)',     'autoincrement' => true ),
							array('v' => '9.1', 'name' => 'name',      'type' => 'varchar(255)', 'default' => "''",
								'comment' => 'Name of the TermEntity. For example: city, country, people.' ),
							array('v' => '9.1', 'name' => 'provider',  'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'The Autocomplete provider name. This is the internal name of the server plugin name that has the Autocomplete connector.' .
								'When the provider is set to empty, Enterprise core server becomes the provider.'),
							array('v' => '9.1', 'name' => 'publishsystemid', 'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'Unique id of the publishing system. Use to bind the publishing storage.' ),
						),
						'indexes' => array(
							array('v' => '9.1', 'name' => 'te_name', 	 'fields' => 'name' ),
							array('v' => '9.1', 'name' => 'te_provider', 'fields' => 'provider' ),
							array('v' => '9.1', 'name' => 'te_termentity', 'fields' => 'name,provider' ),
						),
						'inserts' => array(
						),
						'lookups' => array(
						),
				),
				array( 'v' =>'9.1', 'name' => 'smart_terms',
						'comment' => 'To store all terms that are grouped by Term Entity.',
						'fields' => array(
							array('v' => '9.1', 'name' => 'entityid',        'type' => 'int(11)',      'default' => "'0'" ),
							array('v' => '9.1', 'name' => 'displayname',     'type' => 'varchar(255)', 'default' => "''",
								'comment' => 'Human readable term, as shown in the UI.' ),
							array('v' => '9.1', 'name' => 'normalizedname',  'type' => 'varchar(255)', 'default' => "''",
								'comment' => 'Normalized version of the display name. That is lower-cased, without accents, spaces or dashes.' ),
							array( 'v' => '9.1', 'name' => 'ligatures', 'type' => 'varchar(255)', 'default' => "''",
								'comment' => 'To store the ligatures of the displayname.' ),
						),
						'indexes' => array(
							array('v' => '9.1', 'name' => 'primary', 'fields' => 'entityid, displayname', 'primary' => true ),
							array('v' => '9.1', 'name' => 'tm_entityid', 'fields' => 'entityid' ),
							array('v' => '9.1', 'name' => 'tm_normalizedname', 'fields' => 'entityid,normalizedname' ),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '9.1', 'field' => 'entityid', 'reftable' => 'smart_termentities', 'reffield' => 'id'),
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_users',
						'comment' => 'User definitions.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '3.1', 'name' => 'user',				'type' => 'varchar(40)',		'default' => "''",
						  	'comment' => 'Short user name must be unique' ),
						  array('v' => '3.1', 'name' => 'fullname',			'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '9.1', 'name' => 'pass',				'type' => 'varchar(128)',		'default' => "''",
								'alters' => array('v' => '3.1', 'name' => 'pass',				'type' => 'varchar(40)',		'default' => "''"), ),
						  array('v' => '3.1', 'name' => 'disable',			'type' => 'char(2)',			'default' => "''"),
						  array('v' => '3.4', 'name' => 'fixedpass',		'type' => 'char(2)',			'default' => "''"),
						  array('v' => '3.4', 'name' => 'email',			'type' => 'varchar(100)',		'default' => "''"),
						  array('v' => '3.4', 'name' => 'emailgrp',			'type' => 'char(2)',			'default' => "''"),
						  array('v' => '3.4', 'name' => 'emailusr',			'type' => 'char(2)',			'default' => "''"),
						  array('v' => '8.0', 'name' => 'language',			'type' => 'varchar(4)',			'default' => "''", 'nullable' => false,
						  	'alters' => array('v' => '4.0', 'name' => 'language',			'type' => 'varchar(4)',			'default' => "''", 'nullable' => true)),
						  array('v' => '8.0', 'name' => 'startdate',		'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
						  		'alters' => array('v' => '4.2', 'name' => 'startdate',		'type' => 'varchar(20)',		'default' => "''"),),
						  array('v' => '8.0', 'name' => 'enddate',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
							  	'alters' => array('v' => '4.2', 'name' => 'enddate',			'type' => 'varchar(20)',		'default' => "''")),
						  array('v' => '8.0', 'name' => 'expirepassdate',	'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
						  		'alters' => array('v' => '4.2', 'name' => 'expirepassdate',		'type' => 'varchar(20)',		'default' => "''")),
						  array('v' => '4.2', 'name' => 'expiredays',		'type' => 'int(4)',				'default' => "'0'"),
						  array('v' => '4.2', 'name' => 'trackchangescolor','type' => 'varchar(11)',		'default' => "''"),
						  array('v' => '8.0', 'name' => 'lastlogondate',	'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
						  		'alters' => array('v' => '5.0', 'name' => 'lastlogondate',		'type' => 'varchar(20)',		'default' => "''")),
						  array('v' => '6.0', 'name' => 'organization',		'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '6.0', 'name' => 'location',			'type' => 'varchar(255)',		'default' => "''"),
						  array('v' => '7.0', 'name' => 'externalid',		'type' => 'varchar(200)',		'default' => "''",
								'comment' => 'External id for the user in an external system e.g. LDAP' ),
                          array('v' => '9.4', 'name' => 'importonlogon',    'type' => 'char(2)',            'default' => "''",
                                'comment' => 'Import information from LDAP for users who log on and are only partially known in Enterprise Server. After logon this flag is unset.'),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'us_users', 	'fields' => 'user'),
							array('v' => '4.2', 'name' => 'fu_users', 	'fields' => 'fullname'),
						),
						'inserts' => array(
							"1, 'woodwing', 'WoodWing Software', '', '', '', '', '', '', 'enUS', '', '', '', 0, '#FF0000', '', '', '', '', '' ",
						),
						'lookups' => array(
						),
				),
				array ('v' =>'3.1', 'name' => 'smart_usrgrp',
						'comment' => 'Assign users to user groups.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
								  'alters' => array('v' => '3.1', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'usrid',			'type' => 'int(11)',		'default' => "'0'",
						  	  'alters' => array('v' => '3.1', 'name' => 'usrid',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'grpid',			'type' => 'int(11)',		'default' => "'0'",
						  	  'alters' => array('v' => '3.1', 'name' => 'grpid',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '3.1', 'name' => 'usgi_usrgrp', 'fields' => 'usrid, grpid', 'unique' => true),
							array('v' => '4.2', 'name' => 'gi_usrgrp', 	'fields' => 'grpid'),
						),
						'inserts' => array(
							"2, 1, 2",
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'usrid', 'reftable' => 'smart_users', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'grpid', 'reftable' => 'smart_groups', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.1', 'name' => 'smart_mtp',
						'comment' => '',
						'fields' => array(
							array('v' => '6.1', 'name' => 'publid', 			'type' => 'int(11)', 'default' => "",
								  'alters' => array('v' => '4.1', 'name' => 'publid', 			'type' => 'mediumint(9)', 'default' => ""), ),
							array('v' => '6.1', 'name' => 'issueid',			'type' => 'int(11)', 'default' => "'0'",
								  'alters' => array('v' => '4.1', 'name' => 'issueid',			'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'laytriggerstate',	'type' => 'int(11)', 'default' => "",
								  'alters' => array('v' => '4.1', 'name' => 'laytriggerstate',	'type' => 'mediumint(9)', 'default' => ""), ),
							array('v' => '8.0', 'name' => 'arttriggerstate',	'type' => 'int(11)', 'default' => "0", 'nullable' => false,
								  'alters' => array('v' => '6.1', 'name' => 'arttriggerstate',	'type' => 'int(11)', 'default' => "", 'nullable' => true,
								  'alters' => array('v' => '4.1', 'name' => 'arttriggerstate',	'type' => 'mediumint(9)', 'default' => "", 'nullable' => true ), )),
							array('v' => '8.0', 'name' => 'imgtriggerstate',	'type' => 'int(11)', 'default' => "0", 'nullable' => false,
								  'alters' => array('v' => '6.1', 'name' => 'imgtriggerstate',	'type' => 'int(11)', 'default' => "", 'nullable' => true,
								  'alters' => array('v' => '4.1', 'name' => 'imgtriggerstate',	'type' => 'mediumint(9)', 'default' => "", 'nullable' => true ), )),
							array('v' => '8.0', 'name' => 'layprogstate',		'type' => 'int(11)', 'default' => "0", 'nullable' => false,
								  'alters' => array('v' => '6.1', 'name' => 'layprogstate',		'type' => 'int(11)', 'default' => "", 'nullable' => true,
								  'alters' => array('v' => '4.1', 'name' => 'layprogstate',		'type' => 'mediumint(9)', 'default' => "", 'nullable' => true ), )),
							array('v' => '8.0', 'name' => 'artprogstate',		'type' => 'int(11)', 'default' => "0", 'nullable' => false,
								  'alters' => array('v' => '6.1', 'name' => 'artprogstate',		'type' => 'int(11)', 'default' => "", 'nullable' => true,
								  'alters' => array('v' => '4.1', 'name' => 'artprogstate',		'type' => 'mediumint(9)', 'default' => "", 'nullable' => true ), )),
							array('v' => '8.0', 'name' => 'imgprogstate',		'type' => 'int(11)', 'default' => "0", 'nullable' => false,
								  'alters' => array('v' => '6.1', 'name' => 'imgprogstate',		'type' => 'int(11)', 'default' => "", 'nullable' => true,
								  'alters' => array('v' => '4.1', 'name' => 'imgprogstate',		'type' => 'mediumint(9)', 'default' => "", 'nullable' => true ), )),
							array('v' => '8.0', 'name' => 'mtptext',			'type' => 'blob', 'nullable' => false, 'default' => "''",
								  'alters' => array('v' => '4.1', 'name' => 'mtptext',			'type' => 'blob', 'nullable' => true, 'default' => "")),
						),
						'indexes' => array(
							array ('v' => '4.1', 'name' => 'primary', 'fields' => 'publid, issueid, laytriggerstate', 'primary' => true),
							array ('v' => '4.2', 'name' => 'ii_mtp', 'fields' => 'issueid')
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.1', 'field' => 'publid', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'issueid', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'laytriggerstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'arttriggerstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'imgtriggerstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'layprogstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'artprogstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'imgprogstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.1', 'name' => 'smart_mtpsentobjects',
						'comment' => '',
						'fields' => array(
							array('v' => '6.1', 'name' => 'objid',			'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '5.0', 'name' => 'objid',			'type' => 'mediumint(9)', 'default' => "'0'",
										  'alters' => array('v' => '4.1', 'name' => 'objid', 'type' => 'mediumint(9)', 'default' => "'0'", 'autoincrement' => true)), ),
							array('v' => '6.1', 'name' => 'publid',			'type' => 'int(11)', 'default' => "" ,
									'alters' => array('v' => '4.1', 'name' => 'publid',			'type' => 'mediumint(9)', 'default' => "" ), ),
							array('v' => '6.1', 'name' => 'issueid',		'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '4.1', 'name' => 'issueid',		'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'laytriggerstate','type' => 'int(11)', 'default' => "",
									'alters' => array('v' => '4.1', 'name' => 'laytriggerstate','type' => 'mediumint(9)', 'default' => ""), ),
							array('v' => '4.1', 'name' => 'printstate',		'type' => 'mediumint(1)', 'default' => ""),
						),
						'indexes' => array(
							array ('v' => '4.1', 'name' => 'primary', 'fields' => 'objid, publid, issueid, laytriggerstate, printstate', 'primary' => true),
							array ('v' => '4.2', 'name' => 'ii_mtpsentobjects', 'fields' => 'issueid'),
							array ('v' => '4.2', 'name' => 'ls_mtpsentobjects', 'fields' => 'laytriggerstate')

						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.1', 'field' => 'publid', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'issueid', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.1', 'field' => 'laytriggerstate', 'reftable' => 'smart_states', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.1', 'name' => 'smart_messagelog',
						'comment' => 'Log messages per object / user for reporting purposes.',
						'fields' => array(
							array('v' => '8.0', 'name' => 'id',					'type' => 'bigint(20)', 		'autoincrement' => true,
									'alters' => array('v' => '6.1', 'name' => 'id',					'type' => 'int(11)', 			'autoincrement' => true,
									'alters' => array('v' => '4.1', 'name' => 'id',					'type' => 'mediumint(9)', 		'autoincrement' => true), )),
						    array('v' => '8.0',	'name' => 'objid',	'type' => 'int(11)',	'default' => 0, 	'nullable' => false,
						    		'alters' => array('v' => '6.1', 'name' => 'objid', 				'type' => 'int(11)',		'default' => "'0'", 	'nullable' => true,
						    		'alters' => array('v' => '4.1', 'name' => 'objid', 				'type' => 'mediumint(9)',		'default' => "'0'", 	'nullable' => true), )),
						    array('v' => '8.0', 'name' => 'userid', 			'type' => 'int(11)',		'default' => 0, 	'nullable' => false,
						    		'alters' => array('v' => '6.1', 'name' => 'userid', 			'type' => 'int(11)',		'default' => "'0'", 	'nullable' => true,
						    		'alters' => array('v' => '4.1', 'name' => 'userid', 			'type' => 'mediumint(9)',		'default' => "'0'", 	'nullable' => true), )),
							array('v' => '4.1', 'name' => 'messagetype', 		'type' => 'varchar(255)', 		'default' => ""),
							array('v' => '4.1', 'name' => 'messagetypedetail',	'type' => 'varchar(255)', 		'default' => ""),
							array('v' => '4.1', 'name' => 'message', 			'type' => 'blob', 				'default' => ""),
							array('v' => '4.1', 'name' => 'date',				'type' => 'varchar(30)',		'default' => "''", 'comment' => "'yyyy-mm-ddThh:mm:ss'"),
							array('v' => '4.1', 'name' => 'expirationdate',		'type' => 'varchar(30)',		'default' => "''"),
							array('v' => '4.1', 'name' => 'messagelevel',		'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '4.1', 'name' => 'fromuser',			'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '4.2', 'name' => 'msgid',				'type' => 'varchar(200)',		'default' => "''"),
							array('v' => '4.2', 'name' => 'anchorx',			'type' => 'double',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'anchory',			'type' => 'double',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'left',				'type' => 'double',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'top',				'type' => 'double',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'width',				'type' => 'double',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'height',				'type' => 'double',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'page',				'type' => 'mediumint(9)',		'default' => "'0'",
							  	'comment' => 'Sequential page order within an issue' ),
							array('v' => '4.2', 'name' => 'version',			'type' => 'varchar(200)',		'default' => "''",
								'comment' => 'Sticky info version to track and trigger updates.' ),
							array('v' => '4.2', 'name' => 'color',				'type' => 'varchar(11)',		'default' => "''"),
							array('v' => '5.0', 'name' => 'pagesequence',		'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Sequential page order within a layout' ),
							array('v' => '8.0', 'name' => 'threadmessageid',    'type' => 'varchar(200)',       'default' => "''",
						  		'comment' => 'The msgid of parental message. Can be seen as a message thread.' ),
							array('v' => '8.0', 'name' => 'replytomessageid',   'type' => 'varchar(200)',       'default' => "''",
						  		'comment' => 'The msgid of previous message the user has replied on.' ),
							array('v' => '8.0', 'name' => 'messagestatus',      'type' => 'varchar(15)',        'default' => "'None'",
						  		'comment' => 'Current status of this message, following a basic message workflow.' ),
						    array('v' => '8.0', 'name' => 'majorversion',		'type' => 'mediumint(9)',	    'default' => "'0'",
						  		'comment' => 'Major object version when this message was created.' ),
						    array('v' => '8.0', 'name' => 'minorversion',		'type' => 'mediumint(9)',	    'default' => "'0'",
						  		'comment' => 'Minor object version when this message was created. See also majorversion field.' ),
							array('v' => '8.0', 'name' => 'isread',             'type' => 'varchar(2)',         'default' => "''",
						  		'comment' => 'Whether or not this message has been read by any user (or group) in the system.' ),
						),
						'indexes' => array(
							array ('v' => '4.2', 'name' => 'oimtpa_messagelog', 	'fields' => 'objid, messagetype, page'),
							array ('v' => '4.2', 'name' => 'oimtd_messagelog', 	'fields' => 'objid, messagetypedetail', ),
							array ('v' => '4.2', 'name' => 'mi_messagelog', 	'fields' => 'msgid'),
							array ('v' => '8.0', 'name' => 'uid_messagelog', 	'fields' => 'userid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'userid', 'reftable' => 'smart_users', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'page', 'reftable' => 'smart_pages', 'reffield' => 'pageorder'),
							array('v' => '5.0', 'field' => 'pagesequence', 'reftable' => 'smart_pages', 'reffield' => 'pagesequence'),
						),
				),
				array ('v' =>'4.1', 'name' => 'smart_objectflags',
						'comment' => 'Contains volatile object flags, eg. set by planning system when layout is modified.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'objid',			'type' => 'int(11)',		'default' => "",
									'alters' => array('v' => '4.1', 'name' => 'objid',			'type' => 'mediumint(9)',		'default' => ""), ),
							array('v' => '4.1', 'name' => 'flagorigin',		'type' => 'varchar(255)',		'default' => ""),
							array('v' => '4.1', 'name' => 'flag',			'type' => 'mediumint(9)',		'default' => ""),
							array('v' => '4.1', 'name' => 'severity',		'type' => 'mediumint(9)',		'default' => ""),
						    array('v' => '8.0', 'name' => 'message',		'type' => 'blob',				'default' => "''", 'nullable' => false,
						    	'alters' => array('v' => '4.1', 'name' => 'message',		'type' => 'blob',				'default' => "", 'nullable' => true)),
						    array('v' => '8.0', 'name' => 'locked',			'type' => 'mediumint(1)',		'default' => "0", 'nullable' => false,
						    	'alters' => array('v' => '4.1', 'name' => 'locked',			'type' => 'mediumint(1)',		'default' => "", 'nullable' => true)),
						),
						'indexes' => array(
							array ('v' => '4.1', 'name' => 'primary', 'fields' => 'objid, flagorigin, flag', 'primary' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_issuesection',
						'comment' => '',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'issue',			'type' => 'int(11)',		'default' => "'0'",
						  	'comment' => 'If 0, section for every issue in publication' ,
								  'alters' => array('v' => '4.2', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'",
								  	'comment' => 'If 0, section for every issue in publication' ), ),
						  array('v' => '6.1', 'name' => 'section',			'type' => 'int(11)',		'default' => "'0'",
								  'alters' => array('v' => '4.2', 'name' => 'section',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '8.0', 'name' => 'deadline',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
								'alters' => array('v' => '4.2', 'name' => 'deadline',			'type' => 'varchar(20)',		'default' => "''")),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'isse_issuesection', 'fields' => 'issue, section'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_issuesectionstate',
						'comment' => 'Overrule state/section(category) deadline for issue.',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  		'alters' => array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  array('v' => '6.1', 'name' => 'issue',			'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'issue',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'section',			'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'section',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '6.1', 'name' => 'state',			'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'state',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
						  array('v' => '8.0', 'name' => 'deadline',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
							  	'alters' => array('v' => '4.2', 'name' => 'deadline',			'type' => 'varchar(20)',		'default' => "''")),
						  array('v' => '6.1', 'name' => 'deadlinerelative',	'type' => 'int(11)',		'default' => "'0'",
						  		'alters' => array('v' => '4.2', 'name' => 'deadlinerelative',	'type' => 'mediumint(9)',		'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'issest_issuesectionstate', 'fields' => 'issue, section, state'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_sectionstate',
						'comment' => 'Overrule statedeadline for section (category).',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'section',		'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '4.2', 'name' => 'section',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'state',			'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '4.2', 'name' => 'state',			'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'deadlinerelative','type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '4.2', 'name' => 'deadlinerelative','type' => 'mediumint(9)',		'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'sest_sectionstate', 'fields' => 'section, state'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
							array('v' => '4.2', 'field' => 'state', 'reftable' => 'smart_states', 'reffield' => 'id'),
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_profiles',
						'comment' => 'Profile definitions.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '4.2', 'name' => 'profile',		'type' => 'varchar(255)',		'default' => "''"),
							array('v' => '4.2', 'name' => 'code',			'type' => 'int(4)',				'default' => "'0'"),
							array('v' => '4.2', 'name' => 'description',	'type' => 'blob',				'default' => ""),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'pr_profiles', 'fields' => 'profile'),
						),
						'inserts' => array(
							"1, 'Full Control', 0, 'All features enabled'",
						),
						'lookups' => array(
						),
				),
				array ('v' =>'4.2', 'name' => 'smart_profilefeatures',
						'comment' => 'Enabled/disabled server and client features by profile.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
									'alters' => array('v' => '4.2', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'profile',		'type' => 'int(11)',		'default' => "'0'",
									'alters' => array('v' => '4.2', 'name' => 'profile',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '4.2', 'name' => 'feature',		'type' => 'mediumint(9)',		'default' => "'0'"),
							array('v' => '4.2', 'name' => 'value',			'type' => 'varchar(20)',		'default' => "''"),
						),
						'indexes' => array(
							array('v' => '4.2', 'name' => 'prfe_profiles', 'fields' => 'profile, feature'),
						),
						'inserts' => array(
							"1, 1, 1, 'Yes'",
							"2, 1, 2, 'Yes'",
							"3, 1, 3, 'Yes'",
							"4, 1, 4, 'Yes'",
							"5, 1, 5, 'Yes'",
							"6, 1, 6, 'Yes'",
							"7, 1, 7, 'Yes'",
							"8, 1, 8, 'Yes'",
							"9, 1, 9, 'Yes'",
							"10, 1, 10, 'Yes'",
							"11, 1, 99, 'Yes'",
							"12, 1, 101, 'Yes'",
							"13, 1, 102, 'Yes'",
							"14, 1, 103, 'Yes'",
							"15, 1, 104, 'Yes'",
							"16, 1, 105, 'Yes'",
							"17, 1, 106, 'Yes'",
							"18, 1, 107, 'Yes'",
							"19, 1, 108, 'Yes'",
							"20, 1, 109, 'Yes'",
							"21, 1, 110, 'Yes'",
							"22, 1, 111, 'Yes'",
							"23, 1, 112, 'Yes'",
							"24, 1, 113, 'Yes'",
							"25, 1, 114, 'Yes'",
							"26, 1, 115, 'Yes'",
							"27, 1, 116, 'Yes'",
							"28, 1, 117, 'Yes'",
							"29, 1, 118, 'Yes'",
							"30, 1, 119, 'Yes'",
							"31, 1, 120, 'Yes'",
							"32, 1, 121, 'Yes'",
							"33, 1, 122, 'Yes'",
							"34, 1, 124, 'Yes'",
							"35, 1, 125, 'No'",
							"36, 1, 126, 'Yes'",
							"37, 1, 127, 'Yes'",
							"38, 1, 128, 'Yes'",
							"39, 1, 129, 'Yes'",
							"40, 1, 130, 'Yes'",
							"41, 1, 131, 'Yes'",
							"42, 1, 132, 'Yes'",
							"43, 1, 133, 'Yes'",
							"44, 1, 134, 'Yes'",
							"45, 1, 135, 'Yes'",
							"46, 1, 1001, 'Yes'",
							"47, 1, 1002, 'Yes'",
							"48, 1, 1003, 'Yes'",
							"49, 1, 1004, 'Yes'",
//							"50, 1, 1005, 'Yes'",
//							"51, 1, 1006, 'Yes'",
							"52, 1, 1007, 'Yes'",
							"53, 1, 1008, 'Yes'",
							"54, 1, 91, 'Yes'",
							"55, 1, 92, 'Yes'",
							"56, 1, 93, 'Yes'",
							"57, 1, 90, 'Yes'",
							"58, 1, 98, 'Yes'", // RestrictedProperties
							"59, 1, 88, 'Yes'",
//							"60, 1, 89, 'Yes'", // Edit Sticky Notes. Taken out since v8.0 => Replaced with 70,71and72.
							"61, 1, 87, 'Yes'",
							"62, 1, 86, 'Yes'",
							"63, 1, 85, 'Yes'",
							"64, 1, 1009, 'Yes'",
							"65, 1, 11, 'Yes'",
							"66, 1, 12, 'Yes'",
							"67, 1, 13, 'Yes'",
							"68, 1, 136, 'Yes'",
							"69, 1, 70, 'Yes'",
							"70, 1, 71, 'Yes'",
							"71, 1, 72, 'Yes'",
							"72, 1, 84, 'Yes'",
						),
						'lookups' => array(
							array('v' => '4.2', 'field' => 'profile', 'reftable' => 'smart_profiles', 'reffield' => 'id'),
						),
				),

				array ('v' =>'5.0', 'name' => 'smart_appsessions',
						'comment' => 'Allows applications to store specific session data. The Web Editor uses this to keep track of opened articles which are not checked in yet.',
						'fields' => array(

						 	array('v' => '6.1', 'name' => 'id',						'type' => 'int(11)', 		'autoincrement' => true,
								'comment' => 'Autoincrement, unique ID' ,
								 	'alters' => array('v' => '5.0', 'name' => 'id',						'type' => 'mediumint(9)', 		'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '5.0', 'name' => 'sessionid',			'type' => 'varchar(40)',		'default' => "''",
								'comment' => 'GUID of the application session.' ),
							array('v' => '5.0', 'name' => 'userid',				'type' => 'varchar(40)',		'default' => "''",
								'comment' => 'Short user name (id) who initiated the application session.' ),
							array('v' => '5.0', 'name' => 'appname',				'type' => 'varchar(40)',		'default' => "''",
								'comment' => 'Application name which started the session.' ),
							array('v' => '5.0', 'name' => 'lastsaved',			'type' => 'varchar(20)',		'default' => "''",
							  	'comment' => 'Last time article was saved in workspace. Timestamp in SOAP datetime format.' ),
							array('v' => '5.0', 'name' => 'readonly',				'type' => 'char(2)',				'default' => "''",
							  	'comment' => 'Article opened for reading (on) or opened for editing (empty).' ),

						 	array('v' => '8.0',	'name' => 'articleid',	'type' => 'int(11)',	'default' => 0,	'nullable' => false,	'comment' => 'Article object id.',
						 		'alters' => array('v' => '6.1', 'name' => 'articleid',			'type' => 'int(11)', 		'default' => "'0'",	'nullable' => true,	'comment' => 'Article object id.' ,
							 	'alters' => array('v' => '5.0', 'name' => 'articleid',			'type' => 'mediumint(9)', 		'default' => "'0'",	'nullable' => true,	'comment' => 'Article object id.' ) ) ),
							array('v' => '8.0', 'name' => 'articlename', 'type' => 'varchar(255)', 'default' => "''", 'nullable' => false,
							'alters' => array('v' => '5.0', 'name' => 'articlename',			'type' => 'varchar(255)',		'default' => "NULL",	'nullable' => true),
								'comment' => 'Article object name.' ),
							array('v' => '8.0', 'name' => 'articleformat',		'type' => 'varchar(128)',		'default' => "''",	'nullable' => false,	'comment' => 'Native mime type of article object.',
								'alters' => array('v' => '6.1', 'name' => 'articleformat',		'type' => 'varchar(128)',		'default' => "NULL",	'nullable' => true,	'comment' => 'Native mime type of article object.',
									'alters' => array('v' => '5.0', 'name' => 'articleformat',		'type' => 'varchar(40)',		'default' => "NULL",	'nullable' => true ) )),
						 	array('v' => '8.0', 'name' => 'articleminorversion','type' => 'mediumint(9)',		'default' => 0,	'nullable' => false, 'comment' => 'Current article minor version shown at editor for editing or viewing. Zero for new (uncreated) articles. See also articlemajorversion field.',
						 	'alters' => array('v' => '6.0', 'name' => 'articleminorversion','type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true, 'comment' => 'Current article minor version shown at editor for editing or viewing. Zero for new (uncreated) articles. See also articlemajorversion field.',
						 		'alters' => array(
						 		  'v' => '5.0', 'name' => 'articleversion',		'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true, ))),

						 	array('v' => '8.0', 'name' => 'templateid',			'type' => 'int(11)', 		'default' => 0,	'nullable' => false, 'comment' => 'Article template object id.' , 'alters' =>  array('v' => '6.1', 'name' => 'templateid',			'type' => 'int(11)', 		'default' => "'0'",	'nullable' => true, 'comment' => 'Article template object id.', 'alters' => array('v' => '5.0', 'name' => 'templateid',			'type' => 'mediumint(9)', 		'default' => "'0'",	'nullable' => true, 'comment' => 'Article template object id.' ), )),
							array('v' => '8.0', 'name' => 'templatename',		'type' => 'varchar(255)',		'default' => "''",	'nullable' => false,	'comment' => 'Article template object name.', 'alters' => array('v' => '5.0', 'name' => 'templatename',		'type' => 'varchar(255)',		'default' => "NULL",	'nullable' => true,	'comment' => 'Article template object name.') ),
							array('v' => '8.0', 'name' => 'templateformat',		'type' => 'varchar(128)',		'default' => "''",	'nullable' => false, 'comment' => 'Native mime type of article template object.', 'alters' => array('v' => '6.1', 'name' => 'templateformat',		'type' => 'varchar(128)',		'default' => "NULL",	'nullable' => true, 'comment' => 'Native mime type of article template object.',
									'alters' => array('v' => '5.0', 'name' => 'templateformat',		'type' => 'varchar(40)',		'default' => "NULL",	'nullable' => true ), )),

						 	array('v' => '8.0', 'name' => 'layoutid',				'type' => 'int(11)', 		'default' => 0,	'nullable' => false, 'comment' => 'Layout object id. This layout is used for write-to-fit. Zero when article not placed.' ,
						 	'alters' => array('v' => '6.1', 'name' => 'layoutid',				'type' => 'int(11)', 		'default' => "'0'",	'nullable' => true, 'comment' => 'Layout object id. This layout is used for write-to-fit. Zero when article not placed.',
								 	'alters' => array('v' => '5.0', 'name' => 'layoutid',				'type' => 'mediumint(9)', 		'default' => "'0'",	'nullable' => true,
										'comment' => 'Layout object id. This layout is used for write-to-fit. Zero when article not placed.' ), )),
						 	array('v' => '8.0', 'name' => 'layoutminorversion',	'type' => 'mediumint(9)',		'default' => 0,	'nullable' => false, 'comment' => 'Last layout minor version retrieved from DB to workspace, used for preview, PDF, or write-to-fit. See also layoutmajorversion field.',
						 	'alters' => array('v' => '6.0', 'name' => 'layoutminorversion',	'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true, 'comment' => 'Last layout minor version retrieved from DB to workspace, used for preview, PDF, or write-to-fit. See also layoutmajorversion field.', 
						 		'alters' => array(
						 		  'v' => '5.0', 'name' => 'layoutversion',		'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true, ),)),

						 	array('v' => '8.0', 'name' => 'articlemajorversion',	'type' => 'mediumint(9)',		'default' => 0,	'nullable' => false, 'comment' => 'Current article major version shown at editor for editing or viewing. Zero based. See also articleversion field.',
						 	'alters' => array('v' => '6.0', 'name' => 'articlemajorversion',	'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true, 'comment' => 'Current article major version shown at editor for editing or viewing. Zero based. See also articleversion field.' )),
						 	array('v' => '8.0', 'name' => 'layoutmajorversion',		'type' => 'mediumint(9)',		'default' => 0,	'nullable' => false,
						 		'comment' => 'Last layout major version retrieved from DB to workspace, used for preview, PDF, or write-to-fit. Zero based. See also layoutversion field.', 'alters' => array('v' => '6.0', 'name' => 'layoutmajorversion',		'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true,
						 		'comment' => 'Last layout major version retrieved from DB to workspace, used for preview, PDF, or write-to-fit. Zero based. See also layoutversion field.') ),
							array('v' => '8.0', 'name' => 'dommajorversion',		'type' => 'mediumint(9)',		'default' => "'5'",	'nullable' => false, 
								    'comment' => 'Document model version of article. 5=CS3, 6=CS4, etc. Used as minimum required ID Server version, used for preview, PDF, or write-to-fit. Default value is 5 (=CS3).',
									'alters' => array('v' => '7.5', 'name' => 'dommajorversion',		'type' => 'mediumint(9)',		'default' => 5,	'nullable' => true,					
												'comment' => 'Document model version of article. 5=CS3, 6=CS4, etc. Used as minimum required ID Server version, used for preview, PDF, or write-to-fit. Default value is 5 (=CS3).', 
												'alters' => array('v' => '7.0', 'name' => 'domversion',		'type' => 'mediumint(9)',		'default' => "'5'",	'nullable' => true,
													'comment' => 'Document model version of article. 5=CS3, 6=CS4, etc. Used as minimum required ID Server version, used for preview, PDF, or write-to-fit. Default value is 5 (=CS3).' 
								))),

						 	array('v' => '8.0', 'name' => 'domminorversion',		'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => false,
						 		  'comment' => 'Document model minor version of article. Used as minimum required ID Server version, used for preview, PDF, or write-to-fit. Default value is 0. See also dommajorversion field.',
						 		  'alters' => array('v' => '7.5', 'name' => 'domminorversion',		'type' => 'mediumint(9)',		'default' => "'0'",	'nullable' => true,
							 		'comment' => 'Document model minor version of article. Used as minimum required ID Server version, used for preview, PDF, or write-to-fit. Default value is 0. See also dommajorversion field.' )),
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '5.0', 'field' => 'userid', 'reftable' => 'smart_users', 'reffield' => 'user'),
							array('v' => '5.0', 'field' => 'articleid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '5.0', 'field' => 'templateid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '5.0', 'field' => 'layoutid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'articlemajorversion', 'reftable' => 'smart_objects', 'reffield' => 'majorversion'),
							array('v' => '6.0', 'field' => 'articleminorversion', 'reftable' => 'smart_objects', 'reffield' => 'minorversion'),
							array('v' => '6.0', 'field' => 'layoutmajorversion', 'reftable' => 'smart_objects', 'reffield' => 'majorversion'),
							array('v' => '6.0', 'field' => 'layoutminorversion', 'reftable' => 'smart_objects', 'reffield' => 'minorversion'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_datasources',
						'comment' => 'Datasource definitions (or Datasource Connections).',
						'fields' => array(

							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)',	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)',	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.0', 'name' => 'type',			'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'Type of Datasource (filename)' ),
							array('v' => '6.0', 'name' => 'name',			'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'Name of the Datasource' ),
							array('v' => '6.0', 'name' => 'bidirectional',	'type' => 'char(2)',			'default' => "''",
							'comment' => 'Is this Datasource bidirectional (yes/no)' ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'na_datasources', 'fields' => 'name'),
						),
						'inserts' => array(
						),
						'lookups' => array(
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dspublications',
						'comment' => 'Datasource / Publication relation(s).',
						'fields' => array(

							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)', 	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.1', 'name' => 'datasourceid',	'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Datasource ID' ,
									'alters' => array('v' => '6.0', 'name' => 'datasourceid',	'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Datasource ID' ), ),
							array('v' => '6.1', 'name' => 'publicationid',	'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Publication ID' ,
									'alters' => array('v' => '6.0', 'name' => 'publicationid',	'type' => 'mediumint(9)',	'default' => "'0'",
									'comment' => 'Publication ID' ), ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'dsid_dspublications', 'fields' => 'datasourceid'),
							array('v' => '6.0', 'name' => 'pubid_dspublications', 'fields' => 'publicationid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'datasourceid', 'reftable' => 'smart_datasources', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'publicationid', 'reftable' => 'smart_publications', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dsqueries',
						'comment' => 'Queries.',
						'fields' => array(

							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)', 	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.0', 'name' => 'name',			'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'Query name' ),
							array('v' => '6.0', 'name' => 'query',			'type' => 'blob',		'default' => "''",
							'comment' => 'Query' ),
							array('v' => '6.0', 'name' => 'comment',		'type' => 'blob',		'default' => "''",
							'comment' => 'Comment(s)' ),
							array('v' => '6.0', 'name' => 'interface',		'type' => 'blob',		'default' => "''",
							'comment' => 'Interface (params)' ),
							array('v' => '6.1', 'name' => 'datasourceid',	'type' => 'int(11)',			'default' => "'0'",
							'comment' => 'Datasource ID' ,
									'alters'=> array('v' => '6.0', 'name' => 'datasourceid',	'type' => 'mediumint(9)',			'default' => "'0'",
										'comment' => 'Datasource ID' ), ),
							array('v' => '6.0', 'name' => 'recordid',		'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'the field in the result that is the ID for the record (e.g: ID)' ),
							array('v' => '6.0', 'name' => 'recordfamily',	'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'the field in the result that defines the records family (e.g: color)' ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'dsid_dsqueries', 'fields' => 'datasourceid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'datasourceid', 'reftable' => 'smart_datasources', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dsqueryfields',
						'comment' => 'Query field definitions.',
						'fields' => array(

							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)',	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)',	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.1', 'name' => 'queryid',		'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Query ID' ,
									'alters' => array('v' => '6.0', 'name' => 'queryid',		'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Query ID' ), ),
							array('v' => '6.0', 'name' => 'priority',		'type' => 'tinyint(4)',			'default' => "'0'",
							'comment' => 'Priority level (0 = normal, 1 = high)' ),
							array('v' => '6.0', 'name' => 'name',			'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'Recordfield name' ),
							array('v' => '6.0', 'name' => 'readonly',		'type' => 'tinyint(4)',		'default' => "'0'",
							'comment' => 'Is this field readonly (yes/no)' ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'queryid_dsqueryfields', 'fields' => 'queryid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'queryid', 'reftable' => 'smart_dsqueries', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dssettings',
						'comment' => 'Settings of a Datasource',
						'fields' => array(

							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)', 	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.0', 'name' => 'name',			'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'Name of the setting' ),
							array('v' => '6.0', 'name' => 'value',			'type' => 'blob',			'default' => "''",
							'comment' => 'Value of the setting' ),
							array('v' => '6.1', 'name' => 'datasourceid',	'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Datasource ID' ,
									'alters' => array('v' => '6.0', 'name' => 'datasourceid',	'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Datasource ID' ), ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'dsid_dssettings', 'fields' => 'datasourceid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'datasourceid', 'reftable' => 'smart_datasources', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dsqueryplacements',
						'comment' => 'Table that identifies the relation between documents and queries',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',					'type' => 'int(11)',	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',					'type' => 'mediumint(9)',	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.1', 'name' => 'objectid',			'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Document ID' ,
									'alters' => array('v' => '6.0', 'name' => 'objectid',			'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Document ID' ), ),
							array('v' => '6.1', 'name' => 'datasourceid',		'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Datasource ID' ,
									'alters' => array('v' => '6.0', 'name' => 'datasourceid',		'type' => 'mediumint(9)',	'default' => "'0'",
									'comment' => 'Datasource ID' ), ),
							array('v' => '6.0', 'name' => 'dirty',				'type' => 'char(2)',		'default' => "'0'",
							'comment' => 'Dirty flag' ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'objid_dsqueryplacements', 	'fields' => 'objectid'),
							array('v' => '6.0', 'name' => 'dsid_dsqueryplacements', 	'fields' => 'datasourceid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'datasourceid', 'reftable' => 'smart_datasources', 'reffield' => 'id'),
							array('v' => '6.0',	'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dsqueryfamilies',
						'comment' => 'Table that identifies the relation between queryplacements and family values',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',					'type' => 'int(11)',	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',					'type' => 'mediumint(9)',	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.1', 'name' => 'queryplacementid',	'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Queryplacement ID',
									'alters' => array('v' => '6.0', 'name' => 'queryplacementid',	'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Queryplacement ID' ), ),
							array('v' => '6.0', 'name' => 'familyfield',		'type' => 'varchar(255)',	'default' => "''",
							'comment' => 'Family field name' ),
							array('v' => '6.0', 'name' => 'familyvalue',		'type' => 'blob',			'default' => "''",
							'comment' => 'Family field value' ),
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'queryplacementid', 'reftable' => 'smart_dsqueryplacements', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dsupdates',
						'comment' => 'Table that stores updated record sets',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',					'type' => 'int(11)',	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',					'type' => 'mediumint(9)',	'autoincrement' => true,
										'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.0', 'name' => 'recordset',			'type' => 'longblob',		'default' => "''",
							'comment' => 'The updated record set' ),
							array('v' => '6.0', 'name' => 'familyvalue',		'type' => 'blob',			'default' => "''",
							'comment' => 'Family field value' ),
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_dsobjupdates',
						'comment' => 'Table that holds the relation between objects and updates',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',					'type' => 'int(11)',	'autoincrement' => true,
							'comment' => 'Autoincrement, unique ID' ,
									'alters' => array('v' => '6.0', 'name' => 'id',					'type' => 'mediumint(9)',	'autoincrement' => true,
									'comment' => 'Autoincrement, unique ID' ), ),
							array('v' => '6.1', 'name' => 'updateid',			'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Update ID',
									'alters' => array('v' => '6.0', 'name' => 'updateid',			'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Update ID' ), ),
							array('v' => '6.1', 'name' => 'objectid',			'type' => 'int(11)',	'default' => "'0'",
							'comment' => 'Object ID' ,
									'alters' => array('v' => '6.0', 'name' => 'objectid',			'type' => 'mediumint(9)',	'default' => "'0'",
										'comment' => 'Object ID' ), ),
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0',	'field' => 'updateid', 'reftable' => 'smart_dsupdates', 'reffield' => 'id'),
							array('v' => '6.0',	'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						),
				),

				array(	'v' => '6.0', 'name' => 'smart_channels',
						'comment' => 'Channels',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id', 'type' => 'int(11)', 'autoincrement' => true,
									'alters' => array('v' => '6.0', 'name' => 'id', 'type' => 'mediumint(9)', 'autoincrement' => true), ),
							array('v' => '6.0', 'name' => 'name', 'type' => 'varchar(255)', 'default' => "''"),
							array('v' => '6.1', 'name' => 'publicationid', 'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'publicationid', 'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.0', 'name' => 'type', 'type' => 'varchar(32)', 'default' => "'print'",
								'comment' => 'type is either: print, web, sms, other, dps','dps2'),
							array('v' => '6.0', 'name' => 'description', 'type' => 'varchar(255)', 'default' => "''"),
						  	array('v' => '6.0', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'"),
							array('v' => '6.1', 'name' => 'deadlinerelative',	'type' => 'int(11)',	'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'deadlinerelative',	'type' => 'mediumint(9)',	'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'currentissueid', 'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'currentissueid', 'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'publishsystem',  'type' => 'varchar(64)',  'default' => "''",
									'comment' => 'System (publishing connector) to publish content of the channel'),
							array('v' => '9.1', 'name' => 'suggestionprovider',  'type' => 'varchar(64)',  'default' => "''",
									'comment' => 'Suggestion provider (Suggestion connector) of the channel.'),
							array('v' => '9.1', 'name' => 'publishsystemid', 'type' => 'varchar(40)', 'default' => "''",
									'comment' => 'Unique id of the publishing system. Use to bind the publishing storage.' ),
						),
						'indexes' => array(
						),
						'inserts' => array(
							"1, 'Print', 1, 'print', 'Print Channel', 10, 0, 1, '', '', '' ",
							"2, 'Web', 1, 'web', 'Web Channel', 20, 0, 0, '', '', '' ",
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'publicationid', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'currentissueid', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),

				array ('v' =>'6.0', 'name' => 'smart_editions',
						'comment' => 'Editions, replaces table smart_publeditions.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 	'autoincrement' => true,
									'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)', 	'autoincrement' => true), ),
							array('v' => '6.0', 'name' => 'name',				'type' => 'varchar(255)',	'default' => "''"),
							array('v' => '6.1', 'name' => 'channelid',		'type' => 'int(11)',	'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'channelid',		'type' => 'mediumint(9)',	'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'issueid',			'type' => 'int(11)',	'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'issueid',			'type' => 'mediumint(9)',	'default' => "'0'"), ),
							array('v' => '6.0', 'name' => 'code',				'type' => 'int(4)',			'default' => "'0'"),
							array('v' => '6.1', 'name' => 'deadlinerelative',	'type' => 'int(11)',	'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'deadlinerelative',	'type' => 'mediumint(9)',	'default' => "'0'"), ),
							array('v' => '6.0', 'name' => 'description',		'type' => 'blob',			'default' => ""),
						),
						'indexes' => array(
						),
						'inserts' => array(
							"1, 'North', 1, 0, '10', '', '' ",
							"2, 'South', 1, 0, '20', '', '' ",
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'channelid', 'reftable' => 'smart_channels', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'issueid', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),

				array ('v' =>'6.0', 'name' => 'smart_issues',
						'comment' => 'Replaces table smart_publissues.',
						'fields' => array(
						  	array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true,
						  			'alters' => array('v' => '6.0', 'name' => 'id',				'type' => 'mediumint(9)', 		'autoincrement' => true), ),
						  	array('v' => '6.0', 'name' => 'name',				'type' => 'varchar(255)',		'default' => "''"),
						  	array('v' => '6.1', 'name' => 'channelid',		'type' => 'int(11)',		'default' => "'0'",
						  			'alters' => array('v' => '6.0', 'name' => 'channelid',		'type' => 'mediumint(9)',		'default' => "'0'"), ),
							array('v' => '6.0', 'name' => 'overrulepub',		'type' => 'char(2)',			'default' => "''",
						  	'comment' => 'Define workflow at issue level' ),
						  	array('v' => '6.0', 'name' => 'code',				'type' => 'int(4)',				'default' => "'0'"),
						  	array('v' => '8.0', 'name' => 'publdate',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
								'alters' => array('v' => '6.0', 'name' => 'publdate',			'type' => 'varchar(200)',		'default' => "''")),
						  	array('v' => '8.0', 'name' => 'deadline',			'type' => 'varchar(30)',		'default' => "''", 'comment' => 'yyyy-mm-ddThh:mm:ss',
								'alters' => array('v' => '6.0', 'name' => 'deadline',			'type' => 'varchar(200)',		'default' => "''")),
						  	array('v' => '6.0', 'name' => 'pages',			'type' => 'int(4)',				'default' => "'0'"),
						  	array('v' => '6.0', 'name' => 'subject',			'type' => 'blob',				'default' => ""),
						  	array('v' => '6.0', 'name' => 'description',		'type' => 'blob',				'default' => ""),
						  	array('v' => '6.0', 'name' => 'active',			'type' => 'char(2)',			'default' => "''"),
						  	array('v' => '8.0', 'name' => 'readingorderrev',	'type' => 'varchar(2)',			'default' => "''", 'nullable' => false,
						  		'alters' => array('v' => '6.0', 'name' => 'readingorderrev',	'type' => 'varchar(2)',			'default' => "''", 'nullable' => true)),
							array('v' => '9.2', 'name' => 'calculatedeadlines', 'type' => 'char(2)',  'default' => "''",
								'comment' => 'A field to indicate whether the deadline should be calculated when deadline field is configured.' ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'ch_issues', 	'fields' => 'channelid'),
							array('v' => '6.0', 'name' => 'na_issues', 	'fields' => 'name'),
						),
						'inserts' => array(
							"1, '1st Issue', 1, '', '10', '', '', 16, '', '', 'on', '', '' ",
							"2, '2nd Issue', 1, '', '20', '', '', 16, '', '', 'on', 'on', '' ",
							"3, 'webissue', 2, '', '10', '', '', 16, '', '', 'on', 'on', '' ",
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'channelid', 'reftable' => 'smart_channels', 'reffield' => 'id'),
						),
				),

				array ('v' => '6.0', 'name' => 'smart_targets',
						'comment' => 'Objects can be targeted for more than one channel/issue.',
						'fields' => array(
						  	array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true,
						  			'alters' => array('v' => '6.0', 'name' => 'id',			'type' => 'mediumint(9)', 		'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'objectid', 	'type' => 'int(11)', 'default' => "'0'",
								'comment' => 'Contains child id in case objectrelationid is filled',
									'alters' => array('v' => '6.0', 'name' => 'objectid', 	'type' => 'mediumint(9)', 'default' => "'0'",
										'comment' => 'Contains child id in case objectrelationid is filled'), ),
							array('v' => '6.1', 'name' => 'channelid', 	'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'channelid', 	'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'issueid', 	'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'issueid', 	'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.0', 'name' => 'externalid',	'type' => 'varchar(200)', 	'default' => "''",
								'comment' => 'External id for the object in the target' ),
							array('v' => '6.1', 'name' => 'objectrelationid', 	'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'objectrelationid', 	'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'publisheddate',	'type' => 'varchar(30)', 	'default' => "''",
								'comment' => 'Date/time when published, cleared by undo publish' ),
						  	array('v' => '6.1', 'name' => 'publishedmajorversion',		'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Major object version of object(relation) at publish action.' ),
						  	array('v' => '6.1', 'name' => 'publishedminorversion',		'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Minor object version of object(relation) at publish action.' ),
						),
						'indexes' => array(
							array('v' => '6.1', 'name' => 'obchisobr_targets', 'fields' => 'objectid, channelid, issueid, objectrelationid', 'unique' => true,
									'alters' => array('v' => '6.0', 'name' => 'obchised_targets', 'fields' => 'objectid, channelid, issueid', 'unique' => true),),
							array('v' => '6.0', 'name' => 'obrobid_targets', 'fields' => 'objectrelationid, objectid, id', 'unique' => true),
							array('v' => '8.0', 'name' => 'issueid_targets', 'fields' => 'issueid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'channelid', 'reftable' => 'smart_channels', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'issueid', 'reftable' => 'smart_issues', 'reffield' => 'id'),
						),
				),

			array ('v' => '6.1', 'name' => 'smart_publishhistory',
						'comment' => 'Contains the publish history of dossiers for different targets.',
						'fields' => array(
							// Identification
						  	array('v' => '6.1', 'name' => 'id',                     'type' => 'int(11)',       'autoincrement' => true),
							array('v' => '6.1', 'name' => 'externalid',             'type' => 'varchar(200)',  'default' => "''",
								'comment' => 'External id for the published dossier at integrated publish system. Can be empty.' ),
							array('v' => '6.1', 'name' => 'objectid',               'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise object id of published dossier.'),

							// Publish target
							array('v' => '6.1', 'name' => 'channelid',              'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise publication channel id.'),
							array('v' => '6.1', 'name' => 'issueid',                'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise issue id. Zero (0) means "for the whole channel", like SMS.'),
							array('v' => '7.5', 'name' => 'editionid',              'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise edition id. Zero (0) means "all editions" or not applicable.'),

						    // Core data storage
							array('v' => '6.1', 'name' => 'publisheddate',	'type' => 'varchar(30)', 	'default' => "''",
								'comment' => 'Date/time of publising date' ),
							
							// Integration specific data storage
							array('v' => '7.5', 'name' => 'fields',                 'type' => 'blob',          'default' => "''",
								'comment' => 'PHP serialized array. Integration connector specific publishing fields.' ),
						    array('v' => '7.5', 'name' => 'fieldsmajorversion',     'type' => 'mediumint(9)',  'default' => "'0'",
						  		'comment' => 'Format version of the fields. Holds Ent Server version that created the record.'.
							  		'Server plug-in connector (publishing integration) is responsible for backward compatibility.'),
						    array('v' => '7.5', 'name' => 'fieldsminorversion',     'type' => 'mediumint(9)',  'default' => "'0'",
						  		'comment' => 'To be combined with fieldsmajorversion. Together it represents a major.minor version.' ),

							// Tracking
							array('v' => '6.1', 'name' => 'actiondate',             'type' => 'varchar(30)',   'default' => "''",
								'comment' => 'Date/time stamp of the publish action.' ),
							array('v' => '6.1', 'name' => 'action',                 'type' => 'varchar(20)',   'default' => "''",
								'comment' => 'Publish action; publishDossier, updateDossier, unpublishDossier.' ),
							array('v' => '9.2', 'name' => 'user',                 	'type' => 'varchar(255)',  'default' => "''",
								'comment' => 'Enterprise user (fullname) who did the publishing operation.' ),
						),
						'indexes' => array(
							array('v' => '6.1', 'name' => 'obchis_publhist', 'fields' => 'objectid, channelid, issueid'),
							array('v' => '7.6', 'name' => 'chis_publhist', 'fields' => 'channelid, issueid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.1', 'field' => 'objectid',  'reftable' => 'smart_objects',  'reffield' => 'id'),
							array('v' => '6.1', 'field' => 'channelid', 'reftable' => 'smart_channels', 'reffield' => 'id'),
							array('v' => '6.1', 'field' => 'issueid',   'reftable' => 'smart_issues',   'reffield' => 'id'),
							array('v' => '7.5', 'field' => 'editionid', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
				),

			array ('v' => '7.5', 'name' => 'smart_pubpublishedissues',
						'comment' => 'Contains the info of a published issue/edition (device) combination.',
						'fields' => array(
						
							// Identification
						  	array('v' => '7.5', 'name' => 'id',                     'type' => 'int(11)',       'autoincrement' => true),
							array('v' => '7.5', 'name' => 'externalid',             'type' => 'varchar(200)',  'default' => "''",
								'comment' => 'External id for the published issue at integrated publish system. Can be empty.' ),

							// Publish target
							array('v' => '7.5', 'name' => 'channelid',              'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise publication channel id.'),
							array('v' => '7.5', 'name' => 'issueid',                'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise issue id. Zero (0) means "for the whole channel", like SMS.'),
							array('v' => '7.5', 'name' => 'editionid',              'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise edition id. Zero (0) means "all editions" or not applicable.'),

						    // Core data storage
							array('v' => '7.5', 'name' => 'report',                 'type' => 'blob',          'default' => "''",
								'comment' => 'PHP serialized array. Contains publish Report of the publishing operation.' ),
							array('v' => '7.5', 'name' => 'dossierorder',           'type' => 'blob',          'default' => "''",
								'comment' => 'PHP serialized array. Contains comma separated dossier ids in -published- order. '.
									'Can be overruled/implemented by server plug-in (publishing integration), so format can differ. '.
									'Note that the -production- order is something else, which is stored at the issue itself.' ),
							array('v' => '7.5', 'name' => 'publishdate',            'type' => 'varchar(30)',    'default' => "''",
								'comment' => 'Date/time when the issue was published.' ),
						    array('v' => '7.5', 'name' => 'issuemajorversion',      'type' => 'mediumint(9)',  'default' => "'0'",
						  		'comment' => 'The version of the issue when published. This gets increased each publishing operation.'.
							  		'When content is updated, major is increased. Fields or ordering updates are minor. The version is '.
							  		'used to recognize if client has latest version. If not, to sync first before sending an update.' ),
						    array('v' => '7.5', 'name' => 'issueminorversion',      'type' => 'mediumint(9)',  'default' => "'0'",
						  		'comment' => 'To be combined with issuemajorversion. Together it represents a major.minor version.' ),

							// Integration specific data storage
							array('v' => '7.5', 'name' => 'fields',                 'type' => 'blob',          'default' => "''",
								'comment' => 'PHP serialized array. Integration connector specific publishing fields.' ),
						    array('v' => '7.5', 'name' => 'fieldsmajorversion',     'type' => 'mediumint(9)',  'default' => "'0'",
						  		'comment' => 'Format version of the fields. Holds Ent Server version that created the record.'.
							  		'Server plug-in connector (publishing integration) is responsible for backward compatibility.'),
						    array('v' => '7.5', 'name' => 'fieldsminorversion',     'type' => 'mediumint(9)',  'default' => "'0'",
						  		'comment' => 'To be combined with fieldsmajorversion. Together it represents a major.minor version.' ),

							// Tracking
							array('v' => '7.5', 'name' => 'actiondate',             'type' => 'varchar(30)',   'default' => "''",
								'comment' => 'Date/time stamp of the publish action.' ),
							array('v' => '7.5', 'name' => 'action',                 'type' => 'varchar(20)',   'default' => "''",
								'comment' => 'Publish action; publishIssue, updateIssue or unpublishIssue.' ),
							array('v' => '7.5', 'name' => 'userid',                 'type' => 'int(11)',       'default' => "'0'",
								'comment' => 'Enterprise user (id) who did the publishing operation.' ),
						),
						'indexes' => array(
							array('v' => '7.5', 'name' => 'chised_publhist', 'fields' => 'channelid, issueid, editionid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '7.5', 'field' => 'channelid', 'reftable' => 'smart_channels', 'reffield' => 'id'),
							array('v' => '7.5', 'field' => 'issueid',   'reftable' => 'smart_issues',   'reffield' => 'id'),
							array('v' => '7.5', 'field' => 'userid',    'reftable' => 'smart_users',    'reffield' => 'id'),
							array('v' => '7.5', 'field' => 'editionid', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
				),
				
			array ('v' => '6.1', 'name' => 'smart_publishedobjectshist',
						'comment' => 'History of objects once published in a content management system.',
						'fields' => array(
						  	array('v' => '6.1', 'name' => 'id',			'type' => 'int(11)', 		'autoincrement' => true),
							array('v' => '6.1', 'name' => 'objectid', 	'type' => 'int(11)', 'default' => "'0'",
								'comment' => 'Enterprise object id of published dossier or objects within dossier'),
							array('v' => '6.1', 'name' => 'publishid', 	'type' => 'int(11)', 'default' => "'0'",
								'comment' => 'Enterprise publish id'),
						  	array('v' => '6.1', 'name' => 'majorversion',		'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Major object version of object. See also minor version' ),
						  	array('v' => '6.1', 'name' => 'minorversion',		'type' => 'mediumint(9)',		'default' => "'0'",
						  		'comment' => 'Minor object version of object. See also majorversion field.' ),
							array('v' => '6.1', 'name' => 'externalid',	'type' => 'varchar(200)', 		'default' => "''",
								'comment' => 'External id for the published object, can be empty' ),
							array('v' => '9.2', 'name' => 'objectname',	'type' => 'varchar(255)', 		'default' => "''",
								'comment' => 'Enterprise object name of published dossier or objects within dossier' ),
							array('v' => '9.2', 'name' => 'objecttype',	'type' => 'varchar(40)', 		'default' => "''",
								'comment' => 'Enterprise object type of published dossier or objects within dossier' ),
							array('v' => '9.2', 'name' => 'objectformat',	'type' => 'varchar(128)', 	'default' => "''",
								'comment' => 'Enterprise object format of published dossier or objects within dossier' ),

						),
						'indexes' => array(
							array('v' => '6.1', 'name' => 'obpu_publobjhist', 'fields' => 'objectid, publishid'),
							array('v' => '6.1', 'name' => 'puob_publobjhist', 'fields' => 'publishid, objectid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.1', 'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '6.1', 'field' => 'publishid', 'reftable' => 'smart_publishhistory', 'reffield' => 'id'),
							array('v' => '6.1', 'field' => 'majorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'majorversion'),
							array('v' => '6.1', 'field' => 'minorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'minorversion'),
						),
				),

				array ('v' => '10.1', 'name' => 'smart_publishedplcmtshist',
						'comment' => 'History of object relational placements that are published to a content management system (CMS). '.
							'For example an image placed on a Publish Form for which a crop is made during the publish operation.',
						'fields' => array(
						  	array('v' => '10.1', 'name' => 'id',			'type' => 'int(11)',		 'autoincrement' => true),
							array('v' => '10.1', 'name' => 'objectid', 		'type' => 'int(11)', 		'default' => "'0'",
								'comment' => 'The id of the Enterprise object of which the native file is used for publishing.'),
							array('v' => '10.1', 'name' => 'publishid', 	'type' => 'int(11)', 		'default' => "'0'",
								'comment' => 'Foreign key that refers to the publish operation of the dossier / publish form.'),
						  	array('v' => '10.1', 'name' => 'majorversion',	'type' => 'mediumint(9)',	'default' => "'0'",
						  		'comment' => 'Major object version of the object. Coexists with the minorversion field.' ),
						  	array('v' => '10.1', 'name' => 'minorversion',	'type' => 'mediumint(9)',	'default' => "'0'",
						  		'comment' => 'Minor object version of the object. Coexists with the majorversion field.' ),
							array('v' => '10.1', 'name' => 'externalid',	'type' => 'varchar(200)', 	'default' => "''",
								'comment' => 'Id retrieved from the CMS that refers to the published file.' ),
							array('v' => '10.1', 'name' => 'placementhash',	'type' => 'varchar(64)', 'default' => "",
								'comment' => 'SHA256 hash of placement information which can be used for versioning and identification. '.
									'This information is used to determine whether or not the placement has changed since the previous publish operation. '.
									'When there is no matching record in this table found, the placement was changed or never published before. '.
									'In both cases it needs to be re-published to reflect changes at the CMS. E.g. in case of image cropping '.
									'the crop geometry, scale, form widget id, output mime type and DPI are hashed. The object id and version are excluded. ' ),
						),
						'indexes' => array(
							array('v' => '10.1', 'name' => 'obpu_publplchist', 'fields' => 'objectid, publishid'),
							array('v' => '10.1', 'name' => 'puob_publplchist', 'fields' => 'publishid, objectid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '10.1', 'field' => 'objectid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '10.1', 'field' => 'publishid', 'reftable' => 'smart_publishhistory', 'reffield' => 'id'),
							array('v' => '10.1', 'field' => 'majorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'majorversion'),
							array('v' => '10.1', 'field' => 'minorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'minorversion'),
						),
				),

				array ('v' => '6.0', 'name' => 'smart_targeteditions',
						'comment' => 'Keeps a relation between channel/issue and editions.',
						'fields' => array(
						  	array('v' => '6.1', 'name' => 'id', 'type' => 'int(11)', 'autoincrement' => true,
						  			'alters' => array('v' => '6.0', 'name' => 'id', 'type' => 'mediumint(9)', 'autoincrement' => true), ),
							array('v' => '6.1', 'name' => 'targetid', 'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'targetid', 'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.1', 'name' => 'editionid', 'type' => 'int(11)', 'default' => "'0'",
									'alters' => array('v' => '6.0', 'name' => 'editionid', 'type' => 'mediumint(9)', 'default' => "'0'"), ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'taed_targeteditions', 'fields' => 'targetid, editionid', 'unique' => true),
							array('v' => '6.0', 'name' => 'edta_targeteditions', 'fields' => 'editionid, targetid', 'unique' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'targetid', 'reftable' => 'smart_targets', 'reffield' => 'id'),
							array('v' => '6.0', 'field' => 'editionid', 'reftable' => 'smart_editions', 'reffield' => 'id'),
						),
				),

				array ('v' => '6.0', 'name' => 'smart_indesignservers',
						'comment' => 'Configuration of InDesign Servers.',
						'fields' => array(
						  	array('v' => '6.1', 'name' => 'id',	'type' => 'int(11)', 'autoincrement' => true,
						  			'alters' => array('v' => '6.0', 'name' => 'id',	'type' => 'mediumint(9)', 'autoincrement' => true), ),
							array('v' => '6.0', 'name' => 'hostname', 'type' => 'varchar(64)', 'default' => "''"),
							array('v' => '6.0', 'name' => 'portnumber', 'type' => 'mediumint(9)', 'default' => "'0'"),
							array('v' => '8.0', 'name' => 'description', 'type' => 'varchar(255)', 'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'description', 'type' => 'varchar(255)', 'default' => "", 'nullable' => true)),
							array('v' => '8.0', 'name' => 'active', 'type' => 'char(2)', 'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'active', 'type' => 'char(2)', 'default' => "", 'nullable' => true)),
						    array('v' => '7.5', 'name' => 'servermajorversion', 'type' => 'mediumint(9)', 'default' => "'5'",
						    	'alters' => array('v' => '7.0', 'name' => 'serverversion', 'type' => 'mediumint(9)', 'default' => "'5'"),
						  		'comment' => 'Internal InDesign Server major version; 8=CS6, 10=CC2014, etc' ),
						    array('v' => '7.5', 'name' => 'serverminorversion', 'type' => 'mediumint(9)', 'default' => "'0'",
						  		'comment' => 'Internal InDesign Server minor version; See also servermajorversion field.' ),
							array('v' => '9.6', 'name' => 'prio1', 'type' => 'char(2)', 'default' => "'on'",
								'comment' => 'Internal InDesign Server Prio 1' ),
							array('v' => '9.6', 'name' => 'prio2', 'type' => 'char(2)', 'default' => "'on'",
								'comment' => 'Internal InDesign Server Prio 2' ),
							array('v' => '9.6', 'name' => 'prio3', 'type' => 'char(2)', 'default' => "'on'",
								'comment' => 'Internal InDesign Server Prio 3' ),
							array('v' => '9.6', 'name' => 'prio4', 'type' => 'char(2)', 'default' => "'on'",
								'comment' => 'Internal InDesign Server Prio 4' ),
							array('v' => '9.6', 'name' => 'prio5', 'type' => 'char(2)', 'default' => "'on'",
								'comment' => 'Internal InDesign Server Prio 5' ),
							array('v' => '9.7', 'name' => 'locktoken', 'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'GUID generated by a PHP process to assure it took the lock to pick the IDS instance (blocking other processes accidentally picking the same IDS instance at the same time).' ),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'hopo_indesignservers', 'fields' => 'hostname, portnumber', 'unique' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
						),
				),

				array ('v' => '6.0', 'name' => 'smart_indesignserverjobs',
						'comment' => 'Overview of InDesign Server jobs and their status.',
						'fields' => array(
							// Job definition:
							array( 'v' => '9.7', 'name' => 'id', 'drops' =>
							  	array('v' => '6.1', 'name' => 'id', 'type' => 'int(11)', 		'autoincrement' => true,
							  			'alters' => array('v' => '6.0', 'name' => 'id', 'type' => 'mediumint(9)', 		'autoincrement' => true)),
						        'comment' => 'Used to identify the job record. Use jobid instead.'),
							array('v' => '9.7', 'name' => 'jobid', 'type' => 'varchar(40)', 		'default' => "''",
								'comment' => 'Unique identifier (GUID) of the job record.' ),
							array('v' => '6.0', 'name' => 'foreground', 		'type' => 'char(2)', 		'default' => "''",
								'comment' => 'Whether or not end-users are waiting for the job to complete. Background jobs are offloaded from the workflow.' ),
							array('v' => '8.0', 'name' => 'objid', 'type' => 'int(11)', 'default' => "0",
									'alters' => array('v' => '6.1', 'name' => 'objid', 'type' => 'int(11)', 'default' => "", 'nullable' => true,
									'alters' => array('v' => '6.0', 'name' => 'objid', 'type' => 'mediumint(255)', 'default' => "", 'nullable' => true)),
								'comment' => 'The object id of the Layout or Layout Module that to be processed by the job.'),
							array( 'v' => '9.7', 'name' => 'exclusivelock', 'drops' =>
								array('v' => '8.0', 'name' => 'exclusivelock', 		'type' => 'char(2)', 		'default' => "''",
										'alters' => array('v' => '6.0', 'name' => 'exclusivelock', 		'type' => 'char(2)', 		'default' => "", 'nullable' => true))),
						    array('v' => '9.7', 'name' => 'objectmajorversion',	'type' => 'mediumint(9)', 'default' => "'0'", 
						  		'comment' => 'Major object version of Layout or Layout Module being processed. Populated once the job is picked up from the queue.' ),
						    array('v' => '9.7', 'name' => 'objectminorversion',	'type' => 'mediumint(9)', 'default' => "'0'", 
						  		'comment' => 'Minor object version of Layout or Layout Module being processed. Populated once the job is picked up from the queue.' ),
							array('v' => '6.0', 'name' => 'jobtype', 'type' => 'varchar(32)', 'default' => "",
								'comment' => 'Unique name of this kind of job. Used to configure priorities (JobPrio).' ),
							array('v' => '6.0', 'name' => 'jobscript', 'type' => 'blob', 'default' => "",
								'comment' => 'JavaScript module to be executed in SC for IDS.' ),
							array('v' => '8.0', 'name' => 'jobparams', 'type' => 'blob', 'default' => "''",
									'alters' => array('v' => '6.0', 'name' => 'jobparams', 'type' => 'blob', 'default' => "", 'nullable' => true),
								'comment' => 'Parameters to be passed on to the JavaScript module (JobScript) to execute the script.' ),
							
							// Job execution:
							array('v' => '9.7', 'name' => 'locktoken', 'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'GUID generated by a PHP process to assure it took the lock to execute the job (blocking other processes accidentally running the same job at the same time).' ),
							array('v' => '6.0', 'name' => 'queuetime', 'type' => 'varchar(20)', 'default' => "''",
								'comment' => 'Time when the job was pushed into the queue. Format: yyyy-mm-ddThh:mm:ss'),
							array('v' => '8.0', 'name' => 'starttime', 'type' => 'varchar(30)', 'default' => "''",
								'alters' => array('v' => '6.0', 'name' => 'starttime', 'type' => 'varchar(20)', 'default' => "", 'nullable' => true),
								'comment' => 'Time when the job was picked from the queue. Format: yyyy-mm-ddThh:mm:ss' ),
							array('v' => '8.0', 'name' => 'readytime', 'type' => 'varchar(20)', 'default' => "''",
								'alters' => array('v' => '6.0', 'name' => 'readytime', 'type' => 'varchar(20)', 'default' => "", 'nullable' => true),
								'comment' => 'Time when the job execution was completed. Format: yyyy-mm-ddThh:mm:ss' ),
							array('v' => '8.0', 'name' => 'errorcode', 'type' => 'varchar(32)', 'default' => "''",
								'alters' => array('v' => '6.0', 'name' => 'errorcode', 'type' => 'varchar(32)', 'default' => "", 'nullable' => true),
								'comment' => 'The error returned by the JavaScript module. Zero when no error. Could represent internal SC error code.' ),
							array('v' => '9.7', 'name' => 'errormessage', 'type' => 'varchar(1024)', 'default' => "''",
								'alters' => array('v' => '8.0', 'name' => 'errormessage', 'type' => 'varchar(255)', 'default' => "''",
									'alters' => array('v' => '6.0', 'name' => 'errormessage', 'type' => 'varchar(255)', 'default' => "", 'nullable' => true)),
								'comment' => 'Error message raised by the JavaScript module (or by Enterprise Server in context of job execution).' ),
							array('v' => '8.0', 'name' => 'scriptresult', 'type' => 'blob', 'default' => "''",
								'alters' => array('v' => '6.0', 'name' => 'scriptresult', 'type' => 'blob', 'default' => "", 'nullable' => true),
								'comment' => 'Output of the JavaScript module execution. Used for debugging. Typically contains logging and profiling information.' ),
							array('v' => '9.7', 'name' => 'jobstatus',    'type' => 'int(11)',     'default' => 0,
								'comment' => 'Current status of the job. Combined field; The values of jobcondition and jobprogress are added. See InDesignServerJobStatus.class.php for details.' ),
							array('v' => '9.7', 'name' => 'jobcondition', 'type' => 'int(11)',     'default' => 0,
								'comment' => 'Healthness of the job execution; 4096=Info, 8192=Warning, 16384=Error.' ),
							array('v' => '9.7', 'name' => 'jobprogress',  'type' => 'int(11)',     'default' => 0,
								'comment' => 'Progress of the job execution; Todo=256, Busy=512, Done=1024.' ),
							array('v' => '9.7', 'name' => 'attempts',     'type' => 'int(11)',     'default' => 0,
								'comment' => 'When job pushed into the queue, value is set to zero. After job execution this value is incremented by one.' ),
							array('v' => '10.2', 'name' => 'pickuptime', 'type' => 'varchar(30)', 'default' => "''", 'patched' => array('10.1' => 'idsautomationpickuptime'),
								'comment' => 'Time from which the job runnner should pickup the job.' ),
							
							// ID Server selection:
							array('v' => '8.0', 'name' => 'assignedserverid', 'type' => 'int(9)', 'default' => 0,
								'alters' => array('v' => '6.1', 'name' => 'assignedserverid', 'type' => 'int(9)', 'default' => "", 'nullable' => true,
									'alters' => array('v' => '6.0', 'name' => 'assignedserverid', 'type' => 'mediumint(9)', 'default' => "", 'nullable' => true)),
								'comment' => 'The InDesign Server instance that is picked to handle the job. When job is retried, it represents the last picked one.' ),
						    array('v' => '9.5', 'name' => 'minservermajorversion',	'type' => 'mediumint(9)', 'default' => "'0'", 'patched' => array('9.4' => 'ids2014ccsupport'),
						    	'alters' => array('v' => '7.5', 'name' => 'servermajorversion',	'type' => 'mediumint(9)', 'default' => "'5'",
						    		'alters' => array('v' => '7.0', 'name' => 'serverversion',	'type' => 'mediumint(9)', 'default' => "'0'")),
						  		'comment' => 'Minimum required internal major version of IDS that is suitable to execute the job. 8=CS6, 10=CC2014, etc' ),
						    array('v' => '9.5', 'name' => 'minserverminorversion',	'type' => 'mediumint(9)', 'default' => "'0'", 'patched' => array('9.4' => 'ids2014ccsupport'),
						    	'alters' => array('v' => '7.5', 'name' => 'serverminorversion',	'type' => 'mediumint(9)', 'default' => "'0'"),
						  		'comment' => 'Minimum required internal minor version of IDS that is suitable to execute the job; See also minservermajorversion field.' ),
						    array('v' => '9.5', 'name' => 'maxservermajorversion',	'type' => 'mediumint(9)', 'default' => "'0'", 'patched' => array('9.4' => 'ids2014ccsupport'),
						  		'comment' => 'Maximum required internal major version of IDS that is suitable to execute the job. 8=CS6, 10=CC2014, etc' ),
						    array('v' => '9.5', 'name' => 'maxserverminorversion',	'type' => 'mediumint(9)', 'default' => "'0'", 'patched' => array('9.4' => 'ids2014ccsupport'),
						  		'comment' => 'Maximum required internal minor version of IDS that is suitable to execute the job; See also maxservermajorversion field.' ),
							array('v' => '9.6', 'name' => 'prio',	'type' => 'mediumint(1)', 'default' => "'3'",
								'comment' => 'Priority used to select which InDesign Server instance should pick it up.' ),
							
							// Job session context:
							array('v' => '9.7', 'name' => 'ticketseal',   'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'Ticket that can be used by the job script to logon without user name and password.' ),
							array('v' => '9.7', 'name' => 'ticket',   'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'Ticket of the job itself. Set once the job script has logged in to Enterprise.' ),
							array('v' => '9.7', 'name' => 'actinguser',   'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'Short name of user who has logged in through the IDS job.' ),
							array('v' => '9.7', 'name' => 'initiator',   'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'Short name of user who has pushed the IDS job in the queue.' ),
							array('v' => '9.7', 'name' => 'servicename', 'type' => 'varchar(32)', 'default' => "''",
								'comment' => 'Name of web service in which context the IDS job was pushed into the queue.' ),
							array('v' => '9.7', 'name' => 'context', 'type' => 'varchar(64)', 'default' => "''",
								'comment' => 'Additional information in which context the IDS job was pushed into the queue. Free format, depends per job type.' ),
						),
						'indexes' => array(
							array('v' => '9.7', 'name' => 'primary', 'fields' => 'jobid', 'primary' => true, 'unique' => true,
								'alters' => array( 'v' => '8.0', 'name' => 'primary', 'fields' => 'id', 'primary' => true )
							),
							array('v' => '6.0', 'name' => 'asre_indesignserverjobs', 'fields' => 'assignedserverid, readytime', 'unique' => false),
							array('v' => '6.0', 'name' => 'qt_indesignserverjobs', 'fields' => 'queuetime', 'unique' => false),
							array('v' => '9.2', 'name' => 'objid_indesignserverjobs', 'fields' => 'objid', 'unique' => false),
							array('v' => '9.7', 'name' => 'prid_indesignserverjobs', 'fields' => 'prio, jobid', 'unique' => false,
								'alters' => array('v' => '9.6', 'name' => 'prid_indesignserverjobs', 'fields' => 'prio, id', 'unique' => false)
							),
							array('v' => '9.7', 'name' => 'ts_indesignserverjobs', 'fields' => 'ticketseal', 'unique' => false),
							array('v' => '9.7', 'name' => 'ttjtstrt_indesignserverjobs', 'fields' => 'ticket, jobtype, starttime, readytime', 'unique' => false),
							array('v' => '9.7', 'name' => 'jp_indesignserverjobs', 'fields' => 'jobprogress', 'unique' => false),
							array('v' => '9.7', 'name' => 'jspr_indesignserverjobs', 'fields' => 'jobstatus, prio, queuetime', 'unique' => false),
							array('v' => '9.8', 'name' => 'lt_indesignserverjobs', 'fields' => 'locktoken', 'unique' => false),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '9.7', 'field' => 'objectmajorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'majorversion'),
							array('v' => '9.7', 'field' => 'objectminorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'minorversion'),
							array('v' => '6.0', 'field' => 'assignedserverid', 'reftable' => 'smart_indesignservers', 'reffield' => 'id'),
						),
				),

				array ('v' => '8.0', 'name' => 'smart_servers',
						'comment' => 'Configuration of Enterprise Servers.',
						'fields' => array(
						  	array('v' => '8.0', 'name' => 'id',	'type' => 'int(11)', 'autoincrement' => true),
							array('v' => '8.0', 'name' => 'name', 'type' => 'varchar(64)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'type', 'type' => 'varchar(32)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'url', 'type' => 'varchar(1024)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'description', 'type' => 'varchar(255)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'jobsupport', 'type' => 'char(1)', 'default' => "''",
								'comment' => 'What Server Job types it handles. A=All, N=None, S=Specified.' ),
						),
						'indexes' => array(
							array('v' => '8.0', 'name' => 'hopo_servers', 'fields' => 'name', 'unique' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
						),
				),

				array ('v' => '8.0', 'name' => 'smart_serverjobs',
						'comment' => 'Overview of Server Jobs and their status.',
						'fields' => array(
							// >>> DB fields taken from IDS jobs => TODO: reconsider which ones to embrace
							array( 'v' => '9.4', 'name' => 'id', 'drops' =>
								array( 'v' => '8.0', 'name' => 'id', 'type' => 'int(11)', 		'autoincrement' => true,
						        'comment' => 'Used to identify the job record. Use jobid instead.')),
							array('v' => '9.4', 'name' => 'jobid', 'type' => 'varchar(40)', 		'default' => "''",
								'comment' => 'Unique identifier (GUID) of the job record.' ),
							//array('v' => '8.0', 'name' => 'foreground', 		'type' => 'char(2)', 		'default' => "''"),
							array('v' => '9.4', 'name' => 'attempts', 'type' => 'int(11)', 		'default' => 0,
								'comment' => 'When job pushed into the queue, value is set to zero. After calling ServerJob_EnterpriseConnector->runJob(), value is incremented by one.' ),
							array('v' => '9.4', 'name' => 'queuetime', 'type' => 'varchar(30)', 'default' => "''",
								'comment' => 'Time when the job was pushed into the queue. Unlike other datetimes, this '.
								'one has an accuracy of microseconds (6 digits). The datetime is stored in the following '.
								'format: yyyy-mm-ddThh:mm:ss.ssssss.',
							   'alters' => array('v' => '8.0', 'name' => 'queuetime', 'type' => 'varchar(20)', 'default' => "''")),
							array( 'v' => '9.4', 'name' => 'objid', 'drops' =>
								array( 'v' => '8.0', 'name' => 'objid', 'type' => 'int(11)', 'default' => 0 )),
							array('v' => '8.0', 'name' => 'servicename', 'type' => 'varchar(32)', 'default' => "''" ),
							array('v' => '8.0', 'name' => 'context', 'type' => 'varchar(32)', 'default' => "''"),
							array( 'v' => '9.4', 'name' => 'minorversion', 'drops' =>
								array( 'v' => '8.0', 'name' => 'minorversion',	'type' => 'mediumint(9)',	'default' => 0,
					  			'comment' => 'Minor object version at the time this job was created for it.' )),
							array( 'v' => '9.4', 'name' => 'majorversion', 'drops' =>
								array( 'v' => '8.0', 'name' => 'majorversion',	'type' => 'mediumint(9)',	'default' => 0,
					  			'comment' => 'Major object version at the time this job was created for it.' )),
							//array('v' => '8.0', 'name' => 'exclusivelock', 		'type' => 'char(2)', 		'default' => ""),
							array('v' => '8.0', 'name' => 'servertype', 'type' => 'varchar(32)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'jobtype', 'type' => 'varchar(32)', 'default' => "''"),
							//array('v' => '8.0', 'name' => 'jobscript', 'type' => 'blob', 'default' => ""),
							//array('v' => '8.0', 'name' => 'jobparams', 'type' => 'blob', 'default' => ""),
							array('v' => '8.0', 'name' => 'assignedserverid', 'type' => 'int(11)', 'default' => 0 ),
							array('v' => '8.0', 'name' => 'starttime', 'type' => 'varchar(30)', 'default' => "'0000-00-00T00:00:00'" ),
							array('v' => '8.0', 'name' => 'readytime', 'type' => 'varchar(30)', 'default' => "'0000-00-00T00:00:00'" ),
							//array('v' => '8.0', 'name' => 'errorcode', 'type' => 'varchar(32)', 'default' => ""),
							array('v' => '9.6', 'name' => 'errormessage', 'type' => 'varchar(1024)', 'default' => "''"),
							//array('v' => '8.0', 'name' => 'scriptresult', 'type' => 'blob', 'default' => ""),
						    //array('v' => '8.0', 'name' => 'serverversion',	'type' => 'mediumint(9)', 'default' => "'5'",
						  	//	'comment' => 'Preferred InDesign Server version to run the job. 5=CS3, 6=CS4, etc' ),
							// <<<
						  	
							array('v' => '8.0', 'name' => 'locktoken',    'type' => 'varchar(40)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'ticketseal',   'type' => 'varchar(40)', 'default' => "''"),
							array('v' => '8.0', 'name' => 'actinguser',   'type' => 'varchar(40)', 'default' => "''"),

							array('v' => '8.0', 'name' => 'jobstatus',    'type' => 'int(11)',     'default' => 0),
							array('v' => '8.0', 'name' => 'jobcondition', 'type' => 'int(11)',     'default' => 0),
							array('v' => '8.0', 'name' => 'jobprogress',  'type' => 'int(11)',     'default' => 0),
							array( 'v' => '9.4', 'name' => 'jobdata', 'type' => 'mediumblob', 'default' => "",
								'comment' => 'Any data, depending on the job type.' ),
							array('v' => '9.4', 'name' => 'dataentity',  'type' => 'varchar(20)',     'default' => "''",
								'comment' => 'possible values:objectid,issueid,object,issue' ),
						),
						'indexes' => array(
							array('v' => '9.4', 'name' => 'primary', 'fields' => 'jobid', 'primary' => true, 'unique' => true,
								'alters' => array( 'v' => '8.0', 'name' => 'primary', 'fields' => 'id', 'primary' => true, )
							),
							//array('v' => '8.0', 'name' => 'asre_serverjobs', 'fields' => 'assignedserverid, readytime', 'unique' => false),
							array('v' => '8.0', 'name' => 'qt_serverjobs', 'fields' => 'queuetime', 'unique' => false),
							array('v' => '9.4', 'name' => 'jobinfo', 'fields' => 'locktoken, jobstatus, jobprogress'),
							array('v' => '9.4', 'name' => 'aslt_serverjobs', 'fields' => 'assignedserverid, locktoken'),
						    array('v' => '9.4', 'name' => 'paged_results', 'fields' => 'queuetime, servertype, jobtype, jobstatus, actinguser')
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '8.0', 'field' => 'jobtype', 'reftable' => 'smart_serverjobconfigs', 'reffield' => 'jobtype'),
							array('v' => '8.0', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
							array('v' => '8.0', 'field' => 'majorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'majorversion'),
							array('v' => '8.0', 'field' => 'minorversion', 'reftable' => 'smart_objectversions', 'reffield' => 'minorversion'),
							array('v' => '8.0', 'field' => 'assignedserverid', 'reftable' => 'smart_servers', 'reffield' => 'id'),
						),
				),

				array ('v' => '9.4', 'name' => 'smart_serverjobtypesonhold',
					'comment' => 'To remember which Job Type(s) are put on hold. (Jobs wont be picked up for processing.)',
					'fields' => array(
						array('v' => '9.4', 'name' => 'guid', 'type' => 'varchar(40)', 'default' => "''",
							'comment' => 'Identifier of the record.' ),
						array('v' => '9.4', 'name' => 'jobtype', 'type' => 'varchar(32)', 'default' => "''",
							'comment' => 'Which server job type is entirely put on hold for a while.' ),
						array('v' => '9.4', 'name' => 'retrytimestamp', 'type' => 'varchar(20)', 'default' => "''",
							'comment' => 'When to start retrying this type of server job again. Calculated by core: now() + replanJobType().' ),
					),
					'indexes' => array(
						array('v' => '9.4', 'name' => 'primary', 'fields' => 'guid', 'primary' => true, 'unique' => true ),
						array('v' => '9.4', 'name' => 'jobtype', 'fields' => 'jobtype' ),
						array('v' => '9.4', 'name' => 'retrytime', 'fields' => 'retrytimestamp' ), // used for cleanup old records ( retrytimestamp > now() ).
					),
					'inserts' => array(
					),
					'lookups' => array(
						array('v' => '9.4', 'field' => 'jobtype', 'reftable' => 'smart_serverjobs', 'reffield' => 'jobtype'),
					),
				),

				array ('v' => '8.0', 'name' => 'smart_serverjobconfigs',
						'comment' => 'Configurations of how Server Jobs of a certain type should operate.',
						'fields' => array(
						  	array('v' => '8.0', 'name' => 'id',	'type' => 'int(11)', 'autoincrement' => true),
							array('v' => '8.0', 'name' => 'jobtype', 'type' => 'varchar(32)', 'default' => "''",
								'comment' => 'Unique name of the job.' ),
							array('v' => '8.0', 'name' => 'servertype', 'type' => 'varchar(32)', 'default' => "''",
								'comment' => 'The kind of server to be taken to run the job. Supported: Enterprise.' ),
							array('v' => '8.0', 'name' => 'attempts', 'type' => 'mediumint(9)', 'default' => 0,
								'comment' => 'How many failures (on job runs) befor giving up.' ),
							array('v' => '8.0', 'name' => 'active', 'type' => 'char(1)', 'default' => "'N'",
								'comment' => 'Whether or not jobs (of this type) are processed, Y/N' ),
							array('v' => '8.0', 'name' => 'sysadmin', 'type' => 'char(1)', 'default' => "'-'",
								'comment' => 'Whether or not the job requires system admin rights, Y/N/-. '.
											 'Y = system admin user needs to be configured for this job type at the userid field. N = same, but non-admin user. - = no specification; take acting user.' ),
							array('v' => '8.0', 'name' => 'userid', 'type' => 'int(11)', 'default' => "0",
								'comment' => 'When zero (by default), the acting user (initiating the job) is used to determine access rights while running the job. '.
											 'When non-zero, that specified user is always used instead (fixed). Typically used for system admin jobs to gain more access.' ),
							array('v' => '9.4', 'name' => 'userconfigneeded', 'type' => 'char(1)', 'default' => "'Y'",
								'comment' => 'Whether or not the HealthCheck should raise error when userid is not assigned, Y/N. ' .
											 'When set to Y, HealthCheck will raise error when userid is empty.' ),
							array('v' => '8.0', 'name' => 'recurring', 'type' => 'char(1)', 'default' => "'N'",
								'comment' => 'Whether or not jobs are recurring, Y/N.' ),
							array( 'v' => '9.4', 'name' => 'selfdestructive', 'type' => 'char(1)', 'default' => "'N'",
								'comment' => 'Whether or not job should be removed from the queue once reach Completed status, Y/N.' ),

							// >>> For recurring jobs only...
							array('v' => '8.0', 'name' => 'workingdays', 'type' => 'char(1)', 'default' => "'N'",
								'comment' => 'Days a recurring job can run; working, non-working, all; Y/N/-.' ),
							array('v' => '8.0', 'name' => 'dailystarttime',	'type' => 'varchar(30)', 'default' => "'00-00-00T00:00:00'",
								'comment' => 'Recurring: When to start each day, yyyy-mm-ddThh:mm:ss.' ),
							array('v' => '8.0', 'name' => 'dailystoptime',	'type' => 'varchar(30)', 'default' => "'00-00-00T00:00:00'",
								'comment' => 'Recurring: When to stop each day, yyyy-mm-ddThh:mm:ss.' ),
							array('v' => '8.0', 'name' => 'timeinterval', 'type' => 'mediumint(9)', 'default' => 0,
								'comment' => 'Recurring: Minutes wait between start times (not between end-start). Zero when job is not recurring.' ),
							// <<<
						),
						'indexes' => array(
						),
						'inserts' => array(
						),
						'lookups' => array(
						),
				),

				array ('v' => '8.0', 'name' => 'smart_serverjobsupports',
						'comment' => 'Configuration of which server can handle which Server Job types.',
						'fields' => array(
						  	array('v' => '8.0', 'name' => 'id',	'type' => 'int(11)', 'autoincrement' => true),
							array('v' => '8.0', 'name' => 'serverid', 'type' => 'int(11)', 'default' => 0),
							array('v' => '8.0', 'name' => 'jobconfigid', 'type' => 'int(11)', 'default' => 0),
						),
						'indexes' => array(
							array('v' => '8.0', 'name' => 'sjs_serverconfigs', 'fields' => 'serverid, jobconfigid', 'unique' => true),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '8.0', 'field' => 'serverid', 'reftable' => 'smart_servers', 'reffield' => 'id'),
							array('v' => '8.0', 'field' => 'jobconfigid', 'reftable' => 'smart_serverjobconfigs', 'reffield' => 'id'),
						),
				),
				
				array ('v' => '6.0', 'name' => 'smart_serverplugins',
						'comment' => 'Plug-ins installed at the application server.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',          'type' => 'int(11)', 'autoincrement' => true,
									'alters' => array('v' => '6.0', 'name' => 'id',          'type' => 'mediumint(9)', 'autoincrement' => true), ),
							array('v' => '6.0', 'name' => 'uniquename',  'type' => 'varchar(64)',  'default' => "''"),
							array('v' => '6.0', 'name' => 'displayname', 'type' => 'varchar(128)', 'default' => "''"),
							array('v' => '6.1', 'name' => 'version',     'type' => 'varchar(64)',  'default' => "''",
								'alters' => array('v' => '6.0', 'name' => 'version',     'type' => 'varchar(10)',  'default' => "''"), ),
							array('v' => '8.0', 'name' => 'description', 'type' => 'varchar(255)', 'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'description', 'type' => 'varchar(255)', 'default' => "''", 'nullable' => true)),
							array('v' => '8.0', 'name' => 'copyright',   'type' => 'varchar(128)', 'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'copyright',   'type' => 'varchar(128)', 'default' => "''", 'nullable' => true)),
							array('v' => '8.0', 'name' => 'active',      'type' => 'char(2)',      'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'active',      'type' => 'char(2)',      'default' => "''", 'nullable' => true)),
							array('v' => '8.0', 'name' => 'system',      'type' => 'char(2)',      'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'system',      'type' => 'char(2)',      'default' => "''", 'nullable' => true)),
							array('v' => '8.0', 'name' => 'installed',   'type' => 'char(2)',      'default' => "''", 'nullable' => false,
								'alters' => array('v' => '6.0', 'name' => 'installed',   'type' => 'char(2)',      'default' => "''", 'nullable' => true)),
						    array('v' => '8.0', 'name' => 'modified',    'type' => 'varchar(30)',  'default' => "''", 'nullable' => false, 'comment' => 'yyyy-mm-ddThh:mm:ss',
						    		'alters' => array('v' => '6.0', 'name' => 'modified',    'type' => 'varchar(30)',  'default' => "NULL", 'nullable' => true, 'comment' => 'yyyy-mm-ddThh:mm:ss')),
						),
						'indexes' => array(
						),
						'inserts' => array(
							"1, 'PreviewMetaPHP', 'PHP Preview and Meta Data', 'v6.1', 'Using internal PHP libraries (such as GD) to generate previews and read metadata', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-10-02T09:00:00'",
							"2, 'ImageMagick', 'ImageMagick', 'v6.1', 'Use ImageMagick to support extra formats for preview generation', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', '', 'on', '', '2008-10-02T09:00:00'",
							"3, 'InCopyHTMLConversion', 'InCopy HTML Conversion', 'v6.1', 'Have InCopy and InDesign edit HTML articles by converting the article to text', '(c) 1998-2008 WoodWing Software bv. All rights reserved.', 'on', 'on', 'on', '2008-11-30T09:00:00'",
						),
						'lookups' => array(
						),
				),

				array ('v' => '6.0', 'name' => 'smart_serverconnectors',
						'comment' => 'Connectors installed at the application server. One plug-in can have many connectors.',
						'fields' => array(
							array('v' => '6.1', 'name' => 'id',	         'type' => 'int(11)', 'autoincrement' => true,
									'alters' => array('v' => '6.0', 'name' => 'id',	         'type' => 'mediumint(9)', 'autoincrement' => true), ),
						  	array('v' => '6.1', 'name' => 'pluginid',    'type' => 'int(11)', 'default' => "'0'",
						  			'alters' => array('v' => '6.0', 'name' => 'pluginid',    'type' => 'mediumint(9)', 'default' => "'0'"), ),
							array('v' => '6.0', 'name' => 'classname',   'type' => 'varchar(128)', 'default' => "''"),
							array('v' => '6.0', 'name' => 'interface',   'type' => 'varchar(128)', 'default' => "''"),
							array('v' => '6.0', 'name' => 'type',        'type' => 'varchar(32)',  'default' => "''"),
							array('v' => '6.0', 'name' => 'prio',        'type' => 'mediumint(9)', 'default' => "'0'"),
							array('v' => '6.0', 'name' => 'runmode',     'type' => 'varchar(16)',  'default' => "''"),
							array('v' => '6.0', 'name' => 'classfile',   'type' => 'varchar(255)', 'default' => "''"),
						    array('v' => '8.0', 'name' => 'modified',    'type' => 'varchar(30)',  'default' => "''", 'nullable' => false, 'comment' => 'yyyy-mm-ddThh:mm:ss',
						    	'alters' => array('v' => '6.0', 'name' => 'modified',    'type' => 'varchar(30)',  'default' => "NULL", 'nullable' => true, 'comment' => 'yyyy-mm-ddThh:mm:ss')),
						),
						'indexes' => array(
							array('v' => '6.0', 'name' => 'seco_pluginid', 'fields' => 'pluginid'),
							array('v' => '6.0', 'name' => 'seco_typeinterface', 'fields' => 'type, interface'),
						),
						'inserts' => array(
							"1, 1, 'PreviewMetaPHP_Preview', 'Preview', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_Preview.class.php', '2008-10-02T09:00:00'",
							"2, 1, 'PreviewMetaPHP_MetaData', 'MetaData', '', 500, 'Synchron', '/server/plugins/PreviewMetaPHP/PreviewMetaPHP_MetaData.class.php', '2008-10-02T09:00:00'",
							"3, 3, 'InCopyHTMLConversion_WflGetObjects', 'WflGetObjects', 'WorkflowService', 500, 'After', '/server/plugins/InCopyHTMLConversion/InCopyHTMLConversion_WflGetObjects.class.php', '2008-11-30T09:00:00'",
						),
						'lookups' => array(
							array('v' => '6.0', 'field' => 'pluginid', 'reftable' => 'smart_serverplugins', 'reffield' => 'id'),
						),
				),

				array ('v' =>'7.5', 'name' => 'smart_semaphores',
						'comment' => 'This semaphores table enables PHP processes to implement atomic operations. This can be needed when '.
							'there is a potential danger that process A could disturb process B. For example, two processes working '.
							'at the very same folder at file system, both creating, updating and removing files and folders.',
						'fields' => array(
							array( 'v' => '7.5', 'name' => 'id',        'type' => 'int(11)',     'autoincrement' => true,
								'comment' => 'Semaphore id. There can be only one PHP process at the same time having this id.' ),
							array( 'v' => '7.5', 'name' => 'entityid',  'type' => 'varchar(40)', 'default' => "'0'",
								'comment' => 'The id of any entity. For example, the issue id for which a publishing operation runs.' ),
							array( 'v' => '7.5', 'name' => 'lastupdate', 'type' => 'int(11)',     'default' => "'0'",
								'comment' => 'The last update time, measured in seconds since the Unix Epoch (January 1 1970 00:00:00 GMT). '.
									'This is to calculate the semaphore expiration (which is implementation specific). '.
									'Expired semaphores are cleaned automatically when a process ends. Or, they are cleaned implicitly '.
									'when process A has aborted unexpectedly, the lifetime has elapsed and process B is about to gain access.'.
									'See comment of the lifetime field for more info.' ),
							array( 'v' => '9.6', 'name' => 'lifetime', 'type' => 'int(11)',     'default' => "'0'",
								'comment' => 'The number of seconds the semaphore is kept alive by the system. '.
									'When now() > lastupdate + lifetime then the semaphore expires. '.
									'Expiration can be avoided by updating the lastupdate field with now(). '.
									'These updates should be done repeatedly, within the lifetime, so before it expires. '.
									'See comment of the lastupdate field for more info.' ),
							array( 'v' => '7.5', 'name' => 'user',      'type' => 'varchar(40)', 'default' => "''",
								'comment' => 'The user short name, for who the PHP server process is running.' ),
							array( 'v' => '7.5', 'name' => 'ip',        'type' => 'varchar(30)', 'default' => "''",
								'comment' => 'The IP address of the client for which the PHP server process was created.' ),
						),
						'indexes' => array(
							array( 'v' => '7.5', 'name' => 'idx_entity',     'fields' => 'entityid', 'unique' => true ),
							array( 'v' => '7.5', 'name' => 'idx_entityuser', 'fields' => 'entityid, user' ),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '7.5', 'field' => 'user', 'reftable' => 'smart_users', 'reffield' => 'user'),
						),
				),

				array ('v' =>'7.5', 'name' => 'smart_outputdevices',
						'comment' => 'Device definitions, used for publishing.',
						'fields' => array(
							array('v' => '7.5', 'name' => 'id',              'type' => 'int(11)',       'autoincrement' => true ),
							array('v' => '7.5', 'name' => 'name',            'type' => 'varchar(255)',  'default' => "''" ),
							array('v' => '7.5', 'name' => 'code',            'type' => 'int(4)',        'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'description',     'type' => 'blob',          'default' => "" ),

							array('v' => '7.5', 'name' => 'landscapewidth',  'type' => 'int(11)',       'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'landscapeheight', 'type' => 'int(11)',       'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'portraitwidth',   'type' => 'int(11)',       'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'portraitheight',  'type' => 'int(11)',       'default' => "'0'" ),

							array('v' => '7.5', 'name' => 'previewquality',  'type' => 'int(11)',       'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'landscapelayoutwidth', 'type' => 'double',  'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'pixeldensity',    'type' => 'int(11)',       'default' => "'0'" ),
							array('v' => '7.5', 'name' => 'pngcompression',  'type' => 'int(11)',       'default' => "'0'" ),

							array('v' => '8.0', 'name' => 'thumbheight',    'drops' => 
								array('v' => '7.5', 'name' => 'thumbheight',     'type' => 'int(11)',       'default' => "'1.3'" )),
							array('v' => '7.5', 'name' => 'textviewpadding', 'type' => 'varchar(50)',   'default' => "''" ),
							array('v' => '8.0', 'name' => 'processor',       'drops' =>
								array('v' => '7.5', 'name' => 'processor',       'type' => 'varchar(50)',   'default' => "''" )),
							array('v' => '8.0', 'name' => 'exporthtml5',     'drops' => 
								array('v' => '7.5', 'name' => 'exporthtml5',     'type' => 'char(2)',       'default' => "''" )), // default off
							array('v' => '8.0', 'name' => 'embedimagesincss','drops' =>
								array('v' => '7.5', 'name' => 'embedimagesincss','type' => 'char(2)',       'default' => "'on'" )),
							array('v' => '8.0', 'name' => 'textastext',      'drops' =>
								array('v' => '7.5', 'name' => 'textastext',      'type' => 'char(2)',       'default' => "''" )), // default off
						),
						'indexes' => array(
						),
						'inserts' => array(
							"1, 'iPad - DM', 0, '', 1024, 748, 768, 1004, 4, 558.5, 132, 9, ''",
							"2, 'iPad', 10, '', 1024, 768, 768, 1024, 4, 1024, 132, 9, ''",
							"3, 'Kindle Fire', 20, '', 1024, 600, 600, 1024, 4, 1024, 169, 9, ''",
							"4, 'Xoom', 30, '', 1280, 800, 800, 1280, 4, 1280, 160, 9, ''",
						),
						'lookups' => array(
						),
				),

				array ('v' =>'7.6', 'name' => 'smart_placementtiles',
						'comment' => 'Contains the of tiles of a placement.',
						'fields' => array(
							array('v' => '7.6', 'name' => 'id',				'type' => 'int(11)',		'autoincrement' => true ),
							array('v' => '7.6', 'name' => 'placementid',	'type' => 'int(11)',		'default' => "'0'",
								'comment' => 'Placement id.'),
							array('v' => '7.6', 'name' => 'pagesequence',	'type' => 'mediumint(9)',	'default' => "'0'",
						  		'comment' => 'Sequential page order within a layout' ),
						  	array('v' => '7.6', 'name' => 'left',			'type' => 'double',			'default' => "'0'"),
							array('v' => '7.6', 'name' => 'top',			'type' => 'double',			'default' => "'0'"),
							array('v' => '7.6', 'name' => 'width',			'type' => 'double',			'default' => "'0'"),
							array('v' => '7.6', 'name' => 'height',			'type' => 'double',			'default' => "'0'"),
						),
						'indexes' => array(
							array('v' => '7.6', 'name' => 'pi_placementtiles', 'fields' => 'placementid'),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '7.6', 'field' => 'placementid', 'reftable' => 'smart_placements', 'reffield' => 'id'),
						),
				),

				array ('v' =>'9.1', 'name' => 'smart_objectlabels',
					'comment' => 'Contains the object label definitions.',
					'fields' => array(
						array('v' => '9.1', 'name' => 'id',				'type' => 'int(11)',		'autoincrement' => true ),
						array('v' => '9.1', 'name' => 'objid',			'type' => 'int(11)', 		'default' => "'0'" ),
						array('v' => '9.6', 'name' => 'name',           'type' => 'varchar(250)',  	'default' => "''",
							'alters' => array('v' => '9.1', 'name' => 'name',           'type' => 'varchar(64)',  	'default' => "''" ), ),
					),
					'indexes' => array(
						array('v' => '9.1', 'name' => 'objlabels_objid', 'fields' => 'objid'),
					),
					'inserts' => array(
					),
					'lookups' => array(
						array('v' => '9.1', 'field' => 'objid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
					),
				),

				array ('v' =>'9.1', 'name' => 'smart_objectrelationlabels',
					'comment' => 'Links the objects to object labels.',
					'fields' => array(
						array('v' => '9.1', 'name' => 'labelid',			'type' => 'int(11)', 'default' => "'0'" ),
						array('v' => '9.1', 'name' => 'childobjid',		    'type' => 'int(11)', 'default' => "'0'" ),
					),
					'indexes' => array(
						array('v' => '9.1', 'name' => 'primary', 'fields' => 'labelid, childobjid', 'primary' => true),
						array('v' => '9.1', 'name' => 'objrellabels_childobjid', 'fields' => 'childobjid'),
					),
					'inserts' => array(
					),
					'lookups' => array(
						array('v' => '9.1', 'field' => 'childobjid', 'reftable' => 'smart_objects', 'reffield' => 'id'),
						array('v' => '9.1', 'field' => 'labelid', 'reftable' => 'smart_objectlabels', 'reffield' => 'id'),
					),
				),

				array('v' => '7.0', 'name' => 'smart_channeldata',
						'comment' => 
							'The Brand-, PubChannel- and Issue entities can be extended with custom properties. '.
							'This table stores the custom property values per admin entity. '.
							'Only the entity id is set for which the property is stored. Other ids are zero. '.
							'Drupal 6 integration uses this table to store properties per Issue-Categpry relation. '.
							'In that case both issue id and section id are set. ',
						'fields' => array(
							array('v' => '9.0', 'name' => 'publication','type' => 'int(11)',			'default' => "'0'",
								'comment' => 'Publication ID. Set when custom properties are defined on Brand level. Zero when defined on other level.' ),
							array('v' => '9.0', 'name' => 'pubchannel',	'type' => 'int(11)',			'default' => "'0'",
								'comment' => 'PubChannel ID. Set when custom properties are defined on Publication Channel level. Zero when defined on other level.' ),
							array('v' => '7.0', 'name' => 'issue',		'type' => 'int(11)',			'default' => "'0'",
								'comment' => 'Issue ID. Set when custom properties are defined on Issue level. Zero when defined on other level.' ),
							array('v' => '7.0', 'name' => 'section',	'type' => 'int(11)',			'default' => "'0'",
								'comment' => 'Section ID. Set when custom properties are defined on Issue-Category level (for Drupal 6 only). Zero when defined on other level.' ),
							array('v' => '7.0', 'name' => 'name',		'type' => 'varchar(200)',		'default' => "''"),
							array('v' => '7.0', 'name' => 'value',		'type' => 'blob',				'default' => "''"),
						),
						'indexes' => array(
							array('v' => '9.0', 'name' => 'primary', 	'fields' => 'publication, pubchannel, issue, section, name', 'primary' => true,
								'alters' => array('v' => '7.0', 'name' => 'primary', 	'fields' => 'issue, section, name', 	'primary' => true ) ),
						),
						'inserts' => array(
						),
						'lookups' => array(
							array('v' => '9.0', 'field' => 'publication', 'reftable' => 'smart_publications', 'reffield' => 'id'),
							array('v' => '9.0', 'field' => 'pubchannel', 'reftable' => 'smart_channels', 'reffield' => 'id'),
							array('v' => '7.0', 'field' => 'issue', 'reftable' => 'smart_issues', 'reffield' => 'id'),
							array('v' => '7.0', 'field' => 'section', 'reftable' => 'smart_publsections', 'reffield' => 'id'),
						),
				),
			)
		);

		// Structure containing a dbmodel change introduced in a patch release. The structure contains the version
		// in which the patch was introduced. Next, each patch has a name which is stored in the smart_config table
		// after the patch is installed. Lastly, it contains the specific table(s) changes. The table definition must
		// contain the 'autoincrement' column together with the changed/added columns.
		$this->patches =  array (
			array(
				'version' => '9.4',
				'name' => 'ids2014ccsupport',
				'tables' => array (
					array ('v' => '6.0', 'name' => 'smart_indesignserverjobs', 'comment' => 'Overview of InDesign Server jobs and their status.',
						'fields' => array(
							array('v' => '9.4', 'name' => 'minservermajorversion',	'type' => 'mediumint(9)', 'default' => "'0'",
								'alters' => array('v' => '7.5', 'name' => 'servermajorversion',	'type' => 'mediumint(9)', 'default' => "'5'",
									'alters' => array('v' => '7.0', 'name' => 'serverversion',	'type' => 'mediumint(9)', 'default' => "'0'")),
								'comment' => 'Minimum required InDesign Server internal major version to run the job. 8=CS6, 10=CC2014, etc' ),
							array('v' => '9.4', 'name' => 'minserverminorversion',	'type' => 'mediumint(9)', 'default' => "'0'",
								'alters' => array('v' => '7.5', 'name' => 'serverminorversion',	'type' => 'mediumint(9)', 'default' => "'0'"),
								'comment' => 'Minimum required InDesign Server internal minor version to run the job; See also minservermajorversion field.' ),
							array('v' => '9.4', 'name' => 'maxservermajorversion',	'type' => 'mediumint(9)', 'default' => "'0'",
								'comment' => 'Maximum required InDesign Server internal major version to run the job. 8=CS6, 10=CC2014, etc' ),
							array('v' => '9.4', 'name' => 'maxserverminorversion',	'type' => 'mediumint(9)', 'default' => "'0'",
								'comment' => 'Maximum required InDesign Server internal minor version to run the job; See also maxservermajorversion field.' ),
						)
					)
				)
			),
			array(
				'version' => '9.8',
				'name' => 'idsautomationskipstatus',
				'tables' => array(
					array ('v' =>'3.1', 'name' => 'smart_states', 'comment' => '',
						'fields' => array(
						  array('v' => '6.1', 'name' => 'id',				'type' => 'int(11)', 		'autoincrement' => true, ),
						  array( 'v' => '9.8', 'name' => 'skipidsa', 'type' => 'char(2)', 'default' => "''",
							'comment' => 'When selected do not create InDesign Server Automation job.' ),
						),
					),
				),
			),
			array(
				'version' => '10.1',
				'name' => 'idsautomationpickuptime',
				'tables' => array(
					array ('v' => '6.0', 'name' => 'smart_indesignserverjobs', 'comment' => 'Overview of InDesign Server jobs and their status.',
						'fields' => array(
							array('v' => '10.1', 'name' => 'pickuptime', 'type' => 'varchar(30)', 'default' => "''", 'patched' => array('10.1' => 'idsautomationpickuptime'),
								'comment' => 'Time from which the job runnner should pickup the job.' ),
						)
					)
				)
			)
		);
	}

	/**
	 * Returns tables from db model.
	 * Temporary- and dropped tables are excluded.
	 *
	 * @return array with table info.
	 */
	public function listTables()
	{
		$allTables = $this->struct['tables'];
		$retTables = array();
		foreach( $allTables as $key => $table ) {
			if( !self::toBeDropped($table, '0') ) {
				$retTables[$key] = $table;
			}
		}
		return $retTables;
	}

	/**
	 * Returns the table info for the specified table name.
	 *
	 * @param string $tablename.
	 * @return null| array with table info.
	 */
	public function getTable($tablename)
	{
		foreach ($this->struct['tables'] as $table)
		{
			if ($table['name'] == $tablename) {
				return $table;
			}
		}
		return null;
	}
	
	/**
	 * Returns a list of db table names where the table does not have a primary key.
	 *
	 * @return String[]
	 */
	public function getTablesWithoutAutoIncrement()
	{	
		return array(
			'smart_channeldata',
			'smart_mtp',
			'smart_mtpsentobjects',
			'smart_objectflags',
			'smart_objectrelationlabels',
			'smart_terms',
			'smart_serverjobs', // id field was removed since 9.4
			'smart_serverjobtypesonhold', // table introduced since 9.4
			'smart_indesignserverjobs', // id field was removed since 9.7
			'smart_indesignarticles',
			'smart_idarticlesplacements',
		);
	}

	/**
	 * Returns all table names logically grouped.
	 *
	 * @return array Keys are catetogory names and values are arrays of table names.
	 */
	public function getCategorizedTableNames()
	{
		return array(
			'Objects' => array( 'smart_objects', 'smart_objecteditions', 'smart_objectflags', 'smart_objectlocks', 'smart_objectrelations', 'smart_objectversions', 'smart_deletedobjects', 'smart_targets', 'smart_targeteditions', 'smart_objectrenditions', 'smart_objectlabels', 'smart_objectrelationlabels' ),
			'Object Publishing' => array('smart_publishedobjectshist', 'smart_publishhistory', 'smart_publobjects', 'smart_pubpublishedissues' ),
			'Brand Structure' => array( 'smart_publications', 'smart_channels', 'smart_issues', 'smart_publissues', 'smart_publsections', 'smart_issuesection', 'smart_publeditions', 'smart_issueeditions', 'smart_editions', 'smart_channeldata' ),
			'Workflow Definitions' => array( 'smart_states', 'smart_routing', 'smart_sectionstate', 'smart_issuesectionstate' ),
			'Users, Groups and Authorizations' => array( 'smart_users', 'smart_usrgrp', 'smart_authorizations', 'smart_groups', 'smart_profilefeatures', 'smart_profiles', 'smart_publadmin', 'smart_tickets' ),
			'Layout and Placements' => array( 'smart_pages', 'smart_elements', 'smart_placements', 'smart_placementtiles', 'smart_indesignarticles', 'smart_idarticlesplacements' ),
			'Autocomplete and Suggestions' => array( 'smart_termentities', 'smart_terms' ),
			'Output devices' => array( 'smart_outputdevices' ),
			'Semaphores' => array( 'smart_semaphores' ),
			'Audit trails and Logging' => array( 'smart_log', 'smart_messagelog' ),
			'Application- and User Settings' => array( 'smart_config', 'smart_settings' ),
			'Metadata and Dialog Setup' => array( 'smart_namedqueries', 'smart_properties', 'smart_actionproperties' ),
			'Made To Print' => array( 'smart_mtp', 'smart_mtpsentobjects' ),
			'Data Sources' => array( 'smart_datasources', 'smart_dspublications', 'smart_dsqueries', 'smart_dsqueryfamilies', 'smart_dsqueryfields', 'smart_dsqueryplacements', 'smart_dssettings', 'smart_dsupdates', 'smart_dsobjupdates' ),
			'Server Plug-ins' => array( 'smart_serverplugins', 'smart_serverconnectors' ),
			'Server Jobs' => array( 'smart_serverjobs', 'smart_serverjobconfigs', 'smart_serverjobsupports', 'smart_servers'  ),
			'Web Editor' => array( 'smart_appsessions', 'smart_indesignservers', 'smart_indesignserverjobs' ),
			'File Storage' => array( 'smart_storage' )
		);
	}

	/**
	 * Generates sql-scripts for the whole model. Scripts will be stored in generator.
	 *
	 * @param string $version			Version to generate
	 * @param object $generator			Generator class with logic and storage
	 */
	public function generate($version, $generator)
	{
		$this->errors = array();
		$generator->clean();
		foreach ($this->struct['tables'] as $table) {
			if( !self::toBeDropped($table, '0') ) {
				$this->generateTable($version, $table, $generator, true);
			}
		}
	}

	/**
	 * Generates sql-scripts to implement a database model change introduced in a patch release ('to' version). The
	 * 'from' version is the previous minor version before the 'to' version as the change are introduced by a patch
	 * release, so after the last minor release.
	 *
	 * @param string $fromversion The previous version which is compared with the 'to' version.
	 * @param string $toversion The previous minor version.
	 * @param array $patch Contains the model change plus further patch info.
	 * @param object $generator	Generator class with logic and storage
	 */
	public function generatePatch( $fromversion, $toversion, $patch, $generator )
	{
		$generator->clean();
		$this->doGenerateUpgradeScript( $fromversion , $toversion, $generator, $patch);
		$generator->insertPatchInfo( $patch['name']);
	}

	public function getPatchInfo()
	{
		return $this->patches;
	}

	/**
	 * Generates sql-scripts for given table. Scripts will be stored in generator.
	 * Normally default data is only added to a table when a database is newly generated.
	 * During an upgrade only the definitions are changed. In some special cases it is needed
	 * to add default data during the upgrade process. This is handled by the $insertrecords.
	 *
	 * @param string $version Version to generate.
	 * @param array $table Table info for the table to generate.
	 * @param object $generator	Generator class with logic and storage.
	 * @param boolean $insertrecords True if default data must be inserted, else false.
	 */
	protected function generateTable($version, $table, $generator, $insertrecords)
	{
		$versions = $this->getVersions();
		if( !in_array( $table['v'], $versions ) ) {
			$this->errors[] = 'Table '.$table['name'].' refers to unknown version: '.$table['v'];
		}
		$isLastVersion = $version == end($versions);
		$isTempTable = substr( $table['name'], 0, 5 ) == 'temp_';
		
		if( version_compare( $table ['v'], $version, '<=' ) ) {
			$generator->tablePre($table);
			$hasIdField = false;
			foreach ($table['fields'] as $fld) {
				if( !in_array( $fld['v'], $versions ) ) {
					$this->errors[] = 'Field '.$table['name'].'::'.$fld['name'].' refers to unknown version: '.$fld['v'];
				}
				$toBeDropped = self::toBeDropped($fld, '0');
				$changeToField = $fld;
				$oldfield = self::getAlteredDBInfo($version, $changeToField);
				if( $oldfield && !in_array( $oldfield['v'], $versions ) ) {
					$this->errors[] = 'Field '.$table['name'].'::'.$oldfield['name'].' refers to unknown version: '.$oldfield['v'];
				}
				// Regular case; field is added in some previous version and not dropped yet. 
				if( version_compare( $fld ['v'], $version, '<=' ) && !$toBeDropped) {
					$generator->field( $table, $fld, true );
				}
				elseif ($toBeDropped) {
					$originalField = self::getDroppedInfo($fld);
					// Field will be dropped in a future version but is still actual for the version created now.
					if( version_compare( $fld['v'], $version, '>' ) && version_compare( $originalField, $version, '<=' ) ) {
						$generator->field( $table, $originalField, true );
					}
				}
				// Field has been changed.
				elseif ($oldfield && !$toBeDropped) {
					// Take the definition that is applicable for this version.
					// E.g. definition is created in version 5.0.
					// Definition is changed in version 8.0.
					// Run is done for version 6.0.
					// Take definition of version 5.0 as version 8.0 is not yet applicable.
					if( version_compare( $oldfield['v'], $version, '<=' ) ) {
						$generator->field( $table, $oldfield, true );
					}
				}
				if( !$toBeDropped && !$isTempTable ) {
					if( $fld['name'] == 'id' ) {
						$hasIdField = true;
					}
					// Note that there are many places in the core server assuming that when a table
					// has an "id" field, the auto increment option must be enabled for that field.
					// And, if there is no "id" field, there is no auto increment defined for the table.
					// Here we validate these assumptions.
					if( isset($fld['autoincrement']) && $fld['autoincrement'] ) {
						if( $fld['name'] != 'id' ) {
							$this->errors[] = 'Table "'.$table['name'].'" has field "'.$fld['name'].'" '.
								'for which the "autoincrement" option is set. '.
								'This should be done for id fields only.';
						}
					} else {
						if( $fld['name'] == 'id' ) {
							$this->errors[] = 'Table "'.$table['name'].'" has field "id" '.
								'for which the "autoincrement" option is not set. '.
								'This should always be done for id fields.';
						}
					}
				}
			}
			$generator->tablePost($table);
			
			// Validate tables in DB model against tables in getTablesWithoutAutoIncrement() function.
			if( $isLastVersion && !$isTempTable ) {
				$tablesWithoutIncr = $this->getTablesWithoutAutoIncrement();
				$isListedInTablesWithoutIncr = in_array( $table['name'], $tablesWithoutIncr );
				if( $hasIdField && $isListedInTablesWithoutIncr ) {
					$this->errors[] = 'Table '.$table['name'].' has id field but table is '.
						'listed in the DBStruct::getTablesWithoutAutoIncrement() function.';
				} elseif( !$hasIdField && !$isListedInTablesWithoutIncr ) {
					$this->errors[] = 'Table '.$table['name'].' has no id field but table is '.
						'not listed in the DBStruct::getTablesWithoutAutoIncrement() function.';
				}
			}
			
			if (isset ( $table ['indexes'] ))
				foreach ( $table ['indexes'] as $ix ) {
					if( !in_array( $ix['v'], $versions ) ) {
						$this->errors[] = 'Index "'.$table['name'].'->'.$ix['name'].'" refers to unknown version: '.$ix['v'];
					}
					if( version_compare( $ix ['v'], $version, '<=' ) ) {
						$generator->index ( $ix, $table );
					}
					else {	// It is possible that an index is created for an intermediate version and will changed later on.
							// During creation of the table the latest version of the index is used.
						$latestindex = self::getAlteredDBInfo($version, $ix);
						if ($latestindex != null) {
							if( version_compare( $latestindex['v'], $version, '<=' ) ) {
								$generator->index($latestindex, $table);
							}
						}
					}
			}
			if (isset ( $table ['inserts'] ) && $insertrecords)
				foreach ( $table ['inserts'] as $ins ) {
				$generator->insert($ins, $table);
			}
			$generator->insertpost($table);
		}
	}

	/**
	 * Generates upgrade scripts from one version (from) to another version (till).
	 *
	 * @param $fromversion From version.
	 * @param $tillversion Till version.
	 * @param object $generator	Generator class with logic and storage.
	 */
	public function generateUpgrade( $fromversion, $tillversion, $generator )
	{
		$generator->clean();
		$this->doGenerateUpgradeScript( $fromversion, $tillversion, $generator, $this->struct );
	}

	private function doGenerateUpgradeScript( $fromversion, $tillversion, $generator, $tableInfo )
	{
		$this->errors = array();
		$generator->upgradePre();
		foreach ( $tableInfo['tables'] as $table ) {
			if ( self::isObsolete( $table, $fromversion ) ) {
				continue;
			}
			$tableToBeDropped = self::toBeDropped( $table, $fromversion );
			$originalTable = null;
			if ( $tableToBeDropped ) {
				$originalTable = self::getDroppedInfo( $table );
			}
			if( version_compare( $table['v'], $fromversion, '<=' )
				 && !$tableToBeDropped ) { // Table already exists in the $fromversion and will not be dropped.
				$addedFields = false   ;   // Look if fields are added/changed or dropped.
				if ( isset($table['fields'] )){
					foreach( $table['fields'] as $field ) {
						$changeToField = $field;
						$fromversionField = self::getAlteredDBInfo( $fromversion, $changeToField );
						$firstField = self::getFirstDefinitonAlteredField( $field );
						$fieldToBeDropped = self::toBeDropped( $field, $fromversion );
						$originalField = null;
						if ( $fieldToBeDropped ) {
							$originalField = self::getDroppedInfo( $field );
						}
						if( !$fromversionField ) { // Skip field updates and dropping of fields (handled later).
							$fieldToAdd = false;
							$newField = null;
							$patchedFrom = self::patchedDBInfo( $field, $fromversion );
							if ( !$fieldToBeDropped &&
								 !$patchedFrom &&
								version_compare( $field['v'], $fromversion, '>' ) &&
								version_compare( $field['v'], $tillversion, '<=' ) ) {
								// Add the field that is added in a version between the from and the till version. 
								$fieldToAdd = true;
								$newField = $field;
							}
							elseif ( $fieldToBeDropped &&
								!$patchedFrom &&
								version_compare( $originalField['v'], $fromversion, '>' ) &&
								version_compare( $originalField['v'], $tillversion, '<=' ) &&
								version_compare( $field['v'], $tillversion, '>' ) ) {
								// Add the field also if the field in later conversion is dropped again, but not if it
								// was as already added via a patch.
								$fieldToAdd = true;
								$newField = $originalField;
							}
							elseif ( $firstField &&
								!$fieldToBeDropped &&
								!$patchedFrom &&
								version_compare( $firstField['v'], $fromversion, '>' ) &&
								version_compare( $firstField['v'], $tillversion, '<=' ) ) {
								// Field is added after the from version and before the till version. Next it is changed.
								// Field must be added before it can be changed later on.
								$fieldToAdd = true;
								$newField = $firstField; // Take the initial definition. Beneath the changed definition
														 // is picked up.
							}
							if ( $fieldToAdd ) {
								if ( $addedFields === false ) { // First time only.
									$generator->alterPre( $table );
								}
								$generator->addField( $table['name'], $newField );
								$addedFields = true;
							}
						}
					}
					if( $addedFields ) {
						$generator->alterPost();
					}

					// Check for changed (alter) fields.
					foreach ($table['fields'] as $field) {
						$changeToField = $field;
	    				$fromversionField = self::getAlteredDBInfo( $fromversion, $changeToField );
						$fieldToBeDropped = self::toBeDropped( $field, $fromversion );
						$patchedFrom = self::patchedDBInfo( $field, $fromversion );
	    				if( $fromversionField && !$fieldToBeDropped) {
							if( ( version_compare($changeToField['v'], $fromversion, '>' ) ) &&
								( version_compare( $changeToField['v'], $tillversion, '<=' ) ) &&
						        ( version_compare( $patchedFrom, $field['v'], '<' ) ) ) {
	    						$generator->alterField( $table, $fromversionField, $changeToField );
	    					}
	    				}
					}
				}

				// Check for new indexes.
				if ( isset( $table['indexes'] )) {
					foreach ( $table['indexes'] as $indexInfo ) {
						$oldindex = self::getAlteredDBInfo( $fromversion, $indexInfo );
						if ( $oldindex == null ) { //New index
							if ( ( version_compare( $indexInfo['v'], $fromversion, '>' ) ) &&
								( version_compare( $indexInfo['v'], $tillversion, '<=' ) ) ) {
								$generator->index( $indexInfo, $table );
							}
						}
					}
				}

				// check for changed indexes
				if ( !$generator->addToIdentScript($fromversion, $table )) {
					// If a table is newly created because the identity is changed, the index must not be updated
					// because this results in an error.
					if ( isset( $table['indexes'] )) {
						foreach ( $table['indexes'] as $indexInfo ) {
							if( ( version_compare( $indexInfo['v'], $fromversion, '>' ) ) &&
								( version_compare( $indexInfo['v'], $tillversion, '<=' ) ) ) {
								$oldindex = self::getAlteredDBInfo( $fromversion, $indexInfo );
								if ( $oldindex != null ) { //Changed index
									$generator->alterIndex( $table, $oldindex, $indexInfo );
								}
							}
						}
					}
				}

				// Check for fields to be dropped.
				if( isset( $table['fields'] )){
					// Check for fields to be dropped.
					foreach ( $table ['fields'] as $field ) {
						if ( version_compare( $field ['v'], $fromversion, '>' ) &&
							version_compare( $field['v'], $tillversion, '<=' ) ) {
							$fieldToBeDropped = self::toBeDropped ( $field, $fromversion );
							if ( $fieldToBeDropped ) {
								$generator->dropField ( $table, $field );
							}
						}
					}
				}
				$generator->postTableUpgrade( $table );
			}
			elseif( ( !$tableToBeDropped &&
				version_compare( $table['v'], $fromversion, '>' ) &&
				version_compare( $table['v'], $tillversion, '<=' ) ) ) { // Add new tables.
				$insertrecords = false;
				if ($table['name'] == 'smart_profiles' || $table['name'] == 'smart_profilefeatures') {
				// Profile 'Full Control' and its features must be inserted even when upgrading
					$insertrecords = true;
				}
				$this->generateTable($tillversion, $table, $generator, $insertrecords);
			}
			elseif( ( $tableToBeDropped &&
				version_compare( $originalTable['v'], $fromversion, '>' ) &&
				version_compare( $originalTable['v'], $tillversion, '<=' ) &&
				version_compare( $table['v'], $tillversion, '>' )  ) ) {
				// Add new table even if table is dropped later on, else the drop will result in an error.
				$this->generateTable($tillversion, $originalTable, $generator, false);
			}
			elseif( $tableToBeDropped &&
				version_compare( $table['v'], $fromversion, '>' ) &&
				version_compare( $table['v'], $tillversion, '<=' ) ) { // Drop tables
				$generator->addTxt( $generator->dropTable($table['name'] ));
			}
		}
		$generator->upgradePost();
	}

	/**
	 * Checks if a field/index definition has been changed. Changed (alter) means if it is altered
	 * compared to a version prior to or equal to the 'from' version.
	 * 
	 * @param string $fromversion From version.
	 * @param array  $dbInfo field/index definition.
	 * @return array  $field/index definition of the last 'alter' version or null if the field is not changed.
	 */
	private static function getAlteredDBInfo( $fromversion, $dbInfo )
	{
		$result = null;

		if ( array_key_exists( 'alters', $dbInfo )) {
			if ( is_array($dbInfo['alters'] )) {
				if ( version_compare( $dbInfo['alters']['v'], $fromversion ,'<=' ) ) {
					$result = $dbInfo['alters'];
				}
				else {
					$dbInfo = $dbInfo['alters'];
					$result = self::getAlteredDBInfo( $fromversion, $dbInfo );
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if a table definition has become obsolete. Obsolete means that the table is already dropped in a version
	 * prior to the upgrading from version.
	 *
	 * @param array $table table definition.
	 * @param string $fromVersion The upgrade from version.
	 * @return bool is obsolete then true and else false.
	 */
	static private function isObsolete( $table, $fromVersion )
	{
		return isset ( $table['drops'] ) &&  version_compare( $table['v'], $fromVersion, '<' );
	}

	/**
	 * Checks if a field is db info structure is patched in a certain version.
	 * Returns the original version of the patched structure.
	 *
	 * @param array $dbInfo (field) info structure to check.
	 * @param $version Version on which the patch must be applied.
	 * @return string The original version or null if structure has not been patched.
	 */
	private function patchedDBInfo( $dbInfo, $version )
	{
		$result = null;

		if ( array_key_exists( 'patched', $dbInfo )) {
			if ( $dbInfo['patched'] ) foreach ( $dbInfo['patched'] as $patchVersion => $name ) {
				if ( $patchVersion == $version ) {
					$result = $dbInfo['v'];
					break;
				}
			}
		} elseif ( array_key_exists( 'alters', $dbInfo )) {
			$dbInfo = $dbInfo['alters'];
			$result = self::patchedDBInfo( $dbInfo, $version );
		}

		return $result;
	}

	/**
	 * Finds the first field definition of a field that has been changed (altered).
	 *
	 * @param array $field field info.
	 * @return array field info of the first definition of a changed field  or null if the field was never changed.
	 */
	private static function getFirstDefinitonAlteredField( $field )
	{
		$result = null;
		if ( isset($field['alters']) && is_array($field['alters'] )) {
			$result = self::getFirstDefinitonAlteredField( $field['alters'] );
			if ( is_null($result )){
				$result = $field['alters'];
			}
		}
		return $result;
	}
	
	/**
	 * Checks if the definition (of a table or field etc) contains
	 * a 'drops' entry. The 'drops' entry contains information about when
	 * the table or field has become unneeded and and also what is its history.
	 *
	 * @param array $dbInfo table or field info.
	 * @return array|null array with history in case a table/field definition is dropped else null.
	 */
	private static function getDroppedInfo( $dbInfo )
	{
		$result = null;
		if (array_key_exists( 'drops', $dbInfo )) {
			if (is_array( $dbInfo['drops'] )) {
				$result = $dbInfo['drops'];
			}
		}
		return $result;
	}

	/**
	 * Checks if the definition of a table or field has become unneeded in some version after the passed $fromversion.
	 *
	 * @param $dbInfo  table or field info.
	 * @param string $fromversion version to compare against.
	 * @return boolean true if definition is dropped in a later version else false.
	 */
	private static function toBeDropped($dbInfo, $fromversion)
	{
		$result = false;
		if( ( version_compare($dbInfo['v'], $fromversion, '>' ) ) ) {
    		if (array_key_exists( 'drops' , $dbInfo )) {
    			$result = true;
    		}
    	}
		return $result;
	}

	/**
	 * Provides the errors that occur while generating the DB.
	 *
	 * @return array List of error messages (strings).
	 */
	public function getErrors()
	{
		return $this->errors;
	}
}


/**
 * Base class for all generators.
 *
 * Generators are called for each element of DBStruct and return code by their txt() method.
 *
 * @package dbgen
 * @subpackage GenericGenerator
**/

abstract class GenericGenerator
{
	protected $txt;

	/**
	 * returns generated SQL
	 *
	 * @return string
	 */

	public function txt()
	{
		return $this->txt;
	}

	/**
	 * add txt
	 *
	 * @param string text
	 */

	public function addTxt($txt)
	{
		$this->txt .= $txt;
	}

	/**
	 * materialize in a file
	 *
	 * @param string $file filename
	 * @return bool TRUE when there was content to write. FALSE when empty.
	 */
	public function materialize($file)
	{
		$wroteContent = false;
		if ($this->txt) {
			$fp = @fopen($file, "w+");
			if (!$fp) exit ("Cannot open file for writing: $file");
			fputs($fp, $this->txt);
			fclose($fp);
			$wroteContent = true;
		}
		return $wroteContent;
	}

	/**
	 * empties generated txt
	 */
	public function clean()
	{
		$this->txt ='';
	}

	/**
	 * function to generate start of create table text
	 *
	 * @param array $table DB table info.
	 */
	abstract public function tablePre( $table );

	/**
	 * function to generate end of create table text plus additional stuff (sequences etc)
	 *
	 * @param array $table table info.
	 */
	abstract public function tablePost( $table );

	/**
	 * Function to generate seperate database fields in create statement
	 *
	 * @param array $table table info.
	 * @param array $field field info.
	 * @param bool $comma Add comma to the (add column) statement.
	 * @param bool $doDefault Add default value to the field definition.
	 */
	abstract public function field( $table, $field, $comma, $doDefault=true );

	/**
	 * Method to generate indexes. The index field can contain a length limitation
	 * (e.g. documentid(200). Length limitation on index fields is only applicable
	 * in case of Mysql. For Oracle and Mssql the (200) is removed before the
	 * index field is quoted.
	 *
	 * @param array $index DB index info.
	 * @param array $table DB table info.
	 */
	abstract public function index( $index, $table );

	/**
	 * Function to drop indexes
	 *
	 * @param array $index DB index info
	 * @param array $table DB table info.
	 */
	abstract public function dropIndex( $index, $table );

	/**
	 * function to generate insert statement
	 *
	 * @param string $line
	 * @param array $table DB table info.
	 * @param bool $auto true if auto-match on number of fields needs to be done (beware of , in values!!)
	 */
	abstract public function insert( $line, $table, $auto = true );

	/**
	 * function to generate statements after all insert statements
	 *
	 * @param array $table DB table info.
	 */
	public function insertPost( $table )
	{
	}

	/**
	 * function to stamp database of spec version
	 *
	 * @param string $version Version to stamp.
	 */
	abstract public function setVersion( $version );

	/**
	 * function to change an existing field definition
	 *
	 * @param array $table DB table info.
	 * @param array $oldfld DB field info as in the old/source database.
	 * @param array $newfld DB field info as in the new/target database.
	 */
	abstract public function alterField( $table, $oldfld, $newfld );

	/**
	 * function to drop an existing field definition
	 *
	 * @param array $table DB table info.
	 * @param array $fld DB field info as in the new/target database.
	 */
	abstract public function dropField( $table, $fld );

	/**
	 * function to change an existing index definition
	 *
	 * @param array $table DB table info.
	 * @param array $oldIndex DB index info as in the old/source database.
	 * @param array $newIndex DB index info as in the new/target database.
	 */
	abstract public function alterIndex( $table, $oldIndex, $newIndex );

	/**
	 * function called before running upgrade script
	 */
	public function upgradePre()
	{
	}

	/**
	 * function called after running upgrade script
	 */
	public function upgradePost()
	{
	}

	/**
	 * Tells whether or not the field name is allowed for the target DB engine.
	 *
	 * @param string $fieldName
	 * @return boolean TRUE when allowed, else FALSE.
	 */
	abstract public function isReservedFieldName( $fieldName );
}

/**
 * baseclass for all SQL generators
 *
 * @package dbgen
 * @subpackage StdSqlGenerator
 */
abstract class StdSqlGenerator extends GenericGenerator
{
	protected $thistable;
	protected $thistablefields;
	protected $droptables;
	protected $errors = array();
	protected $myname;

	/**
	 * constructor
	 *
	 * @param $drop boolean	true means drop statements will be generated
	 */
	public function __construct($drop)
	{
		$this->droptables = $drop;
	}

	public function getDBName()
	{
		return $this->myname;
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePre($table)
	{
		$this->txt .= "\r\n";
//		if ($this->droptables) $this->txt .= $this->dropTable($table["name"]).$this->closeline()."\r\n";
		if ($this->droptables) $this->txt .= $this->dropTable($table["name"]);
		$this->txt .= "CREATE TABLE ".$this->quotefields($table["name"])." (\r\n";
		$this->thistable = $table['name'];
		$this->thistablefields = array();
	}

	/**
	 * function to generate alter table text
	 *
	 * @param array $table DB table info.
	 */
	public function alterPre($table)
	{
		$this->txt .= "ALTER TABLE " . $this->quotefields($table["name"]) . "\r\n";
	}

	/**
	 * function to generate alter table text ending
	 *
	 */
	public function alterPost()
	{
		$this->txt = substr($this->txt, 0, -3);		// skip last ,\r\n
		$this->txt .= $this->closeline()."\r\n";
	}

	/**
	 * Function to generate alter table add field.
	 *
	 * @param array $table DB table info.
	 * @param array $field DB field info.
	 */
	public function addField( $table, $field )
	{
		$this->txt .= "ADD ";
		$this->field( $table, $field, true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePost($table)
	{
		$primarykey = 'id';

		// Primary keys (if any).
		if (isset($table['indexes'])) foreach ($table['indexes'] as $ix) {
			if (isset($ix["primary"])) $primarykey = $ix['fields'];
		}

		// Primary key
		$keys = $this->quotefields($primarykey);
		$this->txt .= "  PRIMARY KEY ($keys)\r\n)".$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function field( $table, $field, $comma, $doDefault=true )
	{
		// name, type, not null
		$this->txt .= "  ".$this->quotefields($field["name"])." ".$this->normtype($field["type"]);
		if ((isset($field["nullable"]) && $field["nullable"] == false) || !isset($field["nullable"]) ) {
			$this->txt .= $this->notnull();
		}
		else {
			$this->txt .= $this->addnull();
		}

		// check mandatory field properties
		if( !isset($field['v']) || !isset($field['name']) || !isset($field['type']) ||
			(!isset($field['default']) && !isset($field['autoincrement']) ) ) { // either default or autoincrement
			exit( 'In table '.$table['name'].', incomplete field definition found: '.print_r( $field, true ) );
		}
		
		if( $this->isReservedFieldName( $field['name'] ) ) {
			$this->errors[] = 'In table "'.$table['name'].'" the field "'.$field['name'].'" is defined, but that is a reserved word.';
		}

		$this->thistablefields[] = $field['name'];

		// default or autoincrement
		if( $doDefault === true ) {
			if( isset($field["autoincrement"]) ) {
				$this->txt .= " ".$this->autoincrement();
			} else {
				$this->txt .= $this->setDefault($field);
			}
		}
		if($comma) {
			$this->txt .= ",\r\n";
		}
	}

	/**
	 * Based on the 'default' property a default value is returned.
	 *
	 * @param array $field field properties as defined in dbmodel.
	 * @return mixed The default value.
	 */
	protected function setDefault( $field )
	{
		$default = '';

		if( isset($field['default'])) {
			if ( is_string( $field['default'])) {
				if (strlen($field['default']) > 0 ) {
					$default = ' default '.$field['default'];
				}
				// default => "" are skipped.
			} else {
				$default = ' default '.$field['default'];
			}
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function index($index, $table)
	{
//		if (@$index['primary'] || @$index['unique']) return;
		if (isset($index['primary']) && $index['primary'] == true ) {
			 return;
		}

		$unique = '';

		if (isset($index['unique']) && $index['unique'] == true ) {
			$unique = 'UNIQUE';
		}

		$name = '';
		if (isset($index["name"])) {
			$name = $index["name"];
		}
		
		if (!$name && isset($index[$this->myname."name"])) {
			$name = $index[$this->myname."name"];
		}
		
		if (!$name) return;

		$type = '';
		if (isset($index[$this->myname."type"])) {
			$type = $index[$this->myname."type"];
		}
		
		// Remove length constraint in case of Oracle or Mssql. So 'documentid(200)'
		// will be changed into 'documentid'.
		$indexfields = explode(',', $index['fields']);
		foreach ($indexfields as &$indexfield) {
			$posBracket = strpos($indexfield, '(', 1); 
			if (is_integer($posBracket)) { // bracket found
				$column = substr($indexfield, 0, ($posBracket));
				if ($this->myname == 'mssql' || $this->myname == 'ora') { 
					$indexfield = $column;
				}
			} 
		}	
		$index['fields'] = implode(',', $indexfields);
			
		$this->txt .= "CREATE $unique INDEX ".$this->quotefields($name)." on ".$this->quotefields($table["name"])."(";
		$this->txt .= $this->quotefields($index['fields']);
		$this->txt .= ") $type".$this->closeline()."\r\n";
	}

	/**
	 * Validates mandatory properties that must be set in a field definition.
	 * Also checks if all properties used in the field definition are known / supported.
	 *
	 * @param array $table DB table info.
	 * @param array $field DB field info.
	 */
	private function validateField( $table, $field )
	{
		$mandatories = array( 'v', 'name', 'type' );
		foreach( $mandatories as $prop ) {
			if( !isset($field[$prop]) ) {
				$this->errors[] = 'The "'.$prop.'" property is missing for "'.
									$table['name'].'::'.$field['name'].'" field. ';
			}
			if( empty($field[$prop]) ) {
				$this->errors[] = 'The "'.$prop.'" property value is empty for "'.
									$table['name'].'::'.$field['name'].'" field. ';
			}
		}
		if( !isset($field['default']) && !isset($field['autoincrement']) ) { // either default or autoincrement
			$this->errors[] = 'The "default" and "autoincrement" and  property are both missing for "'.
								$table['name'].'::'.$field['name'].'" field. ';
		}

		$supported = array_flip( array( 'v', 'name', 'type', 'default', 'nullable', 'comment', 'alters', 'autoincrement', 'patched' ) );
		foreach( array_keys($field) as $prop ) {
			if( !isset($supported[$prop]) ) {
				$this->errors[] = 'The "'.$prop.'" property for "'.$table['name'].'::'.$field['name'].
									'" field is not supported. ';
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function alterField( $table, $oldfld, $newfld )
	{
		$this->validateField( $table, $oldfld );
		$this->validateField( $table, $newfld );
		if( !$this->isAutoIncrementEqual( $oldfld, $newfld ) ) {
			$this->errors[] = 'Attempt to alter the "autoincrement" value for the "'.$table['name'].'::'.$newfld['name'].'" field, '.
				'but this is not supported by the DB generator classes. Original value is "'.$oldfld['autoincrement'].'" and the '.
				'new value is "'.$newfld['autoincrement'].'". Field definition cannot be changed if the identity is different. '.
				'In that case table has to rebuild by running a seperate script to update the identity.';
		}
		$this->doAlterField( $table, $oldfld, $newfld );
	}

	/**
	 * function to change an existing field definition
	 *
	 * @param array $table DB table info.
	 * @param array $oldfld field info as in the old/source database.
	 * @param array $newfld field info as in the new/target database.
	 */
	abstract protected function doAlterField( $table, $oldfld, $newfld );

	/**
	 * {@inheritDoc}
	 */
	public function dropField($table, $fld)
	{
		$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) . ' DROP ' .
					  $this->quotefields($fld['name']) .$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function dropIndex( $index, $table )
	{
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert($insert, $table, $auto = true)
	{
		$this->txt .= "INSERT INTO ".$this->quotefields($table['name']);
		if ($auto) {
			$cnt = count($this->thistablefields);
			$insertarr = explode(",", $insert, $cnt+1);
			if (count($insertarr) > $cnt) {
				array_pop($insertarr);
			}
			$insert = implode(",", $insertarr);
		}
		$this->txt .= " (".$this->quotefields(join(", ",$this->thistablefields)).") VALUES (";
		$this->txt .= $insert;
		$this->txt .= ")".$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function setVersion($version)
	{
		$this->txt .= "UPDATE ".$this->quotefields('smart_config')." set ".$this->quotefields('value')." = '$version' where ".$this->quotefields('name')." = 'version'".$this->closeline()."\r\n";
	}

	/**
	 * Under certain conditions a table must not be added to the 'update identity'
	 * script. This function checks if a table must added depending on the version
	 * which is updated.
	 *
	 * @param string $oldversion Version for which the identity script is generated.
	 * 							 E.g. '4.1' or '4.2'
	 * @param array $table DB table info.
	 * @return boolean Whether or not the table must be added to the 'update identity' script
	 */
	public function addToIdentScript( $oldversion, $table )
	{
		return false;
	}

	/**
	 * function to return autoincrement text (or empty string)
	 *
	 * @return string	SQL fragment
	 */
	protected function autoincrement()
	{
		return "auto_increment";
	}

	/**
	 * function to return not null text (or empty string)
	 *
	 * @return string SQL fragment
	 */
	protected function notnull()
	{
		return " not null ";
	}

	/**
	 * function to return 'null' text (or empty string)
	 *
	 * @return string SQL fragment
	 */
	protected function addnull()
	{
		return '';
	}

	/**
	 * function to quote database dependent
	 *
	 * @param string $fld field or list of fields (comma separated). The field can contain a length limitation
	 * in case an index field is passed (e.g. documentid(200). This must be quoted like `documentid`(200).
	 * Length limitation on index fields is only applicable in case of Mysql.
	 * @return string	quoted field(s)
	 */
	public function quotefields($fld)
	{
		$ret = '';
		$arr = explode(',', $fld);
		$komma = '';
		foreach ($arr as $ix) {
			$posBracket = strpos($ix, '(', 1); 
			if (is_integer($posBracket)) { // bracket found
				$column = substr($ix, 0, ($posBracket));
				$betweenBrackets = substr($ix, $posBracket);
				$ret .= $komma.'`'.trim($column).'`'.$betweenBrackets;
			} 
			else { //no bracket
				$ret .= $komma.'`'.trim($ix).'`';
			}
			$komma = ', ';
		}
		return $ret;
	}
	
	/**
	 * Function to convert database types dependent on database
	 *
	 * @param  string $type	type before conversion
	 * @return string Type (after conversion)
	 */
	protected function normtype($type)
	{
		return $type;
	}

	/**
	 * Function to generate text for unique key construct.
	 *
	 * @param string $name name of unique key
	 * @param string $fields field or list of fields (comma separated).
	 * @return string SQL fragment for defining a unique key.
	 */
	protected function uniquekey($name, $fields)
	{
		return " UNIQUE KEY ".$this->quotefields($name)." (".$this->quotefields($fields)."),\r\n";
	}

	/**
	 * function to generate text for closing sql lines
	 *
	 * @return string	SQL fragment
	 */
	protected function closeline()
	{
		return ";";
	}

	/**
	 * function to generate text for drop if exists constructs
	 *
	 * @param string $name Name of table.
	 * @return string	SQL fragment
	 */
	public function dropTable($name)
	{
		return "DROP TABLE IF EXISTS ".$this->quotefields($name) . $this->closeline() . "\r\n";
	}

	/**
	 * Tells if the field's name remains the same (not changed).
	 *
	 * @param string $oldName
	 * @param string $newName
	 * @return bool
	 */
	protected function isNameEqual( $oldName, $newName )
	{
		return strcasecmp( $oldName, $newName ) == 0;
	}

	/**
	 * Tells if the field's type remains the same (not changed).
	 *
	 * @param string $oldType
	 * @param string $newType
	 * @return bool
	 */
	protected function isTypeEqual( $oldType, $newType )
	{
		return strcasecmp( $this->normtype( $oldType ), $this->normtype( $newType ) ) == 0;
	}
	
	/**
	 * Tells if the field's default remains the same (not changed).
	 *
	 * @param array $oldField
	 * @param array $newField
	 * @return bool
	 */
	protected function isDefaultEqual( array $oldField, array $newField )
	{
		$oldNullable = isset($oldField['default']) ? $oldField['default'] : null;
		$newNullable = isset($newField['default']) ? $newField['default'] : null;
		return $oldNullable === $newNullable;
	}

	/**
	 * Tells if the field's nullable remains the same (not changed).
	 *
	 * @param array $oldField
	 * @param array $newField
	 * @return bool
	 */
	protected function isNullableEqual( array $oldField, array $newField )
	{
		$oldNullable = isset($oldField['nullable']) ? $oldField['nullable'] : null;
		$newNullable = isset($newField['nullable']) ? $newField['nullable'] : null;
		return $oldNullable === $newNullable;
	}

	/**
	 * Tells if the field's autoincrement remains the same (not changed).
	 *
	 * @param array $oldField
	 * @param array $newField
	 * @return bool
	 */
	protected function isAutoIncrementEqual( array $oldField, array $newField )
	{
		$oldAutoIncrement = isset($oldField['autoincrement']) ? $oldField['autoincrement'] : null;
		$newAutoIncrement = isset($newField['autoincrement']) ? $newField['autoincrement'] : null;
		return $oldAutoIncrement == $newAutoIncrement;
	}
	
	/**
	 * Provides the errors that occur while generating the DB.
	 *
	 * @return string[] List of error messages.
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Updates the smart_config table with the name of an installed patch.
	 *
	 * @param string $patchName The name of the patch
	 */
	public function insertPatchInfo( $patchName )
	{
		$this->txt .= 'INSERT INTO '.$this->quotefields('smart_config').' ('.$this->quotefields('name').', '.
			$this->quotefields('value') .') '.'VALUES ('."'$patchName'".', '."'yes'".')'.
			$this->closeline()."\r\n";
	}

	/**
	 * After an existing table is upgraded this method can be used for final steps e.g. some cleaning up or closing
	 * statements.
	 *
	 * @param array $table Table definition.
	 */
	public function postTableUpgrade( $table )
	{
	}
}

/**
 * Mysql generator
 *
 * @package dbgen
 * @subpackage MysqlGenerator
 */

class MysqlGenerator extends StdSqlGenerator
{
	/**
	 * function to generate end of create table text plus additional stuff (sequences etc)
	 *
	 * @param $drop boolean	true means drop statements will be generated
	 */
	public function __construct($drop)
	{
		parent::__construct( $drop );
		$this->myname = "mysql";
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePost($table)
	{
		$primarykey = 'id';

		// unique keys (if any)
		if (isset($table['indexes'])) foreach ($table['indexes'] as $ix) {
			if (isset($ix["primary"]) && $ix["primary"] == true) $primarykey = $ix['fields'];
//			if (@$ix["unique"]) $this->txt .= $this->uniquekey($ix['name'], $ix['fields']);
		}

		// primary key
		$keys = $this->quotefields($primarykey);
		$this->txt .= "  PRIMARY KEY ($keys)\r\n) DEFAULT CHARSET=utf8".$this->closeline()."\r\n";
	}

	/**
	 * function to change an existing field definition
	 *
	 * @param array $table table info.
	 * @param array $oldfld field info as in the old/source database.
	 * @param array $newfld field info as in the new/target database.
	 */
	protected function doAlterField( $table, $oldfld, $newfld )
	{
		//$newfldname = $this->quotefields( $newfld['name'] );
		$oldfldname = $this->quotefields( empty($oldfld['name']) ? $newfld['name'] : $oldfld['name'] );
		$tablename = $this->quotefields($table['name']);

		$this->txt .= "ALTER TABLE $tablename CHANGE $oldfldname ";
		$this->field( $table, $newfld, false );
		$this->txt .= ";\r\n";
	}

	/**
	 * function to change an existing index definition. If primary key is changed
	 * it is expected that the new key will also be the new primary key.
	 * The change of the index is done in one step. The reason is that if it is
	 * done in two steps (first the drop of the index and then adding the new)
	 * this can result in errors if the fields of the index are used in foreign keys
	 * or the primary key is referred to.
	 *
	 * @param array $table DB table info.
	 * @param array $oldIndex DB index info as in the old/source database
	 * @param array $newIndex DB index info as in the new/target database
	 */
	public function alterIndex($table, $oldIndex, $newIndex)
	{
		$this->txt .= "ALTER TABLE " . $this->quotefields($table['name']) . ' ';
		if (array_key_exists('primary', $oldIndex) && $oldIndex['primary'] == true) {

			if( $table['fields'] ) foreach( $table['fields'] as $fields ) {
				if( $oldIndex['name'] == 'primary' ) { // When primary key index is not user-defined, it is by default set to 'id', the auto-increment field (which for index, by default becomes 'primary').
					if( $fields['name'] == 'id' ) { // Found the primary DB field definition.
						if( array_key_exists( 'drops', $fields )) { // Is the primary DB field going to be dropped?

							// When the primary DB field is going to be dropped, certain adjustment need to be done for
							// the primary index:
							// When a new field is added and set as primary key, Mysql will do the following:
							// 1) The new field will be added.
							// 2) An index (a primary key) is added for this field.
							// And so, when we drop the field that is set as primary key, the following two will happen:
							// 1) The field is dropped.
							// 2) The primary key is dropped from the index.
							// In our DB script, we have to make sure that the sql script can drop the field and also
							// the primary index, otherwise we will get into sql error.
							// In other words, we cannot first drop the field, then tries to drop the index or rename the
							// index in two separate sql, nor can we drop the index or rename the index first then only
							// drop the field (mysql will complain that the primary index is missing!)
							// In order to remove or rename the field smoothly, we first need to unset the to-be-dropped
							// field as a non-primary key field, change the old index primary to the new one, then only
							// drop the field (which is by then no longer a primary-key field).

							// Unset the primary field to-be-dropped as not primary field.
							$this->txt .= 'CHANGE '. $this->quotefields( 'id' ) . ' '.
								$this->quotefields( 'id' ) . ' '.$fields['drops']['type'].' NOT NULL, '; // Primary key is always 'NOT NULL'
						}
					}
				}


			}
			// Then only change the primary index.
			$this->txt .= 'DROP PRIMARY KEY, ADD PRIMARY KEY (' .
			 			  $this->quotefields($newIndex['fields']) . ')';
		} else {
			$this->txt .= 'DROP INDEX '  . $this->quotefields($oldIndex['name']) . ', ADD ';
					if (array_key_exists('unique', $newIndex) && $newIndex['unique'] == true) {
						$this->txt .= 'UNIQUE ';
					}
			$this->txt .= 'INDEX ' . $this->quotefields($newIndex['name']) . ' (' .
				 		  $this->quotefields($newIndex['fields']) . ') ';
		}

		$this->txt .= $this->closeline(). "\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function dropIndex( $index, $table )
	{
		$this->txt .= 'DROP INDEX ' . $this->quotefields($index['name']) . ' ON ' . $this->quotefields($table['name']) . ' ';
		$this->txt .= $this->closeline(). "\r\n";		
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReservedFieldName( $fieldName )
	{
		return false; // all names are allowed as long as they are quoted
	}
}

/**
 * Mssql generator
 *
 * @package dbgen
 * @subpackage MssqlGenerator
 */

class MssqlGenerator extends StdSqlGenerator
{
	protected $thistableident;
	protected $thistableinserts;
	private $procInserted;

	public function __construct($drop)
	{
		parent::__construct( $drop );
		$this->myname = "mssql";
	}

	/**
	 * Qoutes table names and column names to the Mssql convention ( [<$fld>].
	 *
	 * @param string $fld
	 * @return string
	 */
	public function quotefields($fld)
	{
		$ret = '';
		$arr = explode(',', $fld);
		$komma = '';
		foreach ($arr as $ix) {
			$ret .= $komma.'['.trim($ix).']';
			$komma = ', ';
		}
		return $ret;
	}

	protected function normtype($line)
	{
		$line = preg_replace('/(medium)*int\([ 0-9]*\)/i', 'int', $line); // this will transform "mediumint(9) to int", "tinyint(1) to tinyint".
		$line = preg_replace('/timestamp\([ 0-9]*\)/i', 'timestamp', $line);
		$line = preg_replace('/unsigned/i', '', $line);
		$line = preg_replace('/longblob/i', "image", $line);
		$line = preg_replace('/(medium)*blob/i', "text", $line);
		$line = preg_replace('/double/i', 'real', $line);
		$line = preg_replace('/bigint\([0-9]*\)/i', 'bigint', $line);

		return $line;
	}

	protected function autoincrement()
	{
		$this->thistableident = true;
		return "IDENTITY(1,1)";
	}
	protected function uniquekey($name, $fields)
	{
		return " constraint ".$this->quotefields($name)." unique (".$this->quotefields($fields)."),\r\n";
	}

	protected function dropexists()
	{
		return "";
	}

	/**
	 * Based on the 'default' property a default value is returned.
	 *
	 * @param array $field DB field properties as defined in DB model.
	 * @return string SQL fragment.
	 */
	protected function setDefault( $field )
	{
		$default = '';

		if( isset($field['default'])) {
			if ( is_string( $field['default'])) {
				if (strlen($field['default']) > 0 ) {
					$default = ' default '.$field['default'];
				} elseif ( $field['type'] == 'blob' || $field['type'] == 'mediumblob' ) {
					$default = " default ''";
					// If no default is set mssql throws an error if an record is inserted
					// and the blob values is not specified.
				}
			} else {
				$default = ' default '.$field['default'];
			}
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePost($table)
	{
		parent::tablePost($table);
		$this->thistableinserts = 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function insertpost($table)
	{
		if ($this->thistableident && $this->thistableinserts) {
			$this->txt .= "SET IDENTITY_INSERT ".$this->quotefields($table["name"])." OFF\r\n";
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert($line, $table, $auto = true)
	{
		if ($this->thistableident && !$this->thistableinserts) {
			$this->txt .= "SET IDENTITY_INSERT ".$this->quotefields($table["name"])." ON\r\n";
			$this->thistableinserts += 1;
		}

		$auto = true;
		// handle hex strings (blob in namedqueries)
		$r = array();
		while (preg_match("/0x([0-9a-f]*)/i", $line, $r) > 0) {
			$hex = pack("H*", $r[1]);
			$hex = str_replace("'", "''", $hex);
			$line = preg_replace("/0x".$r[1]."/i", "'$hex'", $line);
			$auto = false;
		}

		parent::insert($line, $table, $auto);
	}

	/**
	 * {@inheritDoc}
	 */
	public function tablePre($table)
	{
		$this->thistableident = false;
		parent::tablePre($table);
	}

	/**
	 * function to generate text for drop if exists constructs
	 *
	 * @param string $name DB table name.
	 * @return string	SQL fragment.
	 */
	public function dropTable($name)
	{
		return "if exists (select 1 from sysobjects where name = '$name') drop table ".$this->quotefields($name) . $this->closeline() . "\r\n";
	}
	/**
	 * function to generate alter table text
	 *
	 * @param array $table DB table info.
	 */
	public function alterPre($table)
	{
		$this->txt .= "ALTER TABLE ".$this->quotefields($table["name"])." ADD \r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function addField($table, $field)
	{
		$this->field( $table, $field, true );
	}

	/**
	 * function to change an existing field definition
	 *
	 * IMPORTANT: The rules of modifying existing columns in MS SQL Server are different from what either Oracle
	 * or DB2 would allow you to do. For example, you cannot change the data type of a column on which a constraint is
	 * based or give it a default value. You also are unable to alter columns with TEXT, NTEXT, IMAGE, or TIMESTAMP data
	 * types. Some more restrictions apply; see vendor's documentation for details.
	 *
	 * @param array $table DB table info.
	 * @param array $oldfld DB field info as in the old/source database.
	 * @param array $newfld DB field info as in the new/target database.
	 */
	protected function doAlterField( $table, $oldfld, $newfld )
	{
		$newfldname = $this->quotefields( $newfld['name'] );
		$oldfldname = $this->quotefields( $oldfld['name'] );
		$tablename = $table['name'];

		// Don't do anything if field's name, type, default and nullable have not been changed.
		// This can happen if the mssql types are the same but the mysql types not.
		if( $this->isNameEqual( $oldfld['name'], $newfld['name'] ) &&
			$this->isTypeEqual( $oldfld['type'], $newfld['type'] ) &&
			$this->isDefaultEqual( $oldfld, $newfld ) && 
			$this->isNullableEqual( $oldfld, $newfld ) ) {
			return;
		}

		// Drop constraint, or else you risk errors such as: ALTER TABLE ALTER COLUMN value failed because one or more objects access this column.(MSSQL: 4922)
		$this->dropFieldConstraint( $table['name'], $oldfld['name'] );
		
		// Alter column when field's type or nullable have changed. (The field's default is added later!)
		if( !$this->isTypeEqual( $oldfld['type'], $newfld['type'] ) ||
			!$this->isNullableEqual( $oldfld, $newfld ) ) {
			// if column allows NULL initially, need to set all column values having NULL to '' 
			// before changing the column to NOT NULL
			if( !$this->isNullableEqual( $oldfld, $newfld ) ) {
				$this->txt .= "UPDATE $tablename SET $oldfldname = '' WHERE $oldfldname is null;";
				$this->txt .= "\r\n";
				
				// check if the field is an index field
				$reAddIndexes = array();
				if( isset($table['indexes']) ){
					foreach( $table['indexes'] as $index ){
						if ( $this->fieldUsedInCurrentIndex( $index, $newfld)) {
							$reAddIndexes[] = $index; // keep track which index we drop now,we need to add it back later 
							$this->dropIndex( $index, $table);
						}	
					}
				}
			}

			if( $this->isNameEqual( $oldfld['name'], $newfld['name'] ) ) {
				$this->txt .= "ALTER TABLE $tablename ALTER COLUMN ";
				$this->field( $table, $newfld, false, false ); // Note: alter column with default value is unsupported for MSSQL
				$this->txt .= ";\r\n";
			}

			if( isset($reAddIndexes) ) foreach( $reAddIndexes as $reAddIndex ){
				// re-adding back the index after setting field from NULL to Not NULL
				$this->index( $reAddIndex, $table );
			}
		}

		// Rename column when field name has changed
		if( !$this->isNameEqual( $oldfld['name'], $newfld['name'] ) ) {
			$this->txt .= "EXECUTE sp_rename '" . "$tablename." . $oldfld['name'] . "', '" . $newfld['name'] . "', 'COLUMN';\r\n";
		}

		// Restore the constraint
		if( !isset($newfld['autoincrement']) ) { // Add default for non autoincrement field
			$default = !isset($newfld['default']) ? "''" : $newfld['default'];
			$this->txt .= 'ALTER TABLE '. $this->quotefields($tablename) .' ADD DEFAULT ('.$default.') FOR '.$newfldname.';'."\r\n";
		}
	}

	/**
	 * Checks if a field is used in the index. To check this the version of the index must be taken into account.
	 * If the field is used an index with an equal/higher version as the field itself the index is not taken into
	 * account. Suppose a field is added in version 800 and the index using that field is also added in version 800.
	 * In that case the field is not yet used as the index is not yet created (indexes are created after the field
	 * definitions are handled). In that case we check on the 'alters' attribute to see if there is maybe an older
	 * definition that also uses the field.
	 *
	 * @param array $index (including the older definitions)
	 * @param array $field (definition of the field)
	 * @return mixed null|array Null if field is not used else the index definition
	 */
	protected function fieldUsedInCurrentIndex( $index, $field )
	{
		$result = null;
		$currentIndex = null;
		
		if( version_compare( $index['v'], $field['v'], '<' ) ) {
			$currentIndex = $index;
		} elseif ( isset ( $index['alters'] )) {
 		   // Check if the field is used in the previous definition which is the current one as the new one is not yet
			// current. Indexes are handled after the fields.
			if( version_compare( $index['alters']['v'], $field['v'], '<' ) ) {
				$currentIndex = $index['alters'];
			}	
		}

		if ( $currentIndex ) {
			$indexFields = isset($currentIndex['fields']) ? preg_split('/\,/', $currentIndex['fields']) : null;
			if( $indexFields ) foreach( $indexFields as $indexField ){
				if( trim($indexField) == $field['name'] ){ // if yes, we need to drop the index field before we can switch it to 'NOT NULL"
					$result = $currentIndex;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Checks if the given field ( parameter $fieldName) is a primary key index.
	 *
	 * @param array $index Index definitions.
	 * @param string $fieldName The field name to be checked if it is a primary key index.
	 * @return bool
	 */
	private static function isIndexPrimary( $index, $fieldName )
	{
		if( array_key_exists('fields',$index) ) {
			if( $index['fields'] == $fieldName ) {
				return array_key_exists('primary',$index) && $index['primary'];
			}
			if( array_key_exists('alters',$index) && $index['alters'] ) {
				return self::isIndexPrimary( $index['alters'], $fieldName );
			}
		}
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function dropField($table, $fld)
	{
		// When the index is an altered primary, we already have dropped the constraint in the alterIndex().
		// By calling dropFieldConstraint() again, would try to drop it again, which needs to be avoided here.
		// For example, this happened when altering the id field into jobid for the smart_serverjobs table.
		$isAlteredPrimaryIndex = false;
		foreach( $table['indexes'] as $index ) {
			if( array_key_exists('alters',$index) && $index['alters'] ) {
				if( $this->isIndexPrimary( $index['alters'], $fld['name'] ) ) {
					$isAlteredPrimaryIndex = true;
					break; // found
				}
			}
		}
		if( !$isAlteredPrimaryIndex ) { // When the primary index is changed, the 'drop constraint' is taken care of in alterIndex(), so don't include here.
			$this->dropFieldConstraint($table['name'], $fld['name']);
		}
		$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) . ' DROP COLUMN ' .
					  $this->quotefields($fld['name']) .$this->closeline()."\r\n";
	}

	/**
	 * Function to change an existing index definition. In mssql the primary key
	 * cannot be dropped without knowing the exact name of the primary key constraint.
	 * For this reason the alter index of a primary key is not supported for mssql.
	 * Syntax depends on the fact if an index is unique or not.
	 *
	 * @param array $table table info
	 * @param array $oldIndex index info as in the old/source database
	 * @param array $newIndex info as in the new/target database
	 */
	public function alterIndex($table, $oldIndex, $newIndex)
	{

		if ( (array_key_exists('primary', $oldIndex) && $oldIndex['primary'] == true) &&
			 (array_key_exists('primary', $newIndex) && $newIndex['primary'] == true)) {
				$this->storeProcedure_ChangePrimaryIndex( $table['name'] );
		} else {
			$this->dropIndex($oldIndex, $table);
		}

		$this->index($newIndex, $table, true);

	}

	/**
	 * Function to drop indexes. If index is 'unique' the index is dropped either as a
	 * 'constrain' otherwise just a 'drop index' is done. This depends on the way the index
	 * was created. If created as constrain a 'drop constrain' is done, otherwise a
	 * 'drop index'. Which of the two is applicable is determined at runtime.
	 *
	 * @param array $index index info.
	 * @param array $table tabel info.
	 */
	public function dropIndex($index, $table)
	{

		if (array_key_exists('primary', $index) && $index['primary'] == true) {
			$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) .
					 	  ' DROP CONSTRAINT ' . $this->quotefields($index['name']) . ' ';
		}
		elseif (array_key_exists('unique', $index) && $index['unique'] == true) {
			$indexname = $index['name'];
			$this->txt .= "IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'" . $this->quotefields($table['name']) .
										"') AND is_unique_constraint = 1 AND name = N" . "'$indexname'" . ' ) ' .
						  'ALTER TABLE '. $this->quotefields($table['name']) . ' DROP CONSTRAINT ' . $this->quotefields($index['name']) . ' ' ;
			$this->txt .= $this->closeline(). "\r\n";
			$this->txt .= "IF  EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID(N'" . $this->quotefields($table['name']) . "')
										AND is_unique_constraint = 0 AND name = N" . "'$indexname'" . ' ) ' .
						  'DROP INDEX ' . $this->quotefields($table['name']) . '.' . $this->quotefields($index['name']) . ' ';
		}
		else { //Non-unique
			$this->txt .= 'DROP INDEX ' . $this->quotefields($table['name']) .
						  '.' . $this->quotefields($index['name']) . ' ';
		}
		$this->txt .= $this->closeline(). "\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function index( $index, $table, $alter = false )
	{
		// Handle adding primary indexes (alter).
		if ( isset($index['primary']) && $index['primary'] == true ) {
			if ( $alter ) {
				$this->txt .= 'ALTER TABLE ' . $this->quotefields( $table['name'] ) .
					' ADD PRIMARY KEY (' . $this->quotefields( $index['fields'] ) . ')' . $this->closeline() . "\r\n";
				return;
			} else { // Create
				return; // Primary indexes are already added by the create statement of the table.
			}
		}

		// Handle adding unique indexes (alter).
		if ( $alter && isset($index['unique']) && $index['unique'] == true ) {
			$this->txt .= 'ALTER TABLE ' . $this->quotefields($table['name']) . ' ADD CONSTRAINT ' .
							$this->quotefields($index['name']) . ' UNIQUE ' .
							' (' . $this->quotefields($index['fields']) . ') ';
			$this->txt .= $this->closeline(). "\r\n";
			return;
		}

		// Handle adding normal indexes (alter/create) and unique indexes (create).
		$unique = '';
		if (isset($index['unique']) && $index['unique'] == true ) {
			$unique = 'UNIQUE';
		}
		$name = '';
		if (isset($index["name"])) {
			$name = $index["name"];
		}

		if (!$name && isset($index[$this->myname."name"])) {
			$name = $index[$this->myname."name"];
		}

		if (!$name) return;

		$type = '';
		if (isset($index[$this->myname."type"])) {
			$type = $index[$this->myname."type"];
		}

		// Remove length constraint in case of Oracle or Mssql. So 'documentid(200)'
		// will be changed into 'documentid'.
		$indexfields = explode(',', $index['fields']);
		foreach ($indexfields as &$indexfield) {
			$posBracket = strpos($indexfield, '(', 1);
			if (is_integer($posBracket)) { // bracket found
				$column = substr($indexfield, 0, ($posBracket));
				$indexfield = $column;
			}
		}
		$index['fields'] = implode(',', $indexfields);

		$this->txt .= "CREATE $unique INDEX ".$this->quotefields($name)." on ".$this->quotefields($table["name"])."(";
		$this->txt .= $this->quotefields($index['fields']);
		$this->txt .= ") $type".$this->closeline()."\r\n";
	}

	/**
	 * {@inheritDoc}
	 */
	public function upgradePre()
	{
		$this->procInserted = false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function upgradePost()
	{
		if( $this->procInserted === true ) {
			$this->txt .= 'DROP PROCEDURE [dbo].[SCE_GetConstraintName];'."\r\n";
			$this->procInserted = false;
		}
	}

	/**
	 * This function executes a stored procedure to retrieve constrains on a
	 * table field. Next the constrain is dropped. If the procedure is not
	 * yet created, it is created first.
	 *
	 * @param string quoted tablename $tablename
	 * @param string quoted fieldname $fldname
	 */
	public function dropFieldConstraint($tablename, $fldname)
	{
		$this->storedProcedure_GetConstraintName(); // make sure our stored procedure exists
		$this->txt .= 'DECLARE @return_value int, @constraintName sysname, @sql nvarchar(1024)'."\r\n";
		$this->txt .= 'EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = \''. $tablename . '\', @columnName = \'' . $fldname . '\', @constraintName = @constraintName OUTPUT'."\r\n";
		$this->txt .= 'SET @sql = \'ALTER TABLE '. $tablename . ' DROP CONSTRAINT \' + @constraintName' . "\r\n";
		$this->txt .= 'EXEC (@sql)' . $this->closeline() . "\r\n";
	}

	/**
	 * This function checks if the the value of autoincrement is the same of two
	 * table fields. Typical this function can be used to check if the autoincrement value
	 * has been changed between the old definition and the new definition of a
	 * field.
	 *
	 * @param array $field1 DB field info (as in the old/source database)
	 * @param array $field2 DB field info (as in the new/target database)
	 * @return bool autoincrement value is the same of two fields
	 */
	private function autoincrementIsSame($field1, $field2)
	{
		$result = true;
		$field1Autoincrement = false;
		$field2Autoincrement = false;

		if (array_key_exists('autoincrement', $field1)) {
			$field1Autoincrement = $field1['autoincrement'];
		}
		if (array_key_exists('autoincrement', $field2)) {
			$field2Autoincrement = $field2['autoincrement'];
		}
		if ($field2Autoincrement <> $field1Autoincrement) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Creates a stored procedure that can find the name of the constraint for any field any table.
	 *
	 * When we set a default value for a field, it also creates a DEFAULT-type constraint for that column.
	 * The constraint is named with 'DF__' as a prefix. In order to use DROP/ALTER COLUMN, the constraint bound to
	 * that column has to be dropped first with this syntax.
	 *   ALTER TABLE table_name DROP CONTRAINT constraint_name
	 * Since we need to supply the exact contraint name to the code above, we have to find constraint name in
	 * the sysobjects table. Unfortunately, we can't use EXEC sp_unbindefault table_name.column_name in order
	 * to drop the default constraint. Then you would need to query the sysobjects table to find the default constraint.
	 */
	private function storedProcedure_GetConstraintName()
	{
	 	// See for good solution: http://www.thescripts.com/forum/thread80097.html
		if( $this->procInserted === false ) {
			$this->txt .=
				'CREATE PROCEDURE [dbo].[SCE_GetConstraintName] ( @tablename sysname, @columnName sysname, @constraintName sysname OUTPUT ) AS'."\r\n".
				'SELECT @constraintName = o1.name FROM sysobjects o1'."\r\n".
				'INNER JOIN sysobjects o2 ON o1.parent_obj = o2.id'."\r\n".
				"INNER JOIN syscolumns c ON (o1.id = c.cdefault) OR (c.id = o2.id and c.cdefault = 0 and o1.xtype = 'PK')"."\r\n".
				'WHERE (o2.name = @tablename) AND (c.name = @columnName);'."\r\n";
			$this->procInserted = true;
		}
	}

	/**
	 * Creates a stored procedure that will drop the old primary key and adds the new one.
	 *
	 * To drop a primary key in MSSQL, the primary key constraint needs to be known;
	 * therefore a procedure is needed to retrieve the constraint name before dropping the index.
	 *
	 * The procedure returned by this function will do the following:
	 * - Gets the old primary index constraint name
	 * - Drops the old primary index constraint name retrieved. ( Dropping the primary key will be done later, so not
	 *     covered in this procedure. )
	 * - Adds the new primary index with the constraint name retrieved.
	 *
	 * @param string $tableName Table name of which the primary index is to be altered.
	 * @param string $oldPrimaryIndex The old primary key index to be dropped.
	 * @param string $newPrimaryIndex The new primary key index to be added.
	 */
	private function storedProcedure_DropPrimaryIndex( $tableName, $oldPrimaryIndex, $newPrimaryIndex )
	{
		$this->storedProcedure_GetConstraintName(); // make sure our stored procedure exists
		$this->txt .=
			'DECLARE @return_value int, @constraintName sysname, @sql1 nvarchar(1024), @sql2 nvarchar(1024)' . "\r\n" .
			'EXEC @return_value = [dbo].[SCE_GetConstraintName] @tablename = \''.$tableName.'\', '.
					'@columnName = \''.$oldPrimaryIndex.'\', @constraintName = @constraintName OUTPUT' . "\r\n" .
			'SET @sql1 = \'ALTER TABLE '.$tableName.' DROP CONSTRAINT \' + @constraintName' . "\r\n" .
			'SET @sql2 = \'ALTER TABLE '.$tableName.' ADD CONSTRAINT \' + @constraintName + \' PRIMARY KEY ('.$newPrimaryIndex.')\'' . "\r\n".
			'EXEC (@sql1)' . "\r\n".
			'EXEC (@sql2);' . "\r\n";
	}

	/**
	 * Creates a procedure to change the primary index of a table.
	 *
	 * @param string $tableName The name of the table (including prefix).
	 */
	private function storeProcedure_ChangePrimaryIndex( $tableName )
	{
		$this->txt .=
		'DECLARE @SQL1 VARCHAR(4000) SET @SQL1 = \'ALTER TABLE '.$tableName.' DROP CONSTRAINT |ConstraintName|\''. "\r\n" .
		'SET @SQL1 = REPLACE(@SQL1, \'|ConstraintName|\', ( SELECT name FROM sysobjects WHERE xtype = \'PK\' AND parent_obj = OBJECT_ID(\''.$tableName.'\')))'. "\r\n" .
		'EXEC (@SQL1);'. "\r\n";
	}

	/**
	 * Under certain conditions a table must not be added to the 'update identity'
	 * script. This function checks if a table must added depending on the version
	 * which is updated.
	 *
	 * @param string $oldversion Version for which the identity script is generated.
	 * 							 E.g. '4.1' or '4.2'
	 * @param array $table DB table info.
	 * @return bool Whether or not the table must be added to the 'update identity' script.
	 */
	public function addToIdentScript($oldversion, $table)
	{
		$result = false;

		switch ($oldversion) {
			case '4.1':
				// Prevent loss of custom fields
				if (!($table["name"]== "smart_objects") && !($table["name"] == "smart_deletedobjects")) {
					$result = true;
				}
				if( version_compare( $table['v'], $oldversion , '>' ) ) {
					$result = false; // From version 4.2 onwards the identity is set
				}
				break;
			case '4.2':
				// Only the identity of mtpsentobjects is changed.
				if ($table["name"]== "smart_mtpsentobjects") {
					$result = true;
				}
				break;
			default:
				break;
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function addnull()
	{
		return " null ";
	}

	/**
	 * {@inheritDoc}
	 */
	public function isReservedFieldName( $fieldName )
	{
		return false; // TODO
	}
}

