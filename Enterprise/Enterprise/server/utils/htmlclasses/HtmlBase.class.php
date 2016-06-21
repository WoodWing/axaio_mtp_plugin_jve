<?php

/**
 * @package     Enterprise
 * @subpackage  HtmlClasses
 * @since       v4.2
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR . '/server/utils/LogHandler.class.php';
require_once BASEDIR . '/server/secure.php';

/**
 * Baseclass for any 'class' that is drawable on html.
 * All classes in this module are derived from HtmlBase.
 * HtmlBase also contains static variables with settings and defaults.
 *
 * @todo If needed: better implement (partly rethink) validation in derived classes.
 * @todo Implement check for the $Name being unique.
 */
abstract class HtmlBase
{

	static public $Application;

	/**
	 *	Static variables which contain default settings.
	 *	They are initialized once, when the first instance is created.
	 */
	static public $DateFormat;
	static public $AM;
	static public $PM;
	static public $DateSep;
	static public $TimeSep;
	static public $IconDir;
		
	/**
	 *	For every instance that is created the static variable $BaseIdCounter is
	 *	incremented so that every instance gets it's own $Id. This may be useful
	 *	when streaming/referencing instances.
	 *	Be careful to note that the baseidcounter does NOT allways represent the
	 *	count of instances in memory, for example when an instance is deleted.
	 *	It does represent the count of instances created.
	 */
	static protected $BaseIdCounter;

		
	/**
	 *	Every instance has its own unique $Id;
	 */
	protected $Id;
		

	/**
	 *	$Owner representing the logical owner of the instance.
	 *  Can be null or else any instance derived from HtmlBase.
	 *  Can be used to apply programming logic in an automated fashion, like
	 *  drawing instances only if and/or when the $Owner is drawn, etc.
	 */
	protected $Owner;
		

	/**
	 *	$Name that is used to identify the object in html.
	 *	When a <input name=...>-field is created for example this is the name used.
	 *	When the form is posted the same $Name is used (must be used) to retrieve
	 *	the value.
	 */
	public $Name;
		

	/**
	 *	$ErrorString holds an errormessage for display to the user.
	 */
	public $ErrorString;


	/**
	 * Constructor
	 *
	 * @param HtmlBase|null $owner either null or an object derived from HtmlBase
	 * @param string $name
	 */
	public function __construct($owner, $name)
	{
		if (!self::$BaseIdCounter) {

			self::$AM = '';
			self::$PM = '';						
			if (defined('LANGPATAMPM')) {
				if (LANGPATAMPM == true) {
					self::$AM = 'AM';
					self::$PM = 'PM';						
				}
			}

			self::$DateFormat = 'dmy';
			self::$DateSep = '-';
			if (defined('LANGPATDATE')) {
				$langpatdate = LANGPATDATE;
				self::$DateFormat = $langpatdate{0} . $langpatdate{2} . $langpatdate{4};
				self::$DateFormat = strtolower(self::$DateFormat);
				self::$DateSep = $langpatdate{1};
			}

			self::$TimeSep = ':';
			self::$IconDir = '../../config/images/';
		}
		
		self::$BaseIdCounter += 1;
		$this->Id = self::$BaseIdCounter;

		if ($owner) {
			assert($owner instanceof HtmlBase);
		}
		$this->Owner = $owner;
		
		$name = trim($name);
		assert(self::is_htmlbasename($name));
		$this->Name = $name;
	}

		
	/**
	 * Private function to check if the name is an allowed name (no special chars)
	 * May be disabled in a production environment.
	 *
	 * @param string $proposedname
	 * @return boolean
	 */
	private function is_htmlbasename($proposedname)
	{
		assert(is_string($proposedname));
		assert(!ctype_space($proposedname));
		return true;
	}

	/**
	 * This is for example the place to include a javascript-file which is called
	 * in the <body>-part.
	 *
	 * @return string The 'html' that should be inserted in to the <head>-part of the html.
	 */
	abstract public function drawHeader();


	/**
	 * For example a call of a javascript-function or a field or just text or etc...
	 *
	 * @return string The 'html' that should be inserted in to the <body>-part of the html.
	 */
	abstract public function drawBody();

}

