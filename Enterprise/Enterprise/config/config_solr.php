<?php
// ---------- Solr search engine settings --------------------------------------
define ('SOLR_SERVER_URL', 'http://localhost:8080/solr'); // Solr home
define ('SOLR_CORE', 'collection1'); // Solr core to use, default collection1.

/**
 * List of attributes that can be used for indexing, to search or can be used as facets.
 * If an attribute must b available as an facet it must also be indexed. Same is true03
 * for search fields.
 *
 	'ID',				 
  	'Publication',		(Name of a Brand)
	'Issue',			(Name of an Issue)
	'Edition',			(Name of an Edition)
	'Category',
	'State',
 	'Name',				(Name of an Object)
	'Type',
	'Slugline',
	'Source',	
	'Description',	
	'ColorSpace',
	'PlainContent',	
	'Creator',	
	'Created',	
	'Modified',
	'Modifier',	
	'LockedBy',	
	'Author',	//Photographer in case of images
	'Rating',	
	'RouteTo',
	'CopyRight',
	'ColorSpace',	
	'Orientation',
	'Credit',
	'Comment',
	'Format',
	'Keywords',
 */

define ('SOLR_INDEX_FIELDS', serialize( array(
		'ID',
		'Publication',
		'PublicationId', //Mandatory
		'IssueId', //Mandatory 
 		'Name',
		'Type',
		'Slugline',
		'Description',	
		'PlainContent',	
		'Creator',	
		'Created',	
		'Modified',	
		'Modifier',	
		'Author',	
		'Orientation',
		'Credit',
		'Comment',
		'Category',
		'CategoryId', //Mandatory
		'State',
		'StateId', //Mandatory
		'RouteTo',
		'ColorSpace',	
		'Keywords',
		'DocumentID',
		'ContentSource',
		'Copyright',
		'CopyrightURL',
		'CopyrightMarked',
		'Source',
		'DescriptionAuthor',
		'Format',
		'Columns',
		'Width',
		'Height',
		'Dpi',
		'LengthWords',
		'LengthChars',
		'LengthParas',
		'LengthLines',
		'FileSize',
		'Encoding',
		'Compression',
		'KeyFrameEveryFrames',
		'Channels',
		'AspectRatio',
		'Deadline',
		'DeadlineSoft',
		'Urgency',
		'Version',
		'Rating',
		'PlannedPageRange',
		'PageRange',
		'PubChannelIds',
		'PubChannelId',
		'IssueIds',
		'Issues',
		'EditionIds',
		'Editions',
		'Closed',
		'Areas',
		'UnreadMessageCount',
		'Deleted',
		'Deletor',
//		'C_STRING',
//		'C_MULTISTRING',
//		'C_MULTILINE',
//		'C_BOOLEAN',
//		'C_INTEGER',
//		'C_DOUBLE',
//		'C_DATE',
//		'C_DATETIME',
//		'C_LIST',
//		'C_MULTILIST',
		)));
		
// Facets used when searching for mixed object types:
define ('SOLR_GENERAL_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Type',
		'Modified',	
		'Credit',
		'State',
		)));
// Facets used when searching for Dossiers only, leave empty to use general set 
define ('SOLR_DOSSIER_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',
		'Rating',	
		'State',
		)));
// Facets used when searching for Images only, leave empty to use general set 
define ('SOLR_IMAGE_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',	
		'Author',
		'Rating',
		'Orientation',
		'Credit',
		'State',
		'ColorSpace',
		)));
// Facets used when searching for Articles only, leave empty to use general set 
define ('SOLR_ARTICLE_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',	
		'Rating',
		'State',
//		'LengthChars',
		'LengthWords',
//		'LengthLines',
		)));
// Facets used when searching for Spreadsheets only, leave empty to use general set 
define ('SOLR_SPREADSHEET_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',	
		'Rating',
		'State',
//		'LengthChars',
		'LengthWords',
//		'LengthLines',
		)));
// Facets used when searching for Videos only, leave empty to use general set 
define ('SOLR_VIDEO_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',	
		'Rating',
		'Credit',
		'State',
		)));
// Facets used when searching for Audios only, leave empty to use general set 
define ('SOLR_AUDIO_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',	
		'Rating',
		'Credit',
		'State',
		)));
// Facets used when searching for Layouts only, leave empty to use general set 
define ('SOLR_LAYOUT_FACETS', serialize(array(		
		'Publication',	// Brand
		'Category',
		'IssueIds',
		'Modified',	
		'State',
		)));
		
// Facets used when searching for items in Dossier, leave empty to use general set
define ('SOLR_DOSSIERITEMS_FACETS', serialize(array(
		'Modified',
		'State',
		'Type',
		'Rating',
		)));

define ('SOLR_CATCHALL_FIELD', 'WW_CATCHALL');
// General field used search for keywords	

//Defines default range for integer facet
define ('SOLR_INTEGER_RANGE', serialize(array(		
		100,
 		500,
 		1000,
 		10000,
		)));
		
//Defines the range used for LENGTHCHARS facet
define ('SOLR_LENGTHCHARS_RANGE', serialize(array(		
		500,
 		1000,
 		5000,
 		10000,
		)));

//Defines the range used for LENGTHWORDS facet		
define ('SOLR_LENGTHWORDS_RANGE', serialize(array(		
		100,
		250,
 		500,
 		1000,
		)));

//Defines the range used for LENGTHCHARS facet
define ('SOLR_LENGTHLINES_RANGE', serialize(array(		
		50,
 		100,
 		250,
 		500,
		)));
		
//Defines the range used for LENGTHCHARS facet
define ('SOLR_RATING_RANGE', serialize(array(		
		1,
 		2,
 		3,
 		4,
		)));

//Defines the range used for NGRAM size
define ('SOLR_NGRAM_SIZE', serialize(array(		
		4,	// MinGramSize
 		15,	// MaxGramSize
		)));		
		
//Defines if the autoCommit in solrconfig.xml is used.
//If set to true please check the autoCommit setting in solrconfig.xml
define ('SOLR_AUTOCOMMIT', true);

//Defines Solr time-out in seconds during searching and indexing.
//Can be increased in case the default time-out is not sufficient.
//Only change this setting if really needed.
define ('SOLR_TIMEOUT', 5);