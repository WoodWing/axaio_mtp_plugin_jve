<?php

// Paranoid check defines that are essential to locate include files.
if( !defined('DIRECTORY_SEPARATOR') || !defined('PATH_SEPARATOR') ) {
	die( 'The PHP constants DIRECTORY_SEPARATOR or PATH_SEPARATOR are undefined.' );
	// Note: The PATH_SEPARATOR was introduced with PHP 4.3.0-RC2. 
}

// Determine which OS is running to use correct default path settings in config files.
if( DIRECTORY_SEPARATOR == '/' && PATH_SEPARATOR == ':' ) {
	$uname = php_uname();
	$parts = preg_split('/[[:space:]]+/', trim($uname));
	if($parts[0] == "Linux") {
		define( 'OS', 'LINUX' );
	} else {  // UNIX or Macintosh
		define( 'OS', 'UNIX' );
	}
} else { // Windows: DIRECTORY_SEPARATOR = '\' and PATH_SEPARATOR = ';'
	define( 'OS', 'WIN' );
}

?>
