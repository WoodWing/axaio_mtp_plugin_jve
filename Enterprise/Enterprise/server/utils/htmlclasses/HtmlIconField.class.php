<?php
	require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyField.class.php';
    
    class HtmlIconField extends HtmlAnyField
    {
        function __construct($owner, $name, $required=false, $readonly=false, $hidden=false)
		{
			HtmlAnyField::__construct($owner, $name, $required, $readonly, $hidden);
        }
        
        function requestInput()
        {
            return null;   
        }
        
        function requestValue()
        {
            return null;   
        }
        
        function setValue($newiconfile)
        {
            $this->Value = $newiconfile;
        }

        function drawHeader()
        {
            return '';   
        }
        
        function drawBody()
        {
            $result = '';
			if( is_array($this->Value) ) {
				$title = $this->Value['title'];
				$iconfile = $this->Value['icon'];
			} else {
				$title = $this->Title;
				$iconfile = $this->Value;
			}
            if (!empty($iconfile))
            {
				//$result .= '<a href="" >';
				$result .= '<div align="center">';
            	$result .= '<img src="'.$iconfile.'" border="0" title="'.$title.'" alt="'.$title.'"></img>';
				$result .= '</div>';
            	//$result .= '</a>';
            }
            return $result;
        }
    }
?>