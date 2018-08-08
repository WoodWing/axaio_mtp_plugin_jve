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
		$elvisVersion = $elvisMetadata[ $this->lvsFieldName ];
		if( isset( $elvisVersion ) ) {
			$enterpriseVersion = Elvis_BizClasses_Version::getEnterpriseObjectVersionNumber( $elvisVersion );
			$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $enterpriseVersion;
		}
	}
}
