<?php
/**
 * DeleteStatuses Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteStatusesRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeleteStatusesResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeleteStatusesService extends EnterpriseService
{
	private $pubId = null;
	private $issueId = null;

	protected function restructureRequest( &$req )
	{
		if( !$req->StatusIds ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No status ids were given.' );
		}
		foreach( $req->StatusIds as $statusId ) {
			if( !ctype_digit( (string)$statusId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', "One of the given status ids is not valid (id={$statusId})." );
			}
		}
		$req->StatusIds = array_map( 'intval', $req->StatusIds ); // cast all ids to integer

		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		$this->pubId = BizAdmStatus::getPubIdFromStatusIds( $req->StatusIds );
		$this->issueId = BizAdmStatus::getIssueIdFromStatusIds( $req->StatusIds );
		if( !$this->pubId && !$this->issueId ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
				'None of the provided status ids do exist.', null, array( '{STATE}', implode(',',$req->StatusIds) ) );
		}
	}

	public function execute( AdmDeleteStatusesRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeleteStatuses',
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function runCallback( AdmDeleteStatusesRequest $req )
	{
		// To authorize the admin user, take the brand that owns the overrule issue.
		if( $this->issueId ) {
			require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
			$authPubId = DBAdmIssue::getPubIdForIssueId( $this->pubId, true );
		} else { // Fallback at the brand when no overrule issue provided.
			$authPubId = $this->pubId;
		}

		require_once BASEDIR.'/server/bizclasses/BizAdmStatus.class.php';
		BizAdmStatus::deleteStatuses( $authPubId, $req->StatusIds );
		return new AdmDeleteStatusesResponse();
	}
}
