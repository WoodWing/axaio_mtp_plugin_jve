<?php

/**
 * @package 	Enterprise
 * @subpackage 	Test
 * @since 		v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 * 
 * This script is STRICTLY for development, test and demo purposes.
 * It will clean all versions from an Enterprise installation. Put this script in a folder 
 * above your Enterprise installation.
 */

main();

function main ()
{
	require_once dirname( __FILE__ ) . '/../../config/config.php';
	require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
	require_once BASEDIR . '/server/bizclasses/BizStorage.php';
	require_once BASEDIR . '/server/dbclasses/DBVersion.class.php';
	require_once BASEDIR . '/server/bizclasses/BizDeletedObject.class.php';
	require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
	require_once BASEDIR . '/server/secure.php';
	
	$clean = intval( @$_REQUEST['clean'] );
	cleanObjectsInDB( $clean );
	if ($clean) {
		purgeObjects();
	}
	$totalSize = cleanVersions( $clean );
	$totalSize += cleanFilesNotInDB( ATTACHMENTDIRECTORY, $clean );
	if ($clean) {
		print "<br />Total " . getPrettySize( $totalSize ) . " has been cleaned<br />\n";
	} else {
		print "<br />Total " . getPrettySize( $totalSize ) . " can be cleaned<br />\n";
		print '<br /><a href="?clean=1">Press here to clean the server</a><br />';
	}
}

function getEnterpriseServer ()
{
	$server = strval(@$_REQUEST['server']);
	if (! isEnterpriseServer($server)) {
		if ( ($dh = opendir(DIRNAME)) ) {
			$servers = array();
			while ( ($server = readdir($dh)) ) {
				if (isEnterpriseServer($server)) {
					$servers[] = $server;
				}
			}
			closedir($dh);
			if (count($servers) > 1) {
				foreach ($servers as $server) {
					print '<a href="?server=' . $server . '">' . $server . '</a><br />';
				}
				$server = '';
			} else {
				if (count($servers) == 1){
					$server = $servers[0];
				} else {
					print "No servers found!";
				}
			}
		}
	}
	
	return $server;
}

function isEnterpriseServer ($server)
{
	$configPath = getConfigPath($server);
	if ($server && $server[0] != '.' && is_readable($configPath)){
		return true;
	}
	return false;
}

function getConfigPath ($server)
{
	return DIRNAME . DIRECTORY_SEPARATOR . $server . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
}

function cleanFilesNotInDB ($dir, $clean)
{
	$result = 0;
	if ( ($dh = opendir($dir)) ) {
		$ids = array();
		while ( ($file = readdir($dh)) ) {
			$id = intval(preg_replace('/^([0-9]*).*$/', '\1', $file));
			if ($id > 0) {
				if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) {
					$result += cleanFilesNotInDB($dir . DIRECTORY_SEPARATOR . $file, $clean);
				} else {
					if (! isset($ids[$id])) {
						$ids[$id] = array();
					}
					$ids[$id][] = $file;
				}
			}
		}
		closedir($dh);
		$idKeys = array_keys($ids);
		$dbObjects = DBBase::listRows('objects', 'id', '', 'id IN (' . implode(',', $idKeys) . ')', 
			array('id' , 'name' , 'type'));
		$totalSize = 0;
		foreach ($idKeys as $idKey) {
			if (! isset($dbObjects[$idKey])) {
				foreach ($ids[$idKey] as $file) {
					$filePath = $dir . DIRECTORY_SEPARATOR . $file;
					$filesize = filesize($filePath);
					if ($clean) {
						if (! unlink($filePath)) {
							print "Error: could not delete " . $filePath . "<br />\n";
						} else {
							$totalSize += $filesize;
						}
					} else {
						$totalSize += $filesize;
					}
				}
			}
		}
		if ($clean) {
			print "Files in directory " . $dir . "<br />\n";
			print "Cleaned " . getPrettySize($totalSize) . "<br />\n";
		} else {
			print "Files in directory " . $dir . "<br />\n";
			print getPrettySize($totalSize) . " can be cleaned<br />\n";
		}
		$result += $totalSize;
	}
	
	return $result;
}

function cleanVersions ($clean)
{
	$dbdriver = DBDriverFactory::gen();
	$versionstable = $dbdriver->tablename("objectversions");
	$objectstable = $dbdriver->tablename("objects");
	$sql = "SELECT v.objid, o.storename, v.types, concat(v.majorversion,'.',v.minorversion) AS version FROM $versionstable v, $objectstable o WHERE v.objid = o.id";
	$sth = $dbdriver->query($sql);
	$totalSize = 0;
	$toDelete = array();
	while ( ($row = $dbdriver->fetch($sth)) ) {
		$types = unserialize($row['types']);
		foreach (array_keys($types) as $tp) {
			$attachobj = StorageFactory::gen($row['storename'], $row['objid'], $tp, $types[$tp], $row['version']);
			$filesize = @filesize($attachobj->getFilename());
			if ($clean) {
				if (! $attachobj->deleteFile()) {
					print "Error: could not delete " . $attachobj->getFilename() . "<br />\n";
				} else {
					$totalSize += $filesize;
				}
				// delete version from DB
				$toDelete[] = $row['objid'];
			} else {
				$totalSize += $filesize;
			}
		}
	}
	if ($clean) {
		$dbdriver->query('DELETE FROM ' . $versionstable . ' WHERE objid IN (' . implode(',', $toDelete) . ')');
		print "Old versions in " . ATTACHMENTDIRECTORY . "<br />\n";
		print "Cleaned " . getPrettySize($totalSize) . "<br />\n";
	} else {
		print "Old versions in " . ATTACHMENTDIRECTORY . "<br />\n";
		print getPrettySize($totalSize) . " can be cleaned<br />\n";
	}
	
	return $totalSize;
}

function getPrettySize ($size)
{
	$result = '';
	if ($size > 1024 * 1024) {
		$result = round($size / (1024 * 1024)) . 'MB';
	} else 
		if ($size > 1024) {
			$result = round($size / (1024)) . 'KB';
		} else {
			$result = $size . ' bytes';
		}
	return $result;
}

function cleanObjectsInDB ( $clean )
{
	print "Check if all objects exist in the filestore<br />\n";
	/*$adminUser =*/ getAdminUser();
	$dbObjects = DBBase::listRows('objects', '', '', '', 
		array('id' , 'storename' , 'types' , 'majorversion' , 'minorversion'));
	foreach ($dbObjects as $dbObject) {
		$types = unserialize($dbObject['types']);
		$notExists = 0;
		foreach (array_keys($types) as $tp) {
			$attachobj = StorageFactory::gen($dbObject['storename'], $dbObject['id'], $tp, $types[$tp], 
				$dbObject['majorversion'] . '.' . $dbObject['minorversion']);
			if (! file_exists($attachobj->getFilename())) {
				print 
					"Object with id=" . $dbObject['id'] . " and type=" . $tp . " doesn't exist in filestore (" . $attachobj->getFilename() .
						 ")<br />";
				$notExists++;
			}
		}
		if ($notExists > 0 && $notExists == count($types)){
			if ($clean){
				require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
				require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
				require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
				
				$ticket = BizSession::getTicket();
				$service = new WflDeleteObjectsService();

				$areas = array('Workflow');
				$request = new WflDeleteObjectsRequest();
				$request->Ticket = $ticket;
				$request->IDs = array( $dbObject['id'] );
				$request->Permanent = true; //Deleting permanently directly from workflow area
				$request->Areas = $areas;
				$service->execute($request);	
			} else {
				print "Object with id=" . $dbObject['id'] . " can be removed from DB<br />";
			}
		}
	}
	print "End check if all object exist in the filestore<br />\n";
}

function purgeObjects ()
{
	print "Purge all objects<br />\n";
	// select admin user
	/*$adminUser =*/ getAdminUser();
	try {
		//To purge Objects
		require_once BASEDIR . '/server/services/wfl/WflDeleteObjectsService.class.php';
		require_once BASEDIR . '/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
				
		$ticket = BizSession::getTicket();
		$service = new WflDeleteObjectsService();
		
		$areas = array('Trash');
		$req = new WflDeleteObjectsRequest();
		$req->Ticket = $ticket;
		$req->IDs = $this->objIds;
		$req->Permanent = true; //clearing objects permanently from trash area
		$req->Areas = $areas;
		$service->execute( $req );
		
	} catch (BizException $e) {
		// ignore
		$e = $e;
	}

	print "End purge all objects<br />\n";
}

function getAdminUser()
{
	$dbdriver = DBDriverFactory::gen();
	$userstable = $dbdriver->tablename("users");
	$usrgrptable = $dbdriver->tablename("usrgrp");
	$groupstable = $dbdriver->tablename("groups");
	$sql = 'SELECT u.`user` FROM ' . $userstable . ' u, ' . $usrgrptable . ' ug, ' . $groupstable . ' g' . ' WHERE u.`id`=ug.`usrid`' .
		 ' AND ug.`grpid`=g.`id`' . ' AND g.`admin` = "on"' . ' LIMIT 1';
	$sth = $dbdriver->query($sql);
	$row = $dbdriver->fetch($sth);
	if ($row && $row['user']) {
		return $row['user'];
	}
	return '';
}
