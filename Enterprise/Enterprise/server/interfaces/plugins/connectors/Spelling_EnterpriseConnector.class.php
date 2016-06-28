<?php
/**
 * Interface for spelling checker implementations in Enterprise Server
 *
 * @package Enterprise
 * @subpackage Core
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

require_once BASEDIR.'/server/interfaces/plugins/DefaultConnector.class.php';

abstract class Spelling_EnterpriseConnector extends DefaultConnector
{
	protected $config = null;
	
	/**
	 * @param $config Configuration name/value array.
	 */
	public function setConfiguration( $config ) 
	{
		$this->config = $config;
	}

	/**
	 * Check spelling for words that is passed in.
	 * @param  array $dictionaries Language of the dictionary.
	 * @param string $stringToCheck The string to get the spelling checked.
	 * @return array Misspelled words found in sentences passed in.
	 */
	abstract public function checkSpelling( array $dictionaries, array $wordsToCheck );


	/**
	 * Returns suggestions for the passed in $word.
	 *
	 * @param  array $dictionaries Language of the dictionary.
	 * @param string $wordForSuggestions The corresponding word to get suggestions for.
	 * @return array Suggestions of the word passed in.
	 */
	abstract public function getSuggestions( array $dictionaries, $wordForSuggestions );

	/**
	 * Returns the version of the spelling engine.
	 *
	 * @return string ... 
	 * @todo: returned format ???
	 */
	abstract public function getEngineVersion();

	/**
	 * Returns the installed dictionaries.
	 *
	 * @return array ... 
	 * @todo: returned format ???
	 */
	abstract public function getInstalledDictionaries();


	// ===================================================================================

	// Generic methods that can be overruled by a connector implementation:
	public function getPrio()      { return self::PRIO_DEFAULT; }

	// Generic methods that can -not- be overruled by a connector implementation:
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }
	final public function getInterfaceVersion() { return 1; }
	final public function getRunModesLimited()  { return array( self::RUNMODE_SYNCHRON ); } // disallow background!
}
