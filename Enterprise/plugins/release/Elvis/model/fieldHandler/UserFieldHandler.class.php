<?php

require_once 'ReadOnlyFieldHandler.class.php';

class UserFieldHandler extends ReadOnlyFieldHandler
{
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
		$username = $this->getEnterpriseValue( $elvisMetadata );
		$fullName = $username;

		if( !empty( $username ) && !strpos( $username, ELVIS_INTERNAL_USER_POSTFIX ) ) {
			require_once dirname( __FILE__ ).'/../../util/ElvisUserUtils.class.php';

			$user = ElvisUserUtils::getUserByUsernameOrActingUser( $username );
			if( isset( $user ) && isset( $user->FullName ) ) {
				$fullName = $user->FullName;
			}
		}

		$entMetadata->{$this->entMetadataCategory}->{$propertyName} = $fullName;
	}
}