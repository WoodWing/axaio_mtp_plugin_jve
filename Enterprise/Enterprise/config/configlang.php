<?php

function getObjectTypeMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['Article']        		= BizResources::localize('OBJ_ARTICLE');
		$map['ArticleTemplate']		= BizResources::localize('OBJ_ARTICLE_TEMPLATE');
		$map['Layout']         		= BizResources::localize('OBJ_LAYOUT');
		$map['LayoutTemplate'] 		= BizResources::localize('OBJ_LAYOUT_TEMPLATE');
		$map['Image']          		= BizResources::localize('OBJ_IMAGE');
		$map['Advert']         		= BizResources::localize('OBJ_ADVERT');
		$map['AdvertTemplate'] 		= BizResources::localize('OBJ_ADVERT_TEMPLATE');
		$map['Plan']           		= BizResources::localize('OBJ_PLAN');
		$map['Audio']          		= BizResources::localize('OBJ_AUDIO');
		$map['Video']          		= BizResources::localize('OBJ_VIDEO');
		$map['Library']        		= BizResources::localize('OBJ_LIBRARY');
		$map['Dossier']        		= BizResources::localize('DOSSIER');
		$map['DossierTemplate'] 	= BizResources::localize('DOSSIER_TEMPLATE');
		$map['LayoutModule']   		= BizResources::localize('OBJ_LAYOUT_MODULE');
		$map['LayoutModuleTemplate']= BizResources::localize('OBJ_LAYOUT_MODULE_TEMPLATE');
		$map['Task']        		= BizResources::localize('OBJ_TASK');
		$map['Hyperlink']        	= BizResources::localize('OBJ_HYPERLINK');
		$map['Presentation']		= BizResources::localize('OBJ_PRESENTATION');
		$map['Archive']				= BizResources::localize('OBJ_ARCHIVE');
		$map['Spreadsheet']			= BizResources::localize('OBJ_SPREADSHEET');
		$map['Other']      			= BizResources::localize('OBJ_OTHER'); // BZ#10482
		$map['PublishForm']			= BizResources::localize('PUBLISH_FORM');
		$map['PublishFormTemplate']	= BizResources::localize('PUBLISH_FORM_TEMPLATE');
	}
	return $map;
}

function getPropertyTypeMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['string']                      = BizResources::localize('PRO_STRING');
		$map['multistring']                 = BizResources::localize('PRO_MULTISTRING');
		$map['multiline']                   = BizResources::localize('PRO_MULTILINE');
		$map['bool']                        = BizResources::localize('PRO_BOOL');
		$map['int']                         = BizResources::localize('PRO_INT');
		$map['double']                      = BizResources::localize('PRO_DOUBLE');
		$map['date']                        = BizResources::localize('PRO_DATE');
		$map['datetime']                    = BizResources::localize('PRO_DATETIME');
		$map['list']                        = BizResources::localize('PRO_LIST');
		$map['multilist']                   = BizResources::localize('PRO_MULTILIST');
		$map['fileselector']                = BizResources::localize('PRO_FILESELECTOR');
		$map['file']                        = BizResources::localize('PRO_FILE');
		$map['articlecomponentselector']    = BizResources::localize('PRO_ARTICLECOMPONENTSELECTOR');
		$map['articlecomponent']            = BizResources::localize('PRO_ARTICLECOMPONENT');
	}
	return $map;
}

function getActionTypeMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['Create']        = BizResources::localize('ACT_CREATE');
		$map['CheckIn']       = BizResources::localize('ACT_CHECKIN');
		$map['SendTo']        = BizResources::localize('ACT_SENDTO');
		$map['CopyTo']        = BizResources::localize('ACT_COPYTO');
		$map['SetProperties'] = BizResources::localize('ACT_SETPROPERTIES');
		$map['Preview'] 	  = BizResources::localize('ACT_PREVIEW');
		
		$map['Query']               	= BizResources::localize('ACT_QUERY');
		$map['QueryOut']            	= BizResources::localize('ACT_QUERYOUT');
		$map['QueryOutContentStation']	= BizResources::localize('ACT_QUERYOUTCONTENTSTATION');
		$map['QueryOutInCopy']      	= BizResources::localize('ACT_QUERYOUTINCOPY');
		$map['QueryOutInDesign']    	= BizResources::localize('ACT_QUERYOUTINDESIGN');
		$map['QueryOutPlanning']       	= BizResources::localize('ACT_QUERYOUTPLANNING');
	}
	return $map;
}

function getObjectActionTypeMap( $objType )
{
	static $map;
	if (!isset($map)) { // not cached yet
		if( $objType ) { // typically not set for Query action
			$objTypes = getObjectTypeMap();
			$objTxt = $objTypes[$objType];
		} else {
			$objTxt = '';
		}
		$map['Create']        = BizResources::localize('ACT_CREATE_OBJECT', true, array( $objTxt ) );
		$map['CheckIn']       = BizResources::localize('ACT_CHECKIN_OBJECT', true, array( $objTxt ) );
		$map['SendTo']        = BizResources::localize('ACT_SENDTO_OBJECT', true, array( $objTxt ) );
		$map['CopyTo']        = BizResources::localize('ACT_COPYTO_OBJECT', true, array( $objTxt ) );
		$map['SetProperties'] = BizResources::localize('ACT_PROPERTIES_DIALOG_TITLE', true, array( $objTxt ) );
		$map['Preview'] 	  = BizResources::localize('ACT_PREVIEW_OBJECT', true, array( $objTxt ) );
		$map['Query'] 		  = BizResources::localize('OBJ_QUERY_DIALOG' );
	}
	return $map;
}

function getPropertyViewMap()
{	
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['All']      = BizResources::localize('PVW_ALL');
		$map['Static']   = BizResources::localize('PVW_STATIC');
		$map['Dynamic']  = BizResources::localize('PVW_DYNAMIC');
		$map['XMP']      = BizResources::localize('PVW_XMP');
		$map['Custom']   = BizResources::localize('PVW_CUSTOM');
		$map['Category'] = BizResources::localize('PVW_CATEGORY');
	}
	return $map;
}

function getPhaseTypeMap()
{
	static $map;
	if (!isset($map)) { // not cached yet
		$map = array();
		$map['Selection']    = BizResources::localize('WORKFLOW_PHASE_SELECTION');
		$map['Production']   = BizResources::localize('WORKFLOW_PHASE_PRODUCTION');
		$map['Completed']    = BizResources::localize('WORKFLOW_PHASE_COMPLETED');
		$map['Archived']     = BizResources::localize('WORKFLOW_PHASE_ARCHIVED');
	}
	return $map;
}

function getUiTerms()
{
	static $terms;
	if (!isset($terms)) { // not cached yet
		$terms = array();
		$terms[] = new Term( 'Publication',     BizResources::localize('PUBLICATION',false) );
		$terms[] = new Term( 'Publications',    BizResources::localize('PUBLICATIONS',false) );
		$terms[] = new Term( 'Issue',           BizResources::localize('ISSUE',false) );
		$terms[] = new Term( 'Issues',          BizResources::localize('ISSUES',false) );
		$terms[] = new Term( 'Section',         BizResources::localize('CATEGORY',false) ); // redirection to Category
		$terms[] = new Term( 'Sections',        BizResources::localize('CATEGORIES',false) ); // redirection to Categories
		$terms[] = new Term( 'Category',        BizResources::localize('CATEGORY',false) );
		$terms[] = new Term( 'Categories',      BizResources::localize('CATEGORIES',false) );
		$terms[] = new Term( 'Edition',         BizResources::localize('EDITION',false) );
		$terms[] = new Term( 'Editions',        BizResources::localize('EDITIONS',false) );
		$terms[] = new Term( 'State',           BizResources::localize('STATE',false) );
		$terms[] = new Term( 'States',          BizResources::localize('STATES',false) );
		$terms[] = new Term( 'Phase',           BizResources::localize('WORKFLOW_PHASE',false) );
		
		// >>> BZ#5636
		$terms[] = new Term( 'Current',         BizResources::localize('CURRENT',false) );
		$terms[] = new Term( 'Next',            BizResources::localize('NEXT',false) );
		$terms[] = new Term( 'Previous',        BizResources::localize('PREVIOUS',false) );
		$terms[] = new Term( 'CurrentIssue',    BizResources::localize('CURRENT_SCE_ISSUE',false) );
		$terms[] = new Term( 'NextIssue',       BizResources::localize('NEXT_SCE_ISSUE',false) );
		$terms[] = new Term( 'PreviousIssue',   BizResources::localize('PREVIOUS_SCE_ISSUE',false) );
		// <<<
		
		$terms[] = new Term( 'PublicationDate', BizResources::localize('PUBLICATION_DATE',false) );
		$terms[] = new Term( 'IssueSubject',    BizResources::localize('ISSUE_SUBJECT',false) );
		$terms[] = new Term( 'IssueDescription',BizResources::localize('ISSUE_DESCRIPTION',false) );
		$terms[] = new Term( 'UserName',        BizResources::localize('USR_USER_NAME',false) );

		// BZ#18554
		$terms[] = new Term( 'Inbox',          BizResources::localize('OBJ_INBOX',false) );
		
		// TODO: Localize at TMS and remove from here. (DPS is currently English only.)
		// DPS General
		$terms[] = new Term( 'ERR_PLEASE_CONTACT_YOUR_ADMIN', 'Please contact your system administrator.' );
		$terms[] = new Term( 'DPS_CONNECTION_CONFIGURED_INCORRECT', 'The connection to the Adobe Distribution Service is not configured correctly for Channel "%1" (id=%2) and Edition "%3" (id=%4).' );
		$terms[] = new Term( 'DPS_CONNECTION_CONFIGURED_INCORRECT_REASON', 'The ADOBEDPS_ACCOUNTS option in the config_dps.php file is incorrect.' );
		$terms[] = new Term( 'DPS_EXTRACTING',  'Extracting...' );
		$terms[] = new Term( 'DPS_EXPORTING',   'Exporting...' );
		$terms[] = new Term( 'DPS_COMPRESSING', 'Compressing...' );
		$terms[] = new Term( 'DPS_UPLOADING',   'Uploading...' );
		$terms[] = new Term( 'DPS_CLEANING',    'Cleaning...' );
		$terms[] = new Term( 'DPS_UNKNOWN', '<unknown>' );
		
		// DPS Generic error
		$terms[] = new Term( 'DPS_ERR_COULDNT_START_PUBLISHING_PROCESS', 'Could not start publishing process.' );
		$terms[] = new Term( 'DPS_ERR_COULDNT_PUBLISHING_PROCESS_DETAIL', 'User "%1" is currently publishing. Please wait and try again.' );
		$terms[] = new Term( 'DPS_ERR_COULDNT_CONTINUE_PUBLISHING_PROCESS', 'Could not continue publishing process.' );
		$terms[] = new Term( 'DPS_ERR_CANT_CONNECT_DPS_SERVER', 'Could not connect to Adobe Distribution Service.' );
		$terms[] = new Term( 'DPS_ERR_FAILED_COMMUNICATION_WITH_DPS_SERVER', 'Fatal communication with Adobe Distribution Service.' );
		$terms[] = new Term( 'DPS_ERR_DPS_SERVER_ERROR', 'Error occurred at Adobe Distribution Service.' );		
		$terms[] = new Term( 'DPS_ERR_COULDNT_SET_PUBLISH_INFO_ISSUE', 'Could not set publishing info for this issue.' );

		// DPS Report
		$terms[] = new Term( 'DPS_REPORT_OPERATION_SUCCESSFUL', 'Operation successful.' );
		$terms[] = new Term( 'DPS_REPORT_OPERATION_ABORTED', 'Operation aborted.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE', 'Could not extract folio file.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE_REASON_1', 'Could not find the folio file in the File Store for layout %LayoutName%, nor for Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE_REASON_2', 'Could not find the folio file in the File Store for Dossier %DossierName%.' );
        $terms[] = new Term( 'DPS_REPORT_COULD_NOT_EXTRACT_FOLIO_FILE_REASON_3', 'Could not find the folio file in the File Store for object %ObjectName%, or for Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOWTOFIX_COULD_NOT_EXTRACT_FOLIO_FILE', 'Please open layout %LayoutName% in InDesign and re-save it.' );
        $terms[] = new Term( 'DPS_REPORT_HOWTOFIX_COULD_NOT_EXTRACT_FOLIO_FILE_2', 'Please upload object %ObjectName% again.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_FIND_ARTICLE_FOLIO', 'Could not find the article folio file.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_FIND_ARTICLE_FOLIO_REASON', 'The article folio file "%1" does not exist.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_TOO_MANY_ARTICLES', 'Unable to import folio file "%ObjectName%" because it contains multiple stories.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_TOO_MANY_ARTICLES_REASON', 'Only the first story will be exported. Please upload a folio file that consists of only one story.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_ARTICLE_NOT_FOUND', 'The first story which is defined in the imported folio file "%ObjectName%" could not be found.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_ARTICLE_NOT_FOUND_REASON', 'Please upload the folio file again.' );
		$terms[] = new Term( 'DPS_REPORT_FAILED_BUILDING_FOLIO_FILE', 'Unable to build the folio file.' );
		$terms[] = new Term( 'DPS_REPORT_FAILED_BUILDING_FOLIO_FILE_REASON', 'Unable to place the contents into folio file "%1". The path to the folio file might be incorrect.' );
		$terms[] = new Term( 'DPS_REPORT_DEVICE_DEFINITION_NOT_FOUND_DETAIL', "No matching Output Device could be found for Edition \"%1\".\n\n" . 
								"Please make sure that the name of the Edition and its corresponding Output Device match each other exactly.\n\n" .
								"(This Edition is defined under Brand \"%2\" and Publication Channel \"%3\".)" );
		$terms[] = new Term( 'DPS_REPORT_MORE_THAN_ONE_HOR_LAYOUT', 'More than one landscape layout found in Dossier %DossierName%; only one landscape layout is used, ignoring the rest.' );
		$terms[] = new Term( 'DPS_REPORT_MORE_THAN_ONE_HOR_LAYOUT_REASON', 'Only one landscape layout is needed in a Dossier.' );
		$terms[] = new Term( 'DPS_REPORT_MORE_THAN_ONE_VER_LAYOUT', 'More than one portrait layout found in Dossier %DossierName%; only one portrait layout is used, ignoring the rest.' );
		$terms[] = new Term( 'DPS_REPORT_MORE_THAN_ONE_VER_LAYOUT_REASON', 'Only one portrait layout is needed in a Dossier.' );
		$terms[] = new Term( 'DPS_REPORT_ISSUE_ORIENTATION_SETTING', 'The issue orientation is set as "%1".' );
		$terms[] = new Term( 'DPS_REPORT_LAYOUT_HOR_IS_IGNORED', 'A landscape layout is found, this will be ignored.');
		$terms[] = new Term( 'DPS_REPORT_LAYOUT_VER_IS_IGNORED', 'A portrait layout is found, this will be ignored.');
		$terms[] = new Term( 'DPS_REPORT_LAYOUT_NOT_HAVING_HOR_NOR_VER', 'The layout %LayoutName% found in Dossier %DossierName% is not in landscape or portrait orientation.' );
		$terms[] = new Term( 'DPS_REPORT_LAYOUT_NOT_HAVING_HOR', 'Dossier %DossierName% does not have a layout in landscape orientation.' );
		$terms[] = new Term( 'DPS_REPORT_LAYOUT_NOT_HAVING_VER', 'Dossier %DossierName% does not have a layout in portrait orientation.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_HOR_IS_IGNORED', 'The folio file contains pages in landscape orientation. These will be ignored because the issue is set to use pages in portrait orientation only.');
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_VER_IS_IGNORED', 'The folio file contains pages in portrait orientation. These will be ignored because the issue is set to use pages in landscape orientation only.');
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_HOR_NOR_VER', 'The imported folio file %ObjectName% found in Dossier %DossierName% does not contain pages in landscape orientation or portrait orientation.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_HOR', 'The issue is set to contain pages in landscape orientation only but Dossier %DossierName% does not contain a folio file with pages in this orientation.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_FILE_NOT_HAVING_VER', 'The issue is set to contain pages in portrait orientation only but Dossier %DossierName% does not contain a folio file with pages in this orientation.' );
		$terms[] = new Term( 'DPS_REPORT_MULTIPLE_IMPORTED_FOLIOS_ASSIGNED', 'Dossier %DossierName% contains one or more folio files which have been assigned to the same Publication Channel.' );
		$terms[] = new Term( 'DPS_REPORT_MULTIPLE_IMPORTED_FOLIOS_ASSIGNED_REASON', 'Make sure that for each Publication Channel one folio file is assigned.' );
		$terms[] = new Term( 'DPS_REPORT_NO_DOSSIER_FOLIO_NOR_LAYOUTS', 'Dossier %DossierName% does not contain layouts or its own Dossier folio.' );
		$terms[] = new Term( 'DPS_REPORT_NO_DOSSIER_FOLIO_NOR_LAY_FOLIO_REASON', 'A Dossier which does not contain its own Dossier folio must at least contain layouts.' );
        $terms[] = new Term( 'DPS_REPORT_LAYOUT_AND_IMPORTED_FOLIO_FOUND', 'Dossier %DossierName% contains multiple folio files which have been assigned for export.' );
        $terms[] = new Term( 'DPS_REPORT_LAYOUT_AND_IMPORTED_FOLIO_FOUND_REASON', 'Make sure that for only one folio file the check box is selected in the Publication Channel column.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_CONTAINS_DOSSIER_LINKS', 'The imported folio file %ObjectName% contains Dossier Links which do not function.' );
        $terms[] = new Term( 'DPS_REPORT_IMPORTED_FOLIO_CONTAINS_DOSSIER_LINKS_REASON', 'The Dossier Link targets are not available. Please replace the folio file with a version without Dossier Links.' );
		$terms[] = new Term( 'DPS_REPORT_LOAD_XML_FAILED', 'Failed to load XML file "%1"' );
		$terms[] = new Term( 'DPS_REPORT_LOAD_XML_FAILED_REASON', 'XML file is invalid or not well formed.' );
		$terms[] = new Term( 'DPS_REPORT_FOLIO_DIMENSION_NOT_MATCH_WITH_DEVICE_DIMENSION', 'The dimensions as set in the folio and configuration files do not match.' );
		$terms[] = new Term( 'DPS_REPORT_FOLIO_WIDEDIMENSION_NOT_MATCH_WITH_DEVICE_WIDEDIMENSION_REASON', 'The device "wideDimension" value set in the Output Devices page does not match the "wideDimension" retrieved from the folio XML.' .
								'The "wideDimension" value set in the Output Devices page is "%1" but is set as "%2" in the folio XML.' );
		$terms[] = new Term( 'DPS_REPORT_FOLIO_NARROWDIMENSION_NOT_MATCH_WITH_DEVICE_NARROWDIMENSION_REASON', 'The device "narrowDimension" value set in the Output Devices page does not match the "narrowDimension" value retrieved from the folio XML.' .
								'The "narrowDimension" set in the Output Devices page is "%1" but is set as "%2" in the folio XML.');
		$terms[] = new Term( 'DPS_REPORT_WRONG_COVER_IMG_DIMENSION', 'Issue Cover Image %ObjectName% in Dossier %DossierName% has the wrong dimension.' );
		$terms[] = new Term( 'DPS_REPORT_WRONG_COVER_LAY_DIMENSION', 'Issue Cover Layout %ObjectName% in Dossier %DossierName% has the wrong dimension.' );		
		$terms[] = new Term( 'DPS_REPORT_WRONG_SEC_COVER_IMG_DIMENSION', 'Section Cover Image %ObjectName% in Dossier %DossierName% has the wrong dimension.' );
		$terms[] = new Term( 'DPS_REPORT_WRONG_SEC_COVER_LAY_DIMENSION', 'Section Cover Layout %ObjectName% in Dossier %DossierName% has the wrong dimension.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_OR_VER', 'It is exactly square, but it must either be in landscape or portrait orientation.' );
		$terms[] = new Term( 'DPS_REPORT_COVER_HOR_NOT_MATCH_WITH_DEVICE_DIMENSION', 'The dimensions of the landscape Issue Cover are not equal to the dimensions of the device.' );
		$terms[] = new Term( 'DPS_REPORT_COVER_VER_NOT_MATCH_WITH_DEVICE_DIMENSION', 'The dimensions of the portrait Issue Cover are not equal to the dimensions of the device.' );
		$terms[] = new Term( 'DPS_REPORT_SEC_COVER_HOR_NOT_MATCH_WITH_DEVICE_DIMENSION', 'The dimensions of the landscape Section Cover are not equal to the dimensions of the device.' );
		$terms[] = new Term( 'DPS_REPORT_SEC_COVER_VER_NOT_MATCH_WITH_DEVICE_DIMENSION', 'The dimensions of the portrait Section Cover are not equal to the dimensions of the device.' );
		$terms[] = new Term( 'DPS_REPORT_DIMENSION', 'The dimensions must be %1 x %2.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_COVER_IMG_NOT_FOUND', 'No Issue Cover Image or Issue Cover Layout in landscape orientation found in %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_VER_COVER_IMG_NOT_FOUND', 'No Issue Cover Image or Cover Layout in portrait orientation found in %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_COVER_IMG_NOT_FOUND_REASON', 'Please add an Issue Cover Image or an Issue Cover Layout in landscape orientation to Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_VER_COVER_IMG_NOT_FOUND_REASON', 'Please add an Issue Cover Image or an Issue Cover Layout in portrait orientation to Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_SEC_COVER_IMG_NOT_FOUND', 'No Section Cover Image or Section Cover Layout in landscape orientation found in %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_VER_SEC_COVER_IMG_NOT_FOUND', 'No Section Cover Image or Section Cover Layout in portrait orientation found in %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_SEC_COVER_IMG_NOT_FOUND_REASON', 'Please add a Section Cover Image or a Section Cover Layout in landscape orientation to Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_VER_SEC_COVER_IMG_NOT_FOUND_REASON', 'Please add a Section Cover Image or a Section Cover Layout in portrait orientation to Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_COVER_NOT_CREATED', 'The Issue Cover Image in landscape orientation could not be created for Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_VER_COVER_NOT_CREATED', 'The Issue Cover Image in portrait orientation could not be created for Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_COVER_NOT_CREATED_REASON', 'The Issue Cover Image in landscape orientation has not been updated on the Adobe Distribution Service. The previous version will be shown.' );
		$terms[] = new Term( 'DPS_REPORT_VER_COVER_NOT_CREATED_REASON', 'The Issue Cover Image in portrait orientation has not been updated on the Adobe Distribution Service. The previous version will be shown.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_SEC_COVER_NOT_CREATED', 'The Section Cover Image in landscape orientation could not be created for Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_VER_SEC_COVER_NOT_CREATED', 'The Section Cover Image in portrait orientation could not be created for Dossier %DossierName%.' );
		$terms[] = new Term( 'DPS_REPORT_HOR_SEC_COVER_NOT_CREATED_REASON', 'The Section Cover Image in landscape orientation has not been updated on the Adobe Distribution Service. The previous version will be shown.' );
		$terms[] = new Term( 'DPS_REPORT_VER_SEC_COVER_NOT_CREATED_REASON', 'The Section Cover Image in portrait orientation has not been updated on the Adobe Distribution Service. The previous version will be shown.' );
		$terms[] = new Term( 'DPS_REPORT_WRONG_COVER_IMG_MIMETYPE', 'The Issue Cover Image %ObjectName% in Dossier %DossierName% is in an incorrect file format.' );
		$terms[] = new Term( 'DPS_REPORT_WRONG_SEC_COVER_IMG_MIMETYPE', 'The Section Cover Image %ObjectName% in Dossier %DossierName% is in an incorrect file format.' );
		$terms[] = new Term( 'DPS_REPORT_WRONG_HEADER_IMG_MIMETYPE', 'The Header Image %ObjectName% in Dossier %DossierName% is in an incorrect file format.' );
		$terms[] = new Term( 'DPS_REPORT_WRONG_TRAY_IMG_MIMETYPE', 'Image %ObjectName% in Dossier %DossierName% is set to be used in the Text View Image Tray but is in an incorrect file format.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_EXTRACT_FILE', 'File %ObjectName% in Dossier %DossierName% could not be extracted from the FileStore.' );
		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_EXPORT_FILE', 'The %ObjectName% file in Dossier %DossierName% could not be exported.' );
		$terms[] = new Term( 'DPS_REPORT_JPG_OR_PNG', 'Please use a file in either JPEG or PNG format.' );
		$terms[] = new Term( 'DPS_REPORT_JPG_OR_PNG_OR_GIF', 'Please use a file in either JPEG, PNG or GIF format.' );
		$terms[] = new Term( 'DPS_REPORT_NO_PUBLICATION_DATE_SET', 'No Publication Date set.' );
		$terms[] = new Term( 'DPS_REPORT_NO_PUBLICATION_DATE_SET_REASON', 'The Issue Publication Date property has not been set. This is used by the Content Viewer for sorting issues in the Library, and for assigning an issue to a subscription duration.' );
		$terms[] = new Term( 'DPS_REPORT_NO_PRODUCT_ID_SET', 'No Product ID set.' );
		$terms[] = new Term( 'DPS_REPORT_NO_PRODUCT_ID_SET_REASON', 'The Issue DPS Product ID property has not been set. Omitting this property might result in the issue being incorrectly viewed in the Content Viewer.' );
        $terms[] = new Term( 'DPS_ERR_COULD_NOT_SEND_PUSH_NOTIFICATION', 'The push notification could not be sent.' );
        $terms[] = new Term( 'DPS_ERR_COULD_NOT_SEND_PUSH_NOTIFICATION_REASON', 'Please wait a few minutes and try re-sending the push notification.' );

		$terms[] = new Term( 'DPS_ERR_MULTIPLE_HTMLRESOURCES_DOSSIERS_FOUND', 'Multiple Dossiers have been found that have the intent set to "HTMLResources".');
		$terms[] = new Term( 'DPS_ERR_MULTIPLE_HTMLRESOURCES_DOSSIERS_FOUND_REASON', 'Please make sure that there is only one dossier with the intent set to "HTMLResources".');
		$terms[] = new Term( 'DPS_ERR_HTMLRESOURCES_DOSSIERS_INVALID_FILES_FOUND', 'The Dossier has the Intent set to "HTMLResources" but does not contain the correct content.');
		$terms[] = new Term( 'DPS_ERR_HTMLRESOURCES_DOSSIERS_INVALID_FILES_FOUND_REASON', 'Please add all required content (such as images) in a ZIP ﬁle and assign it to the Publication Channel.');

		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_FIND_TEXTVIEW_WIDGET', 'Could not export the Text View because the Text View widget isn\'t found.' );

        $terms[] = new Term( 'DPS_IMPORT_WIDGET_INVALID_MANIFEST_FILE',  'Could not store the Widget because the manifest.xml is invalid.' );
		$terms[] = new Term( 'DPS_IMPORT_WIDGET_MANIFEST_FILE_NOT_CONFORM_SCHEMA',  'Could not store the Widget because the manifest.xml file isn\'t conform the schema.' );
		$terms[] = new Term( 'DPS_IMPORT_WIDGET_COULD_NOT_READ_ZIP_FILE',  'Could not read the given archive.' );

		$terms[] = new Term( 'DPS_REPORT_COULD_NOT_READ_FONT_DEF',  'Could read the font definitions for object %ObjectName%.' );
		$terms[] = new Term( 'DPS_REPORT_FILE_NOT_FOUND',  'File %FilePath% not found.' );
	}
	return $terms;
}

/**
 * Returns an array with language codes as keys and English names as values.
 * The keys are language codes in "llCC" format (l = language code, C = county code).
 * (The language code follows ISO-639, and the country code follows ISO-3166.)
 * The values are full English names in "Language[: Country]" format as used in Adobe InCopy documents.
 *
 * Values for which there is no valid key (conform the two ISO standards) are NOT supported by
 * Enterprise, such as 'English: USA Medical'. Since documents are created within Enterprise, 
 * only 'English: USA' will be used instead. At the back-end, admins can install the Medical
 * dictionary, which gets implicitly taken into account by the use of 'English: USA'.
 *
 * The intention of this function is to simplify configuration for 99% of the customers.
 * Values for which there -is- a valid key, but are missing in the list below, are bugs.
 * Same for values that do not match with the actual value at the InCopy document.
 */
function getLanguageCodesTable()
{
	return array( 
		//'' => 'Neutral',
		
		//'' => 'Arabic',
		'arSA' => 'Arabic: Saudi Arabia',
		'arIQ' => 'Arabic: Iraq',
		'arEG' => 'Arabic: Egypt',
		'arLY' => 'Arabic: Libya',
		'arDZ' => 'Arabic: Algeria',
		'arMA' => 'Arabic: Morocco',
		'arTN' => 'Arabic: Tunisia',
		'arOM' => 'Arabic: Oman',
		'arYE' => 'Arabic: Yemen',
		'arSY' => 'Arabic: Syria',
		'arJO' => 'Arabic: Jordan',
		'arLB' => 'Arabic: Lebanon',
		'arKW' => 'Arabic: Kuwait',
		'arAE' => 'Arabic: United Arab Emirates',
		'arBH' => 'Arabic: Bahrain',
		'arQA' => 'Arabic: Qatar',
			
		'afZA' => 'Afrikaans',
		'sqAL' => 'Albanian',
		'euES' => 'Basque',
		'bgBG' => 'Bulgarian',
		'beBY' => 'Byelorussian',
		'caES' => 'Catalan',
			
		//'' => 'Chinese',
		'zhTW' => 'Chinese: Taiwan',
		'zhCN' => 'Chinese: PR China',
		'zhHK' => 'Chinese: Hong Kong',
		'zhSG' => 'Chinese: Singapore',
		
		'hrHR' => 'Croatian',
		'csCZ' => 'Czech',
		'daDK' => 'Danish',
			
		'nlNL' => 'Dutch',
		'nlBE' => 'Dutch: Belgian',
			
		//'' => 'English',
		'enUS' => 'English: USA',
		//'' => 'English: USA Legal',
		//'' => 'English: USA Medical',
		'enGB' => 'English: UK',
		'enAU' => 'English: Australian',
		'enCA' => 'English: Canadian',
		'enNZ' => 'English: New Zealand',
		'enIE' => 'English: Irish',
		'enZA' => 'English: South Africa',
		'enJM' => 'English: Jamaica',
		'enCB' => 'English: Carribean',
			
		//'' => 'US English',
		//'' => 'UK English',
			
		'etEE' => 'Estonian',
		'foFO' => 'Faeroese',
		'faIR' => 'Farsi',
		'fiFI' => 'Finnish',
			
		//'' => 'French',
		'frBE' => 'French: Belgian',
		'frCA' => 'French: Canadian',
		'frCH' => 'French: Swiss',
		'frLU' => 'French: Luxembourg',
		'frFR' => 'France French',
			
		//'' => 'German',
		//'' => 'German: Traditional',
		//'' => 'German: Reformed',
		'deCH' => 'German: Swiss',
		'deAT' => 'German: Austrian',
		'deLU' => 'German: Luxembourg',
		'deLI' => 'German: Liechtenstein',
		'deDE' => 'Germany German',
			
		'elGR' => 'Greek',
		'heIL' => 'Hebrew',
		'huHU' => 'Hungarian',
		'isIS' => 'Icelandic',
		'idID' => 'Indonesian',
			
		'itIT' => 'Italian',
		'itCH' => 'Italian: Swiss',
		
		'jaJP' => 'Japanese',
		//'' => 'Japanese(Not Spell Checked)',
			
		'koKR' => 'Korean',
		//'' => 'Korean: Johab',
			
		'lvLV' => 'Latvian',
		'ltLT' => 'Lithuanian',
			
		//'' => 'Norwegian',
		'nbNO' => 'Norwegian: Bokmal',
		'nnNO' => 'Norwegian: Nynorsk',
			
		'plPL' => 'Polish',
			
		'ptPT' => 'Portuguese',
		'ptBR' => 'Portuguese: Brazilian',
			
		'roRO' => 'Romanian',
		'ruRU' => 'Russian',
		'skSK' => 'Slovak',
		'slSI' => 'Slovenian',
		'srRS' => 'Sorbian',
			
		//'' => 'Spanish',
		'esES' => 'Spanish: Castilian',
		'esMX' => 'Spanish: Mexican',
		//'' => 'Spanish: Modern',
		'esGT' => 'Spanish: Guatemala',
		'esCR' => 'Spanish: Costa Rica',
		'esPA' => 'Spanish: Panama',
		'esDO' => 'Spanish: Dominican Republic',
		'esVE' => 'Spanish: Venezuela',
		'esCO' => 'Spanish: Colombia',
		'esPE' => 'Spanish: Peru',
		'esAR' => 'Spanish: Argentina',
		'esEC' => 'Spanish: Ecuador',
		'esCL' => 'Spanish: Chile',
		'esUY' => 'Spanish: Uruguay',
		'esPY' => 'Spanish: Paraguay',
		'esBO' => 'Spanish: Bolivia',
			
		'svSE' => 'Swedish',
		'thTH' => 'Thai',
		'trTR' => 'Turkish',
		'ukUA' => 'Ukrainian'
	);
}