<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';
class WW_TestSuite_BuildTest_Analytics_AnalyticsWflServices_TestCase extends TestCase
{
	private $objOrIssIds;
	private $objOrIssNames;
	private $jobsToUpdate;
	private $jobsToDelete;
	private $anaUtils;
	private $dossierArticleName;

	public function getDisplayName() { return 'Analytics Workflow Services'; }
	public function getTestGoals() { return 'Checks if Analytics workflow services are working'; }
	public function getTestMethods() { return 'Scenario:<ol>
		<li>Create a unrelated Dossier WflCreateDossier()</li>
		<li>Create an unrelated Article WflCreateArticle()</li>
		<li>Create a relation between the Dossier (Parent) and the Article (Child) WflCreateObjectRelations()</li>
		<li>Update the relation we just created WflUpdateObjectRelations()</li>
		<li>Delete the relation we just created WflDeleteObjectRelations()</li>
		<li>Delete the used Article WflDeleteArticle()</li>
		<li>Delete the used Dossier WflDeleteDossier()</li>
		<li>Create multiple Articles to test WflMultiSetObjectProperties WflCreateMultipleArticles()</li>
		<li>Set some properties of the Articles to a new value WflMultiSetObjectProperties()</li>
		<li>Delete multiple Articles after testing WflMultiSetObjectProperties WflDeleteMultipleArticles()</li>
		<li>Create an Article in a new Dossier WflCreateObjects()</li>
		<li>Set some properties of the Article to a new value WflSetObjectProperties()</li>
		<li>Get and Lock an Article, Save it and Unlock it afterwards WflGetObjects()</li>
		<li>Create a new Target WflCreateObjectTargets()</li>
		<li>Update the new Target WflUpdateObjectTargets()</li>
		<li>Delete the new Target WflDeleteObjectTargets()</li>
		<li>Delete the used Dossier and Article in one sweep WflDeleteObjects()</li>
		<li>Create a new Issue AdmCreateIssues()</li>
		<li>Modify the new Issue AdmModifyIssues()</li>
		<li>Delete the new Issue AdmDeleteIssues()</li>
		</ol>'; }
		//@TODO: Exceptions on multiple objects are giving to many problems at the moment.
		/*
		<li>Create a Fatal Exception Attempt WflCreateFatalExceptionArticleAttempt()</li>
		<li>Delete the Fatal Exception Attempt WflDeleteFatalExceptionArticleAttempt()</li>
		<li>Create a Info Exception Attempt WflCreateInfoExceptionArticleAttempt()</li>
		<li>Delete the Info Exception Attempt WflDeleteInfoExceptionArticleAttempt()</li>
		<li>Create a Fatal Exception Attempt WflCreateMultipleFatalExceptionArticleAttempts()</li>
		<li>Set some properties of the Articles to a new value WflMultiSetObjectPropertiesFatalExceptionAttempt()</li>
		<li>Delete the Fatal Exception Attempts WflDeleteMultipleFatalExceptionArticleAttempts()</li>
		<li>Create a Info Exception Attempt WflCreateMultipleInfoExceptionArticleAttempts()</li>
		<li>Set some properties of the Articles to a new value WflMultiSetObjectPropertiesInfoExceptionAttempt()</li>
		<li>Delete the Info Exception Attempts WflDeleteMultipleInfoExceptionArticleAttempts()</li>
		<li>Create a Fatal Exception Attempt AdmCreateFatalExceptionIssueAttempt()</li>
		<li>Delete the Fatal Exception Attempt AdmDeleteFatalExceptionIssueAttempt()</li>
		<li>Create a Info Exception Attempt AdmCreateInfoExceptionIssueAttempt()</li>
		<li>Delete the Info Exception Attempt AdmDeleteInfoExceptionIssueAttempt()</li>
		*/

	public function getPrio() {	return 2; }

	/**
	 * See if we can create objects, set properties, create relations, get objects, unlock objects, run serverjobs and
	 * finally delete objects. We also try to create, update and delete targets and issues.
	 */
	final public function runTest()
	{
		require_once BASEDIR . '/server/wwtest/testsuite/BuildTest/Analytics/AnalyticsUtils.class.php';
		$this->anaUtils = new AnalyticsUtils();
		if( !$this->anaUtils->initTest( $this ) ) {
			return;
		}

		$this->vars = $this->getSessionVariables();
		$this->ticket = @$this->vars['BuildTest_Analytics']['ticket'];

		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		
		// Make up a unique object name we gonna use for both the dossier and the article.
		$this->dossierArticleName = 'Analytics_TestCase_Dossier_Article_'.date('m d H i s');;

		// Run all the webservice calls

		// Create a unrelated Dossier
		$this->WflCreateDossier();
		$this->anaUtils->runServerJobs();
		// Dossier created by WflCreateDossier should output 1 file
		foreach ($this->objOrIssIds['WflCreateDossier'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateDossier');
		}
		$this->processServerJobs();

		// Create an unrelated Article
		$this->WflCreateArticle();
		$this->anaUtils->runServerJobs();
		// Article created by WflCreateArticle should output 1 file
		foreach ($this->objOrIssIds['WflCreateArticle'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateArticle');
		}
		$this->processServerJobs();

		// Create a relation between the Dossier (Parent) and the Article (Child)
		$this->WflCreateObjectRelations();
		$this->anaUtils->runServerJobs();
		// Relation created by WflCreateObjectRelations should output 1 file
		foreach ($this->objOrIssIds['WflCreateObjectRelations'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateObjectRelations');
		}
		$this->processServerJobs();

		// Update the relation we just created
		$this->WflUpdateObjectRelations();
		$this->anaUtils->runServerJobs();
		// Relation updated by WflUpdateObjectRelations should output 1 file
		foreach ($this->objOrIssIds['WflUpdateObjectRelations'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflUpdateObjectRelations');
		}
		$this->processServerJobs();

		// Delete the relation we just created
		$this->WflDeleteObjectRelations();
		$this->anaUtils->runServerJobs();
		// Relation deleted by WflDeleteObjectRelations should output 1 file
		foreach ($this->objOrIssIds['WflDeleteObjectRelations'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteObjectRelations');
		}
		$this->processServerJobs();

		// Delete the used Article
		$this->WflDeleteArticle();
		$this->anaUtils->runServerJobs();
		// Article deleted by WflDeleteArticle should output 1 file
		foreach ($this->objOrIssIds['WflDeleteArticle'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteArticle');
		}
		$this->processServerJobs();

		// Delete the used Dossier
		$this->WflDeleteDossier();
		$this->anaUtils->runServerJobs();
		// Dossier deleted by WflDeleteDossier should output 1 file
		foreach ($this->objOrIssIds['WflDeleteDossier'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteDossier');
		}
		$this->processServerJobs();

		// Create multiple Articles to test WflMultiSetObjectProperties
		$this->WflCreateMultipleArticles();
		$this->anaUtils->runServerJobs();
		// Articles created by WflCreateMultipleArticles should output 4 files
		foreach ($this->objOrIssIds['WflCreateMultipleArticles'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateMultipleArticles');
		}
		$this->processServerJobs();

		// Set some properties of the Articles to a new value
		$this->WflMultiSetObjectProperties();
		$this->anaUtils->runServerJobs();
		// Properties modified by WflMultiSetObjectProperties should output 4 files
		foreach ($this->objOrIssIds['WflMultiSetObjectProperties'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflMultiSetObjectProperties');
		}
		$this->processServerJobs();

		// Delete multiple Articles after testing WflMultiSetObjectProperties
		$this->WflDeleteMultipleArticles();
		$this->anaUtils->runServerJobs();
		// Articles deleted by WflDeleteMultipleArticles should output 4 files
		foreach ($this->objOrIssIds['WflDeleteMultipleArticles'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteMultipleArticles');
		}
		$this->processServerJobs();

		// Create an Article in a new Dossier
		$this->WflCreateObjects();
		$this->anaUtils->runServerJobs();
		// Dossier and Article created by WflCreateObjects should output 2 files
		foreach ($this->objOrIssIds['WflCreateObjects'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateObjects');
		}
		$this->processServerJobs();

		// Set some properties of the Article to a new value
		$this->WflSetObjectProperties();
		$this->anaUtils->runServerJobs();
		// Dossier and Article created by WflSetObjectProperties should output 1 file
		foreach ($this->objOrIssIds['WflSetObjectProperties'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflSetObjectProperties');
		}
		$this->processServerJobs();

		// Get and Lock an Article, Save it and Unlock it afterwards
		$this->WflGetObjects(); // This doesn't trigger any events, so we don't have to check for it.
		$this->WflSaveObjects();
		$this->anaUtils->runServerJobs();
		// Article saved by WflSaveObjects should output 1 file
		foreach ($this->objOrIssIds['WflSaveObjects'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflSaveObjects');
		}
		$this->processServerJobs();
		$this->WflUnlockObjects(); // This doesn't trigger any events, so we don't have to check for it.

		// Create a new Target
		$this->WflCreateObjectTargets();
		$this->anaUtils->runServerJobs();
		// Target created by WflCreateObjectTargets should output 1 file
		foreach ($this->objOrIssIds['WflCreateObjectTargets'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateObjectTargets');
		}
		$this->processServerJobs();

		// Update the new Target
		$this->WflUpdateObjectTargets();
		$this->anaUtils->runServerJobs();
		// Target updated by WflUpdateObjectTargets should output 1 file
		foreach ($this->objOrIssIds['WflUpdateObjectTargets'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflUpdateObjectTargets');
		}
		$this->processServerJobs();

		// Delete the new Target
		$this->WflDeleteObjectTargets();
		$this->anaUtils->runServerJobs();
		// Target deleted by WflDeleteObjectTargets should output 1 file
		foreach ($this->objOrIssIds['WflDeleteObjectTargets'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteObjectTargets');
		}
		$this->processServerJobs();

		// Delete the used Dossier and Article in one sweep
		$this->WflDeleteObjects();
		$this->anaUtils->runServerJobs();
		// Dossier and Article deleted by WflDeleteObjects should output 2 files
		foreach ($this->objOrIssIds['WflDeleteObjects'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteObjects');
		}
		$this->processServerJobs();

		// Create a new Issue
		$this->AdmCreateIssues();
		$this->anaUtils->runServerJobs();
		// Issue created by AdmCreateIssues should output 1 file
		foreach ($this->objOrIssIds['AdmCreateIssues'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmCreateIssues');
		}
		$this->processServerJobs();

		// Modify the new Issue
		$this->AdmModifyIssues();
		$this->anaUtils->runServerJobs();
		// Issue modified by AdmModifyIssues should output 1 file
		foreach ($this->objOrIssIds['AdmModifyIssues'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmModifyIssues');
		}
		$this->processServerJobs();

		// Delete the new Issue
		$this->AdmDeleteIssues();
		$this->anaUtils->runServerJobs();
		// Issue deleted by AdmDeleteIssues should output 1 file
		foreach ($this->objOrIssIds['AdmDeleteIssues'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmDeleteIssues');
		}
		$this->processServerJobs();

		// Start testing fatal and info exceptions
		// Create a Exception Attempt
		$this->WflCreateFatalExceptionArticleAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by WflCreateFatalExceptionArticleAttempt should output 1 file
		foreach ($this->objOrIssIds['WflCreateFatalExceptionArticleAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateFatalExceptionArticleAttempt');
		}
		$this->processServerJobs();

		// Create a Exception Attempt
		$this->WflDeleteFatalExceptionArticleAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by WflDeleteFatalExceptionArticleAttempt should output 1 file
		foreach ($this->objOrIssIds['WflDeleteFatalExceptionArticleAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteFatalExceptionArticleAttempt');
		}
		$this->processServerJobs();

		// Create a Exception Attempt
		$this->WflCreateInfoExceptionArticleAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by WflCreateInfoExceptionArticleAttempt should output 1 file
		foreach ($this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateInfoExceptionArticleAttempt');
		}
		$this->processServerJobs();
		// Re-run again since there're are some re-planned jobs.
		$this->anaUtils->runServerJobs();
		// Article created by WflCreateInfoExceptionArticleAttempt should output 1 file
		foreach ($this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateInfoExceptionArticleAttempt');
		}
		$this->processServerJobs(); // There's nothing to process anymore, but call it anyway to follow the pattern in the BuildTest so far.

		// Delete the Exception Attempt
		$this->WflDeleteInfoExceptionArticleAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by WflDeleteInfoExceptionArticleAttempt should output 1 file
		foreach ($this->objOrIssIds['WflDeleteInfoExceptionArticleAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteInfoExceptionArticleAttempt');
		}
		$this->processServerJobs();
		$this->anaUtils->runServerJobs();
		// Article created by WflDeleteInfoExceptionArticleAttempt should output 1 file
		foreach ($this->objOrIssIds['WflDeleteInfoExceptionArticleAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteInfoExceptionArticleAttempt');
		}
		$this->processServerJobs();

		/*
		 * @TODO: Exceptions on multiple objects are giving to many problems at the moment.
		 */
		/*
				// Create a Exception Attempt
				$this->WflCreateMultipleFatalExceptionArticleAttempts();
				$this->anaUtils->runServerJobs();
				// Articles created by WflCreateMultipleFatalExceptionArticleAttempts should output 4 files
				foreach ($this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateMultipleFatalExceptionArticleAttempts');
				}
				$this->processServerJobs();

				// Set some properties of the Articles to a new value
				$this->WflMultiSetObjectPropertiesFatalExceptionAttempt();
				$this->anaUtils->runServerJobs();
				// Properties modified by WflMultiSetObjectPropertiesFatalExceptionAttempt should output 4 files
				foreach ($this->objOrIssIds['WflMultiSetObjectPropertiesFatalExceptionAttempt'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflMultiSetObjectPropertiesFatalExceptionAttempt');
				}
				$this->processServerJobs();

				// Delete the Exception Attempts
				$this->WflDeleteMultipleFatalExceptionArticleAttempts();
				$this->anaUtils->runServerJobs();
				// Articles deleted by WflDeleteMultipleFatalExceptionArticleAttempts should output 4 files
				foreach ($this->objOrIssIds['WflDeleteMultipleFatalExceptionArticleAttempts'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteMultipleFatalExceptionArticleAttempts');
				}
				$this->processServerJobs();

				// Create a Exception Attempt
				$this->WflCreateMultipleInfoExceptionArticleAttempts();
				$this->anaUtils->runServerJobs();
				// Articles created by WflCreateMultipleInfoExceptionArticleAttempts should output 4 files
				foreach ($this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflCreateMultipleInfoExceptionArticleAttempts');
				}
				$this->processServerJobs();

				// Set some properties of the Articles to a new value
				$this->WflMultiSetObjectPropertiesInfoExceptionAttempt();
				$this->anaUtils->runServerJobs();
				// Properties modified by WflMultiSetObjectPropertiesInfoExceptionAttempt should output 4 files
				foreach ($this->objOrIssIds['WflMultiSetObjectPropertiesInfoExceptionAttempt'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflMultiSetObjectPropertiesInfoExceptionAttempt');
				}
				$this->processServerJobs();

				// Delete the Exception Attempts
				$this->WflDeleteMultipleInfoExceptionArticleAttempts();
				$this->anaUtils->runServerJobs();
				// Articles deleted by WflDeleteMultipleInfoExceptionArticleAttempts should output 4 files
				foreach ($this->objOrIssIds['WflDeleteMultipleInfoExceptionArticleAttempts'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteMultipleInfoExceptionArticleAttempts');
				}
				$this->processServerJobs();
				// Because we replanned a job, run it again and check the result.
				$this->anaUtils->runServerJobs();
				// Articles deleted by WflDeleteMultipleInfoExceptionArticleAttempts should output 4 files
				foreach ($this->objOrIssIds['WflDeleteMultipleInfoExceptionArticleAttempts'] as $objectId) {
					$this->checkProcessDataResults($objectId, 'ObjectEvent', 'WflDeleteMultipleInfoExceptionArticleAttempts');
				}
				$this->processServerJobs();
		*/

		// Create a Exception Attempt
		$this->AdmCreateFatalExceptionIssueAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by AdmCreateFatalExceptionIssueAttempt should output 1 file
		foreach ($this->objOrIssIds['AdmCreateFatalExceptionIssueAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmCreateFatalExceptionIssueAttempt');
		}
		$this->processServerJobs();

		// Delete the Exception Attempt
		$this->AdmDeleteFatalExceptionIssueAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by AdmDeleteFatalExceptionIssueAttempt should output 1 file
		foreach ($this->objOrIssIds['AdmDeleteFatalExceptionIssueAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmDeleteFatalExceptionIssueAttempt');
		}
		$this->processServerJobs();

		// Create a Exception Attempt
		$this->AdmCreateInfoExceptionIssueAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by AdmCreateInfoExceptionIssueAttempt should output 1 file
		foreach ($this->objOrIssIds['AdmCreateInfoExceptionIssueAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmCreateInfoExceptionIssueAttempt');
		}
		$this->processServerJobs();
		// Because we replanned a job, run it again and check the result.
		$this->anaUtils->runServerJobs();
		foreach ($this->objOrIssIds['AdmCreateInfoExceptionIssueAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmCreateInfoExceptionIssueAttempt');
		}
		$this->processServerJobs();

		// Delete the Exception Attempt
		$this->AdmDeleteInfoExceptionIssueAttempt();
		$this->anaUtils->runServerJobs();
		// Article created by AdmDeleteInfoExceptionIssueAttempt should output 1 file
		foreach ($this->objOrIssIds['AdmDeleteInfoExceptionIssueAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmDeleteInfoExceptionIssueAttempt');
		}
		$this->processServerJobs();
		// Because we replanned a job, run it again and check the result.
		$this->anaUtils->runServerJobs();
		foreach ($this->objOrIssIds['AdmDeleteInfoExceptionIssueAttempt'] as $objectId) {
			$this->checkProcessDataResults($objectId, 'IssueEvent', 'AdmDeleteInfoExceptionIssueAttempt');
		}
		$this->processServerJobs();

		// Clean up everything in case the BuildTest didn't run successfully.
		$this->anaUtils->emptyServerJobsQueue();
		$this->anaUtils->clearAnaDir();
	}

	/**
	 * After an exception has been taken care of, we need to either retry the job in case the status is REPLANNED,
	 * or delete it in case the status is FATAL.
	 */
	private function processServerJobs()
	{
		try {
			require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';

			$bizServerJob = new BizServerJob();

			// REPLANNED jobs need to be updated so they get another try
			if( $this->jobsToUpdate ) {
				foreach ( $this->jobsToUpdate as $job ) {
					$bizServerJob->updateJob($job);
				}
				$this->jobsToUpdate = null; // reset otherwise it will lead to undefined / not found job when the jobs has already been updated!
			}

			// FATAL jobs need to be deleted since we no longer need them
			if( $this->jobsToDelete ) {
				foreach ( $this->jobsToDelete as $job ) {
					$bizServerJob->deleteJob($job);
				}
				$this->jobsToDelete = null; // reset otherwise it will lead to undefined / not found job when the jobs has already been deleted!
			}

			// Also clear all the server job types on hold. Otherwise the jobs aren't executed.
			$dbJobTypeOnHold = new DBServerJobTypesOnHold();
			$dbJobTypeOnHold->deleteExpiredJobTypesOnHold(time()+(24*60*60)); // Delete everything older than 24 hours from now (this will clear everything)
		} catch (BizException $e) {
			$this->setResult( 'ERROR', 'Could not process Server Jobs: '.$e->getDetail(), $e->getMessage());
		}
	}

	/**
	 * Check if the connector has been called and the prepareData() and processData() are executed.
	 *
	 * @param int objectId ID of object that's been handled by the ServerJob methods
	 * @param string $eventType Either ObjectEvent or IssueEvent.
	 * @params string $serviceName Workflow service name.
	 */
	private function checkProcessDataResults($objectId, $eventType, $serviceName)
	{
		require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
		require_once BASEDIR . '/server/bizclasses/BizServerJob.class.php';
		require_once BASEDIR . '/server/bizclasses/BizEnterpriseEvent.class.php';
		require_once BASEDIR . '/server/utils/PhpCompare.class.php';

		// We need the name to check which form of processData we used.
		$objectName = $this->objOrIssNames[$objectId];

		// File created by the processData method in the connector
		if (stripos($objectName, 'fatal') !== false ||
			stripos($objectName, 'info') !== false) {

			// Exception was created while handling multiple objects
			if (stripos($objectName, 'multi') !== false) {
				$file = TEMPDIRECTORY.'/Ana/AnalyticsTest_'.$eventType.'_processMultiObjectsData_'.$objectId.'_exception.txt';
			} else {
				$file = TEMPDIRECTORY.'/Ana/AnalyticsTest_'.$eventType.'_processData_'.$objectId.'_exception.txt';
			}
		} else {

			// ServerJob was processed while handling multiple objects
			if (stripos($objectName, 'multi') !== false) {
				$file = TEMPDIRECTORY.'/Ana/AnalyticsTest_'.$eventType.'_processMultiObjectsData_'.$objectId.'.txt';
			} else {
				$file = TEMPDIRECTORY.'/Ana/AnalyticsTest_'.$eventType.'_processData_'.$objectId.'.txt';
			}
		}

		// It should be there, otherwise something is wrong
		if (file_exists( $file )) {

			$content = file_get_contents( $file );
			$content = unserialize( $content );

			if (stripos($objectName, 'fatal') !== false ||
				stripos($objectName, 'info') !== false) {

				// We can not query ServerJobs for a single record, we have to get a list and parse it
				$bizServerJob = new BizServerJob;
				$status = ServerJobStatus::COMPLETED;

				if (stripos($objectName, 'fatal') !== false) {
					$status = ServerJobStatus::FATAL;
				} elseif (stripos($objectName, 'info') !== false) {
					$status = ServerJobStatus::REPLANNED;
				}
				$jobs = $bizServerJob->listJobs(array('jobstatus' => $status));

				// If the job disappeared in the meanwhile, something went wrong
				if (count($jobs) < 1) {
					$errorMsg = 'EnterpriseEvent ServerJob is not found.';
					$this->setResult( 'ERROR', $errorMsg, 'Error occured in checkProcessDataResult,
						'.$serviceName.' does not have any ServerJobs to check.');
					return;
				}

				$foundJob = false;
				foreach ($jobs as $job) {
					// Unserialize it, so we can compare the content of the data with the content of the file
					BizEnterpriseEvent::unserializeJobFieldsValue($job);

					$jobData = $job->JobData->getData();
					$phpCompare = new WW_Utils_PhpCompare();
					$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
					if ($phpCompare->compareTwoProps( $jobData['data'][0], $content ) ) {

						$foundJob = $job;
						break;
					}
				}

				// The job either disappeared or has problems to be found properly
				if (!$foundJob) {
					$errorMsg = 'Incorrect JobData saved for EnterpriseEvent.';
					$this->setResult( 'ERROR', $errorMsg, 'Error occurred in checkProcessDataResult,
						'.$serviceName.' does not get the same object back from the ServerJob.');
					return;
				} else {

					// Job is found, checked etc. Now we need to do a last attempt to process it or clean it up
					if (stripos($objectName, 'fatal') !== false) {

						// Fatal jobs need to be cleaned up
						$this->jobsToDelete[] = $foundJob->JobId;
					} elseif (stripos($objectName, 'info') !== false) {

						$jobData = $foundJob->JobData->getData();
						// Since we check on the name to see how the job needs to be processed, we have to rename
						// the object, otherwise it stays in a loop which is not desired and not expected.
						if (stripos($objectName, 'article') !== false) {
							$jobData['data'][0]->MetaData->BasicMetaData->Name = 'Processed Article';
							$this->objOrIssIds['Processed Article'] = $this->objOrIssIds[$serviceName];
							$this->objOrIssNames[$objectId] = $jobData['data'][0]->MetaData->BasicMetaData->Name;
						} elseif (stripos($objectName, 'issue') !== false) {
							$jobData['data'][0]->Name = 'Processed Issue';
							$this->objOrIssIds['Processed Issue'] = $this->objOrIssIds[$serviceName];
							$this->objOrIssNames[$objectId] = $jobData['data'][0]->Name;
						}

						$foundJob->JobData->setData( $jobData );
						BizEnterpriseEvent::serializeJobFieldsValue( $foundJob );

						// Replanned jobs need another try to clean up the queue.
						// We changed the name, so it should pass this time.
						$this->jobsToUpdate[] = $foundJob;
					}
				}
			} else {
				// This property should be set in AnalyticsTest_<$eventType>->prepareData()
				// It's only set on objects, not on arrays
				if (is_object($content) && !isset($content->DummyProperty)) {

					$errorMsg = 'Dummy property	is not found';
					$this->setResult( 'ERROR', $errorMsg, 'Error occured in checkProcessDataResult,
						'.$serviceName.' did not produce a dummy property');
					return;
				};
			}

			// Clean up the file after the check so we don't clog the temp directory
			if (!unlink($file)) {

				$errorMsg = 'File '.$file.'	could not be unlinked.';
				$this->setResult( 'ERROR', $errorMsg, 'Error occurred in checkProcessDataResult,
					'.$serviceName.' did not produce a file that can be unlinked.');
				return;
			}
		} else {

			$errorMsg = 'Temporary file '.$file.' could not be found.';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in checkProcessDataResult,
					'.$serviceName.' did not produce a temporary file that can be found.');
			return;
		}
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 *
	 */
	private function WflDeleteDossier()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteDossierRequest();
		$recResp = $this->WflDeleteDossierResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflDeleteDossier');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteDossier'] = $curResp->IDs;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteDossier response.');
			return;
		}
	}

	/**
	 * Compose WflDeleteObjectsRequest.
	 *
	 * @return WflDeleteObjectsRequest
	 */
	private function WflDeleteDossierRequest()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $this->objOrIssIds['WflCreateDossier'];
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->Context = null;
		return $request;
	}

	/**
	 * Compose WflDeleteObjectsResponse object to compare the test response.
	 *
	 * @return WflDeleteObjectsResponse
	 */
	private function WflDeleteDossierResponse()
	{
		$response = new WflDeleteObjectsResponse();
		$response->IDs = $this->objOrIssIds['WflCreateDossier'];
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 */
	private function WflDeleteArticle()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteArticleRequest( $this->objOrIssIds['WflCreateArticle'] );
		$recResp = $this->WflDeleteArticleResponse( $this->objOrIssIds['WflCreateArticle'] );
		$curResp = $this->utils->callService( $this, $req, 'WflDeleteObjects');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteArticle'] = $curResp->IDs;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjects response.');
			return;
		}
	}

	/**
	 * Compose WflDeleteObjectsRequest.
	 *
	 * @param string[] $objectIds List of object ids to be deleted.
	 * @return WflDeleteObjectsRequest
	 */
	private function WflDeleteArticleRequest( $objectIds )
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $objectIds;
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->Context = null;
		return $request;
	}

	/**
	 * Compose WflDeleteObjectsResponse object to compare the test response.
	 *
	 * @param string[] $objectIds List of object ids to be deleted.
	 * @return WflDeleteObjectsResponse
	 */
	private function WflDeleteArticleResponse( $objectIds )
	{
		$response = new WflDeleteObjectsResponse();
		$response->IDs = $objectIds;
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 */
	private function WflDeleteFatalExceptionArticleAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteArticleRequest( $this->objOrIssIds['WflCreateFatalExceptionArticleAttempt'] );
		$recResp = $this->WflDeleteArticleResponse( $this->objOrIssIds['WflCreateFatalExceptionArticleAttempt'] );

		// Set value to ID's we want to delete.
		$req->IDs = $this->objOrIssIds['WflCreateFatalExceptionArticleAttempt'];
		$recResp->IDs = $this->objOrIssIds['WflCreateFatalExceptionArticleAttempt'];

		$curResp = $this->utils->callService( $this, $req, 'WflDeleteFatalExceptionArticleAttempt');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteFatalExceptionArticleAttempt'] = $curResp->IDs;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteFatalExceptionArticleAttempt response.');
			return;
		}
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 */
	private function WflDeleteInfoExceptionArticleAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteArticleRequest( $this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'] );
		$recResp = $this->WflDeleteArticleResponse( $this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'] );

		// Set value to ID's we want to delete.
		$req->IDs = $this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'];
		$recResp->IDs = $this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'];

		$curResp = $this->utils->callService( $this, $req, 'WflDeleteInfoExceptionArticleAttempt');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteInfoExceptionArticleAttempt'] = $curResp->IDs;
			$this->objOrIssNames[$curResp->IDs[0]] = 'Analytics Info Exception Article Attempt';
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteInfoExceptionArticleAttempt response.');
			return;
		}
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 */
	private function WflDeleteObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteObjectsRequest();
		$recResp = $this->WflDeleteObjectsResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflDeleteObjects');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteObjects'] = $curResp->IDs;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjects response.');
			return;
		}
	}

	/**
	 * Compose WflDeleteObjectsRequest.
	 *
	 * @return WflDeleteObjectsRequest
	 */
	private function WflDeleteObjectsRequest()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $this->objOrIssIds['WflCreateObjects'];
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->Context = null;
		return $request;
	}

	/**
	 * Compose WflDeleteObjectsResponse object to compare the test response.
	 *
	 * @return WflDeleteObjectsResponse
	 */
	private function WflDeleteObjectsResponse()
	{
		$response = new WflDeleteObjectsResponse();
		$response->IDs = $this->objOrIssIds['WflCreateObjects'];
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 *
	 */
	private function WflDeleteMultipleArticles()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteMultipleArticlesRequest();
		$recResp = $this->WflDeleteMultipleArticlesResponse();

		$req->Objects = array();
		$recResp->Objects = array();
		for( $counter=1; $counter<=4; $counter++ ) {
			$metaData = new MetaData();
			$metaData->BasicMetaData = new BasicMetaData();
			$metaData->BasicMetaData->Name = 'Analytics Articles '. $counter;
			$req->Objects[] = $metaData;
			$recResp->Objects[] = $metaData;
		}

		$curResp = $this->utils->callService( $this, $req, 'WflDeleteMultipleArticles');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteMultipleArticles'] = $curResp->IDs;
		}

		foreach ($this->objOrIssIds['WflDeleteMultipleArticles'] as $objectId) {
			if (isset($this->objOrIssNames[$objectId])) {
				$this->objOrIssNames[$objectId] = str_ireplace('Analytics Multi ', 'Analytics ', $this->objOrIssNames[$objectId]);
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteMultipleArticles response.');
			return;
		}
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 */
	private function WflDeleteMultipleFatalExceptionArticleAttempts()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteMultipleArticlesRequest();
		$recResp = $this->WflDeleteMultipleArticlesResponse();

		// Set value to ID's we want to delete.
		$req->IDs = $this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'];
		$recResp->IDs = $this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'];

		$curResp = $this->utils->callService( $this, $req, 'WflDeleteMultipleFatalExceptionArticleAttempts');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteMultipleFatalExceptionArticleAttempts'] = $curResp->IDs;
		}

		foreach ($this->objOrIssIds['WflDeleteMultipleFatalExceptionArticleAttempts'] as $objectId) {
			if (isset($this->objOrIssNames[$objectId])) {
				$this->objOrIssNames[$objectId] = str_ireplace('Analytics Multi ', 'Analytics ', $this->objOrIssNames[$objectId]);
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteMultipleFatalExceptionArticleAttempts response.');
			return;
		}
	}

	/**
	 * Access the WflDeleteObjects service and compare the response
	 *
	 */
	private function WflDeleteMultipleInfoExceptionArticleAttempts()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->WflDeleteMultipleArticlesRequest();
		$recResp = $this->WflDeleteMultipleArticlesResponse();

		// Set value to ID's we want to delete.
		$req->IDs = $this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'];
		$recResp->IDs = $this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'];

		$curResp = $this->utils->callService( $this, $req, 'WflDeleteMultipleInfoExceptionArticleAttempts');

		if ( isset($curResp->IDs) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteMultipleInfoExceptionArticleAttempts'] = $curResp->IDs;
		}

		foreach ($this->objOrIssIds['WflDeleteMultipleInfoExceptionArticleAttempts'] as $objectId) {
			if (isset($this->objOrIssNames[$objectId])) {
				$this->objOrIssNames[$objectId] = str_ireplace('Analytics Multi ', 'Analytics ', $this->objOrIssNames[$objectId]);
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '023' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '023' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteMultipleInfoExceptionArticleAttempts response.');
			return;
		}
	}

	/**
	 * Compose WflDeleteObjectsRequest.
	 *
	 * @return WflDeleteObjectsRequest
	 */
	private function WflDeleteMultipleArticlesRequest()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $this->objOrIssIds['WflCreateMultipleArticles'];
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->Context = null;
		return $request;
	}

	/**
	 * Compose WflDeleteObjectsResponse object to compare the test response.
	 *
	 * @return WflDeleteObjectsResponse
	 */
	private function WflDeleteMultipleArticlesResponse()
	{
		$response = new WflDeleteObjectsResponse();
		$response->IDs = $this->objOrIssIds['WflCreateMultipleArticles'];
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflSetObjectProperties service and compare the response
	 *
	 */
	private function WflSetObjectProperties()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
		$req = $this->WflSetObjectPropertiesRequest();
		$recResp = $this->WflSetObjectPropertiesResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflSetObjectProperties');

		if ( isset($curResp->MetaData->BasicMetaData->ID) ) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflSetObjectProperties'][] = (int) $curResp->MetaData->BasicMetaData->ID;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '019' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '019' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflSetObjectProperties response.');
			return;
		}
	}

	/**
	 * Compose WflSetObjectPropertiesRequest.
	 *
	 * @return WflSetObjectPropertiesRequest
	 */
	private function WflSetObjectPropertiesRequest()
	{
		$request = new WflSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->ID = $this->objOrIssIds['WflCreateObjects'][0];
		$request->MetaData = new MetaData();
		$request->MetaData->BasicMetaData = new BasicMetaData();
		$request->MetaData->BasicMetaData->ID = $this->objOrIssIds['WflCreateObjects'][0];
		$request->MetaData->BasicMetaData->DocumentID = 'xmp.did:c639ec8c-43fb-4812-96ae-52f1874ee8dc';
		$request->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$request->MetaData->BasicMetaData->Type = 'Article';
		$request->MetaData->BasicMetaData->Publication = new Publication();
		$request->MetaData->BasicMetaData->Publication->Id = '1';
		$request->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->MetaData->BasicMetaData->Category = new Category();
		$request->MetaData->BasicMetaData->Category->Id = '1';
		$request->MetaData->BasicMetaData->Category->Name = 'News';
		$request->MetaData->BasicMetaData->ContentSource = null;
		$request->MetaData->RightsMetaData = new RightsMetaData();
		$request->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->MetaData->RightsMetaData->Copyright = '';
		$request->MetaData->RightsMetaData->CopyrightURL = '';
		$request->MetaData->SourceMetaData = new SourceMetaData();
		$request->MetaData->SourceMetaData->Credit = '';
		$request->MetaData->SourceMetaData->Source = '';
		$request->MetaData->SourceMetaData->Author = '';
		$request->MetaData->ContentMetaData = new ContentMetaData();
		$request->MetaData->ContentMetaData->Description = 'Article is altered by SetObjectProperties';
		$request->MetaData->ContentMetaData->DescriptionAuthor = '';
		$request->MetaData->ContentMetaData->Keywords = array();
		$request->MetaData->ContentMetaData->Slugline = '';
		$request->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->MetaData->ContentMetaData->Columns = 0;
		$request->MetaData->ContentMetaData->Width = 0;
		$request->MetaData->ContentMetaData->Height = 0;
		$request->MetaData->ContentMetaData->Dpi = 0;
		$request->MetaData->ContentMetaData->LengthWords = 0;
		$request->MetaData->ContentMetaData->LengthChars = 0;
		$request->MetaData->ContentMetaData->LengthParas = 3;
		$request->MetaData->ContentMetaData->LengthLines = 0;
		$request->MetaData->ContentMetaData->PlainContent = '';
		$request->MetaData->ContentMetaData->FileSize = 160967;
		$request->MetaData->ContentMetaData->ColorSpace = '';
		$request->MetaData->ContentMetaData->HighResFile = '';
		$request->MetaData->ContentMetaData->Encoding = '';
		$request->MetaData->ContentMetaData->Compression = '';
		$request->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->MetaData->ContentMetaData->Channels = '';
		$request->MetaData->ContentMetaData->AspectRatio = '';
		$request->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->MetaData->WorkflowMetaData->Deadline = null;
		$request->MetaData->WorkflowMetaData->Urgency = '';
		$request->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$request->MetaData->WorkflowMetaData->Modified = date('Y-m-d\TH:i:s');
		$request->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$request->MetaData->WorkflowMetaData->Created = null;
		$request->MetaData->WorkflowMetaData->Comment = '';
		$request->MetaData->WorkflowMetaData->State = new State();
		$request->MetaData->WorkflowMetaData->State->Id = '1';
		$request->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->MetaData->WorkflowMetaData->State->Type = '';
		$request->MetaData->WorkflowMetaData->State->Produce = null;
		$request->MetaData->WorkflowMetaData->State->Color = null;
		$request->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->MetaData->WorkflowMetaData->RouteTo = '';
		$request->MetaData->WorkflowMetaData->LockedBy = '';
		$request->MetaData->WorkflowMetaData->Version = '0.1';
		$request->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->MetaData->WorkflowMetaData->Rating = 0;
		$request->MetaData->WorkflowMetaData->Deletor = null;
		$request->MetaData->WorkflowMetaData->Deleted = null;
		$request->MetaData->ExtraMetaData = array();
		$request->Targets = array();
		return $request;
	}

	/**
	 * Compose WflSetObjectPropertiesResponse object to compare the test response
	 *
	 * @return WflSetObjectPropertiesResponse
	 */
	private function WflSetObjectPropertiesResponse()
	{
		$response = new WflSetObjectPropertiesResponse();
		$response->MetaData = new MetaData();
		$response->MetaData->BasicMetaData = new BasicMetaData();
		$response->MetaData->BasicMetaData->ID = '180108500';
		$response->MetaData->BasicMetaData->DocumentID = 'xmp.did:c639ec8c-43fb-4812-96ae-52f1874ee8dc';
		$response->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$response->MetaData->BasicMetaData->Type = 'Article';
		$response->MetaData->BasicMetaData->Publication = new Publication();
		$response->MetaData->BasicMetaData->Publication->Id = '1';
		$response->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->MetaData->BasicMetaData->Category = new Category();
		$response->MetaData->BasicMetaData->Category->Id = '1';
		$response->MetaData->BasicMetaData->Category->Name = 'News';
		$response->MetaData->BasicMetaData->ContentSource = '';
		$response->MetaData->RightsMetaData = new RightsMetaData();
		$response->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->MetaData->RightsMetaData->Copyright = '';
		$response->MetaData->RightsMetaData->CopyrightURL = '';
		$response->MetaData->SourceMetaData = new SourceMetaData();
		$response->MetaData->SourceMetaData->Credit = '';
		$response->MetaData->SourceMetaData->Source = '';
		$response->MetaData->SourceMetaData->Author = '';
		$response->MetaData->ContentMetaData = new ContentMetaData();
		$response->MetaData->ContentMetaData->Description = 'Article is altered by SetObjectProperties';
		$response->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->MetaData->ContentMetaData->Keywords = array();
		$response->MetaData->ContentMetaData->Slugline = '';
		$response->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->MetaData->ContentMetaData->Columns = '0';
		$response->MetaData->ContentMetaData->Width = '0';
		$response->MetaData->ContentMetaData->Height = '0';
		$response->MetaData->ContentMetaData->Dpi = '0';
		$response->MetaData->ContentMetaData->LengthWords = '0';
		$response->MetaData->ContentMetaData->LengthChars = '0';
		$response->MetaData->ContentMetaData->LengthParas = '3';
		$response->MetaData->ContentMetaData->LengthLines = '0';
		$response->MetaData->ContentMetaData->PlainContent = '';
		$response->MetaData->ContentMetaData->FileSize = '160967';
		$response->MetaData->ContentMetaData->ColorSpace = '';
		$response->MetaData->ContentMetaData->HighResFile = '';
		$response->MetaData->ContentMetaData->Encoding = '';
		$response->MetaData->ContentMetaData->Compression = '';
		$response->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->MetaData->ContentMetaData->Channels = '';
		$response->MetaData->ContentMetaData->AspectRatio = '';
		$response->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->MetaData->WorkflowMetaData->Deadline = null;
		$response->MetaData->WorkflowMetaData->Urgency = '';
		$response->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->MetaData->WorkflowMetaData->Modified = '2014-04-14T11:47:07';
		$response->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->MetaData->WorkflowMetaData->Created = '2014-04-14T11:47:07';
		$response->MetaData->WorkflowMetaData->Comment = '';
		$response->MetaData->WorkflowMetaData->State = new State();
		$response->MetaData->WorkflowMetaData->State->Id = '1';
		$response->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->MetaData->WorkflowMetaData->State->Produce = null;
		$response->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->MetaData->WorkflowMetaData->RouteTo = '';
		$response->MetaData->WorkflowMetaData->LockedBy = '';
		$response->MetaData->WorkflowMetaData->Version = '0.1';
		$response->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->MetaData->WorkflowMetaData->Rating = '0';
		$response->MetaData->WorkflowMetaData->Deletor = '';
		$response->MetaData->WorkflowMetaData->Deleted = null;
		$response->MetaData->ExtraMetaData = array();
		$response->Targets = array();
		return $response;
	}

	/**
	 * Access the WflMultiSetObjectProperties service and compare the response
	 */
	private function WflMultiSetObjectPropertiesFatalExceptionAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$req = $this->WflMultiSetObjectPropertiesRequest();
		$recResp = $this->WflMultiSetObjectPropertiesResponse();

		$req->IDs = $this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'];
		$recResp->IDs = $this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'];

		$curResp = $this->utils->callService( $this, $req, 'WflMultiSetObjectPropertiesFatalExceptionAttempt');

		// Keep track of used artifacts so we can tear down the process
		$this->objOrIssIds['WflMultiSetObjectPropertiesFatalExceptionAttempt'] = $this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'];

		foreach ($this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'] as $objectId) {
			if (isset($this->objOrIssNames[$objectId])) {
				$this->objOrIssNames[$objectId] = str_ireplace('Analytics ', 'Analytics Multi ', $this->objOrIssNames[$objectId]);
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '070' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '070' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflMultiSetObjectPropertiesFatalExceptionAttempt response.');
			return;
		}
	}

	/**
	 * Access the WflMultiSetObjectProperties service and compare the response
	 */
	private function WflMultiSetObjectPropertiesInfoExceptionAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$req = $this->WflMultiSetObjectPropertiesRequest();
		$recResp = $this->WflMultiSetObjectPropertiesResponse();

		$req->IDs = $this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'];
		$recResp->IDs = $this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'];

		$curResp = $this->utils->callService( $this, $req, 'WflMultiSetObjectPropertiesInfoExceptionAttempt');

		// Keep track of used artifacts so we can tear down the process
		$this->objOrIssIds['WflMultiSetObjectPropertiesInfoExceptionAttempt'] = $this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'];

		foreach ($this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'] as $objectId) {
			if (isset($this->objOrIssNames[$objectId])) {
				$this->objOrIssNames[$objectId] = str_ireplace('Analytics ', 'Analytics Multi ', $this->objOrIssNames[$objectId]);
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '070' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '070' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflMultiSetObjectPropertiesInfoExceptionAttempt response.');
			return;
		}
	}

	/**
	 * Access the WflMultiSetObjectProperties service and compare the response
	 */
	private function WflMultiSetObjectProperties()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$req = $this->WflMultiSetObjectPropertiesRequest();
		$recResp = $this->WflMultiSetObjectPropertiesResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflMultiSetObjectProperties');

		// Keep track of used artifacts so we can tear down the process
		$this->objOrIssIds['WflMultiSetObjectProperties'] = $this->objOrIssIds['WflCreateMultipleArticles'];

		foreach ($this->objOrIssIds['WflMultiSetObjectProperties'] as $objectId) {
			if (isset($this->objOrIssNames[$objectId])) {
				$this->objOrIssNames[$objectId] = str_ireplace('Analytics ', 'Analytics Multi ', $this->objOrIssNames[$objectId]);
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '070' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '070' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflMultiSetObjectProperties response.');
			return;
		}
	}

	/**
	 * Compose WflMultiSetObjectPropertiesRequest object
	 *
	 * @return WflMultiSetObjectPropertiesRequest
	 */
	private function WflMultiSetObjectPropertiesRequest()
	{
		$request = new WflMultiSetObjectPropertiesRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = $this->objOrIssIds['WflCreateMultipleArticles'];
		$request->MetaData = array();
		$request->MetaData[0] = new MetaDataValue();
		$request->MetaData[0]->Property = 'CategoryId';
		$request->MetaData[0]->Values = null;
		$request->MetaData[0]->PropertyValues = array();
		$request->MetaData[0]->PropertyValues[0] = new PropertyValue();
		$request->MetaData[0]->PropertyValues[0]->Value = '2';
		$request->MetaData[0]->PropertyValues[0]->Display = null;
		$request->MetaData[0]->PropertyValues[0]->Entity = null;
		$request->MetaData[1] = new MetaDataValue();
		$request->MetaData[1]->Property = 'StateId';
		$request->MetaData[1]->Values = null;
		$request->MetaData[1]->PropertyValues = array();
		$request->MetaData[1]->PropertyValues[0] = new PropertyValue();
		$request->MetaData[1]->PropertyValues[0]->Value = '2';
		$request->MetaData[1]->PropertyValues[0]->Display = null;
		$request->MetaData[1]->PropertyValues[0]->Entity = null;
		return $request;
	}

	/**
	 * Compose WflMultiSetObjectPropertiesResponse object
	 *
	 * @return WflMultiSetObjectPropertiesResponse
	 */
	private function WflMultiSetObjectPropertiesResponse()
	{
		$response = new WflMultiSetObjectPropertiesResponse();
		$response->MetaData = array();
		$response->MetaData[0] = new MetaDataValue();
		$response->MetaData[0]->Property = 'CategoryId';
		$response->MetaData[0]->Values = null;
		$response->MetaData[0]->PropertyValues = array();
		$response->MetaData[0]->PropertyValues[0] = new PropertyValue();
		$response->MetaData[0]->PropertyValues[0]->Value = '2';
		$response->MetaData[0]->PropertyValues[0]->Display = null;
		$response->MetaData[0]->PropertyValues[0]->Entity = null;
		$response->MetaData[1] = new MetaDataValue();
		$response->MetaData[1]->Property = 'StateId';
		$response->MetaData[1]->Values = null;
		$response->MetaData[1]->PropertyValues = array();
		$response->MetaData[1]->PropertyValues[0] = new PropertyValue();
		$response->MetaData[1]->PropertyValues[0]->Value = '2';
		$response->MetaData[1]->PropertyValues[0]->Display = null;
		$response->MetaData[1]->PropertyValues[0]->Entity = null;
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 *
	 */
	private function WflCreateDossier()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateDossierRequest();
		$recResp = $this->WflCreateDossierResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflCreateDossier');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $object->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflCreateDossier'][] = $objectId;
			$this->objOrIssNames[$objectId] = $object->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '002' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '002' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateDossier response.');
			return;
		}
	}

	/**
	 * Compose WflCreateObjectsRequest object.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function WflCreateDossierRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Dossier';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$request->Objects[0]->MetaData->RightsMetaData = null;
		$request->Objects[0]->MetaData->SourceMetaData = null;
		$request->Objects[0]->MetaData->ContentMetaData = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = date('Y-m-d\TH:i:s');
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '11';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = null;
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = '1';
		$request->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[0]->Targets[0]->PublishedDate = date('Y-m-d\TH:i:s');
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}

	/**
	 * Compose WflCreateObjectsResponse object.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function WflCreateDossierResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = '260100200';
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = '';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Dossier';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = '';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '0';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-04-24T15:42:46';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-04-24T15:42:46';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '11';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Dossiers';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Dossier';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'BBBBBB';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = new Target();
		$response->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Targets[0]->Issue->Id = '1';
		$response->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[0]->Targets[0]->PublishedDate = null;
		$response->Objects[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 *
	 */
	private function WflCreateArticle()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateArticleRequest();
		$recResp = $this->WflCreateArticleResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflCreateArticle');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $object->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflCreateArticle'][] = $objectId;
			$this->objOrIssNames[$objectId] = $object->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateObjects response.');
			return;
		}

	}

	/**
	 * Compose WflCreateObjectsRequest object.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function WflCreateArticleRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Article';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values[0] = '0';
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 0;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$request->Objects[0]->Elements[0]->Content = '';
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 0;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$request->Objects[0]->Elements[1]->Content = '';
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 0;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$request->Objects[0]->Elements[2]->Content = '';
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = '1';
		$request->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[0]->Targets[0]->PublishedDate = '2014-04-24T15:57:52';
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		return $request;
	}

	/**
	 * WflCreateObjectsResponse object
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function WflCreateArticleResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = '260101301';
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:bb0aadab-3837-439e-90f3-a8ac96fec282';
		$response->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Article';
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-04-24T15:57:53';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-04-24T15:57:53';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '0';
		$response->Objects[0]->Elements[0]->LengthChars = '0';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '0';
		$response->Objects[0]->Elements[0]->Snippet = '';
		$response->Objects[0]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '0';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '0';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = new Target();
		$response->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Targets[0]->Issue->Id = '1';
		$response->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[0]->Targets[0]->PublishedDate = null;
		$response->Objects[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 */
	private function WflCreateFatalExceptionArticleAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateArticleRequest();
		$recResp = $this->WflCreateArticleResponse();

		// Set value to check for in the plugin
		$req->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Fatal Exception Article Attempt';
		$recResp->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Fatal Exception Article Attempt';

		$curResp = $this->utils->callService( $this, $req, 'WflCreateFatalExceptionArticleAttempt');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflCreateFatalExceptionArticleAttempt'][0] = (int) $curResp->Objects[0]->MetaData->BasicMetaData->ID;
			$this->objOrIssNames[$curResp->Objects[0]->MetaData->BasicMetaData->ID] = $curResp->Objects[0]->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateFatalExceptionArticleAttempt response.');
			return;
		}
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 */
	private function WflCreateInfoExceptionArticleAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateArticleRequest();
		$recResp = $this->WflCreateArticleResponse();

		// Set value to check for in the plugin
		$req->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Info Exception Article Attempt';
		$recResp->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Info Exception Article Attempt';

		$curResp = $this->utils->callService( $this, $req, 'WflCreateInfoExceptionArticleAttempt');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflCreateInfoExceptionArticleAttempt'][0] = (int) $curResp->Objects[0]->MetaData->BasicMetaData->ID;
			$this->objOrIssNames[$curResp->Objects[0]->MetaData->BasicMetaData->ID] = $curResp->Objects[0]->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateInfoExceptionArticleAttempt response.');
			return;
		}
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 */
	private function WflCreateMultipleArticles()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateMultipleArticlesRequest();
		$recResp = $this->WflCreateMultipleArticlesResponse();

		$req->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Articles 1';
		$req->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Articles 2';
		$req->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Articles 3';
		$req->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Articles 4';
		$recResp->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Articles 1';
		$recResp->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Articles 2';
		$recResp->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Articles 3';
		$recResp->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Articles 4';

		$curResp = $this->utils->callService( $this, $req, 'WflCreateMultipleArticles');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $object->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflCreateMultipleArticles'][] = $objectId;
			$this->objOrIssNames[$objectId] = $object->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateMultipleArticles response.');
			return;
		}

	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 */
	private function WflCreateMultipleFatalExceptionArticleAttempts()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateMultipleArticlesRequest();
		$recResp = $this->WflCreateMultipleArticlesResponse();

		// Set value to check for in the plugin
		$req->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 1';
		$req->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Fatal Exception Article Attempt 2';
		$req->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 3';
		$req->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Fatal Exception Article Attempt 4';
		$recResp->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 1';
		$recResp->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Fatal Exception Article Attempt 2';
		$recResp->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 3';
		$recResp->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Fatal Exception Article Attempt 4';

		$curResp = $this->utils->callService( $this, $req, 'WflCreateMultipleFatalExceptionArticleAttempts');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $object->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflCreateMultipleFatalExceptionArticleAttempts'][] = $objectId;
			$this->objOrIssNames[$objectId] = $object->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateMultipleFatalExceptionArticleAttempts response.');
			return;
		}
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 */
	private function WflCreateMultipleInfoExceptionArticleAttempts()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateMultipleArticlesRequest();
		$recResp = $this->WflCreateMultipleArticlesResponse();

		// Set value to check for in the plugin
		$req->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 1';
		$req->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Info Exception Article Attempt 2';
		$req->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 3';
		$req->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Info Exception Article Attempt 4';
		$recResp->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 1';
		$recResp->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Info Exception Article Attempt 2';
		$recResp->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Normal Article Attempt 3';
		$recResp->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Info Exception Article Attempt 4';

		$curResp = $this->utils->callService( $this, $req, 'WflCreateMultipleInfoExceptionArticleAttempts');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $object->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflCreateMultipleInfoExceptionArticleAttempts'][] = $objectId;
			$this->objOrIssNames[$objectId] = $object->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateMultipleInfoExceptionArticleAttempts response.');
			return;
		}
	}

	/**
	 * Compose WflCreateObjectsRequest object.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function WflCreateMultipleArticlesRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Articles 1';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values[0] = '0';
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 0;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$request->Objects[0]->Elements[0]->Content = '';
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 0;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$request->Objects[0]->Elements[1]->Content = '';
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 0;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$request->Objects[0]->Elements[2]->Content = '';
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = '1';
		$request->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[0]->Targets[0]->PublishedDate = '2014-04-24T15:57:52';
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;

		$request->Objects[1] = new Object();
		$request->Objects[1]->MetaData = new MetaData();
		$request->Objects[1]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[1]->MetaData->BasicMetaData->ID = null;
		$request->Objects[1]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Articles 2';
		$request->Objects[1]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[1]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[1]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[1]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[1]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[1]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[1]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[1]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[1]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[1]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[1]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[1]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[1]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[1]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[1]->MetaData->SourceMetaData->Source = null;
		$request->Objects[1]->MetaData->SourceMetaData->Author = null;
		$request->Objects[1]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[1]->MetaData->ContentMetaData->Description = null;
		$request->Objects[1]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[1]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[1]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[1]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[1]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[1]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[1]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[1]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[1]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[1]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[1]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[1]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[1]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[1]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[1]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[1]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[1]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[1]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[1]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[1]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[1]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[1]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[1]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[1]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[1]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[1]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[1]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[1]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[1]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[1]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[1]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[1]->MetaData->ExtraMetaData = array();
		$request->Objects[1]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[1]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[1]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[1]->MetaData->ExtraMetaData[0]->Values[0] = '0';
		$request->Objects[1]->Relations = array();
		$request->Objects[1]->Pages = null;
		$request->Objects[1]->Files = array();
		$request->Objects[1]->Files[0] = new Attachment();
		$request->Objects[1]->Files[0]->Rendition = 'native';
		$request->Objects[1]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[1]->Files[0]->Content = null;
		$request->Objects[1]->Files[0]->FilePath = '';
		$request->Objects[1]->Files[0]->FileUrl = null;
		$request->Objects[1]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[1]->Files[0] );
		$request->Objects[1]->Messages = null;
		$request->Objects[1]->Elements = array();
		$request->Objects[1]->Elements[0] = new Element();
		$request->Objects[1]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$request->Objects[1]->Elements[0]->Name = 'head';
		$request->Objects[1]->Elements[0]->LengthWords = 0;
		$request->Objects[1]->Elements[0]->LengthChars = 0;
		$request->Objects[1]->Elements[0]->LengthParas = 1;
		$request->Objects[1]->Elements[0]->LengthLines = 0;
		$request->Objects[1]->Elements[0]->Snippet = '';
		$request->Objects[1]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$request->Objects[1]->Elements[0]->Content = '';
		$request->Objects[1]->Elements[1] = new Element();
		$request->Objects[1]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$request->Objects[1]->Elements[1]->Name = 'intro';
		$request->Objects[1]->Elements[1]->LengthWords = 0;
		$request->Objects[1]->Elements[1]->LengthChars = 0;
		$request->Objects[1]->Elements[1]->LengthParas = 1;
		$request->Objects[1]->Elements[1]->LengthLines = 0;
		$request->Objects[1]->Elements[1]->Snippet = '';
		$request->Objects[1]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$request->Objects[1]->Elements[1]->Content = '';
		$request->Objects[1]->Elements[2] = new Element();
		$request->Objects[1]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$request->Objects[1]->Elements[2]->Name = 'body';
		$request->Objects[1]->Elements[2]->LengthWords = 0;
		$request->Objects[1]->Elements[2]->LengthChars = 0;
		$request->Objects[1]->Elements[2]->LengthParas = 1;
		$request->Objects[1]->Elements[2]->LengthLines = 0;
		$request->Objects[1]->Elements[2]->Snippet = '';
		$request->Objects[1]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$request->Objects[1]->Elements[2]->Content = '';
		$request->Objects[1]->Targets = array();
		$request->Objects[1]->Targets[0] = new Target();
		$request->Objects[1]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[1]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[1]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[1]->Targets[0]->Issue = new Issue();
		$request->Objects[1]->Targets[0]->Issue->Id = '1';
		$request->Objects[1]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[1]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[1]->Targets[0]->Editions = array();
		$request->Objects[1]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[1]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[1]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[1]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[1]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[1]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[1]->Targets[0]->PublishedDate = '2014-04-24T15:57:52';
		$request->Objects[1]->Targets[0]->PublishedVersion = null;
		$request->Objects[1]->Renditions = null;
		$request->Objects[1]->MessageList = null;
		$request->Objects[1]->ObjectLabels = null;

		$request->Objects[2] = new Object();
		$request->Objects[2]->MetaData = new MetaData();
		$request->Objects[2]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[2]->MetaData->BasicMetaData->ID = null;
		$request->Objects[2]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Articles 3';
		$request->Objects[2]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[2]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[2]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[2]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[2]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[2]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[2]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[2]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[2]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[2]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[2]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[2]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[2]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[2]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[2]->MetaData->SourceMetaData->Source = null;
		$request->Objects[2]->MetaData->SourceMetaData->Author = null;
		$request->Objects[2]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[2]->MetaData->ContentMetaData->Description = null;
		$request->Objects[2]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[2]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[2]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[2]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[2]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[2]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[2]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[2]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[2]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[2]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[2]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[2]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[2]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[2]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[2]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[2]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[2]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[2]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[2]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[2]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[2]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[2]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[2]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[2]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[2]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[2]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[2]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[2]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[2]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[2]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[2]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[2]->MetaData->ExtraMetaData = array();
		$request->Objects[2]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[2]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[2]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[2]->MetaData->ExtraMetaData[0]->Values[0] = '0';
		$request->Objects[2]->Relations = array();
		$request->Objects[2]->Pages = null;
		$request->Objects[2]->Files = array();
		$request->Objects[2]->Files[0] = new Attachment();
		$request->Objects[2]->Files[0]->Rendition = 'native';
		$request->Objects[2]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[2]->Files[0]->Content = null;
		$request->Objects[2]->Files[0]->FilePath = '';
		$request->Objects[2]->Files[0]->FileUrl = null;
		$request->Objects[2]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[2]->Files[0] );
		$request->Objects[2]->Messages = null;
		$request->Objects[2]->Elements = array();
		$request->Objects[2]->Elements[0] = new Element();
		$request->Objects[2]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$request->Objects[2]->Elements[0]->Name = 'head';
		$request->Objects[2]->Elements[0]->LengthWords = 0;
		$request->Objects[2]->Elements[0]->LengthChars = 0;
		$request->Objects[2]->Elements[0]->LengthParas = 1;
		$request->Objects[2]->Elements[0]->LengthLines = 0;
		$request->Objects[2]->Elements[0]->Snippet = '';
		$request->Objects[2]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$request->Objects[2]->Elements[0]->Content = '';
		$request->Objects[2]->Elements[1] = new Element();
		$request->Objects[2]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$request->Objects[2]->Elements[1]->Name = 'intro';
		$request->Objects[2]->Elements[1]->LengthWords = 0;
		$request->Objects[2]->Elements[1]->LengthChars = 0;
		$request->Objects[2]->Elements[1]->LengthParas = 1;
		$request->Objects[2]->Elements[1]->LengthLines = 0;
		$request->Objects[2]->Elements[1]->Snippet = '';
		$request->Objects[2]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$request->Objects[2]->Elements[1]->Content = '';
		$request->Objects[2]->Elements[2] = new Element();
		$request->Objects[2]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$request->Objects[2]->Elements[2]->Name = 'body';
		$request->Objects[2]->Elements[2]->LengthWords = 0;
		$request->Objects[2]->Elements[2]->LengthChars = 0;
		$request->Objects[2]->Elements[2]->LengthParas = 1;
		$request->Objects[2]->Elements[2]->LengthLines = 0;
		$request->Objects[2]->Elements[2]->Snippet = '';
		$request->Objects[2]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$request->Objects[2]->Elements[2]->Content = '';
		$request->Objects[2]->Targets = array();
		$request->Objects[2]->Targets[0] = new Target();
		$request->Objects[2]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[2]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[2]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[2]->Targets[0]->Issue = new Issue();
		$request->Objects[2]->Targets[0]->Issue->Id = '1';
		$request->Objects[2]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[2]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[2]->Targets[0]->Editions = array();
		$request->Objects[2]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[2]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[2]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[2]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[2]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[2]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[2]->Targets[0]->PublishedDate = '2014-04-24T15:57:52';
		$request->Objects[2]->Targets[0]->PublishedVersion = null;
		$request->Objects[2]->Renditions = null;
		$request->Objects[2]->MessageList = null;
		$request->Objects[2]->ObjectLabels = null;

		$request->Objects[3] = new Object();
		$request->Objects[3]->MetaData = new MetaData();
		$request->Objects[3]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[3]->MetaData->BasicMetaData->ID = null;
		$request->Objects[3]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Articles 4';
		$request->Objects[3]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[3]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[3]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[3]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[3]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[3]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[3]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[3]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[3]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[3]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[3]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[3]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[3]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[3]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[3]->MetaData->SourceMetaData->Source = null;
		$request->Objects[3]->MetaData->SourceMetaData->Author = null;
		$request->Objects[3]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[3]->MetaData->ContentMetaData->Description = null;
		$request->Objects[3]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[3]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[3]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[3]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[3]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[3]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[3]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[3]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[3]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[3]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[3]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[3]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[3]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[3]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[3]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[3]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[3]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[3]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[3]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[3]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[3]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[3]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[3]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[3]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[3]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[3]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[3]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[3]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[3]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[3]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[3]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[3]->MetaData->ExtraMetaData = array();
		$request->Objects[3]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[3]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[3]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[3]->MetaData->ExtraMetaData[0]->Values[0] = '0';
		$request->Objects[3]->Relations = array();
		$request->Objects[3]->Pages = null;
		$request->Objects[3]->Files = array();
		$request->Objects[3]->Files[0] = new Attachment();
		$request->Objects[3]->Files[0]->Rendition = 'native';
		$request->Objects[3]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[3]->Files[0]->Content = null;
		$request->Objects[3]->Files[0]->FilePath = '';
		$request->Objects[3]->Files[0]->FileUrl = null;
		$request->Objects[3]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#005_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[3]->Files[0] );
		$request->Objects[3]->Messages = null;
		$request->Objects[3]->Elements = array();
		$request->Objects[3]->Elements[0] = new Element();
		$request->Objects[3]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$request->Objects[3]->Elements[0]->Name = 'head';
		$request->Objects[3]->Elements[0]->LengthWords = 0;
		$request->Objects[3]->Elements[0]->LengthChars = 0;
		$request->Objects[3]->Elements[0]->LengthParas = 1;
		$request->Objects[3]->Elements[0]->LengthLines = 0;
		$request->Objects[3]->Elements[0]->Snippet = '';
		$request->Objects[3]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$request->Objects[3]->Elements[0]->Content = '';
		$request->Objects[3]->Elements[1] = new Element();
		$request->Objects[3]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$request->Objects[3]->Elements[1]->Name = 'intro';
		$request->Objects[3]->Elements[1]->LengthWords = 0;
		$request->Objects[3]->Elements[1]->LengthChars = 0;
		$request->Objects[3]->Elements[1]->LengthParas = 1;
		$request->Objects[3]->Elements[1]->LengthLines = 0;
		$request->Objects[3]->Elements[1]->Snippet = '';
		$request->Objects[3]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$request->Objects[3]->Elements[1]->Content = '';
		$request->Objects[3]->Elements[2] = new Element();
		$request->Objects[3]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$request->Objects[3]->Elements[2]->Name = 'body';
		$request->Objects[3]->Elements[2]->LengthWords = 0;
		$request->Objects[3]->Elements[2]->LengthChars = 0;
		$request->Objects[3]->Elements[2]->LengthParas = 1;
		$request->Objects[3]->Elements[2]->LengthLines = 0;
		$request->Objects[3]->Elements[2]->Snippet = '';
		$request->Objects[3]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$request->Objects[3]->Elements[2]->Content = '';
		$request->Objects[3]->Targets = array();
		$request->Objects[3]->Targets[0] = new Target();
		$request->Objects[3]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[3]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[3]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[3]->Targets[0]->Issue = new Issue();
		$request->Objects[3]->Targets[0]->Issue->Id = '1';
		$request->Objects[3]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[3]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[3]->Targets[0]->Editions = array();
		$request->Objects[3]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[3]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[3]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[3]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[3]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[3]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[3]->Targets[0]->PublishedDate = '2014-04-24T15:57:52';
		$request->Objects[3]->Targets[0]->PublishedVersion = null;
		$request->Objects[3]->Renditions = null;
		$request->Objects[3]->MessageList = null;
		$request->Objects[3]->ObjectLabels = null;

		$request->Messages = null;
		$request->AutoNaming = true;
		return $request;
	}

	/**
	 * Compose WflCreateObjectsResponse object.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function WflCreateMultipleArticlesResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = '260101301';
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:bb0aadab-3837-439e-90f3-a8ac96fec282';
		$response->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics Multiple Articles 1';
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-04-24T15:57:53';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-04-24T15:57:53';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '0';
		$response->Objects[0]->Elements[0]->LengthChars = '0';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '0';
		$response->Objects[0]->Elements[0]->Snippet = '';
		$response->Objects[0]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '0';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '0';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Targets[0] = new Target();
		$response->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Targets[0]->Issue->Id = '1';
		$response->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[0]->Targets[0]->PublishedDate = null;
		$response->Objects[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();

		$response->Objects[1] = new Object();
		$response->Objects[1]->MetaData = new MetaData();
		$response->Objects[1]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[1]->MetaData->BasicMetaData->ID = '260101301';
		$response->Objects[1]->MetaData->BasicMetaData->DocumentID = 'xmp.did:bb0aadab-3837-439e-90f3-a8ac96fec282';
		$response->Objects[1]->MetaData->BasicMetaData->Name = 'Analytics Multiple Articles 2';
		$response->Objects[1]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[1]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[1]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[1]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[1]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[1]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[1]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[1]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[1]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[1]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[1]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[1]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[1]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[1]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[1]->MetaData->SourceMetaData->Source = '';
		$response->Objects[1]->MetaData->SourceMetaData->Author = '';
		$response->Objects[1]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[1]->MetaData->ContentMetaData->Description = '';
		$response->Objects[1]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[1]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[1]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[1]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[1]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[1]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[1]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[1]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[1]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[1]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[1]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[1]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[1]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[1]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[1]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[1]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[1]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[1]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[1]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[1]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[1]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[1]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[1]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[1]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[1]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[1]->MetaData->WorkflowMetaData->Modified = '2014-04-24T15:57:53';
		$response->Objects[1]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[1]->MetaData->WorkflowMetaData->Created = '2014-04-24T15:57:53';
		$response->Objects[1]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[1]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[1]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[1]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[1]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[1]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[1]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[1]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[1]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[1]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[1]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[1]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[1]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[1]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[1]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[1]->MetaData->ExtraMetaData = array();
		$response->Objects[1]->Relations = array();
		$response->Objects[1]->Pages = array();
		$response->Objects[1]->Files = array();
		$response->Objects[1]->Messages = null;
		$response->Objects[1]->Elements = array();
		$response->Objects[1]->Elements[0] = new Element();
		$response->Objects[1]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$response->Objects[1]->Elements[0]->Name = 'head';
		$response->Objects[1]->Elements[0]->LengthWords = '0';
		$response->Objects[1]->Elements[0]->LengthChars = '0';
		$response->Objects[1]->Elements[0]->LengthParas = '1';
		$response->Objects[1]->Elements[0]->LengthLines = '0';
		$response->Objects[1]->Elements[0]->Snippet = '';
		$response->Objects[1]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$response->Objects[1]->Elements[0]->Content = null;
		$response->Objects[1]->Elements[1] = new Element();
		$response->Objects[1]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$response->Objects[1]->Elements[1]->Name = 'intro';
		$response->Objects[1]->Elements[1]->LengthWords = '0';
		$response->Objects[1]->Elements[1]->LengthChars = '0';
		$response->Objects[1]->Elements[1]->LengthParas = '1';
		$response->Objects[1]->Elements[1]->LengthLines = '0';
		$response->Objects[1]->Elements[1]->Snippet = '';
		$response->Objects[1]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$response->Objects[1]->Elements[1]->Content = null;
		$response->Objects[1]->Elements[2] = new Element();
		$response->Objects[1]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$response->Objects[1]->Elements[2]->Name = 'body';
		$response->Objects[1]->Elements[2]->LengthWords = '0';
		$response->Objects[1]->Elements[2]->LengthChars = '0';
		$response->Objects[1]->Elements[2]->LengthParas = '1';
		$response->Objects[1]->Elements[2]->LengthLines = '0';
		$response->Objects[1]->Elements[2]->Snippet = '';
		$response->Objects[1]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$response->Objects[1]->Elements[2]->Content = null;
		$response->Objects[1]->Targets = array();
		$response->Objects[1]->Targets[0] = new Target();
		$response->Objects[1]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[1]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[1]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[1]->Targets[0]->Issue = new Issue();
		$response->Objects[1]->Targets[0]->Issue->Id = '1';
		$response->Objects[1]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[1]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[1]->Targets[0]->Editions = array();
		$response->Objects[1]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[1]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[1]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[1]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[1]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[1]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[1]->Targets[0]->PublishedDate = null;
		$response->Objects[1]->Targets[0]->PublishedVersion = null;
		$response->Objects[1]->Renditions = null;
		$response->Objects[1]->MessageList = new MessageList();
		$response->Objects[1]->MessageList->Messages = array();
		$response->Objects[1]->MessageList->ReadMessageIDs = array();
		$response->Objects[1]->MessageList->DeleteMessageIDs = null;
		$response->Objects[1]->ObjectLabels = null;
		$response->Objects[1]->InDesignArticles = array();
		$response->Objects[1]->Placements = array();

		$response->Objects[2] = new Object();
		$response->Objects[2]->MetaData = new MetaData();
		$response->Objects[2]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[2]->MetaData->BasicMetaData->ID = '260101301';
		$response->Objects[2]->MetaData->BasicMetaData->DocumentID = 'xmp.did:bb0aadab-3837-439e-90f3-a8ac96fec282';
		$response->Objects[2]->MetaData->BasicMetaData->Name = 'Analytics Multiple Articles 3';
		$response->Objects[2]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[2]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[2]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[2]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[2]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[2]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[2]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[2]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[2]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[2]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[2]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[2]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[2]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[2]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[2]->MetaData->SourceMetaData->Source = '';
		$response->Objects[2]->MetaData->SourceMetaData->Author = '';
		$response->Objects[2]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[2]->MetaData->ContentMetaData->Description = '';
		$response->Objects[2]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[2]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[2]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[2]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[2]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[2]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[2]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[2]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[2]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[2]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[2]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[2]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[2]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[2]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[2]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[2]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[2]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[2]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[2]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[2]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[2]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[2]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[2]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[2]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[2]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[2]->MetaData->WorkflowMetaData->Modified = '2014-04-24T15:57:53';
		$response->Objects[2]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[2]->MetaData->WorkflowMetaData->Created = '2014-04-24T15:57:53';
		$response->Objects[2]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[2]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[2]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[2]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[2]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[2]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[2]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[2]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[2]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[2]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[2]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[2]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[2]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[2]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[2]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[2]->MetaData->ExtraMetaData = array();
		$response->Objects[2]->Relations = array();
		$response->Objects[2]->Pages = array();
		$response->Objects[2]->Files = array();
		$response->Objects[2]->Messages = null;
		$response->Objects[2]->Elements = array();
		$response->Objects[2]->Elements[0] = new Element();
		$response->Objects[2]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$response->Objects[2]->Elements[0]->Name = 'head';
		$response->Objects[2]->Elements[0]->LengthWords = '0';
		$response->Objects[2]->Elements[0]->LengthChars = '0';
		$response->Objects[2]->Elements[0]->LengthParas = '1';
		$response->Objects[2]->Elements[0]->LengthLines = '0';
		$response->Objects[2]->Elements[0]->Snippet = '';
		$response->Objects[2]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$response->Objects[2]->Elements[0]->Content = null;
		$response->Objects[2]->Elements[1] = new Element();
		$response->Objects[2]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$response->Objects[2]->Elements[1]->Name = 'intro';
		$response->Objects[2]->Elements[1]->LengthWords = '0';
		$response->Objects[2]->Elements[1]->LengthChars = '0';
		$response->Objects[2]->Elements[1]->LengthParas = '1';
		$response->Objects[2]->Elements[1]->LengthLines = '0';
		$response->Objects[2]->Elements[1]->Snippet = '';
		$response->Objects[2]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$response->Objects[2]->Elements[1]->Content = null;
		$response->Objects[2]->Elements[2] = new Element();
		$response->Objects[2]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$response->Objects[2]->Elements[2]->Name = 'body';
		$response->Objects[2]->Elements[2]->LengthWords = '0';
		$response->Objects[2]->Elements[2]->LengthChars = '0';
		$response->Objects[2]->Elements[2]->LengthParas = '1';
		$response->Objects[2]->Elements[2]->LengthLines = '0';
		$response->Objects[2]->Elements[2]->Snippet = '';
		$response->Objects[2]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$response->Objects[2]->Elements[2]->Content = null;
		$response->Objects[2]->Targets = array();
		$response->Objects[2]->Targets[0] = new Target();
		$response->Objects[2]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[2]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[2]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[2]->Targets[0]->Issue = new Issue();
		$response->Objects[2]->Targets[0]->Issue->Id = '1';
		$response->Objects[2]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[2]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[2]->Targets[0]->Editions = array();
		$response->Objects[2]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[2]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[2]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[2]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[2]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[2]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[2]->Targets[0]->PublishedDate = null;
		$response->Objects[2]->Targets[0]->PublishedVersion = null;
		$response->Objects[2]->Renditions = null;
		$response->Objects[2]->MessageList = new MessageList();
		$response->Objects[2]->MessageList->Messages = array();
		$response->Objects[2]->MessageList->ReadMessageIDs = array();
		$response->Objects[2]->MessageList->DeleteMessageIDs = null;
		$response->Objects[2]->ObjectLabels = null;
		$response->Objects[2]->InDesignArticles = array();
		$response->Objects[2]->Placements = array();

		$response->Objects[3] = new Object();
		$response->Objects[3]->MetaData = new MetaData();
		$response->Objects[3]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[3]->MetaData->BasicMetaData->ID = '260101301';
		$response->Objects[3]->MetaData->BasicMetaData->DocumentID = 'xmp.did:bb0aadab-3837-439e-90f3-a8ac96fec282';
		$response->Objects[3]->MetaData->BasicMetaData->Name = 'Analytics Multiple Articles 4';
		$response->Objects[3]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[3]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[3]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[3]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[3]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[3]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[3]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[3]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[3]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[3]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[3]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[3]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[3]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[3]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[3]->MetaData->SourceMetaData->Source = '';
		$response->Objects[3]->MetaData->SourceMetaData->Author = '';
		$response->Objects[3]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[3]->MetaData->ContentMetaData->Description = '';
		$response->Objects[3]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[3]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[3]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[3]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[3]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[3]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[3]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[3]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[3]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[3]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[3]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[3]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[3]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[3]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[3]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[3]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[3]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[3]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[3]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[3]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[3]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[3]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[3]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[3]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[3]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[3]->MetaData->WorkflowMetaData->Modified = '2014-04-24T15:57:53';
		$response->Objects[3]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[3]->MetaData->WorkflowMetaData->Created = '2014-04-24T15:57:53';
		$response->Objects[3]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[3]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[3]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[3]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[3]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[3]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[3]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[3]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[3]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[3]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[3]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[3]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[3]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[3]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[3]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[3]->MetaData->ExtraMetaData = array();
		$response->Objects[3]->Relations = array();
		$response->Objects[3]->Pages = array();
		$response->Objects[3]->Files = array();
		$response->Objects[3]->Messages = null;
		$response->Objects[3]->Elements = array();
		$response->Objects[3]->Elements[0] = new Element();
		$response->Objects[3]->Elements[0]->ID = '1cb24706-96e9-39d4-ef3b-431fc9abc097';
		$response->Objects[3]->Elements[0]->Name = 'head';
		$response->Objects[3]->Elements[0]->LengthWords = '0';
		$response->Objects[3]->Elements[0]->LengthChars = '0';
		$response->Objects[3]->Elements[0]->LengthParas = '1';
		$response->Objects[3]->Elements[0]->LengthLines = '0';
		$response->Objects[3]->Elements[0]->Snippet = '';
		$response->Objects[3]->Elements[0]->Version = '1bee80be-1bf6-55da-9967-c212575f9d7e';
		$response->Objects[3]->Elements[0]->Content = null;
		$response->Objects[3]->Elements[1] = new Element();
		$response->Objects[3]->Elements[1]->ID = 'a5f9aa57-6fd5-d033-a1a0-802a65112dd1';
		$response->Objects[3]->Elements[1]->Name = 'intro';
		$response->Objects[3]->Elements[1]->LengthWords = '0';
		$response->Objects[3]->Elements[1]->LengthChars = '0';
		$response->Objects[3]->Elements[1]->LengthParas = '1';
		$response->Objects[3]->Elements[1]->LengthLines = '0';
		$response->Objects[3]->Elements[1]->Snippet = '';
		$response->Objects[3]->Elements[1]->Version = '61decb5c-2831-3557-f28d-8e61d9aa3b95';
		$response->Objects[3]->Elements[1]->Content = null;
		$response->Objects[3]->Elements[2] = new Element();
		$response->Objects[3]->Elements[2]->ID = 'be8fcd90-0bed-8104-6333-3c4a955f7389';
		$response->Objects[3]->Elements[2]->Name = 'body';
		$response->Objects[3]->Elements[2]->LengthWords = '0';
		$response->Objects[3]->Elements[2]->LengthChars = '0';
		$response->Objects[3]->Elements[2]->LengthParas = '1';
		$response->Objects[3]->Elements[2]->LengthLines = '0';
		$response->Objects[3]->Elements[2]->Snippet = '';
		$response->Objects[3]->Elements[2]->Version = '6f1405cd-1d5c-e816-cb6f-3cc181c3de01';
		$response->Objects[3]->Elements[2]->Content = null;
		$response->Objects[3]->Targets = array();
		$response->Objects[3]->Targets[0] = new Target();
		$response->Objects[3]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[3]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[3]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[3]->Targets[0]->Issue = new Issue();
		$response->Objects[3]->Targets[0]->Issue->Id = '1';
		$response->Objects[3]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[3]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[3]->Targets[0]->Editions = array();
		$response->Objects[3]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[3]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[3]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[3]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[3]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[3]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[3]->Targets[0]->PublishedDate = null;
		$response->Objects[3]->Targets[0]->PublishedVersion = null;
		$response->Objects[3]->Renditions = null;
		$response->Objects[3]->MessageList = new MessageList();
		$response->Objects[3]->MessageList->Messages = array();
		$response->Objects[3]->MessageList->ReadMessageIDs = array();
		$response->Objects[3]->MessageList->DeleteMessageIDs = null;
		$response->Objects[3]->ObjectLabels = null;
		$response->Objects[3]->InDesignArticles = array();
		$response->Objects[3]->Placements = array();

		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflCreateObjects service and compare the response
	 */
	private function WflCreateObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req = $this->WflCreateObjectsRequest();
		$recResp = $this->WflCreateObjectsResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflCreateObjects');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $curResp->Objects[0]->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflCreateObjects'][0] = $objectId;
			$this->objOrIssNames[$objectId] = $curResp->Objects[0]->MetaData->BasicMetaData->Name;

			if ( isset($curResp->Objects[0]->Relations) && count($curResp->Objects[0]->Relations) > 0 ) {
				$objectId = (int) $curResp->Objects[0]->Relations[0]->ParentInfo->ID;
				$this->objOrIssIds['WflCreateObjects'][1] = $objectId;
				$this->objOrIssNames[$objectId] = $curResp->Objects[0]->Relations[0]->ParentInfo->Name;
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '021' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '021' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateObjects response.');
			return;
		}
	}

	/**
	 * Compose WflCreateObjectsRequest object.
	 *
	 * @return WflCreateObjectsRequest
	 */
	private function WflCreateObjectsRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = 'false';
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 160967;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = 0;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0] = new ExtraMetaData();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Property = 'Dossier';
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values = array();
		$request->Objects[0]->MetaData->ExtraMetaData[0]->Values[0] = '-1';
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Relations[0] = new Relation();
		$request->Objects[0]->Relations[0]->Parent = '-1';
		$request->Objects[0]->Relations[0]->Child = null;
		$request->Objects[0]->Relations[0]->Type = 'Contained';
		$request->Objects[0]->Relations[0]->Placements = null;
		$request->Objects[0]->Relations[0]->ParentVersion = null;
		$request->Objects[0]->Relations[0]->ChildVersion = null;
		$request->Objects[0]->Relations[0]->Geometry = null;
		$request->Objects[0]->Relations[0]->Rating = null;
		$request->Objects[0]->Relations[0]->Targets = array();
		$request->Objects[0]->Relations[0]->Targets[0] = new Target();
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->Id = '1';
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '2014-04-14T11:54:26';
		$request->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Relations[0]->ParentInfo = null;
		$request->Objects[0]->Relations[0]->ChildInfo = null;
		$request->Objects[0]->Relations[0]->ObjectLabels = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = null;
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#021_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'ae52eb7d-e6bc-3228-f726-1dca5d90d087';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 0;
		$request->Objects[0]->Elements[0]->LengthChars = 0;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 0;
		$request->Objects[0]->Elements[0]->Snippet = '';
		$request->Objects[0]->Elements[0]->Version = 'cde39910-55b1-0db0-18fa-bb09d1457a03';
		$request->Objects[0]->Elements[0]->Content = '';
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = '98b08032-8fa8-71a6-1ac2-f9e0882dc135';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 0;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '6ac3c0f1-c95b-2ceb-d4dc-2eab24f16757';
		$request->Objects[0]->Elements[1]->Content = '';
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = '8b2be265-a0c8-4d49-d6a7-b2c3fd60d41f';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 0;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = 'cf2d4be4-7cbf-3132-63ca-519e394309a0';
		$request->Objects[0]->Elements[2]->Content = '';
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = '1';
		$request->Objects[0]->Targets[0]->PubChannel->Name = 'Print';
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = '1';
		$request->Objects[0]->Targets[0]->Issue->Name = '1st Issue';
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = '1';
		$request->Objects[0]->Targets[0]->Editions[0]->Name = 'North';
		$request->Objects[0]->Targets[0]->Editions[1] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[1]->Id = '2';
		$request->Objects[0]->Targets[0]->Editions[1]->Name = 'South';
		$request->Objects[0]->Targets[0]->PublishedDate = '2014-04-14T11:54:26';
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->Messages = null;
		$request->AutoNaming = true;
		return $request;
	}

	/**
	 * Compose WflCreateObjectsResponse object to compare the test response.
	 *
	 * @return WflCreateObjectsResponse
	 */
	private function WflCreateObjectsResponse()
	{
		$response = new WflCreateObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = '180108502';
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:c639ec8c-43fb-4812-96ae-52f1874ee8dc';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = 'Print';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-04-14T11:54:26';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-04-14T11:54:26';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = '180108503';
		$response->Objects[0]->Relations[0]->Child = '180108502';
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = new Target();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[0]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = 180108503;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->dossierArticleName;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Dossier';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = 180108502;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->dossierArticleName;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'ae52eb7d-e6bc-3228-f726-1dca5d90d087';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '0';
		$response->Objects[0]->Elements[0]->LengthChars = '0';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '0';
		$response->Objects[0]->Elements[0]->Snippet = '';
		$response->Objects[0]->Elements[0]->Version = 'cde39910-55b1-0db0-18fa-bb09d1457a03';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = '98b08032-8fa8-71a6-1ac2-f9e0882dc135';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '0';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = '6ac3c0f1-c95b-2ceb-d4dc-2eab24f16757';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = '8b2be265-a0c8-4d49-d6a7-b2c3fd60d41f';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '0';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = 'cf2d4be4-7cbf-3132-63ca-519e394309a0';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->_Closed = false;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflUnlockObjects service and compare the response
	 */
	private function WflUnlockObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req = $this->WflUnlockObjectsRequest();
		$recResp = $this->WflUnlockObjectsResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflUnlockObjects');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '022' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '022' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflUnlockObjects response.');
			return;
		}
	}

	/**
	 * Compose WflUnlockObjectsRequest object.
	 *
	 * @return WflUnlockObjectsRequest
	 */
	private function WflUnlockObjectsRequest()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objOrIssIds['WflCreateObjects'][0];
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}

	/**
	 * Compose WflUnlockObjectsResponse object to compare the test response.
	 *
	 * @return WflUnlockObjectsResponse
	 */
	private function WflUnlockObjectsResponse()
	{
		$response = new WflUnlockObjectsResponse();
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflSaveObjects service and compare the response
	 */
	private function WflSaveObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->WflSaveObjectsRequest();
		$recResp = $this->WflSaveObjectsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflSaveObjects');

		if ( isset($curResp->Objects) && count($curResp->Objects) > 0) foreach ($curResp->Objects as $object) {
			// Keep track of created artifacts so we can tear down the process
			$objectId = (int) $object->MetaData->BasicMetaData->ID;
			$this->objOrIssIds['WflSaveObjects'][] = $objectId;
			$this->objOrIssNames[$objectId] = $object->MetaData->BasicMetaData->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '006' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '006' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflSaveObjects response.');
			return;
		}

	}

	/**
	 * Compose WflSaveObjectsRequest object.
	 *
	 * @return WflSaveObjectsRequest
	 */
	private function WflSaveObjectsRequest()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->objOrIssIds['WflCreateObjects'][0];
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = null;
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics_TestCase_Article_4';
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = null;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = 'Hello';
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 3;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 1;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 5;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 3;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 3;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Hello';
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 170241;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = null;
		$request->Objects[0]->Pages = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/incopyicml';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/Analytics_TestData/rec#006_att#000_native.wcml';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = array();
		$request->Objects[0]->Elements[0] = new Element();
		$request->Objects[0]->Elements[0]->ID = 'FA912053-451C-489E-A879-8595BDA523CD';
		$request->Objects[0]->Elements[0]->Name = 'head';
		$request->Objects[0]->Elements[0]->LengthWords = 1;
		$request->Objects[0]->Elements[0]->LengthChars = 5;
		$request->Objects[0]->Elements[0]->LengthParas = 1;
		$request->Objects[0]->Elements[0]->LengthLines = 1;
		$request->Objects[0]->Elements[0]->Snippet = 'Hello';
		$request->Objects[0]->Elements[0]->Version = '1CCDF155-0CB1-49F4-88BF-32A22527532A';
		$request->Objects[0]->Elements[0]->Content = null;
		$request->Objects[0]->Elements[1] = new Element();
		$request->Objects[0]->Elements[1]->ID = '39D82118-64BC-4CE7-B9BB-83750B52BACF';
		$request->Objects[0]->Elements[1]->Name = 'intro';
		$request->Objects[0]->Elements[1]->LengthWords = 0;
		$request->Objects[0]->Elements[1]->LengthChars = 0;
		$request->Objects[0]->Elements[1]->LengthParas = 1;
		$request->Objects[0]->Elements[1]->LengthLines = 1;
		$request->Objects[0]->Elements[1]->Snippet = '';
		$request->Objects[0]->Elements[1]->Version = '98BF97D0-20BE-468A-ABC9-BD68CB788354';
		$request->Objects[0]->Elements[1]->Content = null;
		$request->Objects[0]->Elements[2] = new Element();
		$request->Objects[0]->Elements[2]->ID = '7315E765-E299-49E2-8093-410564A6F168';
		$request->Objects[0]->Elements[2]->Name = 'body';
		$request->Objects[0]->Elements[2]->LengthWords = 0;
		$request->Objects[0]->Elements[2]->LengthChars = 0;
		$request->Objects[0]->Elements[2]->LengthParas = 1;
		$request->Objects[0]->Elements[2]->LengthLines = 1;
		$request->Objects[0]->Elements[2]->Snippet = '';
		$request->Objects[0]->Elements[2]->Version = '8FED3EEE-8148-4AE0-84C8-DFE172500364';
		$request->Objects[0]->Elements[2]->Content = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = new MessageList();
		$request->Objects[0]->MessageList->Messages = null;
		$request->Objects[0]->MessageList->ReadMessageIDs = null;
		$request->Objects[0]->MessageList->DeleteMessageIDs = null;
		$request->Objects[0]->ObjectLabels = null;
		$request->ReadMessageIDs = null;
		$request->Messages = null;
		return $request;
	}

	/**
	 * Setup Compose WflSaveObjectsResponse object.
	 *
	 * @return WflSaveObjectsResponse
	 */
	private function WflSaveObjectsResponse()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = '260200400';
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:500e1efa-56ae-4950-8a93-ad93faa46bd9';
		$response->Objects[0]->MetaData->BasicMetaData->Name = 'Analytics_TestCase_Article_4';
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = 'Hello';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '3';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '1';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '5';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '3';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = 'Hello';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '170241';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-04-25T11:35:16';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-04-25T11:34:42';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.2';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = '260106500';
		$response->Objects[0]->Relations[0]->Child = '260200400';
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.2';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = new Target();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[0]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = 260106500;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->dossierArticleName;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Dossier';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = 260200400;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = 'Analytics_TestCase_Article_4';
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = array();
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'FA912053-451C-489E-A879-8595BDA523CD';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '1';
		$response->Objects[0]->Elements[0]->LengthChars = '5';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '1';
		$response->Objects[0]->Elements[0]->Snippet = 'Hello';
		$response->Objects[0]->Elements[0]->Version = '1CCDF155-0CB1-49F4-88BF-32A22527532A';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = '39D82118-64BC-4CE7-B9BB-83750B52BACF';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '1';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = '98BF97D0-20BE-468A-ABC9-BD68CB788354';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = '7315E765-E299-49E2-8093-410564A6F168';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '1';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = '8FED3EEE-8148-4AE0-84C8-DFE172500364';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		$response->Objects[0]->InDesignArticles = array();
		$response->Objects[0]->Placements = array();
		$response->Reports = array();
		return $response;
	}

	/**
	 * Access the WflGetObjects service and compare the response
	 */
	private function WflGetObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->WflGetObjectsRequest();
		$recResp = $this->WflGetObjectsResponse();
		$curResp = $this->utils->callService( $this, $req, 'WflGetObjects');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '060' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '060' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetObjects response.');
			return;
		}
	}

	/**
	 * Compose WflGetObjectsRequest object.
	 *
	 * @return WflGetObjectsRequest
	 */
	private function WflGetObjectsRequest()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objOrIssIds['WflCreateObjects'][0];
		$request->Lock = true;
		$request->Rendition = 'none';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = array();
		$request->Areas[0] = 'Workflow';
		$request->EditionId = null;
		return $request;
	}

	/**
	 * Compose WflGetObjectsResponse object to compare the test response.
	 *
	 * @return WflGetObjectsResponse
	 */
	private function WflGetObjectsResponse()
	{
		$response = new WflGetObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$response->Objects[0]->MetaData = new MetaData();
		$response->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$response->Objects[0]->MetaData->BasicMetaData->ID = '180105500';
		$response->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:82c0a9ae-e7e1-443d-8c45-c4371def07a3';
		$response->Objects[0]->MetaData->BasicMetaData->Name = $this->dossierArticleName;
		$response->Objects[0]->MetaData->BasicMetaData->Type = 'Article';
		$response->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Publication->Name = 'WW News';
		$response->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$response->Objects[0]->MetaData->BasicMetaData->Category->Id = '1';
		$response->Objects[0]->MetaData->BasicMetaData->Category->Name = 'News';
		$response->Objects[0]->MetaData->BasicMetaData->ContentSource = '';
		$response->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$response->Objects[0]->MetaData->RightsMetaData->Copyright = '';
		$response->Objects[0]->MetaData->RightsMetaData->CopyrightURL = '';
		$response->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$response->Objects[0]->MetaData->SourceMetaData->Credit = '';
		$response->Objects[0]->MetaData->SourceMetaData->Source = '';
		$response->Objects[0]->MetaData->SourceMetaData->Author = '';
		$response->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$response->Objects[0]->MetaData->ContentMetaData->Description = '';
		$response->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = '';
		$response->Objects[0]->MetaData->ContentMetaData->Keywords = array();
		$response->Objects[0]->MetaData->ContentMetaData->Slugline = '';
		$response->Objects[0]->MetaData->ContentMetaData->Format = 'application/incopyicml';
		$response->Objects[0]->MetaData->ContentMetaData->Columns = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Width = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Height = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Dpi = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthWords = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthChars = '0';
		$response->Objects[0]->MetaData->ContentMetaData->LengthParas = '3';
		$response->Objects[0]->MetaData->ContentMetaData->LengthLines = '0';
		$response->Objects[0]->MetaData->ContentMetaData->PlainContent = '';
		$response->Objects[0]->MetaData->ContentMetaData->FileSize = '160967';
		$response->Objects[0]->MetaData->ContentMetaData->ColorSpace = '';
		$response->Objects[0]->MetaData->ContentMetaData->HighResFile = '';
		$response->Objects[0]->MetaData->ContentMetaData->Encoding = '';
		$response->Objects[0]->MetaData->ContentMetaData->Compression = '';
		$response->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = '0';
		$response->Objects[0]->MetaData->ContentMetaData->Channels = '';
		$response->Objects[0]->MetaData->ContentMetaData->AspectRatio = '';
		$response->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$response->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Urgency = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modifier = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Modified = '2014-04-15T15:45:12';
		$response->Objects[0]->MetaData->WorkflowMetaData->Creator = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Created = '2014-04-15T15:45:12';
		$response->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Id = '1';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Name = 'Draft text';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Type = 'Article';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->State->Color = 'FF0000';
		$response->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->LockedBy = 'WoodWing Software';
		$response->Objects[0]->MetaData->WorkflowMetaData->Version = '0.1';
		$response->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$response->Objects[0]->MetaData->WorkflowMetaData->Rating = '0';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deletor = '';
		$response->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$response->Objects[0]->MetaData->ExtraMetaData = array();
		$response->Objects[0]->Relations = array();
		$response->Objects[0]->Relations[0] = new Relation();
		$response->Objects[0]->Relations[0]->Parent = '180105501';
		$response->Objects[0]->Relations[0]->Child = '180105500';
		$response->Objects[0]->Relations[0]->Type = 'Contained';
		$response->Objects[0]->Relations[0]->Placements = array();
		$response->Objects[0]->Relations[0]->ParentVersion = '0.1';
		$response->Objects[0]->Relations[0]->ChildVersion = '0.1';
		$response->Objects[0]->Relations[0]->Geometry = null;
		$response->Objects[0]->Relations[0]->Rating = '0';
		$response->Objects[0]->Relations[0]->Targets = array();
		$response->Objects[0]->Relations[0]->Targets[0] = new Target();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue = new Issue();
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Objects[0]->Relations[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Objects[0]->Relations[0]->Targets[0]->Editions = array();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Objects[0]->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Objects[0]->Relations[0]->Targets[0]->PublishedVersion = null;
		$response->Objects[0]->Relations[0]->Targets[0]->ExternalId = '';
		$response->Objects[0]->Relations[0]->ParentInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ParentInfo->ID = 180105501;
		$response->Objects[0]->Relations[0]->ParentInfo->Name = $this->dossierArticleName;
		$response->Objects[0]->Relations[0]->ParentInfo->Type = 'Dossier';
		$response->Objects[0]->Relations[0]->ParentInfo->Format = '';
		$response->Objects[0]->Relations[0]->ChildInfo = new ObjectInfo();
		$response->Objects[0]->Relations[0]->ChildInfo->ID = 180105500;
		$response->Objects[0]->Relations[0]->ChildInfo->Name = $this->dossierArticleName;
		$response->Objects[0]->Relations[0]->ChildInfo->Type = 'Article';
		$response->Objects[0]->Relations[0]->ChildInfo->Format = 'application/incopyicml';
		$response->Objects[0]->Relations[0]->ObjectLabels = null;
		$response->Objects[0]->Pages = array();
		$response->Objects[0]->Files = null;
		$response->Objects[0]->Messages = null;
		$response->Objects[0]->Elements = array();
		$response->Objects[0]->Elements[0] = new Element();
		$response->Objects[0]->Elements[0]->ID = 'b5e5b56a-bad0-7328-f838-24c1fffe0cd8';
		$response->Objects[0]->Elements[0]->Name = 'head';
		$response->Objects[0]->Elements[0]->LengthWords = '0';
		$response->Objects[0]->Elements[0]->LengthChars = '0';
		$response->Objects[0]->Elements[0]->LengthParas = '1';
		$response->Objects[0]->Elements[0]->LengthLines = '0';
		$response->Objects[0]->Elements[0]->Snippet = '';
		$response->Objects[0]->Elements[0]->Version = '32bbf96d-db56-cc15-de4d-a2c45adb3590';
		$response->Objects[0]->Elements[0]->Content = null;
		$response->Objects[0]->Elements[1] = new Element();
		$response->Objects[0]->Elements[1]->ID = '898ad947-9cd5-a48a-6c74-8a5db0ba11cf';
		$response->Objects[0]->Elements[1]->Name = 'intro';
		$response->Objects[0]->Elements[1]->LengthWords = '0';
		$response->Objects[0]->Elements[1]->LengthChars = '0';
		$response->Objects[0]->Elements[1]->LengthParas = '1';
		$response->Objects[0]->Elements[1]->LengthLines = '0';
		$response->Objects[0]->Elements[1]->Snippet = '';
		$response->Objects[0]->Elements[1]->Version = 'cf0b7b5e-94c3-3247-58d1-8d08ff937403';
		$response->Objects[0]->Elements[1]->Content = null;
		$response->Objects[0]->Elements[2] = new Element();
		$response->Objects[0]->Elements[2]->ID = 'b018d8c6-147b-655a-113b-8e3498801ca4';
		$response->Objects[0]->Elements[2]->Name = 'body';
		$response->Objects[0]->Elements[2]->LengthWords = '0';
		$response->Objects[0]->Elements[2]->LengthChars = '0';
		$response->Objects[0]->Elements[2]->LengthParas = '1';
		$response->Objects[0]->Elements[2]->LengthLines = '0';
		$response->Objects[0]->Elements[2]->Snippet = '';
		$response->Objects[0]->Elements[2]->Version = '0875ea7a-0164-9f72-997d-2bdf3bca089a';
		$response->Objects[0]->Elements[2]->Content = null;
		$response->Objects[0]->Targets = array();
		$response->Objects[0]->Renditions = null;
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = array();
		$response->Objects[0]->MessageList->ReadMessageIDs = array();
		$response->Objects[0]->MessageList->DeleteMessageIDs = null;
		$response->Objects[0]->ObjectLabels = null;
		return $response;
	}

	/**
	 * Access the WflCreateObjectTargets service and compare the response
	 */
	private function WflCreateObjectTargets()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';
		$req = $this->WflCreateObjectTargetsRequest();
		$recResp = $this->WflCreateObjectTargetsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflCreateObjectTargets');

		if ( isset($curResp->IDs) ) {

			$this->objOrIssIds['WflCreateObjectTargets'] = $curResp->IDs;
			foreach ($curResp->IDs as $objectId) {
				$this->objOrIssNames[$objectId] = 'WflCreateObjectTargets'; // Just give it a name
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '001' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '001' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateObjectTargets response.');
			return;
		}
	}

	/**
	 * Compose WflCreateObjectTargetsRequest object.
	 *
	 * @return WflCreateObjectTargetsRequest
	 */
	private function WflCreateObjectTargetsRequest()
	{
		$request = new WflCreateObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objOrIssIds['WflCreateObjects'][0];
		$request->Targets = array();
		$request->Targets[0] = new Target();
		$request->Targets[0]->PubChannel = new PubChannel();
		$request->Targets[0]->PubChannel->Id = '1';
		$request->Targets[0]->PubChannel->Name = 'Print';
		$request->Targets[0]->Issue = new Issue();
		$request->Targets[0]->Issue->Id = '1';
		$request->Targets[0]->Issue->Name = '1st Issue';
		$request->Targets[0]->Issue->OverrulePublication = null;
		$request->Targets[0]->Editions = array();
		$request->Targets[0]->Editions[0] = new Edition();
		$request->Targets[0]->Editions[0]->Id = '1';
		$request->Targets[0]->Editions[0]->Name = 'North';
		$request->Targets[0]->Editions[1] = new Edition();
		$request->Targets[0]->Editions[1]->Id = '2';
		$request->Targets[0]->Editions[1]->Name = 'South';
		$request->Targets[0]->PublishedDate = null;
		$request->Targets[0]->PublishedVersion = null;
		return $request;
	}

	/**
	 * Compose WflCreateObjectTargetsResponse object to compare the test response.
	 *
	 * @return WflCreateObjectTargetsResponse
	 */
	private function WflCreateObjectTargetsResponse()
	{
		$response = new WflCreateObjectTargetsResponse();
		$response->IDs = array();
		$response->IDs[0] = '260306300';
		$response->Targets = array();
		$response->Targets[0] = new Target();
		$response->Targets[0]->PubChannel = new PubChannel();
		$response->Targets[0]->PubChannel->Id = '1';
		$response->Targets[0]->PubChannel->Name = 'Print';
		$response->Targets[0]->Issue = new Issue();
		$response->Targets[0]->Issue->Id = '1';
		$response->Targets[0]->Issue->Name = '1st Issue';
		$response->Targets[0]->Issue->OverrulePublication = false;
		$response->Targets[0]->Editions = array();
		$response->Targets[0]->Editions[0] = new Edition();
		$response->Targets[0]->Editions[0]->Id = '1';
		$response->Targets[0]->Editions[0]->Name = 'North';
		$response->Targets[0]->Editions[1] = new Edition();
		$response->Targets[0]->Editions[1]->Id = '2';
		$response->Targets[0]->Editions[1]->Name = 'South';
		$response->Targets[0]->PublishedDate = null;
		$response->Targets[0]->PublishedVersion = null;
		return $response;
	}

	/**
	 * Access the WflUpdateObjectTargets service and compare the response
	 */
	private function WflUpdateObjectTargets()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectTargetsService.class.php';
		$req = $this->WflUpdateObjectTargetsRequest();
		$recResp = $this->WflUpdateObjectTargetsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflUpdateObjectTargets');

		if ( isset($curResp->IDs) ) {

			$this->objOrIssIds['WflUpdateObjectTargets'] = $curResp->IDs;
			foreach ($curResp->IDs as $objectId) {
				$this->objOrIssNames[$objectId] = 'WflUpdateObjectTargets'; // Just give it a name
			}
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '049' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '049' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflUpdateObjectTargets response.');
			return;
		}

	}

	/**
	 * Compose WflUpdateObjectTargetsRequest object.
	 *
	 * @return WflUpdateObjectTargetsRequest
	 */
	private function WflUpdateObjectTargetsRequest()
	{
		$request = new WflUpdateObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objOrIssIds['WflCreateObjects'][0];
		$request->Targets = array();
		$request->Targets[0] = new Target();
		$request->Targets[0]->PubChannel = new PubChannel();
		$request->Targets[0]->PubChannel->Id = '1';
		$request->Targets[0]->PubChannel->Name = 'Print';
		$request->Targets[0]->Issue = new Issue();
		$request->Targets[0]->Issue->Id = '1';
		$request->Targets[0]->Issue->Name = '1st Issue';
		$request->Targets[0]->Issue->OverrulePublication = null;
		$request->Targets[0]->Editions = array();
		$request->Targets[0]->Editions[0] = new Edition();
		$request->Targets[0]->Editions[0]->Id = '1';
		$request->Targets[0]->Editions[0]->Name = 'North';
		$request->Targets[0]->PublishedDate = null;
		$request->Targets[0]->PublishedVersion = null;
		return $request;
	}

	/**
	 * Compose WflUpdateObjectTargetsResponse object to compare the test response.
	 *
	 * @return WflUpdateObjectTargetsResponse
	 */
	private function WflUpdateObjectTargetsResponse()
	{
		$response = new WflUpdateObjectTargetsResponse();
		$response->IDs = array();
		$response->IDs[0] = '260306300';
		$response->Targets = array();
		$response->Targets[0] = new Target();
		$response->Targets[0]->PubChannel = new PubChannel();
		$response->Targets[0]->PubChannel->Id = '1';
		$response->Targets[0]->PubChannel->Name = 'Print';
		$response->Targets[0]->Issue = new Issue();
		$response->Targets[0]->Issue->Id = '1';
		$response->Targets[0]->Issue->Name = '1st Issue';
		$response->Targets[0]->Issue->OverrulePublication = false;
		$response->Targets[0]->Editions = array();
		$response->Targets[0]->Editions[0] = new Edition();
		$response->Targets[0]->Editions[0]->Id = '1';
		$response->Targets[0]->Editions[0]->Name = 'North';
		$response->Targets[0]->PublishedDate = null;
		$response->Targets[0]->PublishedVersion = null;
		return $response;
	}

	/**
	 * Access the WflDeleteObjectTargets service and compare the response
	 */
	private function WflDeleteObjectTargets()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';
		$req = $this->WflDeleteObjectTargetsRequest();
		$recResp = $this->WflDeleteObjectTargetsResponse();

		// Using request instead of response because there's no IDs returned in response.
		if ( $req->IDs ) {

			$this->objOrIssIds['WflDeleteObjectTargets'] = $req->IDs;
			foreach ( $req->IDs as $objectId) {
				$this->objOrIssNames[$objectId] = 'WflDeleteObjectTargets'; // Just give it a name
			}
		}
		$curResp = $this->utils->callService( $this, $req, 'WflDeleteObjectTargets');

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '005' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '005' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjectTargets response.');
			return;
		}

	}

	/**
	 * Compose WflDeleteObjectTargetsRequest object.
	 *
	 * @return WflDeleteObjectTargetsRequest
	 */
	private function WflDeleteObjectTargetsRequest()
	{
		$request = new WflDeleteObjectTargetsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->objOrIssIds['WflCreateObjects'][0];
		$request->Targets = array();
		$request->Targets[0] = new Target();
		$request->Targets[0]->PubChannel = new PubChannel();
		$request->Targets[0]->PubChannel->Id = '1';
		$request->Targets[0]->PubChannel->Name = 'Print';
		$request->Targets[0]->Issue = new Issue();
		$request->Targets[0]->Issue->Id = '1';
		$request->Targets[0]->Issue->Name = '1st Issue';
		$request->Targets[0]->Issue->OverrulePublication = null;
		$request->Targets[0]->Editions = null;
		$request->Targets[0]->PublishedDate = null;
		$request->Targets[0]->PublishedVersion = null;
		return $request;
	}

	/**
	 * Compose WflDeleteObjectTargetsResponse object to compare the test response.
	 *
	 * @return WflDeleteObjectTargetsResponse
	 */
	private function WflDeleteObjectTargetsResponse()
	{
		$response = new WflDeleteObjectTargetsResponse();
		return $response;
	}

	/**
	 * Access the WflCreateObjectRelations service and compare the response
	 */
	private function WflCreateObjectRelations()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req = $this->WflCreateObjectRelationsRequest();
		$recResp = $this->WflCreateObjectRelationsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflCreateObjectRelations');

		if ( isset($curResp->Relations) && count($curResp->Relations) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflCreateObjectRelations'][0] = (int) $curResp->Relations[0]->Parent;
			$this->objOrIssIds['WflCreateObjectRelations'][1] = (int) $curResp->Relations[0]->Child;
			$this->objOrIssNames[$curResp->Relations[0]->Parent] = 'WflCreateObjectRelations';
			$this->objOrIssNames[$curResp->Relations[0]->Child] = 'WflCreateObjectRelations';
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '063' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '063' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflCreateObjectRelations response.');
			return;
		}
	}

	/**
	 * Compose WflCreateObjectRelationsRequest object.
	 *
	 * @return WflCreateObjectRelationsRequest
	 */
	private function WflCreateObjectRelationsRequest()
	{
		$request = new WflCreateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objOrIssIds['WflCreateDossier'][0];
		$request->Relations[0]->Child = $this->objOrIssIds['WflCreateArticle'][0];
		$request->Relations[0]->Type = 'Contained';
		$request->Relations[0]->Placements = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = array();
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;
		return $request;
	}

	/**
	 * Compose WflCreateObjectRelationsResponse object to compare the test response.
	 *
	 * @return WflCreateObjectRelationsResponse
	 */
	private function WflCreateObjectRelationsResponse()
	{
		$response = new WflCreateObjectRelationsResponse();
		$response->Relations = array();
		$response->Relations[0] = new Relation();
		$response->Relations[0]->Parent = '260306300';
		$response->Relations[0]->Child = '51';
		$response->Relations[0]->Type = 'Contained';
		$response->Relations[0]->Placements = array();
		$response->Relations[0]->ParentVersion = '0.1';
		$response->Relations[0]->ChildVersion = '0.1';
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = '0';
		$response->Relations[0]->Targets = array();
		$response->Relations[0]->Targets[0] = new Target();
		$response->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$response->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Relations[0]->Targets[0]->Issue = new Issue();
		$response->Relations[0]->Targets[0]->Issue->Id = '1';
		$response->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Relations[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Relations[0]->Targets[0]->Editions = array();
		$response->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$response->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$response->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Relations[0]->Targets[0]->PublishedVersion = null;
		$response->Relations[0]->Targets[0]->ExternalId = '';
		$response->Relations[0]->ParentInfo = null;
		$response->Relations[0]->ChildInfo = null;
		$response->Relations[0]->ObjectLabels = null;
		return $response;
	}

	/**
	 * Access the WflUpdateObjectRelations service and compare the response
	 */
	private function WflUpdateObjectRelations()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
		$req = $this->WflUpdateObjectRelationsRequest();
		$recResp = $this->WflUpdateObjectRelationsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflUpdateObjectRelations');

		if ( isset($curResp->Relations) && count($curResp->Relations) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflUpdateObjectRelations'][0] = (int) $curResp->Relations[0]->Parent;
			$this->objOrIssIds['WflUpdateObjectRelations'][1] = (int) $curResp->Relations[0]->Child;
			$this->objOrIssNames[$curResp->Relations[0]->Parent] = 'WflUpdateObjectRelations';
			$this->objOrIssNames[$curResp->Relations[0]->Child] = 'WflUpdateObjectRelations';
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '092' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '092' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflUpdateObjectRelations response.');
			return;
		}

	}

	/**
	 * Compose WflUpdateObjectRelationsRequest object.
	 *
	 * @return WflUpdateObjectRelationsRequest
	 */
	private function WflUpdateObjectRelationsRequest()
	{
		$request = new WflUpdateObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objOrIssIds['WflCreateObjectRelations'][0];
		$request->Relations[0]->Child = $this->objOrIssIds['WflCreateObjectRelations'][1];
		$request->Relations[0]->Type = 'Contained';
		$request->Relations[0]->Placements = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = array();
		$request->Relations[0]->Targets[0] = new Target();
		$request->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$request->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$request->Relations[0]->Targets[0]->Issue = new Issue();
		$request->Relations[0]->Targets[0]->Issue->Id = '1';
		$request->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$request->Relations[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Relations[0]->Targets[0]->Editions = array();
		$request->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$request->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$request->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$request->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$request->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$request->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$request->Relations[0]->Targets[0]->PublishedDate = null;
		$request->Relations[0]->Targets[0]->PublishedVersion = null;
		$request->Relations[0]->Targets[1] = new Target();
		$request->Relations[0]->Targets[1]->PubChannel = new PubChannel();
		$request->Relations[0]->Targets[1]->PubChannel->Id = '1';
		$request->Relations[0]->Targets[1]->PubChannel->Name = 'Print';
		$request->Relations[0]->Targets[1]->Issue = new Issue();
		$request->Relations[0]->Targets[1]->Issue->Id = '2';
		$request->Relations[0]->Targets[1]->Issue->Name = '2nd Issue';
		$request->Relations[0]->Targets[1]->Issue->OverrulePublication = null;
		$request->Relations[0]->Targets[1]->Editions = array();
		$request->Relations[0]->Targets[1]->Editions[0] = new Edition();
		$request->Relations[0]->Targets[1]->Editions[0]->Id = '1';
		$request->Relations[0]->Targets[1]->Editions[0]->Name = 'North';
		$request->Relations[0]->Targets[1]->Editions[1] = new Edition();
		$request->Relations[0]->Targets[1]->Editions[1]->Id = '2';
		$request->Relations[0]->Targets[1]->Editions[1]->Name = 'South';
		$request->Relations[0]->Targets[1]->PublishedDate = null;
		$request->Relations[0]->Targets[1]->PublishedVersion = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;
		return $request;
	}

	/**
	 * Compose WflUpdateObjectRelationsResponse object to compare the test response.
	 *
	 * @return WflUpdateObjectRelationsResponse
	 */
	private function WflUpdateObjectRelationsResponse()
	{
		$response = new WflUpdateObjectRelationsResponse();
		$response->Relations = array();
		$response->Relations[0] = new Relation();
		$response->Relations[0]->Parent = '260306300';
		$response->Relations[0]->Child = '50';
		$response->Relations[0]->Type = 'Contained';
		$response->Relations[0]->Placements = null;
		$response->Relations[0]->ParentVersion = '0.1';
		$response->Relations[0]->ChildVersion = '0.1';
		$response->Relations[0]->Geometry = null;
		$response->Relations[0]->Rating = '0';
		$response->Relations[0]->Targets = array();
		$response->Relations[0]->Targets[0] = new Target();
		$response->Relations[0]->Targets[0]->PubChannel = new PubChannel();
		$response->Relations[0]->Targets[0]->PubChannel->Id = '1';
		$response->Relations[0]->Targets[0]->PubChannel->Name = 'Print';
		$response->Relations[0]->Targets[0]->Issue = new Issue();
		$response->Relations[0]->Targets[0]->Issue->Id = '1';
		$response->Relations[0]->Targets[0]->Issue->Name = '1st Issue';
		$response->Relations[0]->Targets[0]->Issue->OverrulePublication = false;
		$response->Relations[0]->Targets[0]->Editions = array();
		$response->Relations[0]->Targets[0]->Editions[0] = new Edition();
		$response->Relations[0]->Targets[0]->Editions[0]->Id = '1';
		$response->Relations[0]->Targets[0]->Editions[0]->Name = 'North';
		$response->Relations[0]->Targets[0]->Editions[1] = new Edition();
		$response->Relations[0]->Targets[0]->Editions[1]->Id = '2';
		$response->Relations[0]->Targets[0]->Editions[1]->Name = 'South';
		$response->Relations[0]->Targets[0]->PublishedDate = '';
		$response->Relations[0]->Targets[0]->PublishedVersion = null;
		$response->Relations[0]->Targets[0]->ExternalId = '';
		$response->Relations[0]->Targets[1] = new Target();
		$response->Relations[0]->Targets[1]->PubChannel = new PubChannel();
		$response->Relations[0]->Targets[1]->PubChannel->Id = '1';
		$response->Relations[0]->Targets[1]->PubChannel->Name = 'Print';
		$response->Relations[0]->Targets[1]->Issue = new Issue();
		$response->Relations[0]->Targets[1]->Issue->Id = '2';
		$response->Relations[0]->Targets[1]->Issue->Name = '2nd Issue';
		$response->Relations[0]->Targets[1]->Issue->OverrulePublication = false;
		$response->Relations[0]->Targets[1]->Editions = array();
		$response->Relations[0]->Targets[1]->Editions[0] = new Edition();
		$response->Relations[0]->Targets[1]->Editions[0]->Id = '1';
		$response->Relations[0]->Targets[1]->Editions[0]->Name = 'North';
		$response->Relations[0]->Targets[1]->Editions[1] = new Edition();
		$response->Relations[0]->Targets[1]->Editions[1]->Id = '2';
		$response->Relations[0]->Targets[1]->Editions[1]->Name = 'South';
		$response->Relations[0]->Targets[1]->PublishedDate = '';
		$response->Relations[0]->Targets[1]->PublishedVersion = null;
		$response->Relations[0]->Targets[1]->ExternalId = '';
		$response->Relations[0]->ParentInfo = null;
		$response->Relations[0]->ChildInfo = null;
		$response->Relations[0]->ObjectLabels = null;
		return $response;
	}

	/**
	 * Access the WflDeleteObjectRelations service and compare the response
	 */
	private function WflDeleteObjectRelations()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectRelationsService.class.php';
		$req = $this->WflDeleteObjectRelationsRequest();
		$recResp = $this->WflDeleteObjectRelationsResponse();

		$curResp = $this->utils->callService( $this, $req, 'WflDeleteObjectRelations');

		if ( isset($req->Relations) && count($req->Relations) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['WflDeleteObjectRelations'][0] = (int) $req->Relations[0]->Parent;
			$this->objOrIssIds['WflDeleteObjectRelations'][1] = (int) $req->Relations[0]->Child;
			$this->objOrIssNames[$req->Relations[0]->Parent] = 'WflDeleteObjectRelations';
			$this->objOrIssNames[$req->Relations[0]->Child] = 'WflDeleteObjectRelations';
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '048' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '048' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflDeleteObjectRelations response.');
			return;
		}

	}

	/**
	 * Compose WflDeleteObjectRelationsRequest object.
	 *
	 * @return WflDeleteObjectRelationsRequest
	 */
	private function WflDeleteObjectRelationsRequest()
	{
		$request = new WflDeleteObjectRelationsRequest();
		$request->Ticket = $this->ticket;
		$request->Relations = array();
		$request->Relations[0] = new Relation();
		$request->Relations[0]->Parent = $this->objOrIssIds['WflCreateObjectRelations'][0];
		$request->Relations[0]->Child = $this->objOrIssIds['WflCreateObjectRelations'][1];
		$request->Relations[0]->Type = 'Related';
		$request->Relations[0]->Placements = null;
		$request->Relations[0]->ParentVersion = null;
		$request->Relations[0]->ChildVersion = null;
		$request->Relations[0]->Geometry = null;
		$request->Relations[0]->Rating = null;
		$request->Relations[0]->Targets = null;
		$request->Relations[0]->ParentInfo = null;
		$request->Relations[0]->ChildInfo = null;
		$request->Relations[0]->ObjectLabels = null;
		return $request;
	}

	/**
	 * Compose WflUpdateObjectTargetsResponse object to compare the test response.
	 *
	 * @return WflUpdateObjectTargetsResponse
	 */
	private function WflDeleteObjectRelationsResponse()
	{
		$response = new WflDeleteObjectRelationsResponse();
		return $response;
	}

	/**
	 * Access the AdmCreateIssues service and compare the response
	 */
	private function AdmCreateIssues()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$req = $this->AdmCreateIssuesRequest();
		$recResp = $this->AdmCreateIssuesResponse();

		$curResp = $this->utils->callService( $this, $req, 'AdmCreateIssues');

		if ( isset($curResp->Issues) && count($curResp->Issues) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmCreateIssues'][0] = (int) $curResp->Issues[0]->Id;
			$this->objOrIssNames[$curResp->Issues[0]->Id] = $curResp->Issues[0]->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '160' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '160' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmCreateIssues response.');
			return;
		}

	}

	/**
	 * Compose AdmCreateIssuesRequest object.
	 *
	 * @return AdmCreateIssuesRequest
	 */
	private function AdmCreateIssuesRequest()
	{
		$request = new AdmCreateIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = 1;
		$request->PubChannelId = 1;
		$request->Issues = array();
		$request->Issues[0] = new AdmIssue();
		$request->Issues[0]->Id = null;
		$request->Issues[0]->Name = 'Analytics Issue 3';
		$request->Issues[0]->Description = '';
		$request->Issues[0]->SortOrder = null;
		$request->Issues[0]->EmailNotify = null;
		$request->Issues[0]->ReversedRead = false;
		$request->Issues[0]->OverrulePublication = false;
		$request->Issues[0]->Deadline = '';
		$request->Issues[0]->ExpectedPages = 0;
		$request->Issues[0]->Subject = '';
		$request->Issues[0]->Activated = true;
		$request->Issues[0]->PublicationDate = '';
		$request->Issues[0]->ExtraMetaData = array();
		$request->Issues[0]->Editions = null;
		$request->Issues[0]->Sections = null;
		$request->Issues[0]->Statuses = null;
		$request->Issues[0]->UserGroups = null;
		$request->Issues[0]->Workflows = null;
		$request->Issues[0]->Routings = null;
		$request->Issues[0]->CalculateDeadlines = false;
		return $request;
	}

	/**
	 * Compose AdmCreateIssuesResponse object to compare the test response.
	 *
	 * @return AdmCreateIssuesResponse
	 */
	private function AdmCreateIssuesResponse()
	{
		$response = new AdmCreateIssuesResponse();
		$response->PublicationId = 1;
		$response->PubChannelId = 1;
		$response->Issues = array();
		$response->Issues[0] = new AdmIssue();
		$response->Issues[0]->Id = '260316000';
		$response->Issues[0]->Name = 'Analytics Issue 3';
		$response->Issues[0]->Description = '';
		$response->Issues[0]->SortOrder = '0';
		$response->Issues[0]->EmailNotify = null;
		$response->Issues[0]->ReversedRead = false;
		$response->Issues[0]->OverrulePublication = false;
		$response->Issues[0]->Deadline = '';
		$response->Issues[0]->ExpectedPages = '0';
		$response->Issues[0]->Subject = '';
		$response->Issues[0]->Activated = true;
		$response->Issues[0]->PublicationDate = '';
		$response->Issues[0]->ExtraMetaData = array();
		$response->Issues[0]->Editions = null;
		$response->Issues[0]->Sections = null;
		$response->Issues[0]->Statuses = null;
		$response->Issues[0]->UserGroups = null;
		$response->Issues[0]->Workflows = null;
		$response->Issues[0]->Routings = null;
		$response->Issues[0]->CalculateDeadlines = false;
		$response->Issues[0]->SectionMapping = array();
		return $response;
	}

	/**
	 * Access the AdmCreateIssues service and compare the response
	 */
	private function AdmCreateFatalExceptionIssueAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$req = $this->AdmCreateIssuesRequest();
		$recResp = $this->AdmCreateIssuesResponse();

		// Set value to check for in the plugin
		$req->Issues[0]->Name = 'Analytics Fatal Exception Issue Attempt';
		$recResp->Issues[0]->Name = 'Analytics Fatal Exception Issue Attempt';

		$curResp = $this->utils->callService( $this, $req, 'AdmCreateFatalExceptionIssueAttempt');

		if ( isset($curResp->Issues) && count($curResp->Issues) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmCreateFatalExceptionIssueAttempt'][0] = (int) $curResp->Issues[0]->Id;
			$this->objOrIssNames[$curResp->Issues[0]->Id] = $curResp->Issues[0]->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '160' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '160' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmCreateFatalExceptionIssueAttempt response.');
			return;
		}
	}

	/**
	 * Access the AdmCreateIssues service and compare the response
	 */
	private function AdmCreateInfoExceptionIssueAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$req = $this->AdmCreateIssuesRequest();
		$recResp = $this->AdmCreateIssuesResponse();

		// Set value to check for in the plugin
		$req->Issues[0]->Name = 'Analytics Info Exception Issue Attempt';
		$recResp->Issues[0]->Name = 'Analytics Info Exception Issue Attempt';

		$curResp = $this->utils->callService( $this, $req, 'AdmCreateInfoExceptionIssueAttempt');

		if ( isset($curResp->Issues) && count($curResp->Issues) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmCreateInfoExceptionIssueAttempt'][0] = (int) $curResp->Issues[0]->Id;
			$this->objOrIssNames[$curResp->Issues[0]->Id] = $curResp->Issues[0]->Name;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '160' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '160' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmCreateInfoExceptionIssueAttempt response.');
			return;
		}
	}

	/**
	 * Access the AdmModifyIssues service and compare the response
	 */
	private function AdmModifyIssues()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmModifyIssuesService.class.php';
		$req = $this->AdmModifyIssuesRequest();
		$recResp = $this->AdmModifyIssuesResponse();

		$curResp = $this->utils->callService( $this, $req, 'AdmModifyIssues');

		if ( isset($curResp->Issues) && count($curResp->Issues) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmModifyIssues'][0] = (int) $curResp->Issues[0]->Id;
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '005' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '005' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmModifyIssues response.');
			return;
		}

	}

	/**
	 * AdmModifyIssuesRequest object.
	 *
	 * @return AdmModifyIssuesRequest
	 */
	private function AdmModifyIssuesRequest()
	{
		$request = new AdmModifyIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->RequestModes = array();
		$request->PublicationId = '1';
		$request->PubChannelId = '1';
		$request->Issues = array();
		$request->Issues[0] = new AdmIssue();
		$request->Issues[0]->Id = $this->objOrIssIds['AdmCreateIssues'][0];
		$request->Issues[0]->Name = 'Analytics Issue 3 Modified';
		$request->Issues[0]->Description = '';
		$request->Issues[0]->SortOrder = '40';
		$request->Issues[0]->EmailNotify = null;
		$request->Issues[0]->ReversedRead = false;
		$request->Issues[0]->OverrulePublication = false;
		$request->Issues[0]->Deadline = '';
		$request->Issues[0]->ExpectedPages = 16;
		$request->Issues[0]->Subject = '';
		$request->Issues[0]->Activated = true;
		$request->Issues[0]->PublicationDate = '';
		$request->Issues[0]->ExtraMetaData[0] = new AdmExtraMetaData();
		$request->Issues[0]->ExtraMetaData[0]->Property = 'C_HIDDEN_DPS_DOSSIER_ORDER';
		$request->Issues[0]->ExtraMetaData[0]->Values = array();
		$request->Issues[0]->ExtraMetaData[0]->Values[0] = '260306303';
		$request->Issues[0]->ExtraMetaData[0]->Values[1] = '260306300';
		$request->Issues[0]->Editions = null;
		$request->Issues[0]->Sections = null;
		$request->Issues[0]->Statuses = null;
		$request->Issues[0]->UserGroups = null;
		$request->Issues[0]->Workflows = null;
		$request->Issues[0]->Routings = null;
		$request->Issues[0]->CalculateDeadlines = false;
		return $request;
	}

	/**
	 * Compose AdmModifyIssuesResponse object to compare the test response.
	 *
	 * @return AdmModifyIssuesResponse
	 */
	private function AdmModifyIssuesResponse()
	{
		$response = new AdmModifyIssuesResponse();
		$response->PublicationId = '1';
		$response->PubChannelId = '1';
		$response->Issues = array();
		$response->Issues[0] = new AdmIssue();
		$response->Issues[0]->Id = '1';
		$response->Issues[0]->Name = 'Analytics Issue 3 Modified';
		$response->Issues[0]->Description = '';
		$response->Issues[0]->SortOrder = '40';
		$response->Issues[0]->EmailNotify = null;
		$response->Issues[0]->ReversedRead = false;
		$response->Issues[0]->OverrulePublication = false;
		$response->Issues[0]->Deadline = '';
		$response->Issues[0]->ExpectedPages = '16';
		$response->Issues[0]->Subject = '';
		$response->Issues[0]->Activated = true;
		$response->Issues[0]->PublicationDate = '';
		$response->Issues[0]->ExtraMetaData = array();
		$response->Issues[0]->Editions = null;
		$response->Issues[0]->Sections = null;
		$response->Issues[0]->Statuses = null;
		$response->Issues[0]->UserGroups = null;
		$response->Issues[0]->Workflows = null;
		$response->Issues[0]->Routings = null;
		$response->Issues[0]->CalculateDeadlines = false;
		$response->Issues[0]->SectionMapping = array();
		return $response;
	}

	/**
	 * Access the AdmDeleteIssues service and compare the response
	 */
	private function AdmDeleteIssues()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$req = $this->AdmDeleteIssuesRequest( $this->objOrIssIds['AdmCreateIssues'] );
		$recResp = $this->AdmDeleteIssuesResponse();

		$curResp = $this->utils->callService( $this, $req, 'AdmDeleteIssues');

		if ( isset($req->IssueIds) && count($req->IssueIds) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmDeleteIssues'][0] = (int) $req->IssueIds[0];
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '165' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '165' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmDeleteIssues response.');
			return;
		}
	}

	/**
	 * Compose AdmDeleteIssuesRequest object.
	 *
	 * @param string[] $issueIds List of issue ids to be deleted.
	 * @return AdmDeleteIssuesRequest
	 */
	private function AdmDeleteIssuesRequest( $issueIds )
	{
		$request = new AdmDeleteIssuesRequest();
		$request->Ticket = $this->ticket;
		$request->PublicationId = '1';
		$request->IssueIds = $issueIds;
		return $request;
	}

	/**
	 * Compose AdmDeleteIssuesResponse object to compare the test response.
	 *
	 * @return AdmDeleteIssuesResponse
	 */
	private function AdmDeleteIssuesResponse()
	{
		$response = new AdmDeleteIssuesResponse();
		return $response;
	}

	/**
	 * Access the AdmDeleteIssues service and compare the response
	 */
	private function AdmDeleteFatalExceptionIssueAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$req = $this->AdmDeleteIssuesRequest( $this->objOrIssIds['AdmCreateFatalExceptionIssueAttempt'] );
		$recResp = $this->AdmDeleteIssuesResponse();

		// Set value to ID's we want to delete
		$req->IssueIds[0] = $this->objOrIssIds['AdmCreateFatalExceptionIssueAttempt'][0];

		$curResp = $this->utils->callService( $this, $req, 'AdmDeleteFatalExceptionIssueAttempt');

		if ( isset($req->IssueIds) && count($req->IssueIds) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmDeleteFatalExceptionIssueAttempt'][0] = (int) $req->IssueIds[0];
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '165' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '165' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmDeleteFatalExceptionIssueAttempt response.');
			return;
		}
	}

	/**
	 * Access the AdmDeleteIssues service and compare the response
	 */
	private function AdmDeleteInfoExceptionIssueAttempt()
	{
		require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$req = $this->AdmDeleteIssuesRequest( $this->objOrIssIds['AdmCreateInfoExceptionIssueAttempt'] );
		$recResp = $this->AdmDeleteIssuesResponse();

		// Set value to ID's we want to delete
		$req->IssueIds[0] = $this->objOrIssIds['AdmCreateInfoExceptionIssueAttempt'][0];

		$curResp = $this->utils->callService( $this, $req, 'AdmDeleteInfoExceptionIssueAttempt');

		if ( isset($req->IssueIds) && count($req->IssueIds) > 0) {
			// Keep track of created artifacts so we can tear down the process
			$this->objOrIssIds['AdmDeleteInfoExceptionIssueAttempt'][0] = (int) $req->IssueIds[0];
			$this->objOrIssNames[$req->IssueIds[0]] = 'Analytics Info Exception Issue Attempt';
		}

		require_once BASEDIR.'/server/utils/PhpCompare.class.php';
		$phpCompare = new WW_Utils_PhpCompare();
		$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
		if( !$phpCompare->compareTwoProps( $recResp, $curResp ) ) {
			$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', '165' );
			$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', '165' );
			$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
			$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
			$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
			$this->setResult( 'ERROR', $errorMsg, 'Error occured in AdmDeleteInfoExceptionIssueAttempt response.');
			return;
		}
	}

	/**
	 * Properties that needs to be ignored in WW_Utils_PhpCompare::compareTwoProps() should be declared here.
	 *
	 * @return array
	 */
	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true,
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true, 'Id' => true, 'ID' => true, 'IDs' => true,
			'IDs[0]' => true, 'Parent' => true, 'Child' => true,
			'DocumentID' => true, 'Description' => true,
			'ParentInfo' => true, 'ChildInfo' => true, 'ExtraMetaData' => true,
			'Modifier' => true, 'Creator' => true, 'LockedBy' => true,
			'Subject' => true,
		);
	}
}