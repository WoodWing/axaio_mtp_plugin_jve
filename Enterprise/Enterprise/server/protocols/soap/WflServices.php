<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @since v3.x
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * Notes: 
 * - Since v6.0 this class was renamed from smartserverbase into WorkflowServices
 * - Since v6.1 this class uses PHP SOAP (instead of PEAR SOAP)
 * - Since v7.0 this class uses Enterprise DIME handler (instead of PEAR DIME)
 *
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
 * Use the WorkflowServices.template.php file instead.
 * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 */

require_once BASEDIR . '/server/protocols/soap/Server.php';
require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';

// include helper-objects
require_once BASEDIR.'/server/secure.php';

class WW_SOAP_WflServices extends WW_SOAP_Service 
{
	public static function getClassMap( $soapAction )
	{
		require_once BASEDIR . '/server/services/wfl/Wfl' . $soapAction . 'Service.class.php';
		return array( $soapAction => 'Wfl' . $soapAction . 'Request' );
	}
	
	public function GetServers( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetServersService.class.php';

		try {
			$service = new WflGetServersService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';

		try {
			$service = new WflLogOnService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';

		try {
			$service = new WflLogOffService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ChangePassword( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflChangePasswordService.class.php';

		try {
			$service = new WflChangePasswordService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ChangeOnlineStatus( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflChangeOnlineStatusService.class.php';

		try {
			$service = new WflChangeOnlineStatusService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetStates( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetStatesService.class.php';

		try {
			$service = new WflGetStatesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDialog( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialogService.class.php';

		try {
			$service = new WflGetDialogService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetDialog2( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';

		try {
			$service = new WflGetDialog2Service();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SendTo( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToService.class.php';

		try {
			$service = new WflSendToService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SendToNext( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToNextService.class.php';

		try {
			$service = new WflSendToNextService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SendMessages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';

		try {
			$service = new WflSendMessagesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateObjectOperations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';

		try {
			$service = new WflCreateObjectOperationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';

		try {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
			$service = new WflCreateObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function InstantiateTemplate( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflInstantiateTemplateService.class.php';

		try {
			$service = new WflInstantiateTemplateService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			$transferServer->switchFilePathToURL( $object );
		}

		return self::returnResponse($resp);
	}

	public function GetObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';

		try {
			$service = new WflGetObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Objects ) foreach( $resp->Objects as $object ) {
			$transferServer->switchFilePathToURL( $object );
		}

		return self::returnResponse($resp);
	}

	public function SaveObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';

		try {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
			$service = new WflSaveObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function LockObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';

		try {
			$service = new WflLockObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UnlockObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';

		try {
			$service = new WflUnlockObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';

		try {
			$service = new WflDeleteObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function RestoreObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';

		try {
			$service = new WflRestoreObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CopyObject( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';

		try {
			$service = new WflCopyObjectService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SetObjectProperties( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';

		try {
			$service = new WflSetObjectPropertiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function MultiSetObjectProperties( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';

		try {
			$service = new WflMultiSetObjectPropertiesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function QueryObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';

		try {
			$service = new WflQueryObjectsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function NamedQuery( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';

		try {
			$service = new WflNamedQueryService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';

		try {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Relations ) foreach( $req->Relations as $relation ) {
				if ( $relation->Geometry ) {
					$transferServer->urlToFilePath( $relation->Geometry );
				}
			}
			$service = new WflCreateObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UpdateObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';

		try {
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Relations ) foreach( $req->Relations as $relation ) {
				if ( $relation->Geometry ) {
					$transferServer->urlToFilePath( $relation->Geometry );
				}
			}
			$service = new WflUpdateObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectRelationsService.class.php';

		try {
			$service = new WflDeleteObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectRelationsService.class.php';

		try {
			$service = new WflGetObjectRelationsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Relations ) foreach( $resp->Relations as $relation ) {
			if ( $relation->Geometry ) {
				$transferServer->filePathToURL( $relation->Geometry );
			}
		}

		return self::returnResponse($resp);
	}

	public function CreateObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';

		try {
			$service = new WflCreateObjectTargetsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UpdateObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectTargetsService.class.php';

		try {
			$service = new WflUpdateObjectTargetsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';

		try {
			$service = new WflDeleteObjectTargetsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetVersion( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetVersionService.class.php';

		try {
			$service = new WflGetVersionService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->VersionInfo->File ) {
			$transferServer->filePathToURL( $resp->VersionInfo->File );
		}

		return self::returnResponse($resp);
	}

	public function ListVersions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflListVersionsService.class.php';

		try {
			$service = new WflListVersionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->Versions ) foreach( $resp->Versions as $versionInfo ) {
			if ( $versionInfo->File ) {
				$transferServer->filePathToURL( $versionInfo->File );
			}
		}

		return self::returnResponse($resp);
	}

	public function RestoreVersion( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRestoreVersionService.class.php';

		try {
			$service = new WflRestoreVersionService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateArticleWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateArticleWorkspaceService.class.php';

		try {
			$service = new WflCreateArticleWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function ListArticleWorkspaces( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflListArticleWorkspacesService.class.php';

		try {
			$service = new WflListArticleWorkspacesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetArticleFromWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetArticleFromWorkspaceService.class.php';

		try {
			$service = new WflGetArticleFromWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function SaveArticleInWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveArticleInWorkspaceService.class.php';

		try {
			$service = new WflSaveArticleInWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function PreviewArticleAtWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticleAtWorkspaceService.class.php';

		try {
			$service = new WflPreviewArticleAtWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function PreviewArticlesAtWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticlesAtWorkspaceService.class.php';

		try {
			$service = new WflPreviewArticlesAtWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteArticleWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteArticleWorkspaceService.class.php';

		try {
			$service = new WflDeleteArticleWorkspaceService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CheckSpelling( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCheckSpellingService.class.php';

		try {
			$service = new WflCheckSpellingService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetSuggestions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetSuggestionsService.class.php';

		try {
			$service = new WflGetSuggestionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CheckSpellingAndSuggest( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCheckSpellingAndSuggestService.class.php';

		try {
			$service = new WflCheckSpellingAndSuggestService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function Autocomplete( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflAutocompleteService.class.php';

		try {
			$service = new WflAutocompleteService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function Suggestions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSuggestionsService.class.php';

		try {
			$service = new WflSuggestionsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function GetPages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetPagesService.class.php';

		try {
			$service = new WflGetPagesService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
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

		return self::returnResponse($resp);
	}

	public function GetPagesInfo( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetPagesInfoService.class.php';

		try {
			$service = new WflGetPagesInfoService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function CreateObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectLabelsService.class.php';

		try {
			$service = new WflCreateObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function UpdateObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectLabelsService.class.php';

		try {
			$service = new WflUpdateObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function DeleteObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectLabelsService.class.php';

		try {
			$service = new WflDeleteObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function AddObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflAddObjectLabelsService.class.php';

		try {
			$service = new WflAddObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}

	public function RemoveObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRemoveObjectLabelsService.class.php';

		try {
			$service = new WflRemoveObjectLabelsService();
			$resp = $service->execute( $req );
		} catch( BizException $e ) {
			throw new SoapFault( $e->getType(), $e->getMessage(), '', $e->getDetail() );
		}
		return self::returnResponse($resp);
	}


}