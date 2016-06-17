<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

class ElvisLoginResponse extends AbstractRemoteObject {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.LoginResponse';
	}
	
	/**
	 * Returns true if connect operation been successful, in this case
	 * loginFaultMessage is empty
	 * 
	 * @var boolean
	 */
	public $loginSuccess;
	
	/**
	 * In case loginSuccess is false, provides information about error, message
	 * localized based on provided user Locale
	 * 
	 * @var string
	 */
	public $loginFaultMessage;
	
	/**
	 * In case loginSuccess is true, sessionId is provided
	 * 
	 *  @var string
	 */
	public $sessionId;
	
}
