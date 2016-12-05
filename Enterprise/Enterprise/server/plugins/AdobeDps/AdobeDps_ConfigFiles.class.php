<?php
/**
 * @package    Enterprise
 * @subpackage AdobeDps
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides config_dps.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging. It hides the password values for its ADOBEDPS_ACCOUNTS option.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class AdobeDps_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'config_dps.php' => BASEDIR.'/config/config_dps.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function displayOptionValue( $configFile, $optionName, $value )
	{
		if( $optionName == 'ADOBEDPS_ACCOUNTS' ) {
			$value = preg_replace( '/\[password\] => ([^)|^\ ]*)/', '[password] => ***', $value );
		}
		return $value;
	}
}