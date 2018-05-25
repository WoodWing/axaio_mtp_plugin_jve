<?php
/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflLogOn_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflLogOn extends WflLogOn_EnterpriseConnector
{
	final public function getPrio () 	{	return self::PRIO_DEFAULT; 	}
	final public function getRunMode () {	return self::RUNMODE_AFTER; }

	final public function runAfter (WflLogOnRequest $req, WflLogOnResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		// Bail out if no publications or if client app is not Content Station or if there are no overrule issues at all.
		if( empty( $resp->Publications ) || count($resp->Publications) < 1 || !OverruleCompatibility::isContentStation($resp->Ticket) ) {
			return;
		}
		$somethingDone = false;
		$pubCount = count( $resp->Publications );
		for( $p=$pubCount-1; $p>=0; $p-- ) { // Don't use foreach to have index to pass to child function, start at end to allow deletion during the loop
			$pub = $resp->Publications[$p];

			// Walk thru pub channels looking for overrule issues:
			$pubChannels = $pub->PubChannels;
			if( !empty($pubChannels) && count($pubChannels) >0 ) { // double check just to be sure in case we get wrong input
				$pubChannelCount = count( $pubChannels );
				for( $pc=$pubChannelCount-1; $pc>=0; $pc-- ) { //Walk thru all channels, start at end to allow deletion during the loop
					if( $pubChannels[$pc]->Type == 'print' ||
						$pubChannels[$pc]->Type == 'dps2') { // Only print related channels can have overrule issues
						$issues = $pubChannels[$pc]->Issues;
						if( !empty($issues) && count( $issues) > 0 ) { // double check just to be sure in case we get wrong input
							$issueCount = count( $issues );
							for( $is=$issueCount-1; $is>=0; $is-- ) { //Walk thru all issues, start at end to allow deletion during the loop
								if( !empty($issues[$is]->OverrulePublication) && $issues[$is]->OverrulePublication == true ) {
									$this->handleOverruleIssue( $resp, $p, $pc, $is );
									$somethingDone = true; // remember we have done something, which means we need to rebuild the publications array
								}
							}
						}
					}
				}
			}
		}

		if( $somethingDone ) {
			// rebuild publications array to prevent missing entries at integer based index, for example an array that does not have element [0]
			// this would make php soap fail to build proper soap response
			$newPubs = array();
			foreach( $resp->Publications as $pub ) {
				$newPubs[] = $pub;
			}
			$resp->Publications = $newPubs;
		}
	}
	
	/**
	 * Converts logon response for overrule issue specified with publication/channel/issue indexes
	 *
	 * @param 	WflLogOnResponse $resp
	 * @param	integer $p 	publication's index in $resp->Publications
	 * @param	integer $pc publication channel's index in $resp->Publications[$p]
	 * @param	integer $is issue's index in $resp->Publications[$p]->PubChannels[$pc]
	 * @returns void
	 */
	private function handleOverruleIssue( WflLogOnResponse &$resp, $p, $pc, $is )
	{
		LogHandler::Log('ContentStationOverruleCompatibility_WflLogOn','DEBUG','Arrange compatibility for overrule issue ');
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';

		$overruleIssue = $resp->Publications[$p]->PubChannels[$pc]->Issues[$is];
		
		// Create new dummy publication to replace overrule issue
		$newPub = clone $resp->Publications[$p];
		$newPub->Id 			= OverruleCompatibility::composePubId($newPub->Id, $overruleIssue->Id);
		$newPub->Name 			= OverruleCompatibility::composePubName( $newPub->Name, $overruleIssue->Name);
		$newPub->Sections 		= $overruleIssue->Sections;
		$newPub->States 		= $overruleIssue->States;
		
		// Content Station gets Issues and Editions from PubChannels, so set to null at this level:
		$newPub->Editions 		= null;
		$newPub->Issues 		= null;
		
		$editions = array();//$overruleIssue->Editions;
		foreach( $overruleIssue->Editions as $ed ) {
			$editions[] = $ed;
		}
		if( $resp->Publications[$p]->PubChannels[$pc]->CurrentIssue == $overruleIssue->Id ) {
			$newPub->CurrentIssue 	= $overruleIssue->Id;
		} else {
			$newPub->CurrentIssue 	= null;
		}
		
		// Get Categories (from sections) for publication level:
		$newPub->Categories = $overruleIssue->Sections;
		
		$newPub->ReversedRead = $overruleIssue->ReversedRead; // Added since v7.6.0.
		
		// Remove all obsolete pubchannels and issues & editions (all but our overrule issue)
		$newPub->PubChannels = Array( clone $newPub->PubChannels[$pc] ) ; // clone to prevent references that will be remobed below
		$newPub->PubChannels[0]->Issues = Array( clone $newPub->PubChannels[0]->Issues[$is] ) ; // clone to prevent references that will be remobed below
		$newPub->PubChannels[0]->Editions = $overruleIssue->Editions;

		// We need to rest Editions, Sections and states, because we moved them and having the at both locations will give reference problems in XML
		$newPub->PubChannels[0]->Issues[0]->Editions = null;
		$newPub->PubChannels[0]->Issues[0]->Sections = null;
		$newPub->PubChannels[0]->Issues[0]->States = null;

		// Move applicable access profiles to overrule brand issue and remove them from the brand itself. EN-85035
		$newFeatureAccessList = array();
		foreach($newPub->FeatureAccessList as $key => $featureAccess) {
			if($featureAccess->Issue==$overruleIssue->Id) {
				$newFeatureAccessList[] = $featureAccess;
				unset($resp->Publications[$p]->FeatureAccessList[$key]);
			}
		}
		$newPub->FeatureAccessList = $newFeatureAccessList;

		// Add our dummy to the publications tree
		$resp->Publications = array_merge( array_slice( $resp->Publications, 0, $p+1 ), array($newPub), array_slice( $resp->Publications, $p+1 ) ); 
		
		// Remove original overrule issue
		unset( $resp->Publications[$p]->PubChannels[$pc]->Issues[$is] );
		// rebuild issues array to prevent missing entries at integer based index, for example an array that does not have element [0]
		// this would make php soap fail to build proper soap response
		$newIssues = array();
		foreach( $resp->Publications[$p]->PubChannels[$pc]->Issues as $issue ) {
			$newIssues[] = $issue;
		}
		$resp->Publications[$p]->PubChannels[$pc]->Issues = $newIssues;
		
		// Check if channels and pub become empty, if so remove them as well
		if( empty( $resp->Publications[$p]->PubChannels[$pc]->Issues ) || count( $resp->Publications[$p]->PubChannels[$pc]->Issues )==0 ) {
			unset( $resp->Publications[$p]->PubChannels[$pc] );
			if( empty( $resp->Publications[$p]->PubChannels ) || count( $resp->Publications[$p]->PubChannels )==0 ) {
				unset( $resp->Publications[$p] );
			} else {
				// rebuild publications array to prevent missing entries at integer based index, for example an array that does not have element [0]
				// this would make php soap fail to build proper soap response
				$newPubChannels = array();
				foreach( $resp->Publications[$p]->PubChannels as $pubchannel ) {
					$newPubChannels[] = $pubchannel;
				}
				$resp->Publications[$p]->PubChannels = $newPubChannels;
			}
		}
	}

	final public function runBefore( WflLogOnRequest &$req ) {} // Not called because we're just doing run after
	
	final public function runOverruled( WflLogOnRequest $req ) {} // Not called because we're just doing run after
}
