<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR . '/server/secure.php';
require_once BASEDIR . '/server/utils/htmlclasses/HtmlApp.class.php';
require_once BASEDIR . '/server/apps/DeadlinesForm.class.php';

function SCEntReport( $inPub, $inIssue )
{
	global $globUser;
    $application = new HtmlApp('report deadlines');
    $application->Ticket = checkSecure();
    $application->User = $globUser;
    $application->MainForm = new DeadlinesForm( $application, 'DeadlinesForm' );
    $application->MainForm->IssueId = $inIssue;
	$application->InReport = true;
	
    $application->MainForm->createFields();
	$result = $application->MainForm->drawHeader() . "\n";
    $application->MainForm->fetchData();
    $result .= $application->drawBody() . "\n";
    $result .= $application->MainForm->drawBody() . "\n";
	
	return $result;
}