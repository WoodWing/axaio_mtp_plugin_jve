<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v9.4
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once dirname(__FILE__).'/BizAnaBase.class.php';

class BizAnaIssue extends BizAnaBase
{
	/**
	 * Returns Issue object to be ready for used by the analytics server.
	 *
	 * @param AdmIssue $issue
	 * @return stdClass
	 */
	public static function getIssue( AdmIssue $issue )
	{
		require_once dirname(__FILE__). '/DBAnaIssue.class.php';

		$isOverruleIssue = $issue->OverrulePublication;
		$issueInfo = new stdClass();
		$issueInfo->entid = intval( $issue->Id );
		$issueInfo->name = $issue->Name;
		$issueInfo->description = $issue->Description;
		$issueInfo->readingorder = $issue->ReversedRead ? 'rtl' : 'ltr';
		$issueInfo->overrulebrand = $isOverruleIssue;
		$issueInfo->deadline = self::convertDateTimeToUTC( $issue->Deadline );
		$issueInfo->expectedpages = $issue->ExpectedPages;
		$issueInfo->subject = $issue->Subject;
		$issueInfo->activated = $issue->Activated;
		$issueInfo->publicationdate = self::convertDateTimeToUTC( $issue->PublicationDate );

		// PubChannelId & PubId
		$pubChannelId = DBAnaIssue::getPubChannelId( $issue->Id );
		$pubId = $pubChannelId ? DBAnaIssue::getPublicationId( $pubChannelId ) : null;

		$overruleIssueId = $isOverruleIssue ? $issue->Id : 0;
		$issueInfo->brand = self::getPublication( $pubId );
		$issueInfo->statuses = self::getStatuses( $pubId, $overruleIssueId );
		$issueInfo->categories = self::getCategories( $pubId, $overruleIssueId );
		$issueInfo->pubchannel = self::getPubChannel( $pubChannelId );
		$issueInfo->editions = self::getEditions( $pubChannelId, $overruleIssueId );

		return $issueInfo;
	}

	/**
	 * Gets a publication given the publication id.
	 *
	 * @throws BizException Throws BizException when the publication is not found.
	 * @param int $pubId Publication id.
	 * @return stdClass|null
	 */
	private static function getPublication( $pubId )
	{
		$publicationObj = null;
		if( $pubId ) {
			require_once dirname(__FILE__). '/DBAnaIssue.class.php';

			$publicationObj = DBAnaIssue::getPublication( $pubId );
			if( !$publicationObj ) {
				throw new BizException( 'ERR_NOTFOUND', 'Server', 'Publication id is not found: publicationId=' . $pubId  );
			}
		}
		return $publicationObj;
	}

	/**
	 * Gets a list of statuses that belong to a publication.
	 *
	 * When issue id ($issueId) is passed in, it should be an overrule issue id.
	 * The statuses defined under this overrule issue will be returned instead.
	 *
	 * When the issue id ($issue id) passed in is not an overrule issue, the statuses defined
	 * under the normal publication will be returned.
	 *
	 * @param int $pubId Publication id of the statuses to be retrieved.
	 * @param int $issueId Overrule issue id, null should be passed in when getting statuses of normal publication.
	 * @return stdClass[]|null
	 */
	private static function getStatuses( $pubId, $issueId=0 )
	{
		$statuses = null;
		if( $pubId ) {
			require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';
			require_once dirname(__FILE__). '/DBAnaIssue.class.php';

			if( $issueId && !DBIssue::isOverruleIssue( $issueId )) {
				$issueId = 0; // When the issueId given is not an overruleissue, issue id is irrelevant, therefore set it to 0.
			}

			$statuses = DBAnaIssue::getStatuses( $pubId, $issueId );
			if( $statuses ) foreach( $statuses as &$status ) {
				$status->color = self::removeHashPrefix( $status->color );
			}
		}
		return $statuses;
	}

	/**
	 * Gets a list of categories defined under a publication.
	 *
	 * Only pass in the issue id ($issueId) when dealing with overrule issue.
	 *
	 * @param int $pubId Publication id.
	 * @param int $issueId Issue id.
	 * @return stdClass[]|null
	 */
	private static function getCategories( $pubId, $issueId=0 )
	{
		if( $pubId ) {
			require_once dirname(__FILE__). '/DBAnaIssue.class.php';
			require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';

			if( $issueId && !DBIssue::isOverruleIssue( $issueId )) {
				$issueId = 0;
			}

			$categories = DBAnaIssue::getCategories( $pubId, $issueId );
		} else {
			$categories = null;
		}
		return $categories;
	}

	/**
	 * Gets publication channel object.
	 *
	 * @throws BizException Throws BizException when the publication channel is not found.
	 * @param int $pubChannelId Publication channel id.
	 * @return stdClass|null
	 */
	private static function getPubChannel( $pubChannelId )
	{
		$pubChannel = null;
		if( $pubChannelId ) {
			require_once dirname(__FILE__). '/DBAnaIssue.class.php';
			$pubChannel = DBAnaIssue::getPubChannel( $pubChannelId );
			if( !$pubChannel ) {
				throw new BizException( 'ERR_NOTFOUND', 'Server', 'Publication channel is not found: pubChannelId=' . $pubChannelId  );
			}
		}
		return $pubChannel;
	}

	/**
	 * Get a list of editions defined under a publication channel.
	 *
	 * Only pass in the issue id ($issueId) when dealing with overrule issue.
	 *
	 * @param int $pubChannelId Publication channel id.
	 * @param int $issueId Issue id.
	 * @return stdClass[]|null
	 */
	public static function getEditions( $pubChannelId, $issueId=0 )
	{
		if( $pubChannelId ) {
			require_once dirname(__FILE__). '/DBAnaIssue.class.php';
			require_once BASEDIR .'/server/dbclasses/DBIssue.class.php';
			if( $issueId && !DBIssue::isOverruleIssue( $issueId )) {
				$issueId = 0;
			}
			$editions = DBAnaIssue::getEditions( $pubChannelId, $issueId );
		} else {
			$editions = null;
		}
		return $editions;
	}
}
