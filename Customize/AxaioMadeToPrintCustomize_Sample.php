<?php
require_once dirname(__FILE__).'/../config.php';

class AxaioMadeToPrintCustomize_Sample
{
    /**
     * outputProcessingFiles_filterFullrow is called by "customize" method in AxaioMadeToPrintDispatcher
     * @param array $fullrow	metadata of layout to put into jobticket. Passed by reference (changes will be taken into main)
     */
    public static function outputProcessingFiles_filterFullrow(&$fullrow)
    {
        // example: we don't need the "Types" array in our metadata
        if( isset($fullrow['Types'])) {
            unset($fullrow['Types']);
        }
    }
}


