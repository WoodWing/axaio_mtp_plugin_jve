<?php
/**
 * @package     Enterprise
 * @subpackage  BuildTools
 * @since       10.2.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Creates a class that can generate PHP classes for a web service interface.
 *
 * After construction, the validate() function should be called to validate and initialise. When the web service
 * definitions are valid, the getWebInterfaces() can be called to iterate through the interfaces that are implemented by
 * the service provider. For each interface, the createGeneratorForInterface() can be called to create a generator factory.
 * The factory can be used to generate the PHP classes, see WW_BuildTools_GenServices_WebServiceClassesGenerator.
 */
class WW_BuildTools_GenServices_WebServiceClassesGeneratorFactory
{
	/** @var string $errorMsg Contains validation errors. Empty when all definitions are ok. */
	private $errorMsg;

	/** @var string $plugin Optionally the internal name of a server plugin to generate classes for. */
	private $plugin;

	/** @var string $version Optionally the version of a server plugin to generate classes for. */
	private $version;

	/** @var string $plugin Optionally the location that the plugin resides in. */
	private $dir;

	/** @var WW_BuildTools_GenServices_WebServiceProviderInterface $provider Tells which interfaces to generate classes for. */
	private $provider;

	/** @var string $genServicesDir The full path of the buildtools/genservices location of core server or of server plugin. */
	private $genServicesDir;

	/** @var string[] $interfaceDirs List of full path of the buildtools/genservices/interfaces/<interface> locations to read definitions from. */
	private $interfaceDirs;

	/** @var WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface[] $interfaceDefs Interface descriptors to generate classes for. */
	private $interfaceDefs;

	/**
	 * Constructor.
	 *
	 * @param string $plugin Name of the server plugin to generate web service classes for.
	 *    Empty when web service classes for the core Enterprise Server should be generated instead.
	 * @param string $dir Location of the plugin directory that the plugin resides in.
	 */
	public function __construct( $plugin, $version, $dir )
	{
		$this->plugin = $plugin;
		$this->version = $version;
		$this->dir = $dir;
		$this->errorMsg = '';
	}

	/**
	 * Validates the web service provider and its web service interface definitions.
	 *
	 * @return bool Whether or not valid. When not valid, call getErrorMessage() to get details.
	 */
	public function validate()
	{
		do {
			$this->validateAndInstantiateServiceProvider();
			if( $this->errorMsg ) {
				break; // bail out
			}
			$this->validateServiceProviderInterfaces();
			if( $this->errorMsg ) {
				break; // bail out
			}
			$this->validateAndInstantiateInterfaceDefinitions();
			if( $this->errorMsg ) {
				break; // bail out
			}
			$this->validateServiceProviderProtocols();
			if( $this->errorMsg ) {
				break; // bail out
			}
		} while( false );
		return !$this->errorMsg;
	}

	/**
	 * Provides any error message of the validation process. Should be called when validate() has returned FALSE.
	 *
	 * @return string Error message.
	 */
	public function getErrorMessage()
	{
		return $this->errorMsg;
	}

	/**
	 * Constructs the web service classes generator.
	 *
	 * @param string $webInterface Abbreviation of the web service interface to generate classes for.
	 * @return WW_BuildTools_GenServices_WebServiceClassesGenerator|null
	 */
	public function createGeneratorForInterface( $webInterface )
	{
		require_once BASEDIR.'/server/buildtools/genservices/WebServiceClassesGenerator.class.php';
		$generator = null;
		if( isset($this->interfaceDefs[$webInterface] ) ) {
			$interfaceDef = $this->interfaceDefs[ $webInterface ];
			$generator = new WW_BuildTools_GenServices_WebServiceClassesGenerator( $interfaceDef, $this->provider->getProtocols(), $this->plugin, $this->version );
		}
		return $generator;
	}

	/**
	 * Lists the web service interface definitions.
	 *
	 * @return string[] Abbreviations of web service interfaces.
	 */
	public function getWebInterfaces()
	{
		return array_keys($this->interfaceDefs);
	}

	/**
	 * Locates, includes, validates and instantiates the web service provider of the core server or server plugin.
	 *
	 * It includes the PHP class module and constructs the class and checks if it implements the correct class interface.
	 * The name of the class is composed as follows:
	 *    server: WW_BuildTools_GenServices_Interfaces_<Interface>_WebServiceProvider
	 *    plugin: <plugin>_BuildTools_GenServices_Interfaces_<Interface>_WebServiceProvider
	 * The class interface it should implement is WW_BuildTools_GenServices_WebServiceProviderInterface.
	 *
	 * When not valid, $this->errorMsg is set.
	 */
	private function validateAndInstantiateServiceProvider()
	{
		$this->genServicesDir = null;
		$this->provider = null;
		$workDir = null;
		if( $this->plugin ) {
			$baseDirs = array( BASEDIR.'/config/plugins', BASEDIR.'/server/plugins' );
			if( $this->dir ) {
				$baseDirs = array( $this->dir );
			}
			foreach( $baseDirs as $baseDir ) {
				$pluginDir = $baseDir.'/'.$this->plugin;
				if( file_exists( $pluginDir ) ) {
					$workDir = $pluginDir;
					break;
				}
			}
			if( !$workDir ) {
				$this->errorMsg = "Server Plug-in $this->plugin not found.";
			}
		} else { // core server
			$workDir = BASEDIR.'/server';
		}
		if( $workDir ) {
			$genDir = $workDir.'/buildtools/genservices';
			$classPrefix = $this->plugin ? $this->plugin : 'WW';
			$className = $classPrefix.'_BuildTools_GenServices_WebServiceProvider';
			if( $this->instantiateFromClassDefinition( $genDir, 'WebServiceProvider.class.php',
				$className, 'WW_BuildTools_GenServices_WebServiceProviderInterface' ) ) {
				$this->genServicesDir = $genDir;
				$this->provider = new $className;
			} // else: $this->errorMsg is populated by instantiateFromClassDefinition()
		}
	}

	/**
	 * Requests the web service provider for its interfaces, validates their names and checks if their file paths exist.
	 *
	 * When not valid, $this->errorMsg is set.
	 */
	private function validateServiceProviderInterfaces()
	{
		$this->interfaceDirs = array();
		$className = get_class( $this->provider );
		$webInterfaces = $this->provider->getInterfaces();
		if( $webInterfaces ) {
			foreach( $webInterfaces as $webInterface ) {
				if( strlen( $webInterface ) !== 3 ||
					strtolower( $webInterface ) !== $webInterface ||
					!ctype_alpha( $webInterface )
				) {
					$this->errorMsg = "The class {$className} provides any interface {$webInterface} ".
						"with bad format. It should consist of 3 lowercase alphabetic characters. ".
						"Please check its getInterfaces() function. ";
					break;
				}
				$interfaceDir = $this->genServicesDir.'/interfaces/'.$webInterface;
				if( file_exists( $interfaceDir ) && is_readable( $interfaceDir ) ) {
					$this->interfaceDirs[ $webInterface ] = $interfaceDir;
				} else {
					$this->errorMsg = "Folder {$interfaceDir} does not exists or is not readable.";
					break;
				}
			}
		} else {
			$this->errorMsg = "The class {$className} does not provide any interfaces. ".
				"Please check its getInterfaces() function. ";
		}
	}

	/**
	 * Locates, includes, validates and instantiates the interfaces given by the web service provider.
	 *
	 * The file of the module is composed as follows:
	 *    server: Enterprise/server/buildtools/genservices/interfaces/<interface>/WebServiceInterfaceDescriptor.class.php
	 *    plugin: Enterprise/[config|server]/buildtools/genservices/interfaces/<interface>/WebServiceInterfaceDescriptor.class.php
	 * The class interface it should implement is WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface.
	 *
	 * When not valid, $this->errorMsg is set.
	 */
	private function validateAndInstantiateInterfaceDefinitions()
	{
		$this->interfaceDefs = array();
		foreach( $this->interfaceDirs as $webInterface => $interfaceDir ) {
			$classPrefix = $this->plugin ? $this->plugin : 'WW';
			$className = $classPrefix.'_BuildTools_GenServices_Interfaces_'.ucfirst($webInterface).'_WebServiceDescriptor';
			if( $this->instantiateFromClassDefinition( $interfaceDir, 'WebServiceDescriptor.class.php',
				$className, 'WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface' ) ) {
				$this->interfaceDefs[$webInterface] = new $className;
			} // else: $this->errorMsg is populated by instantiateFromClassDefinition()
		}
	}

	/**
	 * Checks if the generator supports the required protocols.
	 */
	private function validateServiceProviderProtocols()
	{
		$protocols = $this->provider->getProtocols();
		$provider = $this->plugin ? $this->plugin : 'Enterprise Server';
		if( $protocols ) {
			foreach( $protocols as $protocol ) {
				switch( $protocol ) {
					case 'soap':
					case 'amf':
						if( $this->plugin ) {
							$this->errorMsg = "The {$provider} service provider seems to require the {$protocol} protocol. ".
								"Although the generator supports this for the core server, it has no support for server plugins yet. ".
								"Either make the json protocol as the only requirement or adjust the generator to let it support ".
								"the required protocol for server plugins.";
						}
						break;
					case 'json':
						break;
					default:
						$this->errorMsg = "The {$provider} service provider seems to require the {$protocol} protocol which is ".
							"not supported by the generator. Either remove the requirement from the service provider or implement ".
							"the protocol for the generator.";
						break;
				}
			}
		} else {
			$this->errorMsg = "The {$provider} service provider seems to require no protocols, which does not make sense. ".
				"Please adjust the provider and let it require one or more protocols, such as soap, amf or json.";
		}
	}

	/**
	 * Includes a class module, validates its interface and instantiates a class when valid.
	 *
	 * When not valid, $this->errorMsg is set.
	 *
	 * @param string $moduleDir The folder path the PHP module should be present in.
	 * @param string $moduleName The file name of the PHP module that should define the class.
	 * @param string $className The name of the class to be found in the module.
	 * @param string $interfaceName The interface the class should implement.
	 * @return mixed|null Instantiated class, or NULL when not found or not valid.
	 */
	private function instantiateFromClassDefinition( $moduleDir, $moduleName, $className, $interfaceName )
	{
		$retVal = null;
		if( file_exists( $moduleDir ) && is_readable( $moduleDir ) ) {
			$classFile = $moduleDir.'/'.$moduleName;
			if( file_exists( $classFile ) && is_readable( $classFile ) ) {
				require_once $classFile;
				if( class_exists( $className ) ) {
					$classInstance = new $className;
					$classInterfaces = class_implements( $classInstance );
					if( in_array( $interfaceName, $classInterfaces ) ) {
						$retVal = $classInstance;
					} else {
						$this->errorMsg = "The class {$className} does not implement the {$interfaceName} interface.";
					}
				} else {
					$this->errorMsg = "File {$classFile} does not contain a class named {$className}.";
				}
			} else {
				$this->errorMsg = "File {$classFile} does not exists or is not readable.";
			}
		} else {
			$this->errorMsg = "Folder {$moduleDir} does not exists or is not readable.";
		}
		return $retVal;
	}
}
