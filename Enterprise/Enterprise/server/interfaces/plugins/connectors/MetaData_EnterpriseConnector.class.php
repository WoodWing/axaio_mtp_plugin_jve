<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class MetaData_EnterpriseConnector extends DefaultConnector
{
	/**
	 * canHandleFormat
	 * 
	 * @param string 	$format		mime format
	 * @return int		Return if and how well the format is supported.
	 * 				 	 0 - Not supported
	 * 					 1 - Could give it a try
	 * 					 2 - Reasonable
	 * 					 3 - Pretty Good, but slow
	 * 					 4 - Pretty Good and fast 
	 * 					 5 - Good, but slow
	 * 					 6 - Good and fast
	 * 					 8 - Very good, but slow
	 * 					 9 - Very good and fast
	 * 					10 - perfect and lightening fast
	 * 					11 - over the top to overrule it all
	 */
	abstract public function canHandleFormat( $format );

	/**
	 * readMetaData
	 * 
	 * Read metadata from content of the attachment, will only be called if needsFile returns false
	 * 
	 * @param Attachment 			$attachment			
	 * @param BizMetaDataPreview	$bizMetaDataPreview	Instance of BizMetaDataPreview for file caching
	 * 
	 * @return array key values of meta data, keys all in lowercase or null in case of failure.
	 */
	abstract public function readMetaData( Attachment $attachment, $bizMetaDataPreview );

	/**
	 * handleMetaData
	 * 
	 * Maps flat metadata structure to Object. Return false to pass this responsibility back.
	 * 
	 * @param array 				$metaData			key/value metadata array (as returned by readMetaData
	 * @param Object 				$object				destination object
	 * 
	 * @return boolean	true if implementation handles this, false to let core server handle this
	 */
	public function handleMetaData( $metaData, /** @noinspection PhpLanguageLevelInspection */
	                                Object &$object )
	{
		return false;
	}
	
	
	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
