<?php
require_once dirname(__FILE__).'/../config.php';

class AxaioMadeToPrintCustomize_FilterName
{
    /**
     */
    public static function outputProcessingFiles_filterName(&$name, $fullrow)
    {
        $name = $name . '_' . time();
    }
}


