<?php

require_once '../../../../config/config.php';

print '<html><body>';
print 'Fixing the auto_increment value of the smart_object table<br><br>';

// Set the object id as auto increment value in the smart_objects table
$objectId = isset($_REQUEST['object_id']) ? intval($_REQUEST['object_id']) : 0;
$deletedId = isset($_REQUEST['deleted_id']) ? intval($_REQUEST['deleted_id']) : 0;

if ( $deletedId > 0 ) {
	$dbdriver = DBDriverFactory::gen();
	$objectsTable = DBPREFIX."objects";
	if (DBTYPE == 'mysql') {
		// MySQL auto_increment is the first unused id
		$deletedId = $deletedId + 1;
		$sql = "ALTER TABLE $objectsTable AUTO_INCREMENT = $deletedId";
		$sth = $dbdriver->query($sql);
	}
	elseif (DBTYPE == 'mssql') {
		// MSSQL auto_increment is the latest used id
		$sql = "DBCC CHECKIDENT ($objectsTable, RESEED, $deletedId)";
		$sth = $dbdriver->query($sql);
	}
	// For oracle it is not needed to adjust the 'auto_increment'. It is kept in the sequence and
	// the sequence 
	/* 
	elseif (DBTYPE == 'oracle') {
		// Oracle has no 1 statement to set the "auto increment". For now we have found that 
		// increasing the increment to that it will be set to the correct value and then calling 
		// nextId() to set correct ID works. After that set the increment back to 1.
		// Discussion with JVE is needed to finalise the solution 
		
		$increment = $deletedId - $objectId;
		$objectsTableSeq = $objectsTable."_seq";
		$sql = "alter sequence $objectsTableSeq increment by $increment";
		$sth = $dbdriver->query($sql);
		$oldID = $dbdriver->nextId($objectsTable);
		$sql = "alter sequence $objectsTableSeq increment by 1";
		$sth = $dbdriver->query($sql);
	}*/

	print "Auto_increment is restored to id: $deletedId";
}
else {
	print "There is no deleted ID given. Nothing has been changed.";
}

print '</body></html>';
