<?php
/**
 * @package    Enterprise
 * @subpackage TestSuite
 * @since      v10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';
require_once BASEDIR.'/server/utils/TestSuite.php';

class WW_TestSuite_BuildTest_FileStoreServer_Download_TestCase extends TestCase
{
	/** @var WW_Utils_FileStoreClient $client */
	private $client;

	/** @var WW_Utils_TestSuite $utils */
	private $utils;

	/** @var string $ticket */
	private $ticket;

	/** @var PublicationInfo $publicationInfo */
	private $publicationInfo;

	/** @var Attachment[] $transferFiles */
	private $transferFiles = array();

	/** @var Object $images */
	private $image = array();

	/** @var boolean $imageInTrash Whether or not the image was moved to the Trash Can. */
	private $imageInTrash = false;

	public function getDisplayName() { return 'FileStore Server - Download'; }
	public function getTestGoals()   { return 'Checks if files can be downloaded from the FileStore Server.'; }
	public function getPrio()        { return 10; }

	public function getTestMethods()
	{
		return 
			'Setup:<ol>
				<li>Login user.</li>
				<li>Create image object.</li>
			</ol>
			File downloads:<ol>
				<li>Download and compare files.</li>
			</ol>
			Tear down:<ol>
				<li>Delete image object.</li>
				<li>Logout user.</li>
			</ol>
			';
	}
	
	public function runTest()
	{
		try {
			$this->setUpTestData();

			$this->createImage();
			$this->testDownloadAfterCreateImage();
			$this->saveImage();
			$this->testDownloadAfterSaveImage();
			$this->removeImage();
			$this->testDownloadAfterRemoveImage();
			// TODO: similar scenario as above, but then for an Elvis shadow image
		}
		catch( BizException $e ) {
		}
		$this->tearDownTestData();
	}

	/**
	 * Setup all test-specific data.
	 */
	private function setUpTestData()
	{
		require_once BASEDIR.'/server/utils/TestSuite.php';
		$this->utils = new WW_Utils_TestSuite();
		$this->utils->initTest( 'JSON' );

		// LogOn test user through workflow interface.
		$response = $this->utils->wflLogOn( $this );
		$this->ticket = isset( $response->Ticket ) ? $response->Ticket : null;
		$this->assertNotNull( $this->ticket );

		$suiteOpts = unserialize( TESTSUITE );
		$this->publicationInfo = $this->lookupPublicationInfo( $response, $suiteOpts['Brand'] );
		$this->assertInstanceOf( 'PublicationInfo', $this->publicationInfo );
	}

	/**
	 * Tear down all test-specific data.
	 */
	private function tearDownTestData()
	{
		try {
			if( $this->image ) {
				$errorReport = null;
				$id = $this->image->MetaData->BasicMetaData->ID;
				$areas = $this->imageInTrash ? array( 'Trash' ) : array( 'Workflow' );
				if( !$this->utils->deleteObject( $this, $this->ticket, $id,
					'Delete image object', $errorReport, null, true, $areas )
				) {
					$this->setResult( 'ERROR', "Could not tear down object with id {$id}.".$errorReport );
				}
				$this->image = null;
			}
		} catch( BizException $e ) {
		}

		try {
			if( $this->transferFiles ) {
				require_once BASEDIR.'/server/utils/TransferClient.class.php';
				$transferClient = new WW_Utils_TransferClient( $this->ticket );
				foreach( $this->transferFiles as $transferFile ) {
					$transferClient->cleanupFile( $transferFile );
				}
				$this->transferFiles = null;
			}
		} catch( BizException $e ) {
		}

		try {
			if( $this->ticket ) {
				$this->utils->wflLogOff( $this, $this->ticket );
				$this->ticket = null;
			}
		} catch( BizException $e ) {
		}
	}

	/**
	 * Create new image into the DB / FileStore.
	 */
	private function createImage()
	{
		// Compose a test image workflow object in memory.
		$imageFilePath = __DIR__.'/testdata/image1.jpg';
		$imageObject = $this->composeImageObject( $this->publicationInfo, $imageFilePath );
		$imageObject->MetaData->BasicMetaData->Name = self::composeObjectName('Image');

		// Upload a local image file to the transfer folder.
		require_once BASEDIR.'/server/utils/TransferClient.class.php';
		$transferClient = new WW_Utils_TransferClient( $this->ticket );
		$uploaded = $transferClient->uploadFile( $imageObject->Files[0] );
		$this->assertTrue( $uploaded );
		$this->transferFiles[] = $imageObject->Files[0];

		// Create the image workflow object in DB (and let core server move the image file to the FileStore).
		$lock = true; // keep checked out
		$response = $this->utils->callCreateObjectService( $this, $this->ticket, array($imageObject), $lock );
		$this->assertNotNull( $response );

		$this->image = $response->Objects[0];
	}

	/**
	 * Test all kinds of ways of downloading the image once the image has been created (v0.1).
	 */
	private function testDownloadAfterCreateImage()
	{
		require_once BASEDIR.'/server/utils/FileStoreClient.class.php';

		$imageObject = $this->image;
		$imageId = $imageObject->MetaData->BasicMetaData->ID;
		$imageVersion = $imageObject->MetaData->WorkflowMetaData->Version;

		$client = new WW_Utils_FileStoreClient( $this->ticket );
		$uploadedImageFilePath = __DIR__.'/testdata/image1.jpg';

		// Download latest version and request to search in both areas.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native' );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( '0.1', $client->getObjectVersion() );
		$this->assertEquals( md5_file( $uploadedImageFilePath ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );

		// Download image and request to search in Workflow only.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native', array('Workflow') );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( $imageVersion, $client->getObjectVersion() );
		$this->assertEquals( md5_file( $uploadedImageFilePath ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );

		// Download image and request to search in both areas; Workflow and Trash.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native', array('Workflow','Trash') );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( $imageVersion, $client->getObjectVersion() );
		$this->assertEquals( md5_file( $uploadedImageFilePath ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );

		// Try to download from Trash Can (expecting fail) while image is in Workflow.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native', array('Trash') );
		$this->assertNull( $downloadedImageFilePath, 'Image download successful (unexpected).' );
		$this->assertEquals( 404, $client->getLastHttpResponseCode() );

		// Try to download a non-existing file rendition.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'output' );
		$this->assertNull( $downloadedImageFilePath, 'Image download successful (unexpected).' );
		$this->assertEquals( 404, $client->getLastHttpResponseCode() );

		// Try to download a non-existing image (unknown object id).
		$downloadedImageFilePath = $client->downloadFile( PHP_INT_MAX-1, 'native' );
		$this->assertNull( $downloadedImageFilePath, 'Image download successful (unexpected).' );
		$this->assertEquals( 404, $client->getLastHttpResponseCode() );
	}

	/**
	 * Save new version of the image into the DB / FileStore.
	 */
	private function saveImage()
	{
		// Compose a test image workflow object in memory.
		$imageFilePath = __DIR__.'/testdata/image2.jpg';
		$imageObject = $this->composeImageObject( $this->publicationInfo, $imageFilePath );
		$imageObject->MetaData->BasicMetaData->ID = $this->image->MetaData->BasicMetaData->ID;
		$imageObject->MetaData->BasicMetaData->Name = $this->image->MetaData->BasicMetaData->Name;

		// Upload a local image file to the transfer folder.
		require_once BASEDIR.'/server/utils/TransferClient.class.php';
		$transferClient = new WW_Utils_TransferClient( $this->ticket );
		$uploaded = $transferClient->uploadFile( $imageObject->Files[0] );
		$this->assertTrue( $uploaded );
		$this->transferFiles[] = $imageObject->Files[0];

		// Save the image workflow object in DB (and let core server move the image file to the FileStore).
		$unlock = true; // check-in
		$response = $this->utils->saveObjects( $this, $this->ticket, array($imageObject), $unlock );
		$this->assertNotNull( $response );

		$this->image = $response->Objects[0];
	}

	/**
	 * Test all kinds of ways of downloading the image once the image has been saved (v0.2).
	 */
	private function testDownloadAfterSaveImage()
	{
		require_once BASEDIR.'/server/utils/FileStoreClient.class.php';

		$imageObject = $this->image;
		$imageId = $imageObject->MetaData->BasicMetaData->ID;
		$imageVersion = $imageObject->MetaData->WorkflowMetaData->Version;

		$client = new WW_Utils_FileStoreClient( $this->ticket );
		$uploadedImageFilePath1 = __DIR__.'/testdata/image1.jpg';
		$uploadedImageFilePath2 = __DIR__.'/testdata/image2.jpg';

		// Download latest version and request to search in both areas.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native' );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( $imageVersion, $client->getObjectVersion() );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( md5_file( $uploadedImageFilePath2 ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );

		// Download image and request to search in both areas; Workflow and Trash.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native', array('Workflow','Trash') );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( $imageVersion, $client->getObjectVersion() );
		$this->assertEquals( md5_file( $uploadedImageFilePath2 ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );
	}

	/**
	 * Move the image to the Trash Can.
	 */
	private function removeImage()
	{
		$errorReport = null;
		$permanent = false; // move to trash
		$trashed = $this->utils->deleteObject( $this, $this->ticket, $this->image->MetaData->BasicMetaData->ID,
			'Delete image object', $errorReport, null, $permanent, array( 'Workflow' ) );
		$this->assertTrue( $trashed );

		$this->imageInTrash = true;
	}

	/**
	 * Test all kinds of ways of downloading the image once the image has been moved to trash.
	 */
	private function testDownloadAfterRemoveImage()
	{
		require_once BASEDIR.'/server/utils/FileStoreClient.class.php';

		$imageObject = $this->image;
		$imageId = $imageObject->MetaData->BasicMetaData->ID;
		$imageVersion = $imageObject->MetaData->WorkflowMetaData->Version;

		$client = new WW_Utils_FileStoreClient( $this->ticket );
		$uploadedImageFilePath1 = __DIR__.'/testdata/image1.jpg';
		$uploadedImageFilePath2 = __DIR__.'/testdata/image2.jpg';

		// Download latest version and request to search in both areas.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native' );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( $imageVersion, $client->getObjectVersion() );
		$this->assertEquals( md5_file( $uploadedImageFilePath2 ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );

		// Download image and request to search in Trash only.
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native', array('Trash') );
		$this->assertNotNull( $downloadedImageFilePath, 'Image download failed.' );
		$this->assertEquals( 200, $client->getLastHttpResponseCode() );
		$this->assertEquals( $imageVersion, $client->getObjectVersion() );
		$this->assertEquals( md5_file( $uploadedImageFilePath2 ), md5_file( $downloadedImageFilePath ),
			'The downloaded file differs from the uploaded file.' );

		// Try to download image in Workflow only (while image is in Trash Can).
		$downloadedImageFilePath = $client->downloadFile( $imageId, 'native', array('Workflow') );
		$this->assertNull( $downloadedImageFilePath, 'Image download successful (unexpected).' );
		$this->assertEquals( 404, $client->getLastHttpResponseCode() );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Lookup a brand by name that is returned in the LogOn response.
	 *
	 * @param WflLogOnResponse $response
	 * @param string $brandName
	 * @return PublicationInfo|null
	 */
	private function lookupPublicationInfo( WflLogOnResponse $response, $brandName )
	{
		$foundInfo = null;
		if( $response->Publications ) foreach( $response->Publications as $publicationInfo ) {
			if( $publicationInfo->Name == $brandName ) {
				$foundInfo = $publicationInfo;
				break;
			}
		}
		if( !$foundInfo ) {
			$this->setResult( 'ERROR', 'Could not find the test Brand "'.$brandName.'".',
				'Please check the TESTSUITE setting in configserver.php.' );
		}
		return $foundInfo;
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
	private function pickObjectTypeStatus( PublicationInfo $pubInfo, $objType )
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
	 *
	 * @param PublicationInfo $pubInfo
	 * @param string $imageFilePath
	 * @return Object The image object
	 */
	private function composeImageObject( PublicationInfo $pubInfo, $imageFilePath )
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
		$object->MetaData->BasicMetaData->Publication = new Publication( $pubInfo->Id, $pubInfo->Name );
		$object->MetaData->BasicMetaData->Category = new Category( $category->Id, $category->Name );

		$object->Files = array();
		$object->Files[0] = new Attachment();
		$object->Files[0]->Rendition = 'native';
		$object->Files[0]->Type = MimeTypeHandler::filePath2MimeType( $imageFilePath );
		$object->Files[0]->FilePath = $imageFilePath;

		return $object;
	}

	/**
	 * Composes a unique name for a given object type.
	 *
	 * @param string $objectType
	 * @return string The object name
	 */
	static private function composeObjectName( $objectType )
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		return $objectType.'_'.date( 'Y m d H i s', $microTime[1] ).' '.$miliSec;
	}
}