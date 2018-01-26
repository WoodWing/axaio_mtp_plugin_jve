<?php
require_once dirname(__FILE__).'/../config.php';

class AxaioMadeToPrintCustomize_EditionFilter
{
    /**
     */
    public static function queueLayoutObject_filterEditions(&$layEditions, $layoutId, $layStatusId, $user)
    {
        // Retrieve object props from smart_objects table
        require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
        $fullrow = BizQuery::queryObjectRow($layoutId);
        // Determine the MtP job name
        
        if($fullrow['C_META_SET'] == 'Cover') {
            $layEditions = is_array($layEditions)?array_pop($layEditions):null;
        }
    }
}


