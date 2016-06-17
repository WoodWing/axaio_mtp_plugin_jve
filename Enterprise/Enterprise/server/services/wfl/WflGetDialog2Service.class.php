<?php
/**
 * GetDialog2 Workflow service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2Request.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2Response.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetDialog2Service extends EnterpriseService
{
	public function execute( WflGetDialog2Request $req )
	{
		// Fire the core service
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetDialog2', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}
	
	protected function restructureRequest( &$req )
	{
		if( is_null( $req->MultipleObjects) ) { // @since v9.2
			$req->MultipleObjects = false;
		}
		// Repair and enrich user typed MetaDataValues -here- to expose it to server plugins as well
		// for customization conveniences (easy data access through keys) and the sake of consistency.
		$req->MetaData = self::enrichMetaDataValues( $req->MetaData, $req->MultipleObjects );

	}

	private static function enrichMetaDataValues( $metaData, $multipleObjects )
	{
		// Keys are numeric; Change them into internal property names.
		$retVal = array();
		if( $metaData ) foreach( $metaData as $mdVals ) {
			$retVal[$mdVals->Property] = $mdVals;
		}
		
		// Make sure some mandatory properties exist; Fill in values when missing.
		$manKeys = array();
		if( !$multipleObjects ) {
			// Only repair for non-multi objects request.
			// For multi-objects, it is expected that mandatory fields are sent in, and therefore no repairing will be done.
			$manKeys = array( 'ID', 'Publication', 'Issue', 'Category', 'State', 'Type' );
		}
		if( $manKeys ) foreach( $manKeys as $manKey ) {
			if( !array_key_exists( $manKey, $retVal ) ) {
				$mdVal = new MetaDataValue();
				$mdVal->Property = $manKey;
				$mdVal->Values = null;
				$mdVal->PropertyValues = array( new PropertyValue( '' ) );
				$retVal[$manKey] = $mdVal;
			}
		}
		return $retVal;
	}
	
	public function runCallback( WflGetDialog2Request $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizWorkflow.class.php';
		$reqMetaData = $req->MultipleObjects ? false : true;
		$reqTargets = $req->MultipleObjects ? false : true;
		$retVal = BizWorkflow::getDialog(
			$this->User,
			$req->Action,
			$req->MetaData,
			$req->Targets,
			true,  // $reqDialog
			true, // $reqPub
			$reqMetaData,  // $reqMetaData
			false, // $reqStates
			$reqTargets,  // $reqTargets (including RelatedTargets)
			$req->DefaultDossier,
			$req->Parent,
			$req->Template,
			$req->Areas,
			$req->MultipleObjects );
		
		return new WflGetDialog2Response( 
			$retVal['Dialog'],
			$retVal['PubChannels'],
			$retVal['MetaData'],
			$retVal['Targets'],
			$retVal['RelatedTargets'],
			$retVal['Relations']);
	}
}
