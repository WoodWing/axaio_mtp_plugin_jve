<?php
/**
 * Spelling checker and suggestions using Google web services.
 *
 * @since v7.4
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
require_once BASEDIR.'/server/interfaces/plugins/connectors/Spelling_EnterpriseConnector.class.php';

class GoogleWebSpelling_Spelling extends Spelling_EnterpriseConnector
{
	/**
	 * Refer to abstract class checkSpelling() header.
	 */
	public function checkSpelling( array $dictionaries, array $wordsToCheck )
	{
		$mistakeWords = array();
		$stringToCheck = null;
		foreach( $wordsToCheck as $wordToCheck ) {
			if( trim( $wordToCheck ) ) {
				$stringToCheck .= $wordToCheck . ' ';
			}
		}
		try {
			$mistakesWithSuggestions = $this->callGoogleSpellCheck( $dictionaries, $stringToCheck );
		} catch ( BizException $e ) {
			$detail = 'Google Web Spelling failed to check spelling for language(s): "'.$dictionaries[0].'" . '.$e->getMessage();
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}
		for( $i=0; $i < count($mistakesWithSuggestions); $i++ ) {
			$mistakeWords[] = mb_substr( $stringToCheck, $mistakesWithSuggestions[$i]['offset'], 
											$mistakesWithSuggestions[$i]['length'], 'UTF-8' );
		}
		return $mistakeWords;
	}

	/**
	 * Refer to abstract class getSuggestions() header.
	 */
	public function getSuggestions( array $dictionaries, $wordForSuggestions ) 
	{
		$suggestedWords = array();
		try {
			$suggestions = $this->callGoogleSpellCheck( $dictionaries, $wordForSuggestions );
		} catch ( BizException $e ){
			$detail = 'Google Web Spelling failed to get suggestions for language(s): "'.$dictionaries[0].'" . '.$e->getMessage();
			throw new BizException( null, 'Server', $detail, 'Configuration error' );
		}

		if ( count($suggestions) > 0 && isset($suggestions[0]['suggestions']) ){
			$suggestedWords = explode("\t", $suggestions[0]['suggestions']);
			$suggestedWords = array_slice( $suggestedWords, 0, $this->config['suggestions'] );
		}
		return $suggestedWords;
	}
			
	/*
	* Check for spelling error by calling google spell check.
	* This function returns an array of mistake words found in $stringToCheck.
	* $foundMistakeWords is an array with all spelling-error words with each
	* mistake word having the following info:
	* -offset: The offset of the mistake word found in $stringToCheck.
	* -length: Length of the msitake word found in $stringToCheck.
	* -confidenceLevel: Confidence of the suggestion
	* -suggestions: Tab delimited list of suggestions
	*
	* @param array $dictionaries Language of the dictionary. For an example 'en','ru'.
	* @param string $stringToCheck The string to be checked by google spelling check.
	* @return array $foundMistakeWords Two-Dimensional array of mistake words with its respective info, refer to function header.
	*/
	private function callGoogleSpellCheck( array $dictionaries, $stringToCheck )
	{
		try {
			require_once 'Zend/Http/Client.php';
			$googleSpellLang = $dictionaries[0];
			$url = $this->config['location'].'?lang=' . $googleSpellLang;// . '&hl=en';
			$xml     = '<?xml version="1.0" encoding="utf-8" ?>'.
					   '<spellrequest textalreadyclipped="0" ignoredups="0" ignoredigits="1" ignoreallcaps="1">'.
					   '<text>' . $stringToCheck . '</text></spellrequest>';

			$httpClient = new Zend_Http_client();
			$httpClient->setUri( $url );								
			$httpClient->setConfig(array( 'httpversion' => Zend_Http_Client::HTTP_0 ));
			
			$headers = array(
							Zend_Http_Client::CONTENT_TYPE => 'text/xml',
							Zend_Http_Client::CONTENT_LENGTH => strlen($xml),
							'Connection' => 'close');
			$httpClient->setHeaders( $headers );
			$httpClient->setRawData( $xml, 'text/xml' );
			$response = $httpClient->request( Zend_Http_Client::POST );
			
		} catch( Zend_Http_Client_Exception $e ) {
			$message = 'Google Web Spelling engine returns error : "'.$e->getMessage().'"';
			throw new BizException( null, 'Server', null, $message );
		}
		
		// TODO: detect fatal error (such as too many words, as happens with ruRU language)

		// Parse the returned mistake words and its suggestions
		$domDoc = new DomDocument();
		if( $domDoc->loadXML( $response->getRawBody() ) ){
			$xpath = new DOMXPath( $domDoc );
			$mistakeWords = $xpath->query( '//spellresult/c' );
			
			$i=0;
			$foundMistakeWords = array();
			foreach( $mistakeWords as $mistakeWord ){
				$foundMistakeWords[$i]['offset'] = $mistakeWord->getAttribute('o');
				$foundMistakeWords[$i]['length'] = $mistakeWord->getAttribute('l');
				$foundMistakeWords[$i]['confidenceLevel'] = $mistakeWord->getAttribute('s');
				$foundMistakeWords[$i]['suggestions'] = $mistakeWord->nodeValue;
				$i++;
			}
		} else {
			LogHandler::Log( 'Spelling', 'ERROR', 'Bad XML from Google: ['.htmlentities($response->getRawBody()).']' );
			$message = 'Google Web Spelling engine did not return valid response. '.
				'Please check ENTERPRISE_SPELLING setting in configserver.php.';
			throw new BizException( null, 'Server', null, $message );
		}

		return $foundMistakeWords;
	}

	/**
	 * Refer to abstract class getEngineVersion() header.
	 */
	public function getEngineVersion()
	{
		return '-';
	}
	
	/**
	 * Refer to abstract class getInstalledDictionaries() header.
	 */	
	public function getInstalledDictionaries()
	{
		return null;
	}
}
