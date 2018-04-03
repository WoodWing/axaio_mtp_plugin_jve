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
require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
require_once BASEDIR.'/server/secure.php';

class WW_JSON_WflServices extends WW_JSON_Services
{
	public function GetServers( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetServersService.class.php';
		$req['__classname__'] = 'WflGetServersRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetServersService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function LogOn( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';
		$req['__classname__'] = 'WflLogOnRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflLogOnService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function LogOff( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLogOffService.class.php';
		$req['__classname__'] = 'WflLogOffRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflLogOffService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetUserSettings( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetUserSettingsService.class.php';
		$req['__classname__'] = 'WflGetUserSettingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetUserSettingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SaveUserSettings( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveUserSettingsService.class.php';
		$req['__classname__'] = 'WflSaveUserSettingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSaveUserSettingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteUserSettings( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteUserSettingsService.class.php';
		$req['__classname__'] = 'WflDeleteUserSettingsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflDeleteUserSettingsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ChangePassword( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflChangePasswordService.class.php';
		$req['__classname__'] = 'WflChangePasswordRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflChangePasswordService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ChangeOnlineStatus( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflChangeOnlineStatusService.class.php';
		$req['__classname__'] = 'WflChangeOnlineStatusRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflChangeOnlineStatusService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetStates( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetStatesService.class.php';
		$req['__classname__'] = 'WflGetStatesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetStatesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetDialog( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialogService.class.php';
		$req['__classname__'] = 'WflGetDialogRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetDialogService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetDialog2( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetDialog2Service.class.php';
		$req['__classname__'] = 'WflGetDialog2Request';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetDialog2Service();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SendTo( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToService.class.php';
		$req['__classname__'] = 'WflSendToRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSendToService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SendToNext( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendToNextService.class.php';
		$req['__classname__'] = 'WflSendToNextRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSendToNextService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SendMessages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';
		$req['__classname__'] = 'WflSendMessagesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSendMessagesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateObjectOperations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectOperationsService.class.php';
		$req['__classname__'] = 'WflCreateObjectOperationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCreateObjectOperationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		$req['__classname__'] = 'WflCreateObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
		$service = new WflCreateObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function InstantiateTemplate( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflInstantiateTemplateService.class.php';
		$req['__classname__'] = 'WflInstantiateTemplateRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflInstantiateTemplateService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );

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
		$req['__classname__'] = 'WflGetObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );

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
		$req['__classname__'] = 'WflSaveObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
			require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			if( $req->Objects ) foreach( $req->Objects as $object ) {
				$transferServer->switchURLToFilePath( $object );
			}
		$service = new WflSaveObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function LockObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflLockObjectsService.class.php';
		$req['__classname__'] = 'WflLockObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflLockObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UnlockObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req['__classname__'] = 'WflUnlockObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflUnlockObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req['__classname__'] = 'WflDeleteObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflDeleteObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function RestoreObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRestoreObjectsService.class.php';
		$req['__classname__'] = 'WflRestoreObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflRestoreObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CopyObject( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCopyObjectService.class.php';
		$req['__classname__'] = 'WflCopyObjectRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCopyObjectService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SetObjectProperties( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSetObjectPropertiesService.class.php';
		$req['__classname__'] = 'WflSetObjectPropertiesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSetObjectPropertiesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function MultiSetObjectProperties( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflMultiSetObjectPropertiesService.class.php';
		$req['__classname__'] = 'WflMultiSetObjectPropertiesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflMultiSetObjectPropertiesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function QueryObjects( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req['__classname__'] = 'WflQueryObjectsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflQueryObjectsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function NamedQuery( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflNamedQueryService.class.php';
		$req['__classname__'] = 'WflNamedQueryRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflNamedQueryService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectRelationsService.class.php';
		$req['__classname__'] = 'WflCreateObjectRelationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCreateObjectRelationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UpdateObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectRelationsService.class.php';
		$req['__classname__'] = 'WflUpdateObjectRelationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflUpdateObjectRelationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectRelationsService.class.php';
		$req['__classname__'] = 'WflDeleteObjectRelationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflDeleteObjectRelationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetObjectRelations( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetObjectRelationsService.class.php';
		$req['__classname__'] = 'WflGetObjectRelationsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetObjectRelationsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectTargetsService.class.php';
		$req['__classname__'] = 'WflCreateObjectTargetsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCreateObjectTargetsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UpdateObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectTargetsService.class.php';
		$req['__classname__'] = 'WflUpdateObjectTargetsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflUpdateObjectTargetsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteObjectTargets( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectTargetsService.class.php';
		$req['__classname__'] = 'WflDeleteObjectTargetsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflDeleteObjectTargetsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetVersion( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetVersionService.class.php';
		$req['__classname__'] = 'WflGetVersionRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetVersionService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		if( $resp->VersionInfo->File ) {
			$transferServer->filePathToURL( $resp->VersionInfo->File );
		}

		return $resp;
	}

	public function ListVersions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflListVersionsService.class.php';
		$req['__classname__'] = 'WflListVersionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflListVersionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );

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
		$req['__classname__'] = 'WflRestoreVersionRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflRestoreVersionService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateArticleWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateArticleWorkspaceService.class.php';
		$req['__classname__'] = 'WflCreateArticleWorkspaceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCreateArticleWorkspaceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function ListArticleWorkspaces( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflListArticleWorkspacesService.class.php';
		$req['__classname__'] = 'WflListArticleWorkspacesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflListArticleWorkspacesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetArticleFromWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetArticleFromWorkspaceService.class.php';
		$req['__classname__'] = 'WflGetArticleFromWorkspaceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetArticleFromWorkspaceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function SaveArticleInWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSaveArticleInWorkspaceService.class.php';
		$req['__classname__'] = 'WflSaveArticleInWorkspaceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSaveArticleInWorkspaceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function PreviewArticleAtWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticleAtWorkspaceService.class.php';
		$req['__classname__'] = 'WflPreviewArticleAtWorkspaceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflPreviewArticleAtWorkspaceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function PreviewArticlesAtWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflPreviewArticlesAtWorkspaceService.class.php';
		$req['__classname__'] = 'WflPreviewArticlesAtWorkspaceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflPreviewArticlesAtWorkspaceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteArticleWorkspace( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteArticleWorkspaceService.class.php';
		$req['__classname__'] = 'WflDeleteArticleWorkspaceRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflDeleteArticleWorkspaceService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CheckSpelling( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCheckSpellingService.class.php';
		$req['__classname__'] = 'WflCheckSpellingRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCheckSpellingService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetSuggestions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetSuggestionsService.class.php';
		$req['__classname__'] = 'WflGetSuggestionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetSuggestionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CheckSpellingAndSuggest( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCheckSpellingAndSuggestService.class.php';
		$req['__classname__'] = 'WflCheckSpellingAndSuggestRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCheckSpellingAndSuggestService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function Autocomplete( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflAutocompleteService.class.php';
		$req['__classname__'] = 'WflAutocompleteRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflAutocompleteService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function Suggestions( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflSuggestionsService.class.php';
		$req['__classname__'] = 'WflSuggestionsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflSuggestionsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetPages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetPagesService.class.php';
		$req['__classname__'] = 'WflGetPagesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetPagesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );

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
		$req['__classname__'] = 'WflGetPagesInfoRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetPagesInfoService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function GetRelatedPages( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflGetRelatedPagesService.class.php';
		$req['__classname__'] = 'WflGetRelatedPagesRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetRelatedPagesService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );

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
		$req['__classname__'] = 'WflGetRelatedPagesInfoRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflGetRelatedPagesInfoService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function CreateObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectLabelsService.class.php';
		$req['__classname__'] = 'WflCreateObjectLabelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflCreateObjectLabelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function UpdateObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflUpdateObjectLabelsService.class.php';
		$req['__classname__'] = 'WflUpdateObjectLabelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflUpdateObjectLabelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function DeleteObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectLabelsService.class.php';
		$req['__classname__'] = 'WflDeleteObjectLabelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflDeleteObjectLabelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function AddObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflAddObjectLabelsService.class.php';
		$req['__classname__'] = 'WflAddObjectLabelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflAddObjectLabelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}

	public function RemoveObjectLabels( $req )
	{
		require_once BASEDIR.'/server/services/wfl/WflRemoveObjectLabelsService.class.php';
		$req['__classname__'] = 'WflRemoveObjectLabelsRequest';
		$req = $this->arraysToObjects( $req );
		$req = $this->restructureObjects( $req );
		$service = new WflRemoveObjectLabelsService();
		$resp = $service->execute( $req );
		$resp = $this->restructureObjects( $resp );
		return $resp;
	}


}
