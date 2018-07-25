<?php
/**
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class DbAdmFeatureAccessFactory
{
	// For Plugin
	const FEATUREACCESS_RANGE_MIN = 5000;
	const FEATUREACCESS_RANGE_MAX = 5999;

	// For SubApplication
	const SA_FEATUREACCESS_RANGE_MIN = 1501;
	const SA_FEATUREACCESS_RANGE_MAX = 1999;

	/**
	 * Returns a new DBAdmFeatureAccess class to be used for Feature Access for the Plugins.
	 *
	 * @return DBAdmFeatureAccess
	 */
	public static function createDbFeatureAccessForPlugin()
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmFeatureAccess.class.php';
		return new DBAdmFeatureAccess( self::FEATUREACCESS_RANGE_MIN, self::FEATUREACCESS_RANGE_MAX );
	}

	/**
	 * Returns a new DBAdmFeatureAccess class to be used for Feature Access for the Sub Applications.
	 *
	 * @return DBAdmFeatureAccess
	 */
	public static function createDbFeatureAccessForSubApplication()
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmFeatureAccess.class.php';
		return new DBAdmFeatureAccess( self::SA_FEATUREACCESS_RANGE_MIN, self::SA_FEATUREACCESS_RANGE_MAX );
	}
}