<?php

	//This module is meant for exceptional cases, to resolve error WWL_ERR_FILESTORE_DB_MISMATCH, 2054
	//Should not be delivered by default

	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR . '/server/secure.php';
	include_once( BASEDIR . '/server/utils/license/license.class.php' );

	ob_start();

	$lic = new License();

	//If no license installed yet: everyone may install the first license (the SCE Server license)
	//Once a license has been installed, only admin users may do something here...
	$hasLicense = $lic->hasLicense();
	if ( !$hasLicense )
	{
		print "No license";
		sync_buildDoc();
		exit;
	}

	$SCEAppserial = $lic->getSerial( PRODUCTKEY );
	$info = array();
	$errMsg = '';
	$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errMsg );
	if ( 0 &&  $licenseStatus != WW_LICENSE_OK_TMPCONFIG )
	{
		print "Invalid status";
		sync_buildDoc();
		exit;
	}
	
	if ( !isset( $_POST[ 'doit' ] ) || !$_POST[ 'doit' ] )
	{
		print "<h2>" . BizResources::localize("LIC_SYNC_LICENSES") . "</h2>";
		print BizResources::localize("LIC_CLICK_TO_SYNC"); 
		print "<form method='POST' action='#'>";
		print "<input type='hidden' name='doit' value='1'>";
		print "<br><input type='submit' value='" . BizResources::localize("LIC_SYNCHRONIZE") . "'>";
		print "</form>";
		print "<br><br><a href=\"index.php\">" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
		sync_buildDoc();
		exit;
	}
	
		print "<h2>" . BizResources::localize("LIC_SYNC_LICENSES") . "</h2>";
	
	$magic = $lic->getKeySource( 1 );
	$magic .= "pipo";
	$magic = md5( $magic );
	$err = '';
	if( !$lic->synch( $magic, $err ) ) {
		print $err;
	} else {
		print "OK";
	}
	print "<br><br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";

sync_buildDoc();
function sync_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}
	
?>