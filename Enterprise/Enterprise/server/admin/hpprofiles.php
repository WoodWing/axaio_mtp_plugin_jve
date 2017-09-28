<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/interfaces/services/adm/DataClasses.php';

$ticket = checkSecure('admin');

// determine incoming mode
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// mode handling
if (isset($_REQUEST['vdelete']) && $_REQUEST['vdelete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}

// get param's
$accessProfileName = isset($_REQUEST['profile']) ? trim($_REQUEST['profile']) : '';
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';

$errors = array();

// handle request
switch( $mode ) {
	case 'update':
		try {
			require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
			$request = new AdmGetAccessProfilesRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array( 'GetProfileFeatures' );
			$request->AccessProfileIds = array( $id );
			$service = new AdmGetAccessProfilesService();
			$response = $service->execute( $request );
			$accessProfile = $response->AccessProfiles[0];

			$mutations = getProfileFeatureMutations( $accessProfile->ProfileFeatures );

			$accessProfile = new AdmAccessProfile();
			$accessProfile->Id = intval( $id );
			$accessProfile->Name = strval( $accessProfileName );
			$accessProfile->Description = strval( $description );
			$accessProfile->ProfileFeatures = $mutations;

			require_once BASEDIR.'/server/services/adm/AdmModifyAccessProfilesService.class.php';
			$request = new AdmModifyAccessProfilesRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->AccessProfiles = array( $accessProfile );
			$service = new AdmModifyAccessProfilesService();
			$service->execute( $request );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
			$mode = 'error';
		}
		break;
	case 'insert':
		try {
			$mutations = array();
			foreach( $_REQUEST['checkobj'] as $name => $value ) {
				$profileFeature = new AdmProfileFeature();
				$profileFeature->Name = $name;
				$profileFeature->Value = 'Yes';
				$mutations[$name] = $profileFeature;
			}

			$accessProfile = new AdmAccessProfile();
			$accessProfile->Name = strval( $accessProfileName );
			$accessProfile->Description = strval( $description );
			$accessProfile->SortOrder = 0;
			$accessProfile->ProfileFeatures = $mutations;

			require_once BASEDIR.'/server/services/adm/AdmCreateAccessProfilesService.class.php';
			$request = new AdmCreateAccessProfilesRequest();
			$request->Ticket = $ticket;
			$request->RequestModes = array();
			$request->AccessProfiles = array( $accessProfile );
			$service = new AdmCreateAccessProfilesService();
			$service->execute( $request );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
			$mode = 'error';
		}
		break;
	case 'delete':
		try {
			require_once BASEDIR.'/server/services/adm/AdmDeleteAccessProfilesService.class.php';
			$request = new AdmDeleteAccessProfilesRequest();
			$request->Ticket = $ticket;
			$request->AccessProfileIds = array( $id );
			$service = new AdmDeleteAccessProfilesService();
			$service->execute( $request );
		} catch( BizException $e ) {
			$errors[] = $e->getMessage();
			$mode = 'error';
		}
		break;
}
// delete: back to overview
if( $mode == 'delete' || $mode == 'insert' || $mode == 'update' ) {
	header('Location:profiles.php');
	exit();
}
// generate upper part (edit fields)
if( $mode == 'error' ) {
	$accessProfile = new AdmAccessProfile();
	$accessProfile->Name = $accessProfileName;
	$accessProfile->Description = $description;
} elseif( $mode != 'new' ) {
	try {
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
		$request = new AdmGetAccessProfilesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array( 'GetProfileFeatures' );
		$request->AccessProfileIds = array( $id );
		$service = new AdmGetAccessProfilesService();
		$response = $service->execute( $request );
		$accessProfile = $response->AccessProfiles[0];
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
} else {
	$accessProfile = new AdmAccessProfile();
	$accessProfile->Name = '';
	$accessProfile->Description = '';
}
$txt = HtmlDocument::loadTemplate( 'hpprofiles.htm' );

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= formvar($error) . '<br/>';
}
$txt = str_replace('<!--ERROR-->', $err, $txt);

// fields
$txt = str_replace('<!--VAR:NAME-->',
	'<input maxlength="255" name="profile" value="'.formvar($accessProfile->Name).'"/>', $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar( 'id', $id, 'hidden' ), $txt );
$txt = str_replace('<!--VAR:DESCRIPTION-->',
	'<textarea rows="3" name="description" style="resize: none;">'.formvar( $accessProfile->Description ).'</textarea>', $txt );

if( $mode !='new' ) {
	$txt = str_replace('<!--VAR:BUTTON-->',
		'<input type="button" name="bt_delete" value="'.BizResources::localize('ACT_DEL').'" onclick="return mydelete()"/>'.
		'&nbsp;&nbsp;<input type="submit" name="bt_update" value="'.BizResources::localize('ACT_UPDATE').'" onclick="return myupdate()"/>'
		, $txt );
} else {
	$txt = str_replace('<!--VAR:BUTTON-->', '<input type="submit" name="bt_update" value="'.BizResources::localize('ACT_UPDATE').'" onclick="return myupdate()"/>', $txt );
}
$profileFeatures = array();
if( $mode == 'error' ) {
	$sysProfileFeatures = null;
	try {
		$sysProfileFeatures = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
	} catch( BizException $e ) {
	}
	if( $sysProfileFeatures ) foreach( $sysProfileFeatures as $sysFeature ) {
		if( $_REQUEST['checkobj'][$sysFeature->Name] ) {
			$profileFeature = new AdmProfileFeature();
			$profileFeature->Name = $sysFeature->Name;
			$profileFeature->Value = 'Yes';
			$profileFeatures[$sysFeature->Name] = $profileFeature;
		}
	}
} else if( $mode != 'new' ) {
	$profileFeatures = $accessProfile->ProfileFeatures;
} else {
	//get template access profile with all profile features
	try {
		require_once BASEDIR.'/server/services/adm/AdmGetAccessProfilesService.class.php';
		$request = new AdmGetAccessProfilesRequest();
		$request->Ticket = $ticket;
		$request->RequestModes = array( 'GetProfileFeatures' );
		$request->AccessProfileIds = array( 0 ); //0 is template id
		$service = new AdmGetAccessProfilesService();
		$response = $service->execute( $request );
		$accessProfile = $response->AccessProfiles[0];
		$profileFeatures = $accessProfile->ProfileFeatures;
	} catch( BizException $e ) {
		$errors[] = $e->getMessage();
		$mode = 'error';
	}
}

// generate lower part (3x)
$sSelectAll = BizResources::localize('ACT_SELECT_ALL');
$sSelectAllRows = BizResources::localize('ACT_SELECT_ALL_ROWS');
$sUnselectAllRows = BizResources::localize('ACT_UNSELECT_ALL_ROWS');
$sUnselectAll = BizResources::localize('ACT_UNSELECT_ALL');

$detailtxt = '';
$featureCategories = array(
	'ACCESSFEATURES'        => BizAccessFeatureProfiles::getFileAccessProfiles(),
	'WEBFEATURES'           => BizAccessFeatureProfiles::getApplicationsAccessProfiles(),
	'FEATURE_STYLES'        => BizAccessFeatureProfiles::getTextStylesAccessProfiles(),
	'FEATURE_TYPOGRAPHY'    => BizAccessFeatureProfiles::getTypographyAccessProfiles(),
	'FEATURE_TRACKCHANGES'  => BizAccessFeatureProfiles::getTrackChangesAccessProfiles(),
	'FEATURE_LINGUISTIC'    => BizAccessFeatureProfiles::getLinguisticAccessProfiles(),
	'FEATURE_CONTENT' 		=> BizAccessFeatureProfiles::getContentStationAccessProfiles(),
	'FEATURE_LAYOUT'        => BizAccessFeatureProfiles::getInCopyGeometryAccessProfiles(),
	'FEATURE_COLOR'         => BizAccessFeatureProfiles::getColorAccessProfiles(),
	/*'FEATURE_IMAGES'      => BizAccessFeatureProfiles::getImagesAccessProfiles(), */
	'FEATURE_WORKFLOW'      => BizAccessFeatureProfiles::getWorkflowAccessProfiles(),
	'FEATURE_CONFIG'        => BizAccessFeatureProfiles::getConfigurationAccessProfiles(),
	'FEATURE_DATASOURCES'   => BizAccessFeatureProfiles::getDataSourcesAccessProfiles(),
	'ANNOTATIONS'           => BizAccessFeatureProfiles::getAnnotationsAccessProfiles(),
);

$pluginFeatures = BizAccessFeatureProfiles::getServerPluginFeatureAccessLists();
require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
$connRetVals = array();
BizServerPlugin::runDefaultConnectors( 'FeatureAccess', null, 'composeFeaturesAccessProfilesDialog',
	array( &$featureCategories, $pluginFeatures ), $connRetVals );

foreach( $featureCategories as $featureCategoryResourceKey => $featureDefinitions ) {
	$detailtxt .= '<div class="wwes-grid-item"><table>';
	$detailtxt .= '<tr><td colspan="2"><u>'.BizResources::localize($featureCategoryResourceKey).'</u></td></tr>';
	$arr = array();
	foreach( $featureDefinitions as $feature ) {
		$checked = (isset($profileFeatures[$feature->Name]) && $profileFeatures[$feature->Name]->Value == 'Yes' ? ' checked="checked"': '');
		$detailtxt .=
			'<tr>'.
				'<td>'.formvar($feature->Display).'</td>'.
				'<td align="right"><input type="checkbox" name="checkobj['.formvar($feature->Name).']" '.$checked.'/></td>'.
			'</tr>';
		$arr[] = '\''.formvar($feature->Name).'\'';
	}
	$arrtxt = join(',', $arr);
	$detailtxt .=
		'<tr><td colspan="2" align="right"><div style="padding-top: 5px">'.
			'<a href="" onClick="javascript:checkgroup(new Array('.$arrtxt.'), false); return false; " '.
				'title="'.$sUnselectAllRows.'">'.$sUnselectAll.
			'</a>'.
			'&nbsp;&nbsp;&nbsp;'.
			'<a href="" onClick="javascript:checkgroup(new Array('.$arrtxt.'), true); return false;" '.
				'title="'.$sSelectAllRows.'">'.$sSelectAll.
			'</a>'.
		'</div></td></tr>';
	$detailtxt .= '</table></div>';
}

$txt = str_replace('<!--APPLFEATURES-->', $detailtxt, $txt);

// generate total page
$txt = str_replace('<!--DETAILS-->', $detailtxt, $txt);

//set focus to first field
$txt .= '<script language="javascript">document.forms[0].profile.focus();</script>';

print HtmlDocument::buildDocument($txt);

function getProfileFeatureMutations( $dbProfileFeatures )
{
	$mutations = array();
	if( isset( $_REQUEST['checkobj'] ) ) {
		$formProfileFeatures = $_REQUEST['checkobj'];
	} else {
		$formProfileFeatures = array();
	}

	$dbPosProfileFeatures = array();
	foreach( $dbProfileFeatures as $name => $dbProfileFeature ) {
		if( $dbProfileFeature->Value == 'Yes' ) {
			$dbPosProfileFeatures[$name] = $dbProfileFeature;
		}
	}
	//creates array of keys that are in form but not in db ("Yes" features)
	$posDifferences = array_diff_key( $formProfileFeatures, $dbPosProfileFeatures );
	//creates array of keys that are in db but not in form ("No" features)
	$negDifferences = array_diff_key( $dbPosProfileFeatures, $formProfileFeatures );

	foreach( array_keys( $posDifferences ) as $name ) {
		$profileFeature = new AdmProfileFeature();
		$profileFeature->Name = $name;
		$profileFeature->Value = 'Yes';
		$mutations[] = $profileFeature;
	}
	foreach( array_keys( $negDifferences ) as $name ) {
		$profileFeature = new AdmProfileFeature();
		$profileFeature->Name = $name;
		$profileFeature->Value = 'No';
		$mutations[] = $profileFeature;
	}
	return $mutations;
}
