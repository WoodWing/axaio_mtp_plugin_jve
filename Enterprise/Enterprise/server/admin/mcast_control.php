<?php
// Note: This script is not multilingual.
//
// Reason: this script targets a special group of users and is not available through the menus 
// for users or "normal" administrators.
//
// Assumption: Users with thorough knowledge of Python do understand English.
// No translations in other languages are therefore required.
//

require_once dirname(__FILE__).'/../../config/config.php';

define( 'BODYCHECK_PORT', 27123 );

$cmdmode = @$_GET['cmdmode'];

$multiGroup = BizSettings::getFeatureValue('MulticastGroup');
$eventPort = BizSettings::getFeatureValue('EventPort');

print "<html><head><title>SCE Multicast Mediator</title></head>
<SCRIPT LANGUAGE='JavaScript'>
function changeMode(newMode)
{
	document.location.href = \"mcast_control.php?cmdmode=\"+newMode;
}
</SCRIPT>
<body>
<h2>Multicast Mediator Control Panel</h2>
<div>This control panel allows you to start and stop the Enterprise Multicast Mediator.
The mediator needs to run on the server if you want to use multicast messaging (note: not for broadcasting). 
It is a process written in Python that does the real multicast messaging.
Note that this is needed as long as PHP does not support multicast messaging.
This also implies that a Python installation is required to be installed on the system where Enterprise server is installed.
Instead of using this control panel, the mediator can also be started through a command line, which can be useful for auto start during a server boot. 
For more details, click the Help button or type the following on a command prompt:</div><div style='font-style:italic'> &gt; mcast_mediator.py -h</div>
<br>More info:<ul>
<li><a href='https://en.wikipedia.org/wiki/Multicast'>Multicasting</a></li>
<li><a href='http://www.iana.org/assignments/multicast-addresses'>Multicasting addresses</a></li>
<li><a href='http://www.python.org'>Python</a></li>
</ul>
<hr>
<h2>Current Settings</h2>
<table cellpadding='10' border='1' style='border-collapse: collapse'>
<tr><td width='30%'><b>INPUT:</b></td><td width='20%'>&nbsp;</td><td width='30%'><b>OUTPUT:</b></td><td width='20%'>&nbsp;</td></tr>
<tr><td>MC_MEDIATOR_ADDRESS</td><td>".MC_MEDIATOR_ADDRESS."</td><td>MulticastGroup</td><td>".$multiGroup."</td></tr>
<tr><td>MC_MEDIATOR_PORT</td><td>".MC_MEDIATOR_PORT."</td><td>EventPort</td><td>".$eventPort."</td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>MULTICAST_TTL</td><td>".MULTICAST_TTL."</td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td><td>MULTICAST_IF</td><td>".MULTICAST_IF."</td></tr>
</table><br><br>
<input type='button' value=' Start Mediator ' onClick='javascript:changeMode(\"startMM\")'>
<input type='button' value=' Exit Mediator ' onClick='javascript:changeMode(\"exitMM\")'>
<input type='button' value=' Check Mediator ' onClick='javascript:changeMode(\"checkMM\")'>
<input type='button' value=' Help ' onClick='javascript:changeMode(\"helpMM\")'>
<input type='button' value=' Refesh ' onClick='javascript:changeMode(\"\")'>
<hr>";

if( $cmdmode == "exitMM" )
{
	print "<h2>Exit Multicast Mediator</h2>";
	if(($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) >= 0) 
	{
		$message = "Mediator_Exit"; //reserved message to exit the mediator process
		if(($sendret = socket_sendto($sock, $message, strlen($message), 0, MC_MEDIATOR_ADDRESS, MC_MEDIATOR_PORT)) >= 0) 
			print( "...completed<br>" );
		else
			print( "<b><font color='red'/> failed.</font></b> (could not send message)<br>" );
	}
	else
		print( "<b><font color='red'/> failed.</font></b> (could not create socket)<br>" );
}

if( $cmdmode == "checkMM" )
{
	print "<h2>Body Check Multicast Mediator</h2>";

	// create the socket to receive data from body check
	$sockBC = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
	socket_set_nonblock($sockBC);
	if( !socket_setopt( $sockBC, SOL_SOCKET, SO_REUSEADDR, 1 ) )
		print "ERROR: socket_setopt failed.<br>";
	if( !socket_bind( $sockBC, "0.0.0.0", BODYCHECK_PORT ) )
		print "ERROR: bind socket failed.<br>";
	$sockBCref[] =& $sockBC;  // create a reference to the socket as an array

	// ask mediator to respond on body check
	if(($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) >= 0) 
	{
		$message = "Mediator_BodyCheck"; //reserved message to exit the mediator process
		if(($sendret = socket_sendto($sock, $message, strlen($message), 0, MC_MEDIATOR_ADDRESS, MC_MEDIATOR_PORT)) >= 0) {

			// get data from body check
			socket_select( $sockBCref, $write = NULL, $except = NULL, 5 );  // 5 sec. timout
			$bugBC = '';
			$clientIP = '';
			$clientPort = 0;
			@socket_recvfrom( $sockBC, $bugBC, 8192, 0, $clientIP, $clientPort );
			if( $bugBC == "Multicast Mediator is alive!" )
				print( "The Multicast Mediator is <b><font color='green'/>still alive.</font></b>! Received signal from IP=[$clientIP] Port=[$clientPort]<br>" );
			else
				print( "<b><font color='red'/>WARNING: The Multicast Mediator seems to be dead.</font></b><br>" );
		}
		else
			print( "<b><font color='red'/> failed.</font></b> (could not send message)<br>" );
	}
}

if( $cmdmode == "startMM" )
{
	print "<h2>Starting Multicast Mediator...</h2>";
	if( isset($eventPort) && isset($multiGroup) )
	{
		ob_start();
		$mediatorScript = './mcast_mediator.py';
		if ( OS == 'WIN') {
			$start = 'START /B';
			$end = '1 > NUL';
		} else {
			$start = 'python';
			$end = ' 1> /dev/null & ';
		}
		$cmd = $start.' '.$mediatorScript.' -a '.MC_MEDIATOR_ADDRESS.' -i '.MC_MEDIATOR_PORT.' -g '.$multiGroup.
				' -o '.$eventPort.' -t '.MULTICAST_TTL.' -n '.MULTICAST_IF.' -b '.BODYCHECK_PORT.' '.$end;
		print "<b>Command:</b><pre>$cmd</pre>";
		ob_end_flush();
		flush();
		//synchron:
		$ret = null;
		$output = array();
		exec( $cmd, $output, $ret );
		print "<pre>Select <b>'Check Mediator'</b> to verify if the Mediator is running.</pre>";
	}
	else
		print( "<b><font color='red'/>Multicast Group or Port not defined.</font></b><br>" );
}

if( $cmdmode == "helpMM" )
{
	print "<h2>Multicast Mediator Help...</h2>";
	$cmd = "mcast_mediator.py -h";
	print "<b>Command:</b><pre>$cmd</pre>";
	$output = shell_exec($cmd);		
	print "<b>Output:</b><pre>$output</pre>";
}
print "</body></html>";
