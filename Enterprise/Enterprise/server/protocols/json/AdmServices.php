<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the JsonServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR.'/server/protocols/json/Services.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_AdmServices extends WW_JSON_Services
{
	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOnService.class.php';
		$req['__classname__'] = 'AdmLogOnRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmLogOnService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOffService.class.php';
		$req['__classname__'] = 'AdmLogOffRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmLogOffService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';
		$req['__classname__'] = 'AdmCreateUsersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateUsersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';
		$req['__classname__'] = 'AdmGetUsersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetUsersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';
		$req['__classname__'] = 'AdmModifyUsersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyUsersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';
		$req['__classname__'] = 'AdmDeleteUsersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteUsersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';
		$req['__classname__'] = 'AdmCreateUserGroupsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateUserGroupsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';
		$req['__classname__'] = 'AdmGetUserGroupsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetUserGroupsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';
		$req['__classname__'] = 'AdmModifyUserGroupsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyUserGroupsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUserGroupsService.class.php';
		$req['__classname__'] = 'AdmDeleteUserGroupsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteUserGroupsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function AddUsersToGroup( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';
		$req['__classname__'] = 'AdmAddUsersToGroupRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmAddUsersToGroupService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function RemoveUsersFromGroup( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';
		$req['__classname__'] = 'AdmRemoveUsersFromGroupRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmRemoveUsersFromGroupService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function AddGroupsToUser( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddGroupsToUserService.class.php';
		$req['__classname__'] = 'AdmAddGroupsToUserRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmAddGroupsToUserService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function RemoveGroupsFromUser( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveGroupsFromUserService.class.php';
		$req['__classname__'] = 'AdmRemoveGroupsFromUserRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmRemoveGroupsFromUserService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreatePublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';
		$req['__classname__'] = 'AdmCreatePublicationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreatePublicationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetPublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
		$req['__classname__'] = 'AdmGetPublicationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetPublicationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyPublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPublicationsService.class.php';
		$req['__classname__'] = 'AdmModifyPublicationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyPublicationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeletePublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';
		$req['__classname__'] = 'AdmDeletePublicationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeletePublicationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreatePubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';
		$req['__classname__'] = 'AdmCreatePubChannelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreatePubChannelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetPubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';
		$req['__classname__'] = 'AdmGetPubChannelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetPubChannelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyPubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPubChannelsService.class.php';
		$req['__classname__'] = 'AdmModifyPubChannelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyPubChannelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeletePubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';
		$req['__classname__'] = 'AdmDeletePubChannelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeletePubChannelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';
		$req['__classname__'] = 'AdmCreateIssuesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateIssuesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';
		$req['__classname__'] = 'AdmGetIssuesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetIssuesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyIssuesService.class.php';
		$req['__classname__'] = 'AdmModifyIssuesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyIssuesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';
		$req['__classname__'] = 'AdmDeleteIssuesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteIssuesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CopyIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCopyIssuesService.class.php';
		$req['__classname__'] = 'AdmCopyIssuesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCopyIssuesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';
		$req['__classname__'] = 'AdmCreateEditionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateEditionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetEditionsService.class.php';
		$req['__classname__'] = 'AdmGetEditionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetEditionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyEditionsService.class.php';
		$req['__classname__'] = 'AdmModifyEditionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyEditionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';
		$req['__classname__'] = 'AdmDeleteEditionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteEditionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';
		$req['__classname__'] = 'AdmCreateSectionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateSectionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';
		$req['__classname__'] = 'AdmGetSectionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetSectionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifySections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifySectionsService.class.php';
		$req['__classname__'] = 'AdmModifySectionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifySectionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';
		$req['__classname__'] = 'AdmDeleteSectionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteSectionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateStatusesService.class.php';
		$req['__classname__'] = 'AdmCreateStatusesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateStatusesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';
		$req['__classname__'] = 'AdmGetStatusesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetStatusesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyStatusesService.class.php';
		$req['__classname__'] = 'AdmModifyStatusesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyStatusesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';
		$req['__classname__'] = 'AdmDeleteStatusesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteStatusesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';
		$req['__classname__'] = 'AdmCreateAccessProfilesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateAccessProfilesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
		$req['__classname__'] = 'AdmGetAccessProfilesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetAccessProfilesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
		$req['__classname__'] = 'AdmModifyAccessProfilesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyAccessProfilesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
		$req['__classname__'] = 'AdmDeleteAccessProfilesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteAccessProfilesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmCreateWorkflowUserGroupAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateWorkflowUserGroupAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmGetWorkflowUserGroupAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetWorkflowUserGroupAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyWorkflowUserGroupAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmModifyWorkflowUserGroupAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyWorkflowUserGroupAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmDeleteWorkflowUserGroupAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteWorkflowUserGroupAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreatePublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmCreatePublicationAdminAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreatePublicationAdminAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetPublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationAdminAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmGetPublicationAdminAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetPublicationAdminAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeletePublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationAdminAuthorizationsService.class.php';
		$req['__classname__'] = 'AdmDeletePublicationAdminAuthorizationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeletePublicationAdminAuthorizationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateRoutingsService.class.php';
		$req['__classname__'] = 'AdmCreateRoutingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateRoutingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';
		$req['__classname__'] = 'AdmGetRoutingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetRoutingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyRoutingsService.class.php';
		$req['__classname__'] = 'AdmModifyRoutingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyRoutingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';
		$req['__classname__'] = 'AdmDeleteRoutingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteRoutingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function AddTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';
		$req['__classname__'] = 'AdmAddTemplateObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmAddTemplateObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';
		$req['__classname__'] = 'AdmGetTemplateObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetTemplateObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function RemoveTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';
		$req['__classname__'] = 'AdmRemoveTemplateObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmRemoveTemplateObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';
		$req['__classname__'] = 'AdmCreateAutocompleteTermEntitiesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateAutocompleteTermEntitiesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermEntitiesService.class.php';
		$req['__classname__'] = 'AdmGetAutocompleteTermEntitiesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetAutocompleteTermEntitiesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermEntitiesService.class.php';
		$req['__classname__'] = 'AdmModifyAutocompleteTermEntitiesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyAutocompleteTermEntitiesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';
		$req['__classname__'] = 'AdmDeleteAutocompleteTermEntitiesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteAutocompleteTermEntitiesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';
		$req['__classname__'] = 'AdmCreateAutocompleteTermsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmCreateAutocompleteTermsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermsService.class.php';
		$req['__classname__'] = 'AdmGetAutocompleteTermsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmGetAutocompleteTermsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ModifyAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermsService.class.php';
		$req['__classname__'] = 'AdmModifyAutocompleteTermsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmModifyAutocompleteTermsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';
		$req['__classname__'] = 'AdmDeleteAutocompleteTermsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new AdmDeleteAutocompleteTermsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}


}
