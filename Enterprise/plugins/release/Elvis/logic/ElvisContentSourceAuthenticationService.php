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
	 * @param ElvisLoginRequest $loginRequest
	 * @return ElvisLoginResponse
	 * @throws BizException
	 */
	public function login($loginRequest)
	{
		require_once dirname(__FILE__) . '/../model/ElvisLoginRequest.php';
		require_once dirname(__FILE__) . '/../model/ElvisLoginResponse.php';
		require_once dirname(__FILE__) . '/ElvisAMFClient.php';

		ElvisAMFClient::registerClass(ElvisLoginRequest::getName());
		ElvisAMFClient::registerClass(ElvisLoginResponse::getName());
		$loginResponse = null;
		try {
			$loginResponse = ElvisAMFClient::send( self::SERVICE, 'login', array( $loginRequest ), false );
		} catch( ElvisCSException $e ) {
			throw $e->toBizException();
		}
		return $loginResponse;
	}
	
	/**
	 * Get the version of Elvis running in Content Station. 
	 * This is a non-secured call as Content Station requests this before login.
	 * 
	 * @return string|null Version
	 */
	public function getContentStationClientVersion()
	{
		require_once dirname(__FILE__) . '/ElvisAMFClient.php';
		try {
			return ElvisAMFClient::send(self::SERVICE, 'getContentStationClientVersion', null , false);
		} catch (Exception $e) {
			// This call should not throw exceptions, neccessary for loading the Enterprise
			// access profiles configuration. Downloading the Elvis client will still fail.
			LogHandler::log('ELVIS', 'WARN', "Unable to retrieve content station client version:\n" .
				$e->getMessage());
			return null;
		}
	}
	
}
