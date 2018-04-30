<?php

class ElvisClientResponse
{
	private $httpStatusCode;
	private $body;

	/**
	 * Response constructor.
	 *
	 * @param string $httpStatusCode
	 * @param string $body
	 */
	public function __construct( $httpStatusCode, $body )
	{
		$this->httpStatusCode = intval( $httpStatusCode );
		$this->body = $body;
	}

	public function isError()
	{
		return $this->httpStatusCode < 200 || $this->httpStatusCode >= 300;
	}

	public function isAuthenticationError()
	{
		return $this->httpStatusCode === 401;
	}

	public function body()
	{
		if( $this->body === false ) return '';
		if( $this->body === true ) return '';
		return $this->body;
	}

	/**
	 * @return mixed
	 * @throws ElvisBizException
	 */
	public function jsonBody()
	{
		if( $this->body === false ) {
			throw new ElvisBizException( 'Invalid response body' );
		}
		return json_decode( $this->body );
	}
}
