<?php

/**
 * Contains helper functions for this test case.
 *
 * @since 		v9.7.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class WW_TestSuite_BuildTest_TargetHandling_Utils
{
	/**
	 * Returns the current date time stamp.
	 *
	 * The format returned is:
	 * YrMthDay HrMinSec MiliSec
	 * For example:
	 * 140707 173315 176
	 *
	 * @return string
	 */
	public function getTimeStamp()
	{
		$microTime = explode( ' ', microtime() );
		$miliSec = sprintf( '%03d', round($microTime[0]*1000) );
		return date( 'ymd His', $microTime[1] ).' '.$miliSec;
	}

	/**
	 * Builds a Target from given channel, issue and editions.
	 *
	 * @param PubChannelInfo $chanInfo
	 * @param IssueInfo $issueInfo
	 * @return Target $target
	 */
	public function composeTarget( PubChannelInfo $chanInfo, IssueInfo $issueInfo )
	{
		$pubChannel = new PubChannel();
		$pubChannel->Id = $chanInfo->Id;
		$pubChannel->Name = $chanInfo->Name;

		$issue = new Issue();
		$issue->Id   = $issueInfo->Id;
		$issue->Name = $issueInfo->Name;
		$issue->OverrulePublication = $issueInfo->OverrulePublication;

		$target = new Target();
		$target->PubChannel = $pubChannel;
		$target->Issue      = $issue;
		$target->Editions   = $chanInfo->Editions;

		return $target;
	}

	/**
	 * Returns the pre-configured category.
	 *
	 * @param $testOptions[] Basic set up data configured for each test.
	 * @return Category | null
	 */
	public function getCategoryFromTestOptions( $testOptions )
	{
		return count( $testOptions['Brand']->Categories ) > 0  ? $testOptions['Brand']->Categories[0] : null;
	}

	/**
	 * Returns the Publication info of the pre-configured brand.
	 *
	 * @param $testOptions[] Basic set up data configured for each test.
	 * @return PublicationInfo | null
	 */
	public function getPublFromTestOptions( $testOptions )
	{
		return isset( $testOptions['Brand'] ) ? $testOptions['Brand'] : null ;
	}

	/**
	 * Returns the ticket created during the log on of the pre configured user.
	 *
	 * @param $testOptions[] Basic set up data configured for each test.
	 * @return string | null
	 */
	public function getTicketFromTestOptions( $testOptions )
	{
		return isset( $testOptions['ticket'] ) ? $testOptions['ticket'] : null ;
	}

	/**
	 * Returns the Id of the pre-configured brand.
	 *
	 * @param $testOptions[] Basic set up data configured for each test.
	 * @return int | null
	 */
	public function getPublicationIdFromTestOptions( $testOptions )
	{
		$brand = $this->getPublFromTestOptions( $testOptions );
		return isset( $brand->Id ) ? $brand->Id : null ;
	}

	/**
	 * The order of some data items under the Object data structure are not preserved in the DB.
	 * So after a round-trip through DB (e.g. SaveObjects followed by GetObjects) some data
	 * might appear in different order. This function puts all data in a fixed order, so that,
	 * after calling, the whole Object can be compared with another Object.
	 *
	 * @param Object $object
	 */
	public function sortObjectDataForCompare( /** @noinspection PhpLanguageLevelInspection */ Object $object )
	{
		if( isset( $object->Placements) && $object->Placements ) foreach( array_keys($object->Placements) as $key ) {
			sort( $object->Placements[$key]->InDesignArticleIds );
		}
		if( isset( $object->Relations) && $object->Relations ) {
			$this->sortObjectRelationsForCompare( $object->Relations );
		}
	}

	/**
	 * Sorts Object->Relations structure. See {@link:sortObjectDataForCompare()} for more info.
	 *
	 * @param Relation[] $relations
	 */
	public function sortObjectRelationsForCompare( array &$relations )
	{
		// Sort the relations. For that we compose a special temporary sort key whereby
		// we prefix the digits with leading zeros. Note that max int 64 bit = '9223372036854775807'
		// which has 19 positions, so let's take 20 digits to compose our IDs.
		$sortRelations = array();
		foreach( $relations as $relation ) {
			$sortKey = sprintf( '%020d_%020d_%s', $relation->Parent, $relation->Child, $relation->Type );
			$sortRelations[$sortKey] = $relation;
		}
		ksort( $sortRelations );
		$relations = array_values( $sortRelations ); // remove the temp keys
	}
}