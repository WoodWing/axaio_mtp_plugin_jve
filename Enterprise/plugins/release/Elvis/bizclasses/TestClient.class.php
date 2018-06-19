<?php
/**
 * Client class providing Elvis login/logout web services FOR TESTING PURPOSES ONLY. It talks with Elvis server over the REST API.
 *
 * @since 10.5.0
 * @copyright  WoodWing Software bv. All Rights Reserved.*
 */

class Elvis_BizClasses_TestClient extends Elvis_BizClasses_Client
{
	/** @var string */
	private $authToken;

	/**
	 * Logon the user to Elvis.
	 *
	 * @param string $userPassword
	 * @return bool
	 * @throws BizException
	 */
	public function login( string $userPassword )
	{
		if( is_null( $this->shortUserName ) ) {
			$detail = 'No shortUserName set while calling '.__METHOD__.'().';
			throw new BizException( 'ERR_ARGUMENT', 'Server', $detail );
		}

		$request = Elvis_BizClasses_ClientRequest::newUnauthorizedRequest(
			'services/apilogin' );
		$request->setHttpPostMethod();
		$request->setSubjectEntity( BizResources::localize( 'USR_USER' ) );
		$request->setSubjectName( $this->shortUserName );
		$request->addPostParam( 'username', $this->shortUserName );
		$request->addPostParam( 'password', $userPassword );
		$request->setExpectJson();

		$response = $this->execute( $request );
		$json = $response->jsonBody();
		if( $json->loginSuccess == true ) {
			$this->authToken = $json->authToken;
		}
		return $json->loginSuccess;
	}

	/**
	 * Logoff the user from Elvis.
	 *
	 * @return bool
	 */
	public function logout()
	{
		$request = Elvis_BizClasses_ClientRequest::newAuthorizedRequest(
			'services/logout', $this->shortUserName );
		$request->setHttpPostMethod();
		$request->setSubjectEntity( BizResources::localize( 'USR_USER' ) );
		$request->setSubjectName( $this->shortUserName );
		$request->addPostParam( 'username', $this->shortUserName );
		$request->setExpectJson();

		$response = $this->execute( $request );
		$json = $response->jsonBody();
		return $json->logoutSuccess;
	}

	/**
	 * @inheritdoc
	 */
	protected function execute( Elvis_BizClasses_ClientRequest $request ) : Elvis_BizClasses_ClientResponse
	{
		if( $this->authToken && is_null( $request->getHeader( 'Authorization' ) ) ) {
			$request->setHeader( 'Authorization', 'Bearer '.$this->authToken );
		}
		return parent::execute( $request );
	}
}