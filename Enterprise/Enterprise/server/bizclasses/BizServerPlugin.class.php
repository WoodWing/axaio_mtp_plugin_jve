<?php

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
require_once BASEDIR.'/server/interfaces/plugins/ConnectorInfoData.class.php';

// >>> Simple helper class to pass through empty plugins (placeholders) that are in fatal error status.
class InternalError_EnterprisePlugin extends EnterprisePlugin
{
	final public function getPluginInfo() {  return new PluginInfoData(); }
	final public function getConnectorInterfaces() { return array(); }
	//public function isInstalled()  { return false;  }
} // <<<

class BizServerPlugin
{
	const LOCAL_CACHE_BUCKET = 'ServerPluginConnectors';

	/**
	 * Queries all server plug-in infos ($pluginInfos) from DB and instantiates plug-in
	 * objects ($pluginObjs) from it. Since it reads all the PluginInfo.php files from
	 * the plugins folders and instantiates objects from the class definitions inside,
	 * this function is much slower than getPluginInfosFromDB().
	 *
	 * @param EnterprisePlugin[] $pluginObjs (Out) Will be filled with instantiated plugins. Pass in reference to (empty) array.
	 * @param PluginInfoData[] $pluginInfos (Out) Will be filled with plugin info's. Pass in reference to (empty) array.
	 * @throws BizException On DB error.
	 */
	static private function getPluginsFromDB( &$pluginObjs, &$pluginInfos )
	{
		// create plugin info data instances
		$pluginInfos = self::getPluginInfosFromDB();
		
		// create plugin instances
		foreach( $pluginInfos as $pluginInfo ) {
			$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
			$classFile = $classDir.$pluginInfo->UniqueName.'/PluginInfo.php';
			$errMsg = '';
			$pluginObj = self::instantiatePluginObjectFromFile( $classFile, $errMsg );
			if( $pluginObj ) {
				$pluginObjs[$pluginInfo->UniqueName] = $pluginObj;
			}
			if( $errMsg ) {
				LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
			}
		}
	}

	/**
	 * Queries all server plug-in infos ($pluginInfos) from DB.
	 * Since it does not instantiate server plug-in objects from it, this function
	 * is much faster than getPluginsFromDB().
	 *
	 * @return PluginInfoData[]
	 * @throws BizException On DB error.
	 */
	static private function getPluginInfosFromDB()
	{
		// create plugin info data instances
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$pluginInfos = DBServerPlugin::getPlugins();
		if( DBServerPlugin::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBServerPlugin::getError() );
		}
		return $pluginInfos;
	}
	
	/**
	 * Creates a connector object (in memory), based on given info data. The connector
	 * class file is included and the class is instantiated.
	 *
	 * @param ConnectorInfoData $connInfo 
	 * @return EnterpriseConnector|null Returns NULL on failure.
	 */
	static private function instantiateConnectorObject( ConnectorInfoData $connInfo )
	{
		$connector = null;
		LogHandler::Log('BizServerPlugin', 'DEBUG', 'Loading Connector: '.$connInfo->ClassFile );
		if( file_exists( BASEDIR.$connInfo->ClassFile ) ) {
			require_once( BASEDIR.$connInfo->ClassFile );
			LogHandler::Log('BizServerPlugin', 'DEBUG', 'Instantiating Connector: '.$connInfo->ClassName );
			if( class_exists( $connInfo->ClassName ) ) {
				$connector = new $connInfo->ClassName;
			} else {
				LogHandler::Log('BizServerPlugin', 'ERROR', 'Failed creating Server Connector class: '.$connInfo->ClassName );
			}
		} else {
			LogHandler::Log('BizServerPlugin', 'ERROR', 'Failed loading Server Connector file: '.BASEDIR.$connInfo->ClassFile );
		}
		return $connector;
	}
	
	/**
	 * Queries all connector infos of specified interface queries from DB ($connInfos) and
	 * instantiates connector objects ($connectors) from it. Since it includes and parses
	 * connector classes and instantiates connector objects from that, this function is 
	 * much slower than the getConnectorInfosFromDB() function.
	 *
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param EnterpriseConnector[] $connectors (Out) Will be filled with instantiated connectors. Pass in reference to (empty) array.
	 * @param ConnectorInfoData[] $connInfos    (Out) Will be filled with connector info's. Pass in reference to (empty) array.
	 * @param boolean $activeOnly	Include connectors of activated plugins only. Default true.
	 * @param boolean $installedOnly	Include connectors of installed plugins only. Default true.
	 * @throws BizException On DB error.
	 */
	static private function getConnectorsFromDB( $interface, $type, &$connectors, &$connInfos, $activeOnly = true, $installedOnly = true )
	{
		// create connector info data instances
		$itemId = $interface.'_'.$type;
		if( $activeOnly && $installedOnly && // let's optimize for the most obvious filters only
			BizSession::getTicket() ) {       // without ticket there is no cache (e.g. exclude LogOn, GetServers, etc)
			$cache = WW_BizClasses_LocalCache::getInstance();
			$connInfosData = $cache->readBucketItemData( self::LOCAL_CACHE_BUCKET, $itemId );
		} else {
			$cache = null;
			$connInfosData = false;
		}
		if( $connInfosData === false ) {
			$connInfos = self::getConnectorInfosFromDB( $interface, $type, $activeOnly, $installedOnly );
			if( $cache ) {
				$cache->writeBucketItemData( self::LOCAL_CACHE_BUCKET, $itemId, serialize( $connInfos ) );
			}
		} else {
			$connInfos = unserialize( $connInfosData );
		}

		// create connector instances
		if( $connInfos ) foreach( $connInfos as $connInfo ) {
			// Keep Business Connectors alive throughout the whole session.
			// Reason is to preserve their local class members (cached property data).
			// Then the caller can talk to one connector while data is preserved between the function calls.
			if( !array_key_exists( $connInfo->ClassName, $connectors )) {
				$connector = self::instantiateConnectorObject( $connInfo );
				if( $connector ) {
					$connectors[ $connInfo->ClassName ] = $connector;
				}
			}
		}
	}

	/**
	 * Queries all connector infos of specified interface queries from DB ($connInfos).
	 * Since this function does not instantiate connector objects it is much faster than
	 * the getConnectorsFromDB() function.
	 *
	 * @param string $interface      Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type           Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param boolean $activeOnly    Include connectors of activated plugins only. Default true.
	 * @param boolean $installedOnly Include connectors of installed plugins only. Default true.
	 * @return ConnectorInfoData[]
	 * @throws BizException On DB error.
	 */
	static private function getConnectorInfosFromDB( $interface, $type, $activeOnly = true, $installedOnly = true )
	{
		require_once BASEDIR.'/server/dbclasses/DBServerConnector.class.php';
		$connInfos = DBServerConnector::getConnectors( $interface, $type, $activeOnly, $installedOnly );
		if( DBServerConnector::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBServerConnector::getError() );
		}
		return $connInfos;
	}
	
	/**
	 * Creates all default connectors that implement the given interface and calls the given method with params.
	 * All return values of executed connectors are collected (in the $returnVals) and returned to caller.
	 * 
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param string $methodName   Method / function to call at connector.
	 * @param array $methodParams Method params to pass onto $methodName
	 * @param boolean $activeOnly  Take only active connectors into account. 
	 * @param boolean $installedOnly  Take only installed connectors into account. 
	 * @param array  $returnVals   Collected return values of all connectors. Keys are connector class names. Returns
	 * empty array if no connector is installed.
	 */
	static public function runDefaultConnectors( $interface, $type, $methodName, $methodParams, &$returnVals, $activeOnly = true, $installedOnly = true )
	{
		require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';
		$connectors = &self::getCachedBizConnectors();
		$connInfos	= array();
		self::getConnectorsFromDB( $interface, $type, $connectors, $connInfos, $activeOnly, $installedOnly);
		
		// allow connectors to run before real service
		$returnVals = array();	
		foreach( $connectors as $connClass => $connector ) {
			$mode = null;
			if( isset( $connInfos[$connClass] )) {
				$mode = $connInfos[$connClass]->RunMode;
			}
			if( $mode == DefaultConnector::RUNMODE_SYNCHRON ) {
				LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.$connClass.' executes method '.$methodName );
				$returnVals[$connClass] = call_user_func_array( array(&$connector, $methodName), $methodParams );
				LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
			} /*else if( $mode == DefaultConnector::RUNMODE_BACKGROUND ) {
				// TODO: init background task
			}*/
		}
	}

	/**
	 * Same as {@link runDefaultConnector} but now for just one given server plug-in.
	 * The interface of this function is the same for the caller's convenience.
	 * 
	 * @param string $pluginName   Name of the server plug-in to run the connector for.
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param string $methodName   Method / function to call at connector.
	 * @param array $methodParams Method params to pass onto $methodName
	 * @param boolean $activeOnly  Take only active connectors into account. 
	 * @param boolean $installedOnly  Take only installed connectors into account. 
	 * @param array  $returnVals   Collected return values of all connectors. Keys are connector class names.
	 */
	static public function runDefaultConnector( $pluginName, $interface, $type, $methodName, $methodParams, &$returnVals, $activeOnly = true, $installedOnly = true )
	{
		require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';
		$connectors = &self::getCachedBizConnectors();
		$connInfos	= array();
		self::getConnectorsFromDB( $interface, $type, $connectors, $connInfos, $activeOnly, $installedOnly);
		
		// allow connectors to run before real service
		$returnVals = array();	
		foreach( $connectors as $connClass => $connector ) {
			$mode = null;
			if( isset( $connInfos[$connClass] )) {
				$mode = $connInfos[$connClass]->RunMode;
			}
			if( $mode == DefaultConnector::RUNMODE_SYNCHRON ) {
				$connPluginName = self::getPluginUniqueNameForConnector( $connClass );
				if( $pluginName == $connPluginName ) {
					LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.$connClass.' executes method '.$methodName );
					$returnVals[$connClass] = call_user_func_array( array(&$connector, $methodName), $methodParams );
					LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
				}
			} /*else if( $mode == DefaultConnector::RUNMODE_BACKGROUND ) {
				// TODO: init background task
			}*/
		}
	}
		
	/**
	 * Run a function on the connector defined in the channel set-up.
	 *
	 * @param int $channelID Pub Channel Id.
	 * @param string $functionName Function name to be called by the connector.
	 * @param array $parameters Parameters of the $functionName to be called.
	 * @param boolean $throwException When set to False, errors will be suppressed. Default is True.
	 * @return mixed|null
	 */
	static public function runChannelConnector( $channelID, $functionName, $parameters, $throwException=true )
	{
		// Call the plugin that executes the publish
		$connector = self::getChannelConnector( $channelID, $throwException );
		if( $connector ) {
			return self::runConnector( $connector, $functionName, $parameters );
		} else {
			return null;
		}
	}

	/**
	 * Returns the folder of the publish plugin that is configured for the given publish channel.
	 *
	 * @since 9.0
	 * @param integer $pubChannelId
	 * @param bool $throwException When set to False, errors will be suppressed. Default is True.
	 * @return string|null Full folder path (without ending slash). NULL when not found.
	 */	
	static public function getPluginFolderForChannelId( $pubChannelId, $throwException=true )
	{
		$connector = self::getChannelConnector( $pubChannelId, $throwException );
		if( $connector ) {
			$connName = get_class( $connector );
			$pluginInfo = BizServerPlugin::getPluginForConnector( $connName );
			$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
			return $classDir.$pluginInfo->UniqueName;
		} else {
			return null;
		}
	}

	/**
	 * Find the Publishing connector used for a given channel id.
	 * @throws BizException if connector could not be found, when $throwException is true.
	 *
	 * @param string $channelId
	 * @param boolean $throwException When set to False, errors will be surpressed.
	 * @return EnterpriseConnector|null The connector object. Returns null when no connector found.
	 */
	static private function getChannelConnector( $channelId, $throwException )
	{
		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR . '/server/dbclasses/DBServerPlugin.class.php';
		require_once BASEDIR . '/server/interfaces/plugins/PluginInfoData.class.php';

		// Get the specific server plug-in that is bound to the channel targeted for.
		$channel = DBChannel::getChannel( $channelId );
		$infoData = new PluginInfoData();
		$infoData->UniqueName = $channel['publishsystem'];
		if( empty( $infoData->UniqueName ) ) {
			if( $throwException ) {
				throw new BizException( 'ERR_PUBLISH_SYSTEM_NOT_FOUND', 'ERROR', '' );
			} else {
				return null;
			}
		}

		$connClass = $infoData->UniqueName.'_PubPublishing';
		$connectors = &self::getCachedBizConnectors();
		if( array_key_exists( $connClass, $connectors ) ) {
			return $connectors[$connClass];
		}

		$pluginID = DBServerPlugin::getPluginId( $infoData );

		// Find the publishing connector for the plug-in.
		$connector = self::searchConnectorByPluginId( 'PubPublishing', null, $pluginID );
		if( is_null( $connector ) && $throwException ) {
			if( $throwException ) {
				$message = str_replace( '%1', $channel['publishsystem'], BizResources::localize('ERR_NONACTIVE_PLUGIN') );
				throw new BizException( null, 'ERROR', null, $message );
			} else {
				return null;			
			}
		}
		// Cache the found connector.
		$connectors[$connClass] = $connector;
		return $connector;
	}	
	

	/**
	 * Returns the first connector that returns true on the specified method with specified parameters
	 * the connectors are called synchronously
	 *
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param string $methodName  	Name of the method to call
	 * @param array $methodParams Parameters to call
	 * @return EnterpriseConnector|null The connector object. Returns null when no connector found.
	 */
	static public function searchConnector( $interface, $type, $methodName, $methodParams )
	{
		$connectors = array();
		$connInfos	= array();
		self::getConnectorsFromDB( $interface, $type, $connectors, $connInfos );
		
		foreach( $connectors as $connClass => $connector ) {
			LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.$connClass.' executes method '.$methodName );
			$ret = call_user_func_array( array(&$connector, $methodName), $methodParams );
			LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
			if( $ret ) return $connector;
		}
		// if we arrive here we either didn't had any connector for the specified interface or 
		// they all returned false
		return null;
	}

	/**
	 * Returns all connectors of the given type implementing the given interface
	 *
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param boolean $activeOnly	Include connectors of activated plugins only. Default true.
	 * @param boolean $installedOnly	Include connectors of installed plugins only. Default true.
	 * @return EnterpriseConnector[] The connector objects. Returns empty array when no connectors found.
	 */
	static public function searchConnectors( $interface, $type, $activeOnly = true, $installedOnly = true )
	{
		$connectors = array();
		$connInfos	= array();
		self::getConnectorsFromDB( $interface, $type, $connectors, $connInfos, $activeOnly, $installedOnly );
		return $connectors;
	}

	/**
	 * Returns connector of the given type implementing the given interface with the given plugin id
	 *
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param string $id		  	Plug-in id
	 * @param boolean $activeOnly	Include connectors of activated plugins only. Default true.
	 * @param boolean $installedOnly Include connectors of installed plugins only. Default true.	  
	 * @return EnterpriseConnector|null The connector object. Returns null when no connector found.
	 */
	static public function searchConnectorByPluginId( $interface, $type, $id, $activeOnly = true, $installedOnly = true )
	{
		$result = null;
		$connectors = array();
		$connInfos	= array();
		self::getConnectorsFromDB( $interface, $type, $connectors, $connInfos, $activeOnly, $installedOnly );
		foreach ($connInfos as $connClass => $connInfo){
			if ($connInfo->PluginId == $id){
				$result = $connectors[$connClass];
				break;
			}
		}

		return $result;
	}

	/**
	 * Searches the DB for a connector with a given class name.
	 *
	 * @param string $className Connector class name. E.g: SipsPreview_Preview, PreviewMetaPHP_MetaData, etc
	 * @return EnterpriseConnector|null The connector object. Returns null when no connector found.
	 */
	static public function searchConnectorByClassName( $className )
	{
		require_once BASEDIR.'/server/dbclasses/DBServerConnector.class.php';
		$connObj = null;
		$connInfo = new ConnectorInfoData();
		$connInfo->ClassName = $className;
		$connInfo = DBServerConnector::getConnector( $connInfo ); // ConnectorInfoData
		if( $connInfo ) {
			$connObj = self::instantiateConnectorObject( $connInfo );
		}
		return $connObj;
	}

	/**
	 * Runs method with params of specific connector obtained from searchConnector
	 *
	 * @param object $connector  	Connector as returned by searchConnector(s)
	 * @param string $methodName  	Name of the method to call
	 * @param array $methodParams Parameters to call
	 * @return mixed Results from the connector's function call.
	 */
	static public function runConnector( $connector, $methodName, $methodParams )
	{
		LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.get_class($connector).' executes method '.$methodName );
		$returnValue = call_user_func_array( array(&$connector, $methodName), $methodParams );
		LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
		return $returnValue;
	}

	/**
	 * Creates all connectors that implements the given service and calls its run methods.
	 *
	 * @param object $serviceObj   The service that will be called back through runCallback() function.
	 * @param string $interface  	Connector interface. E.g: WflCopyObject, PubPublishing, NameValidation, etc
	 * @param string $type		  	Connector type. E.g: WorflowService, AdminService, etc. NULL for any type.
	 * @param string $req          Service's request data object to pass on connectors and runCallback() function.
	 * @return object              Service's response data object to be returned to caller.
	 * @throws BizException
	 */
	static public function runServiceConnectors( $serviceObj, $interface, $type, $req )
	{
		$connectors = array();
		try {
			require_once BASEDIR.'/server/interfaces/plugins/ServiceConnector.class.php';

			$connInfos	= array();
			self::getConnectorsFromDB( $interface, $type, $connectors, $connInfos );
		
			// allow connectors to run before real service
			foreach( $connectors as $connClass => $connector ) {
				$mode = $connInfos[$connClass]->RunMode;
				if( $mode == ServiceConnector::RUNMODE_BEFORE || $mode == ServiceConnector::RUNMODE_BEFOREAFTER ) {
					if( $connector ) {
						LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.$connClass.' executes runBefore() for '.$type );
						$connector->runBefore( $req );
						LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
					} else {
						LogHandler::Log('BizServerPlugin', 'ERROR', 'Connector does not exist: '.$connClass );
					}
				}
			}

			// allow connectors to overrule real service
			$overruleConnector = null;
			foreach( $connectors as $connClass => $connector ) {
				$mode = $connInfos[$connClass]->RunMode;
				if( $mode == ServiceConnector::RUNMODE_OVERRULE ) {
					$overruleConnector = $connector;
				}
			}
			if( $overruleConnector ) {
				LogHandler::Log('BizServerPlugin', 'DEBUG', $type . ' overruled' );
				$resp = $overruleConnector->runOverruled( $req ); // allow connector to overrule the system
			} else {
				LogHandler::Log('BizServerPlugin', 'DEBUG', $type . ' callback' );
				$resp = $serviceObj->runCallback( $req ); // let system do the job
			}
			LogHandler::Log('BizServerPlugin', 'DEBUG', $type . ' done' );

			// allow connectors to run after real service
			foreach( $connectors as $connClass => $connector ) {
				$mode = $connInfos[$connClass]->RunMode;
				if( $mode == ServiceConnector::RUNMODE_AFTER || $mode == ServiceConnector::RUNMODE_BEFOREAFTER ) {
					if( $connector ) {
						LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.$connClass.' executes runAfter() for '.$type );
						$connector->runAfter( $req, $resp );
						LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
					} else {
						LogHandler::Log('BizServerPlugin', 'ERROR', 'Connector does not exist: '.$connClass );
					}
				}
			}
			return $resp;
		} catch( BizException $e ) {
			foreach( $connectors as $connClass => $connector ) {
				if( $connector ) {
					LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector '.$connClass.' executes onError() for '.$type );
					$connector->onError( $req, $e );
					LogHandler::Log('BizServerPlugin', 'DEBUG', 'Connector completed' );
				} else {
					LogHandler::Log('BizServerPlugin', 'ERROR', 'Connector does not exist: '.$connClass );
				}
			}
			throw $e; // re-throw
		}
	}

	/**
	 * Queries the DB for all server plugins and their connectors and tries to instantiate them.
	 * Note: Plugins and connectors recorded at DB are read from file system before.
	 * They can be out-of-sync when manually changing files in the server/plugins or config/plugins folders.
	 *
	 * Since this DOES include all plugin- and connector classes, this function is much
	 * SLOWER than the readPluginInfosFromDB() function.
	 *
	 * @param EnterprisePlugin[] $pluginObjs  (Out)
	 * @param PluginInfoData[] $pluginInfos   (Out)
	 * @param EnterpriseConnector[] $connObjs (Out)
	 * @param ConnectorInfoData[] $connInfos  (Out)
	 * @throws BizException On DB error.
	 */
	static public function readPluginsFromDB( &$pluginObjs, &$pluginInfos, &$connObjs, &$connInfos )
	{
		// get plugins
		self::getPluginsFromDB( $pluginObjs, $pluginInfos );

		// get connectors
		$flatConnObjs = array();
		$flatConnInfos = array();
		self::getConnectorsFromDB( null /*interface*/, null /*type*/, $flatConnObjs, $flatConnInfos, false, false );
		
		// move flat lists of connectors into structure (list per plugin)
		foreach( $pluginInfos as $pluginKey => $pluginInfo ) { // $pluginKey = PluginInfoData->UniqueName
			foreach( $flatConnInfos as $connKey => $connInfo ) {
				if( $connInfo->PluginId === $pluginInfo->Id ) {
					if( !isset($connInfos[$pluginKey]) ) $connInfos[$pluginKey] = array();
					if( !isset($connObjs[$pluginKey]) ) $connObjs[$pluginKey] = array();
					$connInfos[$pluginKey][$connKey] = $connInfo;
					$connObjs[$pluginKey][$connKey] = $flatConnObjs[$connKey];
				}
			}
		}
	}

	/**
	 * Queries the DB for info or all server plugins and their connectors.
	 *
	 * Since this does NOT include all plugin- and connector classes, this function is much 
	 * FASTER than the readPluginsFromDB() function.
	 *
	 * @param PluginInfoData[] $pluginInfos   (Out)
	 * @param ConnectorInfoData[] $connInfos  (Out)
	 * @throws BizException On DB error.
	 */
	static public function readPluginInfosFromDB( &$pluginInfos, &$connInfos )
	{
		// get plugin infos
		$pluginInfos = self::getPluginInfosFromDB();

		// get connector infos
		$flatConnInfos = self::getConnectorInfosFromDB( null /*interface*/, null /*type*/, false, false );
		
		// move flat lists of connectors into structure (list per plugin)
		foreach( $pluginInfos as $pluginKey => $pluginInfo ) { // $pluginKey = PluginInfoData->UniqueName
			foreach( $flatConnInfos as $connKey => $connInfo ) {
				if( $connInfo->PluginId === $pluginInfo->Id ) {
					if( !isset($connInfos[$pluginKey]) ) $connInfos[$pluginKey] = array();
					$connInfos[$pluginKey][$connKey] = $connInfo;
				}
			}
		}
	}

	/**
	 * Compares the given plugin infos ($fsPluginInfos) read from folder with given plugin infos 
	 * read from DB ($dbPluginInfos). When any plugin is missing comparing to the two given collections,
	 * an error is logged and returned ($errMsgs). Same is done when Version, Modified or IsSystem 
	 * properties are different, which means the plugin is manually changed after import into DB.
	 *
	 * @param array $fsPluginObjs  List of EnterprisePlugin objects read from file system (folder)
	 * @param array $fsPluginInfos List of PluginInfoData objects read from file system (folder)
	 * @param array $dbPluginObjs  List of EnterprisePlugin objects read from DB
	 * @param array $dbPluginInfos List of PluginInfoData objects read from DB
	 * @param array of string Out: List of error messages. Empty when no error.
	 */
	static public function comparePlugins( $fsPluginObjs, $fsPluginInfos, $dbPluginObjs, $dbPluginInfos, &$errMsgs )
	{
		ksort( $fsPluginObjs );
		ksort( $fsPluginInfos );
		ksort( $dbPluginObjs );
		ksort( $dbPluginInfos );
		
		$diff = array_diff_key( $fsPluginInfos, $dbPluginInfos );
		if( count($diff) > 0 ) foreach( array_keys($diff) as $uniqueName ) {
			$pluginInfo = $fsPluginInfos[$uniqueName];
			$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
			$errMsg = 'Server Plugin "'.$pluginInfo->DisplayName.'" is found at '.$classDir.' folder, but is not imported into DB.';
			$errMsgs[] = $errMsg;
			LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
		}
		$diff = array_diff_key( $dbPluginInfos, $fsPluginInfos );
		if( count($diff) > 0 ) foreach( array_keys($diff) as $uniqueName ) {
			$pluginInfo = $dbPluginInfos[$uniqueName];
			$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
			$errMsg = 'Server Plugin "'.$pluginInfo->DisplayName.'" is found at DB that but is no longer present at '.$classDir.' folder';
			$errMsgs[] = $errMsg;
			LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
		}

		// Quit on error; let user fix those worst problems first
		if( count($errMsgs) > 0 ) {
			return;
		}
		
		// -> Here, the two collections have at least the same keys/entries.
		
		// Compare important properties of the plugins
		foreach( $fsPluginInfos as $pluginKey => $fsPluginInfo ) {
			$dbPluginInfo = $dbPluginInfos[$pluginKey];
			if( $fsPluginInfo->Version !== $dbPluginInfo->Version ) {
				$errMsg = 'The version of Server Plugin "'.$fsPluginInfo->DisplayName.'" at file system differs from DB.';
			} else if( $fsPluginInfo->IsSystem !== $dbPluginInfo->IsSystem ) {
				$errMsg = 'The type (server or config) of Server Plugin "'.$fsPluginInfo->DisplayName.'" at file system differs from DB.';
			} else if( $fsPluginInfo->Modified !== $dbPluginInfo->Modified ) {
				$errMsg = 'The modification date of Server Plugin "'.$fsPluginInfo->DisplayName.'" at file system differs from DB.';
			} else {
				$errMsg = ''; // ok; no differences
			}
			if( !empty( $errMsg ) ) {
				$errMsgs[] = $errMsg;
				LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
			}
		}
	}

	/**
	 * Compares the given connectors infos ($fsConnInfos) read from folder with given connector infos 
	 * read from DB ($dbConnInfos). When any plugin is missing comparing to the two given collections,
	 * an error is logged and returned ($errMsgs). Same is done when Interface, Type, RunMode, ClassName,
	 * ClassFile or Modified  properties are different, which means the connector is manually changed
	 * after import into DB. The Prio is not checked on purpose; In future, it could be changed by
	 * admin users at DB, which is ok.
	 *
	 * @param array $fsConnObjs  List of EnterpriseConnector objects read from file system (folder)
	 * @param array $fsConnInfos List of ConnectorInfoData objects read from file system (folder)
	 * @param array $dbConnObjs  List of EnterpriseConnector objects read from DB
	 * @param array $dbConnInfos List of ConnectorInfoData objects read from DB
	 * @param array of string Out: List of error messages. Empty when no error.
	 */
	static public function compareConnectors( $fsConnObjs, $fsConnInfos, $dbConnObjs, $dbConnInfos, &$errMsgs )
	{
		ksort( $fsConnObjs );
		ksort( $fsConnInfos );
		ksort( $dbConnObjs );
		ksort( $dbConnInfos );
		
		foreach( array_keys($fsConnInfos) as $pluginKey ) {
			$diff = array_diff_key( $fsConnInfos[$pluginKey], $dbConnInfos[$pluginKey] );
			if( count($diff) > 0 ) foreach( array_keys($diff) as $uniqueName ) {
				$connInfo = $fsConnInfos[$pluginKey][$uniqueName];
				$errMsg = 'Server Connector "'.$connInfo->ClassName.'" is found at '.$connInfo->ClassFile.', but is not imported into DB.';
				$errMsgs[] = $errMsg;
				LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
			}
		}
		foreach( array_keys($dbConnInfos) as $pluginKey ) {
			$diff = array_diff_key( $dbConnInfos[$pluginKey], $fsConnInfos[$pluginKey] );
			if( count($diff) > 0 ) foreach( array_keys($diff) as $uniqueName ) {
				$connInfo = $dbConnInfos[$pluginKey][$uniqueName];
				$errMsg = 'Server Connector "'.$connInfo->ClassName.'" is found at DB, but is no longer present at '.$connInfo->ClassFile.'.';
				$errMsgs[] = $errMsg;
				LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
			}
		}

		// Quit on error; let user fix those worst problems first
		if( count($errMsgs) > 0 ) {
			return;
		}
		
		// -> Here, the two collections have at least the same keys/entries.
		
		// Compare important properties of the connectors
		foreach( array_keys($fsConnInfos) as $pluginKey ) {
			foreach( array_keys($fsConnInfos[$pluginKey]) as $uniqueName ) {
				$fsConnInfo = $fsConnInfos[$pluginKey][$uniqueName];
				$dbConnInfo = $dbConnInfos[$pluginKey][$uniqueName];
				if( $fsConnInfo->Interface !== $dbConnInfo->Interface ) {
					$errMsg = 'The interface of Server Connector "'.$fsConnInfo->ClassFile.'" at file system differs from DB.';
				} else if( $fsConnInfo->Type !== $dbConnInfo->Type ) {
					$errMsg = 'The type of Server Connector "'.$fsConnInfo->ClassFile.'" at file system differs from DB.';
				} else if( $fsConnInfo->ClassName !== $dbConnInfo->ClassName ) {
					$errMsg = 'The class of Server Connector "'.$fsConnInfo->ClassFile.'" at file system differs from DB.';
				} else if( $fsConnInfo->ClassFile !== $dbConnInfo->ClassFile ) {
					$errMsg = 'The file name of Server Connector "'.$fsConnInfo->ClassFile.'" at file system differs from DB.';
				} else if( $fsConnInfo->Modified !== $dbConnInfo->Modified ) {
					$errMsg = 'The modification date of Server Connector "'.$fsConnInfo->ClassFile.'" at file system differs from DB.';
				} else {
					$errMsg = '';
				}
			}
			if( !empty( $errMsg ) ) {
				$errMsgs[] = $errMsg;
				LogHandler::Log('BizServerPlugin', 'ERROR', $errMsg );
			}
		}
	}
	
	/**
	 * Checks if the given plugin info is respecting the coding rules.
	 * It bails out on the first best error found. So after solving, you might hit the next problem.
	 *
	 * @param EnterprisePlugin $pluginObj
	 * @param PluginInfoData $pluginInfo
	 * @return string Programming- or configuration error (in English). Empty when no problems detected.
	 */
	static private function validatePluginObjAndInfo( EnterprisePlugin $pluginObj, PluginInfoData  $pluginInfo )
	{
		// The server plug-in version should never exceed length of 64 chars to fit into DB.
		if( strlen($pluginInfo->Version) > 64 ) {
			return 'The version "'.$pluginInfo->Version.'" string '.
				'of the Server plug-in "'.$pluginInfo->UniqueName.'" is too long. '.
				'Maximum allowed version string length is 64 characters.';
		}
		
		// All System plug-ins should have exact match with the core server. (Check added since 9.0.0.)
		if( $pluginInfo->IsSystem && $pluginInfo->Version != SERVERVERSION  ) {
			return 'The version string "'.$pluginInfo->Version.'" '.
				'of this system Server plug-in does not match with Enterprise Server '.
				'version "'.SERVERVERSION.'", which is required.';
		}
		
		// Check if the plug-in requires a minimum server version and error when
		// those requirements are not met by the current Enterprise Server.
		$reqVersion = $pluginObj->requiredServerVersion();
		if( !is_null( $reqVersion ) ) { // for example: '9.0.0 Build 123'
			$curVersion = explode( ' ', SERVERVERSION ); // split '9.0.0' from 'Build 123'
			$reqVersion = explode( ' ', $reqVersion );   // split '9.0.0' from 'Build 123'
			require_once BASEDIR . '/server/utils/VersionUtils.class.php';
			if( VersionUtils::versionCompare( $curVersion[0], $reqVersion[0], '<' ) ) {
				return 'Enterprise Server version '.$reqVersion[0].' '.
					'(or higher) is required for this Server plug-in. ';
			}
		}
		
		// Check if there are plugins still implementing obsoleted methods of the EnterprisePlugin interface.
		if( method_exists( $pluginObj, 'isInstalled' ) ) {
			$interfaceFile = BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
			$className = get_class( $pluginObj );
			return 'PHP class "'.$className.'" should no longer implement '.
				'the isInstalled() function. Reason is that Enterprise Server does no longer call it. '.
				'Instead, the plug-in should ship a Health Check module that validates the installation. '.
				'See "'.$interfaceFile.'" for more details. ';
		}
		
		// To guarantee file access, we first compose the file path to the PluginInfo.php 
		// file ourself and then check if it actually exists.
		$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
		$classFile = $classDir.$pluginInfo->UniqueName.'/PluginInfo.php';
		if( !is_readable( $classFile ) ) {
			return 'Plug-in info file "'.$classFile.'" is not readable or does not exists.';
		}
		
		// To guarantee file access on Linux (which is case sensitive) we ask for the
		// real path and compare it with the one we have composed ourself.
		/*$realFile = realpath( $classFile ); // does also resolve upper/lower case (for PHP < v5.3 only)
		//$pathInfo = pathinfo( $filePath ); // this source file as base
		if( DIRECTORY_SEPARATOR == '\\' ) { // for Windows make uniform file path (to compare later)
			$realFile = str_replace( '\\', '/', $realFile );
			//$pathInfo['dirname'] = str_replace( '\\', '/', $pathInfo['dirname'] );
		}
		if( $pluginInfo->UniqueName == 'elvis' || strpos( $realFile, $pluginInfo->UniqueName.'/PluginInfo.php' ) === false ) {
			return 'The file "'.$realFile.'" on disk is different than the composed file '.
				'"'.$classFile.'". Please check upper/lower case usage.';
		}*/
		
		return ''; // Tell caller all rules are respected.
	}

	/**
	 * Scans the server- and config- folders for installed plugins.
	 * These are also called system- and custom- plugins.
	 * For any bad name convention or missing plugin info, a BizException is thrown.
	 *
	 * @param EnterprisePlugin[] $pluginObjs  (Out)
	 * @param PluginInfoData[] $pluginInfos   (Out)
	 * @param EnterpriseConnector[] $connObjs (Out)
	 * @param ConnectorInfoData[] $connInfos  (Out)
	 * @param array $pluginErrs (Out) List of programming- or configuration errors (in English).
	 * @throws BizException
	 */
	static public function readPluginsFromFolders( &$pluginObjs, &$pluginInfos, &$connObjs, &$connInfos, &$pluginErrs )
	{
		// get plugins
		$customPlugins = self::readPluginObjectsFromFolders( BASEDIR.'/config/plugins', $pluginErrs );
		$systemPlugins = self::readPluginObjectsFromFolders( BASEDIR.'/server/plugins', $pluginErrs );

		// check duplicates
		$duplicatePlugins = array_intersect_key( $customPlugins, $systemPlugins );
		if( count($duplicatePlugins) ) foreach( $duplicatePlugins as $pluginKey => $duplicatePlugin ) {
			$pluginErrs[$pluginKey] = 'The '.$duplicatePlugin.' plugin at /config/plugins folder is already installed at /server/plugins folder.';
		}

		// merge plugins found at config- and server- folders
		$pluginObjs = array_merge( $customPlugins, $systemPlugins );

		// set/overrule system determined plugin properties (to avoid data corruption caused by plugins)
		foreach( $pluginObjs as $pluginKey => $pluginObj ) {
			$pluginInfo = $pluginObj->getPluginInfo();
			// Properties determined by system:
			$pluginInfo->Id = 0;
			$pluginInfo->UniqueName = $pluginKey;
			$pluginInfo->Version = (string)$pluginInfo->Version; // make it string to support === and !== compares
			if( empty($pluginInfo->DisplayName) ) $pluginInfo->DisplayName = $pluginKey; // done for plugins in error status
			//$pluginInfo->IsActive = true; // initialized at storePluginsAtDB() for new plugins only
			//$pluginInfo->IsInstalled = $pluginObj->isInstalled(); // check removed since 9.0.0
			$pluginInfo->IsSystem = array_key_exists( $pluginKey, $systemPlugins );
			$pluginInfos[$pluginKey] = $pluginInfo;
			
			if( !isset($pluginErrs[$pluginKey]) ) {
				$errMsg = self::validatePluginObjAndInfo( $pluginObj, $pluginInfo );
				if( $errMsg ) {
					$pluginErrs[$pluginKey] = $errMsg;
				}
			}
			if( !isset($pluginErrs[$pluginKey]) ) {
				$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
				$classFile = $classDir.$pluginInfo->UniqueName.'/PluginInfo.php';
				$pluginInfo->Modified = date( 'Y-m-d\TH:i:s', filemtime( $classFile ) );
	
				$tmpConnObjs = array();
				$tmpConnInfos = array();
				if( !isset( $pluginErrs[$pluginKey] ) ) { // skip reading connectors when plugin is in error state
					BizServerPlugin::readConnectorsFromFolders( $pluginObj, $pluginInfo, $tmpConnObjs, $tmpConnInfos, $pluginErrs );
				}
				if( isset( $pluginErrs[$pluginKey] ) ) { // unplug when read connectors error
					$pluginInfo->IsActive = false;
					$pluginInfo->IsInstalled = false;
				}
				$connObjs[$pluginKey] = $tmpConnObjs;
				$connInfos[$pluginKey] = $tmpConnInfos;
			}
		}
	}
	
	/**
	 * Scans the server- and config- folders for installed server connectors.
	 * For any bad name convention or missing connector info or class, a BizException is thrown.
	 *
	 * @param EnterprisePlugin $pluginObj     (In)
	 * @param PluginInfoData $pluginInfo      (In)
	 * @param EnterpriseConnector[] $connObjs (Out)
	 * @param ConnectorInfoData[] $connInfos  (Out)
	 * @param array $pluginErrs (Out) List of programming- or configuration errors (in English).
	 */
	static private function readConnectorsFromFolders( $pluginObj, $pluginInfo, &$connObjs, &$connInfos, &$pluginErrs )
	{
		$pluginDir = BASEDIR;
		$pluginDir .= $pluginInfo->IsSystem ? '/server' : '/config';
		$pluginDir .= '/plugins/'.$pluginInfo->UniqueName;
		$connTypeMap = array( 'WorkflowService', 'PlanningService', 'AdminService', 'SysAdminService', 'DataSourceService', 'AdmDatSrcService', 'PublishingService' );
							// Note: DefaultConnector types are NOT validated: 'WorkflowAction', 'DataSource', 'ContentSource', 'NameValidation'
		foreach( $pluginObj->getConnectorInterfaces() as $connIntf ) {
			// For example: $connIntf == WflStatusChange_EnterpriseConnector
			// ... in which case we expect class naming convention:
			//       class RemoveIntermediateVersions_WflStatusChange extends WflStatusChange_EnterpriseConnector
			// .. which inherits:
			//       class WflStatusChange_EnterpriseConnector extends DefaultConnector
			// .. which inherits:
			//       class DefaultConnector extends EnterpriseConnector
			$connIntfParts = explode( '_', $connIntf ); // => 0=WflStatusChange, 1=ActionConnector
			if( count($connIntfParts) < 2 || $connIntfParts[1] != 'EnterpriseConnector' ) {
				$pluginErrs[$pluginInfo->UniqueName] = 'Connector interface name '.$connIntf.' should end with '.
					'"_EnterpriseConnector". Given by getConnectorInterfaces() function at '.$pluginDir.'/PluginInfo.php.';
				return;
			}
			if( $connIntfParts[0] == 'WflGetDialog' ) {
				$pluginErrs[$pluginInfo->UniqueName] = 'Connector interface WflGetDialog is not longer supported. '.
					'Please migrate to WflGetDialog2. Also rename WflGetDialog into WflGetDialog2 '.
					'at your getConnectorInterfaces() function at '.$pluginDir.'/PluginInfo.php.';
				return;
			}
			$connClass = $pluginInfo->UniqueName.'_'.$connIntfParts[0]; // RemoveIntermediateVersions_WflStatusChange
			$connFile = $pluginDir.'/'.$connClass.'.class.php';
			if( !file_exists($connFile) ) {
				$pluginErrs[$pluginInfo->UniqueName] = 'Connector file "'.$connFile.'" does not exist.';
				return;
			}
			require_once $connFile;
			if( !class_exists( $connClass ) ) {
				$pluginErrs[$pluginInfo->UniqueName] = 'Connector class "'.$connClass.'" does not exist at file "'.$connFile .'".';
				return;
			}
			$connObj = new $connClass;
			if( !in_array( $connIntf, class_parents( $connObj)) ) {
				$pluginErrs[$pluginInfo->UniqueName] = 'Class "'.$connClass.'" does not extend "'.$connIntf.'" class.';
				return;
			}
			
			require_once BASEDIR.'/server/interfaces/plugins/ConnectorInfoData.class.php';
			$connInfo = new ConnectorInfoData();
			// Properties determined by plugin:
			$connInfo->Interface = $connIntfParts[0];
			$connInfo->Type      = $connObj->getConnectorType();
			$connInfo->Prio      = $connObj->getValidPrio();
			$connInfo->RunMode   = $connObj->getValidRunMode();
			// Properties determined by system:
			$connInfo->Id = 0;
			$connInfo->PluginId = $pluginInfo->Id;
			$connInfo->ClassName = $connClass;
			$connInfo->ClassFile = str_replace( BASEDIR, '', $connFile ); // remove BASEDIR
			$connInfo->Modified  = date ('Y-m-d\TH:i:s', filemtime( $connFile ));

			$connObjs[$connClass] = $connObj;
			$connInfos[$connClass] = $connInfo;

			// DefaultConnector connectors are free to determine their (custom) type.
			// ServiceConnector connectors are known by system and categorized by their type, so that have to be validated here.
			if( in_array( 'ServiceConnector', class_parents( $connObj)) ) { 
				if( !in_array($connInfo->Type, $connTypeMap) ) {
					$pluginErrs[$pluginInfo->UniqueName] = 'Connector type "'.$connInfo->Type.'" at file "'.$connFile .'" is unknown. Should be any of these: "'.implode('", "',$connTypeMap).'".';
					return;
				}
			}
		}
	}
	
	/**
	 * Stores the given server plugins at DB and removes and other plugin.
	 * Non existing ones are created and existing ones are updated.
	 * The very same is done for all plugin's connectors.
	 *
	 * @param EnterprisePlugin[] $pluginObjs  In/Out
	 * @param PluginInfoData[] $pluginInfos   In/Out
	 * @param EnterpriseConnector[] $connObjs In/Out
	 * @param ConnectorInfoData[] $connInfos  In/Out
	 * @throws BizException
	 */
	static private function storePluginsAtDB( $pluginObjs, &$pluginInfos,
					/** @noinspection PhpUnusedParameterInspection */ $connObjs, &$connInfos )
	{
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		require_once BASEDIR.'/server/dbclasses/DBServerConnector.class.php';
		require_once BASEDIR.'/server/dbclasses/DBAdmPubChannel.class.php';
		$connClasses = array();
		foreach( $pluginInfos as $pluginKey => &$editPlugin ) {
			// store plugin
			$pluginId = DBServerPlugin::getPluginId( $editPlugin );
			if( DBServerPlugin::hasError() ) {
				throw new BizException( 'ERR_DATABASE', 'Server', DBServerPlugin::getError() );
			}
			if( $pluginId ) {
				// update the plugin at DB
				$editPlugin->Id = $pluginId;
				$editPlugin = DBServerPlugin::updatePlugin( $editPlugin );
				if( DBServerPlugin::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBServerPlugin::getError() );
				}
			} else {
				// create the plugin at DB (and catch its DB id)
				$pluginObj = $pluginObjs[$editPlugin->UniqueName];
				try {
					// Install and/or enable plugin, and create/update in DB
					if( $pluginObj->isActivatedByDefault() ) {
						self::activatePlugin( $pluginObj, $editPlugin ); // install + activate
					} else {
						self::createOrUpdatePluginInDb( $editPlugin ); // install (without activate)
					}
				} catch( BizException $e ) {
				}
			}
			// store plugin's connectors
			if( isset($connInfos[$pluginKey]) ) foreach( $connInfos[$pluginKey] as &$connInfo ) {
				$connId = DBServerConnector::getConnectorId( $connInfo );
				$connInfo->PluginId = $editPlugin->Id;
				if( $connId ) {
					// update connector at DB
					$connInfo->Id = $connId;
					$connInfo = DBServerConnector::updateConnector( $connInfo );
				} else {
					// create the connector at DB (and cache its DB id)
					$connInfo = DBServerConnector::createConnector( $connInfo );
				}
				if( DBServerConnector::hasError() ) {
					throw new BizException( 'ERR_DATABASE', 'Server', DBServerConnector::getError() );
				}
				$connClasses[] = $connInfo->ClassName;
			}
		}
		// remove plugins (and all its connectors) from DB that are no longer on disk.
		$deletedPlugins = DBServerPlugin::deleteNonExistingPlugins( $pluginInfos );
		if( DBServerPlugin::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBServerPlugin::getError() );
		}
		// remove plugins connectors from DB that are no longer on disk.
		foreach( $deletedPlugins as $deletedPlugin ){
			DBAdmPubChannel::modifyPubChannelsSuggestionProvider( $deletedPlugin->UniqueName, '' );
			DBServerConnector::deleteConnectorsOnPluginId( $deletedPlugin->Id );
		}
		DBServerConnector::deleteConnectorsOnClassNames( $connClasses );
		if( DBServerConnector::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBServerConnector::getError() );
		}
		// Log the imported plugins at output folder for debug/support purposes
		self::logInstalledServerPlugins();
	}

	/**
	 * Scans the given folder for subfolder that contain a PluginInfo.php file.
	 *
	 * @param string $dirName directory path, no ending '/'.
	 * @throws BizException
	 * @return array of full paths of found PluginInfo.php files.
	 */
	static private function getPluginInfoFiles( $dirName )
	{
		$infoFiles = array();

		if ( $thisDir = opendir( $dirName ) ) {
			while ( ($itemName = readdir( $thisDir )) !== false ) {
				if ( $itemName[0] == '.' ) {
					// cur dir / parent dir
				} else if ( is_dir( $dirName.'/'.$itemName ) ) {
					$infoFile = $dirName.'/'.$itemName.'/PluginInfo.php';
					$infoFiles[$itemName] = $infoFile;
				}
			}
			closedir( $thisDir );
		}

		return $infoFiles;
	}
	
	/**
	 * Scans the given folder for subfolder that contain a PluginInfo.php file.
	 * When found, it loads the class file and checks if <FolderName>_EnterprisePlugin class exists.
	 * This is to make sure there won't be any duplicate classes.
	 *
	 * @param string $classFile Full path of a PluginInfo.php file.
	 * @param string $errMsg (Out) Message (in English) of programming- or configuration error.
	 * @return EnterprisePlugin|null The plugin object that inherit EnterprisePlugin class. NULL on error.
	 	When a class could not be created, InternalError_EnterprisePlugin is returned.
	 */
	private static function instantiatePluginObjectFromFile( $classFile, &$errMsg )
	{
		// When there is no PluginInfo.php file, assume an empty plug-in folder is created
		// where a new plug-in needs to be generated.
		if( LogHandler::debugMode() ) { // Integrators develop in debug mode. Do not disturb production.
			require_once BASEDIR.'/server/bizclasses/BizServerPluginGenerator.class.php';
			$generator = new BizServerPluginGenerator( dirname($classFile) );
			if( !file_exists( $classFile ) ) {
				$generator->createPluginFiles( $errMsg );
			}
		}
		
		// When there was already a PluginInfo.php file (or we just generated one above)
		// and compose the class name, e.g. Foo_EnterprisePlugin.
		$className = null;
		$pluginNameFromFile = null;
		if( !$errMsg ) { // Avoid overwriting any error set before.
			if( file_exists( $classFile ) ) {
				if( is_readable( $classFile ) ) {
					$fileParts = explode( '/', $classFile );
					$filePartCnt = sizeof( $fileParts );
					if( $filePartCnt > 1 ) {
						$pluginNameFromFile = $fileParts[$filePartCnt-2];
						$className = $pluginNameFromFile.'_EnterprisePlugin';
					} else { // should never happen / paranoid check
						$errMsg = 'No full file path provided for the "'.$classFile.'" file.';
					}
				} else {
					$errMsg = 'No read access to the "'.$classFile.'" file.';
				}
			} else {
				$errMsg = 'The "'.$classFile.'" file does not exist.';
			}
		} // else: error already recorded in $errMsg
		
		// Include the PluginInfo.php file and create the Foo_EnterprisePlugin object
		// from the class defined in that file. Check if the class inherits from EnterprisePlugin.
		$pluginObj = null;
		if( !$errMsg && // Avoid overwriting any error set before.
			$className ) {
			require_once $classFile;
			if( class_exists( $className ) ) {
				$pluginObj = new $className();
				$realClass = get_class( $pluginObj );
				if( $realClass == $className ) {
					if( !is_subclass_of( $pluginObj, 'EnterprisePlugin' ) ) {
						$pluginObj = null;
						$errMsg = 'Class "'.$className.'" should inherit from class EnterprisePlugin.';
					} // else :OK !
				} else {
					// PHP class names are case-insentive. Linux file paths are case-sensitive.
					// We have to make sure that the plug-in folder and the class have exact match.
					$classPrefix = substr( $realClass, 0, -strlen('_EnterprisePlugin') );
					$errMsg = 'The prefix "'.$classPrefix.'" of class  "'.$realClass.'" '.
						'should exactly match the Server plug-in\'s folder name "'.$pluginNameFromFile.'", '.
						'which is currently not the case. Please adjust the folder name or class name. '.
						'Make sure upper/lower case characters match as well. ';
				}
			} else {
				$errMsg = 'Class "'.$className.'" does not exist in file "'.$classFile.'".';
			}
		}
		
		// The integrator could have uncommented connectors in the getConnectorInterfaces()
		// function of the Foo_EnterprisePlugin class (defined in PluginInfo.php file).
		// When those connectors do not exist on disk, create an empty connector on-the-fly. 
		if( !$errMsg && // Avoid overwriting any error set before.
			isset( $generator ) ) {
			$generator->createConnectorFiles();
		}
		return $pluginObj;
	}
	
	/**
	 * Scans the given folder for subfolder that contain a PluginInfo.php file.
	 * When found, it loads the class file and checks if <FolderName>_EnterprisePlugin class exists.
	 * This is to make sure there won't be any duplicate classes.
	 *
	 * @param string $dirName Folder path to search through.
	 * @param array $pluginErrs (Out) List of programming- or configuration errors (in English).
	 * @return EnterprisePlugin[] The plugin objects that inherit EnterprisePlugin class. 
	 	When a class could not be created, InternalError_EnterprisePlugin is returned.
	 */
	static private function readPluginObjectsFromFolders( $dirName, &$pluginErrs )
	{
		$plugins = array();
		$files = self::getPluginInfoFiles( $dirName );
		foreach( $files as $fileDir => $file ) {
			$errMsg = '';
			$pluginObj = self::instantiatePluginObjectFromFile( $file, $errMsg );
			if( !$pluginObj ) {
				$pluginObj = new InternalError_EnterprisePlugin();
			}
			$plugins[$fileDir] = $pluginObj;
			if( $errMsg ) {
				$pluginErrs[$fileDir] = $errMsg;
			}
		}
		return $plugins;
	}
	
	/**
	 * Reads the plug-ins from the folders and stores them in the DB.
	 *
	 * @param EnterprisePlugin[] $pluginObjs  In/Out
	 * @param PluginInfoData[] $pluginInfos   In/Out
	 * @param EnterpriseConnector[] $connObjs In/Out
	 * @param ConnectorInfoData[] $connInfos  In/Out
	 * @param array $pluginErrs (Out) List of programming- or configuration errors (in English).
	 */
	static public function registerServerPlugins( &$pluginObjs, &$pluginInfos, &$connObjs, &$connInfos, &$pluginErrs )
	{
		// Scan plugins at config- and server- folders
		self::readPluginsFromFolders( $pluginObjs, $pluginInfos, $connObjs, $connInfos, $pluginErrs );

		// Save changed PluginInfoData to db
		self::storePluginsAtDB( $pluginObjs, $pluginInfos, $connObjs, $connInfos );

		// Change version of the bucket to invalidate all its items (connector info query results).
		WW_BizClasses_LocalCache::getInstance()->resetBucket( self::LOCAL_CACHE_BUCKET );
	}
	
	/**
	 * Creates a logging file at output folder and logs all installed server plugin details 
	 * in HTML table format.
	 *
	 * @return boolean True when plugins could be written to log file. Else false.
	 */
	static private function	logInstalledServerPlugins()
	{
		// Quit when logging disabled
   		if( !defined('OUTPUTDIRECTORY') || OUTPUTDIRECTORY == ''){
   			return false;
   		}

		// Create log file at output folder
		$fh = fopen( OUTPUTDIRECTORY.'ServerPlugins.htm', 'w' );
		if( !is_resource($fh) ) {
			return false; // error
		}

		// Build HTML table of installed plugins
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$plugins = DBServerPlugin::getPlugins();
		$title = 'Installed Server Plug-ins';
		$plnTable = '<table><tr><th>Name</th><th>Version</th><th>Active</th><th>System</th><th>Installed</th><th>Modified</th></tr>'."\n";
		if( $plugins ) foreach( $plugins as $plugin ) {
			$plnTable .= '<tr><td class="h">'.$plugin->DisplayName.'</td><td class="d">'.$plugin->Version.'</td>'.
						'<td class="d">'.($plugin->IsActive?'v':'-').'</td><td class="d">'.($plugin->IsSystem?'v':'-').'</td>'.
						'<td class="d">'.($plugin->IsInstalled?'v':'-').'</td><td class="d">'.$plugin->Modified.'</td></tr>'."\n";
		}
		$plnTable .= '</table>';
		
		// Write HTML table to file in XHTML 1.1 format
		// IMPORTANT: When you make changes below, validate them at http://validator.w3.org/#validate_by_upload
		$html = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<style type="text/css">
			table {border-collapse: collapse;}
			body, td, th, h1, h2 {font-family: sans-serif;}
			th { border: 1px solid #000000; vertical-align: baseline; font-weight: bold; background-color: #ffaa00; color: #ffffff; }
			td { border: 1px solid #000000; vertical-align: baseline; }
			.d {background-color: #eeeeee; color: #000000; }
			.h {background-color: #dddddd; font-weight: bold; color: #000000;}
		</style>

		<title>'.$title.'</title>
		<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />
	</head>
	<body style="text-align:center">
		<h1>'.$title.'</h1>
'.$plnTable.'		
	</body>
</html>';

		if( fwrite( $fh, $html ) === false ) {
			fclose( $fh );
			return false; // error
		}
		fclose( $fh );
		return true;
	}	
	
	/**
	 * Searches for the server plug-in that owns the given server plug-in connector.
	 *
	 * @param string $connName Class name of server plug-in connector
	 * @return PluginInfoData|null Returns null if plug-in was not found in DB.
	 */
	static public function getPluginForConnector( $connName )
	{
		// search for plugin based on internal name
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$plugin = new PluginInfoData();
		$plugin->UniqueName = self::getPluginUniqueNameForConnector( $connName );
		return DBServerPlugin::getPlugin( $plugin );
	}
	
	/**
	 * Derives the server plug-in name that owns the given server plug-in connector.
	 * It does -not- validate if the plugin actually exists; Use getPluginForConnector() for that.
	 *
	 * @param string $connName Class name of server plug-in connector
	 * @return string UniqueName of the plug-in
	 */
	static public function getPluginUniqueNameForConnector( $connName )
	{
		// take class prefix, which is used as internal name (e.g. 'Drupal')
		$classChucks = explode( '_', $connName );
		$uniqueName = array_shift( $classChucks ); 
		return $uniqueName;
	}
	
	/**
	 * Checks if a certain server plug-in is installed and enabled (active).
	 *
	 * @param string $pluginName Internal unique name of server plug-in
	 * @return bool true if installed and enabled, else false
	 */
	static public function isPluginActivated( string $pluginName ) : bool
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$pluginInfo = BizServerPlugin::getInstalledPluginInfo( $pluginName );
		return $pluginInfo && $pluginInfo->IsInstalled && $pluginInfo->IsActive;
	}
	
	/**
	 * Retrieves information of a plug-in that is installed in the database.
	 *
	 * @param string $pluginName Internal unique name of server plug-in
	 * @return PluginInfoData|null Returns null if plug-in was not found in DB.
	 */
	static public function getInstalledPluginInfo( $pluginName )
	{
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		$plugin = new PluginInfoData();
		$plugin->UniqueName = $pluginName;
		return DBServerPlugin::getPlugin( $plugin );
	}

	/**
	 * Retrieves the plug-in info object, as created from PHP class, as read from plugins folder.
	 *
	 * @param PluginInfoData $pluginInfo Internal unique name of server plug-in
	 * @throws BizException Throws BizException on programming- or configuration error.
	 * @return EnterprisePlugin|null Returns null if plug-in was not found on disk.
	 */
	static private function getInstalledPluginObj( PluginInfoData $pluginInfo )
	{
		$pluginErr = '';
		$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
		$classFile = $classDir.$pluginInfo->UniqueName.'/PluginInfo.php';
		$pluginObj = self::instantiatePluginObjectFromFile( $classFile, $pluginErr );
		if( $pluginErr ) {
			throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $pluginErr );
		}
		return $pluginObj;
	}

	/**
	 * Retrieve the installation folder of a given plug-in.
	 *
	 * @since 10.2.0
	 * @param PluginInfoData $pluginInfo
	 * @return string
	 */
	static public function getPluginFolder( PluginInfoData $pluginInfo )
	{
		$classDir = $pluginInfo->IsSystem ? BASEDIR.'/server/plugins/' : BASEDIR.'/config/plugins/';
		return $classDir.$pluginInfo->UniqueName.'/';
	}

	/**
	 * Enables (activates) a given server plug-in. The plug-in must have been installed before.
	 *
	 * @param string $pluginName
	 * @return PluginInfoData|null Info of the invoked plug-in, or null if plug-in was not found in DB.
	 * @throws BizException On DB error.
	 */
	static public function activatePluginByName( string $pluginName ): ?PluginInfoData
	{
		$pluginInfo = self::getInstalledPluginInfo( $pluginName );
		if( $pluginInfo ) {
			$pluginObj = self::getInstalledPluginObj( $pluginInfo );
			if( $pluginObj ) {
				self::activatePlugin( $pluginObj, $pluginInfo );
			}
		}
		// Change version of the bucket to invalidate all its items (connector info query results).
		WW_BizClasses_LocalCache::getInstance()->resetBucket( self::LOCAL_CACHE_BUCKET );
		return $pluginInfo;
	}
	
	/**
	 * Disables (deactivates) a given server plug-in. The plug-in must have been installed before.
	 *
	 * @param string $pluginName
	 * @return PluginInfoData|null Info of the invoked plug-in, or null if plug-in was not found in DB.
	 * @throws BizException On DB error.
	 */
	static public function deactivatePluginByName( string $pluginName ): ?PluginInfoData
	{
		$pluginInfo = self::getInstalledPluginInfo( $pluginName );
		if( $pluginInfo ) {
			$pluginObj = self::getInstalledPluginObj( $pluginInfo );
			if( $pluginObj ) {
				self::deactivatePlugin( $pluginObj, $pluginInfo );
			}
		}
		// Change version of the bucket to invalidate all its items (connector info query results).
		WW_BizClasses_LocalCache::getInstance()->resetBucket( self::LOCAL_CACHE_BUCKET );
		return $pluginInfo;
	}

	/**
	 * Enables (activates) a given server plug-in. The plug-in must have been installed before.
	 *
	 * @param EnterprisePlugin $pluginObj
	 * @param PluginInfoData $pluginInfo
	 * @throws BizException On installation failure or DB error.
	 */
	/*static public function installPlugin( EnterprisePlugin &$pluginObj, PluginInfoData &$pluginInfo )
	{
		if( !$pluginInfo->IsInstalled ) {
			$e = null;
			try {
				$pluginObj->runInstallation(); // might throw BizException
				$pluginInfo->IsInstalled = true;
			} catch( BizException $e ) { // installation error?
				// Continue, since we still need to register the plugin in DB, no matter
				// whether or not there were installation errors raised by the plugin.
				// In that case the IsInstalled flag is simply not set.
			}
			try {
				self::createOrUpdatePluginInDb( $pluginInfo );
			} catch( BizException $e ) { // DB error?
				$pluginInfo->IsInstalled = false; // undo
			}
			if( $e ) { // installation error or DB error?
				throw $e; // re-throw
			}
		}
	}*/
	
	/**
	 * Enables (activates) a given server plug-in. The plug-in must have been installed before.
	 *
	 * @param EnterprisePlugin $pluginObj
	 * @param PluginInfoData $pluginInfo
	 * @throws BizException $e On installation failure or DB error.
	 */
	static private function activatePlugin( EnterprisePlugin &$pluginObj, PluginInfoData &$pluginInfo ) : void
	{
		if( !$pluginInfo->IsInstalled || !$pluginInfo->IsActive ) {
			$e = null;
			$updatedInstalled = false;
			$updatedIsActive = false;
			try {
				if( !$pluginInfo->IsInstalled ) {
					$pluginObj->runInstallation(); // might throw BizException
					$pluginInfo->IsInstalled = true;
					$updatedInstalled = true;
				}
				if( !$pluginInfo->IsActive ) {
					$pluginObj->beforeActivation(); // might throw BizException
					$pluginInfo->IsActive = true;
					$updatedIsActive = true;
				}
			} catch( BizException $e ) { // installation error?
				// Continue, since we still need to register the plugin in DB, no matter
				// whether or not there were installation errors raised by the plugin.
				// In that case the IsInstalled flag is simply not set.
			}
			try {
				self::createOrUpdatePluginInDb( $pluginInfo );
			} catch( BizException $e ) { // DB error?
				if( $updatedInstalled ) {
					$pluginInfo->IsInstalled = false; // undo
				}
				if( $updatedIsActive ) {
					$pluginInfo->IsActive = false; // undo
				}
			}
			if( $e ) { // installation error or DB error?
				throw $e; // re-throw
			}
		}
	}

	/**
	 * Disables (deactivates) a given server plug-in. The plug-in must have been installed before.
	 *
	 * @param EnterprisePlugin $pluginObj
	 * @param PluginInfoData $pluginInfo
	 * @throws BizException on DB error.
	 */
	static private function deactivatePlugin( EnterprisePlugin &$pluginObj,	PluginInfoData &$pluginInfo ) : void
	{
		if( $pluginInfo->IsActive ) {
			try {
				$pluginInfo->IsActive = false;
				self::createOrUpdatePluginInDb( $pluginInfo );
			} catch( BizException $e ) {
				$pluginInfo->IsActive = true; // undo
				throw $e; // re-throw
			}
		}
	}
	
	/**
	 * Creates or updates a given server plug-in at the database. The passed in 
	 * info ($pluginInfo) will be updated only when create/update was successful.
	 *
	 * @param PluginInfoData $pluginInfo
	 * @throws BizException on DB error.
	 */
	private static function createOrUpdatePluginInDb( PluginInfoData &$pluginInfo )
	{
		require_once BASEDIR.'/server/dbclasses/DBServerPlugin.class.php';
		if( $pluginInfo->Id ) {
			$editInfo = DBServerPlugin::updatePlugin( $pluginInfo );
		} else {
			$editInfo = DBServerPlugin::createPlugin( $pluginInfo );
		}
		if( DBServerPlugin::hasError() ) {
			throw new BizException( 'ERR_DATABASE', 'Server', DBServerPlugin::getError() );
		}
		$pluginInfo = $editInfo; // return to caller
	}

	/**
	 * Returns the cached business connector to ensure that the connector is
	 * available within the session.
	 *
	 * @return array List of cached business connectors.
	 */
	private static function &getCachedBizConnectors()
	{
		static $connectors;
		if( !isset($connectors ) ) $connectors = array();
		return $connectors;
	}

	/**
	 * Tests if enterprise has activated plugins for given interface.
	 *
	 * @param string $interface name of interface
	 * @return bool Whether or not active plugins are found
	 */
	public static function hasActivePlugins( $interface )
	{
		return count( self::searchConnectors( $interface, null ) ) != 0;
	}
}
