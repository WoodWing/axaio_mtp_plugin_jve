<?php
/**
 * Interface for adding web applications to Enterprise Server.
 * Web apps are shown by the core server at the Integrations admin page.
 * Each web app is represented by an icon with a caption. When the user clicks
 * on the icon, the webappindex.php module is run which creates and shows the web app. 
 *
 * For each application, the plugin should provide a PHP module that implements the web app.
 * Inside the plugin folder there should be a webapps folder that ships the web apps and
 * their assets. Each web app must have an internal name that is unique within the plug-in.
 * For example when the internal plug-in name is MyServerPlugin and the web app id is MyApp, 
 * there must be a PHP module with the following file name:
 *    MyServerPlugin_MyApp_EnterpriseWebApp.class.php
 * The module should implement the MyServerPlugin_MyApp_EnterpriseWebApp class like this:
 *    require_once BASEDIR.'/server/utils/htmlclasses/EnterpriseWebApp.class.php';
 *    class MyServerPlugin_MyApp_EnterpriseWebApp extends EnterpriseWebApp
 *    {
 *        ... your implementation ...
 *    }
 * See Enterprise/server/utils/htmlclasses/EnterpriseWebApp.class.php for more details.
 *
 * The order of the
 *
 * @package Enterprise
 * @subpackage Core
 * @since v8.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class WebApps_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Tells which web apps are shipped within the server plug-in.
	 *
	 * @return WebAppDefinition[]
	 */
	abstract public function getWebApps();

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}

class WebAppDefinition
{
	/**
	 * URL to 32x32 icon file, relative to the server plug-in folder.
	 * @var string $IconUrl
	 */
	public $IconUrl;
	
	
	/**
	 * Shortname to be shown under the web app icon.
	 * @var string $IconUrl
	 */
	public $IconCaption;

	/**
	 * Identifier of the web app that is unique within the server plug-in.
	 * @var string $IconUrl
	 */
	public $WebAppId;

	/**	
	 * For web apps provided by server plug-ins only.
	 * Tells if the web page should be shown when the server plug-in is disabled.
	 * This can be useful when the application does setup the plug-in. During setup
	 * you do not want to offer the server plug-in's feature to end users yet and so
	 * the plug-in is still unplugged. Or the system admin wants to disable the whole 
	 * feature first and then solve the problem through configuration using the web app.
	 * Set to TRUE to always show, or FALSE to show when plugged-in only.
	 *
	 * @var boolean $ShowWhenUnplugged
	 */
	 public $ShowWhenUnplugged;

	/**
	 * Tells which users can access the web page.
	 *
	 * Set to 'admin' to allow system admin users only.
	 * Set to 'publadmin' to allow brand admin and system admin users.
	 *
	 * @since 10.2.0
	 * @var string $AccessType
	 */
	public $AccessType = 'admin';
}
