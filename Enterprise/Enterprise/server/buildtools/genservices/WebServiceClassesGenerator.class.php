<?php
/**
 * @since       7.6.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Generates PHP/Flex/Java classes and HTML documentation from web service interfaces (WSDLs).
 */

// --------------------------------------------------------
// Note#001: Note that getDataClassPrefix() is NOT called, but getServiceNameShort().
// This is because we -always- want a prefix, also for workflow (Wfl).
// Or else, Flex would give compiler errors in some cases:
//    1146: A constructor cannot be a getter or setter method.	Message.as	.../wfl/dataclasses	line 1	Flex Problem
//    1021: Duplicate function definition.	Message.as	.../wfl/dataclasses	line 1	Flex Problem
//    1146: A constructor cannot be a getter or setter method.	Message.as	.../wfl/dataclasses	line 1	Flex Problem
//    1130: A constructor cannot specify a return type.	Message.as	.../wfl/dataclasses	line 24 Flex Problem
// Aside to the Message class reported above, the same error would raise for the Rendition, Term and Setting classes.
// 
// Reason is that in Flex, a class member can not have same name as the class itself.
// For example, this is a problem:
//    public class Message
//    {
//       public function Message() {}
//       ...
//       public var Message:String; // => PROBLEM!
//
// This is solved by -always- using the prefix for class names:
//    public class WflMessage
//    {
//       public function WflMessage() {}
//       ...
//       public var Message:String; // => OK!
//
// At server side, the Message class can still be found since this line
// is added to the class:
//    [RemoteClass(alias="Message")]
// --------------------------------------------------------

class WW_BuildTools_GenServices_WebServiceClassesGenerator
{
	public $SuccessFiles = array();
	public $ErrorFiles = array();
	public $SkippedFiles = array();

	public $Warnings = array();
	public $FatalErrors = array();

	/** @var DOMXPath $XPath */
	private $XPath;

	/** @var WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface $intfDescriptor */
	private $intfDescriptor;

	/** @var string[] $protocols */
	private $protocols;

	/** @var string $packageName */
	private $packageName;

	/**
	 * Constructor.
	 *
	 * @param WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface $interfaceDescriptor
	 * @param string[] $protocols List of protocols required by the web service provider.
	 * @param string $packageName (optional) Package name of the web service provider.
	 * @param string $packageVersion (optional) Package version of the web service provider.
	 */
	public function __construct( WW_BuildTools_GenServices_Interfaces_WebServiceDescriptorInterface $interfaceDescriptor, $protocols, $packageName = null, $packageVersion = null )
	{
		$this->intfDescriptor = $interfaceDescriptor;
		$this->protocols = $protocols;
		$this->packageName = $packageName;
		if( !$packageName ) {
			$this->packageName = 'Enterprise';
		}
		$this->packageVersion = $packageVersion;
		if( !$packageVersion ) {
			require_once BASEDIR.'/server/serverinfo.php'; // SERVERVERSION
			$version = explode( '.', SERVERVERSION );
			$this->packageVersion = $version[0] .'.'. $version[1];
		}

		// parse wsdl
		$wsdlFile = $this->intfDescriptor->getWsdlFilePath();
		$wsdlDoc = new DOMDocument();
		$cont = file_get_contents( $wsdlFile );
		if( !$wsdlDoc->loadXML( $cont ) ) die( 'Could not parse '.$wsdlFile.PHP_EOL );
		$this->XPath = new DOMXPath( $wsdlDoc );
		$this->XPath->registerNameSpace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
		$this->XPath->registerNameSpace('schm', 'http://www.w3.org/2001/XMLSchema');
		$this->XPath->registerNameSpace('dime', 'http://schemas.xmlsoap.org/ws/2002/04/dime/wsdl/');
	}

	/**
	 * Composes a list of functions that should be called one by one to generate all classes.
	 *
	 * The list is indexed with the logical names of the generation steps. It allows the caller
	 * to show end user which step is currently processing.
	 *
	 * @since 10.2.0
	 * @return string[] List of functions, indexed with logical names of the processing steps.
	 */
	public function getProcessingSteps()
	{
		$pluginFull = $this->intfDescriptor->getPluginNameFull();
		$funcs = array();
		$funcs['Validate parameters'] = 'validateParameters';
		$funcs['Services class'] = 'generateServicesClasses';
		$funcs['Service classes'] = 'generateServiceClasses';
		if( in_array( 'soap', $this->protocols ) ) {
			$funcs['SOAP server+client classes'] = 'generateSoapServerClientClasses';
			$funcs['SOAP classes for Java'] = 'generateSoapClassesForJava';
		}
		if( in_array( 'json', $this->protocols ) ) {
			$funcs['JSON client classes'] = 'generateJsonClientClasses';
		}
		$funcs['Request and response classes for PHP'] = 'generateRequestResponseClasses4Php';
		$funcs['Data classes for PHP'] = 'generateDataClasses4Php';
		if( in_array( 'amf', $this->protocols ) ) {
			$funcs['AMF request and response classes for Flex'] = 'generateRequestResponseClasses4Flex';
			$funcs['AMF data classes for Flex'] = 'generateDataClasses4Flex';
		}
		if( !$pluginFull ) { // core only
			$funcs['Interfaces classes'] = 'generateServiceInterfaces';
		}
		$funcs['Data validation classes'] = 'generateDataValidationClasses';
		$funcs['Readable document'] = 'generateReadableDocument';
		return $funcs;
	}
	
	private function clearErrors()
	{
		$this->SuccessFiles = array();
		$this->SkippedFiles = array();
		$this->ErrorFiles = array();
		$this->Warnings = array();
		$this->FatalErrors = array();
	}

	/**
	 * Validates the input parameters provided for the generator.
	 *
	 * @since 10.2.0
	 */
	public function validateParameters()
	{
		$this->clearErrors();
		$unsupportedProtocols = array_diff( $this->protocols, array( 'soap', 'amf', 'json' ) );
		if( $unsupportedProtocols ) {
			$this->FatalErrors[] = 'The following protocols are not supported: '.implode(', ',$unsupportedProtocols);
		}
	}

	/**
	 * Maps WSDL data types to Flex data types.
	 * For complex types and special types there is a list of package imports built on-the-fly.
	 * This list should be added to the generated package/class.
	 *
	 * @param string $wsdlType Data type in WSDL terms.
	 * @param array $imports Key-based list of required package imports.
	 * @param string $datPackage Base path of package where data classes can be found.
	 * @return string Flex type.
	 */
	private function getWsdl2FlexType( $wsdlType, &$imports, $datPackage )
	{
		$typeMap = array(
			// WSDL         => Flex
			'string'        => 'String',
			'ArrayOfString' => 'Array',
			'ArrayOfUnsignedInt' => 'Array',
			'dateTime'      => 'String', // Fix: The server handles dates as strings, so this is what we send over to AMF
			'double'        => 'Number', // Fix: Changed double into Number to let Flex send NaN values.
			'unsignedInt'   => 'Number', // Fix: Changed uint into Number to let Flex send NaN values.
			'integer'       => 'Number', // Fix: Changed  int into Number to let Flex send NaN values.
			'int'           => 'Number', // Fix: Changed  int into Number to let Flex send NaN values.
			'boolean'       => 'String', // Fix: Changed boolean into String to let Flex send null values.
		);
		$type = str_replace( array('xsd:','tns:'), '', $wsdlType ); // remove namespaces
		if( isset($typeMap[$type]) ) {
			$flexType = $typeMap[$type];
			if ( $wsdlType == 'dateTime' || $wsdlType == 'dateTimeOrEmpty' ) {
				// When handling dates, we need a utility class for the conversion
				$imports[$this->composeWebServicesUri().'.WoodWingUtils'] = true;
			}
		} else {
			if( stripos( $type, 'ArrayOf' ) !== false ) {
				//$flexType = 'ArrayCollection';
				//$imports['mx.collections.ArrayCollection'] = true;
				$flexType = 'Array';
			} else {
				$xpath = $this->XPath;
				$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:simpleType[@name="'.$type.'"]' );
				if( $entries->length > 0 ) {
					$flexType = 'String'; // Let's flatten simple types to String for now
				} else {
					if( $type == 'AttachmentContent' || $type == 'Row' ) {
						$flexType = 'String'; // Flatten exceptional types to String
					} else {
						$wflClasses = array_flip( $this->intfDescriptor->getWflDataClasses() );
						if( isset( $wflClasses[$type] ) ) {
							$flexType = $this->composeWebServicesUri().'.wfl.dataclasses.Wfl'.$type; // some data classes used in planning are defined in workflow
						} else {
							$flexType = $datPackage.'.'.$this->intfDescriptor->getServiceNameShort().$type; // See Note#001
						}
						$imports[$flexType] = true;
					}
				}
			}
		}
		return $flexType;
	}

	private function getDataClass( $props, &$dataClass )
	{
		if( $props->length > 0 ) foreach( $props as $prop ) {
			$propName = $prop->getAttribute('name');
			$propType = $prop->getAttribute('type');
			$propType = str_replace( array('xsd:','tns:'), '', $propType ); // remove namespaces
			if( $propType == 'ArrayOfNamedQuery' ) {
				$propType = 'ArrayOfNamedQueryType'; // See [Note#1]
			}
			// 'String'(capital S) is an element in wsdl, but here we actually want the php type 'string'(small S).
			if( $propType == 'ArrayOfString' ) {
				$propType = 'ArrayOfstring';
			}
			if( $propType == 'ArrayOfUnsignedInt' ) {
				$propType = 'ArrayOfunsignedInt';
			}
			$propRaw = $propType;

			// Getting the phpType. Like ArrayOfXX will become XX[].
			$pattern = '/(ArrayOf)(\w+)/'; // E.g: Change 'ArrayOfTarget' into ...
			$replacement = '${2}[]'; // ... 'Target[]'
			$phpPropType = preg_replace($pattern, $replacement, $propType);  // php readable type

			// Getting the readable property type.
			$readablePropType = str_replace( 'ArrayOf', 'array of ', $propType ); // readable type

			$isArray = false;
			if (substr($readablePropType, 0, 8) == 'array of') {
				$isArray = true;
			}

			$dataClass[$propName] = array();
			$dataClass[$propName]['name']     = $propName;
			$dataClass[$propName]['type']     = $readablePropType;
			$dataClass[$propName]['phptype']  = $phpPropType;
			$dataClass[$propName]['isarray']  = $isArray;
			$dataClass[$propName]['rawtype']  = $propRaw;
			$dataClass[$propName]['null']     = $prop->getAttribute('nillable');
			$dataClass[$propName]['comment']  = $this->grabCommentsFromSibling( $prop, false );
		}
	}
	
	/**
	 * Retrieves all complexType definitions for WSDL, but excludes the ArrayOf types.
	 * It returns structured data (PHP arrays) with the complexType name as key. Each item has
	 * an array of property details with the property name as key.
	 *
	 * [Note#1] The NamedQuery element is both a request as a complexType. This was a problem for
	 *          PEAR, and so it was renamed to NamedQueryType for the complexType as used in PHP code. 
	 *          But even since PEAR SOAP was replaced with PHP SOAP parser, the NamedQueryType was 
	 *          already used all over the shop in Enterprise Server, including custom server plug-ins. 
	 *          So we'll keep it this way, for backwards compatibility reasons.
	 *
	 * @return array Of structured data. (See above.)
	 */
	public function getDataClasses()
	{
		$dataClasses = array();
		$xpath = $this->XPath;
		$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType' );
		foreach( $entries as $entry ) {
			$dataName = $entry->getAttribute('name');
			if( strpos( $dataName, 'ArrayOf' ) === false ) { // skip arrays
				if( $dataName == 'NamedQuery' ) $dataName = 'NamedQueryType'; // See [Note#1]
				$dataClasses[$dataName] = array();
				$props = $xpath->query( '*/schm:element', $entry );
				$this->getDataClass( $props, $dataClasses[$dataName] );
			}
		}
		//print_r($dataClasses);
		return $dataClasses;
	}
	
	/**
	 * Retrieves all service message definitions for WSDL.
	 * It returns structured data (PHP arrays) with the service name as key. Each item has
	 * an array of property details with the property name as key.
	 * Item '__isRequest' indicates the message type; TRUE for Request, or FALSE for Response.
	 *
	 * @return array Of structured data. (See above.)
	 */
	private function getServiceMessages()
	{	
		$xpath = $this->XPath;
		$messages = array();
		$msgEntries = $xpath->query( '/wsdl:definitions/wsdl:message' );
		foreach( $msgEntries as $msgEntry ) {
			$msgName = $msgEntry->getAttribute('name');
			$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:element[@name="'.$msgName.'"]' );
			$entry = $entries->item(0);
			$isRequest = (strpos( $msgName, 'Response' ) === false);
			if( $isRequest ) { // request?
				// remove and add 'Request' postfix to make sure there is always one postfix (workflow don't have, but admin have)
				$msgName = str_replace( 'Request', '', $msgName );
				$msgName .= 'Request';
			}
			$rawMsgName = $msgEntry->getAttribute('name');
			if( $isRequest ) { // request => dimeIn
				$rawMsgName = str_replace( 'Request', '', $rawMsgName );
				$queryResult = $xpath->query('/wsdl:definitions/wsdl:binding/wsdl:operation[@name="' . $rawMsgName . '"]/wsdl:input/dime:message[@layout="http://schemas.xmlsoap.org/ws/2002/04/dime/closed-layout"]' );
				$mightHaveContent = (bool) $queryResult->length;
			} else { // response => dimeOut
				$rawMsgName = str_replace( 'Response', '', $rawMsgName );
				$queryResult = $xpath->query('/wsdl:definitions/wsdl:binding/wsdl:operation[@name="' . $rawMsgName . '"]/wsdl:output/dime:message[@layout="http://schemas.xmlsoap.org/ws/2002/04/dime/closed-layout"]' );
				$mightHaveContent = (bool) $queryResult->length;
			}
			
			$messages[$msgName] = array();
			$messages[$msgName]['isRequest'] = $isRequest;
			$messages[$msgName]['mightHaveContent'] = $mightHaveContent;
			$messages[$msgName]['props'] = array();
			if( $entry ) {
				$props = null;
				$type = preg_replace('/^.*?:/', '', $entry->getAttribute('type')); // get type without namespace
				if (strlen($type) > 0) {
					// element is a complex type, find complexType
					$typeEntries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType[@name="'.$type.'"]' );
					$typeEntry = $typeEntries->item(0);
					$props = $xpath->query( 'schm:sequence/schm:element', $typeEntry ); // get complexType childs 
				} else {
					$props = $xpath->query( 'schm:complexType/schm:sequence/schm:element', $entry );
				}
				$this->getDataClass( $props, $messages[$msgName]['props'] );
			}
		}
		//print_r($messages);
		return $messages;
	}	
	
	/**
	 * Looks are next or previous node (sibling) to grab comments from WSDL that typically belong to
	 * a property definition.
	 * The simpleType/complexType elements have comments BEFORE, but their property elements  
	 * have comments AFTER. We detect simpleType/complexType elements by having children.
	 * For simpleType/complexType elements we take the previousSibling and for params the nextSibling.
	 *
	 * @param DOMNode $node The node to use as starting point. Typically the property node itself.
	 * @param boolean $complexType TRUE when node is a complexType/simpleType. FALSE when a property.
	 * @return string Comments.
	 */
	private function grabCommentsFromSibling( $node, $complexType )
	{
		// Skip text nodes (white chars) after the property definition
		$comment = $complexType ? $node->previousSibling : $node->nextSibling;
		while( $comment && $comment->nodeType == XML_TEXT_NODE ) {
			$comment = $complexType ? $comment->previousSibling : $comment->nextSibling;
		}
		// Get the comments typed before complexType definition or after property definition
		if( $comment && $comment->nodeType == XML_COMMENT_NODE ) {
			$retVal = $comment->nodeValue;
		} else {
			$retVal = '';
		}
		return $retVal;
	}

	/*public function getDataArrayClasses()
	{
		$dataClasses = array();
		$xpath = $this->XPath;
		$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType' );
		foreach( $entries as $entry ) {
			$dataName = $entry->getAttribute('name');
			if( strpos( $dataName, 'ArrayOf' ) !== false ) { // arrays only
				$dataClasses[] = $dataName;
			}
		}
		return $dataClasses;
	}*/

	/**
	 * Composes the SDK base folder path.
	 *
	 * @since 10.2.0
	 * @return string
	 */
	private function getSdkDir()
	{
		$baseDir = $this->intfDescriptor->getProviderBasePath();
		if( $this->intfDescriptor->getPluginNameFull() ) {
			$sdkDir = "$baseDir/sdk";
		} else {
			$sdkDir = "$baseDir/../sdk";
		}
		return $sdkDir;
	}

	/**
	 * Composes the SDK folder path for a given programming language.
	 *
	 * The path for the core server differs from the path for server plugins:
	 * - server:
	 *   - java: BASEDIR.'/server/../sdk/java/src/com/woodwing/enterprise/interfaces/services'
	 *   - flex: BASEDIR.'/server/../sdk/flex/src/com/woodwing/enterprise/interfaces/services'
	 * - plugin:
	 *   - java: BASEDIR.'/config/plugins/<plugin>/sdk/java/src/com/woodwing/enterprise/interfaces/services'
	 *   - flex: BASEDIR.'/config/plugins/<plugin>/sdk/flex/src/com/woodwing/enterprise/interfaces/services'
	 *
	 * @since 10.2.0
	 * @param string $language Used as sub-folder under the SDK folder. Should be set to either 'java' or 'flex'.
	 * @return string The SDK file path.
	 */
	private function composeSdkDirForLanguage( $language )
	{
		return $this->getSdkDir()."/{$language}/src/com/woodwing/enterprise/interfaces/services";
	}

	/**
	 * Composes the URI used to classify web services and their interfaces and dataclasses.
	 *
	 * The uri for the core server differs from the uri for server plugins:
	 * - server: com.woodwing.enterprise.interfaces.services
	 * - plugin: com.woodwing.enterprise.plugins.<plugin>.interfaces.services
	 *
	 * @since 10.2.0
	 * @return string The URI.
	 */
	private function composeWebServicesUri()
	{
		$pluginFull = $this->intfDescriptor->getPluginNameFull();
		$pluginPart = $pluginFull ? 'plugins.'.strtolower($pluginFull).'.' : '';
		return "com.woodwing.enterprise.{$pluginPart}interfaces.services";
	}

	/**
	 * Generate Java classes from a WSDL.
	 *
	 * Those classes can be used by 3rd party integrators to build a client in Java that talks to Enterprise.
	 *
	 * @since 10.2.0
	 */
	public function generateSoapClassesForJava()
	{
		$intfShortLow = strtolower( $this->intfDescriptor->getServiceNameShort() );

		// Delete output folder recursively (if exists).
		$outputFolder = $this->composeSdkDirForLanguage( 'java' )."/{$intfShortLow}";
		if( file_exists( $outputFolder ) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::cleanDirRecursive( $outputFolder, false );
		}

		// Call the WSDL2Java tool to generate Java classes from our WSDL.
		$outClass = $this->composeWebServicesUri().".{$intfShortLow}";
		$entryPoint = eval( 'return '.$this->intfDescriptor->getSoapEntryPoint().';' );
		$httpWsdl = "{$entryPoint}?wsdl=ws-i";
		$sdkDir = $this->getSdkDir();
		$command = 'java -cp '.escapeshellarg('./wsdl2java/axis-1_4/*:./wsdl2java/javamail-1.4.5/mail.jar').
			' org.apache.axis.wsdl.WSDL2Java -u -W -p '.escapeshellarg( $outClass ).' '.escapeshellarg( $httpWsdl ).
			' -o '.escapeshellarg("{$sdkDir}/java/src").' && echo "OK" || (c=$?; echo "NOK"; (exit $c)) ';
			// L> Note that the exec() does not catch errors given by the WSDL2Java module somehow.
			//    As a work-around the errors are caught in $? at bash and returned as either OK or NOK.

		// Report any error returned by the WSDL2Java tool.
		$returnVar = 0;
		$output = array();
		$result = exec( $command, $output, $returnVar );
		if( $result == 'NOK' ) {
			$this->FatalErrors[] = 'Could not generate Java classes. The WSDL file is not valid: '.
				$this->intfDescriptor->getWsdlFilePath().PHP_EOL.
				'Please fix it and try again. You could manually retry as follows: '.$command.PHP_EOL;
		}

		// Because we asked Enterprise Server to return WSDL through HTTP, it replaces the entry point for us.
		// The Java classes are generated on that and the entry point is stored in the service locator.
		// However, that is an temporary URL and so we replace it again, now with something more generic.
		$defaultEntryPoint = str_replace( LOCALURL_ROOT.INETROOT.'/', 'http://127.0.0.1/Enterprise/', $entryPoint );
		list( $prefix, $nameSpace ) = explode( ':', $this->intfDescriptor->getNameSpace(), 2 ); // namespace has 'urn:' prefix
		$javaFile = $outputFolder.'/'.$nameSpace.'ServiceLocator.java';
		if( !file_exists( $javaFile ) ) {
			$this->FatalErrors[] = 'Could not generate Java classes. The service locator file is missing in the output: '.
				$javaFile.PHP_EOL.
				'Please fix it and try again. You could manually retry as follows: '.$command.PHP_EOL;
		} else {
			file_put_contents( $javaFile, str_replace( $entryPoint, $defaultEntryPoint, file_get_contents( $javaFile ) ) );
		}
	}

	/**
	 * Generates an include file to incorporate all necessary Request PHP classes for AMF calls.
	 *
	 * @param array $requestClasses
	 * @return string PHP code fragment.
	 */
	public function generateRequestClassesInclude4AMF(array $requestClasses)
	{
		$outTxt = "";
		foreach ($requestClasses as $class){
			// add a class definition to the include file.
			$outTxt .= "require_once(BASEDIR.'/server/interfaces/services/" . strtolower($this->intfDescriptor->getServiceNameShort()) . "/$class.class.php');\n";
		}
		return $outTxt;
	}

	/**
	 * Generates an include file to incorporate all necessary type mappings.
	 *
	 * @param array $map
	 * @param string $identifier
	 * @return string PHP code fragment.
	 */
	public function generateTypeMap4AMF(array $map, $identifier)
	{
		$outTxt = "<?php\n\n/**\n"
			." * @copyright WoodWing Software bv. All Rights Reserved.\n"
			." *".'/'."\n"
			."\n"
			."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
			."// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!\n"
			."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
			."\n";


		$fileOut = BASEDIR.'/server/protocols/amf/' . $this->intfDescriptor->getServiceNameShort() . $identifier . "TypeMap.php";

		foreach ($map as $asClass => $phpClass){
			// add a class definition to the include file.
			$outTxt .= "\t\$server->setClassMap( '$asClass', '$phpClass' );\n";
		}

		// write the outText. in format admRequestClassesInclude.php
		$this->saveClassFile( $fileOut, $outTxt );

		return $outTxt;
	}
	
	public function generateDataClasses4Php()
	{
		$this->clearErrors();

		$excludeClasses = array_flip( $this->intfDescriptor->getExclDataClasses() );
		$dataClasses = $this->getDataClasses();
		$dataClasses = array_diff_key( $dataClasses, $excludeClasses ); // remove exclusions
		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);
		$classMap = array();
		$simpleTypeComments = array();
		$simpleTypeEnums = $this->getSimpleTypeEnums( $simpleTypeComments ); // Collect enumerations (simpleType) from WSDL

		static $basicTypes = array(
			// WSDL/xsd => PHP:
			'double' => 'float', // actually 'double' or 'float' are supported in PHP.
			'integer' => 'integer',
			'unsignedInt' => 'integer',
			'dateTime' => 'string',
			'dateTimeOrEmpty' => 'string',
			'Color' => 'string',
			'Id' => 'integer', // admin WSDL
		);

		$outTxt = "<?php\n\n/**\n"
				." * @copyright WoodWing Software bv. All Rights Reserved.\n"
				." *".'/'."\n"
				."\n"
				."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
				."// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!\n"
				."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
				."\n"
				."require_once BASEDIR.'/server/protocols/soap/SOAP_Attachment.class.php';"."\n\n";
		foreach( $dataClasses as $dataName => $dataStruct ) {
			$numberFields = array();
			$booleanFields = array();
			$complexFields = array();
			$arrayFields = array();

			// Data class members
			$dataClassName = $this->intfDescriptor->getDataClassPrefix().$dataName;
			$outTxt .= "class ".$dataClassName."\n{\n";
			foreach( $dataClasses[$dataName] as $propName => $propStruct ) {
				$outTxt .= "\tpublic \$$propName;\n";
			}
			$outTxt .= "\n";

			// Data class header
			$outTxt .= "\t/**\n";
			foreach( $dataStruct as /*$propName => */$propStruct ) {
				$propType = $propStruct['phptype']; // display type
				if( $propStruct['isarray'] ) {
					// child item type (when array) or this prop type (when single)
					$propType = str_replace( '[]', '', $propType );
				}

				$showNullable = $propStruct['null'] ? 'Nullable.' : '';
				$isEnum = array_key_exists( $propType, $simpleTypeEnums );
				if( isset($basicTypes[$propType]) ) {
					$propType = $basicTypes[$propType];
				}
				$prefixedPropType = $isEnum ? 'string' : $propType;
				$prefixedPropType = $propStruct['isarray'] ? $prefixedPropType . '[]' : $prefixedPropType; // Add back the [] for Array type.

				$dataClassPrefix = $this->intfDescriptor->getDataClassPrefix();
				$workflowDataClasses = $this->intfDescriptor->getWflDataClasses();

				if( !$isEnum && isset($dataClasses[$propType]) ) {
					if( in_array( $propType, $workflowDataClasses ) ) { // Pln interface is a subset of Wfl, so these data types will not be with Pln prefix($dataClassPrefix)
						// All Wfl interface do not need 'wfl' prefix.
					} else {
						$prefixedPropType = $dataClassPrefix.$prefixedPropType;
					}
				}


				$outTxt .= "\t * @param ".str_pad($prefixedPropType,20)." \$".str_pad($propStruct['name'],20)." $showNullable\n";   //FVFV

				if( $propStruct['isarray'] ) {
					$arrayFields[] = $propStruct['name'];
				} elseif( !$isEnum && isset($dataClasses[$propType]) ) {
					$complexFields[] = $propStruct['name'];
				}

				// Gather number fields to clean up NAN values.
				switch ($propStruct['type']){
					case 'boolean' :
						$booleanFields[] = $propStruct['name'];
						break;
					case 'double' :
					case 'unsignedInt' :
					case 'integer' :
					case 'int' :
						$numberFields[] = $propStruct['name'];
						break;
				}
			}
			$outTxt .= "\t *".'/'."\n";

			// Data class constructor
			$outTxt .= "\tpublic function __construct( ";
			$comma = '';
			foreach( $dataStruct as $propName => $propStruct ) {
				$outTxt .= $comma."\$$propName=null";
				$comma = ', ';
			}
			$outTxt .= ")\n";
			$outTxt .= "\t{\n";
			foreach( $dataStruct as $propName => $propStruct ) {
				$outTxt .= "\t\t\$this->".str_pad($propName,20)." = \$".$propName.";\n";
			}
			$outTxt .= "\t}\n\n";

			// Flex AMF specific function used for object type mapping
			if( in_array( 'amf', $this->protocols ) ) {
				$flexDataClassName = $this->intfDescriptor->getServiceNameShort().$dataName; // See Note#001
				$outTxt .= "\tpublic function getASClassName() { return AS_CLASSNAME_PREFIX.'.$intfShortLow.dataclasses.$flexDataClassName'; } // AMF object type mapping\n\n";
				$classMap[ AS_CLASSNAME_PREFIX.'.'.$intfShortLow.'.dataclasses.'.$flexDataClassName ] = $dataClassName;
			}

			// Add a function to cleanup NUMBER and BOOLEAN fields.
			$outTxt .= "\tpublic function sanitizeProperties4Php()\n\t{\n";
			// clean the single properties.
			if ( 0 < count($numberFields)){
				foreach ($numberFields as $numberField){
					$outTxt .= "\t\tif (is_nan(\$this->$numberField)){ \$this->$numberField = null; }\n";
				}
			}
			if ( 0 < count($booleanFields)){
				foreach ($booleanFields as $booleanField){
					$outTxt .= "\t\tif (!is_null(\$this->$booleanField)){ \$this->$booleanField = ('true' == \$this->$booleanField) ? true : false; }\n";
				}
			}
			// clean array structures of objects.
			if( $arrayFields ) foreach( $arrayFields as $complexField ) {
				$outTxt .= "\t\tif (0 < count(\$this->$complexField)){\n";
				$outTxt .= "\t\t\tif (is_object(\$this->$complexField" ."[0])){\n";
				$outTxt .= "\t\t\t\tforeach (\$this->$complexField as \$complexField){\n";
				$outTxt .= "\t\t\t\t\t\$complexField->sanitizeProperties4Php();\n";
				$outTxt .= "\t\t\t\t}\n";
				$outTxt .= "\t\t\t}\n";
				$outTxt .= "\t\t}\n";
			}
			if( $complexFields ) foreach( $complexFields as $complexField ) {
				$outTxt .= "\t\tif( is_object( \$this->$complexField ) ) {\n";
				$outTxt .= "\t\t\t\$this->{$complexField}->sanitizeProperties4Php();\n";
				$outTxt .= "\t\t}\n";
			}
			$outTxt .= "\t}\n";
			$outTxt .= "}\n";
		}

		// write the dataclass mappings.
		if( in_array( 'amf', $this->protocols ) ) {
			$this->generateTypeMap4AMF( $classMap, 'Data' );
		}

		$classFile = $this->intfDescriptor->getDataClassesFile();
		if( !$classFile ) {
			$this->Warnings[] = 'Feature is not supported';
			return;
		}
		$this->saveClassFile( $classFile, $outTxt );
	}

	public function generateDataClasses4Flex()
	{
		$this->clearErrors();

		$excludeClasses = array_flip( $this->intfDescriptor->getExclDataClasses() );
		$dataClasses = $this->getDataClasses();
		$dataClasses = array_diff_key( $dataClasses, $excludeClasses ); // remove exclusions
		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);

		$dirOut = $this->composeSdkDirForLanguage( 'flex' ).'/'.$intfShortLow.'/dataclasses';
		if( !is_dir($dirOut) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( $dirOut, 0777 );
			if( !is_writable($dirOut) ) {
				$this->FatalErrors[] = 'No write access to folder '.$dirOut;
				return;
			}
		}

		foreach( $dataClasses as $dataName => $dataStruct ) {

			// File header
			$imports = array();
			$datPackage = $this->composeWebServicesUri().".{$intfShortLow}.dataclasses";
			$dataClassName = $this->intfDescriptor->getServiceNameShort().$dataName; // See Note#001
			$outTxt = "/*\n"
					."\t".$this->packageName." $intfFull Services\n"
					."\tCopyright (c) WoodWing Software bv. All Rights Reserved.\n\n"
					."\tIMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!\n"
					.'*/'."\n\n";

			// Package and class constructor
			$outTxt .= "package $datPackage\n{\n";
			$outTxt .= "/*IMPORTS*/"; // to be replaced below
			$outTxt .= "\t[Bindable]\n";
			$outTxt .= "\t[RemoteClass(alias=\"".$datPackage.'.'.$this->intfDescriptor->getServiceNameShort().$dataName."\")]\n\n"; // Note#001
			$outTxt .= "\tpublic class $dataClassName\n\t{\n";

			// Compose AMF class components.
			$constructor = "\t\tpublic function $dataClassName() {\n\t\t}\n\n";
			$properties = "";
			$getset = "";
			foreach( $dataStruct as $propName => $propStruct ) {
				$flexType = $this->getWsdl2FlexType( $propStruct['rawtype'], $imports, $datPackage );
				$propName = $this->sanitizePropertyName4Flex($propName);
				$properties .= "\t\tprivate var _$propName:$flexType;\n";

				// Add Getters/Setters.
				$getset .= $this->getGetterOrSetter4Flex( $propStruct['rawtype'], $flexType, $propName, false);
				$getset .= $this->getGetterOrSetter4Flex( $propStruct['rawtype'], $flexType, $propName, true);
			}

			$outTxt .= $properties . "\n" . $constructor . $getset ."\t}\n}\n";

			// Inject imports
			$importsTxt = count($imports) > 0 ? "\timport ".implode(";\n\timport ",array_keys($imports)).";\n\n" : '';
			$outTxt = str_replace( '/*IMPORTS*/', $importsTxt, $outTxt );
			
			// Write Flex class file
			$classFile = $dirOut.'/'.$dataClassName.'.as';
			$this->saveClassFile( $classFile, $outTxt );
		}

		$outTxt = "/*\n"
			."\t".$this->packageName." Services Utils\n"
			."\tCopyright (c) WoodWing Software bv. All Rights Reserved.\n\n"
			."\tIMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!\n"
			.'*/'."\n\n";

		$outTxt .= "package ".$this->composeWebServicesUri()
			."{\n\n"
			."\tpublic class WoodWingUtils\n"
			."\t{\n\n"
			."\t\t/*\n"
			."\t\t* Returns a date string formatted as yyyy-mm-ddThh:mm:ss\n"
			."\t\t* @param d Date\n"
			."\t\t*\n"
			."\t\t* @return String a yyyy-mm-ddThh:mm:ss formated date.\n"
			."\t\t*/\n"
			."\t\tpublic static function dateToString (d:Date):String {\n\n"
			."\t\t\tif (d != null) {\n"
			."\t\t\t\tvar day:Number = d.date;\n"
			."\t\t\t\tvar year:Number = d.fullYear;\n"
			."\t\t\t\tvar month:Number = d.month + 1;\n"
			."\t\t\t\tvar hours:Number = d.hours;\n"
			."\t\t\t\tvar minutes:Number = d.minutes;\n"
			."\t\t\t\tvar seconds:Number = d.seconds;\n"
			."\t\t\t\tvar sb:String = new String();\n"
			."\t\t\t\tsb += year;\n"
			."\t\t\t\tsb += \"-\";\n\n"
			."\t\t\t\tif (month < 10) {\n"
			."\t\t\t\t\tsb += \"0\";\n"
			."\t\t\t\t}\n"
			."\t\t\t\tsb += month;\n"
			."\t\t\t\tsb += \"-\";\n\n"
			."\t\t\t\tif (day < 10) {\n"
			."\t\t\t\t\tsb += \"0\";\n"
			."\t\t\t\t}\n"
			."\t\t\t\tsb += day;\n"
			."\t\t\t\tsb += \"T\";\n\n"
			."\t\t\t\tif (hours < 10) {\n"
			."\t\t\t\t\tsb += \"0\";\n"
			."\t\t\t\t}\n"
			."\t\t\t\tsb += hours;\n"
			."\t\t\t\tsb += \":\";\n"
			."\t\t\t\tif (minutes < 10) {\n"
			."\t\t\t\t\tsb += \"0\";\n"
			."\t\t\t\t}\n"
			."\t\t\t\tsb += minutes;\n"
			."\t\t\t\tsb += \":\";\n"
			."\t\t\t\tif (seconds < 10) {\n"
			."\t\t\t\t\tsb += \"0\";\n"
			."\t\t\t\t}\n"
			."\t\t\t\tsb += seconds;\n\n"
			."\t\t\t\treturn sb;\n\n"
			."\t\t\t}\n\n"
			."\t\t\treturn null;\n"
			."\t\t}\n\n"
			."\t\t/**\n"
			."\t\t* Returns a date formatted by a String (yyyy-mm-ddThh:mm:ss formatted String)\n"
			."\t\t* (These are local time settings, not utc!)\n"
			."\t\t* @param str String the string containing the date information (format: yyyy-mm-ddThh:mm:ss)\n"
			."\t\t*\n"
			."\t\t* @returns Date a Date object.\n"
			."\t\t*/\n"
			."\t\tpublic static function stringToDate (str:String, ignoreErrors:Boolean = false):Date {\n"
			."\t\t\tvar finalDate:Date;\n"
			."\t\t\tif (str != null && str != \"\") {\n"
			."\t\t\t\t//if the string doesn't contain time, call the fromDateString function\n"
			."\t\t\t\tif (str.indexOf(\"T\") == -1)\n"
			."\t\t\t\t\treturn fromDateString(str);\n\n"
			."\t\t\t\ttry {\n"
			."\t\t\t\t\tvar dateStr:String = str.substring(0, str.indexOf(\"T\"));\n"
			."\t\t\t\t\tvar timeStr:String = str.substring(str.indexOf(\"T\") + 1, str.length);\n"
			."\t\t\t\t\tvar dateArr:Array = dateStr.split(\"-\");\n"
			."\t\t\t\t\tvar year:Number = Number(dateArr.shift());\n"
			."\t\t\t\t\tvar month:Number = Number(dateArr.shift());\n"
			."\t\t\t\t\tvar date:Number = Number(dateArr.shift());\n\n"
			."\t\t\t\t\t//Remove Z\n"
			."\t\t\t\t\tif (timeStr.indexOf(\"Z\") != -1) {\n"
			."\t\t\t\t\t\ttimeStr = timeStr.substring(0, timeStr.indexOf(\"Z\"));\n"
			."\t\t\t\t\t}\n"
			."\t\t\t\t\tif (timeStr.indexOf(\"+\") != -1) {\n"
			."\t\t\t\t\t\ttimeStr = timeStr.substring(0, timeStr.indexOf(\"+\"));\n"
			."\t\t\t\t\t}\n"
			."\t\t\t\t\tif (timeStr.indexOf(\"-\") != -1) {\n"
			."\t\t\t\t\t\ttimeStr = timeStr.substring(0, timeStr.indexOf(\"-\"));\n"
			."\t\t\t\t\t}\n\n"
			."\t\t\t\t\tvar timeArr:Array = timeStr.split(\":\");\n"
			."\t\t\t\t\tvar hour:Number = Number(timeArr.shift());\n"
			."\t\t\t\t\tvar minutes:Number = Number(timeArr.shift());\n"
			."\t\t\t\t\tvar secondsArr:Array = (timeArr.length > 0) ? String(timeArr.shift()).split(".") : null;\n"
			."\t\t\t\t\tvar seconds:Number = (secondsArr != null && secondsArr.length > 0) ? Number(secondsArr.shift()) : 0;\n"
			."\t\t\t\t\tvar milliseconds:Number = (secondsArr != null && secondsArr.length > 0) ? Number(secondsArr.shift()) : 0;\n\n"
			."\t\t\t\t\tfinalDate = new Date(year, month - 1, date, hour, minutes, seconds, milliseconds);\n\n"
			."\t\t\t\t\tif (finalDate.toString() == \"Invalid Date\") {\n"
			."\t\t\t\t\t\tthrow new Error(\"This date does not conform to local date.\");\n"
			."\t\t\t\t\t}\n"
			."\t\t\t\t}\n"
			."\t\t\t\tcatch (e:Error) {\n"
			."\t\t\t\t\tvar eStr:String = \"Unable to parse the string [\" + str + \"] into a date. \";\n"
			."\t\t\t\t\teStr += \"The internal error was: \" + e.toString();\n\n"
			."\t\t\t\t\ttrace(eStr);\n"
			."\t\t\t\t}\n"
			."\t\t\t}\n"
			."\t\t\treturn finalDate;\n"
			."\t\t}\n\n"
			."\t\t/**\n"
			."\t\t* Returns a date formatted by a String (yyyy-mm-dd formatted String)\n"
			."\t\t* @param str String the string containing the date information (format: yyyy-mm-dd)\n"
			."\t\t*\n"
			."\t\t* @returns Date a Date object.\n"
			."\t\t*/\n"
			."\t\tpublic static function fromDateString (str:String):Date {\n"
			."\t\t\tvar finalDate:Date;\n"
			."\t\t\tif (str != null && str != \"\") {\n"
			."\t\t\t\tvar dateArr:Array = str.split(\"-\");\n"
			."\t\t\t\tvar year:Number = Number(dateArr[0]);\n"
			."\t\t\t\tvar month:Number = Number(dateArr[1]) - 1; // subtract 1 to get the right month value\n"
			."\t\t\t\tvar date:Number = Number(dateArr[2]);\n"
			."\t\t\t\tfinalDate = new Date(year, month, date);\n"
			."\t\t\t}\n"
			."\t\t\treturn finalDate;\n"
			."\t\t}\n\n"
			."\t}\n"
			."}\n";


		$utilsFile = $this->composeSdkDirForLanguage( 'flex' ).'/WoodWingUtils.as';
		$this->saveClassFile( $utilsFile, $outTxt );
	}

	/**
	 * Sanitizes a property name for use by flex.
	 *
	 * Flex currently does not support the following (plus their converted values)
	 *
	 * Flex         Sanitized Value
	 * Object       Objects
	 *
	 * @param string $propName The Property Name to be checked.
	 * @return string $propName The sanitized Property Name.
	 */
	private function sanitizePropertyName4Flex( $propName){
		if ( "Object" == $propName ){
			$propName = "Objects";
		}

		if ( "Number" == $propName ){
			$propName = "Numbers";
		}
		return $propName;
	}

	/**
	 * Adds code for support of changed property names.
	 *
	 * Since the WSDL needs to remain intact the only way to send back rewritten properties is by
	 * converting such values right before sending the response. Since doing this at runtime is too costly
	 * (we cannot identify which function has which rewritten properties, and thus we would have to check
	 * every function) a different approach was chosen, by means of the serviceName, the proper code to
	 * sanitize the response is returned.
	 *
	 * @param string $serviceName
	 *
	 * @return string $sanitizeResponse String representing the code to sanitize the response.
	 */
	private function sanitizeResponse4Flex($serviceName){
		switch ($serviceName){
			case "GetVersion" :
				// Object is not an allowed property name in Flex.
				$sanitizeResponse = "\t\t//Sanitize the response for the VersionInfo (Object is an illegal property name in Flex.)\n"
					. "\t\t\$resp->VersionInfo->Objects = \$resp->VersionInfo->Object;\n"
					. "\t\tunset( \$resp->VersionInfo->Object );\n\n";
				break;
			case "QueryObjects" :
			case "NamedQuery" :
				// Sanitize the response, to convert FacetItem->Number to FacetItem->Numbers as Number is an illegal property name in Flex.
				$sanitizeResponse = "\t\t//Sanitize the response for the FacetItems (Number is an illegal property name in Flex.)\n"
					. "\t\tif ( \$resp->Facets ) foreach (\$resp->Facets as \$facet){\n"
					. "\t\t\tforeach (\$facet->FacetItems as \$facetItem){\n"
					. "\t\t\t\t\$facetItem->Numbers = \$facetItem->Number;\n"
					. "\t\t\t\tunset(\$facetItem->Number);\n"
					. "\t\t\t}\n"
					. "\t\t}\n\n";
				break;
			default :
				$sanitizeResponse = "";
				break;
		}
		return $sanitizeResponse;
	}

	private function getGetterOrSetter4Flex($wsdlType, $flexType, $propName, $isSetter=false){
		$wsdlType = str_replace( array('xsd:','tns:'), '', $wsdlType );

		$setNumber = "\t\t\tthis._$propName = $propName;\n";
		$output = "";
		$header = "";

		switch($wsdlType){
			case 'double':
			case 'unsignedInt':
			case 'integer':
			case 'int':
				// Request classes should return a String value.
				$flexType = "Number";
				$output = (true == $isSetter)
					? $setNumber
					: "\t\t\treturn this._$propName;\n";
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'boolean':
				$header .= "\n\t\t// _$propName should be handled like a Boolean, but since Boolean is not a nillable type\n" .
					"\t\t// we handle it like a String to be able to send it nillable to the server. \n";
			default:
				$output .= (true == $isSetter)
					? "\t\t\tthis._$propName = $propName;\n"
					: "\t\t\treturn this._$propName;\n";
		}

		// Generate function header.
		$header .= (true == $isSetter)
			? "\t\tpublic function set $propName($propName:$flexType):void {\n"
			: "\t\tpublic function get $propName():$flexType {\n" ;

		// Generate function footer.
		$footer = "\t\t}\n";
		$footer .= (true == $isSetter) ? "\n" : "";

		// Create an extra getter and setter for the date properties, this allows the integrator
		// to use Flex Date objects.
		if ( $wsdlType == 'dateTime' || $wsdlType == 'dateTimeOrEmpty' ) {
			$footer .= "\n";

			$footer .= (true == $isSetter)
				? "\t\tpublic function set{$propName}AsDate($propName:Date):void {\n"
				: "\t\tpublic function get{$propName}AsDate():Date {\n" ;

			$footer .= (true == $isSetter)
				? "\t\t\tthis._$propName = WoodWingUtils.dateToString($propName);\n"
				: "\t\t\treturn WoodWingUtils.stringToDate(this._$propName);\n";

			$footer .= "\t\t}\n\n";
		}

		return $header . $output . $footer;
	}

	public function generateServicesClasses()
	{
		$this->clearErrors();
		foreach( $this->protocols as $protocol ) {
			$this->generateServicesClass( $protocol );
		}
	}

	private function generateServicesClass( $protocol )
	{
		$xpath = $this->XPath;
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);
		$providerBase = $this->intfDescriptor->getProviderBasePath();
		$pluginFull = $this->intfDescriptor->getPluginNameFull();
		$pluginShort = $this->intfDescriptor->getPluginNameShort();
		$serviceShort = $this->intfDescriptor->getServiceNameShort();

		// TODO: Use getServiceMessages() instead of code fragment below
		$services = array();
		$msgEntries = $xpath->query( '/wsdl:definitions/wsdl:message' );
		foreach( $msgEntries as $msgEntry ) {
			$msgName = $msgEntry->getAttribute('name');
			//$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:element[@name="'.$msgName.'"]' );
			//$entry = $entries->item(0);
			$elem = $msgName;

			$isReq = strpos( $elem, 'Response' ) === false;
			if( $isReq ) {
				$elem = str_replace( 'Request', '', $elem );
				$services[$elem] = array();
				// Check if dime/file transfer is involved in request.
				$queryResult = $xpath->query('/wsdl:definitions/wsdl:binding/wsdl:operation[@name="' . $elem . '"]/wsdl:input/dime:message[@layout="http://schemas.xmlsoap.org/ws/2002/04/dime/closed-layout"]' );
				$services[$elem]['dimeIn'] = (bool) $queryResult->length;
			} else {
				$elem = str_replace( 'Response', '', $elem );
				// Check if dime/file transfer is involved in responce.
				$queryResult = $xpath->query('/wsdl:definitions/wsdl:binding/wsdl:operation[@name="' . $elem . '"]/wsdl:output/dime:message[@layout="http://schemas.xmlsoap.org/ws/2002/04/dime/closed-layout"]' );
				$services[$elem]['dimeOut'] = (bool) $queryResult->length;
			}
		}
		$outTxt = '';
		$reqInc = array();
		$indent = '';
		foreach( $services as $serviceName => $serviceInOut ) {
			$outTxt .= "\tpublic function $serviceName( "; // start function
			$outTxt .= '$req';
			$outTxt .= " )\n\t{\n";
			$serviceClass = "{$pluginShort}{$serviceShort}{$serviceName}Service";
			if( $pluginFull ) {
				$outTxt .= "\t\trequire_once __DIR__.'/../../../services/{$intfShortLow}/{$serviceClass}.class.php';\n";
			} else { // core
				$outTxt .= "\t\trequire_once BASEDIR.'/server/services/$intfShortLow/{$serviceClass}.class.php';\n";
			}
			switch( $protocol ) {
				case 'soap':
					$outTxt .= "\n\t\ttry {\n";
					$indent = "\t\t\t";
					break;
				case 'json':
					$requestClass = "{$pluginShort}{$serviceShort}{$serviceName}Request";
					$outTxt .= "\t\t\$req['__classname__'] = '$requestClass';\n";
					$outTxt .= "\t\t\$req = \$this->arraysToObjects( \$req );\n";
					$outTxt .= "\t\t\$req = \$this->restructureObjects( \$req );\n";
					$indent = "\t\t";
				break;
				case 'amf':
					$outTxt .= "\n\t\ttry {\n";
					$requestClass = "{$pluginShort}{$serviceShort}{$serviceName}Request";
					$outTxt .= "\t\t\t\$req = \$this->objectToRequest( \$req, '$requestClass' );\n";
					$reqInc[] = $requestClass;
					$indent = "\t\t\t";
				break;
			}
			if ($serviceInOut['dimeIn'] == true) {
				$outTxt .= $this->intfDescriptor->getUrlToFilePath( $serviceName ); // Translate external url to internal filepath.
			}
			$outTxt .= $indent."\$service = new {$serviceClass}();\n";
			$outTxt .= $indent."\$resp = \$service->execute( \$req );\n";
			switch( $protocol ) {
				case 'amf':
				case 'soap':
					break;
				case 'json':
					$outTxt .= "\t\t\$resp = \$this->restructureObjects( \$resp );\n";
					break;
			}
			switch( $protocol ) {
				case 'soap':
					$outTxt .= "\t\t} catch( BizException \$e ) {\n";
					$outTxt .= "\t\t\tthrow new SoapFault( \$e->getType(), \$e->getMessage(), '', \$e->getDetail() );\n";
					$outTxt .= "\t\t}\n";
				break;
				case 'amf':
					$outTxt .= "\t\t} catch( BizException \$e ) {\n";
					$outTxt .= "\t\t\trequire_once 'Zend/Amf/Server/Exception.php';\n";
					$outTxt .= "\t\t\tthrow new Zend_Amf_Server_Exception( \$e->getMessage() );\n";
					$outTxt .= "\t\t}\n";
				break;
			}
			if ($serviceInOut['dimeOut'] == true) {
				$outTxt .= $this->intfDescriptor->getFilePathToUrl( $serviceName ); // Translate internal filepath to external url.
			}			
			if( $protocol == 'soap' ) { // tmp hack not to break stuff now
				$outTxt .= "\t\treturn self::returnResponse(\$resp);\n";
			}elseif( $protocol == 'amf' ) {
				// Append any needed code to rename variables that have Flex restricted keywords.
				$outTxt .= $this->sanitizeResponse4Flex($serviceName);
			}

			if ($protocol != 'soap') {
				$outTxt .= "\t\treturn \$resp;\n";
			}
			$outTxt .= "\t}\n\n"; // end function
		}
		
		// read template file
		$classFile = '';
		$className = '';
		switch( $protocol ) {
			case 'soap':
				$classFile = "{$providerBase}/buildtools/genservices/interfaces/{$intfShortLow}/SoapServices.template.php";
			break;
			case 'amf':
				$classFile = BASEDIR.'/server/buildtools/genservices/templates/AmfServices.template.php';
			break;
			case 'json':
				$classFile = BASEDIR.'/server/buildtools/genservices/templates/JsonServices.template.php';
				$className = $pluginFull ? "{$pluginFull}_Protocols_Json_{$intfShort}_Services" : "WW_JSON_{$intfShort}Services";
			break;
		}
		
		$template = file_get_contents( $classFile );
		if( !$template ) {
			$this->FatalErrors[] = 'Could not read from file '.$classFile;
			return;
		}
		// replace /*BODY*/ marker with list of service functions (class body)
		$providerDir = $pluginFull ? "__DIR__.'/../../.." : "BASEDIR.'/server";
		$outTxt = str_replace( '/*BODY*/', $outTxt, $template );
		$outTxt = str_replace( '/*PACKAGENAME*/', $this->packageName, $outTxt );
		$outTxt = str_replace( '/*CLASSNAME*/', $className, $outTxt );
		$outTxt = str_replace( '/*INTFSHORT*/', $intfShort, $outTxt );
		$outTxt = str_replace( '/*INTFSHORTLOW*/', $intfShortLow, $outTxt );
		$outTxt = str_replace( '/*PROVIDERDIR*/', $providerDir, $outTxt );

		if ($protocol == 'amf'){
			$outTxt = str_replace( '/*REQUESTCLASSES*/', $this->generateRequestClassesInclude4AMF($reqInc), $outTxt);
		}
		if( $pluginFull ) {
			$classFile = "{$providerBase}/protocols/{$protocol}/{$intfShortLow}/Services.php";
		} else { // core
			$classFile = "{$providerBase}/protocols/{$protocol}/{$intfShort}Services.php";
		}
		$this->saveClassFile( $classFile, $outTxt );
	}

	public function generateRequestResponseClasses4Php()
	{
		$this->clearErrors();

		static $basicTypes = array(
			// WSDL/xsd => PHP:
			'double' => 'float', // actually 'double' or 'float' are supported in PHP.
			'integer' => 'integer',
			'unsignedInt' => 'integer',
			'dateTime' => 'string',
			'dateTimeOrEmpty' => 'string',
			'Color' => 'string',
			'Id' => 'integer', // admin WSDL
		);

		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);
		$dataClasses = $this->getDataClasses();
		//$excludeClasses = array_flip( $this->ifDescriptor->getExclDataClasses() );
		//$dataClasses = array_diff_key( $dataClasses, $excludeClasses ); // remove exclusions
		$simpleTypeComments = array();
		$simpleTypeEnums = $this->getSimpleTypeEnums( $simpleTypeComments ); // Collect enumerations (simpleType) from WSDL
		$providerBase = $this->intfDescriptor->getProviderBasePath();
		$pluginFull = $this->intfDescriptor->getPluginNameFull();
		$pluginShort = $this->intfDescriptor->getPluginNameShort();

		$dirOut = "{$providerBase}/interfaces/services/{$intfShortLow}";
		if( !is_dir($dirOut) ) {
			if( !is_writable($dirOut) ) {
				$this->FatalErrors[] = 'No write access to folder '.$dirOut;
				return;
			}
		}
		$services = $this->getServiceMessages();
		$classMap = array();
		foreach( $services as $serviceName => $messageStruct ) {
			$isRequest = $messageStruct['isRequest'];
			$numberFields = array();
			$booleanFields = array();
			$complexFields = array();
			$arrayFields = array();

			// File header
			$outTxt = "<?php\n\n/**\n"
					." * @copyright WoodWing Software bv. All Rights Reserved.\n"
					." *".'/'."\n"
					."\n"
					."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
					."// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!\n"
					."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
					."\n";
			// Request/Reponse class
			$outTxt .= "class {$pluginShort}{$intfShort}{$serviceName}\n{\n";

			if( count($messageStruct['props']) > 0 ) {
				foreach( array_keys($messageStruct['props']) as $propName ) {
					$outTxt .= "\tpublic ".'$'.$propName.";\n";
				}
				$outTxt .= "\n\t/**\n";
				foreach( $messageStruct['props'] as $propName => $propStruct ) {
					$propType = $propStruct['phptype']; // display type
					if( $propStruct['isarray'] ) {
						// child item type (when array) or this prop type (when single)
						$propType = str_replace( '[]', '', $propType );
					}

					$showNullable = $propStruct['null'] ? 'Nullable.' : '';
					$isEnum = isset($simpleTypeEnums[$propType]);
					if( isset($basicTypes[$propType]) ) {
						$propType = $basicTypes[$propType];
					}
					$prefixedPropType = $isEnum ? 'string' : $propType;
					$prefixedPropType = $propStruct['isarray'] ? $prefixedPropType . '[]' : $prefixedPropType; // Add back the [] for Array type.

					$dataClassPrefix = $this->intfDescriptor->getDataClassPrefix();
					$workflowDataClasses = $this->intfDescriptor->getWflDataClasses();

					if( !$isEnum && isset($dataClasses[$propType]) ) {
						if( in_array( $propType, $workflowDataClasses ) ) { // Pln interface is a subset of Wfl, so these data types will not be with Pln prefix($dataClassPrefix)
							// All Wfl interface do not need 'wfl' prefix.
						} else {
							$prefixedPropType = $dataClassPrefix.$prefixedPropType;
						}
					}
					$outTxt .= "\t * @param ".str_pad($prefixedPropType,20).' '.'$'.str_pad($propName, 25)." $showNullable\n";

					if( $propStruct['isarray'] ) {
						$arrayFields[] = $propStruct['name'];
					} elseif( !$isEnum && isset($dataClasses[$propType]) ) {
						$complexFields[] = $propStruct['name'];
					}

					// Gather number fields to clean up NAN values.
					switch ($propStruct['type']){
						case 'boolean' :
							$booleanFields[] = $propStruct['name'];
							break;
						case 'double' :
						case 'unsignedInt' :
						case 'integer' :
						case 'int' :
							$numberFields[] = $propStruct['name'];
							break;
					}
				}
				$outTxt .= "\t ".'*'.'/'."\n";
			}

			// Constructor body
			$outTxt .= "\tpublic function __construct( ";
			$comma = '';
			foreach( array_keys($messageStruct['props']) as $propName ) {
				$outTxt .= $comma.'$'.$propName.'=null';
				$comma = ', ';
			}
			$outTxt .= " )\n";
			$outTxt .= "\t{\n";
			foreach( array_keys($messageStruct['props']) as $propName ) {
				$outTxt .= "\t\t".'$this->'.str_pad($propName,20).' = $'.$propName.";\n";
			}
			$outTxt .= "\t}\n\n"; // end constructor

			// Add the service validation function
			$outTxt .= "\tpublic function validate()\n";
			$outTxt .= "\t{\n";
			if( count($messageStruct['props']) > 0 ) {
				$outTxt .= "\t\trequire_once BASEDIR.'/server/services/Validator.php';\n";
				if( $pluginFull ) {
					$outTxt .= "\t\trequire_once __DIR__.'/../../../services/$intfShortLow/DataValidators.php';\n";
				} else { // core
					$outTxt .= "\t\trequire_once BASEDIR.'/server/services/$intfShortLow/DataValidators.php';\n";
				}
				$outTxt .= "\t\t\$validator = new WW_Services_Validator(".($isRequest?'true':'false').");\n";
				$outTxt .= "\t\t\$datObj = \$this;\n\n";
				$outTxt .= "\t\t\$validator->enterPath( '$serviceName' );\n";
				foreach( $messageStruct['props'] as $propName => $propStruct ) {
					$propType = $propStruct['rawtype'];
					$nillable = $propStruct['null'];
					$outTxt .= $this->buildValidateFuncBody( $propName, $propType, $nillable, $dataClasses, $simpleTypeEnums );
				}
				$outTxt .= "\t\t\$validator->leavePath();\n";
			}
			$outTxt .= "\t}\n\n"; // end validate()

			if( in_array( 'amf', $this->protocols ) ) {
				// Flex AMF specific function used for object type mapping
				$outTxt .= "\tpublic function getASClassName() { return AS_CLASSNAME_PREFIX.'.$intfShortLow.$intfShort$serviceName'; } // AMF object type mapping\n";
			}

			if ($isRequest){
				// Add a function to clean up NUMBER / BOOLEAN fields.
				$outTxt .= "\n\tpublic function sanitizeProperties4Php()\n\t{\n";
				// clean the single properties.
				if ( 0 < count($numberFields)){
					foreach ($numberFields as $numberField){
						$outTxt .= "\t\tif (is_nan(\$this->$numberField)){ \$this->$numberField = null; }\n";
					}
				}
				if ( 0 < count($booleanFields)){
					foreach ($booleanFields as $booleanField){
						$outTxt .= "\t\tif (!is_null(\$this->$booleanField)){ \$this->$booleanField = ('true' == \$this->$booleanField) ? true : false; }\n";
					}
				}
				// clean array structures of objects.
				if( $arrayFields ) foreach( $arrayFields as $complexField ) {
					$outTxt .= "\t\tif (0 < count(\$this->$complexField)){\n";
					$outTxt .= "\t\t\tif (is_object(\$this->$complexField" ."[0])){\n";
					$outTxt .= "\t\t\t\tforeach (\$this->$complexField as \$complexField){\n";
					$outTxt .= "\t\t\t\t\t\$complexField->sanitizeProperties4Php();\n";
					$outTxt .= "\t\t\t\t}\n";
					$outTxt .= "\t\t\t}\n";
					$outTxt .= "\t\t}\n";
				}
				if( $complexFields ) foreach( $complexFields as $complexField ) {
					$outTxt .= "\t\tif( is_object( \$this->$complexField ) ) {\n";
					$outTxt .= "\t\t\t\$this->{$complexField}->sanitizeProperties4Php();\n";
					$outTxt .= "\t\t}\n";
				}
				$outTxt .= "\t}\n";
			}
			$mightHaveContent = $messageStruct['mightHaveContent'];
			$mightHaveContentStr = $mightHaveContent ? 'true' : 'false';
			$outTxt .= "\n\tpublic function mightHaveContent() { return $mightHaveContentStr; }\n";

			$outTxt .= "}\n\n";

			// Write php class file
			$classFile = "{$dirOut}/{$pluginShort}{$intfShort}{$serviceName}.class.php";
			$this->saveClassFile( $classFile, $outTxt );

			if ($isRequest){
				$classMap[AS_CLASSNAME_PREFIX.'.'.$intfShortLow.'.'.$intfShort.$serviceName] = $intfShort.$serviceName;
			}
		}

		if( in_array( 'amf', $this->protocols ) ) {
			// write the request class mappings.
			$this->generateTypeMap4AMF( $classMap, 'Request' );
		}
	}

	public function generateRequestResponseClasses4Flex()
	{
		$this->clearErrors();

		$xpath = $this->XPath;
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShortLow = strtolower($intfShort);

		$dirOut = $this->composeSdkDirForLanguage( 'flex' ).'/'.$intfShortLow;
		if( !is_dir($dirOut) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( $dirOut, 0777 );
			if( !is_writable($dirOut) ) {
				$this->FatalErrors[] = 'No write access to folder '.$dirOut;
				return;
			}
		}
		// TODO: Use getServiceMessages() instead of code fragment below
		$msgEntries = $xpath->query( '/wsdl:definitions/wsdl:message' );
		foreach( $msgEntries as $msgEntry ) {
			$elem = $msgEntry->getAttribute('name');
			$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:element[@name="'.$elem.'"]' );
			$entry = $entries->item(0);
			$imports = array();

			// File header
			$outTxt = "/*\n"
					."\t".$this->packageName." $intfFull Services\n"
					."\tCopyright (c) WoodWing Software bv. All Rights Reserved.\n\n"
					."\tIMPORTANT: DO NOT EDIT! THIS PACKAGE IS GENERATED FROM WSDL!\n"
					.'*/'."\n\n";
			$isRequest = (strpos( $elem, 'Response' ) === false );
			if( $isRequest ) {
				// remove and add 'Request' postfix to make sure there is always one postfix (workflow don't have, but admin have)
				$elem = str_replace( 'Request', '', $elem );
				$elem .= 'Request';
			}
			
			// Request/Response class
			$srvPackage = $this->composeWebServicesUri().".{$intfShortLow}";
			$datPackage = $this->composeWebServicesUri().".{$intfShortLow}.dataclasses";
			$outTxt .= "package $srvPackage\n{\n";
			$outTxt .= "/*IMPORTS*/"; // to be replaced below
			$outTxt .= "\t[Bindable]\n";
			$outTxt .= "\t[RemoteClass(alias=\"$srvPackage.$intfShort$elem\")]\n\n";
			$outTxt .= "\tpublic class $intfShort$elem\n\t{\n";
			$childs = null;

			// Compose AMF class components.
			$constructor  = "\t\tpublic function $intfShort$elem() {\n\t\t}\n\n";
			$properties = "";
			$getset = "";

			// Constructor header
			if( $entry ) {
				// get type without namespace
				$type = preg_replace('/^.*?:/', '', $entry->getAttribute('type'));
				if (strlen($type) > 0) {
					// element is a complex type, find complexType
					$typeEntries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType[@name="'.$type.'"]' );
					$typeEntry = $typeEntries->item(0);
					// get complexType childs 
					$childs = $xpath->query( 'schm:sequence/schm:element', $typeEntry );
				} else {
					$childs = $xpath->query( 'schm:complexType/schm:sequence/schm:element', $entry );
				}
				if( $childs->length > 0 ) foreach( $childs as $child ) {
					$propName = $child->getAttribute('name');
					$propType = $child->getAttribute('type');
					$package = ($propType == 'tns:GetStatesResponse') ? $srvPackage : $datPackage; // Exception: GetStates is used as type, but is response
					$flexType = $this->getWsdl2FlexType( $propType, $imports, $package );
					$properties .= "\t\tprivate var _$propName:$flexType;\n";

					// Add Getters/Setters.
					$getset .= $this->getGetterOrSetter4Flex( $propType, $flexType, $propName, false);
					$getset .= $this->getGetterOrSetter4Flex( $propType, $flexType, $propName, true);

					//$nillable = $child->getAttribute('nillable');
					//$showNullable = $nillable ? 'Nullable.' : '';
				}
			}
			$outTxt .= $properties . "\n" . $constructor . $getset ."\t}\n}\n";

			// Inject imports
			$importsTxt = count($imports) > 0 ? "\timport ".implode(";\n\timport ",array_keys($imports)).";\n\n" : '';
			$outTxt = str_replace( '/*IMPORTS*/', $importsTxt, $outTxt );
			
			// Write Flex class file
			$classFile = $dirOut.'/'.$intfShort.$elem.'.as';
			$this->saveClassFile( $classFile, $outTxt );
		}
	}

	public function generateServiceClasses()
	{
		$this->clearErrors();

		$xpath = $this->XPath;
		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);
		$providerBase = $this->intfDescriptor->getProviderBasePath();
		$pluginFull = $this->intfDescriptor->getPluginNameFull();
		$pluginShort = $this->intfDescriptor->getPluginNameShort();

		// TODO: Use getServiceMessages() instead of code fragment below
		$msgEntries = $xpath->query( '/wsdl:definitions/wsdl:message' );
		foreach( $msgEntries as $msgEntry ) {
			$elem = $msgEntry->getAttribute('name');
			$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:element[@name="'.$elem.'"]' );
			$entry = $entries->item(0);
			if( $entry ) {
				$serviceName = $entry->getAttribute('name');
				if( strpos( $serviceName, 'Response' ) === false ) {
					$serviceName = str_replace( 'Request', '', $serviceName );
					// read template file
					$classFile = BASEDIR."/server/buildtools/genservices/templates/Service.template.php";
					$outTxt = file_get_contents( $classFile );
					if( !$outTxt ) {
						$this->FatalErrors[] = 'Could not read from file '.$classFile;
						return;
					}
					$providerDir = $pluginFull ? "__DIR__.'/../.." : "BASEDIR.'/server";
					// fill in template params
					$outTxt = str_replace( '/*PACKAGENAME*/', $this->packageName, $outTxt );
					$outTxt = str_replace( '/*SERVICE*/', $serviceName, $outTxt );
					$outTxt = str_replace( '/*INTFFULL*/', $intfFull, $outTxt );
					$outTxt = str_replace( '/*INTFSHORT*/', $intfShort, $outTxt );
					$outTxt = str_replace( '/*INTFSHORTLOW*/', $intfShortLow, $outTxt );
					$outTxt = str_replace( '/*SERVERVERSION*/', $this->packageVersion, $outTxt );
					$outTxt = str_replace( '/*PLUGINFULL*/', $pluginFull, $outTxt );
					$outTxt = str_replace( '/*PLUGINSHORT*/', $pluginShort, $outTxt );
					$outTxt = str_replace( '/*PROVIDERDIR*/', $providerDir, $outTxt );
					// write template instance into new php class file
					$classFile = "{$providerBase}/services/{$intfShortLow}/{$pluginShort}{$intfShort}{$serviceName}Service.class.php";
					if( file_exists($classFile) ) { // avoid overwriting files that are edit! (after previous generation)
						$this->Warnings['skip'] = 'Service files that were generated before are intend to get changed manually ".
							"atferwards. Avoiding losing changes by SKIPPING service files once exists.';
						$this->SkippedFiles[] = $classFile;
					} else {
						$this->saveClassFile( $classFile, $outTxt );
					}
				}
			}
		}
	}

	public function generateServiceInterfaces()
	{
		$this->clearErrors();

		$xpath = $this->XPath;
		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);

		// TODO: Use getServiceMessages() instead of code fragment below
		$msgEntries = $xpath->query( '/wsdl:definitions/wsdl:message' );
		foreach( $msgEntries as $msgEntry ) {
			$serviceName = $msgEntry->getAttribute('name');
			if( strpos( $serviceName, 'Response' ) === false ) {
				$serviceName = str_replace( 'Request', '', $serviceName );
				// read template file
				$classFile = BASEDIR.'/server/buildtools/genservices/templates/ServiceConnector.template.php';
				$outTxt = file_get_contents( $classFile );
				if( !$outTxt ) {
					$this->FatalErrors[] = 'Could not read from file '.$classFile;
					return;
				}
				// fill in template params
				$outTxt = str_replace( '/*PACKAGENAME*/', $this->packageName, $outTxt );
				$outTxt = str_replace( '/*SERVICE*/', $serviceName, $outTxt );
				$outTxt = str_replace( '/*INTFFULL*/', $intfFull, $outTxt );
				$outTxt = str_replace( '/*INTFSHORT*/', $intfShort, $outTxt );
				$outTxt = str_replace( '/*INTFSHORTLOW*/', $intfShortLow, $outTxt );
				$outTxt = str_replace( '/*SERVERVERSION*/', $this->packageVersion, $outTxt );
				// write template instance into new php class file
				$obsoletedServiceInterfaces = array('WflGetDialog');
				if( !in_array( $intfShort.$serviceName, $obsoletedServiceInterfaces ) ) {
					$classFile = BASEDIR.'/server/interfaces/services/'.$intfShortLow.'/'.$intfShort.$serviceName.'_EnterpriseConnector.class.php';
					if( file_exists($classFile) ) { // avoid overwriting files that are edit! (after previous generation)
						$this->Warnings['skip'] = 'Service interface files that were generated before are intend to get changed ".
							"manually atferwards. Avoiding losing changes by SKIPPING service interface files once exists.';
						$this->SkippedFiles[] = $classFile;
					} else {
						$this->saveClassFile( $classFile, $outTxt );
					}
				}
			}
		}
	}
	
	public function generateSoapServerClientClasses()
	{
		$this->clearErrors();

		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShort = $this->intfDescriptor->getServiceNameShort();

		// read template files
		$classFile = BASEDIR.'/server/buildtools/genservices/templates/SoapServer.template.php';
		$srvrOutTxt = file_get_contents( $classFile );
		if( !$srvrOutTxt ) {
			$this->FatalErrors[] = 'Could not read from file '.$classFile;
			return;
		}
		$classFile = BASEDIR.'/server/buildtools/genservices/templates/SoapClient.template.php';
		$clntOutTxt = file_get_contents( $classFile );
		if( !$clntOutTxt ) {
			$this->FatalErrors[] = 'Could not read from file '.$classFile;
			return;
		}
		
		// determine type maps
		/*$typeMaps = '';
		$arrayClasses = $this->getDataArrayClasses();
		sort($arrayClasses);
		foreach( $arrayClasses as $dataName ) {
			$typeMaps .= "\t\t".'$options[\'typemap\'][] = array(\'type_ns\' => \'urn:SmartConnection\' , \'type_name\' => \''.$dataName.'\' , ' ."\n";
			$typeMaps .= "\t\t\t".'\'from_xml\' => $className . \'::xmlArrayOfTypeToObjects\');'."\n";
		}*/

		// determine class maps
		$classMaps = '';
		$excludeClasses = array_flip( $this->intfDescriptor->getExclDataClasses() );
		$wflClasses = array_flip( $this->intfDescriptor->getWflDataClasses() );
		$dataClasses = $this->getDataClasses();
		//$dataClasses = array_diff_key( $dataClasses, $excludeClasses ); // remove exclusions
		ksort($dataClasses);
		foreach( array_keys($dataClasses) as $dataName /*=> $dataStruct*/ ) {
			if( isset( $wflClasses[$dataName] ) ) { // hack: the planning re-uses some workflow classes
				$classMaps .= "\t\t".'$options[\'classmap\'][\''.$dataName.'\'] = \''.$dataName.'\';'."\n";
			} else if( !isset( $excludeClasses[$dataName] ) ) {
				$classMaps .= "\t\t".'$options[\'classmap\'][\''.$dataName.'\'] = \''.$this->intfDescriptor->getDataClassPrefix().$dataName.'\';'."\n";
			}
		}
		
		// Compose response object class mapping for the SOAP clients.
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$services = $this->getServiceMessages();
		$responseClassMaps = '';
		foreach( $services as $serviceName => $messageStruct ) {
			if( !$messageStruct['isRequest'] ) { // responses only
				$responseClassMaps .= "\t\t".'$options[\'classmap\'][\''.$serviceName.'\'] = \''.$intfShort.$serviceName.'\';'."\n";
			}
		}

		// Determine service location (SOAP entry point) at WSDL
		$xpath = $this->XPath;
		$entries = $xpath->query( '/wsdl:definitions/wsdl:service/wsdl:port/soap:address' );
		$address = $entries->item(0);
		$location = $address->getAttribute('location');
		
		// replace /*...*/ markers for server
		$srvrOutTxt = str_replace( '/*PACKAGENAME*/', $this->packageName, $srvrOutTxt );
		$srvrOutTxt = str_replace( '/*INTFFULL*/', $intfFull, $srvrOutTxt );
		$srvrOutTxt = str_replace( '/*INTFSHORT*/', $intfShort, $srvrOutTxt );
		//$srvrOutTxt = str_replace( '/*TYPEMAPS*/', $typeMaps, $srvrOutTxt );
		$srvrOutTxt = str_replace( '/*CLASSMAPS*/', $classMaps, $srvrOutTxt );
		$srvrOutTxt = str_replace( '/*NAMESPACE*/', $this->intfDescriptor->getNameSpace(), $srvrOutTxt );
		$srvrOutTxt = str_replace( '/*ENTRYPOINT*/', $location, $srvrOutTxt );

		// replace /*...*/ markers for client
		$clntOutTxt = str_replace( '/*PACKAGENAME*/', $this->packageName, $clntOutTxt );
		$clntOutTxt = str_replace( '/*INTFFULL*/', $intfFull, $clntOutTxt );
		$clntOutTxt = str_replace( '/*INTFSHORT*/', $intfShort, $clntOutTxt );
		//$clntOutTxt = str_replace( '/*TYPEMAPS*/', $typeMaps, $clntOutTxt );
		$clntOutTxt = str_replace( '/*CLASSMAPS*/', $classMaps.$responseClassMaps, $clntOutTxt );
		$clntOutTxt = str_replace( '/*NAMESPACE*/', $this->intfDescriptor->getNameSpace(), $clntOutTxt );
		$clntOutTxt = str_replace( '/*ENTRYPOINT*/', $this->intfDescriptor->getSoapEntryPoint(), $clntOutTxt );

		// create server class file		
		$classFile = BASEDIR.'/server/protocols/soap/'.$this->intfDescriptor->getServiceNameShort().'Server.php';
		$this->saveClassFile( $classFile, $srvrOutTxt );

		// create client class file		
		$classFile = BASEDIR.'/server/protocols/soap/'.$this->intfDescriptor->getServiceNameShort().'Client.php';
		$this->saveClassFile( $classFile, $clntOutTxt );
	}

	public function generateJsonClientClasses()
	{
		$this->clearErrors();

		$intfFull = $this->intfDescriptor->getServiceNameFull();
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);
		$providerBase = $this->intfDescriptor->getProviderBasePath();
		$pluginFull = $this->intfDescriptor->getPluginNameFull();
		$jsonClassName = $pluginFull ? "{$pluginFull}_Protocols_Json_{$intfShort}_Client" : "WW_JSON_{$intfShort}Client";

		$classFile = BASEDIR.'/server/buildtools/genservices/templates/JsonClient.template.php';
		$outTxt = file_get_contents( $classFile );
		if( !$outTxt ) {
			$this->FatalErrors[] = 'Could not read from file '.$classFile;
			return;
		}

		// replace /*...*/ markers for client
		$outTxt = str_replace( '/*PACKAGENAME*/', $this->packageName, $outTxt );
		$outTxt = str_replace( '/*INTFFULL*/', $intfFull, $outTxt );
		$outTxt = str_replace( '/*CLASSNAME*/', $jsonClassName, $outTxt );
		$outTxt = str_replace( '/*ENTRYPOINT*/', $this->intfDescriptor->getSoapEntryPoint(), $outTxt );

		// create client class file
		if( $pluginFull ) {
			$classFile = "{$providerBase}/protocols/json/{$intfShortLow}/Client.php";
		} else { // core
			$classFile = "{$providerBase}/protocols/json/{$intfShort}Client.php";
		}
		$this->saveClassFile( $classFile, $outTxt );
	}

	/**
	 * Returns simpleType defintions from WSDL that are enumerations.
	 * Each element in the returned array is an array with 'value' and 'comment' entries.
	 *
	 * @param array $simpleTypeComments
	 * @return array of element definitions
	 */
	private function getSimpleTypeEnums( array & $simpleTypeComments )
	{
		$retVals = array();
		$xpath = $this->XPath;
		$simpleTypes = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:simpleType' );
		foreach( $simpleTypes as $simpleType ) {
			$simpleTypeName = $simpleType->getAttribute('name');
			$simpleEnums = $xpath->query( 'schm:restriction/schm:enumeration', $simpleType );
			foreach( $simpleEnums as $simpleEnum ) {
				$retVals[$simpleTypeName][] = array( 
					'value' => $simpleEnum->getAttribute('value'),
					'comment' => $this->grabCommentsFromSibling( $simpleEnum, false ) );
			}
			$simpleTypeComments[$simpleTypeName] = $this->grabCommentsFromSibling( $simpleType, true );
		}
		return $retVals;
	}
	
	/**
	 * Uses WSDLs to generate HTML files inside the /sdk/doc/interfaces folder to give integrators 
	 * an alternative for reading WSDLs. This became pretty essential since Enterprise Server v8.0 
	 * that supports non-SOAP related protocols: AMF and JSON. For those protocols it is very odd 
	 * to read WSDLs (since WSDLs are specially designed for SOAP traffic).
	 * At the documents, there are two sections: Web Services and Data Entities. Unlike the WSDLs, 
	 * all listed items are sorted and hyperlinked to ease definition lookups.
	 */
	public function generateReadableDocument()
	{
		// Init
		$this->clearErrors();
		$version = 'v'.$this->packageVersion;
		$wsdlFile = basename( $this->intfDescriptor->getWsdlFilePath() );
		$intfFull = $this->intfDescriptor->getServiceNameFull();

		// Collect messages (requests/responses) from WSDL
		$xpath = $this->XPath;
		// TODO: Use getServiceMessages() instead of code fragment below
		$msgEntries = $xpath->query( '/wsdl:definitions/wsdl:message' );
		$msgEntriesSorted = array();
		foreach( $msgEntries as $msgEntry ) {
			$msgEntriesSorted[$msgEntry->getAttribute('name')] = true;
		}
		ksort($msgEntriesSorted);

		// Collect data classes (complexType) from WSDL
		$dataClasses = $this->getDataClasses();
		//$excludeClasses = array_flip( $this->ifDescriptor->getExclDataClasses() );
		//$dataClasses = array_diff_key( $dataClasses, $excludeClasses ); // remove exclusions
		ksort($dataClasses);

		// Collect enumerations (simpleType) from WSDL
		$simpleTypeComments = array();
		$simpleTypeEnums = $this->getSimpleTypeEnums( $simpleTypeComments );
		ksort($simpleTypeEnums);

		// Build HTML document header
		$docOutTxt = '<html>
<head>
	<style>
		body { font-family: helvetica, arial, sans-serif; font-size: 11pt; }
		td { font-family: helvetica, arial, sans-serif; font-size: 11pt; vertical-align:top; }
		div.section { margin: 1em; padding: 1em; border:2px solid #CCCCCC; background-color:#FFEECC; width: 800px; }
		td.caption { vertical-align:middle; padding-left: 1em; padding-top: 1em; }
		table.section { padding: 1em; background-color:#FFDD88; }
		td.hpc { width:180px; font-style: italic; font-weight: bold; } /* head-property-column */ /* WW orange: #FFA500 */
		td.htc { width:220px; font-style: italic; font-weight: bold; } /* head-type-column */
		td.hcc { width:350px; font-style: italic; font-weight: bold; } /* head-comment-column */
		td.bpc {} /* body-property-column */
		td.btc {} /* body-type-column */
		td.bcc {} /* body-comment-column */
		td.fpc {} /* foot-property-column */
		td.ftc { font-style: italic; font-size: 9pt; text-align: right; } /* foot-type-column */
		td.fcc {} /* foot-comment-column */
	</style>
</head>
<body><table><tr><td><img title="" src="woodwing95.gif"/></td>';
		$docOutTxt .= '<td class="caption"><h1>'.$intfFull.' interface - '.$this->packageName.' '.$version.'</h1></td></table>'.PHP_EOL;

		// Build "Web Services" section in HTML
		$docOutTxt .= '<br/><h2>Web Services</h2>'.PHP_EOL;
		foreach( array_keys($msgEntriesSorted) as $elem ) {
			$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:element[@name="'.$elem.'"]' );
			$entry = $entries->item(0);
			if( $entry ) {
				$remark = false;
				$docOutTxt .= '<div class="section"><h3>'.$elem.'</h3>';
				$comments = $this->grabCommentsFromSibling( $entry, true );
				if( $comments ) {
					$docOutTxt .= '<p>'.$comments.'</p>';
				}
				$docOutTxt .= '<table class="section"><tr><td class="hpc">property</td><td class="htc">type</td><td class="hcc">comment</td></tr>'.PHP_EOL;
				$type = preg_replace('/^.*?:/', '', $entry->getAttribute('type'));
				if (strlen($type) > 0) {
					// element is a complex type, find complexType
					$typeEntries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType[@name="'.$type.'"]' );
					$typeEntry = $typeEntries->item(0);
					// get complexType childs 
					$childs = $xpath->query( 'schm:sequence/schm:element', $typeEntry );
				} else {
					$childs = $xpath->query( 'schm:complexType/schm:sequence/schm:element', $entry );
				}
				if( $childs->length > 0 ) foreach( $childs as $child ) {
					$prop = $child->getAttribute('name');
					$type = $child->getAttribute('type');
					$nullable = $child->getAttribute('nillable');
					$comment = $this->grabCommentsFromSibling( $child, false );
					$docOutTxt .= $this->buildReadableType( $type, $prop, $nullable, $comment, 
											$msgEntriesSorted, $dataClasses, $simpleTypeEnums );
					if( !$remark && $nullable ) {
						$remark = true;
					}
				}
				if( $remark ) {
					$docOutTxt .= '<tr><td class="fpc"></td><td class="ftc">* = null value allowed</td><td class="fcc"></td></tr>'.PHP_EOL;
				}
				$docOutTxt .= '</table></div>';
			}
		}

		// Build "Data Entries" section in HTML
		$docOutTxt .= '<br/><h2>Data Entities</h2>'.PHP_EOL;
		foreach( array_keys($dataClasses) as $dataName /*=> $dataStruct*/ ) {
			if( $dataName != 'Row' ) { // suppress workflow Row (which is an array of string) to avoid empty definition
				$entries = $xpath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:complexType[@name="'.$dataName.'"]' );
				$entry = $entries->item(0);
				$docOutTxt .= '<div class="section" id="'.$dataName.'"><h3>'.$dataName.'</h3>';
				$comments = $entry ? $this->grabCommentsFromSibling( $entry, true ) : null;
				if( $comments ) {
					$docOutTxt .= '<p>'.$comments.'</p>';
				}
				$docOutTxt .= '<table class="section"><tr><td class="hpc">property</td><td class="htc">type</td><td class="hcc">comment</td></tr>'.PHP_EOL;
				$remark = false;
				foreach( $dataClasses[$dataName] as $propName => $propStruct ) {
					$nullable = $propStruct['null'];
					$comment = $propStruct['comment'];
					$docOutTxt .= $this->buildReadableType( $propStruct['type'], $propName, $nullable, $comment, 
											$msgEntriesSorted, $dataClasses, $simpleTypeEnums );
					if( !$remark && $nullable ) {
						$remark = true;
					}
				}
				if( $remark ) {
					$docOutTxt .= '<tr><td class="fpc"></td><td class="ftc">* = null value allowed</td><td class="fcc"></td></tr>'.PHP_EOL;
				}
				$docOutTxt .= '</table></div>';
			}
		}

		// Build "Enumeration Types" section in HTML
		$docOutTxt .= '<br/><h2>Enumeration Types</h2>'.PHP_EOL;
		foreach( $simpleTypeEnums as $enumName => $enumStructs ) {
			$docOutTxt .= '<div class="section" id="'.$enumName.'"><h3>'.$enumName.'</h3>';
			$docOutTxt .= '<p>'.$simpleTypeComments[$enumName].'</p>';
			$docOutTxt .= '<table class="section"><tr><td class="htc">choice of</td><td class="hcc">comment</td></tr>'.PHP_EOL;
			foreach( $enumStructs as $enumStruct ) {
				$docOutTxt .= '<tr><td class="btc">"'.$enumStruct['value'].'"</td><td class="bcc">'.$enumStruct['comment'].'</td></tr>'.PHP_EOL;
			}
			$docOutTxt .= '</table></div>';
		}

		// Build HTML document footer
		$docOutTxt .= '<div class="section"><p style="font-size: 9pt;" align="right">Generated from the "'.$wsdlFile.'" file at '.date('j M Y').'</p>'.PHP_EOL;
		$docOutTxt .= '<p style="font-size: 9pt;" align="right">Confidential, '.COPYRIGHT_WOODWING.'</p></div>'.PHP_EOL;
		$docOutTxt .= str_repeat( '<br/>', 35 ); // add some empty space to let # references work (auto-scroll) at end of document
		$docOutTxt .= '</body></html>';
	
		// Create/save the HTML document file (built above)
		$classFile = $this->getSdkDir().'/doc/interfaces/'.$this->intfDescriptor->getServiceNameFull().'.htm';
		$this->saveClassFile( $classFile, $docOutTxt );
	}

	/**
	 * Creates a readable HTML fragment with parameters of a data entity (complexType) or 
	 * web service (element) with type information that is hyperlinked (to lookup).
	 * Helper function of generateReadableDocument().
	 *
	 * @param string $type Property type
	 * @param string $prop Property name
	 * @param boolean $nullable Wether or not null value is allowed for the property
	 * @param boolean $comment Explanation of the property usage
	 * @param array $msgEntriesSorted Sorted list of WSDL messages (requests/responses)
	 * @param array $dataClasses Sorted list of WSDL dataclasses (complexType)
	 * @param array $simpleTypeEnums Sorted list of WSDL enumerations (simpleType)
	 * @return string HTML fragment
	 */
	private function buildReadableType( $type, $prop, $nullable, $comment, 
							array $msgEntriesSorted, array $dataClasses, array $simpleTypeEnums )
	{
		static $basicTypes = array(
					'string' => 'string',
					'dateTime' => 'date (yyyy-mm-dd@hh:mm:ss)',
					'dateTimeOrEmpty' => 'date (yyyy-mm-dd@hh:mm:ss) or empty string',
					'double' => 'double / float',
					'unsignedInt' => 'unsigned integer',
					'integer' => 'integer',
					'int' => 'integer',
					'boolean' => 'boolean',
					
					// Simple types
					'String' => 'string',
					'Row' => 'string',
					'AttachmentContent' => 'binary',
					'Color' => 'color (3-byte, hexadecimal)',
					'Id' => 'integer', // admin WSDL
					'FamilyValue' => 'string',
					);
					
		// Split type into 'array of' prefix and the single (real) type
		$type = str_replace( array('xsd:','tns:'), '', $type ); // remove namespaces
		if( strpos( $type, 'ArrayOf' ) !== false ) {
			$singleType = str_replace( 'ArrayOf', '', $type );
			$typePrefix = 'array of ';
		} else if( strpos( $type, 'array of ' ) !== false ) {
			$singleType = str_replace( 'array of ', '', $type );
			$typePrefix = 'array of ';
		} else {
			$singleType = $type;
			$typePrefix = '';
		}
		
		// Work-around: Fix harmless typo (both terms accidentally plural)
		if( $singleType == 'EditionsPages' ) {
			$singleType = 'EditionPages';
		}
		
		// Check if we have make reference
		$refFound = !isset($basicTypes[$singleType]);
		if( $refFound ) { // non-basic type
			$refFound = isset($msgEntriesSorted[$singleType]);
			if( !$refFound ) {
				$refFound = isset($dataClasses[$singleType]);
				if( !$refFound ) {
					$refFound = isset($simpleTypeEnums[$singleType]);
					if( !$refFound ) {
						$this->Warnings[$singleType] = 'Could not find definition for '.$singleType;
					}
				}
			}
		} else {
			$singleType = $basicTypes[$singleType];
		}
		
		// Output the type
		$mark = $nullable ? '*' : '';
		$docOutTxt = '<tr>';
		if( $refFound ) {
			$docOutTxt .= '<td class="bpc">'.$prop.'</td><td class="btc">'.$typePrefix.'<a href="#'.$singleType.'">'.$singleType.'</a>'.$mark.'</td>';
		} else {
			$docOutTxt .= '<td class="bpc">'.$prop.'</td><td class="btc">'.$typePrefix.$singleType.$mark.'</td>';
		}
		$docOutTxt .= '<td class="bcc">'.$comment.'</td>';
		$docOutTxt .= '</tr>'.PHP_EOL;
		return $docOutTxt;
	}

	/**
	 * Builds one file with PHP classes per interface. Each class can do run-time validation a data class.
	 * The generated code does check for property existence, nillable, type and enumeration values.
	 * For enumeration types (simpleType) there are separate classes generated which check on enum choice.
	 * The validation code calls the WW_Services_Validator class ($validator) for actual validations.
	 * Files are generated in /server/services/<interface>/DataValidators.php
	 */
	public function generateDataValidationClasses()
	{
		// Init
		$this->clearErrors();
		$dataClasses = $this->getDataClasses();
		//$excludeClasses = array_flip( $this->ifDescriptor->getExclDataClasses() );
		//$dataClasses = array_diff_key( $dataClasses, $excludeClasses ); // remove exclusions
		unset( $dataClasses['AttachmentContent'] );
		$intfShort = $this->intfDescriptor->getServiceNameShort();
		$intfShortLow = strtolower($intfShort);
		$simpleTypeComments = array();
		$simpleTypeEnums = $this->getSimpleTypeEnums( $simpleTypeComments ); // Collect enumerations (simpleType) from WSDL
		$providerBase = $this->intfDescriptor->getProviderBasePath();
		$serviceShort = $this->intfDescriptor->getServiceNameShort();
		$pluginShort = $this->intfDescriptor->getPluginNameShort();

		// Build the PHP class file header		
		$outTxt = "<?php\n\n/**\n"
				." * @copyright WoodWing Software bv. All Rights Reserved.\n"
				." *".'/'."\n"
				."\n"
				."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
				."// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!\n"
				."// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -\n"
				."\n";

		// Build PHP validation class for enumerations
		foreach( $simpleTypeEnums as $enumName => $enumStructs ) {
			$outTxt .= "class {$pluginShort}{$serviceShort}{$enumName}Validator\n{\n";
			$outTxt .= "\tstatic public function validate( \$validator, \$enumVal )\n";
			$outTxt .= "\t{\n";
			$outTxt .= "\t\tstatic \$enums = array( ";
			$comma = '';
			foreach( $enumStructs as $enumStruct ) {
				$outTxt .= $comma.'\''.$enumStruct['value'].'\'';
				$comma = ', ';
			}
			$outTxt .= " );\n";
			$outTxt .= "\t\t\$validator->checkEnum( \$enums, \$enumVal );\n";
			$outTxt .= "\t}\n}\n\n"; // end validate()
		}
				
		// Build PHP validation class for complex types
		foreach( $dataClasses as $dataName => $dataStruct ) {
			$outTxt .= "class {$pluginShort}{$serviceShort}{$dataName}Validator\n{\n";
			$outTxt .= "\tstatic public function validate( \$validator, \$datObj )\n";
			$outTxt .= "\t{\n";
			if( $dataStruct ) foreach( $dataStruct as $propName => $propStruct ) {
				$outTxt .= $this->buildValidateFuncBody( $propName, $propStruct['rawtype'], $propStruct['null'], $dataClasses, $simpleTypeEnums );
			}
			$outTxt .= "\t}\n}\n\n"; // end validate()
		}

		// Create/save the PHP classes file (built above)
		$classFile = "{$providerBase}/services/{$intfShortLow}/DataValidators.php";
		$this->saveClassFile( $classFile, $outTxt );
	}
	
	/**
	 * Builds a PHP code fragment that does run-time validation of a property (complexType) with all its members.
	 * The generated code does check for property existence, nillable, type and enumeration values.
	 * The validation code calls the WW_Services_Validator class ($validator) for actual validations.
	 * Helper function for generateRequestResponseClasses4Php() and generateDataValidationClasses().
	 *
	 * @param string  $propName        Name of the property
	 * @param string  $propType        Type of the property
	 * @param boolean $nullable        Whether or not the property is can be nil
	 * @param array   $dataClasses     Complete set of data classes (complexType)
	 * @param array   $simpleTypeEnums Complete set of enumeration classes (simpleType)
	 * @return string PHP code fragment 
	 */
	private function buildValidateFuncBody( $propName, $propType, $nullable, array $dataClasses, array $simpleTypeEnums )
	{
		// Type map to resolve most of WSDL->PHP type mapping
		static $basicTypes = array(

			// WSDL/xsd => PHP:
			'double' => 'float',
			'int' => 'integer',
			'integer' => 'integer',
			'string' => 'string',
			'boolean' => 'boolean',

			// WSDL/tns => PHP (just to simplify/speed run-time validations):
			'String' => 'string',
			'Row' => 'array', // exception needed since it has no 'ArrayOf' in its name
			'FamilyValue' => 'string',
			'AttachmentContent' => 'SOAP_Attachment',

			// no PHP type available, leave as-is to resolve run-time:
			'unsignedInt' => 'unsignedInt',
			'dateTime' => 'dateTime',
			'dateTimeOrEmpty' => 'dateTimeOrEmpty',
			'Id' => 'Id', // admin WSDL
			'Color' => 'Color',
			// ... => NOTE: When adding more types, add them to WW_Services_Validator->checkType() as well!!!
		);
		if( $propType == 'Row' ) $propType = 'ArrayOfString';
		$isArray = strpos( $propType, 'ArrayOf' ) === 0;
		$dataClassPrefix = $this->intfDescriptor->getDataClassPrefix();
		$serviceShort = $this->intfDescriptor->getServiceNameShort();
		$pluginShort = $this->intfDescriptor->getPluginNameShort();

		// Check if types are complete
		if( !$isArray ) {
			$refFound = isset($basicTypes[$propType]);
			if( !$refFound ) { // non-basic type
				$refFound = isset($dataClasses[$propType]);
				if( !$refFound ) {
					$refFound = isset($simpleTypeEnums[$propType]);
					if( !$refFound ) {
						$this->Warnings[$propType] = 'Could not find definition for '.$propType;
					}
				}
			}
		}

		// Build the PHP validation code
		$outTxt = "\t\tif( \$validator->checkExist( \$datObj, '$propName' ) ) {\n";
		$outTxt .= "\t\t\t\$validator->enterPath( '$propName' );\n";
		if( !$nullable ) { // mandatory?
			$outTxt .= "\t\t\t\$validator->checkNull( \$datObj->$propName );\n";
		}
		$outTxt .= "\t\t\tif( !is_null( \$datObj->$propName ) ) {\n";

		if( $isArray ) {
			$propType = str_replace( 'ArrayOf', '', $propType ); // child item type (when array) or this prop type (when single)
			if( isset($basicTypes[$propType]) ) $propType = $basicTypes[$propType];
			$isEnum = isset($simpleTypeEnums[$propType]);
			$outTxt .= "\t\t\t\t\$validator->checkType( \$datObj->$propName, 'array' );\n";
			$outTxt .= "\t\t\t\tif( !empty(\$datObj"."->$propName) ) foreach( \$datObj"."->$propName as \$listItem ) {\n";
			$typeCheck = $isEnum ? 'string' : $propType;
			$workflowDataClasses = $this->intfDescriptor->getWflDataClasses();
			if( !$isEnum && (isset($dataClasses[$propType]) || isset($simpleTypeEnums[$propType])) ) {
				if( $propType == 'GetStatesResponse' && $dataClassPrefix == '' ) { // exception: both data type as response
					$typeCheck = 'Wfl'.$typeCheck;
				} else if( in_array( $propType, $workflowDataClasses ) ) {
					// Pln interface is a subset of Wfl, so these data types will not be with Pln prefix($dataClassPrefix)
				} else {
					$typeCheck = $dataClassPrefix.$typeCheck;
				}
			}
			$outTxt .= "\t\t\t\t\t\$validator->enterPath( '$propType' );\n";
			$outTxt .= "\t\t\t\t\t\$validator->checkType( \$listItem, '$typeCheck' );\n";
			if( isset($dataClasses[$propType]) || $isEnum ) {
				$outTxt .= "\t\t\t\t\t{$pluginShort}{$serviceShort}{$propType}Validator::validate( \$validator, \$listItem );\n";
			}
			$outTxt .= "\t\t\t\t\t\$validator->leavePath();\n";
			$outTxt .= "\t\t\t\t}\n"; // end foreach
		} else {
			$isEnum = isset($simpleTypeEnums[$propType]);
			if( isset($basicTypes[$propType]) ) $propType = $basicTypes[$propType];
			$typeCheck = $isEnum ? 'string' : $propType;
			$workflowDataClasses = $this->intfDescriptor->getWflDataClasses();
			if( !$isEnum && (isset($dataClasses[$propType]) || isset($simpleTypeEnums[$propType])) ) {
				if( $propType == 'GetStatesResponse' && $dataClassPrefix == '' ) { // exception: both data type as response
					$typeCheck = 'Wfl'.$typeCheck;
				} else if( in_array( $propType, $workflowDataClasses ) ) {
					// Pln interface is a subset of Wfl, so these data types will not be with Pln prefix($dataClassPrefix)
				} else {
					$typeCheck = $dataClassPrefix.$typeCheck;
				}
			}
			$outTxt .= "\t\t\t\t\$validator->checkType( \$datObj->$propName, '$typeCheck' );\n";
			if( isset($dataClasses[$propType]) || $isEnum ) {
				$outTxt .= "\t\t\t\t{$pluginShort}{$serviceShort}{$propType}Validator::validate( \$validator, \$datObj->$propName );\n";
			}
		}
		$outTxt .= "\t\t\t}\n"; // end if !is_null()
		$outTxt .= "\t\t\t\$validator->leavePath();\n";
		$outTxt .= "\t\t}\n"; // end if checkExists()
		return $outTxt;
	}

	/**
	 * Creates a file on disk. Also creates the file path in case it does not exists yet.
	 *
	 * @since 10.2.0
	 * @param string $classFile
	 * @param string $content Class definition to write into $classFile
	 */
	private function saveClassFile( $classFile, $content )
	{
		$dirOut = dirname( $classFile );
		if( !is_dir( $dirOut ) ) {
			require_once BASEDIR.'/server/utils/FolderUtils.class.php';
			FolderUtils::mkFullDir( $dirOut, 0777 );
		}
		$fp = @fopen( $classFile, 'w+' );
		if( !$fp || fwrite( $fp, $content ) === false ) {
			$this->ErrorFiles[] = $classFile;
		} else {
			$this->SuccessFiles[] = $classFile;
		}
		if( $fp ) {
			fclose( $fp );
		}
	}
}