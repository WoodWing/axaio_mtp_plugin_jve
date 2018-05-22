<?php
/**
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

require_once 'AbstractRemoteObject.php';

class ElvisCSException extends Exception
{
	/**
	 * Return the fully qualified name of the Java class
	 */
	public static function getJavaClassName()
	{
		return 'com.ds.acm.api.contentsource.model.CSException';
	}

	/**
	 * Return the name of the class
	 *
	 * @return string
	 */
	public static function getName()
	{
		return get_called_class();
	}

	final public function setMessage( $message )
	{
		$this->message = $message;
	}

	final public function setDetail( $detail )
	{
		$this->detail = $detail;
	}

	public function toBizException()
	{
		return new BizException( null, 'Server', $this->detail, $this->message );
	}
}