<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once BASEDIR . '/server/secure.php';
	include_once( BASEDIR . '/server/utils/license/license.class.php' );
	include_once( BASEDIR . '/server/regserver.inc.php' );

	ob_start();
	
	$title = BizResources::localize("LIC_RENEW") . ' ' . BizResources::localize("MNU_OVERVIEW" );
	$renewSettings = BizResources::localize("LIC_AUTOMATED_RENEW")  . ' ' . BizResources::localize("LIC_SETTINGS");

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
	
	$productcode = $_POST[ 'productcode' ];
	if ( !$productcode )
	{
		print BizResources::localize("LIC_MISSING_PARAM_PRODUCTCODE");
		renew_buildDoc();
		exit;
	}
	$productname = $_POST[ 'productname' ];
	if ( !$productname )
	{
		print BizResources::localize("LIC_MISSING_PARAM_PRODUCTNAME");
		renew_buildDoc();
		exit;
	}

	$errorMessage = '';
	$color = 'red';
	$flags = 0;
	$status = '';
	$info = Array();
	$serial = $lic->getSerial( $productcode );
	$licenseStatus = $lic->getLicenseStatus( $productcode, $serial, $info, $errorMessage );
	$lic->getLicenseStatusInfo( $licenseStatus, $color, $status, $flags );
	
	$renew = $info[ 'renew' ];

	$bRenewStatus = ( $licenseStatus == WW_LICENSE_ERR_RENEWTIME ) ||  //Has to be renewed now
					( ( $licenseStatus <= WW_LICENSE_OK_MAX ) && ( $renew && $renew != -1 )); //Renew license

	if ( !$bRenewStatus )
	{
		print '<h2>' . BizResources::localize("LIC_RENEW") . '</h2>';
		print BizResources::localize("LIC_INVALID_STATUS_TO_RENEW") . ': ' . $licenseStatus;
		if ( $errorMessage )
			print ' (' . $errorMessage . ')';
		print '<br/><br/><a href="javascript:history.go(-1)">' . BizResources::localize('ACT_BACK') . '</a>';
		renew_buildDoc();
		exit;
	}
	
	$errorMessage = '';
	$help = '';
	//Avoid someone using an invalid configuration (because configuration parameters are sent via key2 and key3)
   	$warn = false;
	if ( !$lic->wwTest( $errorMessage, $help, $warn ) )
	{
		print BizResources::localize("LIC_ERR_SCENT_INSTALL");
		$url = SERVERURL_ROOT.INETROOT.'/server/wwtest/index.htm';
		print "<br/><br/><a href='$url' target='_top'>wwTest</a>";
		renew_buildDoc();
		exit;
	}
	
?>

	<form method='POST' action='#' name='steps'>
		<input type='hidden' name='productcode' value='<?php echo $productcode; ?>'>
		<input type='hidden' name='productname' value="<?php echo htmlspecialchars($productname); ?>">
		<input type='hidden' name='step'>
	</form>

	<form method='POST' action='contactinfo.php' name='contactinfo'>
		<input type='hidden' name='backURL' value='renew.php'>
		<input type='hidden' name='backDesc' value="<?php echo htmlspecialchars($title); ?>">
		<input type='hidden' name='productcode' value='<?php echo $productcode; ?>'>
		<input type='hidden' name='productname' value="<?php echo htmlspecialchars($productname); ?>">
	</form>

	<!-- preload //-->
	<img src='images/progress.gif' width='0' height='0' alt=''> 

	<script language='Javascript' type='text/Javascript'>
	<!--
		function geto(name)
		{
			var d = document;
			if ( d.all )
				return d.all[ name ];
			else
				return d.getElementById( name );
		}

		function renew( v )
		{
			var f = document.forms.steps;
			f.step.value = v;
			f.submit();
		}			
		function renewBusy( v )
		{
			var o = geto( 'busy' );
			if ( o ) {
				o.innerHTML = "<h2><?php echo BizResources::localize("LIC_RENEW"); ?></h2><image src='images/progress.gif'><br/><?php echo BizResources::localize('LIC_PLEASE_WAIT'); ?>";
			}
			var o = geto( 'entry' );
			if ( o ) {
				o.innerHTML = "";
			}
			renew( v );
		}
		function contactinfo()
		{
			document.forms.contactinfo.submit();
		}
	//-->
	</script>

<?php
	$step = @$_POST[ 'step' ];
	if ( !$step )
	{
		$proxyInfo = $lic->getProxyParameters();
		print '<div id="entry">';
		print '<h2>' . $title . '</h2>';
		print BizResources::localize("LIC_RENEW_CHOICES");
		print '<br/>1)&nbsp;<a href="javascript:renewBusy(1)">' . BizResources::localize("LIC_AUTOMATED_RENEW") . '</a> (<a href="javascript:contactinfo()">' . $renewSettings . '</a>';
		if ( @$proxyInfo[ 'host' ] ) {
			print ', <a href="javascript:renewBusy(3)">' . BizResources::localize('ACT_TEST') . '</a>';
		}
		print ')';
		print '<br/>2)&nbsp;<a href="javascript:renew(4)">' . BizResources::localize('LIC_RENEW_BROWSER') . '</a>';
		print '<br/><br/><a href="javascript:history.go(-1)">' . BizResources::localize('ACT_BACK') . '</a>';
		print '<br/></div>';
		print '<div id="busy"></div>';
		renew_buildDoc();
		exit;
	}
	
	switch( $step )
	{
		case 1:
		{
			print '<h2>' . BizResources::localize("LIC_RENEW" ) . '</h2>';

			$errorMessage = '';
			$bConnection = $lic->SmartRegContact( $productcode, $errorMessage );
		
			if ( $bConnection )
			{
				$newLicenseStatus = 0;
				$newErrorMessage = '';
				$force = true;
		
				$bRenewed = $lic->tryAutoRenew( $productcode, $force, $newLicenseStatus, $newErrorMessage );
		
				//If the auto renew has updated the license
				//Return the most recent data (recursive call)
				if ( $bRenewed )
				{
					print BizResources::localize("LIC_RENEWED");
					print '<br/>';
				}
				else
				{
					print BizResources::localize("LIC_NOT_RENEWED");
					if ( $newErrorMessage )
						print '<br/>' . $newErrorMessage;
					print '<br/><br/><a href="javascript:contactinfo()">' . $renewSettings . '</a>';
					print '<br/><a href="javascript:renew(0)">' . $title . '</a>';
				}
			}
			else
			{
				print BizResources::localize("LIC_NO_CONTACT" );
				print '<br/>' . $errorMessage;
				print '<br/><br/><a href="javascript:contactinfo()">' . $renewSettings . '</a>';
			}
			print '<br/><a href="index.php">' . BizResources::localize("LIC_LICENSE_STATUS") . '</a>';
			renew_buildDoc();
			exit;
			break;
		}
		case 3:
		{
			print '<h2>' . BizResources::localize("LIC_INTERNET_CONNECTION_TEST") . '</h2>';

			$errorMessage = '';
			$bConnection = $lic->SmartRegContact( $productcode, $errorMessage );
		
			if ( $bConnection )
			{
				print BizResources::localize("LIC_CONTACT");
			}
			else
			{
				print BizResources::localize("LIC_NO_CONTACT");
				if ( $errorMessage )
					print '<br/>' . $errorMessage;
			}
			print '<br/><br/><a href="javascript:contactinfo()">' . $renewSettings . '</a>';
			print '<br/><a href="javascript:renew(0)">' . $title . '</a>';
			print '<br/><a href="index.php">' . BizResources::localize("LIC_LICENSE_STATUS") . '</a>';
			renew_buildDoc();
			exit;
			break;
		}
		case 4:
		{

	//Force the image to be reloaded when visiting the page again
	$t = time();
	$step = 10;

	$installationcode = $lic->getInstallationCode( $errorMessage );
	if ( $installationcode === false )
	{
		print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
		print $errorMessage;
		renew_buildDoc();
		exit;
	}

	$localURL = SERVERURL_ROOT.$_SERVER['PHP_SELF'];
	$localURL = str_replace( "renew.php", "getlicense.php", $localURL );
	$clientname = $lic->getServerNameOrAddr();

	print "<h2>" . BizResources::localize("LIC_RENEW") . "</h2>";
	print BizResources::localize("LIC_TESTING_INTERNET_ACCESS");
	print "<br/>" . BizResources::localize("LIC_RENEWING_AFTER_CONNECT");
	print "<br/>";
	print "<br/><a href='javascript:goBack()'>" . BizResources::localize('ACT_BACK') . "</a>";
	print "<br/><a href='javascript:pageReload()'>" . BizResources::localize("ACT_RETRY") . "</a>";
	
	$renewStartStr = BizResources::localize("LIC_RENEW_WILL_START_DONT_INTERRUPT");
	$renewStartStr = str_replace( '\'', '\\\'', $renewStartStr );

	print "<form method='POST' action='#' name='reload'>\n";
	foreach( $_POST as $k => $v )
		print "<input type='hidden' name='$k' value=" . htmlspecialchars( stripslashes( $v )) . ">\n";
	print "</form>";
	
?>

	
	<form method='post' action='<?php echo ACTIVATEURL; ?>' name='theForm'>
	<input type='hidden' name='step' value='<?php echo $step; ?>'>
	<input type='hidden' name='manual' value='0'>
	<input type='hidden' name='version' value='<?php echo PRODUCTVERSION; ?>'>
	<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION; ?>'>
	<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION; ?>'>
	<input type='hidden' name='installationcode' value='<?php echo $installationcode; ?>'>
	<input type='hidden' name='concurrentseats' value='renew'>
	<input type='hidden' name='localURL' value='<?php echo $localURL; ?>'>
	<input type='hidden' name='serial' value='<?php echo $serial; ?>'>
	<input type='hidden' name='productcode' value='<?php echo $productcode; ?>'>
	<input type='hidden' name='productname' value='<?php echo htmlspecialchars($productname); ?>'>
	<input type='hidden' name='localtime' value='<?php echo date( 'Ymd H:i:s' ); ?>'>
	<input type='hidden' name='clientip' value='<?php echo $_SERVER[ 'REMOTE_ADDR' ]; ?>'>
	<input type='hidden' name='clientname' value='<?php echo htmlspecialchars($clientname); ?>'>
	<input type='hidden' name='mode' value='R1'>
	<input type='image' width='0' height='0' alt=''>

<?php
	
	$contactFields = $lic->getContactFields();
	$contactValues = $lic->getContactParameters();
	foreach( $contactFields as $f )
	{
		$cv = '';
		if ( isset( $contactValues[ $f ] ))
			$cv = $contactValues[ $f ];
		print "<input type='hidden' name='$f' value='" . htmlspecialchars($cv) . "'>\n";
	}
?>
	</form>

	<script language='Javascript' type='text/Javascript'>
	<!--
	function conError()
	{
		alert( '<?php echo BizResources::localize("LIC_NO_CONNECTION_CHECK_ACCESS_OR_GO_OFFLINE"); ?>' );
		history.go(-1);
	}
	function conSuccess()
	{
		if ( confirm( '<?php echo $renewStartStr; ?>' ))
		{
			var f = document.forms.theForm;
			f.submit();
		}
	}
	function pageReload()
	{
		document.forms.reload.submit();
	}
	function goBack()
	{
		var f = document.forms.steps;
		f.step.value = 0;
		f.submit();
	}
	//-->
	</script>		
	<img src="<?php echo TESTIMAGEURL . '?t=' . $t; ?>" onError='conError()' onLoad='conSuccess()' width='0' height='0'/>

<?php
			break;
		}
	}

	renew_buildDoc();

function renew_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}
	
?>