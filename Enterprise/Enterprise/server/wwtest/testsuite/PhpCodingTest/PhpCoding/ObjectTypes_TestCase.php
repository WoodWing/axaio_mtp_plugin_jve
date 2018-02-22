<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Checks if the BizObjectTypes class and the object types defined in the SCEnterprise.wsdl are tally.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_PhpCodingTest_PhpCoding_ObjectTypes_TestCase extends TestCase
{
	public function getDisplayName() { return 'Object types'; }
	public function getTestGoals()   { return 'Avoid the mistake adding an object type to the WSDL but not to the BizObjectTypes class.'; }
	public function getTestMethods() { return 'Reads the types defined in SCEnterprise.wsdl file and BizObjectTypes class and validates if the collections are equal.'; }
	public function getPrio()        { return 75; }

	/** @var DOMXPath */
	private $xPath;

	/** @var string[]  */
	private $wsdlObjectTypes;

	/** @var string[]  */
	private $bizObjectTypes;

	/**
	 * @inheritdoc
	 */
	public function runTest()
	{
		$this->readObjectTypesFromWsdl();
		$this->readObjectTypesFromBizObjectTypes();
		$this->validateObjectTypes();
	}

	/**
	 * Parse the SCEnterprise.wsdl file, read the defined object types and populate $this->wsdlObjectTypes.
	 */
	private function readObjectTypesFromWsdl()
	{
		$wsdlDoc = new DOMDocument();
		$wsdlContents = file_get_contents( BASEDIR.'/server/interfaces/SCEnterprise.wsdl' );
		$this->assertTrue( $wsdlDoc->loadXML( $wsdlContents ) );

		$this->xPath = new DOMXPath( $wsdlDoc );
		$this->xPath->registerNameSpace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
		$this->xPath->registerNameSpace('schm', 'http://www.w3.org/2001/XMLSchema');
		$this->xPath->registerNameSpace('dime', 'http://schemas.xmlsoap.org/ws/2002/04/dime/wsdl/');

		$entries = $this->xPath->query( '/wsdl:definitions/wsdl:types/schm:schema/schm:simpleType[@name="ObjectType"]/schm:restriction/schm:enumeration' );
		$this->assertGreaterThan( 0, $entries->length );

		$this->wsdlObjectTypes = array();
		foreach( $entries as $entry ) {
			$objectType = $entry->getAttribute( 'value' );
			if( !empty( $objectType ) ) { // skip the empty entry
				$this->wsdlObjectTypes[] = $objectType;
			}
		}
	}

	/**
	 * Retrieve the object types from BizObjectType and populate $this->bizObjectTypes.
	 */
	private function readObjectTypesFromBizObjectTypes()
	{
		require_once BASEDIR.'/server/bizclasses/BizObjectType.class.php';
		$this->bizObjectTypes = BizObjectType::getObjectTypes();
	}

	/**
	 * Error when the object types read from SCEnterprise.wsdl differ from the ones read from BizObjectType.
	 */
	private function validateObjectTypes()
	{
		$diff = array_diff( $this->wsdlObjectTypes, $this->bizObjectTypes );
		$this->assertCount( 0, $diff,
			'BizObjectType::getObjectTypes() is not tally with the ObjectType definition in SCEnterprise.wsdl. '.
			'The following types are different: '.implode(', ', $diff ) );
	}
}