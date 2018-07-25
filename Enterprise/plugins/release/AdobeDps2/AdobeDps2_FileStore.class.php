<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Connector that implements special treatment for Adobe DPS files in the FileStore.
 */

require_once BASEDIR.'/server/interfaces/plugins/connectors/FileStore_EnterpriseConnector.class.php';

class AdobeDps2_FileStore extends FileStore_EnterpriseConnector
{
	/**
	 * Maps the homebrewed combined file format (dime type) for Adobe DPS article images
	 * onto a special postfix to be used in the file names in the FileStore.
	 * This implements mulitple output (rendition) files per layout (per edition).
	 *
	 * @param integer $id         Object id.
	 * @param string $rendition   Rendition (native, preview, thumb, etc).
	 * @param string $format      File format (mime type).
	 * @param string $version     Object version in major.minor notation.
	 * @param string $page        The layout page number. Typically used for thumb/preview/pdf per page.
	 * @param integer $edition    The object edition id.
	 * @return string Postifx to used in filename.
	 */
	public function mapFormatToPostfix( $id, $rendition, $format, $version, $page, $edition )
	{
		require_once dirname(__FILE__). '/utils/Folio.class.php';
		$postfix = '';
		if( AdobeDps2_Utils_Folio::isSupportedOutputImageFormat( $format ) ) {
			$formats = AdobeDps2_Utils_Folio::parseSupportedOutputImageFormat( $format );
			switch( $formats[1] ) {
				case 'application/adobedps-article-image':
					$postfix = 'article';
				break;
				case 'application/adobedps-social-image':
					$postfix = 'social';
				break;
			}
		}
		return $postfix;
	}
}
