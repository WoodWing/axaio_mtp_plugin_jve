<?php
/**
 * Interface used by the WoodWing Content Source plugin to connect to the Elvis
 * server.
 * 
 * Authentication has this separate interface so it can have 'secured=false'.
 */
class ElvisContentSourceAuthenticationService
{
	
	const SERVICE = 'contentSourceAuthenticationService';
	
	/**
	 * Connect to the Elvis server
	 *  
	 * @param string $credentials
	 * @throws BizException on connection error or authentication error
	 */
	public function login( $credentials )
	{
		require_once __DIR__.'/../model/ElvisLoginRequest.php';
		require_once __DIR__.'/../model/ElvisLoginResponse.php';
		require_once __DIR__.'/ElvisAMFClient.php';
		require_once __DIR__.'/../util/ElvisSessionUtil.php';

		// TODO: Find out where to get the client locale
		// TODO: Find out where to get the correct timezone offset
		$loginRequest = new ElvisLoginRequest( $credentials, 'en_US', 0 );
		$loginRequest->clientId = ElvisSessionUtil::getClientId();

		ElvisAMFClient::registerClass( ElvisLoginRequest::getName() );
		ElvisAMFClient::registerClass( ElvisLoginResponse::getName() );
		$loginResponse = null;
		try {
			$loginResponse = ElvisAMFClient::send( self::SERVICE, 'login', array( $loginRequest ) );
			if( !$loginResponse->loginSuccess ) {
				$message = 'Login to Elvis failed: '.$loginResponse->loginFaultMessage;
				throw new BizException( null, 'Server', null, $message, null, 'INFO' );
			}
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
	}
	
	/**
	 * Get the version of Elvis running in Content Station. 
	 * This is a non-secured call as Content Station requests this before login.
	 * 
	 * @return string|null Version
	 */
	public function getContentStationClientVersion()
	{
		require_once __DIR__.'/ElvisAMFClient.php';
		try {
			return ElvisAMFClient::send(self::SERVICE, 'getContentStationClientVersion', null );
		} catch (Exception $e) {
			// This call should not throw exceptions, necessary for loading the Enterprise
			// access profiles configuration. Downloading the Elvis client will still fail.
			LogHandler::log('ELVIS', 'WARN', "Unable to retrieve content station client version:\n" .
				$e->getMessage());
			return null;
		}
	}
	
}
