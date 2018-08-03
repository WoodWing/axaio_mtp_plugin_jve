<?php

	ob_start();

	require_once dirname(__FILE__).'/../../../config/config.php';
	include_once( BASEDIR . '/server/utils/license/license.class.php' );
	include_once( BASEDIR . '/server/regserver.inc.php' );
	require_once BASEDIR.'/server/secure.php';

	//Map of known product codes to product names
	//Will only be used in case of offline activation
	//If product code not found, the code will be shown as product name.
	$productcode2name = Array(
		'ContentStationPro' => 'Content Station - Pro Edition',
		'ContentStationBasic' => 'Content Station - Basic Edition',
		'SCID' => 'Smart Connection Enterprise InDesign/InCopy',
	);
	
	$step = isset($_POST[ 'step' ]) ? $_POST[ 'step' ] : '';
	if ( !$step )
		$step = 0;
	$step++;
	
	$test = (isset( $_POST[ 'test' ] ) && $_POST[ 'test' ]) ? 1:0;
	
	$onSuccesStr = BizResources::localize("LIC_BROWSER_OK_TO_VALIDATE");
	$onErrorStr = BizResources::localize("LIC_BROWSER_UNABLE_TO_CONTACT_REGSERVER");
	$onErrorRetryStr = BizResources::localize("ACT_RETRY");

	$lic = new License();
	//If no license installed yet: everyone may install the first license (the SCE Server license)
	//Once a license has been installed, only admin users may do something here...
	$hasLicense = $lic->hasLicense();
	if ( $hasLicense && ( $step <= 11 )) {
		$SCEAppserial = $lic->getSerial( PRODUCTKEY );
		$info = array();
		$errMsg = '';
		$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errMsg );
		//The user should only be an administrator if he can logon as an administrator
		//In case of an license error, he is not able to logon as administrator.
		if ( $licenseStatus <= WW_LICENSE_OK_MAX ) {
			$ticket = checkSecure( 'admin' ); // Security: should be admin user
		}
	}

    $localURL = SERVERURL_ROOT.INETROOT.'/server/admin/license/getlicense.php';
	$clientname = $lic->getServerNameOrAddr();
	
	$allFields = Array( 'installationcode', 
						'supportinfo', 
						'manual', 
						'concurrentseats',
						'localURL',
						'serial',
						'productcode',
						'productname',
						'localtime',
						'clientip',
						'clientname',
						'mode' );

	switch( $step )
	{
		case 1:
		{
			$errorMessage = '';
			$help = '';
			//Avoid someone using an invalid configuration (because configuration parameters are sent via key2 and key3)
	    	$warn = false;
			if ( !$lic->wwTest( $errorMessage, $help, $warn ) )
			{
				print '<h2>' . BizResources::localize("LIC_ERROR_MSG"). '</h2>';
				print BizResources::localize("LIC_ERR_SCENT_INSTALL");
				$url = SERVERURL_ROOT.INETROOT.'/server/wwtest/index.htm';
				print "<br/><br/><a href='$url' target='_top'>wwTest</a>";
				getlicense_buildDoc();
				exit;
			}
		
			$option = isset($_POST[ 'option' ]) ? $_POST[ 'option' ] : '';
			if ( $option == 'reclaim' ) {
				$title = BizResources::localize("LIC_RECLAIM") . " / " . BizResources::localize("LIC_ACTIVATE");
			} else if ( $option == 'renew' ) {
				$title = BizResources::localize("LIC_RENEW") . " / " . BizResources::localize("LIC_ACTIVATE");
			} else {
				$title = BizResources::localize("LIC_ACTIVATE");
			}
			$str = BizResources::localize("LIC_VALIDATE_SERIAL_TO_ACTIVATE") . "\n\n";
			$str .= BizResources::localize("LIC_REQUIRES_INTERNET_TO_VALIDATE") . "\n\n";
			$str .= BizResources::localize("LIC_SEE_BELOW_BROWSER_CAN_VALIDATE") . "\n\n";
			$str .= BizResources::localize("LIC_ALSO_SENDING_SYSTEMCODE") . "\n\n";
			$str .= "<br>" . BizResources::localize("LIC_EXPLANATION_SYSTEMCODE") . "\n\n"; ; 
			$str .= BizResources::localize("LIC_CLICK_NEXT_TO_START_VALIDATION") . "\n\n";
			
			$str2 = BizResources::localize("LIC_ACTIVATE_BY_MAIL_OR_FAX") . "\n\n";
			
			$productcode = isset($_POST[ 'productcode' ]) ? $_POST[ 'productcode' ] : '';
			$productname = isset($_POST[ 'productname' ]) ? $_POST[ 'productname' ] : '';
			$supportInfo = isset($_POST[ 'supportinfo' ]) ? $_POST[ 'supportinfo' ] : '';
			
?>
			<h2><?php echo $title;?></h2>
			<?php echo nl2br($str);?>
			<form method='post' action='#' name='theForm'>
			<input type='hidden' name='step' value='<?php echo $step; ?>'>
			<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
			<input type='hidden' name='access' value='0'>
			<input type='hidden' name='option' value='<?php echo $option?>'>
			<input type='hidden' name='productcode' value='<?php echo htmlspecialchars($productcode)?>'>
			<input type="hidden" name="productname" value="<?php echo htmlspecialchars($productname)?>">
			<input type='checkbox' name='offline' value='1'><?php echo $str2; ?>
			<br><br><input name='submitbutton' type='submit' value='<?php echo BizResources::localize("LIS_NEXT");?>'>
			<input name='cancelbutton' type='button' value='<?php echo BizResources::localize("ACT_CANCEL");?>' onClick='location.href="index.php";'>
			</form>

			<script language='Javascript' type='text/Javascript'>
			<!--
			function retry()
			{
				var f = document.forms.theForm;
				f.step.value = 0;
				//Avoid the form to be send twice or more, with step=0
				try {
					f.retrybutton.disabled = true;
					f.cancelbutton.disabled = true;
					f.submitbutton.disabled = true;
				}
				catch( e )
				{
				}
				f.submit();
			}
			
			//-->
			</script>
<?php

			$strBrowserTest = BizResources::localize("LIC_BROWSER_CONNECTION_STATUS") . "<br><span id='test'><img src='images/yellow.gif' width='10' height='10'/> ";
			$strBrowserTest .= BizResources::localize("LIC_TESTING_INTERNET_ACCESS");
			$strBrowserTest .= "</span>";

			print "<hr width='25%' align='left'>\n";
			print $strBrowserTest;
			print "<script language='Javascript' type='text/Javascript' src='connectiontest.js'>\n";
			print "</script>\n";
			$t = time();
			print "<img src='" . TESTIMAGEURL . "?t=$t>' onError='conError( \"$onErrorStr\", \"$onErrorRetryStr\")' onLoad='conSuccess( \"$onSuccesStr\" )' width='0' height='0'>\n";

			print '<noscript><br><font color="red">' . BizResources::localize("ERR_NO_JAVASCRIPT_SUPPORT") . '</font></noscript>';

			getlicense_buildDoc();
			exit;
		}

		case 2:
		{
			$productcode = isset($_POST[ 'productcode' ]) ? $_POST[ 'productcode' ] : '';
			$productname = isset($_POST[ 'productname' ]) ? $_POST[ 'productname' ] : '';
			$supportInfo = isset($_POST[ 'supportinfo' ]) ? $_POST[ 'supportinfo' ] : '';
			$access = isset($_POST[ 'access' ]) ? $_POST[ 'access' ] : '';
			$option = isset($_POST[ 'option' ]) ? $_POST[ 'option' ] : '';

			$errorMessage = '';
			$installationcode = '';
			if ( $option == 'reclaim' )
				$installationcode = $lic->getInstallationCode( $errorMessage, 
																false,  //Do not install the current source keys yet. Do it when the reclaimed license is received.
																true, //Append the reclaim installation code
																$productcode //Use the license info of this product for the old hostid (key1)
																 );
			else
				$installationcode = $lic->getInstallationCode( $errorMessage );
			if ( $installationcode === false )
			{
				print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
				print $errorMessage;
				getlicense_buildDoc();
				exit;
			}

			//Only present if checked
			$offline = isset($_POST[ 'offline' ]) ? $_POST[ 'offline' ] : '';
			if ( $offline )
			{
				$licstr = new LicenseString();
				$installationcode = $licstr->makeManualLicense( $installationcode );
				$title = BizResources::localize("LIC_OFFLINE_ACTIVATION");
				$str = BizResources::localize("LIC_SEND_EMAIL_FOR_OFFLINE_ACTIVATION") . "<br/><a href='mailto:authorize@woodwing.com'>authorize@woodwing.com</a>.";
				$str .= "\n\n" . BizResources::localize("LIC_IN_EMAIL_SPECIFY_FOLLOWING") . "\n";
				$str .= "- " . BizResources::localize("LIC_PRODUCT_NAME_AND_VERSION") . "\n";
				$str .= "- " . BizResources::localize("LIC_PER_PRODUCT") . ": " . BizResources::localize("LIC_THE_SERIAL") . "\n";
				$str .= "- " . BizResources::localize("LIC_PER_PRODUCT") . ": " . BizResources::localize("LIC_NUM_CONCURRENT_USERS") . "\n";
				$str .= "- " . BizResources::localize("LIC_INSTALLATION_CODE") . ": " . "$installationcode.\n";
				$str .= BizResources::localize("LIC_MAY_USE_COPYPASTE") . "\n\n";
				$str .= BizResources::localize("LIC_SUPPORT_DESK_WILL_GIVE_ACTIVATION_CODE") . "\n\n";
				$str .= BizResources::localize("LIC_ENTER_SERIAL_AND_ACTIVATION_CODE");
?>
				<script language='Javascript' type='text/Javascript'>
				<!--
				function validate()
				{
					var f = document.forms.theForm;
					if ( f.serial.value.length == 0 )
					{
						alert( '<?php echo addslashes(BizResources::localize('LIC_ENTER_SERIAL'));?>' );
						f.serial.focus();
						return false;
					}
					if ( f.license.value.length == 0 )
					{
						alert( '<?php echo addslashes(BizResources::localize('LIC_ENTER_ACTIVATION_CODE'));?>' );
						f.license.focus();
						return false;
					}
					return true;
				}
				//-->
				</script>
				<h2><?php echo $title?></h2>
				<?php echo nl2br($str)?>
				<form method='post' action='#' name='theForm'>
				<input type='hidden' name='step' value='10'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
				<input type='hidden' name='manual' value='1'>
				<input type='hidden' name='productcode' value='<?php echo htmlspecialchars($productcode)?>'>
				<input type='hidden' name='productname' value="<?php echo htmlspecialchars($productname)?>">
				<input type='hidden' name='option' value='<?php echo htmlspecialchars($option)?>'>
				<table>
				<tr><td><?php echo BizResources::localize('LIC_SERIAL');?></td><td><input name="serial" size="50" autocomplete="off"/></td></tr>
				<tr><td valign='top'><?php echo BizResources::localize('LIC_ACTIVATION_CODE');?>:<br><small><?php echo BizResources::localize('LIC_AS_SPECIFIED_BY_ACTIVATION_SERVER');?></small></td><td><input name='license' size='50'></td></tr>
				<tr><td colspan='2' align='center'><input type='submit' value='<?php echo BizResources::localize("LIS_NEXT");?>' onClick='return validate();'><input type='button' value='<?php echo BizResources::localize("ACT_CANCEL");?>' onClick="if ( confirm('<?php echo addslashes(BizResources::localize('LIC_ASK_CANCEL_LICENSE_INSTALLATION'));?>')) location.href='index.php';"></td></tr>
				</table>
				</form>
				<script language='Javascript' type='text/Javascript'>
				<!--
					document.forms.theForm.serial.focus();
				//-->
				</script>
<?php
				getlicense_buildDoc();
				exit;
			}
			else
			{
				//If productcode and productname already known: skip the "get concurrent products step"
				$concurrentproducts = '';
				if ( $productcode && $productname )
				{
					$concurrentproducts = $productcode . '~' . $productname . '~?';
					$step++;
				}

?>
				<form method='post' action='#' name='theForm'>
				<input type='hidden' name='step' value='<?php echo $step?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
				<input type='hidden' name='manual' value='0'>
				<input type='hidden' name='installationcode' value='<?php echo $installationcode?>'>
				<input type='hidden' name='productcode' value='<?php echo htmlspecialchars($productcode)?>'>
				<input type='hidden' name='productname' value="<?php echo htmlspecialchars($productname)?>">
				<input type='hidden' name='concurrentproducts' value="<?php echo htmlspecialchars($concurrentproducts)?>">
				<input type='hidden' name='option' value='<?php echo htmlspecialchars($option)?>'>
				<input type='image' width='0' height='0' alt=''>
				<noscript>
				<font color='red'><?php echo BizResources::localize('ERR_NO_JAVASCRIPT_SUPPORT');?></font>
				<br><input type='submit' value='<?php echo BizResources::localize("LIS_NEXT");?>'>
				</noscript>
				</form>

<?php
				if ( !$access )
				{
					//Force the image to be reloaded when visiting the page again
					$t = time();
?>		
					<script language='Javascript' type='text/Javascript'>
					<!--
					var test = <?php echo $test;?>;
					function conError()
					{
						alert(' <?php echo addslashes(BizResources::localize('LIC_BROWSER_UNABLE_TO_CONTACT_REGSERVER')); ?> ');
						history.go(-1);
						//Safari doesn't seem to really go back, so at least supply a link.
						document.getElementById('back').style.visibility = "visible";
					}
					function conSuccess()
					{
						var f = document.forms.theForm;
						if ( !test )
							f.submit();
					}
					//-->
					</script>		
					<?php echo "<h2>" . BizResources::localize("LIC_INTERNET_CONNECTION_TEST") . "</h2>"; ?>
					<?php echo BizResources::localize("LIC_REGSERVER_CONNECTION_TEST"); ?>
					<?php echo "<br>" . BizResources::localize("LIC_WHEN_CONNECTED_SHOWING_PAGE"); ?>
					<br><img src='images/progress.gif'>
					<img src="<?php echo TESTIMAGEURL . '?t=' . $t?>" onError='conError("<?php echo $onErrorStr;?>", "<?php echo $onErrorRetryStr; ?>")' onLoad='conSuccess("<?php echo $onSuccesStr;?>")' width='0' height='0'>
					<div id='back' style='visibility:hidden'><a href='javascript:history.go(-1)'><?php echo BizResources::localize("ACT_BACK");?></a></div>
					
<?php
				}
				if ( $access )
				{
?>
					<script language='Javascript' type='text/Javascript'>
					<!--
						var test = <?php echo $test;?>;
						if ( !test )
							document.forms.theForm.submit();
					//-->
					</script>	
<?php
				}
				getlicense_buildDoc();
				exit;
			}
		}
		case 3:
		{
			$installationcode = isset($_POST[ 'installationcode' ]) ? $_POST[ 'installationcode' ] : '';
			$productcode = isset($_POST[ 'productcode' ]) ? $_POST[ 'productcode' ] : '';
			$productname = isset($_POST[ 'productname' ]) ? $_POST[ 'productname' ] : '';
			$supportInfo = isset($_POST[ 'supportinfo' ]) ? $_POST[ 'supportinfo' ] : '';
			$option = isset($_POST[ 'option' ]) ? $_POST[ 'option' ] : '';
?>
			<h2> <?php echo BizResources::localize("LIC_FETCHING_PRODUCTS"); ?> </h2>
			<?php echo BizResources::localize("LIC_PLEASE_WAIT"); ?>
			<br/><img src='images/progress.gif'/>
			<form method='post' action='<?php echo ACTIVATEURL?>' name='theForm'>
				<input type='hidden' name='step' value='<?php echo $step?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
				<input type='hidden' name='localURL' value='<?php echo $localURL?>'>
				<input type='hidden' name='installationcode' value='<?php echo $installationcode?>'>
				<input type='hidden' name='productcode' value='<?php echo htmlspecialchars($productcode)?>'>
				<input type='hidden' name='productname' value="<?php echo htmlspecialchars($productname)?>">
				<input type='hidden' name='version' value='<?php echo PRODUCTVERSION?>'>
				<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION?>'>
				<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION?>'>
				<input type='hidden' name='platform' value="<?php echo htmlspecialchars(PHP_OS . '/' . PHP_VERSION . '/' . DBTYPE); ?>"/>
				<input type='hidden' name='mode' value='concurrentproducts'>
				<input type='image' width='0' height='0' alt=''>
				<noscript>
					<input type='submit' value='<?php echo BizResources::localize("LIS_NEXT");?>'>
				</noscript>
			</form>
			<script language='Javascript' type='text/Javascript'>
			<!--
				var test = <?php echo $test;?>;
				if ( !test )
					document.forms.theForm.submit();
			//-->
			</script>
<?php
			getlicense_buildDoc();
			exit;
		}
		
		case 4:
		{
			$concurrentproducts = isset($_POST[ 'concurrentproducts' ]) ? $_POST[ 'concurrentproducts' ] : '';
			if ( $concurrentproducts ) {
				$prodarr = explode( '^', $concurrentproducts );
				$prodarr = filterOutNotSupportedProducts( $prodarr );
			}
			$productindex = isset($_POST[ 'productindex' ]) ? $_POST[ 'productindex' ] : '';
			$productcode = isset($_POST[ 'productcode' ]) ? $_POST[ 'productcode' ] : '';
			$supportInfo = isset($_POST[ 'supportinfo' ]) ? $_POST[ 'supportinfo' ] : '';
			$datetime= isset($_POST[ 'datetime' ]) ? $_POST[ 'datetime' ] : '';
			$installationcode = isset($_POST[ 'installationcode' ]) ? $_POST[ 'installationcode' ] : '';
			$option = isset($_POST[ 'option' ]) ? $_POST[ 'option' ] : '';
			$concurrentseats = isset($_POST[ 'concurrentseats' ]) ? trim($_POST[ 'concurrentseats' ]) : '';
			if ( !$concurrentseats )
				$concurrentseats = ''; //Initially empty (instead of '*')
			$error = isset($_POST[ 'error' ]) ? $_POST[ 'error' ] : '';
			
			$license = $lic->getLicense( PRODUCTKEY );
			
			$serial = isset($_POST[ 'serial' ]) ? trim($_POST[ 'serial' ]) : '';
			$availableseats = isset($_POST[ 'availableseats' ]) ? $_POST[ 'availableseats' ] : '';
			if ( $availableseats )
			{
				$availableseatsarr = explode( '^', $availableseats );
				$arr = Array();
				foreach( $availableseatsarr as $a )
				{
					$tmp = explode( '~', $a );
					$arr[ $tmp[0] ] = $tmp[1];
				}
				$availableseats = isset($arr[ $productcode ]) ? $arr[ $productcode ] : '';
			}
			$availableseatsunknown = 100000;
			$maxstr = '';
			if ( !$availableseats && $availableseats != '0' )
				$availableseats = $availableseatsunknown;
			else
			{
				if ( $availableseats > 1 ) {
					$maxstr = $availableseats . " " . BizResources::localize('LIC_CONNECTIONS_AVAILABLE');
				} else if ( $availableseats == 1 ) {
					$maxstr = "1 " . BizResources::localize('LIC_CONNECTIONS_AVAILABLE');
				} else {
					$maxstr = "<font color='red'>" . BizResources::localize('LIC_NO_CONNECTIONS_AVAILABLE') . "</font>";
					$concurrentseats = 0;
				}
				$maxstr .= "<br/>";
			}
			
			$t = time();
			$datetimearr = Array( date( 'H:i', $t), date( 'Y-m-d', $t));
			$serverarr = Array( BizResources::localize('LIC_AS') );
			$servermsg = htmlspecialchars(BizResources::localize("LIC_ASDSTIME", true, $serverarr));
			$servermsg = str_replace( "\n", '\n', $servermsg );
?>		
			<script language='Javascript' type='text/Javascript'>
			<!--
			
			var bSubmit = false;
			
			var bLicense = <?php echo $license ? '1':'0'; ?>;
			
			function geto(name)
			{
				var d = document;
				if ( d.all )
					return d.all[ name ];
				else
					return d.getElementById( name );
			}

			function validate()
			{
				var min = 1;
				var max = <?php echo $availableseats?>;
				var maxunknown = <?php echo $availableseatsunknown?>;
				var f = document.forms.theForm;
				if ( f.productcode.selectedIndex == 0 )
				{
					alert( '<?php echo addslashes(BizResources::localize('LIC_SELECT_PRODUCT')); ?>');
					f.productcode.focus();
					return false;
				}
				f.productname.value = f.productcode.options[ f.productcode.selectedIndex ].text;
				if ( f.serial.value.length==0 )	
				{
					alert( '<?php echo addslashes(BizResources::localize('LIC_ENTER_SERIAL')); ?>');
					f.serial.focus();
					return false;
				}

				if ( !bLicense )
				{
					if ( f.register )
					{
						if ( !f.register.checked )	
						{
							alert( '<?php echo addslashes(BizResources::localize('LIC_CHECK_REGISTER_CHECKBOX')); ?>');
							f.register.focus();
							return false;
						}
	
						if ( f.datetime )
						{
							if ( !f.datetime.checked )	
							{
								if ( confirm( '<?php echo addslashes(BizResources::localize("LIC_DATETIME_CONFIRM", true, $datetimearr)); ?>'))
								{
									f.datetime.checked = true;
								}
								else
								{
									alert( "<?php echo addslashes($servermsg); ?>" );
									f.datetime.focus();
									return false;
								}
							}
						}
					}
				}
<?php		
		if ( ( $option != 'renew' ) && ( $option != 'reclaim' ) )
		{ 
?>
				var val = f.concurrentseats.value;
				if ( val == '' )
				{
					alert( '<?php echo addslashes(BizResources::localize('LIC_INVALID_NUMBER')); ?>' );
					f.concurrentseats.focus();
					return false;
				}
				if ( max < maxunknown )
				{
					if ( val != '*' )
					{
						if ( max == 0 )
						{
							alert( '<?php echo addslashes(BizResources::localize('LIC_CHECK_REGISTER_CHECKBOX')); ?>' );
							f.serial.focus();
							return false;
						}
						if ( val < min )
						{
							alert( ' <?php echo addslashes(BizResources::localize('LIC_SPECIFY_HIGHER_VALUE')); ?> ' + (min-1) + "." );
							f.concurrentseats.focus();
							return false;
						}
						if ( val > max )
						{
							alert( ' <?php echo addslashes(BizResources::localize('LIC_SPECIFY_LOWER_VALUE')); ?> ' + (max+1) + "." );
							f.concurrentseats.focus();
							return false;
						}
						if ( parseInt( val ) != val )
						{
							alert( val + ": " + '<?php echo addslashes(BizResources::localize('LIC_INVALID_NUMBER')); ?> ' );
							f.concurrentseats.focus();
							return false;
						}
					}
				}
				else
				{
					if ( val != '*' )
					{
						if ( parseInt( val ) != val )
						{
							alert( val + ": " + ' <?php echo addslashes(BizResources::localize('LIC_INVALID_NUMBER')); ?> ' );
							f.concurrentseats.focus();
							return false;
						}
                        if ( val < min )
                        {
                            alert( ' <?php echo addslashes(BizResources::localize('LIC_SPECIFY_HIGHER_VALUE')); ?> ' + (min-1) + "." );
                            f.concurrentseats.focus();
                            return false;
                        }
					}
				}
				f.checkbutton.disabled = true; //avoid the form to be submitted twice or more
<?php		
		} 
?>
				
				f.submitbutton.disabled = true; //avoid the form to be submitted twice or more
				f.cancelbutton.disabled = true;
				bSubmit = true;

				return true;
			}
			
<?php		
		if ( ($option != 'renew') && ($option != 'reclaim') )
		{ 
?>
			function checkLicense()
			{
				var f = document.forms.theForm;
				if ( f.serial.value.length==0 )	
				{
					alert( ' <?php echo addslashes(BizResources::localize('LIC_ENTER_SERIAL')); ?> ');
					f.serial.focus();
					return false;
				}
				
				if ( f.productcode.options[ f.productcode.selectedIndex ].value == '' )
				{
					alert( ' <?php echo addslashes(BizResources::localize('LIC_SELECT_PRODUCT')); ?> ');
					f.productcode.focus();
					return false;
				}

				var f2 = document.forms.checkform;
				f2.serial.value = f.serial.value;
				f2.concurrentseats.value = f.concurrentseats.value;
				if ( f2.concurrentseats.value == '0' ) // EN-35147 Blocked the input of '0'. Not sure about this con-
					f2.concurrentseats.value = '*'; // struction. Needed for auto renew?
				f2.productindex.value = f.productcode.selectedIndex;
				f2.productcode.value = f.productcode.options[ f.productcode.selectedIndex ].value;
				f2.productname.value = f.productcode.options[ f.productcode.selectedIndex ].text;

				if ( !bLicense && f.datetime )
					f2.datetime.value = (f.datetime.checked) ? 1 : 0;

				f.checkbutton.disabled = true; //avoid the form to be submitted twice or more
				f.submitbutton.disabled = true; //avoid the form to be submitted twice or more
				var o = geto( 'progress' );
				if ( o ) {
					o.innerHTML = "<?php echo BizResources::localize('LIC_PLEASE_WAIT'); ?><br><img src='images/progress.gif'/>";
				}
				bSubmit = true;
				f2.submit();
			}
<?php		
		}
?>
			
			function productSelected( s )
			{
				var f = s.form;
				if ( s.selectedIndex > 0 )
					f.serial.focus();
			}
			
			timeout = 500;
			
			function testSerial()
			{
				if ( bSubmit )
					return;

				var f = document.forms.theForm;
				if ( f.serial.value.length > 0 )
				{
					if ( ( f.option.value != 'renew' ) && (f.option.value != 'reclaim') )
						f.checkbutton.disabled = false;
					f.submitbutton.disabled = false;
				}
				else
				{
					if ( ( f.option.value != 'renew' ) && (f.option.value != 'reclaim') )
						f.checkbutton.disabled = true;
					f.submitbutton.disabled = true;
				}
				setTimeout( "testSerial()", timeout );
			}
			//-->
			</script>
			
<?php
			print "<h2>" . BizResources::localize("LIC_SERIAL") . "</h2>";		
			print BizResources::localize("LIC_ENTER_SERIAL");
			print "<form method='post' action='#' name='theForm' onSubmit='return validate();'>";
			print "<input type='hidden' name='step' value='$step'>";
			print "<input type='hidden' name='supportinfo' value=\"" . htmlspecialchars(stripslashes($supportInfo)) . "\">";
			print "<input type='hidden' name='manual' value='0'>";
			print "<input type='hidden' name='option' value='".htmlspecialchars($option)."'>";
			print "<input type='hidden' name='installationcode' value='$installationcode'>";
			print "<input type='hidden' name='concurrentproducts' value='$concurrentproducts'>";
			print "<input type='hidden' name='productname' value=''>";
			print "<table border='1'>";
			print "<tr><td>";
			print "<table border='0'>";
			print "<tr><td>" . BizResources::localize("LIC_PRODUCT") . ":</td><td><select name='productcode' onChange='productSelected( this )'>";
			print "<option value=''>" . BizResources::localize("LIC_SELECT_PRODUCT") . "</option>";

			$installedProductcodesArr = $lic->getProductcodes();

			$numOptions = 0;
			foreach( $prodarr as $i => $p )
			{
				$pinfo = explode( '~', $p ); 
				$pcode = $pinfo[0];	// [0]: code
				$pname = $pinfo[1]; // [1]: name
				$pvers = $pinfo[2]; // [2]: version

				// Since v6.1 b96 we no longer user WebEditor licenses, instead we use Content Station licenses.
				// Wasbak still shows this for v6.0 installs, so we filter it here
				if( strstr( $pcode, 'SCEntWebEditor') ) {
					continue;
				}

				//Very first time to install a license?
				//Only show the SCE Server in the list
				if ( !$license ) {
					if ( $pcode != PRODUCTKEY ) {
						continue;
					}
					$productindex = $i+1; //Preselect
				}

				//Application not known yet?
				if( !$productcode ) {
					//Only show applications that have not been installed yet
					if ( in_array( $pcode, $installedProductcodesArr ))
						continue;
				}
				
				//offset is +1: the first option is empty: "choose a product"
				if ( $productindex && ($i+1 == $productindex )) {
					$str = 'selected';
				} else if ( $productcode && ($pcode == $productcode )) {
					$str = 'selected';
					$productindex = $i+1; //Preselect
				} else {
					$str = '';
				}
				if( trim($pvers) == '' || trim($pvers) == '?' ) {
					print "<option value='$pcode' $str>$pname</option>\n";
				} else {
					print "<option value='$pcode' $str>$pname ($pvers)</option>\n";
				}
				$numOptions++;
			}
			if ( !$license && ( $numOptions== 0 ))
			{
				$error .= BizResources::localize("LIC_UNKNOWN_PRODUCT");
			}

			$serialStored = $lic->getSerial( $pcode );
			if ( $serialStored !== false && $str == 'selected' ) { 
			// If an already installed product is selected set the serial. 
				$serial = $serialStored;
				$readonly = ' readonly'; // If there is already a serial for the product it must be reused, BZ#34514.
			} else {
				$readonly = '';
			}
			
			print "</select></td></tr>";
			print "<tr><td>" . BizResources::localize("LIC_SERIAL") . ':</td>';
			print '<td><input type="text" name="serial" value="'.$serial.'" size="50" autocomplete="off"'. $readonly .'></td></tr>';

			if ( $option == 'renew' ) {
				print "<input type='hidden' name='concurrentseats' value='renew'>";
			} 
			else if ( $option == 'reclaim' ) 
			{
				if ( !$productcode )
				{
					print BizResources::localize("LIC_ERR_NO_PRODUCTCODE");
					getlicense_buildDoc();
					exit;
				}
				$concurrentseats = $lic->getNumLicenses( $productcode, $errorMessage );
				if ( !$concurrentseats )
				{
					print BizResources::localize("LIC_ERR_NO_LICENSE_INFO");
					getlicense_buildDoc();
					exit;
				}

				print "<input type='hidden' name='concurrentseats' value='$concurrentseats'>";
				print "<input type='hidden' name='mode' value='reclaim1'>";
			} 
			else 
			{
				print "<tr><td valign='top'>" . BizResources::localize("LIC_NUM_LICENSED_CONNECTIONS") . ":</td><td><input type='text' name='concurrentseats' value='$concurrentseats' size='2'><br>${maxstr} " . BizResources::localize("LIC_SPECIFY_STAR_FOR_ALL") . "</td></tr>\n";
				print "<tr><td>&nbsp;</td><td>" . BizResources::localize("LIC_WITHIN_LICENSE");
				print "<br/><input name='checkbutton' type='button' value='" . BizResources::localize("LIC_CHECK_AVAILABLE_CONNECTIONS") . "' onClick='checkLicense();' disabled><br/><div id='progress'>&nbsp;</div></td></tr>\n";
			}
			if ( $error ) 
			{ 
				print "<tr><td colspan='2'>&nbsp;</td></tr>\n";
				print "<tr><td>" . BizResources::localize("LIC_ERROR_MSG") . "</td><td><font color='red'>$error</font></td></tr>\n";
			}
			if ( !$license ) 
			{
				print "<tr><td colspan='2'>&nbsp;</td></tr>\n";
				print "<tr><td align='right' valign='top'><input type='checkbox' name='register' value='1' checked></td><td>" . BizResources::localize("LIC_WISH_TO_REGISTER_SCENT") . "\n";
				print "<br>" . BizResources::localize("LIC_NOTE_ONLY_REGISTERED_FOR_UPGRADE"). "</td></tr>\n";

				print "<tr><td colspan='2'>&nbsp;</td></tr>\n";
				print "<tr><td align='right' valign='top'><input type='checkbox' name='datetime' value='1'></td><td>" . BizResources::localize("LIC_DATETIME_OK", true, $datetimearr) . "\n";
				print "</td></tr>\n";
			}
			print "</table>";
			print "</td></tr>";
			print "</table>";
			print "<input name='submitbutton' type='submit' value='" . BizResources::localize("LIS_NEXT") . "'><input name='cancelbutton' type='button' value='" . BizResources::localize("ACT_CANCEL") . "' onClick=\"if (confirm('" . addslashes(BizResources::localize("LIC_ASK_CANCEL_LICENSE_INSTALLATION")) . "')) location.href='index.php';\">";

?>

			</form>
			<form method='post' action='<?php echo ACTIVATEURL?>' name='checkform'>
				<input type='hidden' name='step' value='<?php echo $step-1?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
				<input type='hidden' name='manual' value='0'>
				<input type='hidden' name='version' value='<?php echo PRODUCTVERSION?>'>
				<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION?>'>
				<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION?>'>
				<input type='hidden' name='platform' value="<?php echo htmlspecialchars(PHP_OS . '/' . PHP_VERSION . '/' . DBTYPE); ?>"/>
				<input type='hidden' name='installationcode' value='<?php echo $installationcode?>'>
				<input type='hidden' name='concurrentproducts' value='<?php echo $concurrentproducts?>'>
				<input type='hidden' name='productindex' value=''>
				<input type='hidden' name='datetime' value=''>
				<input type='hidden' name='productcode' value=''>
				<input type='hidden' name='productname' value=''>
				<input type='hidden' name='concurrentseats' value=''>
				<input type='hidden' name='serial' value=''>
				<input type='hidden' name='localURL' value='<?php echo $localURL?>'>
				<input type='hidden' name='localtime' value='<?php echo date( 'Ymd H:i:s' )?>'>
				<input type='hidden' name='clientip' value='<?php echo $_SERVER[ 'REMOTE_ADDR' ]?>'>
				<input type='hidden' name='clientname' value="<?php echo htmlspecialchars($clientname)?>">
				<input type='hidden' name='mode' value='checklicense'>
				<input type='image' width='0' height='0' alt=''>
			</form>
			<script language='Javascript' type='text/Javascript'>
			<!--
				//First choose a product
				var f = document.forms.theForm;
				f.productcode.focus();
				
				if ( ( f.option.value != 'renew' ) && (f.option.value != 'reclaim'))
					f.checkbutton.disabled = true;
				f.submitbutton.disabled = true;

<?php			
				if ( $productindex ) 
				{ 
					$concurrentseatspresent = ($option != 'renew' ) && ($option != 'reclaim') ? 'true' : 'false' ;
?>
					var concurrentseatspresent = <?php echo $concurrentseatspresent;?>;
					//If already choosen: fill in the serial
					if ( f.serial.value.length == 0 )
						f.serial.focus();
					else if ( concurrentseatspresent )
						f.concurrentseats.focus();
<?php
				} 
				
				if ( ( $option == 'renew' ) && $serial )
				{
					print "f.serial.value = '$serial';\n";
					print "f.submitbutton.disabled = false;\n";
					print "f.submit();\n";
				}
				if ( $datetime ) 
				{ 
					print "f.datetime.checked = true;";
				}
?>

				testSerial();
				
			//-->
			</script>

<?php
			print "<noscript>";
			print "<font color='red'>" . BizResources::localize("ERR_NO_JAVASCRIPT_SUPPORT") . "<br></font>";
			print "</noscript>";
?>
			<img src='images/progress.gif' width='0' height='0' alt='preload'> 

<?php
			getlicense_buildDoc();
			exit;
		}

		case 5:  //Check whether the given serial is a 'subscription/renewal' serial
		{
			$_POST[ 'mode' ] = 'getsubscription';
			$_POST[ 'localURL' ] = $localURL;
?>
			<form method='post' action='<?php echo ACTIVATEURL?>' name='theForm' onSubmit='return validate();'>
			<input type='hidden' name='step' value='<?php echo $step?>'>
			<input type='hidden' name='version' value='<?php echo PRODUCTVERSION?>'>
			<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION?>'>
			<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION?>'>
<?php
			foreach( $allFields as $f )
			{
				$fldVal = isset($_POST[ $f ]) ? htmlspecialchars( stripslashes( $_POST[ $f ] ) ) : '';
				print "<input type='hidden' name='$f' value='$fldVal'/>\n";
			}
?>			
			</form>

			<form>
			<input type='image' width='0' height='0' alt=''/>
			</form>

			<script language='Javascript' type='text/Javascript'>
			<!--
				document.forms.theForm.submit();
			//-->
			</script>

<?php
			print BizResources::localize('LIC_PLEASE_WAIT');
			print "<br/><img src='images/progress.gif'/>";
			getlicense_buildDoc();
			exit;
		}
			
		//6:
		//Enter contact info
		//Parameter: subscription 0/1
		//In case of subscription: also check connection using the host settings
		case 6:  
		{
			$productcode = isset($_POST[ 'productcode' ]) ? $_POST[ 'productcode' ] : '';
			$subscription = isset($_POST[ 'subscription' ]) ? $_POST[ 'subscription' ] : '';

			$concurrentseats = isset($_POST[ 'concurrentseats' ]) ? trim($_POST[ 'concurrentseats' ]) : '';
			if ( !$concurrentseats )
				$concurrentseats = '*';
			$_POST[ 'concurrentseats' ] = $concurrentseats;

			$mode = isset($_POST[ 'mode' ]) ? $_POST[ 'mode' ] : '';
			if ( !$mode || ($mode == 'getsubscription' ))
				$mode = "R1";
			$_POST[ 'mode' ] = $mode;

			$_POST[ 'localtime' ] = date( 'Ymd H:i:s' );
			$_POST[ 'clientip' ]  = $_SERVER[ 'REMOTE_ADDR' ];
			$_POST[ 'clientname' ] = $clientname ;

			//Updated the contact info (and called ourselves again using the same step)?
			if ( isset( $_POST[ 'name' ] )) {
				$lic->setContactParameters( $_POST );
			}

			//Updated the proxy info (and called ourselves again using the same step)?
			if ( isset( $_POST[ 'host' ] )) {
				$lic->setProxyParameters( $_POST );
			}

			$step = 10; //next step will be 11: the real install
			
			$contactFields = $lic->getContactFields();
			$contactValues = $lic->getContactParameters();
			// >>> variables dynamically created by $GLOBALS below !
			$name = '';
			$email = '';
			$address1 = '';
			$address2 = '';
			$zip = '';
			$fax = '';
			$phone = '';
			$company = '';
			$city = '';
			$country = '';
			// <<<
			foreach( $contactFields as $f )
			{
				if ( isset( $contactValues[ $f ] ))
					$GLOBALS[ $f ] = $contactValues[ $f ];
				else
					$GLOBALS[ $f ] = '';
			}
			
			//Do we need to fill in the contact (and proxy) info?
			//If all contact info is already present, continue to the next step
			//Use 0 or 1 instead of 'true' and 'false', because it is used in Javascript below
			$bContinue = ($name && $email && $address1 && $zip && $city && $country) ? 1 : 0;

			//Exception 1:
			//In case of a subscription serial, we are first testing the connection using the current proxy info			
			$connectionMessage = '';
			if ( $subscription )
			{
				$proxyValues = $lic->getProxyParameters();
				if ( $proxyValues[ 'host'] )
				{
					$bConnection = $lic->SmartRegContact( $productcode, $connectionMessage );
					if ( !$bConnection ) {
						//User intervention required
						$connectionMessage = BizResources::localize("LIC_NO_CONTACT" ) . '<br>' . $connectionMessage;
						$bContinue = 0; //0 instead of false (because it is used in Javascript below)
					}
				}
				//Else: no proxy, the current connection will be used, and is working properly already
			}

			//Exception 2:
			//In case all products have been uninstalled, the contact info is already present in the database.
			//However, still show the page to allow the user to make updates.
			//Note that updates can also be made by typing /server/admin/license/contactinfo.php
			//And once the changes have been saved (and we have been here twice or more), also continue
			$products = $lic->getProductcodes();
			//Very first installation (of SCENT Server)? Be sure to show the contact (and proxy) values
			if ( !isset( $_POST[ 'name' ] ) && //Very first time, i.e. not storing the data that has been filled in?
				  (count( $products ) == 0))
				$bContinue = 0; //0 instead of false (because it is used in Javascript below)

?>			
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

			function submitit( f )
			{
				f.submitbutton.disabled = true; //avoid the form to be submitted twice
				f.cancelbutton.disabled = true;
				var o = geto( 'progress' );
				if ( o ) {
					o.innerHTML = "<?php echo BizResources::localize("LIC_PLEASE_WAIT"); ?> <br><img src='images/progress.gif'></img>";
				}
			}

			/* 
			Note: this function doesn't work properly when two adjacent 'find' characters are in the input string
			*/
			function str_replace( find, replace, str )
			{
				var arr = str.split( find );
				var result = '';
				var i;
				for ( var i=0; i<arr.length; i++)
				{
					if ( result != '' )
						result = result + replace;
					result = result + arr[ i ];
				}
				return result;
			}

			function validate()
			{
				var f = document.forms.theForm;
				if ( !validateContact( f ))
					return false;

				var bProxy = false;
				try {
					if ( f.subscription.value && ( f.host.value != '' ))
						bProxy = true;
				}
				catch( e )
				{
				}
				
				//Contact info has changed?
				var bStoreAndTestProxy = bHadFocus || bProxy;
				
				if ( bStoreAndTestProxy ) {
					f.action = 'getlicense.php';
					f.step.value = 5;
				}
					
				submitit( f );			
				return true;
			}

			//If at least one control had the focus, 
			//then suppose the data	has been changed, and the data needs to be stored.
			//In case of proxy: the proxy needs to be tested first.
			var bHadFocus = false;
			function hadFocus()
			{
				bHadFocus = true;
			}
			//-->
			</script>

			<script language='Javascript' type='text/Javascript' src='validatecontact.js.php'>
			</script>

			<div id='progress'>&nbsp;</div>
			<img src='images/progress.gif' width='0' height='0' alt='preload'> 

			<div id='main' style='visibility:hidden'>
			<form method='post' action='<?php echo ACTIVATEURL?>' name='theForm' onSubmit='return validate();'>
			<input type='hidden' name='step' value='<?php echo $step?>'/>
			<input type='hidden' name='subscription' value='<?php echo $subscription?>'/>
			<input type='hidden' name='version' value='<?php echo PRODUCTVERSION?>'/>
			<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION?>'/>
			<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION?>'/>
			<input type='hidden' name='platform' value="<?php echo htmlspecialchars(PHP_OS . '/' . PHP_VERSION . '/' . DBTYPE); ?>"/>
			<input type='hidden' name='orgemail' value='<?php echo $email?>'/>

<?php		
			foreach( $allFields as $f )
			{
				$fldVal = isset($_POST[ $f ]) ? htmlspecialchars( stripslashes( $_POST[ $f ] ) ) : '';
				print "<input type='hidden' name='$f' value=\"$fldVal\"/>\n";
			}

			if ( $subscription )
			{
				print '<h2>' . 'Proxy server' . '</h2>';
?>
				<table border='1'>
				<tr><td>
					<table>
<?php
					if ( $connectionMessage )
						print "<tr><td colspan='4'><font color='red'>$connectionMessage</font></td></tr>\n";
?>
					<tr><td>Host<sup></sup></td><td colspan='3'><input type='text' name='host' value='<?php echo isset($proxyValues[ 'host' ]) ? $proxyValues[ 'host' ] : ''?>' size='40' onFocus='hadFocus()'/></td></tr>
					<tr><td>Port<sup></sup></td><td colspan='3'><input type='text' name='port' value='<?php echo isset($proxyValues[ 'port' ]) ? $proxyValues[ 'port' ] : ''?>' size='40' onFocus='hadFocus()'/></td></tr>
					<tr><td>Username<sup></sup></td><td colspan='3'><input type='text' name='user' value='<?php echo isset($proxyValues[ 'user' ]) ? $proxyValues[ 'user' ] : ''?>' size='40' onFocus='hadFocus()'/></td></tr>
					<tr><td>Password<sup></sup></td><td colspan='3'><input type='text' name='pass' value='<?php echo isset($proxyValues[ 'pass' ]) ? $proxyValues[ 'pass' ] : ''?>' size='40' onFocus='hadFocus()'/></td></tr>
					</table>
				</td></tr>
				</table>
<?php		
			}
			
			print '<h2>' . BizResources::localize("LIC_CONTACTINFO" ) . '</h2>';
?>

			<table border='1'>
			<tr><td>
			<table>
			<tr><td>Name<sup> *</sup></td><td colspan='3'><input type='text' name='name' value='<?php echo $name?>' size='40' onFocus='hadFocus()'/></td></tr>
			<tr><td>Email<sup> *</sup></td><td colspan='3'><input type='text' name='email' value='<?php echo $email?>' size='40' onFocus='hadFocus()'/></td></tr>
			<tr><td>Company</td><td colspan='3'><input type='text' name='company' value='<?php echo $company?>' size='40' onFocus='hadFocus()'/></td></tr>
			<tr><td>Address 1<sup> *</sup></td><td colspan='3'><input type='text' name='address1' value='<?php echo $address1?>' size='40' onFocus='hadFocus()'/></td></tr>
			<tr><td>Address 2</td><td colspan='3'><input type='text' name='address2' value='<?php echo $address2?>' size='40' onFocus='hadFocus()'/></td></tr>
			<tr><td>Zip<sup> *</sup></td><td><input type='text' name='zip' value='<?php echo $zip?>' size='10' onFocus='hadFocus()'/></td>
			<td>City<sup> *</sup></td><td><input type='text' name='city' value='<?php echo $city?>' size='20' onFocus='hadFocus()'/></td></tr>
			<tr><td>Country<sup> *</sup></td><td colspan='3'><select name='country' onFocus='hadFocus()'>
			<option value=''> <?php echo BizResources::localize("LIC_ENTER_COUNTRY"); ?> </option>
			</select>
			</td></tr>
			<tr><td>Phone</td><td><input type='text' name='phone' value='<?php echo $phone?>' size='20' onFocus='hadFocus()'/></td>
			<td>Fax</td><td><input type='text' name='fax' value='<?php echo $fax?>' size='20' onFocus='hadFocus()'/></td></tr>
			</table>
			<br><sup>*</sup> <?php echo BizResources::localize("LIC_REQUIRED_FIELDS"); ?>
			</td></tr>
			</table>
			<br>

			<input name='submitbutton' type='submit' value='<?php echo BizResources::localize("LIS_NEXT"); ?>'>
			<input name='cancelbutton' type='button' value='<?php echo BizResources::localize("ACT_CANCEL");?>' onClick="if (confirm('<?php echo addslashes(BizResources::localize("LIC_ASK_CANCEL_LICENSE_INSTALLATION")); ?>' )) location.href='index.php';">
			</form>
			</div>
			<script language='Javascript' type='text/Javascript'>
			<!--
				var f = document.forms.theForm;
				loadCountries( f, '<?php echo $country; ?>' );

				var bContinue = <?php echo $bContinue; ?>;
				var bConnectionError = <?php echo $connectionMessage? 1: 0;?>;
				if ( bContinue )
				{
//					f.submitbutton.disabled = true;
					//Show progress
					submitit( f );
					f.submit();
				}
				else
				{
					document.getElementById('main').style.visibility = "visible";
					if ( bConnectionError )
						f.host.focus();
					else
						f.name.focus();
					bHadFocus = false;
				}
			//-->
			</script>
<?php
			getlicense_buildDoc();
			exit;
		}
		
		case 10: //Send the request to SmartReg
		{
			$serial = trim($_POST[ 'serial' ]);
			$productcode = $_POST[ 'productcode' ];
			$productname = $_POST[ 'productname' ];
			$supportInfo = isset($_POST[ 'supportinfo' ]) ? $_POST[ 'supportinfo' ] : '';
			$mode = $_POST[ 'mode' ];
			$concurrentseats = isset($_POST[ 'concurrentseats' ]) ? trim($_POST[ 'concurrentseats' ]) : '';
			$installationcode = $lic->getInstallationCode( $errorMessage );

			print "<h2>" . BizResources::localize("LIC_INSTALL_LICENSE") . "</h2>";
			print BizResources::localize("LIC_PLEASE_WAIT" );
			print "<br/><img src='images/progress.gif'/>";
?>
				<form method='post' action='<?php echo ACTIVATEURL?>' name='theForm'>
				<input type='hidden' name='step' value='<?php echo $step?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
				<input type='hidden' name='manual' value='0'>
				<input type='hidden' name='version' value='<?php echo PRODUCTVERSION?>'>
				<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION?>'>
				<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION?>'>
				<input type='hidden' name='platform' value="<?php echo htmlspecialchars(PHP_OS . '/' . PHP_VERSION . '/' . DBTYPE); ?>"/>
				<input type='hidden' name='serial' value='<?php echo $serial?>'>
				<input type='hidden' name='concurrentseats' value='<?php echo $concurrentseats?>'>
				<input type='hidden' name='productcode' value='<?php echo htmlspecialchars($productcode)?>'>
				<input type='hidden' name='productname' value="<?php echo htmlspecialchars($productname);?>">
				<input type='hidden' name='installationcode' value='<?php echo $installationcode;?>'>
				<input type='hidden' name='clientname' value="<?php echo htmlspecialchars($clientname)?>">
				<input type='hidden' name='localtime' value='<?php echo date( 'Ymd H:i:s' )?>'>
				<input type='hidden' name='clientip' value='<?php echo $_SERVER[ 'REMOTE_ADDR' ]?>'>
				<input type='hidden' name='clientname' value="<?php echo htmlspecialchars($clientname)?>">
				<input type='hidden' name='mode' value='R1'>
				<input type='hidden' name='localURL' value='<?php echo $localURL?>'>
				<input type='image' width='0' height='0' alt=''>
<?php
				$contactFields = $lic->getContactFields();
				foreach( $contactFields as $f )
				{
					print "<input type='hidden' name='$f' value='-'>\n";
				}
?>				
				<noscript>
					<input type='submit' value='Install'>
				</noscript>
				</form>
				<script language='Javascript' type='text/Javascript'>
				<!--
					var test = <?php echo $test;?>;
					if ( !test )
						document.forms.theForm.submit();
				//-->
				</script>
<?php
			getlicense_buildDoc();
			exit;
		}

		case 11: //setting the license
		{
			$licstr = new LicenseString();
			$serial = trim($_POST[ 'serial' ]);
			$license = $_POST[ 'license' ];
			$manual = $_POST[ 'manual' ];
			$productcode = $_POST[ 'productcode' ];
			$productname = $_POST[ 'productname' ];
			$supportInfo = isset($_POST[ 'supportinfo' ]) ? $_POST[ 'supportinfo' ] : '';
			$mode = $_POST[ 'mode' ];
			$option = isset($_POST[ 'option' ]) ? $_POST[ 'option' ] : '';
			$concurrentseats = isset($_POST[ 'concurrentseats' ]) ? trim($_POST[ 'concurrentseats' ]) : '';
			$err = '';
			$errSmartReg = ''; //the English version, if possible
			$msg = '';
			$msgSmartReg = ''; //the English version, if possible
			
			if ( $manual )
			{
				//Probably the productcode is empty in case of applications (not for SCEnt itself)
				if ( !$productcode && $license )
				{
					//Try to get the product code from the license
					//First strip 'manual prefix'
					$license2 = $licstr->stripManualLicense( $license );
					if ( $license2 !== false )
					{
						$licenseArr = $licstr->getLicenseInfo( $license2 );		
						if ( $licenseArr !== false )
						{
							$productcode = $licenseArr[ 'productcode' ];
							if ( !$productname )
							{
								$productCodeChars = '';
								$productNumberFull = '';

								preg_match('/\D*/', $productcode, $productCodeChars);
								preg_match('/\d+/', $productcode, $productNumberFull);

								$productCodeChars = reset($productCodeChars);
								$productNumberFull = reset($productNumberFull);
								$productNumberClean = substr( $productNumberFull, 0, -2 ); // Clean the product number, remove the last 2 numbers

								//Use the parsed product number and the product name
								if ( array_key_exists( $productCodeChars, $productcode2name )){
									$productname = $productcode2name[ $productCodeChars ];
									$productname .= ' (' . $productNumberClean . ')'; // Add the number
								} else {
									$productname = '(' . $productcode . ')';
								}
							}
						}
					}
				}
			}
			
			if ( !$license || !$serial || !$productcode || !$productname )
			{
				if ( !$license )
				{
					$err = BizResources::localize("LIC_NO_LICENSE_RECEIVED");
					$errSmartReg = "No license received from registration server";
				}
				else
				{
					$err = BizResources::localize("LIC_NO_SERIAL_OR_PRODUCT_RECEIVED");
					$errSmartReg = "No serial, productcode or productname received from registration server";
				}
				if ( isset( $_POST[ 'error' ] ))
					$err .= "<br>" .  nl2br($_POST[ 'error' ]);
				$licenseStatus = -1;
			}
			else
			{
				//In case of reclaim, the writing of the current key1 is delayed (to keep the old hostid information as long as possible)...
				//So write it now....
				if ( ($option == 'reclaim') && $manual )
					$lic->getInstallationCode( $err );
				
				$licenseStatus = $lic->installLicense( $productcode, $productname, $serial, $license, $manual, $err );
				$errSmartReg = $err;//Not always English, but probably containing the error code
			}

			switch( $licenseStatus )
			{
				case WW_LICENSE_OK:
					$msg = BizResources::localize("LIC_LICENSE_INSTALLED");
					$msgSmartReg = "License installed successfully.";
					break;
				case WW_LICENSE_OK_REMOVED:
					$msg = BizResources::localize("LIC_LICENSE_REMOVED");
					$msgSmartReg = "License removed successfully.";
					//When status passed to SmartReg to confirm the registration: don't specify an error code.
					$licenseStatus = WW_LICENSE_OK;
					break;
				case WW_LICENSE_OK_USERLIMIT:
				{
					$msg = BizResources::localize("LIC_LICENSE_INSTALLED") . " " . BizResources::localize("LIC_USAGE_LIMIT_REACHED");
					$msgSmartReg = "License installed successfully. License usage limit reached.";
					//When status passed to SmartReg to confirm the registration: don't specify an error code.
					$licenseStatus = WW_LICENSE_OK;
					break;
				}
				case WW_LICENSE_OK_WARNING:
				case WW_LICENSE_OK_INTERNAL:
				{
					//The warning will be displayed too.
					$msg = BizResources::localize("LIC_LICENSE_INSTALLED");
					$msgSmartReg = "License installed successfully.";
					//When status passed to SmartReg to confirm the registration: don't specify an error code.
					$licenseStatus = WW_LICENSE_OK;
					break;
				}
			}

			$ticket = isset($_POST[ 'ticket' ]) ? $_POST[ 'ticket' ] : '';
			if ( !$ticket )
			{
				//Don't try to send the error to the server; the server can't update something without a ticket.
				$manual = true;
			}

			if ( $manual )
			{
				if ( $licenseStatus <= WW_LICENSE_OK_MAX )
					print "<h2>" . BizResources::localize("LIC_LICENSE_STATUS") . "</h2>";
				else
					print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
				if ( $err )
					print "$err<br>";
				if ( $msg )
					print "$msg<br>";

				if ( isset($_POST[ 'retry' ]) && $_POST[ 'retry' ] )
				{
					print "<br>" . BizResources::localize("LIC_AFTER_NOTIFY_CLICK_RECLAIM");
					print "<br><form method='post' action='" . ACTIVATEURL . "' name='theForm'>\n";
					foreach( $_POST as $key => $val )
					{
						print "<input type='hidden' name='$key' value=\"" . htmlspecialchars( $val ) . "\">\n";
					}
					print "<input type='submit' value='Retry'>\n";
					print "</form>\n";
					
					//When the customer waits for a few minutes or hours for the approval email,
					//the session would be lost (expiration after 1 hour?!)
					//So keep the session alive by asking for an invisible image via a php page
					print "<img name='keepsessionalive' width='0' height='0'>\n";
					print "<script language='Javascript' type='text/Javascript' src='keepsessionalive.js'>\n";
					print "</script>\n";
				}

				print "<br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
				//No confirm possible ("offline"), so exit here.

				//Try to add geo information...
				if ( $mode == 'reclaim1' ) {
					print "<br><img src='" . CONFIRMIMAGEURL . "' width='0' height='0'>\n";
				}
				
				getlicense_buildDoc();
				exit;
			}
			else
			{
				$mode = isset($_POST[ 'mode' ]) ? $_POST[ 'mode' ] : '';
				
				if ( $mode == 'reclaim1' ) {
					//Come back at level 10 to continue with the registration process
					$step -= 2; 
				}
				$mode = str_replace( "1", "2", $mode ); //R1->R2, U1->U2

				print "<h2>" . BizResources::localize("LIC_INSTALL_LICENSE") . "</h2>";
				print BizResources::localize("LIC_PLEASE_WAIT" );
				print "<br/><img src='images/progress.gif'/>";
?>
				<form method='post' action='<?php echo ACTIVATEURL?>' name='theForm'>
				<input type='hidden' name='step' value='<?php echo $step?>'>
				<input type='hidden' name='supportinfo' value="<?php echo htmlspecialchars(stripslashes($supportInfo));?>">
				<input type='hidden' name='manual' value='0'>
				<input type='hidden' name='version' value='<?php echo PRODUCTVERSION?>'>
				<input type='hidden' name='majorversion' value='<?php echo PRODUCTMAJORVERSION?>'>
				<input type='hidden' name='minorversion' value='<?php echo PRODUCTMINORVERSION?>'>
				<input type='hidden' name='platform' value="<?php echo htmlspecialchars(PHP_OS . '/' . PHP_VERSION . '/' . DBTYPE); ?>"/>
				<input type='hidden' name='ticket' value='<?php echo $ticket?>'>
				<input type='hidden' name='serial' value='<?php echo $serial?>'>
				<input type="hidden" name="error" value="<?php echo htmlspecialchars($errSmartReg)?>">
				<input type="hidden" name="message" value="<?php echo htmlspecialchars($msgSmartReg)?>">
				<input type='hidden' name='status' value='<?php echo $licenseStatus?>'>
				<input type='hidden' name='concurrentseats' value='<?php echo $concurrentseats?>'>
				<input type='hidden' name='productcode' value='<?php echo htmlspecialchars($productcode)?>'>
				<input type="hidden" name="productname" value="<?php echo htmlspecialchars($productname);?>">
				<input type='hidden' name='mode' value='<?php echo $mode?>'>
				<input type='hidden' name='localURL' value='<?php echo $localURL?>'>
				<input type='image' width='0' height='0' alt=''>
				<noscript>
					<input type='submit' value='Install'>
				</noscript>
				</form>
				<script language='Javascript' type='text/Javascript'>
				<!--
					var test = <?php echo $test;?>;
					if ( !test )
						document.forms.theForm.submit();
				//-->
				</script>				
<?php				
			}
			getlicense_buildDoc();
			exit;
		}
		case 12:
		{
			$ticket = '';
			if ( $hasLicense )
			{
				//getLogCookie() would start the logon page in IE immediately (if cookie not present)
				$ticket = getOptionalCookie('ticket');
				if ( $ticket )
				{
					require_once BASEDIR.'/server/dbclasses/DBTicket.class.php';
					$user = DBTicket::checkTicket( $ticket );
					if ( !$user )
						$ticket = '';
				}
			}

			$licenseStatus = $_POST[ 'status' ];
			$err = $_POST[ 'error' ];
			$msg = $_POST[ 'message' ];
			
			if ( $licenseStatus <= WW_LICENSE_OK_MAX )
			{
				if ( $licenseStatus == WW_LICENSE_OK_REMOVED )
					print "<h2>" . BizResources::localize("LIC_REMOVE_LICENSE") . "</h2>";
				else
					print "<h2>" . BizResources::localize("LIC_INSTALL_LICENSE") . "</h2>";
			}
			else
				print "<h2>" . BizResources::localize("LIC_ERR_INSTALLING_LICENSE") . "</h2>";
			if ( $err )
				print "$err<br>";
			if ( $msg )
				print "$msg<br>";

			//First license installation?
			//We can not distinguish whether this is the first installation (of SCE)
			//We can however, check whether an admin user is already logged on...
			if ( $hasLicense )
			{
				print "<br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";
			}
			else //Some error occured during the first install (no license yet)...
				print "<br><a href='index.php'>" . BizResources::localize("LIC_LICENSE_STATUS") . "</a>";

	
			
			getlicense_buildDoc();
			exit;
		}
 	}

getlicense_buildDoc();
function getlicense_buildDoc()
{
	$txt = ob_get_contents();
	ob_end_clean();

	require_once BASEDIR . '/server/secure.php';
	require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
	
	$txt = HtmlDocument::buildDocument($txt, true, null, false, true);
	print $txt;
}

/**
 * Filter out no longer supported products from the list of all products (as returned by Acttvation Server).
 *
 * @since 10.5.0
 * @param string[] $products
 * @return string[]
 */
function filterOutNotSupportedProducts( array $products ): array
{
    require_once BASEDIR.'/server/utils/license/license.class.php';
    $license = new License();
	$supportedProducts = array();
	$minimumSupportedScDmCVersion = 1100; // CC 2015
	if( $products ) foreach( $products as $product ) {
	    if( $license->isSupportedProduct( $product ) ) {
		     $supportedProducts[] = $product;
        }
	}

	return $supportedProducts;
}