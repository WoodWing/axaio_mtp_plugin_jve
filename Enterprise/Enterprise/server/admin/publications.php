<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

$ticket = checkSecure('publadmin');

// Handle sorting listed brands.
$recs = isset($_REQUEST['recs']) ? intval($_REQUEST['recs']) : 0;
if ($recs > 0) {
	for ($i = 1; $i < $recs; $i++) {
		$id = intval($_REQUEST["order$i"]);
		$code = intval($_REQUEST["code$i"]);
		$where = '`id` = ? ';
		$params = array( intval( $id ) );
		$row = array( 'code' => $code );
		DBBase::updateRow('publications', $row, $where, $params );
	}
}

// Get all brands for which the admin user has access.
$pubs = null;
try {
	require_once BASEDIR.'/server/services/adm/AdmGetPublicationsService.class.php';
	$service = new AdmGetPublicationsService();
	$request = new AdmGetPublicationsRequest();
	$request->Ticket = $ticket;
	$request->RequestModes = array();
	$response = $service->execute( $request );
	$pubs = $response->Publications;
} catch( BizException $e ) {
	// nothing much that could possibly go wrong
}

// Compose HTML page.
$txt = '';
$cnt = 0;
if( $pubs ) {
	require_once BASEDIR.'/server/dbclasses/DBAdmIssue.class.php';
	$pubIds = array_map( function( $pub ) { return $pub->Id; }, $pubs );
	$issueCounts = DBAdmIssue::countIssuesPerPublication( $pubIds );
	foreach( $pubs as $pub ) {
		$cnt++;
		// Compose HTML table row to list brand.
		$bx = inputvar("code$cnt", $pub->SortOrder, 'small').inputvar("order$cnt",$pub->Id,'hidden');
		$txt .= '<tr><td><a href="hppublications.php?id='.$pub->Id.'">'.formvar($pub->Name).'</a></td>'.
			'<td>'.$issueCounts[$pub->Id].'</td><td>'.$bx.'</td></tr>'."\r\n";
	}
}
$txt .= inputvar( 'recs', $cnt, 'hidden' );

// Show HTML page.
$txt = str_replace('<!--ROWS-->', $txt, HtmlDocument::loadTemplate( 'publications.htm' ) );
print HtmlDocument::buildDocument($txt);
