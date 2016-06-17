<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR . '/server/secure.php';
require_once BASEDIR . '/server/admin/global_inc.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlApp.class.php';
require_once BASEDIR . '/server/admin/ChannelForm.class.php';
    
try {
	global $globUser;
	$application = new HtmlApp('editChannel');
	$application->Ticket = checkSecure('publadmin');
	$application->User = $globUser;
	$application->MainForm = new ChannelForm( $application, 'ChannelForm' );
	$application->run();

} catch (Exception $e) {
	echo 'Exception while running: &nbsp;' . __FILE__ . '<br/>';
	echo $e->getMessage();
}
?>