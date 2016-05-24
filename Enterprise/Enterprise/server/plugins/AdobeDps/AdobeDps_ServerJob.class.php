<?php
/****************************************************************************
   Copyright 2013 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR . '/server/interfaces/plugins/connectors/ServerJob_EnterpriseConnector.class.php';
require_once BASEDIR . '/config/config_dps.php';

class AdobeDps_ServerJob extends ServerJob_EnterpriseConnector
{
	/**
	 * The job handler (server plug-in connector) tells the core server how to the job must be handled.
	 * The Id, JobType and ServerType are overruled by the core and not be changed.
	 * Other properties can be set and are configurable by system admin users.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run.
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig )
	{
		$jobConfig->NumberOfAttempts = 5;
		$jobConfig->SysAdmin = true;
		$jobConfig->Recurring = true;
		$jobConfig->Active = true;
		$jobConfig->UserId = 0;
		$jobConfig->WorkingDays = false;
		$jobConfig->JobType = 'AdobeDps';
	}

	/**
	 * Called by BizServerJob when a server job is picked up from the queue
	 * and needs to be run by the job handler implementing this interface.
	 * The handler should update the status through $job->JobStatus->setStatus().
	 * Scan the export directory for the folders containing Adobe Dps folios
	 * generated by the "Preview' within CS. These folders will be deleted
	 * if they are older than expiration time of a ticket.
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job )
	{
		self::unserializeJobFieldsValue( $job );
		foreach ( glob( ADOBEDPS_EXPORTDIR.'user_*', GLOB_MARK ) as $path ) {
			$handle = opendir( $path );
			if ( $handle ) {
    			while (false !== ( $entry = readdir($handle ))) {
					if ( substr( $entry, 0, 1 ) != '.' ) { //Skip hidden files, current and parent directory
						$this->deleteFolder( $path.$entry );
					}
    			}
    		}
  	  		closedir($handle);
		}

		require_once BASEDIR.'/server/dataclasses/ServerJobStatus.class.php';
		$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );

		self::serializeJobFieldsValue( $job );
	}

	/**
	 * Called by BizServerJob when a server job has to be created.
	 *
	 * @param boolean $pushIntoQueue True to insert the job into job queue. False to just return the constructed job.
	 * @since 8.3
	 */
	public function createJob( $pushIntoQueue )
	{
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$job = new ServerJob();
		$job->JobType = 'AdobeDps';
		self::serializeJobFieldsValue( $job );

		if( $pushIntoQueue ) {
			// Push the job into the queue (for async execution)
			require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
			$bizServerJob = new BizServerJob();
			$bizServerJob->createJob( $job );
		}
		return $job;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }

	/**
	 * Deletes a given folder from the Export Folder.
	 * When it cannot be deleted due to any problem (e.g. an access problem),
	 * an ERROR entry is created in server logging.
	 * When the ffolder does not exists, assumed is that the file was deleted before.
	 *
	 * @param string $filePath File to delete.
	 * @param boolean $deleted TRUE when the file was ours and could be deleted (or was deleted before).
	 */
	private function deleteFolder( $filePath )
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$status = stat( $filePath );
		if( $status && $status['atime'] < (time() - EXPIREDEFAULT) ) {
			$deleted = FolderUtils::cleanDirRecursive($filePath, true);
			if ( !$deleted ) {
				LogHandler::Log( 'AdobeDps', 'ERROR',
					'The folder "' . $filePath . '" is expired but cannot be deleted. ' .
					'Please ensure there is enough access rights to the file and folder.' );
			}
		}
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