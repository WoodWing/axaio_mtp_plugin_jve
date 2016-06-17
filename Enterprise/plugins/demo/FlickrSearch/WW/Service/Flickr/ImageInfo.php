<?php
/**
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Flickr
 * @since 	   v6.1
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */
class WW_Service_Flickr_ImageInfo
{
   	/**
     * The photo's title.
     *
     * @var string
     */
    public $title;

    /**
     * The photo's description
     *
     * @var string
     */
    public $description;

    /**
     * The photo owner name
     *
     * @var string
     */
    public $ownername;

    /**
     * The date the photo was taken.
     *
     * @var string
     */
    public $datetaken;
    
    /**
     * The date the photo was upload
     * 
     * @var string
     */
    public $dateupload;
    
    /**
     * The date the photo was upload
     * 
     * @var string
     */
    public $dateupdate;

    /**
     * Parse given Flickr Image xpath
     *
     * @param  DOMXPath $xpath
     * @return void
     */
    public function __construct(DOMXPath $xpath)
    {
    	$description 		= null;
    	$this->title 		= (string) $xpath->query('//title/text()')->item(0)->data;
    	$description 		= $xpath->query('//description/text()');
    	if( $description->item(0) ){
    		$this->description 	= (string) $description->item(0)->data;
    	}
    	$dates	 			= $xpath->query('//dates')->item(0);
    	$this->datetaken	= (string) $dates->getAttribute('taken');
    	$this->dateupload	= (string) $dates->getAttribute('posted');
    	$this->dateupdate	= (string) $dates->getAttribute('lastupdate');
    }
}

