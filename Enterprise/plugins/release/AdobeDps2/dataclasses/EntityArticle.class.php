<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Data structure of the Article entity used to communicate with Adobe DPS services (REST API).
 * The class definition is synchronized with the "20150326 Entity definition.xlsx" file.
 */

require_once dirname(__FILE__).'/../dataclasses/EntityContent.class.php';

class AdobeDps2_DataClasses_EntityArticle extends AdobeDps2_DataClasses_EntityContent
{
	public function __construct()
	{
		$this->entityType = 'article';
	}
	
	/** @var string $author Name of the author that created/curated the content. Can be displayed on cards in the viewer.  */
	public $author = null;

	/** @var string $authorUrl Link to more information about the associated Author  */
	public $authorUrl = null;
	
	/** @var string First few paragraphs of the content. To be used in the Social Sharing Landing Page as a teaser. */
	public $articleText = null;

	/** @var boolean $isAd Indicates whether this content element is an ad or not. */
	public $isAd = null;

	/** @var string $adType What kind of ad is it: 'Static' or 'EFT' */
	public $adType = null;

	/** 
	 * @var string $adCategory Publisher defined (string). Publishers can define some a 
	 * categories to cluster ads on and measure all ads from the "Luxury" Category or "IT" Category. 
	 */
	public $adCategory = null;

	/**
	 * @var string $advertiser The name of the Advertiser that owns the ad. This is also 
	 * used for clustering and ad metrics.  
	 */
	public $advertiser = null;

	/** 
	 * @var string $accessState The access state for the article. Triggers certain viewing 
	 * behaviours and actions in the viewer, eg. incrementing the metering counter.
	 * Supported values: free, metered, protected
	 */
	public $accessState = null;

	/** @var boolean $hideFromBrowsePage Indicates whether this article should be shown in the browse page or not. */
	public $hideFromBrowsePage = null;

	/** @var string $articleFolio The link to the article folio containing the contents of the article in the CDS. */
	public $articleFolio = null;
	
	/** 
	 * @var boolean $isTrustedContent Flag that allows Article to access entitlement info / reading APIs. 
	 * Not in the UI in the Content Dashboard, placed here for migration/data consistency purposes. 
	 */
	public $isTrustedContent = null;
}