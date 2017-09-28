<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_PhpUnitTests_KeyValueArray_TestCase extends TestCase
{
	public function getDisplayName() { return 'WW_Utils_KeyValueArray class'; }
	public function getTestGoals()   { return 'Checks if arrays are properly inserted.'; }
	public function getPrio()        { return 150; }
	public function getTestMethods() { return 'Call the functions of the WW_Utils_KeyValueArray class and and validate the expected return values.';	}

	/** @var array The array to insert data into. For each test, it is cloned to avoid side effects with other tests. */
	private $editArray;

	/** @var array The data to insert into $editArray. */
	private $insertArray;

	/**
	 * @inheritdoc
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/KeyValueArray.class.php';
		try {
			$this->setupTestData();

			$this->testInsertBefore1();
			$this->testInsertBefore2();
			$this->testInsertBefore3();

			$this->testInsertAfter1();
			$this->testInsertAfter2();
			$this->testInsertAfter3();

		} catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/**
	 * Setup arrays to test with.
	 */
	private function setupTestData()
	{
		$this->editArray = array(
			'aap' => 1,
			'noot' => 10,
			'mies' => 4,
		);
		$this->insertArray = array(
			'vuur' => 3,
			'boom' => 2,
		);
	}

	/**
	 * Tear down used test data.
	 */
	private function tearDownTestData()
	{
		unset( $this->editArray );
		unset( $this->insertArray );
	}

	/**
	 * Validate insertion of 'boom' and 'roos' before 1st item 'aap'.
	 */
	private function testInsertBefore1()
	{
		$editArrayClone = unserialize( serialize( $this->editArray ) ); // deep clone
		WW_Utils_KeyValueArray::insertBeforeKey( $editArrayClone, 'aap', $this->insertArray );
		$expectedArray = array(
			'vuur' => 3,
			'boom' => 2,
			'aap' => 1,
			'noot' => 10,
			'mies' => 4,
		);
		$this->assertIdenticalArrays( $expectedArray, $editArrayClone );
	}

	/**
	 * Validate insertion of 'boom' and 'roos' before 2nd item 'noot'.
	 */
	private function testInsertBefore2()
	{
		$editArrayClone = unserialize( serialize( $this->editArray ) ); // deep clone
		WW_Utils_KeyValueArray::insertBeforeKey( $editArrayClone, 'noot', $this->insertArray );
		$expectedArray = array(
			'aap' => 1,
			'vuur' => 3,
			'boom' => 2,
			'noot' => 10,
			'mies' => 4,
		);
		$this->assertIdenticalArrays( $expectedArray, $editArrayClone );
	}

	/**
	 * Validate insertion of 'boom' and 'roos' before 3rd item 'mies'.
	 */
	private function testInsertBefore3()
	{
		$editArrayClone = unserialize( serialize( $this->editArray ) ); // deep clone
		WW_Utils_KeyValueArray::insertBeforeKey( $editArrayClone, 'mies', $this->insertArray );
		$expectedArray = array(
			'aap' => 1,
			'noot' => 10,
			'vuur' => 3,
			'boom' => 2,
			'mies' => 4,
		);
		$this->assertIdenticalArrays( $expectedArray, $editArrayClone );
	}

	/**
	 * Validate insertion of 'boom' and 'roos' after 1st item 'aap'.
	 */
	private function testInsertAfter1()
	{
		$editArrayClone = unserialize( serialize( $this->editArray ) ); // deep clone
		WW_Utils_KeyValueArray::insertAfterKey( $editArrayClone, 'aap', $this->insertArray );
		$expectedArray = array(
			'aap' => 1,
			'vuur' => 3,
			'boom' => 2,
			'noot' => 10,
			'mies' => 4,
		);
		$this->assertIdenticalArrays( $expectedArray, $editArrayClone );
	}

	/**
	 * Validate insertion of 'boom' and 'roos' after 2nd item 'noot'.
	 */
	private function testInsertAfter2()
	{
		$editArrayClone = unserialize( serialize( $this->editArray ) ); // deep clone
		WW_Utils_KeyValueArray::insertAfterKey( $editArrayClone, 'noot', $this->insertArray );
		$expectedArray = array(
			'aap' => 1,
			'noot' => 10,
			'vuur' => 3,
			'boom' => 2,
			'mies' => 4,
		);
		$this->assertIdenticalArrays( $expectedArray, $editArrayClone );
	}

	/**
	 * Validate insertion of 'boom' and 'roos' after 3rd item 'mies'.
	 */
	private function testInsertAfter3()
	{
		$editArrayClone = unserialize( serialize( $this->editArray ) ); // deep clone
		WW_Utils_KeyValueArray::insertAfterKey( $editArrayClone, 'mies', $this->insertArray );
		$expectedArray = array(
			'aap' => 1,
			'noot' => 10,
			'mies' => 4,
			'vuur' => 3,
			'boom' => 2,
		);
		$this->assertIdenticalArrays( $expectedArray, $editArrayClone );
	}

	/**
	 * Check if two arrays are EXACTLY the same, including keys, values and position of the items in the array.
	 *
	 * Note that WW_Utils_PhpCompare::compareTwoArrays() does not take the position of the items into account
	 * and so this function does not report errors as long as all key-values are present and the same.
	 *
	 * @param mixed $expected
	 * @param mixed $actual
	 * @throws BizException when given values are not arrays or when arrays are not identical.
	 */
	private function assertIdenticalArrays( $expected, $actual )
	{
		if( !is_array( $expected ) ) {
			$this->throwError( 'The expected value is not an array.' );
		}
		if( !is_array( $actual ) ) {
			$this->throwError( 'The actual value is not an array.' );
		}
		if( serialize( $expected ) !== serialize( $actual ) ) {
			$this->throwError( 'Arrays are not the same. Expected: '.print_r( $expected, true ).
				' Actual: '.print_r( $actual, true ) );
		}
	}
}