<?php
/**
 * Provides config_elvis.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging. It hides the password values for ELVIS_DEFAULT_USER (and the obsoleted ELVIS_ENT_ADMIN_PASS).
 *
 * @since      10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class Elvis_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'config_elvis.php' => BASEDIR.'/config/config_elvis.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function displayOptionValue( $configFile, $optionName, $value )
	{
		require_once BASEDIR.'/config/config_elvis.php';
		if( $optionName == 'ELVIS_SUPER_USER_PASS' || $optionName == 'ELVIS_ENT_ADMIN_PASS' ) {
			$value = '***';
		}
		// Note that ELVIS_SUPER_USER_PASS is obsoleted since 10.5.0, but still hide it in case the option is not removed yet.
		return $value;
	}
}