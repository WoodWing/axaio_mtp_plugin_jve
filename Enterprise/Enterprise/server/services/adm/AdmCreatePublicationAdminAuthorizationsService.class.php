<?php
/**
 * CreatePublicationAdminAuthorizations Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationAdminAuthorizationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationAdminAuthorizationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmCreatePublicationAdminAuthorizationsService extends EnterpriseService
{
	public function execute( AdmCreatePublicationAdminAuthorizationsRequest $req )
	{
		return $this->executeService(
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmCreatePublicationAdminAuthorizations', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function restructureRequest( &$req)
	{
		if( !$req->PublicationId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand id was given.' );
		}

		require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
		if( !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'Brand with id='.$req->PublicationId.' does not exist.',
				null, array( '{PUBLICATION}', $req->PublicationId ) );
		}

		if( !( is_array( $req->UserGroupIds ) && count( $req->UserGroupIds ) >= 1 ) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No user groups were specified.' );
		}
	}

	public function runCallback( AdmCreatePublicationAdminAuthorizationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublicationAdminAuthorization.class.php';
		BizAdmPublicationAdminAuthorization::createPublicationAdminAuthorizations( $req->PublicationId, $req->UserGroupIds );
		return new AdmCreatePublicationAdminAuthorizationsResponse();
	}
}
