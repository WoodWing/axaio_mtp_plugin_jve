<?php
/**
 * @package    	Enterprise
 * @subpackage HealthCheck2
 * @since       v7.6.13
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * 
 * The C_ARTICLE_ACCESS field is introduced since 7.6.13, 8.3.1 and 9.1 and was formally called C_PROTECT.
 * During database conversion, 0 or empty values are converted to 'Metered' and 1 is converted to 'Protected'.
 * For the new fields, possible values are Metered, Protected and Free. The default value (Metered).
 * This new field is only applicable for supported object type (Dossier) which is also the case formally.
 * Empty or 0 value means it is not applicable for the object type.
 */
class AddArticleAccessProp
{
	const UPDATEFLAG = 'adobedps_article_access_conversion';

	/**
	 * Add the new field C_ARTICLE_ACCESS when it is not yet added.
	 *
	 * It first checks if the new field has been added.
	 * When the field is already added, the function bails out and does nothing.
	 * When the field is not yet added, the function does the following:
	 * - Add the new field into smart_objects and smart_deletedobjects table.
	 * - Map the old value of C_PROTECT into C_ARTICLE_ACCESS where false is mapped into Metered and true is mapped into Protected.
	 * - The dialog setup for the old value is also mapped to the new field accordingly.
	 * - Delete the old field from smart_objects, smart_deletedobjects, smart_actionproperties and smart_properties table.
	 * - Update the smart_config table to flag that the field has been added.
	 *
	 * @return bool True when the field has been successfully added; False otherwise.
	 */
	static public function doAddArticleAccessProp()
	{
		if( self::isUpdated() ) {
			return true;
		}

		$result = true;
		self::setAddArticleAccessPropFlag( '0' ); // To indicate that the conversion process has started.

		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		do {
			// Introduce the new field
			try {
				BizCustomField::insertFieldAtModel( 'objects', 'C_ARTICLE_ACCESS', 'list' );
			} catch ( BizException $e ) {
				$result = false;
				break;
			}

			// Convert the value: Default Value: 'Protected' when the default value of the PROTECTED property was set to true otherwise 'Metered'
			// convert in smart_objects and smart_deletedobjects
			// The Dialog Setup should be updated.
			if( !self::mapFromOldToNewPropName() ) {
				$result = false;
				break;
			}

			// Remove the old PROTECT field.
			try {
				BizCustomField::deleteFieldAtModel( 'objects', 'C_PROTECT' );
			} catch( BizException $e ) {
				$result = false;
				break;
			}

			self::setAddArticleAccessPropFlag( '1' ); // To indicate that the conversion process has completed successfully.
		} while( false );

		return $result;
	}

	/**
	 * Stores a variable in the database to denote if the conversion has been started
	 * or has been completed.
	 *
	 * @param string $flag
	 * @return bool Whether or not the conversion flag was set correctly.
	 */
	static private function setAddArticleAccessPropFlag( $flag )
	{
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::UPDATEFLAG , $flag );
	}			

	/**
	 * Checks if 'adobedps_article_access_conversion' flag already exists in smart_config table.
	 * When the flag exists, meaning the conversion has been taken place, hence returns
	 * True to indicate it has been updated; False otherwise.
	 *
	 * @return bool True when the update has been done; False otherwise.
	 */
	static public function isUpdated()
	{
		$isUpdated = false;
		require_once BASEDIR . '/server/dbclasses/DBConfig.class.php';
		$row = DBConfig::getRow( DBConfig::TABLENAME, 'name = ?', '*', array( self::UPDATEFLAG ) );

		if ( $row ) {
			$isUpdated = ($row['value'] == '1');
		}

		if( !$isUpdated ) { // Not updated, but does C_PROTECT prop exists? If doesn't exists, there's no conversion needed, hence it will be considered as updated.
			require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
			$where = 'name = ? ';
			$params = array( 'C_PROTECT' );
			if ( !DBBase::getRow( 'properties', $where, array('id'), $params )) {
				// No custom property PROTECT found, meaning no conversion needed.
				self::setAddArticleAccessPropFlag('1');
				LogHandler::Log( 'AdobeDps', 'INFO', 'Conversion of custom property PROTECT to ARTICLE_ACCESS is not needed.' .
					'Custom property PROTECT does not exists.' );
				$isUpdated = true; // No conversion needed, considered as updated.
			}
		}
		return $isUpdated;
	}

	/**
	 * Map the old value of C_PROTECT field into C_ARTICLE_ACCESS field.
	 *
	 * Thd old value false (in C_PROTECT) is mapped into Metered and true is mapped into Protected.
	 * The dialog setup for the old value is also mapped to the new field accordingly.
	 *
	 * @returm bool True when all the conversion/mapping are successful; False otherwise.
	 */
	static private function mapFromOldToNewPropName()
	{
		require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

		$noError = true;
		do {
			// smart_properties table
			// UPDATE `smart_properties` SET `defaultvalue` = 'Protected' WHERE `name` = 'C_PROTECT' AND `defaultvalue` = '1'
			$values = array( 'defaultvalue' => 'Protected' );
			$where = '`name` = ? AND `defaultvalue` = ? ';
			$params = array( 'C_PROTECT', '1' );
			if( !DBBase::updateRow( 'properties', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

			// UPDATE `smart_properties` SET `defaultvalue` = 'Metered' WHERE `name` = 'C_PROTECT' AND ( `defaultvalue` = '0' OR `defaultvalue` = '' )
			$values = array( 'defaultvalue' => 'Metered' );
			$where = '`name` = ? AND ( `defaultvalue` = ? OR `defaultvalue` = ? ) ';
			$params = array( 'C_PROTECT', '0', '' );
			if( !DBBase::updateRow( 'properties', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

			// UPDATE `smart_properties` SET `name` = 'C_ARTICLE_ACCESS' , `type` = 'list' , `dispname` = 'Article Access' WHERE `name` = 'C_PROTECT'
			$values = array( 'name' => 'C_ARTICLE_ACCESS',
							 'type' => 'list',
							 'valuelist' => 'Metered,Protected,Free',
							 'dispname' => 'Article Access' );
			$where = '`name` = ? ';
			$params = array( 'C_PROTECT' );
			if( !DBBase::updateRow( 'properties', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

			// smart_object table
			// UPDATE `smart_objects` SET `C_ARTICLE_ACCESS` = 'Protected' WHERE `C_PROTECT` = 1 AND `type` = 'Dossier'
			$values = array( 'C_ARTICLE_ACCESS' => 'Protected' );
			$where = '`C_PROTECT` = ? AND `type` = ? ';
			$params = array( 1, 'Dossier' );
			if( !DBBase::updateRow( 'objects', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

			// Convert all the values that aren't equal to '1' to '0'. The IS NULL part is needed for oracle. (Emtpy strings are null).
			// UPDATE `smart_objects` SET `C_ARTICLE_ACCESS` = 'Metered' WHERE (`C_PROTECT` <> 1 OR `C_PROTECT` IS NULL) AND `type` = 'Dossier'
			$values = array( 'C_ARTICLE_ACCESS' => 'Metered' );
			$where = '(`C_PROTECT` <> ? OR `C_PROTECT` IS NULL) AND `type` = ? ';
			$params = array( 1, 'Dossier' );
			if( !DBBase::updateRow( 'objects', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

			// smart_deletedobject table
			// UPDATE `smart_deletedobjects` SET `C_ARTICLE_ACCESS` = 'Protected' WHERE `C_PROTECT` = 1 AND `type` = 'Dossier'
			$values = array( 'C_ARTICLE_ACCESS' => 'Protected' );
			$where = '`C_PROTECT` = ? AND `type` = ? ';
			$params = array( 1, 'Dossier' );
			if( !DBBase::updateRow( 'deletedobjects', $values, $where, $params ) ){
				$noError = false;
				break;
			}

			// Convert all the values that aren't equal to '1' to '0'. The IS NULL part is needed for oracle. (Emtpy strings are null).
			// UPDATE `smart_deletedobjects` SET `C_ARTICLE_ACCESS` = 'Metered' WHERE (`C_PROTECT` <> 1 OR `C_PROTECT` IS NULL) AND `type` = 'Dossier'
			$values = array( 'C_ARTICLE_ACCESS' => 'Metered' );
			$where = '(`C_PROTECT` <> ? OR `C_PROTECT` IS NULL) AND `type` = ? ';
			$params = array( 1, 'Dossier' );
			if( !DBBase::updateRow( 'deletedobjects', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

			// smart_actionproperties
			// UPDATE `smart_actionproperties` SET `property` = 'C_ARTICLE_ACCESS' WHERE `property` = 'C_PROTECT'
			$values = array( 'property' => 'C_ARTICLE_ACCESS' );
			$where = '`property` = ? ';
			$params = array( 'C_PROTECT' );
			if( !DBBase::updateRow( 'actionproperties', $values, $where, $params ) ) {
				$noError = false;
				break;
			}

		} while ( false );

		return $noError;
	}

}