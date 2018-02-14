<?php

require_once dirname( __FILE__ ).'/../../config/config.php';
require_once BASEDIR.'/server/bizclasses/HighResHandler.class.php';
require_once BASEDIR.'/server/utils/StopWatch.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/DataClasses.php';

set_time_limit( 3600 );
error_reporting( E_ALL );
ini_set( 'display_errors', 'Off' ); // only for debugging.
$suiteOpts = unserialize( TESTSUITE );
validateURLParameters();

////////////////////////////////////////////////////////////////////////
// URL parameters (and defaults when missing).
////////////////////////////////////////////////////////////////////////
// Add "?namebased" to URL to work with names instead of ids.
$nameBased = isset( $_GET["namebased"] ) ? 'true' : 'false';
// Add "?act_create=..." to URL to perform CreateLayouts and/or CreateAdverts.
// Supported values are 'layout', 'advert', 'none' or 'all'. Default value is 'all'.
$actionCreate = isset( $_GET["act_create"] ) ? $_GET["act_create"] : 'all';
// Add "?act_modify=..." to URL to perform ModifyLayouts and/or ModifyAdverts.
// Supported values are 'layout', 'advert', 'none' or 'all'. Default value is 'all'.
$actionModify = isset( $_GET["act_modify"] ) ? $_GET["act_modify"] : 'all';
// Add "?act_delete=..." to URL to perform DeleteLayouts and/or DeleteAdverts.
// Supported values are 'layout', 'advert', 'none' or 'all'. Default value is 'all'.
$actionDelete = isset( $_GET["act_delete"] ) ? $_GET["act_delete"] : 'all';
// Add "?layout_id=..." to URL to skip layout creation and reuse existing layout instead.
$layoutId = isset( $_GET["layout_id"] ) ? $_GET["layout_id"] : '';
// Add "?advert_id=..." to URL to skip advert creation and reuse existing advert instead.
$advertId = isset( $_GET["advert_id"] ) ? $_GET["advert_id"] : '';
// Add '?publication=...' and/or '?issue=...' to define location where to create/modify layouts/adverts.
$publication = isset( $_GET['publication'] ) ? $_GET['publication'] : $suiteOpts['Brand'];
$issue = isset( $_GET['issue'] ) ? $_GET['issue'] : $suiteOpts['Issue'];
$channelName = isset($_GET['channelName']) ? $_GET['channelName'] : '';
// Add '?adv_name=...' and/or '?adv_section=...' and/or '?adv_status=...' to specify name/section/status of advert.
$advertName = isset( $_GET['adv_name'] ) ? $_GET['adv_name'] : 'new advert '.date( 'Ymd_His' );
$advertSection = isset( $_GET['adv_section'] ) ? $_GET['adv_section'] : null;
$advertStatus = isset( $_GET['adv_status'] ) ? $_GET['adv_status'] : null;
// Add '?lay_name=...' and/or '?lay_section=...' and/or '?lay_status=...' to specify name/section/status of layout.
$layoutName = isset( $_GET['lay_name'] ) ? $_GET['lay_name'] : 'new layout '.date( 'Ymd_His' );;
$layoutSection = isset( $_GET['lay_section'] ) ? $_GET['lay_section'] : null;
$layoutStatus = isset( $_GET['lay_status'] ) ? $_GET['lay_status'] : null;
// Add '?lay_template=...' to specify the template name to create new layout.
$layoutTemplateName = isset( $_GET['lay_template'] ) ? $_GET['lay_template'] : 'eklt009';
// Add "?content=..." to URL to determine type of content to send with adverts.
// Supported values are: 'highresfile', 'output', 'preview', 'plaincontent', 'description'.
$content = isset( $_GET["content"] ) ? $_GET["content"] : 'preview';
if( !in_array( $content, array( 'highresfile', 'output', 'preview', 'plaincontent', 'description' ) ) ) {
	die( "Bad value given for parameter 'content': $content" );
}
// Add "?ad_filename=..." to specify the filename of the highres when setting content to 'highresfile'.
$advertHighResFilename = isset( $_GET['ad_filename'] ) ? $_GET['ad_filename'] : null;
// Add '?adv_pagenr=...' to determine advert page positioning.
$advertPageNr = isset( $_GET['pagenr'] ) ? $_GET['pagenr'] : 0;
// Add '?left=...' and/or '?top=...' and/or '?height=...' and/or '?width=...' to URL to define position/size of the adverts (default is random location/size).
$advertLeftPosition = isset( $_GET['left'] ) ? $_GET['left'] : 0;
$advertTopPosition = isset( $_GET['top'] ) ? $_GET['top'] : 0;
$advertHeight = isset( $_GET['height'] ) ? $_GET['height'] : 0;
$advertWidth = isset( $_GET['width'] ) ? $_GET['width'] : 0;
$advertLayerName = isset( $_GET['ad_layer'] ) ? $_GET['ad_layer'] : '';
$advertSetFrameDeltaX = isset( $_GET['ad_dx'] );
$advertFrameDeltaX = isset( $_GET['ad_dx'] ) ? $_GET['ad_dx'] : 0.0;
$advertSetFrameDeltaY = isset( $_GET['ad_dy'] );
$advertFrameDeltaY = isset( $_GET['ad_dy'] ) ? $_GET['ad_dy'] : 0.0;
$advertFrameScaleX = isset( $_GET['ad_scalex'] ) ? $_GET['ad_scalex'] : 0.0;
$advertFrameScaleY = isset( $_GET['ad_scaley'] ) ? $_GET['ad_scaley'] : 0.0;
// Add '?ad_editions=X;Y;Z' and/or '?ad_deadline=YYYY-MM-DDTHH:MM:SS' to URL to define for which editions the advert occurs and the deadline to meet.
$advertDeadline = isset( $_GET['adv_deadline'] ) ? $_GET['adv_deadline'] : null;
$advertEditionNames = isset( $_GET['adv_editions'] ) ? explode( ';', $_GET['adv_editions'] ) : array();
// Add '?lay_editions=X;Y;Z' and/or '?lay_deadline=YYYY-MM-DDTHH:MM:SS' to URL to define the editions of the layout and the deadline to meet.
$layoutDeadline = isset( $_GET['lay_deadline'] ) ? $_GET['lay_deadline'] : null;
$layoutEditionNames = isset( $_GET['lay_editions'] ) ? explode( ';', $_GET['lay_editions'] ) : array();
$pageHeight = isset( $_GET['pag_height'] ) ? $_GET['pag_height'] : 800.0;
$pageWidth = isset( $_GET['pag_width'] ) ? $_GET['pag_width'] : 600.0;
$pageMaster = isset( $_GET['pag_master'] ) ? $_GET['pag_master'] : '';

// Make up placement coordinates when not specified.
$advertCreateLeftPosition = $advertLeftPosition > 0 ? $advertLeftPosition : rand( 20, 400 );
$advertCreateTopPosition = $advertTopPosition > 0 ? $advertTopPosition : rand( 20, 500 );
$advertCreateWidth = $advertWidth > 0 ? $advertWidth : rand( 50, 150 );
$advertCreateHeight = $advertHeight > 0 ? $advertHeight : rand( 75, 250 );
$advertCreateFrameDeltaX = $advertSetFrameDeltaX ? $advertFrameDeltaX : rand( -25, 25 );
$advertCreateFrameDeltaY = $advertSetFrameDeltaY ? $advertFrameDeltaY : rand( -25, 25 );
$advertCreateFrameScaleX = $advertFrameScaleX > 0 ? $advertFrameScaleX : rand( 40, 100 ) / 100;
$advertCreateFrameScaleY = $advertFrameScaleY > 0 ? $advertFrameScaleY : rand( 40, 100 ) / 100;

$advertModifyLeftPosition = $advertLeftPosition > 0 ? $advertLeftPosition : rand( 20, 400 );
$advertModifyTopPosition = $advertTopPosition > 0 ? $advertTopPosition : rand( 20, 500 );
$advertModifyWidth = $advertWidth > 0 ? $advertWidth : rand( 50, 150 );
$advertModifyHeight = $advertHeight > 0 ? $advertHeight : rand( 75, 250 );
$advertModifyFrameDeltaX = $advertSetFrameDeltaX ? $advertFrameDeltaX : rand( -25, 25 );
$advertModifyFrameDeltaY = $advertSetFrameDeltaY ? $advertFrameDeltaY : rand( -25, 25 );
$advertModifyFrameScaleX = $advertFrameScaleX > 0 ? $advertFrameScaleX : rand( 40, 100 ) / 100;
$advertModifyFrameScaleY = $advertFrameScaleY > 0 ? $advertFrameScaleY : rand( 40, 100 ) / 100;

// determine page numbers.
$createPageOrdersByEditions = array();
$modifyPageOrdersByEditions = array();
//$numberOfPages = 0;
$layoutNumberOfPagesToCreate = 0;
$layoutNumberOfPagesToModify = 0;
foreach( $layoutEditionNames as $layoutEditionName ) { // Allow "pagenrs_North=3,4,7,8".
	if( isset( $_GET[ 'pagenrs_'.$layoutEditionName ] ) ) {
		$createPageOrdersByEditions[ $layoutEditionName ] = explode( ',', $_GET[ 'pagenrs_'.$layoutEditionName ] );
		$modifyPageOrdersByEditions[ $layoutEditionName ] = explode( ',', $_GET[ 'pagenrs_'.$layoutEditionName ] );
		$layoutNumberOfPagesToCreate = count( $createPageOrdersByEditions[ $layoutEditionName ] ); // Todo move out of the foreach.
		$layoutNumberOfPagesToModify = count( $modifyPageOrdersByEditions[ $layoutEditionName ] );
	}
}
$numberOfPages = $layoutNumberOfPagesToCreate + $layoutNumberOfPagesToModify;
if( $numberOfPages == 0 ) { // Todo $numberOfPages is always 0 as it is only initialized at line 97?
	$layoutNumberOfPagesToCreate = rand( 1, 5 );
	$layoutNumberOfPagesToModify = rand( 1, 5 );
}
$createPageOrdersNoEdition = array();
$modifyPageOrdersNoEdition = array();
if( isset( $_GET['pagenrs'] ) ) {
	$createPageOrdersNoEdition = explode( ',', $_GET['pagenrs'] );
	$modifyPageOrdersNoEdition = explode( ',', $_GET['pagenrs'] );
} else {
	$pageNumberToStart = rand( 1, 100 );
	for( $p = 0; $p < $layoutNumberOfPagesToCreate; $p++ ) {
		$createPageOrdersNoEdition[] = $pageNumberToStart + $p;
	}
	$pageNumberToStart = rand( 1, 100 );
	for( $p = 0; $p < $layoutNumberOfPagesToModify; $p++ ) {
		$modifyPageOrdersNoEdition[] = $pageNumberToStart + $p;
	}
}
if( $advertPageNr > 0 ) {
	$advertCreatePageNr = $advertPageNr;
	$advertModifyPageNr = $advertPageNr;
} else {
	$advertCreatePageNr = rand( 1, count( $createPageOrdersNoEdition ) );
	$advertModifyPageNr = rand( 1, count( $modifyPageOrdersNoEdition ) );
}
$numberStyle = isset( $_GET['num_style'] ) ? $_GET['num_style'] : 0;
$numberPrefix = isset( $_GET['num_prefix'] ) ? substr( $_GET['num_prefix'], 0, 8 ) : '';

// How to use clientplan.php.
print "<hr><b>Introduction:</b><br>This tool can be used to test the plannings SOAP interface.";
print "Click <a href='clientplan.html'>here</a> for more info. <br>";

// Show all URL parameters including made-up params.
echo "<hr><b>Session parameters:</b><br><table border=1 cellpadding=2>";
echo "<tr><td><b>Common</b></td>           	<td><b>Layout</b></td>                             											<td><b>Advert</b></tr>";
echo "<tr><td></td>                        	<td>layout_id=$layoutId</td>                         											<td>advert_id=$advertId</td></tr>";
echo "<tr><td>namebased=$nameBased</td>    	<td>lay_name=$layoutName</td>                        											<td>adv_name=$advertName</td></tr>";
echo "<tr><td>publication=$publication</td> <td>lay_section=$layoutSection</td>                  											<td>adv_section=$advertSection</td></tr>";
echo "<tr><td>issue=$issue</td>             	<td>lay_status=$layoutStatus</td>                    											<td>adv_status=$advertStatus</td></tr>";
echo "<tr><td>pubchannel=$channelName;</td>	<td>lay_template=$layoutTemplateName</td>                									<td>content=$content</td></tr>";
echo "<tr><td>act_create=$actionCreate</td>  <td>pagenrs (create)=".implode( ',', $createPageOrdersNoEdition )."</td>            <td>pagenr (create)=$advertCreatePageNr (= page sequence) (=> page order=".$createPageOrdersNoEdition[ $advertCreatePageNr - 1 ].")</td></tr>";
echo "<tr><td>act_modify=$actionModify</td>  <td>pagenrs (modify)=".implode( ',', $modifyPageOrdersNoEdition )."</td>            <td>pagenr (modify)=$advertModifyPageNr (= page sequence) (=> page order=".$modifyPageOrdersNoEdition[ $advertModifyPageNr - 1 ].")</td></tr>";
echo "<tr><td>act_delete=$actionDelete</td>  <td>edition pagenrs (create)=".print_r( $createPageOrdersByEditions, true )."</td>  <td>left, top, width, height (create)=$advertCreateLeftPosition, $advertCreateTopPosition, $advertCreateWidth, $advertCreateHeight</td></tr>";
echo "<tr><td>&nbsp;</td>                  	<td>edition pagenrs (modify)=".print_r( $modifyPageOrdersByEditions, true )."</td>  <td>left, top, width, height (modify)=$advertModifyLeftPosition, $advertModifyTopPosition, $advertModifyWidth, $advertModifyHeight</td></tr>";
echo "<tr><td>&nbsp;</td>                  	<td>lay_editions=".print_r( $layoutEditionNames, true )."</td>                      <td>adv_editions=".print_r( $advertEditionNames, true )."</td></tr>";
echo "<tr><td>&nbsp;</td>                  	<td>lay_deadline=$layoutDeadline</td>                											<td>adv_deadline=$advertDeadline</td></tr>";
echo "<tr><td>&nbsp;</td>                  	<td>&nbsp;</td>                                    											<td>adv_dx, adv_dy, adv_scalex, adv_scaley (create)=$advertCreateFrameDeltaX, $advertCreateFrameDeltaY, ".( $advertCreateFrameScaleX * 100 )."%, ".( $advertCreateFrameScaleY * 100 )."%</td></tr>";
echo "<tr><td>&nbsp;</td>                  	<td>&nbsp;</td>                                    											<td>adv_dx, adv_dy, adv_scalex, adv_scaley (modify)=$advertModifyFrameDeltaX, $advertModifyFrameDeltaY, ".( $advertModifyFrameScaleX * 100 )."%, ".( $advertModifyFrameScaleY * 100 )."%</td></tr>";
echo "<tr><td>&nbsp;</td>                  	<td>&nbsp;</td>                                    											<td>adv_filename=$advertHighResFilename</td></tr>";
echo "</table><hr>";

// Generate random text for text fields: comments, description and plain content.
require_once BASEDIR.'/server/wwtest/ngrams/books/en-flyingcars-word-2gram.php';
$book = new NGramsBook(); // Import book from disk.
require_once BASEDIR.'/server/wwtest/ngrams/NGramsBookReader.class.php';
$generator = new NGramsBookReader( $book, false, false, false, false ); // Parse book.
$advertCreateComment = $generator->readBook( 25 );
$advertModifyComment = $generator->readBook( 25 );
$advertCreateDescription = $generator->readBook( 25 );
$advertModifyDescription = $generator->readBook( 25 );
$advertCreatePlainContent = $generator->readBook( 300 );
$advertModifyPlainContent = $generator->readBook( 300 );

// Show all pages derived from URL params and made-up params for CREATE actions.
$pag_create = array(); // Todo Variable is not used?
$pageToCreateHeight = array();
$pageToCreateWidth = array();
$pageToCreateMasterPage = array();

if( $createPageOrdersByEditions ) foreach( $createPageOrdersByEditions as $layoutEditionName => $pageOrder ) {
	$table = createPagesTable( $pageOrder, $pageHeight, $pageWidth, $pageMaster, $numberStyle, $numberPrefix,
		$pageToCreateHeight, $pageToCreateWidth, $pageToCreateMasterPage );
	echo '<b>'.$layoutEditionName.' pages (to create):</b><br/>'.$table.'<hr/>';
}

$createPagesWithoutEditions = empty( $layoutEditionNames ) ? true : false; // Determine if all edition pages are complete, in which case we don't use general pages.
foreach( $layoutEditionNames as $layoutEditionName ) {
	if( !isset( $createPageOrdersByEditions[ $layoutEditionName ] ) ) {
		$createPagesWithoutEditions = true;
		break;
	}
}

if( $createPagesWithoutEditions ) {
	$table = createPagesTable( $createPageOrdersNoEdition, $pageHeight, $pageWidth, $pageMaster, $numberStyle,
		$numberPrefix, $pageToCreateHeight, $pageToCreateWidth, $pageToCreateMasterPage );
	echo '<b>General pages (to create):</b><br/>'.$table.'<hr/>';
}

// Show all pages derived from URL params and made-up params for MODIFY actions.
$pageToModifyHeight = array();
$pageToModifyWidth = array();
$pageToModifyMasterPage = array();

if( $modifyPageOrdersByEditions ) foreach( $modifyPageOrdersByEditions as $layoutEditionName => $pageOrder ) {
	$table = createPagesTable( $pageOrder, $pageHeight, $pageWidth, $pageMaster, $numberStyle, $numberPrefix,
		$pageToModifyHeight, $pageToModifyWidth, $pageToModifyMasterPage );
	echo '<b>'.$layoutEditionName.' pages (to modify):</b><br/>'.$table.'<hr/>';
}

$modifyPagesWithoutEditions = empty( $layoutEditionNames ) ? true : false; // Determine if all edition pages are complete, in which case we don't use general pages.
foreach( $layoutEditionNames as $layoutEditionName ) {
	if( !isset( $createPageOrdersByEditions[ $layoutEditionName ] ) ) {
		$modifyPagesWithoutEditions = true;
		break;
	}
}

if( $modifyPagesWithoutEditions ) {
	$table = createPagesTable( $modifyPageOrdersNoEdition, $pageHeight, $pageWidth, $pageMaster, $numberStyle,
		$numberPrefix, $pageToModifyHeight, $pageToModifyWidth, $pageToModifyMasterPage );
	echo '<b>General pages (to modify):</b><br/>'.$table.'<hr/>';
}

////////////////////////////////////////////////////////////////////////
// Initialisation.
////////////////////////////////////////////////////////////////////////

$watch = new StopWatch();
$clientProxy = new PlanClientProxy( $watch );

$sHighResStore = ''; // Left empty when not used or not exists.
if( $content == 'highresfile' ) {
	$sHighResStore = HighResHandler::getHighResAdvertStore();
	// Check if high res folder exists.
	if( $sHighResStore != '' ) {
		print 'Found high-res advert store: <span style="color:#00f000">'.$sHighResStore.'</span><br/>';
		// Create wwtest folder in high res folder and copy high res test files into it.
		$sHighResStore .= 'wwtest/';
		if( !file_exists( $sHighResStore ) ) {
			if( !mkdir( $sHighResStore, 0777 ) ) {
				print '<span style="color:#ff0000"><b>Error:</b> Failed to create sub folder in high-res advert store.</span>'.
					" Please check \"$sHighResStore\" folder for write access permissions for guests users.<br>";
				die();
			}
		}
		copy( BASEDIR.'/server/wwtest/testdata/output1page1.pdf', $sHighResStore.'output1page1.pdf' );
		copy( BASEDIR.'/server/wwtest/testdata/output1page2.pdf', $sHighResStore.'output1page2.pdf' );
	} else {
		print '<span style="color:#ff0000"><b>Error:</b> High-res advert store is not specified or does not exist.</span>'.
			" Check HighResStoreMac and HighResStoreWin settings in configserver.php.<br>";
		die();
	}
}

////////////////////////////////////////////////////////////////////////
// LogOn.
////////////////////////////////////////////////////////////////////////
require_once BASEDIR.'/server/utils/UrlUtils.php';
$clientIP = WW_Utils_UrlUtils::getClientIP();
$clientName = isset( $_SERVER['REMOTE_HOST'] ) ? $_SERVER['REMOTE_HOST'] : '';
if( !$clientName || ( $clientName == $clientIP ) ) {
	$clientName = gethostbyaddr( $clientIP );
}

require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOnRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOnResponse.class.php';
$req = new PlnLogOnRequest(
	$suiteOpts['User'],
	$suiteOpts['Password'],
	'',
	'Enterprise Server',
	$clientName,
	'',
	'clientplan.php',
	'v'.SERVERVERSION,
	'',
	'' );
$logOnResp = $clientProxy->call( 'LogOn', $req );
if( is_null( $logOnResp ) ) {
	exit();
}
$sTicket = $logOnResp->Ticket;

////////////////////////////////////////////////////////////////////////
// CreateLayouts.
////////////////////////////////////////////////////////////////////////
if( $actionCreate == 'all' || $actionCreate == 'layout' ) {
	// Make up page range for layout when not given.
	$layoutPages = array();
	if( $createPageOrdersByEditions ) foreach( $createPageOrdersByEditions as $layoutEditionName => $pageOrdersByEdition ) {
		$pageSequence = 1;
		if( $pageOrdersByEdition ) foreach( $pageOrdersByEdition as $pageOrder ) {
			$pageEdition = new Edition( null, $layoutEditionName );
			$layoutPages[] = new PlnPage(
				$pageOrder,
				$pageToCreateWidth[ $pageSequence ],
				$pageToCreateHeight[ $pageSequence ],
				null,
				$pageEdition,
				$pageToCreateMasterPage[ $pageSequence ],
				$pageSequence,
				formatPageNumber( $pageOrder, $numberStyle, $numberPrefix ) );
			$pageSequence++;
		}
	}
	if( $createPagesWithoutEditions ) {
		$pageSequence = 1;
		if( $createPageOrdersNoEdition ) foreach( $createPageOrdersNoEdition as $pageOrder ) {
			$layoutPages[] = new PlnPage(
				$pageOrder,
				$pageToCreateWidth[ $pageSequence ],
				$pageToCreateHeight[ $pageSequence ],
				null,
				null,
				$pageToCreateMasterPage[ $pageSequence ],
				$pageSequence,
				formatPageNumber( $pageOrder, $numberStyle, $numberPrefix ) );
			$pageSequence++;
		}
	}

	// put passed editions in SOAP.
	$layoutEditions = null;
	if( count( $layoutEditionNames ) > 0 ) {
		$layoutEditions = array();
		foreach( $layoutEditionNames as $edition ) {
			$layoutEditions[] = new Edition( null, $edition );
		}
	}

	// Build and fire layout SOAP request.
	$newLayout = new PlnLayout(
		null,
		$layoutName,
		$publication,
		$issue,
		$channelName,
		$layoutSection,
		$layoutStatus,
		$layoutPages,
		$layoutEditions,
		$layoutDeadline,
		null );
	$layouts = array( new PlnLayoutFromTemplate( $newLayout, $layoutTemplateName ) );

	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateLayoutsResponse.class.php';
	$req = new PlnCreateLayoutsRequest( $sTicket, $layouts );
	$resp = $clientProxy->call( 'CreateLayouts', $req );
	$layoutId = is_null( $resp ) ? 0 : $resp->Layouts[0]->Id;
	if( is_null( $resp ) ) {
		$e = $clientProxy->getSoapFault();
		if( $e &&
			stripos( $e->getMessage(), '(S1029)' ) !== false &&
			stripos( $e->detail, 'Template:' ) !== false
		) {
			print '<span style="color:#ff0000"><b>Error:</b> The layout template '.$layoutTemplateName.' could not be found.</span><br/>'.
				'Please create a layout template with that name (using InDesign) or specify any template that does '.
				'already exist in DB using the lay_template parameter at the URL.<br/>';
			die();
		}
	} else {
		$clientProxy->showDetails( 'Created layout "'.$resp->Layouts[0]->Name.'" (Id='.$resp->Layouts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// ModifyLayouts.
////////////////////////////////////////////////////////////////////////

if( $actionModify == 'all' || $actionModify == 'layout' ) {
	// Make up page range for layout when not given.
	$layoutPages = array();
	if( $modifyPageOrdersByEditions ) foreach( $modifyPageOrdersByEditions as $layoutEditionName => $pageOrdersByEdition ) {
		$pageSequence = 1;
		if( $pageOrdersByEdition ) foreach( $pageOrdersByEdition as $pageOrder ) {
			$pageEdition = new Edition( null, $layoutEditionName );
			$layoutPages[] = new PlnPage(
				$pageOrder,
				$pageToModifyWidth[ $pageSequence ],
				$pageToModifyHeight[ $pageSequence ],
				null,
				$pageEdition,
				$pageToModifyMasterPage[ $pageSequence ],
				$pageSequence,
				formatPageNumber( $pageOrder, $numberStyle, $numberPrefix ) );
			$pageSequence++;
		}
	}
	if( $modifyPagesWithoutEditions ) {
		$pageSequence = 1;
		if( $modifyPageOrdersNoEdition ) foreach( $modifyPageOrdersNoEdition as $pageOrder ) {
			$layoutPages[] = new PlnPage(
				$pageOrder,
				$pageToModifyWidth[ $pageSequence ],
				$pageToModifyHeight[ $pageSequence ],
				null,
				null,
				$pageToModifyMasterPage[ $pageSequence ],
				$pageSequence,
				formatPageNumber( $pageOrder, $numberStyle, $numberPrefix ) );
			$pageSequence++;
		}
	}

	// put passed editions in SOAP.
	$layoutEditions = null;
	if( count( $layoutEditionNames ) > 0 ) {
		$layoutEditions = array();
		foreach( $layoutEditionNames as $edition ) {
			$layoutEditions[] = new Edition( null, $edition );
		}
	}

	// build and fire layout SOAP request.
	if( $nameBased == 'true' ) {
		$layout = new PlnLayout(
			null,
			$layoutName,
			$publication,
			$issue,
			$channelName,
			$layoutSection,
			$layoutStatus,
			$layoutPages,
			$layoutEditions,
			$layoutDeadline,
			null );
	} else {
		$layout = new PlnLayout(
			$layoutId, null, null, null, null, $layoutSection, $layoutStatus, $layoutPages, $layoutEditions, $layoutDeadline, null );
	}

	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyLayoutsResponse.class.php';
	$req = new PlnModifyLayoutsRequest( $sTicket, array( $layout ) );
	$resp = $clientProxy->call( 'ModifyLayouts', $req );
	if( !is_null( $resp ) ) {
		$clientProxy->showDetails( 'Modified layout "'.$resp->Layouts[0]->Name.'" (Id='.$resp->Layouts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// CreateAdverts.
////////////////////////////////////////////////////////////////////////

if( $actionCreate == 'all' || $actionCreate == 'advert' ) {
	$advertHighResFile = null;
	$advertFile = null;
	if( $content == 'preview' ) {
		$advertFile = new Attachment( $content, 'image/jpeg',
			new SOAP_Attachment( 'Content', 'application/octet-stream', 'testdata/preview1page1.jpg' ) );
	} else if( $content == 'output' ) {
		$advertFile = new Attachment( $content, 'application/pdf',
			new SOAP_Attachment( 'Content', 'application/octet-stream', 'testdata/output1page1.pdf' ) );
	} else if( $content == 'highresfile' ) {
		if( $advertHighResFilename == null ) {
			$advertHighResFile = $sHighResStore.'testdata/output1page1.pdf';
		} else {
			$advertHighResFile = $sHighResStore.$advertHighResFilename;
		}
	}
	// put passed editions in SOAP.
	$advertEditions = null;
	if( count( $advertEditionNames ) > 0 ) {
		$advertEditions = array();
		foreach( $advertEditionNames as $edition ) {
			$advertEditions[] = new Edition( null, $edition );
		}
	}

	$page = null;
	$columns = rand( 1, 4 );
	$placement = new PlnPlacement(
		$advertCreateLeftPosition,
		$advertCreateTopPosition,
		$columns,
		$advertCreateWidth,
		$advertCreateHeight, null,
		$advertLayerName,
		$advertCreateFrameDeltaX,
		$advertCreateFrameDeltaY,
		$advertCreateFrameScaleX,
		$advertCreateFrameScaleY );
	$advert = new PlnAdvert(
		null,
		null,
		$publication,
		$issue,
		$channelName,
		$advertSection,
		$advertStatus,
		$advertName,
		'my type',
		$advertCreateComment,
		'my customer',
		'my color',
		$content == 'description' ? $advertCreateDescription : null,
		$content == 'plaincontent' ? $advertCreatePlainContent : null,
		$advertFile,
		$advertHighResFile,
		$createPageOrdersNoEdition[ $advertCreatePageNr - 1 ],
		$page,
		$placement,
		'Left Top',
		'ShouldHave',
		5.50,
		$advertEditions,
		$advertDeadline,
		$advertCreatePageNr, null );

	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnCreateAdvertsResponse.class.php';
	if( $nameBased == 'true' ) {
		$req = new PlnCreateAdvertsRequest( $sTicket, null, $layoutName, array( $advert ) );
	} else {
		$req = new PlnCreateAdvertsRequest( $sTicket, $layoutId, null, array( $advert ) );
	}
	$resp = $clientProxy->call( 'CreateAdverts', $req );
	$advertId = is_null( $resp ) ? 0 : $resp->Adverts[0]->Id;
	if( !is_null( $resp ) ) {
		$clientProxy->showDetails( 'Created advert "'.$resp->Adverts[0]->Name.'" (Id='.$resp->Adverts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// ModifyAdverts.
////////////////////////////////////////////////////////////////////////

if( $actionModify == 'all' || $actionModify == 'advert' ) {
	$columns = rand( 1, 4 );
	$advertFile = null;
	$advertHighResFile = null;
	if( $content == 'preview' ) {
		$advertFile = new Attachment( $content, 'image/jpeg',
			new SOAP_Attachment( 'Content', 'application/octet-stream', 'testdata/preview1page2.jpg' ) );
	} else if( $content == 'output' ) {
		$advertFile = new Attachment( $content, 'application/pdf',
			new SOAP_Attachment( 'Content', 'application/octet-stream', 'testdata/output1page2.pdf' ) );
	} else if( $content == 'highresfile' ) {
		if( $advertHighResFilename == null ) {
			$advertHighResFile = $sHighResStore.'testdata/output1page2.pdf';
		} else {
			$advertHighResFile = $sHighResStore.$advertHighResFilename;
		}
	}
	// put passed editions in SOAP.
	$advertEditions = null;
	if( count( $advertEditionNames ) > 0 ) {
		$advertEditions = array();
		foreach( $advertEditionNames as $edition ) {
			$advertEditions[] = new Edition( null, $edition );
		}
	}

	$page = null;
	$placement = new PlnPlacement(
		$advertModifyLeftPosition,
		$advertModifyTopPosition,
		$columns,
		$advertModifyWidth,
		$advertModifyHeight,
		null,
		$advertLayerName,
		$advertModifyFrameDeltaX,
		$advertModifyFrameDeltaY,
		$advertModifyFrameScaleX,
		$advertModifyFrameScaleY );
	$advert = new PlnAdvert(
		$nameBased == 'true' ? null : $advertId,
		null,
		$publication,
		$issue,
		$channelName,
		$advertSection,
		$advertStatus,
		$advertName,
		'my type2',
		$advertModifyComment,
		'my customer2',
		'my color2',
		$content == 'description' ? $advertModifyDescription : null,
		$content == 'plaincontent' ? $advertModifyPlainContent : null,
		$advertFile,
		$advertHighResFile,
		$modifyPageOrdersNoEdition[ $advertModifyPageNr - 1 ],
		$page,
		$placement,
		'Lower Part',
		'MustHave', 6.75,
		$advertEditions,
		$advertDeadline,
		$advertModifyPageNr,
		null );

	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnModifyAdvertsResponse.class.php';
	if( $nameBased == 'true' ) {
		$req = new PlnModifyAdvertsRequest( $sTicket, null, $layoutName, array( $advert ) );
	} else {
		$req = new PlnModifyAdvertsRequest( $sTicket, $layoutId, null, array( $advert ) );
	}
	$resp = $clientProxy->call( 'ModifyAdverts', $req );
	if( !is_null( $resp ) ) {
		$clientProxy->showDetails( 'Modified advert "'.$resp->Adverts[0]->Name.'" (Id='.$resp->Adverts[0]->Id.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// DeleteAdverts.
////////////////////////////////////////////////////////////////////////
if( $actionDelete == 'all' || $actionDelete == 'advert' ) {
	if( $nameBased == 'true' ) {
		$advert = new PlnAdvert( null, null, $publication, $issue, $channelName, null, null, $advertName );
	} else {
		$advert = new PlnAdvert( $advertId );
	}

	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteAdvertsResponse.class.php';
	if( $nameBased == 'true' ) {
		$req = new PlnDeleteAdvertsRequest( $sTicket, null, $layoutName, array( $advert ) );
	} else {
		$req = new PlnDeleteAdvertsRequest( $sTicket, $layoutId, null, array( $advert ) );
	}
	$resp = $clientProxy->call( 'DeleteAdverts', $req );
	if( !is_null( $resp ) ) {
		$clientProxy->showDetails( 'Deleted advert "'.$advertName.'" (Id='.$advertId.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// DeleteLayouts.
////////////////////////////////////////////////////////////////////////
if( $actionDelete == 'all' || $actionDelete == 'layout' ) {
	if( $nameBased == 'true' ) {
		$layout = new PlnLayout( null, $layoutName, $publication, $issue, $channelName );
	} else {
		$layout = new PlnLayout( $layoutId );
	}
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsRequest.class.php';
	require_once BASEDIR.'/server/interfaces/services/pln/PlnDeleteLayoutsResponse.class.php';
	$req = new PlnDeleteLayoutsRequest( $sTicket, array( $layout ) );
	$resp = $clientProxy->call( 'DeleteLayouts', $req );
	if( !is_null( $resp ) ) {
		$clientProxy->showDetails( 'Deleted layout "'.$layoutName.'" (Id='.$layoutId.')' );
	}
}

////////////////////////////////////////////////////////////////////////
// LogOff.
////////////////////////////////////////////////////////////////////////
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOffRequest.class.php';
require_once BASEDIR.'/server/interfaces/services/pln/PlnLogOffResponse.class.php';
$req = new PlnLogOffRequest( $sTicket );
$clientProxy->call( 'LogOff', $req );

function validateURLParameters()
{
	if( isset( $_GET['startpage'] ) ) {
		die( 'The \'startpage\' parameter is obsoleted. Please use \'pagenrs\' instead.' );
	}
	if( isset( $_GET['pagecount'] ) ) {
		die( 'The \'pagecount\' parameter is obsoleted. Please use \'pagenrs\' instead.' );
	}
	if( isset( $_GET['pag_editions'] ) ) {
		die( 'The \'pag_editions\' parameter is obsoleted. Please use \'pagenrs[_edition]\' instead.' );
	}
}

function createPagesTable( $pageOrders, $pageHeight, $pageWidth, $pageMaster, $numberStyle, $numberPrefix, &$pageToCreateHeight, &$pageToCreateWidth, &$pageToCreateMasterPage )
{
	$rows = array();
	$table = '<table border="1">';
	$pageSequence = 1;
	if( $pageOrders ) foreach( $pageOrders as $pageOrder ) {
		$rows[] = '<td>PageSequence='.$pageSequence.'</td>';
		$rows[] = '<td>PageOrder='.$pageOrder.'</td>';
		$rows[] = '<td>PageNumber='.formatPageNumber( $pageOrder, $numberStyle, $numberPrefix ).'</td>';
		$pageToCreateHeight[ $pageSequence ] = isset( $_GET[ 'pag'.$pageSequence.'_height' ] ) ? $_GET[ 'pag'.$pageSequence.'_height' ] : $pageHeight;
		$rows[] = '<td>Height='.$pageToCreateHeight[ $pageSequence ].'</td>';
		$pageToCreateWidth[ $pageSequence ] = isset( $_GET[ 'pag'.$pageSequence.'_width' ] ) ? $_GET[ 'pag'.$pageSequence.'_width' ] : $pageWidth;
		$rows[] = '<td>Width='.$pageToCreateWidth[ $pageSequence ].'</td>';
		$pageToCreateMasterPage[ $pageSequence ] = isset( $_GET[ 'pag'.$pageSequence.'_master' ] ) ? $_GET[ 'pag'.$pageSequence.'_master' ] : $pageMaster;
		$rows[] = '<td>Master='.$pageToCreateMasterPage[ $pageSequence ].'</td>';
		$pageSequence++;
	}
	foreach( $rows as $row ) {
		$table = $table.'<tr>'.$row.'</tr>';
	}
	$table .= '</table>';
	return $table;
}

function formatPageNumber( $pageOrder, $numberStyle, $numberPrefix )
{
	$pageNumber = $numberPrefix;
	$convertedPageNumber = $pageOrder;
	if( $numberStyle ) {
		require_once BASEDIR.'/server/utils/NumberUtils.class.php';
		if( $numberStyle == 'roman' ) {
			$convertedPageNumber = NumberUtils::toRomanNumber( intval( $pageOrder ) );
		} elseif( $numberStyle == 'alpha' ) {
			$convertedPageNumber = NumberUtils::toAlphaNumber( intval( $pageOrder ) );
		}
	}
	$pageNumber .= $convertedPageNumber;

	return $pageNumber;
}

class PlanClientProxy
{
	private $watch = null;
	private $client = null;
	private $soapFault = null;

	public function __construct( StopWatch $watch )
	{
		require_once BASEDIR.'/server/protocols/soap/PlnClient.php';
		$this->client = new WW_SOAP_PlnClient();
		$this->watch = $watch;
	}

	public function showDetails( $details )
	{
		echo '<span style="color:gray">&nbsp;&nbsp;&nbsp;-&nbsp;'.$details.'</span><br/>';
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -.
	// internal helper functions that do logging at screen.

	private function showOperation( $operation )
	{
		echo '<hr/><span style="color:blue"><b>Operation: </b>'.$operation.'</span><br/>';
	}

	private function showDuration( $duration )
	{
		echo '<b>Duration: </b>'.$duration.' sec<br/>';
	}

	private function showError( $error )
	{
		echo '<span style="color:red"><b>ERROR: </b>'.$error.'</span><br/>';
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

	/**
	 * @return SoapFault The Soap fault.
	 */
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
		} catch( SoapFault $e ) {
			$this->soapFault = $e;
			$this->showException( $e, $this->watch->Fetch() );
		} catch( BizException $e ) {
			$this->showException( $e, $this->watch->Fetch() );
		}
		$error = !isset( $return ) || is_soap_fault( $return );
		return $error ? null : $return;
	}
}
