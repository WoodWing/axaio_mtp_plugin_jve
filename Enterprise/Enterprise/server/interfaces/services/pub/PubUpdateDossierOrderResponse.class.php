<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PubUpdateDossierOrderResponse
{
	public $DossierIDs;

	/**
	 * @param string[]             $DossierIDs                
	 */
	public function __construct( $DossierIDs=null )
	{
		$this->DossierIDs           = $DossierIDs;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'UpdateDossierOrderResponse' );
		if( $validator->checkExist( $datObj, 'DossierIDs' ) ) {
			$validator->enterPath( 'DossierIDs' );
			$validator->checkNull( $datObj->DossierIDs );
			if( !is_null( $datObj->DossierIDs ) ) {
				$validator->checkType( $datObj->DossierIDs, 'array' );
				if( !empty($datObj->DossierIDs) ) foreach( $datObj->DossierIDs as $listItem ) {
					$validator->enterPath( 'string' );
					$validator->checkType( $listItem, 'string' );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubUpdateDossierOrderResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

