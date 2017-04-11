<?php
/**
 * GetPublicationAdminAuthorizations Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationAdminAuthorizationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationAdminAuthorizationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetPublicationAdminAuthorizationsService extends EnterpriseService
{
	public function execute( AdmGetPublicationAdminAuthorizationsRequest $req )
	{
		return $this->executeService(
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetPublicationAdminAuthorizations', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function restructureRequest( &$req )
	{
		if( !$req->PublicationId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand id was given.' );
		}

		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
		if( !DBPublication::getPublication( $req->PublicationId ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'Brand with id='.$req->PublicationId.' does not exist.',
				null, array( '{PUBLICATION}', $req->PublicationId ) );
		}
	}

	public function runCallback( AdmGetPublicationAdminAuthorizationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublicationAdminAuthorization.class.php';
		$userGroupIds = BizAdmPublicationAdminAuthorization::getPublicationAdminAuthorizations( $req->PublicationId );
		$response = new AdmGetPublicationAdminAuthorizationsResponse();
		$response->UserGroupIds = $userGroupIds;

		if( $userGroupIds && $req->RequestModes && in_array( 'GetUserGroups', $req->RequestModes ) ) {
			require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
			$userGroups = BizAdmUser::listUserGroupsObj( $this->User, array(), null, $userGroupIds );
			$response->UserGroups = $userGroups;
		}
		return $response;
	}
}
