<?php
require_once dirname(__FILE__).'/../../config/config.php';
require_once BASEDIR.'/server/secure.php';
require_once BASEDIR.'/server/admin/global_inc.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

// Check for brand access rights.
global $isadmin;
global $ispubladmin;
checkSecure('publadmin');

// Load HTML template.
$tpl = HtmlDocument::loadTemplate( 'advanced.htm' );
$opCacheMessages = array();

// Requested to clear the Zend OPcache?
if( isset( $_GET['clearopcache'] ) && $_GET['clearopcache'] == 1 ) {
	// This action requires system admin access.
	checkSecure('admin');

	// Just clear the Zend OPcache right away.
	require_once BASEDIR .'/server/utils/ZendOpcache.php';
	$isCleared = WW_Utils_ZendOpcache::clearOPcache();

	// Report whether or not the cache could be cleared.
	if( $isCleared ) {
		$opCacheMessages[] = BizResources::localize( 'ZEND_OPCACHE_SUCC_CLEARED' );
	} else {
		if( is_null( $isCleared ) ) {
			$opCacheMessages[] =  BizResources::localize( 'ZEND_OPCACHE_NOT_INSTALLED' );
		} else {
			$opCacheMessages[] =  BizResources::localize( 'ZEND_OPCACHE_CLEARED_FAILED' );
		}
	}
}

// For brand admins, hide system admin icons.
if( $isadmin ) {
	$tpl = str_replace( '<!--PAR:SHOW_FOR_BRAND_SYS_ADMIN_ONLY-->', '', $tpl );
	$tpl = str_replace( '<!--PAR:SHOW_FOR_SYS_ADMIN_ONLY-->', '', $tpl );
}else {
	$tpl = str_replace( '<!--PAR:SHOW_FOR_BRAND_SYS_ADMIN_ONLY-->', '', $tpl );
	$tpl = str_replace( '<!--PAR:SHOW_FOR_SYS_ADMIN_ONLY-->', 'display:none; ', $tpl );
}

// Raise errors if any.
$body = '';
if( count( $opCacheMessages)  > 0 ) {
	$body = "onLoad=".
			"'javascript:alert(\"".implode('\n',$opCacheMessages)."\"); ". // \n is literal for JavaScript
			"window.location.replace(\"advanced.php\");'";

}

// Show built HTML page to admin user.
print HtmlDocument::buildDocument( $tpl, true, $body );
