<html><body>
<font color="red"><b>DISCLAIMER:</b> This script is experimental and <u>on your own risk</u>. 
WoodWing Software is not responsible for any damage caused by this script. 
Make sure you make a copy of your database before running the SQL!</font>
<br/><br/>
The intention of this script is to repair bad encoding for the storename field at the smart_objects table
and smart_deletedobject table due to a filestore mount with other encoding than UTF-8 
(as reported <a href="http://community.woodwing.net/forum/upgrade-sce5-attachstorage-realfile-sce7">here</a>).
<br/><br/>
When creating objects with such mount, the storename field will have non-UTF-8 characters, which
works as long as the very same mount is used. When changing the mount by using the recommended UTF-8
encoding (e.g. when migrating to new environment), you need to repair the storename fields by transcoding 
them to UTF-8. This script generates an SQL batch file you can run by the use of your DB admin tool.
<br/><br/>
<b>Important:</b> Assumed is that the filestore was mounted with the "cp1252" encoding. When this is not
true for your case, please adjust the $orgEncoding value at the script and run again!
<br/><br/>
<?php
ini_set( 'display_errors', true );
require_once '../../config/config.php';
require_once BASEDIR.'/server/dbclasses/DBBase.class.php';

$orgEncoding = 'cp1252'; // enter your encoding here!
$writeOut = '';
$dbDriver = DBDriverFactory::gen();
foreach( array( 'objects', 'deletedobjects' ) as $tableName ) {
	$dbTable = $dbDriver->tablename( $tableName );
	$sql  = "SELECT o.`id`, o.`storename` FROM $dbTable o WHERE o.`storename` <> '' ";
	$sth = $dbDriver->query( $sql );
	$rows = DBBase::fetchResults( $sth );
	foreach( $rows as $row ) {
		$objectId = $row['id'];
		$storeName = iconv( $orgEncoding, 'UTF-8', $row['storename'] );
		$writeOut .= "UPDATE $dbTable o SET o.`storename` = '$storeName' WHERE o.`id` = $objectId ".PHP_EOL;
	}
}

$fileName = OUTPUTDIRECTORY.'storename_update.sql';
file_put_contents( $fileName,  $writeOut );
print 'SQL update statements saved at: "'.$fileName.'"<br/>';
print 'Update your Enterprise database named "'.DBSELECT.'" with this SQL file by using your DB admin tool.<br/>';
?>
</body>
</html>
