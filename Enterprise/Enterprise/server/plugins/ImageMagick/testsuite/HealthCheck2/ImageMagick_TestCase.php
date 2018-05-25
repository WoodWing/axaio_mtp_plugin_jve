<?php
/**
 * ImageMagick TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_ImageMagick_TestCase extends TestCase
{
	public function getDisplayName() { return 'ImageMagick'; }
	public function getTestGoals()   { return 'Checks if ImageMagick and GhostScript are installed. '; }
	public function getTestMethods() { return 'Shell environment settings are checked and the versions of ImageMagick and GhostScript are retrieved through the shell.'; }
    public function getPrio()        { return 25; }
	
	final public function runTest()
	{
		require_once BASEDIR . '/server/plugins/ImageMagick/ImageMagick.class.php';
		require_once BASEDIR . '/server/plugins/ImageMagick/ImageMagick_MetaData.class.php';
		require_once BASEDIR . '/server/utils/TestSuite.php';
		$utils = new WW_Utils_TestSuite();
		
		if ( OS == 'WIN' ) {
			if ( is_dir( GHOST_SCRIPT_APP_PATH ) ) { 
				$ghostscriptPath = GHOST_SCRIPT_APP_PATH;
				$gsApp = 'gswin32c.exe';
			} else { //Full path to the executable
				$ghostscriptPath = dirname( GHOST_SCRIPT_APP_PATH );
				$gsApp = basename( GHOST_SCRIPT_APP_PATH );
			}
		} else {
			$ghostscriptPath = GHOST_SCRIPT_APP_PATH;
			$gsApp = 'gs';
		}
		
		// Check defined file paths.
		if( !$utils->validateDefines( $this, array('IMAGE_MAGICK_APP_PATH', 'GHOST_SCRIPT_APP_PATH') ) ) {
			return; 
		}
		$help = 'Check the IMAGE_MAGICK_APP_PATH option at the configserver.php file.';
		if( !$utils->validateFilePath( $this, IMAGE_MAGICK_APP_PATH, $help, true, 'ERROR', WW_Utils_TestSuite::VALIDATE_PATH_NO_SPACE ) ) {
			return;	
		}
		$imApp = (OS == 'WIN') ? 'magick.exe' : 'magick';
		if( !$utils->validateFilePath( $this, IMAGE_MAGICK_APP_PATH.'/'.$imApp, $help, false ) ) {
			return;
		}
		$help = 'Check the GHOST_SCRIPT_APP_PATH option at the configserver.php file.';
		if( !$utils->validateFilePath( $this, $ghostscriptPath, $help, true, 'ERROR', WW_Utils_TestSuite::VALIDATE_PATH_NO_SPACE ) ) {
			return;
		}
		if( !$utils->validateFilePath( $this, $ghostscriptPath.'/'.$gsApp, $help, false ) ) {
			return;
		}

		// For Mac/Linux there are environment variables set runtime to let ImageMagick properly run at shell.
		// In safe mode, the safe_mode_allowed_env_vars or safe_mode_protected_env_vars options blocks us from 
		// doing such, so we complain. And, the safe_mode_exec_dir could also disturb firing commands. 
    	$help = 'Your php.ini file is located at "'.$utils->getPhpIniPath().'" folder.<br/>';
    	$help .= 'If your changes to the ini file are not reflected, try restarting the web service.';
		if( OS != 'WIN' ) {
			if ( ini_get('safe_mode') ) {
				$this->setResult( 'ERROR', "The safe_mode option is set. Should be disabled.", $help );
				return;
			}
		}
		LogHandler::Log('wwtest', 'INFO', 'Checked safe mode settings.' );
		
		// Get ImageMagick version to see if executables can be found at command shell.
		$help = 'Check your environment variables in context of the web server. '.
				'Current related variables are: <pre>'.ImageMagick::getEnvVarsHTML().'</pre>';
		$imVersion = ImageMagick::getImageMagicksVersionInfo();
		if( !$imVersion ) {
			$this->setResult( 'ERROR', 'Could not find ImageMagick application.', $help );
			return;
		}
		$imNumericVersion = explode( ' ', $imVersion );
		if( !version_compare( $imNumericVersion[1], '7', '>=' ) ) {
			$helpSupportedVersion = "Supported ImageMagick versions: ImageMagick version 7 or above.<br/>";
			$helpSupportedVersion .= "Please make sure you have installed a supported version.<br/>";
			$this->setResult( 'ERROR', 'Unsupported version of ImageMagick installed: ' . $imVersion, $helpSupportedVersion );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Found ImageMagick application: '.$imVersion );

		// Get GhostScript version to see if executables can be found at command shell.
		$gsVersion = ImageMagick::getGhostScriptVersionInfo();
		if( !$gsVersion ) {
			$this->setResult( 'ERROR', 'Could not find GhostScript application.', $help );
			return;
		}
		LogHandler::Log('wwtest', 'INFO', 'Found GhostScript application: '.$gsVersion );

		// Setup a JPEG test file to check ImageMagick doing stand-alone image conversion.
		$testFiles = array();
		$testFile = new stdClass();
		$testFile->FilePath = dirname(__FILE__).'/jpg_test.jpg';
		$testFile->Format = 'image/jpeg';
		$testFile->Width = 1050;
		$testFile->Height = 750;
		$testFile->Size = 128;
		$testFiles[] = $testFile;
		
		// Setup a PDF test file to check ImageMagick invoking GhostScript to do image conversion together.
		$testFile = new stdClass();
		$testFile->FilePath = dirname(__FILE__).'/pdf_test.pdf';
		$testFile->Format = 'application/pdf';
		$testFile->Width = 504;
		$testFile->Height = 360;
		$testFile->Size = 128;
		$testFiles[] = $testFile;
		
		// Run image conversion for the setup test files
		foreach( $testFiles as $testFile ) {

			// Let ImageMagick convert the test file, retrieve the dimensions of the original file and validate both.
			$meta = null;
			$attachment = new Attachment();
			$attachment->FilePath = $testFile->FilePath;
			$attachment->Type = $testFile->Format;
			$attachment->Rendition = 'native';
			$readMetaData = new ImageMagick_MetaData();
			require_once BASEDIR.'/server/bizclasses/BizMetaDataPreview.class.php';
			$bizMetaDataPreview = new BizMetaDataPreview();
			$meta = $readMetaData->readMetaData( $attachment, $bizMetaDataPreview );
			if( $meta['Width'] != $testFile->Width || $meta['Height'] != $testFile->Height ) {
				$this->setResult( 'ERROR', "ImageMagick image could not resolve Width or Height for {$testFile->FilePath}. ".
					"Expected size is {$testFile->Width}x{$testFile->Height} but found WxH={$meta->ContentMetaData->Width}x{$meta->ContentMetaData->Height}." , $help );
			}
			
			// Create temp output file and write converted JPEG file into it.
			$fileNameOut = tempnam( sys_get_temp_dir(), 'ent' );
			$fileResOut = fopen( $fileNameOut, 'wb' );
			if ( !$fileResOut ) {
				LogHandler::Log('ImageMagick', 'ERROR', "Conversion failed for {$testFile->FilePath}. Can't write output to file $fileResOut." );
				continue;
			}
			$metaData = new MetaData();
			$metaData->ContentMetaData = new ContentMetaData();
			$metaData->ContentMetaData->Format = $meta['Format'];
			$metaData->BasicMetaData = new BasicMetaData();
			$metaData->BasicMetaData->Type = 'Image';
			$data = ImageMagick::convertData( file_get_contents($testFile->FilePath), $testFile->Size, $metaData, false );
			fwrite( $fileResOut, $data );
			fclose( $fileResOut );

			// Let ImageMagick retrieve the dimensions of the converted JPEG output file and validate it.
			$metaData = array();
			/** @noinspection PhpDeprecationInspection */
			if( !ImageMagick::getBasicMetaData( $fileNameOut, $metaData  ) ) {
				LogHandler::Log('ImageMagick', 'ERROR', "Identification of JPEG output image failed for $fileResOut. Used input file {$testFile->FilePath} to convert." );
				continue;
			}
			if( $metaData['Width'] != $testFile->Size && $metaData['Height'] != $testFile->Size ) {
				$this->setResult( 'ERROR', "Unexpected Width or Height for JPEG output file $fileResOut. Used input file {$testFile->FilePath} to convert. ".
					"Expected size is {$testFile->Width}x{$testFile->Height} but found WxH={$metaData['Width']}x{$metaData['Height']}." , $help );
			}

			unlink( $fileNameOut );
			LogHandler::Log('wwtest', 'INFO', "Completed image conversion test for {$testFile->FilePath}." );
		}

		/*if( OS != 'WIN' ) {
			$this->setResult( 'INFO', 'Convert:<pre>'.shell_exec( 'which convert' ).'</pre>', '' );
			$this->setResult( 'INFO', '<pre>'.shell_exec( 'set' ).'</pre>', '' );
		}*/
	}
}
