<?php
/**
 * @package     Enterprise
 * @subpackage  BizClasses
 * @since       v9.0.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 *
 * This class installs licenses for Enterprise products (server and clients).
 * This feature is introduced for internal use, such as automated installers on build servers 
 * and to quickly setup QA/demo environments. The installation goes silently whithout the need
 * of filling in the license admin pages.
 * 
 * Products, seats and serial keys are read from the config/WWActivate.xml file. (Therefor 
 * this file needs to be installed manually before running the Enterprise installation.) 
 * Then the products are registered in the WoodWing Activation Server (WAS) and in Enterprise 
 * Server's filestore/database. Only products are registered that are known to WAS too.
 */
 
class WW_Utils_License_StealthInstaller
{
	/**
	 * Class initializer.
	 */
	private function init()
	{
		require_once dirname(__FILE__).'/../../../config/config.php';
		require_once BASEDIR.'/server/utils/license/license.class.php';
		require_once BASEDIR.'/server/regserver.inc.php';
		require_once BASEDIR.'/server/secure.php';
	
		$this->lic = new License();
		$this->error = '';
		$clientIp = isset($_SERVER[ 'REMOTE_ADDR' ]) ? $_SERVER[ 'REMOTE_ADDR' ] : '';
		$this->baseParams = array(
			'supportinfo'     => '', // TODO
			'manual'          => '0',
			'localURL'        => '',
			'clientname'      => $this->lic->getServerNameOrAddr(),
			'localtime'       => date( 'Ymd H:i:s' ),
			'clientip'        => $clientIp,
			'installationcode'=> $this->lic->getInstallationCode( $this->error ),
			'version'         => PRODUCTVERSION,
			'majorversion'    => PRODUCTMAJORVERSION,
			'minorversion'    => PRODUCTMINORVERSION,
			'platform'        => PHP_OS . '/' . PHP_VERSION . '/' . DBTYPE,
		);
		$this->productParams = array();
		$this->collectedParams = array();
	}
	
	/**
	 * Whether or not the WWActivate.xml file is installed that allows to
	 * do an automated activation/deactivation of all its preconfigured licenses.
	 */
	public function canAutoActivate()
	{
		return file_exists( BASEDIR.'/config/WWActivate.xml' );
	}
	
	/**
	 * (Un)Installs all product licenses that can be found in the WWActivate.xml file
	 * which are also known to the WoodWing Activation Server (WAS).
	 *
	 * @param bool $install TRUE to install, or FALSE to uninstall.
	 * @return Whether or not the (un)installation was successful.
	 */
	public function installProductLicenses( $install = true )
	{
		$retVal = false;
		$wasProducts = array();
		$xmlProducts = array();
		$this->init();
		if( !$this->error ) {
			$xmlProducts = $this->parseWwActivateXml();
		}

		// Step 3: Retrieve list of concurrent products from activation server.
		if( !$this->error ) {
			if( defined( 'SECRETLICENSELOG' ) ) {
				LogHandler::Log( 'StealthInstaller', 'INFO', 
					'Step 3: Retrieve list of concurrent products from activation server.' );
			}
			$wasProducts = $this->getProducts();
		}

		if( !$this->error ) {
		
			// Build a list of products to activate ($actProducts) from the products
			// found in the WWActivate.xml file ($xmlProducts) which are also known
			// by the activation server ($wasProducts).
			$actProducts = $this->buildProductList( $xmlProducts, $wasProducts );
			$retVal = count($actProducts) > 0;
			
			// Register the products at WAS.
			foreach( $actProducts as $actProduct ) {
				$this->productParams = $actProduct;
				if( $install ) {
					$this->installProductLicense();
				} else {
					$this->uninstallProductLicense();
				}
				if( !$this->error ) {
					$retVal = false;
					$this->error = ''; // forget this error and continue with next product
				}
			}
		}
		return $retVal;
	}
	
	/**
	 * Installs a license for a given product ($this->productParams) by using the 
	 * WoodWing Activation Server (WAS).
	 *
	 * @return bool Whether or not installation was successful.
	 */
	private function installProductLicense()
	{
		// Step 5: Get the subscription type from activation server.
		if( !$this->error ) {
			$this->getSubscription();
		}

		// Step 6: Get the license key from activation server.
		if( !$this->error ) {
			$this->getLicense();
		}

		// Step 10: Register product license at activation server.
		if( !$this->error ) {
			$this->registerLicense( true );
		}

		// Step 11: Save product license locally and at activation server.
		if( !$this->error ) {
			$this->setLicense( true );
		}

		return $this->error ? false : true;
	}
	
	/**
	 * Uninstalls a license for a given product ($this->productParams) by using the 
	 * WoodWing Activation Server (WAS).
	 *
	 * @return bool Whether or not installation was successful.
	 */
	public function uninstallProductLicense()
	{
		$productcode = $this->productParams['productcode'];
		$serial = $this->lic->getSerial( $productcode );
		$info = array();
		$errorMessage = ''; 
		$licenseStatus = $this->lic->getLicenseStatus( $productcode, $serial, $info, $errorMessage );
		
		$color = 'red';
		$status = '';
		$flags = 0;
		$this->lic->getLicenseStatusInfo( $licenseStatus, $color, $status, $flags );
	
		if( $licenseStatus > WW_LICENSE_OK_MAX ) {
			$this->error = $errorMessage;
		}
		
		// Step 10: Register product license at activation server.
		if( !$this->error ) {
			$this->registerLicense( false );
		}

		// Step 11: Save product license locally and at activation server.
		if( !$this->error ) {
			$this->setLicense( false );
		}

		return $this->error ? false : true;
		
	}
		
	/**
	 * Step 3: Retrieve list of concurrent products from activation server
	 * On error, the $this->error class member will be set.
	 */
	private function getProducts()
	{
		$products = array();
		$parameters = $this->baseParams + array(
			'mode' => 'concurrentproducts',
		);
		$retFormFields = $this->postToActicationServer( $parameters );
		if( !$this->error ) {
			$this->collectedParams['concurrentproducts'] = $retFormFields['concurrentproducts'];
			
			// Parse the concurrentproducts field and build products array structure from it.
			$productsPack = $this->collectedParams['concurrentproducts'];
			$productsPack = explode( '^', $productsPack ); // product separator
			if( $productsPack ) foreach( $productsPack as $productPack ) {
				$productFields = explode( '~', $productPack ); // field separator
				$product = array();
				$product['productcode'] = $productFields[0];
				$product['productname'] = $productFields[1];
				$product['productversion'] = $productFields[2];
				$products[ $product['productcode'] ] = $product;
			}
		}
		return $products;
	}
	
	/**
	 * Step 5: Get the subscription type from activation server.
	 * On error, the $this->error class member will be set.
	 */
	private function getSubscription()
	{
		if( defined( 'SECRETLICENSELOG' ) ) {
			LogHandler::Log( 'StealthInstaller', 'INFO', 
				'Step 5: Get the subscription type from activation server.' );
		}
		$parameters = $this->baseParams + $this->productParams + $this->collectedParams + array(
			'mode' => 'getsubscription',
		);
		$retFormFields = $this->postToActicationServer( $parameters );
		if( !$this->error ) {
			$this->collectedParams['subscription'] = $retFormFields['subscription'];
		}
	}

	/**
	 * Step 6: Get the license key from activation server.
	 * On error, the $this->error class member will be set.
	 */
	private function getLicense()
	{
		if( defined( 'SECRETLICENSELOG' ) ) {
			LogHandler::Log( 'StealthInstaller', 'INFO', 
				'Step 6: Get the license key from activation server.' );
		}
		$parameters = $this->baseParams + $this->productParams + $this->collectedParams + array(
			'mode' => 'R1',
		);
		$retFormFields = $this->postToActicationServer( $parameters );
		if( !$this->error ) {
			$this->collectedParams['license'] = $retFormFields['license'];
			$this->collectedParams['ticket'] = $retFormFields['ticket'];
		}
	}
			
	/**
	 * Step 10: Register product license at activation server.
	 * On error, the $this->error class member will be set.
	 *
	 * @param boolean $install
	 */
	private function registerLicense( $install )
	{
		if( defined( 'SECRETLICENSELOG' ) ) {
			$action = $install ? 'Register' : 'Unregister';
			LogHandler::Log( 'StealthInstaller', 'INFO', 
				'Step 10: '.$action.' product license at activation server.' );
		}
		$parameters = $this->baseParams + $this->productParams + $this->collectedParams + array(
			'mode' => $install ? 'R1' : 'U1',
		);
		$retFormFields = $this->postToActicationServer( $parameters );
		if( !$install && !$this->error ) {
			$this->collectedParams['license'] = $retFormFields['license'];
			$this->collectedParams['ticket'] = $retFormFields['ticket'];
		}
	}

	/**
	 * Step 11: Save product license locally and at activation server.
	 * On error, the $this->error class member will be set.
	 *
	 * @param boolean $install
	 */
	private function setLicense( $install )
	{
		if( defined( 'SECRETLICENSELOG' ) ) {
			$action = $install ? 'Set' : 'Unset';
			LogHandler::Log( 'StealthInstaller', 'INFO', 
				'Step 11: '.$action.' product license locally and at activation server.' );
		}
		$error = '';
		$licenseStatus = $this->lic->installLicense( 
			$this->productParams['productcode'],
			$this->productParams['productname'],
			$this->productParams['serial'],
			$this->collectedParams['license'],
			false,
			$error
		);
		if( defined( 'SECRETLICENSELOG' ) ) {
			LogHandler::Log( 'StealthInstaller', 'DEBUG', 'License Status: '.$licenseStatus );
		}

		switch( $licenseStatus )
		{
			case WW_LICENSE_OK:
				$message = "License installed successfully.";
				break;
			case WW_LICENSE_OK_REMOVED:
				$message = "License removed successfully.";
				$licenseStatus = WW_LICENSE_OK;
				break;
			case WW_LICENSE_OK_USERLIMIT:
				$message = "License installed successfully. License usage limit reached.";
				$licenseStatus = WW_LICENSE_OK;
				break;
			case WW_LICENSE_OK_WARNING:
			case WW_LICENSE_OK_INTERNAL:
				$message = "License installed successfully.";
				$licenseStatus = WW_LICENSE_OK;
				break;
			default:
				$message = '';
				break;
		}
		
		// Clear error when status is ok. For example "** License for internal use only. **" is not an error.
		if( $licenseStatus <= WW_LICENSE_OK_MAX ) {
			$error = '';
		}

		$parameters = $this->baseParams + $this->productParams + $this->collectedParams + array(
			'mode' => $install ? 'R2' : 'U2',
			'error' => $error,
			'message' => $message,
			'status' => $licenseStatus,
		);
		$this->postToActicationServer( $parameters );
	}
	
	/**
	 * Sends a HTTP request to the WoodWing Activation Server (WAS).
	 * The fields of the returned HTML form are parsed and returned back to caller.
	 *
	 * @param array $parameters Key-value pairs to send.
	 * @return array Key-value pairs parsed from the returned HTML form.
	 */
	private function postToActicationServer( $parameters )
	{
		if( defined( 'SECRETLICENSELOG' ) ) {
			LogHandler::Log( 'StealthInstaller', 'DEBUG', 
				'Sending to activation server: '.print_r( $parameters, true ) );
		}
		$response = post_it( $parameters, ACTIVATEURL, $this->lic->getProxyParameters() );
		$fieldMap = $this->parseFormFields( $response );
		if( defined( 'SECRETLICENSELOG' ) ) {
			LogHandler::Log( 'StealthInstaller', 'DEBUG', 
				'Returned fields from activation server: '.print_r( $fieldMap, true ) );
		}
		return $fieldMap;
	}

	/**
	 * Retrieves form fields from a given HTML document.
	 *
	 * @param string $html HTML document returned by activation server.
	 * @return array The name-value pairs found in the HTML form (=hidden input fields).
	 */
	private function parseFormFields( $html )
	{
		$fieldMap = array();
		$doc = new DOMDocument();
		if( $doc->loadHTML( $html ) ) {
			$xpath = new DOMXPath( $doc );
			$formFields = $xpath->query( '//input[@type="hidden"]' );
			foreach( $formFields as $formField ) {
				$name = $formField->getAttribute( 'name' );
				$value = $formField->getAttribute( 'value' );
				$fieldMap[ $name ] = $value;
			}
			if( isset( $fieldMap['error']) && $fieldMap['error'] ) {
				if( defined( 'SECRETLICENSELOG' ) ) {
					LogHandler::Log( 'StealthInstaller', 'ERROR', 
						'Error returned from activation server: '.$fieldMap['error'] );
				}
				$this->error = $fieldMap['error'];
			}
		} else {
			if( defined( 'SECRETLICENSELOG' ) ) {
				LogHandler::Log( 'StealthInstaller', 'ERROR', 
					'Could not parse HTML returned from activation server: '.$html );
			}
		}
		return $fieldMap;
	}
	
	/**
	 * Parses the config/WWActivate.xml file and retrieves product codes, serial keys 
	 * and concurrent seat counts. When the file does not exist or has no valid XML structure, 
	 * an empty array is returned.
	 *
	 * @return array Array of arrays, each having productcode, serial and concurrentseats keys.
	 */
	private function parseWwActivateXml()
	{
		$products = array();
		$file = BASEDIR.'/config/WWActivate.xml';
		if( file_exists( $file ) ) {
			$doc = new DOMDocument();
			if( $doc->loadXML( file_get_contents( $file ) ) ) { // URL encoded paths fail: $doc->load( $dile ); (BZ#6561)
				$xpath = new DOMXPath( $doc );
				$xmlProducts = $xpath->query( '//wwact:product' );
				foreach( $xmlProducts as $xmlProduct ) {
					$product = array();
					$xmlTexts = $xpath->query( 'wwact:key/text()', $xmlProduct );
					if( $xmlTexts->length > 0 ) {
						$product['productcode'] = $xmlTexts->item(0)->wholeText;
					} else {
						continue; // skip incomplete structures
					}
					$xmlTexts = $xpath->query( 'wwact:serial/text()', $xmlProduct );
					if( $xmlTexts->length > 0 ) {
						$product['serial'] = $xmlTexts->item(0)->wholeText;
					} else {
						continue; // skip incomplete structures
					}
					$xmlTexts = $xpath->query( 'wwact:seats/text()', $xmlProduct );
					if( $xmlTexts->length > 0 ) {
						$product['concurrentseats'] = $xmlTexts->item(0)->wholeText;
					} else {
						$product['concurrentseats'] = '1'; // optional, take default value of 1 seat
					}
					$products[$product['productcode']] = $product;
				}
			} else {
				$this->error = 
					'Could not parse the "'.$file.'" file. Therefor the automated license '.
					'installation failed. Please repair file or install licenses manually.';
			}
		} // else : Be silent since this is an optional feature that only comes into
		  //        action when the WWActive.xml file exists.
		return $products;
	}
	
	/**
	 * Build a list of products to activate ($actProducts) from the products
	 * found in the WWActivate.xml file ($xmlProducts) which are also known
	 * by the activation server ($wasProducts).
	 *
	 * @param array $xmlProducts Products read from WWActivate.xml file.
	 * @param array $wasProducts Products received from WAS.
	 * @return array Products to install/uninstall.
	 */
	private function buildProductList( $xmlProducts, $wasProducts )
	{
		$actProducts = array();
		if( $xmlProducts ) foreach( $xmlProducts as $productCode => $xmlProduct ) {
			if( array_key_exists( $productCode, $wasProducts ) ) {
				$wasProduct = $wasProducts[$productCode];
				$actProduct = $xmlProduct;
				$actProduct['productname'] = $wasProduct['productname'].' ('.$wasProduct['productversion'].')';
				$actProducts[] = $actProduct;
			}
		}
		return $actProducts;
	}
	
	/**
	 * Returns the error message, if any occurred during auto installation.
	 *
	 * @return string Error message string.
	 */	
	public function getError()
	{
		return $this->error;
	}
}