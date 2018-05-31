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
		$this->httpStatusCode = intval( $httpStatusCode );
		$this->body = $body;
		if( $expectJson && $this->body ) {
			$decoded = json_decode( $this->body );
			if( $decoded && isset( $decoded->errorcode ) ) {
				$this->httpStatusCode = intval( $decoded->errorcode );
			}
		}
	}

	/**
	 * Whether the response is an error response or not.
	 *
	 * @return bool true when the response is an error response otherwise false.
	 */
	public function isError()
	{
		return $this->httpStatusCode >= 400;
	}

	/**
	 * Whether the response is an authentication error response or not.
	 *
	 * @return bool true when the response is an authentication error response otherwise false.
	 */
	public function isAuthenticationError()
	{
		return $this->httpStatusCode === 401;
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
		if( !$decoded ) {
			throw new Elvis_BizClasses_Exception( 'Invalid response body' );
		}
		return $decoded;
	}
}
