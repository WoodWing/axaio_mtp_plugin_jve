<?php
/**
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * Unwraps incoming SOAP requests and dispatches them to Workflow Administration Services.
 * Wraps returned service results into outgoing SOAP responses. Also handles exceptions.
 * This way the SOAP message protocol is entirely hidden from the core Enterprise Server.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the server/buildtools/genservices/interfaces/adm/SoapServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/adm/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_AdmServices extends WW_SOAP_Service
{
	public static function getClassMap( $soapAction )
	{
		$soapActionBase = substr( $soapAction, 0, -strlen('Request') );
		require_once BASEDIR . '/server/services/adm/Adm' . $soapActionBase . 'Service.class.php';
		return array( $soapAction => 'Adm' . $soapAction );
	}

	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOnService.class.php';

		try {
			$service = new AdmLogOnService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOffService.class.php';

		try {
			$service = new AdmLogOffService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';

		try {
			$service = new AdmCreateUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';

		try {
			$service = new AdmGetUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';

		try {
			$service = new AdmModifyUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';

		try {
			$service = new AdmDeleteUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';

		try {
			$service = new AdmCreateUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';

		try {
			$service = new AdmGetUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';

		try {
			$service = new AdmModifyUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUserGroupsService.class.php';

		try {
			$service = new AdmDeleteUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function AddUsersToGroup( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';

		try {
			$service = new AdmAddUsersToGroupService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function RemoveUsersFromGroup( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';

		try {
			$service = new AdmRemoveUsersFromGroupService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function AddGroupsToUser( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddGroupsToUserService.class.php';

		try {
			$service = new AdmAddGroupsToUserService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function RemoveGroupsFromUser( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveGroupsFromUserService.class.php';

		try {
			$service = new AdmRemoveGroupsFromUserService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreatePublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';

		try {
			$service = new AdmCreatePublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetPublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';

		try {
			$service = new AdmGetPublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyPublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPublicationsService.class.php';

		try {
			$service = new AdmModifyPublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeletePublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';

		try {
			$service = new AdmDeletePublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreatePubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';

		try {
			$service = new AdmCreatePubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetPubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';

		try {
			$service = new AdmGetPubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyPubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPubChannelsService.class.php';

		try {
			$service = new AdmModifyPubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeletePubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';

		try {
			$service = new AdmDeletePubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';

		try {
			$service = new AdmCreateIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';

		try {
			$service = new AdmGetIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyIssuesService.class.php';

		try {
			$service = new AdmModifyIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';

		try {
			$service = new AdmDeleteIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CopyIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCopyIssuesService.class.php';

		try {
			$service = new AdmCopyIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';

		try {
			$service = new AdmCreateEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetEditionsService.class.php';

		try {
			$service = new AdmGetEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyEditionsService.class.php';

		try {
			$service = new AdmModifyEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';

		try {
			$service = new AdmDeleteEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';

		try {
			$service = new AdmCreateSectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';

		try {
			$service = new AdmGetSectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifySections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifySectionsService.class.php';

		try {
			$service = new AdmModifySectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';

		try {
			$service = new AdmDeleteSectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateStatusesService.class.php';

		try {
			$service = new AdmCreateStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';

		try {
			$service = new AdmGetStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyStatusesService.class.php';

		try {
			$service = new AdmModifyStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';

		try {
			$service = new AdmDeleteStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';

		try {
			$service = new AdmCreateAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';

		try {
			$service = new AdmGetAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';

		try {
			$service = new AdmModifyAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';

		try {
			$service = new AdmDeleteAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$service = new AdmCreateWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$service = new AdmGetWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$service = new AdmModifyWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$service = new AdmDeleteWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreatePublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';

		try {
			$service = new AdmCreatePublicationAdminAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetPublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationAdminAuthorizationsService.class.php';

		try {
			$service = new AdmGetPublicationAdminAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeletePublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationAdminAuthorizationsService.class.php';

		try {
			$service = new AdmDeletePublicationAdminAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateRoutingsService.class.php';

		try {
			$service = new AdmCreateRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';

		try {
			$service = new AdmGetRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyRoutingsService.class.php';

		try {
			$service = new AdmModifyRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';

		try {
			$service = new AdmDeleteRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function AddTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';

		try {
			$service = new AdmAddTemplateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';

		try {
			$service = new AdmGetTemplateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function RemoveTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';

		try {
			$service = new AdmRemoveTemplateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';

		try {
			$service = new AdmCreateAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermEntitiesService.class.php';

		try {
			$service = new AdmGetAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermEntitiesService.class.php';

		try {
			$service = new AdmModifyAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';

		try {
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';

		try {
			$service = new AdmCreateAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermsService.class.php';

		try {
			$service = new AdmGetAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ModifyAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermsService.class.php';

		try {
			$service = new AdmModifyAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';

		try {
			$service = new AdmDeleteAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}