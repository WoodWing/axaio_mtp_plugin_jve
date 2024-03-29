<?php

/**
 * This file holds the AxaioMadeToPrintDispatcher class, which is used for
 * checking trigger and output files to MadeToPrint.
 *
 * The Methods can be customized by hooks in the AxaioMadeToPrintCustomize
 * class, which are called from within here. If you need additional hooking
 * points, please report to <support@axaio.com>.
 *
 * @copyright (c) 2015, axaio software GmbH
 * @author René Treuber <support@axaio.com>
 * @package AxaioMadeToPrint
 */
require_once dirname(__FILE__) . '/config.php';

class AxaioMadeToPrintDispatcher {

    // Singleton object, so block create/delete/copy instances
    private function __construct() {}
    private function __destruct() {}
    private function __copy() {}

    private static function domAddElement($dom, $parentNode, $name, $value)
    {
        $node = $dom->createElement($name);
        $node->appendChild(new DOMText($value));
        $parentNode->appendChild($node);
    }

    public static function newPostProcess($layoutId, $layStatusId, $layEditionId, $success, $message, $servername = null, $counter = 0)
    {
        $ret = [];
        $lock = null;
        $ticket = null;

        try {
            require_once(BASEDIR . '/server/secure.php');
            require_once(BASEDIR . '/server/services/wfl/WflLogOnService.class.php');
            require_once(BASEDIR . '/server/services/wfl/WflLogOffService.class.php');
            require_once(BASEDIR . '/server/services/wfl/WflGetObjectsService.class.php');
            require_once(BASEDIR . '/server/services/wfl/WflSetObjectPropertiesService.class.php');

            $user = AXAIO_MTP_USER;
            $password = AXAIO_MTP_PASSWORD;
            $result = false;



            // when MadeToPrint job errored, log it
            if ($success != 1) {
                LogHandler::Log('mtp', 'ERROR', 'postProcess: MtP failed with message: ' . $message);
                $ret[] = 'postProcess: MtP failed with message: ' . $message;
            }

            // Add MtP job notification to the layout's comment
            if ($layEditionId > 0) {
                require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
                $editionObj = DBEdition::getEditionObj($layEditionId);
                $editionTxt = '(' . BizResources::localize('EDITION') . ': ' . $editionObj->Name . ') ';
            } else {
                $editionTxt = '';
            }

            // Get MtP configuration record for the layout trigger status
            $mtpConfig = self::getMtpConfig($layStatusId);
            if (!$mtpConfig) {
                LogHandler::Log('mtp', 'ERROR', 'postProcess: Could not find MtP configuration for layout status ' . $layStatusId);
                $ret[] = 'postProcess: FAIL: Could not find MtP configuration for layout status ' . $layStatusId;
                return $ret; // should never happen
            }

            $refstatelayout = $mtpConfig['state_after_layout'];
            $refstatearticle = $mtpConfig['state_after_article'];
            $refstateimage = $mtpConfig['state_after_image'];
            $errstatelayout = $mtpConfig['state_error_layout'];

            require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
            require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';

            $affectedObjects = [];

            if (($refstatearticle != 0) || $refstateimage != 0) {
                foreach (self::getPlacedChilds($layoutId) as $childId) {
                    try {
                    $affectedObjects[] = BizObject::getObject($childId, AXAIO_MTP_USER, false, 'none', array('Targets', 'MetaData', 'Relations'), null, false);
                    }catch(Exception $e){}
                }
            }
            //Add the specific layout last, so it will be updated as last object
            $affectedObjects[] = BizObject::getObject($layoutId, AXAIO_MTP_USER, false, 'none', array('Targets', 'MetaData', 'Relations'), null, false);
            foreach ($affectedObjects as $obj) {
                // avoid WoodWing S1019 error
                $obj->MetaData->TargetMetaData = null;
                // avoid WoodWing Deadline error
                $obj->MetaData->WorkflowMetaData->Deadline = null;
                
                self::customize('postProcess_updateObject', $obj->MetaData); //requested by Core-Lab

                // set object properties
                $comment =  '[MTP' . (isset($servername) ? ' ' . $servername . ' ' : ' ') . date('Y-m-d H:i:s', time()) . '] ' . $editionTxt . $message . "\n" . $obj->MetaData->WorkflowMetaData->Comment;
                $commentinfo = array('servername' => $servername
                    , 'editionTxt' => $editionTxt
                    , 'message' => $message
                    , 'comment' => $obj->MetaData->WorkflowMetaData->Comment
                    , 'success' => $success
                );
                self::customize('postProcess_filterComment', $comment, $layoutId, $layStatusId, $layEditionId, $success, $mtpConfig, $commentinfo);
                
                self::customize('postProcess_updatedObject', $obj, $mtpConfig, $commentinfo);
                
                $nextState = 0;
                switch ($obj->MetaData->BasicMetaData->Type) {
                    case 'Layout':
                    case 'LayoutTemplate':
                        $nextState = ($success == 1) ? $refstatelayout : $errstatelayout;
                        if (!$mtpConfig['quiet']) {
                            $obj->MetaData->WorkflowMetaData->Comment = $comment;
                        }
                        break;
                    case 'Article':
                    case 'ArticleTemplate':
                        $nextState = ($success == 1) ? $refstatearticle : 0;
                        break;
                    case 'Image':
                    case 'ImageTemplate':
                        $nextState = ($success == 1) ? $refstateimage : 0;
                        break;
                    default: $nextState = 0;
                }

                if ($nextState) {
                    $obj->MetaData->WorkflowMetaData->State->Id = $nextState;
                    /*                require_once BASEDIR . '/server/dbclasses/DBStates.class.php';
                      $stateObj = DBStates::getStateObj($nextState);
                      $obj->MetaData->WorkflowMetaData->State->Name           = $stateObj->Name;
                      $obj->MetaData->WorkflowMetaData->State->Type           = $stateObj->Type;
                      $obj->MetaData->WorkflowMetaData->State->Produce        = $stateObj->Produce;
                      $obj->MetaData->WorkflowMetaData->State->Color          = $stateObj->Color;
                      $obj->MetaData->WorkflowMetaData->State->DefaultRouteTo = $stateObj->DefaultRouteTo;
                      $obj->MetaData->WorkflowMetaData->State->Phase          = $stateObj->Phase;
                     */
                }

                /* @TODO: route to
                  $newRouteTo = BizWorkflow::doGetDefaultRouting($mtpConfig['publication_id'], $mtpConfig['issue_id'], null, $refstate);
                  if ($newRouteTo) {
                  // BZ##4729: Adding routeTo into update as well.
                  DBBase::updateRow('objects', array('routeto' => $newRouteTo, 'state' => $refstate), "`id` = $childId");
                  }
                 */

                $lockfile = TEMPDIRECTORY . '/Axaio-UpdateStatus.lock';

                $lock = fopen($lockfile, 'a');
                if ($lock === false) {
                    self::logOut($ticket);
                    throw new Exception(sprintf("Could not open lockfile '%s'", $lockfile));
                }

                /* Get exclusive lock: this will block until we get the lock */
                $ret = flock($lock, LOCK_EX);
                if ($ret === false) {
                    self::logOut($ticket);
                    throw new Exception(sprintf("Could not get lock on lockfile '%s'.", $lockfile));
                }

                /*
                 * Critical section which should not be executed
                 * in parallel by another process
                 */

                $ticket = self::logIn();
                
                $service = new WflSetObjectPropertiesService();
                $req = new WflSetObjectPropertiesRequest($ticket, $obj->MetaData->BasicMetaData->ID, $obj->MetaData, null);
                $resp = $service->execute($req);
                if (!$resp) {
                    LogHandler::Log('mtp', 'ERROR', 'SetObjectProperties for Object ' . $obj->MetaData->BasicMetaData->ID . ' failed.');
                    $ret[] = 'SetObjectProperties for Object ' . $obj->MetaData->BasicMetaData->ID . ' failed.';
                } else {
                    
                }
            }
        } catch (BizException $bex) {
#            // try again
#            if($counter < 10) {
#                LogHandler::Log('mtp', 'WARN', 'BizException occured (will try again): ' . $bex);
#                usleep(10);
#                $ret[] = self::newPostProcess($layoutId, $layStatusId, $layEditionId, $success, $message, $servername, ++$counter);
#            } else {
            LogHandler::Log('mtp', 'ERROR', 'BizException occured (failing after ' . $counter . ' tries)' . $bex);
            $ret[] = 'BizException occured (failing after ' . $counter . ' tries)' . $bex;
#            }
        } catch (Exception $ex) {
            LogHandler::Log('mtp', 'ERROR', 'Unknown Exception occured: ' . $ex);
            $ret[] = 'Unknown Exception occured: ' . $ex;
        }

        /* End of critical section */
        /* Release the lock */
        $ret = flock($lock, LOCK_UN);
        if ($ret === false) {
            self::logOut($ticket);
            throw new Exception(sprintf("Could not release lock on lockfile '%s'.", $lockfile));
        }
        fclose($lock);

        // try to log out after all
        self::logOut($ticket);

        return $ret;
    }

    /**
     * Calls the WlfLogOnService to create a the Ticket
     * @return ticket
     */
    private static function logIn()
    {
        $ticket = null;
        require_once(BASEDIR . '/server/services/wfl/WflLogOnService.class.php');
        try {
            $service = new WflLogOnService();
            $req = new WflLogOnRequest(AXAIO_MTP_USER, AXAIO_MTP_PASSWORD, null, "setObjectProperties", null, "setObjectProperties", "mtp", "9.9.10 Build 1093", null, true);
            $resp = $service->execute($req);
            if (!$resp) {
                LogHandler::Log('mtp', 'ERROR', 'LogOn failed: Request failed.');
                return null;
            }
            $ticket = $resp->Ticket;
            if (!$ticket) {
                LogHandler::Log('mtp', 'ERROR', 'LogOn failed: No ticket returned.');
                return $ticket;
            }
        } catch (Exception $ex) {
            //do nothing
        }
        
        return $ticket;
    }

    /**
     * Calls the WlfLogOffService to release the Ticket
     * @param  string $ticket Enterprise Ticket
     * @return void
     */
    private static function logOut($ticket)
    {
        require_once(BASEDIR . '/server/services/wfl/WflLogOffService.class.php');
        try {
            if ($ticket) {
                // log off
                $service = new WflLogOffService();
                $req = new WflLogOffRequest($ticket);
                $service->execute($req);
            }
        } catch (Exception $ex) {
            //do nothing
        }
    }

    /**
     * Called when MtP has processed a job (requested by us).
     * Sends layout and its placements to the configured 'After' status.
     * Also, it adds the processing message to the comment field of te layout.
     * This message/comment is shown when user reopens the layout.
     *
     * @param int  $layoutId     Id of the layout
     * @param int  $layStatusId  Status id of the layout
     * @param int  $layEditionId Edition id of the layout
     * @param int  $success      Whether or not the process was successful (=1)
     * @param string $message    Message about the process
     */
    public static function postProcess($layoutId, $layStatusId, $layEditionId, $success, $message, $servername = null)
    {
        $ret = [];
        $dbDriver = DBDriverFactory::gen();

        /* At MtP you can see process, handled jobs, job status, etc etc  No more reason to do this at SCE
          // Update print status
          $dbmtpsent = $dbDriver->tablename("mtpsentobjects");
          $sql = 'update '.$dbmtpsent.' set `printstate`='.$success.' '
          .'where `objid`='.$layoutId.' and `state_trigger_layout`='.$layStatusId;
          $dbDriver->query($sql);
         */

        // Quit when MtP job has failed
        if ($success != 1) {
            LogHandler::Log('mtp', 'ERROR', 'postProcess: MtP failed with message: ' . $message);
            $ret[] = 'postProcess: MtP failed with message: ' . $message;
        }

        // Get current object comment
        $dbobjects = $dbDriver->tablename("objects");
        $sql = 'select `comment` from ' . $dbobjects . ' where `id`=' . $layoutId;
        $sth = $dbDriver->query($sql);
        $res = $dbDriver->fetch($sth);
        if (!$res) { // happens e.g. when layout has been removed right after pushed into MtP queue
            LogHandler::Log('mtp', 'ERROR', 'postProcess: Could not find layout. Id=' . $layoutId);
            $ret[] = 'postProcess: Could not find layout. Id=' . $layoutId;
            return $ret;
        }


        // Add MtP job notification to the layout's comment
        if ($layEditionId > 0) {
            require_once BASEDIR . '/server/dbclasses/DBEdition.class.php';
            $editionObj = DBEdition::getEditionObj($layEditionId);
            $editionTxt = '(' . BizResources::localize('EDITION') . ': ' . $editionObj->Name . ') ';
        } else {
            $editionTxt = '';
        }

        // Get MtP configuration record for the layout trigger status
        $mtpConfig = self::getMtpConfig($layStatusId);
        if (!$mtpConfig) {
            LogHandler::Log('mtp', 'ERROR', 'postProcess: Could not find MtP configuration for layout status ' . $layStatusId);
            $ret[] = 'postProcess: FAIL: Could not find MtP configuration for layout status ' . $layStatusId;
            return $ret; // should never happen
        }
        $refstatelayout = $mtpConfig['state_after_layout'];
        $refstatearticle = $mtpConfig['state_after_article'];
        $refstateimage = $mtpConfig['state_after_image'];
        $errstatelayout = $mtpConfig['state_error_layout'];
        $doUpdateComment = (!$mtpConfig['quiet']) ? true : false;

        require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
        $updatedObjects = array();
        // Update article/image status
        require_once BASEDIR . '/server/dbclasses/DBBase.class.php';
        if ($success == 1) {
            require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
            $childIds = self::getPlacedChilds($layoutId);
            foreach ($childIds as $childId) {
                $objType = self::getObjectType($childId);

                $refstate = 0;
                if ($objType == 'Image') {
                    $refstate = $refstateimage;
                } elseif ($objType == 'Article') {
                    $refstate = $refstatearticle;
                }

                if ($refstate != 0) {
                    $newRouteTo = BizWorkflow::doGetDefaultRouting($mtpConfig['publication_id'], $mtpConfig['issue_id'], null, $refstate);
                    if ($newRouteTo) {
                        // BZ##4729: Adding routeTo into update as well.
                        DBBase::updateRow('objects', array('routeto' => $newRouteTo, 'state' => $refstate), "`id` = $childId");
                    } else {
                        DBBase::updateRow('objects', array('state' => $refstate), "`id` = $childId");
                    }
                    $updatedObjects[] = BizObject::getObject($childId, AXAIO_MTP_USER, false, 'none', array('Targets', 'MetaData', 'Relations'), null, false);
                }
            }
        }

        $comment = '[MTP' . (isset($servername) ? ' ' . $servername . ' ' : ' ') . date('Y-m-d H:i:s', time()) . '] ' . $editionTxt . $message . "\n" . $res['comment'];
        $commentinfo = array('servername' => $servername
            , 'editionTxt' => $editionTxt
            , 'message' => $message
            , 'comment' => $res['comment']
            , 'success' => $success
        );
        self::customize('postProcess_filterComment', $comment, $layoutId, $layStatusId, $layEditionId, $success, $mtpConfig, $commentinfo);

        // Update layout status and comment
        if ($refstatelayout != 0 && $success == 1) {

            $newRouteTo = BizWorkflow::doGetDefaultRouting($mtpConfig['publication_id'], $mtpConfig['issue_id'], null, $refstatelayout);
            if ($newRouteTo) {
                if ($doUpdateComment) {
                    DBBase::updateRow('objects', array('state' => $refstatelayout, 'routeto' => $newRouteTo, 'comment' => $comment), "`id` = $layoutId");
                } else {
                    DBBase::updateRow('objects', array('state' => $refstatelayout, 'routeto' => $newRouteTo), "`id` = $layoutId");
                }
            } else {
                if ($doUpdateComment) {
                    DBBase::updateRow('objects', array('state' => $refstatelayout, 'comment' => $comment), "`id` = $layoutId");
                } else {
                    DBBase::updateRow('objects', array('state' => $refstatelayout), "`id` = $layoutId");
                }
            }
            self::logLayoutStatus($layoutId, $refstatelayout);
        } else {
            if ($doUpdateComment) {
                DBBase::updateRow('objects', array('comment' => $comment), "`id` = $layoutId");
            }
        }
        if ($success != 1 && $errstatelayout != 0) {
            DBBase::updateRow('objects', array('state' => $errstatelayout), "`id` = $layoutId");
            self::logLayoutStatus($layoutId, $errstatelayout);
        }


        // Add to search index:
        $updatedObjects[] = BizObject::getObject($layoutId, AXAIO_MTP_USER, false, 'none', array('Targets', 'MetaData', 'Relations'), null, false);
        require_once BASEDIR . '/server/bizclasses/BizSearch.class.php';
        BizSearch::indexObjects($updatedObjects);

        self::customize('postProcess_updatedObjects', $layoutId, $updatedObjects);

        LogHandler::Log('mtp', 'DEBUG', 'postProcess: layout status=' . $refstatelayout . ' success=' . $success);
        $ret[] = 'postProcess: layout status=' . $refstatelayout . ' success=' . $success;

        if (is_array($updatedObjects)) {
            foreach ($updatedObjects as $obj) {
                $ret[] = 'postProcess: retriggerObject for object with ID ' . (isset($obj->MetaData->BasicMetaData->ID) ? $obj->MetaData->BasicMetaData->ID : 'unknown');
                self::retriggerObject($obj);
            }
        }

        return $ret;
    }

    /**
     * Pushes the given layout into the MtP queue by creating processing scripts.
     *
     * @param int  $layoutId     Id of the layout
     * @param int  $layPubId     Publication id of the layout
     * @param int  $layIssueId   Issue id of the layout
     * @param int  $layStatusId  Status id of the layout
     * @param array $layEditions List of Edition objects of layout
     */
    private static function queueLayoutObject($ticket, $layoutId, $layPubId, $layIssueId, $layStatusId, $layEditions)
    {

        require_once BASEDIR . '/server/dbclasses/DBTicket.class.php';
        $user = DBTicket::checkTicket($ticket);

        self::customize('queueLayoutObject_begin', $layEditions, $layoutId, $layStatusId, $user);

        /* At MtP you can see process, handled jobs, job status, etc etc  No more reason to do this at SCE
          // Create job record at smart_mtpsentobjects table
          if( !self::saveLayoutIntoQueue( $layoutId, $layPubId, $layIssueId, $layStatusId ) ) {
          return; // error already reported at saveLayoutIntoQueue
          } */

        // Retrieve object props from smart_objects table
        require_once BASEDIR . '/server/bizclasses/BizQuery.class.php';
        $fullrow = BizQuery::queryObjectRow($layoutId);
        // Determine the MtP job name
        $mtpConfig = self::getMtpConfig($layStatusId);
        if (!$mtpConfig) {
            LogHandler::Log('mtp', 'ERROR', 'queueLayoutObject: Could not find MtP configuration for layout status ' . $layStatusId);
            return;
        }

        if (isset($mtpConfig['prio'])) {
            $fullrow['prio'] = $mtpConfig['prio'];
        }

        // We risk getting no issue when no current is set at channel; so we overrule here
        require_once BASEDIR . '/server/dbclasses/DBIssue.class.php';
        $fullrow['Issue'] = DBIssue::getIssueName($layIssueId);
        $axIssue = DBIssue::getIssue($layIssueId);
        //add layouts issue information to metadata, incl. IssueId, IssueName, IssueDescription, etc.
        foreach ($axIssue as $issueKey => $issueValue) {
            $issueKey = 'Issue' . ucfirst($issueKey);
            $fullrow[$issueKey] = $issueValue;
        }

        // Optional feature: Collect special custom MTP fields too (for outputting later on)
        // Those fields have C_MTP_ prefixes at smart_objects table.
        $mtparr = array();
        foreach ($fullrow as $propName => $propValue) {
            if (strncasecmp($propName, 'C_MTP_', 6) == 0) { // could upper (new) or lower (old)
                $mtparr[substr($propName, 6, strlen($propName) - 6)] = $propValue;
            }
        }

        $jobname = (trim($mtpConfig['mtp_jobname']) == '') ? trim(AXAIO_MTP_JOB_NAME) : trim($mtpConfig['mtp_jobname']);


        self::customize('queueLayoutObject_filterEditions', $layEditions, $layoutId, $layStatusId, $user);

        if (is_array($layEditions) && !empty($layEditions)) {
            foreach ($layEditions as $layEdition) {
                self::outputProcessingFiles($layoutId, $layStatusId, $layEdition, $jobname, $fullrow, $mtparr, $layPubId);
            }
        } else { // no edition, so output layout once with default edition
            self::outputProcessingFiles($layoutId, $layStatusId, $layEditions, $jobname, $fullrow, $mtparr, $layPubId);
        }
    }

    public static function mtpTokenize($string, $metadata)
    {
        $mtpTokenBegin = ( defined("AXAIO_MTP_TOKEN_BEGIN")) ? AXAIO_MTP_TOKEN_BEGIN : '«';
        $mtpTokenEnd = ( defined("AXAIO_MTP_TOKEN_END")) ? AXAIO_MTP_TOKEN_END : '»';

        foreach ($metadata as $mkey => $mval) {
            $string = str_ireplace($mtpTokenBegin . $mkey . $mtpTokenEnd, $mval, $string);
        }

        return $string;
    }

    /**
     * Creates processing scripts for MtP to output given layout for certain edition.
     *
     * @param int $layoutId      Id of the layout
     * @param int $layStatusId   Status id of the layout
     * @param object $layEdition Edition object of layout. Null for no edition.
     * @param string $jobname    MtP operation to request
     * @param array  $fullrow    List of all layout object properties
     * @param array  $mtparr     List of MtP specific custom properties to send to MtP process
     * @param int    $publId     Id of the publication of the layout
     */
    private static function outputProcessingFiles($layoutId, $layStatusId, $layEdition, $jobname, $fullrow, $mtparr, $publId = null)
    {
        $break = false;
        self::customize('outputProcessingFiles_begin', $layoutId, $layStatusId, $fullrow, $layEdition, $publId, $break);
        if ($break) {
            return;
        }

        // Calculate page range for printing
        require_once BASEDIR . '/server/dbclasses/DBPage.class.php';
        $sth = DBPage::getPages($layoutId, 'Production', null, $layEdition ? $layEdition->Id : null, true);
        $firstPage = 1000000;
        $lastPage = 0;
        $dbDriver = DBDriverFactory::gen();

        while (($pageRow = $dbDriver->fetch($sth))) {
            if ($firstPage > $pageRow['pageorder']) {
                $firstPage = $pageRow['pageorder'];
            }
            if ($lastPage < $pageRow['pageorder']) {
                $lastPage = $pageRow['pageorder'];
            }
        }
        if ($firstPage == 1000000) {
            $firstPage = 1;
        }
        if ($lastPage == 0) {
            $lastPage = 1;
        }

        $fullrow['EditionPageFirst'] = $firstPage;
        $fullrow['EditionPageLast'] = $lastPage;
        $editionPageRange = str_pad($firstPage, 3, "0", STR_PAD_LEFT);
        if ($firstPage < $lastPage) {
            $editionPageRange .= "-" . str_pad($lastPage, 3, "0", STR_PAD_LEFT);
        }
        $fullrow['EditionPageRange'] = $editionPageRange;

        $layEditionId = $layEdition ? $layEdition->Id : 0;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $rootNode = $dom->appendChild(new DOMElement('mtp-data'));

        // Add custom MtP meta data props to the job
        foreach ($mtparr as $field => $value) {
            // Convert SCE custom prop name convention to MtP, e.g.
            //    PAGE_RANGE to page-range
            //    START_PAGE to start-page
            $value = $mtparr[$field];
            unset($mtparr[$field]);
            $field = strtolower($field);
            $field = str_replace('_', '-', $field);
            $mtparr[$field] = $value;
            // Output prop to MtP
            self::domAddElement($dom, $rootNode, $field, $value);
        }

        // Optionally fill the layout edition info
        if ($layEdition && $layEdition->Id > 0) {
            $fullrow['EditionId'] = $layEdition->Id;
            $fullrow['Edition'] = $layEdition->Name;
        } else {
            $fullrow['EditionId'] = 0;
            $fullrow['Edition'] = '';
        }

        // Concat object id + status id + edition id => to make up unique file name
        // Note: we should not use names here since (accented) unicode chars have problems on cross OS mounted disks
        $name = $layoutId . '_' . $layStatusId . '_' . $layEditionId;
        self::customize('outputProcessingFiles_filterName', $name, $fullrow);

        $mtpPaths = array('axaio_prejs_in' => self::mtpTokenize(AXAIO_MTP_AXAIO_FOLDER_IN . $name . '_pre.js', $fullrow)
            , 'axaio_postjs_in' => self::mtpTokenize(AXAIO_MTP_AXAIO_FOLDER_IN . $name . '_post.js', $fullrow)
            , 'axaio_xml_out' => self::mtpTokenize(AXAIO_MTP_AXAIO_FOLDER_OUT . $name . '.xml', $fullrow)
            , 'server_xml_in' => self::mtpTokenize(AXAIO_MTP_SERVER_FOLDER_IN . $name . '.xml', $fullrow)
            , 'server_prejs_in' => self::mtpTokenize(AXAIO_MTP_SERVER_FOLDER_IN . $name . '_pre.js', $fullrow)
            , 'server_postjs_in' => self::mtpTokenize(AXAIO_MTP_SERVER_FOLDER_IN . $name . '_post.js', $fullrow)
        );

        self::customize('outputProcessingFiles_filterMtpPaths', $mtpPaths);

        $jobname = self::mtpTokenize($jobname, $fullrow);
        self::customize('outputProcessingFiles_filterJobname', $jobname, $fullrow);

        /*
          // if user wants to have seperate IN-folders for each publication he can define AXAIO_MTP_INFOLDER_PER_PUBLICATION
          if( defined( "AXAIO_MTP_INFOLDER_PER_PUBLICATION") && AXAIO_MTP_INFOLDER_PER_PUBLICATION == true) {
          $name = $publId.'/'.$layoutId.'_'.$layStatusId.'_'.$layEditionId;
          } else {
          $name = $layoutId.'_'.$layStatusId.'_'.$layEditionId;
          } */

        // Build processing files and write them to folder (AXAIO_MTP_AXAIO_FOLDER_IN)
        self::domAddElement($dom, $rootNode, 'preprocess-javascript', $mtpPaths['axaio_prejs_in']);
        self::domAddElement($dom, $rootNode, 'postprocess-javascript', $mtpPaths['axaio_postjs_in']);
        self::domAddElement($dom, $rootNode, 'print-set', $jobname);

        // If page range is NOT a custom prop, let's request for all pages
        // Note: Since v6.0 this is renamed from "page-range" to "PAGE_RANGE" to meet name validation
        if (array_key_exists('PAGE_RANGE', $mtparr) === false) {
            self::domAddElement($dom, $rootNode, 'allpages', '1');

            // If start page is NOT a custom prop, let's take the actual start page
            // Note: Since v6.0 this is renamed from "start-page" to "START_PAGE" to meet name validation
            if (array_key_exists('START_PAGE', $mtparr) === false) {
                self::domAddElement($dom, $rootNode, 'start-page', $firstPage);
            } else if ($mtparr['START_PAGE'] < $firstPage) {
                $mtparr['START_PAGE'] = $firstPage; // repair out-of-scope values
            }
        } else {
            // page range always overrules the start page, so remove to avoid errors at MtP
            unset($mtparr['START_PAGE']);
            // repair out-of-scope values
            if ($mtparr['PAGE_RANGE'] > $lastPage) {
                $mtparr['PAGE_RANGE'] = $lastPage;
            }
            // Note: the custom value is outputted below!
        }

        self::customize('outputProcessingFiles_filterFullrow', $fullrow);

        $extendedNode = $rootNode->appendChild(new DOMElement('extended'));
        foreach ($fullrow as $mdfield => $mdvalue) {
            $xmetadataEl = $dom->createElement('xmetadata');
            $xmetadataEl->setAttribute('name', $mdfield);
            $xmetadataEl->setAttribute('value', $mdvalue);
            $extendedNode->appendChild($xmetadataEl);
        }

        $mtpjob = $dom->saveXML();

        // the pre process script content
        $preprocessjs = '
			function handleMTP_WW_Result( success , msg )
			{
			    servername = "Desktop";
			    try {
			        servername = app.serverSettings.configuration;
			    }catch(err){
			        //expected to fail on non-InDesignServer
			        servername = "Desktop";
			    }

				try {
                    app.performSimpleRequest( "' . AXAIO_MTP_POSTPROCESS_LOC . '?id=' . $layoutId
                . '&state=' . $layStatusId
                . '&edition=' . $layEditionId
                . '&servername="+encodeURIComponent(servername)+"'
                . '&success="+success+"'
                . '&message="+encodeURIComponent(msg));
				}catch(err){
			        msg = err.message;
                    success = 99;
			    }

                if( success != 1) {
					msg = "Error: " + msg;
				}
				return msg;
			}

            function ww_login( user, pass, server, maxTries)
            {
                var msg = "", tryCounter = 0;

                while(tryCounter < maxTries) {
                    tryCounter++;

                    try {
                        //try to login, if it fails error is thrown
                        app.entSession.login( user, pass, server );
                        if(app.entSession.activeUser) {
                            msg = ""; //overwrite error messages from earlier tries
                            break; //leave "while" as login already successful
                        }
                    } catch( err ) {
                        msg = err.message; //set error message
                    }
                    //login was not successful, because we would have "break"ed out
                    if(maxTries!=tryCounter) {
                        $.sleep(1000*tryCounter); //wait one second in first round, two in the second, ...
                    }
                }
                if("" != msg) { //if message is not empty, the login failed, so throw error
                    throw new Error( msg );
                }
                return true;
            }

            //{POSSIBLE_CODE_ELVIS_1}

			function preprocessjs()
			{
				// turn off user interaction
				try {
					app.scriptPreferences.userInteractionLevel = UserInteractionLevels.neverInteract;
				} catch(err) { } // fail silently; expected in InDesign Server

				// try to logout
				try {
					app.entSession.logout();
				} catch(err) { } // fail silently;

				// remove pending results from earlier output
				try {
					var report = File("' . addslashes($mtpPaths['axaio_xml_out']) . '");
					if( report.exists && !report.remove()) { // remove pending results (errors) from previous jobs if still exists
						throw new Error( "Cannot delete old xml result file. Please check folder permissions for ' . addslashes($mtpPaths['axaio_xml_out']) . '!");
					}
				} catch( err ) {
					return handleMTP_WW_Result( 7 , err);
				}

				// log in user
				try {
					';
        //when using multiply clients the JS has to retrieve the Username, otherwise take the default
        if (defined("AXAIO_MTP_ENABLE_MULTI_USERS") && AXAIO_MTP_ENABLE_MULTI_USERS == true) {
            $preprocessjs .= 'var username = app.performSimpleRequest("' . AXAIO_MTP_PREPROCESS_LOC . '");';
        } else {
            $preprocessjs .= 'var username = "' . addslashes(AXAIO_MTP_USER) . '";';
        }

        $preprocessjs .= '
					ww_login( username, "' . addslashes(AXAIO_MTP_PASSWORD) . '", "' . addslashes(AXAIO_MTP_SERVER_DEF_ID) . '", 11 );
				} catch( err ) {
					return handleMTP_WW_Result( 4 , "Cannot logon MTP user ["+err+"]");
				}

				//initialize working document pointer
				var myWWDoc = null;

				// open layout
				try {
					myWWDoc = app.openObject("' . $layoutId . '", false);
				} catch( err ) {
					return handleMTP_WW_Result( 5 , "Cannot open layout ' . $layoutId . ' ["+err+"]");
				}';

        // Pre-select the requested edition before MtP starts processing
        if ($layEditionId > 0) {
            $preprocessjs .= '

				// activate edition
				try {
					myWWDoc.activeEdition = "' . addslashes($layEdition->Name) . '";
				} catch( err ) {
					return handleMTP_WW_Result( 6 , "Cannot activate edition ' . $layEditionId . ' ["+err+"]");
				}';
        }
        $preprocessjs .= '
            //{POSSIBLE_CODE_ELVIS_2}
			}

			// run the script now!
			preprocessjs();';

        // the post process script content
        $postprocessjs = '
			function handleMTP_WW_Result( success , msg )
			{
			    servername = "";
			    try {
			        servername = app.serverSettings.configuration;
			    }catch(err){
			        //expected to fail on non-InDesignServer
                    servername = "Desktop";
			    }

				try {
                    msg = app.performSimpleRequest( "' . AXAIO_MTP_POSTPROCESS_LOC . '?id=' . $layoutId
                . '&state=' . $layStatusId
                . '&edition=' . $layEditionId
                . '&servername="+encodeURIComponent(servername)+"'
                . '&success="+success+"'
                . '&message="+encodeURIComponent(msg));
				}catch(err){
			        msg = err.message;
                    success = 99;
			    }

                if( success != 1) {
					msg = "Error: " + msg;
				}
				return msg;
			}

			function postprocessjs()
			{
				// turn off user interaction
				try {
					app.scriptPreferences.userInteractionLevel = UserInteractionLevels.neverInteract;
				} catch(err) { } // fail silently; expected in InDesign Server

				// read MTP report file
				try {
					var report = File("' . addslashes($mtpPaths['axaio_xml_out']) . '");
					report.open("r");
					content = report.read();
                    report.close();
					if( content.length == 0 ) {
						throw new Error( "Cannot read MTP status report file" );
					}
				} catch( err ) {
					return handleMTP_WW_Result( 3 , err );
				}

				// handle result
				try {
					if( content.indexOf("<type>ok") > 0) {
						handleMTP_WW_Result( 1 , "ok" )
						report.remove(); // we only clean on success! or else there is no way to find back fatal errors!
					} else {
						i = content.indexOf("<status>")+8;
						j = content.indexOf("</status>")
						msg = content.substring(i,j);
                        msg=msg.split("&#10;").join("\n");
                        msg=msg.split("&apos;").join("\'");
                        msg=msg.replace(new RegExp("\n         [^\\n]+", "g"), "");

						handleMTP_WW_Result( 2 , msg);
					}
				} catch( err ) {
					handleMTP_WW_Result( 8 , "error when handle the result xml ["+err+"]");
				}

				// close all open documents.
				// MTP can not start with documents open, so here we safely close *all* documents;
				// They must be all ours and this is just to make sure documents don\'t get stacked in fatal situations,
				// for example documents still left open from previous sessions that ended unexpectedly.
				try {
					var runs = 0;
					while(app.documents.length && runs < 100) {
						app.documents.item(0).close(SaveOptions.no);
						runs++;
					}
				} catch (err)  { } // fail silently;

				// try to logout
				try {
					app.entSession.logout();
				} catch(err) { } // fail silently;
			}

			// run the script now!
			postprocessjs();';

        self::customize('outputProcessingFiles_beforeWrite', $preprocessjs, $postprocessjs, $mtpjob, $mtpPaths, $fullrow);

        // output MTP files
        self::writeFile($mtpPaths['server_prejs_in'], $preprocessjs);
        self::writeFile($mtpPaths['server_postjs_in'], $postprocessjs);
        self::writeFile($mtpPaths['server_xml_in'], $mtpjob);

        self::customize('outputProcessingFiles_afterWrite', $preprocessjs, $postprocessjs, $mtpjob, $mtpPaths, $fullrow);

        self::customize('outputProcessingFiles_end', $layoutId, $fullrow);
    }

    public static function clearSentObject($objectId, $newPubId, $newStatusId, $oldStatusId)
    {
        $objectId = $objectId;
        $newPubId = $newPubId;
        $newStatusId = $newStatusId;
        $oldStatusId = $oldStatusId; // keep analyzer happy
        // EKL: There is no much we can do here?
    }

    public static function writeFile($filename, $content, $mode = 0777)
    {
        $fp = fopen($filename, "w+");
        chmod($filename, $mode);
        if ($fp) {
            #Add utf-8 byte order mark
            fwrite($fp, pack("CCC", 0xEF, 0xBB, 0xBF));
            fwrite($fp, $content);
            fclose($fp);

            LogHandler::Log('mtp', 'INFO', 'Wrote into: ' . $filename);
        } else {
            LogHandler::Log('mtp', 'ERROR', 'No write access for: ' . $filename);
        }
    }

    /**
     * Pushes layouts into the MadeToPrint queue when configured trigger statuses are reached.
     * When a layouts pushed into the queue, it will get processed by MadeToPrint later on.
     * The passed object can be a layout or a placed article/image.
     * When passing article/image, it will push the layouts on which they are placed.
     *
     * @param int $objectId The object to push into the queue
     * @param string ticket The session ticket
     */
    public static function doPrint($objectId, $ticket)
    {
        if (self::calledByIDSAutomation($ticket)) {
            return;
        }

        $objType = self::getObjectType($objectId);
        if (substr($objType, 0, 6) == 'Layout') { //also support LayoutTemplates
            $layoutIds = array($objectId);
        } elseif ($objType == 'Article' || $objType == 'Image') {
            $layoutIds = self::getParentLayouts($objectId);
        } else { // ignore unsupported object types
            $layoutIds = array();
        }

        foreach ($layoutIds as $layoutId) {
            $layPubId = $layIssueId = $layStatusId = 0;
            $layEditions = array();
            if (self::getLayoutDetails($layoutId, $layPubId, $layIssueId, $layStatusId, $layEditions)) {
                self::logLayoutStatus($layoutId, $layStatusId);
                if (self::checkTriggerStatuses($layoutId, $layStatusId)) {
                    self::queueLayoutObject($ticket, $layoutId, $layPubId, $layIssueId, $layStatusId, $layEditions);
                }
            }
        }
    }

    /**
     * Checks if the caller is an InDesign Server Job process.
     * @param string $ticket The ticket.
     * @return bool Called by an InDesign Server Job, true, else false.
     */
    static private function calledByIDSAutomation($ticket)
    {
        $idsJob = FALSE;

        require_once BASEDIR . '/server/bizclasses/BizInDesignServerJob.class.php';
        if (method_exists('BizInDesignServerJobs', 'getJobIdForRunningJobByTicketAndJobType')) {
            $idsJob = BizInDesignServerJobs::getJobIdForRunningJobByTicketAndJobType($ticket, 'IDS_AUTOMATION');
        }

        return (bool) $idsJob;
    }

    /**
     * Checks if the layout and its children all match the configured 'trigger'
     * statuses and the layout can go into the queue.
     *
     * @param int $layoutId     Id of the layout
     * @param int $layStatusId  Status id of the layout
     * @return boolean Wether or not all triggers are matching
     */
    private static function checkTriggerStatuses($layoutId, $layStatusId)
    {
        $mtpConfig = self::getMtpConfig($layStatusId);
        if (!$mtpConfig) {
            return false;
        }

        self::customize('checkTriggerStatuses_begin', $layoutId, $layStatusId, $mtpConfig);

        $childIds = self::getPlacedChilds($layoutId);
        foreach ($childIds as $childId) {
            $objType = self::getObjectType($childId);
            if ($objType == 'Article') {
                if ($mtpConfig['state_trigger_article'] != 0) {
                    $childStatusId = self::getObjectStatus($childId);
                    if (is_array($mtpConfig['state_trigger_article'])) {
                        if (!in_array($childStatusId, $mtpConfig['state_trigger_article'])) {
                            return false;
                        }
                    } elseif ($mtpConfig['state_trigger_article'] != $childStatusId) {
                        return false;
                    }
                }
            } elseif ($objType == 'Image') {
                if ($mtpConfig['state_trigger_image'] != 0) {
                    $childStatusId = self::getObjectStatus($childId);
                    if (is_array($mtpConfig['state_trigger_image'])) {
                        if (!in_array($childStatusId, $mtpConfig['state_trigger_image'])) {
                            return false;
                        }
                    } elseif ($mtpConfig['state_trigger_image'] != $childStatusId) {
                        return false;
                    }
                }
            }
        }
        /*
          if (defined("AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY") && AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY == true) {
          $dbDriver = DBDriverFactory::gen();
          $table = DBPREFIX . "axaio_mtp_process_options";
          $sql = " SELECT		option_value as `stateID`
          FROM		{$table}
          WHERE		option_name = 'stateOfLayout_{$layoutId}'
          ORDER BY	id DESC
          LIMIT		2";
          $sth = $dbDriver->query($sql);
          $stateIds = array();
          while (($res = $dbDriver->fetch($sth))) {
          array_push($stateIds, $res['stateID']);
          }

          if (isset($stateIds[0]) && isset($stateIds[1]) && $stateIds[0] === $stateIds[1]) {
          LogHandler::Log('mtp', 'DEBUG', 'Skipping layout ' . $layoutId . ' because the status was not changed');
          return false;
          }
          } */
        return true;
    }

    /*
     * retriggers the document to set next status in panel and forces Woodwing
     * to check if another trigger was hit.
     * 
     * code based on submit from A&F
     */

    private static function retriggerObject($obj)
    {
        require_once(BASEDIR . '/server/secure.php');
        require_once(BASEDIR . "/server/services/wfl/WflLogOnService.class.php");
        require_once(BASEDIR . "/server/services/wfl/WflLogOffService.class.php");
        require_once(BASEDIR . "/server/services/wfl/WflGetObjectsService.class.php");
        require_once(BASEDIR . "/server/services/wfl/WflSetObjectPropertiesService.class.php");

        $user = AXAIO_MTP_USER;
        $password = AXAIO_MTP_PASSWORD;
        $result = false;
        $ticket = null;
        try {
            do {
                ob_start();
                var_dump($obj);
                $objdump = ob_get_contents();
                ob_end_clean();

                LogHandler::Log('mtp', 'INFO', 'Re-Trigger object: ' . $objdump);
                // log on
                $service = new WflLogOnService();
                $req = new WflLogOnRequest($user, $password, null, "setObjectProperties", null, "setObjectProperties", "mtp", "9.9.10 Build 1093", null, true);
                $resp = $service->execute($req);
                if (!$resp) {
                    LogHandler::Log('mtp', 'ERROR', 'LogOn failed: Request failed.');
                    break;
                }
                $ticket = $resp->Ticket;
                if (!$ticket) {
                    LogHandler::Log('mtp', 'ERROR', 'LogOn failed: No ticket returned.');
                    break;
                }
                // get object
                /* 	$service = new WflGetObjectsService();
                  $req = new WflGetObjectsRequest($ticket, array($id), false, 'none');
                  $resp = $service->execute($req);
                  if (!$resp) {
                  self::log('ERROR', 'GetObject failed: Request failed.');
                  break;
                  }
                  $obj = $resp->Objects[0];
                 */ if (!$obj) {
                    LogHandler::Log('mtp', 'ERROR', 'GetObject failed: No object returned.');
                    break;
                }
                // avoid WoodWing S1019 error
                $obj->MetaData->TargetMetaData = null;
                // avoid WoodWing Deadline error
                $obj->MetaData->WorkflowMetaData->Deadline = null;
                // set object properties
                $service = new WflSetObjectPropertiesService();
                $req = new WflSetObjectPropertiesRequest($ticket, $obj->MetaData->BasicMetaData->ID, $obj->MetaData, null);
                $resp = $service->execute($req);
                if (!$resp) {
                    LogHandler::Log('mtp', 'ERROR', 'SetObjectProperties failed.');
                    break;
                }
                LogHandler::Log('mtp', 'DEBUG', 'DONE');
                $result = true;
            } while (false);
        } catch (Exception $ex) {
            LogHandler::Log('mtp', 'ERROR', 'An unexpected exception occured: ' . $ex);
        }
        try {
            if ($ticket) {
                // log off
                $service = new WflLogOffService();
                $req = new WflLogOffRequest($ticket);
                $service->execute($req);
            }
        } catch (Exception $ex) {
            
        }
        return $result;
    }

    /**
     * Returns the object status (from smart_objects table).
     *
     * @param int $objectId
     * @retun int The status id
     */
    public static function getObjectStatus($objectId)
    {
        $dbDriver = DBDriverFactory::gen();
        $dbobjects = $dbDriver->tablename("objects");
        $sql = 'select `state` from ' . $dbobjects . ' where `id`=' . (int) $objectId;
        $sth = $dbDriver->query($sql);
        $res = $dbDriver->fetch($sth);
        return $res['state'];
    }

    /**
     * Returns the object type (from smart_objects table).
     *
     * @param int $objectId
     * @retun string The object type
     */
    private static function getObjectType($objectId)
    {
        $dbDriver = DBDriverFactory::gen();
        $dbobjects = $dbDriver->tablename("objects");
        $sql = 'select `type` from ' . $dbobjects . ' where `id`=' . $objectId;
        $sth = $dbDriver->query($sql);
        $res = $dbDriver->fetch($sth);
        return $res['type'];
    }

    /**
     * Returns all objects that are placed on the given layout.
     *
     * @param int $layoutId
     * @retun array List of placed object ids
     */
    private static function getPlacedChilds($layoutId)
    {
        $dbDriver = DBDriverFactory::gen();
        $dbobjectrel = $dbDriver->tablename("objectrelations");
        $children = array();
        $sql = 'select `child` from ' . $dbobjectrel . ' where `parent`=' . $layoutId . ' and `type` = \'Placed\'';
        $sth = $dbDriver->query($sql);
        while (($res = $dbDriver->fetch($sth))) {
            array_push($children, $res['child']);
        }
        return $children;
    }

    /**
     * Returns all layouts on which the given object is placed.
     *
     * @param int $objectId
     * @retun array List of layout ids
     */
    private static function getParentLayouts($objectId)
    {
        $dbDriver = DBDriverFactory::gen();
        $dbobjectrel = $dbDriver->tablename("objectrelations");
        $parents = array();
        $sql = 'select `parent` from ' . $dbobjectrel . ' where `child`=' . $objectId . ' and `type` = \'Placed\'';
        $sth = $dbDriver->query($sql);
        while (($res = $dbDriver->fetch($sth))) {
            array_push($parents, $res['parent']);
        }
        return $parents;
    }

    /**
     * Get the configured MadeToPrint configuration for the given layout trigger status
     *
     * @param int $layStatusId  Status id of the layout
     * @return string Job name
     */
    private static function getMtpConfig($layStatusId)
    {
        self::customize('getMtpConfig_begin', $layStatusId);

        $dbDriver = DBDriverFactory::gen();
        $dbmtp = $dbDriver->tablename('axaio_mtp_trigger');
        $sql = 'select * from ' . $dbmtp . ' where `state_trigger_layout`=' . $layStatusId;
        $sth = $dbDriver->query($sql);
        $row = $dbDriver->fetch($sth);
        if (!$row) {
            return null;
        }

        // TODO: Move this to admin page (setup) -> validation/repair
        if (trim($row['state_trigger_article']) == '') {
            $row['state_trigger_article'] = 0;
        }
        if (trim($row['state_trigger_image']) == '') {
            $row['state_trigger_image'] = 0;
        }

        if (trim($row['state_after_layout']) == '') {
            $row['state_after_layout'] = 0;
        }
        if (trim($row['state_after_article']) == '') {
            $row['state_after_article'] = 0;
        }
        if (trim($row['state_after_image']) == '') {
            $row['state_after_image'] = 0;
        }

        if (trim($row['state_error_layout']) == '') {
            $row['state_error_layout'] = 0;
        }

        if (trim($row['quiet']) == '') {
            $row['quiet'] = 0;
        }
        if (trim($row['prio']) == '') {
            $row['prio'] = 2;
        }

        self::customize('getMtpConfig_end', $layStatusId, $row);

        return $row;
    }

    /**
     * Determines the current layout's publication, issue and status.
     * Layouts have only one pub+issue !
     *
     * @param int  $layoutId     Layout id
     * @param int  $layPubId     Returned: Publication id of layout
     * @param int  $layIssueId   Returned: Issue id of layout
     * @param int  $layStatusId  Returned: Status id of layout
     * @param array $layEditions Returned: List of Edition objects of layout
     * @return boolean wether or not successful.
     */
    private static function getLayoutDetails($layoutId, &$layPubId, &$layIssueId, &$layStatusId, &$layEditions)
    {
        // Get layout's issue and editions; we assume layouts have exactly 1 issue (=business rule!) and so it has 1 target
        require_once BASEDIR . '/server/bizclasses/BizTarget.class.php';
        $targets = BizTarget::getTargets(null, $layoutId);
        if (count($targets) != 1) {
            LogHandler::Log('mtp', 'ERROR', 'Layout ' . $layoutId . ' is NOT bound to ONE issue. Target count = ' . count($targets));
            return false; // quit; we don't know what issue to take
        }
        if (!isset($targets[0]->Issue->Id) || !$targets[0]->Issue->Id) {
            LogHandler::Log('mtp', 'ERROR', 'Layout ' . $layoutId . ' has unknown issue. Target count = ' . count($targets));
            return false; // quit; issue is corrupt/unset
        }
        $layIssueId = $targets[0]->Issue->Id;
        $layEditions = $targets[0]->Editions;

        // Get layout's publication and status
        $dbDriver = DBDriverFactory::gen();
        $dbobjects = $dbDriver->tablename("objects");
        $sql = 'select `publication`, `state` from ' . $dbobjects . ' where `id`=' . $layoutId;
        $sth = $dbDriver->query($sql);
        $res = $dbDriver->fetch($sth);
        if (!$res) {
            LogHandler::Log('mtp', 'ERROR', 'Layout not found. Id=' . $layoutId);
            return false;
        }
        $dbflags = $dbDriver->tablename("objectflags");
        $sql2 = 'select `objid` from ' . $dbflags . ' where `objid`=' . $layoutId;
        $sth2 = $dbDriver->query($sql2);
        $res2 = $dbDriver->fetch($sth2);
        if ($res2 && !(defined("AXAIO_MTP_IGNORE_LAYOUT_FLAGS") && AXAIO_MTP_IGNORE_LAYOUT_FLAGS == true )) {
            LogHandler::Log('mtp', 'ERROR', 'Layout ' . $layoutId . ' has a Flag.');
            return false;
        }
        $layPubId = $res['publication'];
        if (!$layPubId) {
            LogHandler::Log('mtp', 'ERROR', 'Layout ' . $layoutId . ' has unknown publication.');
            return false;
        }
        $layStatusId = $res['state'];
        if (!$layStatusId) {
            LogHandler::Log('mtp', 'ERROR', 'Layout ' . $layoutId . ' has unknown status.');
            return false;
        }
        return true;
    }

    /**
     * logs the current layout status id into the database
     * Used to determine if the layout status id was changed
     *
     * @param int  $layoutId     Layout id
     * @param int  $layStatusId  Status id of layout
     */
    private static function logLayoutStatus($layoutId, $layStatusId)
    {
        if (defined("AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY") && AXAIO_MTP_TRIGGER_ON_CHANGE_ONLY == true) {
            $dbDriver = DBDriverFactory::gen();
            $sql = "INSERT INTO " . $dbDriver->tablename("axaio_mtp_process_options") . " (`option_name`,`option_value`) VALUES ('stateOfLayout_{$layoutId}', '{$layStatusId}');";
            $dbDriver->query($sql);
        }
    }

    private static function customize($name, &$arg1 = null, &$arg2 = null, &$arg3 = null, &$arg4 = null, &$arg5 = null, &$arg6 = null, &$arg7 = null)
    {
        $filename = dirname(__FILE__) . '/AxaioMadeToPrintCustomize.class.php';
        if (file_exists($filename)) {
            require_once $filename;
            AxaioMadeToPrintCustomize::Customize($name, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7); // run hook
        }
    }

}
