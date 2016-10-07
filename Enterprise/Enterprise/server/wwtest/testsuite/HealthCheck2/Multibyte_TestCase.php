<?php
/**
 * Multibyte TestCase class that belongs to the TestSuite of wwtest.
 * This class is automatically read and run by TestSuiteFactory class.
 * See TestSuiteInterfaces.php for more details about the TestSuite concept.
 *
 * Since v7.0 this test is rewritten to replace PEAR SOAP client with PHP SOAP client.
 *
 * @package Enterprise
 * @subpackage TestSuite
 * @since v6.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 */
 
require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_HealthCheck2_Multibyte_TestCase extends TestCase
{
	public function getDisplayName() { return 'Multi-byte'; }
	public function getTestGoals()   { return 'Checks if the HTTP Server and database can handle multi-byte Unicode text.'; }
	public function getTestMethods() { return 'Creates two users with multi-byte characters directly in the database and reads their names in order to '.
											'compare them. One user has a Latin name with a mixture of accented, upper case and lower '.
											'case characters which consist of a mixture of single- and double-byte characters.'.
											'The other user has a Chinese name which consists of three-byte characters. It searches for the user with '.
											'the Latin name in the database, but without accents on the characters and with all characters lower cased, '.
											'to check if the database is not accent or case sensitive. Creates and retrieves an article with multi-byte '.
											'Unicode characters (Chinese). Therefor SOAP services are called (over HTTP) after which it compares'.
											'sent data with received data. This is done for several article properties (UTF-8) and for plain-text '.
											'file content (UTF-16BE). The plain-text file is sent and received as DIME attachment through SOAP.'; }
    public function getPrio()        { return 10; }
	
	private $suiteOpts = null;    // Array representing the TESTSUITE option (configserver.php)
	private $ticket = null;       // session ticket
	private $soapClient = null;   // workflow SOAP client
	private $createObject = null; // sent article object
	private $getObject = null;    // received article object
	private $objId = null;        // article id (set once created)
	private $pubId = null;        // brand/publication id to use
	private $catId = null;        // category/section id to use
	private $statusId = null;     // article status to use

	/**
	 * Calls all class's functions
	 *
	 * @return bool Whether or not the operation was succesful.
	 */
	final public function runTest()
	{
		require_once BASEDIR.'/server/utils/UrlUtils.php';
		if( !WW_Utils_UrlUtils::isResponsiveUrl( LOCALURL_ROOT.INETROOT.'/index.php?test=ping' ) ) {
			$this->setResult( 'ERROR', 'It seems to be impossible to connect to "'.LOCALURL_ROOT.'". ',
				'Please check your LOCALURL_ROOT setting at the config.php file. Make sure the server can access that URL.' );
			return false;
		}		

		// Before using the client to do multibyte test, do a direct connection to DB to ensure that the unicodes are stored fine.
		if( !$this->checkDbCollation() ) {
			return false;
		}
	
		$calls = array ('logOn', 'getDialog', 'createArticle', 'getArticle', 'unlockArticle', 'validateData', 'deleteArticle', 'logOff');
		foreach( $calls as $call ) {
			$this->$call();
		}
		return true;
	}
	
	/**
	 * Checks if Database collation is set correctly.
	 * This is done by directly inserting UTF-8 data into DB (without going through soap client):
	 * A user with UTF-8 name is created and the user is retrieved
	 * from the DB to check if the name is saved correctly.
	 * 
	 * @return Boolean True When the UTF-8 data can be saved correctly; False otherwise.
	 */
	private function checkDbCollation()
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$unicodes = array( 'unicode_in_general', 'unicode_chinese' );
		foreach( $unicodes as $unicode ) {
			list( $userShort, $userFull )  = $this->getUserName( $unicode );		
			$successful = true;
			try {
				LogHandler::Log('wwtest','INFO', 'Multibyte testing:' . $unicode);
				$userId = DBUser::createUser( $userShort, 'ww', $userFull );
				if( $userId ) {
					// Check DB Collation
					$userRow = DBUser::getUserById( $userId );
					if( $userRow['user'] != $userShort || $userRow['fullname'] != $userFull ) {
						LogHandler::Log( 'wwtest', 'ERROR', 'Unicode text (in UTF-8 format) cannot be saved correctly into database.');
						$successful = false;
					}
					
					// Check if DB collation is accent and case in-sensitive (Only for MySQL)
					// MSSQL and Oracle aren't accent insensitive (BZ#28667)
					if( (DBTYPE != 'mssql' && DBTYPE != 'oracle') && $unicode == 'unicode_in_general' ) {
						$userInfo = DBUser::getUser( 'woodwinghchecktest' ); // Retrieve a userShortName with case-insensitive
						if( !$userInfo ) {
							LogHandler::Log( 'wwtest', 'ERROR', 'Database collation is not case insensitive or accent insensitive.');
							$successful = false;
						}					
					}

					// Error here if there was any problem found above.
					if( !$successful ) {
						if( DBTYPE == 'mssql' ) {
							$help = 'Make sure that: </br>' . PHP_EOL .
									'- The system locale is set to English(US) under Region and Language->Administrative tab->Change system locale. </br>' . PHP_EOL .
									'- The correct* "Microsoft SQL Server Native Client" is installed.</br>' . PHP_EOL .
									'- The correct* "SQL Server Driver for PHP" is installed.</br>' . PHP_EOL .
									'- The database collation option is set to "Latin1_General_CI_AI" when creating the database for Enterprise.</br>' . PHP_EOL .
									'* See the Enterprise Admin Guide for details.';
						} else if( DBTYPE == 'mysql' ) {
							$help = 'Make sure that the database collation is set to utf8_general_ci.</br>';
						} else {
							$help = 'Make sure that the database collation is set to UTF-8. In the Character Sets tab,</br> ' .
									'select "Use Unicode (AL32UTF8)" and select "UTF8 - ..." as the National Character Set.</br>';
						}

						$this->setResult( 'ERROR', 'Unicode text (in UTF-8 format) cannot be saved correctly into the database.</br>' .
									'The configured character set could be incorrect, or the database is accent sensitive or case sensitive.', $help );
					}
					
					// After testing, do clean up.
					DBUser::deleteUser( $userId );
				} else {
					$this->setResult( 'ERROR', 'Cannot test the Database collation.', 'Please ensure that the database configuration is set correctly ' .
										'in config.php and that the database is set up correctly.' );
					$successful = false;
				}
			} catch ( BizException $e ) {
				$this->setResult( 'ERROR', 'Cannot test the Database collation.', 'Error occured:' . $e->getMessage() );
				$successful = false;
			}
			if( !$successful )
			{
				LogHandler::Log('wwtest','ERROR', $unicode . ':Unicode text (in UTF-8 format) cannot be saved correctly into the database.' );
				return false; // No point to continue validating.
			}
		}
		LogHandler::Log('wwtest','INFO', 'All unicode text (in UTF-8 format) can be saved corerctly into database.' );
		return true;
	}
	
	/**
	 * Get the unicode characters.
	 * @param string $unicode Return the unicode representive of the 'unicode range' ($unicode) requested. Can be 'unicode_in_general', 'unicode_chinese'
	 * @return array Username where the first element consists the userShortName and second element consists the userFullName.
	 */
	private function getUserName( $unicode ) 
	{
		switch( $unicode ) {
			case 'unicode_in_general':
				// Name = Woodwing Software
				// Woodwing		
				$oDia = chr(0xC3).chr(0xB6); // o-diaeresis // o
				$iCfl = chr(0xC3).chr(0xAE); // i-circumflex // i
				$nTld = chr(0xC3).chr(0xB1); // n-tilde  // n
			//	$gClef = chr(0xF0).chr(0x9D).chr(0x84).chr(0x9E); // g
				
				// Software
				$sSrp = chr(0xE1).chr(0xBA).chr(0x9E); // s-sharp // S
				$oStk = chr(0xC3).chr(0x98); // o-stroke // o
				$aRng = chr(0xC3).chr(0xA5); // a-ring // a
				$eSae = chr(0xC3).chr(0xA6); // e-smallAE // e
				
				$userShort = 'W' . $oDia . $oDia . 'dW' .$iCfl . $nTld . 'gHCheckTest';
				$userFull = $userShort. ' ' . $sSrp . $oStk . 'ftw' . $aRng . 'r' . $eSae;				
			break;
			case 'unicode_chinese':
				$chineseChar1 = chr(0xEF).chr(0xA5).chr(0x89); // ancient: chr(0xE4).chr(0xA8).chr(0x93);
				$chineseChar2 = chr(0xE6).chr(0x98).chr(0x8E);
				$chineseChar3 = chr(0xE7).chr(0x81).chr(0xAF);
				
				$userShort = $chineseChar2 . $chineseChar3;
				$userFull = $chineseChar1 . $userShort;
			break;			
		}

		$userName = array();		
		$userName[] = $userShort;
		$userName[] = $userFull;
		return $userName;
	}


	/**
	 * Does login the (configured) test user to start the test session for which it retrieves a ticket ($this->ticket).
	 * It determines publication to work with ($this->pubId).
	 */
	private function logOn() 
	{
		try {
			require_once BASEDIR.'/server/protocols/soap/WflClient.php';
			$this->soapClient = new WW_SOAP_WflClient();

			if( !$this->soapClient ) return; // nothing to do
			$this->suiteOpts = defined('TESTSUITE') ? unserialize( TESTSUITE ) : array();
						
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOnResponse.class.php';
			$req = new WflLogOnRequest( 
				// $User, $Password, $Ticket, $Server, $ClientName, $Domain,
				$this->suiteOpts['User'], $this->suiteOpts['Password'], '', '', '', '',
				//$ClientAppName, $ClientAppVersion, $ClientAppSerial, $ClientAppProductKey, $RequestTicket
				'Logon SOAP Test', 'v'.SERVERVERSION, '', '', false );
			$resp = $this->soapClient->LogOn( $req );
			LogHandler::Log('wwtest', 'INFO', 'LogOn successful.');
			$this->ticket = $resp->Ticket;
			LogHandler::Log('wwtest', 'INFO', 'Ticket: '.$this->ticket);
			if( count($resp->Publications) > 0 ) {
				foreach( $resp->Publications as $pub ) {
					if( $pub->Name == $this->suiteOpts['Brand'] ) {
						$this->pubId = $pub->Id;
						LogHandler::Log('wwtest', 'INFO', 'Picked publication: '.$pub->Name." (id=$this->pubId)" );
					}
				}
				if( !$this->pubId ) {
					$help = 'Make sure the TESTSUITE setting at configserver.php has an option named "Brand". '.
						'That brand should exist at your server installation and the configured test user "'.$this->suiteOpts['User'].'" should has access to it. '.
						'For new installations, typically the default brand "WW News" is pre-configured.';
					$this->setResult( 'ERROR', 'Could not find the configured Brand: "'.$this->suiteOpts['Brand'].'"', $help );
				}
			} else {
				$this->setResult( 'ERROR', 'Could not find any publication at LogOn response.');
			}
		} catch( SoapFault $e ) {
			if( stripos( $e->getMessage(), '(S1053)' ) !== false ) { // wrong user/password?
				$help = 'Make sure the TESTSUITE setting at configserver.php has options named "User" and "Password". '.
					'That should be an existing user account at your server installation. '.
					'For new installations, typically the defaults "woodwing" and "ww" are used.';
			} else if( preg_match( '/(S2[0-9]{3})/is', $e->getMessage() ) > 0 ) { // S2xxx code => license error
				$help = 'See license test for more details.';
			} else {
				$help = '';
			}
			$this->setResult( 'ERROR', $e->getMessage(), $help );
		} catch( BizException $e ) {
			$help = 'Please check your LOCALURL_ROOT setting at the config.php file. Make sure the server can access that URL.';
			$this->setResult( 'ERROR', $e->getMessage(), $help );
		}
	}

	/**
	 * Calls the GetDialog service to Create and Article object.
	 * From the server response it determines an issue ($this->issueId), category ($this->catId) 
	 * and status ($this->statusId) to work with.
	 */
	private function getDialog()
	{
		if( !$this->ticket || !$this->pubId ) return; // nothing do to
		try {
			// get workflow dialog definition for Create action and Article object type  
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialogRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetDialogResponse.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetStatesResponse.class.php';
			$req = new WflGetDialogRequest( $this->ticket, null, $this->pubId, null, null, null,
							'Article', 'Create', true, true, false, true, true, null, null, null );
			$resp = $this->soapClient->GetDialog( $req );
			LogHandler::Log('wwtest', 'INFO', 'GetDialog successful.');

			// pick first best found issue
			foreach( $resp->Dialog->MetaData as $mdVal ) {
				if( $mdVal->Property == 'Issues' ) {
					if( count($mdVal->Values) > 0 ){ 
						$this->issueId = $mdVal->Values[0];
						break;
					}
				}
			}
			$categories = null;
			if( $this->issueId ) {
				foreach( $resp->PublicationInfo->PubChannels as $chanInfo ) {
					foreach( $chanInfo->Issues as $issueInfo ) {
						if( $this->issueId == $issueInfo->Id ) {
							LogHandler::Log('wwtest', 'INFO', 'Picked issue: '.$issueInfo->Name." (id=$this->issueId)" );
							if( $issueInfo->OverrulePublication == 'true' ) {
								$categories = $issueInfo->Sections;
							}
							break;
						}
					}
				}
			} else {
				$help = 'Make sure there are issues configured under brand "'.$this->suiteOpts['Brand'].'". ';
				$help .= 'Alternatively, configure other brand at TESTSUITE option (see configserver.php file).';
				$this->setResult( 'ERROR', 'Could not find any Issue.', $help );
			}
			
			// pick first best found category
			if( !$categories ) {
				$categories = $resp->PublicationInfo->Categories;
			}
			if( count($categories) > 0 ) {
				$this->catId = $categories[0]->Id;
				LogHandler::Log('wwtest', 'INFO', 'Picked category: '.$categories[0]->Name." (id=$this->catId)" );
			} else {
				$help = 'Make sure there are categories configured under ';
				if( $issueInfo && $issueInfo->OverrulePublication == 'true' ) {
					$help .= 'issue "'.$issueInfo->Name.'" of ';
				}
				$help .= 'brand "'.$this->suiteOpts['Brand'].'". ';
				$help .= 'Alternatively, configure other brand at TESTSUITE option (see configserver.php file).';
				$this->setResult( 'ERROR', 'Could not find any Category.', $help );
			}
			
			// pick first best found status
			$statuses = $resp->GetStatesResponse->States;
			if( count($statuses) > 0 ) {
				$this->statusId = $statuses[0]->Id;
				LogHandler::Log('wwtest', 'INFO', 'Picked status: '.$statuses[0]->Name." (id=$this->statusId)" );
			} else {
				$help = 'Make sure there are Article statuses configured under ';
				if( $issueInfo && $issueInfo->OverrulePublication == 'true' ) {
					$help .= 'issue "'.$issueInfo->Name.'" of ';
				}
				$help .= 'brand "'.$this->suiteOpts['Brand'].'". ';
				$help .= 'Alternatively, configure other brand at TESTSUITE option (see configserver.php file).';
				$this->setResult( 'ERROR', 'Could not find any Status.', $help );
			}
		} catch( SoapFault $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		}
	}
	
	/**
	 * Creates a new article with multi-byte name, plain content, slugline and plain text file (UTF-16BE)
	 * at server (filestore/DB). When successful, the $this->createObject and $this->objId are set to work with.
	 */
	private function createArticle() 
	{
		if( !$this->ticket || !$this->pubId || !$this->catId || !$this->statusId ) return; // nothing do to

		// make up mult-byte text data
		$chineseName = chr(0xE6).chr(0x98).chr(0x9F).chr(0xE6).chr(0xB4).chr(0xB2).chr(0xE6).chr(0x97).chr(0xA5).chr(0xE5).chr(0xA0).chr(0xB1);
		$objName = $chineseName.' '.date('m d H i s');
		$contentUTF8 = $chineseName.$chineseName.$chineseName.$chineseName;

		// create plain text file
		$newBom = chr(0xFE) . chr(0xFF); // Insert UTF-16BE BOM marker, which eases recognizion for any editor
		$contentUTF16 = $newBom . mb_convert_encoding( $contentUTF8, 'UTF-16BE', 'UTF-8' ); // UTF-16BE can be opened with ID/IC! 
		$files = array(	new Attachment( 'native', 'text/plain', // rendition, mime type
							new SOAP_Attachment('Content','application/octet-stream',null, $contentUTF16) ) );

		// build metadata
		$basMD = new BasicMetaData( null, null, $objName, 'Article', new Publication($this->pubId), new Category($this->catId), null );
		$wflMD = new WorkflowMetaData();
		$wflMD->State = new State( $this->statusId );
		$cntMD = new ContentMetaData();
		$cntMD->Format = 'text/plain';
		$cntMD->PlainContent = $contentUTF8;
		$cntMD->FileSize = strlen($contentUTF16);
		$cntMD->Slugline = $contentUTF8;
		$md = new MetaData();
		$md->BasicMetaData    = $basMD;
		$md->ContentMetaData  = $cntMD;
		$md->WorkflowMetaData = $wflMD;

		// create object
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflCreateObjectsResponse.class.php';
			$this->createObject = new Object( $md, array(), null, $files, null, null, null );
			$req = new WflCreateObjectsRequest( $this->ticket, false, array($this->createObject), null, false );
			$resp = $this->soapClient->CreateObjects( $req );
			LogHandler::Log('wwtest', 'INFO', 'CreateObjects successful.');
			$this->createObject->Files[0]->Content = $contentUTF16; // repair content release by CreateObjects call
			$this->objId = $resp->Objects[0]->MetaData->BasicMetaData->ID;
			LogHandler::Log('wwtest', 'INFO', 'CreateObjects. ID: '.$this->objId);
		} catch( SoapFault $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		}
	}

	/**
	 * Retrieves the new article (using $this->objId) with multi-byte name, plain content, slugline and plain text file (UTF-16BE)
	 * from server (filestore/DB). When successful, the $this->getObject is set to work with.
	 */
	private function getArticle() 
	{
		if( !$this->ticket || !$this->objId ) return; // nothing do to
		LogHandler::Log('wwtest', 'INFO', 'GetObjects. ID: '.$this->objId);
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflGetObjectsResponse.class.php';
			$req = new WflGetObjectsRequest( $this->ticket, array($this->objId), true, 'native', array() );
			$resp = $this->soapClient->GetObjects( $req );
			$this->getObject = $resp->Objects[0];
			LogHandler::Log('wwtest', 'INFO', 'GetObjects successful.');
		} catch( SoapFault $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		}
		LogHandler::Log('wwtest', 'INFO', 'GetObjects successful.');
	}

	/**
	 * Unlocks the article. The lock was obtained by the getArticle function.
	 */
	private function unlockArticle() 
	{
		if( !$this->ticket || !$this->objId ) return; // nothing do to
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflUnlockObjectsResponse.class.php';
			$req = new WflUnlockObjectsRequest( $this->ticket, array($this->objId), null );
			$this->soapClient->UnlockObjects( $req );
			LogHandler::Log('wwtest', 'INFO', 'UnlockObjects successful.');
		} catch( SoapFault $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		}
	}

	/**
	 * Validates the multi-byte name, plain content, slugline and plain text file (UTF-16BE)
	 * by checking the encoding and comparing sent data (createArticle) with retrieved data (getArticle).
	 */
	private function validateData()
	{
		if( !$this->ticket || !$this->objId ) return; // nothing do to
		LogHandler::Log('wwtest', 'INFO', 'Testing names.');
		$errors = array();
		
		// check encoding sent data
		if( mb_detect_encoding( $this->createObject->MetaData->BasicMetaData->Name, 'auto') != 'UTF-8' ) {
			$errors[] = 'Created article name is not UTF-8.';
		}
		if( mb_detect_encoding( $this->createObject->MetaData->ContentMetaData->Slugline, 'auto') != 'UTF-8' ) {
			$errors[] = 'Created article slugline is not UTF-8.';
		}
		if( mb_detect_encoding( $this->createObject->MetaData->ContentMetaData->PlainContent, 'auto') != 'UTF-8' ) {
			$errors[] = 'Created article plain content is not UTF-8.';
		}
		
		// check encoding received data
		if( mb_detect_encoding( $this->getObject->MetaData->BasicMetaData->Name, 'auto') != 'UTF-8' ) {
			$errors[] = 'Retrieved article name is not UTF-8.';
		}
		if( mb_detect_encoding( $this->getObject->MetaData->ContentMetaData->Slugline, 'auto') != 'UTF-8' ) {
			$errors[] = 'Retrieved article slugline is not UTF-8.';
		}
		if( mb_detect_encoding( $this->getObject->MetaData->ContentMetaData->PlainContent, 'auto') != 'UTF-8' ) {
			$errors[] = 'Retrieved article plain content is not UTF-8.';
		}
		
		// compare sent data with received data
		if( $this->createObject->MetaData->BasicMetaData->Name != $this->getObject->MetaData->BasicMetaData->Name ) {
			$errors[] = 'Sent and retrieved article names are not the same.';
		}
		if( $this->createObject->MetaData->ContentMetaData->Slugline != $this->getObject->MetaData->ContentMetaData->Slugline ) {
			$errors[] = 'Sent and retrieved article sluglines are not the same.';
		}
		if( $this->createObject->MetaData->ContentMetaData->PlainContent != $this->getObject->MetaData->ContentMetaData->PlainContent ) {
			$errors[] = 'Sent and retrieved article plain contents are not the same.';
		}
		if( $this->createObject->Files[0]->Content != $this->getObject->Files[0]->Content ) {
			$errors[] = 'Sent and retrieved article file contents are not the same.';
		}
		
		// show errors
		if( count($errors) > 0 ) {
			$help = 'Make sure your database collation is set to UTF-8. Check connection-, database-, table- and column settings. '.
				'Also run the "HTTP Server Encoding" test to check your HTTP server settings and make sure default encodings are set to UTF-8. ';
			$this->setResult( 'ERROR', 'Server is NOT multi-byte safe: <ul><li>'.implode('</li><li>',$errors).'</li></ul>', $help );
		}
		LogHandler::Log('wwtest', 'INFO', 'Names are correct. Multi-byte safe server.');
	}

	/**
	 * The test does self cleaning by removing the article.
	 */
	private function deleteArticle() 
	{
		if( !$this->ticket || !$this->objId ) return; // nothing do to
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflDeleteObjectsResponse.class.php';
			$req = new WflDeleteObjectsRequest( $this->ticket, array($this->objId), true );
			$resp = $this->soapClient->DeleteObjects( $req );
			if( !$resp->Reports ) { // Introduced in v8.0 (DeleteObjects Resp support ErrorReport)
				$this->objId = null;
				LogHandler::Log('wwtest', 'INFO', 'DeleteObjects successful.');
			} else {
				$errMsg = '';
				foreach( $resp->Reports as $report ){
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message . PHP_EOL;
					}
				}
				$this->setResult( 'ERROR', $errMsg, '' );
			}
		} catch( SoapFault $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		}
	}

	/**
	 * Logs off the (configured) test user to end the test session.
	 */
	private function logOff() 
	{
		if( !$this->ticket ) return; // nothing do to
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffRequest.class.php';
			require_once BASEDIR.'/server/interfaces/services/wfl/WflLogOffResponse.class.php';
			$req = new WflLogOffRequest( $this->ticket, false, null, null );
			$this->soapClient->LogOff( $req );
			LogHandler::Log('wwtest', 'INFO', 'LogOff successful.');
		} catch( SoapFault $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', $e->getMessage(), '' );
		}
	}
}
