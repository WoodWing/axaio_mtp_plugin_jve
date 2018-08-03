<?php
/**
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class HtmlColorField extends HtmlAnyField
{
	private $boxSize = null;

	function __construct( $owner, $name, $required = false, $readonly = false, $hidden = false )
	{
		HtmlAnyField::__construct( $owner, $name, $required, $readonly, $hidden );
	}

	public function drawHeader()
	{
		return '';
	}

	public function drawBody()
	{
		if( is_null( $this->boxSize ) ) {
			$result = '<table><tr><td bgcolor="'.$this->Value.'"></td></tr></table>';
		} else {
			if( empty( $this->Value ) ) { // avoid empty boxes when no color must be shown at all
				$result = '';
			} else {
				$result = '<table align="center" title="'.$this->Title.'" border="1" style="border-collapse: collapse" bordercolor="#606060" '.
					'height="'.$this->boxSize.'" width="'.$this->boxSize.'"><tr><td bgColor="'.$this->Value.'"></td></tr></table>';
			}
		}
		if( !empty( $this->ErrorString ) ) {
			$result .= $this->ErrorString;
		}
		return $result;
	}

	public function setValue( $newcolor )
	{
		$this->Value = $newcolor;
	}

	public function requestInput()
	{
		return @$_REQUEST( $this->Name );
	}

	public function requestValue()
	{
		return self::requestInput();
	}

	public function setBoxSize( $boxSize )
	{
		$this->boxSize = $boxSize;
	}

	public function getBoxSize()
	{
		return $this->boxSize;
	}
}
