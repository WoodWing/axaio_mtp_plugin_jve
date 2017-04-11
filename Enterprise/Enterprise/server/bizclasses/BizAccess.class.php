<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v6
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

/*
---------------------------------------------------
Note#001: USER AUTHORIZATIONS FOR MULTIPLE TARGETS
---------------------------------------------------
Access rights can be setup for any combination of Brand+Category+Status. There are no Issues/Edition 
involved here and therefore, targets seem to have nothing to do with this. For normal issues, that is. 
For Overrule Issues, the issue/target is important because access rights are setup for any combination of 
OverruleIssue+Category+Status. Nevertheless, objects can only be in one workflow at the same time, 
and so they can be assigned to one Overrule Issue only. In other terms, there is no need to check all issues.
Taking the Issue of the 'first' Target is good enough to check if that is an Overrule Issue. If so, 
it should be used for access rights checking. If 'normal' issues are involved, or if no targets are 
assigned to the object, the access rights of the brand should be checked (by passing zero for issue).

---------------------------------------------------
Note#002: REFACTORING AND PREFERRED FUNCTIONS
---------------------------------------------------
Access rights are refactored slowly in time. The idea is to remove the authorizationmodule.php
file and move its biz logics into this BizAccess.class.php module. Then we can get the rid of  
this fragment you can find all over the shop:
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
For the time being, some functions are still there for backwards compat reasons, while others
are newly added and are superseding. Please use the functions in the following order of preferrence:
	1) checkRightsForObjectProps / checkRightsForMetaDataAndTargets / checkRightsForBrandContext
	2) checkRightsForParams / checkRightsForObjectRow
	3) $globAuth->getrights + $globAuth->checkright => for many quick checks in one cache
	4) checkRights / checkRightsMetaDataTargets => obsoleted
*/

class BizAccess
{
	const THROW_ON_DENIED = true;
	const DONT_THROW_ON_DENIED = false;

	/**
	 * Checks if the current user has specified rights to an object, based on given object properties.
	 *
	 * @obsoleted since 9.4, please use {@link:checkRightsForObjectProps()} instead.
	 * @param array $objProps Array of object properties (Biz props, mixed case)
	 * @param string $rights String with rights to check, each character is a right that is needed
	 * @throws BizException when session user has no rights
	 * @return bool Whether or not the session user that the requested rights.
	 */
	static public function checkRights( $objProps, $rights )
	{
		Log::LogHandler( 'BizAccess', 'WARN', 'The checkRights() function is obsoleted. '.
			'Please call the checkRightsForObjectProps() function instead.' );

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$issueId = array_key_exists( 'IssueId', $objProps) ? $objProps['IssueId'] : 0; // See Note#001
		return self::checkRightsForParams(
			BizSession::getShortUserName(), $rights, self::THROW_ON_DENIED,
			$objProps['PublicationId'],
			$issueId,
			$objProps['SectionId'],
			$objProps['Type'],
			$objProps['StateId'],
			$objProps['ID'],
			$objProps['ContentSource'],
			$objProps['DocumentID'],
			$objProps['RouteTo'] );
	}

	/**
	 * Checks if the given user has specified rights to an object, based on given object properties.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $rights String with rights to check, each character is a right that is needed
	 * @param bool $throwException Whether or not to throw BizException when no access.
	 * @param array $objProps Array of object properties (Biz props, mixed case)
	 * @return bool Whether or not the user has the requested rights.
	 */
	static public function checkRightsForObjectProps( $user, $rights, $throwException, $objProps )
	{
		$issueId = array_key_exists( 'IssueId', $objProps) ? $objProps['IssueId'] : 0; // See Note#001
		return self::checkRightsForParams(
			$user, $rights, $throwException,
			$objProps['PublicationId'],
			$issueId,
			$objProps['SectionId'],
			$objProps['Type'],
			$objProps['StateId'],
			$objProps['ID'],
			$objProps['ContentSource'],
			$objProps['DocumentID'],
			$objProps['RouteTo'] );
	}

	/**
	 * Checks if the current user has specified rights to object, based on given object row.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $rights String with rights to check, each character is a right that is needed.
	 * @param bool $throwException Whether or not to throw BizException when no access.
	 * @param array $objRow	Row of smart_object table.
	 * @param integer $issueId ID of the overrule issue. Zero when object is targetted for none or normal issues.
	 * @return bool Whether or not the user has the requested rights.
	 */
	static public function checkRightsForObjectRow( $user, $rights, $throwException, $objRow, $issueId )
	{
		return self::checkRightsForParams(
			$user, $rights, $throwException,
			$objRow['publication'],
			$issueId,
			$objRow['section'],
			$objRow['type'],
			$objRow['state'],
			$objRow['id'],
			$objRow['contentsource'],
			$objRow['documentid'],
			$objRow['routeto'] );
	}

	/**
	 * Checks if the session user has specified rights to an object, based on given object metadata and targets.
	 *
	 * @obsoleted since 9.4, please use {@link:checkRightsForMetaDataAndTargets()} instead.
	 * @param MetaData $meta Object metadata
	 * @param Target[] $targets Object targets
	 * @param string $rights String with rights to check, each character is a right that is needed
	 * @throws BizException when session user has no rights
	 * @return bool Whether or not the session user has the requested rights.
	 */
	static public function checkRightsMetaDataTargets( MetaData $meta, array $targets, $rights )
	{
		Log::LogHandler( 'BizAccess', 'WARN', 'The checkRightsMetaDataTargets() function is obsoleted. '.
			'Please call the checkRightsForMetaDataAndTargets() function instead.' );

		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$issueId = count($targets) > 0 ? $targets[0]->Issue->Id : 0; // See Note#001
		return self::checkRightsForParams(
			BizSession::getShortUserName(),
			$rights,
			self::THROW_ON_DENIED,
			$meta->BasicMetaData->Publication->Id,
			$issueId,
			$meta->BasicMetaData->Category->Id,
			$meta->BasicMetaData->Type,
			$meta->WorkflowMetaData->State->Id,
			$meta->BasicMetaData->ID,
			$meta->BasicMetaData->ContentSource,
			$meta->BasicMetaData->DocumentID,
			$meta->WorkflowMetaData->RouteTo );
	}

	/**
	 * Checks if the session user has specified rights to an object, based on given object metadata and targets.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $rights String with rights to check, each character is a right that is needed
	 * @param bool $throwException Whether or not to throw BizException when no access.
	 * @param MetaData $meta Object metadata
	 * @param Target[] $targets Object targets
	 * @throws BizException when session user has no rights
	 * @return bool Whether or not the session user has the requested rights.
	 */
	static public function checkRightsForMetaDataAndTargets(
		$user, $rights, $throwException, MetaData $meta, array $targets )
	{
		$issueId = count($targets) > 0 ? $targets[0]->Issue->Id : 0; // See Note#001
		return self::checkRightsForParams(
			$user, $rights, $throwException,
			$meta->BasicMetaData->Publication->Id,
			$issueId,
			$meta->BasicMetaData->Category->Id,
			$meta->BasicMetaData->Type,
			$meta->WorkflowMetaData->State->Id,
			$meta->BasicMetaData->ID,
			$meta->BasicMetaData->ContentSource,
			$meta->BasicMetaData->DocumentID,
			$meta->WorkflowMetaData->RouteTo );
	}

	/**
	 * Checks if the current user has rights to a specific context in a certain brand.
	 *
	 * @since 9.4
	 * @param string $user Short user name.
	 * @param string $rights String with rights to check, each character is a right that is needed.
	 * @param bool $throwException Whether or not to throw BizException when no access.
	 * @param integer $brandId Brand ID
	 * @param integer $issueId Issue ID
	 * @param integer $categoryId Category ID
	 * @param integer $objectType Object Type
	 * @param integer $statusId Status ID
	 * @throws BizException when $throwException and session user has no rights
	 * @return bool Whether or not session user has rights.
	 */
	static public function checkRightsForBrandContext( $user, $rights, $throwException,
	                                                   $brandId, $issueId = null, $categoryId = null, $objectType = null, $statusId = null )
	{
		$objectId = null;
		$contentSource = null;
		$documentId = null;
		$routeTo = '';
		return self::checkRightsForParams( $user, $rights, $throwException,
			$brandId, $issueId, $categoryId, $objectType, $statusId,
			$objectId, $contentSource, $documentId, $routeTo );
	}

	/**
	 * Checks if the current user has specified rights to an object and/or a specific
	 * context in a certain brand. Should be used when an object is about to move to
	 * another status, category, etc. Else other functions are more preferred, see Note#002.
	 *
	 * @param string $user Short user name.
	 * @param string $rights String with rights to check, each character is a right that is needed
	 * @param bool $throwException Whether or not to throw BizException when no access.
	 * @param integer $brandId Brand ID
	 * @param integer $issueId Issue ID
	 * @param integer $categoryId Category ID
	 * @param integer $objectType Object Type
	 * @param integer $statusId Status ID
	 * @param integer $objectId Object ID (zero for Create operations)
	 * @param string $contentSource
	 * @param string $documentId
	 * @param string $routeTo User to which an object is routed or will be routed to.
	 * @throws BizException when $throwException and session user has no rights
	 * @return bool Whether or not session user has rights.
	 */
	static public function checkRightsForParams( $user, $rights, $throwException,
	                                             $brandId, $issueId, $categoryId, $objectType, $statusId,
	                                             $objectId, $contentSource, $documentId, $routeTo )
	{
		// Check authorization
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		$globAuth->getRights( $user, $brandId, $issueId, $categoryId, $objectType, $statusId );
		for( $i=0; $i < strlen($rights); ++$i ) {
			$hasAccess = $globAuth->checkright( $rights[$i],
				$brandId, $issueId, $categoryId, $objectType, $statusId,
				$objectId, $contentSource, $documentId, $routeTo );

			// When no access: Throw authorization error or return false.
			if( !$hasAccess ) {
				if( $throwException ) {
					if( $objectId ) {
						$details = $objectId.'('.$rights[$i].')';
					} else {
						$details = $rights[$i];
					}
					throw new BizException( 'ERR_AUTHORIZATION', 'Client', $details );
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Checks if layouts can be listed in the Publication Overview for a given user.
	 *
	 * @param MetaData[] $layoutInfos
	 * @param integer $issueId
	 * @param string $user
	 * @return integer[] List of layout ids the user has access for.
	 */
	static public function checkListRightInPubOverview( array $layoutInfos, $issueId, $user )
	{
		global $globAuth;
		if( !isset($globAuth) ) {
			require_once BASEDIR.'/server/authorizationmodule.php';
			$globAuth = new authorizationmodule( );
		}
		$layoutIdsAuth = array();
		$holdCategoryId = -1;
		$holdStateId = -2; // Not -1 as this is reserved for 'Personal State'.
		$auth = false;
		if ( $layoutInfos ) foreach ( $layoutInfos as $layoutId => $layoutInfo ) {
			$globAuth->getRights(
				$user,
				$layoutInfo->BasicMetaData->Publication->Id,
				$issueId,
				$layoutInfo->BasicMetaData->Category->Id,
				$layoutInfo->BasicMetaData->Type  );
			if ( $layoutInfo->BasicMetaData->Category->Id <> $holdCategoryId ||
				$layoutInfo->WorkflowMetaData->State->Id <> $holdStateId ) {
				$auth = ( $globAuth->checkright(
					'L',
					$layoutInfo->BasicMetaData->Publication->Id,
					$issueId,
					$layoutInfo->BasicMetaData->Category->Id,
					$layoutInfo->BasicMetaData->Type,
					$layoutInfo->WorkflowMetaData->State->Id,
					$layoutInfo->BasicMetaData->ID,
					$layoutInfo->BasicMetaData->ContentSource,
					$layoutInfo->BasicMetaData->DocumentID,
					$layoutInfo->WorkflowMetaData->RouteTo ));
				$holdCategoryId = $layoutInfo->BasicMetaData->Category->Id;
				$holdStateId = $layoutInfo->WorkflowMetaData->State->Id;
			}
			if ( $auth ) {
				$layoutIdsAuth[] = $layoutId;
			}
		}

		return $layoutIdsAuth;
	}

	/**
	 * Checks if a user has any authorizations for the given brand and/or issue.
	 *
	 * When validating only a brand, set $issueId to 0.
	 * When validating only an issue, set $brandId to 0.
	 * When validating an overrule issue, set both of them.
	 *
	 * @param int $userId The id of a user.
	 * @param int $brandId The brand id to verify for.
	 * @param int $issueId The issue id to verify for.
	 * @throws BizException when any of the supplied parameters are invalid.
	 * @return bool TRUE when authorizations are found for the brand and issue.
	 */
	static public function isUserAuthorizedForBrandAndIssue( $userId, $brandId, $issueId )
	{
		if( !is_int($userId) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'User id is mandatory.' );
		}

		if( !is_int($brandId) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Brand id is mandatory.' );
		}

		if( !is_int($issueId) ) {
			throw new BizException( 'ERR_ARGUMENT', 'Client', 'Issue id is mandatory.' );
		}

		require_once BASEDIR.'/server/dbclasses/DBAccess.class.php';
		return DBAccess::hasUserAuthorizations( $userId, $brandId, $issueId );
	}
}
