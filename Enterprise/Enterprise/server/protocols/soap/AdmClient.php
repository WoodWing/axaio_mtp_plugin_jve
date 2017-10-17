<?php
/**
 * Admin SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_AdmClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['AccessProfile'] = 'AdmAccessProfile';
		$options['classmap']['Edition'] = 'AdmEdition';
		$options['classmap']['ExtraMetaData'] = 'AdmExtraMetaData';
		$options['classmap']['IdName'] = 'AdmIdName';
		$options['classmap']['Issue'] = 'AdmIssue';
		$options['classmap']['ObjectInfo'] = 'AdmObjectInfo';
		$options['classmap']['ProfileFeature'] = 'AdmProfileFeature';
		$options['classmap']['PubChannel'] = 'AdmPubChannel';
		$options['classmap']['Publication'] = 'AdmPublication';
		$options['classmap']['Routing'] = 'AdmRouting';
		$options['classmap']['Section'] = 'AdmSection';
		$options['classmap']['Status'] = 'AdmStatus';
		$options['classmap']['TemplateObjectAccess'] = 'AdmTemplateObjectAccess';
		$options['classmap']['TermEntity'] = 'AdmTermEntity';
		$options['classmap']['User'] = 'AdmUser';
		$options['classmap']['UserGroup'] = 'AdmUserGroup';
		$options['classmap']['WorkflowUserGroupAuthorization'] = 'AdmWorkflowUserGroupAuthorization';
		$options['classmap']['LogOnResponse'] = 'AdmLogOnResponse';
		$options['classmap']['LogOffResponse'] = 'AdmLogOffResponse';
		$options['classmap']['CreateUsersResponse'] = 'AdmCreateUsersResponse';
		$options['classmap']['GetUsersResponse'] = 'AdmGetUsersResponse';
		$options['classmap']['ModifyUsersResponse'] = 'AdmModifyUsersResponse';
		$options['classmap']['DeleteUsersResponse'] = 'AdmDeleteUsersResponse';
		$options['classmap']['CreateUserGroupsResponse'] = 'AdmCreateUserGroupsResponse';
		$options['classmap']['GetUserGroupsResponse'] = 'AdmGetUserGroupsResponse';
		$options['classmap']['ModifyUserGroupsResponse'] = 'AdmModifyUserGroupsResponse';
		$options['classmap']['DeleteUserGroupsResponse'] = 'AdmDeleteUserGroupsResponse';
		$options['classmap']['AddUsersToGroupResponse'] = 'AdmAddUsersToGroupResponse';
		$options['classmap']['RemoveUsersFromGroupResponse'] = 'AdmRemoveUsersFromGroupResponse';
		$options['classmap']['AddGroupsToUserResponse'] = 'AdmAddGroupsToUserResponse';
		$options['classmap']['RemoveGroupsFromUserResponse'] = 'AdmRemoveGroupsFromUserResponse';
		$options['classmap']['CreatePublicationsResponse'] = 'AdmCreatePublicationsResponse';
		$options['classmap']['GetPublicationsResponse'] = 'AdmGetPublicationsResponse';
		$options['classmap']['ModifyPublicationsResponse'] = 'AdmModifyPublicationsResponse';
		$options['classmap']['DeletePublicationsResponse'] = 'AdmDeletePublicationsResponse';
		$options['classmap']['CreatePubChannelsResponse'] = 'AdmCreatePubChannelsResponse';
		$options['classmap']['GetPubChannelsResponse'] = 'AdmGetPubChannelsResponse';
		$options['classmap']['ModifyPubChannelsResponse'] = 'AdmModifyPubChannelsResponse';
		$options['classmap']['DeletePubChannelsResponse'] = 'AdmDeletePubChannelsResponse';
		$options['classmap']['CreateIssuesResponse'] = 'AdmCreateIssuesResponse';
		$options['classmap']['GetIssuesResponse'] = 'AdmGetIssuesResponse';
		$options['classmap']['ModifyIssuesResponse'] = 'AdmModifyIssuesResponse';
		$options['classmap']['DeleteIssuesResponse'] = 'AdmDeleteIssuesResponse';
		$options['classmap']['CopyIssuesResponse'] = 'AdmCopyIssuesResponse';
		$options['classmap']['CreateEditionsResponse'] = 'AdmCreateEditionsResponse';
		$options['classmap']['GetEditionsResponse'] = 'AdmGetEditionsResponse';
		$options['classmap']['ModifyEditionsResponse'] = 'AdmModifyEditionsResponse';
		$options['classmap']['DeleteEditionsResponse'] = 'AdmDeleteEditionsResponse';
		$options['classmap']['CreateSectionsResponse'] = 'AdmCreateSectionsResponse';
		$options['classmap']['GetSectionsResponse'] = 'AdmGetSectionsResponse';
		$options['classmap']['ModifySectionsResponse'] = 'AdmModifySectionsResponse';
		$options['classmap']['DeleteSectionsResponse'] = 'AdmDeleteSectionsResponse';
		$options['classmap']['CreateStatusesResponse'] = 'AdmCreateStatusesResponse';
		$options['classmap']['GetStatusesResponse'] = 'AdmGetStatusesResponse';
		$options['classmap']['ModifyStatusesResponse'] = 'AdmModifyStatusesResponse';
		$options['classmap']['DeleteStatusesResponse'] = 'AdmDeleteStatusesResponse';
		$options['classmap']['CreateAccessProfilesResponse'] = 'AdmCreateAccessProfilesResponse';
		$options['classmap']['GetAccessProfilesResponse'] = 'AdmGetAccessProfilesResponse';
		$options['classmap']['ModifyAccessProfilesResponse'] = 'AdmModifyAccessProfilesResponse';
		$options['classmap']['DeleteAccessProfilesResponse'] = 'AdmDeleteAccessProfilesResponse';
		$options['classmap']['CreateWorkflowUserGroupAuthorizationsResponse'] = 'AdmCreateWorkflowUserGroupAuthorizationsResponse';
		$options['classmap']['GetWorkflowUserGroupAuthorizationsResponse'] = 'AdmGetWorkflowUserGroupAuthorizationsResponse';
		$options['classmap']['ModifyWorkflowUserGroupAuthorizationsResponse'] = 'AdmModifyWorkflowUserGroupAuthorizationsResponse';
		$options['classmap']['DeleteWorkflowUserGroupAuthorizationsResponse'] = 'AdmDeleteWorkflowUserGroupAuthorizationsResponse';
		$options['classmap']['CreatePublicationAdminAuthorizationsResponse'] = 'AdmCreatePublicationAdminAuthorizationsResponse';
		$options['classmap']['GetPublicationAdminAuthorizationsResponse'] = 'AdmGetPublicationAdminAuthorizationsResponse';
		$options['classmap']['DeletePublicationAdminAuthorizationsResponse'] = 'AdmDeletePublicationAdminAuthorizationsResponse';
		$options['classmap']['CreateRoutingsResponse'] = 'AdmCreateRoutingsResponse';
		$options['classmap']['GetRoutingsResponse'] = 'AdmGetRoutingsResponse';
		$options['classmap']['ModifyRoutingsResponse'] = 'AdmModifyRoutingsResponse';
		$options['classmap']['DeleteRoutingsResponse'] = 'AdmDeleteRoutingsResponse';
		$options['classmap']['AddTemplateObjectsResponse'] = 'AdmAddTemplateObjectsResponse';
		$options['classmap']['GetTemplateObjectsResponse'] = 'AdmGetTemplateObjectsResponse';
		$options['classmap']['RemoveTemplateObjectsResponse'] = 'AdmRemoveTemplateObjectsResponse';
		$options['classmap']['CreateAutocompleteTermEntitiesResponse'] = 'AdmCreateAutocompleteTermEntitiesResponse';
		$options['classmap']['GetAutocompleteTermEntitiesResponse'] = 'AdmGetAutocompleteTermEntitiesResponse';
		$options['classmap']['ModifyAutocompleteTermEntitiesResponse'] = 'AdmModifyAutocompleteTermEntitiesResponse';
		$options['classmap']['DeleteAutocompleteTermEntitiesResponse'] = 'AdmDeleteAutocompleteTermEntitiesResponse';
		$options['classmap']['CreateAutocompleteTermsResponse'] = 'AdmCreateAutocompleteTermsResponse';
		$options['classmap']['GetAutocompleteTermsResponse'] = 'AdmGetAutocompleteTermsResponse';
		$options['classmap']['ModifyAutocompleteTermsResponse'] = 'AdmModifyAutocompleteTermsResponse';
		$options['classmap']['DeleteAutocompleteTermsResponse'] = 'AdmDeleteAutocompleteTermsResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/adminindex.php';
		}
		$options['uri'] = 'urn:SmartConnectionAdmin';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
