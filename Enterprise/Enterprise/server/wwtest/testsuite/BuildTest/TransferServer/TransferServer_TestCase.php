<?php

require_once BASEDIR . '/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_TransferServer_TransferServer_TestCase extends TestCase
{

	private $suiteOpts = null;
	private $ticket;
	private $files = array();

	public function getDisplayName()
	{
		return 'Upload, Download and Delete';
	}

	public function getTestGoals()
	{
		return 'Tests the file upload, download, delete and compression features provided by the Transfer Server.';
	}

	public function getTestMethods()
	{
		return 'Uploads a local JPEG and WCML file to the Transfer Server cache, '.
			'downloads them again and compares the file sizes. Since the CS Web client '.
			'uploads files by using the multipart HTTP POST form method, and the CS Air client '.
			'does use the normal HTTP PUT method to upload files, both ways are tested: '.
			'<ul>'.
				'<li>CS Air: Uncompressed JPEG upload through HTTP PUT</li>'.
				'<li>CS Web: Uncompressed JPEG upload through HTTP POST</li>'.
				'<li>CS Air: Uncompressed WCML upload through HTTP PUT</li>'.
				'<li>CS Air: Compressed WCML upload through HTTP PUT</li>'.
				'<li>CS Web: Compressed WCML upload through HTTP POST</li>'.
				//'<li>CS Web: Compressed WCML upload through HTTP POST and stripped* download</li>'.
			'</ul>'.
			'<p>For downloads HTTP GET is used and for cleaning files HTTP DELETE is used. '.
			'For all test cases, when a file is uploaded in a compressed format, it is also compressed for download. '.
			'For a compressed file transfer, the file is locally read from disk, locally compressed, '.
			'uploaded over HTTP, uncompressed on-the-fly, written in transfer cache, read from transfer cache, '.
			'compressed on-the-fly, downloaded over HTTP, uncompressed and finally written to local disk.</p>';
			//'<p>* For download of WCML articles, duplicate style definitions can be stripped. This is to '.
			//'optimize download speed and less XML nodes also result into faster opening in CS.</p>';
	}

	public function getPrio()
	{
		return 1;
	}

	final public function runTest()
	{
		$this->logOn();
		$this->uploadFiles();
		$this->downloadAndCompareFiles();
		$this->cleanupFiles();
		$this->logOff();

		return true;
	}

	public function __construct()
	{
		require_once BASEDIR . '/server/authorizationmodule.php';
		global $globAuth;
		$globAuth = new authorizationmodule();
		$this->suiteOpts = unserialize( TESTSUITE );
	}

	/**
	 * Logon to Enterprise
	 */
	private function logOn()
	{
		require_once BASEDIR . '/server/services/wfl/WflLogOnService.class.php';

		$req = new WflLogOnRequest();
		$req->User = $this->suiteOpts['User'];
		$req->Password = $this->suiteOpts['Password'];
		$req->Ticket = '';
		$req->Server = '';
		$req->ClientName = '';
		$req->Domain = '';
		$req->ClientAppName = 'Transfer Server';
		$req->ClientAppVersion = 'v'.SERVERVERSION;
		$req->ClientAppSerial = '';
		$req->ClientAppProductKey = '';
		$req->RequestTicket = null; // obsoleted
		$req->RequestInfo = array(); // ticket only

		$service = new WflLogOnService();
		$resp = $service->execute( $req );
		$this->ticket = $resp->Ticket;
	}

	/**
	 * Logoff from Enterprise
	 */
	private function logOff()
	{
		require_once BASEDIR . '/server/services/wfl/WflLogOffService.class.php';

		$req = new WflLogOffRequest();
		$req->Ticket = $this->ticket;
		$req->SaveSettings = false;

		$service = new WflLogOffService();
		$service->execute( $req );
	}

	/**
	 * Upload files from a local folder to the Transfer Server cache. Only PUT method is supported.
	 */
	private function uploadFiles()
	{
		require_once BASEDIR . '/server/utils/TransferClient.class.php';
		// Be sure Transfer Server is not moved out Enterprise

		// Prepare normal JPEG upload (uncompressed, HTTP PUT)
		$localPath = dirname( __FILE__ ) . '/testdata/thumb1.jpg';
		$this->files['thumb']['attachment'] = new Attachment();
		$this->files['thumb']['attachment']->FilePath = $localPath;
		$this->files['thumb']['attachment']->Rendition = 'thumb';
		$this->files['thumb']['attachment']->Type = 'image/jpeg';
		$this->files['thumb']['localpath'] = $localPath;
		$this->files['thumb']['compression'] = ''; // no compression
		$this->files['thumb']['httpmethod'] = 'PUT';

		// Prepare JPEG upload over HTTP POST (to simulate CS Web)
		$localPath = dirname( __FILE__ ) . '/testdata/thumb1.jpg';
		$this->files['thumb_csweb']['attachment'] = new Attachment();
		$this->files['thumb_csweb']['attachment']->FilePath = $localPath;
		$this->files['thumb_csweb']['attachment']->Rendition = 'thumb';
		$this->files['thumb_csweb']['attachment']->Type = 'image/jpeg';
		$this->files['thumb_csweb']['localpath'] = $localPath;
		$this->files['thumb_csweb']['compression'] = ''; // no compression
		$this->files['thumb_csweb']['httpmethod'] = 'POST';
		
		// Prepare normal WCML upload (uncompressed, HTTP PUT)
		$localPath = dirname( __FILE__ ) . '/testdata/article1.wcml';
		$this->files['article']['attachment'] = new Attachment();
		$this->files['article']['attachment']->FilePath = $localPath;
		$this->files['article']['attachment']->Rendition = 'native';
		$this->files['article']['attachment']->Type = 'application/incopyicml';
		$this->files['article']['localpath'] = $localPath;
		$this->files['article']['compression'] = ''; // no compression
		$this->files['article']['httpmethod'] = 'PUT';

		// Prepare compressed WCML upload (HTTP PUT)
		$localPath = dirname( __FILE__ ) . '/testdata/article1.wcml';
		$this->files['article_compressed']['attachment'] = new Attachment();
		$this->files['article_compressed']['attachment']->FilePath = $localPath;
		$this->files['article_compressed']['attachment']->Rendition = 'native';
		$this->files['article_compressed']['attachment']->Type = 'application/incopyicml';
		$this->files['article_compressed']['localpath'] = $localPath;
		$this->files['article_compressed']['compression'] = 'deflate';
		$this->files['article_compressed']['httpmethod'] = 'PUT';

		// Prepare compressed WCML upload over HTTP POST (to simulate CS Web)
		$localPath = dirname( __FILE__ ) . '/testdata/article1.wcml';
		$this->files['article_compressed_csweb']['attachment'] = new Attachment();
		$this->files['article_compressed_csweb']['attachment']->FilePath = $localPath;
		$this->files['article_compressed_csweb']['attachment']->Rendition = 'native';
		$this->files['article_compressed_csweb']['attachment']->Type = 'application/incopyicml';
		$this->files['article_compressed_csweb']['localpath'] = $localPath;
		$this->files['article_compressed_csweb']['compression'] = 'deflate';
		$this->files['article_compressed_csweb']['httpmethod'] = 'POST';

		// Prepare compressed WCML upload and strip duplicate styles for download (HTTP PUT)
		/* COMMENTED OUT: still in experimental phase
		$localPath = dirname( __FILE__ ) . '/testdata/article1.wcml';
		$this->files['article_comp_strip']['attachment'] = new Attachment();
		$this->files['article_comp_strip']['attachment']->FilePath = $localPath;
		$this->files['article_comp_strip']['attachment']->Rendition = 'native';
		$this->files['article_comp_strip']['attachment']->Type = 'application/incopyicml';
		$this->files['article_comp_strip']['localpath'] = $localPath;
		$this->files['article_comp_strip']['compression'] = 'deflate';
		$this->files['article_comp_strip']['httpmethod'] = 'POST';
		$this->files['article_comp_strip']['stripwcml'] = 'styles';
		$this->files['article_comp_strip']['stripsize'] = 890467; // expected WCML file size after stripping
		*/
		
		foreach( $this->files as $key => $file ) {
			$transferClient = new WW_Utils_TransferClient( $this->ticket );
			if ( !$transferClient->uploadFile( $file['attachment'], $file['compression'], $file['httpmethod'] ) ) {
				$this->setResult( 'ERROR', 'Failed uploading '.$key.' file '.$file['attachment']->FilePath, '' );
			}
		}
	}

	/**
	 * Download files from the Transfer Server cache to a local folder.
	 * After download, compare the downloaded file size with the original one.
	 */
	private function downloadAndCompareFiles()
	{
		require_once BASEDIR . '/server/utils/TransferClient.class.php';
		// Be sure Transfer Server is not moved out Enterprise
		foreach( $this->files as $key => $file ) {
			$transferClient = new WW_Utils_TransferClient( $this->ticket );
			$url = $file['attachment']->FileUrl;
			$stripWcml = isset($file['stripwcml']) ? $file['stripwcml'] : '';
			if ( !$transferClient->downloadFile( $file['attachment'], false, $file['compression'], $stripWcml ) ) {
				$this->setResult( 'ERROR', 'Failed to download the '.$key.' file from URL: '.$url );
			} else {
				$oriFileSize = isset($file['stripsize']) ? $file['stripsize'] : filesize( $file['localpath'] );
				$downloadFileSize = strlen( $file['attachment']->Content );
				if ( $downloadFileSize != $oriFileSize ) {
					$this->setResult( 'ERROR', 'The original size ('.$oriFileSize.') '.
						'of the '.$key.' file differs from the downloaded size ('.$downloadFileSize.').', '' );
				}
			}
		}
	}

	/**
	 * Upload file from filepath to transfer server. Only PUT method is supported.
	 */
	private function cleanupFiles()
	{
		require_once BASEDIR . '/server/utils/TransferClient.class.php';
		// Be sure Transfer Server is not moved out Enterprise
		foreach( $this->files as $key => $file ) {
			$transferClient = new WW_Utils_TransferClient( $this->ticket );
			$url = $file['attachment']->FileUrl;
			if ( !$transferClient->cleanupFile( $file['attachment'] ) ) {
				$this->setResult( 'ERROR', 'Failed deleting '.$key.' file using URL: '.$url, '' );
			}
		}
	}

}
