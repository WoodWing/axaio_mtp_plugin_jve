<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/NameValidation_EnterpriseConnector.class.php';

class PasswordRuleDemo_NameValidation extends NameValidation_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }

	final public function validatePassword( $pass )
	{
		/*if( strcmp( strtoupper($pass), $pass ) === 0 || 
			strcmp( strtolower($pass), $pass ) === 0 ) {
			throw new BizException( 'PASS_TOKENS', 'Client', '', 'Please use mixed case characters.' );
		}*/
		
		if( strpos( $pass, '/' ) !== false ) {
			throw new BizException( 'PASS_TOKENS', 'Client', '', 'Password Rule Demo plugin: Slashes are not allowed.' );
		}
		
		// Password valid, continue validate with standard rules:
		return true;
	}

	final public function validateMetaDataAndTargets( $user, MetaData &$meta, &$targets )
	{
		/* // Example how to add user prefix to object name
		$prefix = $user.'_';
		if( strpos( $meta->BasicMetaData->Name, $prefix ) !== 0 ) {
			$meta->BasicMetaData->Name = $prefix.$meta->BasicMetaData->Name;
		}
		*/
	}

	public function validateMetaDataInMultiMode( $user, MetaData $invokedMetaData, array &$changedMetaDataValues )
	{
		// Called during a multi-set object properties request
		// $changedMetaDataValues is shared by all objects of this operation, so modifications are applied to all.
		$user = $user;
		$invokedMetaData = $invokedMetaData;
		$changedMetaDataValues = $changedMetaDataValues;
	}
}
