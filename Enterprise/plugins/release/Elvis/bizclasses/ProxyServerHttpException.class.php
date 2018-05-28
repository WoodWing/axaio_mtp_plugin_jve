<?php

/**
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Exception for the Elvis_BizClasses_ProxyServer class.
 *
 * When Enterprise Server throws a BizException, this class can be used to compose an HTTP error from it.
 */
class Elvis_BizClasses_ProxyServerHttpException extends Exception
{
	/** @var Zend\Http\Response */
	private $response;

	/**
	 * @inheritdoc
	 */
	public function __construct( $message = "", $code = 0, Exception $previous = null )
	{
		$this->response = new Zend\Http\Response();
		$this->response->setStatusCode( $code );
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Return the severity that can be used with the LogHandler.
	 *
	 * @return string 'ERROR' when server error returned, else 'INFO'.
	 */
	public function getSeverity()
	{
		return $this->response->isServerError() ? 'ERROR' : 'INFO';
	}

	/**
	 * Get HTTP status message
	 *
	 * @return string
	 */
	public function getReasonPhrase()
	{
		$this->response->getReasonPhrase();
	}

	/**
	 * Retrieve HTTP status code.
	 *
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->response->getStatusCode();
	}

	/**
	 * Compose a HTTP status message, which consists of the HTTP status code and the message phrase.
	 *
	 * @return string
	 */
	public function getStatusMessage()
	{
		$statusMessage = $this->getStatusCode().' '.$this->getReasonPhrase();
		$message = $this->getMessage();
		if( $message ) { // if there are more lines, take first one only this only one can be sent through HTTP
			if( strpos( $message, "\n" ) !== false ) {
				$msgLines = explode( "\n", $message );
				$message = reset($msgLines);
			}
			// Add message to status; for apps that can not reach message body (like Flex)
			$statusMessage .= " - {$message}";
		}
		return $statusMessage;
	}

	/**
	 * Composes a new HTTP exception from a given BizException.
	 *
	 * @param BizException $e
	 * @return Elvis_BizClasses_ProxyServerHttpException
	 */
	static public function createFromBizException( BizException $e )
	{
		$message = $e->getMessage().' '.$e->getDetail();
		$errorMap = array(
			'S1002' => 403, // ERR_AUTHORIZATION
			'S1029' => 404, // ERR_NOTFOUND
			'S1036' => 404, // ERR_NO_SUBJECTS_FOUND
			'S1080' => 404, // ERR_NO_CONTENTSOURCE
			'S1043' => 401, // ERR_TICKET
		);
		$sCode = $e->getErrorCode();
		$code = array_key_exists( $sCode, $errorMap ) ? $errorMap[$sCode] : 500;
		return new Elvis_BizClasses_ProxyServerHttpException( $message, $code );
	}
}
