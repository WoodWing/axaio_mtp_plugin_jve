<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once dirname(__FILE__).'/config.php';		// CLARO
require_once '../../../config/config.php';
require_once BASEDIR.'/server/dbdrivers/dbdriver.php';

$id = $_REQUEST['id'];
if (CLARODEBUG) print "Layout ID = $id\n";

//if (!file_exists(CLARO_TO_DIRECTORY)) mkdir(CLARO_TO_DIRECTORY);

$imagesTxt = $_REQUEST['images'];
$images = explode('/', $imagesTxt);

$dbDriver	= DBDriverFactory::gen();
$deletedImg = array();

foreach ($images as $image) {
	if (!$image) continue;

	// decode info
	$pars = explode(',', $image);
	$imgId 	= $pars[0];
	$rotate = -$pars[1];
	$width 	= $pars[2];
	$height = $pars[3];
	$cropx 	= $pars[4];
	$cropy 	= $pars[5];
	
	// all information is stored in rounded pixels, except for width/height which are point based
	
	if (CLARODEBUG) print "ID: $imgId\nRot = $rotate\nWidth = $width\nHeight = $height\ncropx = $cropx\ncropy = $cropy\n";
	
	// store info in database
	if (!in_array($imgId, $deletedImg)) {
		$sql = "delete from `smart_claro` where `oid` = $imgId";
		$sth = $dbDriver->query($sql);
		$deletedImg[] = $imgId;
	}

	$sql = "insert into `smart_claro` (`oid`, `cropx`, `cropy`, `rotate`, `width`, `height`) VALUES ($imgId, $cropx, $cropy, $rotate, $width, $height)";
	$sth = $dbDriver->query($sql);
	
}
