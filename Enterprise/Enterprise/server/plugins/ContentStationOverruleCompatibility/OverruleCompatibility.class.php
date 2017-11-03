<?php

/**
 * @package 	ContentStationOverruleCompatibility
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Content Station is not aware of overrule issues. This feature will be replaced in the future, therefore
 * this server plug-in implements a compatibility layer that creates a fake publication out of each 
 * overrule issues. This way for Content Station it's all as expected.
 * The fake publications have id's in this format ':<pubid>:<issueid>' We start with : to make detection as
 * easy as detecting the first character. NOTE: Content Station also checks for this starting : in handling
 * of GetDialog (since v6.1 build 94) so this cannot be changed.
 */

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';
require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';

class OverruleCompatibility
{
	/**
	 * Returns true if caller is Content Station
	 *
	 * @param string $ticket
	 * @returns boolean
	 */
	static public function isContentStation( $ticket )
	{	
		require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
		$app = DBTicket::DBappticket($ticket);
		return (bool)stristr($app, 'content station'); // Can be something like 'Content Station v7.0'
	}

	/**
	 * Returns true if specified publication id is a fake overrule publication
	 *
	 * @param string $pubId
	 * @returns boolean
	 */
	static public function isOverrulePub( $pubId )
	{
		return (isset($pubId[0]) && $pubId[0] == ':');
	}

	/**
	 * Returns Enterprise publication out of fake overrule publication id
	 *
	 * @param string $pubId
	 * @returns string Publication id
	 */
	static public function getPublication( $pubId )
	{
		$fragments = explode( ':', $pubId );
		return $fragments[1];
	}
	
	/**
	 * Returns Enterprise (overrule) issue out of fake overrule publication id
	 *
	 * @param string $pubId
	 * @returns string (Overrule) Issue id
	 */
	static public function getIssue( $pubId )
	{
		$fragments = explode( ':', $pubId );
		return $fragments[2];
	}
	
	/**
	 * Creates fake publication id out of Publication Id and (overrule) issue id
	 *
	 * @param string $pubId		
	 * @param string $issueId
	 * @returns string
	 */
	static public function createPubId( $pubId, $issueId )
	{
		return ":$pubId:$issueId"; 
	}
	
	/**
	 * Creates fake publication name out of Publication name and (overrule) issue name
	 *
	 * @param string $pubName
	 * @param string $issueName
	 * @returns string
	 */
	static public function createPubName( $pubName, $issueName )
	{
		return "$pubName $issueName";
	}

	/**
	 * Creates array of PropertyValue out of array of an object[with Id, Name field]
	 *
	 * @param Object[] $objects
	 * @returns PropertyValue[]
	 */
	public static function createPropertyValues( $objects )
	{
		$propertyValues = Array();
		foreach( $objects as $object ) {
			$propertyValues[] = new PropertyValue( $object->Id, $object->Name );
		}
		return $propertyValues;
	}

	/**
	 * Converts object before it's send to the server to replace fake pubids with real pub ids etc.
	 *
	 * Note: instead of Object you can also pass in CopyObjectRequest or SetPropertyRequest, all 
	 * they need to have in common is MetaData and Targets underneath
	 * Note: the caller does not have to worry if client app is Content Station
	 * The param $overruleIssues is filled and needs to be passed to the convertObjectAfter
	 *
	 * @param string $ticket
	 * @param Object $object in/out object to be manipulated, see comment above
	 * @param array $overruleIssues in/out array to keep track of overruleIssues (key) and the associated pub (val)
	 */
	static public function convertObjectBefore( $ticket, &$object, &$overruleIssues )
	{
		if( self::isContentStation( $ticket ) ) {
			if( isset( $object->MetaData->BasicMetaData->Publication->Id ) ) { // For PublishForm, there will be no Object passed in.
				if( self::isOverrulePub( $object->MetaData->BasicMetaData->Publication->Id ) ) {
					LogHandler::Log('ContentStationOverruleCompatibility','DEBUG','Intercepting overrule pub - before');
					$overrulePubId = $object->MetaData->BasicMetaData->Publication->Id;
					$object->MetaData->BasicMetaData->Publication = new Publication( self::getPublication( $overrulePubId ) );
					$overruleIssueId = self::getIssue( $overrulePubId );

					// If not targets set, set issue as target:
					if( empty( $object->Targets ) ) {
						require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
						require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
						// Comment/Uncomment this to allow or disallow sending issue-less overruled brands from Content Station.
						// Allowing this here can result in invalid scenarios (i.e. not matching statuses and
						// categories for the objects).
						// When not allowed, the metadata validation prevents creating/saving issue-less
						// objects for overruled issues and throws an error to the client.
						$issue = new Issue($overruleIssueId, DBIssue::getIssueName($overruleIssueId), true);
						$pubChannel = BizPublication::getChannelForIssue( $overruleIssueId );
						$object->Targets = array( new Target($pubChannel, $issue) );

					} else if( count( $object->Targets ) == 1 && $object->Targets[0]->Issue->Id == $overruleIssueId ) {
						$object->Targets[0]->Issue->OverrulePublication = true;
					}

					// Save for runAfter that this is overrule issue:
					$overruleIssues[$overruleIssueId] = $overrulePubId;
				}
			}
		}
	}

	/**
	 * Converts array of objects before it's send to the server to replace fake pubids with real pub ids etc.
	 *
	 * The array returned = needs to be passed to the convertObjectsAfter
	 * Note: the caller does not have to worry if client app is Content Station
	 *
	 * @param string $ticket
	 * @param Object[] $objects in/out objects to be manipulated, see comment above
	 * @returns array $overruleIssues in/out array to keep track of overruleIssues (key) and the associated pub (val)
	 */
	static public function convertObjectsBefore( $ticket, &$objects )
	{
		$overruleIssues = null;
		if( self::isContentStation( $ticket ) ) {
			$overruleIssues = array();
			foreach( $objects as $object ) {
				self::convertObjectBefore( $ticket, $object, $overruleIssues );
			}
		}
		return $overruleIssues;
	}
	
	/**
	 * Converts object after it's received from server to replace real pub ids with fake pubids for overrule issues.
	 *
	 * Note: instead of Object you can also pass in CopyObjectResponse or SetPropertyResponse, all 
	 * they need to have in common is MetaData and Targets underneath
	 * Note: the caller does not have to worry if client app is Content Station
	 * The param $overruleIssues is the one filled in by convertObjectBefore
	 *
	 * @param string $ticket
	 * @param Object $object in/out object to be manipulated, see comment above
	 * @param array $overruleIssues as filled in by convertObjectBefore
	 */
	static public function convertObjectAfter( $ticket, &$object, $overruleIssues )
	{
		if( self::isContentStation( $ticket ) ) {
			if( !empty( $object->Targets ) ) {
				if( isset( $object->Targets[0]->Issue->Id ) && array_key_exists( $object->Targets[0]->Issue->Id, $overruleIssues ) ) {
					LogHandler::Log('ContentStationOverruleCompatibility','DEBUG','Intercepting overrule pub - after');
					$object->MetaData->BasicMetaData->Publication->Id	= OverruleCompatibility::createPubId( $object->MetaData->BasicMetaData->Publication->Id, $object->Targets[0]->Issue->Id );
					$object->MetaData->BasicMetaData->Publication->Name = OverruleCompatibility::createPubName( $object->MetaData->BasicMetaData->Publication->Name, $object->Targets[0]->Issue->Name );
				}
			}
		}
	}

	/**
	 * Converts array of objects after it's received from server to replace real pub ids with fake pubids for overrule issues.
	 *
	 * The param $overruleIssues is the one returned by convertObjectBefore
	 * Note: the caller does not have to worry if client app is Content Station
	 *
	 * @param string $ticket
	 * @param Object[] $objects in/out objects to be manipulated, see comment above
	 * @param array $overruleIssues as filled in by convertObjectBefore
	 */
	static public function convertObjectsAfter( $ticket, &$objects, $overruleIssues )
	{
		if( self::isContentStation( $ticket ) ) {
			foreach( $objects as $object ) {
				self::convertObjectAfter( $ticket, $object, $overruleIssues );
			}
		}
	}
	
	/**
	 * Converts query results to change pub id for overrule issues to fake pub ids.
	 *
	 * Note we do not change the publication name, because Content Station uses this from the logon response
	 * In order for this to work we need to have issueId per row. If this is not there while we do have overrule issues
	 * we throw an exception. This way it's very clear that something needs to get fixed instead of vague silent errors.
	 *
	 * @param string $ticket
	 * @param WflQueryObjectsResponse $resp in/out response to be manipulated, see comment above
	 * @throws BizException when a configuration error is encountered
 	*/
	static public function convertQueryResults( $ticket, &$resp )
	{
		if( self::isContentStation( $ticket ) ) {
			// Get all overrule issues:
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
			require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
			$overruleIssues = DBIssue::listAllOverruleIssuesWithPub();
			if( !empty($overruleIssues) ) {
				// Walk thru overrule issue array to convert publication ids to our fake overrule pubs
				$newOverruleIssues = array();
				foreach( $overruleIssues as $issueId => $pubId ) {

					$newOverruleIssues[$issueId]['pubId'] = OverruleCompatibility::createPubId( $pubId, $issueId );

					$pubName = DBPublication::getPublicationName( $pubId );
					$issueName = DBIssue::getIssueName( $issueId );
					$newOverruleIssues[$issueId]['pubName'] = OverruleCompatibility::createPubName( $pubName, $issueName );
				}

				$pubIdCol 	= -1;
				$pubNameCol = -1;
				$issueIdCol	= -1;

				$nCols = count( $resp->Columns );
				for( $i=0; $i < $nCols; $i++ ) { // Trying to find columns for publication and issue
					if( $resp->Columns[$i]->Name == 'PublicationId' ) {
						$pubIdCol = $i;
					} else if( $resp->Columns[$i]->Name == 'Publication' ) {
						$pubNameCol = $i;
					} else if( $resp->Columns[$i]->Name == 'IssueId' ) {
						$issueIdCol = $i;
					}
				}

				// If the query returns a PubId, but no IssueId we cannot convert PubId, so raise an error
				// that the query is incompatible with Content Station
				if( $pubIdCol != -1 && $issueIdCol == -1 ) {
					$msg = 'Contact your System Administrator. Query definition invalid (missing IssueId) for Content Station in combination with overrule publications.';
					throw new BizException( 'Config Error', 'Server', $msg, $msg );
				} elseif( $pubIdCol != -1 && $issueIdCol != -1 ) {
					// We only need to convert PubId if the PubId AND IssueId are part of the columns.
					$nRows = count( $resp->Rows ) ;
					for( $i = 0; $i < $nRows; $i++ ) {
						$issueId = $resp->Rows[$i][$issueIdCol];
						if( array_key_exists( $issueId, $newOverruleIssues ) ) {
							$resp->Rows[$i][$pubIdCol] = $newOverruleIssues[$issueId]['pubId'];
							$resp->Rows[$i][$pubNameCol] = $newOverruleIssues[$issueId]['pubName'];
						}
					}
				}

				// And handle children for hierarchical queries:
				$nChildCols = count( $resp->ChildColumns );
				$nChildRows = count( $resp->ChildRows ) ;
				
				// We can stop in case we don't have child columns/rows
				if( $nChildCols == 0 || $nChildRows == 0 ) {
					return;
				}

				$pubIdChildCol = -1;
				$pubNameChildCol = -1;
				$issueIdChildCol = -1;
				for( $i = 0; $i < $nChildCols; $i++ ) { // Trying to find columns for publication and issue
					if( $resp->ChildColumns[$i]->Name == 'PublicationId' ) {
						$pubIdChildCol = $i;
					} else if( $resp->ChildColumns[$i]->Name == 'Publication' ) {
						$pubNameChildCol = $i;
					} else if( $resp->ChildColumns[$i]->Name == 'IssueId' ) {
						$issueIdChildCol = $i;
					}
				}

				// If child query returns PubId, but no IssueId we cannot convert PubId, so raise error
				// that query is incompatible with Content Station
				if( $pubIdChildCol != -1 && $issueIdChildCol == -1 ) {
					$msg = 'Contact your System Administrator. Query definition invalid (missing child IssueId) for Content Station in combination with overrule publications.';
					throw new BizException( 'Config Error', 'Server', $msg, $msg );
				} elseif( $pubIdChildCol != -1 && $issueIdChildCol != -1 ) {
					// We only need to convert PubId if the PubId AND IssueId are part of the columns.
					for( $i=0; $i < $nChildRows; $i++ ) {
						$issueId = isset($resp->ChildRows[$i], $resp->ChildRows[$i]->Row[$issueIdChildCol]) ? $resp->ChildRows[$i]->Row[$issueIdChildCol] : null;
						if( !is_null($issueId) && array_key_exists( $issueId, $newOverruleIssues ) ) {
							$resp->ChildRows[$i]->Row[$pubIdChildCol] = $newOverruleIssues[$issueId]['pubId'];
							$resp->ChildRows[$i]->Row[$pubNameChildCol] = $newOverruleIssues[$issueId]['pubName'];
						}
					}
				}
			}
		}
	}
}
