<?php
	include_once ( "../../../config/config.php" );
	require_once ( BASEDIR . '/server/secure.php' );
	include_once ( BASEDIR . '/server/utils/license/license.class.php' );

	ob_start();

	$lic = new License();
	
	$title = BizResources::localize("LIC_REMOVE_LICENSE");
	$msgsingle = BizResources::localize("LIC_CLICK_REMOVE_TO_REMOVE_ONE");
	$msgall = BizResources::localize("LIC_CLICK_REMOVE_TO_REMOVE_ALL");
	$msgwarning = BizResources::localize("LIC_WARN_TO_UNREGISTER_FIRST");
	
	$productcode = @$_POST[ 'productcode' ];
	if ( $productcode )
	{
		if ( !isset( $_POST[ 'doit' ] ) || !$_POST[ 'doit' ] )
		{
			print "<h2>$title</h2>\n";
			print $msgsingle;
			print '<br>';
			print nl2br( $msgwarning );
?>
			<form method='POST' action='#'>
				<input type='hidden' name='doit' value='1'>
				<input type='hidden' name='productcode' value='<?php echo $productcode; ?>'>
				<br><input type='submit' value='Remove'>
			</form>
<?php
			print "<br><br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS")  . "</a>";
			removelicense_buildDoc();
			exit;
		}	
	}
	else
	{
		//If no license installed yet: everyone may install the first license (the SCE Server license)
		//Once a license has been installed, only admin users may do something here...
		$hasLicense = $lic->hasLicense();
		if ( $hasLicense ) {
			$SCEAppserial = $lic->getSerial( PRODUCTKEY );
			$info = array();
			$errMsg = '';
			$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errMsg );
			//The user should only be an administrator if he can logon as an administrator
			//In case of an license error, he is not able to logon as administrator.
			if ( $licenseStatus <= WW_LICENSE_OK_MAX ) {
				require_once BASEDIR.'/server/secure.php';
				$ticket = checkSecure( 'admin' ); // Security: should be admin user
			}
		}

		if ( !isset( $_POST[ 'doit' ] ) || !$_POST[ 'doit' ] )
		{
			print "<h2>$title</h2>\n";
			print $msgall;
			print '<br>';
			print nl2br( $msgwarning );
			print "<form method='POST' action='#'>";
			print "<input type='hidden' name='doit' value='1'>";
			print "<br><input type='submit' value='Remove'>";
			print "</form>";
			print "<br><br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
			removelicense_buildDoc();
			exit;
		}	
	}
	print "<h2>$title</h2>";
	
	if ( !$lic->removeLicense( $productcode )) {
		print BizResources::localize("LIC_ERR_REMOVING_LICENSE") . '(S' . $lic->getErrorCode() . ')';
	} else {
		print BizResources::localize("LIC_LICENSE_REMOVED");
	}

	print "<br><br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
	
removelicense_buildDoc();
function removelicense_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}

?>