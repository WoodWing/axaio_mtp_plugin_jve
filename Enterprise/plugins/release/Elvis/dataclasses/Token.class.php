<?php
/**
 * Data class used to store Elvis access tokens for an Enterprise user in the Enterprise DB.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_Token
{
	/** @var string|null Short name of the acting Enterprise user. */
	public $enterpriseUser = null;

	/** @var string|null Elvis user name used to authenticate the Enterprise connection with Elvis. Could be either the acting user or the configured fallback user (ELVIS_SUPER_USER). */
	public $elvisUser = null;

	/** @var string|null Elvis OAuth access token. */
	public $accessToken = null;
}