<?php
/**
 * @package    Enterprise
 * @subpackage BizClasses
 * @since      v10.2
 * @copyright  WoodWing Software bv. All Rights Reserved.
 *
 * Business logics and rules that can handle admin routing objects operations.
 * This is all about the configuration of routing rules in the workflow definition.
 *
 * This class provides functions for validation and validates the user access and the user input that is sent
 * in a request. Only if everything is valid an operation will be performed on the data.
 */

class BizAdmRouting
{
	/**
	 * Checks if an user has admin access to the publication. System
	 * admins have access to all pubs.
	 *
	 * @param integer $pubId The publication id. Null to check if user is admin in some or more publications (just any).
	 * @throws BizException When user has no access.
	 */
	private static function checkPubAdminAccess( $pubId )
	{
		$user = BizSession::getShortUserName();
		$dbDriver = DBDriverFactory::gen();
		$isPubAdmin = hasRights( $dbDriver, $user ) || // system admin?
			( publRights( $dbDriver, $user ) && checkPublAdmin( $pubId, false ) ); // explicit pub admin?

		if( !$isPubAdmin ) {
			throw new BizException( 'ERR_AUTHORIZATION', 'Client', null );
		}
	}

	/**
	 * Validates all properties of a Routing object.
	 *
	 * All properties of an object are tested to see if they contain invalid values. When they do, a
	 * BizException is thrown.
	 *
	 * @param bool $isCreate True if this function is called in a create, false for modify.
	 * @param AdmRouting $routing The routing object to be validated.
	 * @throws BizException when any of the parameters is invalid.
	 */
	private static function validateRouting( $isCreate, AdmRouting $routing )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		//validate routing id
		if( $isCreate && $routing->Id ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'A routing rule should not have an id when created.' );
		}
		if( !$isCreate ) {
			if( $routing->Id ) {
				if( !DBAdmRouting::getRoutings( null, null, null, array( $routing->Id ) ) ) {
					throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client',
						'A routing rule with the given id does not exist.', null, array( '{WFL_ROUTING}', $routing->Id ) );
				}
			} else {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'A routing rule should have an id when modified.' );
			}
		}

		//validate 'route to'
		if( !$routing->RouteTo ) {
			throw new BizException( 'ERR_NOT_EMPTY', 'Client',
				'A user or user group (route to) must be provided for a routing rule.' );
		} else {
			if( !DBAdmRouting::routeToExists( $routing->RouteTo ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given user or user group name (route to) does not exist.',
					null, array( '{USR_USER} | {GRP_GROUP}', $routing->RouteTo ) );
			}
		}

		//validate section
		if( $routing->SectionId ) {
			if( $routing->SectionId <= 0 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The section id should be a positive number.' );
			}
			require_once BASEDIR.'/server/dbclasses/DBSection.class.php';
			if( !DBSection::getSectionObj( $routing->SectionId ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given section does not exist.', null,
					array( '{LBL_SECTION}', $routing->SectionId ) );
			}
		}

		//validate status
		if( $routing->StatusId ) {
			if( $routing->StatusId <= 0 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'The status id should be a positive number.' );
			}
			require_once BASEDIR.'/server/dbclasses/DBAdmStatus.class.php';
			if( !DBAdmStatus::getStatus( $routing->StatusId ) ) {
				throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given status does not exist.', null,
					array( '{LIC_SECTION', $routing->StatusId ) );
			}
		}

		//test if the routing already exists in the database
		if( DBAdmRouting::routingExists( $routing ) ) {
			throw new BizException( 'ERR_SUBJECT_EXISTS', 'Client',
				'A routing rule with these settings already exists', null, array( '{WFL_ROUTING}', null ) );
		}
	}

	/**
	 * Creates routing rules allowing tasks to be routed to users or user groups.
	 *
	 * @param AdmRouting[] $routings List of routing rules to be created.
	 * @return integer[] List of ids of newly created routing rules.
	 */
	public static function createRoutings( array $routings )
	{
		self::checkPubAdminAccess( $routings[0]->PublicationId );

		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		$newRoutingIds = array();
		foreach( $routings as $routing ) {
			self::validateRouting( true, $routing );
			$newRoutingId = DBAdmRouting::createRouting( $routing );

			if( $newRoutingId ) {
				$newRoutingIds[] = $newRoutingId;
			}
		}
		return $newRoutingIds;
	}

	/**
	 * Modifies certain settings of routing rules.
	 *
	 * @param AdmRouting[] $routings List of routings to be modified.
	 * @return AdmRouting[] List of modified routing objects.
	 */
	public static function modifyRoutings( array $routings )
	{
		self::checkPubAdminAccess( $routings[0]->PublicationId );
		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		foreach( $routings as $routing ) {
			self::validateRouting( false, $routing );
			DBAdmRouting::modifyRouting( $routing );
		}
		return $routings;
	}

	/**
	 * Requests routing rules by routing rule id.
	 *
	 * @param integer|null $publicationId The publication id the routing rules are in.
	 * @param integer|null $issueId The issue id the routing rules are in.
	 * @param integer|null $sectionId The section id the routing rules are in.
	 * @param integer[]|null $routingIds List of routing rule ids to be requested.
	 * @return AdmRouting[] List of requested routing objects.
	 * @throws BizException when any of the given routing ids is negative.
	 */
	public static function getRoutings( $publicationId, $issueId, $sectionId, $routingIds )
	{
		self::checkPubAdminAccess($publicationId );
		if( $routingIds ) foreach( $routingIds as $routingId ) {
			if( $routingId < 1 ) {
				throw new BizException( 'ERR_ARGUMENT', 'Client', 'No negative routing ids can be given.' );
			}
		}

		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		$routings = DBAdmRouting::getRoutings( $publicationId, $issueId, $sectionId, $routingIds );
		if( $routingIds && ( count( $routings ) != count( $routingIds ) ) ) {
			foreach( $routings as $routing ) {
				if( array_key_exists( $routing->Id, $routingIds ) ) {
					unset( $routingIds[$routing->Id] );
				}
			}
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Server',
				'Not all requested rules exist.', null, array( '{USR_USER_AUTHORIZATIONS}', implode( ',', $routingIds ) ) );
		}
		return $routings;
	}

	/**
	 * Deletes routing rules by routing rule id.
	 *
	 * @param integer|null $publicationId The publication id the routing rules are in.
	 * @param integer|null $issueId The issue id the routing rules are in.
	 * @param integer|null $sectionId The section id the routing rules are in.
	 * @param integer[] $routingIds List of ids of routing rules to be deleted.
	 */
	public static function deleteRoutings( $publicationId, $issueId, $sectionId, array $routingIds )
	{
		self::checkPubAdminAccess( $publicationId );
		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		DBAdmRouting::deleteRoutings( $publicationId, $issueId, $sectionId, $routingIds );
	}

	/**
	 * Resolves the publication id from a list of Routing ids.
	 *
	 * @param integer[] $routingIds The list of Routing ids.
	 * @return integer|null If only one pubId is resolved, it returns it, otherwise it returns null.
	 * @throws BizException when no id at all is returned from the database.
	 */
	public static function getPubIdFromRoutingIds( array $routingIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		$pubIdArray = DBAdmRouting::getPubIdsForRoutingIds( $routingIds );

		if( !$pubIdArray ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given routing rule ids do not exist.', null,
				array( '{WFL_ROUTING}', implode( ',', $routingIds ) ) );
		}

		return count( array_unique( $pubIdArray ) ) == 1 ? reset( $pubIdArray ) : null;
	}

	/**
	 * Resolves the issue id from a list of Routing ids.
	 *
	 * @param array $routingIds The list of Routing ids.
	 * @return integer|null If only one issueId is resolved, it returns it, otherwise it returns null.
	 * @throws BizException when no id at all is returned from the database.
	 */
	public static function getIssueIdFromRoutingIds( array $routingIds )
	{
		require_once BASEDIR.'/server/dbclasses/DBAdmRouting.class.php';
		$issueIdArray = DBAdmRouting::getIssueIdsForRoutingIds( $routingIds );

		if( !$issueIdArray ) {
			throw new BizException( 'ERR_SUBJECT_NOTEXISTS', 'Client', 'The given routing rule ids do not exist.', null,
				array( '{WFL_ROUTING}', implode( ',', $routingIds ) ) );
		}
		return count( array_unique( $issueIdArray ) ) == 1 ? reset( $issueIdArray ) : null;
	}
} 