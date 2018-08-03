<?php
/**
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides AxaioMadeToPrint/config.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging. It hides the password values for its AXAIO_MTP_PASSWORD option.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class AxaioMadeToPrint_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'plugins/AxaioMadeToPrint/config.php' => __DIR__.'/config.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function displayOptionValue( $configFile, $optionName, $value )
	{
		if( $optionName == 'AXAIO_MTP_PASSWORD' ) {
			$value = '***';
		}
		return $value;
	}
}