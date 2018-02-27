<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v10.1.6
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Admin_MetaDataHandling_TestCase extends TestCase
{
	/** @var array $vars */
	private $vars = null;

	/** @var WW_Utils_TestSuite $utils  */
	private $utils = null;

	/** @var string $dateTime  */
	private $dateTime = null;

	/** @var string $ticket */
	private $ticket = null;

	/** @var int $brandId1 */
	private $brandId1 = null;

	/** @var int $brandId2 */
	private $brandId2 = null;

	/** @var array $metadata0 */
	private $metadata0 = array();

	/** @var array $metadata1 */
	private $metadata1 = array();

	/** @var array $metadata3 */
	private $metadata3 = array();

	/** @var array $metadata2 */
	private $metadata2 = array();


	public function getDisplayName() { return 'MetaData properties'; }
	public function getTestGoals()   { return 'Checks if MetaData properties are properly cleaned up.'; }
	public function getTestMethods()
	{
		return 'Scenario:<ol>
			<li>001: Define a custom property MD0 under all Publications in MetaData page.</li>
			<li>002: Setup MD0 under all Publications in Dialog Setup page.</li>
			<li>003: Setup MD0 under Publication A in Dialog Setup page.</li>
			<li>004: Define a custom property MD2 under Publication B in MetaData page.</li>
			<li>005: Setup MD2 under Publication A in Dialog Setup page.</li>
			<li>006: Delete MD0 in MetaData page and it is checked if it is also removed from the relevant publications in Dialog Setup.</li>
			<li>007: Delete MD1 in MetaData page and it is checked if it is also removed from the relevant publications in Dialog Setup.</li>
			</ol>';
	}
	public function getPrio()        { return 100; }
	public function isSelfCleaning() { return true; }

	final public function runTest()
	{
		do {
			if( !$this->setupTest() ) {
				break;
			}

			// MetaData setup
			$this->composeMetadata0();
			if( !$this->createMetaData( $this->metadata0 )) {
				break;
			}

			$this->composeMetadata3();
			if( !$this->createMetaData( $this->metadata3 )) {
				break;
			}

			$this->composeMetadata2();
			if( !$this->createMetaData( $this->metadata2 )) {
				break;
			}

			// Dialog Setup
			if( !$this->setupDialog( $this->metadata0['pubId'], $this->metadata0['name'] )) { // MD0 For All publication
				break;
			}

			$this->copyMd0ToMd1(); // MD1 = to be used for specific Publication
			if( !$this->setupDialog( $this->metadata1['pubId'], $this->metadata1['name'] )) { // MD1 For Publication A
				break;
			}

			if( !$this->setupDialog( $this->metadata2['pubId'], $this->metadata2['name'] )) { // MD2 For Publication B
				break;
			}

			if( !$this->setupDialog( $this->metadata3['pubId'], $this->metadata3['name'] )) { // MD3 For Publication B
				break;
			}

			// MetaData deletion
			if( $this->deletePropertyAssignedToAllPublication()) {
				$this->metadata0 = array();
				$this->metadata1 = array();
			} else {
				break;
			}

			if( $this->deletePropertyAssignedToOnePublication()) {
				$this->metadata3 = array();
			} else {
				break;
			}

		} while( false );

		$this->tearDownTest();
	}

	private function setupTest()
	{
		$retVal = true;
		do {
			$this->dateTime = date("mdHis");

			$this->vars = $this->getSessionVariables();
			$this->ticket = @$this->vars['BuildTest_Admin']['ticket'];

			require_once BASEDIR.'/server/utils/TestSuite.php';
			$this->utils = new WW_Utils_TestSuite();

			if ( !$this->setupBrands()) {
				$retVal = false;
				break;
			}
		} while ( false );

		return $retVal;
	}

	private function setupBrands()
	{
		$this->brandId1 = @$this->vars['BuildTest_Admin']['Brand']->Id;

		try {
			$this->brandId2 = $this->utils->copyPublication( $this->brandId1, 'MetaDataTest'.$this->dateTime, false, '' );
			$retVal = true;
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',  'Failed to copy Brand from Brand ( id = '. $this->brandId1.' ). '. $e->getMessage() );
			$retVal = false;
		}
		return $retVal;
	}

	private function composeMetadata0()
	{
		$this->metadata0['name'] = 'C_MD1'. $this->dateTime;
		$this->metadata0['displayName'] = 'MD1'. $this->dateTime;
		$this->metadata0['type'] = 'string';
		$this->metadata0['pubId'] = '0'; // all publications
	}

	private function copyMd0ToMd1()
	{
		$this->metadata1['id'] = $this->metadata0['id'];
		$this->metadata1['name'] = $this->metadata0['name'];
		$this->metadata1['displayName'] = $this->metadata0['displayName'];
		$this->metadata1['type'] = $this->metadata0['type'];
		$this->metadata1['pubId'] = $this->brandId1; // Specific Publication
	}

	private function composeMetadata2()
	{
		$this->metadata2['name'] = $this->metadata0['name']; // same name as MD0
		$this->metadata2['displayName'] = $this->metadata0['displayName']; // same display name as MD0
		$this->metadata2['type'] = 'string';
		$this->metadata2['pubId'] = $this->brandId2;  // Specific Publication
	}

	private function composeMetadata3()
	{
		$this->metadata3['name'] = 'C_MD3'. $this->dateTime;
		$this->metadata3['displayName'] = 'MD3'. $this->dateTime;
		$this->metadata3['type'] = 'string';
		$this->metadata3['pubId'] = $this->brandId2;  // Specific Publication
	}

	private function createMetaData( &$metadataInfo )
	{
		$retVal = true;
		try {
			require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
			require_once BASEDIR . '/server/bizclasses/BizCustomField.class.php';
			require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';

			$values = array(
				'publication' => $metadataInfo['pubId'],
				'objtype' => '',
				'name' => $metadataInfo['name'],
				'dispname' => $metadataInfo['displayName'],
				'category' => '',
				'type' => $metadataInfo['type'],
				'defaultvalue' => '',
				'valuelist' => '',
				'minvalue' => '',
				'maxvalue' => '',
				'maxlen' => 0,
				'dbupdated' => 0
			);
			$metadataInfo['id'] = BizProperty::addProperty( $values );

			$foundDbType = BizProperty::getCustomPropType( $metadataInfo['id'], $metadataInfo['type'], $metadataInfo['name'] );
			if( !$foundDbType ) {
				BizCustomField::insertFieldAtModel( 'objects', $metadataInfo['name'], $metadataInfo['type'] );
			}
			DBProperty::updateRow( 'properties', array('dbupdated' => 1), '`id` = ?', array( $metadataInfo['id'] ));

		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',  'Failed to create metadata ( name = '. $metadataInfo['name'].' ). '. $e->getMessage() );
			$retVal = false;
		}
		return $retVal;
	}

	private function setupDialog( $pubId, $propName )
	{
		$retVal = true;
		try{
			require_once BASEDIR .'/server/dbclasses/DBActionproperty.class.php';
			$values = array(
				'publication' => $pubId,
				'action' => '',
				'type' => '',
				'orderid' => 0,
				'property' => $propName,
				'edit' => 'on',
				'mandatory' => '',
				'restricted' => '',
				// 'refreshonchange' => $refreshonchange, // EN-2164, Marked for future use
				'multipleobjects' => ''
			);
			DBActionproperty::insertActionproperty( $values );

		} catch ( BizException $e ) {
			$this->setResult( 'ERROR',  'Failed to setup Dialog for metadata ( name = '. $propName.' ). '. $e->getMessage() );
			$retVal = false;
		}
		return $retVal;
	}

	private function deletePropertyAssignedToAllPublication()
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		BizProperty::checkAndRemoveRelatedDataBeforeDeleteProperty( $this->metadata0['id'], $this->metadata0['type'], $this->metadata0['name'], $this->metadata0['pubId'] );

		// metadata0
		if( !self::validateDoesActionPropertyExistsAfterDeletion( $this->metadata0['id'], $this->metadata0['name'], $this->metadata0['pubId'], false )) { // All Pub
			return false;
		}
		if( !self::validateDoesPropertyExistAfterDeletion( $this->metadata0['id'], $this->metadata0['name'], $this->metadata0['pubId'], false )) { // All Pub
			return false;
		}

		//metadata1
		if( !self::validateDoesActionPropertyExistsAfterDeletion( $this->metadata1['id'], $this->metadata1['name'], $this->metadata1['pubId'], false )) { // Specific Pub
			return false;
		}
		if( !self::validateDoesPropertyExistAfterDeletion( $this->metadata1['id'], $this->metadata1['name'], $this->metadata1['pubId'], false )) { // Specific Pub
			return false;
		}

		// metadata2
		if( !self::validateDoesActionPropertyExistsAfterDeletion( $this->metadata2['id'], $this->metadata2['name'], $this->metadata2['pubId'], true )) { // Specific Pub
			return false;
		}
		if( !self::validateDoesPropertyExistAfterDeletion( $this->metadata2['id'], $this->metadata2['name'], $this->metadata2['pubId'], true )) { // Specific Pub
			return false;
		}

		return true;
	}

	private function deletePropertyAssignedToOnePublication()
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		BizProperty::checkAndRemoveRelatedDataBeforeDeleteProperty( $this->metadata3['id'], $this->metadata3['type'], $this->metadata3['name'], $this->metadata3['pubId'] );

		// metadata3
		if( !self::validateDoesActionPropertyExistsAfterDeletion( $this->metadata3['id'], $this->metadata3['name'], $this->metadata3['pubId'], false )) { // Specific Pub.
			return false;
		}
		if( !self::validateDoesPropertyExistAfterDeletion( $this->metadata3['id'], $this->metadata3['name'], $this->metadata3['pubId'], false )) { // Specific Pub.
			return false;
		}

		return true;
	}

	private function validateDoesActionPropertyExistsAfterDeletion( $id, $name, $publ, $shouldExists )
	{
		$dbh = DBDriverFactory::gen();
		$tableName = $dbh->tablename( 'actionproperties' );
		$sql = "SELECT `id` FROM $tableName WHERE `publication` = ?  AND `property` = ? ";
		$params = array( intval( $publ ), strval( $name ));
		$sth = $dbh->query( $sql, $params );
		if( $sth ) {
			if( $dbh->fetch( $sth ) ) {
				if( !$shouldExists ) {
					$this->setResult( 'ERROR',  "Property '{$name}' (id='{$id}') should be deleted and should ".
						"not exist in smart_actionproperties table." );
					return false; // record found while it shouldn't.
				}
			} else {
				if( $shouldExists ) {
					$this->setResult( 'ERROR',  "Property '{$name}' (id='{$id}') should not be deleted and should ".
						"exist in smart_actionproperties table." );
					return false; // record not found while it should.
				}
			}
		}
		return true;
	}

	private function validateDoesPropertyExistAfterDeletion( $id, $name, $publ, $shouldExists )
	{
		$dbh = DBDriverFactory::gen();
		$tableName = $dbh->tablename( 'properties' );
		$sql = "SELECT `name` FROM $tableName WHERE `id` = ? ";
		$params = array( $id );
		$sth = $dbh->query( $sql, $params );

		if( $sth ) {
			if( $dbh->fetch( $sth ) ) {
				if( !$shouldExists ) {
					$this->setResult( 'ERROR',  "Property '{$name}' (id='{$id}') should be deleted and should ".
						"not exist in smart_properties table." );
					return false; // record found while it shouldn't.
				}
			} else {
				if( $shouldExists ) {
					$this->setResult( 'ERROR',  "Property '{$name}' (id='{$id}') should not be deleted and should ".
						"exist in smart_properties table." );
					return false; // record not found while it should.
				}
			}
		}
		return true;
	}

	private function tearDownTest()
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR . '/server/bizclasses/BizCustomField.class.php';
		require_once BASEDIR .'/server/dbclasses/DBActionproperty.class.php';

		if( $this->metadata0 ) {
			DBActionproperty::deletePropFromActionProperties( $this->metadata0['name'], null );
			$foundDbType = BizProperty::getCustomPropType( $this->metadata0['id'], $this->metadata0['type'], $this->metadata0['name'] );
			if( !$foundDbType ) {
				BizCustomField::deleteFieldAtModel( 'objects', $this->metadata0['name'] );
			}
			BizProperty::deleteProperty( $this->metadata0['id'] );
			$this->metadata0 = array();
		}

		if( $this->metadata1 ) {
			DBActionproperty::deletePropFromActionProperties( $this->metadata1['name'], null );
			$this->metadata1 = array();
		}

		if( $this->metadata2 ) {
			DBActionproperty::deletePropFromActionProperties( $this->metadata2['name'], null );
			$foundDbType = BizProperty::getCustomPropType( $this->metadata2['id'], $this->metadata2['type'], $this->metadata2['name'] );
			if( !$foundDbType ) {
				BizCustomField::deleteFieldAtModel( 'objects', $this->metadata2['name'] );
			}
			BizProperty::deleteProperty( $this->metadata2['id'] );
			$this->metadata2 = array();
		}

		if( $this->metadata3 ) {
			DBActionproperty::deletePropFromActionProperties( $this->metadata3['name'], null );
			$foundDbType = BizProperty::getCustomPropType( $this->metadata3['id'], $this->metadata3['type'], $this->metadata3['name'] );
			if( !$foundDbType ) {
				BizCustomField::deleteFieldAtModel( 'objects', $this->metadata3['name'] );
			}
			BizProperty::deleteProperty( $this->metadata3['id'] );
			$this->metadata3 = array();
		}

		if( $this->brandId2 ) {
			try{
				$this->utils->deletePublication( $this, $this->ticket, $this->brandId2 );
				$this->brandId2 = null;
			} catch ( BizException $e ) {
				$this->setResult( 'ERROR',  'Failed to delete Brand ( id = '. $this->brandId2.' ). '. $e->getMessage() );
			}
		}


	}
}

