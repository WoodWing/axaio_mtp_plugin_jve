<?php
require_once dirname(__FILE__).'/../config.php';

class AxaioMadeToPrintCustomize_TriggerByName
{
    /**
     * checkTriggerStatuses_begin is called by "customize" method in AxaioMadeToPrintDispatcher
     */
    public static function checkTriggerStatuses_begin( $layoutId, $layStatusId, &$mtpConfig)
    {
        $mtpConfig['state_trigger_article'];
        $mtpConfig['state_trigger_image'];
        
        if ($mtpConfig['state_trigger_article'] != 0) {
            $dbDriver = DBDriverFactory::gen();
            $table = $dbDriver->tablename("states");
            $sql = "SELECT		s1.id as `stateID`
                    FROM		{$table} as s1, {$table} as s2 
                    WHERE		s1.type = s2.type
                            AND s1.state = s2.state
                            AND s2.id = {$mtpConfig['state_trigger_article']}
                    ";
     
            $sth = $dbDriver->query($sql);
            $mtpConfig['state_trigger_article'] = array();
            while (($res = $dbDriver->fetch($sth))) {
                array_push($mtpConfig['state_trigger_article'], $res['stateID']);
            }
        }    
        
        if ($mtpConfig['state_trigger_image'] != 0) {
            $dbDriver = DBDriverFactory::gen();
            $table = $dbDriver->tablename("states");
            $sql = "SELECT		s1.id as `stateID`
                    FROM		{$table} as s1, {$table} as s2 
                    WHERE		s1.type = s2.type
                            AND s1.state = s2.state
                            AND s2.id = {$mtpConfig['state_trigger_image']}
                    ";
                            
            $sth = $dbDriver->query($sql);
            $mtpConfig['state_trigger_image'] = array();
            while (($res = $dbDriver->fetch($sth))) {
                array_push($mtpConfig['state_trigger_image'], $res['stateID']);
            }
        }    
    }
}


