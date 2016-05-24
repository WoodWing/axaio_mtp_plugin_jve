<?php
/**
 * @package 	Enterprise
 * @subpackage 	ServerPlugins
 * @since 		v7.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Tika extraction of file plaincontent
**/
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/MetaData_EnterpriseConnector.class.php';

class Tika_MetaData extends MetaData_EnterpriseConnector
{
	/**
	 * Tells if this plug-in can handle the given file format (content type).
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
	final public function canHandleFormat( $format )
	{
		require_once dirname(__FILE__) . '/config.php';
		$tikaConfigFormats = unserialize(TIKA_FORMATS);
		if( in_array($format, $tikaConfigFormats) ) {
			return 8;
		}

		return 0;
	}

	/**
	 * Read metadata from from content of the attachment.
	 * 
	 * @param Attachment $attachment 
	 * @param BizMetaDataPreview $bizMetaDataPreview Used for file caching
	 * @return array key values of meta data
	 */
	public function readMetaData( Attachment $attachment, $bizMetaDataPreview )
	{		
		require_once dirname(__FILE__) . '/TikaServerProxy.class.php';
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$buffer = $transferServer->getContent($attachment);		
		$metaData = array();
		$metaData['PlainContent'] = TikaServerProxy::extractPlainContent( $buffer );
		$metaData['Slugline'] = mb_strcut( $metaData['PlainContent'], 0, 250, 'UTF-8' );
		return $metaData;
	}
}