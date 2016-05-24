<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR . '/server/secure.php';
	include_once( BASEDIR . '/server/utils/license/license.class.php' );
	include_once( BASEDIR . '/server/regserver.inc.php' );

	ob_start();

	$lic = new License();

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
	
	$backURL = @$_POST[ 'backURL' ];
	$backDesc = @$_POST[ 'backDesc' ];
	$change = @$_POST[ 'change' ];
	if ( !$change )
	{
?>
		<script language='Javascript' type='text/Javascript'>
		<!--
			function validate()
			{
				var f = document.forms.theForm;
				if ( !validateContact( f ))
					return false;
				return true;
			}
			function goBack()
			{
				var f = document.forms.theForm;
				f.action = f.backURL.value;
				f.submit();
			}
		//-->
		</script>

		<script language='Javascript' type='text/Javascript' src='validatecontact.js.php'>
		</script>

		<form method='post' action='#' name='theForm' onSubmit='return validate();'>
			<input type='hidden' name='change' value='1'>
			<input type='image' width='0' height='0' alt=''>
			
<?php
			foreach( $_POST as $k => $v )
				print "<input type='hidden' name='$k' value=\"" . htmlspecialchars($v) . "\">\n";

			print "<table>";
			print "<tr><td colspan='2'><h2>" . 'Proxy server' . ':</h2></td></tr>';
			$proxyFields = $lic->getProxyFields();
			$contactFields = $lic->getContactFields();
			$proxyValues = $lic->getProxyParameters();
			$contactValues = $lic->getContactParameters();
			foreach( $proxyFields as $f )
			{
				$v = '';
				if ( isset( $proxyValues[ $f ] ))
					$v = $proxyValues[ $f ];
				print "<tr><td>$f:</td>";
				print "<td><input name='$f' value='$v'></td>";
				print "</tr>\n";
			}
			print "<tr><td colspan='2'><h2>" . BizResources::localize("LIC_CONTACTINFO" ) . ':</h2></td></tr>';
			foreach( $contactFields as $f )
			{
				$v = '';
				if ( isset( $contactValues[ $f ] ))
					$v = $contactValues[ $f ];
				print "<tr><td>$f:</td>";
				if ( $f == 'country' )
				{
					print "<td><select name='$f'>";
					print "<option value=''>" . BizResources::localize("LIC_ENTER_COUNTRY") . "</option>";
					print "</select></td>";
				}
				else
					print "<td><input name='$f' value='$v'></td>";
				print "</tr>\n";
			}
			print "</table>\n";
			print "<br/><input type='submit' value='" . BizResources::localize("BUT_UPDATE") . "'>\n";
			print "<input type='reset' value='" . BizResources::localize("BUT_RESET") . "'>\n";
			print "<input type='hidden' name='orgemail' value='" . $contactValues[ 'email' ] . "'>\n";
		print "</form>\n";
		if ( $backURL && $backDesc )
			print "<br/><a href='javascript:goBack()'>$backDesc</a>";
		print "\n\n<script language='Javascript' type='text/Javascript'>\n";
		print "<!--\n";
		print "var f = document.forms.theForm;\n";
		print "loadCountries( f, '" .  $contactValues[ 'country' ] . "' );\n";
		print "//-->\n";
		print "</script>\n";
	}
	else
	{
		$lic->setProxyParameters( $_POST );
		$lic->setContactParameters( $_POST );
		
		print '<h2>' . BizResources::localize("LIC_CONTACTINFO" ) . '</h2>';
		print BizResources::localize("LIC_CONTACTINFO_UPDATED" );
		
		print "<form method='POST' action='$backURL' name='theForm'>";
		print "<input type='image' width='0' height='0' alt=''>";
		foreach( $_POST as $k => $v )
			print "<input type='hidden' name='$k' value=\"" . htmlspecialchars($v) . "\">\n";
		print "</form>";

		print '<br/>';
		if ( $backURL && $backDesc )
			print "<br/><a href='javascript:document.forms.theForm.submit()'>$backDesc</a>";
		print '<br/><a href="index.php">License status</a>';
	}
	
	//==================================
	
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
	
?>