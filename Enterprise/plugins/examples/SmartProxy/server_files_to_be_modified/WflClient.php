<?php
/**
 * Workflow SOAP client.
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
		$options['classmap']['Attachment'] = 'Attachment';
		$options['classmap']['BasicMetaData'] = 'BasicMetaData';
		$options['classmap']['Category'] = 'Category';
		$options['classmap']['CategoryInfo'] = 'CategoryInfo';
		$options['classmap']['ChildRow'] = 'ChildRow';
		$options['classmap']['ContentMetaData'] = 'ContentMetaData';
		$options['classmap']['Dialog'] = 'Dialog';
		$options['classmap']['DialogTab'] = 'DialogTab';
		$options['classmap']['DialogWidget'] = 'DialogWidget';
		$options['classmap']['Edition'] = 'Edition';
		$options['classmap']['EditionPages'] = 'EditionPages';
		$options['classmap']['Element'] = 'Element';
		$options['classmap']['ExtraMetaData'] = 'ExtraMetaData';
		$options['classmap']['Facet'] = 'Facet';
		$options['classmap']['FacetItem'] = 'FacetItem';
		$options['classmap']['Feature'] = 'Feature';
		$options['classmap']['FeatureAccess'] = 'FeatureAccess';
		$options['classmap']['FeatureProfile'] = 'FeatureProfile';
		$options['classmap']['Issue'] = 'Issue';
		$options['classmap']['IssueInfo'] = 'IssueInfo';
		$options['classmap']['LayoutObject'] = 'LayoutObject';
		$options['classmap']['Message'] = 'Message';
		$options['classmap']['MetaData'] = 'MetaData';
		$options['classmap']['MetaDataValue'] = 'MetaDataValue';
		$options['classmap']['NamedQueryType'] = 'NamedQueryType';
		$options['classmap']['Object'] = 'Object';
		$options['classmap']['ObjectInfo'] = 'ObjectInfo';
		$options['classmap']['ObjectPageInfo'] = 'ObjectPageInfo';
		$options['classmap']['ObjectTargetsInfo'] = 'ObjectTargetsInfo';
		$options['classmap']['ObjectTypeProperty'] = 'ObjectTypeProperty';
		$options['classmap']['ObjectVersion'] = 'ObjectVersion';
		$options['classmap']['Page'] = 'Page';
		$options['classmap']['PageObject'] = 'PageObject';
		$options['classmap']['PlacedObject'] = 'PlacedObject';
		$options['classmap']['Placement'] = 'Placement';
		$options['classmap']['PlacementInfo'] = 'PlacementInfo';
		$options['classmap']['Property'] = 'Property';
		$options['classmap']['PropertyInfo'] = 'PropertyInfo';
		$options['classmap']['PropertyUsage'] = 'PropertyUsage';
		$options['classmap']['PubChannel'] = 'PubChannel';
		$options['classmap']['PubChannelInfo'] = 'PubChannelInfo';
		$options['classmap']['Publication'] = 'Publication';
		$options['classmap']['PublicationInfo'] = 'PublicationInfo';
		$options['classmap']['QueryOrder'] = 'QueryOrder';
		$options['classmap']['QueryParam'] = 'QueryParam';
		$options['classmap']['Relation'] = 'Relation';
		$options['classmap']['Rendition'] = 'Rendition';
		$options['classmap']['RightsMetaData'] = 'RightsMetaData';
		$options['classmap']['Section'] = 'Section';
		$options['classmap']['SectionInfo'] = 'SectionInfo';
		$options['classmap']['ServerInfo'] = 'ServerInfo';
		$options['classmap']['Setting'] = 'Setting';
		$options['classmap']['SourceMetaData'] = 'SourceMetaData';
		$options['classmap']['State'] = 'State';
		$options['classmap']['StickyInfo'] = 'StickyInfo';
		$options['classmap']['Target'] = 'Target';
		$options['classmap']['Term'] = 'Term';
		$options['classmap']['User'] = 'User';
		$options['classmap']['UserGroup'] = 'UserGroup';
		$options['classmap']['VersionInfo'] = 'VersionInfo';
		$options['classmap']['WorkflowMetaData'] = 'WorkflowMetaData';
		

		if (!isset($options['location']))
			$options['location'] = LOCALURL_ROOT.INETROOT.'/index.php';
			
		$options['uri'] = 'urn:SmartConnection';
		$options['use'] = SOAP_LITERAL;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		$options['soap_version'] = SOAP_1_1;
		//$options['local_cert'] = BASEDIR.'/config/encryptkeys/cacert.pem'; // for HTTPS / SSL only

		// soap handler class
		parent::__construct( LOCALURL_ROOT.INETROOT.'/index.php'.'?wsdl', $options );
	}
}
