<?php

require_once 'AbstractRemoteObject.php';

class ElvisLoginRequest extends AbstractRemoteObject
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.LoginRequest';
	}

	/** @var string $cred Encrypted credentials */
	public $cred;

	/** @var string Locale preferred by client */
	public $locale;

	/** @var int $timezoneOffset Timezone offset from UTC in milliseconds */
	public $timezoneOffset;

	/** @var string $clientId Identification of computer, from which user connected (UUID) */
	public $clientId;

	/**
	 * @param string $cred
	 * @param string $locale
	 * @param int $timezoneOffset
	 */
	public function __construct( $cred, $locale, $timezoneOffset )
	{
		$this->cred = $cred;
		$this->locale = $locale;
		$this->timezoneOffset = $timezoneOffset;
	}
}