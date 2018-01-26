<?php
/****************************************************************************
   Copyright 2014 WoodWing Software BV

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

require_once BASEDIR . '/server/interfaces/services/wfl/WflSaveObjects_EnterpriseConnector.class.php';

class AxaioMadeToPrint_WflSaveObjects extends WflSaveObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_BEFOREAFTER; }

    /** @var integer $prevStatusId The object status id before the service was executed. */
	private $prevStatusId;
	
	final public function runBefore( WflSaveObjectsRequest &$req )
	{
		$req = $req; // keep code analyzer happy

        require_once dirname(__FILE__) . '/config.php';
        require_once dirname(__FILE__) . '/AxaioMadeToPrintDispatcher.class.php';
        $this->prevStatusId = AxaioMadeToPrintDispatcher::getObjectStatus( $req->ID );

		// TODO: Add your code that hooks into the service request.
		// NOTE: Replace RUNMODE_AFTER with RUNMODE_AFTER when this hook is not needed.
	} 

	final public function runAfter( WflSaveObjectsRequest $req, WflSaveObjectsResponse &$resp )
	{
		require_once dirname(__FILE__) . '/config.php';
		require_once dirname(__FILE__) . '/AxaioMadeToPrintDispatcher.class.php';
		require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
		$req = $req; $resp = $resp; // keep code analyzer happy

		$ticket = BizSession::getTicket();
		if( defined( "AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY") && AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY == true) {
            if( $this->prevStatusId && (substr($req->Objects[0]->MetaData->BasicMetaData->Type, 0, 6) == 'Layout') ) { //set and other than 0 and is layout
                $currentStatusId = (isset( $req->Objects[0]->MetaData->WorkflowMetaData->State->Id))
                                         ? $req->Objects[0]->MetaData->WorkflowMetaData->State->Id
                                         : 0;

                if($currentStatusId != $this->prevStatusId) {
                    LogHandler::Log('AxaioMadeToPrint', 'DEBUG', 'Layout status was changed from ' . $this->prevStatusId . ' to ' . $currentStatusId);
                    AxaioMadeToPrintDispatcher::doPrint( $resp->Objects[0]->MetaData->BasicMetaData->ID, $ticket );            
                } else {
                    LogHandler::Log('AxaioMadeToPrint', 'DEBUG', 'Skipping layout '.$resp->Objects[0]->MetaData->BasicMetaData->ID.' because the status was not changed' );
                }
            }
        } else {
            if((BizSession::getShortUserName() == AXAIO_MTP_USER) && ($req->Objects[0]->MetaData->WorkflowMetaData->State->Id == $resp->Objects[0]->MetaData->WorkflowMetaData->State->Id) ){
            }else{
                AxaioMadeToPrintDispatcher::doPrint(  $resp->Objects[0]->MetaData->BasicMetaData->ID, $ticket );
            }
        }


	} 
	
	// Not called.
	final public function runOverruled( WflSaveObjectsRequest $req )
	{
		$req = $req; // keep code analyzer happy
	} 
}
