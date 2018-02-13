<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

//* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * Use the AmfServices.template.php file instead.
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


require_once BASEDIR.'/server/protocols/amf/Services.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/adm/AdmLogOnRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmLogOffRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateUsersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetUsersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyUsersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteUsersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateUserGroupsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetUserGroupsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyUserGroupsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteUserGroupsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmAddUsersToGroupRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmRemoveUsersFromGroupRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmAddGroupsToUserRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmRemoveGroupsFromUserRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyPublicationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreatePubChannelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetPubChannelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyPubChannelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeletePubChannelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateIssuesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetIssuesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyIssuesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteIssuesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCopyIssuesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateEditionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetEditionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyEditionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteEditionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateSectionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetSectionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifySectionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteSectionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateStatusesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetStatusesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyStatusesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteStatusesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateAccessProfilesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetAccessProfilesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyAccessProfilesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteAccessProfilesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateWorkflowUserGroupAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetWorkflowUserGroupAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyWorkflowUserGroupAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreatePublicationAdminAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetPublicationAdminAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeletePublicationAdminAuthorizationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateRoutingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetRoutingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyRoutingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteRoutingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmAddTemplateObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetTemplateObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmRemoveTemplateObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermEntitiesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermEntitiesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermEntitiesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermEntitiesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmCreateAutocompleteTermsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmGetAutocompleteTermsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmModifyAutocompleteTermsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/adm/AdmDeleteAutocompleteTermsRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_AdmServices extends WW_AMF_Services
{
	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOnService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmLogOnRequest' );
			$service = new AdmLogOnService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmLogOffService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmLogOffRequest' );
			$service = new AdmLogOffService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUsersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateUsersRequest' );
			$service = new AdmCreateUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUsersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetUsersRequest' );
			$service = new AdmGetUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUsersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyUsersRequest' );
			$service = new AdmModifyUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteUsers( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUsersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteUsersRequest' );
			$service = new AdmDeleteUsersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateUserGroupsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateUserGroupsRequest' );
			$service = new AdmCreateUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetUserGroupsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetUserGroupsRequest' );
			$service = new AdmGetUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyUserGroupsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyUserGroupsRequest' );
			$service = new AdmModifyUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteUserGroups( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteUserGroupsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteUserGroupsRequest' );
			$service = new AdmDeleteUserGroupsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function AddUsersToGroup( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddUsersToGroupService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmAddUsersToGroupRequest' );
			$service = new AdmAddUsersToGroupService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function RemoveUsersFromGroup( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveUsersFromGroupService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmRemoveUsersFromGroupRequest' );
			$service = new AdmRemoveUsersFromGroupService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function AddGroupsToUser( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddGroupsToUserService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmAddGroupsToUserRequest' );
			$service = new AdmAddGroupsToUserService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function RemoveGroupsFromUser( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveGroupsFromUserService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmRemoveGroupsFromUserRequest' );
			$service = new AdmRemoveGroupsFromUserService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreatePublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreatePublicationsRequest' );
			$service = new AdmCreatePublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetPublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetPublicationsRequest' );
			$service = new AdmGetPublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyPublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPublicationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyPublicationsRequest' );
			$service = new AdmModifyPublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeletePublications( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeletePublicationsRequest' );
			$service = new AdmDeletePublicationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreatePubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePubChannelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreatePubChannelsRequest' );
			$service = new AdmCreatePubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetPubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPubChannelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetPubChannelsRequest' );
			$service = new AdmGetPubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyPubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyPubChannelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyPubChannelsRequest' );
			$service = new AdmModifyPubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeletePubChannels( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePubChannelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeletePubChannelsRequest' );
			$service = new AdmDeletePubChannelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateIssuesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateIssuesRequest' );
			$service = new AdmCreateIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetIssuesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetIssuesRequest' );
			$service = new AdmGetIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyIssuesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyIssuesRequest' );
			$service = new AdmModifyIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteIssuesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteIssuesRequest' );
			$service = new AdmDeleteIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CopyIssues( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCopyIssuesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCopyIssuesRequest' );
			$service = new AdmCopyIssuesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateEditionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateEditionsRequest' );
			$service = new AdmCreateEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetEditionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetEditionsRequest' );
			$service = new AdmGetEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyEditionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyEditionsRequest' );
			$service = new AdmModifyEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteEditions( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteEditionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteEditionsRequest' );
			$service = new AdmDeleteEditionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateSectionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateSectionsRequest' );
			$service = new AdmCreateSectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetSectionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetSectionsRequest' );
			$service = new AdmGetSectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifySections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifySectionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifySectionsRequest' );
			$service = new AdmModifySectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteSections( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteSectionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteSectionsRequest' );
			$service = new AdmDeleteSectionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateStatusesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateStatusesRequest' );
			$service = new AdmCreateStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetStatusesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetStatusesRequest' );
			$service = new AdmGetStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyStatusesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyStatusesRequest' );
			$service = new AdmModifyStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteStatuses( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteStatusesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteStatusesRequest' );
			$service = new AdmDeleteStatusesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateAccessProfilesRequest' );
			$service = new AdmCreateAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetAccessProfilesRequest' );
			$service = new AdmGetAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyAccessProfilesRequest' );
			$service = new AdmModifyAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteAccessProfiles( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteAccessProfilesRequest' );
			$service = new AdmDeleteAccessProfilesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateWorkflowUserGroupAuthorizationsRequest' );
			$service = new AdmCreateWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetWorkflowUserGroupAuthorizationsRequest' );
			$service = new AdmGetWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyWorkflowUserGroupAuthorizationsRequest' );
			$service = new AdmModifyWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteWorkflowUserGroupAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteWorkflowUserGroupAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteWorkflowUserGroupAuthorizationsRequest' );
			$service = new AdmDeleteWorkflowUserGroupAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreatePublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreatePublicationAdminAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreatePublicationAdminAuthorizationsRequest' );
			$service = new AdmCreatePublicationAdminAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetPublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetPublicationAdminAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetPublicationAdminAuthorizationsRequest' );
			$service = new AdmGetPublicationAdminAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeletePublicationAdminAuthorizations( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeletePublicationAdminAuthorizationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeletePublicationAdminAuthorizationsRequest' );
			$service = new AdmDeletePublicationAdminAuthorizationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateRoutingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateRoutingsRequest' );
			$service = new AdmCreateRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetRoutingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetRoutingsRequest' );
			$service = new AdmGetRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyRoutingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyRoutingsRequest' );
			$service = new AdmModifyRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteRoutings( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteRoutingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteRoutingsRequest' );
			$service = new AdmDeleteRoutingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function AddTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmAddTemplateObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmAddTemplateObjectsRequest' );
			$service = new AdmAddTemplateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetTemplateObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetTemplateObjectsRequest' );
			$service = new AdmGetTemplateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function RemoveTemplateObjects( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmRemoveTemplateObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmRemoveTemplateObjectsRequest' );
			$service = new AdmRemoveTemplateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermEntitiesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateAutocompleteTermEntitiesRequest' );
			$service = new AdmCreateAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermEntitiesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetAutocompleteTermEntitiesRequest' );
			$service = new AdmGetAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermEntitiesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyAutocompleteTermEntitiesRequest' );
			$service = new AdmModifyAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteAutocompleteTermEntities( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermEntitiesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteAutocompleteTermEntitiesRequest' );
			$service = new AdmDeleteAutocompleteTermEntitiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmCreateAutocompleteTermsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmCreateAutocompleteTermsRequest' );
			$service = new AdmCreateAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmGetAutocompleteTermsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmGetAutocompleteTermsRequest' );
			$service = new AdmGetAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ModifyAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmModifyAutocompleteTermsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmModifyAutocompleteTermsRequest' );
			$service = new AdmModifyAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteAutocompleteTerms( $req )
	{
		require_once BASEDIR.'/server/services/adm/AdmDeleteAutocompleteTermsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'AdmDeleteAutocompleteTermsRequest' );
			$service = new AdmDeleteAutocompleteTermsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
