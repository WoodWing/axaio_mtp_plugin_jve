<?php
/**
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_ResolveBrandSetup
{
	private $publication 	= null;	// Publication object
	private $pubChannelInfo = null; // PubChannelInfo object
	private $pubChannel     = null; // PubChannel object.
	private $issue 			= null;	// Issue object
	private $edition        = null; // Edition object

	/**
	 * Resolves the PubChannelInfo and Publication objects from a given channel id.
	 *
	 * @param integer $channelId Pub Channel id
	 */
	public function resolveBrand( $channelId )
	{
		$this->edition = null; // Clear cache just in case it was used in resolveEditionPubChannelBrand().
		$this->issue = null; // Clear cache just in case it was used in resolveIssuePubChannelBrand().

		require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
		require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';

		// Get PubChannelInfo from channelId
		$this->pubChannelInfo = DBChannel::getPubChannelObj( $channelId );

		// Resolve brand from channel
		$this->publication = new Publication();
		$this->publication->Id = DBChannel::getPublicationId( $this->pubChannelInfo->Id );
		$this->publication->Name = DBPublication::getPublicationName( $this->publication->Id );
	}

	/**
	 * Resolves the Issue, PubChannelInfo and Publication objects from a given issue id.
	 *
	 * @param string $issueId Issue id
	 */
	public function resolveIssuePubChannelBrand( $issueId )
	{
		$this->edition = null; // Clear cache just in case it was used in resolveEditionPubChannelBrand().
		if( !isset($this->issue->Id) || $issueId != $this->issue->Id ) { // caching
			require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
			require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';
			require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';

			// Resolve issue name
			$this->issue = new Issue();
			$this->issue->Id = intval($issueId); // anti hack
			$this->issue->Name = DBIssue::getIssueName( $this->issue->Id );
	
			// Resolve channel from issue
			$this->pubChannel = BizPublication::getChannelForIssue( $this->issue->Id );
			$this->pubChannelInfo = DBChannel::getPubChannelObj( $this->pubChannel->Id );
	
			// Resolve brand from channel
			$this->publication = new Publication();
			$this->publication->Id = DBChannel::getPublicationId( $this->pubChannelInfo->Id );
			$this->publication->Name = DBPublication::getPublicationName( $this->publication->Id );
		}
	}
	
	/**
	 * Resolves the Edition, PubChannelInfo and Publication given the edition id.	 
	 *
	 * @param integer $editionId DB edition id.
	 * @throws BizException
	 */
	public function resolveEditionPubChannelBrand( $editionId )
	{
		$this->issue = null; // Clear cache just in case it was used in resolveIssuePubChannelBrand().
		if( !isset($this->edition->Id) || $editionId != $this->edition->Id ) { // caching
			require_once BASEDIR . '/server/bizclasses/BizPublication.class.php';
			require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
			require_once BASEDIR . '/server/dbclasses/DBChannel.class.php';
			require_once BASEDIR . '/server/dbclasses/DBPublication.class.php';			
			
			$editionObj = DBEdition::getEditionObj( $editionId );
			if( !$editionObj ) {			
				throw new BizException( '', 'ERROR', 'Edition id [' .$editionId.'] is not found in database.',
							'Invalid edition id.');			
			}
			// Resolve edition
			$this->edition = new Edition();
			$this->edition->Id = $editionObj->Id;
			$this->edition->Name = $editionObj->Name;
			
			// Resolve channel from edition
			$this->pubChannel = BizPublication::getChannelForEdition( $this->edition->Id );
			$this->pubChannelInfo = DBChannel::getPubChannelObj( $this->pubChannel->Id );
			
			// Resolve brand from channel
			$this->publication = new Publication();
			$this->publication->Id = DBChannel::getPublicationId( $this->pubChannelInfo->Id );
			$this->publication->Name = DBPublication::getPublicationName( $this->publication->Id );
		}	
		
	}
	

	/**
	 * Returns the brand object after resolved.
	 * Should call resolveBrand() or resolveIssuePubChannelBrand() or 
	 * resolveEditionPubChannelBrand() first.	 
	 *
	 * @return Publication
	 */
	public function getPublication()
	{
		return $this->publication;
	}
	
	/**
	 * Returns the publication channel info object after resolved.
	 * Should call resolveBrand() or resolveIssuePubChannelBrand() or 
	 * resolveEditionPubChannelBrand() first.
	 *
	 * @return PubChannelInfo
	 */
	public function getPubChannelInfo()
	{
		return $this->pubChannelInfo;
	}

	/**
	 * Returns the publication channel object after resolved.
	 * Should call resolveIssuePubChannelBrand() or resolveEditionPubChannelBrand() first.
	 */
	public function getPubChannel()
	{
		return $this->pubChannel;
	}
	
	/**
	 * Returns the issue object after resolved.
	 * Should call resolveIssuePubChannelBrand() first.
	 *
	 * @return Issue
	 */
	public function getIssue()
	{
		return $this->issue;
	}
	
	/**
	 * Returns the edition object after resolved.
	 * Should call resolveEditionPubChannelBrand() first.
	 *
	 * @return Edition
	 */
	public function getEdition()
	{
		return $this->edition;
	}
}