<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

checkSecure('admin');
// Determine incoming mode/parameters.
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
// Mode handling
if (isset($_REQUEST['vdelete']) && $_REQUEST['vdelete']) {
	$mode = 'delete';
} else if (isset($_REQUEST['vupdate']) && $_REQUEST['vupdate']) {
	$mode = ($id > 0) ? 'update' : 'insert';
} else {
	$mode = ($id > 0) ? 'edit' : 'new';
}
$name = isset($_REQUEST['profile']) ? trim($_REQUEST['profile']) : '';
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';

$errors = array();

// Handle request
switch ($mode) {
	case 'update':
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}
		if (checkDuplicateName( $name, $id )) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}
		updateProfile( $name, $description, $id );
		deleteFeaturesByProfile( $id );
		addFeaturesByProfile( $id );
		break;
	case 'insert':
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}
		if ( checkDuplicateName( $name ) ) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}
		$id = addProfile( $name, $description );
		addFeaturesByProfile( $id);
		break;
	case 'delete':
		if ($id) {
			DBBase::deleteRows( 'profiles', '`id` = ?', array( intval( $id )) );
			DBBase::deleteRows( 'profilefeatures', '`profile` = ?', array( intval( $id )) );
			DBBase::deleteRows( 'authorizations', '`profile` = ?', array( intval( $id )) );
		}
		break;
}
// Delete: back to overview
if ($mode == 'delete' || $mode == "insert" || $mode == "update") {
	header("Location:profiles.php");
	exit();
}
// Generate upper part (edit fields)
if ($mode == 'error') {
	$row = array ('profile' => $name, 'description' => $description);
} elseif ($mode != "new") {
	$row = DBBase::getRow( 'profiles', '`id` = ?', '*', array( intval( $id ) ) );
} else {
	$row = array ('profile' => '', 'description' => '');
}
$txt = HtmlDocument::loadTemplate( 'hpprofiles.htm' );
// Error handling
$err = '';
foreach ($errors as $error) {
	$err .= formvar($error) . '<br/>';
}
$txt = str_replace('<!--ERROR-->', $err, $txt);
$txt = str_replace('<!--VAR:NAME-->', '<input maxlength="255" name="profile" value="'.formvar($row['profile']).'"/>', $txt );
$txt = str_replace('<!--VAR:HIDDEN-->', inputvar( 'id', $id, 'hidden' ), $txt );
$txt = str_replace('<!--VAR:DESCRIPTION-->', inputvar('description', $row['description'], 'area'), $txt );

if($mode !='new')
	$txt = str_replace('<!--VAR:BUTTON-->', 
		'<input type="submit" name="bt_update" value="'.BizResources::localize('ACT_UPDATE').'" onclick="return myupdate()"/>'.
		'<input type="submit" name="bt_delete" value="'.BizResources::localize('ACT_DEL').'" onclick="return mydelete()"/>', $txt );
else
	$txt = str_replace('<!--VAR:BUTTON-->', '<input type="submit" name="bt_update" value="'.BizResources::localize('ACT_UPDATE').'" onclick="return myupdate()"/>', $txt );

$ft = array();
if( $mode == 'error' ) {
	$features = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
	foreach( $features as $fid => $feature ) {
		if( array_key_exists( $fid , $_REQUEST['checkobj'] ) ) {
			 $ft[ $fid ] = 'Yes';
		}
	}
} else if( $mode != 'new' ) {
	$profileFeaturesRows = DBBase::listRows( 'profilefeatures', '', '', '`profile` = ?', '*', array( intval( $id ) ) );
	if( $profileFeaturesRows ) foreach( $profileFeaturesRows as $profileFeaturesRow ) {
		$ft[ $profileFeaturesRow['feature'] ] = $profileFeaturesRow['value'];
	}
} else {
	$features = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
	foreach( $features as $fid => $feature ) {
		$ft[ $fid ] = isset( $feature->Default ) ? $feature->Default : 'Yes';
	}
}

// Generate lower part (3x)
$sSelectAll = BizResources::localize('ACT_SELECT_ALL');
$sSelectAllRows = BizResources::localize('ACT_SELECT_ALL_ROWS');
$sUnselectAllRows = BizResources::localize('ACT_UNSELECT_ALL_ROWS');
$sUnselectAll = BizResources::localize('ACT_UNSELECT_ALL');
$detailtxt = '';
$featureColumns = array(
	array(
		'ACCESSFEATURES'        => BizAccessFeatureProfiles::getFileAccessProfiles(),
		'WEBFEATURES'           => BizAccessFeatureProfiles::getApplicationsAccessProfiles(),
	),
	array(
		'FEATURE_STYLES'        => BizAccessFeatureProfiles::getTextStylesAccessProfiles(),
		'FEATURE_TYPOGRAPHY'    => BizAccessFeatureProfiles::getTypographyAccessProfiles(),
		'FEATURE_TRACKCHANGES'  => BizAccessFeatureProfiles::getTrackChangesAccessProfiles(),
		'FEATURE_LINGUISTIC'    => BizAccessFeatureProfiles::getLinguisticAccessProfiles(),
		'FEATURE_CONTENT' 		=> BizAccessFeatureProfiles::getContentStationAccessProfiles(),
	),
	array(
		'FEATURE_LAYOUT'        => BizAccessFeatureProfiles::getInCopyGeometryAccessProfiles(),
		'FEATURE_COLOR'         => BizAccessFeatureProfiles::getColorAccessProfiles(),
		'FEATURE_WORKFLOW'      => BizAccessFeatureProfiles::getWorkflowAccessProfiles(),
		'FEATURE_CONFIG'        => BizAccessFeatureProfiles::getConfigurationAccessProfiles(), 
		'FEATURE_DATASOURCES'   => BizAccessFeatureProfiles::getDataSourcesAccessProfiles(),
		'ANNOTATIONS'           => BizAccessFeatureProfiles::getAnnotationsAccessProfiles(),
	),
);
$colWidth = floor( 100 / count($featureColumns) );
foreach ($featureColumns as $featureset) {
	$detailtxt .= '<td valign="top" width="'.$colWidth.'%"><table class="text" width="100%">';
	foreach ($featureset as $featureDisplay => $featureDef ) {
		$detailtxt .= '<tr><td colspan="2"><u>'.BizResources::localize($featureDisplay).'</u></td></tr>';
		$arr = array();
		foreach( $featureDef as $fid => $feature ) {
			$checked = (isset($ft[$fid]) && $ft[$fid] == 'Yes' ? ' checked="checked"': '');
			$detailtxt .= 
				'<tr>'.
					'<td>'.$feature->Display.'</td>'.
					'<td><input type="checkbox" name="checkobj['.$fid.']"'. $checked . '/></td>'.
				'</tr>';
			$arr[] = '\''.$fid.'\'';
		}
		$arrtxt = join(',', $arr);
		$detailtxt .= 
			'<tr><td colspan="2">'.
				'<a href="" onClick="javascript:checkgroup(new Array('.$arrtxt.'), true); return false;" '.
					'title="'.$sSelectAllRows.'">'.$sSelectAll.
				'</a>'.
				'&nbsp;&nbsp;'.
				'<a href="" onClick="javascript:checkgroup(new Array('.$arrtxt.'), false); return false; " '.
					'title="'.$sUnselectAllRows.'">'.$sUnselectAll.
				'</a>'.
			'</td></tr>';
		$detailtxt .= '<tr><td>&nbsp;</td></tr>';
	}
	$detailtxt .= '</table></td>';
}
$txt = str_replace('<!--APPLFEATURES-->', $detailtxt, $txt);
$txt = str_replace('<!--DETAILS-->', $detailtxt, $txt);
//Set focus to first field
$txt .= '<script language="javascript">document.forms[0].profile.focus();</script>';
print HtmlDocument::buildDocument($txt);

/**
 * Adds selected features to profile. Non-selected features are skipped.
 *
 * @param integer $id
 */
function addFeaturesByProfile( $id )
{
	$features = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
	foreach (array_keys($features) as $fid) {
		$value = isset($_REQUEST['checkobj'][$fid]) ? $_REQUEST['checkobj'][$fid] : '';
		$save = true;
		if ($value) {
			$value = 'Yes';
		} else {
			$save = false;
		}
		if ($save) {
			$values = array( 'profile' => $id, 'feature' => $fid, 'value' => $value );
			DBBase::insertRow( 'profilefeatures', $values );
		}
	}
}

/**
 * Checks if the name of the profile already exists. If no Id is passed in all profiles are checked.
 *
 * @param string $name
 * @param int|null $id
 * @return boolean
 */
function checkDuplicateName( $name, $id = null )
{
	$where = '`profile` = ? ';
	$params = array( strval( $name ) );
	if( $id ) {
		$where .= 'AND `id` != ? ';
		$params[] = intval( $id );
	}
	$row = DBBase::getRow( 'profiles', $where, array( 'id' ), $params );
	return $row ? true : false;
}

/**
 * Update profile
 *
 * @param string $name
 * @param string $description
 * @param integer $id
 */
function updateProfile( $name, $description, $id )
{
	$values = array( 'profile' => $name, 'description' => $description );
	$where = '`id` = ?';
	$params = array( intval( $id ) );
	DBBase::updateRow( 'profiles', $values, $where, $params );
}

/**
 * Delete all features of a profile.
 *
 * @param integer $id
 */
function deleteFeaturesByProfile( $id )
{
	$where = '`profile` = ?';
	$params = array( intval( $id ) );
	DBBase::deleteRows( 'profilefeatures', $where, $params );
}

/**
 * Adds a new profile.
 *
 * @param string $name
 * @param string $description
 * @return bool|int
 */
function addProfile( $name, $description )
{
	$values = array( 'profile' => $name, 'description' => $description, 'code' => 0 );
	$id = DBBase::insertRow( 'profiles', $values );
	return $id;
}
