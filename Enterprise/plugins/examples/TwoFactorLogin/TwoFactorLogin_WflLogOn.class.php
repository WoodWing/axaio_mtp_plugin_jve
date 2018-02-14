<?php
/****************************************************************************
   Copyright 2013 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class TwoFactorLogin_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_BEFORE; }

	final public function runBefore( WflLogOnRequest &$req )
	{
		LogHandler::Log( 'TwoFactorLogin', 'INFO', 'TwoFactorLogin LogOn runBefore' );
		$user = $req->User;
		if ($user == 'woodwing') {
			return;
		}

		require_once BASEDIR.'/config/plugins/SMS/config.php';
		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		
		// CS does not allow to enter empty password field.
		// Therefore we assume that when "?" is typed, the password over SMS is requested too.
		if( $req->Password == '?' ) {
			$req->Password = '';
		}
		
		// checkUser() throws BizException when credentials are not valid.
		// We want this to happen, but only when password was typed by user.
		if( $req->Password == '' ) {
			$checkUser = DBUser::getUser( $user );
		} else {
			$checkUser = DBUser::checkUser( $user );
		}
		
		// Calculate the time difference between now and the expiry date configured for
		// the user. Note that this field is optional so it needs to be handled with care.
		if( $checkUser['expirepassdate'] ) {
			$diff = time() - strtotime($checkUser['expirepassdate']); 
		} else {
			$diff = -1;
		}
			
		// Send SMS to user when password is left empty or when expired.
		if( $diff >= 0 || $req->Password == '' ) {
			$smsRecipient  = trim($checkUser['location']);
			if( $smsRecipient ) {
				$passw = self::generatePassword(6,4);
							
				if (!class_exists('mollie')) {
					require_once BASEDIR . '/config/plugins/SMS/class.mollie.php';
				}
				
				$sms = new mollie();
			
				// Select gateway, set logon info and orginator, all from config.php
				$sms->setGateway	( WWSMS_GATEWAY );
				$sms->setLogin		( WWSMS_USERNAME, WWSMS_PASSWORD );
				$sms->setOriginator	( WWSMS_SENDER );
				$sms->addRecipients	( $smsRecipient );
				$sms->sendSMS		( $passw );
				
				DBUser::setPassword( $user, crypt($passw), 1.0/24/60);	// 1 minute time to use the new password
				
				throw new BizException( 'NEW_PASSWORD', 'Client', null, 'New password sent by SMS' );
			}
		}
	} 
	
	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp ) 
	{
	}
	
	final public function runOverruled( WflLogOnRequest $req )
	{
	}
	
	private static function generatePassword( $length=6, $strength=0 )
	{
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if ($strength & 1) {
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if ($strength & 2) {
			$vowels .= "AEUY";
		}
		if ($strength & 4) {
			$consonants .= '23456789';
		}
		if ($strength & 8) {
			$consonants .= '@#$%';
		}
	
		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}
}
