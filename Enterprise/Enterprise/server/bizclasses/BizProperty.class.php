<?php

/**
 * @package 	SCEnterprise
 * @subpackage 	BizClasses
 * @since 		v5.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */
class BizProperty
{
	const MULTIVALUE_SEPARATOR = '/';

	static private $InfoProps; // list of PropertyInfo, typically used in GetDialog and QueryObjects services
	static private $MetaProps; // list of MetaData paths, typically used for CreateObjects, SaveObjects and SetProperties services
	static private $ObjFProps; // list of column names used in smart_objects table.
	static private $SqlTProps; // list of column types used in smart_objects table.
	static private $JoinProps; // list of join-aliases required to fetch the property-value
	static private $JFldProps; // list of join-fields required to fetch the property-value

	/**
	 * List of properties that can not be customized.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */

	public static function getStaticPropIds()
	{
		return array('Name', 'Publication', 'PubChannels', 'Targets', 'Issues',
						'Issue', // BZ#10219 'Issue' is still there for the sake of client applications (like ID/IC) that deal with 1 issue (which are not multi-channel aware)
						'Editions', 'Category', 'State');
	}

	/**
	 * List of properties that can be partially customized (renamed only).
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getDynamicPropIds()
	{
		return array('ID', 'Type', 'Keywords', 'Slugline', 'Format', 'Columns', 'Width', 'Height', 'Dpi', 'LengthWords',
					'LengthChars', 'LengthParas', 'LengthLines', 'PlainContent', 'FileSize', 'ColorSpace', 'Deadline',
		             'Modifier', 'Creator', 'Comment', 'RouteTo', 'LockedBy', 'Deletor', 'Deleted', 'Version', 'PlacedOn',
			         'PlacedOnPage', 'Flag', 'FlagMsg',	'PageRange', 'PlannedPageRange', 'ContentSource', 'Encoding',
			         'Compression', 'KeyFrameEveryFrames', 'Channels', 'AspectRatio', 'Rating','ElementName', 'Dossier',
			         'UnreadMessageCount');
	}

	/**
	 * List of properties that can be partially customized (renamed only) and are used in Adobe XMP meta data.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getXmpPropIds()
	{
		return array('DocumentID', 'CopyrightMarked', 'Copyright', 'CopyrightURL', 'Credit', 'Source', 'Author',
					'Description', 'DescriptionAuthor', 'Urgency', 'Modified', 'Created');
	}

	/**
	 * List of properties of which their values are determined or calculated by the Enterprise system.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getSystemPropIds()
	{
		return array('ID', 'DocumentID', 'Type', 'Format', 'Columns', 'Width', 'Height', 'Dpi', 'LengthWords', 'LengthChars',
		             'LengthParas', 'LengthLines', 'FileSize', 'ColorSpace', 'Modifier', 'Modified', 'Creator', 'Created', 'LockedBy', 'Deletor', 'Deleted',
		             'Version', 'PlacedOn', 'Flag', 'FlagMsg', 'PageRange', 'PlannedPageRange', 'LockForOffline', 'DeadlineChanged', 'DeadlineSoft',
		             'HighResFile', 'PlacedOnPage', 'StateColor', 'StatePhase', 'ElementName', 'PlainContent', /*'Slugline', BZ#31369 */ 'UnreadMessageCount');
	}

	/**
	 * List of properties that are sent through MetaData->WorkflowMetaData SOAP elements.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getWorkflowPropIds()
	{
		return array('Deadline','Urgency','Modifier','Modified','Creator','Created','Comment',
		             'RouteTo','LockedBy', 'Deletor', 'Deleted','Version','DeadlineSoft','Rating');
	}

	/**
	 * List of properties that are used for specific query purposes.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getSpecialQueryPropIds()
	{
		return array ('DeadlineSoft', 'PlacedOnPage');
	}

	/**
	 * List of properties that are communicated with clients, but not shown at users
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getIdentifierPropIds()
	{
		return array( 'ID', 'PublicationId','CategoryId','IssueId','SectionId', 'PubChannelIds','IssueIds','EditionIds', 'StateId' );
	}

	/**
	 * List of properties that are not communicated with with outside world
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getInternalPropIds()
	{
		return array( 'StoreName','MajorVersion','MinorVersion','Indexed','Closed','Types', 'RouteToUser', 'RouteToGroup', 'HasChildren');
	}

	/**
	 * List of properties that are used in query results to client apps when no customizations are done.
	 * $areas indicates whether it is in Workflow(where non-deleted objects reside) or Trash(where deleted objects reside) area. Properties vary according to the area.
	 * For an example: Property such as 'Modifier' and 'Modified' are not needed in Trash area but 'Deleter' and 'Deleted' are more appropriate.
	 *
	 * @param array $areas
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getStandardQueryPropIds( $areas = array('Workflow'))
	{
		$ret = array('ID','Type','Name','State','RouteTo','LockedBy','PlacedOn', 'PlacedOnPage',
		             'FileSize', 'LengthWords','LengthChars','LengthLines','Comment','Slugline', 'PageRange', 'Flag', 'FlagMsg',
			'Publication','Issues','Category', 'Editions');
		if( in_array('Workflow',$areas) ) { // workflow
			$ret = array_merge( $ret,  array( 'Modifier','Modified','PlannedPageRange','Deadline', 'DeadlineSoft', 'UnreadMessageCount'));
		}
		if( in_array('Trash',$areas) ) { // Trash Can
			$ret = array_merge( $ret,  array( 'Deleted','Deletor'));
	}
		return $ret;
	}

	/**
	 * List of properties that are used in query results to web apps when no customizations are done.
	 * $areas indicates whether it is in Workflow(where non-deleted objects reside) or Trash(where deleted objects reside) area. Properties vary according to the area.
	 * For an example: Property such as 'Modifier' and 'Modified' are not needed in Trash area but 'Deleter' and 'Deleted' are more appropriate.
	 *
	 * @param array $areas
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getWebQueryPropIds( $areas = array('Workflow'))
	{
		$ret = array('ID','Type','Name','Format','State','RouteTo','Comment','LockedBy','PlacedOn','PageRange');
		if( in_array('Workflow',$areas) ) { // workflow
			$ret = array_merge( $ret,  array( 'Modifier','Modified','PlannedPageRange','Deadline', 'DeadlineSoft', 'UnreadMessageCount'));
		}
		if( in_array('Trash',$areas) ) { // Trash Can
			$ret = array_merge( $ret,  array('Deleted','Deletor'));
		}
		return $ret;
	}

	/**
	 * List of properties that are used in workflow dialogs when no customizations are done.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getDefaultDialogPropIds()
	{
		return array_merge( self::getStaticPropIds(), array('Dossier', 'RouteTo','Comment') );
	}

	/**
	 * List of target related properties that are used in workflow dialogs.
	 *
	 * @return array of string  Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getTargetRelatedPropIds()
	{
		return array( 'Targets', 'PubChannels', 'Issues', 'Editions' );
	}

	/**
	 * List of properties (as used in workflow dialogs) for which the dialog must be redrawn/refreshed
	 * upon a value change of such property. That is done by clients re-calling the GetDialog(2) service.
	 *
	 * @return array of string Internal property names (ids) as used in workflow WSDL.
	 */
	public static function getDefaultRefreshPropIds()
	{
		return array(
			'Publication', // changes workflow
			'Category', 'State', // changes RouteTo or access profile (which implies a different data set)
			'PubChannels', 'Issue', 'Issues' ); // changes Dossiers (or overrule issue changes workflow)
	}

	/**
	 * List of property usages for a specific level of customization. <br/>
	 * When no customizations are found at that level, it searches for more generic levels. <br/>
	 * In case no customizations are made, the default set of usages for workflow dialogs is returned. <br/>
	 * All static properties are always included. <br/>
	 * Returned prop usages are ordered as they should be displayed in workflow dialogs.
	 *
	 * $wiwiwUsages: This is only used when in the context of dealing with PublishFormTemplates and PublishForm.
	 * Send in an empty array when caller is dealing with PublishFormTemplates and PublishForm; Null otherwise.
	 * When empty array is sent in, a three dimensional list of PropertyUsages that belong to placed objects on the form
	 * will be returned. Keys are used as follows: $wiwiwUsages[mainProp][wiwProp][wiwiwProp]
	 *
	 * $onlyMultiSetProperties:
	 * When set to true, function only returns properties that support multi set (configured in dialog setup page) and
	 *      -All- static properties.
	 * When set to false (Default), function returns all properties including those configured in dialog setup page and
	 *      -All- static properties.
	 *
	 * @param string $publ    Publication ID.
	 * @param string $objType Object type.
	 * @param string $action  Action type.
	 * @param boolean $withStaticProps If static (default) properties should be returned as well (BZ#6516). Depends on calling code.
	 * @param boolean $explicit  Request NOT to fall back at global definition levels. Specified level only. (BZ#14553)
	 * @param null|string $documentId A specific DocumentID to fetch the property usages for.
	 * @param null|array $wiwiwUsages [writable] See header above.
	 * @param bool $onlyMultiSetProperties See header above.
	 * @return array of PropertyUsage  PropertyUsage definitions as used in workflow WSDL.
	 */
	public static function getPropertyUsages( $publ, $objType, $action, $withStaticProps=true, $explicit=false,
	                                          $documentId=null, &$wiwiwUsages, $onlyMultiSetProperties=false )
	{
		// Get any customizations at most specific level
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		require_once BASEDIR.'/server/dbclasses/DBActionproperty.class.php';
		$usages = DBActionproperty::getPropertyUsages( $publ, $objType, $action, $explicit, $documentId, $wiwiwUsages );
		$targetProps = array_flip( self::getTargetRelatedPropIds() );
		$refreshProps = array_flip( self::getDefaultRefreshPropIds() );
		if( $onlyMultiSetProperties ) {
			// Filter out all the properties that don't support multi-set.
			if( $usages ) foreach( $usages as $usageName => $usage ) {
				if( !$usage->MultipleObjects ) {
					unset( $usages[$usageName] );
				}
			}
		}
		if( empty( $usages ) ) {
			// No customization found, return defaults
			if ( $withStaticProps ) {
				$props = self::getDefaultDialogPropIds();
				foreach( $props as $prop ) {
					$propUsage = new PropertyUsage();
					$propUsage->Name = $prop;
					$propUsage->Editable = true;
					$propUsage->Mandatory = false;
					$propUsage->Restricted = false;
					$propUsage->RefreshOnChange = isset($refreshProps[$prop]);
					// ** For static property, it should never be added to multi-set properties dialog (Hence set to false).
					// However, there's an exception, 'State' and 'Category' are allowed to be edited, including for
					// multi-set-properties. (Hence set to true).
					// For other non-static default dialog props, it is safe to put false here since
					// the admin did not configure them to have MultipleObjects set to be True in the Dialog Setup.
					$propUsage->MultipleObjects = ( $prop == 'State' || $prop == 'Category' ) ? true : false; // Refer to ** above.

					if (($prop == 'RouteTo') || ($prop == 'Comment') || //'Route to' and 'Comment'are not mandatory
						($prop == 'Issue') || isset($targetProps[$prop]) || // BZ#16792
						($prop == 'Dossier') ) { // BZ#17831
						// nothing to do; use default
					} elseif( $action == 'SendTo' ) { //BZ#8836 Static props except for State are not editable when $action is SendTo
						if( $prop != 'State' ) {
							$propUsage->Editable = false;
						}
					} else {
						$propUsage->Mandatory = true;
					}	
					$usages[$prop] = $propUsage;
				}	
			}
		} else {
			if ( $withStaticProps ) {
				// Pre-insert static props (when missing) in the right order.
				// Trick to insert key-value pairs on top: reverse the order, add them at end and reverse them again.
				$staticProps = self::getStaticPropIds();

				$staticProps = array_reverse( $staticProps, true );
				if( $action == 'SendTo' ) { // BZ#20061 - Filter out those non Workflow properties, when action is SendTo
					$wflProps = self::getWorkflowPropIds();
					$tempUsages = array();
					foreach( $usages as $usage ) {
						if( in_array($usage->Name, $wflProps) ) {
							$tempUsages[$usage->Name] = $usage;
						}
					}
					$usages = $tempUsages;
				}
				$usages = array_reverse( $usages, true );
				foreach( $staticProps as $staticProp ) {
					$propUsage = new PropertyUsage();
					$propUsage->Name = $staticProp;
					$propUsage->Editable = true;
					$propUsage->Mandatory = false;
					$propUsage->Restricted = false;
					$propUsage->RefreshOnChange = isset($refreshProps[$staticProp]);
					// For static property, it should never be added to multi-set properties dialog.
					// However, there's an exception, 'State' and 'Category' are allowed to be edited (even for multi-set-properties)
					$propUsage->MultipleObjects = ( $staticProp == 'State' || $staticProp == 'Category' ) ? true : false;

					if( !array_key_exists( $staticProp, $usages ) ) {
						if ($action == 'SendTo') {
							if ($staticProp == 'State') {
								$propUsage->Mandatory = true;
							} else {
								$propUsage->Editable = false;
							}
						} else if( ($staticProp == 'Issue') || isset( $targetProps[$staticProp] ) ) { // BZ#16792
							// nothing to do; use default
						} else {
							$propUsage->Mandatory = true;
						}
					}
					$usages[$staticProp] = $propUsage;
				}
				$usages = array_reverse( $usages, true );
			}
		}
		return $usages;
	}

	/**
	 * The complete list of PropertyInfo, typically used in GetDialog and QueryObjects services
	 * @return array of PropertyInfo, See also workflow WSDL.
	 */
	public static function getPropertyInfos()
	{
		if( !isset( self::$InfoProps ) ) {
			self::buildProperties();
		}
		return self::$InfoProps;
	}

	/**
	 * Returns the full PropertyInfo objects instead of key / value pairs.
	 *
	 * @static
	 * @param null|string $pluginName Plugin unique name. Default null
	 * @param null|string $propName The name of the property. Default null
	 * @param null|string $objectType Object type.
	 * @param null|string $publishSystem For which Publish System the property is applicable.
	 * @param null|integer $templateId The unique publishing form template database id.
	 * @param bool $getTermEntityPropOnly When set to true,only Term Entity properties are returned, else all properties are returned.
	 * @return array An array of PropertyInfo objects.
	 */
	public static function getFullPropertyInfos($pluginName = null, $propName = null, $objectType = null,
											$publishSystem = null, $templateId = null, $getTermEntityPropOnly = false )
	{
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		return DBProperty::getPropertyInfos($pluginName, $propName, $objectType, $publishSystem, $templateId, $getTermEntityPropOnly );
	}

	/**
	 * Returns the display name of a (custom) property. 
	 *
	 * @param string $property
	 * @return true id displayname is found else false 
	 */
	public static function getPropertyDisplayName($property)
	{	
		$propInfos = self::getPropertyInfos();	
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		$customProps = DBProperty::getProperties( 0, '', false );
				
		if( isset($customProps[$property])) {
			$displayName = $customProps[$property]->DisplayName;
		} elseif( isset($propInfos[$property])) {
			$displayName = $propInfos[$property]->DisplayName;
		} else { // typically happens for child row props like IDC, Snippet, etc
			$displayName = $property;
		}		
		
		return $displayName;
	}
	
	/**
	 * The complete list of MetaData paths, typically used for CreateObjects, SaveObjects and SetProperties services
	 * @return array of string, See also workflow WSDL.
	 */
	public static function getMetaDataPaths()
	{
		if( !isset( self::$MetaProps ) ) {
			self::buildProperties();
		}
		return self::$MetaProps;
	}

	/**
	 * The complete list of smart_objects field names. Typically used to map MetaData or Properties
	 * onto DB table or vice versa.
	 * @return array of string,
	 */
	public static function getMetaDataObjFields()
	{
		if( !isset( self::$ObjFProps ) ) {
			self::buildProperties();
		}
		return self::$ObjFProps;
	}

	/**
	 * The complete list of SQL field types. Typically used to map onto field names to build SQL.
	 * These fields include the joined(!) fields (so not only smart_objects field types)
	 * @return array of string,
	 */
	public static function getMetaDataSqlFieldTypes()
	{
		if( !isset( self::$SqlTProps ) ) {
			self::buildProperties();
		}
		return self::$SqlTProps;
	}

	public static function getJoinProps()
	{
		if (!isset(self::$JoinProps)) {
			self::buildProperties();
		}
		return self::$JoinProps;
	}

	public static function getJFldProps()
	{
		if (!isset(self::$JFldProps)) {
			self::buildProperties();
		}
		return self::$JFldProps;
	}

	private static function buildProperties()
	{
		// BasicMetaData
		self::$InfoProps['ID']           = new PropertyInfo( 'ID',          BizResources::localize( 'OBJ_ID' ),         null,'string' );
		self::$MetaProps['ID']           = 'BasicMetaData->ID';
		self::$ObjFProps['ID']           = 'id';
		self::$SqlTProps['ID']           = 'int';
		self::$JoinProps['ID']           = null;
		self::$JFldProps['ID']           = null;

		self::$InfoProps['DocumentID']   = new PropertyInfo( 'DocumentID',  BizResources::localize( 'OBJ_DOCUMENT_ID' ),null,'string' );
		self::$InfoProps['DocumentID']->MaxLength = 512;
		self::$MetaProps['DocumentID']   = 'BasicMetaData->DocumentID';
		self::$ObjFProps['DocumentID']   = 'documentid';
		self::$SqlTProps['DocumentID']   = 'string';
		self::$JoinProps['DocumentID']   = null;
		self::$JFldProps['DocumentID']   = null;

		self::$InfoProps['Name']         = new PropertyInfo( 'Name',        BizResources::localize( 'OBJ_NAME' ),       null,'string' );
		self::$InfoProps['Name']->MaxLength = 63; // BZ#23267
		self::$MetaProps['Name']         = 'BasicMetaData->Name';
		self::$ObjFProps['Name']         = 'name';
		self::$SqlTProps['Name']         = 'string';
		self::$JoinProps['Name']	     = null;
		self::$JFldProps['Name']  	     = null;

		self::$InfoProps['Type']         = new PropertyInfo( 'Type',        BizResources::localize( 'OBJ_TYPE2' ),      null,'string' );
		self::$MetaProps['Type']         = 'BasicMetaData->Type';
		self::$ObjFProps['Type']         = 'type';
		self::$SqlTProps['Type']         = 'string';
		self::$JoinProps['Type']	     = null;
		self::$JFldProps['Type']  	     = null;

		self::$InfoProps['PublicationId'] = new PropertyInfo('PublicationId',BizResources::localize('PUB_PUBLICATION_ID' ), null,'string' );
		self::$MetaProps['PublicationId'] = 'BasicMetaData->Publication->Id';
		self::$ObjFProps['PublicationId'] = 'publication';
		self::$SqlTProps['PublicationId'] = 'int';
		self::$JoinProps['PublicationId'] = null;
		self::$JFldProps['PublicationId'] = null;

		self::$InfoProps['Publication']  = new PropertyInfo( 'Publication', BizResources::localize( 'PUBLICATION' ),    null,'list' );
		self::$MetaProps['Publication']  = 'BasicMetaData->Publication->Name';
		self::$ObjFProps['Publication']  = null;
		self::$SqlTProps['Publication']  = 'string';
		self::$JoinProps['Publication']  = 'pub';
		self::$JFldProps['Publication']  = 'publication';

		// In v6.0 Category is a replication of Section.
		self::$InfoProps['CategoryId']   = new PropertyInfo( 'CategoryId',  BizResources::localize( 'CATEGORY' ),       null,'string' );
		self::$MetaProps['CategoryId']   = 'BasicMetaData->Category->Id';
		self::$ObjFProps['CategoryId']   = 'section'; // This is a duplicate, but needed! Use array_unique() to remove in case you need all fields!
		self::$SqlTProps['CategoryId']   = 'int';
		self::$JoinProps['CategoryId']   = null;
		self::$JFldProps['CategoryId']   = null;

		self::$InfoProps['Category']     = new PropertyInfo( 'Category',    BizResources::localize( 'CATEGORY' ),       null, 'list' );
		self::$MetaProps['Category']     = 'BasicMetaData->Category->Name';
		self::$ObjFProps['Category']     = null;
		self::$SqlTProps['Category']     = 'string';
		self::$JoinProps['Category']     = 'sec';
		self::$JFldProps['Category']     = 'section';

		self::$InfoProps['ContentSource'] = new PropertyInfo( 'ContentSource', BizResources::localize( 'OBJ_CONTENTSOURCE' ), null,'string' );
		self::$MetaProps['ContentSource'] = 'BasicMetaData->ContentSource';
		self::$ObjFProps['ContentSource'] = 'contentsource';
		self::$SqlTProps['ContentSource'] = 'string';
		self::$JoinProps['ContentSource'] = null;
		self::$JFldProps['ContentSource'] = null;

		// TargetMetaData (DEPRECATED since v6.0)
		// TargetMetaData has been removed completely from WSDL but cannot remove
		// the four properties below ('IssueId', 'Issue', 'SectionId' and 'Section')
		// eventhough they were previously derived from TargetMetaData. 
		// These four properties are used all over the shops, removing all entirely will risk the
		// Enterprise not working. So just null the issue and issueId's $MetaProps[] 
		// and for Section and SectionId's $MetaProps[], map them to Category and CategoryId.
		self::$InfoProps['IssueId']      = new PropertyInfo( 'IssueId',     BizResources::localize( 'ISS_ISSUE_ID' ),   null,'string' );
		self::$MetaProps['IssueId']      = null;
		self::$ObjFProps['IssueId']      = 'issue';
		self::$SqlTProps['IssueId']      = 'string';
		self::$JoinProps['IssueId']      = null;
		self::$JFldProps['IssueId']      = null;

		self::$InfoProps['Issue']        = new PropertyInfo( 'Issue',       BizResources::localize( 'ISSUE' ),          null,'list' );
		self::$MetaProps['Issue']        = null;
		self::$ObjFProps['Issue']        = null;
		self::$SqlTProps['Issue']        = 'string';
		self::$JoinProps['Issue']		 = 'iss';
		self::$JFldProps['Issue']		 = 'name';

		self::$InfoProps['SectionId']    = new PropertyInfo( 'SectionId',   BizResources::localize( 'SEC_SECTION_ID' ), null,'string' );
		self::$MetaProps['SectionId']    = 'BasicMetaData->Category->Id';
		self::$ObjFProps['SectionId']    = 'section'; // This is a duplicate, but needed! Use array_unique() to remove in case you need all fields!
		self::$SqlTProps['SectionId']    = 'string';
		self::$JoinProps['SectionId']     = null;
		self::$JFldProps['SectionId']     = null;

		self::$InfoProps['Section']      = new PropertyInfo( 'Section',     BizResources::localize( 'SECTION' ),        null,'list' );
		self::$MetaProps['Section']      = 'BasicMetaData->Category->Name';
		self::$ObjFProps['Section']      = null;
		self::$SqlTProps['Section']      = 'string';
		self::$JoinProps['Section']		 = 'sec';
		self::$JFldProps['Section']		 = 'section';

		// RightsMetaData
		self::$InfoProps['Copyright']    = new PropertyInfo( 'Copyright',   BizResources::localize( 'OBJ_COPYRIGHT_C' ), null,'string' );
		self::$InfoProps['Copyright']->MaxLength = 255; // BZ#23267
		self::$MetaProps['Copyright']    = 'RightsMetaData->Copyright';
		self::$ObjFProps['Copyright']    = 'copyright';
		self::$SqlTProps['Copyright']    = 'string';
		self::$JoinProps['Copyright']	 = null;
		self::$JFldProps['Copyright'] 	 = null;

		self::$InfoProps['CopyrightURL'] = new PropertyInfo( 'CopyrightURL',BizResources::localize( 'OBJ_COPYRIGHT_URL' ), null,'string' );
		self::$InfoProps['CopyrightURL']->MaxLength = 255; // BZ#23267
		self::$MetaProps['CopyrightURL'] = 'RightsMetaData->CopyrightURL';
		self::$ObjFProps['CopyrightURL'] = 'copyrighturl';
		self::$SqlTProps['CopyrightURL'] = 'string';
		self::$JoinProps['CopyrightURL'] = null;
		self::$JFldProps['CopyrightURL'] = null;

		self::$InfoProps['CopyrightMarked'] = new PropertyInfo( 'CopyrightMarked',BizResources::localize( 'OBJ_COPYRIGHT_MARKED' ), null,'bool' );
		self::$InfoProps['CopyrightMarked']->MaxLength = 255; // BZ#23267
		self::$MetaProps['CopyrightMarked'] = 'RightsMetaData->CopyrightMarked';
		self::$ObjFProps['CopyrightMarked'] = 'copyrightmarked';
		self::$SqlTProps['CopyrightMarked'] = 'string';
		self::$JoinProps['CopyrightMarked'] = null;
		self::$JFldProps['CopyrightMarked'] = null;

		// SourceMetaData
		self::$InfoProps['Credit']       = new PropertyInfo( 'Credit',      BizResources::localize( 'OBJ_CREDIT' ),     null,'string' );
		self::$InfoProps['Credit']->MaxLength = 255; // BZ#23267
		self::$MetaProps['Credit']       = 'SourceMetaData->Credit';
		self::$ObjFProps['Credit']       = 'credit';
		self::$SqlTProps['Credit']       = 'string';
		self::$JoinProps['Credit'] 		 = null;
		self::$JFldProps['Credit'] 		 = null;

		self::$InfoProps['Source']       = new PropertyInfo( 'Source',      BizResources::localize( 'OBJ_SOURCE' ),     null,'string' );
		self::$InfoProps['Source']->MaxLength = 255; // BZ#23267
		self::$MetaProps['Source']       = 'SourceMetaData->Source';
		self::$ObjFProps['Source']       = 'source';
		self::$SqlTProps['Source']       = 'string';
		self::$JoinProps['Source'] 		 = null;
		self::$JFldProps['Source'] 		 = null;

		self::$InfoProps['Author']       = new PropertyInfo( 'Author',      BizResources::localize( 'USR_AUTHOR' ),     null,'string' );
		self::$InfoProps['Author']->MaxLength = 255; // BZ#23267
		self::$MetaProps['Author']       = 'SourceMetaData->Author';
		self::$ObjFProps['Author']       = 'author';
		self::$SqlTProps['Author']       = 'string';
		self::$JoinProps['Author'] 		 = null;
		self::$JFldProps['Author'] 		 = null;

		// ContentMetaData
		self::$InfoProps['Description']  = new PropertyInfo( 'Description', BizResources::localize( 'OBJ_DESCRIPTION' ), null,'multiline' ); // BZ#23278
		self::$InfoProps['Description']->MaxLength = 2040; // BZ#23267
		self::$MetaProps['Description']  = 'ContentMetaData->Description';
		self::$ObjFProps['Description']  = 'description';
		self::$SqlTProps['Description']  = 'blob';
		self::$JoinProps['Description']  = null;
		self::$JFldProps['Description']  = null;

		self::$InfoProps['DescriptionAuthor'] = new PropertyInfo( 'DescriptionAuthor', BizResources::localize( 'OBJ_DESCRIPTION_AUTHOR' ), null,'string' );
		self::$InfoProps['DescriptionAuthor']->MaxLength = 255; // BZ#23267
		self::$MetaProps['DescriptionAuthor'] = 'ContentMetaData->DescriptionAuthor';
		self::$ObjFProps['DescriptionAuthor'] = 'descriptionauthor';
		self::$SqlTProps['DescriptionAuthor'] = 'string';
		self::$JoinProps['DescriptionAuthor']  = null;
		self::$JFldProps['DescriptionAuthor']  = null;

		self::$InfoProps['Keywords']     = new PropertyInfo( 'Keywords',    BizResources::localize( 'OBJ_KEYWORDS' ),   null,'multistring' );
		self::$MetaProps['Keywords']     = null; // This is an exception because keywords often has to be dealt with specially, let's revisit this for v6.5 or v7
		self::$ObjFProps['Keywords']     = 'keywords';
		self::$SqlTProps['Keywords']     = 'blob';
		self::$JoinProps['Keywords']  	 = null;
		self::$JFldProps['Keywords']  	 = null;

		self::$InfoProps['Slugline']     = new PropertyInfo( 'Slugline',    BizResources::localize( 'OBJ_SLUGLINE' ),   null,'string' );
		self::$InfoProps['Slugline']->MaxLength = 255; // BZ#23267
		self::$MetaProps['Slugline']     = 'ContentMetaData->Slugline';
		self::$ObjFProps['Slugline']     = 'slugline';
		self::$SqlTProps['Slugline']     = 'string';
		self::$JoinProps['Slugline']  	 = null;
		self::$JFldProps['Slugline']  	 = null;

		self::$InfoProps['Format']       = new PropertyInfo( 'Format',      BizResources::localize( 'OBJ_FORMAT' ),     null,'string' );
		self::$MetaProps['Format']       = 'ContentMetaData->Format';
		self::$ObjFProps['Format']       = 'format';
		self::$SqlTProps['Format']       = 'string';
		self::$JoinProps['Format']  	 = null;
		self::$JFldProps['Format']  	 = null;

		self::$InfoProps['Columns']      = new PropertyInfo( 'Columns',     BizResources::localize( 'OBJ_COLUMNS' ),    null,'int' );
		self::$MetaProps['Columns']      = 'ContentMetaData->Columns';
		self::$ObjFProps['Columns']      = '_columns';
		self::$SqlTProps['Columns']      = 'int';
		self::$JoinProps['Columns']  	 = null;
		self::$JFldProps['Columns']  	 = null;

		self::$InfoProps['Width']        = new PropertyInfo( 'Width',       BizResources::localize( 'OBJ_WIDTH' ),      null,'double' );
		self::$MetaProps['Width']        = 'ContentMetaData->Width';
		self::$ObjFProps['Width']        = 'width';
		self::$SqlTProps['Width']        = 'double';
		self::$JoinProps['Width']  	 	 = null;
		self::$JFldProps['Width']  	 	 = null;


		self::$InfoProps['Height']       = new PropertyInfo( 'Height',      BizResources::localize( 'OBJ_HEIGHT' ),     null,'double' );
		self::$MetaProps['Height']       = 'ContentMetaData->Height';
		self::$ObjFProps['Height']       = 'depth';
		self::$SqlTProps['Height']       = 'double';
		self::$JoinProps['Height']  	 = null;
		self::$JFldProps['Height']  	 = null;

		self::$InfoProps['Dpi']          = new PropertyInfo( 'Dpi',         BizResources::localize( 'OBJ_DPI' ),        null,'int' );
		self::$MetaProps['Dpi']          = 'ContentMetaData->Dpi';
		self::$ObjFProps['Dpi']          = 'dpi';
		self::$SqlTProps['Dpi']          = 'int';
		self::$JoinProps['Dpi']  	 	 = null;
		self::$JFldProps['Dpi']  	 	 = null;

		self::$InfoProps['LengthWords']  = new PropertyInfo( 'LengthWords', BizResources::localize( 'OBJ_LENGTHWORDS' ),null,'int' );
		self::$MetaProps['LengthWords']  = 'ContentMetaData->LengthWords';
		self::$ObjFProps['LengthWords']  = 'lengthwords';
		self::$SqlTProps['LengthWords']  = 'int';
		self::$JoinProps['LengthWords']  = null;
		self::$JFldProps['LengthWords']  = null;

		self::$InfoProps['LengthChars']  = new PropertyInfo( 'LengthChars', BizResources::localize( 'OBJ_LENGTHCHARS' ),null,'int' );
		self::$MetaProps['LengthChars']  = 'ContentMetaData->LengthChars';
		self::$ObjFProps['LengthChars']  = 'lengthchars';
		self::$SqlTProps['LengthChars']  = 'int';
		self::$JoinProps['LengthChars']  = null;
		self::$JFldProps['LengthChars']  = null;

		self::$InfoProps['LengthParas']  = new PropertyInfo( 'LengthParas', BizResources::localize( 'OBJ_LENGTHPARAS' ),null,'int' );
		self::$MetaProps['LengthParas']  = 'ContentMetaData->LengthParas';
		self::$ObjFProps['LengthParas']  = 'lengthparas';
		self::$SqlTProps['LengthParas']  = 'int';
		self::$JoinProps['LengthParas']  = null;
		self::$JFldProps['LengthParas']  = null;

		self::$InfoProps['LengthLines']  = new PropertyInfo( 'LengthLines', BizResources::localize( 'OBJ_LENGTHLINES' ),null,'int' );
		self::$MetaProps['LengthLines']  = 'ContentMetaData->LengthLines';
		self::$ObjFProps['LengthLines']  = 'lengthlines';
		self::$SqlTProps['LengthLines']  = 'int';
		self::$JoinProps['LengthLines']  = null;
		self::$JFldProps['LengthLines']  = null;

		self::$InfoProps['PlainContent'] = new PropertyInfo( 'PlainContent',BizResources::localize( 'OBJ_PLAINCONTENT' ), null,'string' );
		self::$MetaProps['PlainContent'] = 'ContentMetaData->PlainContent';
		self::$ObjFProps['PlainContent'] = 'plaincontent';
		self::$SqlTProps['PlainContent'] = 'blob';
		self::$JoinProps['PlainContent'] = null;
		self::$JFldProps['PlainContent'] = null;

		self::$InfoProps['FileSize']     = new PropertyInfo( 'FileSize',    BizResources::localize( 'OBJ_SIZE' ),       null,'int' );
		self::$MetaProps['FileSize']     = 'ContentMetaData->FileSize';
		self::$ObjFProps['FileSize']     = 'filesize';
		self::$SqlTProps['FileSize']     = 'int';
		self::$JoinProps['FileSize']  	 = null;
		self::$JFldProps['FileSize']  	 = null;

		self::$InfoProps['ColorSpace']   = new PropertyInfo( 'ColorSpace',  BizResources::localize( 'OBJ_COLORSPACE' ), null,'string' );
		self::$MetaProps['ColorSpace']   = 'ContentMetaData->ColorSpace';
		self::$ObjFProps['ColorSpace']   = 'colorspace';
		self::$SqlTProps['ColorSpace']   = 'string';
		self::$JoinProps['ColorSpace'] 	 = null;
		self::$JFldProps['ColorSpace']	 = null;

		self::$InfoProps['HighResFile']  = new PropertyInfo( 'HighResFile', BizResources::localize( 'OBJ_HIGHRESFILE' ),null,'string' );
		self::$MetaProps['HighResFile']  = 'ContentMetaData->HighResFile';
		self::$ObjFProps['HighResFile']  = 'highresfile';
		self::$SqlTProps['HighResFile']  = 'string';
		self::$JoinProps['HighResFile']  = null;
		self::$JFldProps['HighResFile']	 = null;

		self::$InfoProps['Encoding']     = new PropertyInfo( 'Encoding',    BizResources::localize( 'OBJ_ENCODING' ),   null,'string' );
		self::$MetaProps['Encoding']     = 'ContentMetaData->Encoding';
		self::$ObjFProps['Encoding']     = 'encoding';
		self::$SqlTProps['Encoding']     = 'string';
		self::$JoinProps['Encoding'] 	 = null;
		self::$JFldProps['Encoding']	 = null;

		self::$InfoProps['Compression']  = new PropertyInfo( 'Compression', BizResources::localize( 'OBJ_COMPRESSION' ),null,'string' );
		self::$MetaProps['Compression']  = 'ContentMetaData->Compression';
		self::$ObjFProps['Compression']  = 'compression';
		self::$SqlTProps['Compression']  = 'string';
		self::$JoinProps['Compression']  = null;
		self::$JFldProps['Compression']	 = null;

		self::$InfoProps['KeyFrameEveryFrames'] = new PropertyInfo( 'KeyFrameEveryFrames', BizResources::localize( 'OBJ_KEYFRAMEEVERYFRAMES' ), null,'int' );
		self::$MetaProps['KeyFrameEveryFrames'] = 'ContentMetaData->KeyFrameEveryFrames';
		self::$ObjFProps['KeyFrameEveryFrames'] = 'keyframeeveryframes';
		self::$SqlTProps['KeyFrameEveryFrames'] = 'string';
		self::$JoinProps['KeyFrameEveryFrames']  = null;
		self::$JFldProps['KeyFrameEveryFrames']	 = null;

		self::$InfoProps['Channels']     = new PropertyInfo( 'Channels',    BizResources::localize( 'OBJ_AVCHANNELS' ),       null,'string' );
		self::$MetaProps['Channels']     = 'ContentMetaData->Channels';
		self::$ObjFProps['Channels']     = 'channels';
		self::$SqlTProps['Channels']     = 'string';
		self::$JoinProps['Channels']  	 = null;
		self::$JFldProps['Channels']	 = null;

		self::$InfoProps['AspectRatio']  = new PropertyInfo( 'AspectRatio', BizResources::localize( 'OBJ_ASPECTRATIO' ),null,'string' );
		self::$MetaProps['AspectRatio']  = 'ContentMetaData->AspectRatio';
		self::$ObjFProps['AspectRatio']  = 'aspectratio';
		self::$SqlTProps['AspectRatio']  = 'string';
		self::$JoinProps['AspectRatio']  = null;
		self::$JFldProps['AspectRatio']	 = null;

		// Obsoleted property names: Snippet(=>>Slugline), Depth(=>>Height), WordCount(=>>LengthChars),
		//   CharCount(=>>LengthChars), LineCount(=>>LengthLines), Content(=>>PlainContent), Size(=>>FileSize)
		// WorkflowMetaData
		self::$InfoProps['Deadline']     = new PropertyInfo( 'Deadline',    BizResources::localize( 'ISS_DEADLINE' ),   null,'datetime' );
		self::$MetaProps['Deadline']     = 'WorkflowMetaData->Deadline';
		self::$ObjFProps['Deadline']     = 'deadline';
		self::$SqlTProps['Deadline']     = 'string';
		self::$JoinProps['Deadline']     = null;
		self::$JFldProps['Deadline']	 = null;

		self::$InfoProps['Urgency']      = new PropertyInfo( 'Urgency',     BizResources::localize( 'OBJ_URGENCY' ),    null,'string' );
		self::$InfoProps['Urgency']->MaxLength = 40;
		self::$MetaProps['Urgency']      = 'WorkflowMetaData->Urgency';
		self::$ObjFProps['Urgency']      = 'urgency';
		self::$SqlTProps['Urgency']      = 'string';
		self::$JoinProps['Urgency']      = null;
		self::$JFldProps['Urgency']	     = null;

		self::$InfoProps['Modifier']     = new PropertyInfo( 'Modifier',    BizResources::localize( 'ACT_MODIFIED_BY' ),null,'string' );
		self::$MetaProps['Modifier']     = 'WorkflowMetaData->Modifier';
		self::$ObjFProps['Modifier']     = 'modifier';
		self::$SqlTProps['Modifier']     = 'string';
		self::$JoinProps['Modifier']	 = 'mdf';
		self::$JFldProps['Modifier'] 	 = 'fullname';

		self::$InfoProps['Modified']     = new PropertyInfo( 'Modified',    BizResources::localize( 'ACT_MODIFIED_ON' ),null,'datetime' );
		self::$MetaProps['Modified']     = 'WorkflowMetaData->Modified';
		self::$ObjFProps['Modified']     = 'modified';
		self::$SqlTProps['Modified']     = 'string';
		self::$JoinProps['Modified']     = null;
		self::$JFldProps['Modified']	 = null;

		self::$InfoProps['Creator']      = new PropertyInfo( 'Creator',     BizResources::localize( 'ACT_CREATED_BY' ), null,'string' );
		self::$MetaProps['Creator']      = 'WorkflowMetaData->Creator';
		self::$ObjFProps['Creator']      = 'creator';
		self::$SqlTProps['Creator']      = 'string';
		self::$JoinProps['Creator'] 	 = 'crt';
		self::$JFldProps['Creator']		 = 'fullname';

		self::$InfoProps['Created']      = new PropertyInfo( 'Created',     BizResources::localize( 'ACT_CREATED_ON' ), null,'datetime' );
		self::$InfoProps['Created']->MaxLength = 30;
		self::$MetaProps['Created']      = 'WorkflowMetaData->Created';
		self::$ObjFProps['Created']      = 'created';
		self::$SqlTProps['Created']      = 'string';
		self::$JoinProps['Created']      = null;
		self::$JFldProps['Created']		 = null;

		self::$InfoProps['Comment']      = new PropertyInfo( 'Comment',     BizResources::localize( 'OBJ_COMMENT' ),    null,'multiline' );
		self::$InfoProps['Comment']->MaxLength = 255; // BZ#23267
		self::$MetaProps['Comment']      = 'WorkflowMetaData->Comment';
		self::$ObjFProps['Comment']      = 'comment';
		self::$SqlTProps['Comment']      = 'string';
		self::$JoinProps['Comment']      = null;
		self::$JFldProps['Comment']      = null;

		self::$InfoProps['StateId']      = new PropertyInfo( 'StateId',     BizResources::localize( 'WFL_STATUS_ID' ),  null,'string' );
		self::$MetaProps['StateId']      = 'WorkflowMetaData->State->Id';
		self::$ObjFProps['StateId']      = 'state';
		self::$SqlTProps['StateId']      = 'int';
		self::$JoinProps['StateId']      = null;
		self::$JFldProps['StateId']      = null;

		self::$InfoProps['State']        = new PropertyInfo( 'State',       BizResources::localize( 'STATE' ),          null,'list' );
		self::$MetaProps['State']        = 'WorkflowMetaData->State->Name';
		self::$ObjFProps['State']        = null;
		self::$SqlTProps['State']        = 'string';
		self::$JoinProps['State']        = 'sta';
		self::$JFldProps['State']        = 'state';

		self::$InfoProps['StateColor']        = new PropertyInfo( 'StateColor',       BizResources::localize( 'OBJ_COLOR' ),          null,'string' );
		self::$MetaProps['StateColor']        = 'WorkflowMetaData->State->Color';
		self::$ObjFProps['StateColor']        = null;
		self::$SqlTProps['StateColor']        = 'string';
		self::$JoinProps['StateColor']        = 'sta';
		self::$JFldProps['StateColor']        = 'color';

		self::$InfoProps['StatePhase']   = new PropertyInfo( 'StatePhase',       BizResources::localize( 'WORKFLOW_PHASE' ),          null,'string' );
		self::$MetaProps['StatePhase']   = 'WorkflowMetaData->State->Phase';
		self::$ObjFProps['StatePhase']   = null;
		self::$SqlTProps['StatePhase']   = 'string';
		self::$JoinProps['StatePhase']   = 'sta';
		self::$JFldProps['StatePhase']   = 'phase';

		self::$InfoProps['RouteTo']      = new PropertyInfo( 'RouteTo',     BizResources::localize( 'OBJ_ROUTE_TO' ),   null,'list' );
		self::$MetaProps['RouteTo']      = 'WorkflowMetaData->RouteTo';
		self::$ObjFProps['RouteTo']      = 'routeto';
		self::$SqlTProps['RouteTo']      = 'string';
		self::$JoinProps['RouteTo']      = null;
		self::$JFldProps['RouteTo']      = null;

		self::$InfoProps['LockedBy']     = new PropertyInfo( 'LockedBy',    BizResources::localize( 'ACT_IN_USE_BY' ),  null,'string' );
		self::$MetaProps['LockedBy']     = 'WorkflowMetaData->LockedBy';
		self::$ObjFProps['LockedBy']     = null;
		self::$SqlTProps['LockedBy']     = 'string';
		self::$JoinProps['LockedBy']	 = 'lcc';
		self::$JFldProps['LockedBy'] 	 = 'fullname';

		self::$InfoProps['Version']      = new PropertyInfo( 'Version',     BizResources::localize( 'OBJ_VERSION' ),    null,'string' );
		self::$MetaProps['Version']      = 'WorkflowMetaData->Version';
		self::$ObjFProps['Version']      = null;
		self::$SqlTProps['Version']      = 'string';
		self::$JoinProps['Version']      = null;
		self::$JFldProps['Version']      = null;

		self::$InfoProps['DeadlineSoft'] = new PropertyInfo( 'DeadlineSoft',BizResources::localize( 'DEADLINESOFT' ),   null,'datetime' );
		self::$MetaProps['DeadlineSoft'] = 'WorkflowMetaData->DeadlineSoft';
		self::$ObjFProps['DeadlineSoft'] = 'deadlinesoft';
		self::$SqlTProps['DeadlineSoft'] = 'string';
		self::$JoinProps['DeadlineSoft'] = null;
		self::$JFldProps['DeadlineSoft'] = null;

		self::$InfoProps['Rating']       = new PropertyInfo( 'Rating',BizResources::localize( 'OBJ_RATING' ),           null,'int' );
		self::$MetaProps['Rating']       = 'WorkflowMetaData->Rating';
		self::$ObjFProps['Rating']       = 'rating';
		self::$SqlTProps['Rating']       = 'int';
		self::$JoinProps['Rating']       = null;
		self::$JFldProps['Rating']       = null;

		self::$InfoProps['Deletor']    = new PropertyInfo( 'Deletor',   BizResources::localize( 'OBJ_DELETOR' ), null,'string' );
		self::$MetaProps['Deletor']    = 'WorkflowMetaData->Deletor';
		self::$ObjFProps['Deletor']    = 'deletor';
		self::$SqlTProps['Deletor']    = 'string';
		self::$JoinProps['Deletor']     = null;
		self::$JFldProps['Deletor']     = null;

		self::$InfoProps['Deleted']    = new PropertyInfo( 'Deleted',   BizResources::localize( 'OBJ_DELETED' ), null,'datetime' );
		self::$MetaProps['Deleted']    = 'WorkflowMetaData->Deleted';
		self::$ObjFProps['Deleted']    = 'deleted';
		self::$SqlTProps['Deleted']    = 'string';
		self::$JoinProps['Deleted']     = null;
		self::$JFldProps['Deleted']     = null;

		// Target related
		self::$InfoProps['PubChannelIds']= new PropertyInfo('PubChannelIds',BizResources::localize( 'PUB_CHANNEL_IDS' ),null,'multilist' );
		self::$MetaProps['PubChannelIds']= null;
		self::$ObjFProps['PubChannelIds']= null;
		self::$SqlTProps['PubChannelIds']= 'string';
		self::$JoinProps['PubChannelIds']= null;
		self::$JFldProps['PubChannelIds']= null;

		self::$InfoProps['PubChannels']  = new PropertyInfo( 'PubChannels', BizResources::localize( 'CHANNELS' ),       null,'multilist' );
		self::$MetaProps['PubChannels']  = null;
		self::$ObjFProps['PubChannels']  = null;
		self::$SqlTProps['PubChannels']  = 'string';
		self::$JoinProps['PubChannels']	 = null;
		self::$JFldProps['PubChannels']	 = null;

		self::$InfoProps['Targets']  = new PropertyInfo( 'Targets', BizResources::localize( 'OBJ_TARGETS' ),       null,'multilist' );
		self::$MetaProps['Targets']  = null;
		self::$ObjFProps['Targets']  = null;
		self::$SqlTProps['Targets']  = 'string';
		self::$JoinProps['Targets']	 = null;
		self::$JFldProps['Targets']	 = null;

		self::$InfoProps['IssueIds']     = new PropertyInfo( 'IssueIds',    BizResources::localize( 'ISS_ISSUE_IDS' ),  null,'multilist' );
		self::$MetaProps['IssueIds']     = null;
		self::$ObjFProps['IssueIds']     = null;
		self::$SqlTProps['IssueIds']     = 'string';
		self::$JoinProps['IssueIds']	 = null;
		self::$JFldProps['IssueIds']	 = null;

		self::$InfoProps['Issues']       = new PropertyInfo( 'Issues',      BizResources::localize( 'ISSUES' ),         null,'multilist' );
		self::$MetaProps['Issues']       = null;
		self::$ObjFProps['Issues']       = null;
		self::$SqlTProps['Issues']       = 'string';
		self::$JoinProps['Issues']		 = null;
		self::$JFldProps['Issues']		 = null;

		self::$InfoProps['EditionIds']   = new PropertyInfo( 'EditionIds',  BizResources::localize( 'SEC_EDITION_IDS' ),null,'multilist' );
		self::$MetaProps['EditionIds']   = null;
		self::$ObjFProps['EditionIds']   = null;
		self::$SqlTProps['EditionIds']   = 'string';
		self::$JoinProps['EditionIds']	 = null;
		self::$JFldProps['EditionIds']	 = null;

		self::$InfoProps['Editions']     = new PropertyInfo( 'Editions',    BizResources::localize( 'EDITIONS' ),       null,'multilist' );
		self::$MetaProps['Editions']     = null;
		self::$ObjFProps['Editions']     = null;
		self::$SqlTProps['Editions']     = 'string';
		self::$JoinProps['Editions']	 = null;
		self::$JFldProps['Editions']	 = null;

		// Page related
		self::$InfoProps['PlacedOn']     = new PropertyInfo( 'PlacedOn',    BizResources::localize( 'ACT_PLACED_ON' ),  null,'string' );
		self::$MetaProps['PlacedOn']     = null;
		self::$ObjFProps['PlacedOn']     = null;
		self::$SqlTProps['PlacedOn']     = 'string';
		self::$JoinProps['PlacedOn']	 = 'par';
		self::$JFldProps['PlacedOn']	 = 'name';

		self::$InfoProps['PlacedOnPage'] = new PropertyInfo( 'PlacedOnPage',BizResources::localize( 'ACT_PLACED_ON_PAGE' ), null,'string' );
		self::$MetaProps['PlacedOnPage'] = null;
		self::$ObjFProps['PlacedOnPage'] = 'pagenumber';
		self::$SqlTProps['PlacedOnPage'] = 'int';
		self::$JoinProps['PlacedOnPage']= null;
		self::$JFldProps['PlacedOnPage']= null;

		self::$InfoProps['PageRange']    = new PropertyInfo( 'PageRange',   BizResources::localize( 'OBJ_PAGE_RANGE' ),  null,'string' );
		self::$MetaProps['PageRange']    = null;
		self::$ObjFProps['PageRange']    = 'pagerange';
		self::$SqlTProps['PageRange']    = 'string';
		self::$JoinProps['PageRange']	 = null;
		self::$JFldProps['PageRange']	 = null;

		self::$InfoProps['PlannedPageRange'] = new PropertyInfo( 'PlannedPageRange',   BizResources::localize( 'OBJ_PLANNED_PAGE_RANGE' ), null,'string' );
		self::$MetaProps['PlannedPageRange'] = null;
		self::$ObjFProps['PlannedPageRange'] = 'plannedpagerange';
		self::$SqlTProps['PlannedPageRange'] = 'string';
		self::$JoinProps['PlannedPageRange'] = null;
		self::$JFldProps['PlannedPageRange'] = null;

		self::$InfoProps['UnreadMessageCount'] = new PropertyInfo( 'UnreadMessageCount',   BizResources::localize( 'OBJ_UNREAD_MESSAGE_COUNT' ), null, 'int' );
		self::$MetaProps['UnreadMessageCount'] = null;
		self::$ObjFProps['UnreadMessageCount'] = null;
		self::$SqlTProps['UnreadMessageCount'] = 'int';
		self::$JoinProps['UnreadMessageCount'] = null;
		self::$JFldProps['UnreadMessageCount'] = null;

		// Hidden for end-user
		self::$InfoProps['Flag']         = new PropertyInfo( 'Flag', 'Flag', null, 'int' );	// Changed the type from 'bool' to 'int': BZ#24885
		self::$MetaProps['Flag']         = null;
		self::$ObjFProps['Flag']         = null;
//		self::$SqlTProps['Flag']         = 'string'; // used string to implement bool 
		self::$SqlTProps['Flag']         = 'int'; // since no longer using bool, change this to int. BZ#42885
		self::$JoinProps['Flag']		 = 'ofl';
		self::$JFldProps['Flag']	 	 = 'flag';

		self::$InfoProps['FlagMsg']      = new PropertyInfo( 'FlagMsg', 'FlagMsg', null,'string' );
		self::$MetaProps['FlagMsg']      = null;
		self::$ObjFProps['FlagMsg']      = null;
		self::$SqlTProps['FlagMsg']      = 'string';
		self::$JoinProps['FlagMsg']		 = 'ofl';
		self::$JFldProps['FlagMsg']		 = 'message';

		self::$InfoProps['LockForOffline'] = new PropertyInfo( 'LockForOffline',   'LockForOffline', null,'bool' );
		self::$MetaProps['LockForOffline'] = null;
		self::$ObjFProps['LockForOffline'] = null;
		self::$SqlTProps['LockForOffline'] = 'string'; // used string to implement bool
		self::$JoinProps['LockForOffline'] = 'lcb';
		self::$JFldProps['LockForOffline'] = 'lockoffline';

		// Hidden for soap clients
		self::$InfoProps['StoreName']    = new PropertyInfo( 'StoreName', 'StoreName', null, 'string' );
		self::$MetaProps['StoreName']    = null;
		self::$ObjFProps['StoreName']    = 'storename';
		self::$SqlTProps['StoreName']    = 'blob';
		self::$JoinProps['StoreName']	 = null;
		self::$JFldProps['StoreName']	 = null;

		self::$InfoProps['RouteToUser']  = new PropertyInfo( 'RouteToUser',  'RouteToUser',   null, 'string' );
		self::$MetaProps['RouteToUser']      = null;
		self::$ObjFProps['RouteToUser']      = null;
		self::$SqlTProps['RouteToUser']      = 'string';
		self::$JoinProps['RouteToUser']		 = 'rtu';
		self::$JFldProps['RouteToUser']		 = 'fullname';

		self::$InfoProps['RouteToGroup']      = new PropertyInfo( 'RouteToGroup', 'RouteToGroup',   null, 'string' );
		self::$MetaProps['RouteToGroup']      = null;
		self::$ObjFProps['RouteToGroup']      = null;
		self::$SqlTProps['RouteToGroup']      = 'string';
		self::$JoinProps['RouteToGroup']      = 'rtg';
		self::$JFldProps['RouteToGroup']      =  'name';

		self::$InfoProps['MajorVersion'] = new PropertyInfo( 'MajorVersion', 'MajorVersion', null,'int' );
		self::$MetaProps['MajorVersion'] = null;
		self::$ObjFProps['MajorVersion'] = 'majorversion';
		self::$SqlTProps['MajorVersion'] = 'int';
		self::$JoinProps['MajorVersion'] = null;
		self::$JFldProps['MajorVersion'] = null;

		self::$InfoProps['MinorVersion'] = new PropertyInfo( 'MinorVersion', 'MinorVersion', null,'int' );
		self::$MetaProps['MinorVersion'] = null;
		self::$ObjFProps['MinorVersion'] = 'minorversion';
		self::$SqlTProps['MinorVersion'] = 'int';
		self::$JoinProps['MinorVersion'] = null;
		self::$JFldProps['MinorVersion'] = null;

		self::$InfoProps['Indexed']      = new PropertyInfo( 'Indexed', 'Indexed', null,'bool' );
		self::$MetaProps['Indexed']      = null;
		self::$ObjFProps['Indexed']      = 'indexed';
		self::$SqlTProps['Indexed']      = 'string'; // used string to implement bool
		self::$JoinProps['Indexed']		 = null;
		self::$JFldProps['Indexed']		 = null;

		self::$InfoProps['Closed']     	 = new PropertyInfo( 'Closed', 'Closed', null,'bool' );
		self::$MetaProps['Closed']     	 = null;
		self::$ObjFProps['Closed']     	 = 'closed';
		self::$SqlTProps['Closed']     	 = 'string'; // used string to implement bool
		self::$JoinProps['Closed']		 = null;
		self::$JFldProps['Closed']		 = null;

		self::$InfoProps['Types']        = new PropertyInfo( 'Types', 'Types', null,'list' );
		self::$MetaProps['Types']        = null;
		self::$ObjFProps['Types']        = 'types';
		self::$SqlTProps['Types']        = 'blob';
		self::$JoinProps['Types']		 = null;
		self::$JFldProps['Types']		 = null;

		self::$InfoProps['DeadlineChanged'] = new PropertyInfo( 'DeadlineChanged', 'DeadlineChanged', null,'bool' );
		self::$MetaProps['DeadlineChanged'] = null;
		self::$ObjFProps['DeadlineChanged'] = 'deadlinechanged';
		self::$SqlTProps['DeadlineChanged'] = 'string'; // used string to implement bool
		self::$JoinProps['DeadlineChanged'] = null;
		self::$JFldProps['DeadlineChanged'] = null;

		self::$InfoProps['ElementName'] = new PropertyInfo( 'ElementName',  'Label',   null, 'string' );
		self::$MetaProps['ElementName'] = null;
		self::$ObjFProps['ElementName'] = null;
		self::$SqlTProps['ElementName'] = 'string';
		self::$JoinProps['ElementName'] = null;
		self::$JFldProps['ElementName'] = null;

		self::$InfoProps['Dossier'] = new PropertyInfo( 'Dossier',  BizResources::localize( 'DOSSIER' ),   null, 'list' );
		self::$MetaProps['Dossier'] = null;
		self::$ObjFProps['Dossier'] = null;
		self::$SqlTProps['Dossier'] = null;
		self::$JoinProps['Dossier'] = null;
		self::$JFldProps['Dossier'] = null;

		self::$InfoProps['HasChildren'] = new PropertyInfo( 'HasChildren',  'HasChildren',   null, 'bool' );
		self::$MetaProps['HasChildren'] = null;
		self::$ObjFProps['HasChildren'] = null;
		self::$SqlTProps['HasChildren'] = null;
		self::$JoinProps['HasChildren'] = null;
		self::$JFldProps['HasChildren'] = null;

		// Debug: Just check these hugh lists if there are any keys missing (against each other)
		if( LogHandler::debugMode() ) {
			require_once BASEDIR.'/server/interfaces/services/BizException.class.php';
			$a = array_diff( array_keys(self::$InfoProps), array_keys(self::$MetaProps) );
			if( count( $a ) ) {
				throw new BizException( '', 'Server', '', __METHOD__.' - InfoProps and MetaProps are different: '.implode(',',$a) );
			}
			$a = array_diff( array_keys(self::$InfoProps), array_keys(self::$ObjFProps) );
			if( count( $a ) ) {
				throw new BizException( '', 'Server', '', __METHOD__.' - InfoProps and ObjFProps are different: '.implode(',',$a) );
			}
			$a = array_diff( array_keys(self::$InfoProps), array_keys(self::$SqlTProps) );
			if( count( $a ) ) {
				throw new BizException( '', 'Server', '', __METHOD__.' - InfoProps and ObjTProps are different: '.implode(',',$a) );
			}
			// Let's add all configurable dialog props and see if that list is complete
			$props = array('Issue','Section'); // differences that are ok
			$props = array_merge( $props, self::getIdentifierPropIds() );
			$props = array_merge( $props, self::getInternalPropIds() );
			$props = array_merge( $props, self::getStaticPropIds() );
			$props = array_merge( $props, self::getDynamicPropIds() );
			$props = array_merge( $props, self::getXmpPropIds() );
			$props = array_merge( $props, self::getSystemPropIds() );
			$a = array_diff( array_keys(self::$InfoProps), $props );
			if( count( $a ) ) {
				throw new BizException( '', 'Server', '', __METHOD__.' - InfoProps and sum of dialog props are different: '.implode(',',$a) );
			}
		}
	}

	/**
	 * Asks the server plug-ins with a CustomObjectMetaData connector for custom object properties.
	 * Those properties are validated and installed by expanding the DB model.
	 *
	 * @param $pluginName string|null Server Plug-in (name) to run the CustomObjectMetaData connector for. NULL to run all plugins with a CustomObjectMetaData connector.
	 * @param array|null $pluginErrs Empty array to collect errors. NULL to let function throw BizException on errors.
	 * @param bool $coreInstallation True to get the customProperties and install them now; False to let the import page collect and install the properties.
	 * @throws BizException When one of the provided custom properties is not valid.
	 */
	static public function validateAndInstallCustomProperties( $pluginName = null, &$pluginErrs, $coreInstallation )
	{
		$connRetVals = self::collectCustomPropertiesFromConnectors( $pluginName, $coreInstallation );
		
		// Validate the provided properties.
		self::validateCustomProperties( $connRetVals, $pluginErrs );
		
		// Install the provided properties by expanding the DB model.
		self::installCustomProperties( $connRetVals, $pluginErrs );
	}

	/**
	 * Validates custom object properties strictly on structure and against some biz rules.
	 * Expected structure: $connRetVals[connector name][brand id][object type] => array of PropertyInfo.
	 * When object id or brand id is zero, 'all' is assumed.
	 *
	 * @param array $connRetVals The values returned by all the CustomObjectMetaData interface connector(s).
	 * @param array|null $pluginErrs Empty array to collect errors. NULL to let function throw BizException on errors.
	 * @throws BizException When one of the provided custom properties is not valid.
	 */
	static private function validateCustomProperties( $connRetVals, &$pluginErrs )
	{
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$excludeFromModel = BizCustomField::getExcludedObjectFields();
		$objTypes = getObjectTypeMap();
		$propTypes = getPropertyTypeMap();

		foreach( $connRetVals as $connName => $brandProps ) {
			try {
				if( !is_array( $brandProps ) ) {
					throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
						'should return an array of PropertyInfo objects. Returned value is not of type array.' );
				}
				foreach( $brandProps as $brandId => $objTypeProps ) {
					if( !is_array( $objTypeProps ) ) {
						throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
							'should return an array of PropertyInfo objects. No array was found at index ['.$brandId.'].' );
					}
					if( $brandId !== 0 ) {
						if( gettype( $brandId ) != 'integer' ) {
							throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
								'did not return an integer (brand id) for the index of the array: ['. $brandId.'].' );
						}
						require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';
						if( !DBPublication::getPublicationName( $brandId ) ) {
							throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
								'did not return an existing brand id for the index of the array: ['. $brandId.'].' );
						}
					}
					foreach( $objTypeProps as $objType => $propInfos ) {
					
						// Validate object type.
						if( $objType !== 0 ) {
							if( gettype( $objType ) != 'string' || !array_key_exists( $objType, $objTypes ) ) {
								throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'did not return an array of PropertyInfo objects at index ['.$brandId.']['.$objType.'].' );
							}
						}
						// Validate the array of PropertyInfo.
						foreach( $propInfos as $propInfo ) {
							if( !is_object( $propInfo ) || get_class($propInfo) !== 'PropertyInfo' ) {
								throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'did not return an array of PropertyInfo objects at index ['.$brandId.']['.$objType.'].' );
							}
							// Custom object properties always must have C_ prefix.
							if( !self::isCustomPropertyName( $propInfo->Name ) ) {
								throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'should return an array of PropertyInfo objects. The property name should has "C_" prefix. ' .
									'For property "'.$propInfo->Name.'" this is not the case.');
							}
							// Custom property name can store up to 40 characters in smart_properties.
							// In Oracle Db, it allows table column name up to maximum 30 characters.
							// Therefore, when custom properties are added to the DB model (adding columns to smart_objects)
							// There is a limit of 30 chars, else 40 chars.
							$length = in_array( $propInfo->Type, $excludeFromModel ) ? 40 : 30;
							if( strlen( $propInfo->Name ) > $length ) {
								throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'should return property names containing no more than '.$length.' characters. '.
									'Property name "'.$propInfo->Name.'" is too long.' );
							}
							if( preg_match("/^C_[A-Z0-9_]*$/", $propInfo->Name ) == 0 ) {
								throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'should return property names containing A-Z (uppercase) characters or 0-9 characters only. '.
									'Underscores are allowed. For property "'.$propInfo->Name.'" this is not the case.' );
							}
							if( !array_key_exists( $propInfo->Type, $propTypes ) ) {
								throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'returned a property type that is not supported: "'.$propInfo->Type.'". ' .
									'This is the case for property "'.$propInfo->Name.'".' );
							}
	
							if (in_array($propInfo->Type, array('file', 'fileselector', 'articlecomponent', 'articlecomponentselector'))) {
								if ( (!isset($propInfo->AdminUI) ) || ( isset($propInfo->AdminUI) && $propInfo->AdminUI == true ) ){
									throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
										'returned a property type that may not be shown in the Admin UI: "'.$propInfo->Name.'".' );
								}
							}

							// The old way of hiding props was by using C_HIDDEN_ prefix, which we repair here before we store them in the DB.
							// Default the value to true if it is not hidden or the original value was not set.
							$propInfo->AdminUI = ( !self::isHiddenCustomProperty( $propInfo ) );
	
							//	if( isset( $allPropInfos[$propName] ) ) {
							//	TODO: Check for duplicates??? (already inserted in DB)
							// }
						}
					}
				}
			} catch( BizException $e ) {
				if( is_array($pluginErrs) ) { // the caller wants us to collect errors?
					$pluginKey = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
					$pluginErrs[$pluginKey] = $e->getMessage()."\n".$e->getDetail();
				} else { // the caller does collect errors, so we (re)throw
					throw $e;
				}
			}
		}
	}
	
	/**
	 * Stores custom object property definitions at DB and extends the DB model.
	 * 
	 * @param array $connRetVals Custom property definition, as returned by plugin connectors (key = connector name, value = array of PropertyInfo objects)
	 * @param array|null $pluginErrs Empty array to collect errors. NULL to let function throw BizException on errors.
	 * @throws BizException Throws BizException when there's problem with the installation.
	 */
	static private function installCustomProperties( $connRetVals, &$pluginErrs )
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		require_once BASEDIR.'/server/bizclasses/BizAutocompleteDispatcher.class.php';

		// Properties of type 'file' or 'articlecomponent' should not be inserted or updated in the model.
		$excludeFromModel = BizCustomField::getExcludedObjectFields();
		if ( $connRetVals ) foreach( $connRetVals as $connName => $brandProps ) {
			$plugin = BizServerPlugin::getPluginForConnector( $connName );
			try {
				$fieldsToAdd = array();
				$fieldsToUpdate = array();
				if ( $brandProps ) foreach( $brandProps as $brandId => $objTypeProps ) {
					if ( $objTypeProps ) foreach( $objTypeProps as $objType => $propInfos ) {
						if ( $propInfos) foreach( $propInfos as $propInfo ) {
							// Debugging: Uncomment the lines below to cleanup the property from DB.
							/*try { BizCustomField::deleteFieldAtModel( 'objects', $propInfo->Name ); } catch( BizException $e ) { $e = $e; }
							DBProperty::deletePropertyInfo( $brandId, $objType, $propInfo->Name );
							continue;*/

							if( $propInfo->TermEntity ) { // When property has Term Entity defined.
								$autocompleteProvider = $propInfo->AutocompleteProvider;
								if( $autocompleteProvider ) {
									// When the plugin has a property with Term Entity defined, it is expected that the
									// AutocompleteProvider plugin connector should be provided by the plugin.
									if( $autocompleteProvider != $plugin->UniqueName ) {
										throw new BizException( 'PLN_CLICKTOREPAIR', 'Client',
										'The '. $connName.'->collectCustomProperties function has a Term Entity property defined, '.
										'but the \'AutocompleteProvider\' specified for this property ("'.$propInfo->Name.
										'") is incorrect. The Autocomplete provider expected is "'.$plugin->UniqueName.'", but "'.
										$autocompleteProvider.'" is provided instead.' );
									}
								} else { // When not given, resolve it (expected the plugin to provide its own Autocomplete Provider.)
									$autocompleteProvider = $plugin->UniqueName;
									$propInfo->AutocompleteProvider = $autocompleteProvider; // To be stored in the DB later.
								}
								$foundConnector = BizAutocompleteDispatcher::findAutocompleteProviderConnector( $autocompleteProvider );
								if( !$foundConnector ) {
									throw new BizException( 'PLN_CLICKTOREPAIR', 'Client',
										'The ' . $connName.'->collectCustomProperties function has a Term Entity property ' .
										'defined ("'.$propInfo->Name.'"), but this property cannot be installed. Either '.
										'the AutocompleteProvider plugin connector is not found or the AutocompleteProvider ' .
										'"'.$autocompleteProvider.'" cannot handle the Term Entity "'.$propInfo->TermEntity.'" ' .
										'set on the property.' );
								}
								if( is_null( $propInfo->PublishSystemId )) {
									// When the property has Term Entity defined, the PublishSystemId has to be set.
									// When it is set, meaning there might be two same Term Entity being used by two different publishing system instance.
									// When it is empty, it implies the publishing system that using this Term Entity has only one instance.
									// When set to null, it implies this PublishSystemId is not applicable, which is not for this case as this property
									//   has Term Entity defined. Thus set to empty (assumed it has only one publishing system instance).
									$propInfo->PublishSystemId = '';
								}
							}
	
							$customPropertyInfos = DBProperty::getPropertyInfos( $plugin->UniqueName, $propInfo->Name );
							if( count( $customPropertyInfos ) != 0  ) { // should always contain one
								if( $customPropertyInfos[0]->Type != $propInfo->Type ) { // Not allow to change the prop 'Type'
									throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'attempted to change the existing property "'.$propInfo->Name.'" of type '.
									'"'.$customPropertyInfos[0]->Type.'" to type "'.$propInfo->Type.'". This is not allowed.' );
								}
	
								// Only update the model if the property type is not flagged to be excluded.
								if (!in_array($propInfo->Type, $excludeFromModel)) {
									if ( self::supportUpdateMultipleColumns() )
										$fieldsToUpdate['objects'][$propInfo->Name] = array(
																						'Name' => $propInfo->Name,
																						'Type' => $propInfo->Type );
									else {
										BizCustomField::updateFieldAtModel( 'objects', $propInfo->Name, $propInfo->Type );
									}
								}
	
								$propInfo = self::enrichPropertyInfoWithInternalProps( $propInfo, $brandId, $objType, $plugin );

								if ( $propInfo->DefaultValue ) {
									$propInfo->DefaultValue = self::convertDefault( $propInfo->Type, $propInfo->DefaultValue );
								}

								if( !DBProperty::updatePropertyInfo( $propInfo->Name, $propInfo, array( 'serverplugin' => $plugin->UniqueName ) ) ) {
									throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
									'returned a property "'.$propInfo->Name.'" that could not be updated in DB. '.
									'Please check the server logging.' );
								}
							} else { // create
								$custProps = DBProperty::getPropertyByNameAndFields( $propInfo->Name, 'all', null );
								if( $custProps ) { // Cust prop do exists in DB, but in different Publication, check if the prop type is the same.
									// $custProps[0]: Taking just first custProp is fine as it is assumed that
									// when the custom prop shares the same prop name, they share the same prop type too.
									if( $custProps[0]->Type != $propInfo->Type ) {
										// Here error when a new prop name (where name already exists ) is
										// going to have different prop type than the existing one.
											throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
											'is about to introduce a property "'.$propInfo->Name.'" where the name already exists, this '.
											'is allowed but it must have of type "'.$custProps[0]->Type.
											'" instead of type "'.$propInfo->Type.'".' );
									}
									
									// When the prop type of the new prop name (which already exists) are the same
									// the new prop name can be installed, but it is not needed to be installed into smart_objects
									// and smart_deletedobjects table as they already exists.
								} else { // cust prop never exists in DB before, insert into smart_objects and smart_deleted tables.
									// Expand the smart_objects and smart_delete tables with a column to store data for the custom property.
									// Only do this if the field is not flagged for exclusion from the model.
									if (!in_array($propInfo->Type, $excludeFromModel)) {
										if ( !isset( $fieldsToAdd['objects'])) {
											$fieldsToAdd['objects'] = array();
										}
										$fieldsToAdd['objects'][$propInfo->Name] = array(
																					'Name' => $propInfo->Name,
																					'Type' => $propInfo->Type );
									}
								}	
	
								// Now register this new custom prop into smart_property table
								// This is done regardless of if the custom prop name do exists already in DB (but in different publication)
								
								$propInfo = self::enrichPropertyInfoWithInternalProps( $propInfo, $brandId, $objType, $plugin );

								if ( $propInfo->DefaultValue ) {
									$propInfo->DefaultValue = self::convertDefault( $propInfo->Type, $propInfo->DefaultValue );
								}
								
								// Add a row of the custom property definition to the smart_properties table to remember the details.
								if( !DBProperty::insertPropertyInfo( $propInfo ) ) {
									throw new BizException( 'PLN_CLICKTOREPAIR', 'Client', $connName.'->collectCustomProperties function '.
										'returned a property "'.$propInfo->Name.'" that could not be inserted in DB. '.
										'Please check the server logging.' );
								}
							}
						}
					}
				}
				if ( $fieldsToAdd ) {
					BizCustomField::insertMultipleFieldsAtModel( $fieldsToAdd );
				}
				if ( $fieldsToUpdate ) {
					BizCustomField::updateMultipleFieldsAtModel( $fieldsToUpdate );
				}
			} catch( BizException $e ) {
				if( is_array($pluginErrs) ) { // the caller wants us to collect errors?
					$pluginKey = BizServerPlugin::getPluginUniqueNameForConnector( $connName );
					$pluginErrs[$pluginKey] = $e->getMessage()."\n".$e->getDetail();
				} else { // the caller does collect errors, so we (re)throw
					throw $e;
				}
			}
		}
	}

	/**
	 * This function is currently only used for BuildTest.
	 * In production, it's not possible to remove custom properties
	 * introduced by plugin yet.
	 * It removes all the custom properties introduced by the plugin $pluginName;
	 * when pluginName is not specified, it removes all custom properties that
	 * are introduced by the CustomObjectMetaData connector.
	 *
	 * @since 9.0.0
	 * @param $pluginName string|null Server Plug-in (name) to run the CustomObjectMetaData connector for.
	 *                                NULL to run all plugins with a CustomObjectMetaData connector.
	 * @return Boolean True when all custom properties have been successfully deleted. False otherwise.
	 */
	static public function removeCustomProperties( $pluginName = null )
	{
		$result = true;
		$connRetVals = self::collectCustomPropertiesFromConnectors( $pluginName, false );

		// Certain properties will not have a corresponding Object/DeletedObjects column in the database,
		// ensure these fields are not attempted to be removed.
		require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
		$excludeFromModel = BizCustomField::getExcludedObjectFields();

		$failedDeletedFields = array();
		foreach( $connRetVals as $connName => $brandProps ) {
			$plugin = BizServerPlugin::getPluginForConnector( $connName );

			foreach( $brandProps as $brandId => $objTypeProps ) {
				foreach( $objTypeProps as $objType => $propInfos ) {
					foreach( $propInfos as $propInfo ) {
						$continueDelete = true;
						try {
							if (!in_array($propInfo->Type, $excludeFromModel)) {
								BizCustomField::deleteFieldAtModel( 'objects', $propInfo->Name );
							}
						} catch( BizException $e ) {
							if( !isset( $failedDeletedFields[$plugin->UniqueName] )) {
								$failedDeletedFields[$plugin->UniqueName] = array();
							}
							$failedDeletedFields[$plugin->UniqueName][] = $propInfo->Name;
							$continueDelete = false;
						}

						if( $continueDelete ) { // only remove fields from smart_properties and smart_actionproperties table when the above is successful.
							$dbObjType = self::determinePropObjTypeForDB( $objType );
							DBProperty::deletePropertyInfo( $brandId, $dbObjType, $propInfo->Name );
						}
					}
				}
			}
		}

		if( $failedDeletedFields ) {
			$result = false;
			foreach( $failedDeletedFields as $pluginName => $custProps ) {
				LogHandler::Log( 'BizProperty', 'ERROR', __METHOD__ . ': Failed deleting the following custom properties: ' .
							implode( ',', $custProps ) . '" introduced by plugin "'.$pluginName.'"');
			}
		}
		return $result;
	}

	/**
	 * Collect the properties returned by the CustomObjectMetaData connector.
	 * When the $pluginName is given, it will only call the specific plugin
	 * otherwise it collects from -all- plugins that implements CustomObjectMetaData
	 * connector.
	 *
	 * @param $pluginName string|null Server Plug-in (name) to run the CustomObjectMetaData connector for. NULL to run all plugins with a CustomObjectMetaData connector.
	 * @param bool $coreInstallation True to get the customProperties and install them now; False to let the import page collect and install the properties.
	 * @return array Collected returned values from CustomObjectMetaData connector of given plugin or from -all- plugins when pluginName is Null.
	 */
	static public function collectCustomPropertiesFromConnectors( $pluginName = null, $coreInstallation )
	{
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$connRetVals = array();
		if( $pluginName ) {
			BizServerPlugin::runDefaultConnector( $pluginName, 'CustomObjectMetaData', null, 'collectCustomProperties',
										array( $coreInstallation ), $connRetVals );
		} else {
			// Ask the server plug-ins with a CustomObjectMetaData connector for custom object properties.
			BizServerPlugin::runDefaultConnectors( 'CustomObjectMetaData', null, 'collectCustomProperties',
										array( $coreInstallation ), $connRetVals );
		}

		return $connRetVals;
	}
	/**
	 * Enrich the property with some attribute. These attributes are not registered
	 * in WSDL, and therefore they are used internally only.
	 * 
	 * @param PropertyInfo $propInfo PropertyInfo to be enriched with some internal attributes.
	 * @param int $brandId Publication 
	 * @param string $objType The object type for the property.
	 * @param PluginInfoData $plugin
	 * @return PropertyInfo The enriched PropertyInfo with internal attributes.
	 */
	static private function enrichPropertyInfoWithInternalProps( $propInfo, $brandId, $objType, $plugin )
	{
		// Enrich the property with some attributes on-the-fly.
		$propInfo->PublicationId = $brandId;
		$propInfo->ObjectType = self::determinePropObjTypeForDB( $objType );
		$propInfo->Entity = 'Object';
		$propInfo->PlugIn = $plugin->UniqueName;
		$propInfo->DBUpdated = true;
		
		// Show the property in the admin pages, unless explicitly supressed by connector.
		// Note that the AdminUI is an internal attribute, which is therefore not defined in WSDL.
		if( !isset( $propInfo->AdminUI ) ) {
			$propInfo->AdminUI = true;
		}

		if( !isset( $propInfo->PublishSystem ) ) {
			$propInfo->PublishSystem = '';
		}

		if( !isset( $propInfo->TemplateId ) ) {
			$propInfo->TemplateId = 0;
		}

		return $propInfo;							
	}

	/**
	 * To mapped the object type passed in by the caller
	 * to the correct representation of object type in database.
	 *
	 * @param string $objType Can be 0 for all object type.
	 * @return string The object type that is saved in the database.
	 */
	static private function determinePropObjTypeForDB( $objType )
	{
		return empty( $objType ) ? '' : $objType;
	}

	/**
	  * Transforms an object row into an object property bag.
	  * The object row has lower case keys and has values from smart_object table.
	  * The returned object properties have mixed case keys and their values are taken from given the rows.
	  * In other terms, it 'converts' the keys from lower case to mixed case and leaves the values as-is.
	  *
	  * @param array $objRow Object row
	  * @return array        Object properties
	  */
	static public function objRowToPropValues( $objRow )
	{
		$fields = self::getMetaDataObjFields(); // mixed-low
		// Add Version field which is a virtual DB fields, so has no DB column, but IS typically returned
		// in select as concat of major.minor. So we need it here in our translation table.
		$fields['Version'] ='version';

		$fields = array_diff( $fields, array(null) ); // remove non-db props
		$fields = array_flip($fields); // low-mixed
		ksort($objRow); // low-values
		$inter = array_intersect_key( $fields,$objRow ); // low-mixed (values only)
		$objRowKnown = array_intersect_key( $objRow, $fields ); // filter unknown entries, like custom props
		ksort($inter);
		$translated = array_combine( array_values($inter), array_values($objRowKnown) ); // mixed-values
		// Add unknown, typically custom props:
		return array_merge( $translated, array_diff_key( $objRow, $objRowKnown ) );
	}

	/**
	  * Array version of objRowToPropValues
	  *
	  * @param 	array of array 	$objRowArray 	Aray of Object row arrays
	  * @return array        	Array of Object properties array
	  */
	static public function objRowArrayToPropValues( $objRowArray )
	{
		$retArray = array();
		foreach( $objRowArray as $objRow ) {
			$retArray[] =self::objRowToPropValues( $objRow );
		}
		return $retArray;
	}

	/**
	  * Transforms an object prop array into an object row.
	  * The object properties have mixed case keys
	  * The returned object row has lower case keys (db columns) and has values from properties
	  * In other terms, it 'converts' the keys from mixed case to lower case and leaves the values as-is.
	  *
	  * @param array $objProps Object props array
	  * @return array        Object row
	  */
	static public function objPropToRowValues( $objProps )
	{
		$fields = self::getMetaDataObjFields(); // mixed-low
		// Add Version field which is a virtual DB fields, so has no DB column, but IS typically returned
		// in select as concat of major.minor. So we need it here in our translation table.
		$fields['Version'] ='version';

		$fields = array_diff( $fields, array(null) ); // remove non-db props
		ksort($objProps); // mix-values
		$interFields = array_intersect_key( $fields,$objProps ); // low-mixed (values only)
		ksort($interFields);
		$interProps = array_intersect_key( $objProps, $fields );  // Filters props that we don't know in our fields list

		return array_combine( array_values($interFields), array_values($interProps) ); // mixed-values
	}

	/**
	  * Updates a flattened metadata list tree with data from the metadata tree,
	  * Both tree- and flat structures are defined in the workflow WSDL, resp. MetaData and ExtraMetaData.
	  * Normally, only custom properties are sent through ExtraMetaData, however, the WebEditWorkflow
	  * uses this structure for ALL properties, including the standard shipped ones. That makes
	  * this function needed to update one with the other structure. See also {@link updateMetaDataTreeWithFlat}.
	  * Only present props are used to update the tree structure.
	  *
	  * @param $flatMD array of ExtraMetaData to be updated
	  * @param $treeMD MetaData input data
	  */
	public static function updateMetaDataFlatWithTree( &$flatMD, MetaData $treeMD )
	{
		// Ignore the following built-in properties because they SHOULD be filled afterwards, see updateMetaDataFlatWithSpecialProperties
		$ignoreProperties = self::getIgnorePropsForFlatTreeConv();

		// For performance reasons it's better to first put all in variables:
		$basMD = &$treeMD->BasicMetaData;
		$cntMD = &$treeMD->ContentMetaData;
		$wflMD = &$treeMD->WorkflowMetaData;
		$extMD = &$treeMD->ExtraMetaData;

		$propPaths = self::getMetaDataPaths();
		foreach( $flatMD as &$fmd ) {
			$value = '';
			unset($value);

			$display = '';
			unset( $display );

			$entity = '';
			unset( $entity );

			switch( $fmd->Property ) {

				// Logically, the following props are configured for the dialog, but we sent ids instead
				// to make sure the right value is selected at lists (in case of duplicates)
				case 'Publication' :
					$value = isset( $basMD->Publication->Id ) ? $basMD->Publication->Id : '';
					$display = isset( $basMD->Publication->Name ) ? $basMD->Publication->Name : '';
					break;
				case 'Section' :
				case 'Category' :
					$value = isset( $basMD->Category->Id ) ? $basMD->Category->Id : '';
					$display = isset( $basMD->Category->Name ) ? $basMD->Category->Name : '';
					break; // section=category
				case 'State' :
					$value = isset( $wflMD->State->Id ) ? $wflMD->State->Id : '';
					$display = isset( $wflMD->State->Name ) ? $wflMD->State->Name : '';
					break;

				// Handle multi-value
				case 'Keywords':
					if(isset($cntMD->Keywords)) {
						$fmd->PropertyValues = array();
						foreach( $cntMD->Keywords as $val ){
							$fmd->PropertyValues[] = new PropertyValue( $val );
						}
					}
				break;

				default:
					require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
					if( DBProperty::isCustomPropertyName( $fmd->Property ) ) { // custom prop?
						// Let's be flexible (BZ#6704) here and we accept:
						// - ExtraMetaData (single object and array)
						// - ExtraMetaData->ExtraMetaData (single object and array)
						// which is done because those kind of differences happen when
						// calling from PHP internally or from outside (through PEAR SOAP).
						$arrExtMD = array();
						if( isset($extMD->ExtraMetaData) ) {
							if( is_object($extMD->ExtraMetaData) ) {
								$arrExtMD[] = $extMD->ExtraMetaData;
							} else if( is_array($extMD->ExtraMetaData) ) {
								$arrExtMD = $extMD->ExtraMetaData;
							} // else something bad, so let's skip
						} else {
							if( is_object($extMD) ) {
								$arrExtMD[] = $extMD;
							} else if( is_array($extMD) ) {
								$arrExtMD = $extMD;
							} // else something bad, so let's skip
						}
						foreach( $arrExtMD as $emd ) {
							if( $fmd->Property == $emd->Property ) {
								if( $emd->Values ) {
									$fmd->PropertyValues = array();
									foreach( $emd->Values as $val ){
										$fmd->PropertyValues[] = new PropertyValue( $val ); // BZ#28696
									}
								}
								break;
							}
						}
					} else { // built-in props
						if( ! isset($propPaths[$fmd->Property]) || is_null( $propPaths[$fmd->Property] ) ) {
							if (! isset($ignoreProperties[$fmd->Property])){
								LogHandler::Log( 'BizProperty', 'ERROR', 'updateMetaDataFlatWithTree: Ignoring supported property: '.$fmd->Property );
							}
						} else {
							$path = $propPaths[$fmd->Property];
							eval( 'if( isset( $treeMD->'.$path.' ) ) {
										$value = $treeMD->'.$path.';
									}');

							// RouteTo property needs special treatement.
							if( $fmd->Property == 'RouteTo' && isset( $value )) {
								$routeTo = $value;
								$routeToPropValue = self::getRouteToPropertyValue( $routeTo );
								$value = $routeToPropValue->Value;
								$display = $routeToPropValue->Display;
								$entity = $routeToPropValue->Entity;
							}

						}
					}
				break;
			}
			if( isset($value) ) { // handle normal (single value) props
				$propValue = new PropertyValue();
				$propValue->Value = $value;
				$propValue->Display = isset( $display ) ? $display : null;
				$propValue->Entity = isset( $entity ) ? $entity : null;
				$fmd->PropertyValues = array( $propValue );
			}
		}
	}

	/**
	 * Compose PropertyValue based on the passed in $routeTo.
	 *
	 * The function checks if the routeTo is a user or a userGroup and resolve the value and display name accordingly.
	 *
	 * @param string $routeTo The route to name in full (full username).
	 * @return PropertyValue
	 */
	public static function getRouteToPropertyValue( $routeTo )
	{
		require_once BASEDIR .'/server/dbclasses/DBUser.class.php';
		$userRow = DBUser::getUser( $routeTo );
		$userShort = '';
		$entity = null;
		if ( $userRow ) {
			$userShort = $userRow['user'];
			$entity = 'User';
		} else {
			// When the routeTo value isn't a user it can be a usergroup
			$userGroup = DBUser::getUserGroup( $routeTo );
			if ( $userGroup ) {
				// For a usergroup the value and display are the same
				$userShort = $routeTo;
				$entity = 'UserGroup';
			}
		}

		$propValue = new PropertyValue();
		$propValue->Value = $userShort;
		$propValue->Display = $routeTo;
		$propValue->Entity = $entity;

		return $propValue;
	}

	/**
	  * Updates a metadata tree with data from flattened metadata list,
	  * Both tree- and flat structures are defined in the workflow WSDL, resp. MetaData and ExtraMetaData.
	  * Normally, only custom properties are sent through ExtraMetaData, however, the WebEditWorkflow
	  * uses this structure for ALL properties, including the standard shipped ones. That makes
	  * this function needed to update one with the other structure. See also {@link updateMetaDataFlatWithTree}.
	  * Only present props are used to update the tree structure.
	  *
	  * @param $treeMD MetaData to be updated
	  * @param $flatMD array of ExtraMetaData input data
	  */
	public static function updateMetaDataTreeWithFlat( MetaData &$treeMD, $flatMD )
	{
		// Ignore the following built-in properties because they SHOULD be filled afterwards, see updateMetaDataFlatWithSpecialProperties
		$ignoreProperties = self::getIgnorePropsForFlatTreeConv();

		// For performance reasons it's better to first put all in variables:
		$basMD = &$treeMD->BasicMetaData;
		$cntMD = &$treeMD->ContentMetaData;
		$wflMD = &$treeMD->WorkflowMetaData;
		$extMD = &$treeMD->ExtraMetaData;

		$propPaths = self::getMetaDataPaths();
		foreach( $flatMD->MetaDataValue as $fmd ) {

			// Let's be flexible (BZ#6448) here and we accept:
			// - MetaDataValue->Values (single object and array)
			// - MetaDataValue->Values->String (single object and array)
			// which is done because those kind of differences happen when
			// calling from PHP internally or from outside (through SOAP).
			if( isset($fmd->Values->String) ) {
				if( is_array($fmd->Values->String) ) {
					$fmd->Values = $fmd->Values->String;
				} else {
					$fmd->Values = array($fmd->Values->String);
				}
			} elseif( isset($fmd->Values) ) {
				if( !is_array($fmd->Values) ) {
					$fmd->Values = array($fmd->Values);
				}
			}

			$v = count($fmd->Values) > 0 ? $fmd->Values[0] : '';
			switch( $fmd->Property ) {

				case 'Publication': $basMD->Publication = new Publication( $v );
					break;
				case 'Category' : $basMD->Category = new Category( $v );
					break;
				case 'Section' : $basMD->Category = new Category( $v );
					break; // section=category
				case 'State' : $wflMD->State = new State( $v );
					break;

				case 'Issues':
				case 'Editions':
				case 'PubChannels':
				case 'Targets':
					break; // ignore; those are catch by Targets structure elsewhere
				// Handle multi-value
				case 'Keywords':
					$cntMD->Keywords = $fmd->Values;
					break;

				default:
					// BZ#6704: Custom props at the tree ($emd) have NO "c_" prefix
					// while the flattened ($fmd) DOES use the prefix.
					// This is because the structure of the tree tells you that
					// all ExtraMetaData values are custom, but for the flattened
					// (list), custom and built-in props are on same level so
					// custom props are prefixed with "c_" to tell the difference.
					// To compare them, the prefix of the tree must be ignored/skipped.
					require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
					if( DBProperty::isCustomPropertyName( $fmd->Property ) ) { // custom prop?
						$emdFound = false;
						if( $extMD )
							foreach( $extMD as $emd ) {
							if( $emd->Property == $fmd->Property ) {
								$emd->Values = $fmd->Values;
								$emdFound = true;
								break;
							}
						}
						if( !$emdFound ) {
							$extMD[] = new MetaDataValue( $fmd->Property, $fmd->Values );
						}
					} else { // built-in props
						if( ! isset($propPaths[$fmd->Property]) || is_null( $propPaths[$fmd->Property] ) ) {
							if (! isset($ignoreProperties[$fmd->Property])){
								LogHandler::Log( 'BizProperty', 'ERROR', 'updateMetaDataTreeWithFlat: Ignoring supported property: '.$fmd->Property );
							}
						} else {
							// build MetaData tree on-the-fly (only intermediate* nodes are created)
							$propPath = $propPaths[$fmd->Property];
							$pathParts = explode( '->', $propPath );
							array_pop( $pathParts ); // remove leafs, see*
							$path = '';
							foreach( $pathParts as $pathPart ) {
								$path .= $pathPart;
								/** @noinspection PhpUnusedLocalVariableInspection */
								$class = ($pathPart == 'Section') ? 'Category' : $pathPart;
								eval( 'if( !isset( $treeMD->'.$path.' ) ) {
											$treeMD->'.$path.' = new $class();
										}');
								$path .= '->';
							}
							$propType = gettype($v);
							// >>> BZ#12174: Escape slashes and double quotes, or else eval() fails
							//               on check-in when RouteTo has slashes or double quotes (using Web Editor).
							$v = str_replace( '\\', '\\\\', $v );
							$v = str_replace( '"', '\"', $v );
							// <<<
							eval('$treeMD->'.$propPath.' = ('.$propType.')"'.$v.'";');
						}
					}
				break;
			}
		}
	}

	/**
	 * Returns property names of built-in properties that should be initially ignored during tree/flat metadata
	 * structure conversions. The idea is that those should be filled afterwards. See updateMetaDataFlatWithSpecialProperties.
	 */
	static public function getIgnorePropsForFlatTreeConv()
	{
		$ignoreProperties = array (
			'Issue' => true, 'IssueId' => true, // Issue and IssueId are no longer relevant, it is all taken care by Targets,it is safe to ignore
			'PageRange' => true, 'PlannedPageRange' => true, 'PlacedOnPage' => true, 'PlacedOn' => true, // Treated at updateMetaDataFlatWithSpecialProperties
			'RelatedTargets' => true, // RelatedTargets is runtime added by BizWorkflow class, which is safe to skip here
			'Dossier' => true, // Treated at BizObjects::createObjects
			'UnreadMessageCount' => true,
		);
		foreach( self::getTargetRelatedPropIds() as $tarProp ) {
			$ignoreProperties[$tarProp] = true;
		}
		return $ignoreProperties;
	}

	/**
	 * List of properties (or usages) that are present in both given list of ids and objects. <br/>
	 *
	 * @param array of string $ids  The property/usage ids as returned from any get...PropIds function.
	 * @param array of object $objs The property/usage objects as returned from any get...Properties function.
	 * @return array of object  Intersection between $ids and $objs.
	 */
	public static function intersectProperties( $ids, $objs )
	{
		return array_intersect_key( $objs, array_flip( $ids ) );
		/*$ret = array();
		foreach( $ids as $id ) {
			if( array_key_exists( $id, $objs ) ) {
				$ret[$id] = $objs[$id];
			}
		}
		return $ret;*/
	}

	/**
	 * List of properties for a specific level of customization. <br/>
	 * When no customizations are found at that level, it searches for more generic levels. <br/>
	 * In case no customizations are made, the default set of properties for workflow dialogs is returned. <br/>
	 *
	 * @param string $publ    Publication ID.
	 * @param string $objType Object type.
	 * @param string $publishSystem
	 * @param integer $templateId
	 * @param bool $filtered Whether or not to exclude certain fields defined in the function or to return a full set.
	 * @param bool $customOnly Whether or not to exclude any properties that do not have a database field.
	 * @param bool $onlyAllObjType Only return "All" object type property.
	 * @return array of PropertyInfo  PropertyInfo definitions as used in workflow WSDL.
	 */
	public static function getProperties( $publ, $objType, $publishSystem = null, $templateId = null, $filtered = true,
		$customOnly = false, $onlyAllObjType=false )
	{
		// Get custom properties.
		$onlyAllObjType = ($objType == '') ? true : false; // When objType = "All", set onlyAllObjType is true
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		$custProps = DBProperty::getProperties( $publ, $objType, false, $publishSystem, $templateId, $filtered, $onlyAllObjType );
		$stdProps = array();

		if ( !$customOnly ) {
			$stdProps = self::getPropertyInfos(); // Get standard props.
		}

		return array_merge( $stdProps, $custProps ); // Add custom to standard ones.
	}

	/**
	 * Returns an array containing all the Metadata object field keys that are valid for the Object type.
	 *
	 * Takes the custom properties for the publication / object type and joins in the metadata fields for the object
	 * that have corresponding database columns. For the result the keys are returned.
	 *
	 * @since 9.2.0
	 * @param int $publ The PublicationId for which to fetch properties.
	 * @param string $objType The Object type for which to fetch properties.
	 * @return array An array of keys for the object property columns.
	 */
	public static function getPropertiesForObjectType( $publ, $objType )
	{
		// Get the custom properties that are allowed.
		$customProperties = self::getProperties( $publ, $objType, null, null, true, true );
		$customProperties = array_keys( $customProperties );

		// Get standard Object properties and filter out any non database fields.
		$standardProperties = self::getMetaDataObjFields();
		$standardProperties = array_diff( $standardProperties, array( null ) );
		$standardProperties = array_keys( $standardProperties );

		return array_merge( $standardProperties, $customProperties );
	}

	/**
	 *	List of properties for the specified object. This will resolve the customized
	 *	properties. Note: just the keys are returned, the actual property values are
	 * 	not returned.
	 *
	 * @param string	$objectID
	 * @param string 	$publishSystem
	 * @param integer 	$templateId
	 * @return array of PropertyInfo  PropertyInfo definitions as used in workflow WSDL.
	 */
	public static function getPropertiesForObject( $objectID, $publishSystem = null, $templateId = null )
	{
		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
		$objProps = DBObject::getObjectProps( $objectID );
		return self::getProperties( $objProps['PublicationId'], $objProps['Type'], $publishSystem, $templateId );
	}

	/**
	 * Method to find out if a property name is a custom property
	 * All custom properties started with c_ (lowercase c)
	 * In v5.0 custom properties can start with C_ (uppercase C) as well.
	 *
	 * @param string $propertyname Name of the property to check
	 * @return boolean: true if the $propertyname starts with c_ or C_, else false
	 */
	public static function isCustomPropertyName($propertyname)
	{
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		return DBProperty::isCustomPropertyName($propertyname);
	}

	/**
	 * Returns the PropertyType of a custom Property.
	 *
	 * Finds the Property by the supplied name, and returns the PropertyType for that Property.
	 * If the Property cannot be found an empty String is returned for the type.
	 *
	 * @param string $propertyName The name of the Property for which to get the PropertyType.
	 * @param int $publicationId The publication level the custom property is defined for. 0 (Default) for all publication.
	 * @param string $objectType The object type level the custom property is defined for. Empty (Default) for all object type.
	 * @return string The PropertyType or an empty string if the PropertyType could not be determined.
	 */
	public static function getCustomPropertyType( $propertyName, $publicationId = 0, $objectType = '' )
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		return DBProperty::getCustomPropertyType( $propertyName, $publicationId, $objectType );
	}

	/**
	 * Get's the type of a standard property by property name.
	 * By the way: this is not the fieldtype in the db, but a 'propertytype' as defined by woodwing.
	 * @param string propertyname Name of the standard property (not a displayname!)
	 * @return string: type of the property
	**/
	public static function getStandardPropertyType($propName)
	{
		static $propsLow = null;
		if( is_null($propsLow) ) {
			$props = self::getPropertyInfos();
			$propsLow = array_change_key_case( $props, CASE_LOWER );
		}
		$propLowName = strtolower($propName);
		if( isset($propsLow[$propLowName])) {
			return $propsLow[$propLowName]->Type;
		}
		/* BZ#9552: Commented out; Slow thumbnail overview through GetObjects. The method below
		   took 426ms while the method above takes 82ms, so 5x faster. The GetObjects as a whole
		   took 2.1s which roughly goes down to 1.7s using the improved method.
		foreach ($props as $name => $prop) {
			if (strtolower($name) == strtolower($propName)) {
				return $prop->Type;
			}
		}*/
		return null;
	}

	/**
	 * Get's the maximum allowed length of a standard property by property name.
	 *
	 * @param string propName Name of the standard property (not a display name!)
	 * @return string: maximum length of the property or null if not set.
	 **/
	public static function getStandardPropertyMaxLength($propName)
	{
		static $propsLow = null;
		if( is_null($propsLow) ) {
			$props = self::getPropertyInfos();
			$propsLow = array_change_key_case( $props, CASE_LOWER );
		}
		$propLowName = strtolower($propName);
		if( isset($propsLow[$propLowName])) {
			return $propsLow[$propLowName]->MaxLength;
		}

		return null;
	}

	/**
	 * Get a list of properties of type bool(ean)
	 *
	 */
	public static function getBoolTypeProperty()
	{
		static $boolTypes = array();

		if (!empty($boolTypes)) {
			return $boolTypes;
		}

		$InfoProps = self::getPropertyInfos();

		foreach ($InfoProps as $InfoProp) {
			if ($InfoProp->Type == 'bool') {
				$boolTypes[] = $InfoProp->Name;
			}
		}

		return $boolTypes;
	}

	/**
	 * Search through a list of Properties and returns the properties and its info that are Term Entity defined.
	 *
	 * This function returns a list of Term Entity defined property with the following structure:
	 * $termEntityProps[0] = array(
	 *                         array['name'] = 'xxx'
	 *                         array['termentity_terms'] = list of autocomplete terms in an array.
	 *                         array['prop_info'] = PropertyInfo object
	 *                    )
	 *
	 * @param array $flatMetaData List of MetaData that contains Property and its values.
	 * @param string $templateId Unique id of the publishing system. Use to bind the publishing storage.
	 * @return array List of Autocomplete Term Entity properties with its details(see function header).
	 */
	public static function getAutocompleteTermEntityProperty( $flatMetaData, $templateId )
	{
		$termEntityProps = array();
		if( $flatMetaData ) foreach( $flatMetaData as $flatMD ) {
			$propInfos = self::getFullPropertyInfos( null, $flatMD->Property, null, null, $templateId, true );
			if( $propInfos ) {
				$termEntityProperty = array();
				$termEntityProperty['name'] = $flatMD->Property;
				$termEntityProperty['termentity_terms'] = $flatMD->Values;
				$termEntityProperty['prop_info'] = $propInfos[0]; // Should be always 1.
				$termEntityProps[] = $termEntityProperty;
			}
		}
		return $termEntityProps;
	}

	/**
	 * Update the flat metadata structure with the properties PlacedOn, PlacedOnPage,
	 * PageRange and PlannedPageRange and UnreadMessageCount.
	 *
	 * @param int $objId
	 * @param array $flatMD array of MetaDataValue objects
	 */
	public static function updateMetaDataFlatWithSpecialProperties ($objId, &$flatMD)
	{
		$placedOnProps = null;
		$pageRangeProps = null;

		require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

		foreach ($flatMD as $mdv) {
			switch ($mdv->Property) {
				case 'PlacedOn':
				case 'PlacedOnPage':
					if (is_null($placedOnProps)) {
						$rows = DBObject::getPlacedOnRows($objId);
						$placedOn = array();
						$placedOnPage = array();
						foreach ($rows as $row) {
							$placedOn[] = $row['name'];
							$placedOnPage[] = $row['pagerange'];
						}
						$placedOnProps = array('PlacedOn' => implode(', ', $placedOn) ,
							'PlacedOnPage' => implode(', ', $placedOnPage));
					}
					$mdv->PropertyValues = array( new PropertyValue( $placedOnProps[$mdv->Property] ) );
					break;
				case 'PageRange':
				case 'PlannedPageRange':
					if (is_null($pageRangeProps)) {
						$pageRangeProps = array('PageRange' => '' , 'PlannedPageRange' => '');
						$row = DBObject::getPageRangeRow($objId);
						if (! is_null($row)) {
							$pageRangeProps['PageRange'] = $row['pagerange'];
							$pageRangeProps['PlannedPageRange'] = $row['plannedpagerange'];
						}
					}
					$mdv->PropertyValues = array( new PropertyValue( $pageRangeProps[$mdv->Property] ) );
					break;
				case 'UnreadMessageCount':
					require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
					$numberUnread = DBMessage::getUnreadMessageCountForObject($objId);
					if ( !is_null($numberUnread)) {
						$mdv->PropertyValues = array( new PropertyValue( $numberUnread ) );
					}
			}
		}
	}

	/**
	 * Returns values for properties PlacedOn, PlacedOnPage, PageRange and PlannedPageRange (read from DB)
	 * and UnreadMessageCount.
	 * Only requested properties are resolved, for performance reasons.
	 *
	 * @param integer[] $objIds Object Ids
	 * @param array $fieldsToIndex When property is not found in $fieldsToIndex, the property value is not resolved.
	 * @return array key-values of resolved properties with the object id as key.
	 */
	public static function updateIndexFieldWithSpecialProperties( $objIds, $fieldsToIndex )
	{
		$specialData = array_fill_keys( $objIds, array() );
		$doPageRange = in_array( 'PageRange', $fieldsToIndex ) ? true : false;
		$doPlannedPageRange = in_array( 'PlannedPageRange', $fieldsToIndex ) ? true : false;
		$doPlacedOn = in_array( 'PlacedOn', $fieldsToIndex ) ? true : false;
		$doPlacedOnPage = in_array( 'PlacedOnPage', $fieldsToIndex ) ? true : false;
		$unReadMessages = in_array( 'UnreadMessageCount', $fieldsToIndex ) ? true : false;

		if ( $doPageRange || $doPlannedPageRange ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$rows = DBObject::getPageRangeRowByObjIds( $objIds );
			if ( $rows ) foreach ( $rows as $objId => $row ) {
				if ( $doPageRange ) {
					$specialData[$objId]['PageRange'] = $row['pagerange'];
				}
				if ( $doPlannedPageRange ) {
					$specialData[$objId]['PlannedPageRange'] = $row['plannedpagerange'];
				}
			}
		}
		if ( $doPlacedOn || $doPlacedOnPage ) {
			require_once BASEDIR.'/server/dbclasses/DBObject.class.php';
			$rowsAll = DBObject::getPlacedOnRowsByObjIds( $objIds );
			if ( $objIds ) foreach ( $objIds as $objId ) {
				if ( array_key_exists( $objId, $rowsAll ) ) {
					$placedOn = array();
					$placedOnPage = array();
					$rows = $rowsAll[$objId];
					if ( $rows ) foreach ( $rows as $row ) {
						$placedOn[] = $row['name'];
						// BZ#31032: Pagerange 1 => 001; Pagerange 2-5 => 002-005; 4,45-46,48 => 004,045-046,048
						$placedOnPageStr = '';
						$placedOnPageRanges = preg_split( '/([,-])/', $row['pagerange'], -1, PREG_SPLIT_DELIM_CAPTURE );
						if ($placedOnPageRanges ) foreach ( $placedOnPageRanges as $pageElem ) {
							if ( is_numeric( $pageElem ) ) {
								$paddedPageElem = str_pad( $pageElem, 3, "0", STR_PAD_LEFT );
								$placedOnPageStr .= $paddedPageElem;
							} else {
								$placedOnPageStr .= $pageElem; // add ',' or '-'
							}
						}
						$placedOnPage[] = $placedOnPageStr;
					}
					if ( $doPlacedOn ) {
						$specialData[$objId]['PlacedOn'] = implode( ', ', $placedOn );
					}
					if ( $doPlacedOnPage ) {
						$specialData[$objId]['PlacedOnPage'] = implode( ', ', $placedOnPage );
					}
				}
			}
		}
		if ( $unReadMessages ) {
			require_once BASEDIR.'/server/dbclasses/DBMessage.class.php';
			$unreadMessageCountAll = DBMessage::getUnreadMessageCountForObjects( $objIds );
			if ( $objIds ) foreach ( $objIds as $objId ) {
				if ( array_key_exists( $objId, $unreadMessageCountAll ) ) {
					$unreadMessageCount = intval( $unreadMessageCountAll[$objId]['total'] );
				} else {
					$unreadMessageCount = 0;
				}
				$specialData[$objId]['UnreadMessageCount'] = $unreadMessageCount;
			}
		}

		return $specialData;
	}
	
	/**
	 * metaDataToBizPropArray
	 *
	 * Converts MetaData object to array with BizProp keys (like'Publication', 'PublicationId') and their values.
	 * Custom meta data values use their (uppercase C_ name like 'C_MYSTRING )
	 *
	 * @param MetaData $meta
	 * @return array
	 */
	public static function metaDataToBizPropArray( MetaData $meta )
	{
		// Get all property paths used in MetaData and object fields used in DB
		$propPaths = self::getMetaDataPaths();
		$propPaths['Keywords'] = 'ContentMetaData->Keywords'; // Add keywords, it's not in the table, but for our purpose should
		// Walk through all DB object fields and create flat array
		$arr = array();
		foreach( $propPaths as $propName => $objField ) {
			$propPath = $propPaths[$propName];
			if( !is_null($objField) && !is_null($propPath) ) {
				eval( 'if( isset( $meta->'.$propPath.' ) ) $arr["'.$propName.'"] = $meta->'.$propPath.'; else $arr["'.$propName.'"] = null;');
			}
		}

		// handle extra metadata
		$extraMetaData = $meta->ExtraMetaData;
		if( $extraMetaData ) {
			foreach( $extraMetaData as $em ) {
				$propType = self::getCustomPropertyType($em->Property);
				if( $propType == 'multilist' || $propType == 'multistring') {
					$arr[$em->Property] = $em->Values;
				} else if( !empty($em->Values) ) {
					$arr[$em->Property] = $em->Values[0];
				}
			}
		}

		return $arr;
	}

	/**
	 * This function returns the database type of a property.
	 *
	 * This can be a standard property or a custom property.
	 * An empty string value is returned in case the property type cannot be determined.
	 *
	 * @param string $propertyName Name of the property
	 * @return string Database type of the property or empty if it could not be determined.
	 */
	public static function getDBTypeProperty( $propertyName )
	{
		$propertyDBType = '';
		if( BizProperty::isCustomPropertyName( $propertyName ) ) {
			$propertyDBType = self::getCustomPropertyType( $propertyName );
			if( $propertyDBType ) {
				$propertyDBType = self::convertCustomPropertyTypeToDB( $propertyDBType );
			}
		} else {
			$propertyDBTypes = self::getMetaDataSqlFieldTypes();
			if( isset($propertyDBTypes[$propertyName]) ) {
				$propertyDBType = $propertyDBTypes[$propertyName];
			}
		}
		return $propertyDBType;
	}

	/**
	 * This function converts type (File Type) of a custom property to
	 * a database type.
	 *
	 * @param string $theType (File) type of the custom property.
	 * @param string $theTable optional database table name for the custom property
	 * @return string the data(base) type of the custom property.
	 */
    public static function convertCustomPropertyTypeToDB($theType, $theTable = '')
    {
    	if( !$theType || $theType == "" )
    		return "";

    	/* If the tablename equals to objects, deletedobjects or is empty use an integer for the boolean.
    	 * Otherwise use a (var)char for the field.
    	 */

    	switch (DBTYPE) {
    		case 'oracle':
    			$dbTypeMap = array(
    				"string"			=> "varchar(255) default ''",
    				"multistring"		=> "clob",
    				"multiline"			=> "clob",
    				"bool"				=> ($theTable == 'objects' || $theTable == 'deletedobjects' || $theTable == '') ? "integer default 0" : "varchar(2) default ''",
    				"int"				=> "integer default 0",
    				"double"			=> "float default 0.0",
    				"date"				=> "varchar(20) default ''",
    				"datetime"			=> "varchar(20) default ''",
    				"list"				=> "varchar(255) default ''",
    				"multilist"			=> "clob",
    				"password"			=> "varchar(40) default ''",
    				"language"			=> "varchar(4) default ''",
					"color"				=> "varchar(11) default ''",
					"fileselector"		=> "varchar(255) default ''",
					"file"		        => "varchar(255) default ''",
					"articlecomponentselector" => "varchar(255) default ''",
					"articlecomponent"	=> "varchar(255) default ''",
    			);
    			break;
    		case 'mssql':
    			$dbTypeMap = array(
    				"string"			=> "varchar(255) default ''",
    				"multistring"		=> "text",
    				"multiline"			=> "text",
    				"bool"				=> ($theTable == 'objects' || $theTable == 'deletedobjects' || $theTable == '') ? "integer default 0" : "char(2) default ''",
    				"int"				=> "integer default 0",
    				"double"			=> "real default 0.0",
    				"date"				=> "varchar(20) default ''",
    				"datetime"			=> "varchar(20) default ''",
    				"list"				=> "varchar(255) default ''",
    				"multilist"			=> "text",
    				"password"			=> "varchar(40) default ''",
    				"language"			=> "varchar(4) default ''",
					"color"				=> "varchar(11) default ''",
					"fileselector"		=> "varchar(255) default ''",
					"file"		        => "varchar(255) default ''",
					"articlecomponentselector" => "varchar(255) default ''",
					"articlecomponent"	=> "varchar(255) default ''",
    			);
    			break;
    		default: // MySQL:
    			$dbTypeMap = array(
					"string"			=> "tinytext",
    				"multistring"		=> "mediumblob", // all the 'blob' changed to 'mediumblob' BZ#21480
    				"multiline"			=> "mediumblob",
    				"bool"				=> ($theTable == 'objects' || $theTable == 'deletedobjects' || $theTable == '') ? "integer default 0" : "char(2) default ''",
    				"int"				=> "integer default 0",
    				"double"			=> "double default 0.0",
    				"date"				=> "varchar(20) default ''",
    				"datetime"			=> "varchar(20) default ''",
					"list"				=> "tinytext",
    				"multilist"			=> "mediumblob",
    				"password"			=> "varchar(40) default ''",
    				"language"			=> "varchar(4) default ''",
					"color"				=> "varchar(11) default ''",
					"fileselector"		=> "tinytext",
					"file"		        => "tinytext",
					"articlecomponentselector" => "tinytext",
					"articlecomponent" => "tinytext",
    			);
    			break;
    	}

    	return $dbTypeMap[$theType];
    }
    
	/**
	 * Finds out if a property is a hidden custom property.
	 *
	 * In the database, all properties with the 'adminui' field set to '' are flagged 
	 * as hidden properties. The ones set to 'on' should be shown in the admin pages.
	 * Once read from DB, the PropertyInfo->AdminUI is respectively set to false or true. 
	 * In case of a new property, the AdminUI might not be set, which means that the 
	 * property needs to be shown (default). The AdminUI attribute is not defined in
	 * the WSDL and therefor not an initial member of the PropertyInfo class. This
	 * attribute is added on-the-fly by the core server and is used internally only.
	 *
	 * @param PropertyInfo $propInfo Property to check.
	 * @return boolean: true if property is hidden, else false.
	 */
	public static function isHiddenCustomProperty( PropertyInfo $propInfo )
	{
		return isset( $propInfo->AdminUI) && false === $propInfo->AdminUI;
	}

	/**
	 * Adds a propery. Before updating the data is validated. 
	 * Note: The methods expects all attributes of the property to be set.
	 * @param array $values attribute/value pairs
	 * @return int Id of created record.
	 * @throws BizException
	 */
	public static function addProperty( $values )
	{
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		self::validateName( $values['name'], $values['publication'] );
		self::validateDefault( $values['type'], $values['valuelist'], $values['defaultvalue'] );
		$values['defaultvalue'] = self::convertDefault( $values['type'], $values['defaultvalue'] );

		// TODO: This fix is needed currently. This function is called from the admin pages,
		// but should run via a service. insertRow should only be called from the database layer.
		$blob = null;
		if ( isset($values['valuelist']) ) {
			$blob = $values['valuelist'];
			$values['valuelist'] = '#BLOB#';
		}

		$result = DBProperty::insertRow( 'properties', $values, true, $blob );
		if( $result === false ) {
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
		return $result; // New id
	}

	/**
	 * Updates a propery. Before updating the data is validated. 
	 * Note: The methods expects all attributes of the property to be set.
	 * @param int Id of the property.
	 * @param array $values attribute/value pairs.
	 * @throws BizException
	 */
	public static function updateProperty( $id, $values )
	{
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		self::validateDefault( $values['type'], $values['valuelist'], $values['defaultvalue'] );
		$values['defaultvalue'] = self::convertDefault( $values['type'], $values['defaultvalue'] );
		$where = '`id` = ?';
		$params = array($id);
		// TODO: This fix is needed currently. This function is called from the admin pages,
		// but should run via a service. insertRow should only be called from the database layer.
		$blob = null;
		if ( isset($values['valuelist']) ) {
			$blob = $values['valuelist'];
			$values['valuelist'] = '#BLOB#';
		}
		$result = DBProperty::updateRow( 'properties', $values, $where, $params, $blob );
		if( $result === false ) {
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
	}

	/**
	 * Deletes a property.
	 * @param int $id Id of the property.
	 * @throws BizException
	 */
	public static function deleteProperty( $id )
	{
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		// update metadata
		$result = DBProperty::deleteRows( 'properties', '`id` = ?', array($id) );
		if( is_null( $result ) ) {
			$dbDriver = DBDriverFactory::gen();
			throw new BizException( 'ERR_DATABASE', 'Server', $dbDriver->error() );
		}
	}

	/**
	 * Validates the name of the custom property. Rules:
	 * - Not empty.
	 * - Allowed are only alphanumeric characters plus the underscore.
	 * - Should not start with a number.
	 * - Must be unique per publication if no custom property, else must be unique system wide.
	 *
	 * @param string $name
	 * @param $publication
	 * @throws BizException Throws BizException when the validation fails.
	 */
	private static function validateName( $name, $publication )
	{
		if( strlen( $name ) == 0 ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client', '' );
		}

		if( self::isCustomPropertyName( $name ) ) {
			$messName = substr( $name, 2 );
		} else {
			$messName = $name;
		}

		$nounderscorename = str_replace( '_', '', $name ); // Remove underscore(s) 
		if( ctype_alnum( $nounderscorename ) == false || is_numeric( $name[0] ) ) {
			throw new BizException( 'ERR_NAME_INVALID', 'Client', 'Name: ' . $messName );
		} 
		
		require_once BASEDIR . '/server/dbclasses/DBProperty.class.php';
		$where = '`publication` = ? AND UPPER(`name`) = ? ';
		$params = array( $publication, $name );
		
		$row = DBProperty::getRow( 'properties', $where, array('id'), $params );
		if( $row ) {
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client', '', null, array('{OBJ_PROPERTY}', $messName) );
		}
	}

	/**
	 * Checks if the default value is an element of the (multi)list.
	 *
	 * @param string $type Type of the property.
	 * @param array $list List containing allowed values.
	 * @param string $default Default value.
	 * @throws BizException Throws BizException when the validation fails.
	 */
	private static function validateDefault( $type, $list, $default )
	{
		if( $default && $list && ( $type == 'list' || $type == 'multilist' ) ) {
			$listpieces = explode( ',', $list );
			if( !in_array( $default, $listpieces ) ) {
				throw new BizException( 'ERR_DEFAULT_NOT_LIST', 'Client', '' );
			}
		}
	}

	/**
	 * Validates the default value if the type is date or datetime.
	 * @param string $type Type of the property.
	 * @param string $default Default value.
	 * @return string (converted) default value.
	 * @throws BizException
	 */
	public static function convertDefault( $type, $default )
	{
		$result = $default;
		if( ($type == 'datetime' || $type == 'date') && $default ) {
			return self::convertDateTime( $type, $default );
		} elseif( $type == 'bool' ) {
			return self::convertBoolean( $default );
		}

		return $result;
	}

	/**
	 * Validates the default value if the type is date or datetime.
	 * @param string $type Type of the property.
	 * @param string $default Default value.
	 * @return string (converted) default value.
	 * @throws BizException
	 */
	private static function convertDateTime( $type, $default )
	{
		require_once BASEDIR . '/server/utils/DateTimeFunctions.class.php';
		$includeTime = ($type == 'datetime');
		$temp = DateTimeFunctions::validDate( $default, $includeTime );
		if( $temp ) {
			$result = $temp;
		} else {
			throw new BizException( 'INVALID_DATE', 'Client', '' );
		}

		return $result;
	}

	/**
	 * Make sure that true/false value is stored as '1' and '0' in the database.
	 * Allowed values for true are: 'true', '1', 'on' and 'y'. Anything else is stored as false.
	 * @param string $default Default value.
	 * @return string (converted) default value.
	 */
	private static function convertBoolean( $default )
	{
		$trimVal = trim( strtolower( $default ) );
		$result = ( $trimVal == 'true' || $trimVal == '1' || $trimVal == 'on' || $trimVal == 'y' ) ? '1' : '0';
		return $result;
	}

	/**
	 * Checks if the database type of the property is not changed to another type.
	 *
	 * This function checks if there is already another property with the same name. When the name is the same
	 * and the database type is the same these properties can be added to the system. This is useful when
	 * defining the same property for different brands for example.
	 *
	 * @param int $id Id of the property.
	 * @param string $type Type of the property.
	 * @param string $name Name of the property.
	 * @throws BizException When the database type isn't the same as the one that is asked for.
	 * @return string|null Returns a string with the type if it already exists, if not null is returned.
	 */
	public static function getCustomPropType( $id, $type, $name )
	{
		$storedDbType = null;
		if( self::isCustomPropertyName( $name ) ) {
			$dbType = BizProperty::convertCustomPropertyTypeToDB( $type );

			$params = array( $name );
			$where = '`name` = ? ';
			if( $id ) {
				// We want to get the other 'properties' that's why is shouldn't be equal to the property id.
				$params[] = $id;
				$where .= 'AND `id` != ? ';
			}
			$row = DBProperty::getRow( 'properties', $where, array('type'), $params );

			if( $row ) {
				$storedDbType = BizProperty::convertCustomPropertyTypeToDB( trim( $row['type'] ) );
				if( $dbType != $storedDbType ) {
					throw new BizException( 'ERR_PROP_WRONG_TYPE', 'Client', '' );
				}
			}
		}

		return $storedDbType;
	}

	/**
	 * Enriched the flattened metadata structure with the properties from $objectProps.
	 *
	 * @param array $flatMD List of MetaDataValue objects to be enriched.
	 * @param array $objectProps List of object properties and its corresponding values, used to fill in $flatMD.
	 * @param array $mixedValueProps List of key(properties)-value(true) properties of which the objects have different values.
	 */
	public static function enrichFlatMDWithObjectProps( &$flatMD, $objectProps, $mixedValueProps )
	{
		require_once BASEDIR. '/server/interfaces/services/wfl/DataClasses.php';
		$mandatoryProps = array( 'IDs', 'Publication', 'Type' );
		if( $flatMD ) foreach( $flatMD as $propName => &$metaDataValue ) {
			if( !in_array( $propName, $mandatoryProps ) &&
				!isset( $mixedValueProps[$propName]) ) { // Property where the objects use the same value.
				// Populate the values.
				$propId = $propName;
				switch( $propName ) {
					case 'Category':
						$propId = 'CategoryId';
						break;
					case 'State':
						$propId = 'State';
						break;
				}
				$propValue = new PropertyValue();
				$propValue->Value = $objectProps[$propId];
				$metaDataValue->PropertyValues = array( $propValue );
			}
		}
	}

	/**
	 * Checks if the DBMS support multiple changes of column definitions in one alter statement. At this moment
	 * only mysql supports this.
	 *
	 * @return bool
	 */
	private static function supportUpdateMultipleColumns()
	{
		$dbdriver = DBDriverFactory::gen();

		return $dbdriver->supportUpdateMultipleColumns();
	}

	/**
	 * Retrieve the unused publish form properties by Publish System name.
	 *
	 * @param string $publishSystem The name of the Publish System.
	 * @return array $result Array of unused publish form properties.
	 */
	public static function getUnusedPublishFormProperties( $publishSystem )
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		$result = DBProperty::getUnusedPublishFormProperties( $publishSystem );
		return $result;
	}

	/**
	 * Delete custom properties by Id.
	 *
	 * @param array $ids Array of property Ids.
	 */
	public static function deleteProperties( $ids )
	{
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		DBProperty::deleteProperties( $ids );
	}
}
