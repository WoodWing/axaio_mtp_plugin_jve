<?php
/**
 * @since      10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class ElvisClientResponse
{
	private $httpStatusCode;
	private $body;

	/**
	 * Response constructor.
	 *
	 * @param integer $httpStatusCode
	 * @param string $body
	 */
	public function __construct( $httpStatusCode, $body )
	{
		$this->httpStatusCode = intval( $httpStatusCode );
		$this->body = $body;
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
	 * @throws ElvisBizException
	 */
	public function jsonBody()
	{
		if( $this->body === false ) {
			throw new ElvisBizException( 'Invalid response body' );
		}
		$decoded = json_decode( $this->body );
		if( !$decoded ) {
			throw new ElvisBizException( 'Invalid response body' );
		}
		return $decoded;
	}
}
