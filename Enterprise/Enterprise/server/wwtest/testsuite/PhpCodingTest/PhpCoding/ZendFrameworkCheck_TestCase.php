<?php

/**
 * @package 	Enterprise
 * @subpackage 	TestSuite
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Checks if changes made to the Zend Framework are still present. In some cases
 * changes are made to the framework to getter better support for WoodWing specific
 * requirements or because the framework contains bugs.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/FolderInterface.intf.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';

define('PATH_ZENDFRAMEWORK',  BASEDIR . '/server/ZendFramework/library/Zend');
	
class WW_TestSuite_PhpCodingTest_PhpCoding_ZendFrameworkCheck_TestCase extends TestCase
{
	public function getDisplayName() { return 'Check Zend Framework'; }
	public function getTestGoals()   { return 'Avoids run-time problems due to upgrading the Zend Framework without customized files.'; }
	public function getTestMethods() { return 'Checks if customized files are still present. If not, an error is thrown. All problems reported needs to be fixed properly.'; }
	public function getPrio()		{ return 45; }
	
	private $filesChanged = array();

	/**
	 * Performs the test as written in module header.
	 */
	final public function runTest()
	{
		$this->filesChanged[] = PATH_ZENDFRAMEWORK . '/Uri/Http.php';
		$this->checkCustomized();
	}

	/**
	 * Customized files are marked by the term 'WoodWing Software' in the header. The first 1024 bytes are regarded as
	 * the header. If the file is not found or if the term 'WoodWing' is not found an error is thrown.   
	 */
	private function checkCustomized()
	{
		foreach ($this->filesChanged as $file) 
		{
			if ( !file_exists($file)) {
				$this->setResult( 'ERROR', 'Customized file, '. $file . ' is missing.' );     
			} else {
				$fp = fopen($file, 'r');
				$content = fread($fp, filesize( $file )); // Customized files contain a marker 'WoodWing Software' in their header.
				if ( stristr($content, 'WoodWing') === false ) {
					$this->setResult( 'ERROR', 'Customized file, '. $file . ', has been replaced by a non-customized version.'); 
				}
			}
		}
	}
}
