<?php
if( !defined('IMPREZZEO_INSTALLED') ) {
	define( 'IMPREZZEO_INSTALLED', false );
}
if( !defined('IMPREZZEO_SERVER') ) {
	define( 'IMPREZZEO_SERVER', 'localhost' );
}
if( !defined('IMPREZZEO_PORT') ) {
	define( 'IMPREZZEO_PORT', '8080' );
}
if( !defined('IMPREZZEO_PATH') ) {
	define( 'IMPREZZEO_PATH', '/imprezzeo-engine/services/imprezzeo-engine' );
}
if( !defined('IMPREZZEO_PROXY') ) {
	define( 'IMPREZZEO_PROXY', '' );
}
// In some cases we need to logon to Enterprise, for this we need a user account with read access to all images
if( !defined('IMPREZZEO_WWUSER') ) {
	//	define('IMPREZZEO_WWUSER',	'Imprezzeo' );
}
if( !defined('IMPREZZEO_SYNCHRONOUS_INDEX') ) {
	define( 'IMPREZZEO_SYNCHRONOUS_INDEX', true );
}