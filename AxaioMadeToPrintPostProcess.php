<pre><?php
echo "postProcess: begin\n";
try {
    require_once dirname(__FILE__) . '/config.php';
    require_once dirname(__FILE__) . '/AxaioMadeToPrintDispatcher.class.php';
    

#    require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
#	var_dump( BizObject::getObject($_REQUEST['id'], AXAIO_MTP_USER, false, 'none', array('Targets', 'MetaData', 'Relations'), null, false));
    // Heavy debug only:
    // LogHandler::Log('mtp', 'INFO', print_r($_REQUEST, true));
    
    $layoutId     = isset($_REQUEST['id'])         ? $_REQUEST['id']         : 0;
    $layStatusId  = isset($_REQUEST['state'])      ? $_REQUEST['state']      : 0;
    $layEditionId = isset($_REQUEST['edition'])    ? $_REQUEST['edition']    : 0;

    $message      = isset($_REQUEST['message'])    ? trim($_REQUEST['message']) : null;

    $success      = isset($_REQUEST['success'])    ? $_REQUEST['success']    : 0;

    $ip           = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    $servername   = isset($_REQUEST['servername']) ? $_REQUEST['servername'] : '';


    if (LogHandler::debugMode())
    {
            LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'DEBUG', 'AxaioMadeToPrintPostProcess {ip}:' );
            LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'DEBUG', print_r($ip, true) );

            LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'DEBUG', 'AxaioMadeToPrintPostProcess {servername}: ' );
            LogHandler::Log( 'AxaioMadeToPrintPostProcess.php', 'DEBUG', print_r($servername, true) );
    }

    if($servername) {
        if($ip) {
            $servername = $ip . " " . $servername;
        }
        $servername = addslashes(html_entity_decode($servername));
    }
    if($message){
            $message = preg_replace('/<status>/is', '', $message);
            $message = preg_replace('@</status>@is', '', $message);
            $message = addslashes(html_entity_decode($message));
    }

    $debugMessage = 'postProcess: calling postProcess with: ' . "\n" . 
                                                            '- $layoutId:     ' . $layoutId     ."\n" . 
                                                            '- $layStatusId:  ' . $layStatusId  ."\n" . 
                                                            '- $layEditionId: ' . $layEditionId ."\n" . 
                                                            '- $success:      ' . $success      ."\n" . 
                                                            '- $servername:   ' . $servername   ."\n" . 
                                                            '- $message:      ' . $message      ."\n" ;
    LogHandler::Log('mtp', 'DEBUG', $debugMessage);

    print_r($debugMessage);
    echo "\n";

    #$ret = AxaioMadeToPrintDispatcher::postProcess( $layoutId, $layStatusId, $layEditionId, $success, $message, $servername);
    $ret = AxaioMadeToPrintDispatcher::newPostProcess( $layoutId, $layStatusId, $layEditionId, $success, $message, $servername);
    
    echo "\nMessages: ";
    echo var_dump($ret);

} catch(Exception $err) {
    LogHandler::Log('mtp', 'ERROR', $err);
    var_dump($err);
}
