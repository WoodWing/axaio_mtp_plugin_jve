<?php 
/**
 * @package 	Enterprise
 * @subpackage 	Test
 * @since 		v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * --------------------------------------------------------------------------------------------------
 * IMPORTANT: This script is STRICTLY for development, test and demo purposes. It removes data and
 *            there is no data recovery. Use it at your own risk! WoodWing Software is not responsibe
 *            for any damage caused by this script.
 * --------------------------------------------------------------------------------------------------
 *
 * It removes all object versions from an Enterprise installation. The current version of each
 * object is preserved. In other terms, the full object history gets erased from DB and FileStore.
 *
 * To install, put this script into this location:
 *    <webroot>/Enterprise/server/useful/clearversions.php
 * Run clearversions.php at your web browser to get an overview.
 * Do perform the cleaning, run it again, but then with an extra parameter: 
 *    clearversions.php?clean=1
 * Remove the script after usage to avoid anyone else causing damage to your system!
 */

require_once '../../config/config.php';
require_once BASEDIR.'/server/bizclasses/BizStorage.php';
require_once BASEDIR.'/server/bizclasses/BizVersion.class.php';

$dbdriver = DBDriverFactory::gen();
$versionstable = $dbdriver->tablename("objectversions");
$objectstable = $dbdriver->tablename("objects");
$sql = "SELECT v.objid, o.storename, v.types, concat(v.majorversion,'.',v.minorversion) AS version ".
		"FROM $versionstable v ".
		"INNER JOIN $objectstable o ON (v.objid = o.id)";
$sth = $dbdriver->query($sql);
if( !$sth ) {
	print 'Error getting version from DB';
	exit;
}

$cleanIt = false;

if( $_REQUEST['clean'] ) {
	print "<h2>Cleaning all versions...</h2>";
	$cleanIt = true;
} else {
	print "<h2>The following objectversions are available at DB ".DBSELECT.".<br/>".
		"Run this script with <code>?clean=1</code> to delete all of them.<br/>AT YOUR OWN RISK</h2>";
}

$rows = array(); $msg = ''; $totalSize = 0; $nVersions=0;
while( ($row = $dbdriver->fetch($sth)) ) {
	$id 		= $row['objid'];
	$vers		= $row['version'];
	$storename	= $row['storename'];
	$types 		= unserialize($row['types']);

	if( $cleanIt ) {
		print 'Deleting deleting version: '.$vers.' of object '.$id.'<br/>';
		try {
			BizVersion::deleteVersions( array( $id ), array( $vers ), array( $id => $storename ), array( $types ) );
		} catch ( BizException $e) {
			print "Error deleting version from DB: $vers of object $id. Detail: ".$e->getDetail()."<br/>";
		}
	} else {
		// No cleaning, just collecting info:
		$msg .= "Object id: $id, v$vers - ";
		foreach( $types as $tp => $format ) {
			$attachobj = StorageFactory::gen( $storename, $id, $tp, $types[$tp], $vers );
			$totalSize = $totalSize + filesize( $attachobj->getFilename() );
			$msg .= "$tp ($format) ";
		}
		$msg .= '<br/>';
	}
	++$nVersions;
}

if( !$cleanIt ) {
	if( $totalSize > 1024*1024 ) {
		$totalSize = round($totalSize/(1024*1024)) .'Mb';
	} else if( $totalSize > 1024 ) {
		$totalSize = round($totalSize/(1024)) .'kb';
	}
	
	print "<h3>Number of versions: $nVersions with total size: $totalSize on FileStore: ".ATTACHMENTDIRECTORY."</h3>";
	print $msg;
}
