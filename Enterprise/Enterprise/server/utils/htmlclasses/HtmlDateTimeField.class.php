<?php
/**
 * Class derived from HmtlAnyField that represents a field for entering a valid date AND time.
 *
 * @todo Implement languages for the date-time picker: 'close', 'this month', 'days of the week', 'months'
 *
 * @package     Enterprise
 * @subpackage  HtmlClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyField.class.php';
require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';
require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlDateTimeField extends HtmlAnyField
{
	public $MinutesGranule;
	public $CalenderIcon;        

	/*
	 *  $Year, $Month and $Day of the date, and $Hour, $Minute and $Second of the time.
	 *  These are set when setValue is executed.
	 */
	protected $Year;
	protected $Month;
	protected $Day;
	protected $Hour;
	protected $Minute;
	protected $Seconds;

	/**
	 *	Constructor
	 *
	 * @param HtmlBase|null $owner either null or an object derived from HtmlBase
	 *	@param $name string may not contain special chars
	 *	@param $required boolean = false 
	 *	@param $readonly boolean = false
	 *	@param $hidden boolean = false
	 */
	public function __construct($owner, $name, $required=false, $readonly=false, $hidden=false)
	{			
		HtmlAnyField::__construct($owner, $name, $required, $readonly, $hidden);
		$this->MinutesGranule = 5;

		// todo Replace this icon with 'cal.gif' instead of cal_16.gif as this is the same icon as for issue and the calender looks better.
		$this->CalenderIcon = self::$IconDir . 'cal_16.gif';
	}
	

	/**
	 * Sets a date time value.
	 *
	 * @param $isodatetime string iso-formatted string, for example: 01-01-2006T09:30:45
	 * sets $Value to this value if indeed iso-formatted and valid.
	 * $Year, $Month, $Day, $Hour, $Minute and $Second are also set for ease of use.
	 * @return boolean Returns true if the value was successfully set, otherwise false.
	 */
	public function setValue($isodatetime)
	{
		if( is_array($isodatetime) ) {
			$this->ErrorString = $isodatetime['error'];
			$isodatetime = (string)$isodatetime['value'];
		}
		$temp = DateTimeFunctions::iso2dateArray( $isodatetime );
		if ($temp)
		{
			$this->Year = $temp['year'];
			$this->Month = $temp['mon'];
			$this->Day = $temp['mday'];
			$this->Hour = $temp['hours'];
			$this->Minute = $temp['minutes'];
			$this->Seconds = $temp['seconds'];
			$this->Value = $isodatetime;
			return true;
		}
		else
		{
			$this->Value = null;
		}
		return false;
	}
	
	/**
	 *  @return returns the raw input-value as an array of two strings as
	 *  in this implementation there are two inputfields.
	 *  Returns null if not posted.
	**/	
	public function requestInput()
	{
		if (!isset($_REQUEST[$this->Name . '_date'])) {
			return null;
		}

		$result = array();					
		$result['date'] = isset($_REQUEST[$this->Name . '_date']) ? $_REQUEST[$this->Name . '_date'] : '';
		$result['time'] = isset($_REQUEST[$this->Name . '_time']) ? $_REQUEST[$this->Name . '_time'] : '';
		return $result;
	}

	/**
	 *  @return returns the converted raw input-value as an isodatetime-string.
	 *  Returns null if not posted or input was not valid.
	 *  Returns '' if no input given but the field is not required, so no input then, which is ok.
	**/
	public function requestValue()
	{
		$temp = self::requestInput();
		if (is_null($temp)) {
			$result = null;
		} else if (trim($temp['date']) === '') {
			$result = '';   
		} else {
			$result = DateTimeFunctions::validDate( $temp['date'] . ' ' . $temp['time'] );
		}
		return $result;
	}

	/**
	 *
	**/
	public function getDisplayValue()
	{
		if ($this->Value != null)
		{
			$time = DateTimeFunctions::iso2time( $this->Value );      
			$datesep = self::$DateSep;
			
			if (self::$DateFormat == 'mdy')
			{
				$result = strftime("%m$datesep%d$datesep%Y", $time);
			}
			else // if self::$DateFormat == 'dmy' or anything else
			{
				$result = strftime("%d$datesep%m$datesep%Y", $time);
			}
			
			if (isset($this->Hour) && isset($this->Minute))
			{
				if (!empty(self::$AM) && !empty(self::$PM))
				{
					$hour = $this->Hour < 12 ? $this->Hour : $this->Hour - 12;
					$ampm = $this->Hour < 12 ? self::$AM : self::$PM;
					$result .= ', ' . DateTimeFunctions::addzeros($hour,2) . self::$TimeSep . DateTimeFunctions::addzeros($this->Minute,2) . ' ' . $ampm;
				}
				else
				{
					$result .= ', ' . DateTimeFunctions::addzeros($this->Hour,2) . self::$TimeSep . DateTimeFunctions::addzeros($this->Minute,2);
				}
			}
			return $result;
		}
		else
		{
			return '';
		}
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
	 *  @return string Html that draws the input-fields and datepicker-button.
	**/
	public function drawBody()
	{
		/**
		 *  The actual drawing is separated into subfunctions to make it
		 *  easier to implement other styles or other ways of input.
		**/

		$result = '<div style="float: left; width: 200px">';
		$result .= '<div style="float: left; width: 175px">';
		$result .= self::drawDateFieldBody($this->Name . '_date');
		$result .= self::drawTimeFieldBody($this->Name . '_time');
		$result .= '</div>';

		if (!$this->ReadOnly)
		{
			$datesep = self::$DateSep;
			$dateformat = self::$DateFormat;
			$fieldname = $this->Name . '_date';
			$pick = BizResources::localize('PICK');
			//$result .= "<a href=\"javascript:displayDatePicker('$fieldname',false,'$this->DateFormat','$this->DateSep')\" title='$pick'>";
			//$result .= "<div style='float: left; margin-top: 4px; margin-left: 4px'><img src='$this->CalenderIcon' style='border-style: none'/></a></div>";
			//$result .= "<div style='float: left; padding: 2px'>";
			//$result .= "<input type='image' src='$this->CalenderIcon' onclick=\"javascript:displayDatePicker('$fieldname',false,'$this->DateFormat','$this->DateSep')\" title='$pick'/></div>";
			$result .= "<div style='float: left; margin-top: 4px; margin-left: 4px'><img src='$this->CalenderIcon'";
			$result .= " onclick=\"javascript:displayDatePicker('$fieldname',false,'$dateformat','$datesep')\"";
			$result .= " style='border-style: none' title='$pick'/></a></div>";
		}
		$result .= '</div>';
		if (!empty($this->ErrorString)) { $result .= '<i><font color="red">'.$this->ErrorString.'</font></i>'; }
		return $result;
	}
	
	protected function drawDateFieldBody($fieldname)
	{
		$onchangeflag = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'readonly="readonly"' : '';
		$date = BizResources::localize('DATE');
		if ($this->Value != null)
		{
			if (self::$DateFormat == 'mdy')
			{
				$htmlvalue = isset($this->Day) ? DateTimeFunctions::addzeros($this->Month,2) . self::$DateSep . DateTimeFunctions::addzeros($this->Day,2) . self::$DateSep . $this->Year : '';                    
			}
			else // if self::$DateFormat == 'dmy' or anything else
			{
				$htmlvalue = isset($this->Day) ? DateTimeFunctions::addzeros($this->Day,2) . self::$DateSep . DateTimeFunctions::addzeros($this->Month,2) . self::$DateSep . $this->Year : '';
			}
		}
		else 
		{
			$htmlvalue = null;
		}
		return
			'<div style="float: left">'.
				'<input name="'.$fieldname.'" value="'.formvar($htmlvalue).'" style="width: 100px" '.
					$readonlyflag.' '.$onchangeflag.' title="'.$date.'"/>'.
			'</div>';
	}
	
	protected function drawTimeFieldBody($fieldname)
	{
		$onchangeflag = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'readonly' : '';
		if ($this->Value != null)
		{
			if (isset($this->Hour) && isset($this->Minute))
			{
				if (!empty(self::$AM) && !empty(self::$PM))
				{
					$hour = $this->Hour < 12 ? $this->Hour : $this->Hour - 12;
					$ampm = $this->Hour < 12 ? self::$AM : self::$PM;
					$htmlvalue = DateTimeFunctions::addzeros($hour,2) . self::$TimeSep . DateTimeFunctions::addzeros($this->Minute,2) . ' ' . $ampm;
				}
				else
				{
					$htmlvalue = DateTimeFunctions::addzeros($this->Hour,2) . self::$TimeSep . DateTimeFunctions::addzeros($this->Minute,2);
				}
			}
			else 
			{
				$htmlvalue = '';
			}
		}
		else
		{
			$htmlvalue = '';
		}
		$time = BizResources::localize('TIME');
		return 
			'<div style="float: left">'.
				'<input name="'.$fieldname.'" value="'.formvar($htmlvalue).'" style="width: 48px" '.
					$onchangeflag.' '.$readonlyflag.' title="'.$time.'"/>'.
			'</div>';
	}
}