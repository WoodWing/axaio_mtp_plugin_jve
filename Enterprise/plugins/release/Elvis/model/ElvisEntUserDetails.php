<?php
require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

class ElvisEntUserDetails extends AbstractRemoteObject {

	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.EntUserDetails';
	}

	/**
	 * @var string
	 */
	public $username;
	
	/**
	 * @var string
	 */
	public $fullName;
	
	/**
	 * @var string
	 */
	public $email;
	
	/**
	 * @var boolean
	 */
	public $enabled;
	
	/**
	 * @var boolean
	 */
	public $ldapUser;
}
