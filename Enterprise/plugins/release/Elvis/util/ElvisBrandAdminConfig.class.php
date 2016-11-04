<?php
/**
 * @package    Enterprise
 * @subpackage Elvis
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Utility that provides the settings made by brand administrator.
 */
class ElvisBrandAdminConfig
{
	/**
	 * Returns the value of a custom property, as configured for a given Publication.
	 *
	 * @param AdmExtraMetaData[]|null $extraMetaData Custom props of Publication.
	 * @param string $optionName Name of the configuration option.
	 * @return string|null The configuration option value. NULL when none found.
	 */
	private static function getOptionValue( $extraMetaData, $optionName )
	{
		require_once BASEDIR .'/server/bizclasses/BizAdmProperty.class.php';
		return BizAdmProperty::getCustomPropVal( $extraMetaData, $optionName );
	}

	/**
	 * Sets the value of a custom property, as configured for a given Publication.
	 *
	 * @param AdmExtraMetaData[]|null $extraMetaData
	 * @param string $optionName Name of the configuration option.
	 * @param string $optionValue Value of the configuration option.
	 */
	private static function setOptionValue( $extraMetaData, $optionName, $optionValue )
	{
		require_once BASEDIR .'/server/bizclasses/BizAdmProperty.class.php';
		BizAdmProperty::setCustomPropVal( $extraMetaData, $optionName, $optionValue );
	}

	/**
	 * Returns the Project reference, as configured for a given 'dps2' Publication Channel.
	 *
	 * From Adobe DPS point of view, this is the external id, and is stored in Enterprise Server.
	 * In the API this is called publication name. In the Adobe UI this is called "Link Reference"
	 * which can be found on the "Project" configuration page.
	 * Note that this field is read-only at the Publication Channel Maintenance page. It can only
	 * be set through the registration procedure.
	 *
	 * @param AdmPublication $publication
	 * @return string|null The project reference. NULL when none found.
	 */
	public static function getProductionZone( AdmPublication $publication )
	{
		return self::getOptionValue( $publication->ExtraMetaData, 'C_ELVIS_PRODUCTION_ZONE' );
	}

	/**
	 * Saves the Project reference and id. See getProjectRef() / getProjectId() function headers for more info.
	 *
	 * @param AdmPublication $publication
	 * @param string $productionZone
	 */
	public static function setProductionZone( AdmPublication $publication, $productionZone )
	{
		$productionZone = str_replace( '${brand}', $publication->Name, $productionZone );
		self::setOptionValue( $publication->ExtraMetaData, 'C_ELVIS_PRODUCTION_ZONE', $productionZone );
	}

	/**
	 * Replaces placeholder such as /Enterprise/${brand}/${date:Y-m} with current date stamp.
	 *
	 * @param string $productionZone
	 * @return string
	 */
	public static function substituteDateInProductionZone( $productionZone )
	{
		$dateStart = strpos( $productionZone, '${date:' );
		if( $dateStart !== false ) {
			$dateStart += strlen( '${date:' );
			$dateEnd = strpos( $productionZone, '}', $dateStart );
			if( $dateEnd !== false ) {
				$dateFormat = substr( $productionZone, $dateStart, $dateEnd-$dateStart );
				$dateObj = new DateTime(); // now
				$date = $dateObj->format( $dateFormat );
				$productionZone = str_replace( '${date:'.$dateFormat.'}', $date, $productionZone );
			}
		}
		return $productionZone;
	}
}
