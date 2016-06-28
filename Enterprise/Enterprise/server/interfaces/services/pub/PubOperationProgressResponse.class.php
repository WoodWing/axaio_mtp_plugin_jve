<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class PubOperationProgressResponse
{
	public $Phases;

	/**
	 * @param PubProgressPhase[]   $Phases                    
	 */
	public function __construct( $Phases=null )
	{
		$this->Phases               = $Phases;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'OperationProgressResponse' );
		if( $validator->checkExist( $datObj, 'Phases' ) ) {
			$validator->enterPath( 'Phases' );
			$validator->checkNull( $datObj->Phases );
			if( !is_null( $datObj->Phases ) ) {
				$validator->checkType( $datObj->Phases, 'array' );
				if( !empty($datObj->Phases) ) foreach( $datObj->Phases as $listItem ) {
					$validator->enterPath( 'ProgressPhase' );
					$validator->checkType( $listItem, 'PubProgressPhase' );
					PubProgressPhaseValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubOperationProgressResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

