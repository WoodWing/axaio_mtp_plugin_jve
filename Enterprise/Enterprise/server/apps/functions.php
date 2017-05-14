<?php

// Delete file from server
function deletefile( $filepath )
{
	if ( file_exists( $filepath ) )
		unlink( $filepath );
}

// Opens given file and reads its content.
// File is closed after read.
function openFile( $filepath )
{
	$contents = "";
	$fp = fopen( $filepath, 'r' );
	if( $fp )
	{
		$contents = fread ( $fp, filesize( $filepath ) );
		fclose( $fp );
	}
	else
	{
		echo "Error opening file ".$filepath." for reading.<br>";
	}
	return $contents;
}

function mkpath($path)
{
	if (file_exists($path)) {
		return true;
	} else {
		if(!mkdir($path)) {
			return false;
		}
	}
	return true;

}

function checkFields ($check)
{
	if (empty($check))
	{
		$check = "<font color=red>*</font>";
		return $check;
	}
	else
	{
		$check = "*";
		return $check;
	}
}

function cookie($name, $getcookie, &$p0, &$p1, &$p2, &$p3, &$p4, &$p5, &$p6)
{
	if( $getcookie ) {
		getCookieValues( $name, $p0, $p1, $p2, $p3, $p4, $p5, $p6 );
	}
	setcookie( $name, "$p0/$p1/$p2/$p3/$p4/$p5/$p6", time()+3600*24*365*10, INETROOT );
}

function getCookieValues( $name, &$p0, &$p1, &$p2, &$p3, &$p4, &$p5, &$p6 )
{
	$ck = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	if( $ck ) {
		$arr = explode('/', $ck);
		$p0 = $arr[0];
		$p1 = $arr[1];
		$p2 = $arr[2];
		$p3 = $arr[3];
		$p4 = $arr[4];
		$p5 = $arr[5];
		$p6 = $arr[6];
	}
}

function cookieMonster($name, $getcookie, &$p0, &$p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7, &$p8, &$p9, &$p10, &$p11, &$p12, &$p13, &$p14, &$p15, &$p16, &$p17, &$p18, &$p19)
{
	$ck = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	if ($ck && $getcookie) {
		$arr = explode('/', $ck);
		$p0 = $arr[0];
		$p1 = $arr[1];
		$p2 = $arr[2];
		$p3 = $arr[3];
		$p4 = $arr[4];
		$p5 = $arr[5];
		$p6 = $arr[6];
		$p7 = $arr[7];
		$p8 = $arr[8];
		$p9 = $arr[9];
		$p10 = $arr[10];
		$p11 = $arr[11];
		$p12 = $arr[12];
		$p13 = $arr[13];
		$p14 = $arr[14];
		$p15 = $arr[15];
		$p16 = $arr[16];
		$p17 = $arr[17];
		$p18 = $arr[18];
		$p19 = $arr[19];
	}
	setcookie( $name, "$p0/$p1/$p2/$p3/$p4/$p5/$p6/$p7/$p8/$p9/$p10/$p11/$p12/$p13/$p14/$p15/$p16/$p17/$p18/$p19", time()+3600*24*365*10, INETROOT );
}

function listrouteto( $ticket, $publ = null, $issue = null)
{
	require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
	require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
	$arr = array();
	try {
		BizSession::startSession( $ticket );
		// First routable groups:
		$grps = BizUser::getUserGroups( $publ, $issue, true );
		if( is_array($grps) ) {
			foreach( $grps as $grp ) {
				$arr[] = $grp->Name;
			}
		}
		// And next users:
		$usrs = BizUser::getUsers( $publ, $issue, 'user' );
		if( is_array($usrs) ) foreach( $usrs as $usr ) {
			$arr[] = $usr->UserID;
		}
	} catch( BizException $e ) {
		BizSession::endSession();
	}
	BizSession::endSession();
	return $arr;
}
