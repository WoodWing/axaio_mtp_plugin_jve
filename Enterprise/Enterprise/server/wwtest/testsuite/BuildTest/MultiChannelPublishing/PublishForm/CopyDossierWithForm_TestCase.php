<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v9.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_MultiChannelPublishing_PublishForm_CopyDossierWithForm_TestCase extends TestCase
{
	// Session related stuff
	private $ticket = null;
	private $vars = null;
	private $utils = null; // WW_Utils_TestSuite
	private $mcpUtils = null; // MultiChannelPublishingUtils
	
	// Objects to work on:
	private $template = null;
	private $dossier = null;
	private $form = null;
	private $image = null;
	private $article = null;
	private $copiedInvalidForm = null;
	private $copiedDossierWithForm = null;
	private $copiedDossierWithoutForm = null;
	private $printTarget = null;

	public function getDisplayName()
	{
		return 'CopyDossierWithForm';
	}

	public function getTestGoals()
	{
		return 'Checks if copying a Dossier,will the Form gets copied as well when the Targets are the same.';
	}

	public function getTestMethods()
	{
		return
			'Test with objects assigned to a print channel: <ul>'.
				'<li>01 Creates a Template, Dossier, a PublishForm, an Image and an Article object (CreateObjects).</li>'.
				'<li>02 Move the Image and the Article into the Dossier (CreateObjectRelations).</li>'.
				'<li>03 Place the Image and Article into the Dossier (CreateObjectRelations).</li>'.
				'<li>04 Publish the Form by setting the PublishedVersion and Date of the relation Targets (UpdateObjectRelations).</li>'.
				'<li>05 Attempt to copy a PublishForm which should fail (CopyObject).</li>'.
				'<li>06 Copy Dossier with the new Dossier assign to the same Target as the original Dossier (CopyObject).</li>'.
				'<li>07 Verify the copied Dossier and its newly copied Form. </li>'.
				'<li>08 Do another copy of the first Dossier again but this time with different Targets(Print Target) (CopyObject). </li>'.
				'<li>09 Verify the second copied Dossier, and ensure that this time there is no Form copied into this new Dossier. </li>'.
			'</ul>';
	}

	public function getPrio()
	{
		return 60;
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
			if( !$this->publishTheForm() ) {
				break;
			}
			if( !$this->doInvalidCopyForm() ) {
				break;
			}
			// Copy Dossier and make sure the Form is deep-copied instead of getting a reference
			// like any other child(image, article) in the Dossier.
			$this->copiedDossierWithForm = $this->doCopyDossier();
			if( is_null( $this->copiedDossierWithForm) ) {
				break;
			}
			if( !$this->verifyCopiedDossierWithForm() ) {
				break;
			}
			$this->printTarget = $this->getPrintTargets();
			$this->copiedDossierWithoutForm = $this->doCopyDossier( array( $this->printTarget ));
			if( is_null( $this->copiedDossierWithoutForm )) {
				break;
			}
			if( !$this->verifyCopiedDossierWithoutForm() ) {
				break;
			}
		} while( false );
		
		// Remove the test objects from DB.
		$this->tearDownTestData();
	}

	/**
	 * Create a Template, Dossier, Form, Image and Article object in database for testing.
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
			$stepInfo = 'Create the Publish Form object.';
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

		// Create an article object.
		$stepInfo = 'Create an Article object.';
		$this->article =  $this->mcpUtils->createArticle( $stepInfo );
		if( is_null($this->article) ) {
			$this->setResult( 'ERROR', 'Could not create an Article object.' );
			$retVal = false;
		}

		// Place the image in the Dossier and onto the Form.
		if( !is_null( $this->image ) ) {
			// Place the image in the dossier.
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;
			$imageId = $this->image->MetaData->BasicMetaData->ID;
			$stepInfo = 'Place the Image on the Publish Form and assign it to the Dossier.';
			$createdRelation = $this->mcpUtils->createRelationObject( $stepInfo, $dossierId, $imageId, 'Contained', null );
			if( is_null( $createdRelation )) {
				$this->setResult( 'ERROR', 'Could not place the image in the Dossier.' );
				$retVal = false;
			} else {
				// Place the image on the form. (Create a Placed object relation between both.)
				$formId = $this->form->MetaData->BasicMetaData->ID;
				$stepInfo = 'Place the Image on the Publish Form.';
				$composedRelation = $this->mcpUtils->composePlacedRelation( $formId, $imageId, 0, null );
				$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ));
				if( is_null( $relations ) || !isset( $relations[0] ) ) {
					$this->setResult( 'ERROR',  'Failed to place an image onto the Form.' );
					$retVal = false;
				}
			}
		}

		// Place the article in the Dossier and onto the Form.
		if( !is_null( $this->article )) {
			// Place the article in the dossier.
			$dossierId = $this->dossier->MetaData->BasicMetaData->ID;
			$articleId = $this->article->MetaData->BasicMetaData->ID;
			$stepInfo = 'Place the Article on the Publish Form and assign it to the Dossier.';
			$createdRelation = $this->mcpUtils->createRelationObject( $stepInfo, $dossierId, $articleId, 'Contained', null );
			if( is_null( $createdRelation )) {
				$this->setResult( 'ERROR', 'Could not place the image in the Dossier.' );
				$retVal = false;
			} else {
				// Place the article on the form. (Create a Placed object relation between both.)
				$formId = $this->form->MetaData->BasicMetaData->ID;
				$stepInfo = 'Place the Article on the Publish Form.';
				$composedRelation = $this->mcpUtils->composePlacedRelation( $formId, $articleId, 0, null );
				$relations = $this->mcpUtils->createPlacementRelationsForForm( $stepInfo, array( $composedRelation ) );
				if( is_null( $relations ) || !isset( $relations[0] ) ) {
					$this->setResult( 'ERROR',  'Failed to place an article onto the Form.' );
					$retVal = false;
				}
			}
		}

		return $retVal;
	}

	/**
	 * Tear down the Test environment setup in {@link: setupTestData()}.
	 */
	private function tearDownTestData()
	{
		$retVal = true;

		if( !$this->unpublishTheForm( $this->form ) ) {
			$retVal = false;
		}

		// Permanent delete the Image.
		if( $this->image ) {
			$id = $this->image->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Image object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Image object: '.$errorReport );
				$retVal = false;
			}
			$this->image = null;
		}

		// Permanent delete the Article.
		if( $this->article ) {
			$id = $this->article->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Article object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
				$retVal = false;
			}
			$this->article = null;
		}

		// Permanent delete the copied Dossier.
		if( $this->copiedDossierWithForm ) {
			$id = $this->copiedDossierWithForm->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the copied Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down copied Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->copiedDossierWithForm = null;
		}

		// Permanent delete the copied Dossier
		if( $this->copiedDossierWithoutForm ) {
			$id = $this->copiedDossierWithoutForm->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the copied Dossier object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down copied Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->copiedDossierWithoutForm = null;
		}

		// Permanent delete the Publish Form that should not be successfully copied.
		if( $this->copiedInvalidForm ) {
			$id = $this->copiedInvalidForm->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Publish Form object: '.$errorReport );
				$retVal = false;
			}
			$this->form = null;

		}

		// Permanent delete the Publish Form.
		if( $this->form ) {
			$id = $this->form->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Publish Form object.';
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
			$stepInfo = 'Tear down the Dossier object.';
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
			$stepInfo = 'Tear down the Publish Form Template object.';
			if( !$this->mcpUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Template object: '.$errorReport );
				$retVal = false;
			}
			$this->template = null;
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
		// The updated Target after 'publishing' the Form. The Target is updated with PublishedVersion and Date.
		$target = $this->template->Targets[0];
		$formRelations = BizRelation::getObjectRelations( $this->form->MetaData->BasicMetaData->ID, null, true, 'both' );

		if( $formRelations ) {
			foreach( $formRelations as &$relation ) {
				$relation->Rating = null; // To repair the Rating ( This should not be needed. same as above).
				$isPlacedOnForm = $relation->Parent == $this->form->MetaData->BasicMetaData->ID &&
					( $relation->Child == $this->image->MetaData->BasicMetaData->ID ||
					  $relation->Child == $this->article->MetaData->BasicMetaData->ID ) && $relation->Type == 'Placed';
				$isContainedForm = $relation->Child == $this->form->MetaData->BasicMetaData->ID &&
									$relation->Type == 'Contained';

				if( $isPlacedOnForm || $isContainedForm ){
					// To perform the 'Publishing' action.
					$target->PublishedDate = date( 'Y-m-d\TH:i:s' ); // After published, get a PublishedDate.
					$target->PublishedVersion = '2.0'; // And Version.
					$relation->Targets = array( $target );
				}
			}
			$this->form->Relations = $formRelations;

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
	 * Do a copy Form by calling CopyObject service call.
	 *
	 * This operation should fail as Copying a Form is not allowed by design.
	 *
	 * @return bool True when invalid copy failed(which is good), False when the invalid operation did not fail.
	 */
	private function doInvalidCopyForm()
	{
		$formToBeCopied = unserialize( serialize( $this->form ));
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		$metaData = $formToBeCopied->MetaData;
		$metaData->BasicMetaData->Name = 'CopyForm_'.$postfix;
		$sourceId = $metaData->BasicMetaData->ID;
		$targets = $formToBeCopied->Targets;

		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
		$request = new WflCopyObjectRequest();
		$request->Ticket = $this->ticket;
		$request->SourceID             = $sourceId;
		$request->MetaData             = $metaData;
		$request->Relations            = null;
		$request->Targets              = $targets;

		$stepInfo = 'Attempt to copy a Publish Form (Not Allowed).';
		$response = $this->utils->callService( $this, $request, $stepInfo, '(S1019)' );
		
		if( $response ) {
			require_once BASEDIR.'/server/utils/PHPClass.class.php';
			$this->copiedInvalidForm = WW_Utils_PHPClass::typeCast( $response, 'Object' ); // $resp is a WflCopyObjectResponse, cast it to Object type.
		}
		return is_null($response);
	}

	/**
	 * Do a CopyObject service call.
	 *
	 * When the Object Targets is not given, it copies the Dossier by using the source Dossier's Targets.
	 *
	 * @param array $targets
	 * @return null|object
	 */
	private function doCopyDossier( $targets = array() )
	{
		$copiedDossier = null;
		$dossierToBeCopied = unserialize( serialize( $this->dossier ));
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		$postfix = date( 'ymd His', $microTime[1] ).' '.$miliSec;

		$metaData = $dossierToBeCopied->MetaData;
		$metaData->BasicMetaData->Name = 'CopyDossier_'.$postfix;
		$sourceId = $metaData->BasicMetaData->ID;
		if( !$targets ) {
			 $targets = $dossierToBeCopied->Targets;
		}
		try {
			require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
			$service = new WflCopyObjectService();
			$request = new WflCopyObjectRequest();
			$request->Ticket = $this->ticket;
			$request->SourceID             = $sourceId;
			$request->MetaData             = $metaData;
			$request->Relations            = null;
			$request->Targets              = $targets;

			$resp = $service->execute( $request );
			require_once BASEDIR.'/server/utils/PHPClass.class.php';
			$copiedDossier = WW_Utils_PHPClass::typeCast( $resp, 'Object' ); // $resp is a WflCopyObjectResponse, cast it to Object type.
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Copy Dossier failed.' . $e->getDetail() );
		}

		return $copiedDossier;
	}

	/**
	 * Make sure the Dossier copied is valid.
	 *
	 * The function checks for the copied Dossier if the children are copied correctly.
	 * The children here refers to Article, Image and Form.
	 * While the Article and Image should be copied with reference only, the PulbishForm
	 * is expected to be deep-copied (which means clone a new PublishForm ) but not taking
	 * over the Publish history of the original PublishForm.
	 * The copied PublishForm's name is checked too as it should be in the format of
	 * 'DossierName-PubChannelName'.
	 *
	 * @return bool
	 */
	private function verifyCopiedDossierWithForm()
	{
		$result = true;
		// Verify copied Dossier's relations.
		if( $this->copiedDossierWithForm->Relations ) {
			$articleFound = false;
			$imageFound = false;
			$formFound = false;
			$copiedForm = null;
			foreach( $this->copiedDossierWithForm->Relations as $relation ) {
				if( $relation->Type == 'Contained' ) {
					switch( $relation->ChildInfo->Type ) {
						case 'Article':
							$articleId = $this->article->MetaData->BasicMetaData->ID;
							if( $relation->Child != $articleId ) {
								$this->setResult( 'ERROR', 'Article in the new copied Dossier was not copied by reference.' );
								$result = false;
							} else {
								$articleFound = true;
							}
							break;
						case 'Image';
							$imageId = $this->image->MetaData->BasicMetaData->ID;
							if( $relation->Child != $imageId ) {
								$this->setResult( 'ERROR', 'Image in the new copied Dossier was not copied by reference.' );
								$result = false;
							} else {
								$imageFound = true;
							}
							break;
						case 'PublishForm';
							$formId = $this->form->MetaData->BasicMetaData->ID;
							if( $relation->Child == $formId ) {
								$this->setResult( 'ERROR', 'PublishForm in the new copied Dossier was copied by reference which is wrong.' .
									'A new Publish Form is expected in the copied Dossier instead of a reference from the original Form.' );
								$result = false;
							} else {
								$formFound = true;
							}

							$user = BizSession::checkTicket( $this->ticket );
							$copiedForm = BizObject::getObject( $relation->Child, $user, false, 'none', array( 'Relations', 'Targets'));

							// Check for the Published info such as PublishedVersion, Date.
							if( $relation->Targets[0]->PublishedDate || $relation->Targets[0]->PublishedVersion ) {
								$this->setResult( 'ERROR', 'The new Publish Form copied should not carry over the ' .
								 'Published history such as "PublishedVersion" and "PublishedDate" from the source ' .
								 'Publish Form.' );
								$result = false;

								// The new Form was copied from the source including the PublishedDate and Version (which is wrong)
								// Need to clear the PublishedVersion and Date for the copied Form, else the copied Form
								// cannot be deleted after the test.
								$this->unpublishTheForm( $copiedForm );
							}
							break;
					}
				}
			}
			if( !$articleFound || !$imageFound || !$formFound ) {
				$this->setResult( 'ERROR', 'PublishForm or Article or Image are not found in the copied Dossier, which is wrong.' );
				$result = false;
			}
			if( $formFound ) {
				// verify Form's name.
				$expectedCopiedFormName = $this->copiedDossierWithForm->MetaData->BasicMetaData->Name;
				if( $expectedCopiedFormName != $copiedForm->MetaData->BasicMetaData->Name ) {
					$this->setResult( 'ERROR', 'The copied Form name is expected to be "'.$expectedCopiedFormName.
										'" but "'.$copiedForm->MetaData->BasicMetaData->Name.'" is returned, which is wrong.');
				}
			}
		}
		return $result;
	}

	/**
	 * Compose a Print Target.
	 *
	 * @return Target
	 */
	private function getPrintTargets()
	{
		$pubChannel = $this->vars['BuildTest_MultiChannelPublishing']['printPubChannel'];
		$issue = $this->vars['BuildTest_MultiChannelPublishing']['printIssue'];
		$printTarget = new Target();
		$printTarget->PubChannel = new PubChannel( $pubChannel->Id, $pubChannel->Name);
		$printTarget->Issue = new Issue($issue->Id, $issue->Name, $issue->OverrulePublication);

		return $printTarget;
	}

	/**
	 * 'UnPublish' the Form.
	 *
	 * This function 'fakes' the operation of Un-Publishing Form by clearing its PublishedDate
	 * and PublishedVersion via UpdateObjectRelations service call.
	 *
	 * @param Object $formToUnPublish
	 * @return bool True when the Form can be 'un-published'; False otherwise.
	 */
	private function unpublishTheForm( $formToUnPublish )
	{
		// The updated Target after 'un-publishing' the Form. The Target is updated with PublishedVersion and Date (both cleared).
		$target = $this->template->Targets[0];
		$formRelations = BizRelation::getObjectRelations( $formToUnPublish->MetaData->BasicMetaData->ID, null, true, 'both' );

		if( $formRelations ) {
			foreach( $formRelations as &$relation ) {
				$relation->Rating = null; // To repair the Rating ( This should not be needed. same as above).
				$isPlacedOnForm = $relation->Parent == $formToUnPublish->MetaData->BasicMetaData->ID &&
					( $relation->Child == $this->image->MetaData->BasicMetaData->ID ||
						$relation->Child == $this->article->MetaData->BasicMetaData->ID ) && $relation->Type == 'Placed';
				$isContainedForm = $relation->Child == $formToUnPublish->MetaData->BasicMetaData->ID &&
					$relation->Type == 'Contained';

				if( $isPlacedOnForm || $isContainedForm ){
					// To perform the 'Publishing' action.
					$target->PublishedDate = ""; // After unpublish, clear the PublishedDate.
					$target->PublishedVersion = ""; // And Version.
					$relation->Targets = array( $target );

				}
			}
			$formToUnPublish->Relations = $formRelations;
			require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
			$request = new WflUpdateObjectRelationsRequest();
			$request->Ticket = $this->ticket;
			$request->Relations = $formToUnPublish->Relations;
			$stepInfo = 'Delete the Form relations to simulate un-publish operation.';
			$response = $this->utils->callService( $this, $request, $stepInfo );
			if( !$response ) {
				$this->setResult( 'ERROR', 'Failed to un-publish the Form, the PublishedVersion '.
					'and PublishedDate cannot be cleared.' );
				return false;
			}

		} else {
			$this->setResult( 'ERROR', 'There is no Form to un-publish, PublishedVersion '.
				'and PublishedDate cannot be cleared.' );
			return false;
		}
		return true;
	}


	/**
	 * Make sure the Dossier copied is valid.
	 *
	 * The function checks for the copied Dossier if the children are copied correctly.
	 * The children here refers to Article and Image and the both has to be copied with
	 * reference only.
	 *
	 * The PublishForm existence in the copied Dossier is checked, there should not be any
	 * PublishForm in the Dossier as this copied Dossier is targeted to a Print Channel.
	 *
	 * The copied Dossier Targets is also verified to make sure that it is targeted to a
	 * Print Channel.
	 *
	 * @return bool
	 */
	private function verifyCopiedDossierWithoutForm()
	{
		$result = true;
		// Verify copied Dossier's relations.
		if( $this->copiedDossierWithoutForm->Relations ) {
			$articleFound = false;
			$imageFound = false;
			foreach( $this->copiedDossierWithoutForm->Relations as $relation ) {
				if( $relation->Type == 'Contained' ) {
					switch( $relation->ChildInfo->Type ) {
						case 'Article':
							$articleId = $this->article->MetaData->BasicMetaData->ID;
							if( $relation->Child != $articleId ) {
								$this->setResult( 'ERROR', 'Article in the new copied Dossier was not copied by reference.' );
								$result = false;
							} else {
								$articleFound = true;
							}
							break;
						case 'Image';
							$imageId = $this->image->MetaData->BasicMetaData->ID;
							if( $relation->Child != $imageId ) {
								$this->setResult( 'ERROR', 'Image in the new copied Dossier was not copied by reference.' );
								$result = false;
							} else {
								$imageFound = true;
							}
							break;
						case 'PublishForm';
							$this->setResult( 'ERROR', 'PublishForm is found in the new copied Dossier .' .
									'A "print" Target was assigned to the new copied Dossier, therefore the Form should '.
									'not be in the new Dossier.' );
								$result = false;
							break;
					}
				}
			}
			if( !$articleFound || !$imageFound ) {
				$this->setResult( 'ERROR', 'Article or Image are not found in the copied Dossier, which is wrong.' );
				$result = false;
			}


			// Make sure the Copied Dossier has the correct Print Target.
			$copiedDossierTargets = $this->copiedDossierWithoutForm->Targets;
			if( count( $copiedDossierTargets ) > 1 ) {
				$this->setResult( 'ERROR', 'The copied Dossier has more than one Target assigned to the Dossier, ' .
					'which is unexpected. Only one Print Target is expected.' );
				$result = false;
			} else {
			    require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			    $phpCompare = new WW_Utils_PhpCompare();
				$phpCompare->initCompare( array()); // Object properties that will not be compared
				if( !$phpCompare->compareTwoObjects( $this->printTarget, $copiedDossierTargets[0] ) ) {
					$this->setResult( 'ERROR', 'The copied Dossier has invalid Targets returned. ' .
						print_r( $phpCompare->getErrors(),1), $this->tipMsg );
					$result = false;
				}
			}

		}
		return $result;

	}

}