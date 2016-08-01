<?php
/**
 * Utility class to build HTML document based on HTML fragments (sections) taken from a HTML template.
 * This is especially used by web/admin applications.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_HtmlClasses_TemplateSection
{
	private $beginMark;
	private $endMark;
	
	/**
	 * Constructor. Determines begin and end markers used to specific a section within a HTML document.
	 *
	 * @param string $mark
	 */
	public function __construct( $mark )
	{
		$this->beginMark = '<!--SEC:'.$mark.'>-->';
		$this->endMark = '<!--SEC:<'.$mark.'-->';
	}
	
	/**
	 * Returns a HTML section taken from a given HTML document.
	 *
	 * @param string $template HTML template content
	 * @return string HTML section content
	 */
	public function getSection( $template )
	{
		$sections = array();
		$keysPattern = '/'.$this->beginMark.'.*'.$this->endMark.'/is';
		preg_match( $keysPattern, $template, $sections );
		return $sections ? $sections[0] : '';
	}

	/**
	 * Replaces a HTML section with a HTML template.
	 *
	 * @param string $template HTML template content
	 * @param string $section HTML section content
	 * @return string HTML document with replaced section
	 */
	public function replaceSection( $template, $section )
	{
		$keysPattern = '/'.$this->beginMark.'.*'.$this->endMark.'/is';
		return preg_replace( $keysPattern, $section, $template );
	}
	
	/**
	 * Takes all properties from a given data object and fill-in a HTML section.
	 * Assumed is that all property names are present in the HTML section which
	 * must be prefixed with "<!--PAR:" and postfixed with "-->" markers.
	 *
	 * @param string $section
	 * @param object $object
	 * @param boolean $editMode
	 * @return string HTML section with replaced fields
	 */
	public function fillInRecordFields( $section, $object, $editMode=false )
	{
		require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
		$class = get_class( $object );
		foreach( array_keys(get_object_vars($object)) as $prop ) {
			$var = '';
			if( property_exists( get_class($object), $prop ) ) {
				if( is_bool($object->$prop) ) { // do not call gettype (see php man)
					if( $editMode ) {
						$var = $object->$prop ? 'checked' : '';
					} else {
						$var = $object->$prop ? '<img src="../../config/images/opts_16.gif"/>' : '';
					}	
				} else {
					if( is_object($object->$prop) || is_array($object->$prop) ) {
					} else {
						$var = formvar($object->$prop);
					}
				}
			}
			$dateTimeVar =  DateTimeFunctions::iso2date($var);
			$var = $dateTimeVar ? $dateTimeVar : $var;
			$section = str_replace( '<!--PAR:'.$class.'->'.$prop.'-->', $var, $section );
		}
		return $section;
	}
}