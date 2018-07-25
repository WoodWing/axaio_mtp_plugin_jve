<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeletePublicationsRequest
{
	public $Ticket;
	public $PublicationIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer[]            $PublicationIds            
	 */
	public function __construct( $Ticket=null, $PublicationIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationIds       = $PublicationIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'DeletePublicationsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublicationIds' ) ) {
			$validator->enterPath( 'PublicationIds' );
			$validator->checkNull( $datObj->PublicationIds );
			if( !is_null( $datObj->PublicationIds ) ) {
				$validator->checkType( $datObj->PublicationIds, 'array' );
				if( !empty($datObj->PublicationIds) ) foreach( $datObj->PublicationIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeletePublicationsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->PublicationIds)){
			if (is_object($this->PublicationIds[0])){
				foreach ($this->PublicationIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

