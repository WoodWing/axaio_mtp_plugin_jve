<?php
/****************************************************************************
Copyright 2008-2013 WoodWing Software BV

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 ****************************************************************************/

/**
 * Connector class for Twitter.
 *
 * Demo publishing connector to post messages to Twitter.
 *
 * @package        Enterprise
 * @subpackage     ServerPlugins
 * @since          v7.6
 * @copyright      WoodWing Software bv. All Rights Reserved.
 *
 * This plug-in is provided "as is" without warranty from WoodWing Software.
 */
class EnterpriseTwitterConnector
{
	/**
	 * Get the Zend_Service_Twitter object.
	 *
	 * @param int $issueId The issue ID.
	 *
	 * @return Zend_Service_Twitter $twitter The Zend Twitter object.
	 * @throws BizException Throws an error in case of an error (incl. logon problems)
	 */
	public function getTwitter($issueId)
	{
		$this->extendZendWithMissingModules();
		require_once 'WoodWing/Service/Twitter.php';

		try {
			$client = $this->getHttpClient($issueId);
			$twitter = new WoodWing_Service_Twitter(null, null, $client);
			$response = $twitter->account->verifyCredentials();
		} catch ( Exception $e ) {
			$msg = 'Error accessing Twitter';
			$detail = 'Error Details: ' . $e->getMessage();
			throw new BizException(null, 'Server', $detail, $msg);
		}

		if ( !$response->isSuccess() ) {
			// Check if the response contains an error. We have encountered cases where isSuccess()
			// returns false while the credentials are correct. When this was the case there was no
			// error set. So only throw an exception when an error is set.
			if ( $response->error ) {
				$msg = 'Error accessing Twitter';
				$error = (string)$response->error;
				$detail = 'Error Details: ' . $error;
				throw new BizException(null, 'Server', $detail, $msg);
			}
		}
		return $twitter;
	}

	/**
	 * Returns the Access Token for Twitter.
	 *
	 * Returns the token that gives access to Twitter. This token was retrieved from Twitter before
	 * during interactive the authorization session. The token is saved int the _SYSTEM_ folder at the filestore
	 * in a file named twitter_token.bin.
	 * See redirectToRetrieveAccessToken() and retrieveAccessTokenFromRedirection() to get new token from Twitter.
	 *
	 * @param int $issueId The IssueId for which to get the Access Token.
	 *
	 * @return Zend_Oauth_Token_Access The Zend Oauth authorization token.
	 */
	public function getAccessToken($issueId)
	{
		require_once 'Zend/Oauth/Token/Access.php'; // Needed to serialize this class below.
		$tokenFile = $this->getAccessTokenFilePath($issueId);
		$token = null;
		if ( file_exists($tokenFile) ) {
			$token = unserialize(file_get_contents($tokenFile));
		}

		return $token;
	}

	/**
	 * Returns the username of the Twitter account registered for a given Enterprise issue.
	 *
	 * @param int $issueId The IssueId for which to retrieve the Twitter user name.
	 *
	 * @return string $userName The user name for the registered Twitter acocunt.
	 */
	public function getUserName($issueId)
	{
		$token = $this->getAccessToken($issueId);
		$userName = '';
		if ( $token ) {
			$userName = $token->getParam('screen_name');
		}
		return $userName;
	}

	/**
	 * Creates and returns new consumer.
	 *
	 * Creates a new Zend_Oauth_Consumer object for the consumer credentials registered in the configuration.
	 *
	 * @param int $issueId
	 * @return Zend_Oauth_Consumer The newly created Zend_Oauth_Consumer
	 */
	private function getOauthConsumer( $issueId )
	{
		require_once 'Zend/Oauth/Consumer.php';
		return new Zend_Oauth_Consumer($this->getConfig( $issueId ));
	}

	/**
	 * Returns the configuration options used to authorize the "WoodWing Enterprise Publishing Connector".
	 *
	 * @param int $issueId
	 * @return array Returns an array containing the configuration.
	 */
	private function getConfig( $issueId )
	{
		require_once BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
		$issueObj = DBAdmIssue::getIssueObj( $issueId );

		require_once BASEDIR . '/server/bizclasses/BizAdmProperty.class.php';
		$consumerKey = BizAdmProperty::getCustomPropVal( $issueObj->ExtraMetaData, 'C_TPF_CHANNEL_CONSUMER_KEY' );
		$consumerSecret = BizAdmProperty::getCustomPropVal( $issueObj->ExtraMetaData, 'C_TPF_CHANNEL_CONSUMER_SECRET' );

		return array(
			'callbackUrl' => SERVERURL_ROOT . INETROOT . '/config/plugins/Twitter/callback.php',
			'requestTokenUrl' => 'https://api.twitter.com/oauth/request_token',
			'authorizeUrl' => 'https://api.twitter.com/oauth/authorize',
			'accessTokenUrl' => 'https://api.twitter.com/oauth/access_token',

			// Keys obtained from Twitter product registration created for "WoodWing Enterprise Publishing Connector"
			'consumerKey' => $consumerKey,
			'consumerSecret' => $consumerSecret
		);
	}

	/**
	 * Get OAuth client.
	 *
	 * @param int $issueId The IssueId for which to get the client.
	 *
	 * @return Zend_Oauth_Client The client object.
	 */
	private function getHttpClient($issueId)
	{
		$token = $this->getAccessToken($issueId);
		if ( $token ) {
			$client = $token->getHttpClient($this->getConfig($issueId));
		} else {
			$client = null;
		}
		return $client;
	}

	/**
	 * Redirects the request to retrieve an access token.
	 *
	 * Creates new Request Token and redirects(!) current web page to Twitter authorization page to
	 * get the Access Token. Before doing so, the old token is removed from disk and old session
	 * data is cleared. Only the Request Token is saved in session data since that should live only
	 * while admin user is setting up authorization. The Access Token is saved on disk (not using
	 * sesson data) since that token needs to live for long time (for all tweets at get published)
	 * and it needs to get picked up for all users working with Enterprise.
	 *
	 * @param int $issueId The IssueId for which to retrieve the access token.
	 */
	public function redirectToRetrieveAccessToken($issueId)
	{
		// Remove the old token and session data
		$tokenFile = $this->getAccessTokenFilePath($issueId);

		if ( file_exists($tokenFile) ) {
			unlink($tokenFile);
		}

		// Store data in the session.
		$vars = array();
		$vars['TWITTER_REQUEST_TOKEN'] = null;
		$vars['TWITTER_ISSUE_ID'] = null;
		BizSession::setSessionVariables( $vars );

		// Get new Request Token (in preparation to get the Access Token).
		$consumer = $this->getOauthConsumer($issueId);
		$reqToken = $consumer->getRequestToken();

		$vars['TWITTER_REQUEST_TOKEN'] = serialize($reqToken);
		$vars['TWITTER_ISSUE_ID'] = $issueId;
		BizSession::setSessionVariables( $vars );

		// Redirect user to Twitter site so they can log in and approve our access
		$consumer->redirect();
	}

	/**
	 * Retrieves the Access Token after redirection.
	 *
	 * Someone's knocking at the door using the Callback URL - if they have some GET data.
	 * This happens when Twitter has approved OAuth access to user account.
	 * So here exchange our current Request Token for a newly authorised Access Token.
	 * The new Access Token is saved on disk (to authorize published tweets later).
	 *
	 * @param array $reqData The request data (such as $_GET) passed onto callback.php
	 *
	 * @return void.
	 */
	public function retrieveAccessTokenFromRedirection($reqData)
	{
		$vars = BizSession::getSessionVariables();
		$reqToken = $vars['TWITTER_REQUEST_TOKEN'];
		$issueId = $vars['TWITTER_ISSUE_ID'];

		if ( !empty($reqData) && $reqToken ) {
			$consumer = $this->getOauthConsumer($issueId);
			$accToken = $consumer->getAccessToken($reqData, unserialize($reqToken));
			if ( $accToken ) {
				$tokenFile = $this->getAccessTokenFilePath($issueId);
				file_put_contents($tokenFile, serialize($accToken));
			}
			$vars['TWITTER_REQUEST_TOKEN'] = null;
			$vars['TWITTER_ISSUE_ID'] = null;
			BizSession::setSessionVariables( $vars );
		}
	}

	/**
	 * Deletes an access token by issue id.
	 *
	 * Deletes a token file based on the issue id, if the file exists.
	 *
	 * @param int $issueId The Issue id for which to delete the token file.
	 *
	 * @return void.
	 */
	public function releaseAccessToken($issueId)
	{
		$tokenFile = $this->getAccessTokenFilePath($issueId);
		if ( file_exists($tokenFile) ) {
			unlink($tokenFile);
		}
	}

	/**
	 * Retrieves the access token file path.
	 *
	 * @param int $issueId The Issue id for which to retrieve the access token file path.
	 *
	 * @return string The access token file path.
	 */
	private function getAccessTokenFilePath($issueId)
	{
		return WOODWINGSYSTEMDIRECTORY . '/twitter_token_' . $issueId . '.bin';
	}

    /**
	 * Extend Zend Framework with missing modules.
	 *
	 * Enterprise v9 ships with an older version of Zend Framework that does not support the Twitter 1.1 API
     *
	 * @return void.
	 */
	private function extendZendWithMissingModules()
	{
		require_once 'Zend/Version.php';
        
        static $included = false;

		if ( !$included ) {
            LogHandler::Log('Twitter', 'DEBUG', 'Zend_Version ' . Zend_Version::VERSION);
            
            //Check if we have to use the twitter library in the connector
            if (Zend_Version::compareVersion('1.12.3') == 1) {
                LogHandler::Log('Twitter', 'DEBUG', 'Use twitter_lib of plugin');
                $orgPath = ini_get('include_path');
                ini_set('include_path', dirname(__FILE__) . '/ZendFramework/twitter_lib' . PATH_SEPARATOR . $orgPath);           
            }

			$included = true;
		}
	}

}