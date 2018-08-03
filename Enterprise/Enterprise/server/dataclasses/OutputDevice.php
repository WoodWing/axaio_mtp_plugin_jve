<?php
/**
 * Data class of an Output Device (for publishing).
 *
 * @since v7.5
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
class OutputDevice
{
	public $Id               = 0;
	public $Name             = '';
	public $Description      = '';
	public $SortOrder        = null;
	
	public $PortraitWidth    = 0;
	public $PortraitHeight   = 0;
	public $LandscapeWidth   = 0;
	public $LandscapeHeight  = 0;
	
	public $PixelDensity     = 0;
	public $PreviewQuality   = 0;
	public $LandscapeLayoutWidth = 0.0;
	public $PngCompression   = 0;

	public $TextViewPadding  = null; // 4 digits (comma sep)
	
	private $Valid           = true;
	private $DeviceErrors    = null;
	
	/**
	 * Determines the maximum screen height in pixels.
	 *
	 * @param boolean $portrait TRUE when screen held in portrait position, or FALSE for landscape.
	 * @return integer Screen height.
	 */
	public function getScreenHeight( $portrait )
	{
		return $portrait ? $this->PortraitHeight : $this->LandscapeHeight;
	}

	/**
	 * Determines the maximum screen width in pixels.
	 *
	 * @param boolean $portrait TRUE when screen held in portrait position, or FALSE for landscape.
	 * @return integer Screen width.
	 */
	public function getScreenWidth( $portrait )
	{
		return $portrait ? $this->PortraitWidth : $this->LandscapeWidth;
	}

	/**
	 * Determines the screen resolution in DPI (Dots Per Inch).
	 */
	public function getScreenResolution()
	{
		$dpi = (float)(($this->LandscapeWidth / $this->LandscapeLayoutWidth) * 72);
		// Note: 72 is fixed and stands for points per inch for the InDesign layout
		return $dpi;
	}

	/**
	 * Maximum height in pixels for page thumbs used in the page viewer
	 * The page viewer will be as high as necessary to include the thumbs.
	 *
	 * @return int
	 */
	public function getMaxHeightPageThumb()
	{
		return $this->ThumbHeight;
	}

	/**
	 * String with the self defined textview padding
	 *
	 * @return string
	 */
	public function getTextViewPadding()
	{
		return $this->TextViewPadding;
	}

	/**
	 * Set a boolean whether the device is configured correctly.
	 *
	 * @param boolean $isValid
	 */
	public function setValid( $isValid )
	{
		$this->Valid = $isValid;
	}

	/**
	 * Get a boolean whether the device is configured correctly.
	 *
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->Valid;
	}
}
