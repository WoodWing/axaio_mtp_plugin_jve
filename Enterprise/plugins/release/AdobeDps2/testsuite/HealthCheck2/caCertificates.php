<?php
if( file_exists('../../../../../config/config.php') ) {
	require_once '../../../../../config/config.php';
} else { // fall back at symbolic link to Perforce source location of server plug-in
	require_once '../../../../../Enterprise/config/config.php';
}
require_once BASEDIR . '/server/utils/FolderUtils.class.php';

print '<html><body>';

if ( !file_exists(ENTERPRISE_CA_BUNDLE) ) {
	$directory = dirname(ENTERPRISE_CA_BUNDLE);
	if ( !file_exists($directory) ) {
		FolderUtils::mkFullDir($directory);
	}

	if ( !is_dir( $directory ) ) {
		print '<font color="red">' . $directory . ' already exists but is not a directory.</font>';
	}
} else {
	if ( !is_readable(ENTERPRISE_CA_BUNDLE) ) {
		print '<font color="red">' . ENTERPRISE_CA_BUNDLE . ' is not readable.</font>';
	}
}

if ( !FolderUtils::isDirWritable(dirname(ENTERPRISE_CA_BUNDLE)) ) {
	print '<font color="red">' . dirname(ENTERPRISE_CA_BUNDLE) . ' is not a writable directory.</font>';
}

downloadFile();

print '</body></html>';

function downloadFile()
{
	$configs = 	defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' ?
		unserialize( ENTERPRISE_PROXY ) : array();

	$curlOptions = array();
	if ( $configs ) {
		if ( isset($configs['proxy_host']) ) {
			$curlOptions [CURLOPT_PROXY] = $configs['proxy_host'];
		}
		if ( isset($configs['proxy_port']) ) {
			$curlOptions [CURLOPT_PROXYPORT] = $configs['proxy_port'];
		}
		if ( isset($configs['proxy_user']) && isset($configs['proxy_pass']) ) {
			$curlOptions [CURLOPT_PROXYUSERPWD] = $configs['proxy_user'] . ":" . $configs['proxy_pass'];
		}
	}

	require_once 'Zend/Http/Client.php';
	$curlConfig = array(
		'adapter'   => 'Zend_Http_Client_Adapter_Curl',
		'curloptions' => $curlOptions ,
	);
	$httpClient = new Zend_Http_Client( 'http://downloads.woodwing.net/certificate-bundles/enterprise-server/ca-bundle.crt', $curlConfig );
	try {
		$response = $httpClient->request();
		file_put_contents(ENTERPRISE_CA_BUNDLE, $response->getBody());
		print '<font color="green">' . ENTERPRISE_CA_BUNDLE . ' is created!</font>';
	} catch ( Exception $e ) {
		/** @noinspection PhpSillyAssignmentInspection */
		$e = $e; // To make analyzer happy.
		print '<font color="red">' . ENTERPRISE_CA_BUNDLE . ' could not be downloaded!</font>';
		print '<br/>';
		print 'You can download the file from: <a href="http://downloads.woodwing.net/certificate-bundles/enterprise-server/ca-bundle.crt" target="_blank">http://downloads.woodwing.net/certificate-bundles/enterprise-server/ca-bundle.crt</a>.';
		print '<br/>';
		print 'Download the cacert.pem file and save it as: "' . ENTERPRISE_CA_BUNDLE . '".';
	}

}
