<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmCreatePublicationsResponse
{
	public $Publications;

	/**
	 * @param AdmPublication[]     $Publications              
	 */
	public function __construct( $Publications=null )
	{
		$this->Publications         = $Publications;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'CreatePublicationsResponse' );
		if( $validator->checkExist( $datObj, 'Publications' ) ) {
			$validator->enterPath( 'Publications' );
			$validator->checkNull( $datObj->Publications );
			if( !is_null( $datObj->Publications ) ) {
				$validator->checkType( $datObj->Publications, 'array' );
				if( !empty($datObj->Publications) ) foreach( $datObj->Publications as $listItem ) {
					$validator->enterPath( 'Publication' );
					$validator->checkType( $listItem, 'AdmPublication' );
					AdmPublicationValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmCreatePublicationsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

