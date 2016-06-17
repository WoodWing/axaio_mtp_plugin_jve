<?php
	require_once dirname(__FILE__).'/../../../config/config.php';
	require_once ( BASEDIR . '/server/secure.php' );
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
		reclaim_buildDoc();
		exit;
	}
	$productname = $_POST[ 'productname' ];
	if ( !$productname )
	{
		print BizResources::localize("LIC_MISSING_PARAM_PRODUCTNAME");
		reclaim_buildDoc();
		exit;
	}

	$errorMessage = '';
	$color = 'red';
	$flags = 0;
	$status = '';
	$info = Array();
	$appserial = $lic->getSerial( $productcode );
	$licenseStatus = $lic->getLicenseStatus( $productcode, $appserial, $info, $errorMessage );
	$lic->getLicenseStatusInfo( $licenseStatus, $color, $status, $flags );

	if ( $licenseStatus != WW_LICENSE_OK_TMPCONFIG )
	{
		print BizResources::localize("LIC_INVALID_STATUS_TO_RECLAIM") . ": " . "$licenseStatus";
		reclaim_buildDoc();
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
		reclaim_buildDoc();
		exit;
	}
	
	$mode = $_POST[ 'mode' ];
	if ( !$mode )
	{
?>
		<script language='Javascript' type='text/Javascript'>
		<!--
		function validate( f )
		{
			if ( f.change[0].checked )
			{
				f.mode.value = 'reclaim';
				return true;
			}
			if ( f.change[1].checked )
			{
				if ( confirm( '<?php echo BizResources::localize("LIC_ASK_REMOVE_LICENSE_FOR_NEW") ?>' ))
				{
					f.mode.value = 'new';
					return true;
				}
				return false;
			}
			alert('<?php echo BizResources::localize("LIC_PLEASE_CHOOSE");?>');
			return false;
		}
		//-->
		</script>
		
<?php
		print "<h2>" . BizResources::localize("LIC_RECLAIM") . "</h2>";
		print BizResources::localize("LIC_RECLAIM_AFTER_CHANGING_SYSTEM");
		print "<br><br>";
		print BizResources::localize("LIC_SPECIFY_CONFIGURATION_CHANGE");
		print "<form name='theForm' method='POST' onsubmit='return validate( this );' action='#'>";
		print "<table>";
		print "<tr><td valign='top'>";
		print "<input type='radio' name='change' value='reclaim' onClick='this.form.submitbutton.disabled=false;'></td><td>";
		print BizResources::localize("LIC_ONE_SYSTEM");
		print "<br>";
		print "<ul>\n";
		print "<li>" . BizResources::localize("LIC_MOVE_ALL_LICENSES") . "</li>\n";
		print "<li>" . BizResources::localize("LIC_NEW_REPLACES_AND_STILL_ONE_SYSTEM") . "</li>\n";
		print "<li>" . BizResources::localize("LIC_FREE_OF_CHARGE") . "</li>\n";
		print "<li>" . BizResources::localize("LIC_SITUATION_CAN_OCCUR_ON_RESTORE") . "</li>\n";
		print "</ul>\n";
		print "</td></tr>";
		print "<tr><td valign='top'><input type='radio' name='change' value='new' onClick='this.form.submitbutton.disabled=false;'/></td><td>";
		print BizResources::localize("LIC_TWO_OR_MORE_SYSTEMS");
		print "<br>";
		print "<ul>";
		print "<li>" . BizResources::localize("LIC_SCENT_WORKS_ON_BOTH") . "</li>";
		print "<li>" . BizResources::localize("LIC_PREVIOUS_CONFIG_STAY_AVAILABLE") . "</li>";
		print "<li>" . BizResources::localize("LIC_FREE_OF_CHARGE_ONLY_IF_FREE_LICENSES") . "</li>";
		
		print "</ul></td></tr>";
		print "</table>";
		print BizResources::localize("LIC_ADDITIONAL_INFO");
			
?>
		<textarea name='supportinfo' rows='5' cols='45'></textarea>
		<br>
		<br>
			
			<input name='submitbutton' type='submit' value='<?php echo BizResources::localize("LIS_NEXT");?>'>
			<input name='cancelbutton' type='button' value='<?php echo BizResources::localize("ACT_CANCEL");?>' onClick="if (confirm( '<?php echo addslashes(BizResources::localize("LIC_ASK_CANCEL_LICENSE_INSTALLATION")) ?>' )) location.href='index.php';">
			<input type='hidden' name='productcode' value='<?php echo $productcode;?>'>
			<input type='hidden' name='productname' value='<?php echo htmlspecialchars($productname);?>'>
			<input type='hidden' name='mode' value=''>
		</form>
<script language='Javascript' type='text/Javascript'>
<!--
	document.forms.theForm.submitbutton.disabled=true;
//-->
</script>
<?php
		reclaim_buildDoc();
		exit;
	}
	
	if ( $mode == 'new' )
	{
		if ( !$lic->removeLicense( $productcode ) )
		{
			print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
			print BizResources::localize("LIC_ERR_REMOVING_LICENSE") . '. (S' . $lic->getErrorCode() . ')';
			reclaim_buildDoc();
			exit;
		}
?>		
		<h2>Get License</h2>
			<form method='post' action='getlicense.php' name='theForm'>
			<input type='hidden' name='productcode' value='<?php echo $productcode;?>'>
			<input type='hidden' name='productname' value='<?php echo htmlspecialchars($productname);?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes(@$_POST[ 'supportinfo' ]));?>">
				<input type='image' width='0' height='0' alt=''>
			</form>
			<script language='Javascript' type='text/Javascript'>
			<!--
				document.forms.theForm.submit();
				
			//-->
			</script>
<?php
	}
	else if ( $mode == 'reclaim' )
	{
?>
		<h2>Reclaim License</h2>
			<form method='post' action='getlicense.php' name='theForm'>
				<input type='hidden' name='productcode' value='<?php echo $productcode;?>'>
				<input type='hidden' name='productname' value='<?php echo htmlspecialchars($productname);?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes(@$_POST[ 'supportinfo' ]));?>">
				<input type='hidden' name='option' value='reclaim'>
				<input type='image' width='0' height='0' alt=''>
			</form>
			<script language='Javascript' type='text/Javascript'>
			<!--
				document.forms.theForm.submit();
				
			//-->
			</script>
<?php
	}
	
reclaim_buildDoc();
function reclaim_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/secure.php';
	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}
	
	
?>