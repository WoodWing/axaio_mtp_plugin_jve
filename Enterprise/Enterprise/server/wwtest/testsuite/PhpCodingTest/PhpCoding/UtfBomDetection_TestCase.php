<?php
/**
 * @since 		v8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 *
 * Checks Enterprise source files for Unicode BOMs (Byte Order Markers).
 * A BOM could be accidentally sent through output stream disturbing other format such as 
 * HTML/SOAP/JPEG output. Library files are supressed. BOMs can be inserted by some text
 * editors which is potentially dangerous.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/FolderInterface.intf.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';
require_once BASEDIR.'/server/utils/FileHandler.class.php';

class WW_TestSuite_PhpCodingTest_PhpCoding_UtfBomDetection_TestCase extends TestCase implements FolderIterInterface
{
	public function getDisplayName() { return 'Unicode BOM detection'; }
	public function getTestGoals()   { return 'Avoid garbage output characters due to BOM marker added by some editors at the begin of a PHP file. When an editor puts a BOM marker before the &lt;? marker, it gets sent to output causing unexpected results, such as invalid SOAP responses.'; }
	public function getTestMethods() { return 'Parses PHP sources and checks for the first 3 bytes to see if it is the UTF-8 BOM marker. Actually, 8 bytes are taken to detect other Unicode BOM markers as well, such as UTF-16. The 3rd party libraries are ignored (considered to be ok).'; }
    public function getPrio()        { return 20; }
    
    private $filesChecked;
    
	/**
	 * Performs the test as written in module header.
	 */
	final public function runTest()
	{
		$this->filesChecked = 0;
		
		// Test: Enterprise Server
		$exclFolders = FolderUtils::getLibraryFolders();
		FolderUtils::scanDirForFiles( $this, BASEDIR, array('php'), $exclFolders );

		// Test: Demo/example server plug-ins
		$exclFolders = array();
		FolderUtils::scanDirForFiles( $this, BASEDIR.'/../plugins/', array('php'), $exclFolders );

		//$this->setResult( 'INFO', $this->filesChecked.' PHP files checked.' );
	}

	/**
	 * Check the PHP source file (called by parent class).
	 * @param $filePath string  Full file path of PHP file. <br/>
	 * @param $level    integer Current ply in folder structure of recursion search.
	 */
	public function iterFile( $filePath, $level )
	{
		// read first 8 bytes...
		$firstBytes = file_get_contents( $filePath, false, null, -1, 8 );
		
		// detect Unicode BOM...
		$bomlen = 0;
		$detection = FileHandler::detect_UTF_BOM( $bomlen, $firstBytes );
		
		// show colored message on error
		if( strlen( $detection ) > 0 ) { // BOM marker
			$message = 'Detected '.$detection.' BOM marker ';
			$this->setResult( 'ERROR', $this->getFileInfoStr( $filePath ).$message );
		}
		$this->filesChecked += 1;
	}

	// These three functions are called by parent class, but have no meaning here.
	public function skipFile( $filePath, $level )
	{
	}

	public function iterFolder( $folderPath, $level )
	{
	}

	public function skipFolder( $folderPath, $level )
	{
	}

	/**
	 * Transform file path into readable HTML fragment with file path info to be used as prefix 
	 * in setResult() messages.
	 *
	 * @param string $filePath Full file path of a PHP module being parsed.
	 * @return string The HTML fragment, as explained above.
	 */
	private function getFileInfoStr( $filePath )
	{
		$pathInfo = pathinfo($filePath);
		$pathInfo['dirname'] = str_replace( BASEDIR, '', $pathInfo['dirname'] );
		return '<i>'.$pathInfo['dirname'].'/<b>'.$pathInfo['basename'].':</b></i><br/>';
	}
}
