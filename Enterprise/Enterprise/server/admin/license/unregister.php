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
	
	$productcode = $_POST[ 'productcode' ];
	if ( !$productcode )
	{
		print BizResources::localize("LIC_MISSING_PARAM_PRODUCTCODE");
		unregister_buildDoc();
		exit;
	}
	$productname = $_POST[ 'productname' ];
	if ( !$productname )
	{
		print BizResources::localize("LIC_MISSING_PARAM_PRODUCTNAME");
		unregister_buildDoc();
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

	if ( $licenseStatus > WW_LICENSE_OK_MAX )
	{
		print BizResources::localize("LIC_INVALID_STATUS") . ": " . "$licenseStatus";
		unregister_buildDoc();
		exit;
	}

//	if ( $licenseStatus == WW_LICENSE_OK_USERLIMIT )
//	{
//		print "Warning: user limit exceeded.";
//	}

	$errorMessage = '';
	$keystr = $lic->getInstallationCode( $errorMessage );
	if ( $keystr === false )
	{
		print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
		print $errorMessage;
		unregister_buildDoc();
		exit;
	}
	$installationcode = $keystr;

    $localURL = SERVERURL_ROOT.htmlspecialchars( INETROOT.'/server/admin/license/unregister.php' );
	$localURL = str_replace( "unregister.php", "getlicense.php", $localURL );

	$step = 10;
	
	$errorMessage = '';
	$app=''; //SCE Server
	$numlic = $lic->getNumLicenses( $productcode, $errorMessage );
	if ( $numlic === false )
	{
		print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
		print $errorMessage;
		unregister_buildDoc();
		exit;
	}
	if ( $numlic == '*' )
	{
		print "<h2>" . BizResources::localize("LIC_UNREGISTER") . "</h2>";
		print BizResources::localize("LIC_NO_OPTION_TO_UNREGISTER");
		unregister_buildDoc();
		exit;
	}

	if ( $numlic == 0 )
	{
		print "<h2>" . BizResources::localize("LIC_UNREGISTER") . "</h2>";
		print BizResources::localize("LIC_UNREGISTERED");
		print "<br><br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
		unregister_buildDoc();
		exit;
	}
	
	$min = 1;
	$max = $numlic;
	$note = '';
	if ( $min != $max )
		$note = "Valid range: $min-$max";

	$clientname = $lic->getServerNameOrAddr();
	$prodcodes = $lic->getProductcodes();
	$numproducts = count( $prodcodes );
	$isSCEServer = ($productcode == PRODUCTKEY)?'1':'0';
	if ( $isSCEServer )
		$units = 'connections';
	else
		$units = 'users';
	
?>
<script language='Javascript' type='text/Javascript'>
<!--
function geto2(name)
{
	var d = document;
	if ( d.all )
		return d.all[ name ];
	else
		return d.getElementById( name );
}

function validate()
{
	var f = document.forms.theForm;
	var v = f.concurrentseats.value;
	var vv = parseInt( v );
	var minval = 1;
	var maxval = <?php echo $max;?>;
	var numproducts = <?php echo $numproducts;?>;
	var isSCEServer = <?php echo $isSCEServer;?>;
	if ( vv < 1 || vv > maxval )
	{
		alert( '<?php echo BizResources::localize("LIC_NUM_CONCURRENT_USERS"); ?> ' + minval + '-' + maxval + '.' );
		f.concurrentseats.focus();
		return false;
	}
	
	if ( (vv == maxval) && (numproducts > 1) && isSCEServer )
	{
		alert('<?php echo BizResources::localize("LIC_UNREGISTER_OTHERS_FIRST"); ?>' );
		f.concurrentseats.focus();
		return false;
	}
	
	f.submitbutton.disabled = true; //avoid form submitted twice or more...
	f.cancelbutton.disabled = true; //avoid form submitted twice or more...
	var o = geto2( 'progress' );
	if ( o )
	{
		if ( document.all )
			o.innerHTML = '<?php echo BizResources::localize("LIC_PLEASE_WAIT") ?>';
		else
			o.innerHTML = '<?php echo BizResources::localize("LIC_PLEASE_WAIT") ?>' + '<br><img src="images/progress.gif">';
	}
	
	return true;
}
//-->
</script>
<h2>Unregister</h2>
<form method='post' action='<?php echo ACTIVATEURL;?>' name='theForm' onSubmit='return validate();'>
	<input type='hidden' name='step' value='<?php echo $step;?>'>
	<input type='hidden' name='manual' value='0'>
	<input type='hidden' name='installationcode' value='<?php echo $installationcode;?>'>
	<input type='hidden' name='localURL' value='<?php echo $localURL;?>'>
	<input type='hidden' name='serial' value='<?php echo $serial;?>'>
	<input type='hidden' name='productcode' value='<?php echo $productcode;?>'>
	<input type='hidden' name='productname' value='<?php echo htmlspecialchars($productname);?>'>
	<input type='hidden' name='version' value='<?php echo PRODUCTVERSION;?>'>
	<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION;?>'>
	<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION;?>'>
	<input type='hidden' name='localtime' value='<?php echo date( 'Ymd H:i:s' );?>'>
	<input type='hidden' name='clientip' value='<?php echo $_SERVER[ 'REMOTE_ADDR' ];?>'>
	<input type='hidden' name='clientname' value='<?php echo htmlspecialchars($clientname);?>'>
	<input type='hidden' name='mode' value='U1'>
	<input type='hidden' name='access' value='0'>

<?php
	if (strtolower($units) == 'users') {
		print BizResources::localize("LIC_NUM_USERS_TO_UNREGISTER");		
	}
	else {
		print BizResources::localize("LIC_NUM_CONNECTIONS_TO_UNREGISTER");		
	}
	
	print ": <input name='concurrentseats' value='$max' size='3'>";
	print "<br>$note";
?>

	<br><input name='submitbutton' type='submit' value='<?php echo BizResources::localize("LIS_NEXT");?>'>
	<input name='cancelbutton' type='button' value='<?php echo BizResources::localize("ACT_CANCEL");?>' onClick='location.href="index.php";'>
</form>
<div id='progress'>&nbsp;</div>
<img src='images/progress.gif' width='0' height='0' alt='preload'> 

<form method='post' action='#' name='reloadForm'>
	<input type='hidden' name='productcode' value='<?php echo $productcode;?>'>
	<input type='hidden' name='productname' value='<?php echo htmlspecialchars($productname);?>'>
	<input type='image' width='0' height='0' alt=''>
</form>

<script language='Javascript' type='text/Javascript'>
<!--
	document.forms.theForm.concurrentseats.focus();

	function retry() {
		document.forms.reloadForm.submit();
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
	
unregister_buildDoc();
function unregister_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}
