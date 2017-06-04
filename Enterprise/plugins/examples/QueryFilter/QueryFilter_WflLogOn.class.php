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

class QueryFilter_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }
	
	// Not called.
	final public function runBefore( WflLogOnRequest &$req )
	{
	}

	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp )
	{
		LogHandler::Log( 'QueryFilter', 'DEBUG', 'Called: QueryFilter_WflLogOn->runAfter()' );

		// Determine if current user has admin rights.
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$isAdmin = DBUser::isAdminUser( BizSession::getShortUserName() );
		
		// When current user has no admin rights, it is an end-user (normal user).
		// Only for end-users, hide the Named Queries having a "$" prefix.
		if( !$isAdmin ) {
			$namedQueries = array();
			if( $resp->NamedQueries ) foreach( $resp->NamedQueries as $namedQuery ) {
				if( $namedQuery->Name[0] == '$' ) { // compares first char only
					// Do nothing, which hides the query!
					LogHandler::Log( 'QueryFilter', 'INFO', 'Hiding the Named Query "'.$namedQuery->Name.'" '.
						'from user because it is prefixed with the "$" character '.
						'(and because the user has no admin rights).' );
				} else { // Show query
					$namedQueries[] = $namedQuery;
				}			
			}
			$resp->NamedQueries = $namedQueries;
		}		

		LogHandler::Log( 'QueryFilter', 'DEBUG', 'Returns: QueryFilter_WflLogOn->runAfter()' );
	} 
	
	// Not called.
	final public function runOverruled( WflLogOnRequest $req )
	{
	}
}
