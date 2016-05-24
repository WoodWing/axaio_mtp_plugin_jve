<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';
require_once dirname(__FILE__) . '/../SabreAMF/SabreAMF/Externalized.php';


/**
 * Simplified version of org.granite.collections.BasicMap<K, V>
 */
class BasicMap extends AbstractRemoteObject implements SabreAMF_Externalized, IteratorAggregate, ArrayAccess, Countable {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'org.granite.collections.BasicMap';
	}
		
	/**
	 * data
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Construct this object
	 *
	 * @param array $data pass an array here to populate the array collection
	 * @return void
	 */
	function __construct($data = array()) {

		if (!$data) $data = array();
		$this->data = new ArrayObject($this->mapToArray($data));

	}

	/**
	 * This is used by SabreAMF when this object is unserialized (from AMF3)
	 *
	 * @param array $data
	 * @return void
	 */
	function readExternal($data) {
		$this->data = new ArrayObject($this->mapToArray($data));
	}

	private function mapToArray($data) {
		$transformedData = array();
		
		foreach ($data as $entry) {
			$key = $entry[0];
			$value = $entry[1];
			$transformedData[$key] = $value;
		}
		
		return $transformedData;
	}
	
	/**
	 * This is used by SabreAMF when this object is serialized
	 *
	 * @return array
	 */
	function writeExternal() {

		return iterator_to_array($this->data);

	}

	/**
	 * implemented from IteratorAggregate
	 *
	 * @return ArrayObject
	 */
	function getIterator() {

		return $this->data;

	}

	/**
	 * implemented from ArrayAccess
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	function offsetExists($offset) {

		return isset($this->data[$offset]);

	}

	/**
	 * Implemented from ArrayAccess
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	function offsetGet($offset) {

		return $this->data[$offset];

	}

	/**
	 * Implemented from ArrayAccess
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	function offsetSet($offset,$value) {

		if (!is_null($offset)) {
			$this->data[$offset] = $value;
		} else {
			$this->data[] = $value;
		}

	}

	/**
	 * Implemented from ArrayAccess
	 *
	 * @param mixed $offset
	 * @return void
	 */
	function offsetUnset($offset) {

		unset($this->data[$offset]);

	}

	/**
	 * Implemented from Countable
	 *
	 * @return int
	 */
	function count() {

		return count($this->data);

	}

}