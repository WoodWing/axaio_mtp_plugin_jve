<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmCreatePublicationAdminAuthorizationsRequest
{
	public $Ticket;
	public $PublicationId;
	public $UserGroupIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             
	 * @param integer[]            $UserGroupIds              
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $UserGroupIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->UserGroupIds         = $UserGroupIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreatePublicationAdminAuthorizationsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublicationId' ) ) {
			$validator->enterPath( 'PublicationId' );
			$validator->checkNull( $datObj->PublicationId );
			if( !is_null( $datObj->PublicationId ) ) {
				$validator->checkType( $datObj->PublicationId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'UserGroupIds' ) ) {
			$validator->enterPath( 'UserGroupIds' );
			$validator->checkNull( $datObj->UserGroupIds );
			if( !is_null( $datObj->UserGroupIds ) ) {
				$validator->checkType( $datObj->UserGroupIds, 'array' );
				if( !empty($datObj->UserGroupIds) ) foreach( $datObj->UserGroupIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreatePublicationAdminAuthorizationsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (0 < count($this->UserGroupIds)){
			if (is_object($this->UserGroupIds[0])){
				foreach ($this->UserGroupIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

