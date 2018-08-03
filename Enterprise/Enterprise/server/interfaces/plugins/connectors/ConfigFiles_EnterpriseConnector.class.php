<?php
/**
 * @since      v10.1.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Allows plugins to offer their config files and filter option value before shown.
 *
 * The option of the config files are shown in the Config Overview page (wwinfo.php) that is
 * accessible through the Health Check page. And, they are also shown in the phpinfo.htm file
 * that can be found in the server log folder.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class ConfigFiles_EnterpriseConnector extends DefaultConnector
{
	/**
	 * The plugin can provide a list of config files to be invoked.
	 *
	 * The returned items are key-value pairs. The key holds the display name of the config file.
	 * This name could be a short path, base name of the file or a logical name. The value holds
	 * the full file path of the config file itself. You may want to use __DIR__ as a base dir.
	 *
	 * For example:
	 *    return array( 'plugins/MyPlugin/config.php' => __DIR__.'/config.php' );
	 *
	 * @return string[] List if full file path names, indexed with their display names.
	 */
	public function getConfigFiles()
	{
		return array();
	}

	/**
	 * For each option, this function is called to allow the plugin to filter the option value.
	 *
	 * When the plugin's password option is passed in, it could replace this with '***'.
	 *
	 * For example:
	 *    if( $optionName == 'MY_PASSWORD' ) {
	 *       $value = '***';
	 *    }
	 *    return $value;
	 *
	 * @param string $configFile
	 * @param string $optionName
	 * @param mixed $value
	 * @return mixed
	 */
	public function displayOptionValue( $configFile, $optionName, $value )
	{
		return $value;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that cannot be overruled by a connector:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}