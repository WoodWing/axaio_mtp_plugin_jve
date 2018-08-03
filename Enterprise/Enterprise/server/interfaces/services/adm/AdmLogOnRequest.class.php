<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmLogOnRequest
{
	public $AdminUser;
	public $Password;
	public $Ticket;
	public $Server;
	public $ClientName;
	public $Domain;
	public $ClientAppName;
	public $ClientAppVersion;
	public $ClientAppSerial;
	public $ClientAppCode;

	/**
	 * @param string               $AdminUser                 
	 * @param string               $Password                  
	 * @param string               $Ticket                    Nullable.
	 * @param string               $Server                    Nullable.
	 * @param string               $ClientName                
	 * @param string               $Domain                    Nullable.
	 * @param string               $ClientAppName             
	 * @param string               $ClientAppVersion          Nullable.
	 * @param string               $ClientAppSerial           Nullable.
	 * @param string               $ClientAppCode             Nullable.
	 */
	public function __construct( $AdminUser=null, $Password=null, $Ticket=null, $Server=null, $ClientName=null, $Domain=null, $ClientAppName=null, $ClientAppVersion=null, $ClientAppSerial=null, $ClientAppCode=null )
	{
		$this->AdminUser            = $AdminUser;
		$this->Password             = $Password;
		$this->Ticket               = $Ticket;
		$this->Server               = $Server;
		$this->ClientName           = $ClientName;
		$this->Domain               = $Domain;
		$this->ClientAppName        = $ClientAppName;
		$this->ClientAppVersion     = $ClientAppVersion;
		$this->ClientAppSerial      = $ClientAppSerial;
		$this->ClientAppCode        = $ClientAppCode;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'LogOnRequest' );
		if( $validator->checkExist( $datObj, 'AdminUser' ) ) {
			$validator->enterPath( 'AdminUser' );
			$validator->checkNull( $datObj->AdminUser );
			if( !is_null( $datObj->AdminUser ) ) {
				$validator->checkType( $datObj->AdminUser, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Password' ) ) {
			$validator->enterPath( 'Password' );
			$validator->checkNull( $datObj->Password );
			if( !is_null( $datObj->Password ) ) {
				$validator->checkType( $datObj->Password, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Server' ) ) {
			$validator->enterPath( 'Server' );
			if( !is_null( $datObj->Server ) ) {
				$validator->checkType( $datObj->Server, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ClientName' ) ) {
			$validator->enterPath( 'ClientName' );
			$validator->checkNull( $datObj->ClientName );
			if( !is_null( $datObj->ClientName ) ) {
				$validator->checkType( $datObj->ClientName, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Domain' ) ) {
			$validator->enterPath( 'Domain' );
			if( !is_null( $datObj->Domain ) ) {
				$validator->checkType( $datObj->Domain, 'string' );
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
		if( $validator->checkExist( $datObj, 'ClientAppVersion' ) ) {
			$validator->enterPath( 'ClientAppVersion' );
			if( !is_null( $datObj->ClientAppVersion ) ) {
				$validator->checkType( $datObj->ClientAppVersion, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ClientAppSerial' ) ) {
			$validator->enterPath( 'ClientAppSerial' );
			if( !is_null( $datObj->ClientAppSerial ) ) {
				$validator->checkType( $datObj->ClientAppSerial, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ClientAppCode' ) ) {
			$validator->enterPath( 'ClientAppCode' );
			if( !is_null( $datObj->ClientAppCode ) ) {
				$validator->checkType( $datObj->ClientAppCode, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmLogOnRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

