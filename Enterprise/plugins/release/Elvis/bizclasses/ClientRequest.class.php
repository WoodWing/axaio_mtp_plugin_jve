<?php
/**
 * REST request composer for the Elvis_BizClasses_Client class.
 *
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.*
 */

class Elvis_BizClasses_ClientRequest
{
	const HTTP_METHOD_GET = 'GET';
	const HTTP_METHOD_POST = 'POST';

	/** @var array */
	private $pathParams;

	/** @var array */
	private $queryParams;

	/** @var string */
	private $userShortName;

	/** @var bool */
	private $expectJson = false;

	/** @var Attachment|null */
	private $fileToUpload = null;

	/** @var string */
	private $httpMethod = self::HTTP_METHOD_GET;

	/**
	 * Constructor.
	 *
	 * @param string $relativeRestServicePath
	 * @param string $userShortName For who this request should be authorized.
	 */
	public function __construct( string $relativeRestServicePath, string $userShortName )
	{
		$relativeRestServicePath = trim( $relativeRestServicePath, '/' );
		foreach( explode( '/', $relativeRestServicePath ) as $pathParam ) {
			$this->addPathParam( $pathParam );
		}
		$this->userShortName = $userShortName;
	}

	/**
	 * Retrieve the user name for which this request should be authorized.
	 *
	 * @return string
	 */
	public function getUserShortName()
	{
		return $this->userShortName;
	}

	/**
	 * Add a string value parameter to the REST service request.
	 *
	 * @param string $value
	 */
	public function addPathParam( string $value )
	{
		$this->pathParams[] = urlencode( $value );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Add a query parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addQueryParam( string $name, string $value )
	{
		$this->queryParams[ urlencode( $name ) ] = urlencode( $value );
	}

	/**
	 * Add a query parameter to the REST service request that needs to be JSON encoded.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function addQueryParamAsJson( string $name, $value )
	{
		$this->addQueryParam( $name, json_encode( $value ) );
	}

	/**
	 * Add a search query parameter to the REST service request.
	 *
	 * @param string $paramName
	 * @param string $searchName
	 * @param string $searchValue
	 */
	public function addSearchQueryParam( string $paramName, string $searchName, string $searchValue )
	{
		$this->queryParams[ urlencode( $paramName ) ] = urlencode( $searchName ).':"'.urlencode( $searchValue ).'"';
	}

	/**
	 * Add a CSV (comma separated value list) query parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string[] $values
	 */
	public function addCsvQueryParam( string $name, array $values )
	{
		$values = array_map( 'strval', $values );
		$values = array_map( 'urlencode', $values );
		$this->queryParams[ $name ] = implode( ',', $values );
	}

	/**
	 * Compose the relative REST service path. Base path and query parameters are excluded.
	 *
	 * @return string
	 */
	public function composeServicePath(): string
	{
		return implode( '/', $this->pathParams );
	}

	/**
	 * Compose the relative REST service URL. Base path is excluded. Query parameters are included.
	 *
	 * @return string
	 */
	public function composeServiceUrl(): string
	{
		$queryParamsUrl = '';
		if( $this->queryParams && $this->httpMethod == self::HTTP_METHOD_GET ) {
			$separator = '?';
			foreach( $this->queryParams as $name => $value ) {
				$queryParamsUrl .= $separator.$name.'='.$value;
				$separator = '&';
			}

		}
		return $this->composeServicePath().$queryParamsUrl;
	}

	/**
	 * Define the HTTP POST method for the request.
	 */
	public function setHttpPostMethod()
	{
		$this->httpMethod = self::HTTP_METHOD_POST;
	}

	/**
	 * @return string
	 */
	public function getHttpMethod()
	{
		return $this->httpMethod;
	}

	/**
	 * Call this function when a JSON structure is expected in the response.
	 */
	public function setExpectJson()
	{
		$this->expectJson = true;
	}

	/**
	 * Tells whether or not a JSON structure is expected in the response.
	 *
	 * @return bool
	 */
	public function getExpectJson()
	{
		return $this->expectJson;
	}

	/**
	 * @param Attachment $file
	 */
	public function addFileToUpload( Attachment $file )
	{
		$this->fileToUpload = $file;
	}

	/**
	 * @return Attachment|null
	 */
	public function getFileToUpload()
	{
		return $this->fileToUpload;
	}
}