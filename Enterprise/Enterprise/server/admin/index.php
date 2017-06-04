<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure();
$tpl = HtmlDocument::loadTemplate( 'mainadmin.htm' );
print HtmlDocument::buildDocument($tpl);
