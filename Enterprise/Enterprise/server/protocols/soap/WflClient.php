<?php
/**
 * Workflow SOAP client.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Client.php';

class WW_SOAP_WflClient extends WW_SOAP_Client
{
	public function __construct(array $options = array())
	{
		if (! isset( $options['classmap'] ) || ! is_array( $options['classmap'] )) {
			$options['classmap'] = array();
		}
		
		// add our classmaps
		$options['classmap']['ActionProperty'] = 'ActionProperty';
		$options['classmap']['AppFeature'] = 'AppFeature';
		$options['classmap']['ArticleAtWorkspace'] = 'ArticleAtWorkspace';
		$options['classmap']['Attachment'] = 'Attachment';
		$options['classmap']['AutoSuggestProperty'] = 'AutoSuggestProperty';
		$options['classmap']['AutoSuggestTag'] = 'AutoSuggestTag';
		$options['classmap']['BasicMetaData'] = 'BasicMetaData';
		$options['classmap']['Category'] = 'Category';
		$options['classmap']['CategoryInfo'] = 'CategoryInfo';
		$options['classmap']['ChildRow'] = 'ChildRow';
		$options['classmap']['ContentMetaData'] = 'ContentMetaData';
		$options['classmap']['Dialog'] = 'Dialog';
		$options['classmap']['DialogButton'] = 'DialogButton';
		$options['classmap']['DialogTab'] = 'DialogTab';
		$options['classmap']['DialogWidget'] = 'DialogWidget';
		$options['classmap']['Dictionary'] = 'Dictionary';
		$options['classmap']['Edition'] = 'Edition';
		$options['classmap']['EditionPages'] = 'EditionPages';
		$options['classmap']['EditionRenditionsInfo'] = 'EditionRenditionsInfo';
		$options['classmap']['Element'] = 'Element';
		$options['classmap']['EntityTags'] = 'EntityTags';
		$options['classmap']['ErrorReport'] = 'ErrorReport';
		$options['classmap']['ErrorReportEntity'] = 'ErrorReportEntity';
		$options['classmap']['ErrorReportEntry'] = 'ErrorReportEntry';
		$options['classmap']['ExtraMetaData'] = 'ExtraMetaData';
		$options['classmap']['Facet'] = 'Facet';
		$options['classmap']['FacetItem'] = 'FacetItem';
		$options['classmap']['Feature'] = 'Feature';
		$options['classmap']['FeatureAccess'] = 'FeatureAccess';
		$options['classmap']['FeatureProfile'] = 'FeatureProfile';
		$options['classmap']['InDesignArticle'] = 'InDesignArticle';
		$options['classmap']['Issue'] = 'Issue';
		$options['classmap']['IssueInfo'] = 'IssueInfo';
		$options['classmap']['LayoutObject'] = 'LayoutObject';
		$options['classmap']['Message'] = 'Message';
		$options['classmap']['MessageList'] = 'MessageList';
		$options['classmap']['MessageQueueConnection'] = 'MessageQueueConnection';
		$options['classmap']['MetaData'] = 'MetaData';
		$options['classmap']['MetaDataValue'] = 'MetaDataValue';
		$options['classmap']['NamedQueryType'] = 'NamedQueryType';
		$options['classmap']['Object'] = 'Object';
		$options['classmap']['ObjectInfo'] = 'ObjectInfo';
		$options['classmap']['ObjectLabel'] = 'ObjectLabel';
		$options['classmap']['ObjectOperation'] = 'ObjectOperation';
		$options['classmap']['ObjectPageInfo'] = 'ObjectPageInfo';
		$options['classmap']['ObjectTargetsInfo'] = 'ObjectTargetsInfo';
		$options['classmap']['ObjectTypeProperty'] = 'ObjectTypeProperty';
		$options['classmap']['ObjectVersion'] = 'ObjectVersion';
		$options['classmap']['Page'] = 'Page';
		$options['classmap']['PageObject'] = 'PageObject';
		$options['classmap']['Param'] = 'Param';
		$options['classmap']['PlacedObject'] = 'PlacedObject';
		$options['classmap']['Placement'] = 'Placement';
		$options['classmap']['PlacementInfo'] = 'PlacementInfo';
		$options['classmap']['PlacementTile'] = 'PlacementTile';
		$options['classmap']['Property'] = 'Property';
		$options['classmap']['PropertyInfo'] = 'PropertyInfo';
		$options['classmap']['PropertyNotification'] = 'PropertyNotification';
		$options['classmap']['PropertyUsage'] = 'PropertyUsage';
		$options['classmap']['PropertyValue'] = 'PropertyValue';
		$options['classmap']['PubChannel'] = 'PubChannel';
		$options['classmap']['PubChannelInfo'] = 'PubChannelInfo';
		$options['classmap']['Publication'] = 'Publication';
		$options['classmap']['PublicationInfo'] = 'PublicationInfo';
		$options['classmap']['QueryOrder'] = 'QueryOrder';
		$options['classmap']['QueryParam'] = 'QueryParam';
		$options['classmap']['Relation'] = 'Relation';
		$options['classmap']['RenditionTypeInfo'] = 'RenditionTypeInfo';
		$options['classmap']['RightsMetaData'] = 'RightsMetaData';
		$options['classmap']['RoutingMetaData'] = 'RoutingMetaData';
		$options['classmap']['ServerInfo'] = 'ServerInfo';
		$options['classmap']['Setting'] = 'Setting';
		$options['classmap']['SourceMetaData'] = 'SourceMetaData';
		$options['classmap']['State'] = 'State';
		$options['classmap']['StickyInfo'] = 'StickyInfo';
		$options['classmap']['Suggestion'] = 'Suggestion';
		$options['classmap']['Target'] = 'Target';
		$options['classmap']['Term'] = 'Term';
		$options['classmap']['User'] = 'User';
		$options['classmap']['UserGroup'] = 'UserGroup';
		$options['classmap']['VersionInfo'] = 'VersionInfo';
		$options['classmap']['WorkflowMetaData'] = 'WorkflowMetaData';
		$options['classmap']['GetServersResponse'] = 'WflGetServersResponse';
		$options['classmap']['LogOnResponse'] = 'WflLogOnResponse';
		$options['classmap']['LogOffResponse'] = 'WflLogOffResponse';
		$options['classmap']['GetUserSettingsResponse'] = 'WflGetUserSettingsResponse';
		$options['classmap']['SaveUserSettingsResponse'] = 'WflSaveUserSettingsResponse';
		$options['classmap']['DeleteUserSettingsResponse'] = 'WflDeleteUserSettingsResponse';
		$options['classmap']['ChangePasswordResponse'] = 'WflChangePasswordResponse';
		$options['classmap']['ChangeOnlineStatusResponse'] = 'WflChangeOnlineStatusResponse';
		$options['classmap']['GetStatesResponse'] = 'WflGetStatesResponse';
		$options['classmap']['GetDialogResponse'] = 'WflGetDialogResponse';
		$options['classmap']['GetDialog2Response'] = 'WflGetDialog2Response';
		$options['classmap']['SendToResponse'] = 'WflSendToResponse';
		$options['classmap']['SendToNextResponse'] = 'WflSendToNextResponse';
		$options['classmap']['SendMessagesResponse'] = 'WflSendMessagesResponse';
		$options['classmap']['CreateObjectOperationsResponse'] = 'WflCreateObjectOperationsResponse';
		$options['classmap']['CreateObjectsResponse'] = 'WflCreateObjectsResponse';
		$options['classmap']['InstantiateTemplateResponse'] = 'WflInstantiateTemplateResponse';
		$options['classmap']['GetObjectsResponse'] = 'WflGetObjectsResponse';
		$options['classmap']['SaveObjectsResponse'] = 'WflSaveObjectsResponse';
		$options['classmap']['LockObjectsResponse'] = 'WflLockObjectsResponse';
		$options['classmap']['UnlockObjectsResponse'] = 'WflUnlockObjectsResponse';
		$options['classmap']['DeleteObjectsResponse'] = 'WflDeleteObjectsResponse';
		$options['classmap']['RestoreObjectsResponse'] = 'WflRestoreObjectsResponse';
		$options['classmap']['CopyObjectResponse'] = 'WflCopyObjectResponse';
		$options['classmap']['SetObjectPropertiesResponse'] = 'WflSetObjectPropertiesResponse';
		$options['classmap']['MultiSetObjectPropertiesResponse'] = 'WflMultiSetObjectPropertiesResponse';
		$options['classmap']['QueryObjectsResponse'] = 'WflQueryObjectsResponse';
		$options['classmap']['NamedQueryResponse'] = 'WflNamedQueryResponse';
		$options['classmap']['CreateObjectRelationsResponse'] = 'WflCreateObjectRelationsResponse';
		$options['classmap']['UpdateObjectRelationsResponse'] = 'WflUpdateObjectRelationsResponse';
		$options['classmap']['DeleteObjectRelationsResponse'] = 'WflDeleteObjectRelationsResponse';
		$options['classmap']['GetObjectRelationsResponse'] = 'WflGetObjectRelationsResponse';
		$options['classmap']['CreateObjectTargetsResponse'] = 'WflCreateObjectTargetsResponse';
		$options['classmap']['UpdateObjectTargetsResponse'] = 'WflUpdateObjectTargetsResponse';
		$options['classmap']['DeleteObjectTargetsResponse'] = 'WflDeleteObjectTargetsResponse';
		$options['classmap']['GetVersionResponse'] = 'WflGetVersionResponse';
		$options['classmap']['ListVersionsResponse'] = 'WflListVersionsResponse';
		$options['classmap']['RestoreVersionResponse'] = 'WflRestoreVersionResponse';
		$options['classmap']['CreateArticleWorkspaceResponse'] = 'WflCreateArticleWorkspaceResponse';
		$options['classmap']['ListArticleWorkspacesResponse'] = 'WflListArticleWorkspacesResponse';
		$options['classmap']['GetArticleFromWorkspaceResponse'] = 'WflGetArticleFromWorkspaceResponse';
		$options['classmap']['SaveArticleInWorkspaceResponse'] = 'WflSaveArticleInWorkspaceResponse';
		$options['classmap']['PreviewArticleAtWorkspaceResponse'] = 'WflPreviewArticleAtWorkspaceResponse';
		$options['classmap']['PreviewArticlesAtWorkspaceResponse'] = 'WflPreviewArticlesAtWorkspaceResponse';
		$options['classmap']['DeleteArticleWorkspaceResponse'] = 'WflDeleteArticleWorkspaceResponse';
		$options['classmap']['CheckSpellingResponse'] = 'WflCheckSpellingResponse';
		$options['classmap']['GetSuggestionsResponse'] = 'WflGetSuggestionsResponse';
		$options['classmap']['CheckSpellingAndSuggestResponse'] = 'WflCheckSpellingAndSuggestResponse';
		$options['classmap']['AutocompleteResponse'] = 'WflAutocompleteResponse';
		$options['classmap']['SuggestionsResponse'] = 'WflSuggestionsResponse';
		$options['classmap']['GetPagesResponse'] = 'WflGetPagesResponse';
		$options['classmap']['GetPagesInfoResponse'] = 'WflGetPagesInfoResponse';
		$options['classmap']['CreateObjectLabelsResponse'] = 'WflCreateObjectLabelsResponse';
		$options['classmap']['UpdateObjectLabelsResponse'] = 'WflUpdateObjectLabelsResponse';
		$options['classmap']['DeleteObjectLabelsResponse'] = 'WflDeleteObjectLabelsResponse';
		$options['classmap']['AddObjectLabelsResponse'] = 'WflAddObjectLabelsResponse';
		$options['classmap']['RemoveObjectLabelsResponse'] = 'WflRemoveObjectLabelsResponse';
		

		if( !array_key_exists( 'location', $options ) ) {
			$options['location'] = LOCALURL_ROOT.INETROOT.'/index.php';
		}
		$options['uri'] = 'urn:SmartConnection';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;

		// soap handler class
		parent::__construct( $options['location'].'?wsdl', $options );
	}
}
