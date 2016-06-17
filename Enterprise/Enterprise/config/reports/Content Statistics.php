<?php

function SCEntReport( $inPub, $inIssue )
{
	require_once dirname(__FILE__).'/../config.php';
	require_once BASEDIR.'/server/dbclasses/DBObject.class.php';

	$rows = DBObject::getObjectCountsPerType( $inIssue, true );
	$graph = array();
	if ( $rows ) foreach ( $rows as $type => $total ) {
		$graph[$type] = $total;
	}

	// Result into a table: first column pie chart:
	$txt = "<table><tr><td>";
	$txt .= graphbar($graph,125);

	// second column list of status and the count of objects
	$txt .= "</td><td><table cellspacing=10 bgcolor=#eeeeee>";
	foreach ($graph as $key => $value)
		$txt .= "<tr><td border=0>".$key."</td><td align=\"right\">$value</td></tr>";
	$txt .= "</table></td>";

	return $txt;
}
