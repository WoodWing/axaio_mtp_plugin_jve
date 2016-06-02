<?php
/*
=======================================================================
 Bestand:		classs.mollie.php
 Description:	See sendSMS.php for functionality.
 Created:		16-01-2005
 Author:		Mollie B.V.
 Ver:			  v 2.1 29-10-2007
 Thanks to: Laurens van Alphen (www.keenondots.com)
 
 More information? Go to www.mollie.nl
========================================================================
 Possible returns:
========================================================================
 10 - succesfully sent
 20 - no 'username' given
 21 - no 'password' given
 22 - no or incorrect 'originator' given
 23 - no 'recipients' given
 24 - no 'message' given
 25 - no juiste 'recipients' given
 26 - no juiste 'originator' given
 27 - no juiste 'message' given
 29 - wrong parameter(s)
 30 - incorrect 'username' or 'password'
 31 - not enough credits
========================================================================
*/

class mollie {
	var $username	  	= null;
	var $password	  	= null;
	var $gateway	  	= 1;
	var $originator		= null;
	var $resultcode		= null;
	var $resultmessage	= null;
	var $success	  	= false;
	var $successcount	= 0;
	var $recipients		= array();
	var $reference    = '';
	
	function setGateway($gateway) {
		$this->gateway = $gateway;
	}
	
	function setLogin($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}
	
	function setOriginator($originator) {
		$this->originator = $originator;
	}
	
	function addRecipients($recipient) {
		array_push($this->recipients, $recipient);
	}
	
	function setReference ($reference) {
	  $this->reference = $reference;
	}
	
	function sendSMS($message) {
		$recipients = implode(',', $this->recipients);
		
		$result = $this->sendToHost('www.mollie.nl', '/xml/sms/',
							 		'gateway='.urlencode($this->gateway).
							 		'&username='.urlencode($this->username).
							 		'&password='.urlencode($this->password).
							 		'&originator='.urlencode($this->originator).
							 		'&recipients='.urlencode($recipients).
							 		'&message='.urlencode($message) .
							 		(($this->reference != '') ? '&reference='.$this->reference : '')
							 		);

		if( !empty($result) ) {
			$this->recipients = array();
			
			list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
			$data = simplexml_load_string($xml);
			
			$this->success = ($data->item->success == 'true');
			$this->successcount = $data->item->recipients;
			$this->resultcode = $data->item->resultcode;
			$this->resultmessage = $data->item->resultmessage;
		} else {
			$this->success = ($data->item->success == 'false');
			$this->successcount = 0;
			$this->resultcode = 0;
			$this->resultmessage = 'No response from SMS gateway Mollie';
		}
	}
	
	function sendToHost($host,$path,$data) {
		$fp  = @fsockopen($host,80);
		$buf = '';
		if ($fp) {
			@fputs($fp, "POST $path HTTP/1.0\n");
			@fputs($fp, "Host: $host\n");
			@fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
			@fputs($fp, "Content-length: " . strlen($data) . "\n");
			@fputs($fp, "Connection: close\n\n");
			@fputs($fp, $data);
			while (!feof($fp)) {
			  $buf .= fgets($fp,128);
			}
			fclose($fp);
		}
		return $buf;
	}
}
?>