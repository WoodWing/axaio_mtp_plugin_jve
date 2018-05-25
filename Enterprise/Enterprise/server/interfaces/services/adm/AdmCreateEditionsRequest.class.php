<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class AdmCreateEditionsRequest
{
	public $Ticket;
	public $PublicationId;
	public $PubChannelId;
	public $IssueId;
	public $Editions;

	/**
	 * @param string               $Ticket                    
	 * @param integer              $PublicationId             
	 * @param integer              $PubChannelId              Nullable.
	 * @param integer              $IssueId                   Nullable.
	 * @param AdmEdition[]         $Editions                  
	 */
	public function __construct( $Ticket=null, $PublicationId=null, $PubChannelId=null, $IssueId=null, $Editions=null )
	{
		$this->Ticket               = $Ticket;
		$this->PublicationId        = $PublicationId;
		$this->PubChannelId         = $PubChannelId;
		$this->IssueId              = $IssueId;
		$this->Editions             = $Editions;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'CreateEditionsRequest' );
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
		if( $validator->checkExist( $datObj, 'PubChannelId' ) ) {
			$validator->enterPath( 'PubChannelId' );
			if( !is_null( $datObj->PubChannelId ) ) {
				$validator->checkType( $datObj->PubChannelId, 'integer' );
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
		if( $validator->checkExist( $datObj, 'Editions' ) ) {
			$validator->enterPath( 'Editions' );
			$validator->checkNull( $datObj->Editions );
			if( !is_null( $datObj->Editions ) ) {
				$validator->checkType( $datObj->Editions, 'array' );
				if( !empty($datObj->Editions) ) foreach( $datObj->Editions as $listItem ) {
					$validator->enterPath( 'Edition' );
					$validator->checkType( $listItem, 'AdmEdition' );
					AdmEditionValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreateEditionsRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (is_nan($this->PublicationId)){ $this->PublicationId = null; }
		if (is_nan($this->PubChannelId)){ $this->PubChannelId = null; }
		if (is_nan($this->IssueId)){ $this->IssueId = null; }
		if (0 < count($this->Editions)){
			if (is_object($this->Editions[0])){
				foreach ($this->Editions as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
	}

	public function mightHaveContent() { return false; }
}

