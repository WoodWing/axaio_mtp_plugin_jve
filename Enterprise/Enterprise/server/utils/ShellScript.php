<?php
/**
 * Utils class that runs commands on the shell.
 *
 * It accepts template files that are preferably loaded from the Enterprise/config/shellscript folder by caller.
 * It fills in the command parameters on-the-fly before it executes the command.
 *
 * @package Enterprise
 * @subpackage Utils
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
class WW_Utils_ShellScript
{
	private $cmdName;
	private $cmdLine;
	
	/**
	 * Initialization of the command template.
	 *
	 * @param string $cmdName Logical name of the command, use for logging and profiling.
	 * @param string $cmdFile Full file path to the template file of the shell command.
	 * @return bool Whether or not template file could be loaded.
	 */
	public function init( $cmdName, $cmdFile )
	{
		$this->cmdName = null; // clean previous runs
		$this->cmdLine = null;

		if( !file_exists($cmdFile) ) {
			LogHandler::Log( 'ShellScript', 'ERROR', 'Could not find template "'.$cmdFile.'".' );
			return false;
		}
		$cmdLine = trim( file_get_contents( $cmdFile ) );
		if( empty($cmdLine) ) {
			LogHandler::Log( 'ShellScript', 'ERROR', 'The template file "'.$cmdFile.'" command seems to be empty.' );
			return false;
		}
		$this->cmdName = $cmdName;
		$this->cmdLine = $cmdLine;
		return true;
	}

	/**
	 * Fill in given parameters in the command template file.
	 *
	 * @param array $params
	 * @return string Command line with params filled in.
	 */
	public function buildCmd( array $params )
	{
		if( !$this->cmdLine || !$this->cmdName ) { return ''; } // avoid run cmd when caller did not check init()
		$cmdLine = $this->cmdLine; // copy (to avoid infecting other runs)
		foreach( $params as $key => $value ) {
			$value = escapeshellarg( $value ); // quote and escape
			$cmdLine = str_replace( '%'.$key.'%', $value, $cmdLine );
		}
		return $cmdLine;
	}
	
	/**
	 * Runs the command using PHP's shell_exec() function.
	 *
	 * @param array $params
	 * @return string Return value of the command itself. 
	 */
	public function shell_exec( array $params )
	{
		if( !$this->cmdLine || !$this->cmdName ) { return ''; } // avoid run cmd when caller did not check init()
		$cmdLine = $this->buildCmd( $params ); // copy (to avoid infecting other runs)

		// Run the command (with profiling and logging)
		PerformanceProfiler::startProfile( $this->cmdName.' command', 4 );
		LogHandler::Log( __CLASS__, 'INFO', 'Running command: '.$cmdLine );
		$retVal = shell_exec( $cmdLine );
		$retToLog = (strlen($retVal) > 500) ? substr( 0, 500, $retVal ).'...' : $retVal;
		LogHandler::Log( __CLASS__, 'INFO', 'Command returns: '.$retToLog );
		PerformanceProfiler::stopProfile( $this->cmdName.' command', 4 );
		return $retVal;
	}
}
