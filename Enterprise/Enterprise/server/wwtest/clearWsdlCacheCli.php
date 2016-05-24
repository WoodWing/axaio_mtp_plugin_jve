<?php
/**
 * Clear the WSDL cache via the command line
 *
 * Usage
 * This script must be run from the command line and on an installed sever
 *
 * php clearWsdlCacheCli.php
 */
require_once dirname(__FILE__) . '/../../config/config.php';

require_once BASEDIR . '/server/protocols/soap/Server.php';
WW_SOAP_Server::initWsdlCache();
$cacheFolder = WW_SOAP_Server::getWsdlCacheFolder();

if (($cf = opendir($cacheFolder))) {
	while (($file = readdir($cf)) !== false) {
		if (preg_match("/wsdl-/i", $file)) {
			unlink($cacheFolder . '/' . $file);
		}
	}
	closedir($cf);
}