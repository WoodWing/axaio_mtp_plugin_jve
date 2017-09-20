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

require_once BASEDIR.'/server/dbscripts/dbupgrades/ObjectConverter.class.php';

class WW_DbScripts_DbUpgrades_Spreadsheets extends WW_DbScripts_DbUpgrades_ObjectConverter
{
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
	 * @return bool Whether or not the updates were successful.
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
		
		return $this->convertObjectType( $spreadSheetFileFormats, 'Article', 'Spreadsheet' );
	}

	public function introduced()
	{
		return '8.0';
	}
}
