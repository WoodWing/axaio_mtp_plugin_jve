<?php
/**
 * Before Enterprise 8.2, MS-Excel spreadsheets were stored as Article typed objects.
 * Since 8.2 the object type Spreadsheet is introduced. This module changes the object
 * type from Article into Spreadsheet for old objects that were created before 8.2.
 *
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v8.2.0 (this module was split from ObjectConverter class since 9.0.0)
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/dbscripts/ObjectConverter.class.php';

class DBUpgradeSpreadsheets extends ObjectConverter
{
	const NAME = 'DBUpgradeSpreadsheets';

 	/**
	 * See {@link DbUpgradeModule} class.
	 *
	 * @return string Flag name 
	 */
	protected function getUpdateFlag()
	{
		return 'dbadmin_spreadsheet_types_converted'; // Important: never change this name
	}

	/**
	 * Converts objects (in workflow and trash area) of Enterprise object type 'Article'
	 * and having the mime types of spreadsheets into Enterprise object type 'Spreadsheet'.
	 *
	 * List of spreadsheet mime types that will be converted from type 'Article' to 'Spreadsheet':
	 * - application/vnd.ms-excel
	 * - application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
	 * - application/vnd.ms-excel.sheet.macroEnabled.12
	 * - application/vnd.openxmlformats-officedocument.spreadsheetml.template
	 * - application/vnd.ms-excel.template.macroEnabled.12
	 * - application/vnd.ms-excel.sheet.binary.macroEnabled.12
	 * - application/vnd.oasis.opendocument.spreadsheet
	 * - application/vnd.oasis.opendocument.spreadsheet-template
	 * - application/x-apple-numbers
	 *
	 * @since 8.2.0
	 * @return bool Whether or not the updates were successful. When no update is needed, true is returned.
	 */
	public function run()
	{
		$spreadSheetFileFormats = array(
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel.sheet.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'application/vnd.ms-excel.template.macroEnabled.12',
			'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.oasis.opendocument.spreadsheet-template',
			'application/x-apple-numbers' );

		$result = true;
		if ( self::isConversionNeeded( $spreadSheetFileFormats ) ) {
			$result = $this->convertObjectType( $spreadSheetFileFormats, 'Article', 'Spreadsheet' );
		}
		return $result;
	}

	public function introduced()
	{
		return '800';
	}

	/**
	 * Checks if there's any conversion from object type Article to Spreadsheet is needed.
	 *
	 * Function returns True when any of the Objects ( workflow or deleted )
	 * that has spreadsheet mime types ( file format ) but still having the object type 'Article'.
	 * Otherwise, function returns False, which means no conversion needed.
	 *
	 * @since 10.1.6
	 * @param string[] $spreadSheetFileFormats
	 * @return bool
	 */
	private static function isConversionNeeded( $spreadSheetFileFormats )
	{
		require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
		$dbh = DBDriverFactory::gen();
		$spreadSheetFileFormatsInString = self::arrayToSQLString( $spreadSheetFileFormats );

		// Workflow objects.
		$tableName = $dbh->tablename( 'objects' );
		$sql = "SELECT `id` FROM $tableName WHERE `type` = ? AND `format` IN ( $spreadSheetFileFormatsInString )";
		$params = array( 'Article' );
		$sth = $dbh->query( $sql, $params );

		if ( $sth ) {
			if ( $dbh->fetch( $sth )) {
				LogHandler::Log( self::NAME, 'INFO',
					'The conversion from object type "Article" to "Spreadsheet" is needed.' );
				return true;
			}
		}

		// Deleted objects.
		$tableName = $dbh->tablename( 'deletedobjects' );
		$sql = "SELECT `id` FROM $tableName WHERE `type` = ? AND `format` IN ( $spreadSheetFileFormatsInString )";
		$params = array( 'Article' );
		$sth = $dbh->query( $sql, $params );

		if ( $sth ) {
			if ( $dbh->fetch( $sth )) {
				LogHandler::Log( self::NAME, 'INFO',
					'The conversion from object type "Article" to "Spreadsheet" is needed.' );
				return true;
			}
		}

		LogHandler::Log( self::NAME, 'INFO',
			'No objects with mime type of spreadsheet found with objec type = "Article". No conversion is needed.' );
		return false;
	}
}
