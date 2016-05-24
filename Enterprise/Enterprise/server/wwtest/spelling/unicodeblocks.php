<?php
/**
 * Test application for manually testing all kind of installed spelling integrations.
 * Those integrations are established through Server Plug-ins.
 *
 * @package Enterprise
 * @subpackage Core
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/../../../config/config.php';
require_once BASEDIR.'/server/secure.php';

/*$ticket =*/ checkSecure('publadmin');

?>
<html>
<head>
	<title>Enterprise Spelling - Workbench</title>
	<meta http-equiv="Content-Type" content="text/plain; charset=UTF-8" />
	<style type="text/css">
		table { border: 1px; border-spacing: 0px; empty-cells: show; }
	</style>
</head>
<body style="font-family: Arial;">
	<h1>Enterprise Spelling - Unicode Blocks</h1>
	<p>Inspection tool to determine Unicode character ranges to use in regular expressions for the 
Spelling configuration. Those expressions needs to be filled in at the 'wordchars' setting of the 
ENTERPRISE_SPELLING option at the configserver.php file.</p>
<?php

if( isset($_REQUEST['from']) ) {
	$from = hexdec($_REQUEST['from']);
	$to = hexdec($_REQUEST['to']);
	$rows = 20;
	$cols = floor( ($to-$from) / $rows );
	print '<table cellpadding="8"><tr>';
	for( $col = 0; $col <= $cols; $col++ ) {
		print '<td valign="top"><table border="1" cellpadding="3"><thead><tr align="left"><th>Hex</th><th>Char</th></tr></thead><tbody>';
		$i = 0;
		for( $c = $from + ($col*20); $c <= $to && $i < 20; $c++ ) {
			$cHex = strtoupper( dechex( $c ) );
			$cHex1 = substr( $cHex, 0, 2 );
			$cHex2 = substr( $cHex, 2, 2 );
			$href = 'http://www.fileformat.info/info/unicode/char/'.$cHex.'/index.htm';
			print '<tr align="left"><td><a href="'.$href.'">'.$cHex.'</a></td><td>'.'&#x'.$cHex1.$cHex2.';'.'</td></tr>';
			$i++;
		}
		print '</tbody></table></td>';
	}
	print '</tr></table>';
} else {
	$domDoc = new DomDocument();
	$domDoc->load( dirname(__FILE__).'/unicodeblocks.xml' );
	$xpath = new DOMXPath( $domDoc );
	$uniBlocks = $xpath->query( '//UnicodeBlocks/ub' );
	//print 'blocks:'.$uniBlocks->length.'<br/>';
	$thisUrl = explode( '?', SERVERURL_SCRIPT );
	print '<table border="1" cellpadding="3"><thead><tr align="left"><th>Hex Range</th><th>Unicode Block</th></tr></thead><tbody>';
	foreach( $uniBlocks as $uniBlock ) {
		$c1 = $uniBlock->firstChild;
		$c2 = $c1->nextSibling;
		$c3 = $c2->nextSibling;
		$href = htmlspecialchars($thisUrl[0]).'?from='.$c2->textContent.'&to='.$c3->textContent;
		print '<tr align="left"><td>'.$c2->textContent.' - '.$c3->textContent.'</td><td><a href="'.$href.'">'.$c1->textContent.'</a></td></tr>';
	}
	print '</tbody></table></td>';
}
?>
</body>
</html>