<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Dispatches incomming SOAP requests to Administration Services.<br>
 * It unpacks/packs the SOAP operations while doing so.
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the AdminServices.template.php file instead.
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