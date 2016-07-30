<?php
//This module can be used to send an HTTP POST request to a server.
//If necessary, also a proxy server (with authentication) can be specified.

function makeReqBodyFormFields( $dataArr, $boundary )
{
	$reqbody = "";
	foreach($dataArr as $key=>$val)
		$reqbody .= "--$boundary\r\nContent-Disposition: form-data; name=\"$key\"\r\n\r\n" . urldecode($val) . "\r\n";
	return $reqbody;
}

/**
 * Send the fields in the given array as parameters for the POST request (like form fields)
 *
 * @param array $dataArr POST (form) fields
 * @param string $url page to post to
 * @param string[]|string $proxyInfo optional array that specifies proxy server settings
 * @return string The response of the remote server (not including the header), or:
 * in case of an error: a string that starts with "error: ".
 */
function post_it( $dataArr, $url, $proxyInfo='' )
{
   $proxy_host='';
   $proxy_port=0;
   $proxy_user='';
   $proxy_pass='';
   if ( is_array( $proxyInfo ))
   {
      if (isset($proxyInfo[ 'host' ])) {
	      $proxy_host=$proxyInfo[ 'host' ];
      }
      if (isset($proxyInfo[ 'port' ])) {
	      $proxy_port=$proxyInfo[ 'port' ];
      }
      if (isset($proxyInfo[ 'user' ])) {
	      $proxy_user=$proxyInfo[ 'user' ];
      }
      if (isset($proxyInfo[ 'pass' ])) {
	      $proxy_pass=$proxyInfo[ 'pass' ];
      }
   }

   $url2 = preg_replace("@^http://@i", "", $url);
	$host = substr($url2, 0, strpos($url2, "/"));
	$portpos = strpos($host, ":");
	if ( $portpos !== FALSE ) {
		$port = intval(substr($url2, $portpos + 1));
		$host = substr($host, 0, $portpos );
	} else {
		$port = 80;
	}
	//$uri = strstr($url2, '/');

	$boundary="----------------------------" . time();
	$reqbody = makeReqBodyFormFields( $dataArr, $boundary );
	$reqbody .= "--$boundary--\r\n";

	if ( !$proxy_host )
		$proxy_host = $host;
	if ( !$proxy_port )
		$proxy_port = $port;

	$contentlength = strlen($reqbody);
	$reqheader =  "POST $url HTTP/1.1\r\n".
				"Host: $proxy_host\r\n";
	if ( $proxy_user )
		$reqheader .= "Proxy-Authorization: Basic " . base64_encode ("$proxy_user:$proxy_pass") . "\r\n";

	$reqheader .= "User-Agent: Woodwing SCEServer\r\n".
				"Content-Type: multipart/form-data; boundary=$boundary\r\n".
				"Content-Length: $contentlength\r\n\r\n".
				$reqbody;

//print "<br>reqheader:";
//print htmlspecialchars( $reqheader );

	$connectTimeout = WWREGCONNECTTIMEOUT;
	$errstr = '';
	$errno = 0;
	$socket = @fsockopen($proxy_host, $proxy_port, $errno, $errstr, $connectTimeout);
	if (!$socket) {
		//Be sure to specify "error:" (in lowercase); the error can be handled by the caller
		return "error: Connection to $proxy_host (port $proxy_port) failed; errorno=$errno; message=$errstr";
	}

	$timeout = WWREGTIMEOUT;
	stream_set_timeout( $socket, $timeout );
	fputs($socket, $reqheader);

//		$timeout = 20;
//		stream_set_timeout( $socket, $timeout );

	$header = '';
	$stop = false;
      do {
		$c = fread($socket,1);
		if ( $c === false || feof($socket ) ) {
			$stop = true;
		} else {
            $header .= $c;
         }
      } while ( !$stop && !preg_match('/\\r\\n\\r\\n$/',$header));

	//In case of a proxy server, "100 Continue" may be answered
     if ( strpos($header, " 100 Continue") !== FALSE )
     {
      //Reset header and continue reading
		$header = '';
		$stop = false;
	      do 	{
			$c = fread($socket,1);
			if ( $c === false || feof($socket) ) {
				$stop = true;
			} else {
	            $header .= $c;
	         }
	      } while (!$stop && !preg_match('/\\r\\n\\r\\n$/',$header));
	}

     $headerError = ( strpos($header, " 200 OK") === FALSE );
     if ( $headerError )
     {
      if ( !$header )
         $header = '(no (header) result returned from server)';
      $headerlines = explode( "\n", $header );
      $result = 'error: ' . $headerlines[0]; //has HTTP error code in it.
   }
   else
   {
		$result = '';
		// check for chunked encoding
		if (preg_match('/Transfer\\-Encoding:\\s+chunked\\r\\n/i',$header))
		{
			do {
				$byte = "";
				$chunk_size="";
				do
				{
					$chunk_size.=$byte;
					$byte=fread($socket,1);
				} while ($byte!="\r");      // till we match the CR
				$byte=fread($socket,1); // also drop off the LF
				$chunk_size=hexdec($chunk_size); // convert to real number
				if ( $chunk_size )
				{
					$toRead = $chunk_size;
					do {
						$tmp = fread($socket,$toRead);
						$toRead -= strlen( $tmp );
						$result.= $tmp;
					} while ( $toRead > 0  );
				}

				// ditch the CRLF that trails the chunk
				$byte=fread($socket,1); // also drop off the LF
				$byte=fread($socket,1); // also drop off the LF
			} while ($chunk_size);         // till we reach the 0 length chunk (end marker)
		}
		else
		{
			// check for specified content length
			$matches = array();
			if (preg_match('/Content\\-Length:\\s+([0-9]*)\\r\\n/i',$header,$matches))
			{
				$toRead = $matches[1];
				do {
					$tmp = fread($socket,$toRead);
					$toRead -= strlen( $tmp );
					$result.= $tmp;
				} while ( $toRead > 0  );
			}
			else
			{
				// not a nice way to do it (may also result in extra CRLF which trails the real content???)
				while (!feof($socket))
				{
					$tmp = fread($socket, 4096);
					$result .= $tmp;
				}
			}
		}
	}
	// close connection
	fclose($socket);

//		$result = $header . $result;

	return $result;
}