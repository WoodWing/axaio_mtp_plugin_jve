<?php
/**
 * @since 		v6.1
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 */
 
require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class Preview_EnterpriseConnector extends DefaultConnector
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
	 * generatePreview
	 * 
	 * Create preview from from attachment, will only be called if needsFile returns false
	 * 
	 * @param Attachment 			$attachment			
	 * @param int					$max				Max width/height for preview
	 * @param string				$previewFormat		Out parameter to return the format of generated preview.
	 * @param MetaData				$meta				Output parameter, allows to modify meta data, typically for width/height/format/colorspace/dpi
	 * @param BizMetaDataPreview	$bizMetaDataPreview	Instance of BizMetaDataPreview for file caching
	 * 
	 * @return 
	 */
	abstract public function generatePreview( Attachment $attachment, $max, &$previewFormat, &$meta, $bizMetaDataPreview );

	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_BACKGROUND; }
	// L> Since v8, the background mode is supported to avoid longer wait and  swapping other processes
	// out of memory (delaying other services too). The foreground mode (RUNMODE_SYNCHRON) is no longer supported.
	final public function getRunModesLimited()  { return array( self::RUNMODE_BACKGROUND ); } // disallow foreground!
	final public function getInterfaceVersion() { return 1; }
}
