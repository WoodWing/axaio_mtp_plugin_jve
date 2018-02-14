<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PubGetDossierURLRequest
{
	public $Ticket;
	public $DossierID;
	public $Target;

	/**
	 * @param string               $Ticket                    
	 * @param string               $DossierID                 
	 * @param PubPublishTarget     $Target                    
	 */
	public function __construct( $Ticket=null, $DossierID=null, $Target=null )
	{
		$this->Ticket               = $Ticket;
		$this->DossierID            = $DossierID;
		$this->Target               = $Target;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetDossierURLRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'DossierID' ) ) {
			$validator->enterPath( 'DossierID' );
			$validator->checkNull( $datObj->DossierID );
			if( !is_null( $datObj->DossierID ) ) {
				$validator->checkType( $datObj->DossierID, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'Target' ) ) {
			$validator->enterPath( 'Target' );
			$validator->checkNull( $datObj->Target );
			if( !is_null( $datObj->Target ) ) {
				$validator->checkType( $datObj->Target, 'PubPublishTarget' );
				PubPublishTargetValidator::validate( $validator, $datObj->Target );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubGetDossierURLRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if( is_object( $this->Target ) ) {
			$this->Target->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

