<?php

/**
 *	@package     SCEnterprise
 *	@subpackage  HtmlClasses
 *	@since       v4.2
 *	@copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/utils/htmlclasses/HtmlBase.class.php';

/**
 *	Abstract class derived from HmtlBase that represents any type of field in html.
 *	May represent an <input>-field but also a <select>-field (combo).
 *	May also represent a combination of <input> or <select> or other html-types.
 *	The derived field-class must take care of drawing the html and handling input.
**/
	abstract class HtmlAnyField extends HtmlBase
	{
		public $DrawStyle;
		
		
		
/**
 *	$Required: if input is required or not.
 *	If $Required is true the value is automatically checked when validating.
 *	An empty string or a string containing only whitespace is normally not
 *	accepted.
**/
		public $Required;
		

/**
 *	$ReadOnly: if a value can be edited or not.
**/
		public $ReadOnly;


/**
 *	$Hidden: if the field is shown or only used as a dev-only variable.
**/
		public $Hidden;

		

/**
 *	$StayReadOnly: if a field is shown as a text-field instead of something else, like a combo.
 *	Only applicable if the field is ReadOnly. 
**/
		public $StayReadOnly;


/**
 *	
 *
**/
		public $Title;	


/**
 *	$OnCalculate: dev-defined callback to calculate the value of the field.
 *	A calculated field is only calculated if the field is $ReadOnly. (?!?)
 *	Must call setValue to set the value.
**/
		public $OnCalculate;


/**
 *	$OnValidate: dev-defined callback called when validating the entered value.
 *	Must return false or true.
**/
		public $OnValidate;


/**
 *	$OnChange: string with what to do when a fields value changes, typically calls 
 *	a javascript to update some values without refreshing.
**/
		public $OnChange;

/**
 *	@param $OnClick: string with what to do when the field is clicked.
**/
		public $OnClick;

/**
 *	$Value: generic placeholder for any value a field may be.
 *	The allowed values for $Value depend on the type of field and must be 
 *	described in the derived class-definition. Best is to stick closely to
 *	standard value-types (like 'iso' for a date).
 *	Be carefull to note that $Value does NOT represent the drawn value but is
 *	only used for calculating the drawn value.
**/
		protected $Value;

		/**
		 * @var int Width  The width in pixels of the widget.
		 */
		protected $Width;
/**
 *	Constructor
 *	@param $owner object derived from HtmlBase
 *	@param $name string may not contain special chars
 *	@param $required boolean = false 
 *	@param $readonly boolean = false
 *	@param $hidden boolean = false
 *	@param $stayreadonly boolean = false
 *	@param $width int = null
 **/
		function __construct( $owner, $name, $required=false, $readonly=false, 
								$hidden=false, $stayreadonly=false, $width=null)
		{
			HtmlBase::__construct($owner, $name);
			$this->Required = $required;
			$this->ReadOnly = $readonly;
			$this->Hidden = $hidden;
			$this->StayReadOnly = $stayreadonly;
			$this->Title = $name;
			$this->Width = $width;
			$this->DrawStyle = null;
		}

/**
 *	@param $newvalue Sets the value of $Value. A derived setValue-function must
 *	check if the value is allowed.
 *	@return null if the value is NOT allowed/set.
 */
		abstract public function setValue($newvalue);
		

/**
 *	
 *
**/
		public function getDisplayValue()
		{
			return $this->Value;	
		}
		
/**
 *	@return returns the raw input (as in $_REQUEST['fieldname']) as entered by
 *	the user.
 *	If a field is combined (consists of more html-fields) the values are
 *	returned as an associative array with names => raw valuepairs.
 *	If the form was not posted (no $_REQUEST available) the function should
 *	return null.
**/
		abstract public function requestInput();


/**
 *	@return returns the input as a $Value. If needed the value is quoted,
 *	validated and converted to the type of value the class expects.
 *	If no value was posted should return null.
**/
		abstract public function requestValue();

		

/**
 *	@return returns the $Value as set in setValue()
 *	Can not be used for getting input-values. Use requestValue/requestInput instead.
**/
		public function getValue()
		{
			return $this->Value;
		}

				
/**
 *	Does basic validation on the input for the fieldtype.
 *	Calls OnValidate if assigned to be able to implement more specific
 *	validation. 
 *	@return boolean true if input was valid, false if not.
**/
		public function validate()
		{
			$result = true;
			if (is_callable($this->OnValidate)) {
				$result = call_user_func($this->OnValidate);
			}
			return $result;
		}


/**
 *	Calls OnCalculate if assigned.
 *	The OnCalculate-callback is responsible for setting the value with setValue()
 *	@return nothing
**/
		public function calculate()
		{
			$result = $this->Value;
			if ($this->ReadOnly || $this->Hidden) {
				if (is_callable($this->OnCalculate)) {
					$result = call_user_func($this->OnCalculate);
				}
				return $result;
			}
			return null;
		}
	}
?>
