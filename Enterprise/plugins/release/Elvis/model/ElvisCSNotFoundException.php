<?php

require_once dirname(__FILE__) . '/ElvisCSException.php';

class ElvisCSNotFoundException extends ElvisCSException {
	
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName() {
		return 'com.ds.acm.api.contentsource.model.CSNotFoundException';
	}

	public function toBizException() {
		return new BizException('ERR_NOTFOUND', 'Server', $this->detail);
	}
}