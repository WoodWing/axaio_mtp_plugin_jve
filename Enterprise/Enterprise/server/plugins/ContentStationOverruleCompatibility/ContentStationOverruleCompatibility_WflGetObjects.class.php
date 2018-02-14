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
		require_once __DIR__ . '/OverruleCompatibility.class.php';
		if( isset( $resp->Objects ) && count( $resp->Objects ) > 0 &&
			OverruleCompatibility::isContentStation($req->Ticket) ) {  // Only for this for Content Station
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
			require_once BASEDIR . '/server/dbclasses/DBTarget.class.php';
			foreach ($resp->Objects as $object) {
				if( isset( $object->MetaData->BasicMetaData->Publication ) ) {
					// Find issue ID and issue name for correct MetaData information
					$issueId = DBTarget::getObjectTargetIssueID( $object->MetaData->BasicMetaData->ID );
					if( $issueId !== false ) {
						$issueName = DBIssue::getIssueName( $issueId );
						if( $issueName != '' && DBIssue::isOverruleIssue( $issueId ) ) {
							$object->MetaData->BasicMetaData->Publication->Id = OverruleCompatibility::composePubId( $object->MetaData->BasicMetaData->Publication->Id, $issueId );
							$object->MetaData->BasicMetaData->Publication->Name = OverruleCompatibility::composePubName( $object->MetaData->BasicMetaData->Publication->Name, $issueName );
						}
					}
				}
			}
		}
	}

	/**
	 * @param WflGetObjectsRequest $req
	 */
	final public function runBefore (WflGetObjectsRequest &$req) {}

	/**
	 * @param WflGetObjectsRequest $req
	 */
	final public function runOverruled (WflGetObjectsRequest $req) {}
}
