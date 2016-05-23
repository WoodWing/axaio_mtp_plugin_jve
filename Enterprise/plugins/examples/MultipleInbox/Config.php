<?php
/*
** Please be caution that we at least need to setup one v6 system in the $myservers array.
*/
$myservers[]=array(
					'ServerName'	=>	'Enterprise',
					'ServerUrl'		=>	'http://localhost/Enterprise/index.php',
					'username'		=>	'multi',
					'password'		=>	'ww',
					'serverversion'	=>	'v6'
				);

$myservers[]=array(
					'ServerName'	=>	'SCEnterprise',
					'ServerUrl'		=>	'http://localhost/SCEnterprise/index.php',
					'username'		=>	'multi',
					'password'		=>	'ww',
					'serverversion'	=>	'v5'
				);
/*
$myservers[]=array(
					'ServerName'	=>	'SCE429',
					'ServerUrl'		=>	'http://localhost/SCE429/index.php',
					'username'		=>	'woodwing',
					'password'		=>	'ww',
					'serverversion'	=>	'v4'
				);
*/

/*
** Do not edit script below
*/

for($i = 0; $i < sizeof($myservers); $i++){
	if ($myservers[$i]['serverversion']=='v6'){
		$options = array(
			'namespace' => 'urn:SmartConnection',
			'trace' => 1,
			'timeout' => 3600,
			'location' => $myservers[$i]['ServerUrl'],
			'use' => 'literal',
			'capath' => BASEDIR.'/config/encryptkeys/cacert.pem' // for HTTPS / SSL only
		);
		$myservers[$i]['options']=$options;

		$parameterslogon = array('User' => $myservers[$i]['username'], 'Password' => $myservers[$i]['password'], 'Ticket' =>'',
						    		'Server' => $myservers[$i]['ServerName'], 'ClientName' => '', 'Domain' => '','ClientAppName' => 'MultiInbox ContentSource',
    		'ClientAppVersion' => 'v'.SERVERVERSION, 'ClientAppSerial' => '', 'ClientAppProductKey' => '', 'RequestTicket' => true );
    	$myservers[$i]['parameterslogon']=$parameterslogon;
	}else if ($myservers[$i]['serverversion']=='v5'){
		$options = array(
			'namespace' => 'urn:SmartConnection',
			'trace' => 1,
			'timeout' => 3600,
			'location' => $myservers[$i]['ServerUrl'],
			'use' => 'literal',
			'capath' => BASEDIR.'/config/encryptkeys/cacert.pem' // for HTTPS / SSL only
		);
		$myservers[$i]['options']=$options;

		$parameterslogon = array('User' => $myservers[$i]['username'], 'Password' => $myservers[$i]['password'], 'Ticket' =>'',
						    		'Server' => $myservers[$i]['ServerName'], 'ClientName' => '', 'Domain' => '','ClientAppName' => 'MultiInbox ContentSource',
    		'ClientAppVersion' => 'v'.SERVERVERSION, 'ClientAppSerial' => '', 'ClientAppProductKey' => '', 'RequestTicket' => true );
    	$myservers[$i]['parameterslogon']=$parameterslogon;
	}else if ($myservers[$i]['serverversion']=='v4'){
		$options = array('namespace' => 'urn:SmartConnection',
				'trace' => 1,
		'timeout' => 3600,
		'use' => 'literal' );
		$myservers[$i]['options']=$options;

		$parameterslogon = array('User' => $myservers[$i]['username'], 'Password' => $myservers[$i]['password'], 'Ticket' =>'',
		    		'Server' => $myservers[$i]['ServerName'], 'ClientName' => '', 'Domain' => '','ClientAppName' => 'MultiInbox ContentSource',
  		'ClientAppVersion' => 'v'.SERVERVERSION, 'ClientAppSerial' => '');
  		$myservers[$i]['parameterslogon']=$parameterslogon;
	}
}

define("SERVERLIST", serialize($myservers));
define('MultipleInbox_CONTENTSOURCEID', 'Inbox');
define('MultipleInbox_CONTENTSOURCEPREFIX', '_Inbox_');
?>