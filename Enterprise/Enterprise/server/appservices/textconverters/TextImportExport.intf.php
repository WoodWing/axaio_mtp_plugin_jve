<?php
/**
 * Interface for text convertors used by the Web Editor.<br>
 *
 * For each text file conversion, there is an import and an export module required. <br>
 * An import object implements TextImport interface and converts from its native format to XHTML. <br>
 * An export object implements TextExport interface and converts from XHTML back into its native format. <br>
 * XHTML is used as internal format by the TinyMCE component (3rd party) to allow text editing. <br>
 * For each text component, there is a XHTML 'document' created, which we call a frame. <br>
 * For a complete file conversion, a collection of frames is passed through (see xFrames param). <br>
 *
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
interface TextImport
{
	public function importFile( $filePath, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion );
	public function importBuf( $docIn, &$xFrames, &$stylesCSS, &$stylesMap, &$domVersion );

	/**
	 * Whether or not to parse inline images during text conversion.
	 * Should be called before importFile() or importBuf().
	 *
	 * @since 9.0.0
	 * @return bool Whether or not inline image processing is supported.
	 */
	public function enableInlineImageProcessing();

	/**
	 * Retrieves object ids of the inline images that are embedded in the text.
	 * Should be called after importFile() or importBuf().
	 *
	 * @since 9.0.0
	 * @return array|null Image object ids. Null when image processing not supported. Empty array when supported but none found.
	 */
	public function getInlineImages();
}

abstract class HtmlTextImport implements TextImport
{
	private $sameWindow;

	public function __construct()
	{
		$this->sameWindow = false;
	}

	/**
	 * Set the hyperlink to open in the same window.
	 * @since 8.2.x
	 */
	public function setOpenHyperlinkInSameWindow()
	{
		$this->sameWindow = true;
	}

	/**
	 * Get the setting if the Hyperlink should be opened in a new window.
	 *
	 * @since 8.2.x
	 * @return bool True to open the Hyperlink in the same window; False to open the Hyperlink in new window.
	 */
	public function getOpenHyperlinkInSameWindow()
	{
		return $this->sameWindow;
	}
}

interface TextExport
{
	public function exportFile( $xFrames, $filePath, $draft );
	public function exportBuf( $xFrames, &$docOut, $draft );
}
