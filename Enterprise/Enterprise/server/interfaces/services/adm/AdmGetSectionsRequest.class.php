<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmGetSectionsRequest
{
	public $Ticket;
	public $RequestModes;
	public $PublicationId;
	public $IssueId;
	public $SectionIds;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $RequestModes              
	 * @param integer              $PublicationId             
	 * @param integer              $IssueId                   
	 * @param integer[]            $SectionIds                Nullable.
	 */
	public function __construct( $Ticket=null, $RequestModes=null, $PublicationId=null, $IssueId=null, $SectionIds=null )
	{
		$this->Ticket               = $Ticket;
		$this->RequestModes         = $RequestModes;
		$this->PublicationId        = $PublicationId;
		$this->IssueId              = $IssueId;
		$this->SectionIds           = $SectionIds;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'GetSectionsRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'RequestModes' ) ) {
			$validator->enterPath( 'RequestModes' );
			$validator->checkNull( $datObj->RequestModes );
			if( !is_null( $datObj->RequestModes ) ) {
				$validator->checkType( $datObj->RequestModes, 'array' );
				if( !empty($datObj->RequestModes) ) foreach( $datObj->RequestModes as $listItem ) {
					$validator->enterPath( 'Mode' );
					$validator->checkType( $listItem, 'string' );
					AdmModeValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
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
		if( $validator->checkExist( $datObj, 'IssueId' ) ) {
			$validator->enterPath( 'IssueId' );
			$validator->checkNull( $datObj->IssueId );
			if( !is_null( $datObj->IssueId ) ) {
				$validator->checkType( $datObj->IssueId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SectionIds' ) ) {
			$validator->enterPath( 'SectionIds' );
			if( !is_null( $datObj->SectionIds ) ) {
				$validator->checkType( $datObj->SectionIds, 'array' );
				if( !empty($datObj->SectionIds) ) foreach( $datObj->SectionIds as $listItem ) {
					$validator->enterPath( 'Id' );
					$validator->checkType( $listItem, 'Id' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetSectionsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (0 < count($this->RequestModes)){
			if (is_object($this->RequestModes[0])){
				foreach ($this->RequestModes as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if (0 < count($this->SectionIds)){
			if (is_object($this->SectionIds[0])){
				foreach ($this->SectionIds as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

