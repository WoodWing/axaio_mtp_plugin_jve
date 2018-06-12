<?php
	include( '../regserver.inc.php' );
	
	function testConnection( $url, $description, $proxyServer='', $proxyPort='' )
	{
		$timeout = 10;
		
  		$url2 = preg_replace("@^http://@i", "", $url); 
		$host = substr($url2, 0, strpos($url2, "/")); 
		$portpos = strpos($host, ":");
		if ( $portpos !== FALSE ) {
			$port = intval(substr($url2, $portpos + 1)); 
			$host = substr($host, 0, $portpos );
		} else {
			$port = 80; 
		}
		
		$isProxy = (($proxyServer != '') && ($proxyServer != WWREGSERVER));

		if ( !$proxyServer )
			$proxyServer = $host;
		if ( !$proxyPort )
			$proxyPort = $port;

		if ( $description )
			print $description . ':<br>';
		print "Opening connection to server $proxyServer, port $proxyPort timeout=$timeout..."; 
		flush();
		$errno = 0;
		$errstr = '';
		$fp = @fsockopen($proxyServer, $proxyPort, $errno, $errstr, $timeout);
		if (!$fp) {
			print "<br><font color='red'>Error: $errstr ($errno)</font>";
			flush();
		} else {
			print "<br><font color='green'>OK!</font>";
			flush();
			
			if ( $isProxy ) {
				print "<br>Connection to proxy OK. Now requesting Woodwing test page...";
				flush();

				$boundary="----------------------------" . time();
				$reqbody = "--$boundary--\r\n";
				$contentlength = strlen($reqbody); 

		
				$reqheader =  "POST $url HTTP/1.1\r\n". 
							"Host: $proxyServer\r\n";
//				if ( $proxy_user )
//					$reqheader .= "Proxy-Authorization: Basic " . base64_encode ("$proxy_user:$proxy_pass") . "\r\n";

				$reqheader .= "User-Agent: Woodwing SCEServer\r\n".
							"Content-Type: multipart/form-data; boundary=$boundary\r\n".
							"Content-Length: $contentlength\r\n\r\n". 
							$reqbody; 
		
				stream_set_timeout( $fp, $timeout );
				fputs($fp, $reqheader); 
		
				$header = '';
				$stop = false;
			   	do {
					$c = fread($fp,1);
					if ( $c === false || !$c || feof($fp ) ) {
						$stop = true;
					} else {
			   			$header .= $c; 
			   		}
			   	} while (!$stop && !preg_match('/\\r\\n\\r\\n$/',$header));
		
				//In case of a proxy server, "100 Continue" may be answered
		        if ( strpos($header, " 100 Continue") !== FALSE ) 
		        {
		        	//Reset header and continue reading
					$header = '';
				   	do 	{
				   		$header .= fread($fp,1); 
				   	} while (!preg_match('/\\r\\n\\r\\n$/',$header));
				}
		
		        $headerError = ( strpos($header, " 200 OK") === FALSE );
		        if ( $headerError )
		        {
		        	if ( !$header )
		        		$header = '(no (header) result returned from server)';
		        	print "<br><font color='red'>Error: " . $header . "</font>";
		    	}
		    	else
		    	{
		    		print "<br><font color='green'>OK!</font>";
				}
			}
			fclose( $fp );
		}
		print "<br>";
		flush();
	}

	$address = $_POST[ 'address' ];
	$port = $_POST[ 'port' ];

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Enterprise - Test page</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="PRAGMA" content="NO-CACHE" />
        <meta http-equiv="Expires" content="-1" />
		<link rel="stylesheet" href="../../config/templates/woodwingmain.css" type="text/css" />
		<link rel="icon" href="../../config/images/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="../../config/images/favicon.ico" type="image/x-icon" />
	</head>
	<body bgcolor="black">
		<a class="text" href="index.htm" target="_blank">WW test</a>&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="text" href="wwinfo.php" target="_blank">Configuration Overview</a>&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="text" href="phpinfo.php" target="_blank">PHP Info</a>&nbsp;&nbsp;&nbsp;&nbsp;

		<h1><font color="#F6A124" >Connection test</font></h1><br>

		<div style="background: white;">

<table>
<tr>
<td>&nbsp</td>
<td>

<form method='post'>
	<h2>Proxy Server Settings</h2>
	This test page is meant for experimenting and testing and does not use the proxy settings that are optionally stored in the current Enterprise configuration.
	<br>To specify successful proxy server parameters for use by Enterprise, use the <a href='../admin/license/contactinfo.php'>Proxy server settings</a> page.
	<br>
	<table>
	<tr><td>Proxy server (host):</td><td><input name='address' value='<?php echo $address; ?>'></td></tr>
	<tr><td>Proxy port:</td><td><input name='port' value='<?php echo $port; ?>' size='6'></td></tr>
	<tr><td colspan='2'><input type='submit' value='Test'></td></tr>
	</table>
</form>
<hr>
<?php
	print "<h2>Test results</h2>";
	
	if ( WWREGSERVERPORT != '80' )
	{
		$pingUrl1 = str_replace( WWREGSERVERPORT, '80', PINGURL );
		$pingUrl2 = PINGURL;
		if ( $address ) {
			testConnection( $pingUrl1, 'test page 1', $address, $port );
			testConnection( $pingUrl2, 'test page 2', $address, $port );
		} else {
			testConnection( $pingUrl1, 'test page 1' );
			testConnection( $pingUrl2, 'test page 2' );
		}
	}
	else
	{
		if ( $address ) {
			testConnection( PINGURL, '', $address, $port );
		} else {
			testConnection( PINGURL, '' );
		}
	}
	print "<br>Connection test ready.";
?>

	<br><br><a href='index.htm'>WWtest</a>
	<br><a href='../admin/license/contactinfo.php'>Proxy server settings</a>
</td>
<td>&nbsp</td>
</tr>
</table>

	</div>
</body>
</html>