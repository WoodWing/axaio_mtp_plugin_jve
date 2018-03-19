<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_RelationTargets_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils
	
	// Objects to work on:
	private $template = null;
	private $form = null;
	private $dossier = null;
	private $image = null;

	public function getDisplayName()
	{
		return 'Relational Targets - Print Channel';
	}

	public function getTestGoals()
	{
		return 'Checks if Object and Relation Targets are be round-tripped correctly.';
	}

	public function getTestMethods()
	{
		return
			'Test with objects assigned to a web channel: <ul>'.
				'<li>01 Create a Template, Dossier, Form and an Image object (CreateObjects).</li>'.
				'<li>02 Place the Image onto the Form (CreateObjectRelations).</li>'.
				'<li>03 Refresh the Form (GetObjects).</li>'.
				'<li>04 Simulate Publishing the Form. Fake: Just set the PublishedVersion and PublishedDate (UpdateObjectRelations).</li>'.
				'<li>05 Lock the Form (UnlockObjects).</li>'.
				'<li>06 Save the Form (SaveObjects).</li>'.
				'<li>07 Ensure that Relation Target returned in SaveObjects response has the latest PublishedDate and PublishedVersion.</li>'.
			'</ul>';
	}

	public function getPrio()
	{
		return 50;
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
			// Create objects for testing.
			if( !$this->setupTestData() ) {
				break;
			}
			
			// Performs several actions to ensure that the Relation Targets are round-tripped.
			if( !$this->placeImageOntoForm() ) {
				break;
			}
			if( !$this->refreshTheForm() ) {
				break;
			}
			if( !$this->publishTheForm() ) {
				break;
			}
			if( !$this->lockTheForm() ) {
				break;
			}
			if( !$this->saveTheForm() ) {
				break;
			}
			if( !$this->validateRelationTargets() ) {
				break;
			}
		} while( false );
		
		// Remove the test objects again.
		$this->tearDownTestData();
	}

	/**
	 * Prepare the test structure by creating objects for testing.
	 *
	 * @return bool
	 */
	private function setupTestData()
	{
		$retVal = true;
		
		// Compose postfix for object name.
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		// Create the Publish Form Template.
		$stepInfo = 'Create the Publish Form Template.';
		$this->template = $this->mcpUtils->createPublishFormTemplateObject( $stepInfo );
		if( is_null($this->template) ) {
			$this->setResult( 'ERROR',  'Could not create the Publish Form Template.');
			$retVal = false;
		}

		// Create the Dossier.
		$stepInfo = 'Create the Dossier object for Web.';
		$this->dossier = $this->mcpUtils->createDossier( $stepInfo, 'DossierForWeb '.$postfix, 'web' );
		if( is_null($this->dossier) ) {
			$this->setResult( 'ERROR',  'Could not create the Dossier.' );
			$retVal = false;
		}

		// Create the Publish Form.
		if( $this->dossier ) {
			$stepInfo = 'Create the Publish Form object and assign to the Dossier.';
			$this->form = $this->mcpUtils->createPublishFormObject( $this->template, $this->dossier, $stepInfo );
			if( is_null($this->form) ) {
				$this->setResult( 'ERROR',  'Could not create Publish Form object.' );
				$retVal = false;
			}
		}

		// Create the Image.
		$stepInfo = 'Create the Image object for Web.';
		$this->image = $this->mcpUtils->createPublishFormPlacedImage( $stepInfo, 'ImageForWeb '.$postfix );
		if( is_null( $this->image ) ) {
			$this->setResult( 'ERROR',  'Could not create the Image.' );
			$retVal = false;
		}
		
		return $retVal;
	}

	/**
	 * Tear down the Test environment setup in {@link: setupTestData()}.
	 *
	 * @return bool Whether or not sucessful.
	 */
	private function tearDownTestData()
	{
		$retVal = true;
		
		// Permanent delete the Article.
		if( $this->image ) {
			$id = $this->image->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
				$retVal = false;
			}
			$this->image = null;
		}

		// Permanent delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport );
				$retVal = false;
			}
			$this->form = null;
		}
		
		// Permanent delete the Dossier.
		if( $this->dossier ) {
			$id = $this->dossier->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->dossier = null;
		}
		
		// Permanent delete the Publish Form Template.
		if( $this->template ) {
			$id = $this->template->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
				$retVal = false;
			}
			$this->template = null;
		}
		return $retVal;
	}

	/**
	 * Place an Image onto the Form.
	 *
	 * @return bool
	 */
	private function placeImageOntoForm()
	{
		// Place the first image on the form.
		$stepInfo = 'Place the first Image on the Publish Form.';
		$composedRelation = $this->mcpUtils->composePlacedRelation( $this->form->MetaData->BasicMetaData->ID,
													$this->image->MetaData->BasicMetaData->ID, 0, null );
		$formImageRelations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );

		if( is_null( $formImageRelations ) || !isset( $formImageRelations[0] )) {
			$this->setResult( 'ERROR',  'Failed to place an image onto the Form. Test cannot be continued.' );
			return false;
		}
		return true;
	}

	/**
	 * Does 'refresh' the Publish Form by calling GetObjects.
	 * It also makes sure that the cached Publish Form has the latest Relations from DB.
	 *
	 * @return bool Whether or not the form could be retrieved from database.
	 */
	private function refreshTheForm()
	{
		$id = $this->form->MetaData->BasicMetaData->ID;
		$stepInfo = 'Refresh the Publish Form object.';
		$response = $this->callGetObjects( array( $id ), false, $stepInfo );
		$retVal = isset($response->Objects[0]);
		if( $retVal ) {
			$this->form = $response->Objects[0];
		}
		return $retVal;
	}

	/**
	 * 'Publish' the Form.
	 *
	 * This function 'fakes' the operation of Publishing Form by updating its PublishedDate
	 * and PublishedVersion via UpdateObjectRelations service call.
	 *
	 * @return bool True when the Form can be 'published'; False otherwise.
	 */
	private function publishTheForm()
	{
		// The updated Target after 'publishing' the Form. The Target is udpated with PublishedVersion and Date.
		$target = $this->template->Targets[0];
		
		if( $this->form->Relations ) {
			foreach( $this->form->Relations as &$relation ) {
				$relation->Rating = null; // To repair the Rating ( This should not be needed. same as above).
				if( $relation->Parent == $this->form->MetaData->BasicMetaData->ID &&
					$relation->Child == $this->image->MetaData->BasicMetaData->ID &&
					$relation->Type == 'Placed' ) {
					// To perform the 'Publishing' action.
					$target->PublishedDate = date( 'Y-m-d\TH:i:s' ); // After published, get a PublishedDate.
					$target->PublishedVersion = '0.8'; // And Version.
					$relation->Targets = array( $target );
				}
			}
			require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
			$request = new WflUpdateObjectRelationsRequest();
			$request->Ticket = $this->ticket;
			$request->Relations = $this->form->Relations;
			$stepInfo = 'Update the Form relations to simulate publish operation.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( !$response ) {
				$this->setResult( 'ERROR', 'Failed to Publish the Form, the PublishedVersion '.
								'and PublishedDate cannot be set.' );
				return false;
			}

		} else {
			$this->setResult( 'ERROR', 'There is no Form to publish, PublishedVersion '.
							'and PublishedDate cannot be set.' );
			return false;
		}
		return true;
	}

	/**
	 * To unlock the Form by calling GetObjects service call.
	 *
	 * @return bool Whether or not sucessful.
	 */
	private function lockTheForm()
	{
		$id = $this->form->MetaData->BasicMetaData->ID;
		$stepInfo = 'Lock the Publish Form object.';
		$response = $this->callGetObjects( array( $id ), true, $stepInfo );
		$retVal = isset($response->Objects[0]);
		if( $retVal ) {
			$this->form = $response->Objects[0];
		}
		return $retVal;
	}

	/**
	 * Save the Form to get the latest Relation Targets.
	 *
	 * This testing is to ensure that the Relation Targets sent in SaveObjects request
	 * does not overwrite the Targets in the Database if the Database has the most recent
	 * records. Instead it retrieves the records(Targets) from the Database and return
	 * in the SaveObjects response.
	 *
	 * @return WflSaveObjectsResponse|null The saved Form.
	 */
	private function saveTheForm()
	{
		// constructing saveObjects request structure.
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = true;
		$request->Objects = array( $this->form );
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		
		$stepInfo = 'Save the Publish Form with relational targets.';
		$response = $this->utils->callService( $this, $request, $stepInfo );
		return !is_null( $response ) ? $response->Objects[0] : null;
	}

	/**
	 * Validate the Relation Targets after doing a SaveObjects service call.
	 *
	 * This is to ensure that the Targets returned by the SaveObjects service call are
	 * up-to-date.
	 *
	 * @return bool
	 */
	private function validateRelationTargets()
	{
		if( !$this->form->Relations ) {
			$this->setResult( 'ERROR',  'Form was found without Relation which is not expected.' );
			return false;
		}

		// To ensure that the PublishedVersion and PublishedDate are still intact after saving the PublishForm.
		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		foreach( $this->form->Relations as $relation ) {
			if( $relation->Parent == $this->form->MetaData->BasicMetaData->ID &&
				$relation->Child == $this->image->MetaData->BasicMetaData->ID &&
				$relation->Type == 'Placed' ) {
				$phpCompare = new WW_Utils_PhpCompare();
				$phpCompare->initCompare( array()); // Object properties that will not be compared
				if( !$phpCompare->compareTwoObjects( $relation->Targets[0], $this->template->Targets[0] ) ) {
					$formId = $this->form->MetaData->BasicMetaData->ID;
					$this->setResult( 'ERROR', 
						'The Relation Targets for object type "PublishForm" was not returned '.
						'correctly. '.print_r( $phpCompare->getErrors(), true ), 
						'Please check the PublishForm Relational Targets for Form id "' .
						$formId. '" in CreateObjectRelations, UpdateObjectRelations and '.
						'SaveObjects response.');
					return false;
				}
			}
		}
		return true;
	}


	/**
	 * Call GetObjects service call and indicate if the Object should be locked or not.
	 *
	 * @param array $objIds
	 * @param bool $lock Whether the lock the Object.
	 * @param string $stepInfo Extra logging info.
	 * @return WflGetObjectsResponse|null
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
}
