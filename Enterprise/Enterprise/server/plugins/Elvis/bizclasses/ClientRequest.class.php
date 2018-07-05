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
	const HTTP_METHOD_PUT = 'PUT';
	const HTTP_METHOD_DELETE = 'DELETE';

	/** @var array */
	private $pathParams;

	/** @var array */
	private $queryParams = array();

	/** @var array */
	private $postParams = array();

	/** @var string|null */
	private $userShortName = null;

	/** @var bool */
	private $expectJson = false;

	/** @var bool */
	private $expectRawData = false;

	/** @var Attachment|null */
	private $fileToUpload = null;

	/** @var string */
	private $httpMethod = self::HTTP_METHOD_GET;

	/** @var int */
	private $attempt = 1;

	/** @var string */
	private $subjectEntity = '';

	/** @var string */
	private $subjectId = '';

	/** @var string */
	private $subjectName = '';

	/** @var bool */
	private $notFoundIsSevere = false;

	/** @var string|null request body, can be for POST, GET, etc. When null, no body is set. */
	private $body = null;

	/** @var array */
	private $headers = array();

	/**
	 * Compose a new request class for which no authorization is required.
	 *
	 * @param string $relativeRestServicePath
	 * @return Elvis_BizClasses_ClientRequest
	 */
	public static function newUnauthorizedRequest( string $relativeRestServicePath ) : Elvis_BizClasses_ClientRequest
	{
		return new self( $relativeRestServicePath );
	}

	/**
	 * Compose a new request class for which authorization is required.
	 *
	 * @param string $relativeRestServicePath
	 * @param string $userShortName
	 * @return Elvis_BizClasses_ClientRequest
	 */
	public static function newAuthorizedRequest( string $relativeRestServicePath, string $userShortName ) : Elvis_BizClasses_ClientRequest
	{
		$request = new self( $relativeRestServicePath );
		$request->setUserShortName( $userShortName );
		return $request;
	}

	/**
	 * Constructor.
	 *
	 * @param string $relativeRestServicePath
	 */
	private function __construct( string $relativeRestServicePath )
	{
		$relativeRestServicePath = trim( $relativeRestServicePath, '/' );
		foreach( explode( '/', $relativeRestServicePath ) as $pathParam ) {
			$this->addPathParam( $pathParam );
		}
	}

	/**
	 * Set the user name for which this request should be authorized.
	 *
	 * @param string $userShortName
	 */
	public function setUserShortName( string $userShortName )
	{
		$this->userShortName = $userShortName;
	}

	/**
	 * Retrieve the user name for which this request should be authorized.
	 *
	 * @return string|null
	 */
	public function getUserShortName(): ?string
	{
		return $this->userShortName;
	}

	/**
	 * @return bool
	 */
	public function requiresAuthentication(): bool
	{
		return $this->getUserShortName() && is_null( $this->getHeader( 'Authorization' ) );
	}

	/**
	 * Increment the number of times this request was repeated.
	 */
	public function nextAttempt()
	{
		$this->attempt += 1;
	}

	/**
	 * Retrieve the number of times this request was repeated.
	 *
	 * @return integer
	 */
	public function getAttempt(): int
	{
		return $this->attempt;
	}

	/**
	 * Indicate the subject entity of this request.
	 *
	 * @return string
	 */
	public function getSubjectEntity(): string
	{
		return $this->subjectEntity;
	}

	/**
	 * Retrieve the subject entity of this request.
	 *
	 * @param string $entity
	 */
	public function setSubjectEntity( string $entity ): void
	{
		$this->subjectEntity = $entity;
	}

	/**
	 * Retrieve the subject id of this request.
	 *
	 * @return string
	 */
	public function getSubjectId(): string
	{
		return $this->subjectId;
	}

	/**
	 * Indicate the subject id of this request.
	 *
	 * @param string $id
	 */
	public function setSubjectId( string $id ): void
	{
		$this->subjectId = $id;
	}

	/**
	 * Retrieve the subject name of this request.
	 */
	public function getSubjectName(): string
	{
		return $this->subjectName;
	}

	/**
	 * Indicate the subject name of this request.
	 *
	 * @param string $name
	 */
	public function setSubjectName( string $name ): void
	{
		$this->subjectName = $name;
	}

	/**
	 * Add a string value parameter to the REST service request.
	 *
	 * @param string $value
	 */
	public function addPathParam( string $value ): void
	{
		$this->pathParams[] = urlencode( $value );
	}

	// - - - - - - - - - - - - - - - - - QUERY PARAMS - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Add a query parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addQueryParam( string $name, string $value ): void
	{
		$this->queryParams[ urlencode( $name ) ] = urlencode( $value );
	}

	/**
	 * Add a query parameter to the REST service request that needs to be JSON encoded.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function addQueryParamAsJson( string $name, $value ): void
	{
		$this->queryParams[ urlencode( $name ) ] = json_encode( $value );
	}

	/**
	 * Add a search query parameter to the REST service request.
	 *
	 * @param string $paramName
	 * @param string $searchName
	 * @param string[] $searchValues
	 */
	public function addSearchQueryParam( string $paramName, string $searchName, array $searchValues ): void
	{
		$paramValue = '';
		$operator = '';
		foreach( $searchValues as $searchValue ) {
			$paramValue .= $operator.urlencode( $searchName ).':"'.urlencode( $searchValue ).'"';
			$operator = urlencode( ' OR ' );
		}
		$this->queryParams[ urlencode( $paramName ) ] = $paramValue;
	}

	/**
	 * Add a CSV (comma separated value list) query parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string[] $values
	 */
	public function addCsvQueryParam( string $name, array $values ): void
	{
		$values = array_map( 'strval', $values );
		$values = array_map( 'urlencode', $values );
		$this->queryParams[ urlencode( $name ) ] = implode( ',', $values );
	}

	// - - - - - - - - - - - - - - - - - POST PARAMS - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Add a POST parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addPostParam( string $name, string $value ): void
	{
		$this->postParams[ $name ] = $value;
	}

	/**
	 * Add a POST parameter to the REST service request that needs to be JSON encoded.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function addPostParamAsJson( string $name, $value ): void
	{
		$this->postParams[ $name ] = json_encode( $value );
	}

	/**
	 * Add a search POST parameter to the REST service request.
	 *
	 * @param string $paramName
	 * @param string $searchName
	 * @param string[] $searchValues
	 */
	public function addSearchPostParam( string $paramName, string $searchName, array $searchValues ): void
	{
		$paramValue = '';
		$operator = '';
		foreach( $searchValues as $searchValue ) {
			$paramValue .= $operator.$searchName.':"'.$searchValue.'"';
			$operator = ' OR ';
		}
		$this->postParams[ $paramName ] = $paramValue;
	}

	/**
	 * Add a CSV (comma separated value list) POST parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string[] $values
	 */
	public function addCsvPostParam( string $name, array $values ): void
	{
		$values = array_map( 'strval', $values );
		$this->postParams[ $name ] = implode( ',', $values );
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

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
		if( $this->queryParams ) {
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
	public function setHttpPostMethod(): void
	{
		$this->httpMethod = self::HTTP_METHOD_POST;
	}

	/**
	 * Define the HTTP PUT method for the request.
	 */
	public function setHttpPutMethod(): void
	{
		$this->httpMethod = self::HTTP_METHOD_PUT;
	}

	/**
	 * Define the HTTP DELETE method for the request.
	 */
	public function setHttpDeleteMethod(): void
	{
		$this->httpMethod = self::HTTP_METHOD_DELETE;
	}

	/**
	 * @return array
	 */
	public function getPostParams() : array
	{
		return $this->postParams;
	}

	/**
	 * @return array
	 */
	public function getQueryParams() : array
	{
		return $this->queryParams;
	}

	/**
	 * @return string
	 */
	public function getHttpMethod() : string
	{
		return $this->httpMethod;
	}

	/**
	 * Call this function when a JSON structure is expected in the response.
	 */
	public function setExpectJson(): void
	{
		$this->expectJson = true;
	}

	/**
	 * Tells whether or not a JSON structure is expected in the response.
	 *
	 * @return bool
	 */
	public function getExpectJson(): bool
	{
		return $this->expectJson;
	}

	/**
	 * Call this function when raw data is expected in the response.
	 */
	public function setExpectRawData(): void
	{
		$this->expectRawData = true;
	}

	/**
	 * Tells whether or not raw data is expected in the response.
	 *
	 * @return bool
	 */
	public function getExpectRawData(): bool
	{
		return $this->expectRawData;
	}

	/**
	 * Call this function when a Not Found error is severe.
	 */
	public function setNotFoundErrorAsSevere(): void
	{
		$this->notFoundIsSevere = true;
	}

	/**
	 * Tells whether or not a Not Found error is severe.
	 *
	 * @return bool
	 */
	public function isNotFoundErrorSevere(): bool
	{
		return $this->notFoundIsSevere;
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
	public function getFileToUpload(): ?Attachment
	{
		return $this->fileToUpload;
	}

	/**
	 * Compose a description of the request e.g. for logging purposes.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		$description = $this->composeServicePath();
		if( $this->getSubjectId() ) {
			$description .= ' for id: '.$this->getSubjectId();
		}
		return $description;
	}

	/**
	 * Get all request headers.
	 *
	 * @return array key value pairs of headers.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Set header value.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setHeader( string $name, string $value ): void
	{
		$this->headers[$name] = $value;
	}

	/**
	 * Get header value.
	 *
	 * @param string $name
	 * @return string|null String value when found, or NULL when not found.
	 */
	public function getHeader( string $name ): ?string
	{
		return isset( $this->headers[$name] ) ? $this->headers[$name] : null;
	}

	/**
	 * Return whether or not request has a body.
	 *
	 * @return bool true when body is set, false otherwise.
	 */
	public function hasBody(): bool
	{
		return isset( $this->body );
	}

	/**
	 * Retrieve request body.
	 *
	 * @return null|string body content, null when body isn't set.
	 */
	public function getBody(): ?string
	{
		return $this->body;
	}

	/**
	 * Set request body to use for GET, POST, PUT, etc. requests.
	 *
	 * @param string $body
	 */
	public function setBody( string $body ): void
	{
		$this->body = $body;
	}

	/**
	 * Set JSON request body. Correct request headers will be set too.
	 *
	 * @param mixed $body
	 */
	public function setJsonBody( $body ): void
	{
		$this->setBody( json_encode( $body ) );
		$this->setHeader( 'Content-Type', 'application/json' );
	}
}