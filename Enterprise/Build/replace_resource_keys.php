<?php
/**
 * @package      Enterprise
 * #subpackage   Build
 * @since        9.6
 * @copyright    WoodWing Software bv. All Rights Reserved.
 *
 * This script prefixes the resource keys with the internal name of a given plugin.
 * This is done for all resource files of a given Enterprise Server resource folder.
 * 
 * A resource file has the following markup:
 *    <LocalizationMap language="enUS">
 *       <Term key="MY_RESOURCE_KEY">foo</Term>
 *       ...
 * 
 * The script translates it into this:
 *    <LocalizationMap language="enUS">
 *       <Term key="MyPluginName.MY_RESOURCE_KEY">foo</Term>
 *       ...
 * 
 * After doing so, the resource files can be installed in a plugin resource folder, e.g.:
 *    Enterprise/config/plugins/MyPluginName/resources
 *
 * The script accepts the following parameters:
 *    replace_resource_keys.php <path of resource folder> <internal plugin name>
 */

// Validate the input paramters
$folderPath = $argv[1];
$prefix = $argv[2];
if( !$folderPath || !$prefix ) {
	print 'Usage: Please use '.$argv[0].' <path of resource folder> <internal plugin name> '.PHP_EOL;
	exit(1);
}
if( !is_dir($folderPath) ) {
	print 'ERROR: No valid folder: '.$folderPath.PHP_EOL;
	exit(1);
}
if( !is_writable($folderPath) ) {
	print 'ERROR: No write access to folder: '.$folderPath.PHP_EOL;
	exit(1);
}

// Iterate through all resource files in the resource folder.
$resFiles = glob( $folderPath.'/*.xml' );
if( $resFiles ) foreach( $resFiles as $resFile ) {
	if( is_file( $resFile ) ) {
		if( !is_writable($resFile) ) {
			print 'ERROR: No write access to file: '.$resFile.PHP_EOL;
			exit(1);
		}
		
		// Read the resource file.
		$dom = new DomDocument();
		$dom->loadXML( file_get_contents( $resFile ) );

		// Replace the keys in memory.
		$xPath = new DOMXPath( $dom );
		$xTerms = $xPath->query( '/LocalizationMap/Term' );
		if( $xTerms ) foreach( $xTerms as $xTerm ) {
			$term = $xTerm->getAttribute( 'key' );
			$xTerm->setAttribute( 'key', $prefix.'.'.$term );
		}

		// Save back the resource file (overwrites old one!).
		file_put_contents( $resFile, $dom->saveXML() );
	}
}