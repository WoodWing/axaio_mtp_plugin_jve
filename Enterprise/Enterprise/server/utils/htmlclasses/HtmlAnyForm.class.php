<?php

/**
 *	Class derived from HmtlBase that represents a html-form for editing/maintaining/viewing data.
 *
 * @todo This class is not implemented yet in any way.
 *	@todo This class is not documented yet.
 *
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/HtmlBase.class.php';

abstract class HtmlAnyForm extends HtmlBase
{
	protected $Mode;
	protected $hasError = false;

	function __construct( $owner, $name, $mode = null )
	{
		HtmlBase::__construct( $owner, $name );
		$this->Mode = $mode;
	}

	abstract public function createFields();

	abstract public function execAction();

	abstract public function fetchData();

	public function setError()
	{
		$this->hasError = true;
	}
}