<?php
/**
 * HTTP client that integrates with OpenCalais.
 * More information about connection options, requests and responses is available at:
 * http://developer.permid.org/open-calais-api/open-calais-tagging-user-guide/
 *
 * @package     Enterprise
 * @subpackage  ServerPlugins
 * @since       v9.1.0
 * @copyright   WoodWing Software bv. All Rights Reserved.
 */

class OpenCalais
{
	/* The Name of the Key in the smart_config table. */
	const CONFIG_API_KEY = 'opencalais_key';
	/* The API URL. */
	const API_URL = 'https://api.thomsonreuters.com/permid/calais';

	/**
	 * Retrieves the OpenCalais API key from the database
	 *
	 * @return null|string The retrieved API key, or null if not set.
	 */
	public static function getApiKey()
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::getValue( self::CONFIG_API_KEY );
	}

	/**
	 * Stores the OpenCalais API key in the database.
	 *
	 * @param string $key The API key to be stored / updated.
	 * @return bool Whether or not the Key was successfully stored.
	 */
	public static function storeApiKey( $key )
	{
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		return DBConfig::storeValue( self::CONFIG_API_KEY , $key );
	}

	/**
	 * Returns the OpenCalais API url.
	 *
	 * @return string The OpenCalais url.
	 */
	public static function getUrl()
	{
		return self::API_URL;
	}

	/**
	 * Retrieves suggestions from OpenCalais and forms them into TermEntity Objects.
	 *
	 * TermEntities is an array of values containing term entities in lowercase, the key for these values can be any
	 * unique string. for example: array( 'SociaLTags' => 'socialtags')/
	 *
	 * @param string $content The text content for which to get OpenCalais suggestions.
	 * @param string[] $termEntities The Term Entities for which to get OpenCalais suggestions.
	 * @return array|TermEntity[] An empty array if no values found, or an array containing TermEntity objects otherwise.
	 */
	public static function suggest( $content, $termEntities )
	{
		$enterpriseData = array();

		// Retrieve suggestions from OpenCalais.
		$openCalaisResponse = self::request( $content );

		// Verify that we have received a valid response.
		$response = self::validate( $openCalaisResponse );
		if( $response ) {
			// Document is valid, retrieve the TermEntities from it.
			$enterpriseData = self::getEnterpriseData( $response, $termEntities );
		}
		return $enterpriseData;
	}

	/**
	 * Sends a request to OpenCalais for suggestions.
	 *
	 * @param string $content The text content for which to get suggestions.
	 * @return null|string The raw response string or null if the request was unsuccessful.
	 */
	private static function request( $content )
	{
		$returnValue = null;

		try {
			// Check that the API key is set.
			$key = self::getApiKey();
			if( empty( $key ) ) {
				throw new BizException( 'ERR_ERROR', 'Server', 'The API key was not found, please run the integrations page.');
			}

			PerformanceProfiler::startProfile( 'OpenCalais Request', 3 );
			// Get the Params XML.

			// Create a new HTTP client and send the request.
			try {
				$http = self::httpClient();

				// Compose the final request.
				$http->setHeaders( array(
					'Content-Type' => 'text/raw',
					'X-AG-Access-Token' => $key,
					'outputFormat' => 'application/json',
					'x-calais-language' => 'English'
				));

				$http->setRawData( $content, 'text/raw' );
				// Send the request.
				$response = $http->request( Zend_Http_Client::POST );

				if( $response->isSuccessful() ) {
					$returnValue = $response->getBody();
				} else {
					$errorMessage = self::handleRequestErrors( $response );
					throw new BizException( 'ERR_ERROR', 'Server', $errorMessage );
				}
			} catch( Exception $ex ) {
				// Rethrow the Zend HTTP error as a BizException.
				throw new BizException( 'ERR_ERROR', 'Server', $ex->getMessage() );
			}
		} catch( BizException $e ) {
		}
		PerformanceProfiler::stopProfile( 'OpenCalais Request', 3 );

		return $returnValue;
	}

	/**
	 * Returns a reference to a http client.
	 *
	 * @return Zend_Http_Client
	 */
	static public function httpClient()
	{
		require_once 'Zend/Http/Client.php';

		// Resolve the enterprise proxy if configured. This is taken as is from the original
		// DigitalPublishingSuiteClient and has not been tested.
		$configurations = ( defined('ENTERPRISE_PROXY') && ENTERPRISE_PROXY != '' )
			? unserialize( ENTERPRISE_PROXY )
			: array();

		if ( $configurations ) {
			if ( isset($configurations['proxy_host']) ) {
				$curlOptions[CURLOPT_PROXY] = $configurations['proxy_host'];
			}
			if ( isset($configurations['proxy_port']) ) {
				$curlOptions[CURLOPT_PROXYPORT] = $configurations['proxy_port'];
			}
			if ( isset($configurations['proxy_user']) && isset($configurations['proxy_pass']) ) {
				$curlOptions[CURLOPT_PROXYUSERPWD] = $configurations['proxy_user'] . ":" . $configurations['proxy_pass'];
			}
		}

		$curlOptions[CURLOPT_SSL_VERIFYPEER] = true; // To prevent a man in the middle attack. (EN-29338).

		// This should always be a real path. When the file doesn't exist the OpenCalais healthcheck will fix
		// this.
		$curlOptions[CURLOPT_CAINFO] = realpath( ENTERPRISE_CA_BUNDLE );

		// The connections should use TLSv1. The PHP documentation doesn't list this option, but
		// since it is passed directly to libcurl we can use it. (Checked in the PHP source code) (EN-29335).
		$curlOptions[CURLOPT_SSLVERSION] = 1;
		$curlOptions[CURLOPT_CONNECTTIMEOUT] = 10; // To improve connectivity (EN-31584).

		$curlConfig = array( 'curloptions' => $curlOptions );
		$curlConfig['adapter'] = 'Zend_Http_Client_Adapter_Curl';
		$httpClient = new Zend_Http_Client( self::API_URL, $curlConfig );

		return $httpClient;
	}

	/**
	 * Validates the raw OpenCalais response.
	 *
	 * @param string $response The raw response from OpenCalais in json format.
	 * @return false|array Associative array of the converted json response or false in case of errors.
	 */
	private static function validate( $response )
	{
		$result = false;
		$errors = array();

		if ( is_string( $response ) ) {
			$responseAssoc = json_decode( $response, true );
		} else {
			LogHandler::Log( __METHOD__, 'ERROR', 'The OpenCalais response is not valid.' );
			return $result;
		}

		if( $responseAssoc ) foreach( $responseAssoc as $properties ) {
			if ( isset( $properties['_typeGroup'] ) &&
				 $properties['_typeGroup'] == 'messages' &&
				 $properties['messagetype'] == 'error' ) {
				$errors[] = $properties['text'];
			}
		}

		if( $errors ) {
			LogHandler::Log( __METHOD__, 'ERROR', 'The OpenCalais response contains errors.' );
			foreach ($errors as $error) {
				LogHandler::Log( __METHOD__, 'ERROR', 'Reported Error: '  . $error );
			}
		} else {
			$result = $responseAssoc;
		}

		return $result;
	}

	/**
	 * Handles responses error status codes. Based on the status code a message is written to the log file.
	 *
	 * @param Zend_Http_Response $response
	 * @return string Human readable message.
	 */
	private static function handleRequestErrors( Zend_Http_Response $response )
	{
		$message = 'OpenCalais: Status: '.$response->getStatus().'. '. $response->getMessage().' ';
		$body = json_decode( $response->getBody(), true );
		$detail = '';
		if ( $body ) {
			self::convertToString( $body, $detail );
		}
		if ( $detail ) {
			$message .= 'Detail: '.$detail;
		}

		return $message;
	}

	/**
	 * Converts an array (of arrays) into a string value. If the input is not an array the string value of the
	 * input is taken.
	 *
	 * @param mixed $input Input to be converted.
	 * @param string $resultString The input converted to a string.
	 */
	private static function convertToString( $input, &$resultString )
	{
		if ( is_array( $input )) {
			if ( $input ) foreach ( $input as $content ) {
				self::convertToString( $content, $resultString );
			}
		} else {
			$resultString .= strval( $input ).'. ';
		}
	}

	/**
	 * Transforms the json response into Enterprise compatible TermEntity objects.
	 *
	 * @param array $response The OpenCalais (as an associative array).
	 * @param null|string[] $termEntities The TermEntities to retrieve from the OpenCalais response.
	 * @return array|EntityTags[] An empty array if no valid values were found, an EntityTags array otherwise.
	 */
	private static function getEnterpriseData( array $response, $termEntities=null )
	{
		$suggestedTags = array();
		require_once BASEDIR . '/server/interfaces/services/wfl/DataClasses.php';

		$tagsByEntity = array();
		if( $response ) foreach( $response as $properties ) {
			if( isset( $properties['_typeGroup'] )) {
				switch ( $properties['_typeGroup'] ) {
					case 'entities':
						$entityName = $properties['_type'];
						break;
					case 'socialTag';
						$entityName = 'SocialTags';
						break;
					default:
						$entityName = '';
						// E.g. 'topics' are ignored.
				}

				$storeName = array_search( strtolower( $entityName ), $termEntities );
				if( $storeName ) { // Found
					// Get the actual Name of the property as requested by CS.
					if( !isset( $tagsByEntity[$storeName] )) {
						$tagsByEntity[$storeName] = array();
					}
					if( $entityName != 'SocialTags' ) { // SocialTags require special handling.
						$tag = new AutoSuggestTag();
						$tag->Value = $properties['name'];
						$tag->Score = $properties['relevance'];
					} else { // SocialTags
						$tag = new AutoSuggestTag();
						$text = $properties['name'];
						$originalValue = $properties['originalValue'];
						$lastPosition = strrpos($text, $originalValue);
						if ( false == $lastPosition ) {
							$tag->Value = $text;
						} else {
							$tag->Value = substr( $text, 0, $lastPosition );
						}
						// SocialTags importance has a different meaning than the relevance on a normal tag. The higher the
						// number the less important it is, therefore rearrange the scores to match the relevance of normal
						// term entities; 1.000 for the highest importance (1) and 0.500 in all other cases for the importance 2.
						$newScore = intval( $properties['importance'] );
						$newScore = ($newScore == 1) ? '1.000' : '0.500';
						$tag->Score = $newScore;
					}
					$tagsByEntity[$storeName][] = $tag;
				}
			}
		}

		// Sort all our found values by Score descending and Value ascending.
		if( $tagsByEntity ) foreach( $tagsByEntity as $entity => $tagList ) {
			// Add the values to a scores list.
			$scores = array();
			foreach( $tagList as $autoSuggestTag ) {
				if (!isset($scores[$autoSuggestTag->Score])) {
					$scores[$autoSuggestTag->Score] = array();
				}
				$scores[$autoSuggestTag->Score][$autoSuggestTag->Value] = $autoSuggestTag;
			}

			// Sort the scores, ensure that the negative values for SocialTags are correctly sorted.
			$tagList = array();
			krsort( $scores );

			// Sort the values per score and add the values to the tagList.
			foreach( $scores as $tags ) {
				sort( $tags );
				foreach( $tags as $tag) {
					$tagList[] = $tag;
				}
			}

			// Create the output list.
			$entityTags = new EntityTags();
			$entityTags->Entity = $entity;
			$entityTags->Tags = $tagList;
			$suggestedTags[] = $entityTags;
		}

		return $suggestedTags;
	}
}