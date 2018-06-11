<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'ReadOnlyFieldHandler.class.php';

class UserFieldHandler extends ReadOnlyFieldHandler
{
	/** @var bool Whether or not to replace unknown user in Enterprise with current acting user */
	private $replaceUnknownUserWithActingUser = true;

	public function __construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName )
	{
		parent::__construct( $lvsFieldName, $multiValue, $dataType, $entPropertyName );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once __DIR__.'/../../config.php'; // ELVIS_INTERNAL_USER_POSTFIX

		$propertyName = $this->property->Name;
		$username = $this->getEnterpriseValue( $elvisMetadata ); // short- or full name
		$fullName = null;

		if( !empty( $username ) && !strpos( $username, ELVIS_INTERNAL_USER_POSTFIX ) ) {
			$bizUser = new Elvis_BizClasses_User();
			if( $this->replaceUnknownUserWithActingUser ) {
				$fullName = $bizUser->getFullNameOfUserOrActingUser( $username );
			} else {
				$fullName = $bizUser->getFullNameOfUser( $username );
			}
		}
		if( !$fullName ) {
			$fullName = $username;
		}

		$entMetadata->{$this->entMetadataCategory}->{$propertyName} = $fullName;
	}

	/**
	 * To determine if the unknown user from Elvis should be replaced with current acting user.
	 *
	 * By default, $this->replaceUnknownUserWithActingUser is set to true ( which is replace the
	 * unknown user with current acting user ). To overrule the default, this function can be
	 * used to change the behaviour.
	 *
	 * @since 10.3.1
	 * @param string $handlerName The current handler/context that is calling this class.
	 */
	public function replaceUnknownUserWithActingUser( $handlerName )
	{
		switch( $handlerName ) {
			case 'VersionHandler': // EN-90140
				$this->replaceUnknownUserWithActingUser = false;
				break;
			default:
				$this->replaceUnknownUserWithActingUser = true;
		}
	}
}