<?php
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Sips_SipsPreview_TestCase extends TestCase
{
	public function getDisplayName() { return 'Sips Image Preview on Mac'; }
	public function getTestGoals()   { return 'By using the sips command prompt in Mac, ' . 
											  'to generate preview for various image file format like ' .
	 										  'jpeg, gif, png, tiff, psd, eps, ai and pdf.'; }
	public function getTestMethods() { return 'Generate each different format image preview using sips command.'; }
    public function getPrio()        { return 101; }
    
    final public function runTest()
	{
		if( OS != 'UNIX' ) {
			$this->setResult( 'WARN', 'Sips only available in Mac OS', '' );
			return; // nothing to test
		}

		// Make sure the SipsPreview plugin is active (enabled).
		require_once BASEDIR.'/server/bizclasses/BizServerPlugin.class.php';
		$didActivate = false;
		if( !BizServerPlugin::isPluginActivated( 'SipsPreview' ) ) {
			$didActivate = BizServerPlugin::activatePluginByName( 'SipsPreview' );
			if( !$didActivate ) {
				$this->setResult( 'ERROR', 'Server plugin "Sips Preview" '.
					'is not active and could not be activated', '' );
				return;
			}
		}

		// Run the test.
		$this->doTest();
		
		// De-activate the SipsPreview plugin again (but only when we did activate).
		if( $didActivate ) {
			if( !BizServerPlugin::deactivatePluginByName( 'SipsPreview' ) ) {
				$this->setResult( 'ERROR', 'Server plugin "Sips Preview" '.
					'was made active but could not be deactivated.', '' );
			}
		}
	}
	
	private function doTest()
	{			
		$testImagesDir = dirname(__FILE__) .'/Sips_test_images/';
		$images = array();
		if( ($handle = opendir($testImagesDir)) ) {
			// Loop through the dir to get the all images
			while (false !== ($file = readdir($handle))) {
				if($file!="." && $file!=".." && $file!='.DS_Store'){
					$images[] = $file;
				}
			}
			closedir($handle);
		}
		$connector = BizServerPlugin::searchConnectorByClassName( 'SipsPreview_Preview' );
		if( is_null($connector) ) {
			$this->setResult( 'ERROR', 'Could not load the SipsPreview_Preview connector', '' );
		} else {
			foreach( $images as $image ) {
				require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
				$format = MimeTypeHandler::filePath2MimeType( $image );
				self::convertImage( $testImagesDir, $image, $format, $connector );
			}
		}
	}

	private function convertImage( $imageDir, $image, $format, $connector )
	{
		LogHandler::Log('SipsPreview_TestCase', 'DEBUG', 'Convert image ' . $image);
		$previewFormat = null;
		$meta = new MetaData();
		$meta->ContentMetaData = new ContentMetaData();
		$meta->ContentMetaData->Format = $format;
		$imagePath = $imageDir . $image;

		$sourceAttachment = new Attachment();
		$sourceAttachment->Type = $format;
		$sourceAttachment->FilePath = $imagePath;
		$maxPreview = 600;
		$preview = $connector->generatePreview( $sourceAttachment, $maxPreview, $previewFormat, $meta, null );
		if( !empty($preview) ) {
			$sourceAttachment->Content = $preview;
			$sourceAttachment->FilePath = '';
			$maxThumb = 100;
			$thumbnail = $connector->generatePreview( $sourceAttachment, $maxThumb, $previewFormat, $meta, null );
			if( empty($thumbnail) ) {
				$this->setResult( 'ERROR', "Sips failed to generate image thumbnail for $imagePath.", '' );
			}
		} else {
			$this->setResult( 'ERROR', "Sips failed to generate image preview for $imagePath.", '' );
		}
	}
}
