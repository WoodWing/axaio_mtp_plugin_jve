<?php
/**
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This abstract class defines an interface to setup an entire workflow.
 * Al it requires is a home brewed data structure (to be provided) to specify the workflow setup.
 * It is up to the implementation classes to setup and tear down the specified admin entities.
 */

abstract class WW_TestSuite_Setup_AbstractConfig
{
	/** @var stdClass */
	protected $config;
	/** @var TestCase */
	protected $testCase;
	/** @var string */
	protected $ticket;
	/** @var string */
	private $timeStamp;
	/** @var WW_Utils_TestSuite */
	protected $testSuiteUtils;

	/**
	 * Create test data used by the test script.
	 */
	abstract public function setupTestData();

	/**
	 * Remove test data used by the test script.
	 */
	abstract public function teardownTestData();

	/**
	 * @param TestCase $testCase
	 * @param string $ticket
	 * @param string $timeStamp
	 * @param WW_Utils_TestSuite $testSuiteUtils
	 */
	public function __construct( TestCase $testCase, $ticket, $timeStamp, WW_Utils_TestSuite $testSuiteUtils )
	{
		$this->testCase = $testCase;
		$this->ticket = $ticket;
		$this->timeStamp = $timeStamp;
		$this->testSuiteUtils = $testSuiteUtils;
		$this->config = new stdClass();
	}

	/**
	 * Set a property taken from the home brewed data structure object.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function addConfigOption( $key, $value )
	{
		$this->config->{$key} = $value;
	}

	/**
	 * In a given string value, replace any placeholders named '%timestamp%' with a datetime timestamp.
	 *
	 * @param string $value String value that may contain the timestamp placeholder.
	 * @return string Same as $value but any placeholders are replaced with a timestamp.
	 */
	protected function replaceTimeStampPlaceholder( $value )
	{
		return str_replace( '%timestamp%', $this->timeStamp, $value );
	}

	/**
	 * Copy over all the values from a home brewed data structure object to an admin data class.
	 *
	 * @param stdClass $dataConfig The home brewed data structure object to copy from.
	 * @param mixed $dataClass The admin data class to copy to.
	 */
	protected function copyConfigPropertiesToAdminClass( $dataConfig, $dataClass )
	{
		foreach( array_keys( get_class_vars( get_class( $dataClass ) ) ) as $prop ) {
			if( array_key_exists( $prop, $dataConfig ) ) {
				if( is_string( $dataConfig->{$prop} ) ) {
					$dataClass->{$prop} = $this->replaceTimeStampPlaceholder( $dataConfig->{$prop} );
				} else {
					$dataClass->{$prop} = $dataConfig->{$prop};
				}
			}
		}
	}

	/**
	 * Compose a list of MetaDataValues from a home brewed data structure object.
	 *
	 * @param stdClass $dataConfig
	 * @return MetaDataValue[]
	 */
	protected function configPropertiesToMetaDataValues( $dataConfig )
	{
		$mdValues = array();
		foreach( get_object_vars( $dataConfig ) as $propName => $propValue ) {
			$mdValue = new MetaDataValue();
			$mdValue->Property = $propName;
			if( is_string( $propValue ) ) {
				$mdValue->Values = array( $this->replaceTimeStampPlaceholder( $propValue ) );
			} else {
				$mdValue->Values = array( $propValue );
			}
			$mdValues[] = $mdValue;
		}
		return $mdValues;
	}
}