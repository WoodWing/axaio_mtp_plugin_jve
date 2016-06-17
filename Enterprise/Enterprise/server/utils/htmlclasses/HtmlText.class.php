<?php

require_once BASEDIR.'/server/utils/htmlclasses/HtmlBase.class.php';

class HtmlText extends HtmlAnyField
{
	function __construct( $owner, $name, $required=false, $readonly=false, $hidden=false )
	{			
		parent::__construct( $owner, $name, $required, $readonly, $hidden) ;
	}

	public function requestInput()
	{
		return $_REQUEST[$this->Name];
	}
	
	public function requestValue()
	{
		return self::requestInput();
	}
	
	public function drawHeader()
	{
		return '';   
	}
	
	public function drawBody()
	{
        $hiddenflag = $this->Hidden ? 'type="hidden"' : '';
        $readonlyflag = $this->ReadOnly ? 'disabled="disabled"' : '';
        $onchange = $this->OnChange ? 'onchange="' . $this->OnChange .'"' : '';

		return '<textarea id="'.$this->Name.'" name="'.$this->Name.'" '.
				$hiddenflag.' '.$readonlyflag.' '.$onchange.' type="textarea" style="width:200px">'.
				formvar($this->Value).
			'</textarea>';
	}
	
	public function setValue( $text )
	{
		$this->Value = $text;
	}
}
