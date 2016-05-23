<?php
////// graphics
function graphbar ($stat, $sz=30)
{
	$out = '';
	$w = 20+$sz*count($stat); if ($w <120) $w=120;
	$r = INETROOT;
	$out .= "<applet code=javachart.applet.columnApp.class archive=\"javachart/jars/columnApp.jar\" codebase=$r/server/ width=$w height=120>";
	$out .= '<param name=appletKey value="6115-370">';
	$out .= '<param name=dataset0Color value="green">';
	$out .= '<param name=3D value="yes">';
	$out .= '<param name=xDepth value="10">';
	$out .= '<param name=yDepth value="10">';
	$out .= '<param name=outlineColor value=lightGray>';
	$out .= graphdata($stat, 'bar');
	$out .= "</applet>\n";

	return $out;
}

function graphtime ($stat,$df,$dt)
{

	$out = '';
	$r = INETROOT;
	$out .= "<applet code=javachart.applet.dateAreaApp.class archive=\"javachart/jars/dateAreaApp.jar\" codebase=$r/server/ width=300 height=100>";
	$out .= '<param name=appletKey value="6115-370">';
	$out .= '<param name=dataset0Color value="green">';
	$out .= '<param name=3D value="yes">';
	$out .= '<param name=xDepth value="5">';
	$out .= '<param name=yDepth value="5">';
	$out .= '<param name=inputDateFormat value="dd-MM-yyyy">';
	$out .= '<param name=yAxisStart value=0>';
	$out .= '<param name=outlineColor value=lightGray>';
$out .= "<param name=startDate value=$df>";				// ## temp vals argh
$out .= "<param name=endDate value=$dt>";
	$out .= graphdata($stat, 'time');
	$out .= "</applet>\n";

	return $out;
}

function graphpie ($stat, $w=200, $colormap=null)
{
	if ($colormap == null) $colormap = "green,yellow,blue,orange,blue,yellow";
	$out = '';
	$r = INETROOT;
	$out .= "<applet code=javachart.applet.pieApp.class archive='javachart/jars/pieApp.jar' codebase=$r/server/ width=$w height=$w>";
	$out .= '<param name=appletKey value="6115-370">';
	$out .= '<param name=dataset0Colors value="'.$colormap.'">';
	$out .= '<param name=3D value="yes">';
	$out .= '<param name=textLabelsOn value="anything">';
	$out .= '<param name=pieHeight value="0.4">';
	$out .= '<param name=yOffset value="30">';
	$out .= '<param name=outlineColor value=lightGray>';
	$out .= graphdata($stat, 'pie');
	$out .= "</applet>\n";

	return $out;
}

function graphdata ($stat, $mode)
{
	$out = '';
	if ($mode == 'bar') {

		$out .= '<param name=xAxisLabels value="';

		$i = 0;
		foreach (array_keys($stat) as $key) {
			if ($i++) $out .= ',';
			if (!$key) $key = '.';
			$out .= $key;
		}
		$out .= "\">\n";
	}

	switch ($mode) {
		case 'pie':
			$txt = 'Labels';
			break;
		case 'bar':
			$txt = 'xValues';
			break;
		case 'time':
			$txt = 'dateValues';
			break;
	}
	$out .= "<param name=dataset0$txt value=\"";
	$i = 0;
	foreach (array_keys($stat) as $key) {
		if ($i++) $out .= ',';
		if (!$key) $key = '.';
		$out .= $key;
	}

	$out .= '"><param name=dataset0yValues value="';
	$i = 0;
	foreach ($stat as $wrd) {
		if ($i++) $out .= ',';
		$out .= $wrd;
	}
	$out .= '">';

	return $out;
}
?>