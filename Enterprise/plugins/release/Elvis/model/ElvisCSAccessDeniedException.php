<?php

require_once 'ElvisCSException.php';

class ElvisCSAccessDeniedException extends ElvisCSException
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.CSAccessDeniedException';
	}

	public function toBizException()
	{
		return new BizException( 'ERR_AUTHORIZATION', 'Server', $this->detail, null, null, 'INFO' );
	}
}