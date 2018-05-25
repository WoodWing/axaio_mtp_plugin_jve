<?php
/**
 * Utility class for Password Encryption.
 *
 * It takes a public key for encryption and a private key for decryption.
 *
 * @since v4.2
 * @copyright WoodWing Software bv. All Rights Reserved.
*/

class PasswordEncryption
{
	/**
	 * @var string
	 */
	private $mLastError;
	/**
	 * @var string
	 */
	private $mPublicKeyFile;
	/**
	 * @var string
	 */
	private $mPrivateKeyFile;

	/**
	 * Construct the Password Encryption.
	 *
	 * @param string $publickeyfile Full path (using forward slashes) to file which contains public key used for password encryption
	 * @param string $privatekeyfile Full path (using forward slashes) to file which contains private key used for password decryption
	 */
   public function __construct( $publickeyfile=null, $privatekeyfile=null )
   {
   	$this->mPublicKeyFile = $publickeyfile;
   	$this->mPrivateKeyFile = $privatekeyfile;
   	$this->mLastError = '';
   }

	/**
	 * Validate Private Key.
	 *
	 * Check if the given private key file is accessable and can be parsed.<br>
	 * Return <var>TRUE</var> when key configured well or not given at all.<br>
	 * Return <var>FALSE</var> when fails. Call {@link GetLastError()} to retrieve error.<br>
	 *
	 * @return boolean Whether or not the private key is valid
	 */
	public function ValidatePrivateKey()
	{
		if( !is_null( $this->mPrivateKeyFile ) ) {
			if( !extension_loaded('openssl') ) {
				$this->mLastError = 'Configuration error: OpenSSL module not installed or enabled for PHP.';
				return FALSE;
			}
			if( file_exists( $this->mPrivateKeyFile ) === FALSE ) {
				$this->mLastError = 'Configuration error: Could not find private key file for password decryption:'."\r\n".$this->mPrivateKeyFile;
				return FALSE;
			}
			$privkey = openssl_pkey_get_private( "file://".$this->mPrivateKeyFile );
			if( $privkey === FALSE ) {
				$this->mLastError = 'Configuration error: Could not parse private key for password decryption. Seems to be unaccessable or in bad format: ['.$this->mPrivateKeyFile.']';
				return FALSE;
			}
			openssl_pkey_free( $privkey );
		}
		return TRUE;
	}

	/**
	 * Validate Public Key.
	 *
	 * Check if the given public key file is accessable and can be parsed.<br>
	 * Return <var>TRUE</var> when key configured well or not given at all.<br>
	 * Return <var>FALSE</var> when fails. Call {@link GetLastError()} to retrieve error.<br>
	 *
	 * @return boolean Whether or not the public key is valid
	 */
	public function ValidatePublicKey()
	{
		if( !is_null( $this->mPublicKeyFile ) ) {
			if( !extension_loaded('openssl') ) {
				$this->mLastError = 'Configuration error: OpenSSL module not installed or enabled for PHP.';
				return FALSE;
			}
			if( file_exists( $this->mPublicKeyFile ) === FALSE ) {
				$this->mLastError = 'Configuration error: Could not find public key file for password decryption:'."\r\n".$this->mPublicKeyFile;
				return FALSE;
			}
			// TODO: public key check fails because we manualy delete the begin/end markers (-----BEGIN PUBLIC KEY-----)
			// to make the ID plugins happy... this needs to be solved at plugins first, before we can uncomment this check
			//			$publickey = openssl_pkey_get_public( "file://".$this->mPublicKeyFile );
			//			if( $publickey === FALSE ) {
			//				$this->mLastError = 'Configuration error: Could not parse public key for password decryption. Seems to be unaccessable or in bad format: ['.$this->mPublicKeyFile.']';
			//				return FALSE;
			//			}
			//			openssl_pkey_free( $publickey );
		}
		return TRUE;
	}
	
	/**
	 * Decrypt Private Password.
	 *
	 * Decrypt given <var>$password</var> using the private key.<br>
	 * Use {@link ValidatePrivateKey()} before this calling {@link DecryptPrivatePassword()} to determine key exists and is valid.<br>
	 * On success, it returns the decrypted password.<br>
	 * When decryption fails, assumed is that the password is badly encrypted or not encrypted at all.<br>
	 * In that case, the given password is passed back as-is to let caller try to logon anyway.<br>
	 *
	 * @param string $password plain user typed password
	 * @return string
	 */
	public function DecryptPrivatePassword( $password )
	{
		require_once BASEDIR.'/server/utils/StopWatch.class.php';
		$sw = new StopWatch();
		$sw->Start();
		LogHandler::Log( __CLASS__, 'DEBUG', 'before private decryption: '.$sw->Fetch().' sec' );

		$privkey = openssl_pkey_get_private( "file://".$this->mPrivateKeyFile );
		$decrypted_pw = '';
		if( openssl_private_decrypt( base64_decode($password), $decrypted_pw, $privkey ) === FALSE ) {
			// Password decryption failed. 
			// Assume client app did not (support) password encryption; fall back at plain password
			$decrypted_pw = $password;
		} else {
			// for debuging purposes:
			// LogHandler::Log('soap', 'DEBUG', 'Typed ['.$password.'] Decrypted ['.$decrypted_pw.']' );
		
			openssl_pkey_free( $privkey );
		}
		LogHandler::Log( __CLASS__, 'DEBUG', 'after private decryption: '.$sw->Fetch().' sec' );
		return $decrypted_pw;
	}

	/**
	 * Get Public Key.
	 *
	 * Return the public key value (which is the content of given file path).<br>
	 * Use {@link ValidatePrivateKey()} before this calling {@link GetPublicKey()} to determine key exists and is valid.<br>
	 * Return empty string when no key given.<br>
	 *
	 * @return string Public key value
	 */
	public function GetPublicKey() { return is_null($this->mPublicKeyFile) ? '' : file_get_contents( $this->mPublicKeyFile );	}  

	/**
	 * Get Last Error.
	 *
	 * Return any error raised during {@link ValidatePublicKey()} or {@link ValidatePrivateKey()}.<br>
	 * Return empty string when no error.<br>
	 *
	 * @return string
	 */
	public function GetLastError() { return $this->mLastError; }
}