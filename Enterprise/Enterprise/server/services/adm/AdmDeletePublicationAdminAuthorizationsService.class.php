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

	/**
	 * @inheritdoc
	 * @param AdmDeletePublicationAdminAuthorizationsRequest $req
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
