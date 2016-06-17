<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_JSON_Services
{
	/**
	 *
	 */
	public function __construct()
	{
		// init authorization
		global $globAuth;
		if (! isset( $globAuth )) {
			require_once BASEDIR . '/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
	}

	/**
	 * Converts an array based decoded JSON request structure into an Enterprise object structure.
	 * The embracing JSON request array remains untouched, but its inner 'params' member gets converted.
	 *
	 * @param array $options JSON request to convert
	 * @return array Enterprise request
	 */
	public function arraysToObjects( array $options )
	{
		// Check if special WW property is set which indicates array must be converted to Enterprise request/data object.
		if( isset($options['__classname__']) ) {
			$className = $options['__classname__'];
			if( class_exists($className) ) {
				$cnvOptions = new $className();
			} else {
				$cnvOptions = new stdClass(); // quickfix to continue building request structure
				$this->_isMethodError = true;
				LogHandler::Log( __CLASS__, 'ERROR', 'Unknown classname in JSON request: '.$className );
			}
			foreach( $options as $key => $value ) {
				if( is_array( $value ) ) {
					$cnvOptions->$key = $this->arraysToObjects( $value );
				} else {
					$cnvOptions->$key = $value;
				}
			}
			unset($cnvOptions->__classname__);
		} else {
			$cnvOptions = array();
			foreach( $options as $key => $value ) {
				if( is_array( $value ) ) {
					$cnvOptions[$key] = $this->arraysToObjects( $value );
				} else {
					$cnvOptions[$key] = $value;
				}
			}
		}
		return $cnvOptions;
	}

	/**
	 * Restructure objects in two ways:
	 * - Removes elements which are not reflected in the class signature.
	 * - Adds round trip information.
	 *
	 * @param array|object $arrayObject
	 * @throws BizException If the passed parameter is of the wrong type, it's an invalid operation
	 * @return array|object $arrayObject
	 */
	public static function restructureObjects($arrayObject) {

		// Nothing to do
		if (is_object($arrayObject)) {

			// Create a reference from the found class
			$parameterClass = get_class($arrayObject);
			$referenceClassProps = get_class_vars( $parameterClass );

			$tmpObject = (array) $arrayObject; // Cast the object to an array to make it flat
			$tmpObject['__classname__'] = $parameterClass; // Add roundtrip information

			$checkProperties = array(); // We need an array with only keys to check against
			foreach ($referenceClassProps as $property => $value) {

				$checkProperties[$property] = true; // Just fill it with something
			}
		}
		elseif (is_array($arrayObject)) {

			// Default: it's an array, so copy 'object' in both and move on
			$tmpObject = $arrayObject;
			$checkProperties = $arrayObject;
		}
		else {

			$msg = 'Parameter passed into '.__METHOD__.'() function has type '.gettype($arrayObject).' which is invalid; Should be array or object.';
			throw new BizException( 'ERR_INVALID_OPERATION', 'Client', $msg );
		}

		if ($tmpObject) foreach ($tmpObject as $property => $value) {

			// Recursive if it's an array or object, else keep the value.
			$value = (is_array($value) || is_object($value)) ? self::restructureObjects($value) : $value;

			// Compare arrays if the passed parameter is an object.
			if (is_object($arrayObject)) {

				// Check if the property is reflected in the class signature, or is roundtrip information
				if (array_key_exists($property, $checkProperties) || $property == '__classname__') {

					// Value can be a pruned array-/objecttree
					$arrayObject->$property = $value;
				}
				else {

					// If property is not reflected in the class signature, unset it
					unset($arrayObject->$property);
					LogHandler::Log( __CLASS__, 'INFO', 'Pruned property from class '.$parameterClass.': '.$property );
				}
			}
			// If passed parameter is an array, add to return value
			elseif (is_array($arrayObject)) {

				// Value can be a pruned array-/objecttree
				$arrayObject[$property] = $value;
			}
		}

		// Return the possibly restructured parameter
		return $arrayObject;
	}
}