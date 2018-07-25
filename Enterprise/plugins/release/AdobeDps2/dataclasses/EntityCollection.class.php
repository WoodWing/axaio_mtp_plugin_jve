<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Data structure of the Collection entity used to communicate with Adobe DPS services (REST API).
 * The class definition is synchronized with the "20150326 Entity definition.xlsx" file.
 */

require_once dirname(__FILE__).'/../dataclasses/EntityContent.class.php';

class AdobeDps2_DataClasses_EntityCollection extends AdobeDps2_DataClasses_EntityContent
{
	public function __construct()
	{
		$this->entityType = 'collection';
	}
	
	/** @var boolean $isIssue Flag that indicating certain metrics in ABC Analytics  */
	public $isIssue = null;

	/**
	 * @var boolean $allowDownload Flag that defines the ability of a collection to be 
	 * downloaded entirely on the device or not. For static collections the publishers have 
	 * UI to enable or disable the ability to download the entire collection. For dynamic 
	 * collection they will not have the ability to control this and it is set as false by default.
	 */
	public $allowDownload = null;

	/** 
	 * @var string $openTo Specifies the opening behaviour when a collection card is tapped. 
	 * The options are to open in the Browse Page (note: always opens to the top) or Content View.
	 */
	public $openTo = null;

	/**
	 * @var string $readingPosition Setting for remembering the reading position for a collection. 
	 * When a user enters a collection, is the article positioning reset or retain: 
	 * - Reset: all articles are reset to the top.
	 * - Retain: articles retain their reading position based on the last time the user viewed the article.  
	 */
	public $readingPosition = null;

	/** 
	 * @var integer $maxSize The maximum number of content elements in collection. Helps achieve 
	 * a "Newsfeed" type of use case. (e.g. Last 10 Individual published articles are contained 
	 * in the NewsFeed Collection). It is by default set to "unlimited" the collection does 
	 * not have a max size (can grow indefinitely). 
	 */
	public $maxSize = null;

	/**
	 * @var boolean $lateralNavigation The ability for a reader to navigate within the 
	 * collection elements laterally, either through buttons or swipe gestures; (e.g. As a 
	 * user in a collection of articles, I can enter an article and swipe left/right to read 
	 * the next/previous one in the collection). 
	 */
	public $lateralNavigation = null;

	/** 
	 * @var string $_links->background The image used as background for the browse page. The  
	 * actual usage is determined if the layout template associated to this collection specifies  
	 * that the background should be a certain image.
	 *
	 * @var string $_links->contentElements URL that can be used to get/set all content elements 
	 * that belong to the collection. Each item is a reference to an article, banner or collection. 
	 *
	 * @var string $_links->view The link to the view that uses this collection. (Automatically Populated)
	 */
}