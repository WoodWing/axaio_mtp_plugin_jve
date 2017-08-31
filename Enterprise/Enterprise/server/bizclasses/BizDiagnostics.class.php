<?php
/**
 * @package 		Enterprise
 * @subpackage 	BizClasses
 * @since 			v7.6.6
 * @copyright		WoodWing Software bv. All Rights Reserved.
 *
 * Business logics for the diagnostics services.
 *  
 */

class BizDiagnostics
{
	/**
	 * Handles the diagnostics send by the client applications.
	 *
	 * @param string $category Category of diagnostic (user/system generated)
	 * @param string $synopsis Summary of the diagnostics.
	 * @param string $description more info about the circumstances etc.
	 * @param array $attachments List of attachments like screen shot, log files etc.
	 * @return array List of info with the operation status (Success/Failure)
	 */
	public static function handle ( $category, $synopsis, $description, array $attachments )
	{
		// At this moment all diagnostics are handled by sending emails.
		$succes = self::send($category, $synopsis, $description, $attachments);

		if ( $succes ) {
			$result = array( 'status' => 'Success', 'detail' => '');
		} else {
			$result = array( 'status' => 'Failure', 'detail' => 'Failed to send the diagnostic report.'  );
		}	

		return $result;
	}	
	
	/**
	 * Sends the diagnostics to the configured email addresses. Email subject, text are
	 * created and attachments (if any) are added. Also some customer info is added. 
	 *
	 * @param string $category Category of diagnostic (user/system generated)
	 * @param string $synopsis Summary of the diagnostics.
	 * @param string $description more info about the circumstances etc.
	 * @param array $attachments List of attachments like screen shot, log files etc.
	 * @return bool Whether or not the email was sent successfully
	 */
	private static function send( $category, $synopsis, $description,  array $attachments )
	{
		require_once BASEDIR.'/server/bizclasses/BizEmail.class.php';
		require_once BASEDIR.'/server/dbclasses/DBConfig.class.php';
		$logMessage = 'Received diagnostic report. ';

		$clientInfoRows = DBConfig::getContactInfo();
		$customer = 'Unknown Customer'; 
		$country = 'Unknown Country'; 
		foreach ( $clientInfoRows as $infoRow ) {
			if ( $infoRow['name'] == 'contactinfo_company' ) {
				$customer = empty($infoRow['value']) ? $customer : $infoRow['value']; 
			} elseif ( $infoRow['name'] == 'contactinfo_country' ) {	
				$country = empty($infoRow['value']) ? $country : $infoRow['value']; 
			}	
		}
		
		$emailTo = unserialize( DIAGNOSTICS_EMAIL_TO );
		$sender = unserialize( DIAGNOSTICS_EMAIL_FROM );
		$emailText = $description;
		$subject = $customer.'/'.$country.'/'.$category; // These fields are mandatory
		if ( $synopsis ) { $subject .= '/'.$synopsis; }
		$logMessage .= "Subject: $subject. Description: " . substr( $description, 0, 40) . '. ';
		$mailAttachments = array();
		if ( $attachments ) {
			$mailAttachments = self::createAttachments( $attachments, $logMessage );
		}
		
		// Add general server information.
		$phpInfoAttachment = array();
		$phpInfoAttachment['content'] = self::getWWInfo();
		$phpInfoAttachment['format'] = 'text/html';
		$phpInfoAttachment['filename'] = 'phpinfo.htm'; 
		$logMessage .= 'Added Enterprise configuration: phpinfo.htm. ';
		$mailAttachments[] = $phpInfoAttachment; 
		
		LogHandler::Log('Diagnostics', 'INFO', $logMessage);
		return BizEmail::sendMail($emailTo, $emailText, $subject, $sender, $mailAttachments );
	}	

	/**
	 * Returns the phpinfo output plus all kind of Enterprise settings like configserver.php. 
	 * @return string settings in htm format. 
	 */
	private static function getWWInfo()
	{
		ob_start();                                                                                                       
		$infoPath = BASEDIR.'/server/wwtest/wwinfo.php';
		include( $infoPath );
		$phpInfo = ob_get_contents();                                                                                        
		ob_end_clean();
		
		return $phpInfo;
	}

	/**
	 * In case of error reporting the attachments must be zipped. The zip file 
	 * can be further processed (e.g. send by email).
	 * The content of an attachment is stored and in a temporary file. These temporary
	 * files are added to the zip archive and then cleaned.
	 * The archive is added as content to the (mail) attachment.
	 *
	 * @param array $attachments List of Attachment
	 * @param string $logMessage Log message.
	 * @return array of (mail) attachments.
	 */
	private static function createAttachments( array $attachments, &$logMessage )
	{
		require_once BASEDIR.'/server/utils/ZipUtility.class.php';
		require_once BASEDIR.'/server/utils/MimeTypeHandler.class.php';
		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$zipUtil = WW_Utils_ZipUtility_Factory::createZipUtility( false ); 
		$tempArchive = $zipUtil->createZipArchive();
		$mailAttachments = array();
		$tempFiles = array();
		$logMessage .= 'Received files: ';
		$filenames = array();
		
		foreach( $attachments as $attachment) {
			$transferServer = new BizTransferServer();
			$content = $transferServer->getContent($attachment);
			$filename = self::getFileName( $content, $attachment->Type );
			$filenames[] = $filename;
			$tempFile = tempnam( sys_get_temp_dir(), $filename ); 
			if ( $tempFile !== false ) {
				$tempFiles[] = $tempFile;
				$handle = fopen( $tempFile, 'r+' );
				if ( $handle !== false ) {
					fwrite( $handle, $content );	
					clearstatcache();
					if ( $attachment->Type === 'application/xml') {
						$extension = '.xml';
					} else {
						$extension = MimeTypeHandler::mimeType2FileExt( $attachment->Type );
					}
					$zipUtil->addFile( $tempFile, "$filename$extension" );
					fclose( $handle );
				}
			}
		}

		$logMessage .= implode(', ', $filenames) . '. ';

		$zipUtil->closeArchive();
		if ( $tempFiles ) foreach ( $tempFiles as $tempFile ) {
			@unlink( $tempFile );
		}

		$handle = fopen( $tempArchive, 'r');
		if ( $handle !== false ) {
			$mailAttachment['content'] = fread( $handle, filesize( $tempArchive ));
			$mailAttachment['filename'] = 'ContentStationReporting.zip';
			$mailAttachment['format'] = 'application/zip'; 
			$mailAttachments[] = $mailAttachment;
		}

		return $mailAttachments;
	}	

	/**
	 * Returns the filename of the file. The way the name is retireved depends on the type of the file.
	 * If filename could not be resolved a unique name is returned.
	 * @param string $content XML document
	 * @param string $type mime type of the file
	 * @return string filename
	 */
	private static function getFileName( $content, $type )
	{
		$fileName = '';
		if ( $type === 'application/xml' || $type === 'application/incopyicml' ) {
			$fileName = self::getXMLFileName($content);
		} elseif ( $type === 'text/plain' ) {
			$position =  strpos( $content, ':');
			if ( $position !== false ) {
				$fileName = substr($content, 0, $position);
			}	
		} else {
			$fileName = 'Screenshot'; 
		}
		
		return $fileName ? $fileName : uniqid('LogFileCS'); // Fallback;
	}

	/**
	 * Returns the filename of the crash file. The filename is in the xml document.
	 * It is set on an attribute named CSCrashFile. 
	 * @param string $content XML document
	 * @return string filename, empty string if not found.
	 */
	private static function getXMLFileName( $content )
	{
		$domDoc = new DOMDocument();
		$fileName = ''; 
		$loaded = $domDoc->loadXML( $content );
		
		if ( !$loaded ) {
			return $fileName;
		}

		$domXPath = new DOMXPath( $domDoc );
		$query = '//@CSCrashFile[1]'; // There is only one.
		$nodes = $domXPath->query( $query );
		if ( $nodes->length > 0 ) {
			$attribute = $nodes->item(0);
			$fileName = $attribute->value;
		}
		
		return $fileName;
	}	
}
