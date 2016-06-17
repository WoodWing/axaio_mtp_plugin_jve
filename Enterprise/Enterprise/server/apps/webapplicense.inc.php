<?php
require_once BASEDIR.'/server/apps/functions.php';

function logoffWebApps()
{
	$webappproductcodes = array( 'ContentStationPro700' );
	foreach( $webappproductcodes as $webappproductcode ) {
		//Make it unique for different applications...
		$cookiekey = 'webapp_ticket_' . $webappproductcode;
		$dum = '';
		$webappticket = '';
		cookie($cookiekey, true, $webappticket, $dum, $dum, $dum, $dum, $dum, $dum);
		//Ticket present in cookie?
		if ( $webappticket ) {
			$newwebappticket = '';
			//Reset the ticket in the cookie, one can retry now
			cookie($cookiekey, false, $newwebappticket, $dum, $dum, $dum, $dum, $dum, $dum);

			LogOff::execute( $webappticket );
		}
	}
}
?>