<?php
require_once 'AbstractRemoteObject.php';

class ElvisEntUserDetails extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.EntUserDetails';
	}

	/** @var string $username */
	public $username;

	/** @var string $fullName */
	public $fullName;

	/** @var string $email */
	public $email;

	/** @var boolean $enabled */
	public $enabled;

	/** @var boolean $ldapUser */
	public $ldapUser;
}
