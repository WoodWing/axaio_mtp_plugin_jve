<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Data structure used to communicate with Adobe DPS services (REST API).
 * The class definition is synchronized with the "20150326 Entity definition.xlsx" file.
 *
 * The following properties are common to all entities. The required column indicates which 
 * fields are required in Producer before an entity can be published. If a property is not 
 * required then the Producer should assigned it a default value as is specified in the default 
 * column. If a property is marked required then the Producer API should enforce that the 
 * property exists and the Content UI should require a value to be entered in the UI. All 
 * property should be present when an entity is published either with a set value or default value.
 */

abstract class AdobeDps2_DataClasses_EntityCommon
{
	/**
	 * @var string $entityType Each entity has a type. The entity type is a string identifying 
	 * the name of the entity type. Supported values are: 
	 *    "publication", "view", "collection", "article", "layout", "cardTemplate"
	 */
	public $entityType = null;

	/**
	 * @var string $entityName Each entity has a name that is created and assigned by the 
	 * publisher (editors, creatives, etc). The name must be unique for entities of the same 
	 * type within a publication. Names can be basic alpha-numberic strings adhering to the 
	 * following regular expression: "[0-9a-zA-Z]{1}[0-9a-zA-Z.-]{0,62}[0-9a-zA-Z]{1}".
	 */
	public $entityName = null;

	/** 
	 * @var string $entityId Each entity has an Id that uniquely identifies the entity. 
	 * Entity Ids are formed using the publication Id, entity type and entity name. Entity Ids 
	 * are represented as uniform resource names (urn). An entity Id is a urn string of the 
	 * following format: "urn:{Id}:{entityType}:{entityName}". The publication entity Id is 
	 * a GUID assigned by AMAS at the time the publication is created. An entity Id other 
	 * than the publication entity Id, would look something like this: 
	 *    "urn:0ab81ae4-9d43-46bc-87e8-200611fe90cc:view:dailyDigest".
	 */
	public $entityId = null;

	/**
	 * @var string $version Entities can be versioned. Each time the entity changes a new 
	 * version of the entity is created. A limited number of previous versions of each 
	 * entity are maintained by the system.	 
	 * Entity version ID is managed by service. For creation of a new entity, you must not specify version ID. 
	 * Entity version ID is generated by service backend and immutable after creation. 
	 * On updating an existing entity, entity version ID must be specified to match the head version of the entity. 
	 * Entity version changes by service backend when the entity is successfully updated. 
	 * If specified Version ID is incorrect, a version conflict error will be returned in API response. 
	 */
	public $version = null;

	/** 
	 * @var string $url URI Entities can have a url that is specified by the publisher. 
	 * This property can be used to identify a related location for the entity outside the 
	 * Adobe DPS system. For example, the url associated with the publication entity could identify 
	 * a website for the publication maintained by the publisher, or the URL associated for 
	 * an article would be the url of that article published on the publisher's website, a 
	 * URL from a banner leads the user to that link upon tapping the banner in the browse-page.
	 * 
	 * For article entities, the url should link to the web version of the article. The social 
	 * share page will use the url to link to the article on the publication website. (If blank, 
	 * no link will be surfaced on the social sharing page).
	 */
	public $url = null;

	/** 
	 * @var stdClass $_links Note that for this data class, contentUrl->href is specified.
	 * The contentUrl is a stdClass and the href (string) is an URL to the associated content for 
	 * this entity (e.g. the article folio file). (Url to content available via the Content Delivery API) 
	 */
	public $_links = null;

	/** @var string $created Time the entity was created. */
	public $created = null;

	/** @var string $modified Time the entity was last modified. */
	public $modified = null;

	/** @var string $published Time the entity was last published (or re-published). */
	public $published = null;

	/** @var string $contentUrl URI */
	public $contentUrl = null;

	/** @var PublishStatus $publishStatus 'unpublished' or 'publishing' or 'published' */
	public $publishStatus = null;

	/** @var array $data Map[string,stdClass] */
	public $data = null;
}