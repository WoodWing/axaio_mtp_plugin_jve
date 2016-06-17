<?php
/**
 * @package    Enterprise
 * @subpackage RabbitMQ
 * @since      v10.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_RabbitMQ_RestAPI_User
{
	/** @var string $Name */
	public $Name = null;

	/** @var string $Password */
	public $Password = null;

	/** @var string[] $Tags Supported values are "administrator", "monitoring", "management" or "policymaker"  */
	public $Tags = null;

	/**
	 * Updates the user properties with a given User JSON object (used in the REST API).
	 *
	 * @param stdClass|null $json
	 */
	public function setJson( $json )
	{
		if( $json ) {
			$this->Name = $json->name;
			$this->Tags = explode( ',', $json->tags );
		}
	}

	/**
	 * Composes a User JSON object (used in the REST API) from the user properties.
	 *
	 * @return stdClass
	 */
	public function getJson()
	{
		$json = new stdClass();
		if( $this->Name ) {
			$json->name = $this->Name;
		}
		if( $this->Password ) {
			$json->password = $this->Password;
		}
		if( is_array( $this->Tags ) ) {
			$json->tags = implode( ',', $this->Tags );
		}
		return $json;
	}
}