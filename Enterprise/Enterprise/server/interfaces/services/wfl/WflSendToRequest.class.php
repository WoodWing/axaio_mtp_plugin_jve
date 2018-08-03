<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class WflSendToRequest
{
	public $Ticket;
	public $IDs;
	public $WorkflowMetaData;

	/**
	 * @param string               $Ticket                    
	 * @param string[]             $IDs                       
	 * @param WorkflowMetaData     $WorkflowMetaData          
	 */
	public function __construct( $Ticket=null, $IDs=null, $WorkflowMetaData=null )
	{
		$this->Ticket               = $Ticket;
		$this->IDs                  = $IDs;
		$this->WorkflowMetaData     = $WorkflowMetaData;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(true);
		$datObj = $this;

		$validator->enterPath( 'SendToRequest' );
		if( $validator->checkExist( $datObj, 'Ticket' ) ) {
			$validator->enterPath( 'Ticket' );
			$validator->checkNull( $datObj->Ticket );
			if( !is_null( $datObj->Ticket ) ) {
				$validator->checkType( $datObj->Ticket, 'string' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'IDs' ) ) {
			$validator->enterPath( 'IDs' );
			$validator->checkNull( $datObj->IDs );
			if( !is_null( $datObj->IDs ) ) {
				$validator->checkType( $datObj->IDs, 'array' );
				if( !empty($datObj->IDs) ) foreach( $datObj->IDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'WorkflowMetaData' ) ) {
			$validator->enterPath( 'WorkflowMetaData' );
			$validator->checkNull( $datObj->WorkflowMetaData );
			if( !is_null( $datObj->WorkflowMetaData ) ) {
				$validator->checkType( $datObj->WorkflowMetaData, 'WorkflowMetaData' );
				WflWorkflowMetaDataValidator::validate( $validator, $datObj->WorkflowMetaData );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSendToRequest'; } // AMF object type mapping

	public function sanitizeProperties4Php()
	{
		if (0 < count($this->IDs)){
			if (is_object($this->IDs[0])){
				foreach ($this->IDs as $complexField){
					$complexField->sanitizeProperties4Php();
				}
			}
		}
		if( is_object( $this->WorkflowMetaData ) ) {
			$this->WorkflowMetaData->sanitizeProperties4Php();
		}
	}

	public function mightHaveContent() { return false; }
}

