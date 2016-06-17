<?php
	/**
 * @package     SCEnterprise
 * @subpackage  HtmlClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * Class derived from HmtlAnyField that represents a field for entering a
 * timedifference. At this moment only in days/hours/minutes. May need
 * expansion later.
 *
 * @todo Implement fields for other time-units.
 * @todo Implement flags for selecting the fields for time-units.
**/

require_once BASEDIR.'/server/utils/htmlclasses/HtmlAnyField.class.php';
require_once BASEDIR.'/server/admin/global_inc.php'; // formvar, inputvar

class HtmlDiffTimeField extends HtmlAnyField
{
	public $MinutesGranule;
	public $MaxDays;
	public $DaysLabel;
	public $HoursLabel;
	public $MinutesLabel;

	/**
	 *  Which combos to show.
	 *  @todo Not all combos implemented yet.
	**/

	public $ShowYears;
	public $ShowMonths;
	public $ShowWeeks;
	public $ShowDays;
	public $ShowHours;
	public $ShowMinutes;
	public $AsTextField;

	/**
	 *  $DiffDays, $DiffHours and $DiffMinutes of the timedifference.
	**/
	protected $DiffDays;
	protected $DiffHours;
	protected $DiffMinutes;


	/**
	 *	Constructor
	 *	@param $owner object derived from HtmlBase or null
	 *	@param $name string may not contain special chars
	 *	@param $required boolean = false 
	 *	@param $readonly boolean = false
	 *	@param $hidden boolean = false
	**/
	function __construct($owner, $name, $required=false, $readonly=false, $hidden=false, $stayreadonly=false)
	{
		HtmlAnyField::__construct($owner, $name, $required, $readonly, $hidden, $stayreadonly);
		$this->MinutesGranule = 5;
		$this->MaxDays = 31;

		$this->DaysLabel = 'd';
		$this->HoursLabel = 'h';
		$this->MinutesLabel = 'm';
		
		
		$this->ShowDays = true;
		$this->ShowHours = true;
		$this->ShowMinutes = true;

		/**
		 *  @todo Why is LANGPATTIMEDIFF not defined here?
		**/
		if (defined('LANGPATTIMEDIFF')) {
			$langpattimediff = LANGPATTIMEDIFF;
			$this->DaysLabel = $langpattimediff{0};
			$this->HoursLabel = $langpattimediff{1};
			$this->MinutesLabel = $langpattimediff{2};
		}
	}

	/**
	 *  setValue
	 *  @param $totaldiffinseconds int 
	 *  sets $Value to this value.
	 *  $DiffDays, $DiffHours and $DiffMinutes are also set for ease of use.
	**/
	public function setValue($totaldiffinseconds)
	{
		$this->Value = $totaldiffinseconds;
		$secondsleft = $totaldiffinseconds;
		
		$this->DiffDays = (int) ($secondsleft / (24*60*60));
		$secondsleft = (int) ($secondsleft - ($this->DiffDays * (24*60*60)));
		$this->DiffHours = (int) ($secondsleft / (60*60));
		$secondsleft = (int) ($secondsleft - ($this->DiffHours * (60*60)));
		$this->DiffMinutes = (int) ($secondsleft / (60));
	}

	/**
	 *  @return returns the raw input-value as an array of three strings as
	 *  in this implementation there are three inputfields (comboboxes).
	 *  Returns null if not posted.
	**/	
	public function requestInput()
	{
		$diffdays = isset($_REQUEST[$this->Name . '_diffdays']) ? $_REQUEST[$this->Name . '_diffdays'] : '';
		$diffhours = isset($_REQUEST[$this->Name . '_diffhours']) ? $_REQUEST[$this->Name . '_diffhours'] : '';
		$diffminutes = isset($_REQUEST[$this->Name . '_diffminutes']) ? $_REQUEST[$this->Name . '_diffminutes'] : '';
		
		if (isset($diffdays) || isset($diffhours) || isset($diffminutes)) {
			$result = array();
			$result['diffdays'] = (int) $diffdays;
			$result['diffhours'] = (int) $diffhours;
			$result['diffminutes'] = (int) $diffminutes;
		} else {
			$result = null;   
		}
		return $result;
	}

	/**
	 *  @return returns the converted raw input-values as totalseconds.
	 *  Returns null if not posted.
	**/
	public function requestValue()
	{
		$temp = self::requestInput();
		if (is_null($temp)) {
			return $temp;   
		}
		if (is_array($temp)) {
			$result = ($temp['diffdays']*(24*60*60)) + ($temp['diffhours'] * (60*60)) + ($temp['diffminutes'] * (60));
		}
		else {
			$result = 0;
		}
		return $result;
	}

	/**
	 *  Draws nothing as there is nothing to include,
	 *  but drawHeader must be implemented as it is abstract
	**/
	public function drawHeader()
	{
		return '';
	}
	
	/**
	 *  
	**/    
	public function getDisplayValue()
	{
		$result = '';
		if ($this->Value == 0)
		{
			return '';
		}
		if ($this->ShowDays)
		{
			$result .= $this->DiffDays . $this->DaysLabel;
		}
		if ($this->ShowHours)
		{
			$result .= ' ' . $this->DiffHours . $this->HoursLabel;
		}
		if ($this->ShowMinutes)
		{
			$result .= ' ' . $this->DiffMinutes . $this->MinutesLabel;
		}
		return strtolower($result);
	}

	/**
	 *  Draws the three inputfields
	 *  Each in separate function
	 *  @return html
	**/
	public function drawBody()
	{
		$result = '<nobr>';
		
		if ($this->ReadOnly && $this->StayReadOnly)
		{
			$value = '';
			if ($this->ShowDays) $value .= $this->DiffDays . $this->DaysLabel . ' ';
			if ($this->ShowHours) $value .= $this->DiffHours . $this->HoursLabel . ' ';
			if ($this->ShowMinutes) $value .= $this->DiffMinutes . $this->MinutesLabel;
			$result = '<input readonly="readonly" value="'.formvar($value).'"/>';
		}
		else
		{
			if ($this->ShowDays) $result .= self::drawDiffDaysFieldBody($this->Name . '_diffdays');
			if ($this->ShowHours) $result .= self::drawDiffHoursFieldBody($this->Name . '_diffhours');
			if ($this->ShowMinutes) $result .= self::drawDiffMinutesFieldBody($this->Name . '_diffminutes');
		}
		$result .= '</nobr>';

		if (!empty($this->ErrorString)) { $result .= $this->ErrorString; }
		return $result;
	}

	protected function drawDiffDaysFieldBody($fieldname)
	{
		$days = BizResources::localize('DAYS');
		$label = strtolower($this->DaysLabel);
		$onchange = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'readonly="readonly"' : '';
		$result = '<input '.$readonlyflag.' '.$onchange.' name="'.$fieldname.'" title="'.$days.'" style="width: 28px" value="'.formvar($this->DiffDays).'"/>';
		$result .= "$label&nbsp;&nbsp;";
		return $result;
	}
	
	protected function drawDiffHoursFieldBody($fieldname)
	{
		$hours = BizResources::localize('HOURS');
		$label = strtolower($this->HoursLabel);
		$onchange = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'readonly="readonly"' : '';
		$result = '<input '.$readonlyflag.' '.$onchange.' name="'.$fieldname.'" title="'.$hours.'" style="width: 28px" value="'.formvar($this->DiffHours).'"/>';
		$result .= "$label&nbsp;&nbsp;";
		return $result;
	}

	protected function drawDiffMinutesFieldBody($fieldname)
	{
		$mins = BizResources::localize('MINUTES');
		$label = strtolower($this->MinutesLabel);
		$onchange = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'readonly="readonly"' : '';
		$result = '<input '.$readonlyflag.' '.$onchange.' name="'.$fieldname.'" title="'.$mins.'" style="width: 28px" value="'.formvar($this->DiffMinutes).'"/>';
		$result .= "$label&nbsp;&nbsp;";
		return $result;
	}

	protected function drawDiffDaysFieldBodyAsCombo($fieldname)
	{
		$days = BizResources::localize('DAYS');
		$onchange = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'disabled="disabled"' : '';
		$result = '<select '.$readonlyflag.' '.$onchange.' name="'.$fieldname.'" title="'.$days.'" style="width: 52px">';
		for ($dd=0; $dd<=$this->MaxDays; $dd++) 
		{	
			$selected = ($dd == $this->DiffDays) ? ' selected="selected" ' : ' ';
			$result .= '<option '.$selected.' value="'.$dd.'">'.$dd.' '.$this->DaysLabel.'</option>';
		}
		$result .= '</select>';
		return $result;
	}
	
	protected function drawDiffHoursFieldBodyAsCombo($fieldname)
	{
		$hours = BizResources::localize('HOURS');
		$onchange = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'disabled="disabled"' : '';
		$result = '<select '.$readonlyflag.' '.$onchange.' name="'.$fieldname.'" title="'.$hours.'" style="width: 52px">';
		
		for ($dh=0; $dh<=23; $dh++) 
		{	
			$selected = ($dh == $this->DiffHours) ? ' selected="selected" ' : ' ';
			$result .= '<option '.$selected.' value="'.$dh.'">'.$dh.' '.$this->HoursLabel.'</option>';
		}
		$result .= '</select>';
		return $result;
	}

	protected function drawDiffMinutesFieldBodyAsCombo($fieldname)
	{
		$mins = BizResources::localize('MINUTES');
		$onchange = isset($this->OnChange) ? 'onchange="'.$this->OnChange.'"' : '';
		$readonlyflag = $this->ReadOnly ? 'disabled="disabled"' : '';
		$result = '<select '.$readonlyflag.' '.$onchange.' name="'.$fieldname.'" title="'.$mins.'" style="width: 52px">';
		for ($dm=0; $dm<60; $dm+=$this->MinutesGranule) 
		{	          
			$selected = ($this->DiffMinutes > $dm) && ($this->DiffMinutes) < ($dm + $this->MinutesGranule) ? ' selected="selected" ' : ' ';
			$result .= '<option '.$selected.' value="'.$dm.'">'.$dm.' '.$this->MinutesLabel.'</option>';
		}
		$result .= '</select>';
		return $result;
	}
}
?>
