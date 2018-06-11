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

	/**
	 * Convert a stdClass object into an ElvisEntUserDetails object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class ElvisEntUserDetails.
	 *
	 * @since 10.5.0
	 * @param stdClass $stdClassHit
	 * @return ElvisEntUserDetails
	 */
	public static function fromStdClass( stdClass $stdClassHit ) : ElvisEntUserDetails
	{
		return WW_Utils_PHPClass::typeCast( $stdClassHit, 'ElvisEntUserDetails' );
	}
}
