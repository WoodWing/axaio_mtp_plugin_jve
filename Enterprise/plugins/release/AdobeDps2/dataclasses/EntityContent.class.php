<?php
/**
 * @package 	Enterprise
 * @subpackage 	AdobeDps2
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Data structure of the Content Element Entity used to communicate with Adobe DPS services (REST API).
 * Any entity that can be shown in a browse page should be a specialization of a Content Element Entity. 
 * The class definition is synchronized with the "20150326 Entity definition.xlsx" file.
 */

require_once dirname(__FILE__).'/../dataclasses/EntityCommon.class.php';

abstract class AdobeDps2_DataClasses_EntityContent extends AdobeDps2_DataClasses_EntityCommon
{
	/** 
	 * @var string $title The reader-facing title of the content element as defined by the editor. 
	 * It can be displayed on cards in the viewers. 
	 */
	public $title = null;

	/** 
	 * @var string $shortTitle An shortened article title for usage on cards on smaller 
	 * screens such as in the phone layout or on cards with a smaller form factor.
	 */
	public $shortTitle = null;

	/** 
	 * @var string $abstract Abstract of the content element as a teaser/preview of the 
	 * article or collection for users. It's a boiled down version of the contents of the 
	 * article/collection.
	 */
	public $abstract = null;

	/** 
	 * @var string $shortAbstract Abbreviated/Shortened version of the abstract. Can be mapped to phones.
	 */
	public $shortAbstract = null;

	/** 
	 * @var string[] $keywords A list of separated keywords. They can surface in the 
	 * viewer as keyword based search/pivots. It can also be used in layout rules to match 
	 * content elements to cards.  
	 */
	public $keywords = null;

	/** 
	 * @var string[] $internalKeywords A list of separated keywords. These can be used 
	 * by the publishers to identify/query for certain type of content elements, but will 
	 * not be shown in the viewer as pivots (e.g. sidebar, special). 
	 */
	public $internalKeywords = null;

	/** 
	 * @var string $_links->thumbnail Image that would be used to display the content element 
	 * on a card. Each thumbnail image will have specific downsamples for specific sizes, 
	 * found under e.g._links/thumbnail?size=200
	 *
	 * @var string $_links->socialSharing Image that would be used to display the content element 
	 * in social media shares (e.g. facebook) - depending on the quality of thumbnails, we may 
	 * use the thumbnail image also for social sharing. 
	 *
	 * @var string $_links->toc image to be displayed in the TOC. (TBD if we will still maintain 
	 * a TOC concept similar to DPS Classic. Otherwise it might not be required).
	 */

	/**
	 * @var string[] $productIds A list of product IDs to which this content element belongs. 
	 * The product IDs are tied to entitlement and purchasing in app-stores. Product IDs for 
	 * articles and banners are computed, based on the collections they belong to. A product ID 
	 * must start with a lowercase letter or a number, and must be composed using only lowercase 
	 * letters (a-z), numbers (0-9), and dots (.). The product ID "android.test" is reserved, 
	 * as are all product IDs that start with "android.test."
	 */
	public $productIds = null;

	/** 
	 * @var string $department This property can be used to categorize content elements within 
	 * a publication. e.g.Sport, Design, etc. (Usually also referred to as Kicker).The metadata 
	 * hierarchy for a magazine would be :Department(e.g. Sports) >Category(e.g. Basketball) 
	 * >Keywords(e.g. Miami Heat, Michael Jordan, Lakers, Stockton)
	 */
	public $department = null;

	/**
	 * @var string $category This metadata field is used for categorising content and 
	 * enabling pivoting. It is more specific than Department, but more general compared 
	 * to keywords. There is only one specified category for the given content element.
	 * The metadata hierarchy for a magazine would be: 
	 *    Department (e.g. Sports) > Category (e.g. Basketball) > Keywords 
	 *    (e.g. # Miami Heat, #Michael Jordan, #Lakers, #Stockton) 
	 */
	public $category = null;

	/**
	 * @var string $importance How important is the content element in the context of the 
	 * publication. It is used for card mapping rules and to order content by it in the browse page.  
	 * Enumeration: low, normal, high. Default value: normal.
	 */
	public $importance = null;

	/**
	 * @var string[] $collections The collections this content element belong to. 
	 * Within J35 it will enable us to show in the producer to what other collections the 
	 * content element is referenced in. Post J35 we may want to expose these in the viewer 
	 * in an "Also in: <Nature>, <Wildlife>" Collection. This is a computed property, 
	 * not settable from the UI or API. 
	 */
	public $collections = null;

	/** @var string $socialShareUrl The social share URL that will be shared to a supported social network. */
	public $socialShareUrl = null;

	/**
	 * @var string The date that content is available and goes on sale. The Availability 
	 * Date is used to determine if content is entitled as part of a subscription. 
	 * If no Availability Date is provided, the first-Published Date is used.
	 */
	public $availabilityDate = null;
}
