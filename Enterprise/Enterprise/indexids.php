<?php

// Returns the WSDL for InDesign Server (with patches locations)

require_once dirname(__FILE__).'/config/config.php';

if (isset( $_GET['wsdl']) && isset($_GET['server']) ) {

	// read the original WSDL
	$contents = file_get_contents( BASEDIR.'/server/interfaces/IDSP.wsdl' );

	// replace the IDS location with the real one
	$serverUrl = urlencode($_GET['server']);
	$contents = str_replace( 'location="http://localhost:80"', 'location="'.$serverUrl.'"', $contents );

	// output patched WSDL
	header( 'Content-type: text/xml' );
	header( 'Content-Length: '.strlen($contents) ); // required for PHP v5.3
	print $contents;
}