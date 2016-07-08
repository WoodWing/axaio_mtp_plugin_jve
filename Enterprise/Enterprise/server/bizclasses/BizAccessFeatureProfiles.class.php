<?php

/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v9.0.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * IMPORTANT: Access/Workflow features have reserved range: [1...99].
 *    This range is used by authorizationmodule::getRightsCached to filter out those rights efficiently.
 *    Only for THESE rights, the server can/will check access.
 *    Clients are free whether or not to check them too, which is for usability only.
 *    If not respected by clients, server will raise Access Denied error.
 *    Other rights, the server is considered NOT able to check.
 *    These rights are checked client-side only (and so always accepted by server).
 *
 * MAINTENANCE: You are NOT allowed to change the right ids nor the names!!!
 *    This is because those ids are stored in databases and names are interpreted by clients.
 *    Changing ids would results into mismatches for existing databases or confuse clients.
 *
 * @since 9.0.0 This module is introduced in replacement of the old BizAccessData.php module.
 * This is done to make it more dynamic and so the getSubApps() function could be added.
 * Another reason is to make code execution faster (no more need for serialize/unserialize calls)
 * and to reduce the change of name conflicts (no more global defines).
 * Comparing old and new solution:
 *                  BizAccessData.php (old)      BizAccessFeatureProfiles.class.php (new)
 *  ------------------------------------------------------------------------------------
 *  storage     :   define of serialized array   function returning an array
 *  execution   :   include time / slow          run time / fast
 *  memory      :   always on include            only on demand (function call)
 *  extend      :   no / hard                    yes / easy
 *  localization:   to do by caller              built-in / by function
 */

// TODO: Generate this data class from the SystemAdmin.wsdl:
class SysFeatureProfile
{
	public $Id;
	public $Name;
	public $Display;
	public $Flag;
	public $Default;
	
	/**
	 * @param integer              $Id                   
	 * @param string               $Name                 
	 * @param string               $Flag                 
	 * @param string               $Display                 
	 * @param string               $Default                 
	 */
	public function __construct( $Id=null, $Name=null, $Flag=null, $Display=null, $Default=null )
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Flag                 = $Flag;
		$this->Display              = $Display;
		$this->Default              = $Default;
	}
}

class BizAccessFeatureProfiles
{
	const FILE_VIEW                    =  1;
	const FILE_LISTPUBOVERVIEW         = 11;
	const FILE_READ                    =  2;
	const FILE_OPENEDIT                =  9;
	const FILE_OPENEDIT_UNPLACED       = 14;
	const FILE_WRITE                   =  3;
	const FILE_DELETE                  =  4;
	const FILE_PURGE                   = 10;
	const FILE_CHANGESTATUSFWD         =  5;
	const FILE_CHANGESTATUS            =  6;
	const FILE_RESTOREVERSION          =  7;
	const FILE_KEEPLOCKED              =  8;
	const FILE_DOWNLOADPREVIEW         = 12;
	const FILE_DOWNLOADORIGINAL        = 13;
	
	/**
	 * Returns the access rights as listed under the File Access section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('ACCESSFEATURES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getFileAccessProfiles()
	{
		return array(
			self::FILE_VIEW                  => new SysFeatureProfile( self::FILE_VIEW,
				'View',                      'V', BizResources::localize('ACT_VIEW') ),
			self::FILE_LISTPUBOVERVIEW       => new SysFeatureProfile( self::FILE_LISTPUBOVERVIEW, 
				'List_PubOverview',          'L', BizResources::localize('ACT_LIST_PUB_OVERVIEW') ),
			self::FILE_READ                  => new SysFeatureProfile( self::FILE_READ,
				'Read',                      'R', BizResources::localize('ACT_READ') ),
			self::FILE_OPENEDIT              => new SysFeatureProfile( self::FILE_OPENEDIT,
				'Open_Edit',                 'E', BizResources::localize('ACT_OPEN_EDIT') ),
			self::FILE_OPENEDIT_UNPLACED     => new SysFeatureProfile( self::FILE_OPENEDIT_UNPLACED,
				'Open_Edit_Unplaced',        '', BizResources::localize('ACT_OPEN_EDIT_UNPLACED') ),
			self::FILE_WRITE                 => new SysFeatureProfile( self::FILE_WRITE,
				'Write',                     'W', BizResources::localize('ACT_WRITE') ),
			self::FILE_DELETE                => new SysFeatureProfile( self::FILE_DELETE,
				'Delete',                    'D', BizResources::localize('ACT_DELETE') ),
			self::FILE_PURGE                 => new SysFeatureProfile( self::FILE_PURGE,
				'Purge',                     'U', BizResources::localize('ACT_PURGE'), 'No' ),
			self::FILE_CHANGESTATUSFWD       => new SysFeatureProfile( self::FILE_CHANGESTATUSFWD,
				'Change_Status_Forward',     'F', BizResources::localize('ACT_CHANGE_STATUS_FORWARD') ),
			self::FILE_CHANGESTATUS          => new SysFeatureProfile( self::FILE_CHANGESTATUS,
				'Change_Status',             'C', BizResources::localize('ACT_CHANGE_STATUS') ),
			self::FILE_RESTOREVERSION        => new SysFeatureProfile( self::FILE_RESTOREVERSION,
				'Restore_Version',           'S', BizResources::localize('ACT_RESTORE_VERSION') ),
			self::FILE_KEEPLOCKED            => new SysFeatureProfile( self::FILE_KEEPLOCKED,
				'Keep_Locked',               'K', BizResources::localize('ACT_KEEP_LOCKED') ),
			self::FILE_DOWNLOADPREVIEW       => new SysFeatureProfile( self::FILE_DOWNLOADPREVIEW,
				'Download_Preview',          '', BizResources::localize('ACT_DOWNLOAD_PREVIEW') ),
			self::FILE_DOWNLOADORIGINAL      => new SysFeatureProfile( self::FILE_DOWNLOADORIGINAL,
				'Download_Original',         '', BizResources::localize('ACT_DOWNLOAD_ORIGNAL') ),
		);
	}
	
	const ANNOTATION_EDIT              = 70;
	const ANNOTATION_VIEW              = 71;
	const ANNOTATION_DELETE            = 72;

	/**
	 * Returns the access rights as listed under the Annotations section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_ANNOTATION', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getAnnotationsAccessProfiles()
	{
		// For backward compatibility for v7Client talking to v8Server, 'name' is remained 
		// as EditStickyNotes as client uses 'name' for access right notation.
		return array(
			self::ANNOTATION_EDIT            => new SysFeatureProfile( self::ANNOTATION_EDIT,
				'EditStickyNotes',           '', BizResources::localize('CREATE_REPLY_NOTES') ),
			self::ANNOTATION_VIEW            => new SysFeatureProfile( self::ANNOTATION_VIEW,
				'ViewNotes',                 '', BizResources::localize('VIEW_NOTES') ),
			self::ANNOTATION_DELETE          => new SysFeatureProfile( self::ANNOTATION_DELETE,
				'DeleteNotes',               '', BizResources::localize('DELETE_NOTES') ),
		);
	}

	const WORKFLOW_ADDICIMAGES         = 84;
	const WORKFLOW_PUBLISH             = 85;
	const WORKFLOW_CREATETASK          = 86;
	const WORKFLOW_MUTLIPLACEMENT      = 87;
	const WORKFLOW_CHANGEEDITION       = 88;
	//const WORKFLOW_EDITSTICKY        = 89;
	const WORKFLOW_CREATEDOSSIER       = 90;
	const WORKFLOW_CHECKINARTFROMLAYER = 91;
	const WORKFLOW_CHECKINARTFROMDOC   = 92;
	const WORKFLOW_ABORTCHECKOUT       = 93;
	//const WORKFLOW_RENAMECHECKIN     = 94;
	//const WORKFLOW_SAVEAS            = 95;
	//const WORKFLOW_CREATEARTICLE     = 96;
	//const WORKFLOW_CREATEIMAGE       = 97;
	const WORKFLOW_RESTRICTED          = 98;
	const WORKFLOW_CHANGEPIS           = 99;
	                                 // L> MAX VALUE: 99 !!!

	/**
	 * Returns the access rights as listed under the Workflow section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_WORKFLOW', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getWorkflowAccessProfiles()
	{
		return array(
			self::WORKFLOW_ADDICIMAGES       => new SysFeatureProfile( self::WORKFLOW_ADDICIMAGES,
				'AddInCopyImages',           '', BizResources::localize('ACT_ADDINCOPYIMAGES'), 'No' ),
			self::WORKFLOW_PUBLISH           => new SysFeatureProfile( self::WORKFLOW_PUBLISH,
				'Publish',                   '', BizResources::localize('ACT_PUBLISH') ),
			self::WORKFLOW_CREATETASK        => new SysFeatureProfile( self::WORKFLOW_CREATETASK,
				'Create_Tasks',              't', BizResources::localize('ACT_CREATE_TASKS') ),
			self::WORKFLOW_MUTLIPLACEMENT    => new SysFeatureProfile( self::WORKFLOW_MUTLIPLACEMENT,
				'AllowMultipleArticlePlacements', 'M', BizResources::localize('ACT_ALLOWMULTIPLEARTICLEPLACEMENTS') ),
			self::WORKFLOW_CHANGEEDITION     => new SysFeatureProfile( self::WORKFLOW_CHANGEEDITION,
				'ChangeEdition',             'e', BizResources::localize('ACT_CHANGEEDITION') ),
			//self::WORKFLOW_EDITSTICKY      => new SysFeatureProfile( self::WORKFLOW_EDITSTICKY,
			//	'EditStickyNotes',           '', BizResources::localize('ACT_EDITSTICKYNOTES') ), // Replaced with APPLFEATURES_ANNOTATION
			self::WORKFLOW_CREATEDOSSIER     => new SysFeatureProfile( self::WORKFLOW_CREATEDOSSIER,
				'CreateDossier',             'd', BizResources::localize('ACT_CREATEDOSSIER') ),
			self::WORKFLOW_CHECKINARTFROMLAYER => new SysFeatureProfile( self::WORKFLOW_ADDICIMAGES,
				'CheckinArticleFromLayer',   '', BizResources::localize('ACT_CHECKINARTICLEFROMLAYER') ),
			self::WORKFLOW_CHECKINARTFROMDOC => new SysFeatureProfile( self::WORKFLOW_CHECKINARTFROMDOC,
				'CheckinArticleFromDocument', '', BizResources::localize('ACT_CHECKINARTICLEFROMDOCUMENT') ),
			self::WORKFLOW_ABORTCHECKOUT     => new SysFeatureProfile( self::WORKFLOW_ABORTCHECKOUT,
				'AbortCheckOut',             '', BizResources::localize('ACT_ABORTCHECKOUT') ),
			//self::WORKFLOW_RENAMECHECKIN   => new SysFeatureProfile( self::WORKFLOW_RENAMECHECKIN,
			//	'RenameOnCheckIn',           '', BizResources::localize('ACT_RENAMEONCHECKIN') ),
			//self::WORKFLOW_SAVEAS          => new SysFeatureProfile( self::WORKFLOW_SAVEAS,
			//	'SaveAs',                    '', BizResources::localize('ACT_SAVEAS') ),
			//self::WORKFLOW_CREATEARTICLE   => new SysFeatureProfile( self::WORKFLOW_CREATEARTICLE,
			//	'CreateArticle',             '', BizResources::localize('ACT_CREATEARTICLE') ),
			//self::WORKFLOW_CREATEIMAGE     => new SysFeatureProfile( self::WORKFLOW_CREATEIMAGE,
			//	'CreateImage',               '', BizResources::localize('ACT_CREATEIMAGE') ),
			self::WORKFLOW_RESTRICTED        => new SysFeatureProfile( self::WORKFLOW_RESTRICTED,
				'RestrictedProperties',      'r', BizResources::localize('ACT_RESTRICTEDPROPERTIES') ),
			self::WORKFLOW_CHANGEPIS         => new SysFeatureProfile( self::WORKFLOW_CHANGEPIS,
				'ChangePIS',                 'P', BizResources::localize('ACT_CHANGEPIS') ),
		);
	}

	const TEXT_APPLYPARASTYLE          = 101;
	const TEXT_EDITPARASTYLE           = 102;
	const TEXT_APPLYCHARSTYLE          = 104;
	const TEXT_EDITCHARSTYLE           = 105;

	/**
	 * Returns the access rights as listed under Text Styles section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_STYLES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getTextStylesAccessProfiles()
	{
		return array(
			self::TEXT_APPLYPARASTYLE        => new SysFeatureProfile( self::TEXT_APPLYPARASTYLE,
				'ApplyParaStyles',           '', BizResources::localize('ACT_APPLYPARASTYLES') ),
			self::TEXT_EDITPARASTYLE         => new SysFeatureProfile( self::TEXT_EDITPARASTYLE,
				'EditParaStyles',            '', BizResources::localize('ACT_EDITPARASTYLES') ),
			self::TEXT_APPLYCHARSTYLE        => new SysFeatureProfile( self::TEXT_APPLYCHARSTYLE,
				'ApplyCharStyles',           '', BizResources::localize('ACT_APPLYCHARSTYLES') ),
			self::TEXT_EDITCHARSTYLE         => new SysFeatureProfile( self::TEXT_EDITCHARSTYLE,
				'EditCharStyles',            '', BizResources::localize('ACT_EDITCHARSTYLES') ),
		);
	}

	const TEXT_APPLYPARAFORMAT         = 103;
	const TEXT_APPLYCHARFONTFAM        = 106;
	const TEXT_APPLYCHARFONTSTYLE      = 107;
	const TEXT_APPLYCHARBASICFORMAT    = 108;
	const TEXT_APPLYCHARADVFORMAT      = 109;
	const TEXT_COPYFIT                 = 110;
	const TEXT_COMPOSITIONPREFS        = 117;

	/**
	 * Returns the access rights as listed under Typography section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_TYPOGRAPHY', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getTypographyAccessProfiles()
	{
		return array(
			self::TEXT_APPLYPARAFORMAT       => new SysFeatureProfile( self::TEXT_APPLYPARAFORMAT,
				'ApplyParaFormats',          '', BizResources::localize('ACT_APPLYPARAFORMATS') ),
			self::TEXT_APPLYCHARFONTFAM       => new SysFeatureProfile( self::TEXT_APPLYCHARFONTFAM,
				'ApplyCharFontFamily',       '', BizResources::localize('ACT_APPLYCHARFONTFAMILY') ),
			self::TEXT_APPLYCHARFONTSTYLE    => new SysFeatureProfile( self::TEXT_APPLYCHARFONTSTYLE,
				'ApplyCharFontStyle',        '', BizResources::localize('ACT_APPLYCHARFONTSTYLE') ),
			self::TEXT_APPLYCHARBASICFORMAT  => new SysFeatureProfile( self::TEXT_APPLYCHARBASICFORMAT,
				'ApplyCharBasicFormats',     '', BizResources::localize('ACT_APPLYCHARBASICFORMATS') ),
			self::TEXT_APPLYCHARADVFORMAT    => new SysFeatureProfile( self::TEXT_APPLYCHARADVFORMAT,
				'ApplyCharAdvancedFormats',  '', BizResources::localize('ACT_APPLYCHARADVANCEDFORMATS') ),
			self::TEXT_COPYFIT               => new SysFeatureProfile( self::TEXT_COPYFIT,
				'CopyFit',                   '', BizResources::localize('ACT_COPYFIT') ),
			self::TEXT_COMPOSITIONPREFS       => new SysFeatureProfile( self::TEXT_COMPOSITIONPREFS,
				'CompositionPrefs',          '', BizResources::localize('ACT_COMPOSITIONPREFS') ),
		);
	}

	const TEXT_FORCETRACKCHANGES       = 125;
	const TEXT_EDITTRACKCHANGES        = 126;

	/**
	 * Returns the access rights as listed under the Track Changes section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_TRACKCHANGES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getTrackChangesAccessProfiles()
	{
		return array(
			self::TEXT_FORCETRACKCHANGES     => new SysFeatureProfile( self::TEXT_FORCETRACKCHANGES,
				'ForceTrackChanges',         '', BizResources::localize('ACT_FORCETRACKCHANGES'), 'No' ),
			self::TEXT_EDITTRACKCHANGES      => new SysFeatureProfile( self::TEXT_EDITTRACKCHANGES,
				'EditTrackChanges',          '', BizResources::localize('ACT_EDITTRACKCHANGES') ),
		);
	}

	const TEXT_CHANGELANGUAGE          = 114;
	const TEXT_EDITDICTIONARY          = 115;

	/**
	 * Returns the access rights as listed under the Linguistic section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_LINGUISTIC', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getLinguisticAccessProfiles()
	{
		return array(
			self::TEXT_CHANGELANGUAGE        => new SysFeatureProfile( self::TEXT_CHANGELANGUAGE,
				'ChangeLanguage',            '', BizResources::localize('ACT_CHANGELANGUAGE') ),
			self::TEXT_EDITDICTIONARY        => new SysFeatureProfile( self::TEXT_EDITDICTIONARY,
				'EditDictionary',            '', BizResources::localize('ACT_EDITDICTIONARY') ),
		);
	}

	const TEXT_RESIZEFRAMEPERLINE      = 118;
	const TEXT_RESIZEFRAMEPERCOORD     = 119;

	/**
	 * Returns the access rights as listed under the InCopy Geometry section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_LAYOUT', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getInCopyGeometryAccessProfiles()
	{
		return array(
			self::TEXT_RESIZEFRAMEPERLINE    => new SysFeatureProfile( self::TEXT_RESIZEFRAMEPERLINE,
				'ResizeTFLines',             '', BizResources::localize('ACT_RESIZETFLINES') ),
			self::TEXT_RESIZEFRAMEPERCOORD   => new SysFeatureProfile( self::TEXT_RESIZEFRAMEPERCOORD,
				'ResizeTF',                  '', BizResources::localize('ACT_RESIZETF') ),
		);
	}

	const TEXT_APPLYSWATCHES           = 111;
	const TEXT_EDITSWATCHES            = 112;
	//const TEXT_TRANSPARENCY          = 130;
	//const TEXT_COLORMANAGEMENT       = 131;
	//const TEXT_ANYCOLOR              = 133;

	/**
	 * Returns the access rights as listed under the Color on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_COLOR', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getColorAccessProfiles()
	{
		return array(
			self::TEXT_APPLYSWATCHES         => new SysFeatureProfile( self::TEXT_APPLYSWATCHES,
				'ApplySwatches',             '', BizResources::localize('ACT_APPLYSWATCHES') ),
			self::TEXT_EDITSWATCHES          => new SysFeatureProfile( self::TEXT_EDITSWATCHES,
				'EditSwatches',              '', BizResources::localize('ACT_EDITSWATCHES') ),
			//self::TEXT_TRANSPARENCY          => new SysFeatureProfile( self::TEXT_TRANSPARENCY,
			//	'Transparency',              '', BizResources::localize('ACT_TRANSPARENCY') ),
			//self::TEXT_COLORMANAGEMENT       => new SysFeatureProfile( self::TEXT_COLORMANAGEMENT,
			//	'ColorManagement',           '', BizResources::localize('ACT_COLORMANAGEMENT') ),
			//self::TEXT_ANYCOLOR              => new SysFeatureProfile( self::TEXT_ANYCOLOR,
			//	'AnyColor',                  '', BizResources::localize('ACT_ANYCOLOR') ),
		);
	}

	//const IMAGE_SELECT               = 127;
	//const IMAGE_CROP                 = 128;
	//const IMAGE_SCALE                = 129;

	/**
	 * Returns the access rights as listed under the Images section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_IMAGES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	/*static public function getImagesAccessProfiles()
	{
		return array(
			self::IMAGE_SELECT               => new SysFeatureProfile( self::IMAGE_SELECT,
				'ImageSelect',               '', BizResources::localize('ACT_IMAGESELECT') ),
			self::IMAGE_CROP                 => new SysFeatureProfile( self::IMAGE_CROP,
				'ImageCropping',             '', BizResources::localize('ACT_IMAGECROPPING') ),
			self::IMAGE_SCALE                => new SysFeatureProfile( self::IMAGE_SCALE,
				'ImageScale',                '', BizResources::localize('ACT_IMAGESCALE') ),
				// 'domain' => array ('FEATURE_YES', 'FEATURE_NO',	'FEATURE_PROPORTIONAL') )
		);
	}*/

	const CONFIG_EDITAGS               = 113;
	const CONFIG_SHORTCUTS             = 116;
	const CONFIG_TEXTMACROS            = 120;
	//const CONFIG_OUTPUTPREFS         = 132;
	const CONFIG_ADVELEMENTSPANEL      = 136;
	
	/**
	 * Returns the access rights as listed under the Configuration section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_CONFIG', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getConfigurationAccessProfiles()
	{
		return array(
			self::CONFIG_EDITAGS             => new SysFeatureProfile( self::CONFIG_EDITAGS,
				'EditTags',                  '', BizResources::localize('ACT_EDITTAGS') ),
			self::CONFIG_SHORTCUTS           => new SysFeatureProfile( self::CONFIG_SHORTCUTS,
				'ShortCuts',                 '', BizResources::localize('ACT_SHORTCUTS') ),
			self::CONFIG_TEXTMACROS          => new SysFeatureProfile( self::CONFIG_TEXTMACROS,
				'EditTextMacros',            '', BizResources::localize('ACT_EDITTEXTMACROS') ),
			//self::CONFIG_OUTPUTPREFS         => new SysFeatureProfile( self::CONFIG_OUTPUTPREFS,
			//	'OutputPrefs',               '', BizResources::localize('ACT_OUTPUTPREFS') ),
			self::CONFIG_ADVELEMENTSPANEL    => new SysFeatureProfile( self::CONFIG_ADVELEMENTSPANEL,
				'AdvElementsPanel',          '', BizResources::localize('ACT_ADVELEMENTSPANEL') ),
		);
	}

	const DATASOURCE_UPDATE            = 130; // Update Placed Content
	const DATASOURCE_WRITETOSERVER     = 131; // Save to Data Source
	const DATASOURCE_CREATEFIELD       = 132; // Place
	const DATASOURCE_UPDATECONTENTDB   = 133; // Check for Data Changes

	/**
	 * Returns the access rights as listed under the Data Sources section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_DATASOURCES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getDataSourcesAccessProfiles()
	{
		return array(
			self::DATASOURCE_UPDATE          => new SysFeatureProfile( self::DATASOURCE_UPDATE,
				'DSUpdate',                  '', BizResources::localize('ACT_DSUPDATE') ),
			self::DATASOURCE_WRITETOSERVER   => new SysFeatureProfile( self::DATASOURCE_WRITETOSERVER,
				'DSWriteDataToServer',       '', BizResources::localize('ACT_DSWRITEDATATOSERVER') ),
			self::DATASOURCE_CREATEFIELD     => new SysFeatureProfile( self::DATASOURCE_CREATEFIELD,
				'DSCreateField',             '', BizResources::localize('ACT_DSCREATEFIELD') ),
			self::DATASOURCE_UPDATECONTENTDB => new SysFeatureProfile( self::DATASOURCE_UPDATECONTENTDB,
				'DSUpdateContentDatabase',   '', BizResources::localize('ACT_DSUPDATECONTENTDATABASE') ),
		);
	}

	const CS_EDITTEXTCOMP              = 134; // Add/Remove Text Component
	const CS_INSERTINLINEIMAGE         = 135; // Insert Inline Image

	/**
	 * Returns the access rights as listed under the Content Station section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES_CONTENT', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getContentStationAccessProfiles()
	{
		return array(
			self::CS_EDITTEXTCOMP            => new SysFeatureProfile( self::CS_EDITTEXTCOMP,
				'EditTextComp',              '', BizResources::localize('ACT_EDITTEXTCOMP') ),
			self::CS_INSERTINLINEIMAGE       => new SysFeatureProfile( self::CS_INSERTINLINEIMAGE,
				'InsertInlineImage',         '', BizResources::localize('ACT_INSERTINLINEIMG') ),
		);
	}

	/**
	 * Build list of access rights. Each access right represents a Sub Application, 
	 * which can be returned by server plug-ins.
	 *
	 * @param integer $idCounter Access ID to be used as starting point to be increased.
	 * @return SysFeatureProfile[]
	 */
	private static function getSubApps( $idCounter )
	{
		$retVal = array();
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		require_once BASEDIR.'/server/services/sys/SysGetSubApplicationsService.class.php';
		try {
			$request = new SysGetSubApplicationsRequest();
			$request->Ticket = BizSession::getTicket();
			$request->ClientAppName = null; // all clients
			$service = new SysGetSubApplicationsService();
			$response = $service->execute( $request );
			if( $response->SubApplications ) foreach( $response->SubApplications as $subApp ) {
				$retVal[$idCounter] = new SysFeatureProfile( $idCounter, $subApp->ID, '', $subApp->DisplayName );
				$idCounter += 1;
			}
		} catch( BizException $e ) {
			// ignore errors
		}
		return $retVal;
	}

	const ACCESS_QUERY_BROWSE          = 1001;
	const ACCESS_PUBLICATION_OVERVIEW  = 1002;
	const ACCESS_UPLOAD                = 1003;
	const ACCESS_REPORTING             = 1004;
	//const ACCESS_EXPORT              = 1005;
	//const ACCESS_WEBEDITOR           = 1006;
	const ACCESS_MYPROFILE             = 1007;
	const ACCESS_PLANNING              = 1008;
	const ACCESS_CONTENTSTATIONPRO     = 1009;
	                                   // L> 1501...1999 is reserved for SubApps

	/**
	 * Returns the access rights as listed under the Applications section on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('WEBFEATURES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getApplicationsAccessProfiles()
	{
		return array(
			self::ACCESS_QUERY_BROWSE        => new SysFeatureProfile( self::ACCESS_QUERY_BROWSE,
				'Query_Browse',              '', BizResources::localize('ACT_QUERY_BROWSE') ),
			self::ACCESS_PUBLICATION_OVERVIEW=> new SysFeatureProfile( self::ACCESS_PUBLICATION_OVERVIEW,
				'Publication_Overview',      '', BizResources::localize('ACT_PUBLICATION_OVERVIEW') ),
			self::ACCESS_UPLOAD              => new SysFeatureProfile( self::ACCESS_UPLOAD,
				'Upload',                    '', BizResources::localize('ACT_UPLOAD') ),
			self::ACCESS_REPORTING           => new SysFeatureProfile( self::ACCESS_REPORTING,
				'Reporting',                 '', BizResources::localize('ACT_REPORTING') ),
			//self::ACCESS_EXPORT              => new SysFeatureProfile( self::ACCESS_EXPORT,
			//	'Export',                    '', BizResources::localize('ACT_EXPORT') ), // Moved to admin, see hidden in Applications access list
			//self::ACCESS_WEBEDITOR           => new SysFeatureProfile( self::ACCESS_WEBEDITOR, // Removed, Web Editor is no longer exist
			//	'Web_Editor',                '', BizResources::localize('ACT_WEB_EDITOR') ),
			self::ACCESS_MYPROFILE           => new SysFeatureProfile( self::ACCESS_MYPROFILE,
				'MyProfile',                 '', BizResources::localize('ACT_MYPROFILE') ),
			self::ACCESS_PLANNING            => new SysFeatureProfile( self::ACCESS_PLANNING,
				'Planning',                  '', BizResources::localize('OBJ_PLANNING') ),
			self::ACCESS_CONTENTSTATIONPRO   => new SysFeatureProfile( self::ACCESS_CONTENTSTATIONPRO,
				'ContentStationPro',         '', BizResources::localize('ACT_CS_PRO_EDITION') ),
		) + self::getSubApps( 1501 );
	}

	/**
	 * Returns application feature related access rights as listed on the Profile Maintanance admin page. 
	 * That are all rights, excluding the ones listed under the Applications- and File Access sections.
	 * Formerly this was defined in BizAccessData.php: define('APPLFEATURES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getAppFeaturesAccessProfiles()
	{
		return                                        // former defines:
			self::getTextStylesAccessProfiles()     + // APPLFEATURES_STYLES
			self::getTypographyAccessProfiles()     + // APPLFEATURES_TYPOGRAPHY
			self::getTrackChangesAccessProfiles()   + // APPLFEATURES_TRACKCHANGES
			self::getLinguisticAccessProfiles()     + // APPLFEATURES_LINGUISTIC
			self::getInCopyGeometryAccessProfiles() + // APPLFEATURES_LAYOUT
			self::getColorAccessProfiles()          + // APPLFEATURES_COLOR
			self::getWorkflowAccessProfiles()       + // APPLFEATURES_WORKFLOW
			self::getAnnotationsAccessProfiles()    + // APPLFEATURES_ANNOTATION
			self::getConfigurationAccessProfiles()  + // APPLFEATURES_CONFIG
			self::getDataSourcesAccessProfiles()    + // APPLFEATURES_DATASOURCES
			self::getContentStationAccessProfiles();  // APPLFEATURES_CONTENT
	}

	/**
	 * Returns -all- access rights as listed on the Profile Maintanance admin page.
	 * Formerly this was defined in BizAccessData.php: define('FEATURES', serialize(array( ... )));
	 *
	 * @since 9.0.0
	 * @return SysFeatureProfile[]
	 */
	static public function getAllFeaturesAccessProfiles()
	{
		return                                        // former defines:
			self::getFileAccessProfiles()           + // ACCESSFEATURES
			self::getAppFeaturesAccessProfiles()    + // APPLFEATURES
			self::getApplicationsAccessProfiles();    // WEBFEATURES
	}
}