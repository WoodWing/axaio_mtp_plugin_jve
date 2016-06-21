<?php
/**
 * @package     SCEnterprise
 * @subpackage  HtmlClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Class derived from HmtlAnyField that represents a field for entering a valid date.
**/

require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlDateField extends HtmlAnyField
{
	public $CalenderIcon;

	/**
	 *  $Year, $Month and $Day of the date. These are set when setValue is executed.
	**/
	protected $Year;
	protected $Month;
	protected $Day;


	/**
	 *	Constructor
	 *	@param $owner object derived from HtmlBase or null
	 *	@param $name string may not contain special chars
	 *	@param $required boolean = false 
	 *	@param $readonly boolean = false
	 *	@param $hidden boolean = false
	**/
	function __construct($owner, $name, $required=false, $readonly=false, $hidden=false)
	{
		parent::__construct($owner, $name, $required, $readonly, $hidden);
		$this->CalenderIcon = self::$IconDir . 'cal_16.gif';
	}
	
	/**
	 *  setValue
	 *  @param $isodatetime string iso-formatted string, for example: 01-01-2006T09:30:45
	 *  sets $Value to this value if indeed iso-formatted and valid.
	 *  $Year, $Month and $Day are also set for ease of use.
	 *  @return Returns true if the value was succesfully set, otherwise false.
	**/
	public function setValue($isodatetime)
	{
		$temp = DateTimeFunctions::iso2dateArray( $isodatetime );
		if ($temp) {
			$this->Year = $temp['year'];
			$this->Month = $temp['mon'];
			$this->Day = $temp['mday'];
			$this->Value = $isodatetime;
			return true;
		}
		return false;
	}


	/**
	 *  @return returns the raw input-value as a string. Returns null if not posted.
	**/	
	public function requestInput()
	{
		$result = @$_REQUEST[$this->Name];
		return $result;
	}

	
	/**
	 *  @return returns the converted raw input-value as an isodatetime.
	 *  Returns null if not posted.
	**/
	public function requestValue()
	{
		$result = self::requestInput();
		if (is_null($result)) {
			return null; // No validation
		}
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		return DateTimeFunctions::validDate($result);
	}

	/**
	 *  @return string Html that includes the javascript-file for picking a date.
	**/
	public function drawHeader()
	{
		$jsInc = file_get_contents( BASEDIR.'/server/utils/javascript/DatePicker.js' );
		$result = '<script language="javascript">'. HtmlDocument::buildDocument( $jsInc, false ) .'</script>';
		$result .= "<script language='Javascript'>";
		$result .= 'function datePickerClosed(datefield) { if (datefield.onchange) datefield.onchange(); }';
		$result .= "</script>";
		return $result;
	}


	/**
	 *  @return string Html that draws the input-field and datepicker-button.
	**/
	public function drawBody()
	{
		/**
		 *  The actual drawing is separated into subfunctions to make it
		 *  easier to implement other styles or other ways of input.
		**/
		
		$result = self::drawDateFieldBody($this->Name);
		if (!empty($this->ErrorString)) { $result .= $this->ErrorString; }
		return $result;
	}

	protected function drawDateFieldBody($fieldname)
	{
		if ($this->Month && $this->Day && $this->Year) {
			switch (self::$DateFormat)
			{
				case 'mdy':
				{
					$htmlvalue = $this->Month . self::$DateSep . $this->Day. self::$DateSep . $this->Year;
					break;
				}
				case 'ymd':
				{
					$htmlvalue = $this->Year . self::$DateSep . $this->Month . self::$DateSep . $this->Day;
					break;
				}
				case 'dmy':
				default:
				{
					$htmlvalue = $this->Day . self::$DateSep . $this->Month . self::$DateSep . $this->Year;
				}
			}
		}
		else {
			$htmlvalue = '';	
		}

		$readonlyflag = $this->ReadOnly ? 'readonly="readonly"' : '' ;
		$hidden = $this->Hidden ? ' type="hidden"' : '';
		$datesep = self::$DateSep;
		$dateformat = self::$DateFormat;
		$onchangeflag = isset($this->OnChange) ? "onchange='$this->OnChange'" : "";
		$result = '<input ' . $hidden . ' name="'.$fieldname.'" value="'.formvar($htmlvalue).'" '.$readonlyflag.' '.$onchangeflag.'></input>';
		if (!$this->ReadOnly)
		{
			$result .= "<a href=\"javascript:displayDatePicker('$fieldname',false,'$dateformat','$datesep')\"><img src='$this->CalenderIcon'/></a>";
		}
		return $result;	
	}
}
?>