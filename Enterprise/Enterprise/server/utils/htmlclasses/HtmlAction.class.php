<?php

require_once BASEDIR.'/server/utils/htmlclasses/HtmlBase.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlAction extends HtmlBase
{
	public $Caption;
	public $Icon;
	public $Hint;
	public $Submit;
	public $ExecFunc;

	function __construct($owner, $name, $caption, $execfunc, $submit = false, $onClick = '', $icon = '', $hint = '')
	{
		HtmlBase::__construct($owner, $name);
		$this->Caption = $caption;
		$this->Icon = $icon;
		$this->Hint = $hint;
		$this->Submit = $submit;
		$this->ExecFunc = $execfunc;
		$this->OnClick = $onClick;
	}
	
	function drawHeader()
	{
		return '';   
	}

	function drawBody()
	{
		$submit = $this->Submit ? 'type="submit"' : 'type="button"';
		$onClick = $this->OnClick ? 'onclick="'.$this->OnClick.'"' : '';
		return '<input '.$submit.' id="'.$this->Name.'" name="'.$this->Name.'" value="'.formvar($this->Caption).'" '.$onClick.'></input>';
	}
	
	function drawBodyWithTag($tag)
	{
		$submit = $this->Submit ? 'type="submit"' : 'type="button"';
		$taggedName = $this->Name . '~' . $tag;
		$onClick = $this->OnClick ? 'onclick="'.$this->OnClick.'"' : '';
		return '<input '.$submit.' id="'.$taggedName.'" name="'.$taggedName.'" value="'.formvar($this->Caption).'" '.$onClick.'></input>';
	}
}
