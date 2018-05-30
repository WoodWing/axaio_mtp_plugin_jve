<?php
/**
 * REST request composer for the Elvis_BizClasses_Client class.
 *
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.*
 */

class Elvis_BizClasses_ClientRequest
{
	/** @var array */
	private $pathParams;

	/** @var array */
	private $queryParams;

	/** @var string */
	private $userShortName;

	/** @var bool */
	private $expectJson = false;

	/**
	 * Constructor.
	 *
	 * @param string $relativeRestServicePath
	 */
	public function __construct( $relativeRestServicePath )
	{
		$relativeRestServicePath = trim( $relativeRestServicePath, '/' );
		foreach( explode( '/', $relativeRestServicePath ) as $pathParam ) {
			$this->addPathParam($pathParam );
		}
	}

	/**
	 * Define the user name for which this request should be authorized.
	 *
	 * @param $name
	 */
	public function setUserShortName( string $name )
	{
		$this->userShortName = $name;
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
	 * Add a search query parameter to the REST service request.
	 *
	 * @param string $paramName
	 * @param string $searchName
	 * @param string $searchValue
	 */
	public function addSearchQueryParam( string $paramName, string $searchName, string $searchValue )
	{
		$this->queryParams[ urlencode( $paramName ) ] = $searchName.':"'.urlencode( $searchValue ).'"';
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
}