<?php
/**
 *	Class derived from HmtlAnyField that represents a field for entering a
 *	string. Inputvalidation may be automatically done with a $PregMask.
 *
 *	@todo This class is not documented yet.
 *
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class HtmlStringField extends HtmlAnyField
{
	protected $MaxLength;
	protected $PregMask;

	function __construct( $owner, $name, $required = false, $readonly = false, $hidden = false,
	                      $maxlength = 0, $pregmask = null, $stayreadonly = false, $width = null )
	{
		HtmlAnyField::__construct( $owner, $name, $required, $readonly, $hidden, $stayreadonly, $width );
		$this->MaxLength = $maxlength;
		$this->PregMask = $pregmask;
	}

	public function drawHeader()
	{
		return '';
	}

	public function drawBody()
	{
		$readonlyflag = $this->ReadOnly ? ' readonly="readonly"' : '';
		$maxlengthflag = $this->MaxLength ? ' maxlength="'.$this->MaxLength.'"' : '';
		$hiddenflag = $this->Hidden ? ' type="hidden"' : '';
		$style = $this->Width ? ' style="width:'.$this->Width.'px;"' : '';
		$result = '<input'.$hiddenflag.' name="'.$this->Name.'" value="'.formvar( self::getValue() ).'"'.
			$readonlyflag.$maxlengthflag.$style.'></input>';
		if( !empty( $this->ErrorString ) ) {
			$result .= $this->ErrorString;
		}
		return $result;
	}

	public function setValue( $newvalue )
	{
		$this->Value = $newvalue;
	}

	public function requestInput()
	{
		if( isset( $_REQUEST[ $this->Name ] ) ) {
			return $_REQUEST[ $this->Name ];
		}
		return null;
	}

	public function requestValue()
	{
		return self::requestInput();
	}

	public function validate()
	{
		$result = false;
		$value = self::requestValue();
		if( $this->MaxLength > 0 ) {
			$result = strlen( $value ) <= $this->MaxLength;
		}
		if( !$result ) {
			return false;
		}
		if( preg_match( $this->PregMask, $result ) !== 1 ) {
			return false;
		}
		return HtmlAnyField::validate();
	}
}