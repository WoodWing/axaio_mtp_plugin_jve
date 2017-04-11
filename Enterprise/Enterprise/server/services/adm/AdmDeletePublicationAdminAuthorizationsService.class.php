<?php
/**
 * DeletePublicationAdminAuthorizations Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationAdminAuthorizationsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationAdminAuthorizationsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmDeletePublicationAdminAuthorizationsService extends EnterpriseService
{
	public function execute( AdmDeletePublicationAdminAuthorizationsRequest $req )
	{
		return $this->executeService(
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmDeletePublicationAdminAuthorizations', 	
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

		if( empty( $req->UserGroupIds ) ) {
			$req->UserGroupIds = null;
		}
	}

	public function runCallback( AdmDeletePublicationAdminAuthorizationsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmPublicationAdminAuthorization.class.php';
		BizAdmPublicationAdminAuthorization::deletePublicationAdminAuthorizations( $req->PublicationId, $req->UserGroupIds );
		return new AdmDeletePublicationAdminAuthorizationsResponse();
	}
}
