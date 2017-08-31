<?php
/**
 * Web service entry point for server plug-ins.
 *
 * The core server provides web services. Each bundle of services is defined by a WSDL file. Such bundle is called an
 * interface. The services can be called by clients through an entry point. For each interface there is such entry
 * point that can be found on the root level of an Enterprise Server installation. For example: Enterprise/adminindex.php.
 *
 * Server plug-ins can also provide there own web services. But their entry point relies deep down in the folder structure
 * of the plugins: Enterprise/config/plugins/<plugin>/indexes/<interface>/index.php. To increase the security
 * level of an Enterprise installation, you may want to give access permissions for incoming requests only to the root
 * folder of Enterprise. In such setup, the core indexes are accessible, but the server plugin indexes are not. To solve
 * this problem, the pluginindex.php module is introduced that can explicitly invoke any of the indexes provided by the
 * server plug-ins.
 *
 * Clients should connect as follows:
 *    Enterprise/pluginindex.php?plugin=<plugin>&interface=<interface>&protocol=<protocol>
 * Which interfaces and protocols can be used depends on the server plug-in.
 * Example:
 *    Enterprise/pluginindex.php?plugin=ContentStation&interface=pub&protocol=json
 *
 * @since 10.2.0
 */

// Validate URL parameters.
$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
$interface = isset( $_REQUEST['interface'] ) ? $_REQUEST['interface'] : '';
if( !$plugin || !$interface ) {
	$message = 'Please specify "plugin", "interface" and "protocol" paramateres at URL';
	header('HTTP/1.1 400 Bad Request');
	header('Status: 400 Bad Request - '.$message );
	exit( $message );
}

// Compose and validate index file path.
$plugin = removeDangerousCharacters( $plugin );
$interface = removeDangerousCharacters( $interface );
$fileName = __DIR__."/config/plugins/{$plugin}/indexes/{$interface}/index.php";
if( !file_exists($fileName) ) {
	$message = 'Index file could not be found. Please check the URL parameters and the plugin installation. '.$fileName;
	header('HTTP/1.1 404 Not Found');
	header('Status: 404 Not Found - '.$message );
	exit( $message );
}

// Invoke the index.php of the server plugin.
require_once $fileName;

/**
 * Removes special characters that are reserved for file paths and URLs.
 *
 * @param string $input
 * @return string The input string without dangerous characters.
 */
function removeDangerousCharacters( $input )
{
	$dangerousChars = "`~!@#$%^*\\|;:'<>/?\"";
	$safeReplacements = str_repeat( '', strlen( $dangerousChars ) );
	return strtr( $input, $dangerousChars, $safeReplacements );

}