<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v9.2
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/FileHandler.class.php';
require_once BASEDIR.'/server/utils/FolderUtils.class.php';
require_once BASEDIR.'/server/utils/FolderInterface.intf.php';
require_once BASEDIR.'/server/appservices/textconverters/TextConverter.class.php';

define( 'AC_TEST_DATA', dirname(__FILE__) . '/inCopyTestData' );
define( 'AC_TEST_DATA_HTML', dirname(__FILE__)  . '/inCopyTestData/testHtml/' );

class WW_TestSuite_BuildTest_AdobeInCopy_AdobeInCopyConverter_TestCase extends TestCase
{
	public function getDisplayName() { return 'Adobe InCopy files to HTML Converter check'; }
	public function getTestGoals()   { return 'Make sure the converter is working correctly'; }
	public function getTestMethods() { return 'Calls the Converter and checks if the output is correct'; }
    public function getPrio()        { return 5103; }

	private $inputFiles = null;

	final public function runTest()
	{
		try{
			$this->testWwcxFilesConverter();
			$this->testWcmlFilesConverter();
		}catch( BizException $e ){
			$this->setResult( 'ERROR', 'Testing failed and is aborted', $e->getMessage() );
		}
	}

	/**
	 * Run the Tests for the WW_TextConverters_Wcml2Xhtml Converter.
	 *
	 * Checks if the output of the tests is still correct.
	 */
	public function testWwcxFilesConverter()
	{
		// Collect wwcx files to convert
		$this->inputFiles = array();
		FolderUtils::scanDirForFiles( $this, AC_TEST_DATA, array( 'wwcx') );

		// Convert all wwcx files in the testData folder and write conversions in the output folder
		$tc = new TextConverter();
		$tc->import( $this->inputFiles );

		foreach( $this->inputFiles as $file ) {
			$path_parts = pathinfo( $file['FilePath'] );
			$icFile = $path_parts['filename'];
			$outputHtml = null;
			$inputHtml = null;
			// Output InCopy files into HTML frames (one file per frame)
			$xFrames = $file['HtmlFrames'];
			foreach( $xFrames as $xFrame ) {
				$doc = $xFrame->Document;
				$outputHtml = $doc->saveHTML();

				// Uncomment if you want to create new html files for wwcx files
				// $doc->saveHTMLFile( AC_TEST_DATA_HTML . $icFile . '.html' );
			}
			$inputHtml = file_get_contents(AC_TEST_DATA_HTML . $icFile . '.html');

			$outputDoc = new DOMDocument();
			$outputDoc->loadHTML( $outputHtml );
			$inputDoc = new DOMDocument();
			$inputDoc->loadHTML( $inputHtml );

			require_once dirname(__FILE__) . '/XMLDiff/XmlDiff.php';
			$diff = new XmlDiff( $inputDoc, $outputDoc );
			$delta = (string) $diff->diff();

			if( !empty($delta) ){
				$this->setResult( 'ERROR', 'WWCX test: The converted file ('.$path_parts['basename'].') does not match the expected output.',
					'Difference: ' . $delta );
			}
		}
	}

	/**
	 * Run the Tests for the WW_TextConverters_Wcml2Xhtml Converter.
	 *
	 * Checks if the output of the tests is still correct.
	 */
	public function testWcmlFilesConverter()
	{
		// Collect wcml files to convert
		$this->inputFiles = array();
		FolderUtils::scanDirForFiles( $this, AC_TEST_DATA, array( 'wcml') );

		// Convert all wcl files in the testData folder and write conversions in the output folder
		$tc = new TextConverter();
		$tc->import( $this->inputFiles );

		foreach( $this->inputFiles as $file ) {
			$path_parts = pathinfo( $file['FilePath'] );
			$icFile = $path_parts['filename'];

			// Output InCopy files into HTML frames (one file per frame)
			$xFrames = $file['HtmlFrames'];
			$allOutputFramesDoc = new DOMDocument();
			$inputDoc = new DOMDocument();
			$outputDoc = new DOMDocument();

			foreach( $xFrames as $xFrame ) {
				$domElement = $xFrame->Document;
				$allOutputFramesDoc->appendChild($allOutputFramesDoc->importNode($domElement,TRUE));
			}

			// Uncomment if you want to create new html files for wcml files
			//$allFramesDoc->saveHTMLFile( AC_TEST_DATA_HTML . $icFile . '.html' );

			$outputHtml = $allOutputFramesDoc->saveHTML();

			$outputDoc->loadHTML( $outputHtml );
			$inputDoc->loadHTML( file_get_contents(AC_TEST_DATA_HTML . $icFile . '.html') );

			require_once dirname(__FILE__) . '/XMLDiff/XmlDiff.php';
			$diff = new XmlDiff( $inputDoc, $outputDoc );
			$delta = (string) $diff->diff();

			if( !empty($delta) ){
				$this->setResult( 'ERROR', 'WCML test: The converted file ('.$path_parts['basename'].') does not match the expected output.',
					'Difference: ' . $delta );
			}
		}
	}

	/**
	 * This function determines the file format and fills in the file path of all the input files.
	 *
	 * @param $filePath
	 * @param $level
	 */
	public function iterFile( $filePath, $level )
	{
		if( $level > 0 ) return; // only one ply to avoid parsing output folders that might be subfolders

		$ext = pathinfo($filePath, PATHINFO_EXTENSION);

		// check which format should be added to the file
		if( $ext == 'wwcx' ){
			$format = 'application/incopy';
		} else if( $ext == 'wcml' ){
			$format = 'application/incopyicml';
		}else{
			return;
		}

		$this->inputFiles[] = array( 'FilePath' => $filePath, 'Format' => $format, 'HtmlFrames' => null );
	}

	public function skipFile( $filePath, $level )
	{
		$filePath = $filePath; $level = $level; // keep analyzer happy
	}

	public function iterFolder( $folderPath, $level )
	{
		$folderPath = $folderPath; $level = $level; // keep analyzer happy
	}

	public function skipFolder( $folderPath, $level )
	{
		$folderPath = $folderPath; $level = $level; // keep analyzer happy
	}

}
