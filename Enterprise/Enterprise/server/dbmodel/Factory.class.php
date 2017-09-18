<?php

/**
 * Class that can contruct a DB model definition provided by the core server and/or by the server plugins.
 *
 * @package    Enterprise
 * @subpackage DbModel
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_DbModel_Factory
{
	/**
	 * Contruct DB model definitions provided by the core server and by the (installed and active) server plugins.
	 *
	 * @return WW_DbModel_Provider[]
	 */
	static public function createModels()
	{
		$definitions = array( self::createModelForEnterpriseServer() );
		return array_merge( $definitions, self::createModelsForServerPlugins() );
	}

	/**
	 * Contruct the DB model definition provided by the (installed and active) server plugins.
	 *
	 * @return WW_DbModel_Provider
	 */
	static public function createModelForEnterpriseServer()
	{
		require_once BASEDIR.'/server/dbmodel/Definition.class.php';
		return new WW_DbModel_Definition();
	}

	/**
	 * Contruct the DB model definition provided by a given (installed and active) server plugin.
	 *
	 * @param string $pluginName
	 * @return WW_DbModel_Provider
	 * @throws BizException when the server plug-in is not installed nor active or does not return a valid provider.
	 */
	static public function createModelForServerPlugin( $pluginName )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		/** @var WW_DbModel_Provider[] $connRetVals */
		$connRetVals = array();
		BizServerPlugin::runDefaultConnector( $pluginName, 'DbModel', null, 'getDbModelProvider',
			array(), $connRetVals );
		if( !$connRetVals ) {
			$message = "Could not find an installed server plug-in named {$pluginName}. Please install and try again.";
			throw new BizException( '', 'Server', '', $message );
		}
		$provider = reset( $connRetVals );
		self::validateDbModelProvider( $pluginName, $provider );
		return $connRetVals ? reset( $connRetVals ) : null;
	}

	/**
	 * Contruct DB model definitions provided by the (installed and active) server plugins.
	 *
	 * @return WW_DbModel_Provider[]
	 * @throws BizException when one of the server plug-ins does not return a valid provider.
	 */
	static public function createModelsForServerPlugins()
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		/** @var WW_DbModel_Provider[] $connRetVals */
		$connRetVals = array();
		BizServerPlugin::runDefaultConnectors( 'DbModel', null, 'getDbModelProvider',
				array(), $connRetVals );

		// Replace the keys; instead of connector names the caller is more interested in the plugin names.
		$pluginRetVals = array();
		if( $connRetVals ) foreach( $connRetVals as $connectorName => $provider ) {
			$pluginName = BizServerPlugin::getPluginUniqueNameForConnector( $connectorName );
			$pluginRetVals[ $pluginName ] = $provider;
			self::validateDbModelProvider( $pluginName, $provider );
		}
		return $pluginRetVals;
	}

	/**
	 * Validates whether the provider returned by the getDbModelProvider() function has a correct type and parent class.
	 *
	 * @param string $pluginName
	 * @param mixed $provider
	 * @throws BizException when the provider is not an object or when it does not inherit from WW_DbModel_Provider
	 */
	static private function validateDbModelProvider( $pluginName, $provider )
	{
		$method = "{$pluginName}_DbModel::getDbModelProvider()";
		if( !is_object( $provider ) ) {
			$message = "The function {$method} does not return an object. ".
				"Please let is return an object that extends from WW_DbModel_Provider and try again.";
			throw new BizException( '', 'Server', '', $message );
		}
		if( !is_subclass_of( $provider, 'WW_DbModel_Provider' ) ) {
			$className = get_class( $provider );
			$message = "The function {$method} does not return an object that inherits from WW_DbModel_Provider. ".
				"Please let class {$className} extend from WW_DbModel_Provider and try again.";
			throw new BizException( '', 'Server', '', $message );
		}
	}
}