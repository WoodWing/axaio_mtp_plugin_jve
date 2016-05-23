<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';
class WW_TestSuite_BuildTest_Analytics_AnalyticsPubEntEvent_TestCase extends TestCase
{
	private $vars = null;
	private $ticket = null;
	private $utils = null;
	private $anaUtils = null;
	private $dossier1 = null;
	private $image1 = null;
	private $image2 = null;
	private $article1 = null;
	private $article2 = null;
	private $childrenObjsOfDossier = null;
	private $dossierIsPublished  = false; // To track if the Dossier has been published or not.

	public function getDisplayName() { return 'Publishing and EnterpriseEvent Job Type'; }
	public function getTestGoals()   { return 'Checks if Publishing actions create EnterpriseEvent Server Jobs.'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<li>Creates a dossier.</li>
		<li>Place several objects (Images and Articles) into the dossier.</li>
		<li>Publish the Dossier and checks if EnterpriseEvent Server Job  is created for the "contained" objects.</li>
		<li>Update the Dossier and checks if EnterpriseEvent Server Job  is created for the "contained" objects.</li>
		<li>Unpublish the Dossier and checks if EnterpriseEvent Server Job  is created for the "contained" objects.</li>
		</ol>'; }
	public function getPrio() {	return 4; }

	/**
	 * Executes several tests to that EnterpriseEvent job type is created when publishing events take place..
	 */
	final public function runTest()
	{
		require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Analytics/AnalyticsUtils.class.php';
		$this->anaUtils = new AnalyticsUtils();
		if( !$this->anaUtils->initTest( $this ) ) {
			return;
		}

		require_once BASEDIR. '/server/interfaces/services/adm/DataClasses.php';
		$this->vars = $this->getSessionVariables();
		$this->ticket        = @$this->vars['BuildTest_Analytics']['ticket'];

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		if( $this->setUpTestCase() ) {
			do {
				$this->anaUtils->emptyServerJobsQueue(); // Clear the queue as we are only interested in the jobs created through the Publishing events.
				if( !$this->callPublishing())                             { break; }
				if( !$this->validateJobsCreated( 'PublishDossiers' ))     { break; }
				sleep(2); // Drag some time before doing update publish, otherwise the both published and update time will be the same. This will lead to unexpected behavior due to unrealistic scenario.

				$this->anaUtils->emptyServerJobsQueue(); // Clear the queue as we are only interested in the jobs created through the Publishing events.
				if( !$this->callUpdatePublishing())                       { break; }
				if( !$this->validateJobsCreated( 'UpdateDossiers' ))      { break; }
				sleep(2); // Drag some time before doing unpublish, otherwise the both update and unpublish time will be the same. This will lead to unexpected behavior due to unrealistic scenario.

				$this->anaUtils->emptyServerJobsQueue(); // Clear the queue as we are only interested in the jobs created through the Publishing events.
				if( !$this->callUnPublishing())                           { break; }
				if( !$this->validateJobsCreated( 'UnPublishDossiers' ))   { break; }

			} while ( false );
		}

		$this->tearDownTestCase();
	}

	/**
	 * Setup the test data and configurations needed for this build test.
	 *
	 * @return bool True when the setup is successful, False otherwise.
	 */
	private function setupTestCase()
	{
		do {
			$retVal = true;

			$this->dossier1 = $this->anaUtils->createDossierObject( 'Create Dossier 1', 'dossierAnaPubEvent1-' . $this->anaUtils->getUniqueTimeStamp() );
			if( is_null( $this->dossier1 )) {
				$retVal = false;
				break;
			}

			// Image 1
			$relation = $this->composeContainedRelationWithDossier();
			$this->image1 = $this->anaUtils->createImageObject( 'Create image 1.', 'imgAnaPubEvent1-' . $this->anaUtils->getUniqueTimeStamp(),
								array( $relation) );
			if( is_null( $this->image1 )) {
				$retVal = false;
				break;
			}
			// Remember the child object for validation later.
			$id = $this->image1->MetaData->BasicMetaData->ID;
			$this->childrenObjsOfDossier[$id] = true;

			// Image 2
			$relation = $this->composeContainedRelationWithDossier();
			$this->image2 = $this->anaUtils->createImageObject( 'Create image 2.', 'imgAnaPubEvent2-' . $this->anaUtils->getUniqueTimeStamp(),
				array( $relation) );
			if( is_null( $this->image2 )) {
				$retVal = false;
				break;
			}
			// Remember the child object for validation later.
			$id = $this->image2->MetaData->BasicMetaData->ID;
			$this->childrenObjsOfDossier[$id] = true;

			// Article 1
			$relation = $this->composeContainedRelationWithDossier();
			$this->article1 = $this->anaUtils->createArticleObject( 'Create article 1.', 'artAnaPubEvent1-' . $this->anaUtils->getUniqueTimeStamp(),
								array( $relation ));
			if( is_null( $this->article1 )) {
				$retVal = false;
				break;
			}
			// Remember the child object for validation later.
			$id = $this->article1->MetaData->BasicMetaData->ID;
			$this->childrenObjsOfDossier[$id] = true;

			// Article 2
			$relation = $this->composeContainedRelationWithDossier();
			$this->article2 = $this->anaUtils->createArticleObject( 'Create article 2.', 'artAnaPubEvent2-' . $this->anaUtils->getUniqueTimeStamp(),
				array( $relation ));
			if( is_null( $this->article2 )) {
				$retVal = false;
				break;
			}
			// Remember the child object for validation later.
			$id = $this->article2->MetaData->BasicMetaData->ID;
			$this->childrenObjsOfDossier[$id] = true;

		} while( false );

		return $retVal;
	}

	/**
	 * Delete and remove all the test data setup at {@link: setUpTestCase()}.
	 *
	 * @return bool
	 */
	private function tearDownTestCase()
	{
		$retVal = true;
		sleep(2); // Drag some time before start tearing down the objects to avoid getting unexpected behavior due to unrealistic scenarios such as published and unpublished having the same time.
		if( $this->dossierIsPublished ) {
			if( !$this->callUnPublishing()) {
				$this->setResult( 'ERROR', 'Could not unpublish dossier, this will lead to errors in deleting the '.
					'published dossier.' );
				$retVal = false;
			}
		}

		// Image 1
		if( $this->image1 ) {
			$id = $this->image1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->image1 = null;
		}

		// Image 2
		if( $this->image2 ) {
			$id = $this->image2->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->image2 = null;
		}

		// Article 1
		if( $this->article1 ) {
			$id = $this->article1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Article object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
				$retVal = false;
			}
			$this->article1 = null;
		}

		// Article 2
		if( $this->article2 ) {
			$id = $this->article2->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Article object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Article object: '.$errorReport );
				$retVal = false;
			}
			$this->article2 = null;
		}

		// Dossier 1
		if( $this->dossier1 ) {
			$id = $this->dossier1->MetaData->BasicMetaData->ID;
			$errorReport = '';
			$stepInfo = 'Tear down the Dossier object.';
			if( !$this->anaUtils->deleteObject( $id, $stepInfo, $errorReport ) ) {
				$this->setResult( 'ERROR', 'Could not tear down Dossier object: '.$errorReport );
				$retVal = false;
			}
			$this->dossier1 = null;
		}

		// Clear all the ServerJobs in the queue.
		$this->anaUtils->emptyServerJobsQueue();

		return $retVal;
	}

	/**
	 * Compose a 'Contained' Relation object.
	 *
	 * @return Relation
	 */
	private function composeContainedRelationWithDossier()
	{
		// Place the image in the Dossier.
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';
		$relation = new Relation();
		$relation->Parent               = $this->dossier1->MetaData->BasicMetaData->ID;
		$relation->Type                 = 'Contained';
		$target = $this->dossier1->Targets[0];
		$relation->Targets              = array( $target );
		return $relation;
	}

	/**
	 * Calls PublishedDossier service call.
	 *
	 * @return bool
	 */
	private function callPublishing()
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		require_once BASEDIR.'/server/services/pub/PubPublishDossiersService.class.php';
		$req = $this->pubPublishDossiersRequest();
		$resp = $this->utils->callService( $this, $req, 'PubPublishDossiers');
		if( $resp ) {
			$this->dossierIsPublished = true;
		}
		return !is_null( $resp ) ? true : false;
	}

	/**
	 * Compose PubPublishDossiersRequest object.
	 *
	 * @return PubPublishDossiersRequest
	 */
	private function pubPublishDossiersRequest()
	{
		require_once BASEDIR.'/server/services/pub/PubPublishDossiersService.class.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';

		$dossierId = $this->dossier1->MetaData->BasicMetaData->ID;
		$publishedDossiers = array();
		$publishedDossiers[] = $this->composePublishedDossier( $dossierId );

		$request = new PubPublishDossiersRequest();
		$request->Ticket               = $this->ticket;
		$request->PublishedDossiers    = $publishedDossiers;
		$request->OperationId          = NumberUtils::createGUID();

		return $request;
	}

	/**
	 * Calls UpdateDossiers service call.
	 *
	 * @return bool
	 */
	private function callUpdatePublishing()
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		require_once BASEDIR.'/server/services/pub/PubUpdateDossiersService.class.php';
		$req = $this->pubUpdateDossiersRequest();
		$resp = $this->utils->callService( $this, $req, 'PubUpdateDossiers');
		return !is_null( $resp ) ? true : false;
	}

	/**
	 * Compose PubUpdateDossiersRequest object.
	 *
	 * @return PubUpdateDossiersRequest
	 */
	private function pubUpdateDossiersRequest()
	{
		require_once BASEDIR.'/server/services/pub/PubUpdateDossiersService.class.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';

		$dossierId = $this->dossier1->MetaData->BasicMetaData->ID;
		$publishedDossiers = array();
		$publishedDossiers[] = $this->composePublishedDossier( $dossierId );

		$request = new PubUpdateDossiersRequest();
		$request->Ticket               = $this->ticket;
		$request->DossierIDs           = array( $dossierId );
		$request->PublishedDossiers    = $publishedDossiers;
		$request->OperationId          = NumberUtils::createGUID();

		return $request;
	}

	/**
	 * Calls UnPublishDossiers service call.
	 *
	 * @return bool
	 */
	private function callUnPublishing()
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';
		require_once BASEDIR.'/server/services/pub/PubUnPublishDossiersService.class.php';
		$req = $this->pubUnPublishDossiersRequest();

		$resp = $this->utils->callService( $this, $req, 'PubUnPublishDossiers');
		if( $resp ) {
			$this->dossierIsPublished = false;
		}
		return !is_null( $resp ) ? true : false;
	}

	/**
	 * Compose PubUnPublishDossiersRequest object.
	 *
	 * @return PubUnPublishDossiersRequest
	 */
	private function pubUnPublishDossiersRequest()
	{
		require_once BASEDIR . '/server/services/pub/PubUnPublishDossiersService.class.php';
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';

		$dossierId = $this->dossier1->MetaData->BasicMetaData->ID;
		$publishedDossiers = array();
		$publishedDossiers[] = $this->composePublishedDossier( $dossierId );

		$request = new PubUnPublishDossiersRequest();
		$request->Ticket               = $this->ticket;
		$request->PublishedDossiers    = $publishedDossiers;
		$request->OperationId          = NumberUtils::createGUID();

		return $request;

	}

	/**
	 * Compose PubPUblishedDossier object.
	 *
	 * @param int $dossierId
	 * @return PubPublishedDossier
	 */
	private function composePublishedDossier( $dossierId )
	{
		require_once BASEDIR.'/server/interfaces/services/pub/DataClasses.php';

		$publishTarget = new PubPublishTarget();
		$publishTarget->PubChannelID         = $this->dossier1->Targets[0]->PubChannel->Id;
		$publishTarget->IssueID              = $this->dossier1->Targets[0]->Issue->Id;

		$publishedDossier = new PubPublishedDossier();
		$publishedDossier->DossierID            = $dossierId;
		$publishedDossier->Target               = $publishTarget;

		return $publishedDossier;
	}

	/**
	 * Validates if EnterpriseEvent Server Jobs have been created after a publishing event.
	 *
	 * Function checks if each and every child objects contained in the Dossier has
	 * an EnterpriseEvent server job created in the job queue when the Dossier is
	 * published/ updated / unpublished.
	 *
	 * @param string $publishEvent The publish event name. Possible values: PublishDossiers, UpdateDossiers, UnPublishDossiers
	 * @return bool
	 */
	private function validateJobsCreated( $publishEvent )
	{
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		require_once BASEDIR . '/server/bizclasses/BizEnterpriseEvent.class.php';

		$retVal = true;
		// Clear all the jobs created in the job queue.
		$bizServerJob = new BizServerJob;
		$jobs = $bizServerJob->listJobs();

		$jobCreatedForIds = array();
		if( !$jobs ) {
			$this->setResult( 'ERROR', 'No Server Jobs found after "'.$publishEvent.'" event, which is not expected.',
				'Please check in the "'.$publishEvent.'" service call.' );
			$retVal = false;
		} else {
			foreach( $jobs as $job ) {
				BizEnterpriseEvent::unserializeJobFieldsValue( $job );
				$entEventData = $job->JobData->getData();
				$objId = $entEventData['data'][0];
				$jobCreatedForIds[$objId] = true;
			}
			if( $this->childrenObjsOfDossier ) foreach( array_keys( $this->childrenObjsOfDossier ) as $childrenObjId ) {
				if( !isset( $jobCreatedForIds[$childrenObjId] )) {
					switch( $publishEvent ) {
						case 'PublishDossiers':
							$action = 'published';
							break;
						case 'UpdateDossiers':
							$action = 'updated';
							break;
						case 'UnPublishDossiers':
							$action = 'un-published';
							break;
					}
					$this->setResult( 'ERROR', 'There is no EnterpriseEvent Server Job Type found for Child object (id='.
						$childrenObjId. ') contained in the Dossier (id='.$this->dossier1->MetaData->BasicMetaData->ID.
						')  that has been '.$action.'.' );
					$retVal = false;
				}
			}
		}
		return $retVal;
	}
}