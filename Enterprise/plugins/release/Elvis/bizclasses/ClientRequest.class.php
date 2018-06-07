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
	private $queryParams = array();

	/** @var array */
	private $postParams = array();

	/** @var string|null */
	private $userShortName = null;

	/** @var bool */
	private $expectJson = false;

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

	/** @var bool */
	private $notFoundIsSevere = false;

	/**
	 * Constructor.
	 *
	 * @param string $relativeRestServicePath
	 */
	public function __construct( string $relativeRestServicePath )
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
		$this->userShortName = ELVIS_SUPER_USER; // TODO: remove this hack once we can get rid of fallback user
	}

	/**
	 * Retrieve the user name for which this request should be authorized.
	 *
	 * @return string|null
	 */
	public function getUserShortName()
	{
		return $this->userShortName;
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
	public function getAttempt()
	{
		return $this->attempt;
	}

	/**
	 * Indicate the subject entity of this request.
	 */
	public function getSubjectEntity()
	{
		return $this->subjectEntity;
	}

	/**
	 * Retrieve the subject entity of this request.
	 *
	 * @param string $entity
	 */
	public function setSubjectEntity( string $entity )
	{
		$this->subjectEntity = $entity;
	}

	/**
	 * Indicate the subject id of this request.
	 */
	public function getSubjectId()
	{
		return $this->subjectId;
	}

	/**
	 * Retrieve the subject id of this request.
	 *
	 * @param string $id
	 */
	public function setSubjectId( string $id )
	{
		$this->subjectId = $id;
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

	// - - - - - - - - - - - - - - - - - QUERY PARAMS - - - - - - - - - - - - - - - - - - - - - - - - - - -

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
		$this->queryParams[ urlencode( $name ) ] = json_encode( $value );
	}

	/**
	 * Add a search query parameter to the REST service request.
	 *
	 * @param string $paramName
	 * @param string $searchName
	 * @param string[] $searchValues
	 */
	public function addSearchQueryParam( string $paramName, string $searchName, array $searchValues )
	{
		$encSearchValue = '';
		$operator = '';
		foreach( $searchValues as $searchValue ) {
			$encSearchValue .= $operator.urlencode( $searchName ).':"'.urlencode( $searchValue ).'"';
			$operator = urlencode( ' OR ' );
		}
		$this->queryParams[ urlencode( $paramName ) ] = $encSearchValue;
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
		$this->queryParams[ urlencode( $name ) ] = implode( ',', $values );
	}

	// - - - - - - - - - - - - - - - - - POST PARAMS - - - - - - - - - - - - - - - - - - - - - - - - - - -

	/**
	 * Add a POST parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addPostParam( string $name, string $value )
	{
		$this->postParams[ urlencode( $name ) ] = urlencode( $value );
	}

	/**
	 * Add a POST parameter to the REST service request that needs to be JSON encoded.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function addPostParamAsJson( string $name, $value )
	{
		$this->postParams[ urlencode( $name ) ] = json_encode( $value );
	}

	/**
	 * Add a search POST parameter to the REST service request.
	 *
	 * @param string $paramName
	 * @param string $searchName
	 * @param string[] $searchValues
	 */
	public function addSearchPostParam( string $paramName, string $searchName, array $searchValues )
	{
		$encSearchValue = '';
		$operator = '';
		foreach( $searchValues as $searchValue ) {
			$encSearchValue .= $operator.urlencode( $searchName ).':"'.urlencode( $searchValue ).'"';
			$operator = ' OR ';
		}
		$this->postParams[ urlencode( $paramName ) ] = $encSearchValue;
	}

	/**
	 * Add a CSV (comma separated value list) POST parameter to the REST service request.
	 *
	 * @param string $name
	 * @param string[] $values
	 */
	public function addCsvPostParam( string $name, array $values )
	{
		$values = array_map( 'strval', $values );
		$values = array_map( 'urlencode', $values );
		$this->postParams[ urlencode( $name ) ] = implode( ',', $values );
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
	public function setExpectJson()
	{
		$this->expectJson = true;
	}

	/**
	 * Tells whether or not a JSON structure is expected in the response.
	 *
	 * @return bool
	 */
	public function getExpectJson() : bool
	{
		return $this->expectJson;
	}

	/**
	 * Call this function when a Not Found error is severe.
	 */
	public function setNotFoundErrorAsSevere()
	{
		$this->notFoundIsSevere = true;
	}

	/**
	 * Tells whether or not a Not Found error is severe.
	 *
	 * @return bool
	 */
	public function isNotFoundErrorSevere() : bool
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
	public function getFileToUpload()
	{
		return $this->fileToUpload;
	}
}