<?php
/**
 * RemoveTemplateObjects Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveTemplateObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmRemoveTemplateObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmRemoveTemplateObjectsService extends EnterpriseService
{
	public function execute( AdmRemoveTemplateObjectsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmRemoveTemplateObjects', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	/**
	 * @inheritdoc
	 * @param AdmRemoveTemplateObjectsRequest $req
	 */
	public function restructureRequest( &$req )
	{
		//Nothing can be done without a brand or issue id.
		if( !$req->PublicationId && !$req->IssueId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand or issue id were given.' );
		}

		if( $req->IssueId ) {
			require_once( BASEDIR.'/server/dbclasses/DBIssue.class.php' );
			//TODO: Replace with a proper resolve function after a BizIssueBase class is made
			//Test whether the given issue id exists and belongs to an overrule issue.
			$allOverruleIssues = DBIssue::listAllOverruleIssuesWithPub();
			if( !array_key_exists( $req->IssueId, $allOverruleIssues )) {
				if( !DBIssue::getIssue( $req->IssueId ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given issue with id='.$req->IssueId.' does not exist.',
						null, array( '{ISSUE}', $req->IssueId ) );
				} else {
					throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given issue id is not an overrule issue.');
				}
			}

			$issuePubId = $allOverruleIssues[$req->IssueId];
			//If a publication id is given it should match the one resolved.
			if( $req->PublicationId && $req->PublicationId != $issuePubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'The given brand id ('.$req->PublicationId.') does not match the brand id ('.$issuePubId.') of the issue ('.$req->IssueId.').' );
			} elseif( !$req->PublicationId ) {
				//If no publication id is given, the one resolved will be used.
				$req->PublicationId = $issuePubId;
			}
		} else {
			//The issue id is not optional and should be provided a neutral value if not given.
			$req->IssueId = 0;
		}

		require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
		//This test is only useful if the publication id is not resolved from the issue id.
		if( !$req->IssueId && !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id='.$req->PublicationId.' does not exist.',
					null, array( '{PUBLICATION}', $req->PublicationId ) );
		}

		if( $req->TemplateObjects ) foreach( $req->TemplateObjects as &$templateObject ) {
			$templateObject->PublicationId = $req->PublicationId;
			$templateObject->IssueId = $req->IssueId;
			if( !ctype_digit( $templateObject->UserGroupId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given user user group id is not valid.');
			}
			$templateObject->UserGroupId = intval($templateObject->UserGroupId);
			if( !ctype_digit( $templateObject->TemplateObjectId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given template object id is not valid.');
			}
			$templateObject->TemplateObjectId = intval($templateObject->TemplateObjectId);
		} else {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No template object access rules were given to be removed.' );
		}
	}

	public function runCallback( AdmRemoveTemplateObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmTemplateObject.class.php';
		BizAdmTemplateObject::removeTemplateObjects( $req->TemplateObjects );
		return new AdmRemoveTemplateObjectsResponse();
	}
}
