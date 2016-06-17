<?php

$cacheFolder	= isset($_REQUEST['cache_folder']) ? $_REQUEST['cache_folder'] : '';
$fileFound = false;

print '<html><body>';
print 'WSDL Cache folder: '. $cacheFolder. '<br/>';
print '<ul>';
if( ($cf = opendir($cacheFolder)) ) {
	while (($file = readdir($cf)) !== false) {
		if( preg_match("/wsdl-/i", $file) ) {
			$fileFound = true;
			print '<li>';
			if( unlink($cacheFolder .'/'. $file) ) {
				print '<font color="green">WSDL Cache file "'.$file.'" successfully removed.</font>';
			} else {
				print '<font color="red">Failed to remove WSDL Cache file "'.$file.'" !</font>';
			}			
			print '</li>';
		}
	}
	closedir($cf);
}
print '</ul>';

if( !$fileFound ) {
	print 'No action taken; No WSDL Cache files present in WSDL Cache folder.<br/>';
}

print '</body></html>';
