<?php
// Show the web applications index page

require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// check if current user has ticket
$ticket = checkSecure( null, null, false );

$tpl = HtmlDocument::loadTemplate( 'mainapps.htm' );
print HtmlDocument::buildDocument( $tpl );
?>