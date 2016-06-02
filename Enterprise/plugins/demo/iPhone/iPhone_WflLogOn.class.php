<?php

/**
 * @package 	iPhone service plug-in for Enterprise
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class iPhone_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_AFTER; }

	final public function runBefore( WflLogOnRequest &$req )
	{
		$req=$req;
		// not called
	}
	
	final public function runAfter( WflLogOnRequest $req, WflLogOnResponse &$resp ) 
	{
		// Only do this for iPhone clients:
		$app = DBTicket::DBappticket( BizSession::getTicket() );
		if( $app == 'iPhone' ) {
			LogHandler::Log( 'iPhone', 'DEBUG', 'Filtering output for iPhone app' );
			
			foreach( $resp->Publications as $pub ) {
				$pub->Sections = null;
				$pub->States = null;
				$pub->Issues = null;
				$pub->ObjectTypeProperties = null;
				$pub->ActionProperties = null;
				$pub->Editions = null;
				$pub->FeatureAccessList = null;
				$pub->Categories = null;
			}
			$resp->NamedQueries = null;
			$resp->FeatureSet = null;
			$resp->LimitationSet = null;
			$resp->ServerInfo = null;
			$resp->Settings = null;
			$resp->Users = null;
			$resp->UserGroups = null;
			$resp->Membership = null;
			$resp->ObjectTypeProperties = null;
			$resp->ActionProperties = null;
			$resp->Terms = null;
			$resp->FeatureProfiles = null;
			$resp->Messages = null;
			$resp->TrackChangesColor = null;
		}
	}
	
	final public function runOverruled( WflLogOnRequest $req ) 
	{
		$req=$req;
		// not called
	}
}
