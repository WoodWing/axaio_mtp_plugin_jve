<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
require_once BASEDIR.'/server/dbclasses/DBPublication.class.php';

checkSecure('admin');

// collect standard (built-in) property definitions
$allprops      = BizProperty::getPropertyInfos();
$staticprops   = BizProperty::intersectProperties( BizProperty::getStaticPropIds(),  $allprops );
$dynamicprops  = BizProperty::intersectProperties( BizProperty::getDynamicPropIds(), $allprops );
$xmpprops      = BizProperty::intersectProperties( BizProperty::getXmpPropIds(),     $allprops );
//asort($staticprops); // no sort
asort($dynamicprops);
asort($xmpprops);

$objmap = getObjectTypeMap();
$actmap = getPropertyViewMap();

// database stuff
$dbh = DBDriverFactory::gen();

// determine incoming mode
$publ = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$objtype = isset($_REQUEST['objtype']) ? $_REQUEST['objtype'] : '';
$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$warning = isset($_REQUEST['warning']) ? (bool)$_REQUEST['warning'] : false;
if (!$view) $view = 'All';

// Validate data retrieved from form (XSS attacks)
if( !array_key_exists($objtype, $objmap) ) $objtype = '';

$dum = '';
cookie('properties', !(isset($_REQUEST['isform']) && $_REQUEST['isform']), $publ, $objtype, $view, $dum, $dum, $dum, $dum);

// Re-validate data retrieved from cookie! (XSS attacks)
$publ = intval($publ); 
if( !array_key_exists($objtype, $objmap) ) $objtype = '';

$alert = '';
if($warning === true){
	$alert = 'alert("'.BizResources::localize("ERR_INVALID_OPERATION").'");';
}

$sAll = BizResources::localize("LIS_ALL");
$objmap = getObjectTypeMap();
$actmap = getPropertyViewMap();

// generate upperpart
$txt = HtmlDocument::loadTemplate( 'properties.htm' );
$txt = str_replace("<!--ERR_INVALID_OPERATION-->", $alert, $txt );

// Show results in a list of hyperlinks to select the Pub/ObjType combos when user clicks on them...
$rows = DBProperty::getAdminUIPropertiesOfObjects();
$pubobjtypelist = "";
if( $rows ) foreach( $rows as $row ) {
	$disp_pub = $row['publication'];
	if( !$disp_pub ) {
		$disp_pub = '<'.$sAll.'>'; // set to "<all>" when not defined
	}
	$disp_objtype = trim($row['objtype']);
	if( $disp_objtype ) {
		$disp_objtype = $objmap[$disp_objtype];
	} else {
		$disp_objtype = '<'.$sAll.'>'; // set to "<all>" when not defined
	}
	$pubobjtypelist .= '<a href="javascript:SetCombos(\''.$row['pubid'].'\',\''.trim($row['objtype']).'\');">'
					.formvar($disp_pub).' - '.formvar($disp_objtype).'</a><br/>';
}
$txt = str_replace("<!--PUB_OBJTYPE_LIST-->", $pubobjtypelist, $txt );

$sth = DBPublication::listPublicationsByNameId();
$combo = inputvar( 'isform', '1', 'hidden' );
$combo .= '<select name="publ" onchange="submit();">';
$combo .= '<option value="0">&lt;'.$sAll.'&gt;</option>';
while (($row = $dbh->fetch($sth))) {
	$selected = ($row['id'] == $publ) ? 'selected="selected"' : '';
	$combo .= '<option value="'.$row['id'].'" '.$selected.'>'.formvar($row['publication']).'</option>';
}
$combo .= '</select>';
$txt = str_replace('<!--COMBO:PUBL-->', $combo, $txt );

$combo = '<select name="objtype" onchange="submit();">';
$combo .= '<option value="">&lt;'.$sAll.'&gt;</option>';
$sel = $objtype;
foreach ($objmap as $k => $sDisplayType) {
	$selected = ($k == $sel) ? 'selected="selected"' : '';
	$combo .= '<option value="'.$k.'" '.$selected.'>'.formvar($sDisplayType).'</option>';
}
$combo .= '</select>';
$txt = str_replace('<!--COMBO:TYPE-->', $combo, $txt );

$combo = '<select name="view" onchange="submit();">';
$sel = $view;
foreach ($actmap as $k => $sPropertyView) {
	$selected = ($k == $sel) ? 'selected="selected"' : '';
	$combo .= '<option value="'.$k.'" '.$selected.'>'.formvar($sPropertyView).'</option>';
}
$combo .= '</select>';
$txt = str_replace('<!--COMBO:VIEW-->', $combo, $txt );

// lower part
$dettxt = HtmlDocument::loadTemplate( 'propertiesdet.htm' );
$details = '';

$props = DBProperty::getObjectPropertiesByObjectType( $publ, $objtype );
// generate based on viewmode
if ($view == 'All' || $view == 'Static') {
	$details .= '<tr><td colspan="4">&nbsp;</td></tr><tr><td colspan="4"><b>'
		.BizResources::localize('OBJ_STATIC_PROPERTIES').'</b></td></tr>';
	foreach ($staticprops as $p => $sDisplayProperty) {
		$pr = getprop($props, $p);
		if ($pr) {
			$details .= showprop($pr, $sDisplayProperty->DisplayName, $publ, $objtype);
		} else {
			$details .= showprop($p, $sDisplayProperty->DisplayName, $publ, $objtype);
		}
	}
}

if ($view == 'All' || $view == 'Dynamic') {
	$details .= '<tr><td colspan="4">&nbsp;</td></tr><tr><td colspan="4"><b>'
		.BizResources::localize('OBJ_DYNAMIC_PROPERTIES').'</b></td></tr>';
	foreach ($dynamicprops as $p => $sDisplayProperty) {
		$pr = getprop($props, $p);
		if ($pr) {
			$details .= showprop($pr, $sDisplayProperty->DisplayName, $publ, $objtype);
		} else {
			$details .= showprop($p, $sDisplayProperty->DisplayName, $publ, $objtype);
		}
	}
}

if ($view == 'All' || $view == 'XMP') {
	$details .= '<tr><td colspan="4">&nbsp;</td></tr><tr><td colspan="4"><b>'
		.BizResources::localize('LBL_XMP_PROP').'</b></td></tr>';
	foreach ($xmpprops as $p => $sDisplayProperty) {
		$pr = getprop($props, $p);
		if ($pr) {
			$details .= showprop($pr, $sDisplayProperty->DisplayName, $publ, $objtype);
		} else {
			$details .= showprop($p, $sDisplayProperty->DisplayName, $publ, $objtype);
		}
	}
}
if ($view == 'All' || $view == 'Custom') {
	$details .= '<tr><td colspan="4">&nbsp;</td></tr><tr><td colspan="4"><b>'
		.BizResources::localize('LBL_CUSTOM_PROP').'</b></td></tr>';
	$pr = getcustprops($props);

	if ($pr) foreach ($pr as $p){
		if( $p['adminui'] == 'on' ) { // Only display on the UI when it is intended.
			if(strlen(trim($p['dispname'])) == 0 || $p['dispname'] == null){
				if (BizProperty::isCustomPropertyName($p['name'])){
					$sDisplay = mb_substr($p['name'], 2);
				}
			}else{
				$sDisplay = $p['dispname'];
			}
			$details .= showprop($p, $sDisplay, $publ, $objtype );
		}		
	}
}
if ($view == 'Category') {
	$lookupProps = $xmpprops + $dynamicprops;
	if ($props) foreach ($props as $p) {
		if( array_key_exists( $p['name'], $lookupProps ) ) {
			$sDisplay = $lookupProps[$p['name']]->DisplayName;
		} else {
			$sDisplay = '';
		}
		$details .= showprop($p, $sDisplay, $publ, $objtype);
	}
}

// generate lowerpart
$dettxt = str_replace("<!--ROWS-->", $details, $dettxt );

// generate total page
$txt = str_replace("<!--DETAILS-->", $dettxt, $txt );

// Append the "Add Custom Property" link
$addCust = '<tr><td><a href="propertydet.php?publ=' .$publ. '&objtype=' .urlencode($objtype). '">';
$addCust .= '<img src="../../config/images/add_16.gif" title="' . BizResources::localize('ACT_ADD_CUSTOM_PROPERTY' ).'"/> ' . BizResources::localize('ACT_ADD_CUSTOM_PROPERTY' ). '</a></td></tr>';


$txt = str_replace('<!--VAR:ADD_CUSTUM_PROP_LNK-->', $addCust, $txt);
print HtmlDocument::buildDocument($txt);

function getprop($rows, $name)
{
	if ($rows) foreach ($rows as $row) {
		if ($row['name'] == $name) {
			return $row;
		}
	}

	return null;
}

function getcustprops($rows)
{
	$ret = array();
	if ($rows) foreach ($rows as $row) {
		if (BizProperty::isCustomPropertyName($row['name'])) {
			$ret[] = $row;
		}
	}
	return $ret;
}

function showprop($row, $sDisplayProperty, $publ, $objtype)
{
	if (!is_array($row)) {
		$dum = array();
		$dum['name'] = $row;
		$row = $dum;
	}
	$txt = '';
	$name = $row['name'];
	if (BizProperty::isCustomPropertyName($name)) {
		$name = formvar(substr($name,2));
	} else {
		$name = '<!---->'.formvar($name);	// prefix for translation based on ui-terms
	}
	$cat = isset($row['category']) ? $row['category'] : '';
	$type = isset($row['type']) ? $row['type'] : '';
	// Show built-in display names grey-ed out and customized display names in black -> You can easily see the customizations.
	$displ = empty( $row['dispname'] ) ? '<font color="#aaaaaa">'.formvar($sDisplayProperty).'</font>' : formvar($row['dispname']);
	$txt .= "<tr><td><a href='propertydet.php?publ=$publ&objtype=".urlencode($objtype)."&name=".urlencode($row['name'])."'>".$name."</a></td>";
	$txt .= '<td>'.$displ.'</td><td>'.formvar($cat).'</td><td>'.formvar($type).'</td></tr>';
	return $txt;
}
?>