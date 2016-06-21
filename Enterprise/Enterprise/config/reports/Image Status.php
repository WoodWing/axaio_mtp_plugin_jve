<?php

function SCEntReport( $inPub, $inIssue )
{
	// Initialize parameters passed by reference.
	$colors = '';
	$graph = array();

	// First get associative array with status/counts and list of colors for these status
	$reportutils = new scent_reportutils;
	$reportutils->getColorList( $inPub, $inIssue, "Image", $graph, $colors );

	// Result into a table: first column pie chart:
	$txt = "<table><tr><td>";
	$txt .= graphpie($graph,450, $colors);

	// second column list of status and the count of objects
	$i = 0;
	$colorArr = explode( ',', $colors );
	$boxSize = preg_match("/safari/", strtolower($_SERVER['HTTP_USER_AGENT'])) ? 10 : 13;
	$txt .= "</td><td><table cellspacing=10 bgcolor=#eeeeee>";
	foreach ($graph as $key => $value) {
		$color = '#'.$colorArr[$i++];
		$boxHtml = "<table border='1' style='border-collapse: collapse' bordercolor='#606060' height='$boxSize' width='$boxSize'><tr>\t<td bgColor='$color'></td></tr></table>";
		$txt .= "<tr><td>$boxHtml</td><td>$key</td><td align=\"right\">$value</td></tr>";
	}
	$txt .= "</table></td>";
	
	return $txt;
}