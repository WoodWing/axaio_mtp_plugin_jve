<?php
/**
 * @since      10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides Elvis/config.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging. It hides the password values for ELVIS_SUPER_USER_PASS and ELVIS_ENT_ADMIN_PASS.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class Elvis_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'plugins/Elvis/config.php' => __DIR__.'/config.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function displayOptionValue( $configFile, $optionName, $value )
	{
		require_once __DIR__.'/config.php';
		if( $optionName == 'ELVIS_SUPER_USER_PASS' || $optionName == 'ELVIS_ENT_ADMIN_PASS' ) {
			$value = '***';
		}
		return $value;
	}
}