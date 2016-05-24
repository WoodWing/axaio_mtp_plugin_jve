<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class WflSendToResponse
{
	public $SendTo;

	/**
	 * @param WorkflowMetaData     $SendTo                    
	 */
	public function __construct( $SendTo=null )
	{
		$this->SendTo               = $SendTo;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/wfl/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'SendToResponse' );
		if( $validator->checkExist( $datObj, 'SendTo' ) ) {
			$validator->enterPath( 'SendTo' );
			$validator->checkNull( $datObj->SendTo );
			if( !is_null( $datObj->SendTo ) ) {
				$validator->checkType( $datObj->SendTo, 'WorkflowMetaData' );
				WflWorkflowMetaDataValidator::validate( $validator, $datObj->SendTo );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.wfl.WflSendToResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

