<?php
/**
 * GetPages workflow business service.
 *
 * @package SCEnterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetPagesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetPagesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetPagesService extends EnterpriseService
{
	public function execute( WflGetPagesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetPages', 	
			true,  		// check ticket
			false   	// no transaction, it's a get function
			);
	}

	public function runCallback( WflGetPagesRequest $req )
	{
		// Validate the request parameters.
		// Rules are introduced in patch release (8.3.3), so we log warnings only instead of:
		//    throw new BizException( 'ERR_INVALID_OPERATION', 'Client', '...' );
		if( !$req->IDs ) {
			LogHandler::Log( 'GetPages', 'WARN', 
				'For the GetPages request, the IDs parameter should be provided.' );
		}
		if( !is_null($req->Params) || !is_null($req->PageOrders) || !is_null($req->PageSequences) || 
			!is_null($req->RequestMetaData) || !is_null($req->RequestFiles) ) {
			LogHandler::Log( 'GetPages', 'WARN', 
				'For the GetPages request, the Params, PageOrders, PageSequences, RequestMetaData and '.
				'RequestFiles parameters are no longer supported. Should be left out (or set to nil).' );
		}
		
		// Check if the QueryParams just contains IssueId and/or Type. Then we all understood.
		$issueId = 0;
		$objTypeFilter = false;
		$allUnderstood = false;
		if( $req->Params ) {
			$understood = 0;
			foreach( $req->Params as $queryParam ) {
				if( $queryParam->Operation == '=' ) {
					switch( $queryParam->Property ) {
						case 'IssueId':
							$issueId = intval( $queryParam->Value );
							$understood++;
						break;			
						case 'Type':
							if( $queryParam->Value == 'Layout' ) {
								$understood++;
							}
							$objTypeFilter = true;
						break;			
					}
				}
			}
			$allUnderstood = ($understood == count( $req->Params ));
		}
		
		// If the QueryParams is not understood, then perform expensive QueryObjects 
		// to resolve the layout ids.
		$layoutIds = $req->IDs ? $req->IDs : array();
		if( $req->Params && !$allUnderstood ) {
		
			LogHandler::Log( 'GetPages', 'WARN', 'There are more QueryParams given than direcly understood. '.
				'(Only IssueId and Type are understood.) As a result, an expensive QueryObjects is called '.
				'internally to resolve the object ids. Please reconcider the QueryParams request parameter. ' );
		
			// Only layout objects have pages to the shown at Publication Overview.
			if( !$objTypeFilter ) {
				$req->Params[] = new QueryParam( 'Type', '=', 'Layout', false );
			}

			// This could be more efficient, but this is easy:
			require_once BASEDIR."/server/bizclasses/BizQuery.class.php";
			$resp = BizQuery::queryObjects( $req->Ticket,
											$this->User,
											$req->Params,
											1,     // First entry
											0,     // Max entries
											false, // Deleted objects
											null,  // Force App
											false, // Hierarchical
											null,  // Order
											null,  // Minimal properties
											array( 'ID', 'Type', 'Name' ), // Requested properties
											null,  // Areas
											11 );  // Access right
			
			// Determine the object ID column index
			$idIdx = 0;
			if( isset($resp->Columns) ) foreach( $resp->Columns as $col ) {
				if( $col->Name == 'ID' ) {
					break; // found!
				}
				$idIdx++;
			}
			
			// Collect the retrieved object IDs
			if( isset($resp->Rows) ) foreach( $resp->Rows as $row ) {
				$layoutIds[] = $row[$idIdx];
			}
		}

		// Call the biz layer to query the pages.
		require_once BASEDIR.'/server/bizclasses/BizPage.class.php';
		$retobj = BizPage::getPages2( 
			$issueId,
			isset($req->Edition->Id) ? $req->Edition->Id : null,
			$layoutIds,
			$req->Renditions );
			
		return new WflGetPagesResponse( $retobj );
	}
}
