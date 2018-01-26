<?php
require_once dirname(__FILE__).'/../config.php';

define('AXAIO_MTP_LICENSE_WARNING_DAYS', 14); // warn X days before license ends
define('AXAIO_MTP_LICENSE_WARNING_EACH', 24*60); // one warning per X minutes
define('AXAIO_MTP_LICENSE_LOC',          SERVERURL_ROOT.INETROOT.'/config/plugins/AxaioMadeToPrint/Customize/AxaioMadeToPrintCustomize_LicenseDaysLeft.php');

//check whether this page was called with days left
if( isset($_REQUEST['daysleft'])) {
    AxaioMadeToPrintCustomize_LicenseDaysLeft::getReportDaysLeft( intval($_REQUEST['daysleft']));
}

class AxaioMadeToPrintCustomize_LicenseDaysLeft
{
    /**
     * outputProcessingFiles_filterFullrow is called by "customize" method in AxaioMadeToPrintDispatcher
     * @param array $fullrow	metadata of layout to put into jobticket. Passed by reference (changes will be taken into main)
     */
    public static function outputProcessingFiles_beforeWrite(&$preprocessjs, &$postprocessjs, &$mtpjob, &$mtpPaths, &$fullrow)
    {
        $licenseScript = 'try {
                var daysleft = app.theMadeToPrintObject.getLicenseDaysLeft();
                if( daysleft <= '.AXAIO_MTP_LICENSE_WARNING_DAYS.') {
                    app.performSimpleRequest( "'.AXAIO_MTP_LICENSE_LOC.'?daysleft="+daysleft);
                }
            } catch(e) {
                //fail silently
            }
            ';
 
        $preprocessjs = $licenseScript . $preprocessjs;
    }
    
    public static function getReportDaysLeft( $days ) {
        if($days == -1) {
            //unlimited license
            return;
        }
        if($days < AXAIO_MTP_LICENSE_WARNING_DAYS) {
            //not yet have reached a critical time
            return;
        }
        
        $dbDriver = DBDriverFactory::gen();
        $table = DBPREFIX."axaio_mtp_process_options";
            $sql =" SELECT		option_value as `lastcheck`
                    FROM		{$table}
                    WHERE		option_name = 'AXAIO_MTP_LICENSE_LastMail'
                    ORDER BY            `id` DESC
                    LIMIT		1";
                    
            $sth = $dbDriver->query($sql);
            $res = $dbDriver->fetch($sth);
            $lastMail = isset($res['lastcheck'])? intval($res['lastcheck']) : 0;
            
            echo "<pre>";
            echo "time:     " . time() . "<br>";
            echo "last:     " . $lastMail . "<br>";
            echo "diff:     " . AXAIO_MTP_LICENSE_WARNING_EACH. " Min<br>";
            echo "diff:     " . (AXAIO_MTP_LICENSE_WARNING_EACH*60). " Sec<br>";
            echo "need:     " . (time()-AXAIO_MTP_LICENSE_WARNING_EACH*60) . "<br>";
            if($lastMail <= (time()-AXAIO_MTP_LICENSE_WARNING_EACH*60)) {
                echo "TRUE. " . (time()-AXAIO_MTP_LICENSE_WARNING_EACH*60);
            } else {
                echo "FALSE.    ". (time()-AXAIO_MTP_LICENSE_WARNING_EACH*60);
            }
            var_dump($lastMail);
           
        $lastmail = 0;
       
         var_dump(get_class_methods(LogHandler));
        // do something, log, mail... 
    }
}


