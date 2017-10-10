<?php
/**
 * Data class of an application feature access (as listed on the Profile Maintenance page).
 *
 * It defines a server feature and/or client application feature for which access can be given to users.
 *
 * Since 10.2 the class SysFeatureProfile is renamed to ProfileFeatureAccess and moved
 * from server/bizclasses/BizAccessFeatureProfiles.class.php into this file.
 *
 * @package    Enterprise
 * @subpackage DataClasses
 * @since      10.2.0
 * @copyright  WoodWing Software bv. All Rights Reserved.
 */

class ProfileFeatureAccess
{
	public $Id;
	public $Name;
	public $Display;
	public $Flag;
	public $Default;

	/**
	 * @param integer              $Id
	 * @param string               $Name
	 * @param string               $Flag
	 * @param string               $Display
	 * @param string               $Default
	 */
	public function __construct( $Id=null, $Name=null, $Flag=null, $Display=null, $Default=null )
	{
		$this->Id                   = $Id;
		$this->Name                 = $Name;
		$this->Flag                 = $Flag;
		$this->Display              = $Display;
		$this->Default              = $Default;
	}
}
