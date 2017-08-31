<?php
/**
 * @package Enterprise
 * @subpackage Core
 * @since v9.7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Entry point to start processing InDesign Server background jobs from the queue.
 *
 * This module can either dispath jobs or process a job. When no params given, it
 * will dispatch jobs. When the command=rundispatchedjob param is given it will 
 * process a job, for which additional params jobid, serverid and locktoken are required.
 *
 * The dispatcher should repeatedly run every minute for a duration of one minute.
 * This can be setup with help of a Scheduler (Windows) or a Crontab (Unix).
 * Make sure the Scheduler/Crontab starts automatically after machine has booted.
 */

require_once dirname(__FILE__).'/config/config.php';
set_time_limit(3600);    // Run this PHP script for at least one hour.
ignore_user_abort(true); // Disallow the Scheduler/Crontab or cURL to end the job dispatcher or processor.

try {
	$command = isset($_GET['command']) ? strval($_GET['command']) : 'dispatchjobs';
	switch( $command ) {
		case 'dispatchjobs':

			// Accept HTTP parameters at URL.
			$options = array();
			if( isset($_GET['sleeptime']) ) {
				$options['sleeptime'] = intval($_GET['sleeptime']);
			}
			if( isset($_GET['maxexectime']) ) {
				$options['maxexectime'] = intval($_GET['maxexectime']);
			}

			// Process the server job request.
			require_once BASEDIR.'/server/bizclasses/BizInDesignServerDispatcher.class.php';
			$processor = new BizInDesignServerDispatcher( $options );
			$processor->handle();
			break;
			
		case 'rundispatchedjob':
			$jobId     = isset($_GET['jobid'])     ? strval($_GET['jobid']) : '';
			$serverId  = isset($_GET['serverid'])  ? intval($_GET['serverid']) : '';
			$lockToken = isset($_GET['locktoken']) ? strval($_GET['locktoken']) : '';
			if( !$jobId || !$serverId || !$lockToken ) {
				throw new BizException( 'ERR_ARGUMENT', 'Server', 
					'Invalid params provided for the '.$command.' ('.basename(__FILE__).' module).' );
			}
			require_once BASEDIR.'/server/bizclasses/BizInDesignServerJob.class.php';
			BizInDesignServerJobs::runDispatchedJob( $jobId, $serverId, $lockToken );
			break;

		default:
			throw new BizException( 'ERR_ARGUMENT', 'Server', 
				'Invalid params provided for the '.basename(__FILE__).' module.' );
	}
} catch( BizException $e ) {
}