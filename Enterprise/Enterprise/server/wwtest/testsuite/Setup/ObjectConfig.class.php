<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.4.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * This class makes it easy to compose workflow objects with a brand, category, status and targets.
 * Aside to that, all supported object properties can be specified that are part of the MetaData.
 * Al it requires is a home brewed data structure (to be provided) to specify the workflow objects.
 * Functions are available to retrieve the composed workflow objects.
 */

require_once BASEDIR.'/server/wwtest/testsuite/Setup/AbstractConfig.class.php';

class WW_TestSuite_Setup_ObjectConfig extends WW_TestSuite_Setup_AbstractConfig
{
	/** @var WW_TestSuite_Setup_PublicationConfig */
	private $publicationConfig;
	/** @var Object[] */
	private $objects;

	/**
	 * @param WW_TestSuite_Setup_PublicationConfig $publicationConfig
	 */
	public function setPublicationConfig( WW_TestSuite_Setup_PublicationConfig $publicationConfig )
	{
		$this->publicationConfig = $publicationConfig;
	}

	/**
	 * @inheritdoc
	 */
	public function setupTestData()
	{
		if( isset( $this->config->Objects ) ) {
			foreach( $this->config->Objects as $objectConfig ) {
				$object = new Object();
				$object->MetaData = new MetaData();
				$object->MetaData->BasicMetaData = new BasicMetaData();
				$object->MetaData->BasicMetaData->Publication = new Publication();
				$object->MetaData->BasicMetaData->Publication->Id = $this->publicationConfig->getPublicationId( $objectConfig->Publication );
				$object->MetaData->BasicMetaData->Publication->Name = $this->replaceTimeStampPlaceholder( $objectConfig->Publication );
				$this->testCase->assertGreaterThan( 0, $object->MetaData->BasicMetaData->Publication->Id,
					"While composing Object '{$objectConfig->Name}' from the JSON config setup, the Publication ".
					"'{$objectConfig->Publication}' could not be found under Publications." );
				$object->MetaData->BasicMetaData->Category = new Category();
				$object->MetaData->BasicMetaData->Category->Id = $this->publicationConfig->getCategoryId( $objectConfig->Publication, $objectConfig->Category );
				$object->MetaData->BasicMetaData->Category->Name = $this->replaceTimeStampPlaceholder( $objectConfig->Category );
				$this->testCase->assertGreaterThan( 0, $object->MetaData->BasicMetaData->Category->Id,
					"While composing Object '{$objectConfig->Name}' from the JSON config setup, the Category ".
					"'{$objectConfig->Category}' could not be found under Publication '{$objectConfig->Publication}'." );
				$object->MetaData->RightsMetaData = new RightsMetaData();
				$object->MetaData->SourceMetaData = new SourceMetaData();
				$object->MetaData->ContentMetaData = new ContentMetaData();
				$object->MetaData->WorkflowMetaData = new WorkflowMetaData();
				$object->MetaData->WorkflowMetaData->State = new State();
				$object->MetaData->WorkflowMetaData->State->Id = $this->publicationConfig->getStatusId( $objectConfig->Publication, $objectConfig->State );
				$object->MetaData->WorkflowMetaData->State->Name = $this->replaceTimeStampPlaceholder( $objectConfig->State );
				$this->testCase->assertGreaterThan( 0, $object->MetaData->WorkflowMetaData->State->Id,
					"While composing Object '{$objectConfig->Name}' from the JSON config setup, the State ".
					"'{$objectConfig->State}' could not be found under Publication '{$objectConfig->Publication}'." );
				$object->MetaData->ExtraMetaData = array();
				$object->Targets = array();
				if( isset( $objectConfig->Targets ) ) {
					foreach( $objectConfig->Targets as $targetConfig ) {
						$target = new Target();
						$target->PubChannel = new PubChannel();
						$target->PubChannel->Id = $this->publicationConfig->getPubChannelId( $objectConfig->Publication, $targetConfig->PubChannel );
						$this->testCase->assertGreaterThan( 0, $target->PubChannel->Id,
							"While composing Object '{$objectConfig->Name}' from the JSON config setup, the PubChannel ".
							"'{$targetConfig->PubChannel}' could not be found under Publication '{$objectConfig->Publication}'." );
						$target->PubChannel->Name = $this->replaceTimeStampPlaceholder( $targetConfig->PubChannel );
						$target->Issue = new Issue();
						$target->Issue->Id = $this->publicationConfig->getIssueId( $objectConfig->Publication, $targetConfig->PubChannel, $targetConfig->Issue );
						$target->Issue->Name = $this->replaceTimeStampPlaceholder( $targetConfig->Issue );
						$this->testCase->assertGreaterThan( 0, $target->Issue->Id,
							"While composing Object '{$objectConfig->Name}' from the JSON config setup, the Issue '{$targetConfig->Issue}' ".
							"could not be found under Publication '{$objectConfig->Publication}' and PubChannel '{$targetConfig->PubChannel}'." );
						$target->Editions = array();
						if( isset( $targetConfig->Editions ) ) {
							foreach( $targetConfig->Editions as $editionConfigName ) {
								$edition = new Edition();
								$edition->Id = $this->publicationConfig->getEditionId( $objectConfig->Publication, $targetConfig->PubChannel, $editionConfigName );
								$edition->Name = $this->replaceTimeStampPlaceholder( $editionConfigName );
								$this->testCase->assertGreaterThan( 0, $edition->Id,
									"While composing Object '{$objectConfig->Name}' from the JSON config setup, the Edition '{$editionConfigName}' ".
									"could not be found under Publication '{$objectConfig->Publication}' and PubChannel '{$targetConfig->PubChannel}'." );
								$target->Editions[] = $edition;
							}
						}
						$object->Targets[] = $target;
					}
				}

				// Clear the props handled above to avoid side effects in updateMetaDataTreeWithFlat().
				unset( $objectConfig->Publication );
				unset( $objectConfig->Category );
				unset( $objectConfig->State );
				unset( $objectConfig->Targets );

				// Copy the configured values to the MetaData tree.
				require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
				$flatMD = new stdClass();
				$flatMD->MetaDataValue = $this->configPropertiesToMetaDataValues( $objectConfig );
				BizProperty::updateMetaDataTreeWithFlat( $object->MetaData, $flatMD );

				// Cache the composed objects.
				$this->objects[ $objectConfig->Name ] = $object;
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function teardownTestData()
	{
	}

	/**
	 * @param string $objectConfigName
	 * @return Object
	 */
	public function getComposedObject( $objectConfigName )
	{
		$object = $this->objects[ $objectConfigName ];
		$this->testCase->assertInstanceOf( 'Object', $object,
			"The Object '{$objectConfigName}' could not be found in the JSON config setup under Objects." );
		return $object;
	}
}