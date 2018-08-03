<?php
    
/**
 * Flags module
 *
 * Accepts an array of flag names and keeps track which flags are selected.
 * The selection can be stored in an integer, each bit of the integer representing a selected/deselected flag.
 * This is handy when for example you need to store a selection in a cookie.
 * For this reason (maximum size of an integer) only 32 flags can be used in one instance of Flags.
 * If you need more flags than 32, create a new instance of Flags and manage that separately.

 * @since       v5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */
    
class Flags
{
	/** @var string[] Names of all registered flags. */
	private $names;

	/** @var int Bit-set of all registered flags. */
	private $bits;

	/**
	 * Constructs an instance of the Flags class.
	 *
	 * As changing the order of names or adding names would render the value of possibly set bits useless
	 * the constructor is the only place where the flag names can be instantiated. No altering is possible afterwards.
	 * In this version (1.0) Flags may not consist of more than 32 names.
	 * 1. Initializes the array of flag names
	 * 2. Sets the initially selected flags to 0.
	 *
	 * @param string[] $flagNames
	 */
	public function __construct( array $flagNames )
	{
		assert( count( $flagNames ) <= 32 );
		$bitvalue = 0x01;
		$this->names = array();
		foreach( $flagNames as $flagName ) {
			$this->names[ $flagName ] = $bitvalue;
			$bitvalue *= 0x02;
		}
		$this->bits = 0x00;
	}

	/**
	 * Returns all registered flag names (raised and lowered).
	 *
	 * @return string[] flag names
	 */
	public function listAllNames()
	{
		return array_keys( $this->names );
	}

	/**
	 * Lowers all flags.
	 */
	public function clear()
	{
		$this->bits = 0x00;
	}

	/**
	 * Returns the raised flags as a bit-set (32 bits).
	 * 
	 * @return integer bits
	 */
	public function getBits()
	{
		return $this->bits;
	}

	/**
	 * Returns the raised flags as an array of flag names.
	 * 
	 * @return string[] flag names
	 */
	public function getFlags()
	{
		return self::bits2flags( $this->bits );
	}

	/**
	 * Raises flags by a given list of flag names.
	 *
	 * If flags where already selected they stay selected.
	 *
	 * @param string[] $flagNames to select as an array of names.
	 */
	public function addFlags( $flagNames )
	{
		$this->bits |= self::flags2bits( $flagNames );
	}

	/**
	 * Lowers flags by a given list of flag names.
	 *
	 * If flags where already not selected they stay unselected.
	 *
	 * @param string[] $flagNames to remove as an array of flag names.
	 */
	public function removeFlags( $flagNames )
	{
		$this->bits = $this->bits & ~self::flags2bits( $flagNames );
	}

	/**
	 * Raises flags by a given list of bits.
	 *
	 * If flags where selected they stay selected.
	 *
	 * @param $bits to select as an integer.
	 */
	public function addBits( $bits )
	{
		$this->bits |= $bits;
	}

	/**
	 * Takes flags down by a given list of bits.
	 *
	 * If bits where not selected they stay unselected.
	 *
	 * @param $bits to remove as an integer.
	 */
	public function removeBits( $bits )
	{
		$this->bits = $this->bits & ~$bits;
	}

	/**
	 * Checks if a given flag is raised or not.
	 *
	 * @param string $flagName name of the flag to check.
	 * @return boolean true if flag is selected, false if not.
	 */
	public function hasFlag( $flagName )
	{
		return $this->bits & $this->names[ $flagName ];
	}

	/**
	 * Checks whether or not a flag is raised by a given bit.
	 *
	 * @param $bit bit to check.
	 * @return boolean true if flag is selected, false if not.
	 */
	public function hasBit( $bit )
	{
		return $this->bits & $bit;
	}

	/**
	 * Raises or lowers a flag by a given name and value.
	 *
	 * @param string $flagName name of the flag to set.
	 * @param boolean $value either select/deselect.
	 */
	public function setFlag( $flagName, $value )
	{
		if( $value ) {
			self::addFlags( array( $flagName ) );
		} else {
			self::removeFlags( array( $flagName ) );
		}
	}

	/**
	 * Raises or lowers all registered flag by a given bit-set.
	 *
	 * @param integer $bits
	 */
	public function setBits( $bits )
	{
		$this->bits = $bits;
	}

	/**
	 * Converts the bit-set to a flag-set with the same flags being selected as the bits given.
	 *
	 * @param integer $bits
	 * @return string[] flag names
	 */
	public function bits2flags( $bits )
	{
		$result = array();
		foreach( $this->names as $flagName => $bitvalue ) {
			if( $bits & $bitvalue ) {
				$result[] = $flagName;
			}
		}
		return $result;
	}

	/**
	 * Converts the flag-set to a bit-set with the same bits being set as the flags given.
	 *
	 * @param string[] $flagNames
	 * @return integer $bits
	 */
	public function flags2bits( $flagNames )
	{
		$result = 0x00;
		foreach( $flagNames as $flagName ) {
			$result |= $this->names[ $flagName ];
		}
		return $result;
	}
}