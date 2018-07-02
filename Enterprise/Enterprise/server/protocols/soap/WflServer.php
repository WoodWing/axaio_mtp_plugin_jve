<?php
/**
 * Workflow SOAP server.
 *
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

require_once BASEDIR . '/server/protocols/soap/Server.php';

class WW_SOAP_WflServer extends WW_SOAP_Server 
{
	public function __construct($wsdl, array $options = array())
	{
		$options['uri'] = 'urn:SmartConnection';
		if (! isset( $options['typemap'] ) || ! is_array( $options['typemap'] )) {
			$options['typemap'] = array();
		}
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
		

		// soap handler class
		require_once BASEDIR . '/server/protocols/soap/WflServices.php';
		$className = 'WW_SOAP_WflServices';
		
		parent::__construct($wsdl, $className, $options);
	}

	/**
	 * Checks if client requests for the wsdl file instead of calling a SOAP action.
	 *
	 * @return boolean return true if wsdl has been requested and sent
	 */
	public function wsdlRequest()
	{
		// return wsdl if requested
		if (isset( $_GET['wsdl'] )) {
			$contents = file_get_contents( $this->wsdl );
			// Especially .NET does not interpret our array definitions correctly.
			// Therefore convert them to the WS-I standard, which is understood.
			// The .NET clients should ask for: ?wsdl=ws-i 
			// Other clients should simply ask the usual: ?wsdl
			if( $_GET['wsdl'] == 'ws-i' ) {
				$contents = parent::convertWsdlArrayDefsToWsi( $contents );
			}
			// replace default web service location with the real one
			$contents = str_replace( 'http://127.0.0.1/scenterprise/index.php',
				SERVERURL_ROOT.INETROOT.'/index.php', $contents ); // do not use SERVERURL_SCRIPT (or else "?wsdl" gets added to URL)
			header( 'Content-type: text/xml' );
			header( 'Content-Length: '.strlen($contents) ); // required for PHP v5.3
			print $contents;
			return true;
		}
		return false;
	}
}
