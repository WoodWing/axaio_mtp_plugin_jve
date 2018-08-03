<?php

/**
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// * IMPORTANT: DO NOT EDIT! THIS CLASS IS GENERATED FROM WSDL!
// * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

class PubPublishDossiersResponse
{
	public $PublishedDossiers;
	public $PublishedIssue;

	/**
	 * @param PubPublishedDossier[] $PublishedDossiers         Nullable.
	 * @param PubPublishedIssue    $PublishedIssue            Nullable.
	 */
	public function __construct( $PublishedDossiers=null, $PublishedIssue=null )
	{
		$this->PublishedDossiers    = $PublishedDossiers;
		$this->PublishedIssue       = $PublishedIssue;
	}

	public function validate()
	{
		require_once BASEDIR.'/server/services/Validator.php';
		require_once BASEDIR.'/server/services/pub/DataValidators.php';
		$validator = new WW_Services_Validator(false);
		$datObj = $this;

		$validator->enterPath( 'PublishDossiersResponse' );
		if( $validator->checkExist( $datObj, 'PublishedDossiers' ) ) {
			$validator->enterPath( 'PublishedDossiers' );
			if( !is_null( $datObj->PublishedDossiers ) ) {
				$validator->checkType( $datObj->PublishedDossiers, 'array' );
				if( !empty($datObj->PublishedDossiers) ) foreach( $datObj->PublishedDossiers as $listItem ) {
					$validator->enterPath( 'PublishedDossier' );
					$validator->checkType( $listItem, 'PubPublishedDossier' );
					PubPublishedDossierValidator::validate( $validator, $listItem );
					$validator->leavePath();
				}
			}
			$validator->leavePath();
		}
		if( $validator->checkExist( $datObj, 'PublishedIssue' ) ) {
			$validator->enterPath( 'PublishedIssue' );
			if( !is_null( $datObj->PublishedIssue ) ) {
				$validator->checkType( $datObj->PublishedIssue, 'PubPublishedIssue' );
				PubPublishedIssueValidator::validate( $validator, $datObj->PublishedIssue );
			}
			$validator->leavePath();
		}
		$validator->leavePath();
	}

	public function getASClassName() { return AS_CLASSNAME_PREFIX.'.pub.PubPublishDossiersResponse'; } // AMF object type mapping

	public function mightHaveContent() { return false; }
}

