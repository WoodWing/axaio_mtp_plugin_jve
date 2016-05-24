<?php
/**
 * GetObjects workflow business service.
 *
 * @package Enterprise
 * @subpackage WorkflowServices
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class WflGetObjectsService extends EnterpriseService
{
	/**
	 * {@inheritdoc}
	 */
	public function execute( WflGetObjectsRequest $req )
	{
		// Run the service
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'WorkflowService',
			'WflGetObjects', 	
			true,  		// check ticket
			true	   	// use transactions
			);
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function restructureResponse( $req, &$resp )
	{
		$req = $req; // Keep analyzer happy.
		
		// Restructure messages from 8.0 (or newer) to 7.x (or older), to make old clients happy.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$clientMajorVersion = intval( BizSession::getClientVersion( null, null, 1 ) );
		if( $clientMajorVersion && $clientMajorVersion <= 7 ) { // 7.x (or older) => restructure!
			if( $resp->Objects ) foreach( $resp->Objects as $object ) {
				if( $object->MessageList ) {
					$object->Messages = $object->MessageList->Messages;
					$object->MessageList = null;
				}
			}
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function runCallback( WflGetObjectsRequest $req )
	{
		// BZ#6021 Don't fail when more then one object is requested and one of them fails (not found for example)
		$retobj = array();
		$objCount = count($req->IDs) + count($req->HaveVersions);
		require_once BASEDIR.'/server/bizclasses/BizObject.class.php';
		if( count($req->IDs) > 0 ) {
			foreach( $req->IDs as $id ) {
				try {
					$curobj = BizObject::getObject( $id, $this->User /* from super class */, 
									$req->Lock, $req->Rendition, $req->RequestInfo, null, 
									true, $req->Areas, $req->EditionId, true, $req->SupportedContentSources );
					$retobj[] = $curobj;	
				}
				catch (BizException $e) {
					// keep behavior the same as before when only one object requested.
					if( $objCount == 1 ) {
						throw( $e );
					}
				}
			}
		}
		if( count($req->HaveVersions) > 0 ) {
			foreach( $req->HaveVersions as $haveVersion ) {
				try {
					$curobj = BizObject::getObject( $haveVersion->ID, $this->User /* from super class */, 
									$req->Lock, $req->Rendition, $req->RequestInfo, $haveVersion->Version, 
									true, $req->Areas, $req->EditionId, true, $req->SupportedContentSources );
					$retobj[] = $curobj;
				}
				catch (BizException $e) {
					// keep behavior the same as before when only one object requested.
					if( $objCount == 1 ) {
						throw( $e );
					}
				}
			}
		} 

		return new WflGetObjectsResponse( $retobj );
	}
}
