<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

abstract class EnterprisePlugin
{
	public function getInterfaceVersion() { return 1; }

	abstract public function getPluginInfo();
	abstract public function getConnectorInterfaces();

	/**
	 * Although a plug-in is plugged into the system, it might require some extra installation
	 * steps before it can be used. This should be only those steps the plug-in can do
	 * automatically by itself, without the need of manual steps or other user intervention.
	 *
	 * If this is the case, the plug-in should overrule this function and implement the
	 * required installation steps in there. This function is called by the core server
	 * once only during a clean installation, or when the plugin is manually added after
	 * and the admin user tries to activate the plug-in. When the installation has failed
	 * this function should throw a BizException. As long as the installation fails this way,
	 * it will be called again by the core server each time the admin user tries to activate 
	 * the plug-in. Once ran without a BizException being thrown, it won't ever be called 
	 * again anymore (not even after deactivating and re-activating the plug-in again).
	 *
	 * @since 9.0.0 This function should no longer be used to validate configurations.
	 * Instead, the plug-in should ship a TestSuite module in its plugin folder which gets
	 * automatically picked up by the Health Check page. It loads the TestSuite module and
	 * calls its runTest() function. The runTest() function should simply validate if the 
	 * plug-in is correctly installed and configured, but nothing more than that (e.g. no repair).
	 *
	 * @since 6.0.0
	 * @throws BizException On installation or configuration failure.
	 */
	public function runInstallation()
	{
	}
	
	/**
	 * @since 9.0.0 No longer used. When runInstallation() did not throw BizException
	 * the core server marks the plug-in as installed. In other terms, the core server
	 * keeps track whether or not the plugin was once successfully installed. This is
	 * no longer to the plug-in to decide. See also runInstallation() for more info.
	 *
	 * @since 6.0.0
	 * @return boolean Whether or not the plug-in is currently installed.
	 */
	//public function isInstalled()
	//{
	//	return true;
	//}

	/**
	 * For the first time installation, the core server will ask the plug-in whether or
	 * not it should be activated. This happens when the core reads the plug-ins from disk
	 * and is about to update the database to register the plug-in for the first time. 
	 * This is the case during clean installation or after the plug-in is mannualy added
	 * in the config/plugins folder.
	 *
	 * Most plug-ins might want to be activated automatically after a Enterprise Server 
	 * installation. By default this is the case. However, when the plug-in integrates 
	 * with an external system, it is recommended to leave it deactivated after installation. 
	 * Nevertheless, that is for built-in plug-ins only. Custom plug-ins require manual 
	 * installation (extract at config/plugins folder) and so it is recommended to activate 
	 * the custom plug-ins by default.
	 *  
	 * Nevertheless whether or not to be activated by default is up to the plug-in to decide. 
	 * To deactivate a plug-in after Enterprise Server installation, the isActivatedByDefault()
	 * function must be implemented and return false. Else there is no need for action.
	 *
	 * @since 9.0.0
	 * @return boolean
	 */
	public function isActivatedByDefault()
	{
		return true;
	}
	
	/**
	 * When the admin user decides to activate the plug-in, this function is called by the
	 * core server. It allows the plug-in to raise error (by throwing a BizException) in
	 * case it wants to block activation. The raised error should tell why the plug-in
	 * could not be activated. This should be used in cases whereby the plug-in can not
	 * work at all, such as an incompatible OS type.
	 *
	 * This function should -not- be used to validate configurations. Instead, the plug-in 
	 * should ship a TestSuite module in its plugin folder which gets automatically picked 
	 * up by the Health Check page. It loads the TestSuite module and calls its runTest() 
	 * function. The runTest() function should simply validate if the plug-in is correctly 
	 * installed and configured, but nothing more than that (e.g. no repair).
	 *
	 * @since 9.0.0
	 * @throws BizException When plug-in can never be activated at all.
	 */
	public function beforeActivation()
	{
	}
	
	/**
	 * In case the server plug-in depends on new Enterprise Server core features, it is recommended
	 * to implement this function and return the minimum required server version which introduces
	 * those features. (Note that Build numbers must be part of the string but are not checked.)
	 *
	 * Please return the following format:
	 *       '<major>.<minor>.<patch> Build <buildnr>'
	 * For example:
	 *       '9.0.0 Build 1'
	 *
	 * @return string|null Server version. NULL to accept all versions (skip version check).
	 */
	public function requiredServerVersion()
	{
		return null;
	}
}
