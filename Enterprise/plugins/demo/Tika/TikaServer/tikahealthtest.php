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


$healthTest	= !empty($_REQUEST['healthTest']) ? $_REQUEST['healthTest'] : null;
$multiByte	= !empty($_REQUEST['multiByte']) ? $_REQUEST['multiByte'] : null;

if( is_null($healthTest) ) {
	$message = 'Tika Server installed.';
	exit($message);	
}

$xmlDoc = new DOMDocument();
$xmlTikaServer = $xmlDoc->createElement( 'TikaServer' );
$xmlDoc->appendChild( $xmlTikaServer );

// Return Interface version
$serverPluginVersion = Tika::$version;
$xmlServerPlugin = $xmlDoc->createElement( 'Interface' );
$xmlTikaServer->appendChild( $xmlServerPlugin );
createTextElem( $xmlDoc, $xmlServerPlugin, 'Version', $serverPluginVersion );

// Return installed PHP version in Tika Server
$xmlPHP = $xmlDoc->createElement( 'PHP' );
$xmlTikaServer->appendChild( $xmlPHP );
createTextElem( $xmlDoc, $xmlPHP, 'Version', phpversion() );

// Return installed Java version in Tika Server
$javaVersion = Tika::getJavaVersion();
if( !is_array($javaVersion) ) {
	$message = str_replace(array("\r\n", "\r", "\n"), ' ', $javaVersion);
	header('HTTP/1.1 500 Internal Server Error');
	header('Status: 500 Internal Server Error - '.$message );
	exit( $message );
}

$xmlJava = $xmlDoc->createElement( 'Java' );
$xmlTikaServer->appendChild( $xmlJava );
createTextElem( $xmlDoc, $xmlJava, 'Version', $javaVersion[0] );

// Return installed Tika version in Tika Server
$tikaVersion = Tika::getTikaVersion();
if( !is_array($tikaVersion) ) {
	$message = $tikaVersion;
	header('HTTP/1.1 500 Internal Server Error');
	header('Status: 500 Internal Server Error - '.$message );
	exit( $message );
}

$xmlTika = $xmlDoc->createElement( 'Tika' );
$xmlTikaServer->appendChild( $xmlTika );
createTextElem( $xmlDoc, $xmlTika, 'Version', $tikaVersion[0] );

// Return supported format of current Tika version
/*$metaData = Tika::getSupportedFormats();
if( !is_array($metaData) ) {
	$message = $metaData;
	header('HTTP/1.1 500 Internal Server Error');
	header('Status: 500 Internal Server Error - '.$message );
	exit( $message );
}
foreach( $metaData as $meta ) {
	$xmlMetaData = $xmlDoc->createElement( 'SupportedFormat' );
	$xmlTikaServer->appendChild( $xmlMetaData );
	createTextElem( $xmlDoc, $xmlMetaData, 'Format', $meta );
}*/

// Return the Multibyte value
if( $multiByte ) {
	$xmlMultibyte = $xmlDoc->createElement( 'MultiByte' );
	$xmlTikaServer->appendChild( $xmlMultibyte );
	createTextElem( $xmlDoc, $xmlMultibyte, 'Value', $multiByte );
}

// Return write access of system temp folder -  this test should on after java and tika test above
$readAccess = Tika::checkSystemFolderAccess();
$xmlSysTempFolder = $xmlDoc->createElement( 'SysTempFolder' );
$xmlTikaServer->appendChild( $xmlSysTempFolder );
createTextElem( $xmlDoc, $xmlSysTempFolder, 'Name', sys_get_temp_dir());
createTextElem( $xmlDoc, $xmlSysTempFolder, 'Value', $readAccess);

header('Content-type: text/xml');
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