<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS FILE IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class SysSubApplicationValidator
{
	static public function validate( $validator, $datObj )
	{
		if( $validator->checkExist( $datObj, 'ID' ) ) {
			$validator->enterPath( 'ID' );
			$validator->checkNull( $datObj->ID );
			if( !is_null( $datObj->ID ) ) {
				$validator->checkType( $datObj->ID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Version' ) ) {
			$validator->enterPath( 'Version' );
			$validator->checkNull( $datObj->Version );
			if( !is_null( $datObj->Version ) ) {
				$validator->checkType( $datObj->Version, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PackageUrl' ) ) {
			$validator->enterPath( 'PackageUrl' );
			$validator->checkNull( $datObj->PackageUrl );
			if( !is_null( $datObj->PackageUrl ) ) {
				$validator->checkType( $datObj->PackageUrl, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DisplayName' ) ) {
			$validator->enterPath( 'DisplayName' );
			$validator->checkNull( $datObj->DisplayName );
			if( !is_null( $datObj->DisplayName ) ) {
				$validator->checkType( $datObj->DisplayName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ClientAppName' ) ) {
			$validator->enterPath( 'ClientAppName' );
			$validator->checkNull( $datObj->ClientAppName );
			if( !is_null( $datObj->ClientAppName ) ) {
				$validator->checkType( $datObj->ClientAppName, 'string' );
			}
			$validator->leavePath();
		}
	}
}

