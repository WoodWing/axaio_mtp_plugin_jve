<?php
/**
 * Handles string conversions of Enterprise license strings.
 *
 * @package Enterprise
 * @subpackage License
 * @since v5.0
 * @copyright WoodWing Software bv. All Rights Reserved.

 * ===================== IMPORTANT =================
 * If you change this file, you may also have to change this file in the SmartReg package!!!
 * ===================== IMPORTANT =================
*/

class LicenseString
{
	/** @var string $mWWLError */
	private $mWWLError;

	/**
	 * @var integer $mWWTimeOffset date time
	 * Convert a unix timestamp into a string.
	 * To avoid that people recognize the number (and shorten the data that needs to be transferred), 
	 * count the number of seconds since 2000-1-1
	 */
	private $mWWTimeOffset;
	
	/** @var string $mWWLTranstab A translation table to obfuscate a string in a simple way using strtr(). */
	private $mWWLTranstab = "1owu4pr5sig2k8h9mf3nq7vx6jatey0zblcd-A,BCDEFGH:I\\J/KLMNOPQRSTUVWXYZ";
	
	/** @var integer mOpenSSLLevel The level of security (openSSL key) */
	private $mOpenSSLLevel = 1024;
	
	/** @var string[] $mLicenseFields The fields that are encrypted in the license string */
	private $mLicenseFields = Array(
					//For license validation checks:
					'serialchecksum',  	//a checksum of the associated serial number
					'productcode', 		//the SmartReg productcode
					'key1', 			//The 'hardware ID', 'hostid' of the particular SCE installation

					//For real time usage checks:
					'maxusage',			//The maximum number of connections (SCE), 
										//or the maximum number of concurrent users (client applications)
					'starttime', 		//If specified, license start date
					'expiretime', 		//If specified, license end date
					'renewtime', 		//If specified, renew before this date (same effect as 'expiretime' if exceeded)
					'expiremaxobj', 	//If specified, use until this number of smart_objects have been created
										//(the current number of objects is specified at the moment of registration)
					
					//In case the 'hardware ID' changes:
					'errorstart',		//The date/time the conflict started 		
					'errormaxobj',		//The number of smart_objects at the moment the conflict started
					
					'testflags'		//Which hack tests to do?
					
					//Warning: when adding a new field here, 
					//be sure to handle backwards compatibility in the method makeLicenseKey below!

					);
	
	/** @var string $mReclaimSeparator Glue for the two installation codes when reclaiming a license */
	private $mReclaimSeparator = '#RECLAIM#';

	/** @var string $mProductInfoSeparator Separator for the license information (product name, serial and license) of a certain product */
	private $mProductInfoSeparator = '||';

	/**
	 * Construct the License String.
	 */
	public function __construct()
	{
		$this->mWWLError = '';
		$this->mWWTimeOffset = mktime( 0, 0, 0, 1, 1, 2000 );
	}

	/**
	 * Convert unix timestamp into a string.
	 *
	 * The 'woodwing time' starts in 2000, instead of in 1970.
	 * Return a hexadecimal value.
	 *
	 * @param integer $unixtime
	 * @return string converted timestamp
	 */
	public function unix2WWTimeStr( $unixtime )
	{
		$mytime = $unixtime - $this->mWWTimeOffset;
		return dechex( $mytime );
	}

	/**
	 * Convert 'woodwing timestamp string' back to a unix timestamp
	 *
	 * The 'woodwing time' starts in 2000, instead of in 1970.
	 * Return a hexadecimal value.
	 *
	 * @param string $str
	 * @return int unix timestamp
	 */
	public function WWTimeStr2Unix( $str )
	{
		$mytime = hexdec( $str );
		$unixtime = $mytime + $this->mWWTimeOffset;

		//unix2WWTimeStr() was called with 0 as input parameter? Return 0 here again
		//$unixtime can be 0x100000000, return 0 then.
		if ( $unixtime && (intval($unixtime) == 0 ))
			$unixtime = 0;

		return $unixtime;
	}

	/**
	 * Return a password for conversions
	 *
	 * @return string password
	 */
	private function hashcrypt_password()
	{
		return "S3(r3tP433w0r6!";
	}

	/**
	 * Get a private key for openSSL string conversions
	 *
	 * If the privatey is stored in the source code below, the returned value is a string (that doesn't need to be freed)
	 *
	 * @return string|bool the private key, false on error
	 */
	private function getPrivKey()
	{
		if( !extension_loaded('openssl') )
		{
			$this->mWWLError = 'Configuration error: OpenSSL module not installed or enabled for PHP.';
			return false;
		}

		//To obfuscate the private key, we build an array of strings instead of one long string.
		//Moveover, we add some wrong lines between them in case some concatenates the strings by hand
		$s = Array();
		if ( $this->mOpenSSLLevel == 1024 )
		{
			$s[0] = "-----BEGIN RSA PRIVATE KEY-----";
			$s[1] = "MIIEowIBAAKCAQEApNvzFboYxqnRJtcm8vzOoS0kS9oyrOs+ZgjsjLOL3mJT7ucO"; //Wrong
			$s[2] = "X7CYO4a48U6hsAEK5MekLHKI3hlO0D1IsskAvlCntBEXqtsHKK8mtzflaOU45pjG"; //Wrong
			$s[3] = "MIICXAIBAAKBgQDaduQaSQpDvD0Rynr0pRdV8ouakKWZ7QnDKpK+gr9duH4NYvch";
			$s[4] = "IsCT3iHqJ34hTtSQvMECKI9iDSgdJUj9zFrVGVYc8hN1xS5rjpgg9/MPveUl5hgN";
			$s[5] = "UpAVjcqnJp6BUpRaHnyoHetMddvqK9Y5an7bkpNoYKzqxRJtsws/ycI79wIDAQAB";
			$s[6] = "AoGAS/ndCmcscrIcavuIF1zy6KkZc/+qGAYfxwgfULIX63gmHnG0kImNf99Y3B4v";
			$s[7] = "91V7B3sdhKqmcuswcDJvWBSh4aYTiOVs6MufXfgrLUCylCD514jL1d7zgC1I5LZt";
			$s[8] = "4k/p0ULMp0qXr7PiSsXo9wXKayYPMZbbupHVpzndFzCRZ4ECQQDwFGiNoe8J+Sd4";
			$s[9] = "VzugZuprpiz86Sa++b0B9PgTcrRlhpgPSSCjZU4jA4osjX8ifPE3XbxxzAivF+6r";
			$s[10] = "+ExboYMv/IxJPiHHCib1214mQkMokYgBDQzeRDnjDV9HFPEjqa+4SSngBltrqKia"; //Wrong!!
			$s[11] = "/pu23MyiPuNz+IZ846Q06zNKCUanCD01gLFCw0cInjbWCVZ1AJFsLqIb33xwnzv1"; //Wrong!!
			$s[12] = "a1+WfAJ/AkEA6POKmTawxyg4DSpH9eAyZnyEm4J7WM5WBXziNeqOgDEO7KFPU7eR";
			$s[13] = "d8DwCMRQcd0+XcoHKK+gP6tSMm/4fNwaiQJADj8XyTfPyIa5eeGrTyRsSsEMsGFh";
			$s[14] = "hDmrpp8TzYuj+tZW1algP3H2hgtiuqwW3EQPyFpOoW4bIdDIv/FaNcLcHwJBAIJD";
			$s[15] = "BUKtoWaMZ+N8vCaSirxibqii6yR6pQGGltv9YFfwlt7dSQWQRajIW7EbTbyOMfyy";
			$s[16] = "JxSqOJrBy0Hm+PWhArkCQChzLJ/GAf7yUOYHMk3X7xnn1qKy8plvqaRtxCjIr7xQ";
			$s[17] = "hccuTCHcGQbDG5UgmZ3sja/ui5gdbBhtcZcDuplWzBo=";
			$s[18] = "-----END RSA PRIVATE KEY-----";

			unset( $s[1] ); //remove the bad one
			unset( $s[2] ); //remove the bad one
			unset( $s[10] ); //remove the bad one
			unset( $s[11] ); //remove the bad one
		}
		else if ( $this->mOpenSSLLevel == 2048 )
		{
			$s[0] = "-----BEGIN RSA PRIVATE KEY-----";
			$s[1] = "MIICXAIBAAKBgQDZ3qOYLAWMIj5fSX+RKTw5uQLZ+Ck0BBchiJlztp1T4Xp2vChV"; //Wrong!!
			$s[2] = "XIGfMA0GCSqGSiQKBgQDZ3qOYIb3DQEBAQUAA4GNADCBLAWMIj5fSX+RKTw5uQLZ"; //WRONG!!

			$s[3] = "MIIEowIBAAKCAQEApNvzFboYxqnRJtcm8vzOoS0kS9oyrOs+ZgjsjLOL3mJT7ucO";
			$s[4] = "X7CYO4a48U6hsAEK5MekLHKI3hlO0D1IsskAvlCntBEXqtsHKK8mtzflaOU45pjG";
			$s[5] = "s1ukkQnUN+ZHoBMPO43FofG7EB9mBwdJ+cif4WnzQNHP89fY3Iq9PKu254ryo4AP";
			$s[6] = "ySLW5Mly4m16XpOt8JL4Us4/xwM6NPBlatKUriYVn61F1SNcZtuLVJcpe1oZsj72";
			$s[7] = "r/dJLVJniBFsUNhFZK1s9hEB4dRNsKnQ79LMlHR5A+8BShBNGLp781627l65zOXB";
			$s[8] = "Wg9Tptgq8Ys3rOTMXi9kDqd9s+X5KnxGkGnIKwIDAQABAoIBAQCYBcOJW4yRVfNq";
			$s[9] = "j6h5FastccUwtUtZraxixwPrU349WgrwGN1mXCBlc/yDePEcrjlvcrHv0sZnmIrW";
			$s[10] = "lztp1T4Xp+Ck0BBchiJaceXH+4vj+82vChVPyWYPRub8TMGulk9lMxEiasmN1ekE"; //WRONG!!
			$s[11] = "hoFpF9zSorBd4/LozfHAKlwzI55dtKV5qwJAB70y7MUZOKLnpL1cDNrUAnzPSxaU";//Wrong!!
			$s[12] = "+ExboYMv/IxJPiHHCib1214mQkMokYgBDQzeRDnjDV9HFPEjqa+4SSngBltrqKia";
			$s[13] = "/pu23MyiPuNz+IZ846Q06zNKCUanCD01gLFCw0cInjbWCVZ1AJFsLqIb33xwnzv1";
			$s[14] = "KJWHwK3AnvcP0qP0xvg6iWtZQ07BNy3q2imbiBA+kpRby5EUyX5ctHLReDXbSbzA";
			$s[15] = "EtjkEnbNvS90F/ty8KEnR2IGHxrY0x+WKirjSz6w3082NpNgMoTQJ4ZDKwHnVc0a";
			$s[16] = "zXUk3EAhAoGBANYhv4JutSa1aRyNwCDf1ZLolIrLpSzl9M/oVnZ4it7zEj90sj5J";
			$s[17] = "ZXgszOPBdVFO5F4rdctzOO2JyF9QPQc1VsbaqF9uB3fZTaddBbfGfP1Egx1slR/j";
			$s[18] = "SJJTPlOMSovG+VBKgaohTwyXamF031rgUO6cZYWdMk2x6WaHvamEqFmJAoGBAMUX";
			$s[19] = "4sR93WJ2gJwOAKc3XHjL9e3cR+b30EmHFOKm65qUsAUtDIz1LlY4aByJeKYk3FmF";
			$s[20] = "jG5JUUf/d0eNtg+w0sUx+YhrOll7as1KXaYqmarQVjiSIFCX4BpwL6QlxXwJcGg5";
			$s[21] = "f5yopj43BvhJ5Aiwhu8A0+GRAsJrmbgSN6u8MEsTAoGATi1+FOnEW4CKArHB/n6a";
			$s[22] = "JAukB9R31p+SNMHXp2M1GFbYR7CcTt9PG4QHvfNomKnb+IGXfRLxKtBOBnZuAY64";
			$s[23] = "wtsb89NhCKGel0cACmt+QJ1d3UVCYKUDXuDjdW9X3BZl+alqE6obiTZS0Yfw7+XL";
			$s[24] = "xoH1EsYkknctBpDybsyPBjkCgYAfKYmnO6dXPBlVkzkzNyOdzdHSi/OqZyA9n+gD";
			$s[25] = "dhTmWgACsX68gr+SzlRXvryeuFFGrkDn5xu84H2BiLxyeqi4mRA1K48MOuFGGC36";
			$s[26] = "ad3mDg6z3xbQ51J9m6NMttQkqnFlaUHuCRRP3e9yLmypmw+almzpyz2+c7jYS7kV";
			$s[27] = "pCmWlQKBgEMac7JCwF+3gtxX3VTWhIkWsMI9bY8JyrlmCaY+uc1SeaI24j7ZiTzc";
			$s[28] = "nA+CN4+ZFuh8v3gAn965st/sADz/v/2PR792sEW7ifFrz0+ecvfk1S6hvcoW7NQO";
			$s[29] = "H3ya9RpUK9gUs1yyBnVMJrH98VBIDsG52NBbhTIbaAmjAMskD06X";
			$s[30] = "-----END RSA PRIVATE KEY-----";
			
			unset( $s[1] ); //remove the bad one
			unset( $s[2] ); //remove the bad one
			unset( $s[10] ); //remove the bad one
			unset( $s[11] ); //remove the bad one
		}
		$privkey = implode( "\n", $s );
		
//		$privkey = openssl_pkey_get_private( "-----BEGIN RSA PRIVATE KEY-----\nMIICXAIBAAKBgQDZ3qOYLAWMIj5fSX+RKTw5uQLZ+Ck0BBchiJlztp1T4Xp2vChV\nPyaceXH+4vj+8WTMGulk9lYPRub8MxEiasmN1ekEpVFwgnA9LT2o55LFovUPZhjw\nMSEXdyguhEsOuxW5fBMGVg2CLMKWw0hcbKay96qEPjH0aUfTR2hkZvu5EQIDAQAB\nAoGAYaFbPf28HqfZu2l8ONh5nIRDutlx3VVX6DcGTMwwhD4uWrbGfCzoaIYjh0y+\nt8AEo1IL9KpOtlnXeHyJ2RpxZsHeYhwaHAAqRWIufhLwCDiRznztF9h4D+Vn6Vr+\n7qE8MygJcxruooVxkbfZyLdERvwZJ442545KItlUY+lONWECQQD4YZ512SKRcR3K\n+FOFgDvW+3WU65YZ+XWPE4KNpxQ6ouJjzs6+c1T7k1Wdps8HAdqeJDlBio/VU2Hn\nwNhXqXQzAkEA4I1udlq8UDNyHxTdbNQHCXbawIx/6JJ25tPCLw3kmm90YbpSeNVn\nhoFpF9zSorBd4/LozfHAKlwzI55dtKV5qwJAB70y7MUZOKLnpL1cDNrUAnzPSxaU\nFLcbHJwlAzjjfng4yZdWBcUeLsCGeGUMKlG6eBb02b/xf1obwNbXZtPW1QJBAJCq\nzZW2k0fgPQ/FM3pfh0ETr48JdYZyhePkXWBPRQ4x6/riPSvX4OCJExnqjFF/6p1m\n65FyxGkGCXRTHO+7BA0CQC6L5n4M3kdSp/XVADd+eTMxO7OG7QYgR4LPYslUdDO5\nQ7ARhOk3uPdwkrZAP+fnzx0FRglK3IbdF7V8HiTSJLY=\n-----END RSA PRIVATE KEY-----" );
		if( !$privkey )
		{
			$this->mWWLError = BizResources::localize("LIC_ERR_UNABLE_TO_PARSE_PRIVATE_KEY");
			return false;
		}
		return $privkey;
	}

	/**
	 * Get the public key for openSSL string conversions
	 *
	 * @return resource|bool the public key, false on error
	 */
	private function getPubKey()
	{
		if( !extension_loaded('openssl') )
		{
			$this->mWWLError = BizResources::localize("LIC_ERR_OPENSSL");
			return false;
		}
	
		$publicKeyFile = BASEDIR . "/server/utils/license/pubkey_{$this->mOpenSSLLevel}.pem";
		if( !file_exists( $publicKeyFile ) )
		{
			$this->mWWLError = BizResources::localize("LIC_ERR_UNABLE_TO_FIND_PUBLIC_KEY") . ' ' . $publicKeyFile;
			return false;
		}
		//$openssl_password = "S3(r3tP433w0r6!";
	//		$openssl_password = "";
		$pubkey = openssl_pkey_get_public( "file://".$publicKeyFile );
		if( !$pubkey )
		{
			$this->mWWLError = BizResources::localize("LIC_ERR_UNABLE_TO_PARSE_PUBLIC_KEY") . ': ' . $publicKeyFile;
			return false;
		}
	
		return $pubkey;
	}

	/**
	 * Encrypt the given string using one of the encryption methods
	 * modes: 
	 *  1: debug. One can still see the source
	 *	2: encrypt without openSSL
	 *	3: openSSL + base64. String length will grow.
	 *	4: simple obfuscate, keeping the same string length
	 *
	 * Output:
	 *
	 *	character 0: random
	 *	character 1: random
	 *	character 2: mode
	 *	character 3 and further: the encrypted string
	 *
	 * @param string $str the string to encrypt
	 * @param int $mode the encryption mode, default=1
	 * @return string the converted string, or false on error. Check getError()
	 */
	public function wwl_encrypt( $str, $mode = 1 )
	{
		$this->mWWLError = '';
	
		mt_srand((double)microtime()*1000000);
		$seed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		$randomchar1 = $seed[mt_rand(0,61)];
		$randomchar2 = $seed[mt_rand(0,61)];
		
		switch( $mode )
		{
			case 1:
				$pubkey = $this->getPubKey();
				if( !$pubkey ) { return false; }
				$str= $this->adjustDataLengthForEncryption($str);	
				$ret = openssl_public_encrypt( $str, $str, $pubkey );
				openssl_pkey_free( $pubkey );
				if ( !$ret )
				{
					$this->mWWLError = BizResources::localize("LIC_ERR_PUBLIC_ENCRYPT");;
					return false;
				}
				$str = base64_encode($str);
				break;
			case 2:
				$str = strtr( $str, '|' . $this->mWWLTranstab, $this->mWWLTranstab . '|' );
				break;
			/*
			case 3:
				//In case openSSL can not be used:
				//Try a "Simple but secure encryption based on hash functions"
				//See http://www.phpclasses.org/browse/package/2982.html
				require_once 'wwcr.php';
				// Instantiate new hash_encryption object, feed password
				$crypt = new hash_encryption(hashcrypt_password());
				$str = $crypt->encrypt($str);
			*/
				break;
		}
		
		return $randomchar1 . $randomchar2 . $mode . $str;
	}

	/**
	 * Basically when you encrypt something using an RSA key (whether public or private), the encrypted value must be 
	 * smaller than the key (due to the maths used to do the actual encryption). So if you have a 1024-bit key, in theory
	 * you could encrypt any 1023-bit value (or a 1024-bit value smaller than the key) with that key.
	 * However, the PKCS#1 standard, which OpenSSL uses, specifies a padding scheme (so you can encrypt smaller quantities
	 * without losing security), and that padding scheme takes a minimum of 11 bytes (it will be longer if the value you're
	 * encrypting is smaller). So the highest number of bits you can encrypt with a 1024-bit key is 936 bits because of 
	 * this (unless you disable the padding by adding the OPENSSL_NO_PADDING flag, in which case you can go up to 1023-1024 bits).
	 * With a 2048-bit key it's 1960 bits instead.
	 * See: http://www.php.net/manual/en/function.openssl-public-encrypt.php#55901
	 * @param string $data
	 * @return string $result 
	 */
	private function adjustDataLengthForEncryption( $data )
	{
		$length = strlen( $data );
		$max = floor($this->mOpenSSLLevel / 8) - 11;
		// Maxmimum in bytes = encryption level in bits divided by 8
		// minus padding scheme of 11 bytes.
	
		$result = $data;
		if ( $length !== false && $length > $max ) {
			$result = mb_strcut($data, 0, $max, 'UTF-8');
		}

		return $result;
	}
	
	/**
	 * Decrypt the given string using one of the decryption methods (created by wwl_encrypt())
	 * Input:
	 *	character 0: random
	 *	character 1: random
	 *	character 2: mode
	 *	character 3 and further: the encrypted string

	 * modes: 
	 *  1: debug. One can still see the source
	 *	2: encrypt without openSSL
	 *	3: openSSL + base64. String length will grow.
	 *	4: simple obfuscate, keeping the same string length
	 *
	 * @param string $str the string to decrypt
	 * @return string the decrypted string, or false on error. Check getError();
	 */
	public function wwl_decrypt( $str )
	{
		//skip 2 random chars
		$mode = substr( $str, 2, 1 );
		$str = substr( $str, 3 );
		switch( $mode )
		{
			case 1:
				$privkey = $this->getPrivKey();
				if( !$privkey )
					return false;
				$decrypted_pw = '';
				$ret = openssl_private_decrypt( base64_decode($str), $decrypted_pw, $privkey );
				
				//In case the private key is a string, we don;t need to free it
				//openssl_pkey_free( $privkey ); 

				if ( $ret === false )
				{
					$this->mWWLError = BizResources::localize("LIC_ERR_PRIVATE_DECRYPT");
					return false;
				}
				return $decrypted_pw;
				break;
			case 2:
				return strtr( $str, $this->mWWLTranstab . '|', '|' . $this->mWWLTranstab );
				break;
/*			case 3:
				require_once 'wwcr.php';	
				// Instantiate new hash_encryption object, feed password
				$crypt = new hash_encryption(hashcrypt_password());
				return $crypt->decrypt($str);
				break;
*/
			default:
				//Unknown mode
				$this->mWWLError = BizResources::localize("LIC_UNKNOWN_ERR_DECRYPT");
				return false;
		}
	}
	
	/**
	 * Return the last error string, generated by another function
	 * @return int the last error string
	 */
	public function getError()
	{
		return $this->mWWLError;
	}
	
	/**
	 * Convert the input array containing the separate keys into one license key string
	 * @param array $arr the separate keys
	 * @return string the keys in one string
	 */
	public function makeLicenseKey( $arr )
	{
	//	print "<br>lic:";
	//	print_r( $arr );

		//Convert the array to be sure that the order of the fields is according to our mLicenseFields array
		$arr2 = Array();
		foreach( $this->mLicenseFields as $i => $f )
		{
			if ( !isset( $arr[ $f ] ))
			{
				//In case a new field has been added to $this->mLicenseFields,
				// be sure to support backwards compatibility when currently installed licenses are being updated
				// (e.g. to write an error situation)
				switch( $f )
				{
					case 'testflags':
					{
						//Added in the summer of 2007 (during alpha/beta testing)
						$arr[ $f ] = 0; //backwards compatible value
						break; //continue making a license key
					}
					default:
					{
						$this->mWWLError = BizResources::localize("LIC_MISSING_LICENSE_FIELD") . " $i";
						return false;
					}
				}
			}
			$arr2[] = $arr[ $f ];
		}
		return $this->wwl_encrypt( implode( '#', $arr2 ) );
	}
	
	/**
	 * Convert one license key string into an array of separate keys
	 * @param string the keys in one string
	 * @return array|bool array of separate keys, or false on error
	 */
	public function getLicenseInfo( $license )
	{
		$d = $this->wwl_decrypt( $license );
		if ( $d === FALSE )
			return false;
	//	print "<br>lic=$d";
		$arr = explode( '#', $d );

		//Convert the array where the fieldnames of the mLicenseFields array are used as a 'key' (instead of the order number)
		$arr2 = Array();
		foreach( $arr as $index => $v )
		{
			$arr2[ $this->mLicenseFields[ $index ] ] = $v;
		} 
		return $arr2;
	}
	
	/**
	 * To avoid cut and paste errors, the code (string) can have a prefix that specifies the length of the rest of the string.
	 * @param string $key containing a length prefix
	 * @return string the license string without the length prefix, or false on error.
	 */
	public function stripManualLicense( $key )
	{
		$minpos = strpos( $key, '-' );
		if ( $minpos === FALSE )
			return $key;
			
		$len1 = intval( substr( $key, 0, $minpos ) );
		$rest = substr( $key, $minpos+1 );
		$len2 = strlen( $rest );
		//Including the dot of a normal sentence?
		if ( ( $len2 == $len1 + 1 ) && (substr($rest, -1 ) == '.' ))
		{
			$rest = substr( $rest, 0, - 1);
			$len2 = strlen( $rest );
		}
	//	print "<br>1=$len1, 2=$len2";
		if ( $len1 == $len2 )
			return $rest;
		else
			return false;
	}
	
	/**
	 * To avoid cut and paste errors, the code (string) can have a prefix that specifies the length of the rest of the string.
	 * @param string $str the license string without the length prefix
	 * @return string the string with a length prefix
	 */
	public function makeManualLicense( $str )
	{
		return strlen( $str ) . '-' . $str;
	}
	
	/**
	 * Merge the two strings into one, using the separator
	 * 
	 * @param string in1
	 * @param string in2
	 * @return string merged string
	 */
	public function mergeReclaimString( $in1, $in2)
	{
		return $in1 . $this->mReclaimSeparator . $in2;
	}

	/**
	 * Split the string into two separate strings
	 * 
	 * @param string int
	 * @param string out1
	 * @param string out2
	 * @return boolean found/succes
	 */
	public function splitReclaimString( $in, &$out1, &$out2 )
	{
		$reclaimpos = strpos( $in, $this->mReclaimSeparator );
		if ( $reclaimpos === false )
		{
			$out1 = $in;
			$out2 = '';
			return false;
		}
		$out1 = substr( $in, 0, $reclaimpos );
		$out2 = substr( $in, $reclaimpos + strlen( $this->mReclaimSeparator ) );
		return true;
	}

	/**
	 * Packs license information about a certain productcode into one string
	 * This packed string is stored as a 'value' of the productcode in both FS and DB
	 *
	 * @param string $productname
	 * @param string $serial
	 * @param string $license
	 * @return string 
	 */
	public function makeProductInfo( $productname, $serial, $license )
	{
		$arr = Array( $productname, $serial, $license );
		return implode( $this->mProductInfoSeparator, $arr );
	}

	/**
	 * Unpacks license information about a certain productcode into 3 strings
	 * The unpacked string is stored as a 'value' of the productcode in both FS and DB
	 *
	 * @param string $str string
	 * @param string $productname
	 * @param string $serial
	 * @param string $license
	 * @return boolean success 
	 */
	public function getProductInfo( $str, &$productname, &$serial, &$license )
	{
		$arr = explode( $this->mProductInfoSeparator, $str );
		$productname = array_shift( $arr );
		$serial = array_shift( $arr );
		//In case the license part also contains the separator, make one part again...
		$license = implode( $this->mProductInfoSeparator, $arr );
		return true;
	}
	

}