<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure( 'publadmin' );
$tpl = HtmlDocument::loadTemplate( 'removebydate.htm' );

$inPub = isset($_POST['Publication']) ? intval($_POST['Publication']) : 0;
$inCategory = isset($_POST['Category']) ? intval($_POST['Category']) : 0;
$inArticle = isset($_REQUEST['Article']) ? (bool)$_REQUEST['Article'] : false;
$inImage = isset($_REQUEST['Image']) ? (bool)$_REQUEST['Image'] : false;
$inVideo = isset($_REQUEST['Video']) ? (bool)$_REQUEST['Video'] : false;
$inAudio = isset($_REQUEST['Audio']) ? (bool)$_REQUEST['Audio'] : false;
if (!isset($_POST['Publication'])) { // are we here the first time?
	$inArticle = true;
	$inImage = true;
}

$date = isset($_POST['Date']) ? $_POST['Date'] : '';
$show = isset($_REQUEST['show']) ? (bool)$_REQUEST['show'] : false;
$directdel = isset($_REQUEST['directdel']) ? (bool)$_REQUEST['directdel'] : false;
$del = isset($_POST['del']) ? (bool)$_POST['del'] : false;

$dum = null;
cookie("removeByDate", $inPub === 0, $inPub, $inCategory, $inArticle, $dum, $inImage, $inVideo, $inAudio);

// Re-validate data retrieved from cookie! (XSS attacks)
$inPub = intval($inPub);
$inCategory = intval($inCategory);
$inArticle = (bool)$inArticle;
$inImage = (bool)$inImage;
$inVideo = (bool)$inVideo;
$inAudio = (bool)$inAudio;

$permanent = false;
$txt_deleted = "";

if($directdel === true) {
	if ($inPub && $inCategory) {
		$normdate = '';
		$txt_deleted = ValidateDate( $date, $normdate );
		if( !$txt_deleted ) {
			if (!$dbh = DBDriverFactory::gen()) die ("database error");
			$mySQL = RemoveByDateSQL( $inPub, $inCategory, $inArticle, $inImage, $inVideo, $inAudio, $normdate );
			$sth = $dbh->query ($mySQL);
			if ($sth) {
				$i=0;
				$ids = array();
				while (($result=$dbh->fetch($sth))) {
					$ids[$i]=$result['id'];
					$i++;
				}
				try {
					require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
					$service = new WflDeleteObjectsService();
					$request = new WflDeleteObjectsRequest();
					$request->Ticket = BizSession::getTicket();
					$request->IDs = $ids;
					$request->Permanent = $permanent;
					$service->execute( $request );

					$succeed = true;
					$sErrorMessage = BizResources::localize("ERR_SUCCESS_DELETE");
					$txt_deleted = "<font class='text'>" . $sErrorMessage . "</font><ul>";
					$txt_deleted .= "<li>$i ".BizResources::localize('OBJECTS').' '.BizResources::localize('DELETED')."</li>";
					$txt_deleted .= "</ul></font>";
				} catch( BizException $e ) {
					$succeed = false;
					$message = $e->getMessage();
					$sErrorMessage = BizResources::localize("ERR_DELETE");
					$txt_deleted = "<font class='text'>" . $sErrorMessage . "</font><ul>";
					$txt_deleted .= "<li></li>";
					$txt_deleted .= "</ul></font>";
				}
			}
		}
	}
}

if ($del === true) {
	$sErrorMessage = BizResources::localize('ERR_SUCCESS_DELETE');
	$txt_deleted = '<font class="text">' . $sErrorMessage . '<ul>';
	$delete = array();
	$amount = 0;
	$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
	for ($i = 0; $i < $amount; $i++) {
		if (isset($_POST['checkbox'.$i])) {
			$delete[] = $_POST['objectID'.$i]; // can be alien id (=string!)
			$txt_deleted .= '<li>'.formvar($_POST['name'.$i]).'</li>';
		}
	}
	$txt_deleted .= '</ul></font>';

	try {
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$service = new WflDeleteObjectsService();
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = $delete;
		$request->Permanent = $permanent;
		$service->execute( $request );
	} catch( BizException $e ) {
		$message = $e->getMessage();
		$sErrorMessage = BizResources::localize('ERR_DELETE');
		$txt_deleted = '<font class="text">' . $sErrorMessage . '<ul>';
		$delete = array();
		$amount = 0;
		$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
		for ($i = 0; $i < $amount; $i++) {
			if (isset($_POST['checkbox'.$i])) {
				$delete[] = $_POST['objectID'.$i]; // can be alien id (=string!)
				$txt_deleted .= '<li>'.formvar($_POST['name'.$i]).'</li>';
			}
		}
		$txt_deleted .= '</ul></font>';
	}
}

//
/////////////////////// *** Publication combo *** ///////////////////////
//

$comboBoxPub = '<select name="Publication" style="width:150px" onchange="submit();">';
$comboBoxPub .= "<option></option>";
require_once BASEDIR.'/server/bizclasses/BizPublication.class.php';
$pubs = BizPublication::getPublications( BizSession::getShortUserName() );
foreach( $pubs as $pub ) {
	if( $pub->Id != $inPub ) {
		$comboBoxPub .= '<option value="'.$pub->Id.'">'.formvar($pub->Name).'</option>';
	} else {
		$comboBoxPub .= '<option value="'.$pub->Id.'" selected="selected">'.formvar($pub->Name).'</option>';
	}
}
$comboBoxPub .= '</select>';
$tpl = str_replace ('<!--COMBOPUB-->',$comboBoxPub, $tpl);

//
/////////////////////// *** Category combo *** ///////////////////////
//
$comboBoxCat = '<select name="Category" style="width:150px" onchange="submit();">';
if ($inPub > 0) {
	global $globAuth;
	$categoryInfos = BizPublication::getCategoryInfos( $globAuth->getCachedRights(), array('id'=>$inPub) );
	$comboBoxCat .= '<option value="-1">'.BizResources::localize("ACT_SELECT_ALL").'</option>';
	foreach( $categoryInfos as $categoryInfo ) {
		if( $categoryInfo->Id != $inCategory ) {
			$comboBoxCat .= '<option value="'.$categoryInfo->Id.'">'.formvar($categoryInfo->Name).'</option>';
		} else {
			$comboBoxCat .= '<option value="'.$categoryInfo->Id.'" selected="selected">'.formvar($categoryInfo->Name).'</option>';
			//$overrulePub = isset($iss->OverrulePublication) ? (trim($iss->OverrulePublication) != '') : false;
		}
	}
} else {
	$comboBoxCat .= '<option value="0"></option>';
}
$comboBoxCat .= '</select>';
$tpl = str_replace ('<!--COMBOCAT-->',$comboBoxCat, $tpl);

//
/////////////////////// *** Date *** ///////////////////////
//
$calgif = '../../config/images/cal_16.gif';
$langpatdate = LANGPATDATE;
$dateformat = $langpatdate{0} . $langpatdate{2} . $langpatdate{4};
$datesep = $langpatdate{1};
$datefieldtxt  = '<input name="Date" align="left" width="50px" value="'.formvar($date).'"></input>';
$datefieldtxt .= "<a href=\"javascript:displayDatePicker('Date',false,'$dateformat','$datesep')\"><img src='$calgif'/></a>";
$tpl = str_replace ('<!--DATE-->',$datefieldtxt, $tpl);

//
/////////////////////// *** Checkboxes *** ///////////////////////
//
$selected = $inArticle ? 'checked="checked"' : '';
$chkBox = '<input name="Article" type="checkbox" onclick="submit();" '.$selected .' />';
$tpl = str_replace ('<!--CHECKARTICLE-->',$chkBox, $tpl);

$selected = $inImage ? 'checked="checked"' : '';
$chkBox = '<input name="Image" type="checkbox" onclick="submit();" '.$selected .' />';
$tpl = str_replace ('<!--CHECKIMAGE-->',$chkBox, $tpl);

$selected = $inVideo ? 'checked="checked"' : '';
$chkBox = '<input name="Video" type="checkbox" onclick="submit();" '.$selected .' />';
$tpl = str_replace ('<!--CHECKVIDEO-->',$chkBox, $tpl);

$selected = $inAudio ? 'checked="checked"' : '';
$chkBox = '<input name="Audio" type="checkbox" onclick="submit();" '.$selected .' />';
$tpl = str_replace ('<!--CHECKAUDIO-->',$chkBox, $tpl);

//
/////////////////////// *** Showfiles *** ///////////////////////
//

$objectmap = getObjectTypeMap();
if( ($inPub > 0 || $inPub = -1) && ($inCategory > 0 || $inCategory = -1) ) { // -1 means <All> 
	$dbDriver = DBDriverFactory::gen();
	if( $show === false ) {
		$txt = ValidateDate( $date, $normdate );
		if( !$txt ) {
			$sql = RemoveByDateSQL( $inPub, $inCategory, $inArticle, $inImage, $inVideo, $inAudio, $normdate, true ); // just count
			$h = $dbDriver->query($sql);
			if( $h ) {
				$number = '';
				while (($row = $dbDriver->fetch($h))) {
					$number.= '<br/>'.$row['total']. ' '.$objectmap[$row['type']];
				}
				if( $number ) {
					$sErrorMessage1 = BizResources::localize('ERR_DELETE_SECTION');
					$txt = BizResources::localize('ACT_YOU_ARE_ABOUT_TO_DELETE_FOLLOWING_OBJECTS').' '.$number;
					$txt .= '<tr>
							<td colspan="7">
								<a href="javascript:setval();javascript:document.content.submit();">
									<img src="../../config/images/prefs_16.gif" border="0" title="'.BizResources::localize('ACT_SHOW').'"/>
									'.BizResources::localize('ACT_SHOW').'
								</a>
								<a href="javascript:directdelete(\''. $sErrorMessage1 . '\');">
									<img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_DELETE').'"/>
									'.BizResources::localize('ACT_DELETE').'
								</a>'.
								inputvar( 'show', '', 'hidden' ).
								inputvar( 'del', '', 'hidden' ).
								inputvar( 'directdel', '', 'hidden' ).
							'</td>
							</tr></table>';
				} else {
					if( !$directdel && !$del ) { // Not to show message, after deletion
						$txt = BizResources::localize( 'ERR_NO_SUBJECTS_FOUND', true, array( '{OBJECTS}' ) );
					}
				}
			}
		}
		$txt = $txt_deleted . $txt;
		$tpl = str_replace ('<!--DELETEINFO-->', $txt, $tpl);
	}
	else { // $show
		$txt = ValidateDate( $date, $normdate );
		if( !$txt ) {
			$sql = RemoveByDateSQL( $inPub, $inCategory, $inArticle, $inImage, $inVideo, $inAudio, $normdate );
			$h = $dbDriver->query($sql);
			$IDs = array();
			if( $h ) {
				while (($row = $dbDriver->fetch($h))) {
					$IDs[] = $row['id'];
				}
				$txt = showfiles( $IDs );
			}
		}

		$txt = $txt_deleted . $txt;
		$tpl = str_replace ('<!--CONTENT-->', $txt, $tpl);
	}
}

function ValidateDate( $date, &$normdate )
{
	$txt = '';
	if( $date ) {
		$r = array();
		preg_match('/([0-9]+)-([0-9]+)-([0-9]+)/i', $date, $r );
		if( strlen( $r[3] ) == 4 ) {
			require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
			$validDate = DateTimeFunctions::validDate( $date );
			if( $validDate ) {
				$normdate = $validDate;
			} else {
				$txt = '<font color="#ff0000">'.BizResources::localize('INVALID_DATE').'</font>';
			}
		} else {
			$txt = '<font color="#ff0000">'.BizResources::localize('INVALID_YEAR').'</font>';
		}
	} else {
		$txt = BizResources::localize('SPECIFY_DATE');
	}
	return $txt;
}

function RemoveByDateSQL( $inPub, $inCategory, $inArticle, $inImage, $inVideo, $inAudio, $normdate, $justCount = false )
{
	$dbDriver = DBDriverFactory::gen();
	$or = '';
	$typeStr = "(";
	if( $inArticle ) { $typeStr.=" o.`type` = 'Article' "; $or = ' OR '; }
	if( $inImage ) { $typeStr.=" $or o.`type` = 'Image' "; $or = ' OR '; }
	if( $inVideo ) { $typeStr.=" $or o.`type` = 'Video' "; $or = ' OR '; }
	if( $inAudio ) { $typeStr.=" $or o.`type` = 'Audio' "; }
	$typeStr .= ")";

	if( $justCount ) {
		$sql = "SELECT count(*) as `total` , o.`type` ";
	} else {
		$sql = "SELECT o.`id`, o.`name` ";
	}
	$sql .= "from ".$dbDriver->tablename("objects")." o "
		."left join ".$dbDriver->tablename("objectrelations")." rel on o.`id` = rel.`child` "
		."where rel.`child` is null and $typeStr "
		//."where $typeStr "
		."and o.`publication` = $inPub "
		."and o.`modified` <= '" . $dbDriver->toDBString($normdate) . "' ";
	if ($inCategory > 0) {
		$sql .= 'AND o.`section` = '.$inCategory.' ';
	}
	if( $justCount ) {
		$sql .= "group by o.`type` ";
	}
	return $sql;
}

function showfiles( $IDs )
{
	require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
	try {
		$request = new WflGetObjectsRequest();
		$request->Ticket = BizSession::getTicket();
		$request->IDs = $IDs;
		$request->Lock = false;
		$request->Rendition = 'none';
		$service = new WflGetObjectsService();
		$resp = $service->execute( $request );
		$objects = $resp->Objects;
	} catch( BizException $e ) {
		return $e->getMessage();
	}

	// get some information about images
	$elements = array();
	$t = 0;
	foreach ($objects as $object) {
		// get id and state info from layout
		$id = $object->MetaData->BasicMetaData->ID;
		$name = $object->MetaData->BasicMetaData->Name;
		$stinfo =  $object->MetaData->WorkflowMetaData->State->Id;
		$created =  $object->MetaData->WorkflowMetaData->Created;
		$modified =  $object->MetaData->WorkflowMetaData->Modified;
		$lockedby =  $object->MetaData->WorkflowMetaData->LockedBy;
		$size = $object->MetaData->ContentMetaData->FileSize;
		$type = $object->MetaData->BasicMetaData->Type;
		$elements[$t] = array('id' => $id, 'lockedby' => $lockedby, 'state' => $stinfo, 'name' => $name,
						'size' => $size, 'type' => $type, 'created' => $created, 'modified' => $modified );
		$t++;
	}
	ksort($elements);

	$objectmap = getObjectTypeMap();
	// show rows
	$txt = '<table class="listpane">
				<tr>
					<th style="width:25px"> '.BizResources::localize('ACT_DELETE').' </th>
					<th style="width:75px"> '.BizResources::localize('OBJ_TYPE2').' </th>
					<th style="width:125px"> '.BizResources::localize('OBJ_NAME').' </th>
					<th style="width:100px"> '.BizResources::localize('OBJ_LOCKED_BY').' </th>
					<th style="width:125px"> '.BizResources::localize('OBJ_CREATED').' </th>
					<th style="width:125px"> '.BizResources::localize('OBJ_MODIFIED').' </th>
					<th style="width:75px"> '.BizResources::localize('OBJ_FILESIZE').' </th>
				</tr>';

	// display pages in table
	$ix = 0;
	for ($ix; $ix < count($elements); $ix++) {
		$isPlaced = isset($elements[$ix]['placed']) ? checkPlaced($elements[$ix]['placed']) : 0;
		if ($elements[$ix]['type'] == 'Layout' ) $isPlaced = 1;
		$txt .= '<tr bgcolor="#DDDDDD" onmouseOver="this.bgColor=\'#FF9342\';" onmouseOut="this.bgColor=\'#DDDDDD\';">
					<td align="center">'.
					inputvar( 'objectID'.$ix, $elements[$ix]['id'], 'hidden' ).
					inputvar( 'placed'.$ix, $isPlaced, 'hidden' ).
					inputvar( 'name'.$ix, $elements[$ix]['name'], 'hidden' ).
					'<input type="checkbox" name="checkbox'.$ix.'" checked="checked" ';
		if (isset( $elements[$ix]['used'] ) && $elements[$ix]['used'] != '') $txt .= ' disabled="disabled" ';
		$typ = $elements[$ix]['type'];
		$txt .= '>
					</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($objectmap[$typ]) . '
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['name']) . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['lockedby']) . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['created']) . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');">' . formvar($elements[$ix]['modified']) . '</td>
					<td onmouseUp="popUp(\'../apps/info.php?id=' . $elements[$ix]['id'] . '\');" align="right">' . formvar(CalculateSize($elements[$ix]['size'])) . '</td>
				</tr>';
	}

	$sErrorMessage = BizResources::localize('ERR_REMOVE_BY_DATE');
	$txt .= '</table><table class="appbtnbar"><tr>
				<td width="60px">
						<a href="javascript:setval();javascript:document.content.submit();">
							<img src="../../config/images/ref_16.gif" border="0" title="'.BizResources::localize('ACT_REFRESH').'"/>
							'.BizResources::localize('ACT_REFRESH').'
						</a>
						&nbsp;
				</td>
				<td width="60px">
						<a href="javascript:AreYouSure(\''. $sErrorMessage . '\');">
							<img src="../../config/images/remov_16.gif" border="0" title="'.BizResources::localize('ACT_DELETE').'"/>
							'.BizResources::localize('ACT_DELETE').'
						</a>
						&nbsp;
				</td>
				<td></td> <!-- trick: used fill button bar to far right -->
			</tr></table>'.
			inputvar( 'show', '', 'hidden' ).
			inputvar( 'amount', $ix, 'hidden' ).
			inputvar( 'del', '', 'hidden' ).
			inputvar( 'directdel', '', 'hidden' );
	return $txt;
}

function checkPlaced( $placed ) {
	if ($placed != "") {
		return "1";
	} else {
		return "0";
	}
}

function CalculateSize( $size ) {
	$sizes = Array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
	$ext = $sizes[0];
	for ($i=1; (($i < count($sizes)) && ($size >= 1024)); $i++) {
		$size = $size / 1024;
		$ext  = $sizes[$i];
	}
	return round($size, 2) . " " . $ext;
}

$sErrorMessage = BizResources::localize("ERR_REMOVE_BY_DATE");
$tpl = str_replace('<!--REMOVEBYDATE-->', $sErrorMessage, $tpl);

$sErrorMessage = BizResources::localize('ERR_MANDATORYFIELDS');
$tpl = str_replace('<!--ARGUMENT-->', $sErrorMessage, $tpl);

$tpl .= '<script type="text/javascript">document.forms[0].Date.focus();</script>';

$err = '';
if (isset($message)) {
	$err = "onLoad='javaScript:Message(\"$message\")'";
}

print HtmlDocument::buildDocument($tpl, true, $err);
