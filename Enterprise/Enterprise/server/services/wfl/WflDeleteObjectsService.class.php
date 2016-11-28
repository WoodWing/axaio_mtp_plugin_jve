<?php
/**
 * DeleteObjects workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflDeleteObjectsService extends EnterpriseService
{
	private $isV7 = false;
	
	public function execute( WflDeleteObjectsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflDeleteObjects', 	
			true,  		// check ticket
			true	   	// use transaction
						// Delete does not modify the filestore*, so we can easily do a transaction accross the complete get
						// (* Note that a delete is not a purge)
			);
	}

	protected function restructureRequest( &$req )
	{
		$this->isV7=false;
		if ( count($req->Areas) == 0){ //for backward compatibility; v7 client won't understsand Area, so when it is Null, assume it is a v7 client and by default set Area to be Workflow
			$req->Areas = array('Workflow');
			$this->isV7 = true;
		}
		if( count($req->Areas) >1){
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Can only have one of the two areas: Workflow OR Trash' );
		}
		
		if( in_array('Trash',$req->Areas) && !$req->Permanent){
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Cannot have Area=Trash and Permanent=False combination');
		}
		
		// Do NOT allow users/clients (accidentally) delete all objects at workflow system
		if( is_null($req->IDs) && is_null($req->Params) && !$req->Permanent ) {
			throw new BizException( 'ERR_ARGUMENT', 'Server', 'Empty Param and Empty IDs are not allowed on Workflow area.');
		}
		
		// When there's a problem:
		// - Old clients expect SoapFaults.
		// - New clients expect ErrorReport. (Introduced in Ent Server v8.0)
		if( !$this->isV7 ) {
			$this->enableReporting();
		}
		
		// Resolve params into object IDs to avoid letting each server plug-ins fire QueryObjects individually (which would be very expensive)
		if( count( $req->Params ) > 0 // Delete or Purge queried objects?
			|| (is_null($req->IDs) && is_null($req->Params) && $req->Permanent && in_array('Trash',$req->Areas) ) ) { // Purge ALL accessible objects from Trash Can?
			if( !is_array( $req->IDs ) ) {
				$req->IDs = array();
			}
			
			// Note that nil is allowed for Params, but it is mandatory to QueryObjects, so fixed here.
			if( !is_array( $req->Params ) ) {
				$req->Params = array();
			}
			$minimalProps = array('ID', 'Type', 'Name');
			require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
			$response = BizQuery::queryObjects(
				$req->Ticket, $this->User, $req->Params, null, 0, null, false, null, $minimalProps, null, $req->Areas );
			// Determine column indexes to work with
			$indexes = array_combine( array_values($minimalProps), array_fill(0,count($minimalProps), -1) );//initialize all $minimalProps with -1.
			foreach( array_keys($indexes) as $colName ) {
				foreach( $response->Columns as $index => $column ) {
					if( $column->Name == $colName ) {
						$indexes[$colName] = $index;
						break; // found
					}
				}			
			}
			
			// Collect the object IDs from QueryObjects response
			if( $response->Rows ) foreach( $response->Rows as $row ) {
				$req->IDs[] = $row[$indexes['ID']];
			}
			$req->Params = null; // indicate we have resolved it, and from now use IDs only
			
			$req->IDs = array_unique($req->IDs); //To avoid duplicates ID incase $req->IDs are initially set by the client.
		}
	}

	public function runCallback( WflDeleteObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizDeletedObject.class.php';
		$errors = array();
		$successfulDeletedIds  = array();
		$ret=new WflDeleteObjectsResponse();
		if($req->IDs) {
			if( $this->isV7 ) { // Does not support ErrorReport, catch the soap faults if there's error.
				try {
					$successfulDeletedIds = BizDeletedObject::deleteObjects( $this->User, $req->IDs, $req->Permanent, $req->Areas, $req->Context );
					$ret->IDs = array();
				} catch ( BizException $e ) {
					$sCodes = array();
					preg_match_all( '/\((S[0-9]+)\)/', $e->getMessage(), $sCodes); //grab S(xxxx) error code (S-code) from localized message
					$sCode = count($sCodes[1]) > 0 ? $sCodes[1][count($sCodes[1])-1] : ''; // there should be only one S-code, but when many, take last one since those codes are at the end of message (=rule). 
					$errors[$sCode] = $e->getMessage();
				}
				
				if( count($errors) > 0 ) { // it doesn't support deleteObjects response, so just throw BizException.
					$faultString = implode( PHP_EOL,array_keys($errors));
					$faultDetails = implode( PHP_EOL,array_values($errors));
					throw new BizException( 'ERR_ARGUMENT', 'Client', $faultString, $faultDetails );
				}
			} else { // Supports ErrorReport, get the ErrorReports if there's error.
				$successfulDeletedIds = BizDeletedObject::deleteObjects( $this->User, $req->IDs, $req->Permanent, $req->Areas, $req->Context );
				$ret->IDs = $successfulDeletedIds;
				$ret->Reports = BizErrorReport::getReports();

			}
		} else {
			$ret->IDs = array(); // There is nothing to delete
		}
		return $ret;
	}
}
