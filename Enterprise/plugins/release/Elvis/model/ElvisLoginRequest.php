<?php

require_once dirname(__FILE__) . '/AbstractRemoteObject.php';

class ElvisLoginRequest extends AbstractRemoteObject {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.LoginRequest';
	}
	
	/**
	 * Encrypted credentials
	 * 
	 * @var string
	 */
	public $cred;
	
	/**
	 * Locale preferred by client
	 * 
	 * @var string
	 */
	public $locale;
	
	/**
	 * Timezone offset from UTC in milis
	 * 
	 *  @var int
	 */
	public $timezoneOffset;

    /**
     * Identification of computer, from which user connected (UUID)
     *
     * @var string
     */
    public $clientId;
	
	/**
	 * 
	 * @param string $cred
	 * @param string $locale
	 * @param int $timezoneOffset
	 */
	function __construct($cred, $locale, $timezoneOffset) {
		$this->cred = $cred;
		$this->locale = $locale;
		$this->timezoneOffset = $timezoneOffset;
	}

}