<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_EntUserDetails
{
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
	 * Convert a stdClass object into an Elvis_DataClasses_EntUserDetails object.
	 *
	 * REST responses from Elvis server are JSON decoded and result into stdClass.
	 * This function can be called to convert it to the real data class Elvis_DataClasses_EntUserDetails.
	 *
	 * @since 10.5.0
	 * @param stdClass $stdClassHit
	 * @return Elvis_DataClasses_EntUserDetails
	 */
	public static function fromStdClass( stdClass $stdClassHit ) : Elvis_DataClasses_EntUserDetails
	{
		return WW_Utils_PHPClass::typeCast( $stdClassHit, 'Elvis_DataClasses_EntUserDetails' );
	}
}
