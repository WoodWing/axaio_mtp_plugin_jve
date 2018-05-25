<?php
/**
 * GetTemplateObjects Admin service.
 *
 * @since v10.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/adm/AdmGetTemplateObjectsRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/AdmGetTemplateObjectsResponse.class.php';
require_once BASEDIR.'/server/services/EnterpriseService.class.php';

class AdmGetTemplateObjectsService extends EnterpriseService
{
	public function execute( AdmGetTemplateObjectsRequest $req )
	{
		return $this->executeService( 
			$req, 
			$req->Ticket, 
			'AdminService',
			'AdmGetTemplateObjects', 	
			true,  		// check ticket
			true   	// use transactions
			);
	}

	public function restructureRequest( &$req )
	{
		if( !$req->PublicationId && !$req->IssueId ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'No brand or issue id were given.' );
		}

		if( $req->IssueId ) {
			require_once( BASEDIR.'/server/dbclasses/DBIssue.class.php' );
			//TODO: Replace with a proper resolve function after a BizIssueBase class is made
			//test whether the given issue id exists and belongs to an overrule issue
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
			//if a publication id is given it should match the one resolved
			if( $req->PublicationId && $req->PublicationId != $issuePubId ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client',
					'The given brand id ('.$req->PublicationId.') does not match the brand id ('.$issuePubId.') of the issue ('.$req->IssueId.').' );
			} elseif( !$req->PublicationId ) {
				//if no publication id is given, the one resolved will be used
				$req->PublicationId = $issuePubId;
			}
		} else {
			//The issue id is not optional and should be provided a neutral value if not given.
			$req->IssueId = 0;
		}

		//this test is only useful if the publication id is not resolved from the issue id
		if( !$req->IssueId ) {
			require_once( BASEDIR . '/server/bizclasses/BizAdmPublication.class.php' );
			if( !BizAdmPublication::doesPublicationIdExists( $req->PublicationId ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given brand with id='.$req->PublicationId.' does not exist.',
					null, array( '{PUBLICATION}', $req->PublicationId ) );
			}
		}

		if( $req->TemplateObjectId ) {
			if( !ctype_digit( (string)$req->TemplateObjectId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given user template object id is not valid.');
			}
			$req->TemplateObjectId = intval($req->TemplateObjectId);

			require_once BASEDIR.'/server/bizclasses/BizAdmTemplateObject.class.php';
			if ( !BizAdmTemplateObject::getObjectInfos( array( $req->TemplateObjectId ) ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client',
					'The given dossier template with id='.$req->TemplateObjectId.' does not exist.',
					null, array( '{DOSSIER_TEMPLATE}', $req->TemplateObjectId ) );
			}
		}

		if( $req->UserGroupId ) {
			if( !ctype_digit( (string)$req->UserGroupId ) ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The given user user group id is not valid.');
			}
			$req->UserGroupId = intval($req->UserGroupId);

			require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
			if( !BizAdmUser::listUserGroupsObj( $this->User, array(), null, array( $req->UserGroupId ) ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given user group with id='.$req->UserGroupId.' does not exist.',
					null, array( '{GRP_GROUP}' ) );
			}
		}
	}

	public function runCallback( AdmGetTemplateObjectsRequest $req )
	{
		require_once BASEDIR.'/server/bizclasses/BizAdmTemplateObject.class.php';
		$templateObjects = BizAdmTemplateObject::getTemplateObjects(
			$req->PublicationId, $req->IssueId, $req->TemplateObjectId, $req->UserGroupId );
		$response = new AdmGetTemplateObjectsResponse();
		$response->TemplateObjects = $templateObjects;

		if( !is_null( $req->RequestModes ) || !empty( $req->RequestModes ) ) {
			$userGroupIds = array();
			$templateObjectIds = array();
			foreach( $templateObjects as $templateObj ) {
				if( $templateObj->UserGroupId > 0 ) {
					$userGroupIds[] = $templateObj->UserGroupId;
				}
				if( $templateObj->TemplateObjectId > 0 ) {
					$templateObjectIds[] = $templateObj->TemplateObjectId;
				}
			}
			if( in_array( 'GetUserGroups', $req->RequestModes ) ) {
				if( !empty( $userGroupIds ) ) {
					$userGroupIds = array_unique( $userGroupIds );
					require_once BASEDIR.'/server/bizclasses/BizAdmUser.class.php';
					$userGroups = BizAdmUser::listUserGroupsObj( $this->User, array(), null, $userGroupIds );
					$response->UserGroups = $userGroups;
				}
			}
			if( in_array( 'GetObjectInfos', $req->RequestModes ) ) {
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
