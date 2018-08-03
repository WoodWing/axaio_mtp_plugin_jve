<?php

/**
 * Contains helper functions for this test case.
 *
 * @since 		v9.8.0
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

class WW_TestSuite_BuildTest_InDesignServerAutomation_AutomatedPrintWorkflow_Utils
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
}