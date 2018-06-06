<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since 10.4.1
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_Exiftool_Exiftool_TestCase extends TestCase
{
	/** @var WW_TestSuite_BuildTest_WebServices_WflServices_Utils $wflServicesUtils */
	private $testSuiteUtils = null;

	/** @var string Ticket */
	private $ticket = '';

	/** @var bool activatedPlugin */
	private $activatedPlugin;

	/** @var WW_Utils_TestSuite */
	private $utils;

	/** @var integer[] createdImageIds */
	private $createdImageIds = array();

	/** @var Attachment[] $transferFiles */
	private $transferFiles = array();

	public function getDisplayName()
	{
		return 'ExifTool Metadata test.';
	}

	public function getTestGoals()
	{
		return 'Create database images and check if the dimensions data is properly stored.';
	}

	public function getTestMethods()
	{
		return '<ul>'.
			'<li>Scenario:</li>'.
			'<li>Creates database images based on the images stored in the ./testdata folder.</li>'.
			'<li>Reads the dimensions of the images as stored in the database.</li>'.
			'<li>Compares the stored dimensions with the dimensions that are contained in the file names of the test data.</li>'.
			'</ul>';
	}

	public function getPrio()
	{
		return 1;
	}

	final public function runTest()
	{
		try {
			$this->setupTest();
			$this->testDimensions();
		} catch( BizException $e ) {
			$this->teardownTest();
		}

		$this->teardownTest();
	}

	final public function setupTest()
	{
		$this->initTest();
		$this->enablePlugin();
		$response = $this->doLogOn();
		$suiteOpts = unserialize( TESTSUITE );
		$this->lookupPublicationInfo( $response, $suiteOpts['Brand'] );
	}

	/**
	 * Initialize the utils class.
	 */
	private function initTest()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );
	}

	/**
	 * Enable the ExifTool plug-in.
	 */
	private function enablePlugin()
	{
		$this->activatedPlugin = $this->utils->activatePluginByName( $this, 'ExifTool' );
		$this->assertNotNull( $this->activatedPlugin, 'Please make sure ExifTool plug-in is installed.' );
	}

	/**
	 * Do a log on request to acquire a ticket and publication information.
	 *
	 * @return null|WflLogOnResponse
	 */
	private function doLogOn()
	{
		$response = $this->utils->wflLogOn( $this );
		$this->assertNotNull( $response );
		$this->ticket = isset( $response->Ticket ) ? $response->Ticket : null;
		$this->assertNotNull( $this->ticket );
		return $response;
	}

	/**
	 * Retrieve the publication info from the LogOn response for the specified brand.
	 *
	 * @param WflLogOnResponse $response
	 * @param string $brandName
	 */
	private function lookupPublicationInfo( WflLogOnResponse $response, string $brandName )
	{
		$foundInfo = null;
		if( $response->Publications ) foreach( $response->Publications as $publicationInfo ) {
			if( $publicationInfo->Name == $brandName ) {
				$foundInfo = $publicationInfo;
				break;
			}
		}
		$this->assertInstanceOf( 'PublicationInfo', $foundInfo );
		$this->publicationInfo = $foundInfo;
	}

	/**
	 * The actual test. Images are read from the 'testdata' folder. Database images are created and the dimensions are
	 * checked.
	 */
	private function testDimensions()
	{
		require_once BASEDIR.'/server/utils/FolderUtils.class.php';
		$fileExtensions = array( 'jpg', 'jpeg', 'tif', 'psd', 'gif', 'png' );
		$filePath = __DIR__.'/testdata';
		FolderUtils::scanDirForFiles( $this, $filePath, $fileExtensions );
	}

	/**
	 * Handle each image of the 'testdata' folder.
	 *
	 * @param string $filePath Full file path of the file.
	 * @param integer $level Current ply in folder structure of recursion search.
	 */
	public function iterFile( string $filePath, /** @noinspection PhpUnusedParameterInspection */
	                          int $level )
	{
		$image = $this->createImage( $filePath );
		$this->checkDimensions( $image, basename( $filePath ) );
	}

	/**
	 * Check if the dimensions of the database images are as expected.
	 *
	 * See also 'testdata/readme.txt'.
	 *
	 * @param Object $image
	 * @param string $fileName
	 */
	public function checkDimensions( Object $image, string $fileName )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		$fileNameWithoutExt = MimeTypeHandler::native2DBname( $fileName, '' );
		$dimensions = explode( '_', $fileNameWithoutExt );
		$this->assertEquals( $dimensions[0], $image->MetaData->ContentMetaData->Height );
		$this->assertEquals( $dimensions[1], $image->MetaData->ContentMetaData->Width );
		$this->assertEquals( $dimensions[2], $image->MetaData->ContentMetaData->Dpi );
	}

	// These three functions are called by parent class, but have no meaning here.
	public function skipFile( $filePath, $level )
	{
		// Nothing to do.
	}

	public function iterFolder( $folderPath, $level )
	{
		// Nothing to do.
	}

	public function skipFolder( $folderPath, $level )
	{
		// Nothing to do.
	}

	/**
	 * Create new image into the DB / FileStore.
	 *
	 * @param string $imageFilePath Location of the native file.
	 * @return Object image object
	 */
	private function createImage( string $imageFilePath )
	{
		// Compose a test image workflow object in memory.
		$imageObject = $this->composeImageObject( $this->publicationInfo, $imageFilePath );

		// Upload a local image file to the transfer folder.
		require_once BASEDIR.'/server/utils/TransferClient.class.php';
		$transferClient = new WW_Utils_TransferClient( $this->ticket );
		$uploaded = $transferClient->uploadFile( $imageObject->Files[0] );
		$this->assertTrue( $uploaded );
		$this->transferFiles[] = $imageObject->Files[0];

		// Create the image workflow object in DB (and let core server move the image file to the FileStore).
		$response = $this->utils->callCreateObjectService( $this, $this->ticket, array( $imageObject ) );
		$this->assertNotNull( $response );
		$this->createdImageIds[] = $response->Objects[0]->MetaData->BasicMetaData->ID;

		return $response->Objects[0];
	}

	/**
	 * Tear down all created test data.
	 */
	private function teardownTest()
	{
		if( $this->activatedPlugin ) {
			$this->utils->deactivatePluginByName( $this, 'ExifTool' );
			$this->activatedPlugin = null;
		}
		$this->deleteImages();
		$this->cleanUpTransferServer();
		if( $this->ticket ) {
			$this->utils->wflLogOff( $this, $this->ticket );
		}
	}

	/**
	 * Make sure the created images are deleted.
	 */
	private function deleteImages()
	{
		$errorReport = '';
		if( $this->createdImageIds ) foreach( $this->createdImageIds as $createdImageId ) {
			if( !$this->utils->deleteObject( $this, $this->ticket, $createdImageId,
				'Delete image object', $errorReport, null, true, array( 'Workflow' ) ) ) {
				$this->setResult( 'WARN', $errorReport );
			}
		}
		$this->createdImageIds = array();
	}

	/**
	 * Remove the temporary files from the transfer server cache.
	 */
	private function cleanUpTransferServer()
	{
		if( $this->transferFiles ) {
			require_once BASEDIR.'/server/utils/TransferClient.class.php';
			$transferClient = new WW_Utils_TransferClient( $this->ticket );
			foreach( $this->transferFiles as $transferFile ) {
				$transferClient->cleanupFile( $transferFile );
			}
			$this->transferFiles = null;
		}
	}

	/**
	 * Compose the image object. Object can be used in the request for the WflCreateObjects service.
	 *
	 * @param PublicationInfo $pubInfo
	 * @param string $imageFilePath
	 * @return Object The image object
	 */
	private function composeImageObject( PublicationInfo $pubInfo, string $imageFilePath )
	{
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';

		$imageStatus = $this->pickObjectTypeStatus( $pubInfo, 'Image' );
		$this->assertInstanceOf( 'State', $imageStatus );

		$category = $this->pickCategory( $pubInfo );
		$this->assertInstanceOf( 'CategoryInfo', $category );

		$object = new Object();
		$object->MetaData = new MetaData();
		$object->MetaData->BasicMetaData = new BasicMetaData();
		$object->MetaData->BasicMetaData->Type = 'Image';
		$object->MetaData->BasicMetaData->Name = $this->composeObjectName( 'Image' );
		$object->MetaData->BasicMetaData->Publication = new Publication( $pubInfo->Id, $pubInfo->Name );
		$object->MetaData->BasicMetaData->Category = new Category( $category->Id, $category->Name );
		$object->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$object->MetaData->WorkflowMetaData->State = $imageStatus;

		$object->Files = array();
		$object->Files[0] = new Attachment();
		$object->Files[0]->Rendition = 'native';
		$object->Files[0]->Type = MimeTypeHandler::filePath2MimeType( $imageFilePath );
		$object->Files[0]->FilePath = $imageFilePath;

		return $object;
	}

	/**
	 * Picks a status for a given object type that is configured for a given brand ($pubInfo).
	 *
	 * It prefers picking a non-personal status, but when none found and the Personal Status
	 * feature is enabled, that status is used as fallback. When none found an error is logged.
	 *
	 * @param PublicationInfo $pubInfo
	 * @param string $objType
	 * @return State|null Picked status, or NULL when none found.
	 */
	private function pickObjectTypeStatus( PublicationInfo $pubInfo, string $objType )
	{
		$objStatus = null;
		if( $pubInfo->States ) foreach( $pubInfo->States as $status ) {
			if( $status->Type == $objType ) {
				$objStatus = $status;
				if( $status->Id != -1 ) { // prefer non-personal status
					break;
				}
			}
		}
		if( !$objStatus ) {
			$this->setResult( 'ERROR',
				'Brand "'.$pubInfo->Name.'" has no '.$objType.' Status to work with.',
				'Please check the Brand Maintenance page and configure one.' );
		}
		return $objStatus;
	}

	/**
	 * Returns the first best Category configured for the given brand ($pubInfo).
	 *
	 * @param PublicationInfo $pubInfo
	 * @return CategoryInfo|null
	 */
	private function pickCategory( PublicationInfo $pubInfo )
	{
		// Simply pick the first Category of the Brand
		$categoryInfo = count( $pubInfo->Categories ) > 0 ? $pubInfo->Categories[0] : null;
		if( !$categoryInfo ) {
			$this->setResult( 'ERROR', 'Brand "'.$pubInfo->Name.'" has no Category to work with.',
				'Please check the Brand Maintenance page and configure one.' );
		}
		return $categoryInfo;
	}

	/**
	 * Composes a unique name for a given object type.
	 *
	 * @param string $objectType
	 * @return string The object name
	 */
	private function composeObjectName( string $objectType )
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round( $microTime[0] * 1000 ) );
		return $objectType.'_'.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
	}
}