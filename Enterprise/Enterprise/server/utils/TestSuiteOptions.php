<?php
/**
 * Provides the options configured for the TESTSUITE define in the configserver.php file.
 *
 * @since 	    v10.0.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class WW_Utils_TestSuiteOptions
{
	/**
	 * Returns a list of key-values configured in the TESTSUITE option.
	 *
	 * @return array List of key-values.
	 */
	static private function getOptions()
	{
		static $options = null;
		if( is_null( $options ) ) {
			if( defined('TESTSUITE') ) {
				$options = unserialize( TESTSUITE );
			} else {
				LogHandler::Log( 'TestSuite', 'ERROR', 'No TESTSUITE option defined. Please check your configserver.php file.' );
				$options = array();
			}
		}
		return $options;
	}

	/**
	 * Returns the value configured for a given entry (key) in the TESTSUITE option.
	 *
	 * @param string $key Entry to lookup.
	 * @return string|null Value when found, else NULL.
	 */
	static private function getOptionValue( $key )
	{
		$options = self::getOptions();
		return array_key_exists( $key, $options ) ? $options[$key] : null;
	}
	
	/**
	 * Returns the User configured in the TESTSUITE option.
	 *
	 * @return string|null User. NULL when not defined.
	 */
	static public function getUser()
	{
		return self::getOptionValue('User');
	}

	/**
	 * Returns the Password configured in the TESTSUITE option.
	 *
	 * @return string|null Password. NULL when not defined.
	 */
	static public function getPassword()
	{
		return self::getOptionValue('Password');
	}

	/**
	 * Returns the Brand configured in the TESTSUITE option.
	 *
	 * @return string|null Brand. NULL when not defined.
	 */
	static public function getBrand()
	{
		return self::getOptionValue('Brand');
	}

	/**
	 * Returns the Issue configured in the TESTSUITE option.
	 *
	 * @return string|null Issue. NULL when not defined.
	 */
	static public function getIssue()
	{
		return self::getOptionValue('Issue');
	}

	/**
	 * Returns the SoapUrlDebugParams configured in the TESTSUITE option.
	 *
	 * @return string|null SoapUrlDebugParams. NULL when not defined.
	 */
	static public function getHttpEntryPointDebugParams()
	{
		return self::getOptionValue('SoapUrlDebugParams');
	}
}
