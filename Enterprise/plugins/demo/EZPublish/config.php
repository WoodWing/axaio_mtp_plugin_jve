<?php
// URL to the eZ Publish server
if( !defined('EZPUBLISH_URL') ) {
	define( 'EZPUBLISH_URL', 'localhost' );
}
// Path to soap.php on server
if( !defined('EZPUBLISH_PATH_ON_SERVER') ) {
	define( 'EZPUBLISH_PATH_ON_SERVER', '/ezpublishdemo/soap.php' );
}
// URL for the eZ Publish frontend, including /
if( !defined('EZPUBLISH_EXTERNAL_URL') ) {
	define( 'EZPUBLISH_EXTERNAL_URL', 'http://localhost/ezpublishdemo/' );
}

// eZ Publish username
if( !defined('EZPUBLISH_USERNAME') ) {
	define( 'EZPUBLISH_USERNAME', 'woodwing' );
}
// eZ Publish password
if( !defined('EZPUBLISH_PASSWORD') ) {
	define( 'EZPUBLISH_PASSWORD', 'ww' );
}
?>