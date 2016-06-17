<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class SysGetSubApplicationsRequest
{
	public $Ticket;
	public $ClientAppName;

	/**
	 * @param string               $Ticket                    
	 * @param string               $ClientAppName             Nullable.
	 */
	public function __construct( $Ticket=null, $ClientAppName=null )
	{
		$this->Ticket               = $Ticket;
		$this->ClientAppName        = $ClientAppName;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/sys/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetSubApplicationsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'ClientAppName' ) ) {
			$validator->enterPath( 'ClientAppName' );
			if( !is_null( $datObj->ClientAppName ) ) {
				$validator->checkType( $datObj->ClientAppName, 'string' );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.sys.SysGetSubApplicationsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
	}

	public function mightHaveContent() { return false; }
}

