<?php
/**
 * @package    Enterprise
 * @subpackage WordPress
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Provides WordPress/config.php options to the Config Overview page (wwinfo.php) page and the phpinfo.htm file
 * in the server logging. It hides the password values for its WORDPRESS_SITES option.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/ConfigFiles_EnterpriseConnector.class.php';

class WordPress_ConfigFiles extends ConfigFiles_EnterpriseConnector
{
	/**
	 * @inheritdoc
	 */
	public function getConfigFiles()
	{
		return array( 'plugins/WordPress/config.php' => __DIR__.'/config.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function displayOptionValue( $configFile, $optionName, $value )
	{
		if( $optionName == 'WORDPRESS_SITES' ) {
			$value = preg_replace( '/\[password\] => [^\n]*/', '[password] => ***', $value );
		}
		return $value;
	}
}