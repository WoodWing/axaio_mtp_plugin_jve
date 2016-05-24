<?php
/****************************************************************************
   Copyright 2007-2009 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/
 
require_once BASEDIR . '/server/interfaces/plugins/connectors/PubPublishing_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class SMS_PubPublishing extends PubPublishing_EnterpriseConnector
{
	final public function getPrio()      { return self::PRIO_DEFAULT; }
	final public function getRunMode()   { return self::RUNMODE_SYNCHRON; }

	/**
	 * publishDossier
	 *
	 * Publishes a dossier with contained objects (articles and hyperlinks) to SMS.
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsInDossier
	 * @param PublishTarget $publishTarget
	 * @return array (always empty)
	 * @throws BizException when text becomes too long or when no text found or sending SMS fails.
	**/	
	final public function publishDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// Make code analyzer happy
		$dossier = $dossier;
		$publishTarget = $publishTarget;

		// Publish dossier
		$text = $this->getTextToSend( $objectsInDossier );
		$smsResult = $this->sendSMS( $text, $publishTarget ); 
		if( !empty( $smsResult ) ) {
			$msg = "Error sending SMS:\n$smsResult";
			throw new BizException( null, 'Client', null, $msg );
		}
		return array();
	}

	/**
	 * updateDossier
	 * 
	 * There is no way to updated an SMS that has been sent out already, so just resend
	 *
	 * @param writable Object $dossier
	 * @param writable array of Object $objectsInDossier
	 * @param PublishTarget $publishTarget
	 * @return array (always empty)
	**/	
	final public function updateDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		return $this->publishDossier( $dossier, $objectsInDossier, $publishTarget );
	}

	/**
	 * unpublishDossier
	 * 
	 * Raises an exception because there is no way to revoke SMS-es.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsInDossier
	 * @param PublishTarget $publishTarget
	 * @throws BizException (always)
	 */
	final public function unpublishDossier( $dossier, $objectsInDossier, $publishTarget )
	{
		// Make code analyzer happy
		$dossier = $dossier; 
		$objectsInDossier = $objectsInDossier; 
		$publishTarget = $publishTarget;
		
		// Once SMS is sent out, there is no way to remove it
		$msg = 'SMS cannot be unpublished, sorry...';
		throw new BizException( null, 'Client', null, $msg );
	}

	/**
	 * previewDossier
	 *
	 * Determines an URL to our preview application.
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsInDossier
	 * @param PublishTarget $publishTarget
	 * @return array of Field objects. Contains "URL" field that point to our preview application.
	 */
	final public function previewDossier( &$dossier, &$objectsInDossier, $publishTarget )
	{
		// Make code analyzer happy
		$dossier = $dossier;
		$publishTarget = $publishTarget;
		
		// Build URL to our preview application
		$lastslash	= strrpos( SERVERURL_SCRIPT, '/' );
		$url 		= substr( SERVERURL_SCRIPT, 0, $lastslash ) . '/config/plugins/SMS/Preview/PreviewSMS.php';
		$text 		= $this->getTextToSend( $objectsInDossier );
		$url	   .= '?text='.urlencode($text);
		
		$result = array();
		if (class_exists('PubField')){ // PubField only exists in v7.0+
			$result[] = new PubField('URL','string',array($url));
		} else { // v6.0
			$result[] = new Field('URL','string',array($url));
		}
		return $result;
	}
		
	/**
	 * requestPublishFields
	 * 
	 * There is nothing we can say after SMS has been sent, so return empty array
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsInDossier
	 * @param PublishTarget $publishTarget
	 * @return array (always empty)
	**/	
	final public function requestPublishFields( $dossier, $objectsInDossier, $publishTarget )
	{
		// Make code analyzer happy
		$dossier = $dossier; 
		$objectsInDossier = $objectsInDossier;
		$publishTarget = $publishTarget;
		
		// Nothing to say
		return array();
	}
	
	/**
	 * getDossierURL
	 * 
	 * SMS does not have URL, so return empty string
	 *
	 * @param Object $dossier
	 * @param array of Object $objectsInDossier
	 * @param PublishTarget $publishTarget
	 * 
	 * @return string
	 */	
	final public function getDossierURL( $dossier, $objectsInDossier, $publishTarget )
	{
		// Make code analyzer happy
		$dossier = $dossier; 
		$objectsInDossier = $objectsInDossier; 
		$publishTarget = $publishTarget;
		
		// There is no URL
		return '';
	}
	
	/**
	 * getTextToSend
	 * 
	 * Determines the SMS text to send from given objects (= child objects of a dossier).
	 * Those objects are marked by user for publishing through SMS channel.
	 * The function goes thru all children and collects all texts and hyperlinks.
	 *
	 * @param array of Object $objectsInDossier
	 * @return string SMS text to send.
	 * @throws BizException When there are objects of unsuported type.
	 */	
	private function getTextToSend( $objectsInDossier )
	{
		$text = '';
		$hyperlinks = array();
		
		// Go thru dossier's children and collects all texts and hyperlinks
		foreach ($objectsInDossier as $child) {
			switch ($child->MetaData->BasicMetaData->Type) {
				case 'Article':
					// take PlainContent property and strip leading/trailing spaces (and html codes, which is paranoid)
					$plainContent = trim( $child->MetaData->ContentMetaData->PlainContent );
					if( !empty( $plainContent ) ) {
						if( !empty( $text ) ) { // separate articles with line endings
							$text .= "\n";
						}
						$text .= $plainContent; // collect texts
					}
					break;
				case 'Hyperlink':
					// take DocumentID property (which holds hyperlinks) and stip leading/trailing spaces
					$documentID = trim( $child->MetaData->BasicMetaData->DocumentID );
					if( !empty( $documentID ) ) {
						$hyperlinks[] = $documentID; // collect hyperlinks
					}
					break;
				default:
					// We don't SMS images, audio, video and unknown
					'SMS error:\nObject of type "'.$child->MetaData->BasicMetaData->Type.'" is not supported.';
					throw new BizException( null, 'Client', null, $msg );
					break;
			}
		}
		
		// We have collect all content, append hyperlinks after text
		foreach( $hyperlinks as $hyperlink ) {
			if( !empty( $text ) ) { // separate hyperlinks with line endings
				$text .= "\n";
			}
			$text .= $hyperlink;
		}

		// No text is not valid
		if( empty( $text ) ) {
			$msg = "SMS error:\nThere is no text found at dossier.";
			throw new BizException( null, 'Client', null, $msg );
		}

		// Add footer to SMS message (only when configured)
		if( WWSMS_FOOTER != '' ) {
			$text .= "\n".WWSMS_FOOTER;
		}
		
		// If text is too large, bail out:
		if( strlen( $text ) > 160 ) {
			$maxBodyLen = 160 - strlen("\n".WWSMS_FOOTER);
			$msg = "SMS error:\nMessage too long.\nOnly $maxBodyLen charaters are allowed.\nMessage: \"".substr( $text, 0, 160) . '..."';
			throw new BizException( null, 'Client', null, $msg );
		}

		return $text;
	}
	
	/**
	 * Sends SMS using Mollie gateway
	 *
	 * @param string $sender  Name of sender.
	 * @param string $message Message to send, will be truncated if needed
	 * @return string result message. Empty on error.
	 */
	private function sendSMS( $message, $publishTarget )
	{
		require_once dirname(__FILE__) . '/class.mollie.php';
	
		$sms = new mollie();
		
		// Select gateway, set logon info and orginator, all from config.php
		$sms->setGateway	( WWSMS_GATEWAY );
		$sms->setLogin		( WWSMS_USERNAME, WWSMS_PASSWORD );
		
		// Get recipients and sende name from issue's description and subject, fallback defined in conif.php
		require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
		$issue = DBIssue::getIssue( $publishTarget->IssueID );
		$issueSubject = trim($issue['subject']);
		$issueDescription = trim($issue['description']);
		$recipients = !empty($issueDescription) ? $issueDescription : WWSMS_RECIPIENTS;
		$sender = !empty($issueSubject) ? $issueSubject : WWSMS_SENDER;

		$sms->setOriginator	( $sender );
		
		$phoneNumbers = explode( ' ', $recipients );
		foreach( $phoneNumbers as $phoneNumber ) {
			$sms->addRecipients( $phoneNumber );
		}
			
		// And send SMS:
		LogHandler::Log('SMS', 'INFO', 'SMS message about to send: "'.nl2br($message).'" by '.$sender.' to '.$recipients);
		$sms->sendSMS($message);
		
		if ($sms->success) {
			LogHandler::Log('SMS', 'INFO', 'SMS message sent to '.$sms->successcount.' number(s)!');
			return '';
		} else {
			LogHandler::Log('SMS', 'ERROR', 'Failed to send SMS<br/>Errorcode: '.$sms->resultcode.'<br/>Errormessage: '.$sms->resultmessage );
			$msg = "Error sending SMS:\n".$sms->resultmessage.' ('.$sms->resultcode.')';
			throw new BizException( null, 'Server', null, $msg );
		}
	}
}