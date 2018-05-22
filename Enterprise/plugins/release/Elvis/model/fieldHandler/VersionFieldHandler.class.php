<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'ReadOnlyFieldHandler.class.php';

class VersionFieldHandler extends ReadOnlyFieldHandler
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
		require_once __DIR__.'/../../util/ElvisUtils.class.php';
		$elvisVersion = $elvisMetadata[ $this->lvsFieldName ];
		if( isset( $elvisVersion ) ) {
			$enterpriseVersion = ElvisUtils::getEnterpriseVersionNumber( $elvisVersion );
			$entMetadata->{$this->entMetadataCategory}->{$this->property->Name} = $enterpriseVersion;
		}
	}
}
