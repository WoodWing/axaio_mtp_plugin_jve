<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('admin');

// database stuff
$dbh = DBDriverFactory::gen();
$dbp = $dbh->tablename('profiles');
$dbpv = $dbh->tablename('profilefeatures');
$dba = $dbh->tablename('authorizations');

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
$name = isset($_REQUEST['profile']) ? trim($_REQUEST['profile']) : '';
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] : '';

$errors = array();

// handle request
switch ($mode) {
	case 'update':
		// check not null
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $dbp where `profile` = '" . $dbh->toDBString($name) . "' and `id` != $id";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}

		// DB
		$sql = "update $dbp set `profile`='" . $dbh->toDBString($name) . "', `description` = '" . $dbh->toDBString($description) . "' where `id` = $id";
		$sth = $dbh->query($sql);
		
		$sql = "delete from $dbpv where `profile` = $id";
		$sth = $dbh->query($sql);
		
		sql_features($dbh, $id);
		
		break;
	case 'insert':
		// check not null
		if (trim($name) == '') {
			$errors[] = BizResources::localize("ERR_NOT_EMPTY");
			$mode = 'error';
			break;
		}

		// check duplicates
		$sql = "select `id` from $dbp where `profile` = '" . $dbh->toDBString($name) . "'";
		$sth = $dbh->query($sql);
		$row = $dbh->fetch($sth);
		if ($row) {
			$errors[] = BizResources::localize("ERR_DUPLICATE_NAME");
			$mode = 'error';
			break;
		}

		// DB
		$sql = "insert INTO $dbp (`profile`, `description`, `code`) VALUES ('" . $dbh->toDBString($name) . "', '" . $dbh->toDBString($description) . "', 0)";
		$sql = $dbh->autoincrement($sql);
		$sth = $dbh->query($sql);
		if (!$id) $id = $dbh->newid($dbp,true);
		
		sql_features($dbh, $id);
		break;
	case 'delete':
		if ($id) {
			$sql = "delete from $dbp where `id` = $id";
			$sth = $dbh->query($sql);

			// cascading delete: profilefeatures
			$sql = "delete from $dbpv where `profile` = $id";
			$sth = $dbh->query($sql);

			// cascading delete: authorizations
			$sql = "delete from $dba where `profile` = $id";
			$sth = $dbh->query($sql);
		}
		break;
}
// delete: back to overview
if ($mode == 'delete' || $mode == "insert" || $mode == "update") {
	header("Location:profiles.php");
	exit();
}
// generate upper part (edit fields)
if ($mode == 'error') {
	$row = array ('profile' => $name, 'description' => $description);
} elseif ($mode != "new") {
	$sql = "select * from $dbp where `id` = $id";
	$sth = $dbh->query($sql);
	$row = $dbh->fetch($sth);
} else {
	$row = array ('profile' => '', 'description' => '');
}
$txt = HtmlDocument::loadTemplate( 'hpprofiles.htm' );

// error handling
$err = '';
foreach ($errors as $error) {
	$err .= formvar($error) . '<br/>';
}
$txt = str_replace('<!--ERROR-->', $err, $txt);

// fields
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
if ($mode == 'error') {
	$features = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
	foreach ($features as $fid => $feature) {
		if ($_REQUEST['checkobj'][$fid]) $ft[$fid] = 'Yes';
	}
} else if ($mode != 'new') {
	$sql = "select * from $dbpv where `profile` = $id";
	$sth = $dbh->query($sql);
	while( ($row = $dbh->fetch($sth) ) ) {
		$ft[$row['feature']] = $row['value'];
	}
} else {
	$features = BizAccessFeatureProfiles::getAllFeaturesAccessProfiles();
	foreach ($features as $fid => $feature) {
		$ft[$fid] = isset($feature->Default) ? $feature->Default : 'Yes';
	}
}

// generate lower part (3x)
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
		/*'FEATURE_IMAGES'      => BizAccessFeatureProfiles::getImagesAccessProfiles(), */
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

// generate total page
$txt = str_replace('<!--DETAILS-->', $detailtxt, $txt);

//set focus to first field
$txt .= '<script language="javascript">document.forms[0].profile.focus();</script>';

print HtmlDocument::buildDocument($txt);

function sql_features($dbh, $id)
{
	// handle insert of features
	$dbpv = $dbh->tablename('profilefeatures');

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
			$sql = "INSERT INTO $dbpv (`profile`, `feature`, `value`) ".
					"VALUES ($id, $fid, '".$dbh->toDBString($value)."')";
			$sql = $dbh->autoincrement($sql);
			$dbh->query($sql);
		}
	}
}