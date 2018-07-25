<?php
/**
 * @since v9.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflObjectLabels_TestCase extends TestCase
{
	/**
	 * @var WW_Utils_TestSuite $utils
	 */
	private $utils;
	/**
	 * @var array $vars
	 */
	private $vars;
	/**
	 * @var string $ticket
	 */
	private $ticket;
	/**
	 * @var PublicationInfo $publication
	 */
	private $publication;
	/**
	 * @var CategoryInfo $category
	 */
	private $category;

	public function getDisplayName() { return 'Object Labels'; }
	public function getTestGoals()   { return 'Checks if object labels can be created, updated and removed. '; }
	public function getTestMethods() { return '<ol>'.
	'<li>Creates a dossier label.</li>'.
	'<li>Updates a dossier label.</li>'.
	'<li>Deletes a dossier label.</li>'.
	'</ol>'; }
	public function getPrio()        { return 90; }

	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		$this->vars = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_WebServices_WflServices']['ticket'];
		$this->publication = @$this->vars['BuildTest_WebServices_WflServices']['publication'];
		$this->category = @$this->vars['BuildTest_WebServices_WflServices']['category'];
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please enable the WflLogon test.' );
			return;
		}

		// First we need a dossier to test on... (make sure it is deleted later on)
		$dossier = $this->createDossier();

		// Perform the create, update and delete actions for the labels. (Bail out when something goes wrong).
		$label = $this->testCreateDossierLabels( $dossier );
		if ( $label ) {
			$this->testDeleteDossierLabels( $label );
		}

		// The update object label service should throw an error that it is not implemented yet.
		$this->testUpdateDossierLabels( $label );

		// Test adding labels to objects
		$this->testAddAndDeleteDossierLabels( $dossier );

		// Test if a deletion of a object label that is already deleted is silent
		$this->testSilentDeleteObjectLabels( $dossier );

		// Create labels and attach labels to objects that don't exist should error
		$this->testCreateAndAddObjectLabelsForNonExistingObjects( $dossier );

		// Delete the dossier that was created before.
		$this->deleteObject( $dossier );

		// "Instantiate" a Dossier Template and see if the labels are copied
		$this->testCopyDossier();
	}

	/**
	 * This function creates a dossier to test the Dossier Labels functionality.
	 *
	 * The dossier that is created has a Dossier <datetime> name. The description
	 * tells that the dossier is created for this test.
	 *
	 * When the $dossierTemplate parameter is set to true, a DossierTemplate will be created.
	 *
	 * @param bool $dossierTemplate
	 * @return Object|null
	 */
	private function createDossier( $dossierTemplate = false )
	{
		$type = $dossierTemplate ? 'DossierTemplate' : 'Dossier';

		// Determine unique dossier name.
		$microTime = explode( ' ', microtime() );
		$milliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$milliSec;
		$dossierName = $type.' '.$postfix;

		$publication = new Publication();
		$publication->Id = $this->publication->Id;
		$publication->Name = $this->publication->Name;

		$category = new Category();
		$category->Id = $this->category->Id;
		$category->Name = $this->category->Name;

		// MetaData
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->BasicMetaData->Name = $dossierName;
		$metaData->BasicMetaData->Type = $type;
		$metaData->BasicMetaData->Publication = $publication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Description = 'Temporary dossier to test Object Labels. Created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->ExtraMetaData = array();

		$object = new Object();
		$object->MetaData = $metaData;

		// Create the article objects at Enterprise DB
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		$service = new WflCreateObjectsService();
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $this->ticket;
		$request->Lock		= false;
		$request->Objects	= array( $object );
		$resp = $service->execute( $request );

		return ( $resp->Objects ) ? $resp->Objects[0] : null;
	}

	/**
	 * Deletes the given dossier permanently.
	 *
	 * @param Object $object
	 */
	private function deleteObject( $object )
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
		$service = new WflDeleteObjectsService();
		$request = new WflDeleteObjectsRequest();
		$request->Ticket	= $this->ticket;
		$request->IDs 		= array( $object->MetaData->BasicMetaData->ID );
		$request->Permanent	= true;
		$service->execute( $request );
	}

	/**
	 * This function tests the WflCreateObjectLabelsService.
	 *
	 * Two different tests are performed.
	 * - Create a new Dossier Label
	 * - Create a Dossier Label with an name that already exists for the dossier (only casing of name differs)
	 *
	 * @param Object $dossier
	 * @return ObjectLabel|null
	 */
	private function testCreateDossierLabels( $dossier )
	{
		$labelName = 'Test Label';

		$dossierLabel = new ObjectLabel();
		$dossierLabel->Name = $labelName;

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectLabelsService.class.php';
		$request = new WflCreateObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );
		$request->ObjectId 		= $dossier->MetaData->BasicMetaData->ID;

		/* @var WflCreateObjectLabelsResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Create dossier label' );
		if ( !$resp || !$resp->ObjectLabels ) {
			$this->setResult( 'ERROR', 'The CreateObjectLabels didn\'t respond correctly.' );
			return null;
		}

		if ( count($resp->ObjectLabels) != 1 ) {
			$this->setResult( 'ERROR', 'The CreateObjectLabels didn\'t send one Object Label back as expected.' );
			return null;
		}

		$objectLabelResponse = unserialize(serialize($resp->ObjectLabels[0]));

		if ( !$objectLabelResponse->Id || $objectLabelResponse->Name != $dossierLabel->Name ) {
			$this->setResult( 'ERROR', 'The CreateObjectLabels didn\'t send the id back for the Object Label or the name doesn\'t match.' );
			return null;
		}

		// The casing of the name should not matter. So for this test lower case the name.
		$dossierLabel->Name = strtolower($labelName);

		$request = new WflCreateObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );
		$request->ObjectId 		= $dossier->MetaData->BasicMetaData->ID;

		/* @var WflCreateObjectLabelsResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Create dossier label with a name that already exists.' );
		if ( !$resp || !$resp->ObjectLabels ) {
			$this->setResult( 'ERROR', 'The CreateObjectLabels didn\'t respond correctly.' );
			return null;
		}

		if ( count($resp->ObjectLabels) != 1 ) {
			$this->setResult( 'ERROR', 'The CreateObjectLabels didn\'t send one Object Label back as expected.' );
			return null;
		}

		// The Id should be the same as the previously created label.
		if ( $resp->ObjectLabels[0]->Id != $objectLabelResponse->Id || $resp->ObjectLabels[0]->Name != $objectLabelResponse->Name ) {
			$this->setResult( 'ERROR', 'The CreateObjectLabels didn\'t send the id back for the Object Label or the name doesn\'t match.' );
			return null;
		}

		return $objectLabelResponse;
	}

	/**
	 * The UpdateObjectLabels service isn't implemented yet so it should throw an 'Invalid operation (S1019)' error.
	 *
	 * @param ObjectLabel $label
	 */
	private function testUpdateDossierLabels( $label )
	{
		$label->Name = 'New Name';

		require_once BASEDIR . '/server/services/wfl/WflUpdateObjectLabelsService.class.php';
		$request = new WflUpdateObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $label );

		/* @var WflUpdateObjectLabelsResponse $resp */
		$this->utils->callService( $this, $request, 'Update dossier label name.', '(S1019)' ); // S1019 = Invalid operation
	}

	/**
	 * Tests if the Dossier Label can be deleted correctly.
	 *
	 * @param ObjectLabel $label
	 */
	private function testDeleteDossierLabels( $label )
	{
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectLabelsService.class.php';
		$request = new WflDeleteObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $label );

		/* @var WflDeleteObjectLabelsResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Delete dossier label.' );
		if ( !$resp ) {
			$this->setResult( 'ERROR', 'The DeleteObjectLabels service didn\'t respond correctly.' );
		}
	}

	/**
	 * This function checks the basic functionality of Object Labels.
	 *
	 * This function:
	 *  - Creates an Object Label inside the Dossier
	 *  - Creates a Hyperlink object inside the Dossier
	 *  - Attaches the Object Label to the Hyperlink object
	 *  - Removes the Object Label from the Hyperlink Object
	 *  - Deletes the Object Label from the Dossier
	 *  - Deletes the Hyperlink object.
	 *
	 * @param Object $dossier
	 */
	private function testAddAndDeleteDossierLabels( $dossier )
	{
		$hyperlink = $this->createHyperlink( $dossier );

		$dossierLabel = new ObjectLabel();
		$dossierLabel->Name = 'TestAddAndDeleteDossierLabel';

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectLabelsService.class.php';
		$request = new WflCreateObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );
		$request->ObjectId 		= $dossier->MetaData->BasicMetaData->ID;

		/* @var WflCreateObjectLabelsResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Create dossier label in function: ' . __FUNCTION__ );

		if ( $resp->ObjectLabels && isset($resp->ObjectLabels[0]) ) {
			$label = $resp->ObjectLabels[0];

			require_once BASEDIR . '/server/services/wfl/WflAddObjectLabelsService.class.php';
			$addRequest = new WflAddObjectLabelsRequest();
			$addRequest->Ticket = $this->ticket;
			$addRequest->ParentId = $dossier->MetaData->BasicMetaData->ID;
			$addRequest->ChildIds = array( $hyperlink->MetaData->BasicMetaData->ID );
			$addRequest->ObjectLabels = array( $label );
			/* @var WflAddObjectLabelsResponse $addResp */
			$addResp = $this->utils->callService( $this, $addRequest, 'Add dossier label in function: ' . __FUNCTION__ );

			// At this stage there should be Object Labels defined for the dossier and on the Relation with the Hyperlink
			$this->validateObjectLabelsForObject( true, $dossier );

			if ( $addResp ) {
				require_once BASEDIR . '/server/services/wfl/WflRemoveObjectLabelsService.class.php';
				$deleteRequest = new WflRemoveObjectLabelsRequest();
				$deleteRequest->Ticket = $this->ticket;
				$deleteRequest->ParentId = $dossier->MetaData->BasicMetaData->ID;
				$deleteRequest->ChildIds = array( $hyperlink->MetaData->BasicMetaData->ID );
				$deleteRequest->ObjectLabels = array( $label );
				$this->utils->callService( $this, $deleteRequest, 'Delete dossier label in function: ' . __FUNCTION__ );
			}

			// Delete the label from the dossier
			$this->testDeleteDossierLabels( $label );

			// There should be no labels be defined anymore for the Dossier.
			$this->validateObjectLabelsForObject( false, $dossier );
		} else {
			$this->setResult( 'ERROR', 'Could not create label.' );
		}

		// Delete the Hyperlink object.
		$this->deleteObject( $hyperlink );
	}

	/**
	 * Tests if the deletion of an object label that is already deleted is silent.
	 * If the dossier is already deleted Record not found (S1029) error should be hrown.
	 *
	 * @param Object $dossier
	 */
	private function testSilentDeleteObjectLabels( $dossier )
	{
		$dossierLabel = new ObjectLabel();
		$dossierLabel->Id = -1;
		$dossierLabel->Name = 'TestSilentDeleteObjectLabels';

		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectLabelsService.class.php';
		$request = new WflDeleteObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );

		/* @var WflDeleteObjectLabelsResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Delete dossier label with an invalid id.' );
		if ( !$resp ) {
			$this->setResult( 'ERROR', 'The DeleteObjectLabels service didn\'t respond correctly.' );
		}

		require_once BASEDIR . '/server/services/wfl/WflRemoveObjectLabelsService.class.php';
		$request = new WflRemoveObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ParentId 		= -1;
		$request->ChildIds 		= array( -1 );
		$request->ObjectLabels 	= array( $dossierLabel );

		// When the Dossier ID is incorrect a 'Record not found' error is thrown
		$this->utils->callService( $this, $request, 'Delete dossier label with non existing dossier id.', '(S1029)' ); // S1029 = Record not found

		// When the parent dossier exists but the child objects don't it should be silent (expect that it deleted already)
		$request->ParentId 		= $dossier->MetaData->BasicMetaData->ID;
		$this->utils->callService( $this, $request, 'Delete dossier label with non existing child id.' );
	}

	/**
	 * Tests if you can't create object labels for a non-existing dossier.
	 *
	 * @param Object $dossier
	 */
	private function testCreateAndAddObjectLabelsForNonExistingObjects( $dossier )
	{
		$dossierLabel = new ObjectLabel();
		$dossierLabel->Name = 'TestCreateObjectLabelsForNonExistingObjects';

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectLabelsService.class.php';
		$request = new WflCreateObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );
		$request->ObjectId 		= -1;

		$this->utils->callService( $this, $request, 'Test silent create of object labels. (Parent id doesn\'t exists)', '(S1029)' ); // S1029 = Record not found

		require_once BASEDIR . '/server/services/wfl/WflAddObjectLabelsService.class.php';
		$request = new WflAddObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );
		$request->ParentId 		= -1;
		$request->ChildIds 		= array( -1 );

		$this->utils->callService( $this, $request, 'Test silent add of object labels. (Parent id doesn\'t exists)', '(S1029)' ); // S1029 = Record not found

		$request->ParentId 		= $dossier->MetaData->BasicMetaData->ID;
		$this->utils->callService( $this, $request, 'Test silent add of object labels. (Label id doens\'t exists)',
			'ERR_ASSIGN_OBJECT_LABELS' ); // The ERR_ASSIGN_OBJECT_LABELS key is thrown as an error.
	}

	/**
	 * This function tests whether the Object Labels are copied when a Dossier is copied.
	 *
	 * The following steps are performed.
	 * - The function creates a Dossier Template with one Object Label
	 * - Two objects (Hyperlinks) are created within the Dossier Template
	 * - One Hyperlink gets the Object Label assigned
	 * - The Dossier Template is copied and transformed into a Dossier
	 * - The Object Labels should now be copied and one Hyperlink should have the
	 *      new Object Label assigned.
	 */
	private function testCopyDossier()
	{
		$dossierTemplate = $this->createDossier( true );
		$hyperlinkWithLabel = $this->createHyperlink( $dossierTemplate );
		$hyperlinkWithoutLabel = $this->createHyperlink( $dossierTemplate );

		$dossierLabel = new ObjectLabel();
		$dossierLabel->Name = 'CopyTestLabel';

		require_once BASEDIR . '/server/services/wfl/WflCreateObjectLabelsService.class.php';
		$request = new WflCreateObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ObjectLabels 	= array( $dossierLabel );
		$request->ObjectId 		= $dossierTemplate->MetaData->BasicMetaData->ID;

		$resp = $this->utils->callService( $this, $request, 'Add an Object Label to the Dossier Template.' );

		require_once BASEDIR . '/server/services/wfl/WflAddObjectLabelsService.class.php';
		$request = new WflAddObjectLabelsRequest();
		$request->Ticket		= $this->ticket;
		$request->ParentId 		= $dossierTemplate->MetaData->BasicMetaData->ID;
		$request->ChildIds		= array( $hyperlinkWithLabel->MetaData->BasicMetaData->ID );
		$request->ObjectLabels	= $resp->ObjectLabels;

		$this->utils->callService( $this, $request, 'Assign an Object Label to the hyperlink in the Dossier Template.' );

		$dossierMetaData = unserialize(serialize($dossierTemplate->MetaData));
		$microTime = explode( ' ', microtime() );
		$milliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$milliSec;
		$dossierMetaData->BasicMetaData->Name = 'InstantiatedDossier ' . $postfix;
		$dossierMetaData->BasicMetaData->Type = 'Dossier';

		require_once BASEDIR . '/server/services/wfl/WflCopyObjectService.class.php';
		$request = new WflCopyObjectRequest();
		$request->Ticket 		= $this->ticket;
		$request->SourceID 		= $dossierTemplate->MetaData->BasicMetaData->ID;
		$request->MetaData 	 	= $dossierMetaData;

		/* @var WflCopyObjectResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Copy the Dossier Template to make a Dossier.' );

		$newObjectId = $resp->MetaData->BasicMetaData->ID;

		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$request = new WflGetObjectsRequest();
		$request->Ticket 		= $this->ticket;
		$request->IDs 			= array( $newObjectId );
		$request->Lock			= false;
		$request->Rendition		= 'none';
		$request->RequestInfo	= array( 'Relations', 'ObjectLabels' );

		/* @var WflGetObjectsResponse $resp */
		$resp = $this->utils->callService( $this, $request, 'Get the newly created Dossier.' );
		if ( $resp && $resp->Objects ) {
			/* @var Object $dossier */
			$dossier = $resp->Objects[0];

			if ( !$dossier->ObjectLabels || count($dossier->ObjectLabels) != 1 ) {
				$this->setResult( 'ERROR', 'The instantiated Dossier should have 1 Object Label assigned.' );
			}

			$copiedLabel = $dossier->ObjectLabels[0];
			if ( $copiedLabel->Name != $dossierLabel->Name ) {
				$this->setResult( 'ERROR', 'The Object Label of the instantiated Dossier should have the same name as the Dossier Template.' );
			}

			if( !$dossier->Relations || count($dossier->Relations) != 2 ) {
				$this->setResult( 'ERROR', 'The instantiated Dossier should have 2 relations assigned.' );
			}

			/* @var Relation $relation */
			foreach ( $dossier->Relations as $relation ) {
				if ( $relation->Child == $hyperlinkWithLabel->MetaData->BasicMetaData->ID ) {
					if ( !$relation->ObjectLabels || count($relation->ObjectLabels) != 1 ) {
						$this->setResult( 'ERROR', 'The relation between parent: ' . $relation->Parent . ' and child: '
							. $relation->Child . ' should have 1 Object Label assigned.' );
					}

					$relationLabel = $relation->ObjectLabels[0];
					if( $relationLabel->Id != $copiedLabel->Id || $relationLabel->Name != $copiedLabel->Name ) {
						$this->setResult( 'ERROR', 'The relation between parent: ' . $relation->Parent . ' and child: '
							. $relation->Child . ' does not have the correct label assigned.' );
					}
				} else if ( $relation->Child == $hyperlinkWithoutLabel->MetaData->BasicMetaData->ID ) {
					if ( $relation->ObjectLabels ) {
						$this->setResult( 'ERROR', 'The relation between parent: ' . $relation->Parent . ' and child: '
										 . $relation->Child . ' should have no Object Labels assigned.' );
					}
				} else {
					$this->setResult( 'ERROR', 'A unknown relation is detected!' );
				}
			}

			$this->deleteObject( $dossier );
		}

		$this->deleteObject( $hyperlinkWithLabel );
		$this->deleteObject( $hyperlinkWithoutLabel );
		$this->deleteObject( $dossierTemplate );
	}

	/**
	 * This function validates the Object Labels for a Dossier.
	 *
	 * When the $objectLabelsShouldExist property is set to true, there should
	 * be some Object Labels in the $object->ObjectLabels and
	 * $object->Relations[]->ObjectLabels properties. Otherwise these should be empty.
	 *
	 * @param bool $objectLabelsShouldExist
	 * @param Object $dossier
	 * @return bool
	 */
	private function validateObjectLabelsForObject( $objectLabelsShouldExist, $dossier )
	{
		require_once BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php';
		$getObjReq = new WflGetObjectsRequest();
		$getObjReq->Ticket = $this->ticket;
		$getObjReq->IDs = array( $dossier->MetaData->BasicMetaData->ID );
		$getObjReq->Lock = false;
		$getObjReq->RequestInfo = array( 'MetaData', 'Relations', 'ObjectLabels' );
		$getObjReq->Rendition = 'none';
		/**
		 * @var WflGetObjectsResponse $getObjResp
		 */
		$getObjResp = $this->utils->callService( $this, $getObjReq, 'GetObjects call with RequestInfo set to ObjectLabels' );

		if ( !$getObjResp || !$getObjResp->Objects ) {
			$this->setResult( 'ERROR', 'Could not retrieve the dossier object.' );
			return false;
		}

		$retDossier = $getObjResp->Objects[0];
		if ( $retDossier->ObjectLabels != $objectLabelsShouldExist ) {
			$message = $objectLabelsShouldExist ? 'There should be ObjectLabels defined for the object.' : 'There should be no ObjectLabels defined for the object.';
			$this->setResult( 'ERROR', $message );
			return false;
		}

		if ( $retDossier->Relations ) foreach ( $retDossier->Relations as $relation ) {
			if ( $relation->ObjectLabels != $objectLabelsShouldExist ) {
				$message = $objectLabelsShouldExist ? 'There should be ObjectLabels defined for the object.' : 'There should be no ObjectLabels defined for the object.';
				$this->setResult( 'ERROR', $message );
				return false;
			}
		}

		return true;
	}

	/**
	 * Creates a Hyperlink object in a Dossier.
	 *
	 * @param Object $parentDossier
	 * @return Object|null
	 */
	private function createHyperlink( $parentDossier )
	{
		// Determine unique dossier name.
		$microTime = explode( ' ', microtime() );
		$milliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$milliSec;
		$hyperlinkName = 'Hyperlink '.$postfix;

		$publication = new Publication();
		$publication->Id = $this->publication->Id;
		$publication->Name = $this->publication->Name;

		$category = new Category();
		$category->Id = $this->category->Id;
		$category->Name = $this->category->Name;

		// MetaData
		$metaData = new MetaData();
		$metaData->BasicMetaData = new BasicMetaData();
		$metaData->BasicMetaData->Name = $hyperlinkName;
		$metaData->BasicMetaData->Type = 'Hyperlink';
		$metaData->BasicMetaData->Publication = $publication;
		$metaData->BasicMetaData->Category = $category;
		$metaData->RightsMetaData = new RightsMetaData();
		$metaData->SourceMetaData = new SourceMetaData();
		$metaData->ContentMetaData = new ContentMetaData();
		$metaData->ContentMetaData->Description = 'Temporary hyperlink to test Object Labels. Created by BuildTest class '.__CLASS__;
		$metaData->WorkflowMetaData = new WorkflowMetaData();
		$metaData->ExtraMetaData = array();

		$object = new Object();
		$object->MetaData = $metaData;

		$relation = new Relation();
		$relation->Parent = $parentDossier->MetaData->BasicMetaData->ID;
		$relation->Type = 'Contained';
		$object->Relations = array($relation);

		// Create the article objects at Enterprise DB
		require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
		$service = new WflCreateObjectsService();
		$request = new WflCreateObjectsRequest();
		$request->Ticket	= $this->ticket;
		$request->Lock		= false;
		$request->Objects	= array( $object );
		$resp = $service->execute( $request );

		return ( $resp->Objects ) ? $resp->Objects[0] : null;
	}

}