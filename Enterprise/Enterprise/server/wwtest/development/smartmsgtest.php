<?php

/** SCEntMessenger uses a connectionless UDP socket to transmit binary to its destination.
  *
  * Example of use:
  *
  * $m = new SCEntMessenger();
  * $m->set_destination("192.168.1.5", 3890);
  * $m->send("just an example");
  *
  * Since it is connectionless, you can change the destination address/port at any time.
  * If you are having problems establishing communication, it may be due to a bad address,
  * improper setup of the IP routing table, or a problem on the other end.  When in doubt,
  * use tcpdump or ethereal to check that packets are indeed being transmitted.
  */
class SCEntMessenger
{
	private $sock = NULL;
	private $address = NULL;
	private $port = NULL;

	function SCEntMessenger($address = NULL, $port = NULL)
	{
		$this->address = $address;
		$this->port = $port;

		if(($this->sock = socket_create(AF_INET, SOCK_DGRAM, 0)) < 0) {
			$this->error("Could not create datagram socket.");
		}
	}

	/**
	 * Destructor function, usually not needed, provided in case you want to free the socket.
	 */
	function destroy()
	{
		socket_close($this->sock);
	}

	// You can enable this part if you have PHP 4.3.0 or later...
	function enable_broadcast()
	{
		if( socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1) < 0 ) {
			$this->error("Failed to enable broadcast option.");
		}
	}

	function disable_broadcast()
	{
		if( socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 0) < 0 ) {
			$this->error("Failed to disable broadcast option.");
		}
	}

	/**
	 * Address is an IP address, given as a string.
	 * To convert a hostname to IP, use gethostbyname('www.example.com')
	 * You must also specify a port as an integer, typically $port is larger than 1024.
	 */
	function set_destination($address, $port)
	{
		$this->address = $address;
		$this->port = $port;
	}

	/**
	 * send() accepts either an OSCDatagram object or a binary string
	 */
	function send($message)
	{
		if(is_null($this->address) || is_null($this->port)) {
			$this->error("Destination is not well-defined.  Please use SCEntMessenger::set_destination().");
		}
		if(is_object($message)) {
			$message = $message->get_binary();
		}
		if(($ret = socket_sendto($this->sock, $message, strlen($message), 0, $this->address, $this->port)) < 0) {
			$this->error("Transmission failure.");
		}
		if($ret != strlen($message)) {
			$mlen = strlen($message);
			$this->error("Could not send the entire message, only $ret bytes were sent, of $mlen total");
		}
		return $ret;
	}

	/** 
	 * Report a fatal error.
	 */
	function error($message)
	{
		trigger_error("SCEntMessenger Error: $message", E_USER_ERROR);
	}
}

function strhex($string)
{
   $hex="";
   for ($i=0;$i<strlen($string);$i++)
       $hex.=(strlen(dechex(ord($string[$i])))<2)? "0".dechex(ord($string[$i])): dechex(ord($string[$i]));
   return $hex;
}

function hexstr($hex)
{
   $string="";
   for ($i=0;$i<strlen($hex)-1;$i+=2)
       $string.=chr(hexdec($hex[$i].$hex[$i+1]));
   return $string;
}

function MessageField( $id, $data )
{
	$field = "";
	$field .= chr(0);
	$field .= chr( strlen( $id ) );
	$field .= $id;
	$field .= chr(0);
	$field .= chr( strlen( $data ) );
	$field .= $data;
	return $field;
}

$m = new SCEntMessenger('255.255.255.255', 8092);
$m->enable_broadcast();

$msg = "";                    
$msg .= chr(1); // format  > initial   
$msg .= chr(12); // event   > 3 = CreateObject, 4 = DeleteObject, 5 = SaveObject, 12 = SendMessage
$msg .= chr(3); // type    > 1 = system, 2 = client, 3 = user
$msg .= chr(0); // reserved   

/*$msg .= MessageField( "ID", "45" );
$msg .= MessageField( "Name", "Frédéric©" );
$msg .= MessageField( "Comment", "this is a message" );
$msg .= MessageField( "Type", "Article" );
$msg .= MessageField( "Size", "123" );*/

$msg .= MessageField( "UserID", "woodwing" );
$msg .= MessageField( "ObjectID", "67" );
$msg .= MessageField( "MessageID", "12" ); // 12=SendMessage
$msg .= MessageField( "MessageType", "" );
$msg .= MessageField( "Message", "hello world" );
$msg .= MessageField( "TimeStamp", "2004_10_06@14h57" );

$m->send( $msg );             
