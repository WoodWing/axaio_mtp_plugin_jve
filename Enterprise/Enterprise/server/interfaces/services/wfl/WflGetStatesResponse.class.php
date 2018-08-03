<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflGetStatesResponse
{
	public $States;
	public $RouteToUsers;
	public $RouteToGroups;

	/**
	 * @param State[]              $States                    
	 * @param User[]               $RouteToUsers              
	 * @param UserGroup[]          $RouteToGroups             
	 */
	public function __construct( $States=null, $RouteToUsers=null, $RouteToGroups=null )
	{
		$this->States               = $States;
		$this->RouteToUsers         = $RouteToUsers;
		$this->RouteToGroups        = $RouteToGroups;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetStatesResponse' );
		if( $validator->checkExist( $datObj, 'States' ) ) {
			$validator->enterPath( 'States' );
			$validator->checkNull( $datObj->States );
			if( !is_null( $datObj->States ) ) {
				$validator->checkType( $datObj->States, 'array' );
				if( !empty($datObj->States) ) foreach( $datObj->States as $listItem ) {
					$validator->enterPath( 'State' );
					$validator->checkType( $listItem, 'State' );
					WflStateValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RouteToUsers' ) ) {
			$validator->enterPath( 'RouteToUsers' );
			$validator->checkNull( $datObj->RouteToUsers );
			if( !is_null( $datObj->RouteToUsers ) ) {
				$validator->checkType( $datObj->RouteToUsers, 'array' );
				if( !empty($datObj->RouteToUsers) ) foreach( $datObj->RouteToUsers as $listItem ) {
					$validator->enterPath( 'User' );
					$validator->checkType( $listItem, 'User' );
					WflUserValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RouteToGroups' ) ) {
			$validator->enterPath( 'RouteToGroups' );
			$validator->checkNull( $datObj->RouteToGroups );
			if( !is_null( $datObj->RouteToGroups ) ) {
				$validator->checkType( $datObj->RouteToGroups, 'array' );
				if( !empty($datObj->RouteToGroups) ) foreach( $datObj->RouteToGroups as $listItem ) {
					$validator->enterPath( 'UserGroup' );
					$validator->checkType( $listItem, 'UserGroup' );
					WflUserGroupValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflGetStatesResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

