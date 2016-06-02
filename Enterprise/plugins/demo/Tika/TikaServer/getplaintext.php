<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

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

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/Tika.class.php';

$inputPath = $_FILES['upload']['tmp_name'];
$outputPath = tempnam( sys_get_temp_dir(), 'tika-out-' );

// check whether upload file exist or not
if( !file_exists( $inputPath ) ) {
	$message = "$inputPath file doesn't exist.";
	header('HTTP/1.1 500 Internal Server Error');
	header('Status: 500 Internal Server Error - '.$message );
	exit( $message );
}

header('Content-type: text/xml');
$xmlDoc = new DOMDocument();
$xmlFile = $xmlDoc->createElement( 'File' );
$xmlDoc->appendChild($xmlFile);

// Run Tika command to get file metadata
/* - Commented out for now, since it is not use
$metaData = Tika::runTikaCmd($inputPath, $outputPath, "m");
if( !is_array($metaData) ) {
	$message = $metaData;
	header('HTTP/1.1 500 Internal Server Error');
	header('Status: 500 Internal Server Error - '.$message );
	exit( $message );
}

foreach( $metaData as $meta ) {
	$metaNameValue = explode( ":", $meta );
	$xmlMetaData = $xmlDoc->createElement( 'MetaData' );
	$xmlFile->appendChild( $xmlMetaData );
	createTextElem( $xmlDoc, $xmlMetaData, 'Name', $metaNameValue[0] );
	createTextElem( $xmlDoc, $xmlMetaData, 'Value', trim($metaNameValue[1]) );
}
*/

// Run Tika command to get file plaintext
$status = Tika::runTikaCmd($inputPath, $outputPath, "t");
if( !is_array($status) ) {
	$message = $status;
	header('HTTP/1.1 500 Internal Server Error');
	header('Status: 500 Internal Server Error - '.$message );
	exit( $message );
}

if(file_exists( $outputPath )) {
	$plainText = file_get_contents( $outputPath );
	if(strlen( $plainText ) > 0) {
		createTextElem( $xmlDoc, $xmlFile, 'plaincontent', $plainText );
	}
	unlink( $outputPath );
}

echo $xmlDoc->saveXML();

// Helper function to create XML text elements
function createTextElem( $xmlDoc, $xmlParent, $nodeName, $nodeText )
{
	$xmlNode = $xmlDoc->createElement( $nodeName );
	$xmlParent->appendChild( $xmlNode );
	$xmlText = $xmlDoc->createTextNode( $nodeText );
	$xmlNode->appendChild( $xmlText );
	return $xmlNode;
}