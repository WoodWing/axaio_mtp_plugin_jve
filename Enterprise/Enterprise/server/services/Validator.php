<?php
/**
 * Services Validator
 * 
 * Checks all properties of service requests and reponse objects of Enterprise interfaces.
 * It does name, type, nillable, existence checking.
 *
 * @package 	Enterprise
 * @subpackage 	Services
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class WW_Services_Validator
{
	private $pathNodes = array();
	private $isRequest = null; // TRUE for Request, or FALSE for Response
	
	public function __construct( $isRequest )
	{
		$this->isRequest = $isRequest;
	}
	
	/**
	 * Builds a data path from a request or reponse.
	 * Can be called when a validation check has been failed at a service.
	 * It uses the path set/unset by the {@link: enterPath} and {@link: leavePath}
	 * functions called from validate() while validating serivce. Each node at this path is
	 * printed at a string separated with arrow ("->") markers.
	 * For example: LogOnResponse->NamedQueries->NamedQuery
	 *
	 * @return string The data path
	 */
	private function buildDataPath()
	{
		return implode( '->', $this->pathNodes );
	}
	
	public function enterPath( $node )
	{
		array_push( $this->pathNodes, $node );
	}

	public function leavePath()
	{
		array_pop( $this->pathNodes );
	}
	
	/**
	 * Throws an BizException with a given error message to return a fault to client app.
	 * When the data path is configured at SERVICE_VALIDATION_IGNORE_PATHS setting, it suppresses
	 * the exception and just writes to log file.
	 *
	 * @param string $errorMsg
	 * @throws BizException
	 */
	private function raiseFault( $errorMsg )
	{
		$raiseException = true;
		$dataPath = $this->buildDataPath();
		if( !empty($dataPath) ) {
			// Check if we should suppress raising the error (and so log only)
			if( defined('SERVICE_VALIDATION_IGNORE_PATHS') ) {
				$ignores = unserialize(SERVICE_VALIDATION_IGNORE_PATHS);
				//$raiseException = !in_array( $dataPath, $ignores );
				foreach( $ignores as $ignore ) {
					if( strpos( $dataPath, $ignore ) === 0 ) {
						$raiseException = false;
						break;
					}
				}
			}
			$errorMsg .= "\nData path: ".$dataPath;
		}
		if( $raiseException ) { // raise a fault?
			$context = $this->isRequest ? 'Client' : 'Server'; // Bad request = client's fault. Bad reponse = server's fault.
			throw new BizException( 'ERR_ARGUMENT', $context, $errorMsg, null, null, 'ERROR' );
		} else { // Suppress the a fault and so we just log only
			LogHandler::Log( 'ServicesValidator', 'ERROR', $errorMsg );
		}
	}

	/**
	 * Checks if a given property value is one of a given enumeration set.
	 *
	 * @param array $enums List of possible values
	 * @param string $enumVal Current value to check
	 * @throws BizException when property is not set. See {@link raiseFault} for more details.
	 */
	public function checkEnum( array $enums, $enumVal )
	{
		if( !in_array( $enumVal, $enums) ) {
			$error = "Enumeration option does not exist: '$enumVal'";	
			$this->raiseFault( $error );
		}
	}
	
	/**
	 * Checks if the given parent have a certain property.
	 *
	 * @param Mixed $parentObj Either an object or array that should have the property
	 * @param string $propName Name of property that should be present.
	 * @return boolean Wether or not the property does exist. False (or exception!) when missing.
	 * @throws BizException when property does not exist. See {@link raiseFault} for more details.
	 */
	public function checkExist( $parentObj, $propName )
	{
		$error = '';
		switch( gettype($parentObj) ) {
			case 'object':
				if( !property_exists( $parentObj, $propName ) ) {
					$error = 'Property does not exist.';	
				}
				break;
			case 'array':
				if( !array_key_exists( $propName, $parentObj ) ) {
					$error = 'Property does not exist.';	
				}
				break;
			default: // should never happen
				$error = 'Property has unknown parent type "'.gettype($parentObj).'".';
				break;
		}
		if( !empty($error) ) {
			$this->enterPath( $propName );
			$this->raiseFault( $error );
			$this->leavePath();
		}
		return empty($error);
	}

	/**
	 * Checks if a property is not null.
	 * Should only be called for non-nillable (mandatory) properties.
	 *
	 * @param string $propValue
	 * @throws BizException when property is null. See {@link raiseFault} for more details.
	 */
	public function checkNull( $propValue )
	{
		if( is_null($propValue) ) {
			$error = "Property is null, which is not allowed.";
			$this->raiseFault( $error );
		}
	}

	/**
	 * Checks if a property has the correct type.
	 * It also has hard-coded enumeration checking for SCE's workflow interface.
	 *
	 * @param mixed $propValue   Actual property value
	 * @param string $propType    The WSDL data type. Will be mapped onto PHP types.
	 * @throws BizException when property has bad type. See {@link raiseFault} for more details.
	 */
	public function checkType( $propValue, $propType )
	{
		$actualType = gettype($propValue);
		$emptyAllowed = false;
		$formatOk = true;
		$typeOk = true;
		switch( $actualType ) {
			case 'object':
				$actualType = get_class($propValue);
				$typeOk = ( $propType == $actualType || 'stdClass' == $actualType || $propType == 'AttachmentContent' );
				break;
			case 'string':
				switch( $propType ) {
				
					// PHP types...
					case 'integer':
						$formatOk = is_numeric($propValue);
						$emptyAllowed = ($formatOk && $propValue == 0); // to let 'empty' function work (below)
						break;
					case 'float':
						$formatOk = is_numeric($propValue);
						$emptyAllowed = ($formatOk && $propValue == 0); // to let 'empty' function work (below)
						break;
					case 'string':
					case 'array':
						$formatOk = true;
						$emptyAllowed = true;
						break;
					case 'boolean':
						$formatOk = ($propValue === 'true' || $propValue === 'false');
						$emptyAllowed = ($formatOk && $propValue === 'false'); // to let 'empty' function work (below)
						break;

					// WSDL types and hard-coded simpleTypes...
					case 'unsignedInt': // WSDL type
					case 'Id': // WSDL type
						$formatOk = is_numeric($propValue) && $propValue >= 0;
						$emptyAllowed = ($formatOk && $propValue == 0); // to let 'empty' function work (below)
						break;
					case 'dateTimeOrEmpty': // simpleType
						$emptyAllowed = true;
						if( !empty($propValue) ) {
							require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
							$formatOk = DateTimeFunctions::validSoapDateTime($propValue);
						}
						break;
					case 'dateTime': // WSDL type
						require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
						$formatOk = DateTimeFunctions::validSoapDateTime($propValue);
						break;
					case 'Color': // simpleType
						$formatOk = ctype_xdigit($propValue) && strlen($propValue) == 6; // TODO: validate color format (ctype_xdigit)
						break;
					default:
						$typeOk = false;
						break;
				}
				break;
			case 'integer': 
			case 'float': 
			case 'double': 
				$typeOk = ($propType == 'integer' || $propType == 'float' || $propType == 'string' ||
					$propType == 'unsignedInt' || $propType == 'Id'); // simpleType
				$formatOk = ($propType == 'unsignedInt') ? ($propValue >= 0) : true;
				$emptyAllowed = ($typeOk && $formatOk && ( $propValue === 0 || $propValue === 0.0 ) ); // to let 'empty' function work (below)
				break;
			case 'boolean': 
				if( $propType == 'boolean' || $propType == 'string' ) {
					$formatOk = ($propValue === true || $propValue === false);
					$emptyAllowed = ($propValue === false); // to let 'empty' function work (below)
				} else {
					$typeOk = false;
				}
				break;
			case 'array': 
				$typeOk = ($propType == 'array');
				$emptyAllowed = true;
				break;
			case 'NULL': 
				$typeOk = true; // null is already checked at checkNull()
				$emptyAllowed = true;
				break;
			default:
				$typeOk = false;
				break;
		}
		if( !$emptyAllowed && empty($propValue) ) {
			$error = "Property is empty, which is not allowed (type info: '$propType' / '$actualType').";
			$this->raiseFault( $error );
		} elseif( !$typeOk ) {
			$error = "Property has wrong type '$actualType' which should be '$propType'.";
			$this->raiseFault( $error );
		} elseif( !$formatOk ) {
			$error = "Property (of type '$propType') has badly formatted value '$propValue'.";
			$this->raiseFault( $error );
		}
	}
}