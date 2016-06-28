<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar(), inputvar()
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/protocols/soap/WflServices.php';
require_once BASEDIR.'/server/apps/functions.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

checkSecure('admin');

$Number_of_Results = 50;
$newStartPos = isset($_REQUEST["startPos"]) ? intval($_REQUEST["startPos"]) : 1;

$user = @$_POST['user'];
$service = @$_POST['service'];
$ip = @$_POST['ip'];
$date = @$_POST['date'];
$id = intval(@$_REQUEST['id']);
$logSearch = (bool)@$_POST['logSearch'];

if( isset($_GET['getvars']) && $_GET['getvars'] ) {
	$user = $_GET['user'];
	$service = $_GET['service'];
	$ip = $_GET['ip'];
	$logSearch = (bool)$_GET['logSearch'];
	//$date = $_GET['date'];
}

$tpl = HtmlDocument::loadTemplate( 'log.htm' );

$delID = isset($_GET['del']) ? $_GET['del'] : ''; // comma separated list of object ids 

$dbDriver = DBDriverFactory::gen();
if( !empty($delID) ) {
	$dds = explode( ',', $delID );
	if( count($dds) > 1 ) {
		array_pop($dds);
	}
	for( $counter=0; $counter < count($dds); $counter++ ) {
		$ids = intval($dds[$counter]);
		$permanent = true;
		if(!empty($ids)) {
			$deleteQuery = "DELETE FROM ".$dbDriver->tablename("log")." WHERE `id`=$ids";
		}
		$dbDriver->query($deleteQuery);
	}
	$delID = '';
	$logsearch = true;
}

//Get all variables for the User dropdownbox
$userQuery = "SELECT `user` FROM ".$dbDriver->tablename("users");
$result = $dbDriver->query($userQuery);

$selectUserOptions = '';
while(($row = $dbDriver->fetch($result))) {
	$rowUser = $row['user'];
	if($user == $rowUser) {
		$selectUserOptions .= '<option value="'.formvar($rowUser).'" selected="selected">'.formvar($rowUser).'</option>';
	} else {
		$selectUserOptions .= '<option value="'.formvar($rowUser).'">'.formvar($rowUser).'</option>';
	}
}
$tpl = str_replace ('<!--USER-->',$selectUserOptions, $tpl);

//Get all variables for the Services dropdownbox
$calls = get_class_methods('WW_SOAP_WflServices'); // get methods, which are service names + inherited methods (BZ#15414)
$calls = array_diff( $calls, get_class_methods(get_parent_class('WW_SOAP_WflServices')) ); // remove inherited methods (keep service names)

$calls = array_diff( $calls, array('Ping') ); // Remove Ping (used for debugging only)
sort($calls);


$selectCallsOptions = '';
for($i = 0; $i < sizeof($calls); $i++){
	if($service == $calls[$i]) {
		$selectCallsOptions .= '<option value="'.formvar($calls[$i]).'" selected="selected">'.formvar($calls[$i]).'</option>';
	} else {
		$selectCallsOptions .= '<option value="'.formvar($calls[$i]).'">'.formvar($calls[$i]).'</option>';
	}
}
$tpl = str_replace ('<!--SERVICE-->',$selectCallsOptions, $tpl);

//Get all variables for the IP dropdownbox
$ipQuery = "SELECT `ip` FROM ".$dbDriver->tablename("log")." GROUP BY `ip`";
$result = $dbDriver->query($ipQuery);

$selectIpOptions = '';
while (($row = $dbDriver->fetch($result))) {
	$rowIp = $row['ip'];
	if($ip == $rowIp) {
		$selectIpOptions .= '<option value="'.formvar($rowIp).'" selected="selected">'.formvar($rowIp).'</option>';
	} else {
		$selectIpOptions .= '<option value="'.formvar($rowIp).'">'.formvar($rowIp).'</option>';
	}
}
$tpl = str_replace ('<!--IP-->',$selectIpOptions, $tpl);
$tpl = str_replace ('<!--DATE-->',formvar($date), $tpl);

$search = '';
if($logSearch === true || $id > 0) {

	$dbl = $dbDriver->tablename('log');
	$dbp = $dbDriver->tablename('publications');
	$dbi = $dbDriver->tablename('issues');
	$dbs = $dbDriver->tablename('publsections');
	$dbst = $dbDriver->tablename('states');
	$dbo = $dbDriver->tablename('objects');
	$dbdo = $dbDriver->tablename('deletedobjects');
	
	$sqlfrom = "from $dbl l
		left join $dbp p on p.`id` = l.`publication`
		left join $dbi i on i.`id` = l.`issue`
		left join $dbs s on s.`id` = l.`section`
		left join $dbst st on st.`id` = l.`state`
		left join $dbo o on o.`id` = l.`objectid`
		left join $dbo op on op.`id` = l.`parent`
		left join $dbdo do on do.`id` = l.`objectid`";
		
	$sqlselect = 'select l.`id`, l.`user`, l.`service`, l.`ip`, l.`date`,
					l.`lock`, l.`rendition`, '.
					$dbDriver->concatFields( array( 'l.`majorversion`', "'.'", 'l.`minorversion`' ) ).' as "version",
					p.`publication`, i.`name` as `issuename`, s.`section`, st.`state`,
					o.`id` as `oid`, o.`name` as `oname`,
					op.`id` as `opid`, op.`name` as `opname`,
					do.`id` as `doid`, do.`name` as `doname` ';

	function where($field, $value, $first = false)
	{
		$dbDriver = DBDriverFactory::gen();
		$field = $dbDriver->quoteIdentifier($field);

		if(!empty($value)){
			if($first)
				return " WHERE $field $value";
			else
				return " AND $field $value";
		}
		return '';
	}

	$sqlwhere = '';
	$first = true;
	$dbh = $dbDriver;
	if(!empty($user) && $first){
		$sqlwhere .= where("user","LIKE '%".$dbh->toDBString($user)."%'", $first);
		$first= false;
	} else {
		if(!empty($user))
			$sqlwhere .= where("user","LIKE '%".$dbh->toDBString($user)."%'");
	}
	if(!empty($service) && $first){
		$sqlwhere .= where("service", "='".$dbh->toDBString($service)."'", $first);
		$first= false;
	} else {
		if(!empty($service))
			$sqlwhere .= where("service", "='".$dbh->toDBString($service)."'");
	}
	if(!empty($ip) && $first){
//		$sqlwhere .= where("ip","LIKE '%$ip%'", $first);
		$sqlwhere .= where("ip","= '".$dbh->toDBString($ip)."'", $first);
		$first= false;
	} else {
		if(!empty($ip))
			$sqlwhere .= where("ip","= '".$dbh->toDBString($ip)."'");
	}
	if(!empty($date) && $first){
		$sqlwhere .= where("date","LIKE '%".$dbh->toDBString($date)."%'", $first);
		$first= false;
	} else {
		if(!empty($date))
			$sqlwhere .= where("date","LIKE '%".$dbh->toDBString($date)."%'");
	}

	if ($id > 0) {
		$sqlwhere .= where("objectid","= $id", $first);
		$first = false;
	}
	$sqlorder = 'ORDER BY l.`date` DESC';
	
	$sql = $sqlselect.' '.$sqlfrom.' '.$sqlwhere.' '.$sqlorder;
	$sqlcount = 'select count(*) as `c` '.$sqlfrom.' '.$sqlwhere; // BZ#4879: no ORDER BY on COUNT
	
	$result = $dbDriver->query($sqlcount);
	$row = $dbDriver->fetch($result);
	$maxnum_results = $row["c"];
	
	if ($Number_of_Results != -1) {
		$query = $dbDriver->limitquery($sql, $newStartPos-1, $Number_of_Results);
	} else {
		$query = $sql;
	}
	$result = $dbDriver->query($query);
	
	$output="";
	$count=0;
	$oname = null;
	while (($row = $dbDriver->fetch($result))) {
		$rowId			= $row['id'];
		$rowUser		= $row['user'];
		$rowService		= $row['service'];
		$rowIp			= $row['ip'];
		$rowDate		= $row['date'];
		$version		= $row['version'];
		if( strpos( $version, '-1' ) !== false ) {
			$version = ''; // clear version if major or minor is not set
		}
		$count++;

		if(!empty($rowId)){
			$table = HtmlDocument::loadTemplate( 'logtable.htm' );
			$chkobj='<input type="checkbox" id="chkobj" name="chkobj" value="'.$rowId.'"/>';
			
			$table = str_replace('<!--ID-->', $rowId, $table);
			$table = str_replace('<!--CHKBOX-->',$chkobj,$table);

			$table = str_replace ('<!--USER-->',formvar($rowUser), $table);
			$table = str_replace ('<!--SERVICE-->',formvar($rowService), $table);
			$table = str_replace ('<!--IP-->',formvar($rowIp), $table);

			$oname = $row['oname'];		// keep for later
			// if object has been deleted, use deleted object name
			if (intval($row['doid'])>0){
				$oname = $row['doname'];
			}
			$table = str_replace ('<!--OBJECT-->',formvar($oname), $table);
			$table = str_replace ('<!--PUBLICATION-->',formvar($row['publication']), $table);
			$table = str_replace ('<!--ISSUE-->',formvar($row['issuename']), $table);
			$table = str_replace ('<!--SECTION-->',formvar($row['section']), $table);
			$table = str_replace ('<!--VERSION-->',formvar($version), $table);

			$rowDate = timeConverter($rowDate);
			$table = str_replace ('<!--DATE-->',formvar($rowDate), $table);
			$sErrorMessage = BizResources::localize('ERR_DEL_LOG');
			$delimg='<td onmouseUp="Delete(\'log.php?user='.urlencode($rowUser).
								'&logSearch='.urlencode($logSearch).'&getvars=true&service='.urlencode($rowService).
								'&ip='.urlencode($rowIp).'&date='.urlencode($rowDate).'&del='.$rowId.'\', \''.$sErrorMessage.'\');"/>'.
						'<img src="../../config/images/trash_16.gif" border="0" alt="delete"></td>';
			$table = str_replace ('<!--DELETEOBJ-->',$delimg, $table);
		}

		$output .= $table;
	}
	if(!empty($user) || !empty($service) || !empty($ip) || !empty($date) || $id > 0){
		$params ='';
		if ($id > 0 && $oname) $params .= '[ '.formvar($oname).' ]';
		if(!empty($user))      $params .= '[ '.formvar($user).' ] ';
		if(!empty($service))   $params .= '[ '.formvar($service).' ] ';
		if(!empty($ip))        $params .= '[ '.formvar($ip).' ] ';
		if(!empty($date))      $params .= '[ '.formvar($date).' ] ';
		$tpl = str_replace ('<!--PARAM-->',$params, $tpl);
	}
	$output .= inputvar( 'id', $id, 'hidden' );
	$output .= inputvar( 'startPos', $newStartPos, 'hidden' );
	$tpl = str_replace ('<!--RESULT-->',$output, $tpl);
	$sErrorMessage = BizResources::localize('ERR_DEL_LOG');
	$tpl = str_replace ('<!--DELETEALL-->',
		'<a href="#" onclick="deleteselected(\''.formvar($rowUser).'\', \''.formvar($logSearch).'\', true, '.
					'\''.formvar($service).'\', \''.formvar($ip).'\', \''.formvar($date).'\', \'\', '.
					'\''.formvar($count).'\', \''.$sErrorMessage.'\');" '.
		'title="'.BizResources::localize('ACT_DELETE').'">'.BizResources::localize('ACT_DELETE_SELECTED').'</a>', $tpl);
	$tpl = str_replace ('<!--SELECTALL-->','<a href="#" onclick="SetAllCheckBoxes(\'log\',\'chkobj\',true)" '.
		'title="'.BizResources::localize('ACT_SELECT_ALL_ROWS').'">'.BizResources::localize('ACT_SELECT_ALL').'</a>', $tpl);
	$tpl = str_replace ('<!--UNSELECTALL-->','<a href="#" onclick="SetAllCheckBoxes(\'log\',\'chkobj\',false)" '.
		'title="'.BizResources::localize('ACT_UNSELECT_ALL_ROWS').'">'.BizResources::localize('ACT_UNSELECT_ALL').'</a>', $tpl);

	// handle nav bar
	$num_results = $count;
	if( $Number_of_Results != -1 && $maxnum_results > 0 && $Number_of_Results < $maxnum_results )
	{
		$pageCount = ceil( $maxnum_results / $Number_of_Results );
		$restPage = $maxnum_results % $Number_of_Results;
		$curPage = ceil( $newStartPos / $Number_of_Results );
		$backPos = $newStartPos - $Number_of_Results;
		$nextPos = $newStartPos + $Number_of_Results;
		$thisEndPos = $newStartPos + $num_results - 1;
		$lastPos = max($maxnum_results - $restPage + 1, $nextPos);
		/* // paranoid debugging:
		print "<br><br><br><br><br><br>
			num_results=$num_results<br>
			maxnum_results=$maxnum_results<br>
			Number_of_Results=$Number_of_Results<br>
			newStartPos=$newStartPos<br>
			pageCount=$pageCount<br>
			restPage=$restPage<br>
			curPage=$curPage<br>
			backPos=$backPos<br>
			nextPos=$nextPos<br>
			lastPos=$lastPos<br>";*/
		$search .= "<td align=right width=33%>";
		if( $curPage > 1 ) {
			$search .= "<a href=\"javascript:ShowLimitedResults( '1' );\">";
			$search .= "<img src='../../config/images/rewnd_32.gif' border='0' title='".BizResources::localize("LIS_BEGIN")."'></a>&nbsp;&nbsp;&nbsp;";
			$search .= "<a href=\"javascript:ShowLimitedResults( '$backPos' );\">";
			$search .= "<img src='../../config/images/back_32.gif' border='0' title='".BizResources::localize("ACT_BACK")."'></a>";
		}
		$search .= "</td>\r\n";
		$search .= "<td align=center width=33%>$newStartPos - $thisEndPos / $maxnum_results</td>\r\n";
		$search .= "<td align=left width=33%>";
		if( $curPage < $pageCount ) {
			$search .= "<a href=\"javascript:ShowLimitedResults( '$nextPos' );\">";
			$search .= "<img src='../../config/images/forwd_32.gif' border='0' title='".BizResources::localize("LIS_NEXT")."'></a>&nbsp;&nbsp;&nbsp;";
			$search .= "<a href=\"javascript:ShowLimitedResults( '$lastPos' );\">";
			$search .= "<img src='../../config/images/fastf_32.gif' border='0' title='".BizResources::localize("LIS_LAST")."'></a></td>\r\n";
		}
		$search .= "</td>\r\n";
	}
	if( strlen( $search ) == 0 )
	{
		if( $maxnum_results == 0 ) {
			$search .= '<td align="center"><i>'.BizResources::localize('NO_MATCH_FOUND').'</i></td>'; // BUG fix: localization: NO_MATCH_FOUND
		} else {
			$search .= '<td align="center">'.$maxnum_results.' / '.$maxnum_results.'</td>';
		}
	}
}

$tpl = str_replace ("<!--SEARCHBACK_NEXT-->",$search, $tpl);

print HtmlDocument::buildDocument($tpl);

function timeConverter($val) {
	$val_array = preg_split('/[T]/', $val);
	$date_array = preg_split('/[-]/', $val_array['0']);
	$date_formated = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];
	return $date_formated . " " . $val_array['1'];
}

?>