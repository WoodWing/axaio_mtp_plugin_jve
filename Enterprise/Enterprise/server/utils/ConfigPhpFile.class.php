<?php
/**
 * Utility class to update defines made in PHP config files.
 *
 * @since 10.5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_ConfigPhpFile
{
	/** @var string $outputContent File content of the original read config file. */
	private $inputContent = '';

	/** @var string $outputContent File content of the newly composed config file. */
	private $outputContent = '';

	/** @var array $setDefines Key-value pairs of all defines read from the original config file. */
	private $readDefines = array();

	/** @var array $setDefines Key-value pairs of defines to be (over)written in the config file. */
	private $setDefines = array();

	/**
	 * Constructor.
	 *
	 * @param string $configFilePath
	 */
	public function __construct( string $configFilePath )
	{
		$this->configFilePath = $configFilePath;
	}

	/**
	 * Read the config file, and overwrite the values for the provided defines.
	 *
	 * When a define could not be found in the config file, add it to the end of the file.
	 *
	 * @param array $defines Key-value pairs of defines to be (over)written in the config file.
	 * @return bool TRUE when file was updated, FALSE when parse/write failed.
	 */
	public function setDefineValues( array $defines )
	{
		$this->inputContent = file_get_contents( $this->configFilePath );
		if( !$this->inputContent ) {
			$this->inputContent = '<?php'.PHP_EOL;
		}
		$this->readDefines = array();
		$this->outputContent = '';
		$this->setDefines = $defines;
		$this->parseDefines();
		$this->addMissingDefines();
		$retVal = (bool)file_put_contents( $this->configFilePath, $this->outputContent );
		WW_Utils_ZendOpcache::clearOPcache(); // required to reflect changes of the config file to the next PHP run
		return $retVal;
	}

	/**
	 * Parse a PHP config file ($this->inputContent) and read or write define values.
	 */
	private function parseDefines()
	{
		$state = 0;
		$key = '';
		$value = '';
		$brackets = 0;
		$tokens = token_get_all( $this->inputContent );
		$token = reset( $tokens );
		while( $token ) {
			if( is_array( $token ) ) {
				switch( $state ) {
					case 0: // currently not parsing a define
						if( $token[0] == T_STRING && strtolower( $token[1] ) == 'define' ) {
							$state += 1;
							$key = '';
							$value = '';
							$brackets = 0;
						}
						break;
					case 1: // parsed: define
						break;
					case 2: // parsed: define(
						if( $token[0] == T_CONSTANT_ENCAPSED_STRING ) {
							$key = trim( $token[1], '\'"' );
							$state += 1;
						}
						break;
					case 3: // parsed: define( '...'
						break;
					case 4: // parsed: define( '...',
						$this->parseDefineValueToken( $key, $value, $token );
						break;
				}
			} else {
				switch( $state ) {
					case 0: // currently not parsing a define
						break;
					case 1: // parsed: define
						if( $token == '(' ) {
							$state += 1;
						}
						break;
					case 2: // parsed: define(
						break;
					case 3: // parsed: define( '...'
						if( $token == ',' ) {
							$state += 1;
							$this->parseNonDefineValueToken( $token );
						}
						break;
					case 4: // parsed: define( '...',
						if( $token == '(' ) {
							$brackets += 1;
						} else if( $token == ')' ) {
							$brackets -= 1;
						}
						if( $brackets == -1 ) {
							$this->parsedDefine( $key, $value );
							$state = 0;
						} else {
							$this->parseDefineValueToken( $key, $value, $token );
						}
						break;
				}
			}
			if( $state !== 4 ) {
				$this->parseNonDefineValueToken( $token );
			}
			$token = next( $tokens );
		}
	}

	/**
	 * Called when a whole define statement was parsed.
	 *
	 * @param string $key
	 * @param string $value
	 */
	private function parsedDefine( string $key, string $value )
	{
		if( array_key_exists( $key, $this->setDefines ) ) {
			$this->outputContent .= $this->setDefines[ $key ];
		} else {
			$this->outputContent .= $value;
		}
		$this->readDefines[ $key ] = $value;
	}

	/**
	 * Called when tokens were parsed that are part of the value in the define.
	 *
	 * @param string $key The name of the define.
	 * @param string $value Read/write. The (part of the) define value, to be enriched with earlier findings.
	 * @param mixed $token The PHP token being parsed. See token_get_all() for more info.
	 */
	private function parseDefineValueToken( string $key, string &$value, $token )
	{
		if( is_array( $token ) ) {
			// $comment = $token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT;
			$value .= $token[1]; // record all
		} else {
			$value .= $token; // record all
		}
	}

	/**
	 * Called when tokens were parsed that are NOT part of the value in the define.
	 *
	 * @param mixed $token
	 */
	private function parseNonDefineValueToken( $token )
	{
		if( is_array( $token ) ) {
			$this->outputContent .= $token[1];
		} else {
			$this->outputContent .= $token;
		}
	}

	/**
	 * For the defines that are not found in the config file, add those to the end of file.
	 */
	private function addMissingDefines()
	{
		$addMissingDefines = array_diff_key( $this->setDefines, $this->readDefines );
		if( $addMissingDefines ) {
			$this->outputContent .= PHP_EOL;
			foreach( $addMissingDefines as $key => $value ) {
				$this->outputContent .= "define( '{$key}', {$value} );".PHP_EOL;
			}
		}
	}
}