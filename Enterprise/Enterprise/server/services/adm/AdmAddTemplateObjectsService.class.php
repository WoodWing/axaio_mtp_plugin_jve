<?php
/**
 * AddTemplateObjects Admin service.
 *
 * @package Enterprise
 * @subpackage Services
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmAddTemplateObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmAddTemplateObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmAddTemplateObjectsService extends EnterpriseService
{
	public function execute( AdmAddTemplateObjectsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmAddTemplateObjects', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	/**
	 * @inheritdoc
	 * @param AdmAddTemplateObjectsRequest $req
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

		//This test is only useful if the publication id is not resolved from the issue id.
		if( !$req->IssueId ) {
			require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
			if( !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id='.$req->PublicationId.' does not exist.',
					null, array( '{PUBLICATION}', $req->PublicationId ) );
			}
		}

		if( $req->TemplateObjects ) foreach( $req->TemplateObjects as &$templateObject ) {
			$templateObject->PublicationId = $req->PublicationId;
			$templateObject->IssueId = $req->IssueId;
			if( !ctype_digit( (string)$templateObject->TemplateObjectId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given template object id is not valid.');
			}
			$templateObject->TemplateObjectId = intval($templateObject->TemplateObjectId);
			if( !ctype_digit( (string)$templateObject->UserGroupId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given user group id is not valid.');
			}
			$templateObject->UserGroupId = intval($templateObject->UserGroupId);
		} else {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No template object access rules were given to be added.' );
		}
	}

	public function runCallback( AdmAddTemplateObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmTemplateObject.class.php';
		BizAdmTemplateObject::addTemplateObjects( $req->TemplateObjects );
		$response = new AdmAddTemplateObjectsResponse();

		if( !is_null( $req->RequestModes ) || !empty( $req->RequestModes ) ) {
			$userGroupIds = array();
			$templateObjectIds = array();
			foreach( $req->TemplateObjects as $templateObj ) {
				if( $templateObj->UserGroupId > 0 ) {
					$userGroupIds[] = $templateObj->UserGroupId;
				}
				if( $templateObj->TemplateObjectId > 0 ) {
					$templateObjectIds[] = $templateObj->TemplateObjectId;
				}
			}
			if( in_array( 'GetUserGroups', $req->TemplateObjects ) ) {
				if( !empty( $userGroupIds ) ) {
					$userGroupIds = array_unique( $userGroupIds );
					require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
					$userGroups = BizAdmUser::listUserGroupsObj( $this->User, array(), null, $userGroupIds );
					$response->UserGroups = $userGroups;
				}
			}
			if( in_array( 'GetObjectInfos', $req->TemplateObjects ) ) {
				if( !empty( $templateObjectIds ) ) {
					$templateObjectIds = array_unique( $templateObjectIds );
					$objectInfos = BizAdmTemplateObject::getObjectInfos( $templateObjectIds );
					$response->ObjectInfos = $objectInfos;
				}
			}
		}
		return $response;
	}
}
