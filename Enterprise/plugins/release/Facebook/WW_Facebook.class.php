<?php

require_once dirname(__FILE__) . '/sdk/facebook.php';

/**
 * Class WW_Facebook
 *
 * This is needed for override some functions of the Facebook sdk that are needed for some actions.
 */
class WW_Facebook extends Facebook
{
	/**
	 * Get access token for saving.
	 *
	 * This function is needed to get the access token so it can be stored in a bin file.
	 *
	 * @return bool|mixed
	 */
	public function getUserAccessTokenForSaving()
    {
        return parent::getUserAccessToken();
    }

	/**
	 * Get access token from Facebook redirect code.
	 *
	 * After the login you get a code this needs to be converted to an access-token, there is a function for this in the Facebook sdk.
	 *
	 * @param $code
	 * @param null $redirect_uri
	 * @return mixed
	 */
	public function getAccessTokenFromFbCode($code, $redirect_uri = null)
    {
        return $this->getAccessTokenFromCode($code, $redirect_uri);
    }
}