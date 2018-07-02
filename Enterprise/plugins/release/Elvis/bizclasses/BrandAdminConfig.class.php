<?php
/**
 * Utility that provides the settings made by brand administrator.
 *
 * @since      10.1.1
 * @since      10.5.0 Class originates from util/ElvisBrandAdminConfig.class.php
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_BizClasses_BrandAdminConfig
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
		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		BizAdmProperty::setCustomPropVal( $extraMetaData, $optionName, $optionValue );
	}

	/**
	 * Returns the custom Production Zone property value of a given Publication.
	 *
	 * @param AdmPublication $publication
	 * @return string|null The project reference. NULL when none found.
	 */
	public static function getProductionZone( AdmPublication $publication )
	{
		return self::getOptionValue( $publication->ExtraMetaData, 'C_ELVIS_PRODUCTION_ZONE' );
	}

	/**
	 * Query the database for the Elvis Production Zone value of the publication
	 *
	 * @param string $pubId Publication id.
	 * @return string|null Production zone of the publication.
	 */
	public static function getProductionZoneByPubId( $pubId )
	{
		$where  = '`publication` = ? AND `name` = ? ';
		$params[] = $pubId;
		$params[] = 'C_ELVIS_PRODUCTION_ZONE';

		require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
		$row = DBBase::getRow( 'channeldata', $where, array('value'), $params );

		return $row ? reset($row) : null;
	}

	/**
	 * Saves the custom Production Zone property value for a given Publication.
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
	 * Adds the custom Production Zone property value for a given Publication.
	 *
	 * @param AdmPublication $publication
	 * @param string $productionZone
	 */
	public static function addProductionZone( AdmPublication $publication, $productionZone )
	{
		$productionZone = str_replace( '${brand}', $publication->Name, $productionZone );
		$publication->ExtraMetaData[] = new AdmExtraMetaData( 'C_ELVIS_PRODUCTION_ZONE', array( $productionZone ) );
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
