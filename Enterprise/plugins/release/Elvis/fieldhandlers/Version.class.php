<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class Elvis_FieldHandlers_Version extends Elvis_FieldHandlers_ReadOnly
{
	public function __construct()
	{
		parent::__construct( "versionNumber", false, "number", "Version" );
	}

	/**
	 * @inheritdoc
	 */
	public function read( $entMetadata, $elvisMetadata )
	{
		require_once __DIR__.'/../util/ElvisUtils.class.php';
		$elvisVersion = $elvisMetadata[ $this->lvsFieldName ];
		if( isset( $elvisVersion ) ) {
			$enterpriseVersion = ElvisUtils::getEnterpriseVersionNumber( $elvisVersion );
			$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $enterpriseVersion;
		}
	}
}
