<?php
/**
 * Server Plug-in Generator.
 *
 * Creates Custom Server Plug-in files on-the-fly to speed up development of new integrations.
 * See config/plugin-templates/readme.txt for more info.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since 9.0.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * IMPORTANT: There are two types of line endings:
 *   Windows: CR+LF = "\r\n" = 0x0D0A = "\015\012"
 *   Mac OSX:    LF =   "\n" =   0x0A =     "\012"
 * The PHP templates read from disk have Windows line endings, just like any other PHP
 * file in the Enterprise package. Reason is that the setup builder machine is Windows
 * and that its Perforce settings tell to convert to local line endings, which is Windows.
 * Important to realize here is that this package is used by integrators too who'll run this
 * generator class (which reads the template files) on Windows, but also on Mac OSX. 
 * This is different from the core server developers who retrieve the template files directly
 * from Perforce; Those file would get transformed into local line endings, which now are Mac.
 * When this generator class would use PHP_EOL to inject PHP fragments into those templates,
 * there would be a mixture of Windows and Mac line endings, which would confuse text
 * editors drawing many lines on the same row. To prevent this, the template files types are
 * changed into "binary" to use Windows line endings on Windows- and Mac development machines
 * and the shipped Enterprise package. And, the generator class uses "\r\n" (instead of
 * PHP_EOL) to make sure all injected code fragments respect the Windows line endings,
 * no matter the generator runs on Mac or Windows or ran by core developer or integrator.
 */

class BizServerPluginGenerator
{
	/**
	 * Full file path to the server plug-in folder.
	 * @var string
	 */
	private $pluginDir = null;

	/**
	 * Constructor that accepts a plugin folder to work on.
	 *
	 * @param string $pluginDir Full file path to the server plug-in folder.
	 */
	public function __construct( $pluginDir )
	{
		$this->pluginDir = $pluginDir;
	}
	
	/**
	 * When there is not PluginInfo.php file in the given server plug-in folder,
	 * this function creates the following files based on config/plugin-templates files:
	 * - config.php (based on the config.template file)
	 * - PluginInfo.php (based on the PluginInfo.template.php file)
	 * - NOTICE (based on the NOTICE.template file)
	 * - LICENSE (based on the LICENSE.template file)
	 *
	 * @param string $errMsg (Out) Message (in English) of programming- or configuration error.
	 */
	public function createPluginFiles( &$errMsg )
	{
		if( $this->createPluginInfoFile( $errMsg ) ) { // only do once for all
			$this->createConfigFile();
			$this->createNoticeFile();
			$this->createLicenseFile();
		}
	}

	/**
	 * Derives the internal server plug-in name from the plug-in's folder name.
	 *
	 * @return string The plug-in's internal name.
	 */
	private function getPluginInternalName()
	{
		return basename( $this->pluginDir );
	}

	/**
	 * Builds display name from internal name by simply inserting a space before each
	 * uppercase char (assumeing it is in camel case). So HelloWorld becomes Hello World.
	 *
	 * @return string The plug-in's display name.
	 */
	private function getPluginDisplayName()
	{
		return trim( preg_replace( '/([A-Z])([^A-Z]*)/', ' $1$2', $this->getPluginInternalName() ) );
	}
	
	/**
	 * Returns the Enterpise Server major.minor version and build date (YYYYMMDD).
	 *
	 * @return string Server version
	 */
	private function getPluginVersion()
	{
		// Let the server plugin version match with the current server version and insert that
		// hard-coded into the info file so you can always see for which server it is designed.
		require_once BASEDIR.'/server/serverinfo.php'; // SERVERVERSION
		$version = explode( '.', SERVERVERSION );
		return $version[0] .'.'. $version[1].' '.date('Ymd');
	}
	
	/**
	 * Replaces the following template parameters/placeholders in a given template file body
	 * with real data:
	 *    %internalName% => The internal plug-in name (file safe name)
	 *    %displayName%  => Human readable plug-in name (shown in admin UI)
	 *    %version%      => Plug-in version (derived from current Enterprise Server)
	 *    %year%         => Copyright year (current year)
	 * 
	 * @param string Template content
	 * @return string Updated template content (params filled in)
	 */
	private function substituteTemplateFields( $template )
	{
		$template = str_replace( '%plugin%', $this->getPluginInternalName(), $template );
		$template = str_replace( '%displayName%', $this->getPluginDisplayName(), $template );
		$template = str_replace( '%version%', $this->getPluginVersion(), $template );
		$template = str_replace( '%year%', date('Y'), $template );
		return $template;
	}
	
	/**
	 * Creates a PluginInfo.class.php file in the plug-in's folder which is derived
	 * from the PluginInfo.template.php file. This is only done when the file is missing.
	 * Notice: This requires write access to the config/plugins/YourPlugin folder.
	 *
	 * @param string $errMsg (Out) Message (in English) of programming- or configuration error.
	 * @return bool True when the file can be created, False otherwise.
	 */
	private function createPluginInfoFile( &$errMsg )
	{
		$createdFile = false;
		$pluginInfoFile = $this->pluginDir.'/PluginInfo.php';
		if( !file_exists( $pluginInfoFile ) ) {
		
			// Collect all service- and biz connectors by iterating through the PHP files 
			// under the /server/interfaces/services tree that have a _EnterpriseConnector postfix.
			$connectors = '';
			$spath = BASEDIR . '/server/interfaces/services';
			$sh = opendir( $spath );
			while( false !== ($dirname = readdir($sh)) ) {
				if( is_dir($spath . '/' . $dirname ) && $dirname[0] != '.' ) {
					$dh  = opendir($spath . '/' . $dirname);
					$connectors .= "\r\n// $dirname services\r\n";
					while (false !== ($filename = readdir($dh))) {
						if ( strpos($filename,'_EnterpriseConnector') > 0 ) {
							$filename = str_replace('.class.php', '', $filename);
							$connectors .= "\t\t\t// '".$filename . "',\r\n";
						}
					}
				}
			}
			
			$connectors .= "\r\n// business connectors\r\n";
			$spath = BASEDIR . '/server/interfaces/plugins/connectors';
			$sh = opendir( $spath );
			while( false !== ($filename = readdir($sh)) ) {
				if ( strpos($filename,'_EnterpriseConnector.class.php') > 0 ) {
					$filename = str_replace('.class.php', '', $filename);
					$connectors .= "\t\t\t// '".$filename . "',\r\n";
				}
			}
			
			// Read the info template file (PluginInfo.template.php) and auto fill-in all the 
			// determined fields above and save it as PluginInfo.class.php, which gets included runtime.
			$template = file_get_contents( BASEDIR.'/config/plugin-templates/PluginInfo.template.php' );
			$template = $this->substituteTemplateFields( $template );
			$template = str_replace( '%connectorList%', $connectors, $template );
			@file_put_contents( $pluginInfoFile, $template );
			@chmod( $pluginInfoFile, 0777 );
			// L> Uses @ to suppress PHP notices since we do error handler below.
			
			// Reflect file creation above and check if that was successful.
			clearstatcache();
			if( file_exists( $pluginInfoFile ) ) {
				$createdFile = true;
			} else {
				$errMsg = 'Could not create the "'.$pluginInfoFile.'" file. '.
					'Please check if the internet user has write access to the plug-in folder.';
			}
		}
		return $createdFile;
	}
			
	/**
	 * Create an empty config.php in the plug-in's folder.
	 * This is only done when the file is missing.
	 * Notice: This requires write access to the config/plugins/YourPlugin folder.
	 */
	private function createConfigFile()
	{
		$configFile = $this->pluginDir.'/config.php';
		if( !file_exists( $configFile ) ) {
			$template = file_get_contents( BASEDIR.'/config/plugin-templates/config.template.php' );
			$template = $this->substituteTemplateFields( $template );
			file_put_contents( $configFile, $template );
			chmod( $configFile, 0777 );
		}
	}
			
	/**
	 * Creates the NOTICE file in the plug-in's folder based on this template file:
	 *    config/plugin-templates/NOTICE.template
	 * This is only done when the file is missing in the server plugin folder.
	 * Notice: This requires write access to the config/plugins/YourPlugin folder.
	 */
	private function createNoticeFile()
	{
		$noticeFile = $this->pluginDir.'/NOTICE';
		if( !file_exists( $noticeFile ) ) {
			$template = file_get_contents( BASEDIR.'/config/plugin-templates/NOTICE.template' );
			$template = $this->substituteTemplateFields( $template );
			file_put_contents( $noticeFile, $template );
			chmod( $noticeFile, 0777 );
		}
		
	}

	/**
	 * Creates the LICENSE file in the plug-in's folder based on this template file:
	 *    config/plugin-templates/LICENSE.template
	 * This is only done when the file is missing in the server plugin folder.
	 * Notice: This requires write access to the config/plugins/YourPlugin folder.
	 */
	private function createLicenseFile()
	{
		$licenseFile = $this->pluginDir.'/LICENSE';
		if( !file_exists( $licenseFile ) ) {
			$template = file_get_contents( BASEDIR.'/config/plugin-templates/LICENSE.template' );
			$template = $this->substituteTemplateFields( $template );
			file_put_contents( $licenseFile, $template );
			chmod( $licenseFile, 0777 );
		}
	}
	
	/**
	 * Creates connector files based on the ServiceConnector.template.php file or the
	 * BusinessConnector.template.php file.
	 * Only connector files are generated that are returned by the getConnectorInterfaces()
	 * function but do not exist in the plug-in folder yet.
	 * The caller should include the PluginInfo.class.php file before calling this function.
	 */
	public function createConnectorFiles()
	{
		$pluginName = $this->getPluginInternalName();
		$pluginInfoClass = $pluginName.'_EnterprisePlugin';
		$pluginInfo = new $pluginInfoClass();
		$connInterfaces = $pluginInfo->getConnectorInterfaces();
		foreach( $connInterfaces as $connInterface ) {
			$service = str_replace( '_EnterpriseConnector', '', $connInterface );
			$connectorFile = $this->pluginDir.'/'.$pluginName.'_'.$service.'.class.php';
			if( !file_exists( $connectorFile ) ) {
				$interfacePrefix = strtolower( substr( $connInterface, 0, 3 ) );
				$isBizConnector = file_exists( BASEDIR.'/server/interfaces/plugins/connectors/'.$connInterface.'.class.php' );
				if( $isBizConnector ) {
					$template = file_get_contents( BASEDIR.'/config/plugin-templates/BusinessConnector.template.php' );
				} else {
					$template = file_get_contents( BASEDIR.'/config/plugin-templates/ServiceConnector.template.php' );
				}
				$template = $this->substituteTemplateFields( $template );
				$template = str_replace( '%interface%', $interfacePrefix, $template );
				$template = str_replace( '%service%', $service, $template );
				if( $isBizConnector ) {
					$body = $this->composeBizConnectorClass( $connInterface );
					$template = str_replace( '%methods%', $body, $template );
				}
				file_put_contents( $connectorFile, $template );
				chmod( $connectorFile, 0777 );
			}
		}
	}
	
	/**
	 * Replaces the %methods% template field with a list of methods to be implemented
	 * by a business connector.
	 *
	 * @param string $connInterface
	 * @return string Updated business connector body (PHP content).
	 */
	private function composeBizConnectorClass( $connInterface )
	{
		require_once BASEDIR.'/server/interfaces/plugins/connectors/'.$connInterface.'.class.php';
		
		$body = '';
		//require_once 'Zend/Reflection/Class.php';
		//$class = new Zend_Reflection_Class( $connInterface );
		$class = new ReflectionClass( $connInterface );
		foreach( $class->getMethods() as $method ) {
			
			// Skip functions we do not want to implement.
			$superMethod = ($method->getDeclaringClass()->name != $connInterface); // inherit from super class?
			if( $method->isConstructor() || $method->isDestructor() || 
				$method->isPrivate() || $method->isFinal() ||
				($superMethod && !$method->isAbstract()) ) {
				continue;
			}
			
			// Copy the phpDocumentor comments of function header.
			$comments = $method->getDocComment();
			if( $comments ) {
				$comments = str_replace( PHP_EOL, "\r\n", $comments ); // Force Windows line endings
				$body .= "\t".$comments."\r\n\t";
			} else {
				$body .= "\t";
			}
			
			// Declare the function: [static] [protected|public] function <name>
			if( $method->isStatic() ) {
				$body .= 'static ';
			}
			$body .= $method->isProtected() ? 'protected ' : 'public ';
			$body .= 'function '.$method->name;
			// Note that in the above, abstraction functions implicitly become non-abstract.
			
			// Copy the function parameters.
			$params = $this->getMethodParams( $method );
			$body .= $params ? '( '.$params.' ) ' : '() ';
			
			if( $method->isAbstract() ) {	
				$body .= "\r\n\t{\t\t\r\n\t}";
			} else {
				
				if( $method->getEndLine() == $method->getStartLine() ) { // one-liner?
					$lines = array_slice(
						file( $method->getDeclaringClass()->getFileName(), FILE_IGNORE_NEW_LINES ),
						$method->getStartLine()-1, 2, true
					);
					// Skip function declaration and just grab the body between the {} brackets.
					$singleLine = array_shift( $lines );
					$body .= substr( $singleLine, strpos( $singleLine, '{' ) );
				} else { // multi-line function body?
					$lines = array_slice(
						file( $method->getDeclaringClass()->getFileName(), FILE_IGNORE_NEW_LINES ),
						$method->getStartLine(),
						$method->getEndLine() - $method->getStartLine(),
						true
					);
					$body .= "\r\n".implode( "\r\n", $lines );
				}
				// Note: Zend_Reflection_Method->getBody() tries to do the same as the code fragment 
				// above but fails badly on function bodies that simply end with two } brackets.
			}
			//$body .= '// Source: '.$method->getDeclaringClass()->getFileName();
			//$body .= '#'.$method->getStartLine().'-#'.$method->getEndLine()."\r\n";
			$body .= "\r\n\r\n";
		}
		return $body;
	}
	
	/**
	 * Composes a PHP code fragment that defines the parameters of a given class method.
	 * Returned value is a comma separated string that can be put between () brackets
	 * of the function declaration.
	 *
	 * @param ReflectionMethod $method
	 * @return string PHP fragment that defines the parameters of the method.
	 */
	private function getMethodParams( $method )
	{
		$parameters = array();
		foreach( $method->getParameters() as $par ) {
			$paramStr = '';
			
			// Declare array or class
			if( $par->isArray() ) {
				$paramStr .= 'array ';
			} else {
				$parClass = $par->getClass();
				if( $parClass ) {
					$paramStr .= $parClass->getName().' ';
				}
			}
			
			// Add reference: &
			if( $par->isPassedByReference() ) {
				$paramStr .= '&';
			}
			
			// Add paramter name.
			$paramStr .= '$'.$par->name;
			
			// Add default value.
			if( $par->isOptional() ) {
				$defaultValue = $par->getDefaultValue();
				if( is_array( $defaultValue ) ) {
					$defaultValueArr = $defaultValue;
					$defaultValue = 'array( ';
					$comma = '';
					foreach( $defaultValueArr as $key => $val ) {
						$keyStr = is_string($key) ? "'$key'" : (string)$key;
						$valStr = is_string($val) ? "'$val'" : (string)$val;
						$defaultValue .= $comma . $keyStr . ' => ' . $valStr;
						$comma = ', ';
					}
					$defaultValue .= ' )';
				}
				$paramStr .= ' = '.$defaultValue;
			}
			$parameters[] = $paramStr;
		}
		
		// Build comma separated string of parameters.
		return implode( $parameters, ', ' );
	}
}