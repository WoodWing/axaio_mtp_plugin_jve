<?php
//This module is meant for Q & A during the test phase only, and should not be delivered in the final release!
//This module can be used to modify the license:
// - the maximum number of concurrent users
// - the startdate, expiration date and renew date
// When changing the license, the license can only be made more limited: 
// - less concurrent user
// - expiration or renew earlier
// - the dates can not be set after the MAXTIME definition below:

define( 'MAXTIME', "2020-12-31" );

require_once dirname( __FILE__ ) . '/../../../config/config.php';
include_once( BASEDIR . '/server/utils/license/license.class.php' );
include_once( BASEDIR . '/server/regserver.inc.php' );
require_once BASEDIR . '/server/secure.php';

$lic = new License();
$licstr = new LicenseString();

//If no license installed yet: everyone may install the first license (the SCE Server license)
//Once a license has been installed, only admin users may do something here...
$hasLicense = $lic->hasLicense();
if( !$hasLicense ) {
	exit;
}

$SCEAppserial = $lic->getSerial( PRODUCTKEY );
$info = array();
$errMsg = '';
$licenseStatus = $lic->getLicenseStatus( PRODUCTKEY, $SCEAppserial, $info, $errMsg );
//The user should only be an administrator if he can logon as an administrator
//In case of an license error, he is not able to logon as administrator.
if( $licenseStatus > WW_LICENSE_OK_MAX ) {
	exit;
}

$ticket = checkSecure( 'admin' ); // Security: should be admin user

$productcode = @$_POST['productcode'];
if( !$productcode ) {
	?>
	<h2>QA</h2>
	Select product:
	<form action='#' method='POST'>
		<select name='productcode'>
			<?php
			$productcodes = $lic->getProductcodes();
			foreach( $productcodes as $pcode )
				print "<option value='$pcode'>" . $lic->getName( $pcode ) . "</option>\n";
			?>		
		</select>
		<br><input type='submit'>
	</form>
	<?php
	exit;
}

$productinfo = $lic->getLicenseField( $productcode );
if( $productinfo === false ) {
	print "Error 1";
	exit;
}
$productname = '';
$serial = '';
$license = '';
if( !$licstr->getProductInfo( $productinfo, $productname, $serial, $license ) ) {
	print "Error 2";
	exit;
}
if( !$license ) {
	print "Error 3";
	exit;
}

$arr = $licstr->getLicenseInfo( $license );
if( $arr === false ) {
	print "Error 4";
	exit;
}
if( !isset( $_POST['maxusage'] ) ) {
	print "<h2>Modify</h2>";
	print "<form method='POST' action='#'>\n";

	$maxusage = $arr['maxusage'];
	print "Usage limit:<input name='maxusage' value='$maxusage'>\n";
	print "<br>The date/time values must have the format: Y-m-d H:i:s. \n";
	$starttime = $arr['starttime'];
	if( $starttime ) {
		$starttime = $licstr->WWTimeStr2Unix( $starttime );
		$starttime = date( 'Y-m-d H:i:s', $starttime );
	}
	print "<br>Start time: <input name='starttime' value='$starttime'>\n";
	$expiretime = $arr['expiretime'];
	if( $expiretime ) {
		$expiretime = $licstr->WWTimeStr2Unix( $expiretime );
		$expiretime = date( 'Y-m-d H:i:s', $expiretime );
	}
	print "<br>Expire time: <input name='expiretime' value='$expiretime'>\n";
	$renewtime = $arr['renewtime'];
	if( $renewtime ) {
		$renewtime = $licstr->WWTimeStr2Unix( $renewtime );
		$renewtime = date( 'Y-m-d H:i:s', $renewtime );
	}
	print "<br>Renew time: <input name='renewtime' value='$renewtime'>\n";

	if( $renewtime ) {
		print "<br><input name='autorenew' value='1' type='checkbox'> Try auto renew if necessary (in the next logon, possibly overwriting some parameters)";
	}

	//Set in e.g. config.php
	if( defined( 'LICENSE_QA_TESTFLAGS' ) ) {
		$testflags = $arr['testflags'];
		if( $testflags & 1 )
			$es1 = 'selected';
		if( $testflags & 6 )
			$es6 = 'selected';
		if( $testflags & 7 )
			$es7 = 'selected';
		print "<br>test: <select name='testflags'>\n";
		print "<option value='0'>none</option>\n";
		print "<option value='1' $es1>FS or DB copy</option>\n";
		print "<option value='6' $es6>system time</option>\n";
		print "<option value='7' $es7>all</option>\n";
		print "</select>";
	}

	//Set in e.g. config.php
	if( defined( 'LICENSE_QA_ERROR' ) ) {
		$errorstart = $arr['errorstart'];
		if( $errorstart ) {
			$errorstart = $licstr->WWTimeStr2Unix( $errorstart );
			$errorstart = date( 'Y-m-d H:i:s', $errorstart );
		}
		print "<br>Error time: <input name='errorstart' value='$errorstart'>\n";
	}

	print "<br><input type='hidden' name='productcode' value='$productcode'>\n";
	print "<br><input type='submit' value='Modify'>\n";
	print "</form>\n";
	exit;
}

$maxusage = $_POST['maxusage'];
if( !is_numeric( $maxusage ) || ($maxusage > $arr['maxusage'] ) || ($maxusage <= 0 ) ) {
	print "Invalid value";
	exit;
}
$arr['maxusage'] = $maxusage;


$key = 'starttime';
$t = handleTime( $licstr, $key, $_POST[$key], $arr[$key] );
if( $t === false )
	exit;
$arr[$key] = $t;

$key = 'expiretime';
$t = handleTime( $licstr, $key, $_POST[$key], $arr[$key] );
if( $t === false )
	exit;
$arr[$key] = $t;

$key = 'renewtime';
$t = handleTime( $licstr, $key, $_POST[$key], $arr[$key] );
if( $t === false )
	exit;
$arr[$key] = $t;

$doAutoRenew = $_POST['autorenew'];
if( $doAutoRenew ) {
	//Remove the lastautorenew key, so the next logon will do the 'auto renew'.
	$dbkey = "lastautorenew";
	$driver = DBDriverFactory::gen();
	$dbo = $driver->tablename( 'config' );
	$sql = "DELETE FROM $dbo WHERE `name`='$dbkey'";
	$sth = $driver->query( $sql, null );
}

if( isset( $_POST['testflags'] ) ) {
	$arr['testflags'] = $_POST['testflags'];
}
if( isset( $_POST['errorstart'] ) ) {
	$key = 'errorstart';
	if( $_POST[$key] ) {
		$t = handleTime( $licstr, $key, $_POST[$key], $arr[$key] );
		if( $t !== false )
			$arr[$key] = $t;
	}
	else
		$arr[$key] = '';
}

$license = $licstr->makeLicenseKey( $arr );
if( $license == false ) {
	print "Error 5";
	exit;
}

$productname = '';
$serial = $lic->getSerial( $productcode );
if( !$lic->setLicense( $productcode, $productname, $serial, $license ) ) {
	print "Error 6";
	exit;
}

print "<br>License updated";
print "<br><a href='index.php'>Index</a>";

function handleTime( &$licstr, $key, $newval, $oldval )
{
	if( $newval ) {
		$unixt = strtotime( $newval );
//		print "<br>unixt=$unixt";
		if( $unixt === false ) {
			print "Invalid date/time ($key)(1a)";
			return false;
		}
		if( ($key != 'starttime') && ( $unixt < time() ) ) {
			print "Invalid date/time; time in the past ($key)(1b)";
			return false;
		}
		if( $unixt > strtotime( MAXTIME ) ) {
			print "Invalid date/time; time after " . MAXTIME . " ($key)(1c)";
			return false;
		}
		$unixt2 = $licstr->Unix2WWTimeStr( $unixt );
		if( $oldval ) {
			if( ($key != 'starttime') && ($unixt2 > $oldval ) ) {
				print "Invalid date/time; after old value ($key)(2a)";
				return false;
			} else if( ($key == 'starttime') && ($unixt2 < $oldval ) ) {
				print "Invalid date/time; after old value ($key)(2b)";
				return false;
			}
		}
		return $unixt2;
	} else {
		if( ( $key != 'starttime' ) && $oldval ) {
			print "Invalid time ($key)(3)";
			return false;
		}
		return $newval;
	}
}
?>