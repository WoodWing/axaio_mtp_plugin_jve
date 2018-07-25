<?php
/**
 * @since      10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_PhpUnitTests_BizObjectType_TestCase extends TestCase
{
	public function getDisplayName() { return 'BizObjectType class'; }
	public function getTestGoals()   { return 'Checks if the functions of the BizObjectType class behave correctly.'; }
	public function getPrio()        { return 200; }
	public function getTestMethods() { return 'Call the functions of the BizObjectType class and and validate the expected return values.';	}

	/**
	 * @inheritdoc
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectType.class.php';

		$this->assertTrue( BizObjectType::isObjectTypeAnyOf( 'Dossier', BizObjectType::CONTAINER ) );
		$this->assertFalse( BizObjectType::isObjectTypeAnyOf( 'Article', BizObjectType::CONTAINER ) );

		$this->assertTrue( BizObjectType::isObjectTypeAnyOf( 'LayoutModuleTemplate', BizObjectType::TEMPLATE ) );
		$this->assertFalse( BizObjectType::isObjectTypeAnyOf( 'Image', BizObjectType::TEMPLATE ) );

		$this->assertTrue( BizObjectType::isObjectTypeAnyOf( 'Task', BizObjectType::TEMPLATE | BizObjectType::CONTAINER ) );
		$this->assertFalse( BizObjectType::isObjectTypeAnyOf( 'Task', BizObjectType::TEMPLATE | BizObjectType::OBSOLETED ) );
	}
}