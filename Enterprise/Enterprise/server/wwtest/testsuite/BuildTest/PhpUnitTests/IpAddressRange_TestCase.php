<?php
/**
 * @since v9.5.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_PhpUnitTests_IpAddressRange_TestCase extends TestCase
{
	public function getDisplayName() { return 'WW_Utils_IpAddressRange class'; }
	public function getTestGoals()   { return 'Checks if IP addresses and ranges are correcly validated.'; }
	public function getPrio()        { return 100; }
	public function getTestMethods() { return 'Call the functions of the WW_Utils_IpAddressRange class and and validate the expected return values.';	}
	
	final public function runTest()
	{
		$this->test_isValidRange();
		$this->test_isIpInRange();
	}
	
	/**
	 * Tests WW_Utils_IpAddressRange::isValidRange().
	 *
	 * Note that this also tests WW_Utils_IpAddressRange::isValidIp() implicitly.
	 */
	private function test_isValidRange()
	{
		require_once BASEDIR.'/server/utils/IpAddressRange.class.php';
		$ranges = array(
			// range => boolean (whether or not the range should be valid)
			// IPv4:
			'127.0.0.1' => true,
			'255.255.255.255' => true,
			'256.0.0.1' => false, // 255 is max
			'-1.0.0.1' => false, // 0 is min
			'127.0.0.0.1' => false, // <> 4 digits
			'127.0.1' => false, // <> 4 digits
			'127.0..1' => false, // empty is not a digit
			'127.0.0.a' => false, // a is not a digit
			'127.0.0.1/16' => true,
			'127.0.0.1/a' => false, // a is not a digit
			'127.0.0.1/33' => false, // cidr > 32
			'127.0.0.1/255.0.0.0' => true,
			'127.0.0.1/255.255.255.255' => true,
			'127.0.0.1/255.0.0.0.0' => false, // <> 4 digits
			'127.0.0.1/255.0.0.a' => false, // a is not a digit
			'127.0.0.1/255.0.0.*' => false, // * not allowed in netmasks
			'127.0.0.*' => true,
			'127.0.*.*' => true,
			'127.*.*.1' => true,
			'127.*.*.*' => true,
			'*.*.*.*' => true,
			'127.0.0.0-127.0.0.100' => true,
			'127.0.0.100-127.0.0.0' => false, // bad range (min > max)
			// IPv6:
			'::1' => true,
			'::g' => false, // hex digit > f
			'2001:db8::-2001:db8::ffff' => true,
			'2001:db8::1-2001:db8::' => false, // min > max
			'0:0:0:0:0:0:0:0:0' => false, // > 8 digits
			'*::1' => false, // * not supported for IPv6
			'2001:db8::/128' => true,
			'2001:db8::/a' => false, // cidr is not a digit
			'2001:db8::/129' => false, // cidr > 128
		);
		foreach( $ranges as $range => $shouldBeValid ) {
			if( $shouldBeValid != WW_Utils_IpAddressRange::isValidRange( $range ) ) {
				$shouldBeValidOrNot = $shouldBeValid ? 'valid' : 'invalid';
				$this->setResult( 'ERROR', 
					"Expected range $range to be $shouldBeValidOrNot, ".
					"but WW_Utils_IpAddressRange::isValidRange() tells it is not." );
			}
		}
	}

	/**
	 * Tests WW_Utils_IpAddressRange::isIpInRange().
	 */
	private function test_isIpInRange()
	{
		require_once BASEDIR.'/server/utils/IpAddressRange.class.php';
		$tests = array(
			// IPv4
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.1', 'expected' => true ),
			array( 'ip' => '127.000.000.001', 'range' => '127.0.0.1', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.000.000.001', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.1/255.255.255.255', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/255.255.255.0', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/255.255.0.0', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/255.0.0.0', 'expected' => true ),
			array( 'ip' => '127.3.0.1', 'range' => '127.0.0.0/255.255.0.0', 'expected' => false ), // 2nd digit mismatch
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0-127.0.0.255', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.1-127.0.0.1', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.*', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.*.*', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '*.*.*.*', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.*.2', 'expected' => false ), // last digit mismatch
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/8', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/16', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/24', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.0/32', 'expected' => false ), // last digit mismatch
			array( 'ip' => '127.0.0.1', 'range' => '127.0.0.1/32', 'expected' => true ),
			array( 'ip' => '127.0.0.1', 'range' => '127.0.1.1/32', 'expected' => false ), // 3rd digit mismatch
			// dual-stack IPv4-IPv6
			array( 'ip' => '::ffff:10.0.0.1', 'range' => '10.0.0.*', 'expected' => true ),
			// IPv6
			array( 'ip' => '::1', 'range' => '::1', 'expected' => true ),
			array( 'ip' => '::ff', 'range' => '::FF', 'expected' => true ),
			array( 'ip' => '::FF', 'range' => '::ff', 'expected' => true ),
			array( 'ip' => '2001:db8::1', 'range' => '2001:db8::1', 'expected' => true ),
			array( 'ip' => '2001:db8::1', 'range' => '::/0', 'expected' => true ),
			array( 'ip' => '2001:0db8::0000:0001', 'range' => '2001:db8::1', 'expected' => true ),
			array( 'ip' => '2001:db8:0::', 'range' => '2001:db8:0::/48', 'expected' => true ),
			array( 'ip' => '2001:db8:1::', 'range' => '2001:db8:0::/48', 'expected' => false ), // 3rd digit mismatch
			array( 'ip' => '2001:db8::1', 'range' => '2001:db8::-2001:db9::', 'expected' => true ),
			array( 'ip' => '2001:db9::0', 'range' => '2001:db8::-2001:db9::', 'expected' => true ),
			array( 'ip' => '2001:db9::1', 'range' => '2001:db8::-2001:db9::', 'expected' => false ), // last digit mismatch
			array( 'ip' => '2001:db8::', 'range' => '2001:db8::-2001:db8::ffff', 'expected' => true ),
			array( 'ip' => '2001:db8::ffff', 'range' => '2001:db8::-2001:db8::ffff', 'expected' => true ),
			array( 'ip' => '2001:db9::', 'range' => '2001:db8::-2001:db8::ffff', 'expected' => false ), // last digit mismatch
		);
		foreach( $tests as $test ) {
			if( $test['expected'] != WW_Utils_IpAddressRange::isIpInRange( $test['ip'], $test['range'] ) ) {
				$shouldBeValidOrNot = $test['expected'] ? 'in range of' : 'out-of-range with';
				$this->setResult( 'ERROR', 
					"Expected {$test['ip']} to be $shouldBeValidOrNot {$test['range']}, ".
					"but WW_Utils_IpAddressRange::isIpInRange() tells it is not." );
			}
		}
	}
}