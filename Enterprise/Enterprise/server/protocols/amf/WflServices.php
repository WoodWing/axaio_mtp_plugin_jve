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
require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetServersRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetUserSettingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSaveUserSettingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflDeleteUserSettingsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflChangePasswordRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflChangeOnlineStatusRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetStatesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetDialogRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetDialog2Request.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSendToRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSendToNextRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSendMessagesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectOperationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflInstantiateTemplateRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSaveObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflLockObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflRestoreObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCopyObjectRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSetObjectPropertiesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflMultiSetObjectPropertiesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflQueryObjectsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflNamedQueryRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectRelationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectRelationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectRelationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetObjectRelationsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectTargetsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectTargetsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectTargetsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetVersionRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflListVersionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflRestoreVersionRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCreateArticleWorkspaceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflListArticleWorkspacesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetArticleFromWorkspaceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSaveArticleInWorkspaceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflPreviewArticleAtWorkspaceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflPreviewArticlesAtWorkspaceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflDeleteArticleWorkspaceRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCheckSpellingRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetSuggestionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCheckSpellingAndSuggestRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflAutocompleteRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflSuggestionsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetPagesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetPagesInfoRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetRelatedPagesRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflGetRelatedPagesInfoRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectLabelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflUpdateObjectLabelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectLabelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflAddObjectLabelsRequest.class.php');
require_once(BASEDIR.'/server/interfaces/services/wfl/WflRemoveObjectLabelsRequest.class.php');

require_once BASEDIR.'/server/secure.php';

class WW_AMF_WflServices extends WW_AMF_Services
{
	public function GetServers( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetServersService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetServersRequest' );
			$service = new WflGetServersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflLogOnRequest' );
			$service = new WflLogOnService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflLogOffRequest' );
			$service = new WflLogOffService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetUserSettings( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetUserSettingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetUserSettingsRequest' );
			$service = new WflGetUserSettingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveUserSettings( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveUserSettingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSaveUserSettingsRequest' );
			$service = new WflSaveUserSettingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteUserSettings( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteUserSettingsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflDeleteUserSettingsRequest' );
			$service = new WflDeleteUserSettingsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ChangePassword( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflChangePasswordService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflChangePasswordRequest' );
			$service = new WflChangePasswordService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ChangeOnlineStatus( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflChangeOnlineStatusService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflChangeOnlineStatusRequest' );
			$service = new WflChangeOnlineStatusService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetStates( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetStatesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetStatesRequest' );
			$service = new WflGetStatesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDialog( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialogService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetDialogRequest' );
			$service = new WflGetDialogService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetDialog2( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetDialog2Request' );
			$service = new WflGetDialog2Service();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SendTo( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSendToRequest' );
			$service = new WflSendToService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SendToNext( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToNextService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSendToNextRequest' );
			$service = new WflSendToNextService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SendMessages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSendMessagesRequest' );
			$service = new WflSendMessagesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateObjectOperations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCreateObjectOperationsRequest' );
			$service = new WflCreateObjectOperationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCreateObjectsRequest' );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
			$service = new WflCreateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function InstantiateTemplate( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflInstantiateTemplateService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflInstantiateTemplateRequest' );
			$service = new WflInstantiateTemplateService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			$transferServer->switchFilePathToURL( $object );
		}

		return $resp;
	}

	public function GetObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetObjectsRequest' );
			$service = new WflGetObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			$transferServer->switchFilePathToURL( $object );
		}

		return $resp;
	}

	public function SaveObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSaveObjectsRequest' );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
			$service = new WflSaveObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function LockObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflLockObjectsRequest' );
			$service = new WflLockObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UnlockObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflUnlockObjectsRequest' );
			$service = new WflUnlockObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflDeleteObjectsRequest' );
			$service = new WflDeleteObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function RestoreObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflRestoreObjectsRequest' );
			$service = new WflRestoreObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CopyObject( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCopyObjectRequest' );
			$service = new WflCopyObjectService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SetObjectProperties( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSetObjectPropertiesRequest' );
			$service = new WflSetObjectPropertiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function MultiSetObjectProperties( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflMultiSetObjectPropertiesRequest' );
			$service = new WflMultiSetObjectPropertiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function QueryObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflQueryObjectsRequest' );
			$service = new WflQueryObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		//Sanitize the response for the FacetItems (Number is an illegal property name in Flex.)
		if ( $resp->Facets ) foreach ($resp->Facets as $facet){
			foreach ($facet->FacetItems as $facetItem){
				$facetItem->Numbers = $facetItem->Number;
				unset($facetItem->Number);
			}
		}

		return $resp;
	}

	public function NamedQuery( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflNamedQueryRequest' );
			$service = new WflNamedQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		//Sanitize the response for the FacetItems (Number is an illegal property name in Flex.)
		if ( $resp->Facets ) foreach ($resp->Facets as $facet){
			foreach ($facet->FacetItems as $facetItem){
				$facetItem->Numbers = $facetItem->Number;
				unset($facetItem->Number);
			}
		}

		return $resp;
	}

	public function CreateObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCreateObjectRelationsRequest' );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
			$service = new WflCreateObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UpdateObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflUpdateObjectRelationsRequest' );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
			$service = new WflUpdateObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectRelationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflDeleteObjectRelationsRequest' );
			$service = new WflDeleteObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectRelationsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetObjectRelationsRequest' );
			$service = new WflGetObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			$transferServer->switchFilePathToURL( $object );
		}

		return $resp;
	}

	public function CreateObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCreateObjectTargetsRequest' );
			$service = new WflCreateObjectTargetsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UpdateObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectTargetsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflUpdateObjectTargetsRequest' );
			$service = new WflUpdateObjectTargetsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflDeleteObjectTargetsRequest' );
			$service = new WflDeleteObjectTargetsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetVersion( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetVersionService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetVersionRequest' );
			$service = new WflGetVersionService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->VersionInfo->File ) {
			$transferServer->filePathToURL( $resp->VersionInfo->File );
		}

		//Sanitize the response for the VersionInfo (Object is an illegal property name in Flex.)
		$resp->VersionInfo->Objects = $resp->VersionInfo->Object;
		unset( $resp->VersionInfo->Object );

		return $resp;
	}

	public function ListVersions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflListVersionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflListVersionsRequest' );
			$service = new WflListVersionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Versions ) foreach( $resp->Versions as $versionInfo ) {
			if ( $versionInfo->File ) {
				$transferServer->filePathToURL( $versionInfo->File );
			}
		}

		return $resp;
	}

	public function RestoreVersion( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRestoreVersionService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflRestoreVersionRequest' );
			$service = new WflRestoreVersionService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateArticleWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateArticleWorkspaceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCreateArticleWorkspaceRequest' );
			$service = new WflCreateArticleWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function ListArticleWorkspaces( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflListArticleWorkspacesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflListArticleWorkspacesRequest' );
			$service = new WflListArticleWorkspacesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetArticleFromWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetArticleFromWorkspaceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetArticleFromWorkspaceRequest' );
			$service = new WflGetArticleFromWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function SaveArticleInWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveArticleInWorkspaceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSaveArticleInWorkspaceRequest' );
			$service = new WflSaveArticleInWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function PreviewArticleAtWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticleAtWorkspaceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflPreviewArticleAtWorkspaceRequest' );
			$service = new WflPreviewArticleAtWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function PreviewArticlesAtWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticlesAtWorkspaceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflPreviewArticlesAtWorkspaceRequest' );
			$service = new WflPreviewArticlesAtWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteArticleWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteArticleWorkspaceService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflDeleteArticleWorkspaceRequest' );
			$service = new WflDeleteArticleWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CheckSpelling( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCheckSpellingService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCheckSpellingRequest' );
			$service = new WflCheckSpellingService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetSuggestions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetSuggestionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetSuggestionsRequest' );
			$service = new WflGetSuggestionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CheckSpellingAndSuggest( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCheckSpellingAndSuggestService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCheckSpellingAndSuggestRequest' );
			$service = new WflCheckSpellingAndSuggestService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function Autocomplete( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflAutocompleteService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflAutocompleteRequest' );
			$service = new WflAutocompleteService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function Suggestions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSuggestionsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflSuggestionsRequest' );
			$service = new WflSuggestionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetPages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetPagesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetPagesRequest' );
			$service = new WflGetPagesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->ObjectPageInfos ) foreach( $resp->ObjectPageInfos as $pageInfo ) {
			if( $pageInfo->Pages ) foreach( $pageInfo->Pages as $page ) {
				if( $page->Files ) foreach( $page->Files as $file ) {
					$transferServer->filePathToURL( $file );
				}
			}
		}

		return $resp;
	}

	public function GetPagesInfo( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetPagesInfoService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetPagesInfoRequest' );
			$service = new WflGetPagesInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function GetRelatedPages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetRelatedPagesRequest' );
			$service = new WflGetRelatedPagesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->ObjectPageInfos ) foreach( $resp->ObjectPageInfos as $pageInfo ) {
			if( $pageInfo->Pages ) foreach( $pageInfo->Pages as $page ) {
				if( $page->Files ) foreach( $page->Files as $file ) {
					$transferServer->filePathToURL( $file );
				}
			}
		}

		return $resp;
	}

	public function GetRelatedPagesInfo( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesInfoService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflGetRelatedPagesInfoRequest' );
			$service = new WflGetRelatedPagesInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function CreateObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectLabelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflCreateObjectLabelsRequest' );
			$service = new WflCreateObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function UpdateObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectLabelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflUpdateObjectLabelsRequest' );
			$service = new WflUpdateObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function DeleteObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectLabelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflDeleteObjectLabelsRequest' );
			$service = new WflDeleteObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function AddObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflAddObjectLabelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflAddObjectLabelsRequest' );
			$service = new WflAddObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}

	public function RemoveObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRemoveObjectLabelsService.class.php';

		try {
			$req = $this->objectToRequest( $req, 'WflRemoveObjectLabelsRequest' );
			$service = new WflRemoveObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			require_once 'Zend/Amf/Server/Exception.php';
			throw new Zend_Amf_Server_Exception( $e->getMessage() );
		}
		return $resp;
	}


}
