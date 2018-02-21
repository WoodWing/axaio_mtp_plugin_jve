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

	/**
	 * @inheritdoc
	 * @param AdmGetPublicationAdminAuthorizationsRequest $req
	 */
	public function restructureRequest( &$req )
	{
		if( !$req->PublicationId || !ctype_digit( (string)$req->PublicationId ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No valid brand id was given.' );
		}
		$req->PublicationId = intval($req->PublicationId);

		require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
		if( !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
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