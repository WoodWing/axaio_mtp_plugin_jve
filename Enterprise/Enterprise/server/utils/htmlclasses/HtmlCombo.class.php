<?php
    
require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyField.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlCombo extends HtmlAnyField
{
    public $Options;


    function __construct($owner, $name, $required=false, $readonly=false, $hidden=false)
	{
		HtmlAnyField::__construct($owner, $name, $required, $readonly, $hidden);
        $this->Options = array();
	}

    public function setValue($optionid)
    {
        $this->Value = $optionid;
    }    
    
    public function setValueByName($optionname)
    {
        $optionid = array_search($optionname, $this->Options);
        self::setValue($optionid);
    }

    public function setOptions($newoptions)
    {
        $this->Options = $newoptions;
    }

    public function addOption($optionid, $optionname)
    {
        $this->Options[$optionid] = $optionname;   
    }

    public function getDisplayValue()
    {
        if (array_key_exists($this->Value, $this->Options))
        {
            return $this->Options[$this->Value];
        }
        return '';
    }

    /**
     * @return string
     */
    public function requestInput()
    {
    	$optionid = @$_REQUEST[$this->Name];
    	return $optionid;
    }
    
    public function requestValue()
    {
        return self::requestInput();
    }
    
    public function requestValueByName()
    {
        return $this->Options[self::requestValue()];
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

        $result = '<select id="'.$this->Name.'" name="'.$this->Name.'" '.$hiddenflag.' '.$readonlyflag.' '.$onchange.'>';
        foreach ($this->Options as $optionid => $option)
        {
            $selectedflag = ($optionid == $this->Value) ? 'selected' : '';
            $result .= '<option id="'.formvar($optionid).'" value="'.formvar($optionid).'" '.$selectedflag.'>';
            $result .= formvar($option);
            $result .= '</option>';
        }
        $result .= '</select>';
        return $result;
    }
}