<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/bizclasses/HighResHandler.class.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/DataClasses.php';

set_time_limit(3600);
error_reporting(E_ALL);
ini_set('display_errors', '0'); // only for debugging
$suiteOpts = unserialize( TESTSUITE );

////////////////////////////////////////////////////////////////////////
// URL parameters (and defaults when missing)
////////////////////////////////////////////////////////////////////////

// add "?namebased" to URL to work with names instead of ids
$namebased = isset($_GET["namebased"]) ? 'true' : 'false';

// add "?act_create=..." to URL to perform CreateLayouts and/or CreateAdverts
// Supported values are 'layout', 'advert', 'none' or 'all'. Default value is 'all'.
$act_create = isset($_GET["act_create"]) ? $_GET["act_create"] : 'all';

// add "?act_modify=..." to URL to perform ModifyLayouts and/or ModifyAdverts
// Supported values are 'layout', 'advert', 'none' or 'all'. Default value is 'all'.
$act_modify = isset($_GET["act_modify"]) ? $_GET["act_modify"] : 'all';

// add "?act_delete=..." to URL to perform DeleteLayouts and/or DeleteAdverts
// Supported values are 'layout', 'advert', 'none' or 'all'. Default value is 'all'.
$act_delete = isset($_GET["act_delete"]) ? $_GET["act_delete"] : 'all';

// add "?delete" to URL to delete objects after creation
//$deleteafter = isset($_GET["delete"]) ? 'true' : 'false'; // obsoleted; use act_delete instead

// add "?no_modify" to URL to suppress ModifyAdverts or ModifyLayouts
//$no_modify = isset($_GET["no_modify"]) ? 'true' : 'false'; // obsoleted; use act_modify instead

// add "?layout_id=..." to URL to skip layout creation and reuse existing layout instead
$lay_id = isset($_GET["layout_id"]) ? $_GET["layout_id"] : '';

// add "?advert_id=..." to URL to skip advert creation and reuse existing advert instead
$adv_id = isset($_GET["advert_id"]) ? $_GET["advert_id"] : '';

// add '?publication=...' and/or '?issue=...' to define location where to create/modify layouts/adverts
$sPub      = isset($_GET['publication'])  ? $_GET['publication'] : $suiteOpts['Brand'];
$sIss      = isset($_GET['issue'])        ? $_GET['issue']    : $suiteOpts['Issue'];

// add '?adv_name=...' and/or '?adv_section=...' and/or '?adv_status=...' to specify name/section/status of advert
$adv_name    = isset($_GET['adv_name'])    ? $_GET['adv_name']    : 'new advert '.date('Ymd_His');
$adv_section = isset($_GET['adv_section']) ? $_GET['adv_section'] : null;
$adv_status  = isset($_GET['adv_status'])  ? $_GET['adv_status']  : null;

// add '?lay_name=...' and/or '?lay_section=...' and/or '?lay_status=...' to specify name/section/status of layout
$lay_name    = isset($_GET['lay_name'])    ? $_GET['lay_name']    : 'new layout '.date('Ymd_His');;
$lay_section = isset($_GET['lay_section']) ? $_GET['lay_section'] : null;
$lay_status  = isset($_GET['lay_status'])  ? $_GET['lay_status']  : null;

// add '?lay_template=...' to specify the template name to create new layout
$lay_template= isset($_GET['lay_template'])? $_GET['lay_template']: 'eklt009';

// add "?content=..." to URL to determine type of content to send with adverts. 
// Supported values are: 'highresfile', 'output', 'preview', 'plaincontent', 'description'
$content = isset($_GET["content"]) ? $_GET["content"] : 'preview';
if(!in_array( $content, array('highresfile', 'output', 'preview', 'plaincontent', 'description'))) die("Bad value given for parameter 'content': $content");

// add "?ad_filename=..." to specify the filename of the highres when setting content to 'highresfile'
$adv_filename = isset($_GET['ad_filename'])  ? $_GET['ad_filename']  : null;


// add '?adv_pagenr=...' to determine advert page positioning
$adv_pagenr       = isset($_GET['pagenr'])    ? $_GET['pagenr']    : 0;
//$lay_start_pagenr = isset($_GET['startpage']) ? $_GET['startpage'] : 0;
//$lay_page_count   = isset($_GET['pagecount']) ? $_GET['pagecount'] : 0;
if( isset($_GET['startpage']) ) {
	die( 'The \'startpage\' parameter is obsoleted. Please use \'pagenrs\' instead.' );
}
if( isset($_GET['pagecount']) ) {
	die( 'The \'pagecount\' parameter is obsoleted. Please use \'pagenrs\' instead.' );
}

// add '?left=...' and/or '?top=...' and/or '?height=...' and/or '?width=...' to URL to define position/size of the adverts (default is random location/size)
$ad_left   = isset($_GET['left'])   ? $_GET['left']   : 0;
$ad_top    = isset($_GET['top'])    ? $_GET['top']    : 0;
$ad_height = isset($_GET['height']) ? $_GET['height'] : 0;
$ad_width  = isset($_GET['width'])  ? $_GET['width']  : 0;

$ad_layer       = isset($_GET['ad_layer'])  ? $_GET['ad_layer'] : '';
$ad_dx_set      = isset($_GET['ad_dx']);
$ad_dx          = isset($_GET['ad_dx'])     ? $_GET['ad_dx']    : 0.0;
$ad_dy_set      = isset($_GET['ad_dy']);
$ad_dy          = isset($_GET['ad_dy'])     ? $_GET['ad_dy']    : 0.0;
$ad_scalex      = isset($_GET['ad_scalex']) ? $_GET['ad_scalex']: 0.0;
$ad_scaley      = isset($_GET['ad_scaley']) ? $_GET['ad_scaley']: 0.0;

// add '?ad_editions=X;Y;Z' and/or '?ad_deadline=YYYY-MM-DDTHH:MM:SS' to URL to define for which editions the advert occurs and the deadline to meet
$ad_deadline = isset($_GET['adv_deadline']) ? $_GET['adv_deadline'] : null;
$ad_editions = isset($_GET['adv_editions']) ? explode(';', $_GET['adv_editions']) : array();

// add '?ad_editions=X;Y;Z' and/or '?ad_deadline=YYYY-MM-DDTHH:MM:SS' to URL to define for which editions the advert occurs and the deadline to meet
$lay_deadline = isset($_GET['lay_deadline']) ? $_GET['lay_deadline'] : null;
$lay_editions = isset($_GET['lay_editions']) ? explode(';', $_GET['lay_editions']) : array();

$pag_height = isset($_GET['pag_height']) ? $_GET['pag_height'] : 800.0;
$pag_width  = isset($_GET['pag_width'])  ? $_GET['pag_width']  : 600.0;
//$pag_editions= isset($_GET['pag_editions'])? explode(';', $_GET['pag_editions']) : array(''); // at least one (empty) defined to simplify code below
if( isset($_GET['pag_editions']) ) {
	die( 'The \'pag_editions\' parameter is obsoleted. Please use \'pagenrs[_edition]\' instead.' );
}
$pag_master = isset($_GET['pag_master']) ? $_GET['pag_master'] : '';

// make up placement coordinates when not specified
$ad_left_create   = $ad_left   > 0 ? $ad_left   : rand(20, 400);
$ad_top_create    = $ad_top    > 0 ? $ad_top    : rand(20, 500);
$ad_width_create  = $ad_width  > 0 ? $ad_width  : rand(50, 150);
$ad_height_create = $ad_height > 0 ? $ad_height : rand(75, 250);
$ad_dx_create     = $ad_dx_set     ? $ad_dx     : rand(-25, 25);
$ad_dy_create     = $ad_dy_set     ? $ad_dy     : rand(-25, 25);
$ad_scalex_create = $ad_scalex > 0 ? $ad_scalex : rand(40, 100)/100;
$ad_scaley_create = $ad_scaley > 0 ? $ad_scaley : rand(40, 100)/100;

$ad_left_modify   = $ad_left   > 0 ? $ad_left   : rand(20, 400);
$ad_top_modify    = $ad_top    > 0 ? $ad_top    : rand(20, 500);
$ad_width_modify  = $ad_width  > 0 ? $ad_width  : rand(50, 150);
$ad_height_modify = $ad_height > 0 ? $ad_height : rand(75, 250);
$ad_dx_modify     = $ad_dx_set     ? $ad_dx     : rand(-25, 25);
$ad_dy_modify     = $ad_dy_set     ? $ad_dy     : rand(-25, 25);
$ad_scalex_modify = $ad_scalex > 0 ? $ad_scalex : rand(40, 100)/100;
$ad_scaley_modify = $ad_scaley > 0 ? $ad_scaley : rand(40, 100)/100;

// determine page numbers
$pagnrs_editions_create = array();
$pagnrs_editions_modify = array();
$pagecount = 0;
foreach( $lay_editions as $lay_edition ) { // allow pagnrs_North=3,4,7,8
	if( isset($_GET['pagenrs_'.$lay_edition]) ) {
		$pagnrs_editions_create[$lay_edition] = explode(',', $_GET['pagenrs_'.$lay_edition]);
		$pagnrs_editions_modify[$lay_edition] = explode(',', $_GET['pagenrs_'.$lay_edition]);
		$lay_page_count_create = count($pagnrs_editions_create[$lay_edition]);
		$lay_page_count_modify = count($pagnrs_editions_modify[$lay_edition]);
	} 
}
if( $pagecount == 0 ) {
	$lay_page_count_create = rand(1, 5);
	$lay_page_count_modify = rand(1, 5);
}
$pagnrs_create = array();
$pagnrs_modify = array();
if( isset($_GET['pagenrs']) ) {
	$pagnrs_create = explode(',', $_GET['pagenrs']);
	$pagnrs_modify = explode(',', $_GET['pagenrs']);
} else {
	$startpage = rand(1, 100);
	for( $p=0; $p < $lay_page_count_create; $p++ ) {
		$pagnrs_create[] = $startpage + $p;
	}
	$startpage = rand(1, 100);
	for( $p=0; $p < $lay_page_count_modify; $p++ ) {
		$pagnrs_modify[] = $startpage + $p;
	}
}
if( $adv_pagenr > 0 ) {
	$adv_pagenr_create = $adv_pagenr;
	$adv_pagenr_modify = $adv_pagenr;
} else {
	$adv_pagenr_create = rand( 1, count($pagnrs_create) );
	$adv_pagenr_modify = rand( 1, count($pagnrs_modify) );
}	

// how to use clientplan.php
print "<hr><b>Introduction:</b><br>This tool can be used to test the plannings SOAP interface.";
print "Click <a href='clientplan.html'>here</a> for more info. <br>";

// show all URL parameters including made-up params
echo "<hr><b>Session parameters:</b><br><table border=1 cellpadding=2>";
echo "<tr><td><b>Common</b></td>           <td><b>Layout</b></td>                             <td><b>Advert</b></tr>";
echo "<tr><td></td>                        <td>layout_id=$lay_id</td>                         <td>advert_id=$adv_id</td></tr>";
echo "<tr><td>namebased=$namebased</td>    <td>lay_name=$lay_name</td>                        <td>adv_name=$adv_name</td></tr>";
echo "<tr><td>pubblication=$sPub</td>      <td>lay_section=$lay_section</td>                  <td>adv_section=$adv_section</td></tr>";
echo "<tr><td>issue=$sIss</td>             <td>lay_status=$lay_status</td>                    <td>adv_status=$adv_status</td></tr>";
echo "<tr><td>act_create=$act_create</td>  <td>lay_template=$lay_template</td>                <td>content=$content</td></tr>";
echo "<tr><td>act_modify=$act_modify</td>  <td>pagenrs (create)=".implode(',',$pagnrs_create)."</td><td>pagenr (create)=$adv_pagenr_create (= page sequence) (=> page order=".$pagnrs_create[$adv_pagenr_create-1].")</td></tr>";
echo "<tr><td>act_delete=$act_delete</td>  <td>pagenrs (modify)=".implode(',',$pagnrs_modify)."</td><td>pagenr (modify)=$adv_pagenr_modify (= page sequence) (=> page order=".$pagnrs_modify[$adv_pagenr_modify-1].")</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>edition pagenrs (create)=".print_r($pagnrs_editions_create,true)."</td>  <td>left, top, width, height (create)=$ad_left_create, $ad_top_create, $ad_width_create, $ad_height_create</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>edition pagenrs (modify)=".print_r($pagnrs_editions_modify,true)."</td>  <td>left, top, width, height (modify)=$ad_left_modify, $ad_top_modify, $ad_width_modify, $ad_height_modify</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>lay_editions=".print_r($lay_editions,true)."</td><td>adv_editions=".print_r($ad_editions,true)."</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>lay_deadline=$lay_deadline</td>                <td>adv_deadline=$ad_deadline</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>&nbsp;</td>                                    <td>adv_dx, adv_dy, adv_scalex, adv_scaley (create)=$ad_dx_create, $ad_dy_create, ".($ad_scalex_create*100)."%, ".($ad_scaley_create*100)."%</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>&nbsp;</td>                                    <td>adv_dx, adv_dy, adv_scalex, adv_scaley (modify)=$ad_dx_modify, $ad_dy_modify, ".($ad_scalex_modify*100)."%, ".($ad_scaley_modify*100)."%</td></tr>";
echo "<tr><td>&nbsp;</td>                  <td>&nbsp;</td>                                    <td>adv_filename=$adv_filename</td></tr>";
echo "</table><hr>";

// Generate random text for text fields: comments, description and plain content
require_once BASEDIR.'/server/wwtest/ngrams/books/en-flyingcars-word-2gram.php';
$book = new NGramsBook(); // Import book from disk
require_once BASEDIR.'/server/wwtest/ngrams/NGramsBookReader.class.php';
$gen = new NGramsBookReader( $book, false, false, false, false ); // Parse book
$ad_comment_create = $gen->readBook( 25 );
$ad_comment_modify = $gen->readBook( 25 );
$ad_description_create = $gen->readBook( 25 );
$ad_description_modify = $gen->readBook( 25 );
$ad_plaincontent_create = $gen->readBook( 300 );
$ad_plaincontent_modify = $gen->readBook( 300 );

// show all pages derived from URL params and made-up params for CREATE actions
$pag_create = array();
$pag_height_create = array();
$pag_width_create = array();
$pag_master_create = array();

foreach( $pagnrs_editions_create as $lay_edition => $pagnrs_edition ) {
	$rows = array_fill(0,5,''); // edition specific pages to create
	$table = '<table border="1">';
	$j = 1; // page SEQUENCE; not to be confused with page ORDER !
	foreach( $pagnrs_edition as $i ) {
		$rows[0] = $rows[0].'<td>PageSequence='.$j.'</td>';
		$rows[1] = $rows[1].'<td>PageOrder='.$i.'</td>';
		$pag_height_create[$j] = isset($_GET['pag'.$j.'_height']) ? $_GET['pag'.$j.'_height'] : $pag_height;
		$rows[2] = $rows[2].'<td>Height='.$pag_height_create[$j].'</td>';
		$pag_width_create[$j] = isset($_GET['pag'.$j.'_width']) ? $_GET['pag'.$j.'_width'] : $pag_width;
		$rows[3] = $rows[3].'<td>Width='.$pag_width_create[$j].'</td>';
		$pag_master_create[$j] = isset($_GET['pag'.$j.'_master']) ? $_GET['pag'.$j.'_master'] : $pag_master;
		$rows[4] = $rows[4].'<td>Master='.$pag_master_create[$j].'</td>';
		$j++;
	}
	foreach( $rows as $row ) {
		$table = $table.'<tr>'.$row.'</tr>';
	}
	$table .= '</table>';
	echo '<b>'.$lay_edition.' pages (to create):</b><br/>'.$table.'<hr/>';
}

$use_general_pages_create = empty($lay_editions) ? true : false; // determine if all edition pages are complete, in which case we don't use general pages
foreach( $lay_editions as $lay_edition ) {
	if( !isset( $pagnrs_editions_create[$lay_edition] ) ) {
		$use_general_pages_create = true;
		break;
	}
}

if( $use_general_pages_create ) {
	$rows = array_fill(0,5,''); // general pages to create (used when no edition specific pages specified)
	$table = '<table border="1">';
	$j = 1; // page SEQUENCE; not to be confused with page ORDER !
	foreach( $pagnrs_create as $i ) {
		$rows[0] = $rows[0].'<td>PageSequence='.$j.'</td>';
		$rows[1] = $rows[1].'<td>PageOrder='.$i.'</td>';
		$pag_height_create[$j] = isset($_GET['pag'.$j.'_height']) ? $_GET['pag'.$j.'_height'] : $pag_height;
		$rows[2] = $rows[2].'<td>Height='.$pag_height_create[$j].'</td>';
		$pag_width_create[$j] = isset($_GET['pag'.$j.'_width']) ? $_GET['pag'.$j.'_width'] : $pag_width;
		$rows[3] = $rows[3].'<td>Width='.$pag_width_create[$j].'</td>';
		$pag_master_create[$j] = isset($_GET['pag'.$j.'_master']) ? $_GET['pag'.$j.'_master'] : $pag_master;
		$rows[4] = $rows[4].'<td>Master='.$pag_master_create[$j].'</td>';
		$j++;
	}
	foreach( $rows as $row ) {
		$table = $table.'<tr>'.$row.'</tr>';
	}
	$table .= '</table>';
	echo '<b>General pages (to create):</b><br/>'.$table.'<hr/>';
}

// show all pages derived from URL params and made-up params for MODIFY actions
$pag_height_modify = array();
$pag_width_modify = array();
$pag_master_modify = array();

foreach( $pagnrs_editions_modify as $lay_edition => $pagnrs_edition ) {
	$rows = array_fill(0,5,''); // edition specific pages to modify
	$table = '<table border="1">';
	$j = 1; // page SEQUENCE; not to be confused with page ORDER !
	foreach( $pagnrs_edition as $i ) {
		$rows[0] = $rows[0].'<td>PageSequence='.$j.'</td>';
		$rows[1] = $rows[1].'<td>PageOrder='.$i.'</td>';
		$pag_height_modify[$j] = isset($_GET['pag'.$j.'_height']) ? $_GET['pag'.$j.'_height'] : $pag_height;
		$rows[2] = $rows[2].'<td>Height='.$pag_height_modify[$j].'</td>';
		$pag_width_modify[$j] = isset($_GET['pag'.$j.'_width']) ? $_GET['pag'.$j.'_width'] : $pag_width;
		$rows[3] = $rows[3].'<td>Width='.$pag_width_modify[$j].'</td>';
		$pag_master_modify[$j] = isset($_GET['pag'.$j.'_master']) ? $_GET['pag'.$j.'_master'] : $pag_master;
		$rows[4] = $rows[4].'<td>Master='.$pag_master_modify[$j].'</td>';
		$j++;
	}
	foreach( $rows as $row ) {
		$table = $table.'<tr>'.$row.'</tr>';
	}
	$table .= '</table>';
	echo '<b>'.$lay_edition.' pages (to modify):</b><br/>'.$table.'<hr/>';
}

$use_general_pages_modify = empty($lay_editions) ? true : false; // determine if all edition pages are complete, in which case we don't use general pages
foreach( $lay_editions as $lay_edition ) {
	if( !isset( $pagnrs_editions_create[$lay_edition] ) ) {
		$use_general_pages_modify = true;
		break;
	}
}

if( $use_general_pages_modify ) {
	$rows = array_fill(0,5,''); // general pages to modify (used when no edition specific pages specified)
	$table = '<table border="1">';
	$j = 1; // page SEQUENCE; not to be confused with page ORDER !
	foreach( $pagnrs_modify as $i ) {
		$rows[0] = $rows[0].'<td>PageSequence='.$j.'</td>';
		$rows[1] = $rows[1].'<td>PageOrder='.$i.'</td>';
		$pag_height_modify[$j] = isset($_GET['pag'.$j.'_height']) ? $_GET['pag'.$j.'_height'] : $pag_height;
		$rows[2] = $rows[2].'<td>Height='.$pag_height_modify[$j].'</td>';
		$pag_width_modify[$j] = isset($_GET['pag'.$j.'_width']) ? $_GET['pag'.$j.'_width'] : $pag_width;
		$rows[3] = $rows[3].'<td>Width='.$pag_width_modify[$j].'</td>';
		$pag_master_modify[$j] = isset($_GET['pag'.$j.'_master']) ? $_GET['pag'.$j.'_master'] : $pag_master;
		$rows[4] = $rows[4].'<td>Master='.$pag_master_modify[$j].'</td>';
		$j++;
	}
	foreach( $rows as $row ) {
		$table = $table.'<tr>'.$row.'</tr>';
	}
	$table .= '</table>';
	echo '<b>General pages (to modify):</b><br/>'.$table.'<hr/>';
}

//die( 'stopped here' );
/*echo '<table width="100%">';
echo '<tr><td><b>Layout</b></td><td></td><td><b>Advert</b></td><td></td></tr>';
echo '<tr><td width="15%">Name</td><td width="35%"><input type=text name=lay_name value="'.$lay_name.'" ></td><td width="15%">Name</td><td width="35%"><input type=text name=adv_name value="'.$adv_name.'" ></td></tr>';
echo '</table>';*/

////////////////////////////////////////////////////////////////////////
// Initialisation
////////////////////////////////////////////////////////////////////////

$watch = new StopWatch();
$clientProxy = new PlanClientProxy( $watch );

$sHighResStore = ''; // left empty when not used or not exists
if( $content == 'highresfile' ) {
	$sHighResStore = HighResHandler::getHighResAdvertStore();
	// check if high res folder exists
	if( $sHighResStore != '' ) {
		print "Found high-res advert store: <font color='#00f000'>$sHighResStore</font><br/>";
		// create wwtest folder in high res folder and copy high res test files into it
		$sHighResStore .= 'wwtest/';
		if( !file_exists($sHighResStore) ) {
			if( !mkdir($sHighResStore, 0777) ) {
				print "<font color='#ff0000'><b>Error:</b> Failed to create sub folder in high-res advert store.</font>".
						"Please check \"$sHighResStore\" folder for write access permissions for guests users.<br>";
				die();
			}
		}
		copy( BASEDIR.'/server/wwtest/testdata/output1page1.pdf', $sHighResStore.'output1page1.pdf' );
		copy( BASEDIR.'/server/wwtest/testdata/output1page2.pdf', $sHighResStore.'output1page2.pdf' );
	} else {
		print "<font color='#ff0000'><b>Error:</b> High-res advert store is not specified or does not exist.</font>".
				"Check HighResStoreMac and HighResStoreWin settings in configserver.php.<br>";
		die();
	}
}

////////////////////////////////////////////////////////////////////////
// LogOn
////////////////////////////////////////////////////////////////////////
require_once BASEDIR.'/server/utils/UrlUtils.php';
$clientip = WW_Utils_UrlUtils::getClientIP();
$clientname = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
if ( !$clientname || ($clientname == $clientip )) {
	$clientname = gethostbyaddr($clientip); 
}

require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOnRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOnResponse.class.php';
$req = new PlnLogOnRequest( 
	// $User, $Password, $Ticket, $Server, $ClientName, $Domain,
	$suiteOpts['User'], $suiteOpts['Password'], '', 'Enterprise Server', $clientname, '',
	//$ClientAppName, $ClientAppVersion, $ClientAppSerial, $ClientAppProductKey
	'clientplan.php', 'v'.SERVERVERSION, '', '' );
$logOnResp = $clientProxy->call( 'LogOn', $req );
if( is_null($logOnResp) ) { // without ticket we can't do anything, so quit
	exit();
}
$sTicket = $logOnResp->Ticket;

////////////////////////////////////////////////////////////////////////
// CreateLayouts
////////////////////////////////////////////////////////////////////////
	
if( $act_create == 'all' || $act_create == 'layout' ) {
	// make up page range for layout when not given
	$pagesArr = array();
	foreach( $pagnrs_editions_create as $lay_edition => $pagnrs_edition ) {
		$j = 1; // page SEQUENCE; not to be confused with page ORDER !
		foreach( $pagnrs_edition as $i ) {
			$pag_edition = new Edition(  null, $lay_edition );
			$pagesArr[] = new PlnPage( $i, $pag_width_create[$j], $pag_height_create[$j], null, $pag_edition, 
				$pag_master_create[$j], $j ); // PageOrder, Width, Height, Files, Edition, Master, PageSequence
			$j++;
		}
	}	
	if( $use_general_pages_create ) {
		$j = 1; // page SEQUENCE; not to be confused with page ORDER !
		foreach( $pagnrs_create as $i ) {
			$pagesArr[] = new PlnPage( $i, $pag_width_create[$j], $pag_height_create[$j], null, null, 
				$pag_master_create[$j], $j ); // PageOrder, Width, Height, Files, Edition, Master, PageSequence
			$j++;
		}
	}
	
	// put passed editions in SOAP
	$lay_editionsArr = null;
	if( count($lay_editions) > 0 ) {
		$lay_editionsArr = array();
		foreach( $lay_editions as $ed ) {
			$lay_editionsArr[] = new Edition( null, $ed );
		}
	}
	
	// build and fire layout SOAP request
	$newlayout = new PlnLayout( // Id, Name, Publication, Issue, Section, Status, Pages, Editions, Deadline, Version
		null, $lay_name, $sPub, $sIss, $lay_section, $lay_status, $pagesArr, $lay_editionsArr, $lay_deadline, null ); 
	$layouts = array( new PlnLayoutFromTemplate( $newlayout, $lay_template ) );

	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsResponse.class.php';
	$req = new PlnCreateLayoutsRequest( $sTicket, $layouts );
	$resp = $clientProxy->call( 'CreateLayouts', $req );
	$lay_id = is_null($resp) ? 0 : $resp->Layouts[0]->Id;
	if( is_null($resp) ) {
		$e = $clientProxy->getSoapFault();
		if( $e &&
			stripos( $e->getMessage(), '(S1029)' ) !== false &&
			stripos( $e->detail, 'Template:' ) !== false ) { 
			print "<font color='#ff0000'><b>Error:</b> The layout template '$lay_template' could not be found.</font><br/>".
					"Please create a layout template with that name (using InDesign) or specify any template that does ".
					"already exist in DB using the lay_template parameter at the URL.<br/>";
			die();
		}
	} else {
		$clientProxy->showDetails( 'Created layout "'.$resp->Layouts[0]->Name.'" (Id='.$resp->Layouts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// ModifyLayouts
////////////////////////////////////////////////////////////////////////

if( $act_modify == 'all' || $act_modify == 'layout' ) {
	// make up page range for layout when not given
	$pagesArr = array();
	foreach( $pagnrs_editions_modify as $lay_edition => $pagnrs_edition ) {
		$j = 1; // page SEQUENCE; not to be confused with page ORDER !
		foreach( $pagnrs_edition as $i ) {
			$pag_edition = new Edition( null, $lay_edition );
			$pagesArr[] = new PlnPage( $i, $pag_width_modify[$j], $pag_height_modify[$j], null, $pag_edition, 
				$pag_master_modify[$j], $j ); // PageOrder, Width, Height, Files, Edition, Master, PageSequence
			$j++;
		}
	}	
	if( $use_general_pages_modify ) {
		$j = 1; // page SEQUENCE; not to be confused with page ORDER !
		foreach( $pagnrs_modify as $i ) {
			$pagesArr[] = new PlnPage( $i, $pag_width_modify[$j], $pag_height_modify[$j], null, null,
				$pag_master_modify[$j], $j ); // PageOrder, Width, Height, Files, Edition, Master, PageSequence
			$j++;
		}
	}
	
	// put passed editions in SOAP
	$lay_editionsArr = null;
	if( count($lay_editions) > 0 ) {
		$lay_editionsArr = array();
		foreach( $lay_editions as $ed ) {
			$lay_editionsArr[] = new Edition( null, $ed );
		}
	}

	// build and fire layout SOAP request
	if( $namebased == 'true' ) {
		$layout = new PlnLayout( // Id, Name, Publication, Issue, Section, Status, Pages, Editions, Deadline, Version
			null, $lay_name, $sPub, $sIss, $lay_section, $lay_status, $pagesArr, $lay_editionsArr, $lay_deadline, null ); 
	} else {
		$layout = new PlnLayout( // Id, Name, Publication, Issue, Section, Status, Pages, Editions, Deadline, Version
			$lay_id, null, null, null, $lay_section, $lay_status, $pagesArr, $lay_editionsArr, $lay_deadline, null ); 
	}

	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsResponse.class.php';
	$req = new PlnModifyLayoutsRequest( $sTicket, array($layout) );
	$resp = $clientProxy->call( 'ModifyLayouts', $req );
	if( !is_null($resp) ) {
		$clientProxy->showDetails( 'Modified layout "'.$resp->Layouts[0]->Name.'" (Id='.$resp->Layouts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// CreateAdverts
////////////////////////////////////////////////////////////////////////

if( $act_create == 'all' || $act_create == 'advert' ) {
	$adv_hifile = null;
	$adv_file = null;
	if( $content == 'preview' ) {
		$adv_file = new Attachment( $content, 'image/jpeg', 
			new SOAP_Attachment( 'Content','application/octet-stream', 'testdata/preview1page1.jpg' ) );
	} else if( $content == 'output' ) {
		$adv_file = new Attachment( $content, 'application/pdf', 
			new SOAP_Attachment( 'Content','application/octet-stream', 'testdata/output1page1.pdf' ) );
	} else if( $content == 'highresfile' ) {
		if( $adv_filename == null ) {
			$adv_hifile = $sHighResStore.'testdata/output1page1.pdf';
		} else {
			$adv_hifile = $sHighResStore.$adv_filename;
		}
	}
	// put passed editions in SOAP
	$ad_editionsArr = null;
	if( count($ad_editions) > 0 ) {
		$ad_editionsArr = array();
		foreach( $ad_editions as $ed ) {
			$ad_editionsArr[] = new Edition( null, $ed );
		}
	}

	$page = null;
	$cols = rand(1,4);
	$placement = new PlnPlacement( // Left, Top, Columns, Width, Height, Fixed, Layer, ContentDx, ContentDy, ScaleX, ScaleY
		$ad_left_create, $ad_top_create, $cols, $ad_width_create, $ad_height_create, null,
		$ad_layer, $ad_dx_create, $ad_dy_create, $ad_scalex_create, $ad_scaley_create );
	$advert = new PlnAdvert( // Id, AlienId, Publication, Issue, Section, Status, Name, AdType, 
		null, null, $sPub, $sIss, $adv_section, $adv_status, $adv_name, 'my type',
		// Comment, Source, ColorSpace, Description, PlainContent, File, HighResFile, 
		$ad_comment_create, 'my customer', 'my color', $content == 'description' ? $ad_description_create : null, 
		$content == 'plaincontent' ? $ad_plaincontent_create : null, $adv_file,  $adv_hifile, 
		// PageOrder, Page, Placement, PreferredPlacement, PublishPrio, Rate, Editions, Deadline, PageSequence, Version
		$pagnrs_create[$adv_pagenr_create-1], $page, $placement, 'Left Top', 'ShouldHave', 5.50, $ad_editionsArr, $ad_deadline, $adv_pagenr_create, null );
	
	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsResponse.class.php';
	if( $namebased == 'true' ) {
		$req = new PlnCreateAdvertsRequest( $sTicket, null, $lay_name, array($advert) );
	} else {
		$req = new PlnCreateAdvertsRequest( $sTicket, $lay_id, null, array($advert) );
	}
	$resp = $clientProxy->call( 'CreateAdverts', $req );
	$adv_id = is_null($resp) ? 0 : $resp->Adverts[0]->Id;
	if( !is_null($resp) ) {
		$clientProxy->showDetails( 'Created advert "'.$resp->Adverts[0]->Name.'" (Id='.$resp->Adverts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// ModifyAdverts
////////////////////////////////////////////////////////////////////////

if( $act_modify == 'all' || $act_modify == 'advert' ) {
	$cols = rand(1,4);
	$adv_file = null;
	$adv_hifile = null;
	if( $content == 'preview' ) {
		$adv_file = new Attachment( $content, 'image/jpeg', 
			new SOAP_Attachment( 'Content','application/octet-stream', 'testdata/preview1page2.jpg' ) );
	} else if( $content == 'output' ) {
		$adv_file = new Attachment( $content, 'application/pdf', 
			new SOAP_Attachment( 'Content','application/octet-stream', 'testdata/output1page2.pdf' ) );
	} else if( $content == 'highresfile' ) {
		if( $adv_filename == null ) {
			$adv_hifile = $sHighResStore.'testdata/output1page2.pdf';
		} else {
			$adv_hifile = $sHighResStore.$adv_filename;
		}
	}
	// put passed editions in SOAP
	$ad_editionsArr = null;
	if( count($ad_editions) > 0 ) {
		$ad_editionsArr = array();
		foreach( $ad_editions as $ed ) {
			$ad_editionsArr[] = new Edition( null, $ed );
		}
	}

	$page = null;
	$placement = new PlnPlacement( // Left, Top, Columns, Width, Height, Fixed, Layer, ContentDx, ContentDy, ScaleX, ScaleY
		$ad_left_modify, $ad_top_modify, $cols, $ad_width_modify, $ad_height_modify, null,
		$ad_layer, $ad_dx_modify, $ad_dy_modify, $ad_scalex_modify, $ad_scaley_modify );
	$advert = new PlnAdvert( // Id, AlienId, Publication, Issue, Section, Status, Name, AdType, 
		$namebased == 'true' ? null : $adv_id, null, $sPub, $sIss, $adv_section, $adv_status, $adv_name, 'my type2',
		// Comment, Source, ColorSpace, Description, PlainContent, File, HighResFile, 
		$ad_comment_modify, 'my customer2', 'my color2', $content == 'description' ? $ad_description_modify : null, 
		$content == 'plaincontent' ? $ad_plaincontent_modify : null, $adv_file,  $adv_hifile, 
		// PageOrder, Page, Placement, PreferredPlacement, PublishPrio, Rate, Editions, Deadline, PageSequence, Version
		$pagnrs_modify[$adv_pagenr_modify-1], $page, $placement, 'Lower Part', 'MustHave', 6.75, $ad_editionsArr, $ad_deadline, $adv_pagenr_modify, null );

	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsResponse.class.php';
	if( $namebased == 'true' ) {
		$req = new PlnModifyAdvertsRequest( $sTicket, null, $lay_name, array($advert) );
	} else {
		$req = new PlnModifyAdvertsRequest( $sTicket, $lay_id, null, array($advert) );
	}
	$resp = $clientProxy->call( 'ModifyAdverts', $req );
	if( !is_null($resp) ) {
		$clientProxy->showDetails( 'Modified advert "'.$resp->Adverts[0]->Name.'" (Id='.$resp->Adverts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// DeleteAdverts
////////////////////////////////////////////////////////////////////////
if( $act_delete == 'all' || $act_delete == 'advert' ) {
	if( $namebased == 'true' ) {
		$advert = new PlnAdvert( null /*id*/, null /*alien*/, $sPub, $sIss, null /*section*/, null /*state*/, $adv_name  ); 
	} else {
		$advert = new PlnAdvert( $adv_id ); 
	}
	
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsResponse.class.php';
	if( $namebased == 'true' ) {
		$req = new PlnDeleteAdvertsRequest( $sTicket, null, $lay_name, array($advert) );
	} else {
		$req = new PlnDeleteAdvertsRequest( $sTicket, $lay_id, null, array($advert) );
	}
	$resp = $clientProxy->call( 'DeleteAdverts', $req );
	if( !is_null($resp) ) {
		$clientProxy->showDetails( 'Deleted advert "'.$adv_name.'" (Id='.$adv_id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// DeleteLayouts
////////////////////////////////////////////////////////////////////////
if( $act_delete == 'all' || $act_delete == 'layout' ) {
	if( $namebased == 'true' ) {
		$layout = new PlnLayout( null /*id*/, $lay_name, $sPub, $sIss );
	} else {
		$layout = new PlnLayout( $lay_id );
	}
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsResponse.class.php';
	$req = new PlnDeleteLayoutsRequest( $sTicket, array($layout) );
	$resp = $clientProxy->call( 'DeleteLayouts', $req );
	if( !is_null($resp) ) {
		$clientProxy->showDetails( 'Deleted layout "'.$lay_name.'" (Id='.$lay_id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// LogOff
////////////////////////////////////////////////////////////////////////
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOffRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOffResponse.class.php';
$req = new PlnLogOffRequest( $sTicket );
$clientProxy->call( 'LogOff', $req );


class PlanClientProxy
{
	private $watch = null;
	private $client = null;
	private $soapFault = null;
	
	public function __construct( $watch = null )
	{
		require_once BASEDIR.'/server/protocols/soap/PlnClient.php';
		$this->client = new WW_SOAP_PlnClient();
		$this->watch = $watch;	
	}

	public function showDetails( $details )
	{
		echo '<font color="gray">&nbsp;&nbsp;&nbsp;-&nbsp;'.$details.'</font><br/>';
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// internal helper functions that do logging at screen
	
	private function showOperation( $operation )
	{
		echo '<hr/><font color="blue"><b>Operation: </b>'.$operation.'</font><br/>';
	}
	
	private function showDuration( $duration )
	{
		echo '<b>Duration: </b>'.$duration.' sec<br/>';
	}

	private function showError( $error )
	{
		echo '<font color="red"><b>ERROR: </b>'.$error.'</font><br/>';
	}

	private function showSuccess( $duration )
	{
		$this->showDuration( $duration );
	}

	private function showException( $e, $duration )
	{
		$this->showDuration( $duration );
		self::showError( $e->getMessage() );
	}

	public function getSoapFault()
	{
		return $this->soapFault;
	}

	public function call( $operation, $req )
	{
		$this->soapFault = null;
		try {
			self::showOperation( $operation );
			$this->watch->Start();
			$return = $this->client->$operation( $req );
			self::showSuccess( $this->watch->Fetch() );
		} catch( SoapFault $e ){
			$this->soapFault = $e;
			$this->showException( $e, $this->watch->Fetch() );
		} catch( BizException $e ) {
			$this->showException( $e, $this->watch->Fetch() );
		}
		$error = !isset($return) || is_soap_fault($return);
		return $error ? null : $return;
	}
}
