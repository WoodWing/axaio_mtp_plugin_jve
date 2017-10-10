<?php
/**
 * @package Enterprise
 * @subpackage Utils
 * @since v6.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Utilities dealing with PHP classes itself.
 */

class WW_Utils_PHPClass
{
	/**
	 * Casts an object to a different class
	 *
	 * @param object $oldObject     Object instance to cast.
	 * @param string $newClassname  Class to cast object to.
	 * @return mixed The casted object instance.
	 * @throws BizException
	 */
	static public function typeCast( $oldObject, $newClassname ) 
	{
		if( !class_exists($newClassname) ) {
			throw new BizException( 'ERR_ERROR', 'Server', 'Class '.$newClassname.' does not exist.' );
		}
		$old_serializedObject = serialize($oldObject);
		$oldObjectNameLength = strlen(get_class($oldObject));
		$subtringOffset = $oldObjectNameLength + strlen($oldObjectNameLength) + 6;
		$newSerializedObject  = 'O:' . strlen($newClassname) . ':"' . $newClassname . '":';
		$newSerializedObject .= substr($old_serializedObject, $subtringOffset);
		return unserialize($newSerializedObject);
	}
	
	/**
	 * Copies public, non-static properties from one object instance to another.
	 * Only properties that are 'shared' (intersection) between both instances are copied!
	 *
	 * @param object $objFrom Object instance to take property values from.
	 * @param object $objTo   Object instance to update with values from $objFrom.
	 */
	static public function copyObjPropsIntersect( $objFrom, &$objTo )
	{
		$varsFrom = array_keys( get_object_vars( $objFrom ) );
		$varsTo = array_keys( get_object_vars( $objTo ) );
		$sharedProps = array_intersect( $varsFrom, $varsTo );
		foreach( $sharedProps as $propName ) {
			$objTo->$propName = $objFrom->$propName;
		}
	}

	/**
	 * Copies all properties from one object instance to another.
	 * All properties on both instances are copied!
	 *
	 * @param object $objFrom Object instance to take property values from.
	 * @param object $objTo   Object instance to update with values from $objFrom.
	 */
	static public function copyObjProps( $objFrom, &$objTo )
	{
		$varsFrom = array_keys( get_class_vars( get_class( $objFrom ) ) );
		$varsTo = array_keys( get_class_vars( get_class( $objTo ) ) );
		$allProps = array_intersect( $varsFrom, $varsTo );
		foreach( $allProps as $propName ) {
			$objTo->$propName = $objFrom->$propName;
		}
	}

	/**
	 * Checks if a method is declared in a class.
	 *
	 * Normally, to check if a method is declared in a class, method_exists() can
	 * be used. However, to check if a method exists in a child class (a 'child'
	 * that extends its 'parent' class), method_exists() is not applicable because
	 * as long as the parent class has the method declared, method_exists() returns
	 * true (where we expect a false).
	 *
	 * Example:
	 *    class Bar { public function abc() {} }
	 *    class Foo extends Bar { }
	 *    echo method_exists( 'Foo', 'abc' ) ? 'true' : 'false';
	 *    echo methodExistsInDeclaringClass( 'Foo', 'abc' ) ? 'true' : 'false';
	 * Result:
	 *    true
	 *    false
	 * Method abc() is declared in the parent class but not in the child class.
	 * If we want to find out if abc() is declared in the child class,
	 * method_exists() returns true which is unwanted.
	 *
	 * To tackle this problem, function methodExistsInDeclaringClass() can be used.
	 * By passing in the class (can be a child class), it searches if a method is declared
	 * in the mentioned class.
	 *
	 * @param string $class Class name.
	 * @param string $method Method name.
	 * @return bool Whether or not the method ($method) exists in the class ($class).
	 */
	public static function methodExistsInDeclaringClass( $class, $method )
	{
		try {
			$reflectionClass = new ReflectionClass( $class ); // e.g. the 'child'
			// Fetch the method from parent or child. This throws Exception when both don't declare the method.
			$reflectionMethod = $reflectionClass->getMethod( $method );

			// Checks whether method is explicitly defined in this class
			$retVal = $reflectionMethod->getDeclaringClass()->getName() == $reflectionClass->getName();
		} catch( Exception $e ) {
			$retVal = false;
		}
		return $retVal;
	}

	/**
	 * Returns the entry of the PHP stack of the calling function. Provides details of 
	 * function A that has called function B, whereby function B calls this utils function 
	 * {@link getCaller()} to find out who is function A.
	 *
	 * @since 9.7.0
	 * @param string $calledClass Should be __CLASS__
	 * @param string $calledFunction Should be __FUNCTION__
	 * @param array $skipClasses Classes (names) that are between the caller and callee to be ignored.
	 * @param integer $plies Pass in 1 to get the caller, or 2 to get the caller of the caller too, etc
	 * @return array List of stack entries, see {@link debug_backtrace()}
	 */
	static public function getCallers( $calledClass, $calledFunction, $skipClasses, $plies ) 
	{
		$retVals = array();
		$found = false;
		if( defined('DEBUG_BACKTRACE_PROVIDE_OBJECT') && defined('DEBUG_BACKTRACE_IGNORE_ARGS') ) {
			$trace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS ); // ignore arguments for performance reasons
		} else { // let's not be noisy for older PHP versions < 5.3.6
			$trace = debug_backtrace();
		}
		$countDown = count($trace) - 1;
		for( $lev = 1; $lev <= $countDown; $lev++ ) {
			$stackClass = isset($trace[$lev]['class']) ? $trace[$lev]['class'] : '';
			$stackFunction = isset($trace[$lev]['function']) ? $trace[$lev]['function'] : '';
			if( $found && !in_array( $stackClass, $skipClasses ) ) {
				$retVals[] = $trace[$lev];
				if( $plies == count( $retVals ) ) {
					break;
				}
			} else if( $calledClass == $stackClass && $calledFunction == $stackFunction ) {
				$found = true;
			}
		}
		return $retVals;
	}
}
