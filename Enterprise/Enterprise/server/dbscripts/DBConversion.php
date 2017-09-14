<?php
/**
 * Class containing data conversion rules for upgrading databases. This script
 * changes heavily between versions. If an example is needed how to solve a data
 * conversion also have look at previous versions. These versions maybe contain
 * code fragments that can be reused.
 * 
 * FOR DEVELOPERS ONLY!
 * 
 * @package 	Enterprise
 * @subpackage 	DBScripts
 * @since 		v6.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
class DBConversion
{
	/** @var StdSqlGenerator $dbGenerator */
	private static $dbGenerator;

	/** @var string $previousVersion in 'major.minor' notation */
	private static $previousVersion;

	/** @var string $lastVersion in 'major.minor' notation */
	private static $lastVersion;
	
	/**
	 * Determines if there is a need for a DB conversion between two given Enterprise DB versions.
	 * If needed, a DB conversion scripts is added to the given DB generator (self::$dbGenerator).
	 * Whether or not a scripts was added can be requested through self::$dbGenerator->materialize().
	 *
	 * @param StdSqlGenerator $dbGenerator
	 * @param string $mode 'pre' or 'post'
	 * @param string $previousVersion in 'major.minor' notation
	 * @param string $lastVersion in 'major.minor' notation
	 */
	public static function generateDBConvScripts( StdSqlGenerator $dbGenerator, $mode, $previousVersion, $lastVersion )
	{
		self::$dbGenerator = $dbGenerator;
		self::$previousVersion = $previousVersion;
		self::$lastVersion = $lastVersion;
		
		if( $mode == 'post') {
			self::removeProfileFeatures();
			self::changePublicationChannelTypes();
			self::setObjectRelationParentType();
		}
		if( $mode == 'pre') {
			self::dropIndexOnIndesignServerJobs();
		}
	}

	/**
	 * This function does the opposite of insertProfileFeatures().
	 * It creates DB SQL for all DB flavors(MYSQL,MSSQL,ORACLE).
	 * It is typically used when a profile feature that was supported in 
	 * previous version is no longer supported(used) in v8.
	 * 
	 * The SQL created will remove the obsoleted profile features
	 * from the DB when db migration script is executed.
	 */	
	private static function removeProfileFeatures()
	{
      $toBeRemovedFeatures = array();
		if( version_compare( self::$previousVersion, '9.5', '<' ) && version_compare( self::$lastVersion, '9.5', '>=' ) ) {
			$toBeRemovedFeatures[] = '1006'; // Web Editor no longer exist, remove this access rights for Web Editor profile feature
		}

		if (isset($toBeRemovedFeatures)) foreach( $toBeRemovedFeatures as $toBeRemovedFeature ) {
			self::$dbGenerator->addTxt(
				"DELETE FROM " . self::$dbGenerator->quotefields("smart_profilefeatures") . 
				" WHERE " . self::$dbGenerator->quotefields("feature") . " = " . $toBeRemovedFeature . ";\r\n\r\n"
			);
		}
	}

	/**
	 * This function updates the publication channel types. For v8 the
	 * 'digital magazine' channel type is not supported anymore and this is
	 * changed into the type 'other'.
	 * For v9 the 'newsfeed' channel type isn't supported anymore.
	 */
	private static function changePublicationChannelTypes()
	{
		$changeChannelTypes = array();
		if( version_compare( self::$previousVersion, '9.0', '<' ) && version_compare( self::$lastVersion, '9.0', '>=' ) ) {
			$changeChannelTypes['newsfeed'] = 'other';
		}

		if (isset($changeChannelTypes)) foreach ( $changeChannelTypes as $from => $to ) {
			self::$dbGenerator->addTxt( "UPDATE " . self::$dbGenerator->quotefields("smart_channels") .
				" SET " . self::$dbGenerator->quotefields("type") . " = '" . $to . "'" .
				" WHERE " . self::$dbGenerator->quotefields("type") . " = '" . $from . "';\r\n\r\n" );
		}
	}

	/**
	 * This function fills in the ObjectRelations ParentType added since 9.0.0.
	 *
	 * This update is needed when upgrading from < 900 to 900 or greater to fill in the ObjectRelations parenttype
	 * column which otherwise would remain empty.
	 *
	 * @return void
	 */
	private static function setObjectRelationParentType()
	{
		if( version_compare( self::$previousVersion, '9.0', '<' ) && version_compare( self::$lastVersion, '9.0', '>=' ) ) {
			$objectTypes = array('Article','ArticleTemplate','Layout','LayoutTemplate','Image','Advert','AdvertTemplate',
			                     'Plan','Audio','Video','Library','Dossier','DossierTemplate','LayoutModule',
			                     'LayoutModuleTemplate','Task','Hyperlink','Spreadsheet','Other','PublishForm',
			                     'PublishFormTemplate');
			$tables = array ('smart_objects', 'smart_deletedobjects');

			// Go through all the Object Types and update the ParentType column for those matched by the smart_objects
			// or smart_deleted objects type.
			foreach ($tables as $table) {
				foreach ($objectTypes as $type) {
					self::$dbGenerator->addTxt("UPDATE " . self::$dbGenerator->quotefields('smart_objectrelations') .
					" SET " . self::$dbGenerator->quotefields('parenttype') . " = '" . $type . "'" .
					" WHERE " . self::$dbGenerator->quotefields('parent') . " IN ( SELECT " .
					self::$dbGenerator->quotefields('id') . " FROM " . self::$dbGenerator->quotefields($table) .
					" WHERE " . self::$dbGenerator->quotefields('type') . " = '" . $type . "');\r\n\r\n");
				}
			}
		}
	}

	/**
	 * Adds the drop index on the objid column of the smart_indesignserverjobs table. This statement
	 * is needed as long as upgrades of version 8.3.4 till 8.9.9 are supported.
	 */
	private static function dropIndexOnIndesignServerJobs()
	{
		// Temporary remove database changes introduced in version 8.3.4 to prevent 'already exists' error later on.
		// During patch 7.6.2 database changes are introduced. See BZ#34633. Extra scripts are needed
		// to prevent sql errors during upgrade. These scripts can be removed the moment upgrading from version
		// 8.3.4 - 8.9.9 is not anymore supported.
		if( version_compare( self::$previousVersion, '8.0', '>=' ) && version_compare( self::$previousVersion, '9.0', '<' ) ) {
			self::$dbGenerator->dropIndex(
				array(
					'v' => '8.0',
					'name' => 'objid_indesignserverjobs',
					'fields' => 'objid'
				),
				array( 'name' => 'smart_indesignserverjobs' )
			);
		}
	}
}