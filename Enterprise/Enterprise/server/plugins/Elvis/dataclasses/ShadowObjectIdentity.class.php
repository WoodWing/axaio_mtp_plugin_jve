<?php
/**
 * Data class used between Elvis-Enterprise communication.
 *
 * @copyright  WoodWing Software bv. All Rights Reserved.
 * @since 10.5.0
 */

class Elvis_DataClasses_ShadowObjectIdentity
{
	/** @var string $enterpriseSystemId */
	public $enterpriseSystemId;

	/** @var string $assetId */
	public $assetId;

	/**
	 * Elvis_BizClasses_ShadowObjectIdentity constructor.
	 *
	 * @param string $enterpriseSystemId
	 * @param string $assetId
	 */
	public function __construct( $enterpriseSystemId, $assetId )
	{
		$this->enterpriseSystemId = $enterpriseSystemId;
		$this->assetId = $assetId;
	}
}
