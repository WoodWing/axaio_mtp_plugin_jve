<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmDeleteSectionsRequest
{
	public $Ticket;
	public $PublicationId;
	public $IssueId;
	public $SectionIds;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             
	 * @param integer              $IssueId                   Nullable.
	 * @param integer[]            $SectionIds                
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $IssueId=null, $SectionIds=null )
	{
		$this->Ticket               = $Ticket;
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

		$validator->enterPath( 'DeleteSectionsRequest' );
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
		if( $validator->checkExist( $datObj, 'IssueId' ) ) {
			$validator->enterPath( 'IssueId' );
			if( !is_null( $datObj->IssueId ) ) {
				$validator->checkType( $datObj->IssueId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'SectionIds' ) ) {
			$validator->enterPath( 'SectionIds' );
			$validator->checkNull( $datObj->SectionIds );
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

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmDeleteSectionsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
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

