<?php
//
// - Enterprise Server Event Monitor - 
//
// To start, don't open in browser, but type the following instruction at command line: 
//   php server.php
//

require_once dirname(__FILE__).'/../../config/config.php';

// Set time limit to indefinite execution 
set_time_limit (0); 

// Get socket port we will listen on for incomming broadcasts
$eventPort = BizSettings::getFeatureValue('EventPort');

define ('SERVERACTIONS', serialize(array( 
	"", // => 0
	"Logon",
	"Logoff",
	"CreateObject",
	"DeleteObject",
	"SaveObject",
	"SetObjectProperties",
	"SendTo",
	"LockObject",
	"UnlockObject",
	"CreateObjectRelation",
	"DeleteObjectRelation",
	"SendMessage",
	"UpdateObjectRelation" )));
$server_actions = unserialize(SERVERACTIONS);

define ('EVENTTYPES', serialize(array( "", "System", "Client", "User" )));
$event_types = unserialize(EVENTTYPES);

print "---------------------------------------------------------\r\n";
print "SCE Event Monitor\r\n";
print "---------------------------------------------------------\r\n";
print "Initializing...";
flush();

// Open socket
$socket = socket_create(AF_INET, SOCK_DGRAM, 0);
if (!$socket) die("Failed to create socket");

$res = socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
if (!$res) die ("Failed to set SO_REUSEADDR socket option");

$res = socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
if (!$res) die ("Failed to set SO_BROADCAST socket option");

if( BizSettings::isFeatureEnabled('MulticastGroup') )
{
	die ("No support for multicasting");
}
else
{
	$res = socket_bind($socket, 0, $eventPort);
	if (!$res) die ("Failed to bind socket for broadcasting");
}

echo "done!\r\nLogging events...\n";
flush();

while(true)
{
	ob_start();
	$ret = socket_select($c = array($socket), $a = null, $b=null, 0, 1024);
	if( $ret ) {
		// read broadcasted event data from socket
		$rawdata = socket_read( $socket, 1024 );

		/*
		// print raw
		print "READ: $rawdata\r\n";
				
		// print hex		
		for ($i=0; $i <strlen($rawdata); $i++)
			print dechex(ord(substr($rawdata,$i,1))).' ';
		print "\r\n";		
		*/
		
		// print event's header info
		$data = unpack("Cformat/Cevent/Ctype/Creserved", $rawdata);
		print "---------------------------------------------------------\r\n";
		//print $data['format']."/".$data['event']."/".$data['type']."/".$data['reserved']."\r\n";
		print "event : ".$server_actions[ $data['event'] ]."\r\n";
		print "type  : ".$event_types[ $data['type'] ]."\r\n";
		print "fields:\r\n"; 
		$offset = 4;
		
		// print event's fields
		while ($offset < strlen($rawdata)) 
		{
			// parse key
			$data = unpack("nsize", substr($rawdata,$offset) );
			$size = $data["size"];
			$fieldkey = substr($rawdata, $offset+2, $size);
			$offset += $size+2;

			// parse value
			$data = unpack("nsize", substr($rawdata,$offset) );
			$size = $data["size"];
			$fieldval = substr($rawdata, $offset+2, $size);
			$offset += $size+2;

			printf( "- %-20s: %s\r\n", $fieldkey, $fieldval);
		}
			
		flush();
		ob_flush();
	}
	
	ob_end_clean();
}

?>
