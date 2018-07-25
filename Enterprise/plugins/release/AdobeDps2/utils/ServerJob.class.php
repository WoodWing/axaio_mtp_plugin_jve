<?php
/**
 * @since       v9.6
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Covers the utilities for the Adobe DPS plugin.
 */
class AdobeDps2_Utils_ServerJob
{
	/**
	 * Creates a server job of job type 'AdobeDps2' and push it into job queue.
	 *
	 * @param string $id The id of the layout.
	 * @param string $name The name of the layout.
	 * @param float $version The latest version of the layout.
	 * @param int $publicationId Publication id of the layout.
	 * @param string $publicationName Publication name of the layout.
	 * @param int $pubChannelId Publication channel id of the layout.
	 * @param string $pubChannelName Publication channel name of the layout.
	 */
	public function createServerJob( $id, $name, $version, 
		$publicationId, $publicationName, $pubChannelId, $pubChannelName )
	{
		// Set the upload status for the layout to "Pending". Only do when not busy uploading.
		require_once BASEDIR.'/server/dbclasses/DBSemaphore.class.php';
		$semaName = 'AdobeDps2_Publish_'.$id;
		$semaUser = DBSemaphore::getSemaphoreUser( $semaName );
		if( !$semaUser ) { // not busy uploading?
			require_once dirname(__FILE__).'/../bizclasses/Publishing.class.php';
			AdobeDps2_BizClasses_Publishing::updateStatusForLayout( $id, 
				AdobeDps2_BizClasses_Publishing::UPLOAD_STATUS_PENDING, $version );
		}
		
		// Compose the server job.
		require_once BASEDIR.'/server/dataclasses/ServerJob.class.php';
		$job = new ServerJob();
		$job->JobType = 'AdobeDps2';
		$job->JobData = array( 
			'ID' => $id, 
			'Name' => $name, 
			'Version' => $version, 
			'PublicationId' => $publicationId,
			'PublicationName' => $publicationName,
			'PubChannelId' => $pubChannelId,
			'PubChannelName' => $pubChannelName
		);
		self::serializeJobFieldsValue( $job );
		
		// Push the job into the queue.
		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
		$bizServerJob = new BizServerJob();
		$bizServerJob->createJob( $job );
	}

	/**
	 * Make sure the parameter $job passed in is ready for used by database.
	 *
	 * Mainly called when ServerJob Object needs to be passed to functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	public static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			if( self::validateJobFieldsValue( $job->JobData ) ) {
				$job->JobData = serialize( $job->JobData ) ;
			} else {
				$job->JobData = null;
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
	public static function unserializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData )) {
			$job->JobData = unserialize( $job->JobData );
			if( !self::validateJobFieldsValue( $job->JobData ) ) {
				$job->JobData = null;
			}
		}
	}

	/**
	 * The job data for AdobeDps2 jobs must be a layout id.
	 *
	 * @param array $jobData
	 * @return bool TRUE when data contains a positive number. Else FALSE.
	 */
	private static function validateJobFieldsValue( &$jobData )
	{
		$retVal = false;
		do {
			$layoutId = $jobData['ID'];
			if( !ctype_digit( strval($layoutId) ) || $layoutId <= 0 ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No valid layout id found in job data: '.print_r($jobData,true) );
				break;
			}
			$jobData['ID'] = intval( $jobData['ID'] );

			if( !$jobData['Name'] ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No valid layout name found in job data: '.print_r($jobData,true) );
				break;
			}

			if( !$jobData['Version'] ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No valid layout version found in job data: '.print_r($jobData,true) );
				break;
			}

			if( !$jobData['PublicationId'] || $jobData['PublicationId'] <= 0 ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No publication id found in job data: '.print_r($jobData,true) );
				break;
			}

			if( !$jobData['PublicationName'] ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No valid publication name found in job data: '.print_r($jobData,true) );
				break;
			}

			if( !$jobData['PubChannelId'] || $jobData['PubChannelId'] <= 0 ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No publication channel id found in job data: '.print_r($jobData,true) );
				break;
			}

			if( !$jobData['PubChannelName'] ) {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'No valid publication channel name found in job data: '.print_r($jobData,true) );
				break;
			}

			$retVal = true;
		} while( false ); // once only
		return $retVal;
	}
}
