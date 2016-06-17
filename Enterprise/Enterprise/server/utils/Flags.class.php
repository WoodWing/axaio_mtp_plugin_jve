<?php
    
/**
 * @package     SCEnterprise
 * @subpackage  Utils
 * @since       v5.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 * @version     v1.0
**/
    
/**
 * module Flags
 * Accepts an array of flagnames and keeps track which flags are selected.
 * The selection can be stored in an integer, each bit of the integer representing a selected/unselected flag.
 * This is handy when for example you need to store a selection to a cookie.
 * For this reason (maximum size of an integer) only 32 flags can be used in one instance of Flags.
 * If you need more flags than 32, create a new instance of flags and manage that separately. 
**/
    
class Flags
{
	private $Names;
	private $Bits;
       
	/**
 	 *	Constructs an instance of the Flags-class.
 	 *  As changing the order of names or adding names would render the value of possibly set bits useless
 	 *  the constructor is the only place where the flagnames can be instantiated. No altering is possible afterwards.
 	 *  In this version (1.0) Flags may not consist of more than 32 names.
 	 *  1. Initializes the array of flagnames
 	 *  2. Sets the initially selected flags to 0.
	**/

	public function __construct(array $flagnames)   
	{
		assert(count($flagnames) <= 32);
		$bitvalue = 0x01;
		$this->Names = array();
		foreach ($flagnames as $flagname) {
			$this->Names[$flagname] = $bitvalue;
			$bitvalue *= 0x02;
        }
		$this->Bits = 0x00;
	}
       
	/**
	 *	@return array of ALL flagnames (selected or not).
	**/
	 
	public function listAllNames()
	{
		return array_keys($this->Names);
	}

	/**
     *	Clears all bits, effectively unselecting all flags.
	**/

	public function clear()
	{
		$this->Bits = 0x00;
	}
       
	/**
	 *	@return selected flags as an int (32 bits).
	**/

	public function getBits()
	{
		return $this->Bits;   
	}
               
	/**
	 *	@return selected flags as an array of flagnames.
	**/

	public function getFlags()
	{
		return self::bits2flags($this->Bits);
	}

	/**
	 *	Adds (selects) the flags in the array $flagnames.
	 *  If flags where allready selected they stay selected.
	 *	@param $flagnames to select as an array of names.
	**/

	public function addFlags($flagnames)
	{
		$this->Bits |= self::flags2bits($flagnames);
	}
       
	/**
	 *	Removes (unselects) the flags in the array $flagnames.
	 *  If flags where allready not selected they stay unselected.
	 *	@param $flagnames to remove as an array of flagnames.
	**/

	public function removeFlags($flagnames)
	{
    	$this->Bits = $this->Bits &~ self::flags2bits($flagnames);
	}
       
	/**
	 *	Adds (selects) the bits of the int $bits.
	 *  If flags where allready selected they stay selected.
	 *	@param $bits to select as an integer.
	**/

	public function addBits($bits)
	{
		$this->Bits |= $bits;
	}
       
	/**
	 *	Removes (unselects) the bits of the int $bits.
	 *  If flags where allready not selected they stay unselected.
	 *	@param $bits to remove as an integer.
	**/

	public function removeBits($bits)
	{
		$this->Bits = $this->Bits &~ $bits;
	}
       
	/**
	 *	Checks if the flag $flagname is selected or not.
	 *  @param $flagname name of the flag to check.
	 *	@return boolean true if flag is selected, false if not.
	**/
		
	public function hasFlag($flagname)
	{
		return $this->Bits & $this->Names[$flagname];    
	}
    
	/**
	 *	Checks if the flag corresponding to bit is selected or not.
	 *  @param $bit bit to check.
	 *	@return boolean true if flag is selected, false if not.
	**/    
       
	public function hasBit($bit)
	{
		return $this->Bits & $bit;
	}

	/**
	 *	Selects/unselects the flag $flagname, depending on $value.
	 *  @param $flagname name of the flag to set.
	 *  @param $value either select/unselect.
	**/    
       
	public function setFlag($flagname, $value)
	{
		if ($value) {
			self::addFlags(array($flagname));      
		}
		else {
			self::removeFlags(array($flagname));   
		}
	}
       
	/**
	 *	@param $bits Sets the complete selection to $bits
	**/

	public function setBits($bits)
	{
		$this->Bits = $bits;   
	}

	/**
	 *	Converts the bitset to a flagset with the same flags being selected as the bits given.
	 *	@param $bits 
	 *	@result $array of flagnames
	**/

	public function bits2flags($bits)
	{
		$result = array();
		foreach ($this->Names as $flagname => $bitvalue) {
			if ($bits & $bitvalue) {
        		$result[] = $flagname;
        	}
        }
		return $result;
	}
       
	/**
	 *	Converts the flagset to a bitset with the same bits being set as the flags given.
	 *	@param $flagnames
	 *	@result $bits
	**/

	public function flags2bits($flagnames)
	{
		$result = 0x00;
		foreach ($flagnames as $flagname) {
			$result |= $this->Names[$flagname];
		}
		return $result;
	}
}
   
?>