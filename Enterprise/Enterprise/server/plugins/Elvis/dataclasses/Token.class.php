<?php
/**
 * Data class used to store Elvis access tokens for an Enterprise user in the Enterprise DB.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_Token
{
	/** @var string Short name of the acting Enterprise user. */
	public $enterpriseUser = '';

	/** @var string Elvis user name used to authenticate the Enterprise connection with Elvis. Could be either the acting user or the configured fallback user (ELVIS_DEFAULT_USER). */
	public $elvisUser = '';

	/** @var string Elvis OAuth access token. */
	public $accessToken = '';

	/**
	 * Constructor.
	 *
	 * @param string $enterpriseUser
	 * @param string $elvisUser
	 * @param string $accessToken
	 */
	public function __construct( string $enterpriseUser, string $elvisUser, string $accessToken )
	{
		$this->enterpriseUser = $enterpriseUser;
		$this->elvisUser = $elvisUser;
		$this->accessToken = $accessToken;
	}
}