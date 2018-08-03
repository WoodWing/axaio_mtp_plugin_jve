<?php
/**
 * WsdlCache TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_WsdlCache_TestCase extends TestCase
{
	public function getDisplayName() { return 'WSDL Cache'; }
	public function getTestGoals()   { return 'Checks if cached WSDL files on the server are accurate. If not accurate, there is a risk of communication errors between client applications and server, which needs to be avoided and solved by clearing the cache.'; }
	public function getTestMethods() { 
		return 'If the WSDL cache is enabled for PHP*, it checks the modification date of '.
		'all cached WSDL files against all shipped WSDL files (located in the / Enterprise '.
		'/ server / interfaces folder). If one shipped file is newer than any from cache, '.
		'there is a risk of inaccuracy for which it will display an error and list steps '.
		'to clear the cache. <br/>'.
		'* WSDL caching options for PHP are <code>soap.wsdl_cache_enabled</code> and '.
		'<code>soap.wsdl_cache_dir</code> and can be found in the php.ini file.'; 
	}
    public function getPrio()        { return 7; }
	
	final public function runTest()
	{
		// Note: ini_get() returns the runtime value while get_cfg_var() returns the 
		// value directly taken from the php.ini file only! So use ini_get(). (BZ#17248)
		
		// Check if the WSDL cache feature is enabled for PHP.
		if( !ini_get( 'soap.wsdl_cache_enabled' ) ) {
			$this->setResult( 'WARN', 'The WSDL Cache is disabled. '.
				'You can improve performance by enabling the cache. '.
				'Please check the <code>soap.wsdl_cache_enabled</code> in the php.ini file. ' );
			return;
		}
		
		// Check if there is a WSDL cache folder configured.
		$cacheFolder = ini_get( 'soap.wsdl_cache_dir' );
		if( !$cacheFolder ) {
			$this->setResult( 'WARN', 'The WSDL Cache is disabled. '.
				'You can improve performance by enabling the cache. '.
				'Please check the <code>soap.wsdl_cache_dir</code> in the php.ini file. ' );
			return;
		}
		
		// Above this point the WSDL cache is disabled, which gives bad performance.
		// Therefor a WARN is raised.
		// From this point onward, the system admin user intension is to have WSDL cache enabled.
		// Therefore an ERROR is given.
		
		// Check if the configured WSDL cache folder exists.
		if( !is_dir( $cacheFolder ) ) {
			$this->setResult( 'ERROR', 'The WSDL Cache folder does not exist. '.
				'Please check the <code>soap.wsdl_cache_dir</code> in the php.ini file, '.
				'or manualy create the "'.$cacheFolder.'" folder.' );
			return;
		}
		
		// If not exists yet, create a subfolder with Enterprise Server version info in 
		// its name. We can harmlessly do this on-the-fly since the SOAP server itself
		// does the very same. Error when it could not be created.
		require_once BASEDIR.'/server/protocols/soap/Server.php';
		WW_SOAP_Server::initWsdlCache();
		$cacheFolder = WW_SOAP_Server::getWsdlCacheFolder();
		
		// Check if the subfolder exists in the WSDL cache folder.
		if( !is_dir( $cacheFolder ) ) {
			$this->setResult( 'ERROR', 'The WSDL Cache folder does not exist. '.
				'Please manualy create the "'.$cacheFolder.'" folder.' );
			return;
		}
		
		// Check if we have write access to the subfolder in the WSDL cache folder.
		if( !is_writable( $cacheFolder ) ) {
			$this->setResult( 'ERROR', 'No write access to the WSDL Cache folder. '.
				'Please check the access rights for the "'.$cacheFolder.'" folder.' );
			return;
		}

		// Get the earliest modified cache file in cache folder
		$earliestModCache = null;
		foreach( glob( $cacheFolder.'/wsdl-*' ) as $cache ) {
			if(!$earliestModCache) {
				$earliestModCache = filemtime($cache);
			} else {
				if($earliestModCache > filemtime($cache)) {
					$earliestModCache = filemtime($cache);
				}
			}
		}
		// Get the latest modified wsdl file in interfaces folder
		$lastModWsdl = null;
		$wsdl = null;
		foreach( glob( BASEDIR.'/server/interfaces/*.wsdl') as $wsdl ) {
			$latestTime = 0;
			if( filectime($wsdl) < filemtime($wsdl) ) {
				$latestTime = filemtime($wsdl);
			} else {
				$latestTime = filectime($wsdl);
			}
			if(!$lastModWsdl) {
				$lastModWsdl = $latestTime;
			} else {
				if($lastModWsdl < $latestTime) {
					$lastModWsdl = $latestTime;
				}
			}
		}
		// always set remove text
		if( is_writable($cacheFolder) ) {
			require_once BASEDIR . '/server/utils/UrlUtils.php';
			$testFile = dirname(__FILE__).'/clearWSDLCache.php';
			$url = WW_Utils_UrlUtils::fileToUrl( $testFile, 'server', false );
			$url .= '?cache_folder='.$cacheFolder;
			$remove = 'Please run the <a href="'.$url.'" target="_blank">Clear Cache</a> page that will automatically clear the cache.';
		} else {
			$remove = 'Please remove all the files that have a "wsdl-" prefix in the cache folder, '. $cacheFolder . ' that reside on the server.';
		}
		// if cache is older than WSDL show help and remove text
		if( $earliestModCache && $earliestModCache < $lastModWsdl ) {
			$help =  'Shipped WSDL file found ('.basename($wsdl).') that is newer than oldest WSDL in cache.<br/>';
			$help .= $remove;
			$this->setResult( 'ERROR', $help );
			return;
		} else {
			// BZ#17248 now check if one of the parent directories has been modified / moved
			// We also test the parent of BASEDIR to detect if BASEDIR is renamed. The result
			// can be a false negative but we accept that.
			$dirs = array(BASEDIR);
			$dirs[] = $dirs[0] . DIRECTORY_SEPARATOR . 'server';
			$dirs[] = $dirs[1] . DIRECTORY_SEPARATOR . 'interfaces';
			$dirs[] = $dirs[0] . DIRECTORY_SEPARATOR . '..';
			foreach ($dirs as $dir){
				$mtime = filemtime($dir);
				if ($mtime > filectime($dir)){
					$mtime = filectime($dir);
				}
				if ($mtime > $lastModWsdl){
					$lastModWsdl = $mtime;
				}
			}
			// if cache is older than one of the parent directories, show help and remove text
			if( $earliestModCache && $earliestModCache < $lastModWsdl ) {
				$help =  'One of the WSDL parent directories (' . implode(',',$dirs). ') is newer than oldest WSDL in cache.<br/>';
				$help .= $remove;
				$this->setResult( 'WARN', $help );
				return;
			}
		}
		// success
		LogHandler::Log('wwtest', 'INFO', 'WSDL Cache checked.');
    }
}
