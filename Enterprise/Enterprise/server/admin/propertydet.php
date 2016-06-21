<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/bizclasses/BizProperty.class.php';
require_once BASEDIR.'/server/bizclasses/BizCustomField.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';

checkSecure('admin');

$staticProps   = BizProperty::getStaticPropIds();
$dynamicProps  = BizProperty::getDynamicPropIds();
$xmpProps      = BizProperty::getXmpPropIds();

// determine incoming mode
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$publ = isset($_REQUEST['publ']) ? intval($_REQUEST['publ']) : 0;
$name = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
$objType = isset($_REQUEST['objtype']) ? $_REQUEST['objtype'] : '';
$nameField = isset($_REQUEST['namefield']) ? $_REQUEST['namefield'] : '';
$oldNameField = isset($_REQUEST['oldnamefield']) ? $_REQUEST['oldnamefield'] : '';

if( !$name ) { $name = $nameField ? $nameField : $oldNameField; }

$fmode = 'custom';
if( in_array($name, $staticProps) ) $fmode = 'static';
if( in_array($name, $dynamicProps) ) $fmode = 'dynamic';
if( in_array($name, $xmpProps) ) $fmode = 'xmp';

if ($fmode == 'custom') {
	$name = strtoupper($name);
}

// Determine operation mode
if( isset($_REQUEST['delete']) && $_REQUEST['delete'] ) {
	$mode = 'delete';
} else if( isset($_REQUEST['insert']) && $_REQUEST['insert'] ) {
	$mode = 'insert';
} else if( isset($_REQUEST['update']) && $_REQUEST['update'] ) {
	$mode = $id > 0 ? 'update' : 'insert';
} else {
	$mode = 'init';
}

// Determine and fix name
$name = trim($name);
if( $name && isset($_REQUEST["customname"]) && $_REQUEST["customname"] ) {
	$name = "C_$name";
}

$errorString = '';

// handle form params
$dispName = isset($_REQUEST["dispname"]) ? $_REQUEST["dispname"] : '';
if (empty($dispName)) {
	if( isset($_REQUEST["customname"]) && $_REQUEST["customname"] ) {
		$dispName = substr( $name, 2 ); // remove the c_ prefix
	} else {
		$dispName = $name;
	}
}

$cat = isset($_REQUEST['cat']) ? $_REQUEST['cat'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
$defl = isset($_REQUEST['defl']) ? trim($_REQUEST['defl']) : '';
$min = isset($_REQUEST['min']) ? $_REQUEST['min'] : '';
$max = isset($_REQUEST['max']) ? $_REQUEST['max'] : '';
$list = isset($_REQUEST['list']) ? $_REQUEST['list'] : '';
$len = isset($_REQUEST['len']) ? intval($_REQUEST['len']) : 0;

$list = str_replace("\n", ",", $list);
$list = str_replace("\r", '', $list);

// handle update request
if( ($mode == "insert" || $mode == "update") ) {
	if (!BizProperty::isCustomPropertyName($name)){
		$type = BizProperty::getStandardPropertyType($name);
	}
	$values = array(
		'publication' => $publ,
		'objtype' => $objType,
		'name' => $name,
		'dispname' => $dispName,
		'category' => $cat,
		'type' => $type,
		'defaultvalue' => $defl,
		'valuelist' => $list,
		'minvalue' => $min,
		'maxvalue' => $max,
		'maxlen' => $len,
		'dbupdated' => 0
		);

	try {
		$foundDbType = BizProperty::getCustomPropType( $id, $type, $name );
	
		if( $mode == 'update' ) {
			BizProperty::updateProperty( $id, $values );
		} else if( $mode == 'insert' ) {
			// handle autoincrement for non-mysql
			$id = BizProperty::addProperty( $values );
		}
	} catch ( BizException $e ) {
		$errorString = $e->getMessage();
	}

	if ( strlen($errorString) == 0) {
		require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
		if ( BizProperty::isCustomPropertyName($name) ) {
			try {
				if( !$foundDbType ) { // BZ#33742 - Insert/Update DB field only when type is not found in DB
					if( $mode == 'update' ) {
						BizCustomField::updateFieldAtModel( 'objects', $name, $type );
					} else if( $mode == 'insert' ) {
						BizCustomField::insertFieldAtModel( 'objects', $name, $type );
					}
				}
				DBProperty::updateRow( 'properties', array('dbupdated' => 1), '`id` = ?', array( $id ));
			} catch( BizException $e ) {
				$errorString = $e->getMessage();
			}
		} else {
			DBProperty::updateRow( 'properties', array('dbupdated' => 1), '`id` = ?', array( $id ));
		}
	}
}

if( $mode == 'delete' ) {
	if( $id > 0 ) {
		try {
			if( BizProperty::isCustomPropertyName($name) ) {
				require_once BASEDIR . '/server/dbclasses/DBActionproperty.class.php';
				DBActionproperty::deletePropFromActionProperties( $name, $publ );

				$foundDbType = BizProperty::getCustomPropType( $id, $type, $name );
				if( !$foundDbType ) { // BZ#33742 - Delete DB field only when type is not found in DB
					BizCustomField::deleteFieldAtModel( 'objects', $name );
				} else { // Reset custom property value when the property removed from particular publication
					require_once BASEDIR . '/server/dbclasses/DBObject.class.php';
					$updateValues = array( $name => '' );
					$where = '`publication` = ?';
					$params = array( $publ );

					$updateResult = DBObject::updateRow( 'objects', $updateValues, $where, $params );
					if( $updateResult ) {
						DBObject::updateRow( 'deletedobjects', $updateValues, $where, $params );
					}
				}
			}
			BizProperty::deleteProperty( $id );
		} catch( BizException $e ) {
			$errorString = $e->getMessage();
	}
}
}

// redirect if necessary
if( !$errorString && ($mode == "delete" || $mode == "insert" || $mode == "update") ) {
	header("Location: properties.php");
	exit;
}

// choose template
switch( $fmode ) {
	case 'static':
		$template = 'propertydetstatic.htm';
		break;
	case 'dynamic':
	case 'xmp':
		$template = 'propertydetdynamic.htm';
		break;
	case 'custom':
		$template = 'propertydetcustom.htm';
		break;
}
$txt = HtmlDocument::loadTemplate( $template );

// get current record (if any)    (BZ#5707: fill default to roundtrip data in case of error)
$defRow = array( 'id' => $id, 'name' => $name, 'dispname' => $dispName, 'category' => $cat,
				'type' => $type, 'defaultvalue' => $defl, 'valuelist' => $list,
				'minvalue' => $min, 'maxvalue' => $max, 'maxlen' => $len ); 
$row = $defRow;

$readOnlyProp = '';
$displayNone = '';
if( ($mode == 'update' || $mode == 'init') && empty($errorString) ) {
	require_once BASEDIR.'/server/dbclasses/DBProperty.class.php';
	$where = '`publication` = ? AND `objtype` = ? AND `name` = ? '; 
	$params = array( $publ, $objType, $name);
	$row = DBProperty::getRow('properties', $where, '*', $params); 

	if( !$row ) {
		$row = $defRow;
		unset($row['dispname']); // BZ#16570
	} else {
		if( $row['serverplugin'] ) {
			$readOnlyProp = 'disabled="disabled"';
			$displayNone = 'display:none';
		}
	}
}

// >>> BZ#12576: Convert ISO date to user input date
if( isset($row['type']) && ($row['type'] == 'datetime' || $row['type'] == 'date') && $row['defaultvalue'] ) {
	$temp = DateTimeFunctions::iso2date($row['defaultvalue']);
	if( $temp ) {
		$row['defaultvalue'] = $temp;
	}
}
// <<<

// replace all vars in template
if( $fmode == 'custom' ) {
	$myname = $row? substr($row['name'],2) : '';
	//if ($mode == 'insert' || empty($myname) || $mode == 'errorname' ) {
	if( $mode == 'insert' || empty($myname) ) {
		$repl = '<input name="namefield" value="'.formvar($myname).'" '.$readOnlyProp.'/>';
		$repl .= inputvar( 'customname', '1', 'hidden' );
	} else {
		$repl = '<input name="namefield" value="'.formvar($myname).'" disabled="disabled"/>';
		$repl .= inputvar( 'oldnamefield', $myname, 'hidden' );
		$repl .= inputvar( 'customname', '1', 'hidden' );
	}
} else {
	$repl = '<!---->'.formvar($row['name']);
	$repl .= inputvar( 'name', $row['name'], 'hidden' );
}
if( $mode == "insert" ) {
	$repl .= inputvar( 'insert', '1', 'hidden' ); // remember we are inserting
}
// add some other stuff
$repl .= inputvar( 'id', isset($row['id']) ? $row['id'] : '', 'hidden' );
$repl .= inputvar( 'publ', $publ, 'hidden' );
$repl .= inputvar( 'objtype', $objType, 'hidden' );

$txt = str_replace('<!--VAR:NAME-->', $repl, $txt);
$txt = str_replace('<!--VAR:DISPNAME-->', '<input maxlength="200" name="dispname" value="'.(isset($row['dispname']) ? formvar($row['dispname']) : '').'" '.$readOnlyProp.'/>', $txt);
$txt = str_replace('<!--VAR:CATEGORY-->', '<input name="cat" value="'.(isset($row['category']) ? formvar($row['category']) : '').'" '.$readOnlyProp.'/>', $txt);
$txt = str_replace('<!--VAR:DEFAULT-->', '<input name="defl" value="'.(isset($row['defaultvalue']) ? formvar($row['defaultvalue']) : '').'" '.$readOnlyProp.'/>', $txt);
$txt = str_replace('<!--VAR:MIN-->', '<input name="min" value="'.(isset($row['minvalue']) ? formvar($row['minvalue']) : '').'" '.$readOnlyProp.'/>', $txt);
$txt = str_replace('<!--VAR:MAX-->', '<input name="max" value="'.(isset($row['maxvalue']) ? formvar($row['maxvalue']) : '').'" '.$readOnlyProp.'/>', $txt);
$txt = str_replace('<!--VAR:LEN-->', '<input name="len" value="'.(isset($row['maxlen']) ? formvar($row['maxlen']) : '').'" '.$readOnlyProp.'/>', $txt);
$txt = str_replace('<!--VAR:UPDATEDELETE_BUTTONS-->', $displayNone, $txt);

// combo:type
$map = getPropertyTypeMap();

if( isset($row['id']) && $row['id'] ) { // do not allow to change type once property exists, or else you'll risk db errors
	$combo = inputvar( 'type', $row['type'], 'hidden' );
	$combo .= '<select disabled="">';
} else {
	$combo = '<select name="type">';
}
$sel = isset($row['type']) ? $row['type'] : '';
foreach( $map as $k => $sDisplayType ) {
	// Exclude property types a user should not be able to set, but we need in our internal mapping.
	if( !in_array($k, array('file', 'articlecomponentselector', 'fileselector', 'articlecomponent')) ) {
		$selected = ($k == $sel) ? 'selected="selected"' : '';
		$combo .= '<option value="'.$k.'" '.$selected.'>'.formvar($sDisplayType).'</option>';
	}
}
$combo .= "</select>";
$txt = str_replace("<!--VAR:TYPE-->", $combo, $txt );

// BZ#27672 -  When ',' is 1st char of valueslist,  add ',' in front, textarea needs 2 "\n" in order to display 1st empty line
$valueList = isset($row['valuelist']) ? $row['valuelist'] : '';
$pos = strpos( $valueList,  ',' );
if( $pos === 0 ) { 
	$valueList = ','.$valueList;
}
// valuelist
$list = str_replace(",","\n", $valueList);
$txt = str_replace("<!--VAR:LIST-->", '<textarea cols="20" rows="10" name="list" '.$readOnlyProp .'>'.formvar($list).'</textarea>', $txt);
$txt = str_replace("<!--ERROR-->", formvar($errorString), $txt);

// generate total page
print HtmlDocument::buildDocument($txt);
