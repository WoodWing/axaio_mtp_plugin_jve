<?php

/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 * 
 * See OverruleCompatibility.class.php for more info
 */

require_once BASEDIR . '/server/interfaces/services/wfl/WflGetObjects_EnterpriseConnector.class.php';

class ContentStationOverruleCompatibility_WflGetObjects extends WflGetObjects_EnterpriseConnector
{

	final public function getPrio () {	return self::PRIO_DEFAULT; }

	final public function getRunMode () { return self::RUNMODE_AFTER; }

	/**
	 * Converts server overrule issues to Content Station.
	 *
	 * @param WflGetObjectsRequest $req
	 * @param WflGetObjectsResponse $resp
	 */
	final public function runAfter (WflGetObjectsRequest $req, WflGetObjectsResponse &$resp)
	{
		require_once dirname(__FILE__) . '/OverruleCompatibility.class.php';
		if( isset($resp->Objects) && count($resp->Objects) > 0 && 
			OverruleCompatibility::isContentStation($req->Ticket) ) {  // Only fo this for Content Station
			foreach ($resp->Objects as $object) {
				require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
				// Find issue ID and issue name for correct MetaData information
				$issueId = DBTarget::getObjectTargetIssueID($object->MetaData->BasicMetaData->ID);
				if ($issueId !== false) {
					$issueName = DBIssue::getIssueName($issueId);
					if( $issueName != '' && DBIssue::isOverruleIssue( $issueId ) ) {
						$object->MetaData->BasicMetaData->Publication->Id	= OverruleCompatibility::createPubId( $object->MetaData->BasicMetaData->Publication->Id, $issueId );
						$object->MetaData->BasicMetaData->Publication->Name = OverruleCompatibility::createPubName( $object->MetaData->BasicMetaData->Publication->Name, $issueName );
					}
				}
			}
		}
	}

	/**
	 * @param WflGetObjectsRequest $req
	 */
	final public function runBefore (WflGetObjectsRequest &$req) // Not called because we're just doing run after	
	{
		$req = $req; // keep analyzer happy
	}

	/**
	 * @param WflGetObjectsRequest $req
	 */
	final public function runOverruled (WflGetObjectsRequest $req) // Not called because we're just doing run after
	{
		$req = $req; // keep analyzer happy
	}
}
