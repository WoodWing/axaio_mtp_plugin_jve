<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

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
require_once dirname(__FILE__) . '/config.php';

class BrandLogonManager_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_BEFORE; }

	final public function runAfter (WflLogOnRequest $req, WflLogOnResponse &$resp) {} // Not called because we're just doing run before

	final public function runBefore (WflLogOnRequest &$req)	
	{
		LogHandler::Log( 'Brand Logon Manager','DEBUG', 'Entering Logon::runBefore');
		// Get Organization of the user:
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$dbdriver = DBDriverFactory::gen();
		$userRow = DBUser::findUser( null, $req->User, $req->User );
		if( empty($userRow) ) return; // Invalid user, let's normal logon handle this case.

		$userOrg = trim($userRow['organization']);
		global $brandLimits;
		// Get limit of logons for this organizations. If not found we assume there is no limit.
		$limit = 0;
		if( isset( $brandLimits[$userOrg] ) ) {
			$limit = $brandLimits[$userOrg];
		} else {
			// use fallback '*' if exists
			if( isset( $brandLimits['*'] ) ) {
				$limit = $brandLimits['*'];
			}
		}
		// 0 means no limit, so pass to normal logon:
		if( !$limit ) return;		

		// Ok, now we have the limit. First check if this user already has a logon ticket occupied, if so he is already counted
		$shortUser = $userRow['user'];
		$dbt = $dbdriver->tablename("tickets");
		$sql = "SELECT * FROM $dbt WHERE `usr`='$shortUser'";
		$sth = $dbdriver->query($sql);
		if( $sth &&  $dbdriver->fetch($sth) ) {
			// Logon row found, so already counted
			return;
		}

		// User does not yet have a logon, check how many unique users of this brand are logged on:
		$dbt = $dbdriver->tablename("tickets");
		$dbu = $dbdriver->tablename("users");
		$sql = "SELECT count($dbu.id) as usercount FROM $dbt, $dbu WHERE $dbt.usr = $dbu.user AND $dbu.organization='$userOrg' GROUP BY $dbu.id";
		$sth = $dbdriver->query($sql);
		// On empty results we skip the if, no logons, so limit not reached
		if( $sth ) {
			$logonsRow = $dbdriver->fetch($sth);
			if( $logonsRow && $logonsRow['usercount'] >= $limit ) {
				// Limit reached for this brand, throw exception to prevent logon
				$msg = BRANDLOGON_LIMIT_MSG;
				$msg = str_replace( '$limit', $limit, $msg );
				$msg = str_replace( '$userOrg', $userOrg, $msg );
				throw new BizException( 'BRAND_USER_LIMIT', 'Client', $msg, $msg );				
			}
		}
	} 
	final public function runOverruled (WflLogOnRequest $req) 	{$req=$req;} // Not called because we're just doing run before
}
