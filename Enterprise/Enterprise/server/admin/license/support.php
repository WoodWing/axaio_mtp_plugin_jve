<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR . '/server/utils/license/license.class.php';
	require_once BASEDIR . '/server/secure.php';

	$debug = 0;
	ob_start();
?>

<script language='Javascript' type='text/Javascript'>
<!--
function validate()
{
	var f = document.forms.theForm;
	if ( f.name.value.length == 0 )
	{
		alert('<?php echo BizResources::localize("LIC_ENTER_NAME"); ?>');
		f.name.focus();
		return false;
	}
	if ( f.email.value.length == 0 )
	{
		alert('<?php echo BizResources::localize("LIC_ENTER_EMAIL"); ?>');
		f.email.focus();
		return false;
	}
	return true;
}
//-->
</script>

<?php
	print "<h2>" . BizResources::localize("LIC_LICENSE_SUPPORT") . "</h2>";
	print BizResources::localize("LIC_USE_SUPPORT_FORM_TO_SEND_LICENSEINFO");

	$supporturl = SUPPORTURL;
	print "<form method='POST' action='$supporturl' onSubmit='return validate();' name='theForm'>";
	print "<br>";
	print "<table>";
	print "<tr><td colspan='2'>" . BizResources::localize("OBJ_GENERAL_INFORMATION") . "</td></tr>";
	print "<tr><td>" . BizResources::localize("LIC_ENTER_NAME") . "</td><td><input name='name'></td></tr>";
	print "<tr><td>" . BizResources::localize("LIC_ENTER_EMAIL") . "</td><td><input name='email'></td></tr>";
//	print "<tr><td colspan='2'>" . BizResources::localize("OBJ_SPECIFIC_INFORMATION") . "</td></tr>";
	print "<tr><td valign='top'>" . BizResources::localize("LIC_ADDITIONAL_INFO") . "</td><td><textarea name='supportinfo' cols='45' rows='10'></textarea></td></tr>";
	
	
	
	print "</table>";

	$lic = new License();
	$licstr = new LicenseString();
	$keystr = '';
	$errorstr = '';
	for ( $key=1; $key<=3; $key++ )
	{
		$keysrc = $lic->getKeySource( $key );
		if ( $keysrc === false )
		{
			$wwl_error = $lic->getErrorCode();
			$errorstr = BizResources::localize("LIC_ERR_PREPARING_LICENSEINFO") . " " . "(S" . $wwl_error. ")";
			break;
		}

/*
		if ( $debug ) print "<br>keysrc($key)=$keysrc";
		$keydb = $lic->getLicenseField( "key$key" );
		if ( $debug ) print "<br>keydb($key)=$keydb";
	//	print "<br>key1db=$key1db (" . wwl_decrypt( $key1db ) . ")";

		$keysrcenc = $licstr->wwl_encrypt( $keysrc );
		if ( $keysrcenc === false )
		{
			$errorstr = "Encrypt error " . $licstr->getError();
			break;
		}
*/

		if ( $debug ) print "key ($key) = $keysrc";
		if ( $keystr )
			$keystr .= "#";
		$keystr .= $keysrc;
	}
	
	if ( $errorstr )
	{
		print "<input type='hidden' name='error' value='" . htmlspecialchars($errorstr) . "'>\n";
	}
	else
	{
		$keystr = $licstr->wwl_encrypt( $keystr, 2 ); //2: simple encryption to avoid long strings
	
		print "<input type='hidden' name='keys' value='$keystr'>\n";
	
		for ( $key=1; $key<=3; $key++ )
		{
			$dbkey = $lic->getLicenseField( "key$key" );
			if ( $dbkey )
				print "<input type='hidden' name='key$key' value='$dbkey'>\n";
		}
		$local = $lic->getLicenseField( "local" );
		if ( $local )
			print "<input type='hidden' name='local' value='$local'>\n";
		$productcodes = $lic->getLicenseField( "productcodes" );
		if ( $productcodes )
		{
			print "<input type='hidden' name='productcodes' value='$productcodes'>\n";
			$productcodesArr = explode( '|', $productcodes );
			foreach( $productcodesArr as $pc )
			{
				$pcval = $lic->getLicenseField( $pc );
				if ( $pcval )
					print "<input type='hidden' name='$pc' value='$pcval'>\n";

				$errorMessage = '';
				$info = Array();
				$serial = $lic->getSerial( $pc );
				$pcname = $lic->getName( $pc );
				if ( $serial && $pcname )
				{
					$licenseStatus = $lic->getLicenseStatus( $pc, $serial, $info, $errorMessage );
					print "<input type='hidden' name='${pc}_status' value='$licenseStatus'>\n";
					if ( $errorMessage )
						print "<input type='hidden' name='${pc}_error' value='" . htmlspecialchars( $errorMessage ) . "'>\n";
				}
			}
		}
	}
	
	print "<input type='hidden' name='serverversion' value='" . SERVERVERSION . "'>\n";

	print "<br><input type='submit' value='" . BizResources::localize("ACT_SUBMIT") . "'>";
?>
</form>

<script language='Javascript' type='text/Javascript'>
<!--
	function retry() {
		window.location.reload( true );
	}

//-->
</script>

<?php
	$onSuccesStr = BizResources::localize("LIC_BROWSER_OK_TO_VALIDATE");
	$onErrorStr = BizResources::localize("LIC_BROWSER_UNABLE_TO_CONTACT_REGSERVER");
	$onErrorRetryStr = BizResources::localize("ACT_RETRY");

	$strBrowserTest = BizResources::localize("LIC_BROWSER_CONNECTION_STATUS") . "<br><span id='test'><img src='images/yellow.gif' width='10' height='10'> ";
	$strBrowserTest .= BizResources::localize("LIC_TESTING_INTERNET_ACCESS");
	$strBrowserTest .= "</span>";

	print "<hr width='25%' align='left'>\n";
	print $strBrowserTest;
	print "<script language='Javascript' type='text/Javascript' src='connectiontest.js'>\n";
	print "</script>\n";
	$t = time();
	print "<img src='" . TESTIMAGEURL . "?t=$t>' onError='conError(\"$onErrorStr\", \"$onErrorRetryStr\")' onLoad='conSuccess(\"$onSuccesStr\")' width='0' height='0'>\n";

	$txt = ob_get_contents();
	ob_end_clean();


	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';

	//print $txt;
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
?>