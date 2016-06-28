<?php
require_once '../../../../config/config.php';
require_once BASEDIR . '/server/utils/FolderUtils.class.php';
require_once BASEDIR . '/server/utils/TestSuite.php';

print '<html><body>';

//$defineName = $_REQUEST['define'];
$directory = html_entity_decode($_REQUEST['path']);
$utils = new WW_Utils_TestSuite();
if( $utils->dirPathExists($directory) ) {
	if (FolderUtils::isDirWritable($directory)) {
		print '<font color="green">Folder "'.$directory.'" was already created and is writable.</font>';
	} else {
		print '<font color="red">Folder "'.$directory.'" was already created but is not writable.</font>';
	}
} else {
	$old_umask = umask(0); // Needed for mkdir, see http://www.php.net/umask
	if( mkdir( $directory, 0777, true ) ) {
		chmod( $directory, 0777 );  // We cannot alway set access with mkdir because of umask
		umask($old_umask);
		
		if ( FolderUtils::isDirWritable($directory) ) {
			print '<font color="green">Folder "'.$directory.'" successfully created.</font>';
		} else {
			print '<font color="red">Folder "'.$directory.'" is created but isn\'t writable..</font>';
		}
	} else {
		print '<font color="red">Folder "'.$directory.'" could not be created. Please create it manually or change the path. Note that the folder path is case sensitive.</font>';
	}
}
print '</body></html>';
