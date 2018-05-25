<?php
/**
 * @since      v10.0.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_RabbitMQ_RestAPI_Permissions
{
	/** @var string $Read Regular expression that defines read access. No access by default. */
	public $Read = '^$';

	/** @var string $Write Regular expression that defines write access. No access by default. */
	public $Write = '^$';

	/** @var string $Configure Regular expression that defines configure access. No access by default. */
	public $Configure = '^$';

	/** @var string $VirtualHost The Virtual Host for which the permissions are defined. */
	public $VirtualHost = null;

	/**
	 * Updates the permissions properties with a given Permissions JSON object (used in the REST API).
	 *
	 * @param stdClass|null $json JSON object
	 */
	public function setJson( $json )
	{
		if( $json ) {
			$this->Read = $json->read;
			$this->Write = $json->write;
			$this->Configure = $json->configure;
			$this->VirtualHost = $json->vhost;
		}
	}

	/**
	 * Composes a Permissions JSON object (used in the REST API) from the permissions properties.
	 *
	 * @return stdClass JSON object
	 */
	public function getJson()
	{
		$json = new stdClass();
		if( $this->Read ) {
			$json->read = $this->Read;
		}
		if( $this->Write ) {
			$json->write = $this->Write;
		}
		if( $this->Configure ) {
			$json->configure = $this->Configure;
		}
		if( $this->VirtualHost ) {
			$json->vhost = $this->VirtualHost;
		}
		return $json;
	}

	/**
	 * Tells whether or not this permission gives full read/write/config access.
	 *
	 * @return bool
	 */
	public function hasFullAccess()
	{
		return $this->Read == '.*' && $this->Write == '.*' && $this->Configure == '.*';
	}

	/**
	 * Enable read/write/config access.
	 */
	public function setFullAccess()
	{
		$this->Read = '.*';
		$this->Write = '.*';
		$this->Configure = '.*';
	}
}