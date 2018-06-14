<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Class to contain Elvis response data and provide usefull methods on it.
 */
class Elvis_BizClasses_ClientResponse
{
	/** @var int */
	private $httpStatusCode;

	/** @var string */
	private $errorMessage;

	/** @var bool|string */
	private $body;

	/**
	 * Response constructor.
	 *
	 * @param integer $httpStatusCode
	 * @param bool|string $body
	 * @param bool $expectJson
	 */
	public function __construct( $httpStatusCode, $body, $expectJson )
	{
		$this->errorMessage = '';
		$this->httpStatusCode = intval( $httpStatusCode );
		$this->body = $body;
		if( $expectJson && $this->body && $this->httpStatusCode >= 200 && $this->httpStatusCode < 300 ) {
			$decoded = json_decode( $this->body );
			if( $decoded && isset( $decoded->errorcode ) ) {
				$this->httpStatusCode = intval( $decoded->errorcode );
				$this->errorMessage = $decoded->message;
			}
		} else {
			$response = new Zend\Http\Response();
			$response->setStatusCode( $this->httpStatusCode );
			$this->errorMessage = $response->getReasonPhrase();
		}
	}

	/**
	 * Whether or not the response is a client (4xx) or server (5xx) error.
	 *
	 * @return bool
	 */
	public function isError()
	{
		return $this->httpStatusCode >= 400;
	}

	/**
	 * Whether or not the response is an Authentication error (HTTP 401).
	 *
	 * @return bool
	 */
	public function isAuthenticationError()
	{
		return $this->httpStatusCode === 401;
	}

	/**
	 * Whether or not the response is an Forbidden error (HTTP 403).
	 *
	 * @return bool
	 */
	public function isForbiddenError()
	{
		return $this->httpStatusCode === 403;
	}

	/**
	 * Whether or not the response is an Not Found error (HTTP 404).
	 *
	 * @return bool
	 */
	public function isNotFoundError()
	{
		return $this->httpStatusCode === 404;
	}

	/**
	 * Whether or not the response is an Request Timeout error (HTTP 408).
	 *
	 * @return bool
	 */
	public function isRequestTimeoutError()
	{
		return $this->httpStatusCode === 408;
	}

	/**
	 * Whether or not the response is an Conflict error (HTTP 409).
	 *
	 * @return bool
	 */
	public function isConflictError()
	{
		return $this->httpStatusCode === 409;
	}

	/**
	 * Whether or not the response is a Gone error (HTTP 410).
	 *
	 * @return bool
	 */
	public function isGoneError()
	{
		return $this->httpStatusCode === 410;
	}

	/**
	 * Whether or not the response is caused by client but not any of the 4xx errors listed above.
	 *
	 * @return bool
	 */
	public function isClientProgrammaticError()
	{
		$excludedErrorCodes = array( 401, 403, 404, 408, 409, 410 );
		return $this->httpStatusCode >= 400 && $this->httpStatusCode < 500 && !in_array( $this->httpStatusCode, $excludedErrorCodes );
	}

	/**
	 * String representation of the response body.
	 *
	 * @return string string representation of the response body.
	 */
	public function body()
	{
		return is_bool( $this->body ) ? '' : $this->body;
	}

	/**
	 * JSON decoded representation of the response body.
	 *
	 * @return mixed JSON decoded representation of the response body.
	 * @throws Elvis_BizClasses_Exception
	 */
	public function jsonBody()
	{
		if( $this->body === false ) {
			throw new Elvis_BizClasses_Exception( 'Invalid response body' );
		}
		$decoded = json_decode( $this->body );
		if( is_null( $decoded ) ) {
			throw new Elvis_BizClasses_Exception( 'Invalid response body' );
		}
		return $decoded;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->errorMessage;
	}
}
