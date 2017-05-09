<?php
/**
 * @package Enterprise
 * @subpackage TestSuite
 * @since v8.0
 * @copyright WoodWing Software bv. All Rights Reserved.
 *
 * @todo Refactor the code that retrieves the session variables and overwrites them.
 *
 */

require_once BASEDIR.'/server/wwtest/testsuite/TestSuiteInterfaces.php';

class WW_TestSuite_BuildTest_WebServices_WflServices_WflMessages_TestCase extends TestCase
{
	private $ticket 	= null;
	private $suiteOpts 	= null;
	private $pubObj 	= null;
	private $issueObj 	= null;
	private $layoutId	= null;
	private $editionObj	= null;
	private $pubChannelObj	= null;
	private $layoutStatus	= null;
	private $isV8Client = null; // To indicate whether testing for client v7(uses Messages) or v8(uses MessageList->Messages).
	private $msgId = null; // Message that is sent to user by system for testing, remember the msgId for deletion at end of testing.
	private $messagesForVerification = null; // Messages that is sent in LogOn Request, to be verified in the test.
	private $msgSettingsSet = null; // The details(ThreadMsgId, ReplyMsgId, its own MsgId) for group of reply notes.
	private $msgsettingsCount = null; // Keeping track on which setting should be used in $msgSettingsSet
	private $unreadStickyReplyCount = null; // Keep track unread messages.When new messages created, the count increase;when messages are marked as read, the count decreases.
	
	public function getDisplayName() { return 'Annotations'; }
	public function getTestGoals()   { return 'Checks if Messages in Object is returned correctly and complete.'; }
    public function getPrio()        { return 109; }
   	public function getTestMethods() { return 
   		'Testing with Sticky notes creation and deletion on a layout.(Simulation with v7 & v8 client)
   		<ol>
   		<li>A layout with five pages is created. Page numbering starts at irregular page such as 21.</li>
   		<li>The same layout is added with several sticky notes on different randomly selected pages.</li>
   		<li>Layout is saved(check-in).</li>
   		<li>The layout is re-opened and some sticky notes that was previously added is now removed and few new ones are added into the layout.</li>
   		<li>Layout is saved(check-in) again.</li>
   		<li>Layout is re-opened and all sticky notes are now removed.</li>
   		<li>Layout is closed and saved(checked in) again.</li>
   		<li>Each time when the layout is saved, the Messages returned in response is checked whether it is valid and complete.</li>
  		</ol>	
   		Testing with messages on PublicationOverview
   		<ol>
   		<li>Before the sticky notes are removed in the above testing, getObjects service call is called.</li>
   		<li>Messages is checked in the getObjects response.</li>
   		</ol>
   		Testing with user message(sent by system) on LogOn Response.
   		<ol>
   		<li>A system message is sent to user via SendMessages service call.</li>
   		<li>Log off the current user and re-logon to check the Messages returned in logOn response.</li>
   		<li>For v8Client, one more testing is done that is to mark the message as "Read". This is done to hit the code for sendingMessageToUser.</li>
   		<li>Message sent earlier on is deleted for the next testing.</li>
   		</ol>
   		Testing with sticky notes, replies and unreadMessage count with saveObject(Called by SC) and sendMessages(Called by CS publication overview).
   		This section is only tested with v8 Client only.
   		<ol>
		<li>Simulating SC dealing with stickies and replies.(Using saveObjects)</li>
	   		<ol>
				<li>The same layout with five pages where page numbering starts at irregular page is created.</li>
				<li>The same layout is added with several sticky notes on different randomly selected pages.</li>
				<li>One reply is sent for one of the sticky note created above. Layout is saved (check-in) to send the reply.</li>
				<li>Second, followed by the third reply is sent for the reply above.All done by saving(checking in) the layout.</li>
				<li>Each time the reply is sent, a getObjects is called to check if the reply sent were correct.</li>
				<li>Once all the stickies and replies are sent, unread message count is checked by calling queryObjects.</li>
				<li>Then all replies will be marked as read by calling the saveObjects.</li>
				<li>Unread message count is checked again by calling queryObjects, this time the unread count shuld be reduced.</li>
				<li>Next, the replies are deleted one by one. First it tries to delete the reply sitting in the middle of the replies.
				    This is done by calling saveObject.</li>
				<li>Expected result for the above is server will not delete the reply and just silently log in the logging.</li>
				<li>Then test case attempts to delete each reply by starting from the most last reply by caling saveObject.</li>
				<li>Lastly, it will delete the main message by calling saveObject.</li>
				<li>The layout is unlock and gets deleted once all the testing above is done.</li>
			</ol>	
		<li>Simulating CS dealing with stickies and replies.(Using sendMessages)</li>
	   		<ol>
				<li>The same layout with five pages where page numbering starts at irregular page is created.</li>
				<li>The same layout is added with several sticky notes on different randomly selected pages.</li>	   		
				<li>One reply is sent for one of the sticky note created above. This is done by calling sendMessages service.</li>
				<li>Second, followed by the third reply is sent for the reply above.All done by calling sendMessages service.</li>
				<li>Each time the reply is sent, a getObjects is called to check if the reply sent were correct.</li>
				<li>Once all the stickies and replies are sent, unread message count is checked by calling queryObjects.</li>
				<li>Then all replies will be marked as read by calling the sendMessages.</li>
				<li>Unread message count is checked again by calling queryObjects, this time the unread count shuld be reduced.</li>
				<li>Next, the replies are deleted one by one. First it tries to delete the reply sitting in the middle of the replies.
				    This is done by calling sendMessages.</li>
				<li>Expected result for the above is server will not delete the reply and just silently log in the logging.</li>
				<li>Then test case attempts to delete each reply by starting from the most last reply by caling sendMessages.</li>
				<li>Lastly, it will delete the main message by calling sendMessages.</li>
				<li>The layout is unlock and gets deleted once all the testing above is done.</li>
	   		</ol>
   		</ol>
   		'; }
	
	final public function runTest()
	{
		//<<<<1.Starts------------------------Check if test credentials are available before start real testing-------------------------->
		// Retrieve the Ticket that has been determined by WflLogOn TestCase,
		// and log out with the ticket retrieved as this test case needs to re-logon
		// with one tweak on the logOn request parameters('ClientAppVersion').
   		$vars = $this->getSessionVariables();
   		$varsOriginal = $vars;
		$this->ticket = isset( $vars['BuildTest_WebServices_WflServices']['ticket'] ) ?
   							$vars['BuildTest_WebServices_WflServices']['ticket'] : null;

		if( $this->ticket ) {
			$this->logOff( $this->ticket ); // Log off the ticket retrieved in WflLogOn Test Case
			// At end of this script, re-logon is needed for the other test cases to continue as it was.
		}
	
		$this->suiteOpts = unserialize( TESTSUITE );
		
		// Clear messages sent to user to make sure the current system data does not badly affect
		// comparisons of recorded results with current test results (later on this test script).
		$this->clearUserMessages( $this->suiteOpts['User'] );

		// LogOn test user through workflow interface
		$this->isV8Client = false; // Hack in LogOn req, set req->ClientAppVersion to v7 client(does not supports MessageList)
		$response = $this->logOn( $this->suiteOpts['User'], $this->suiteOpts['Password'] );

		if( !is_null($response) ) {
			$this->ticket = $response->Ticket;

			// Determine the brand to work with
			if( count($response->Publications) > 0 ) {
				foreach( $response->Publications as $pub ) {
					if( $pub->Name == $this->suiteOpts['Brand'] ) {
						$this->pubObj = $pub;
						break;
					}
				}
			}
			if( !$this->pubObj ) {
				$this->setResult( 'ERROR', 'Could not find the test Brand: '.$this->suiteOpts['Brand'], 
					'Please check the TESTSUITE setting in configserver.php.' );
			}
		}
		if( !$this->ticket ) {
			$this->setResult( 'ERROR',  'Could not find ticket to test with.', 'Please check the TESTSUITE setting in configserver.php.' );
			return;
		}

		// Make sure the save operations are directly reflected at Solr indexes,
		// or else we risk race-conditions, whereby saved data is not queryable the next
		// split second, and so tests would fail randomly.
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		BizSession::setDirectCommit( true );
		
		// Save the retrieved ticket into session data.
		// This data is picked up by successor TestCase modules within this WflServices TestSuite.
		$vars = array();
		$vars['BuildTest_WebServices_WflServices']['ticket'] = $this->ticket;
		$vars['BuildTest_WebServices_WflServices']['publication'] = $this->pubObj;
		$this->setSessionVariables( $vars );
		
		$this->resolveBrandSetup();
		$this->userTrackChangesColor = $this->getUserColor();

		//1.Ends>>>>>>

		require_once BASEDIR.'/server/bizclasses/BizTransferServer.class.php';
		$this->transferServer = new BizTransferServer();
		
		//<<<<2.Starts------------------------v7 and older client Testing---------------------------------------------------------------->
		
		//<<<<2a.Starts------------------------Sticky notes creation and deletion testing------------------------------------------------>
		// Sticky notes creation&deletion on layout with client v7(and older) testing.
		$this->currentDateTime = date("m d H i s");
		$this->isV8Client = false;
		$this->callCreateObject();    // Create plain layout.
		$this->callSaveObject001();   // Add few sticky notes on random pages and save layout.
		$this->callUnlockObject();    // Check-in layout.
		$this->callGetObjects001();   // Retrieve the same layout.
		$this->callSaveObject002();   // Remove some of the previous sticky notes and add few new ones, and save the layout.
		$this->callUnlockObject();    // Check-in layout.
		
		//<<<<2b.Starts--------------------------------Messages on PublicationOverview testing.------------------------------------------>
		$this->pubOverviewCallGetObjects();
		//2b.Ends>>>>>>
		
		// Clean up the sticky notes for next testing.
		// In the mean time, also check if the responses are returned correctly.
		$this->callGetObjects002();   // Retrieve the same layout.
		$this->callSaveObject003();   // Remove ALL sticky notes and save the layout.
		$this->callUnlockObject();    // Check-in layout.
		$this->callDeleteObjects();   // Purge(Delete permanently) the layout, this is needed as the MessageID in message is hard-coded and
                                      // have bind in the binary file of the test layout. Layout has to be deleted in order to re-run the same
                                      // test(with the same layout and MessageID) in the next run.
		
		//2a.Ends>>>>>>

		//<<<<2c.Starts----------------------------User message on LogOn Resp testing.--------------------------------------------------->
		$this->timeStampMsgSent = date('Y-m-d\Th:m:s');
		$this->messageForUser = 'This is just for build test testing on user message-with client v7.';
		$this->callSendMessages(); // This is preparation for User Message testing below.
		$this->logOff( $this->ticket );
		
		$this->callLogOnForUserMsgValidation();		
		$this->callSendMessagesForMsgDeletion();
		//2c.Ends>>>>>>
		
		//2.Ends>>>>>>

		//<<<<3.Starts----------------------Preparing for v8 client testing. Reset the ticket and environment.--------------------------->
		$this->logOff( $this->ticket );
		// Clear messages sent to user to make sure the current system data does not badly affect
		// comparisons of recorded results with current test results (later on this test script).
		$this->clearUserMessages( $this->suiteOpts['User'] );
		
		// LogOn test user through workflow interface
		$this->isV8Client = true; // Hack in LogOn req, set req->ClientAppVersion to v8 client(supports MessageList)
		$response = $this->logOn( $this->suiteOpts['User'], $this->suiteOpts['Password'] );
		$this->ticket = $response->Ticket;
		//3.Ends>>>>>>		
		
		//<<<<4.Starts--------------------------------v8 and newer client Testing-------------------------------------------------------->
		
        //<<<<4a.Starts------------------------Sticky notes creation and deletion testing------------------------------------------------>
		// Sticky notes creation&deletion on layout with client v8(and newer) testing.
		$this->currentDateTime = date("m d H i s");
		$this->isV8Client = true;
		$this->callCreateObject();    // Create plain layout.
		$this->callSaveObject001();   // Add few sticky notes on random pages and save layout.
		$this->callUnlockObject();    // Check-in layout.
		$this->callGetObjects001();   // Retrieve the same layout.
		$this->callSaveObject002();   // Remove some of the previous sticky notes and add few new ones, and save the layout.
		$this->callUnlockObject();    // Check-in layout.
		
		//<<<<4b.Starts--------------------------------Messages on PublicationOverview testing.------------------------------------------>
		$this->pubOverviewCallGetObjects();
		//4b.Ends>>>>>>

		// Clean up the sticky notes for next testing.
		// In the mean time, also check if the responses are returned correctly.
		$this->callGetObjects002();   // Retrieve the same layout.
		$this->callSaveObject003();   // Remove ALL sticky notes and save the layout.
		$this->callUnlockObject();    // Check-in layout.
		$this->callDeleteObjects();   // Purge(Delete permanently) the layout, this is needed as the MessageID in message is hard-coded and
                                      // have bind in the binary file of the test layout. Layout has to be deleted in order to re-run the same
                                      // test(with the same layout and MessageID) in the next run.
	
		//4a.Ends>>>>>>
		
		//<<<<4c.Starts---------------------------------User message on LogOn Resp testing.---------------------------------------------->
		$this->timeStampMsgSent = date('Y-m-d\Th:m:s');
		$this->messageForUser = 'This is just for build test testing on user message-with client v8.';
		$this->callSendMessages(); // This is preparation for User Message testing below.
		$this->logOff( $this->ticket );
		
		$this->callLogOnForUserMsgValidation();
		$this->callSendMessageToMarkMessageAsRead(); // There's no verification here, just to hit the code on sendMessageToUser.	
		$this->callSendMessagesForMsgDeletion();		
		//4c.Ends>>>>>>

		//4.Ends>>>>>>
		
		//<<<<4d, 4d preparations-----------------------------Messages with InDesign client(Only for v8 client).------------------------->
		$this->timeStampMsgSent = date('Y-m-d\Th:m:s');
		$threadMsgId = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE'; // Will be created in callCreateObject() below.
		$this->msgSettingsSet = array(
							array( 'messageId'   => '1806D271-5DDD-4396-B2BB-A60878527D12',
								   'repMsg'      => 'This is on page 22 bottom - Reply 1',
								   'threadMsgId' => $threadMsgId,
								   'repToMsgId'  => $threadMsgId // for the FIRST reply, repToMsgId is the same as the $threadMsgId.
								   ),
							array( 'messageId'   => '119A5212-B212-41E1-9317-19B014B04098',
								   'repMsg'      => 'This is on page 22 bottom - Reply 2',
								   'threadMsgId' => $threadMsgId,
								   'repToMsgId'  => '1806D271-5DDD-4396-B2BB-A60878527D12'
								   ),
							array( 'messageId'   => 'CF3BCBE3-645F-4F2D-AE50-4B73502237C5',
								   'repMsg'      => 'This is on page 22 bottom - Reply 3',
								   'threadMsgId' => $threadMsgId,
								   'repToMsgId'  => '119A5212-B212-41E1-9317-19B014B04098'
								   ),
					);
					
		//<<<<4d.Starts-----------------------------Messages with InDesign client(Only for v8 client).----------------------------------->
					
		// Indicator for the getObjects response, what messages should be expected in the getObjects response.					
		// When the replyN is set to false, meaning the message is not sent yet.
		$this->reply1 = false; // 'FIRST' reply is not sent yet.
		$this->reply2 = false; // Reply('SECOND') on the 'FIRST' reply note is not sent yet.
		$this->reply3 = false; // Reply('THIRD') on the 'SECOND' reply note is not sent yet.
		
		$this->currentDateTime = date("m d H i s");
		$this->callCreateObject();    // Create plain layout.
		$this->callSaveObject001();   // Add few sticky notes on random pages and save layout.

		$this->msgsettingsCount = 0;
		$this->callSaveObjectToReplySticky();   // Reply("This is on page 22 bottom - Reply 1") to the sticky note("This is on page 22 bottom").
		$this->reply1 = true; // Only has one reply sent.
		$this->callGetObjectsToCheckSticky();

		$this->msgsettingsCount = 1;
		$this->callSaveObjectToReplySticky();   // Reply("This is on page 22 bottom - Reply 2") to the sticky note("This is on page 22 bottom - Reply 1").
		$this->reply1 = true; // has reply one
		$this->reply2 = true; // and reply two
		$this->callGetObjectsToCheckSticky();

		$this->msgsettingsCount = 2;
		$this->callSaveObjectToReplySticky();   // Reply("This is on page 22 bottom - Reply 3") to the sticky note("This is on page 22 bottom - Reply 2").
		$this->reply1 = true; // has reply one
		$this->reply2 = true; // reply two
		$this->reply3 = true; // and reply three
		$this->callGetObjectsToCheckSticky();	

		$this->callQueryObjectsToVerifyUnreadMessages();
		$this->callSaveObjectToMarkMessageAsRead();
		$this->callQueryObjectsToVerifyUnreadMessages();
		
		$this->replyNoteMsgId = $this->msgSettingsSet[1]['messageId']; // Try to delete reply2("This is on page 22 bottom - Reply 2") which should fail.
		$this->callSaveObjectToDeleteSticky( 'Message cannot be deleted.' ); // Expect error here

		$this->replyNoteMsgId = $this->msgSettingsSet[2]['messageId']; // Delete reply3("This is on page 22 bottom - Reply 3").
		$this->callSaveObjectToDeleteSticky();	

		$this->replyNoteMsgId = $this->msgSettingsSet[1]['messageId']; // Delete reply2("This is on page 22 bottom - Reply 2").
		$this->callSaveObjectToDeleteSticky();

		$this->replyNoteMsgId = $this->msgSettingsSet[0]['messageId']; // Delete reply1("This is on page 22 bottom - Reply 1").
		$this->callSaveObjectToDeleteSticky();
		
		$this->replyNoteMsgId = $threadMsgId;
		$this->callSaveObjectToDeleteSticky();

		$this->callUnlockObject();    // Check-in layout.
		$this->callDeleteObjects();
		
		//4d.Ends>>>>>>
		
		
		
		//<<<<4e.Starts-----------------------------Messages with CS client-PubOverview(Only for v8 client).----------------------------->
		// Indicator for the getObjects response, what messages should be expected in the getObjects response.					
		$this->reply1 = false; // 'FIRST NOTE' reply is not sent yet.
		$this->reply2 = false; // Reply('SECOND NOTE') on the 'FIRST NOTE' reply note is not sent yet.
		$this->reply3 = false; // Reply('THIRD NOTE') on the 'SECOND NOTE' reply note is not sent yet.		

		$this->currentDateTime = date("m d H i s");
		$this->callCreateObject();    // Create plain layout.
		$this->callSaveObject001();   // Add few sticky notes on random pages and save layout.
		
		$this->msgsettingsCount = 0;
		$this->callSendMessagesToReplySticky();   // Reply("This is on page 22 bottom - Reply 1") to the sticky note("This is on page 22 bottom").
		$this->reply1 = true; // Only has one reply sent.
		$this->callGetObjectsToCheckSticky();

		$this->msgsettingsCount = 1;
		$this->callSendMessagesToReplySticky();   // Reply("This is on page 22 bottom - Reply 2") to the sticky note("This is on page 22 bottom").
		$this->reply1 = true; // has reply one
		$this->reply2 = true; // and reply two
		$this->callGetObjectsToCheckSticky();

		$this->msgsettingsCount = 2;
		$this->callSendMessagesToReplySticky();   // Reply("This is on page 22 bottom - Reply 3") to the sticky note("This is on page 22 bottom").
		$this->reply1 = true; // has reply one
		$this->reply2 = true; // reply two
		$this->reply3 = true; // and reply three
		$this->callGetObjectsToCheckSticky();
		
		$this->callQueryObjectsToVerifyUnreadMessages();
		$this->callSendMessageToMarkMessagesAsRead();
		$this->callQueryObjectsToVerifyUnreadMessages();

		$this->replyNoteMsgId = $this->msgSettingsSet[1]['messageId']; // Try to delete reply2("This is on page 22 bottom - Reply 2") which should fail.
		$this->callSaveObjectToDeleteSticky( 'Message cannot be deleted.' ); //Expect error here.

		$this->replyNoteMsgId = $this->msgSettingsSet[2]['messageId']; // Delete reply3("This is on page 22 bottom - Reply 3").
		$this->callSendMessagesToDeleteSticky();	

		$this->replyNoteMsgId = $this->msgSettingsSet[1]['messageId']; // Delete reply2("This is on page 22 bottom - Reply 2").
		$this->callSendMessagesToDeleteSticky();

		$this->replyNoteMsgId = $this->msgSettingsSet[0]['messageId']; // Delete reply1("This is on page 22 bottom - Reply 1").
		$this->callSendMessagesToDeleteSticky();

		$this->replyNoteMsgId = $threadMsgId;
		$this->callSendMessagesToDeleteSticky();
	
		$this->callUnlockObject();    // Check-in layout.
		$this->callDeleteObjects();
		
		//4d.Ends>>>>>>		

		//<<<<5.Starts---------------------------End of TestCase - "Clean Up" for other TestCases to continue---------------------------->
		$this->logOff( $this->ticket ); // To log out the ticket retrieved for Messages TestCase.
		$this->isV8Client = null; // Does not need to hack in Req->ClientAppVersion, just leave this parameter out.
		$response = $this->logOn( $this->suiteOpts['User'], $this->suiteOpts['Password'] ); // Re-logon for other test cases testing.
		$varsOriginal['BuildTest_WebServices_WflServices']['ticket'] = $response->Ticket;
		$this->setSessionVariables( $varsOriginal );
		//5.Ends>>>>>>
		
	}
	
	/**
	 * Removes all messages assigned to a given user.
	 *
	 * @param string $shortUserName
	 */
	private function clearUserMessages( $shortUserName )
	{
		require_once BASEDIR.'/server/dbclasses/DBUser.class.php';
		$userId = DBUser::getUserDbIdByShortName( $shortUserName );
		
		require_once BASEDIR.'/server/bizclasses/BizMessage.class.php';
		BizMessage::deleteMessagesForUser( $userId );
	}

////////////////////// LogOn Service call(s) ////////////////////////////////////
	/**
	 * Calls the workflow interface to LogOn given user.
	 *
	 * @param string $user
	 * @param string $password
	 * @return WflLogOnResponse on success. NULL on error.
	 */
	private function logOn( $user, $password )
	{
		try {
			require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
			require_once BASEDIR.'/server/services/wfl/WflLogOnService.class.php';

			$req = new WflLogOnRequest();
			$req->User     = $user;
			$req->Password = $password;
			$req->Server   = 'Enterprise Server';
			
			require_once BASEDIR.'/server/utils/UrlUtils.php';
			$clientip = WW_Utils_UrlUtils::getClientIP();
			$req->ClientName = isset($_SERVER[ 'REMOTE_HOST' ]) ? $_SERVER[ 'REMOTE_HOST' ] : '';
			// >>> BZ#6359 Let's use ip since gethostbyaddr could be extreemly expensive! which also risks throwing "Maximum execution time of 11 seconds exceeded" 
			if( empty($req->ClientName) ) { 
				$req->ClientName = $clientip; 
			}
			// <<<
			$req->ClientAppName	= 'BuildTest_WebServices_WflServices';
			if( $this->isV8Client !== false ) { // true or null
				$req->ClientAppVersion =  'v'.SERVERVERSION;
			} else {
				$req->ClientAppVersion = 'v7.6.0 build 1';
			}
			$curResp = $this->runService( $req );
		} catch( BizException $e ) {
			$this->setResult( 'ERROR', 'Could not logon Wfl test user: '.$e->getMessage(), 
				'Please check the TESTSUITE setting in configserver.php.' );
			$curResp = null;
		}
		return $curResp;
	}


	/**
	 * This function does a logOn call and verify its logOn response Messages.
	 * The expected message is the message sent in callSendMessages().
	 */
	private function callLogOnForUserMsgValidation()
	{
		$curResp = $this->logOn( $this->suiteOpts['User'], $this->suiteOpts['Password'] );
		if( !is_null( $curResp )) {
			$this->ticket = $curResp->Ticket;
			
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$ignorePaths = array(
							'[0]->MessageStatus' => true,
							'[0]->ObjectVersion' => true,						
							);

			$phpCompare->initCompare( $ignorePaths, $this->getCommonPropDiff() ); // all properties should be checked
			if( $this->isV8Client ) {
				$recRespMessages = $this->messagesForVerification;
				$curRespMessages = $curResp->MessageList->Messages;
				$this->msgId = $curRespMessages[0]->MessageID; // Remember the msgId to be deleted after this test.
				$clientVr = 'v8';
			} else {
				$recRespMessages = $this->messagesForVerification;
				$curRespMessages = $curResp->Messages;
				$this->msgId = $curRespMessages[0]->MessageID; // Remember the msgId to be deleted after this test.
				$clientVr = 'v7';
			}
			// Ignore license notifications (since that depends on installed license keys).
			if( $curRespMessages ) {
				foreach( $curRespMessages as $key => $curRespMessage ) {
					if( $curRespMessage->MessageTypeDetail == 'LicenseNotification' ) {
						unset( $curRespMessages[$key] );
					}
				}
				// Repair keys (since intermediate key-values could have been deleted above).
				$newKeys = range( 0, count($curRespMessages)-1 );
				$curRespMessages = array_combine( $newKeys, array_values($curRespMessages) );
			}
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $this->messagesForVerification, 'print_r', 'callLogOnForUserMsgValidation-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curRespMessages, 'print_r', 'callLogOnForUserMsgValidation-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in messages in LogOn response.');
				return;
			}	
		
		} else {
			$this->setResult( 'ERROR', 'LogOn: failed. Cannot proceed with User message test from logOn response.' );
		}
		
	}

////////////////////// LogOff Service call(s) ////////////////////////////////////
	/**
	 * Calls the workflow interface to LogOff with the given ticket
	 *
	 * @param string $ticket
	 */
	private function logOff( $ticket )
	{
		try{
			require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
			require_once BASEDIR . '/server/services/wfl/WflLogOffService.class.php';

			$req = new WflLogOffRequest();
			$req->Ticket = $ticket;
			/*$curResp = */$this->runService( $req );
		} catch ( BizException $e ){
			$this->setResult( 'ERROR', 'Could not logOff Wfl test user: '.$e->getMessage(), 
					'Please check the TESTSUITE setting in configserver.php.' );
		}
	}

////////////////////// CreateObject Service call(s) ////////////////////////////////////
	/**
	 * Call createObject service call 
	 * and retrieve the layout id in $this->layoutId.
	 */
	private function callCreateObject()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflCreateObjectsService.class.php';
		
		$req = $this->getCreateObjectRequest();
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$resp = $this->runService( $req );
			$this->layoutId = $resp->Objects[0]->MetaData->BasicMetaData->ID;
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'CreateObjects: failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Construct WflCreateObjectsRequest object.
	 * @return WflCreateObjectsRequest
	 */
	private function getCreateObjectRequest()
	{
		$request = new WflCreateObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Lock = true;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = null;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:5D91A69E082068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = null;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 438272;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = '2012-05-02T22:59:30';
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = '2012-05-02T22:59:30';
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#000_thumb.jpg';		
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#001_preview.jpg';		
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#002_thumb.jpg';		
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#004_thumb.jpg';		
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#003_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->Objects[0]->MessageList = null;
		$request->Messages = null;
		$request->AutoNaming = null;
		return $request;
	}
	
////////////////////// SaveObject Service call(s) ////////////////////////////////////	
	/**
	 * Call saveObject service call to save layout.
	 * The layout saved is added with few sticky notes on several
	 * randomly selected pages.
	 */	 
	private function callSaveObject001()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSaveObjectRequest001();
		$recResp = $this->getExpectedSaveObjectResponse001();
		
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
	
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			if( $this->isV8Client ) {
				$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
				$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
				$clientVr = 'v8';
			} else {
				$recRespMessages = $recResp->Objects[0]->Messages;
				$curRespMessages = $curResp->Objects[0]->Messages;
				$clientVr = 'v7';
			}
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {		
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callSaveObject001-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callSaveObject001-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflSaveObjects response.');
				return;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SaveObjects: failed: "'.$e->getMessage().'"' );
			$curResp = null;
		}		
	
	}

	/**
	 * Construct WflSaveObjectsRequest object.
	 * @return WflSaveObjectsRequest
	 */
	private function getSaveObjectRequest001()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6350DE95092068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 319488;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#000_thumb.jpg';		
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#005_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
		$request->ReadMessageIDs = null;
		
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = null;
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = null;
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = null;
		$messages[0]->ReplyToMessageID = null;
		$messages[0]->MessageStatus = null;
		$messages[0]->ObjectVersion = null;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = null;
		$messages[1]->MessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 22 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:18';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = null;
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 310.658924;
		$messages[1]->StickyInfo->AnchorY = 584.951272;
		$messages[1]->StickyInfo->Left = 310.658924;
		$messages[1]->StickyInfo->Top = 584.951272;
		$messages[1]->StickyInfo->Width = 180;
		$messages[1]->StickyInfo->Height = 124.881;
		$messages[1]->StickyInfo->Page = 22;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 2;
		$messages[1]->ThreadMessageID = null;
		$messages[1]->ReplyToMessageID = null;
		$messages[1]->MessageStatus = null;
		$messages[1]->ObjectVersion = null;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = null;
		$messages[2]->MessageID = '042EEA33-AB76-4535-94B6-C4A5D7842DD1';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 23 middle';
		$messages[2]->TimeStamp = '2012-03-30T22:53:27';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = null;
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 78.203846;
		$messages[2]->StickyInfo->AnchorY = 268.284605;
		$messages[2]->StickyInfo->Left = 78.203846;
		$messages[2]->StickyInfo->Top = 268.284605;
		$messages[2]->StickyInfo->Width = 338.361;
		$messages[2]->StickyInfo->Height = 155.041;
		$messages[2]->StickyInfo->Page = 23;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 3;
		$messages[2]->ThreadMessageID = null;
		$messages[2]->ReplyToMessageID = null;
		$messages[2]->MessageStatus = null;
		$messages[2]->ObjectVersion = null;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = null;
		$messages[3]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'This is on page 52 bottom';
		$messages[3]->TimeStamp = '2012-03-30T22:53:35';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = null;
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 209.376873;
		$messages[3]->StickyInfo->AnchorY = 611.684565;
		$messages[3]->StickyInfo->Left = 209.376873;
		$messages[3]->StickyInfo->Top = 611.684565;
		$messages[3]->StickyInfo->Width = 229.241;
		$messages[3]->StickyInfo->Height = 123.486039;
		$messages[3]->StickyInfo->Page = 52;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 5;
		$messages[3]->ThreadMessageID = null;
		$messages[3]->ReplyToMessageID = null;
		$messages[3]->MessageStatus = null;
		$messages[3]->ObjectVersion = null;
		if( $this->isV8Client ) { // v8 client
			$request->Objects[0]->MessageList = new MessageList;
			$request->Objects[0]->MessageList->Messages = $messages;
		} else { // v7 client or older
			$request->Objects[0]->MessageList = null;
			$request->Messages = $messages;
		}
		
		$this->unreadStickyReplyCount = count( $messages );
		return $request;
	}	

	/** 
	 * Construct WflSaveObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getSaveObjectRequest001()
	 */
	private function getExpectedSaveObjectResponse001()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = null;
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = '';
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = '';
		$messages[0]->ReplyToMessageID = '';
		$messages[0]->MessageStatus = 'None';
		$messages[0]->ObjectVersion = '0.2';
		$messages[0]->Id = '899300500';
		$messages[0]->IsRead = false;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = null;
		$messages[1]->MessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 22 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:18';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = '';
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 310.658924;
		$messages[1]->StickyInfo->AnchorY = 584.951272;
		$messages[1]->StickyInfo->Left = 310.658924;
		$messages[1]->StickyInfo->Top = 584.951272;
		$messages[1]->StickyInfo->Width = 180;
		$messages[1]->StickyInfo->Height = 124.881;
		$messages[1]->StickyInfo->Page = 22;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 2;
		$messages[1]->ThreadMessageID = '';
		$messages[1]->ReplyToMessageID = '';
		$messages[1]->MessageStatus = 'None';
		$messages[1]->ObjectVersion = '0.2';
		$messages[1]->Id = '899300501';
		$messages[1]->IsRead = false;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = null;
		$messages[2]->MessageID = '042EEA33-AB76-4535-94B6-C4A5D7842DD1';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 23 middle';
		$messages[2]->TimeStamp = '2012-03-30T22:53:27';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = '';
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 78.203846;
		$messages[2]->StickyInfo->AnchorY = 268.284605;
		$messages[2]->StickyInfo->Left = 78.203846;
		$messages[2]->StickyInfo->Top = 268.284605;
		$messages[2]->StickyInfo->Width = 338.361;
		$messages[2]->StickyInfo->Height = 155.041;
		$messages[2]->StickyInfo->Page = 23;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 3;
		$messages[2]->ThreadMessageID = '';
		$messages[2]->ReplyToMessageID = '';
		$messages[2]->MessageStatus = 'None';
		$messages[2]->ObjectVersion = '0.2';
		$messages[2]->Id = '899300502';
		$messages[2]->IsRead = false;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = null;
		$messages[3]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'This is on page 52 bottom';
		$messages[3]->TimeStamp = '2012-03-30T22:53:35';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = '';
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 209.376873;
		$messages[3]->StickyInfo->AnchorY = 611.684565;
		$messages[3]->StickyInfo->Left = 209.376873;
		$messages[3]->StickyInfo->Top = 611.684565;
		$messages[3]->StickyInfo->Width = 229.241;
		$messages[3]->StickyInfo->Height = 123.486039;
		$messages[3]->StickyInfo->Page = 52;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 5;
		$messages[3]->ThreadMessageID = '';
		$messages[3]->ReplyToMessageID = '';
		$messages[3]->MessageStatus = 'None';
		$messages[3]->ObjectVersion = '0.2';
		$messages[3]->Id = '899300503';
		$messages[3]->IsRead = false;
		
		if( $this->isV8Client ) {
			$response->Objects[0]->MessageList = new MessageList();
			$response->Objects[0]->MessageList->Messages = $messages;
		} else {
			$response->Objects[0]->Messages = $messages;
		}
		return $response;
	}

	/**
	 * Call saveObject service call to save layout.
	 * Several old sticky notes are removed and some new ones are
	 * placed on to the layout before layout is being saved.
	 */	 	
	private function callSaveObject002()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSaveObjectRequest002();
		$recResp = $this->getExpectedSaveObjectResponse002();
	
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked			
			if( $this->isV8Client ) {
				$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
				$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
				$clientVr = 'v8';
			} else {
				$recRespMessages = $recResp->Objects[0]->Messages;
				$curRespMessages = $curResp->Objects[0]->Messages;
				$clientVr = 'v7';
			}
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callSaveObject002-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callSaveObject002-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflSaveObjects response.');
				return;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SaveObjects: failed: "'.$e->getMessage().'"' );
			$curResp = null;
		}	
	}

	/**
	 * Construct WflSaveObjectsRequest object.
	 * @return WflSaveObjectsRequest
	 */
	private function getSaveObjectRequest002()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6350DE95092068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 307200;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#009_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;
	
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = null;
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = null;
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = null;
		$messages[0]->ReplyToMessageID = null;
		$messages[0]->MessageStatus = null;
		$messages[0]->ObjectVersion = null;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = null;
		$messages[1]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 52 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:35';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = null;
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 209.376873;
		$messages[1]->StickyInfo->AnchorY = 611.684565;
		$messages[1]->StickyInfo->Left = 209.376873;
		$messages[1]->StickyInfo->Top = 611.684565;
		$messages[1]->StickyInfo->Width = 229.241;
		$messages[1]->StickyInfo->Height = 123.486039;
		$messages[1]->StickyInfo->Page = 52;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 5;
		$messages[1]->ThreadMessageID = null;
		$messages[1]->ReplyToMessageID = null;
		$messages[1]->MessageStatus = null;
		$messages[1]->ObjectVersion = null;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = null;
		$messages[2]->MessageID = 'F1C6F7B8-9922-46E8-9EFD-66CA54FD1504';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 21 top';
		$messages[2]->TimeStamp = '2012-03-30T22:55:33';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = null;
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 79.485897;
		$messages[2]->StickyInfo->AnchorY = 70.9436;
		$messages[2]->StickyInfo->Left = 79.485897;
		$messages[2]->StickyInfo->Top = 70.9436;
		$messages[2]->StickyInfo->Width = 285.881;
		$messages[2]->StickyInfo->Height = 207.481;
		$messages[2]->StickyInfo->Page = 21;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 1;
		$messages[2]->ThreadMessageID = null;
		$messages[2]->ReplyToMessageID = null;
		$messages[2]->MessageStatus = null;
		$messages[2]->ObjectVersion = null;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = null;
		$messages[3]->MessageID = '90B8B2A8-B585-4020-8CE7-4008C0CBCDF2';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'this is on page 51 middle';
		$messages[3]->TimeStamp = '2012-03-30T22:55:42';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = null;
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 192.30641;
		$messages[3]->StickyInfo->AnchorY = 302.805098;
		$messages[3]->StickyInfo->Left = 192.30641;
		$messages[3]->StickyInfo->Top = 302.805098;
		$messages[3]->StickyInfo->Width = 258.721;
		$messages[3]->StickyInfo->Height = 134.441;
		$messages[3]->StickyInfo->Page = 51;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 4;
		$messages[3]->ThreadMessageID = null;
		$messages[3]->ReplyToMessageID = null;
		$messages[3]->MessageStatus = null;
		$messages[3]->ObjectVersion = null;
		
		$messageIdsToBeDeleted = array();
		$messageIdsToBeDeleted[0] = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
		$messageIdsToBeDeleted[1] = '042EEA33-AB76-4535-94B6-C4A5D7842DD1';
		
		if( $this->isV8Client ) { // v8 client
			$request->Objects[0]->MessageList = new MessageList;
			$request->Objects[0]->MessageList->Messages = $messages;

			// For message deletions
			$request->Objects[0]->MessageList->DeleteMessageIDs = $messageIdsToBeDeleted;
			
		} else { // v7 client or older
			$request->Objects[0]->MessageList = null;
			$request->Messages = $messages;

			// For message deletions
			$request->ReadMessageIDs = $messageIdsToBeDeleted;
		}
		return $request;
	}	

	/** 
	 * Construct WflSaveObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getSaveObjectRequest002()
	 */
	private function getExpectedSaveObjectResponse002()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = null;
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = '';
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = '';
		$messages[0]->ReplyToMessageID = '';
		$messages[0]->MessageStatus = 'None';
		$messages[0]->ObjectVersion = '0.3';
		$messages[0]->Id = '899300500';
		$messages[0]->IsRead = false;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = null;
		$messages[1]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 52 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:35';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = '';
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 209.376873;
		$messages[1]->StickyInfo->AnchorY = 611.684565;
		$messages[1]->StickyInfo->Left = 209.376873;
		$messages[1]->StickyInfo->Top = 611.684565;
		$messages[1]->StickyInfo->Width = 229.241;
		$messages[1]->StickyInfo->Height = 123.486039;
		$messages[1]->StickyInfo->Page = 52;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 5;
		$messages[1]->ThreadMessageID = '';
		$messages[1]->ReplyToMessageID = '';
		$messages[1]->MessageStatus = 'None';
		$messages[1]->ObjectVersion = '0.3';
		$messages[1]->Id = '899300503';
		$messages[1]->IsRead = false;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = null;
		$messages[2]->MessageID = 'F1C6F7B8-9922-46E8-9EFD-66CA54FD1504';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 21 top';
		$messages[2]->TimeStamp = '2012-03-30T22:55:33';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = '';
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 79.485897;
		$messages[2]->StickyInfo->AnchorY = 70.9436;
		$messages[2]->StickyInfo->Left = 79.485897;
		$messages[2]->StickyInfo->Top = 70.9436;
		$messages[2]->StickyInfo->Width = 285.881;
		$messages[2]->StickyInfo->Height = 207.481;
		$messages[2]->StickyInfo->Page = 21;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 1;
		$messages[2]->ThreadMessageID = '';
		$messages[2]->ReplyToMessageID = '';
		$messages[2]->MessageStatus = 'None';
		$messages[2]->ObjectVersion = '0.3';
		$messages[2]->Id = '899300900';
		$messages[2]->IsRead = false;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = null;
		$messages[3]->MessageID = '90B8B2A8-B585-4020-8CE7-4008C0CBCDF2';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'this is on page 51 middle';
		$messages[3]->TimeStamp = '2012-03-30T22:55:42';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = '';
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 192.30641;
		$messages[3]->StickyInfo->AnchorY = 302.805098;
		$messages[3]->StickyInfo->Left = 192.30641;
		$messages[3]->StickyInfo->Top = 302.805098;
		$messages[3]->StickyInfo->Width = 258.721;
		$messages[3]->StickyInfo->Height = 134.441;
		$messages[3]->StickyInfo->Page = 51;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 4;
		$messages[3]->ThreadMessageID = '';
		$messages[3]->ReplyToMessageID = '';
		$messages[3]->MessageStatus = 'None';
		$messages[3]->ObjectVersion = '0.3';
		$messages[3]->Id = '899300901';
		$messages[3]->IsRead = false;
		if( $this->isV8Client ) {
			$response->Objects[0]->MessageList = new MessageList();
			$response->Objects[0]->MessageList->Messages = $messages;
		} else {
			$response->Objects[0]->Messages = $messages;
		}
		return $response;
	}

	/**
	 * Call saveObject service call to save layout.
	 * All sticky notes are removed from the layout
	 * before layout is being saved.
	 */
	private function callSaveObject003()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSaveObjectRequest003();
		$recResp = $this->getExpectedSaveObjectResponse003();
	
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			if( $this->isV8Client ) {
				$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
				$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
				$clientVr = 'v8';
			} else {
				$recRespMessages = $recResp->Objects[0]->Messages;
				$curRespMessages = $curResp->Objects[0]->Messages;
				$clientVr = 'v7';
			}
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callSaveObject003-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callSaveObject003-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflSaveObjects response.');
				return;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SaveObjects: failed: "'.$e->getMessage().'"' );
			$curResp = null;
		}
	}

	/**
	 * Construct WflSaveObjectsRequest object.
	 * @return WflSaveObjectsRequest
	 */
	private function getSaveObjectRequest003()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6350DE95092068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 466944;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;

		$messageIdsToBeDeleted = array();
		$messageIdsToBeDeleted[0] = '90B8B2A8-B585-4020-8CE7-4008C0CBCDF2';
		$messageIdsToBeDeleted[1] = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messageIdsToBeDeleted[2] = 'F1C6F7B8-9922-46E8-9EFD-66CA54FD1504';
 		$messageIdsToBeDeleted[3] = '409163C9-D8BC-455C-A441-8DA47949B046';
		
		if( $this->isV8Client ) {
			$request->Objects[0]->MessageList = New MessageList();
			$request->Objects[0]->MessageList->Messages = array();
			
			// For message deletions
			$request->Objects[0]->MessageList->DeleteMessageIDs = $messageIdsToBeDeleted;
			
		} else {
			$request->Objects[0]->MessageList = null;
			$request->Messages = array();

			// For message deletions
			$request->ReadMessageIDs = $messageIdsToBeDeleted;
		}		
		return $request;
	}
	
	/** 
	 * Construct WflSaveObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getSaveObjectRequest003()
	 */	
	private function getExpectedSaveObjectResponse003()
	{
		$response = new WflSaveObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		if( $this->isV8Client ) {
			$response->Objects[0]->MessageList = new MessageList();
			$response->Objects[0]->MessageList->Messages = array();
		} else {
			$response->Objects[0]->Messages = array();
		}
		return $response;
	}	


	/**
	 * This function has the same purpose as callSendMessagesToReplySticky(),
	 * that is to reply sticky note.
	 * The difference is this function uses saveObjects 
	 * service call(Used by SmartConnection) to reply sticky, 
	 * where else callSendMessagesToReplySticky() uses
	 * sendMessages service call(Used by Content Station) to reply sticky.
	 */	 
	private function callSaveObjectToReplySticky()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSaveObjectRequestToReplySticky();
		
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			 // For validation later
			$this->msgSettingsSet[$this->msgsettingsCount]['layoutVersion'] = $curResp->Objects[0]->MetaData->WorkflowMetaData->Version;
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SaveObjects: failed: "'.$e->getMessage().'"' );
		}	
	}
	
	/**
	 * Construct WflSaveObjectsRequest object.
	 * @return WflSaveObjectsRequest
	 */
	private function getSaveObjectRequestToReplySticky()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6350DE95092068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 466944;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;

		
		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check.
		
			$uniqueMsgId = $this->msgSettingsSet[$this->msgsettingsCount]['messageId'];
			$repMsg      = $this->msgSettingsSet[$this->msgsettingsCount]['repMsg'];
			$threadMsgId = $this->msgSettingsSet[$this->msgsettingsCount]['threadMsgId'];
			$repToMsgId  = $this->msgSettingsSet[$this->msgsettingsCount]['repToMsgId'];
			
			$messages = array();
			$messages[0] = new Message();
			$messages[0]->ObjectID = $this->layoutId;
			$messages[0]->UserID = null;
			$messages[0]->MessageID = $uniqueMsgId; // e.g. '1806D271-5DDD-4396-B2BB-A60878527D12';
			$messages[0]->MessageType = 'reply';
			$messages[0]->MessageTypeDetail = '';
			$messages[0]->Message = $repMsg; // e.g. This is on page 23 bottom - Reply 1
			$messages[0]->TimeStamp = $this->timeStampMsgSent;
			$messages[0]->Expiration = null;
			$messages[0]->MessageLevel = '';
			$messages[0]->FromUser = $this->suiteOpts['User'];
//			$messages[0]->StickyInfo // Not applicable for messageType = 'reply'
			$messages[0]->ThreadMessageID = $threadMsgId; // e.g DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE
			$messages[0]->ReplyToMessageID = $repToMsgId; // e.g DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE
			$messages[0]->MessageStatus = 'None';
			$messages[0]->IsRead = false;
			
			$request->Objects[0]->MessageList = new MessageList();
			$request->Objects[0]->MessageList->Messages = $messages;
			
			$this->unreadStickyReplyCount+= count( $messages );		
		}
		return $request;
	}

	/**
	 * Call saveObject service call to save layout.
	 * A sticky note, reply note or all sticky and reply note
	 * is deleted before layout is being saved.
	 *
	 * @param string $expectedError Expected error code or error message for the web service call.
	 */	
	private function callSaveObjectToDeleteSticky( $expectedError = '' )
	{
		// When a certain error is expected, decrease its serverity to INFO to avoid 
		// errors and warnings in the server log.
		if( $expectedError ) {
			$map = new BizExceptionSeverityMap( array( $expectedError => 'INFO' ) );
		}
		
		// Run the SaveObjects service.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSaveObjectRequestToDeleteSticky();
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			if( !$expectedError && $curResp->Reports ) {
				$clientVr = 'v8';
				$curRespFile = LogHandler::logPhpObject( $curResp->Reports, 'print_r', 'callSaveObjectToDeleteSticky-' . $clientVr );
				$errorMsg = 'While deleting sticky or reply using saveObject service, error encountered which is not expected.<br/>' . PHP_EOL;
				$errorMsg .= 'Error returned by saveObjects service(which was not expected) is captured in: ' . $curRespFile . '<br/>';
				$this->setResult( 'ERROR', 'SaveObjects to delete sticky failed: '.$errorMsg );
				return;
			}
		} catch ( BizException $e ) {
			if( !$expectedError ) {
				$this->setResult( 'ERROR', 'SaveObjects to delete sticky: failed: "'.$e->getMessage().'"' );
			}
		}	
	}
	
	/**
	 * Construct WflSaveObjectsRequest object.
	 * @return WflSaveObjectsRequest
	 */	
	private function getSaveObjectRequestToDeleteSticky()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6350DE95092068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 466944;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;

		
		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check
			$request->Objects[0]->MessageList = new MessageList();
			$request->Objects[0]->MessageList->DeleteMessageIDs = array( $this->replyNoteMsgId );
		}
		return $request;
	}

	/**
	 * It calls saveObject service call to mark message(s) as read.
	 * The function updates $this->unreadStickyReplyCount to decrease
	 * the unread messages count.
	 */
	private function callSaveObjectToMarkMessageAsRead()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSaveObjectRequestToMarkMessageAsRead();
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/* $curResp =  */$this->runService( $req );
			$messagesMarkedAsRead = count( $req->Objects[0]->MessageList->ReadMessageIDs );
			$this->unreadStickyReplyCount -= $messagesMarkedAsRead; // To keep track how many messages are still unread.
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SaveObjects to mark message as read: failed: "'.$e->getMessage().'"' );
		}	
	}
	
	/**
	 * Construct WflSaveObjectsRequest object.
	 * @return WflSaveObjectsRequest
	 */
	private function getSaveObjectRequestToMarkMessageAsRead()
	{
		$request = new WflSaveObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->CreateVersion = true;
		$request->ForceCheckIn = false;
		$request->Unlock = false;
		$request->Objects = array();
		$request->Objects[0] = new Object();
		$request->Objects[0]->MetaData = new MetaData();
		$request->Objects[0]->MetaData->BasicMetaData = new BasicMetaData();
		$request->Objects[0]->MetaData->BasicMetaData->ID = $this->layoutId;
		$request->Objects[0]->MetaData->BasicMetaData->DocumentID = 'xmp.did:6350DE95092068118A6DA9A757153A36';
		$request->Objects[0]->MetaData->BasicMetaData->Name = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Objects[0]->MetaData->BasicMetaData->Type = 'Layout';
		$request->Objects[0]->MetaData->BasicMetaData->Publication = new Publication();
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Id = $this->pubObj->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Publication->Name = $this->pubObj->Name;
		$request->Objects[0]->MetaData->BasicMetaData->Category = new Category();
		$request->Objects[0]->MetaData->BasicMetaData->Category->Id = $this->category->Id;
		$request->Objects[0]->MetaData->BasicMetaData->Category->Name = $this->category->Name;
		$request->Objects[0]->MetaData->BasicMetaData->ContentSource = null;
		$request->Objects[0]->MetaData->RightsMetaData = new RightsMetaData();
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightMarked = false;
		$request->Objects[0]->MetaData->RightsMetaData->Copyright = null;
		$request->Objects[0]->MetaData->RightsMetaData->CopyrightURL = null;
		$request->Objects[0]->MetaData->SourceMetaData = new SourceMetaData();
		$request->Objects[0]->MetaData->SourceMetaData->Credit = null;
		$request->Objects[0]->MetaData->SourceMetaData->Source = null;
		$request->Objects[0]->MetaData->SourceMetaData->Author = null;
		$request->Objects[0]->MetaData->ContentMetaData = new ContentMetaData();
		$request->Objects[0]->MetaData->ContentMetaData->Description = null;
		$request->Objects[0]->MetaData->ContentMetaData->DescriptionAuthor = null;
		$request->Objects[0]->MetaData->ContentMetaData->Keywords = null;
		$request->Objects[0]->MetaData->ContentMetaData->Slugline = null;
		$request->Objects[0]->MetaData->ContentMetaData->Format = 'application/indesign';
		$request->Objects[0]->MetaData->ContentMetaData->Columns = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Width = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Height = 0;
		$request->Objects[0]->MetaData->ContentMetaData->Dpi = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthWords = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthChars = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthParas = 0;
		$request->Objects[0]->MetaData->ContentMetaData->LengthLines = 0;
		$request->Objects[0]->MetaData->ContentMetaData->PlainContent = null;
		$request->Objects[0]->MetaData->ContentMetaData->FileSize = 466944;
		$request->Objects[0]->MetaData->ContentMetaData->ColorSpace = null;
		$request->Objects[0]->MetaData->ContentMetaData->HighResFile = null;
		$request->Objects[0]->MetaData->ContentMetaData->Encoding = null;
		$request->Objects[0]->MetaData->ContentMetaData->Compression = null;
		$request->Objects[0]->MetaData->ContentMetaData->KeyFrameEveryFrames = null;
		$request->Objects[0]->MetaData->ContentMetaData->Channels = null;
		$request->Objects[0]->MetaData->ContentMetaData->AspectRatio = null;
		$request->Objects[0]->MetaData->WorkflowMetaData = new WorkflowMetaData();
		$request->Objects[0]->MetaData->WorkflowMetaData->Deadline = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Urgency = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modifier = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Modified = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Creator = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Created = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Comment = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->State = new State();
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Id = $this->layoutStatus->Id;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Name = $this->layoutStatus->Name;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Type = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Produce = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->Color = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->State->DefaultRouteTo = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->RouteTo = '';
		$request->Objects[0]->MetaData->WorkflowMetaData->LockedBy = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Version = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->DeadlineSoft = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Rating = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deletor = null;
		$request->Objects[0]->MetaData->WorkflowMetaData->Deleted = null;
		$request->Objects[0]->MetaData->ExtraMetaData = null;
		$request->Objects[0]->Relations = array();
		$request->Objects[0]->Pages = array();
		$request->Objects[0]->Pages[0] = new Page();
		$request->Objects[0]->Pages[0]->Width = 595.275591;
		$request->Objects[0]->Pages[0]->Height = 841.889764;
		$request->Objects[0]->Pages[0]->PageNumber = '21';
		$request->Objects[0]->Pages[0]->PageOrder = 21;
		$request->Objects[0]->Pages[0]->Files = array();
		$request->Objects[0]->Pages[0]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[0]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[0]->Content = null;
		$request->Objects[0]->Pages[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#000_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[0] );
		$request->Objects[0]->Pages[0]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[0]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[0]->Files[1]->Content = null;
		$request->Objects[0]->Pages[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#001_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[0]->Files[1] );
		$request->Objects[0]->Pages[0]->Edition = null;
		$request->Objects[0]->Pages[0]->Master = 'Master';
		$request->Objects[0]->Pages[0]->Instance = 'Production';
		$request->Objects[0]->Pages[0]->PageSequence = 1;
		$request->Objects[0]->Pages[0]->Renditions = null;
		$request->Objects[0]->Pages[1] = new Page();
		$request->Objects[0]->Pages[1]->Width = 595.275591;
		$request->Objects[0]->Pages[1]->Height = 841.889764;
		$request->Objects[0]->Pages[1]->PageNumber = '22';
		$request->Objects[0]->Pages[1]->PageOrder = 22;
		$request->Objects[0]->Pages[1]->Files = array();
		$request->Objects[0]->Pages[1]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[1]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[0]->Content = null;
		$request->Objects[0]->Pages[1]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#002_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[0] );
		$request->Objects[0]->Pages[1]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[1]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[1]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[1]->Files[1]->Content = null;
		$request->Objects[0]->Pages[1]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[1]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[1]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#003_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[1]->Files[1] );
		$request->Objects[0]->Pages[1]->Edition = null;
		$request->Objects[0]->Pages[1]->Master = 'Master';
		$request->Objects[0]->Pages[1]->Instance = 'Production';
		$request->Objects[0]->Pages[1]->PageSequence = 2;
		$request->Objects[0]->Pages[1]->Renditions = null;
		$request->Objects[0]->Pages[2] = new Page();
		$request->Objects[0]->Pages[2]->Width = 595.275591;
		$request->Objects[0]->Pages[2]->Height = 841.889764;
		$request->Objects[0]->Pages[2]->PageNumber = '23';
		$request->Objects[0]->Pages[2]->PageOrder = 23;
		$request->Objects[0]->Pages[2]->Files = array();
		$request->Objects[0]->Pages[2]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[2]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[0]->Content = null;
		$request->Objects[0]->Pages[2]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#004_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[0] );
		$request->Objects[0]->Pages[2]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[2]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[2]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[2]->Files[1]->Content = null;
		$request->Objects[0]->Pages[2]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[2]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[2]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#005_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[2]->Files[1] );
		$request->Objects[0]->Pages[2]->Edition = null;
		$request->Objects[0]->Pages[2]->Master = 'Master';
		$request->Objects[0]->Pages[2]->Instance = 'Production';
		$request->Objects[0]->Pages[2]->PageSequence = 3;
		$request->Objects[0]->Pages[2]->Renditions = null;
		$request->Objects[0]->Pages[3] = new Page();
		$request->Objects[0]->Pages[3]->Width = 595.275591;
		$request->Objects[0]->Pages[3]->Height = 841.889764;
		$request->Objects[0]->Pages[3]->PageNumber = '51';
		$request->Objects[0]->Pages[3]->PageOrder = 51;
		$request->Objects[0]->Pages[3]->Files = array();
		$request->Objects[0]->Pages[3]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[3]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[0]->Content = null;
		$request->Objects[0]->Pages[3]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#006_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[0] );
		$request->Objects[0]->Pages[3]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[3]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[3]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[3]->Files[1]->Content = null;
		$request->Objects[0]->Pages[3]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[3]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[3]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#007_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[3]->Files[1] );
		$request->Objects[0]->Pages[3]->Edition = null;
		$request->Objects[0]->Pages[3]->Master = 'Master';
		$request->Objects[0]->Pages[3]->Instance = 'Production';
		$request->Objects[0]->Pages[3]->PageSequence = 4;
		$request->Objects[0]->Pages[3]->Renditions = null;
		$request->Objects[0]->Pages[4] = new Page();
		$request->Objects[0]->Pages[4]->Width = 595.275591;
		$request->Objects[0]->Pages[4]->Height = 841.889764;
		$request->Objects[0]->Pages[4]->PageNumber = '52';
		$request->Objects[0]->Pages[4]->PageOrder = 52;
		$request->Objects[0]->Pages[4]->Files = array();
		$request->Objects[0]->Pages[4]->Files[0] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[0]->Rendition = 'thumb';
		$request->Objects[0]->Pages[4]->Files[0]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[0]->Content = null;
		$request->Objects[0]->Pages[4]->Files[0]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[0]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#008_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[0] );
		$request->Objects[0]->Pages[4]->Files[1] = new Attachment();
		$request->Objects[0]->Pages[4]->Files[1]->Rendition = 'preview';
		$request->Objects[0]->Pages[4]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Pages[4]->Files[1]->Content = null;
		$request->Objects[0]->Pages[4]->Files[1]->FilePath = '';
		$request->Objects[0]->Pages[4]->Files[1]->FileUrl = null;
		$request->Objects[0]->Pages[4]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#009_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Pages[4]->Files[1] );
		$request->Objects[0]->Pages[4]->Edition = null;
		$request->Objects[0]->Pages[4]->Master = 'Master';
		$request->Objects[0]->Pages[4]->Instance = 'Production';
		$request->Objects[0]->Pages[4]->PageSequence = 5;
		$request->Objects[0]->Pages[4]->Renditions = null;
		$request->Objects[0]->Files = array();
		$request->Objects[0]->Files[0] = new Attachment();
		$request->Objects[0]->Files[0]->Rendition = 'native';
		$request->Objects[0]->Files[0]->Type = 'application/indesign';
		$request->Objects[0]->Files[0]->Content = null;
		$request->Objects[0]->Files[0]->FilePath = '';
		$request->Objects[0]->Files[0]->FileUrl = null;
		$request->Objects[0]->Files[0]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#010_native.indd';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[0] );
		$request->Objects[0]->Files[1] = new Attachment();
		$request->Objects[0]->Files[1]->Rendition = 'thumb';
		$request->Objects[0]->Files[1]->Type = 'image/jpeg';
		$request->Objects[0]->Files[1]->Content = null;
		$request->Objects[0]->Files[1]->FilePath = '';
		$request->Objects[0]->Files[1]->FileUrl = null;
		$request->Objects[0]->Files[1]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#011_thumb.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[1] );
		$request->Objects[0]->Files[2] = new Attachment();
		$request->Objects[0]->Files[2]->Rendition = 'preview';
		$request->Objects[0]->Files[2]->Type = 'image/jpeg';
		$request->Objects[0]->Files[2]->Content = null;
		$request->Objects[0]->Files[2]->FilePath = '';
		$request->Objects[0]->Files[2]->FileUrl = null;
		$request->Objects[0]->Files[2]->EditionId = '';
		$inputPath = dirname(__FILE__).'/testdata/rec#012_att#012_preview.jpg';
		$this->transferServer->copyToFileTransferServer( $inputPath, $request->Objects[0]->Files[2] );
		$request->Objects[0]->Messages = null;
		$request->Objects[0]->Elements = null;
		$request->Objects[0]->Targets = array();
		$request->Objects[0]->Targets[0] = new Target();
		$request->Objects[0]->Targets[0]->PubChannel = new PubChannel();
		$request->Objects[0]->Targets[0]->PubChannel->Id = $this->pubChannelObj->Id;
		$request->Objects[0]->Targets[0]->PubChannel->Name = $this->pubChannelObj->Name;
		$request->Objects[0]->Targets[0]->Issue = new Issue();
		$request->Objects[0]->Targets[0]->Issue->Id = $this->issueObj->Id;
		$request->Objects[0]->Targets[0]->Issue->Name = $this->issueObj->Name;
		$request->Objects[0]->Targets[0]->Issue->OverrulePublication = null;
		$request->Objects[0]->Targets[0]->Editions = array();
		$request->Objects[0]->Targets[0]->Editions[0] = new Edition();
		$request->Objects[0]->Targets[0]->Editions[0]->Id = $this->editionObj->Id;
		$request->Objects[0]->Targets[0]->Editions[0]->Name = $this->editionObj->Name;
		$request->Objects[0]->Targets[0]->PublishedDate = null;
		$request->Objects[0]->Targets[0]->PublishedVersion = null;
		$request->Objects[0]->Renditions = null;

		
		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check
			$request->Objects[0]->MessageList = new MessageList();
			$readMessageIds = array();
			foreach( $this->msgSettingsSet as $msgSettings ) {
				$readMessageIds[] = $msgSettings['messageId'];
			}
			$request->Objects[0]->MessageList->ReadMessageIDs = $readMessageIds;
		}
		return $request;	
	}
	

////////////////////// GetObjects Service call(s) ////////////////////////////////////	
	/**
	 * Returns the object layout version based on the $this->layoutId.
	 * When it is not found in the getObjects service, it returns Null.
	 * @return int|NULL Layout version id Or Null when version is not found.
	 */
	private function getCurrentLayoutVersion()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';	
		$req = $this->getObjectsRequestToGetLayoutVersion();
		
		$layoutVersion = null;
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			$layoutVersion = isset( $curResp->Objects[0]->MetaData->WorkflowMetaData->Version ) ? 
									$curResp->Objects[0]->MetaData->WorkflowMetaData->Version : null;
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'Cannot determine Layout version for layout (id='.$this->layoutId.') as GetObjects: failed: "'.
										$e->getMessage().'"' );							
		}
		return $layoutVersion;
	}

	/**
	 * Construct WflGetObjectsRequest object.
	 * @return WflGetObjectsRequest
	 */
	private function getObjectsRequestToGetLayoutVersion()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->Lock = false;
		$request->Rendition = 'none';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		return $request;
	}	
	
	/**
	 * Call getObjects service call to re-open a layout.
	 * The getObjectsResponse->Messages is checked and validated with the
	 * expected Messages data structure.
	 */	
	private function callGetObjects001()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->getObjectsRequest001();
		$recResp = $this->getExpectedGetObjectsResponse001();

		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
			$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
			$clientVr = 'v8';
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callGetObjects001-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callGetObjects001-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetObjects response.');
				return;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetObjects: failed: "'.$e->getMessage().'"' );
		}		
	}

	/**
	 * Construct WflGetObjectsRequest object.
	 * @return WflGetObjectsRequest
	 */
	private function getObjectsRequest001()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		return $request;
	}
	
	/** 
	 * Construct WflGetObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getObjectsRequest001()
	 */	
	private function getExpectedGetObjectsResponse001()
	{
		$response = new WflGetObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = null;
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = '';
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = '';
		$messages[0]->ReplyToMessageID = '';
		$messages[0]->MessageStatus = 'None';
		$messages[0]->ObjectVersion = '0.2';
		$messages[0]->Id = '899300500';
		$messages[0]->IsRead = false;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = null;
		$messages[1]->MessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 22 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:18';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = '';
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 310.658924;
		$messages[1]->StickyInfo->AnchorY = 584.951272;
		$messages[1]->StickyInfo->Left = 310.658924;
		$messages[1]->StickyInfo->Top = 584.951272;
		$messages[1]->StickyInfo->Width = 180;
		$messages[1]->StickyInfo->Height = 124.881;
		$messages[1]->StickyInfo->Page = 22;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 2;
		$messages[1]->ThreadMessageID = '';
		$messages[1]->ReplyToMessageID = '';
		$messages[1]->MessageStatus = 'None';
		$messages[1]->ObjectVersion = '0.2';
		$messages[1]->Id = '899300501';
		$messages[1]->IsRead = false;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = null;
		$messages[2]->MessageID = '042EEA33-AB76-4535-94B6-C4A5D7842DD1';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 23 middle';
		$messages[2]->TimeStamp = '2012-03-30T22:53:27';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = '';
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 78.203846;
		$messages[2]->StickyInfo->AnchorY = 268.284605;
		$messages[2]->StickyInfo->Left = 78.203846;
		$messages[2]->StickyInfo->Top = 268.284605;
		$messages[2]->StickyInfo->Width = 338.361;
		$messages[2]->StickyInfo->Height = 155.041;
		$messages[2]->StickyInfo->Page = 23;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 3;
		$messages[2]->ThreadMessageID = '';
		$messages[2]->ReplyToMessageID = '';
		$messages[2]->MessageStatus = 'None';
		$messages[2]->ObjectVersion = '0.2';
		$messages[2]->Id = '899300502';
		$messages[2]->IsRead = false;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = null;
		$messages[3]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'This is on page 52 bottom';
		$messages[3]->TimeStamp = '2012-03-30T22:53:35';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = '';
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 209.376873;
		$messages[3]->StickyInfo->AnchorY = 611.684565;
		$messages[3]->StickyInfo->Left = 209.376873;
		$messages[3]->StickyInfo->Top = 611.684565;
		$messages[3]->StickyInfo->Width = 229.241;
		$messages[3]->StickyInfo->Height = 123.486039;
		$messages[3]->StickyInfo->Page = 52;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 5;
		$messages[3]->ThreadMessageID = '';
		$messages[3]->ReplyToMessageID = '';
		$messages[3]->MessageStatus = 'None';
		$messages[3]->ObjectVersion = '0.2';
		$messages[3]->Id = '899300503';
		$messages[3]->IsRead = false;
		
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = $messages;
		return $response;
	}

	/**
	 * Call getObjects service call to re-open a layout.
	 * The getObjectsResponse->Messages is checked and validated with the
	 * expected Messages data structure.
	 */	
	private function callGetObjects002()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->getObjectsRequest002();
		$recResp = $this->getExpectedGetObjectsResponse002();
	
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
			$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
			$clientVr = 'v8';
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callGetObjects002-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callGetObjects002-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetObjects response.');
				return;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetObjects: failed: "'.$e->getMessage().'"' );
		}
	}
	
	/**
	 * Construct WflGetObjectsRequest object.
	 * @return WflGetObjectsRequest
	 */	
	private function getObjectsRequest002()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->Lock = true;
		$request->Rendition = 'native';
		$request->RequestInfo = null;
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		return $request;
	}

	/** 
	 * Construct WflGetObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getObjectsRequest002()
	 */		
	private function getExpectedGetObjectsResponse002()
	{
		$response = new WflGetObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = null;
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = '';
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = '';
		$messages[0]->ReplyToMessageID = '';
		$messages[0]->MessageStatus = 'None';
		$messages[0]->ObjectVersion = '0.3';
		$messages[0]->Id = '899300500';
		$messages[0]->IsRead = false;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = null;
		$messages[1]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 52 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:35';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = '';
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 209.376873;
		$messages[1]->StickyInfo->AnchorY = 611.684565;
		$messages[1]->StickyInfo->Left = 209.376873;
		$messages[1]->StickyInfo->Top = 611.684565;
		$messages[1]->StickyInfo->Width = 229.241;
		$messages[1]->StickyInfo->Height = 123.486039;
		$messages[1]->StickyInfo->Page = 52;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 5;
		$messages[1]->ThreadMessageID = '';
		$messages[1]->ReplyToMessageID = '';
		$messages[1]->MessageStatus = 'None';
		$messages[1]->ObjectVersion = '0.3';
		$messages[1]->Id = '899300503';
		$messages[1]->IsRead = false;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = null;
		$messages[2]->MessageID = 'F1C6F7B8-9922-46E8-9EFD-66CA54FD1504';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 21 top';
		$messages[2]->TimeStamp = '2012-03-30T22:55:33';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = '';
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 79.485897;
		$messages[2]->StickyInfo->AnchorY = 70.9436;
		$messages[2]->StickyInfo->Left = 79.485897;
		$messages[2]->StickyInfo->Top = 70.9436;
		$messages[2]->StickyInfo->Width = 285.881;
		$messages[2]->StickyInfo->Height = 207.481;
		$messages[2]->StickyInfo->Page = 21;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 1;
		$messages[2]->ThreadMessageID = '';
		$messages[2]->ReplyToMessageID = '';
		$messages[2]->MessageStatus = 'None';
		$messages[2]->ObjectVersion = '0.3';
		$messages[2]->Id = '899300900';
		$messages[2]->IsRead = false;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = null;
		$messages[3]->MessageID = '90B8B2A8-B585-4020-8CE7-4008C0CBCDF2';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'this is on page 51 middle';
		$messages[3]->TimeStamp = '2012-03-30T22:55:42';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = '';
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 192.30641;
		$messages[3]->StickyInfo->AnchorY = 302.805098;
		$messages[3]->StickyInfo->Left = 192.30641;
		$messages[3]->StickyInfo->Top = 302.805098;
		$messages[3]->StickyInfo->Width = 258.721;
		$messages[3]->StickyInfo->Height = 134.441;
		$messages[3]->StickyInfo->Page = 51;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 4;
		$messages[3]->ThreadMessageID = '';
		$messages[3]->ReplyToMessageID = '';
		$messages[3]->MessageStatus = 'None';
		$messages[3]->ObjectVersion = '0.3';
		$messages[3]->Id = '899300901';
		$messages[3]->IsRead = false;
		
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = $messages;
		return $response;
	}

	/**
	 * This function simulates Publication Overview in Content Station.
	 * It does a getObjects service call and compare the Messages.
	 */
	private function pubOverviewCallGetObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->getGetObjectsRequestForPubOverview();
		$recResp = $this->getExpectedGetObjectsRespForPubOverview();

	
		try {	
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
	
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked
			$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
			$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
			$clientVr = 'v8';

			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callGetObjectsForPubOverview-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callGetObjectsForPubOverview-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetObjects response.');
				return;
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetObjects: failed: "'.$e->getMessage().'"' );
			$curResp = null;
		}
	}
	
	/**
	 * Construct WflGetObjectsRequest object.
	 * @return WflGetObjectsRequest
	 */
	private function getGetObjectsRequestForPubOverview()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->Lock = false;
		$request->Rendition = 'thumb';
		$request->RequestInfo = array();
		$request->RequestInfo[0] = 'Pages';
		$request->RequestInfo[1] = 'Messages';
		$request->RequestInfo[2] = 'Targets';
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		return $request;
	}	

	/** 
	 * Construct WflGetObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getGetObjectsRequestForPubOverview()
	 */	
	private function getExpectedGetObjectsRespForPubOverview()
	{
		$response = new WflGetObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();

		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = $this->layoutId;
		$messages[0]->UserID = $this->suiteOpts['User'];
		$messages[0]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[0]->MessageType = 'sticky';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = 'This is on page 22 top';
		$messages[0]->TimeStamp = '2012-03-30T22:53:12';
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = '';
		$messages[0]->FromUser = $this->suiteOpts['User'];
		$messages[0]->StickyInfo = new StickyInfo();
		$messages[0]->StickyInfo->AnchorX = 100.402514;
		$messages[0]->StickyInfo->AnchorY = 68.284605;
		$messages[0]->StickyInfo->Left = 100.402514;
		$messages[0]->StickyInfo->Top = 68.284605;
		$messages[0]->StickyInfo->Width = 239.361;
		$messages[0]->StickyInfo->Height = 158.161;
		$messages[0]->StickyInfo->Page = 22;
		$messages[0]->StickyInfo->Version = '0';
		$messages[0]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[0]->StickyInfo->PageSequence = 2;
		$messages[0]->ThreadMessageID = '';
		$messages[0]->ReplyToMessageID = '';
		$messages[0]->MessageStatus = 'None';
		$messages[0]->ObjectVersion = '0.3';
		$messages[0]->Id = '899500319';
		$messages[0]->IsRead = false;
		$messages[1] = new Message();
		$messages[1]->ObjectID = $this->layoutId;
		$messages[1]->UserID = $this->suiteOpts['User'];
		$messages[1]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[1]->MessageType = 'sticky';
		$messages[1]->MessageTypeDetail = '';
		$messages[1]->Message = 'This is on page 52 bottom';
		$messages[1]->TimeStamp = '2012-03-30T22:53:35';
		$messages[1]->Expiration = null;
		$messages[1]->MessageLevel = '';
		$messages[1]->FromUser = $this->suiteOpts['User'];
		$messages[1]->StickyInfo = new StickyInfo();
		$messages[1]->StickyInfo->AnchorX = 209.376873;
		$messages[1]->StickyInfo->AnchorY = 611.684565;
		$messages[1]->StickyInfo->Left = 209.376873;
		$messages[1]->StickyInfo->Top = 611.684565;
		$messages[1]->StickyInfo->Width = 229.241;
		$messages[1]->StickyInfo->Height = 123.486039;
		$messages[1]->StickyInfo->Page = 52;
		$messages[1]->StickyInfo->Version = '0';
		$messages[1]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[1]->StickyInfo->PageSequence = 5;
		$messages[1]->ThreadMessageID = '';
		$messages[1]->ReplyToMessageID = '';
		$messages[1]->MessageStatus = 'None';
		$messages[1]->ObjectVersion = '0.3';
		$messages[1]->Id = '899500322';
		$messages[1]->IsRead = false;
		$messages[2] = new Message();
		$messages[2]->ObjectID = $this->layoutId;
		$messages[2]->UserID = $this->suiteOpts['User'];
		$messages[2]->MessageID = 'F1C6F7B8-9922-46E8-9EFD-66CA54FD1504';
		$messages[2]->MessageType = 'sticky';
		$messages[2]->MessageTypeDetail = '';
		$messages[2]->Message = 'This is on page 21 top';
		$messages[2]->TimeStamp = '2012-03-30T22:55:33';
		$messages[2]->Expiration = null;
		$messages[2]->MessageLevel = '';
		$messages[2]->FromUser = $this->suiteOpts['User'];
		$messages[2]->StickyInfo = new StickyInfo();
		$messages[2]->StickyInfo->AnchorX = 79.485897;
		$messages[2]->StickyInfo->AnchorY = 70.9436;
		$messages[2]->StickyInfo->Left = 79.485897;
		$messages[2]->StickyInfo->Top = 70.9436;
		$messages[2]->StickyInfo->Width = 285.881;
		$messages[2]->StickyInfo->Height = 207.481;
		$messages[2]->StickyInfo->Page = 21;
		$messages[2]->StickyInfo->Version = '0';
		$messages[2]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[2]->StickyInfo->PageSequence = 1;
		$messages[2]->ThreadMessageID = '';
		$messages[2]->ReplyToMessageID = '';
		$messages[2]->MessageStatus = 'None';
		$messages[2]->ObjectVersion = '0.3';
		$messages[2]->Id = '899500323';
		$messages[2]->IsRead = false;
		$messages[3] = new Message();
		$messages[3]->ObjectID = $this->layoutId;
		$messages[3]->UserID = $this->suiteOpts['User'];
		$messages[3]->MessageID = '90B8B2A8-B585-4020-8CE7-4008C0CBCDF2';
		$messages[3]->MessageType = 'sticky';
		$messages[3]->MessageTypeDetail = '';
		$messages[3]->Message = 'this is on page 51 middle';
		$messages[3]->TimeStamp = '2012-03-30T22:55:42';
		$messages[3]->Expiration = null;
		$messages[3]->MessageLevel = '';
		$messages[3]->FromUser = $this->suiteOpts['User'];
		$messages[3]->StickyInfo = new StickyInfo();
		$messages[3]->StickyInfo->AnchorX = 192.30641;
		$messages[3]->StickyInfo->AnchorY = 302.805098;
		$messages[3]->StickyInfo->Left = 192.30641;
		$messages[3]->StickyInfo->Top = 302.805098;
		$messages[3]->StickyInfo->Width = 258.721;
		$messages[3]->StickyInfo->Height = 134.441;
		$messages[3]->StickyInfo->Page = 51;
		$messages[3]->StickyInfo->Version = '0';
		$messages[3]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[3]->StickyInfo->PageSequence = 4;
		$messages[3]->ThreadMessageID = '';
		$messages[3]->ReplyToMessageID = '';
		$messages[3]->MessageStatus = 'None';
		$messages[3]->ObjectVersion = '0.3';
		$messages[3]->Id = '899500324';
		$messages[3]->IsRead = false;
		
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = $messages;
		return $response;
	}
	
	/**
	 * Calls getObjects service.
	 * It checks whether the messages are returned correctly
	 * after 'reply' to the sticky notes actions have been done.
	 */	
	private function callGetObjectsToCheckSticky()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflGetObjectsService.class.php';
		$req = $this->getGetObjectsRequestToCheckSticky();
		$recResp = $this->getExpectedGetObjectsRespToCheckSticky();

		try {	
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
	
			require_once BASEDIR.'/server/utils/PhpCompare.class.php';
			$phpCompare = new WW_Utils_PhpCompare();
			$phpCompare->initCompare( array(), $this->getCommonPropDiff() ); // all properties should be checked

			$recRespMessages = $recResp->Objects[0]->MessageList->Messages;
			$curRespMessages = $curResp->Objects[0]->MessageList->Messages;
			$clientVr = 'v8';
			
			if( !$phpCompare->compareTwoProps( $recRespMessages, $curRespMessages ) ) {
				$recRespFile = LogHandler::logPhpObject( $recResp, 'print_r', 'callGetObjectsToCheckSticky-' . $clientVr );
				$curRespFile = LogHandler::logPhpObject( $curResp, 'print_r', 'callGetObjectsToCheckSticky-' . $clientVr );
				$errorMsg = implode( PHP_EOL, $phpCompare->getErrors() );
				$errorMsg .= 'Recorded response: '.$recRespFile.'<br/>';
				$errorMsg .= 'Current response: '.$curRespFile.'<br/>';
				$this->setResult( 'ERROR', $errorMsg, 'Error occured in WflGetObjects response.');
				return;
			}
			
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'GetObjects (to check sticky): failed: "'.$e->getMessage().'"' );
		}		
	}

	/**
	 * Construct WflGetObjectsRequest object.
	 * @return WflGetObjectsRequest
	 */	
	private function getGetObjectsRequestToCheckSticky()
	{
		$request = new WflGetObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->Lock = false;
		$request->Rendition = 'thumb';
		$request->RequestInfo = array();
		$request->RequestInfo[0] = 'Pages';
		$request->RequestInfo[1] = 'Messages';
		$request->RequestInfo[2] = 'Targets';
		$request->HaveVersions = null;
		$request->Areas = null;
		$request->EditionId = null;
		return $request;
	}

	/** 
	 * Construct WflGetObjectsResponse object.
	 * Response constructed is the expected response
	 * that should be returned by getGetObjectsRequestToCheckSticky()
	 */	
	private function getExpectedGetObjectsRespToCheckSticky()
	{
		$response = new WflGetObjectsResponse();
		$response->Objects = array();
		$response->Objects[0] = new Object();
		
		$indexCounter=0;
		$messages = array();
		
		$messages[$indexCounter] = new Message();
		$messages[$indexCounter]->ObjectID = $this->layoutId;
		$messages[$indexCounter]->UserID = null;
		$messages[$indexCounter]->MessageID = '409163C9-D8BC-455C-A441-8DA47949B046';
		$messages[$indexCounter]->MessageType = 'sticky';
		$messages[$indexCounter]->MessageTypeDetail = '';
		$messages[$indexCounter]->Message = 'This is on page 22 top';
		$messages[$indexCounter]->TimeStamp = '2012-03-30T22:53:12';
		$messages[$indexCounter]->Expiration = null;
		$messages[$indexCounter]->MessageLevel = '';
		$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
		$messages[$indexCounter]->StickyInfo = new StickyInfo();
		$messages[$indexCounter]->StickyInfo->AnchorX = 100.402514;
		$messages[$indexCounter]->StickyInfo->AnchorY = 68.284605;
		$messages[$indexCounter]->StickyInfo->Left = 100.402514;
		$messages[$indexCounter]->StickyInfo->Top = 68.284605;
		$messages[$indexCounter]->StickyInfo->Width = 239.361;
		$messages[$indexCounter]->StickyInfo->Height = 158.161;
		$messages[$indexCounter]->StickyInfo->Page = 22;
		$messages[$indexCounter]->StickyInfo->Version = '0';
		$messages[$indexCounter]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[$indexCounter]->StickyInfo->PageSequence = 2;
		$messages[$indexCounter]->ThreadMessageID = '';
		$messages[$indexCounter]->ReplyToMessageID = '';
		$messages[$indexCounter]->MessageStatus = 'None';
		$messages[$indexCounter]->ObjectVersion = '0.2';
		$messages[$indexCounter]->Id = '899500319';
		$messages[$indexCounter]->IsRead = false;
		$indexCounter++;

		$messages[$indexCounter] = new Message();
		$messages[$indexCounter]->ObjectID = $this->layoutId;
		$messages[$indexCounter]->UserID = null;
		$messages[$indexCounter]->MessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
		$messages[$indexCounter]->MessageType = 'sticky';
		$messages[$indexCounter]->MessageTypeDetail = '';
		$messages[$indexCounter]->Message = 'This is on page 22 bottom';
		$messages[$indexCounter]->TimeStamp = '2012-03-30T22:53:18';
		$messages[$indexCounter]->Expiration = null;
		$messages[$indexCounter]->MessageLevel = '';
		$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
		$messages[$indexCounter]->StickyInfo = new StickyInfo();
		$messages[$indexCounter]->StickyInfo->AnchorX = 310.658924;
		$messages[$indexCounter]->StickyInfo->AnchorY = 584.951272;
		$messages[$indexCounter]->StickyInfo->Left = 310.658924;
		$messages[$indexCounter]->StickyInfo->Top = 584.951272;
		$messages[$indexCounter]->StickyInfo->Width = 180;
		$messages[$indexCounter]->StickyInfo->Height = 124.881;
		$messages[$indexCounter]->StickyInfo->Page = 22;
		$messages[$indexCounter]->StickyInfo->Version = '0';
		$messages[$indexCounter]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[$indexCounter]->StickyInfo->PageSequence = 2;
		$messages[$indexCounter]->ThreadMessageID = '';
		$messages[$indexCounter]->ReplyToMessageID = '';
		$messages[$indexCounter]->MessageStatus = 'None';
		$messages[$indexCounter]->ObjectVersion = '0.2';
		$messages[$indexCounter]->Id = '899500319';
		$messages[$indexCounter]->IsRead = false;
		$indexCounter++;

		if( $this->reply1 == true ) {
			$messages[$indexCounter] = new Message();		
			$messages[$indexCounter]->ObjectID = $this->layoutId;
			$messages[$indexCounter]->UserID = null;
			$messages[$indexCounter]->MessageID = '1806D271-5DDD-4396-B2BB-A60878527D12';
			$messages[$indexCounter]->MessageType = 'reply';
			$messages[$indexCounter]->MessageTypeDetail = '';
			$messages[$indexCounter]->Message = 'This is on page 22 bottom - Reply 1';
			$messages[$indexCounter]->TimeStamp = $this->timeStampMsgSent;
			$messages[$indexCounter]->Expiration = null;
			$messages[$indexCounter]->MessageLevel = '';
			$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
			$messages[$indexCounter]->StickyInfo = null;
			$messages[$indexCounter]->ThreadMessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
			$messages[$indexCounter]->ReplyToMessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
			$messages[$indexCounter]->MessageStatus = 'None';
			$messages[$indexCounter]->ObjectVersion = $this->msgSettingsSet[0]['layoutVersion'];
			$messages[$indexCounter]->Id = '';
			$messages[$indexCounter]->IsRead = false;
			$indexCounter++;
		}

		if( $this->reply2 == true ) {
			$messages[$indexCounter] = new Message();		
			$messages[$indexCounter]->ObjectID = $this->layoutId;
			$messages[$indexCounter]->UserID = null;
			$messages[$indexCounter]->MessageID = '119A5212-B212-41E1-9317-19B014B04098';
			$messages[$indexCounter]->MessageType = 'reply';
			$messages[$indexCounter]->MessageTypeDetail = '';
			$messages[$indexCounter]->Message = 'This is on page 22 bottom - Reply 2';
			$messages[$indexCounter]->TimeStamp = $this->timeStampMsgSent;
			$messages[$indexCounter]->Expiration = null;
			$messages[$indexCounter]->MessageLevel = '';
			$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
			$messages[$indexCounter]->StickyInfo = null;
			$messages[$indexCounter]->ThreadMessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
			$messages[$indexCounter]->ReplyToMessageID = '1806D271-5DDD-4396-B2BB-A60878527D12';
			$messages[$indexCounter]->MessageStatus = 'None';
			$messages[$indexCounter]->ObjectVersion = $this->msgSettingsSet[1]['layoutVersion'];
			$messages[$indexCounter]->Id = '';
			$messages[$indexCounter]->IsRead = false;
			$indexCounter++;		
		
		}

		if( $this->reply3 == true ) {
			$messages[$indexCounter] = new Message();		
			$messages[$indexCounter]->ObjectID = $this->layoutId;
			$messages[$indexCounter]->UserID = null;
			$messages[$indexCounter]->MessageID = 'CF3BCBE3-645F-4F2D-AE50-4B73502237C5';
			$messages[$indexCounter]->MessageType = 'reply';
			$messages[$indexCounter]->MessageTypeDetail = '';
			$messages[$indexCounter]->Message = 'This is on page 22 bottom - Reply 3';
			$messages[$indexCounter]->TimeStamp = $this->timeStampMsgSent;
			$messages[$indexCounter]->Expiration = null;
			$messages[$indexCounter]->MessageLevel = '';
			$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
			$messages[$indexCounter]->StickyInfo = null;
			$messages[$indexCounter]->ThreadMessageID = 'DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE';
			$messages[$indexCounter]->ReplyToMessageID = '119A5212-B212-41E1-9317-19B014B04098';
			$messages[$indexCounter]->MessageStatus = 'None';
			$messages[$indexCounter]->ObjectVersion = $this->msgSettingsSet[2]['layoutVersion'];
			$messages[$indexCounter]->Id = '';
			$messages[$indexCounter]->IsRead = false;
			$indexCounter++;		
		
		}
		
		$messages[$indexCounter] = new Message();
		$messages[$indexCounter]->ObjectID = $this->layoutId;
		$messages[$indexCounter]->UserID = null;
		$messages[$indexCounter]->MessageID = '042EEA33-AB76-4535-94B6-C4A5D7842DD1';
		$messages[$indexCounter]->MessageType = 'sticky';
		$messages[$indexCounter]->MessageTypeDetail = '';
		$messages[$indexCounter]->Message = 'This is on page 23 middle';
		$messages[$indexCounter]->TimeStamp = '2012-03-30T22:53:27';
		$messages[$indexCounter]->Expiration = null;
		$messages[$indexCounter]->MessageLevel = '';
		$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
		$messages[$indexCounter]->StickyInfo = new StickyInfo();
		$messages[$indexCounter]->StickyInfo->AnchorX = 78.203846;
		$messages[$indexCounter]->StickyInfo->AnchorY = 268.284605;
		$messages[$indexCounter]->StickyInfo->Left = 78.203846;
		$messages[$indexCounter]->StickyInfo->Top = 268.284605;
		$messages[$indexCounter]->StickyInfo->Width = 338.361;
		$messages[$indexCounter]->StickyInfo->Height = 155.041;
		$messages[$indexCounter]->StickyInfo->Page = 23;
		$messages[$indexCounter]->StickyInfo->Version = '0';
		$messages[$indexCounter]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[$indexCounter]->StickyInfo->PageSequence = 3;
		$messages[$indexCounter]->ThreadMessageID = '';
		$messages[$indexCounter]->ReplyToMessageID = '';
		$messages[$indexCounter]->MessageStatus = 'None';
		$messages[$indexCounter]->ObjectVersion = '0.2';
		$messages[$indexCounter]->Id = '899500324';
		$messages[$indexCounter]->IsRead = false;
		$indexCounter++;
		
		$messages[$indexCounter] = new Message();
		$messages[$indexCounter]->ObjectID = $this->layoutId;
		$messages[$indexCounter]->UserID = $this->suiteOpts['User'];
		$messages[$indexCounter]->MessageID = '59D45BD8-5738-40F0-B8EC-17729F967FAA';
		$messages[$indexCounter]->MessageType = 'sticky';
		$messages[$indexCounter]->MessageTypeDetail = '';
		$messages[$indexCounter]->Message = 'This is on page 52 bottom';
		$messages[$indexCounter]->TimeStamp = '2012-03-30T22:53:35';
		$messages[$indexCounter]->Expiration = null;
		$messages[$indexCounter]->MessageLevel = '';
		$messages[$indexCounter]->FromUser = $this->suiteOpts['User'];
		$messages[$indexCounter]->StickyInfo = new StickyInfo();
		$messages[$indexCounter]->StickyInfo->AnchorX = 209.376873;
		$messages[$indexCounter]->StickyInfo->AnchorY = 611.684565;
		$messages[$indexCounter]->StickyInfo->Left = 209.376873;
		$messages[$indexCounter]->StickyInfo->Top = 611.684565;
		$messages[$indexCounter]->StickyInfo->Width = 229.241;
		$messages[$indexCounter]->StickyInfo->Height = 123.486039;
		$messages[$indexCounter]->StickyInfo->Page = 52;
		$messages[$indexCounter]->StickyInfo->Version = '0';
		$messages[$indexCounter]->StickyInfo->Color = $this->userTrackChangesColor;
		$messages[$indexCounter]->StickyInfo->PageSequence = 5;
		$messages[$indexCounter]->ThreadMessageID = '';
		$messages[$indexCounter]->ReplyToMessageID = '';
		$messages[$indexCounter]->MessageStatus = 'None';
		$messages[$indexCounter]->ObjectVersion = '0.2';
		$messages[$indexCounter]->Id = '899500322';
		$messages[$indexCounter]->IsRead = false;
		
		$response->Objects[0]->MessageList = new MessageList();
		$response->Objects[0]->MessageList->Messages = $messages;
		return $response;
	}

////////////////////// QueryObjects Service call(s) ////////////////////////////////////
	/**
	 * It does a queryObjects service call to retrieve UnreadMessageCount of a
	 * specific one layout.
	 * The function throws error when:
	 * 'UnreadMessageCount' column is not returned in the queryObjects response.
	 * More than one layout is found in the queryObjects.
	 * The total unread message count is not correct. (It is checked against 
	 * $this->unreadStickyReplyCount)
	 */
	private function callQueryObjectsToVerifyUnreadMessages()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflQueryObjectsService.class.php';
		$req = $this->getQueryObjectsRequestToGetUnreadMessages();

		$unreadMessages = null;
		try {	
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			
			// Get the index number where 'UnreadMessageCount' is stored in returned column of queryObjects resp.
			$columnIndex = -1;
			if( $curResp->Columns ) foreach( $curResp->Columns as $column ){
				$columnIndex++;				
				if( $column->Name == 'UnreadMessageCount' ) {
					break; // Found the index number of UnreadMessageCount.
				}
			}
			
			if( $columnIndex == -1 ) {
				$this->setResult( 'ERROR', 'UnreadMessageCount is not returned in queryObjects response. ' .
									'Cannot determine Unread Messages of the stickies/replies.' );									
			} else { // Column 'UnreadMessageCount' is returned.
				if( count( $curResp->Rows ) == 1 ) { // should be only one layout.
					$unreadMessages = $curResp->Rows[0][$columnIndex];
					if( $this->unreadStickyReplyCount != $unreadMessages ) {
						$this->setResult( 'ERROR', 'UnreadMessageCount returned in queryObjects response is not correct.' .
													'Expected ('.$this->unreadStickyReplyCount. ') count, ('.$unreadMessages.') is returned.');
					}
				} else { // should not come in here.
					$this->setResult( 'ERROR', 'queryObjects returned '.count( $curResp->Rows ).' layouts. Expected one layout.' );
					LogHandler::logPhpObject( $curResp, 'print_r', 'callQueryObjectsToVerifyUnreadMessages' );
				}
			}	
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'queryObjects (to check unRead messages): failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Construct WflQueryObjectsRequest object.
	 * @return WflQueryObjectsRequest
	 */	
	private function getQueryObjectsRequestToGetUnreadMessages()
	{
		$request = new WflQueryObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->Params = array();
		$request->Params[0] = new QueryParam();
		$request->Params[0]->Property = 'Name';
		$request->Params[0]->Operation = '=';
		$request->Params[0]->Value = 'layoutMessageTest' . ' _' . $this->currentDateTime;
		$request->Params[0]->Special = null;
		$request->FirstEntry = 1;
		$request->MaxEntries = null;
		$request->Hierarchical = false;
		$request->Order = array();
		$request->MinimalProps = array();
//		$request->MinimalProps[0] = 'PublicationId';
//		$request->MinimalProps[1] = 'StateId';
//		$request->MinimalProps[2] = 'State';
//		$request->MinimalProps[3] = 'Format';
//		$request->MinimalProps[4] = 'DocumentID';
//		$request->MinimalProps[5] = 'LockedBy';
//		$request->MinimalProps[6] = 'DeadlineSoft';
//		$request->MinimalProps[7] = 'Slugline';
//		$request->MinimalProps[8] = 'HasChildren';
		$request->RequestProps = null;
		$request->Areas = null;
		return $request;		
	}
	
////////////////////// Unlock Service call(s) ////////////////////////////////////	
	/**
	 * Call unlockObject service call to unlock layout.
	 */	
	private function callUnlockObject()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflUnlockObjectsService.class.php';
		$req = $this->getUnlockObjectRequest();

		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$this->runService( $req );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'UnlockObject: failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Construct WflUnlockObjectsRequest object.
	 * @return WflUnlockObjectsRequest
	 */
	private function getUnlockObjectRequest()
	{
		$request = new WflUnlockObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->ReadMessageIDs = null;
		$request->MessageList = null;
		return $request;
	}	

////////////////////// DeleteObjects Service call(s) ////////////////////////////////////	
	/**
	 * Call deleteObjects service call to delete layout.
	 * Purge(Delete permanently) the layout, this is needed as the MessageID in message is hard-coded and
	 * have bind in the binary file of the test layout. Layout has to be deleted in order to re-run the same
	 * test(with the same layout and MessageID) in the next run.
	 */	
	private function callDeleteObjects()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflDeleteObjectsService.class.php';
		$req = $this->getDeleteObjectsRequest();
	
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			$curResp = $this->runService( $req );
			if( $curResp->Reports ){ // Introduced in v8.0
				$errMsg = '';
				foreach( $curResp->Reports as $report ){
					foreach( $report->Entries as $reportEntry ) {
						$errMsg .= $reportEntry->Message . PHP_EOL;
					}
				}
				$this->setResult( 'ERROR', 'DeleteObjects: failed: "'.$errMsg.'"' );
			}
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'DeleteObjects: failed: "'.$e->getMessage().'"' );
		}
	}

	/**
	 * Construct WflDeleteObjectsRequest object.
	 * @return WflDeleteObjectsRequest
	 */	
	private function getDeleteObjectsRequest()
	{
		$request = new WflDeleteObjectsRequest();
		$request->Ticket = $this->ticket;
		$request->IDs = array();
		$request->IDs[0] = $this->layoutId;
		$request->Permanent = true;
		$request->Params = null;
		$request->Areas = $this->isV8Client ? array( 'Workflow' ) : null;
		return $request;
	}

////////////////////// SendMessages Service call(s) ////////////////////////////////////	
	/**
	 * This function calls sendMessages service call to
	 * simulate a message sent to user by system.
	 */
	private function callSendMessages()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';
	
		$req = $this->getSendMessagesRequest();
		try {	
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/*$curResp = */$this->runService( $req );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SendMessages failed: "'.$e->getMessage().'"' );
		}			
	}

	/**
	 * Construct WflSendMessagesRequest object.
	 * @return WflSendMessagesRequest
	 */	
	private function getSendMessagesRequest()
	{
		$request = new WflSendMessagesRequest();
		$request->Ticket = $this->ticket;
		$messages = array();
		$messages[0] = new Message();
		$messages[0]->ObjectID = null;
		$messages[0]->UserID = $this->suiteOpts['User'];
		$messages[0]->MessageID = 'd11f4269-d8f1-b05e-1c9e-e4ed59b900f1';
		$messages[0]->MessageType = 'system';
		$messages[0]->MessageTypeDetail = '';
		$messages[0]->Message = $this->messageForUser;
		$messages[0]->TimeStamp = $this->timeStampMsgSent;
		$messages[0]->Expiration = null;
		$messages[0]->MessageLevel = '';
		$messages[0]->FromUser = $this->suiteOpts['User'];
//		$messages[0]->StickyInfo = null; // not needed, so leave it out.
		$messages[0]->ThreadMessageID = '';
		$messages[0]->ReplyToMessageID = '';
		$messages[0]->MessageStatus = null;
		$messages[0]->ObjectVersion = null;

		$this->messagesForVerification = $messages;
		if( $this->isV8Client ) {
			$request->MessageList = new MessageList();
			// Do a deep clone else during the request call, this->messagesForVerification will be modified as well!
			$request->MessageList->Messages = unserialize(serialize($this->messagesForVerification));
		} else {
			$request->Messages = unserialize(serialize($this->messagesForVerification));
		}
		return $request;
	}
	
	/**
	 * Do a sendMessages service call to mark message
	 * as read. (Setting isRead to be true)
	 *
	 * This function is similar to callSendMessageToMarkMessagesAsRead(),
	 * but this one only update one message instead of multiple messages.
	 *
	 * This function is just called to hit the code on sendMesageToUser in
	 * BizMessage layer. There's no verificaiton on read or unread messages count.
	 *
	 */	 
	private function callSendMessageToMarkMessageAsRead()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';
		$req = $this->getSendMessagesRequestToMarkMessageAsRead();
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/* $curResp =  */$this->runService( $req );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SendMessages to mark message as read failed: "'.$e->getMessage().'"' );
		}	
	
	}
	
	/**
	 * Construct WflSendMessagesRequest object.
	 * @return WflSendMessagesRequest
	 */
	private function getSendMessagesRequestToMarkMessageAsRead()
	{
		$request = new WflSendMessagesRequest();
		$request->Ticket = $this->ticket;

		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check.
			$request->MessageList = new MessageList();
			$request->MessageList->ReadMessageIDs = array( $this->msgId );
		}
		return $request;	
	}

	/**
	 * This function deletes the message sent in callSendMessages().
	 * It is called after testing has been done.
	 */
	private function callSendMessagesForMsgDeletion()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';
	
		$req = $this->getSendMessagesRequestForMsgDeletion();
		try {	
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/*$curResp = */$this->runService( $req );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SendMessages: failed: "'.$e->getMessage().'"' );
		}			
	}

	/**
	 * Construct WflSendMessagesRequest object.
	 * @return WflSendMessagesRequest
	 */		
	private function getSendMessagesRequestForMsgDeletion()
	{
		$request = new WflSendMessagesRequest();
		$request->Ticket = $this->ticket;
		if( $this->isV8Client ) {
			$request->MessageList = new MessageList();
			$request->MessageList->DeleteMessageIDs = array( $this->msgId );
		} else {
			$messages = array();
			$messages[0] = new Message();
			$messages[0]->ObjectID = 0;
			$messages[0]->UserID = 0;
			$messages[0]->MessageID = $this->msgId;
			$request->Messages = $messages;
		}
		return $request;
	}

	/**
	 * This function has the same purpose as callSaveObjectToReplySticky(),
	 * that is to reply sticky note.
	 * The difference is this function uses sendMessages(Used by Content Station) 
	 * service call to reply sticky, 
	 * where else callSaveObjectToReplySticky() uses
	 * saveObjects service call(Used by SmartConnection) to reply sticky.
	 */	 
	private function callSendMessagesToReplySticky()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSendMessagesService.class.php';
	
		$req = $this->getSendMessagesToReplyStickyRequest();
		try {	
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/*$curResp = */$this->runService( $req );
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SendMessages failed: "'.$e->getMessage().'"' );
		}	
	
	}

	/**
	 * Construct WflSendMessagesRequest object.
	 * @return WflSendMessagesRequest
	 */
	private function getSendMessagesToReplyStickyRequest()
	{
		$request = new WflSendMessagesRequest();
		$request->Ticket = $this->ticket;

		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check.
		
			$uniqueMsgId = $this->msgSettingsSet[$this->msgsettingsCount]['messageId'];
			$repMsg      = $this->msgSettingsSet[$this->msgsettingsCount]['repMsg'];
			$threadMsgId = $this->msgSettingsSet[$this->msgsettingsCount]['threadMsgId'];
			$repToMsgId  = $this->msgSettingsSet[$this->msgsettingsCount]['repToMsgId'];
			$this->msgSettingsSet[$this->msgsettingsCount]['layoutVersion'] = $this->getCurrentLayoutVersion(); // For validation later
			
			$messages = array();
			$messages[0] = new Message();
			$messages[0]->ObjectID = $this->layoutId;
			$messages[0]->UserID = null;
			$messages[0]->MessageID = $uniqueMsgId; // e.g. '1806D271-5DDD-4396-B2BB-A60878527D12';
			$messages[0]->MessageType = 'reply';
			$messages[0]->MessageTypeDetail = '';
			$messages[0]->Message = $repMsg; // e.g. This is on page 23 bottom - Reply 1
			$messages[0]->TimeStamp = $this->timeStampMsgSent;
			$messages[0]->Expiration = null;
			$messages[0]->MessageLevel = '';
			$messages[0]->FromUser = $this->suiteOpts['User'];
//			$messages[0]->StickyInfo // Not applicable for messageType = 'reply'
			$messages[0]->ThreadMessageID = $threadMsgId; // e.g DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE
			$messages[0]->ReplyToMessageID = $repToMsgId; // e.g DFFC1FDB-2CDA-4812-ABA5-8574C70B68AE
			$messages[0]->MessageStatus = 'None';
			$messages[0]->IsRead = false;
			$messages[0]->ObjectVersion = $this->msgSettingsSet[$this->msgsettingsCount]['layoutVersion'];
			$request->MessageList = new MessageList();
			$request->MessageList->Messages = $messages;
			
			$this->unreadStickyReplyCount+= count( $messages );
			
		}			
 
		return $request;	
	
	}

	/**
	 * Do a sendMessages service call to delete specific sticky.
	 *
	 */
	private function callSendMessagesToDeleteSticky()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSendMessagesRequestToDeleteSticky();
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/*$curResp = */$this->runService( $req );
		} catch ( BizException $e ) {
				$this->setResult( 'ERROR', 'SendMessages call to delete sticky: failed: "'.$e->getMessage().'"' );
		}	
	}

	/**
	 * Construct WflSendMessagesRequest object.
	 * @return WflSendMessagesRequest
	 */	
	private function getSendMessagesRequestToDeleteSticky()
	{
		$request = new WflSendMessagesRequest();
		$request->Ticket = $this->ticket;

		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check.

			$request->MessageList = new MessageList();
			$request->MessageList->DeleteMessageIDs = array( $this->replyNoteMsgId );
		}
 
		return $request;
	}
	
	/**
	 * Do a sendMessages service call to mark messages
	 * as read. (Setting isRead to be true)
	 *
	 * This function is similar to callSendMessageToMarkMessageAsRead(),
	 * but this one update multiple messages instead of only only update one.
	 */
	private function callSendMessageToMarkMessagesAsRead()
	{
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		require_once BASEDIR.'/server/services/wfl/WflSaveObjectsService.class.php';
		$req = $this->getSendMessagesRequestToMarkMessagesAsRead();
		try {
			LogHandler::Log( 'BuildTest', 'INFO', 'Calling ' . __METHOD__ );
			/* $curResp =  */$this->runService( $req );
			$messagesMarkedAsRead = count( $req->MessageList->ReadMessageIDs );
			$this->unreadStickyReplyCount -= $messagesMarkedAsRead; // To keep track how many messages are still unread.
		} catch ( BizException $e ) {
			$this->setResult( 'ERROR', 'SendMessages to mark message as read failed: "'.$e->getMessage().'"' );
		}	
	}
	
	/**
	 * Construct WflSendMessagesRequest object.
	 * @return WflSendMessagesRequest
	 */
	private function getSendMessagesRequestToMarkMessagesAsRead()
	{
		$request = new WflSendMessagesRequest();
		$request->Ticket = $this->ticket;

		if( $this->isV8Client ) { // This function only used by client v8 test, but just double check.
			$request->MessageList = new MessageList();
			$readMessageIds = array();
			foreach( $this->msgSettingsSet as $msgSettings ) {
				$readMessageIds[] = $msgSettings['messageId'];
			}
			$request->MessageList->ReadMessageIDs = $readMessageIds;			
		}
 
		return $request;	
	}

///////////////////////// TestCase functions ///////////////////////////////////////		
	/**
	 * Resolve Issue/Status/Pubchannel/Category/Edition
	 *
	 */
	private function resolveBrandSetup()
	{
		if ( $this->pubObj->PubChannels ) foreach( $this->pubObj->PubChannels as $pubChannel ) {
			if ( $pubChannel->Issues ) foreach ( $pubChannel->Issues as $issue ) {
				if( $issue->Name == $this->suiteOpts['Issue'] ) {
					$this->issueObj = $issue;
					break 2;
				}
			}
		}
		if( !$this->issueObj ) {
			$this->setResult( 'ERROR', 'Could not find the test Issue: '.$this->suiteOpts['Issue'], 
								'Please check the TESTSUITE setting in configserver.php.' );
		}

		// Determine layout type status
		foreach( $this->pubObj->States as $status ) {
			if( $status->Type == 'Layout' ) {
				if( $status->Id != -1 ) { // prefer non-personal status
					$this->layoutStatus = $status;
					break;
				}
			}
		}
		// Determine pubchannel object
		require_once BASEDIR . '/server/utils/ResolveBrandSetup.class.php';
		$setup = new WW_Utils_ResolveBrandSetup();
		$setup->resolveIssuePubChannelBrand( $this->issueObj->Id );
		$this->pubChannelObj = $setup->getPubChannelInfo();
		
		$this->category = count( $this->pubObj->Categories ) > 0  ? $this->pubObj->Categories[0] : null;
		require_once BASEDIR . '/server/dbclasses/DBAdmEdition.class.php';
		$editions = DBAdmEdition::listChannelEditionsObj( $this->pubChannelObj->Id );
		$this->editionObj = ( count($editions) > 0 ) ? $editions[0] : null;
	}

	/**
	 * Retrieve the track changes color of the session user.
	 *
	 * @return string The track changes color, retrieved from the DB.
	 */
	private function getUserColor()
	{
		require_once BASEDIR.'/server/bizclasses/BizSession.class.php';
		$trackchangescolor = BizSession::getUserInfo( 'trackchangescolor' );
		$trackchangescolor = substr( $trackchangescolor, 1 );
		return $trackchangescolor;
	}
	
	/**
	 * Calls a web service. Uses either a SoapClient or calls server directly.
	 *
	 * @param mixed $request request data object
	 * @return mixed response data object
	 */
	private function runService( $request )
	{
		$serviceName = get_class( $request );
		$serviceName = substr( $serviceName, 0, strlen($serviceName) - strlen('Request') ) . 'Service';
		$service = new $serviceName();
		$response = $service->execute( $request );

		return $response;
	}

	/**
	 * Compose list of properties that needs to be ignored in WW_Utils_PhpCompare::compareTwoProps().
	 *
	 * @return array List of property names.
	 */
	private function getCommonPropDiff()
	{
		return array(
			'Ticket' => true, 'Version' => true, 'ParentVersion' => true, 
			'Created' => true, 'Modified' => true, 'Deleted' => true,
			'FilePath' => true,
			'Id' => true,
			'UserID' => true,			
		);
	}	
}