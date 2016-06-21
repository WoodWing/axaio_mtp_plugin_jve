<?php
/**
 * This is a InDesignServerJob demo server plugin.
 * In this demo, it will show the connector can decide whether to skip the job creation by core server.
 * Beside this, it will show the connector can overrule and extend the job property.
 *
 * @package Enterprise
 * @subpackage ServerPlugins
 * @since v9.8
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/interfaces/plugins/connectors/InDesignServerJob_EnterpriseConnector.class.php';

class InDesignServerJobDemo_InDesignServerJob extends InDesignServerJob_EnterpriseConnector
{
	/**
	 * The server plugin connector allow to set to skip the InDesign Server Job creation.
	 * It will check if the InDesign Server Job is a IDS_AUTOMATION job type and the user who has pushed the IDS job,
	 * initiator is 'guest', then it will return true to skip the job creation.
	 *
	 * @param InDesignServerJob $job
	 * @return bool True to skip the job creation, else false
	 */
	public function skipJobCreation( InDesignServerJob $job )
	{
		if( $job->JobType == 'IDS_AUTOMATION' && $job->Initiator == 'guest' ) {
			return true;
		 }
		return false;
	}

	/**
	 * The server plugin connector will check if the InDesign Server Job is a IDS_AUTOMATION job type.
	 * When it does, then compare the job MinServerVersion to check it is require older version of InDesign Server.
	 * When it does, get the job JobScript, modifying by add in a message of converting the layout to CC 2014 document.
	 * Set the MinServerVersion and MaxServerVersion to CC 2014, so that this job will be pick by CC 2014 IDS.
	 * Set the job priority = 1[Very high], so that it can pick up and process by the IDS sooner.
	 *
	 * @param InDesignServerJob $job
	 */
	public function beforeCreateJob( InDesignServerJob $job )
	{
		if( $job->JobType == 'IDS_AUTOMATION' ) {
			if( version_compare($job->MinServerVersion, '10.0', '<') ) {
				$job->MinServerVersion = '10.0';
				$job->MaxServerVersion = '10.9';
				$job->JobPrio = 1;
				$job->JobParams['message'] = 'Converting a layout document of previous version to CC 2014 document.';
				$scriptText = $job->JobScript;
				$scriptText = str_replace( 'var pServer', 'var pMessage = app.scriptArgs.get("message")'.PHP_EOL.'var pServer', $scriptText );
				$scriptText = str_replace( 'wwlog( CONSOLE, \'----------------\' );', 'wwlog( CONSOLE, \'----------------\' );'.
											PHP_EOL.'wwlog( CONSOLE, pMessage );', $scriptText);
				$job->JobScript = $scriptText;
			}
		}
	}
}
