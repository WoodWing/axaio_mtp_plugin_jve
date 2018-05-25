<?php
/**
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_ObjectTargets_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils
	
	// Objects to work on:
	private $layout = null;
	private $layoutLocked = false;
	private $image = null;
	private $dossier = null;

	public function getDisplayName()
	{
		return 'Object Targets - Drupal Channel';
	}

	public function getTestGoals()
	{
		return 'Checks if Object and Relation Targets are be round-tripped correctly.';
	}

	public function getTestMethods()
	{
		return
			'Test with objects assigned to a print channel: <ul>'.
				'<li>01 Creates a Dossier, a Layout and an Image object (CreateObjects).</li>'.
				'<li>02 Place the Image onto the Layout (CreateObjectRelations).</li>'.
				'<li>03 Move the Image and the Layout into the Dossier (CreateObjectRelations).</li>'.
				'<li>04 Validate the Object Targets for Layout and Image to ensure that the Targets were round-tripped (GetObjects).</li>'.
			'</ul>';
	}

	public function getPrio()
	{
		return 55;
	}

	/**
	 * Runs the testcases for this TestSuite.
	 */
	final public function runTest()
	{
		// Use the publishing Utils.
		require_once BASEDIR.'/server/wwtest/testsuite/BuildTest/MultiChannelPublishing/MultiChannelPublishingUtils.class.php';
		$this->mcpUtils = new MultiChannelPublishingUtils();
		if( !$this->mcpUtils->initTest( $this ) ) {
			return;
		}

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		// Retrieve the Ticket that has been determined by WflLogOn TestCase.
		$this->vars = $this->getSessionVariables();
		$this->ticket = $this->vars['BuildTest_MultiChannelPublishing']['ticket'];
		
		do {
			// Create a Dossier, Image and Layout object in database for testing.
			if( !$this->setupTestData() ) {
				break;
			}
			// Perform several actions to ensure that the Object Targets are round-tripped.
			if( !$this->placeImageOntoLayout() ) {
				break;
			}
			if( !$this->updateAndCheckInLayout() ) {
				break;
			}
			$relationsForChecking = array();
			if( !$this->moveImageAndLayoutIntoDossier( $relationsForChecking ) ) {
				break;
			}
			if( !$this->validateObjectTargets( $relationsForChecking ) ) {
				break;
			}
		} while( false );
		
		// Remove the test objects from DB.
		$this->tearDownTestData();
	}

	/**
	 * Create a Dossier, Image and Layout object in database for testing.
	 *
	 * @return bool Whether or not all objects could be created.
	 */
	private function setupTestData()
	{
		$retVal = true;
		
		// Compose postfix for object name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		// Create the Dossier.
		$stepInfo = 'Create the Dossier object for Print.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo, 'DossierForPrint '.$postfix, 'print' );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR',  'Could not create the Dossier.' );
			$retVal = false;
		}

		// Create the Layout.
		$stepInfo = 'Create the Layout object.';
		$this->layout = $this->mcpUtils->createLayout( $stepInfo, 'LayoutForPrint '.$postfix, true ); // true = to lock the layout.
		if( is_null($this->layout) ) {
			$this->setResult( 'ERROR',  'Could not create the Layout.' );
			$retVal = false;
			$this->layoutLocked = false;
		} else {
			$this->layoutLocked = true;
		}

		// Create the Image.
		$stepInfo = 'Create the Image object for Print.';
		$this->image = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo, 'ImageForPrint '.$postfix );
		if( is_null( $this->image ) ) {
			$this->setResult( 'ERROR',  'Could not create the Image.' );
			$retVal = false;
		}
		
		return $retVal;
	}

	/**
	 * Tear down the Test environment setup in {@link: setupTestData()}.
	 */
	private function tearDownTestData()
	{
		// Permanent delete the Image.
		if( $this->image ) {
			$id = $this->image->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
			}
			$this->image = null;
		}

		// Permanent delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
			}
			$this->dossier = null;
		}
		
		// Unlock the Layout, only when we still got the lock.
		if( $this->layout && $this->layoutLocked ) {
			$stepInfo = 'Unlock the Layout object (in preparation for deletion).';
			$this->unlockObject( $this->layout, $stepInfo );
			$this->layoutLocked = false;
		}
		
		// Permanent delete the Layout.
		if( $this->layout ) {
			$id = $this->layout->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Layout object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Layout object: '.$errorReport );
			}
			$this->layout = null;
		}
	}

	/**
	 * To release the lock of an object.
	 *
	 * @param Object $objectToUnlock
	 * @param string $stepInfo
	 */
	private function unlockObject( $objectToUnlock, $stepInfo )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array( $objectToUnlock->MetaData->BasicMetaData->ID );
		/*$response =*/ $this->utils->callService( $this, $request, $stepInfo );
	}

	/**
	 * To place an Image onto Layout.
	 *
	 * The function places an Image onto Layout by calling CreateObjectRelations service call.
	 *
	 * @return bool True when the Image has been successfully placed on the Layout; False otherwise.
	 */
	private function placeImageOntoLayout()
	{
		$parent = $this->layout->MetaData->BasicMetaData->ID;
		$child = $this->image->MetaData->BasicMetaData->ID;
		
		// Create the Relation Object.
		$relation = new Relation();
		$relation->Parent = $parent;
		$relation->Child = $child;
		$relation->Type = 'Placed';
		$placement = new Placement();
		$placement->Page = 1;
		$placement->Element = 'graphic';
		$placement->ElementID = '';
		$placement->FrameOrder = 0;
		$placement->FrameID = '218';
		$placement->Left = 73;
		$placement->Top = 164;
		$placement->Width = 406;
		$placement->Height = 357;
		$placement->Overset = null;
		$placement->OversetChars = null;
		$placement->OversetLines = null;
		$placement->Layer = 'Layer 1';
		$placement->Content = '';
		$placement->Edition = null;
		$placement->ContentDx = 0;
		$placement->ContentDy = 0;
		$placement->ScaleX = null;
		$placement->ScaleY = null;
		$placement->PageSequence = 1;
		$placement->PageNumber = '1';
		$placement->Tiles = array();
		$placement->FormWidgetId = null;
		$relation->Placements = array( $placement );
		$relation->ParentVersion = null;
		$relation->ChildVersion = null;
		$relation->Rating = null;
		$relation->Targets = null;
		$relation->ParentInfo = null;
		$relation->ChildInfo = null;

		// Call the CreateObjectRelations to place the image onto the layout.
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array( $relation );

		$stepInfo = 'Place the Image object onto the Layout.';
		$response = $this->utils->callService( $this, $request, $stepInfo );

		if( !$response ) {
			$this->setResult( 'ERROR', 'Failed placing Image onto Layout. '.
					'Test for Object Targets cannot be continued.',
					'Please check in the CreateObjectRelations service call where '.
					'Parent=' . $parent . ', Child=' . $child . ' Type=Placed' );
		}
		return (bool)$response;
	}

	/**
	 * Update the Layout and CheckIn the Layout.
	 *
	 * The function updates the Relation between the Layout and the Image
	 * and calls the SaveObjects service call to CheckIn the Layout.
	 *
	 * @return bool True when the CheckIn is successful; False otherwise.
	 */
	private function updateAndCheckInLayout()
	{
		// Construct the layout Relation with its image.
		$relation = new Relation();
		$relation->Parent = $this->layout->MetaData->BasicMetaData->ID;
		$relation->Child = $this->image->MetaData->BasicMetaData->ID;
		$relation->Type = 'Placed';

		$placement = new Placement();
		$placement->Page = 1;
		$placement->Element = 'graphic';
		$placement->ElementID = '';
		$placement->FrameOrder = 0;
		$placement->FrameID = '218';
		$placement->Left = 73;
		$placement->Top = 164;
		$placement->Width = 406;
		$placement->Height = 357;
		$placement->Overset = null;
		$placement->OversetChars = null;
		$placement->OversetLines = null;
		$placement->Layer = 'Layer 1';
		$placement->Content = '';
		$placement->Edition = null;
		$placement->ContentDx = 0;
		$placement->ContentDy = 0;
		$placement->ScaleX = null;
		$placement->ScaleY = null;
		$placement->PageSequence = 1;
		$placement->PageNumber = '1';
		$placement->Tiles = array();
		$placement->FormWidgetId = null;
		$relation->Placements = array( $placement );

		$relation->ParentVersion = null;
		$relation->ChildVersion = null;
		$relation->Rating = null;
		$relation->Targets = null;
		$relation->ParentInfo = null;
		$relation->ChildInfo = null;

		$this->layout->Relations = array( $relation );

		// Construct the layout attachments on pages.
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$pageFiles = array();
		$file = new Attachment();
		$file->Rendition = 'thumb';
		$file->Type = 'image/jpeg';
		$file->Content = null;
		$file->FilePath = '';
		$file->FileUrl = null;
		$file->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#012_att#000_thumb.jpg';

		$transferServer->copyToFileTransferServer( $inputPath, $file );
		$pageFiles[] = $file;

		$file = new Attachment();
		$file->Rendition = 'preview';
		$file->Type = 'image/jpeg';
		$file->Content = null;
		$file->FilePath = '';
		$file->FileUrl = null;
		$file->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#012_att#001_preview.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $file );
		$pageFiles[] = $file;

		$this->layout->Pages[0]->Files = $pageFiles;

		// Construct the attachments.
		$files = array();
		$file = new Attachment();
		$file->Rendition = 'native';
		$file->Type = 'application/indesign';
		$file->Content = null;
		$file->FilePath = '';
		$file->FileUrl = null;
		$file->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#012_att#002_native.indd';
		$transferServer->copyToFileTransferServer( $inputPath, $file );
		$files[] = $file;

		$file = new Attachment();
		$file->Rendition = 'thumb';
		$file->Type = 'image/jpeg';
		$file->Content = null;
		$file->FilePath = '';
		$file->FileUrl = null;
		$file->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#012_att#003_thumb.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $file );
		$files[] = $file;

		$file = new Attachment();
		$file->Rendition = 'preview';
		$file->Type = 'image/jpeg';
		$file->Content = null;
		$file->FilePath = '';
		$file->FileUrl = null;
		$file->EditionId = '';
		$inputPath = dirname(__FILE__).'/../testdata/rec#012_att#004_preview.jpg';
		$transferServer->copyToFileTransferServer( $inputPath, $file );
		$files[] = $file;

		$this->layout->Files = $files;

		// Call the SaveObjects service to CheckIn the Layout.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array( $this->layout );
		$request->ReadMessageIDs = null;
		$request->Messages = null;

		$stepInfo = 'Update and checkin the Layout.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !$response ) {
			$this->setResult( 'ERROR', 'Failed to CheckIn the layout. Test cannot be continued.',
				'Please check in the SaveObjects service call.' );
			return false;
		}
		$this->layoutLocked = false;
		
		return true;
	}

	/**
	 * Moves the Image and Layout into Dossier.
	 *
	 * It moves the Image and Layout into Dossier by calling CreateObjectRelations service call.
	 * Relation is remembered in $relationsForChecking for Image and Layout respectively for
	 * validation later on.
	 *
	 * @param array &$relationsForChecking
	 * @return bool True when the Image and Layout are successfully moved into Dossier; False otherwise.
	 */
	private function moveImageAndLayoutIntoDossier( &$relationsForChecking )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		
		// Move the Layout into the Dossier.
		$parentId = $this->dossier->MetaData->BasicMetaData->ID;
		$childId = $this->layout->MetaData->BasicMetaData->ID;
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $parentId;
		$request->Relations[0]->Child = $childId;
		$request->Relations[0]->Type = 'Contained';
		$request->Relations[0]->Placements = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		
		$stepInfo = 'Move the Layout into into the Dossier.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !isset($response->Relations[0]) ) {
			$this->setResult( 'ERROR', 'Cannot move Layout into Dossier. Test cannot be continued.',
				'Please check in the CreateObjectRelations service call for Dossier (id='.$parentId. ') '.
				'and Layout (id='.$childId.').' );
			return false;
		}
		$relationsForChecking['Layout'] = $response->Relations[0];

		// Move the Image into the Dossier.
		$parentId = $this->dossier->MetaData->BasicMetaData->ID;
		$childId = $this->image->MetaData->BasicMetaData->ID;
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $parentId;
		$request->Relations[0]->Child = $childId;
		$request->Relations[0]->Type = 'Contained';
		$request->Relations[0]->Placements = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		
		$stepInfo = 'Move the Image into into the Dossier.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		if( !isset($response->Relations[0]) ) {
			$this->setResult( 'ERROR', 'Cannot move Image into Dossier.Test for Object Targets cannot be continued.',
				'Please check in the CreateObjectRelations service call for Dossier(dossier id='.$parentId. ') '.
					'and Image(image id='.$childId.').' );
			return false;
		}
		$relationsForChecking['Image'] = $response->Relations[0];
		
		return true;
	}

	/**
	 * Call GetObjects service call and indicate if the Object should be locked or not.
	 *
	 * @param array $objIds
	 * @param bool $lock Whether the lock the Object.
	 * @param string $stepInfo Extra logging info.
	 * @return GetObjectsResponse
	 */
	private function callGetObjects( $objIds, $lock, $stepInfo )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $objIds;
		$request->Lock = $lock;
		$request->Rendition = 'none';
		$request->RequestInfo = array( 'Relations', 'Targets' );
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return $response;
	}

	/**
	 * Checks if the Object Target and Relational Targets returned from the GetObjects service call
	 * are valid.
	 *
	 * @param array $relationsForChecking Relations returned from CreateObjectRelations response to be validated against the GetObjects response Targets.
	 * @return bool False when the validation fails.
	 */
	private function validateObjectTargets( $relationsForChecking )
	{
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$objects = array( $this->layout, $this->image );
		if( $objects ) foreach( $objects as $object ) {
			$objId = $object->MetaData->BasicMetaData->ID;
			$stepInfo = 'Get the object targets to validate.';
			$response = $this->callGetObjects( array( $objId ), false, $stepInfo );

			// Check Object Target.
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array()); // Object properties that will not be compared
			if( !$phpCompare->compareTwoArrays( $object->Targets, $response->Objects[0]->Targets ) ) {
				$objId = $object->MetaData->BasicMetaData->ID;
				$objType = $object->MetaData->BasicMetaData->Type;
				$this->setResult( 'ERROR', 'The Object Targets for object type "'.$objType.'" returned by GetObjects is '.
					'different from the CreateObjects Response.</br>' .
					print_r( $phpCompare->getErrors(),1), 'Please check the Object Targets for object id "' .$objId. '"'.
					'in CreateObjects and GetObjects response.');
				return false;
			}

			$targetIndex = 0;
			if( $response->Objects[0]->Targets ) foreach( $response->Objects[0]->Targets as $respObjTarget ) {
				if( $respObjTarget->PublishedDate ) {
					$this->setResult( 'ERROR', 'Targets['.$targetIndex.']->PublishedDate is expected to be null '.
						' in the GetObjects response, but this is not the case.', 'Please check the Object Targets '.
						'for object id "' .$objId. '" in CreateObjects and GetObjects response.');
					return false;
				}
				if( $respObjTarget->PublishedVersion ) {
					$this->setResult( 'ERROR', 'Targets['.$targetIndex.']->PublishedVersion is expected to be null '.
						' in the GetObjects response, but this is not the case.', 'Please check the Object Targets '.
						'for object id "' .$objId. '" in CreateObjects and GetObjects response.');
					return false;
				}
				$targetIndex++;
			}

			// Check Relational Target
			if( $response->Objects[0]->Relations ) foreach( $response->Objects[0]->Relations as $relation ) {
				$phpCompare = new WW_Utils_PhpCompare();
				$phpCompare->initCompare( array()); // Object properties that will not be compared
				$child = $object->MetaData->BasicMetaData->Type;
				if( !$phpCompare->compareTwoArrays( $relationsForChecking[$child]->Targets, $relation->Targets ) ) {
					$objId = $object->MetaData->BasicMetaData->ID;
					$objType = $object->MetaData->BasicMetaData->Type;
					$this->setResult( 'ERROR', 'The Relational Targets for object type "'.$objType.'" returned by GetObjects is '.
						'different from the CreateObjectRelations Response.</br>' .
						print_r( $phpCompare->getErrors(),1), 'Please check the Relational Targets for object id "' .$objId. '"'.
						'in CreateObjectRelations and GetObjects response.');
					return false;
				}

				$targetIndex = 0;
				if( $relation->Targets ) foreach( $relation->Targets as $relationTarget ) {
					if( $relationTarget->PublishedDate ) {
						$this->setResult( 'ERROR', 'Targets['.$targetIndex.']->PublishedDate in the Relation->Target is expected to be null '.
							' in the GetObjects response, but this is not the case.', 'Please check the Relational Targets '.
							'for object id "' .$objId. '" in CreateObjectRelations and GetObjects response.');
						return false;
					}
					if( $relationTarget->PublishedVersion ) {
						$this->setResult( 'ERROR', 'Targets['.$targetIndex.']->PublishedVersion in the Relation->Target is expected to be null '.
							' in the GetObjects response, but this is not the case.', 'Please check the Relational Targets '.
							'for object id "' .$objId. '" in CreateObjectRelations and GetObjects response.');
						return false;
					}
					$targetIndex++;
				}
			}
		}
		return true;
	}
}