<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR . '/server/secure.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlApp.class.php';
require_once BASEDIR . '/server/apps/DeadlinesForm.class.php';

try {
	global $globUser;
	$application = new HtmlApp('report deadlines');
	$application->Ticket = checkSecure();
	$application->User = $globUser;
	$application->MainForm = new DeadlinesForm( $application, 'DeadlinesForm' );
	$application->run();

} catch (Exception $e) {
	echo 'Exception while running: &nbsp;' . __FILE__ . '<br/>';
	echo $e->getMessage();
}   
?>