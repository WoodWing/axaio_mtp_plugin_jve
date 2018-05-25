<?php

/**
 * @since 		v6.0
 * @copyright	WoodWing Software bv. All Rights Reserved.
 */
 
abstract class EnterpriseConnector
{
	abstract public function getInterfaceVersion();
	abstract public function getConnectorType();

	// - - - - - PRIOS - - - - - 
	
	const PRIO_VERYLOW  = 100;
	const PRIO_LOW      = 300;
	const PRIO_DEFAULT  = 500;
	const PRIO_HIGH     = 700;
	const PRIO_VERYHIGH = 900;
	
	final public function getPrios() 
	{ 
		return array( self::PRIO_VERYLOW, self::PRIO_LOW, self::PRIO_DEFAULT, self::PRIO_HIGH, self::PRIO_VERYHIGH ); 
	}
	
	final public function getValidPrio() 
	{
		$prio = $this->getPrio();
		if( !in_array($prio,$this->getPrios() )) {
			throw new BizException( '', 'Server', 'Configuration error', 
				'Unknown prio "'.$prio.'" configured for connector "'.get_class($this).'".' );
		}
		return $prio;
	}
	
	abstract public function getPrio();


	// - - - - - RUN MODES - - - - - 
	
	abstract public function getRunMode();
	abstract public function getRunModes();
	public function getRunModesLimited() { return null; } // default no limitations
	
	final public function getValidRunMode() 
	{
		$limModes = $this->getRunModesLimited(); // allow subselection
		$allModes = $this->getRunModes();

		if( count($limModes) == 0 && count($allModes) == 0 ) {
			throw new BizException( '', 'Server', 'Configuration error', 
				'No run modes configured for connector "'.get_class($this).'".' );
		}
		$mode = $this->getRunMode();
		if( !in_array($mode,$allModes)) {
			throw new BizException( '', 'Server', 'Configuration error', 
				'Unknown mode "'.$mode.'" configured for connector "'.get_class($this).'".' );
		}
		if( $limModes && !in_array($mode,$limModes)) {
			throw new BizException( '', 'Server', 'Configuration error', 
				'Mode "'.$mode.'" is not allowed for connector "'.get_class($this).'".' );
		}
		return $mode;
	}
}
