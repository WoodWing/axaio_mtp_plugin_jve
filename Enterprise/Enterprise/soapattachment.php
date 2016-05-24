<?php
/** 
  * Enterprise uses SOAP attachments over DIME. However, when client or server does not support
  * the DIME format, there is an alternative. The server then returns an URL to this PHP file
  * and specifies the file attachment as URL param.
  *
  * This PHP reads the specified file from file system, returns it through http stream and deletes(!) 
  * the file. It accepts "file" param that is located at the  ATTACHMENTDIRECTORY/_SYSTEM_/SoapCache/ 
  * folder. The "file" param may have one parent folder. Both file and parent folder are removed.
  * The "ticket" param should have a valid user ticket.
  * When no access or file missing, a HTTP 404 error is returned.
  
  * This module will be used by Web Editor and Flex, because JavaScript and Flex do NOT handle DIME 
  * (only PEAR/PHP server and C++/RB clients do). Note that workflow- and planning interfaces are 
  * implemented using PEAR (so they both do DIME), but the admin- and appservices are using PHP/SOAP 
  * (so they do NOT use DIME). Nevertheless, Flex does NOT use DIME, but wants to use the workflow 
  * interface (DIME). Therefor, calls like GetObjects will have to support BOTH. A flag needs to tell 
  * how the clients want to have returned their attachments.
  */

$ticket = $_REQUEST['ticket'];
$fileName = $_REQUEST['file'];
$fileName = urldecode( $fileName );

require_once dirname(__FILE__).'/config/config.php';
require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

require_once( BASEDIR . '/server/dbclasses/DBTicket.class.php' );
$user = DBTicket::checkTicket( $ticket );

$pathParts = pathinfo( $fileName );

// Stream the file
$filePath = ATTACHMENTDIRECTORY . '/_SYSTEM_/SoapCache/' . $fileName; 
if( $user && file_exists( $filePath ) ) {
	$mimeType = MimeTypeHandler::filePath2MimeType( $filePath );
	header('Cache-Control: maxage=5'); //Adjust maxage appropriately
	header('Pragma: public');
	header('Content-Type: '.$mimeType);
	header('Content-Disposition: filename="'.$pathParts['filename'].'"');
	header('Content-Length: '.filesize($filePath) );
	readfile( $filePath );

	// Remove the file
	unlink( $filePath );
} else {
	header("HTTP/1.1 404 Not Found");
	echo '<h2 class="center">Error 404 - Not Found</h2>';
}

// Remove folder if specified
$parentDir = $pathParts['dirname'];
if( !empty($parentDir) ) {
	if( !strpos($parentDir, '.') && !strpos($parentDir,'/') &&
	    !strpos($parentDir, '*') && !strpos($parentDir,'?') ) { // avoid mistakes and block hacks 
		if( is_dir(  ATTACHMENTDIRECTORY . '/_SYSTEM_/SoapCache/' .$parentDir ) ) {
			rmdir( ATTACHMENTDIRECTORY . '/_SYSTEM_/SoapCache/' . $parentDir );
		}
	}
}