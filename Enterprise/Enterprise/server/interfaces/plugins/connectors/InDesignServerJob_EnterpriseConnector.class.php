<?php
/**
 * @package 	Enterprise
 * @subpackage  ServerPlugins
 * @since 		v9.8
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * InDesignServerJob is a business connector interface that can be implemented by a server plug-in connector.
 * It enables the connector to skip the InDesignServerJob creation, and to overrule or extend
 * the InDesignServerJob before job creation by core.
 * 
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class InDesignServerJob_EnterpriseConnector extends DefaultConnector
{
	/**
	 * Enables connector to control over the creation of a InDesign Server Job.
	 * Called before the InDesign Server job creation by core server, when function return true,
	 * core server will skip the job creation.
	 * Connector can check on the job properties to decide whether to skip the job creation.
	 * For example, skip the job creation when JobType is IDS_AUTOMATION,
	 * if( $job->JobType == ‘IDS_AUTOMATION’ ) {
	 *      return true;
	 * }
	 *
	 * @param $job
	 * @return bool Return true to skip job creation, else false.
	 */
	public function skipJobCreation( InDesignServerJob $job )
	{
		$job = $job;
		return false;
	}

	/**
	 * Enables connector to overrule or further extend the InDesign Server Job.
	 *
	 * There are more things you can do when the $job parameter is passed in to connector.
	 * You can check, overrule and extend the job properties.
	 *
	 * For example,
	 * - Check on $job->JobType, to determine what next action should be taken.
	 *
	 * - Check for 'IDS_AUTOMATION' jobs:
	 * In $job->Context you can find the (placed) object that triggered the creation: "$objectType $objectId”
	 * if( $job->JobType == ‘IDS_AUTOMATION’ ) {
	 *      list( $objectType, $objectId ) = explode( ' ', $job->Context );
	 *  	if( $objectType == 'Article' ) {
	 *      	...
	 *  	}
	 * 	}
	 * In $job->ObjectId you can find the layout that is about to get processed.
	 *
	 * - You can overrule $job->JobScript by injecting your own JS module.
	 *   With this you could do e.g. preflight checking in IDS after opening the layout.
	 *
	 * - You can extend $job->JobParams with extra parameters for that JS module.
	 *
	 * Migrate old documents into CC 2014 (currently only does an exact match between the layout document version and the IDS instance version):
	 * if( $job->JobType == 'IDS_AUTOMATION' ) {
	 *      if( version_compare( $job->MinServerVersion, '10.0', '<' ) ) {
	 *      	$job->MinServerVersion = '10.0';
	 *          $job->MaxServerVersion = '10.9';
	 *      }
	 * }
	 *
	 * @param InDesignServerJob $job
	 */
	public function beforeCreateJob( InDesignServerJob $job )
	{
		$job = $job;
	}

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); }
	final public function getInterfaceVersion() { return 1; }
}