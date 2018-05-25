<?php
/**
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * Handle all server jobs that are related to Enterprise Object.
 */

require_once BASEDIR.'/server/bizclasses/BizServerJobHandler.class.php';
class WW_BizClasses_ObjectJob extends BizServerJobHandler
{
	
	/**
	 * Implementation of BizServerJobHandler::getJobConfig() abstract.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run. 
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		// Nothing to do.
	}
	
	/**
	 * Implementation of BizServerJobHandler::runJob() abstract.
	 * Called by BizServerJob when a server job is picked up from the queue.
	 *
	 * @param ServerJob $job
	 */
	 public function runJob( ServerJob $job )
	{
		self::unserializeJobFieldsValue( $job );
		switch( $job->JobType )
		{
			/*UpdateParentModifierAndModified:
			* Updates the parent fields (modifier/modified OR deletor/deleted),
	 		* update on the indexing and broadcast the updated parent ID in the background.
	 		*/
			case 'UpdateParentModifierAndModified':
				require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
				$jobStatusId = BizObject::updateParentObject( $job );
				break;
			default:
				/**
				 * It should never enter this part. Every job should has its JobType ( $job->JobType).
				 * Each jobType should be delegated to its respective job(method/function) to 
				 * run the task.
				 * Entering here indicates the specific jobType has no one to handle it yet, which is not correct.
				 */
				$jobStatusId = ServerJobStatus::DISABLED; // actually didn't run anything and shouldn't enter here.
		}
		$job->JobStatus->setStatus( $jobStatusId );

		self::serializeJobFieldsValue( $job );
	}
	
	
	/**
	 * To create a background job to update parent object in the background.
	 * When an object is created/saved/deleted inside a dossier/dossier template/task OR
	 * object relation is created/updated/deleted inside/from a dossier/dossier template/task
	 * the parent (dossier/dossier template/task) has to be updated.
	 * The updates include update parents' modifier&modified field, update indexing and broadcast.
	 * 
	 * To do these updates in the foreground, it slows down performance, and therefore we 
	 * pass it to background server job.
	 * This method will create an entry in the serverJob so that the serverJob will pick up 
	 * this task and run it in the background.
	 * 
	 * We pass in either parentId OR childId to server job.
	 * When following Id is passed:
	 * parentId: the parent gets updated directly. (For create/update/delete objectRelations)
	 * OR
	 * childId: the parent of this childId gets updated directly.(For this case, the parent of the
	 * child will be searched before updating the parents itself) (For other services not mentioned above)
	 * 
	 * The reason we don't pass in parentId directly is because there could be thousands of
	 * parents to be passed into server job (thousands of server jobs created!), so we pass in
	 * childId (always 1) and when the server job picks up this task, it will find for its parents 
	 * ( could be up to thousands) and update the parents.
	 * 
	 * However, for deleteObjectRelations, the parent is detached from the child by the time 
	 * server job picks up the task, and therefore the parent cannot be updated. In this case, we
	 * cannot pass in the childId but the parentId (before it gets detached).
	 * 
	 * For createObjectRelations, updateObjetRelations, we pass in the parent instead of child because
	 * for an example, when one childObject is removed from a doosierA, we only want to update dossierA
	 * but not all the parents of childObject.
	 * 
	 * When 'child' is passed in, we will pass in childId to the server job, else when 'parent' is passed,
	 * we pass in the parentId and during the background server job, it updates the parent directly.
	 *
	 * @param int $childId
	 * @param int $parentId
	 */
	public function createUpdateTaskInBgJob( $childId, $parentId )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$jobObjId = ( !is_null($childId) ) ? $childId // we pass in childId and update its parent later in the background server job
					: $parentId; // we pass in parentId and update the parent directly
						
		$context = ( is_null($childId) ) ? 'parent' : 'child'; // to indicate whether the objId passed in is a child or parent.

		// Prepare $job->JobData (consists of ObjectId )
		$jobData = array( 'objectid' => $jobObjId );

		// Prepare a new server job
		$job = new ServerJob();
		$job->Context = $context;
		$job->JobType = 'UpdateParentModifierAndModified';
		$job->JobData = $jobData;
		$job->DataEntity = 'objectid'; // Only consists of object id, no object version.
		self::serializeJobFieldsValue( $job );

		// Push the job into the queue (for async execution)
		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$bizServerJob = new BizServerJob();
		$bizServerJob->createJob( $job );
	}


	/**
	 * Prepare ServerJob (parameter $job) to be ready for use by the caller.
	 *
	 * The parameter $job is returned from database as it is (i.e some data might be
	 * serialized for DB storage purposes ), this function make sure all the data are
	 * un-serialized.
	 *
	 * Mainly called when ServerJob Object is passed from functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function unserializeJobFieldsValue( ServerJob $job )
	{
		// Make sure to include the necessary class file(s) here, else it will result into
		// 'PHP_Incomplete_Class Object' during unserialize.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		if( !is_null( $job->JobData )) {
			$job->JobData = unserialize( $job->JobData );
		}
	}

	/**
	 * Make sure the parameter $job passed in is ready for used by database.
	 *
	 * Mainly called when ServerJob Object needs to be passed to functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = serialize( $job->JobData ) ;
		}
	}
	
}