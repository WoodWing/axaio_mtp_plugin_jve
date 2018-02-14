<?php
/**
 * @package 	Enterprise
 * @subpackage 	BizClasses
 * @since 		v4.2, refactored in v6.1
 * @copyright 	WoodWing Software bv. All Rights Reserved.
 */

use Zend\Mail;
use Zend\Mime;

require_once BASEDIR.'/server/utils/htmlclasses/HtmlDocument.class.php';

class BizEmail
{
	/**
	 * Send Email notification if this has been configured
	 *
	 * @param string $mode				Name of the action triggering this, not used.
	 * @param Object $object			Object for which to send notification
	 * @param string	$types	            Rendition types for this object (packed as serialized string)
	 * @param string $previousRouteTo	Short username or group of route-to before the action.
	 * 									if route-to has not changed we will not send email.
	 */
	public static function sendNotification( /** @noinspection PhpUnusedParameterInspection */ $mode,
												$object, $types, $previousRouteTo )
	{
		// Is there anything to email? Like email server setup, emailing turned on for publication
		if( !self::somethingToEmail( $object->MetaData->BasicMetaData->Publication->Id, $object->MetaData->WorkflowMetaData->RouteTo, $previousRouteTo ) ) {
			return;
		}

		// get convert MetaData object to flat array plus some extras
		$props = self::convertMetaToArray( $object );
		
		// generate list of emails to send, email subject and body
		$emails = self::getEmailRecipients( $props['RouteTo'] );
		$subject = self::generateEmailsSubject( $props );
		$file=''; $fileFormat='';
		$emailTxt = self::generateEmailBody( $object, $props, $types, $file, $fileFormat );
		
		// Setup email transport
		$transport = self::setupEmailTransport();

		// Get email and full name of user triggering the action, this will be the email sender.
		$senderEmail = EMAIL_SENDER_ADDRESS ? EMAIL_SENDER_ADDRESS : BizSession::getUserInfo('email');
		$senderName	 = EMAIL_SENDER_NAME ? EMAIL_SENDER_NAME : BizSession::getUserInfo('fullname');

		// Note: When the $senderEmail is empty we can NOT fall back to the user name (by filling in), 
		// because that is not a valid email address, some (strict?) SMTP servers will complain:
		//     "Domain name required for sender address".
		// Nevertheless, when the $senderEmail is empty, we just leave it that way and $mail->setFrom(...)
		// will take care; the $senderName will appear in the "From" field of the arrived email.
		// This works well, even for SMTP servers that complain in the situation described above.
		// Before v6.1, the EMAIL_SMTP_SENDER option was used to fill in the $senderEmail.
		// But, by using the user email or leaving it empty works well, so the option is obsoleted.

		if( $emails ) foreach( $emails as $email ) { // $email: array( email, full name, language )
			// Future enhancement: translate per email/user
			try {
				$message = new Mail\Message();
				$message->setEncoding( 'utf-8' );
				$message->addTo( $email[0], $email[1] ); // email, full name
				$message->setFrom( $senderEmail, $senderName ); // email, full name
				$message->setSubject( $subject );

				$body = new Mime\Message();
				$html = new Mime\Part();
				$html->setContent( $emailTxt );
				$html->setType( 'text/html' );
				$body->addPart( $html );

				if( !empty($file) ) {
					$image = new Mime\Part();
					$image->setContent( $file );
					$image->setType( $fileFormat );
					$image->setDisposition( Mime\Mime::DISPOSITION_INLINE );
					$image->setEncoding( Mime\Mime::ENCODING_BASE64 );
					$image->setId( 'previewthumb' );
					$body->addPart( $image );
				}

				$message->setBody( $body );
				if( !empty($file) ) {
					$message->getHeaders()->get( 'content-type' )->setType( Mime\Mime::MULTIPART_RELATED );
				}
				$transport->send( $message );
			} catch( Exception $e ) {
				LogHandler::Log( __CLASS__, 'ERROR', 'Error sending email to '.$email[0].', error:'.$e->getMessage() ); // $e->getMessage() is typically empty...
			}
		}
	}

	/**
	 * Send Email notifications for multiple objects at once if this has been configured.
	 *
	 * This function will bundle the objects that are changed per group or user.
	 * It will only send a notification when the RouteTo has been changed.
	 *
	 * @param MetaData[] $invokedObjects A list of MetaData objects that have been modified.
	 * @param array $objProps
	 * @param array $statuses
	 * @param array $categories
	 * @param integer $newCategoryId
	 * @param integer $newStateId
	 */
	public static function sendNotifications( $invokedObjects, $objProps, $statuses, $categories, $newCategoryId, $newStateId )
	{
		$newRouteTo = '';
		$routeObjects = array();

		if( isset( $objProps['standard']['RouteTo'] )){
			$newRouteTo = $objProps['standard']['RouteTo'];
		}

		foreach( $invokedObjects as $invokedObject ){
			if( self::somethingToEmail( $invokedObject->BasicMetaData->Publication->Id, $newRouteTo, $invokedObject->WorkflowMetaData->RouteTo ) ) {
				$routeObjects[] = $invokedObject;
			}
		}

		if( !$newRouteTo || !$routeObjects ){
			return; // there is nothing to mail
		}

		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$row = DBUser::getUser( $newRouteTo );
		if( $row ){
			$fullName = $row['fullname']; // routed to a user
		} else {
			$fullName = $newRouteTo; // routed to a group
		}

		$senderEmail = EMAIL_SENDER_ADDRESS ? EMAIL_SENDER_ADDRESS : BizSession::getUserInfo('email');
		$senderName	 = EMAIL_SENDER_NAME ? EMAIL_SENDER_NAME : BizSession::getUserInfo('fullname');

		$emailTxt = self::generateEmailBodyForMultipleObjects( $routeObjects, $objProps, $senderName, $statuses, $categories, $newCategoryId, $newStateId, $fullName );
		$params = array( count($routeObjects), $fullName, $senderName );
		$emailSubject = BizResources::localize( 'MULTI_FILE_EMAIL_SUBJECT', true, $params );

		$emails = self::getEmailRecipients( $newRouteTo );

		// Setup email transport
		$transport = self::setupEmailTransport();

		if( $emails ) foreach ( $emails as $email ) { // $email: array( email, full name, language )
			// Future enhancement: translate per email
			try {
				$message = new Mail\Message();
				$message->setEncoding( 'utf-8' );
				$message->addTo( $email[0], $email[1] ); // email, full name
				$message->setFrom( $senderEmail, $senderName ); // email, full name
				$message->setSubject( $emailSubject );

				$body = new Mime\Message();
				$html = new Mime\Part( $emailTxt );
				$html->setContent( $emailTxt );
				$html->setType( 'text/html' );
				$body->addPart( $html );

				$message->setBody( $body );
				$transport->send( $message );
			} catch( Exception $e ) {
				LogHandler::Log( __CLASS__, 'ERROR', 'Error sending email to ' . $email[0] . ', error:'.$e->getMessage() ); // $e->getMessage() is typically empty...
			}
		}
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
		$props['StateColor'] 		= '#'.$object->MetaData->WorkflowMetaData->State->Color;
		
		// Convert dates to display strings
		$props['Created']	= DateTimeFunctions::iso2date($props['Created']);
		$props['Modified'] = DateTimeFunctions::iso2date($props['Modified']);
		if( isset($props['Deadline'])) {
			$props['Deadline'] = DateTimeFunctions::iso2date($props['Deadline']);
		}
		
		// Translate object type to correct language:
		$objTypeMap = getObjectTypeMap();
		$props['Type'] = $objTypeMap[$props['Type']];

		$issues = array();
		if( $object->Targets) foreach( $object->Targets as $target ) { // Get all the object target issue
			$issues[] = $target->Issue->Name;
		}

		if( $object->Relations ) { // Get object relation target issue
			require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
			$maxRelationTargets = 0; // 0 - Get all relation target issues
			$countRelationTargets = count($issues); // Starting number of relation targets
			if( BizRelation::manifoldPlacedChild($object->MetaData->BasicMetaData->ID) ) {
				$maxRelationTargets = 2; // When it is manifold placed child, set a maximum no of issues to be retrieve
			}
			foreach( $object->Relations as $relation ) {
				if( $relation->Targets ) foreach( $relation->Targets as $relationTarget ) {
					if( !in_array($relationTarget->Issue->Name, $issues) ) {
						$issues[] = $relationTarget->Issue->Name;
						if( $maxRelationTargets ) {
							if( $countRelationTargets < $maxRelationTargets ) {
								$countRelationTargets += 1;
							} else {
								$issues[] = '...';
								break 2; // Break after getting maximum relation target issues
							}
						}
					}
				}
			}
		}
		$props['Issues'] = implode(',',$issues);
		
		return $props;
	}
	
	/**
	 * Gets preview or thumbnail rendition for object. Preview is scaled down to 405 pixels
	 *
	 * @param string $objId Object id to get
	 * @param string $rendition	Rendition to get: thumb or preview
	 * @param string $format Format of the rendition
	 * @return string
	 */
	private static function getFile( $objId, $rendition, $format )
	{
		require_once BASEDIR.'/server/bizclasses/BizStorage.php';
		require_once BASEDIR.'/server/bizclasses/BizQuery.class.php';
		$objectProps = BizQuery::queryObjectRow($objId );
		$attachment = BizStorage::getFile($objectProps, $rendition, $objectProps['Version'] );

		require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$filedata = $transferServer->getContent($attachment);
		// We scale preview down:
		if ($rendition == 'preview') {
			require_once BASEDIR.'/server/utils/ImageUtils.class.php';
			$preview = '';
			switch( $format ) {
				case 'image/gif':
				case 'image/png':
				case 'image/x-png':
					$ret = ImageUtils::ResizePNG( 405, 			// max for both width/height
												  $filedata,	null, 	// no file, but buffer
												  null, null, 		// height, width max
												  $preview 		// output 
											   );
					break;
				case 'image/jpeg':
				case 'image/pjpeg':
				case 'image/jpg':
				default:
					$ret = ImageUtils::ResizeJPEG( 405, 			// max for both width/height
												   $filedata,	null, 	// no file, but buffer
												   80, 				// quality
												   null, null, 		// height, width max
												   $preview 		// output 
											   );
					break;
			}
			if( $ret ) {
				return $preview;
			}
		} 
		return $filedata;
	}
	
	/**
	 * Gets list of all email recipients. In case of a group this is all the users of the group that have
	 * group email enabled.
	 *
	 * @param string $routeTo
	 * @return array Info list where list [0] email, [1] full name, [2] language
	 */
	private static function getEmailRecipients( $routeTo )
	{
		$emails = array();
		require_once BASEDIR . '/server/dbclasses/DBUser.class.php';
		$row = DBUser::getUser( $routeTo );
		if( $row ) {
			if( trim( $row['email'] ) && !trim( $row['disable'] ) && trim( $row['emailusr'] ) ) {
				if( array_key_exists( 'language', $row ) ) {
					$emails[] = array( $row['email'], $row['fullname'], $row['language'] );
				} else {
					if( BizSettings::isFeatureEnabled( 'CompanyLanguage' ) ) {
						$emails[] = array( $row['email'], $row['fullname'], BizSettings::getFeatureValue( 'CompanyLanguage' ) );
					} else {
						$emails[] = array( $row['email'], $row['fullname'], 'enUS' );
					}
				}
			}
		} else {
			$sth = DBUser::getGroupMembers( $routeTo );
			if( $sth ) {
				$dbDriver = DBDriverFactory::gen();
				while( ( $row = $dbDriver->fetch( $sth ) ) ) {
					if( trim( $row['email'] ) && !trim( $row['disable'] ) && trim( $row['emailgrp'] ) ) {
						if( array_key_exists( 'language', $row ) ) {
							$emails[] = array( $row['email'], $row['fullname'], $row['language'] );
						} else {
							if( BizSettings::isFeatureEnabled( 'CompanyLanguage' ) ) {
								$emails[] = array( $row['email'], $row['fullname'], BizSettings::getFeatureValue( 'CompanyLanguage' ) );
							} else {
								$emails[] = array( $row['email'], $row['fullname'], 'enUS' );
							}
						}
					}
				}
			}
		}

		return $emails;
	}
	
	/**
	 * Generate email subject, comes from resources with BizProp translations
	 *
	 * @param array $props BizProps to insert into subject
	 * @return string
	 */
	private static function generateEmailsSubject( $props )
	{
		// Generate email subject
		$subject = BizResources::localize( 'EMAIL_SUBJECT' );

		$vars = array();
		preg_match_all( "/%(.*?)%/", $subject, $vars, PREG_SET_ORDER );
		foreach( $vars as $var ) {
			if( isset( $props[$var[1]] ) ) {
				$subject = str_replace( "%" . $var[1] . "%", $props[$var[1]], $subject );
			}
		}
		return $subject;
	}
	
	/**
	 * Generate email body, comes from file that can be object type specific
	 * Resource strings can be referenced which are replaced as well as BizProps
	 * Also inserts thumb or preview if this is referenced in email template.
	 *
	 * @param Object	$object		Object that we're emailing about
	 * @param array		$props		BizProps to insert into body
	 * @param string		$types		Types for the preview/thumb to embed (packed as serialized string)
	 * @param string	$file	    In/out Thumb or preview file
	 * @param string		$fileFormat	Format of the file
	 * @return string Email html body
	 */
	private static function generateEmailBody( $object, $props, $types, &$file, &$fileFormat )
	{
		// Generate email body, replacing resource strings, metadata keys plus thumb/preview image
		
		// First see if we have object type specific template. If not, use generic
		// Not: $props['Type'] contains localized type, so is not usable.
		if( file_exists( BASEDIR.'/config/templates/email_'.$object->MetaData->BasicMetaData->Type.'.htm' ) ){
			$emailTxt = file_get_contents( BASEDIR.'/config/templates/email_'.$object->MetaData->BasicMetaData->Type.'.htm' );
		} else {
			$emailTxt = file_get_contents( BASEDIR.'/config/templates/email.htm' );
		}

		$emailTxt = HtmlDocument::buildDocument( $emailTxt, false );

		$vars = array();
		preg_match_all("/%(.*?)%/", $emailTxt, $vars, PREG_SET_ORDER);
		foreach ($vars as $var) {
			if( empty($file) && ( $var[1]=='preview' || $var[1]=='thumb') ) {
				$types = unserialize($types);
				if( isset($types[$var[1]])) {
					$fileFormat	= $types[$var[1]];
					$file = self::getFile( $props['ID'], $var[1], $fileFormat );
				}
			} elseif( $var[1]=='Messages' ) {
				// Read object messages backward (newest first) and show up to 5 messages
				require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
				require_once BASEDIR.'/server/utils/DateTimeFunctions.class.php';
				$messageList = BizMessage::getMessagesForObject( $object->MetaData->BasicMetaData->ID );
				$msgText = '';
				$totalMessages = count( $messageList->Messages );
				if( $totalMessages > 0 ) {
					for( $i=$totalMessages-1; $i >= 0 && $i >= $totalMessages-5; --$i ) {
						$msgText .= '<i>'.DateTimeFunctions::iso2date($messageList->Messages[$i]->TimeStamp).'</i>';
						$msgText .= ' &mdash; '. $messageList->Messages[$i]->Message . '<br/>';
					}
				}
				$emailTxt = str_replace("%".$var[1]."%", $msgText, $emailTxt);
			} elseif (isset($props[$var[1]])) {
				if( is_array($props[$var[1]]) ) {
					$emailTxt = str_replace("%".$var[1]."%", implode(', ',$props[$var[1]]), $emailTxt);
				} else {
					$emailTxt = str_replace("%".$var[1]."%", $props[$var[1]], $emailTxt);
				}
			} elseif( $var[1] == 'server_inetroot' ) {
				// URL to login at Content Station Web (BZ#1787 / BZ#10515)
				$emailTxt = str_replace('%server_inetroot%', SERVERURL_ROOT.INETROOT, $emailTxt);
			} else { // when property is not set, remove the %Prop% key from email:
				$emailTxt = str_replace("%".$var[1]."%", '', $emailTxt);
			}
		}
		if( !empty($file) ) {
			$emailTxt = str_replace('%thumb%', '<br/><img src="cid:previewthumb" class="image"/>', $emailTxt);
			$emailTxt = str_replace('%preview%', '<br/><img src="cid:previewthumb" class="image"/>', $emailTxt);
		} else {
			$emailTxt = str_replace('%thumb%', '', $emailTxt);
			$emailTxt = str_replace('%preview%', '', $emailTxt);
		}
		
		return $emailTxt;
	}

	/**
	 * Generate email body for multiple objects.
	 * If the category and/or status is changed this function will also update those.
	 * If the comment is set for all invoked objects, that comment is used. If not, the comment already set on
	 * the invoked objects is used in the email.
	 *
	 * @param Object[] $invokedObjects Objects that we're emailing about
	 * @param array $objProps The changed data
	 * @param string $senderName The name of the person changing the objects
	 * @param array $statuses all the existing statuses
	 * @param array $categories all the existing categories
	 * @param int $newCategoryId the new category id that is set in the multiset
	 * @param int $newStateId the new state/status id that is set in the multiset
	 * @param string $routeToFullName the full name of the routeTo, this can be a user or a group
	 * @return string Email the html body for the email
	 */
	private static function generateEmailBodyForMultipleObjects( $invokedObjects, $objProps,
		$senderName, $statuses, $categories, $newCategoryId, $newStateId, $routeToFullName  )
	{
		$emailTxt = file_get_contents( BASEDIR . '/config/templates/emailMultipleObjects.htm' );
		$emailTxt = HtmlDocument::buildDocument( $emailTxt, false );
		$count = 1;
		$comment = '';
		$objectRowsText = '';
		$newCategory = null;
		$state = null;

		if( isset( $objProps['standard']['Comment'] )){
			$comment = $objProps['standard']['Comment'];
		}

		if( $newStateId ) foreach( $statuses as $status ){
			if( $newStateId == $status->Id ){
				$state = $status;
				break;
			}
		}

		if( $newCategoryId ) {
			$categoryObj = $categories[$newCategoryId];
			$newCategory = $categoryObj->Name;
		}

		foreach( $invokedObjects as $object ){
			if( !$newCategory ){
				$categoryName = $object->BasicMetaData->Category;
				$objectCategory = $categoryName->Name;
			}else{
				$objectCategory = $newCategory;
			}

			if ( $newStateId == '-1' ) {
				$objectStatusColor = PERSONAL_STATE_COLOR;
				$objectStatusName = BizResources::localize('PERSONAL_STATE', true, null);
			} elseif( $state ){
				$objectStatusColor = $state->Color;
				$objectStatusName = $state->Name;
			} else{
				$objectState = $object->WorkflowMetaData->State;
				$objectStatusName = $objectState->Name;
				$objectStatusColor = '#' . $objectState->Color;
			}

			$objectStatus = '<table cellspacing="0" cellpadding="0" style="font:13px arial, sans-serif; border-collapse: collapse;">
				<tr><td><table border="1" style="border-collapse: collapse; border:1px solid #606060;  height:10px; width:10px;">
				<tr><td style="background-color: ' . $objectStatusColor . ';"></td></tr></table></td><td>&nbsp;' . $objectStatusName . '</td>
				</tr></table>';

			$rowText = '<td valign="top">'.$object->BasicMetaData->Name.'</td>';
			$rowText .= '<td valign="top">'.$object->BasicMetaData->Publication->Name.'</td>';
			$rowText .= '<td valign="top">'.$objectCategory.'</td>';
			$rowText .= '<td valign="top">'.$objectStatus.'</td>';
			$rowText .= '<td valign="top">'.self::resolveModifierFromObject( $object ).'</td>';
			$rowText .= '<td valign="top">'.$object->WorkflowMetaData->Modified.'</td>';
			$rowText .= '<td valign="top">'.( !empty( $comment ) ? $comment : $object->WorkflowMetaData->Comment ).'</td>';

			if( ($count % 2) == 0 ){ // this is used for the row styling
				$objectRowsText .= '<tr class="even">' . $rowText . '</tr>';
			} else{
				$objectRowsText .= '<tr class="odd">' . $rowText . '</tr>';
			}

			$count++;
		}

		$params = array( $routeToFullName, $senderName );
		$emailHeader = BizResources::localize('EMAIL_HEADER', true, $params);

		$emailTxt = str_replace( '%Header%', $emailHeader, $emailTxt );
		$emailTxt = str_replace( '%Comment%', $comment, $emailTxt );
		$emailTxt = str_replace( '%server_inetroot%', SERVERURL_ROOT.INETROOT, $emailTxt );
		$emailTxt = str_replace( '%changedObjectRows%', $objectRowsText, $emailTxt );

		return $emailTxt;
	}

    private function resolveModifierFromObject( $object )
    {
        require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
        $modifier = '';
        if( !empty( $object->WorkflowMetaData->Modifier ) ) {
            $modifier = BizUser::resolveFullUserName( $object->WorkflowMetaData->Modifier );
        }

        return $modifier;
    }

	/**
	 * Figures out if there is something to email
	 *
	 * @param integer $pubId The id of the publication.
	 * @param string $routeTo
	 * @param string	$previousRouteTo	RouteTo before the action, we only email if it has changed
	 * @return boolean whether we should email
	 */
	private static function somethingToEmail( $pubId, $routeTo, $previousRouteTo )
	{
		// Nothing to do if email server not set
		if (!EMAIL_SMTP) return false;

		require_once BASEDIR.'/server/bizclasses/BizUser.class.php';
		// Nothing to do if Pub or RouteTo not set or if route-to
		// is not changing
		if (!$pubId || !$routeTo || $routeTo == BizUser::resolveFullUserName($previousRouteTo) ) {
			LogHandler::Log( __CLASS__, 'DEBUG', "No need to send email ($pubId, $routeTo, $previousRouteTo)" );
			return false;
		}

		// check if we need notifications for this publication
		require_once BASEDIR.'/server/dbclasses/DBPublication.class.php'; 
		$sth = DBPublication::listPublicationsByNameId( null, $pubId );
		$dbDriver = DBDriverFactory::gen();
		$row = $dbDriver->fetch($sth);
		if (!$row || !trim($row['email'])) {
			return false; // Email turned off for this publication.
		}

		return true;
	}

	/**
	 * Setup Zend email transport using variables from config file
	 *
	 * @return Zend\Mail\Transport\Smtp|null
	 */
	private static function setupEmailTransport()
	{
		$transport = null;
		try {
			$options = new Mail\Transport\SmtpOptions();
			$options->setHost( EMAIL_SMTP );
			$emailPort = EMAIL_PORT;
			if( !empty($emailPort) ) {
				$options->setPort( EMAIL_PORT );
			}
			$config = array();
			$smtp = EMAIL_SMTP_USER;
			if( !empty($smtp) ) {
				$options->setConnectionClass( 'login' );
				$config['username'] = EMAIL_SMTP_USER;
				$config['password'] = EMAIL_SMTP_PASS;
			}
			$emailSSL = EMAIL_SSL;
			if( !empty($emailSSL) ) {
				$config['ssl'] = EMAIL_SSL;
			}
			if( $config ) {
				$options->setConnectionConfig( $config );
			}
			$transport = new Mail\Transport\Smtp( $options );
		} catch( Exception $e ) {
			LogHandler::Log( __CLASS__, 'ERROR', 'Failed to setup SMTP transport: '.$e->getMessage() );
		}
		
		return $transport;
	}

	/**
	 * Sends separate emails with attachments to passed list of email addresses.
	 * @param array 	$emailTo List with email address to send the email to.
	 * @param string 	$emailTxt text for email body.
	 * @param string 	$subject subject of the email.
	 * @param array 	$sender From address
	 * @param array 	$attachments list of files to be added as attachment.
	 * @return boolean	True in the case of success. Success means all emails are send.
	 */
	public static function sendMail( $emailTo, $emailTxt, $subject, $sender, $attachments )
	{
		$transport = self::setupEmailTransport();
		$result = true;
		if( $emailTo ) foreach( $emailTo as $email ) { // $email: array( email, full name )
			try {
				$message = new Mail\Message();
				$message->setEncoding( 'utf-8' );
				$message->addTo( $email['address'], $email['fullname'] ); // email, full name
				$message->setFrom( $sender['address'], $sender['fullname'] ); // email, full name
				$message->setSubject( $subject );

				$body = new Mime\Message();
				foreach( $attachments as $attachment ) {
					$part = new Mime\Part();
					$part->setContent( $attachment['content'] );
					$part->setType( $attachment['format'] );
					$part->setDisposition( Mime\Mime::DISPOSITION_ATTACHMENT );
					$part->setEncoding( Mime\Mime::ENCODING_BASE64 );
					$part->setFileName( $attachment['filename'] );
					$body->addPart( $part );
				}
				$html = new Mime\Part();
				$html->setContent( $emailTxt );
				$html->setType( 'text/html' );
				$body->addPart( $html );

				$message->setBody( $body );
				$message->getHeaders()->get( 'content-type' )->setType( Mime\Mime::MULTIPART_MIXED );
				$transport->send( $message );

			} catch( Exception $e ) {
				LogHandler::Log( __CLASS__, 'ERROR', 'Error sending email to ' . $email['address'] . ', error:' . $e->getMessage() ); // $e->getMessage() is typically empty...
				$result = false;
			}
		} else {
			$result = false;
		}

		return $result;
	}
	
	/**
	 * Generic Send Email function where SMTP configuration is checked / settled here.
	 * Can specify more than one recipient under $tos: "emailAddress:Recipient Name"
	 * i.e $tos = array(
	 * 		"abc@woodwing.com" => "Mr. ABC",
	 * 		"xyz@woodwing.com" => "Ms. xyz"
	 * 	)
	 *
	 * @param string $from
	 * @param string $fromFullName
	 * @param array $tos
	 * @param string $subject
	 * @param string $content
	 * @return bool True when email sucessfully sent, False otherwise.
	 */
	public static function sendEmail( $from, $fromFullName, $tos, $subject, $content )
	{
		if( !$from || is_null($tos) ){
			LogHandler::Log( __CLASS__, 'ERROR', __METHOD__.':: No Sender or Recipients Email' );
			return false;
		}
		// Setup email transport
		$transport = self::setupEmailTransport();
		
		if( $transport ){
			foreach ($tos as $to => $toFullName ) { 
				// Future enhancement: translate per email/user
				try{
					$message = new Mail\Message();
					$message->setEncoding( 'utf-8' );
					$message->addTo( $to, $toFullName ); // email, full name
					$message->setFrom( $from, $fromFullName ); // email, full name
					$message->setSubject( $subject );

					$body = new Mime\Message();
					$html = new Mime\Part();
					$html->setContent( $content );
					$html->setType( 'text/html' );
					$body->addPart( $html );

					$message->setBody( $body );
					$transport->send( $message );

				} catch( Exception $e ) {
					LogHandler::Log( __CLASS__, 'ERROR', 'Error sending email to '. $to .', error:'.$e->getMessage() ); // $e->getMessage() is typically empty...
				}
			}
			return true;
		}
		return false;
	}
}
