<?php
require_once "../../config/config.php";
require_once BASEDIR."/server/secure.php";
//require_once BASEDIR.'/server/bizclasses/BizAccessFeatureProfiles.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

$ticket = checkSecure();
//webauthorization( BizAccessFeatureProfiles::ACCESS_EXPORT );
$tpl = HtmlDocument::loadTemplate( 'export.htm' );
print HtmlDocument::buildDocument($tpl);
?>