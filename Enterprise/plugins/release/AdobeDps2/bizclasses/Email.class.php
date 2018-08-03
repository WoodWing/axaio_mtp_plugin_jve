<?php
/**
 * @since 		v9.6
 * @copyright	WoodWing Software bv. All Rights Reserved.
 *
 * Handles email that should be sent out to the end-user when there's error in the Adobe DPS upload operations.
 */
class AdobeDps2_BizClasses_Email
{
	/**
	 * Sends Email to the acting user when error occurred during Adobe DPS uploads.
	 *
	 * @param string $layoutId
	 * @param string $errorMessage
	 */
	public static function sendEmail( $layoutId, $errorMessage )
	{
		if( self::isEmailEnabled() ) {
			require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
			$object = BizObject::getObject( $layoutId, BizSession::getShortUserName(), false, 'preview', null, null, true );
			$props = self::convertMetaToArray( $object );

			$emailSubject = self::generateEmailSubject( $props, $object );
			$file = ''; $fileFormat='';
			$emailBody = self::generateEmailBody( $file, $fileFormat, $emailSubject, $props, $object, $errorMessage );

			// Setup email transport
			$transport = self::setupEmailTransport();

			$userEmail = defined('EMAIL_SENDER_ADDRESS') && EMAIL_SENDER_ADDRESS ? EMAIL_SENDER_ADDRESS : BizSession::getUserInfo('email');
			$userFullName = defined('EMAIL_SENDER_NAME') && EMAIL_SENDER_NAME ? EMAIL_SENDER_NAME : BizSession::getUserInfo('fullname');

			if( $userEmail && $transport ) {
				try {
					$mail = new Zend_Mail('utf-8');
					$mail->addTo( $userEmail, $userFullName );
					$mail->setFrom( $userEmail, $userFullName );
					$mail->setSubject( $emailSubject );
					if( !empty($file) ) {
						$img = $mail->createAttachment( $file, $fileFormat,
							Zend_Mime::DISPOSITION_INLINE,
							Zend_Mime::ENCODING_BASE64 );
						$img->id 	= 'previewthumb';
						$mail->setType(Zend_Mime::MULTIPART_RELATED);
					}
					$mail->setBodyHtml( $emailBody );
					$mail->send( $transport );
				} catch( Zend_Exception $e ) {
					LogHandler::Log('AdobeDps2', 'ERROR', 'Error sending email to '.$userEmail.', error:'.$e->getMessage() ); // $e->getMessage() is typically empty...
				}
			} else {
				LogHandler::Log( 'AdobeDps2', 'ERROR', 'Failed sending email, user \''.
								BizSession::getShortUserName().'\' has no email configured.' );
			}
		} else {
			LogHandler::Log( 'AdobeDps2', 'ERROR', 'Failed sending email. Email feature is not enabled, please run the Health Check.' );
		}
	}

	/**
	 * Compose the Email subject based on the template.
	 *
	 * Function retrieves the content from a email_subject template and replace the variables
	 * with the corresponding value of the Layout object. The updated content is returned
	 * to the caller.
	 *
	 * @param array $props
	 * @param Object $object
	 * @return string
	 */
	private static function generateEmailSubject( $props, $object )
	{
		// Email content Title. (Also used as Email Subject)
		$emailSubject = file_get_contents( dirname(__FILE__).'/../templates/email_subject.txt' );
		$emailSubject = self::replaceConfigKeys( $emailSubject );
		$previewInfo = array();
		self::replaceTemplateVariables( $emailSubject, $props, $object, $previewInfo );
		$emailSubject = str_replace( "<!--VAR:ERROR-->", BizResources::localize( 'AdobeDps2.ERROR_SERVER_ERROR' ), $emailSubject );

		return $emailSubject;
	}

	/**
	 * Composed the Email body to be sent out.
	 *
	 * Retrieves the email template and replace the relevant information about the layout of which
	 * the error has occurred during uploading to Adobe DPS.
	 *
	 * @param string $file
	 * @param string $fileFormat
	 * @param string $emailSubject
	 * @param array $props
	 * @param Object $object
	 * @param string $errorMessage
	 * @return string
	 */
	private static function generateEmailBody( &$file, &$fileFormat, $emailSubject, $props, $object, $errorMessage )
	{
		require_once BASEDIR . '/server/utils/htmlclasses/HtmlDocument.class.php';
		$emailBody = file_get_contents( dirname(__FILE__).'/../templates/email_body.htm' );
		$emailBody = HtmlDocument::buildDocument( $emailBody, false );

		// @TODO: To add the Published dates into the email body.
//		$tmpPublishedDates = array( '2014-10-10T12:00:42', '2014-10-13T14:50:42', '2014-10-17T09:50:42' );
//		$msgText = '';
//		foreach( $tmpPublishedDates as $tmpPublishedDate ) {
//			$msgText .= '<i>'.$tmpPublishedDate.'</i><br/>';
//		}
//		$emailBody = str_replace( "%PublishedDates%", $msgText, $emailBody );

		$previewInfo = array();
		self::replaceTemplateVariables( $emailBody, $props, $object, $previewInfo );
		if( $previewInfo ) {
			$file = $previewInfo['file'];
			$fileFormat = $previewInfo['fileFormat'];
		}

		// Preview content in the Email body.
		if( !empty($file) ) {
			$emailBody = str_replace('%thumb%', '<br/><img src="cid:previewthumb" class="image"/>', $emailBody);
			$emailBody = str_replace('%preview%', '<br/><img src="cid:previewthumb" class="image"/>', $emailBody);
		} else {
			$emailBody = str_replace('%thumb%', '', $emailBody);
			$emailBody = str_replace('%preview%', '', $emailBody);
		}

		// Email content Title. (Also used as Email Subject)
		$emailBody = str_replace( "<!--VAR:SUBJECT-->", $emailSubject, $emailBody );

		// Substitute the Acting User.
		$emailBody = str_replace( "<!--VAR:ACTING_USER-->", BizSession::getUserInfo('fullname'), $emailBody );

		// Email content error details.
		$emailBody = str_replace( "<!--VAR:ERROR_DETAILS-->", $errorMessage, $emailBody );

		return $emailBody;
	}

	/**
	 * Replaces the variables that has %variable% with the corresponding value.
	 *
	 * @param string $emailContent [In/Out]
	 * @param array $props
	 * @param Object $object
	 * @param array $previewInfo [In/Out]
	 */
	private static function replaceTemplateVariables( &$emailContent, $props, $object, &$previewInfo )
	{
		$vars = array();
		preg_match_all("/%(.*?)%/", $emailContent, $vars, PREG_SET_ORDER);

		if( $vars ) foreach ( $vars as $var ) {
			if( $var[1]=='preview' ) {
				$types = BizObject::serializeFileTypes( $object->Files );
				$types = unserialize($types);

				if( isset($types[$var[1]])) {
					$fileFormat	= $types[$var[1]];
					$attachment = $object->Files[0]; // Only preview.
					$previewInfo['fileFormat'] = $fileFormat;
					$previewInfo['file'] = self::getPreviewFile( $fileFormat, $attachment );
				}
			} elseif (isset($props[$var[1]])) {
				if( is_array($props[$var[1]]) ) {
					$emailContent = str_replace("%".$var[1]."%", implode(', ',$props[$var[1]]), $emailContent) ;
				} else {
					$emailContent = str_replace("%".$var[1]."%", $props[$var[1]], $emailContent );
				}
			} else { // when property is not set, remove the %Prop% key from email:
				$emailContent = str_replace("%".$var[1]."%", '', $emailContent );
			}
		}
	}

	/**
	 * Replaces all resource keys with localized resource strings in given text contents.
	 *
	 * The resource keys must have "<!--RES:[KEY]-->" pattern wherein [KEY] is the key name.
	 * Each key is looked up in the config/configlang.php file and in the config/resources files.
	 *
	 * @param string $txt Contents that may contain resource keys.
	 * @return string Contents with localized resource strings.
	 */
	private static function replaceConfigKeys( $txt )
	{
		// show configurable UI terms (pub/iss/sec/status/edition) by replacing CONFIG keys
		$docKeys = array();
		if( preg_match_all( '<!--RES:([a-zA-Z0-9_\.]*)-->', $txt, $docKeys ) ) {
			$uiTerms = BizResources::getConfigTerms();
			$docKeys = array_unique( $docKeys[1] );
			foreach( $docKeys as $docKey ) {
				if( array_key_exists( $docKey, $uiTerms ) ) {
					$localized = $uiTerms[$docKey];
				} else {
					$localized = BizResources::localize( $docKey );
				}
				$txt = str_replace('<!--RES:'.$docKey.'-->', $localized, $txt );
			}
		}
		return $txt;
	}

	/**
	 * Convert MetaData to flat array with BizProps (like Publication, PublicationId, see BizProperty)
	 * Also translates some values (dates, object types) to display values and adds status color and Issues.
	 *
	 * @param Object $object
	 * @return array
	 */
	private static function convertMetaToArray( $object )
	{
		require_once BASEDIR . '/server/bizclasses/BizProperty.class.php';
		require_once BASEDIR . '/server/utils/DateTimeFunctions.class.php';

		// Convert MetaData to flat array of BizProps and add some special values/conversions:
		$props = BizProperty::metaDataToBizPropArray( $object->MetaData );
		$props['StateColor'] = '#'.$object->MetaData->WorkflowMetaData->State->Color;

		// Convert dates to display strings
		$props['Created']	= DateTimeFunctions::iso2date($props['Created']);
		$props['Modified']	= DateTimeFunctions::iso2date($props['Modified']);

		// Translate object type to correct language:
		$objTypeMap = getObjectTypeMap();
		$props['Type']		= $objTypeMap[$props['Type']];

		$issues = array();
		if( $object->Targets) foreach( $object->Targets as $target ) { // Get all the object target issue
			$issues[] = $target->Issue->Name;
		}
		$props['Issues'] = implode(',',$issues);

		return $props;
	}

	/**
	 * Gets preview rendition for object.
	 *
	 * The Preview is scaled down to 405 pixels.
	 *
	 * @param string $format Format of the rendition
	 * @param Attachment $attachment Attachment file of the preview to be re-sized.
	 * @return string The scaled down Preview, original size of Preview if the scaling wasn't successful.
	 */
	private static function getPreviewFile( $format, $attachment )
	{
		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$fileData = $transferServer->getContent($attachment);

		// Scale down the preview.
		require_once BASEDIR.'/server/utils/ImageUtils.class.php';
		$resizedPreview = '';
		switch( $format ) {
			case 'image/gif':
			case 'image/png':
			case 'image/x-png':
				$ret = ImageUtils::ResizePNG( 300, // max for both width/height
					$fileData,	null, 	// no file, but buffer
					null, null, 		// height, width max
					$resizedPreview 		// output
				);
				break;
			case 'image/jpeg':
			case 'image/pjpeg':
			case 'image/jpg':
			default:
				$ret = ImageUtils::ResizeJPEG( 300, 			// max for both width/height
					$fileData,	null, 	// no file, but buffer
					100, 				// quality
					null, null, 		// height, width max
					$resizedPreview 		// output
				);
				break;
		}

		return $ret ? $resizedPreview : $fileData;
	}

	/**
	 * Setup Zend email transport using variables from config file
	 *
	 * @return Zend_Mail_Transport_Smtp|null
	 */
	private static function setupEmailTransport()
	{
		// Create and set mail transport
		require_once BASEDIR."/server/ZendFramework/library/Zend/Mail.php";
		require_once BASEDIR."/server/ZendFramework/library/Zend/Mail/Transport/Smtp.php";
		$smtp = EMAIL_SMTP_USER;
		$config = array();
		if( !empty($smtp) ) {
			$config['auth'] 	= 'login';
			$config['username']	= EMAIL_SMTP_USER;
			$config['password']	= EMAIL_SMTP_PASS;
		}
		$emailPort = EMAIL_PORT;
		if( !empty($emailPort) ) {
			$config['port'] = EMAIL_PORT;
		}
		$emailSSL = EMAIL_SSL;
		if( !empty($emailSSL) ) {
			$config['ssl'] = EMAIL_SSL;
		}
		$transport = null;
		try{
			$transport = new Zend_Mail_Transport_Smtp(EMAIL_SMTP, $config);
		} catch( Exception $e ) {
			LogHandler::Log( 'AdobeDps2', 'ERROR', 'Failed to setup SMTP transport: '.$e->getMessage() );
		}

		return $transport;
	}

	/*
	 * Checks if the Email feature is enabled.
	 *
	 * Function checks if the option 'EMAIL_SMTP' is configured in configserver.php,
	 * when it is set, the email feature is considered to be enabled.
	 *
	 * @return bool Returns true when the email feature is enabled, false otherwise.
	 */
	public static function isEmailEnabled()
	{
		return EMAIL_SMTP ? true : false;
	}
}
