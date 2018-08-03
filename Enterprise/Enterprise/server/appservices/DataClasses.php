<?php
/**
 * @since v7.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class ObjectIcon
{
	public $ObjectType;
	public $Format;
	public $Attachments;

	/**
	 * @param string               $ObjectType                   
	 * @param string               $Format                 
	 * @param array                $Attachments                   
	 */
	public function __construct( $ObjectType=null, $Format=null, $Attachments=null )
	{
		$this->ObjectType           = $ObjectType;
		$this->Format               = $Format;
		$this->Attachments          = $Attachments;
	}
}

class PubChannelIcon
{
	public $Id;
	public $Name;
	public $Attachments;

	/**
	 * @param string               $Id                   
	 * @param array                $Attachments
	 */
	public function __construct( $Id=null, $Attachments=null )
	{
		$this->Id                   = $Id;
		$this->Attachments          = $Attachments;
	}
}

class Coordinates
{
	public $Left;
	public $Top;
	public $Width;
	public $Height;

	/**
	 * @param string	$Left
	 * @param string	$Top
	 * @param string	$Width
	 * @param string	$Height
	 */
	public function __construct( $Left=null, $Top=null, $Width=null, $Height=null )
	{
		$this->Left 	= $Left;
		$this->Top		= $Top;
		$this->Width	= $Width;
		$this->Height	= $Height;
	}
}