<?php
require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyField.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlButton extends HtmlBase
{
    public $Hidden;
    public $Caption;
    public $Submit;

    function __construct( $owner, $name, $caption, $submit = true, $hidden = false )
    {
        HtmlBase::__construct( $owner, $name );
        $this->Caption = $caption;
        $this->Submit = $submit;
        $this->Hidden = $hidden;
    }

    public function requestInput()
    {
    }

    public function requestValue()
    {
    }

    public function drawHeader()
    {
        return '';
    }

    public function drawBody()
    {
        $submitflag = $this->Submit ? 'type="submit"' : 'type="button"';
        return '<input '.$submitflag.' name="'.$this->Name.'" value="'.formvar( $this->Caption ).'" align="right"></input>';
    }
}