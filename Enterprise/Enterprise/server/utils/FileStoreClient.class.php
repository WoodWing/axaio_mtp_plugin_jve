<?php
/**
 * Client proxy class connected through HTTP to the FileStore Server (fileindex.php).
 *
 * This is a client helper class that deals with file downloads through HTTP. For PHP scripts that are running inside
 * Enterprise Server is no reason to travel through HTTP and so this class should NOT be used; Instead, the FileStore
 * should be accessed directly. Nevertheless, for testing or integration purposes, this class can be of great help.
 * In the server architecture, the FileStoreClient, class and the SoapClient class are in the same 'layer'. They are
 * each other co-workers; One does the file downloads and the other does the service operations.
 *
 * A close alternative of the FileStoreClient class is the TransferClient class. This class supports file uploads as well,
 * and is easier to integrate for clients. It is easier because the file descriptors returned in the services refer to
 * files that are guaranteed to be present (in the Transfer Folder). However, that route is slower since it always makes
 * a copy from the FileStore to the Transfer Folder. This step is not done by the FileStore Server which makes it faster,
 * especially for large files. Another difference is that file URLs of the File Transfer Server are randomly generated
 * while the file URLs of the FileStore Server are predictable. And so the and the file URLs of the File Transfer Server
 * can only work in conjunction with web services that return those URLs, while the file URLs of the FileStore Server can
 * be composed client side.
 *
 * @package    Enterprise
 * @subpackage Utils
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_FileStoreClient
{
	/** @var \Zend\Http\Client Helper class that is internally used to download the file. */
	private $httpClient = null;

	/** @var string The ticket of the client session. */
	private $ticket = null;

	/** @var string $fileName The full path of the locally downloaded file. */
	private $fileName = null;

	/** @var Zend\Http\Response The last HTTP response, set after calling downloadFile(). */
	private $response = null;

	/**
	 * WW_Utils_FileStoreClient constructor.
	 *
	 * @param string $ticket
	 * @throws BizException on connection problems.
	 */
	public function __construct( $ticket )
	{
		$this->ticket = $ticket;
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		$this->httpClient = WW_Utils_UrlUtils::createHttpClient('');
	}

	/**
	 * Download a file from the FileStore Server over HTTP.
	 *
	 * The caller is responsible to delete the downloaded file after usage. Use getFileName() to find the local file.
	 *
	 * @param string $objectId The ID of the workflow object in Enterprise. The object may reside in workflow, history or Trash Can.
	 * @param string $rendition The file rendition. Options: native, preview, thumb, etc. See SCEnterprise.wsdl for the complete list.
	 * @param string[]|null $areas In which areas to search for. Supported values are 'Workflow' and 'Trash'. Null to search both.
	 * @param string|null $expectedError Clients can pass an expected error (S-code) on the URL of the entry point.
	 *                                   When that error is thrown, is should be logged as INFO (not as ERROR).
	 *                                   This is for testing purposes only, in case the server log must stay free of errors.
	 * @return string|null File path of downloaded file when download was successful, or null when failed.
	 */
	public function downloadFile( $objectId, $rendition, $areas = null, $expectedError = null )
	{
		PerformanceProfiler::startProfile( 'FileStore client file download', 3 );
		$this->response = null;
		$fileName = null;
		$url = LOCALURL_ROOT.INETROOT.'/fileindex.php?ticket='.urlencode( $this->ticket );
		$url .= '&objectid='.urlencode( $objectId ).'&rendition='.urlencode( $rendition );
		if( $areas ) {
			$url .= '&areas='.urlencode( implode( ',', $areas ) );
		}
		if( $expectedError ) {
			$url .= '&expectedError='. urlencode($expectedError);
		}
		LogHandler::Log( __CLASS__, 'INFO', "downloadFile: Started download \"$url\" over HTTP." );
		try {
			$this->httpClient->setUri( $url );
			$this->setCurlOptionsForSsl();
			$this->httpClient->setMethod( Zend\Http\Request::METHOD_GET );
			$this->httpClient->setStream(); // use temp file
			$this->response = $this->httpClient->send();
			if( $this->response->isSuccess() ) {
				$fileName = $this->response->getStreamName();
				LogHandler::Log( __CLASS__, 'INFO', "File download over HTTP successfully completed." );
				LogHandler::Log( __CLASS__, 'DEBUG', 'File size in bytes of download file: '.filesize( $this->fileName ) );
				$result = true;
			} else {
				LogHandler::Log( __CLASS__, 'INFO', 'downloadFile failed:<br/>'.$this->response->getHeaders()->toString() );
			}
		} catch( Exception $e ) {
			LogHandler::Log( __CLASS__, 'INFO', 'downloadFile failed: '.$e->getMessage() );
		}
		PerformanceProfiler::stopProfile( 'FileStore client file download', 3 );

		return $fileName;
	}

	/**
	 * Retrieve the HTTP status code of the file download.
	 *
	 * @return integer
	 */
	public function getLastHttpResponseCode()
	{
		return $this->response->getStatusCode();
	}

	/**
	 * Retrieve the object version of the downloaded file.
	 *
	 * @return string|null The object version in major.minor notation, or NULL when response was an error.
	 */
	public function getObjectVersion()
	{
		$version = null;
		if( $this->response->isSuccess() ) {
			$header = $this->response->getHeaders()->get( 'WW-Object-Version' );
			if( $header) {
				$version = $header->getFieldValue();
			}
		}
		return $version;
	}

	/**
	 * If the Transfer Server is accessed over SSL extra options on the Curl adapter has to be set.
	 *
	 * @throws BizException
	 */
	public function setCurlOptionsForSsl()
	{
		if(  $this->httpClient->getUri() && $this->httpClient->getUri()->getScheme() == 'https' ) {
			$certificate = $this->getCertificate();

			if( !$certificate ) {
				throw new BizException( null, 'Server', null,
					'The certificate file, to access the Transfer Server over SSL, does not exist.' );
			}

			$curlOptions = array(
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_SSL_VERIFYPEER => 1,
				CURLOPT_CAINFO => $certificate
			);

			$this->httpClient->setOptions( array( 'curloptions' => $curlOptions ) );
		}
	}

	/**
	 * Retrieve the SSL certificate to be used for the HTTPS connection.
	 *
	 * @return string
	 */
	private function getCertificate()
	{
		$certificateSpecific = BASEDIR.'/config/encryptkeys/transferserver/cacert.pem';
		$certificateCommon = BASEDIR.'/config/encryptkeys/cacert.pem';
		$certificate = '';

		if( file_exists( $certificateSpecific ) ) {
			$certificate = $certificateSpecific;
		} elseif( file_exists( $certificateCommon ) ) {
			$certificate = $certificateCommon;
		}

		return $certificate;
	}
}
