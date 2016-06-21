<?php
/**
 * This file can be used to customize functions inside the AxaioMadeToprint-
 * Woodwing-PlugIn. Try not to edit AxaioMadeToPrintDispatcher.class.php by 
 * yourself, but intoduce a new method inside this class. If a hook is missing 
 * in AxaioMadeToPrintDispatcher, please notify axaio software at 
 * <support@axaio.com>.
 * 
 * @copyright (c) 2015, axaio software GmbH
 * @author René Treuber <support@axaio.com>
 * @package AxaioMadeToPrint
 * @uses EnterprisePlugin
 */

require_once dirname(__FILE__).'/config.php';

/**
 * This class can be used to customize the MadeToPrint Plugin. 
 * MadeToPrint Plugin hooks into this class on several positions in the workflow 
 * if this class implements a method for specific hooks.
 */
class AxaioMadeToPrintCustomize {

    static private $customClasses = array();

    private static function loadClasses()
    {
        $files = glob( dirname(__FILE__).'/Customize/AxaioMadeToPrintCustomize_*.php');
        foreach ($files as $key => $filename) {
            require_once $filename;
            self::$customClasses[] = str_replace(dirname(__FILE__).'/Customize/', '', str_replace('.php', '', $filename));
        }
        LogHandler::Log('mtp', 'DEBUG', 'CustomClasses: ' . print_r(self::$customClasses,true));
    }

    public static function Customize($name, &$arg1=null, &$arg2=null, &$arg3=null, &$arg4=null, &$arg5=null, &$arg6=null, &$arg7=null)
    {
        if( empty(self::$customClasses)) {
            self::loadClasses();
        }
        
        foreach (self::$customClasses as $id => $class) {
            if( method_exists($class, $name)) {
                LogHandler::Log('mtp', 'DEBUG', "Running MTP customization $class::$name" );
                $classFunc = array($class, $name);
                $funcParam = array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, &$arg7);
                call_user_func_array($classFunc, $funcParam);
            }
        }
    }
}