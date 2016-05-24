<?php

$incpath = '';

require_once dirname(__FILE__).'/../../config/config.php';

define ('CHECKIMAGE', '<img src="../../config/images/opts_16.gif" />');
define ('LOCKIMAGE', '<img src="../../config/images/lock_16.gif" />');

function formvar( $var )
{
	return htmlentities( $var, ENT_QUOTES, 'UTF-8' );
}

function inputvar( $name, $value, $type = null, $domain = null, $nullallowed = true, $title = null, $disabled = false )
{
	switch ($type) {
		case 'checkbox':
			return '<input type="checkbox" title="'.$title.'" name="'.$name.'" '.(trim($value)?'checked="checked"':'').($disabled?' disabled':'').'/>';
		case 'combo':
			$combo = '<select name="'.$name.'"'.($disabled?' disabled':'').'>';
			if ($nullallowed) {
				$txt = '';
				if ($nullallowed !== true) $txt = "<".$nullallowed.">";
				$combo.= '<option value="">'.formvar($txt).'</option>';
			}
			if ($domain) foreach (array_keys($domain) as $key) {
				$combo .= '<option value="'.$key.'" '.($value==$key?'selected="selected"':'').'>'.formvar($domain[$key]).'</option>';
			}
			$combo .= '</select>';
			return $combo;
		case 'small':
			return '<input maxlength="8" size="5" name="'.$name.'" value="'.formvar($value).'"'.($disabled?' disabled':'').'/>';
		case 'area':
			return '<textarea name="'.$name.'" rows="5" cols="30"'.($disabled?' disabled':'').'>'.formvar($value).'</textarea>';
		case 'date':
			$temp = '<nobr><input name="'.$name.'" value="'.formvar($value).'"'.($disabled?' disabled':'').'/>';
			$calgif = '../../config/images/cal_16.gif';
			$langpatdate = LANGPATDATE;
			$dateformat = $langpatdate{0} . $langpatdate{2} . $langpatdate{4};
			$datesep = $langpatdate{1};
			$temp .= "<a href=\"javascript:displayDatePicker('$name',false,'$dateformat','$datesep',false)\"><img src=\"$calgif\"/></a></nobr>";
			return $temp;
		case 'datetime':
			require_once(BASEDIR . '/server/utils/htmlclasses/HtmlDateTimeField.class.php');
			$field = new HtmlDateTimeField(NULL, $name, !($nullallowed));
			$field->setValue($value);
			return $field->drawBody();
		case 'shortname':
			return '<input maxlength="40" name="'.$name.'" value="'.formvar($value).'"'.($disabled?' disabled':'').'/>';
		case 'hidden':
			return '<input type="hidden" name="'.$name.'" value="'.formvar($value).'"'.($disabled?' disabled':'').'/>';
	}

	// default	
	return '<input maxlength="255" name="'.$name.'" value="'.formvar($value).'"'.($disabled?' disabled':'').'/>';
}
