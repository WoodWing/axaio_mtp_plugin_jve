<?php
/**
 * @since 		v10.1.3
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/interfaces/plugins/connectors/InDesignServerJob_EnterpriseConnector.class.php';

class AutomatedPrintWorkflow_InDesignServerJob extends InDesignServerJob_EnterpriseConnector
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
		if( $job->JobType == 'IDS_AUTOMATION' ) {
			require_once BASEDIR.'/server/bizclasses/BizObjectOperation.class.php';
			$currentOperations = BizObjectOperation::getOperations( $job->ObjectId );
			// We should keep the last created operation for the frame. So we have to reverse the order. We get oldest first from the db.
			$reversedOps = array_reverse($currentOperations);

			$frameOperations = array();
			if( $currentOperations ) foreach( $reversedOps as $operation ) {
				$id = $operation->Id;
				$editionId = $splineId = null;
				if( $operation->Params ) foreach( $operation->Params as $param ) {
					switch($param->Name) {
						case 'EditionId':
							$editionId = $param->Value;
							break;
						case 'SplineId':
							$splineId = $param->Value;
							break;
					}
				}

				// We should only delete operations when they are created by the AutomatedPrintWorkflow plugin and when it is a 'known' operation.
				if( $operation->Type == 'AutomatedPrintWorkflow'
					&& in_array( $operation->Name, array( 'ClearFrameContent', 'PlaceImage', 'PlaceArticleElement' ) ) ) {
					$splineEditionId = $splineId . '-' . $editionId;
					if( in_array( $splineEditionId, $frameOperations ) ) {
						// If there is already a operation for the SplineId (and edition) then we should delete the older ones.
						// The operations repair themselves. So when a PlaceImage will place the image if it is different and then
						// crop it for example. 
						BizObjectOperation::deleteOperation( $job->ObjectId, $id );
						continue;
					}
					$frameOperations[] = $splineEditionId;
				}
			}
		}
	}

	public function getPrio() { return self::PRIO_DEFAULT; }


}
