<?php

/**
 * @package Enterprise
 * @subpackage Services
 * @copyright WoodWing Software bv. All Rights Reserved.
 * 
 * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
 */

class AdmGetPubChannelsResponse
{
	public $PublicationId;
	public $PubChannels;

	/**
	 * @param integer              $PublicationId             
	 * @param AdmPubChannel[]      $PubChannels               
	 */
	public function __construct( $PublicationId=null, $PubChannels=null )
	{
		$this->PublicationId        = $PublicationId;
		$this->PubChannels          = $PubChannels;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/adm/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'GetPubChannelsResponse' );
		if( $validator->checkExist( $datObj, 'PublicationId' ) ) {
			$validator->enterPath( 'PublicationId' );
			$validator->checkNull( $datObj->PublicationId );
			if( !is_null( $datObj->PublicationId ) ) {
				$validator->checkType( $datObj->PublicationId, 'integer' );
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PubChannels' ) ) {
			$validator->enterPath( 'PubChannels' );
			$validator->checkNull( $datObj->PubChannels );
			if( !is_null( $datObj->PubChannels ) ) {
				$validator->checkType( $datObj->PubChannels, 'array' );
				if( !empty($datObj->PubChannels) ) foreach( $datObj->PubChannels as $listItem ) {
					$validator->enterPath( 'PubChannel' );
					$validator->checkType( $listItem, 'AdmPubChannel' );
					AdmPubChannelValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.adm.AdmGetPubChannelsResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

