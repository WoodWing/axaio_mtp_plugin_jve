<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'AbstractRemoteObject.php';

class ElvisLoginResponse extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.LoginResponse';
	}

	/**
	 * Returns true if connect operation been successful, in this case
	 * loginFaultMessage is empty
	 *
	 * @var boolean $loginSuccess
	 */
	public $loginSuccess;

	/**
	 * In case loginSuccess is false, provides information about error, message
	 * localized based on provided user Locale
	 *
	 * @var string $loginFaultMessage
	 */
	public $loginFaultMessage;

	/**
	 * In case loginSuccess is true, sessionId is provided
	 *
	 * @var string $sessionId
	 */
	public $sessionId;
}
