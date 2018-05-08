<?php
require_once __DIR__.'/../../config/config.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$tpl = HtmlDocument::loadTemplate( 'noright.htm' );
print HtmlDocument::buildDocument( $tpl );
