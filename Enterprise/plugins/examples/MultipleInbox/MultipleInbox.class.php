<?php

require_once dirname(__FILE__).'/Config.php';

class MultipleInbox
{
	static $arr_publications = array();

	/*
	** Call the SCE Inbox Logon
	*/
	final static public function Logon()
	{
		/*
		** 1) Setup Soap Client and its parameter option accordingly to their server version.
		** 2) Logon to multiple SCE to retrieve the Ticket
		** 3) Return the tickets
		*/
		$myservers = unserialize(SERVERLIST);
		$tickets=array();
		$ticketstring='';
		for($i = 0; $i < sizeof($myservers); $i++){
			$soapclient = new SOAP_Client($myservers[$i]['ServerUrl']);

			$result = $soapclient->call('LogOn',$myservers[$i]['parameterslogon'], $myservers[$i]['options']);
			$tickets[$i]=$result['Ticket'];
			$ticketstring.=$myservers[$i]['ServerUrl'].'|'.$tickets[$i].'#';
		}
		$File =TEMPDIRECTORY."/serverticket";
		$Handle = fopen($File, 'w');
		$Data=print_r($ticketstring,1);
		fwrite($Handle, $Data);
		fclose($Handle);
		return $tickets;
	}


	/*
	** Call the SCE Inbox SmartConnection QueryObjects
	*/
	final static public function QueryObjects( $params )
	{
		$myservers = unserialize(SERVERLIST);
		$tickets=self::Logon();

		/*
		** Make NamedQuery towards multiple SCE
		*/
		for($i = 0; $i < sizeof($myservers); $i++){
			$ps = array();
			$ps[] = new SOAP_Value('QueryParam', '{urn:SmartConnection}QueryParam', array ('Property' => 'par1','Value' => $myservers[$i]['username']));
			$parameters = array('Ticket' => $tickets[$i], 'Query' => 'Inbox', 'Params' => $ps );

			$soapclient = new SOAP_Client($myservers[$i]['ServerUrl']);
			if ($myservers[$i]['serverversion']=='v6'){
				$v6result[] = $soapclient->call('NamedQuery',$parameters, $myservers[$i]['options']);
			}else if ($myservers[$i]['serverversion']=='v5'){
				$v5result[] = $soapclient->call('NamedQuery',$parameters, $myservers[$i]['options']);
			}else if ($myservers[$i]['serverversion']=='v4'){
				$v4result[] = $soapclient->call('NamedQuery',$parameters, $myservers[$i]['options']);
			}
		}

		/*
		** Compiling wflnamedqueryresponse, take the first instance of the $v6result as template:
		*/
		$mywflnamedqueryresponse=&$v6result[0]; //Must exist at least one V6 Inbox
		$mywflnamedqueryresponserows=&$mywflnamedqueryresponse['Rows'];
		$myvalid=self::determinerowformat($mywflnamedqueryresponserows);
		if ($myvalid=='0'){
			//Single stdObject class, need to retrieve the class object out and put as an array
			$mysinglerow=$mywflnamedqueryresponserows->Row;
			$mywflnamedqueryresponserows=array(array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $myservers['0']['ServerUrl'] . "#@" .$myservers['0']['serverversion'],$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['10'],$mysinglerow['11'],$mysinglerow['12'],$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['17'],$mysinglerow['18'],$mysinglerow['19'],$mysinglerow['20']));
		}else if ($myvalid=='1'){
			//Do nothingy as $mywflnamedqueryresponserows already an array
		}else if ($myvalid=='2'){
			//Rows not defined, add an empty array
			$mywflnamedqueryresponserows=array();
		}

		/*
		** Compiling wflnamedqueryresponse, proceed to $v6result (2nd record(s) onward), $v5result, $v4result
		*/

		for($i = 1; $i < sizeof($v6result); $i++){
			$tempwflnamedqueryresponse=$v6result[$i];
			$tempwflnamedqueryresponserows=$tempwflnamedqueryresponse['Rows'];
			$myvalid=self::determinerowformat($tempwflnamedqueryresponserows);
			self::compilewflnamedqueryresponse($myvalid,$mywflnamedqueryresponserows,$tempwflnamedqueryresponserows,'v6',$myservers[$i]['ServerUrl']);
		}
		for($i = 0; $i < sizeof($v5result); $i++){
			$tempwflnamedqueryresponse=$v5result[$i];
			$tempwflnamedqueryresponserows=$tempwflnamedqueryresponse['Rows'];
			$myvalid=self::determinerowformat($tempwflnamedqueryresponserows);
			self::compilewflnamedqueryresponse($myvalid,$mywflnamedqueryresponserows,$tempwflnamedqueryresponserows,'v5',$myservers[$i+sizeof($v6result)]['ServerUrl']);
		}

		for($i = 0; $i < sizeof($v4result); $i++){
			$tempwflnamedqueryresponse=$v4result[$i];
			$tempwflnamedqueryresponserows=$tempwflnamedqueryresponse['Rows'];
			$myvalid=self::determinerowformat($tempwflnamedqueryresponserows);
			self::compilewflnamedqueryresponse($myvalid,$mywflnamedqueryresponserows,$tempwflnamedqueryresponserows,'v4',$myservers[$i+sizeof($v6result)+sizeof($v5result)]['ServerUrl']);
		}
		$myfinalwflnamedqueryresponserows=array();
		for($i = 0; $i < sizeof($mywflnamedqueryresponserows); $i++){
			$myrow=$mywflnamedqueryresponserows[$i];
			$myimagetype=$myrow['1'];
			if (strcmp($myimagetype, 'Image')==0){
				$myfinalwflnamedqueryresponserows[]=$mywflnamedqueryresponserows[$i];
			}
		}
		return $myfinalwflnamedqueryresponserows;
	}

	/*
	** There is three way of compiling the wflnamedqueryresponse->Rows
	** a) stdObject()
	** b) Array of array
	** c) null
	*/
	public static function determinerowformat($mywflnamedqueryresponserows)
	{
		$myvalid='0';//a) stdObject()
		if (is_array($mywflnamedqueryresponserows)){//b) Array of array
			$myvalid='1';
		}else if ($mywflnamedqueryresponserows==null){//c) null
			$myvalid='2';
		}
		return $myvalid;
	}

	/*
	** Compile the namedqueryresponse into understandable array for the ease of passing the variable
	*/
	public static function compilewflnamedqueryresponse($myvalid,&$mywflnamedqueryresponserows,$tempwflnamedqueryresponserows,$serverversion,$serverurl)
	{
		if ($myvalid=='0'){
			//Single stdObject class, need to retrieve the class object out and put as an array
			$mysinglerow=$tempwflnamedqueryresponserows->Row;
			if ($serverversion=='v4'){
				$tempwflnamedqueryresponserows=array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $serverurl . "#@" .$serverversion,$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['11'],'',$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['18'],$mysinglerow['19'],'','',$mysinglerow['17']);
			}else if ($serverversion=='v5'){
				$tempwflnamedqueryresponserows=array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $serverurl . "#@" .$serverversion,$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['11'],'',$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['18'],$mysinglerow['19'],'',$mysinglerow['20'],$mysinglerow['17']);
			}else{
				$tempwflnamedqueryresponserows=array(array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $serverurl . "#@" .$serverversion,$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['10'],$mysinglerow['11'],$mysinglerow['12'],$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['17'],$mysinglerow['18'],$mysinglerow['19'],$mysinglerow['20']));
			}
			$mywflnamedqueryresponserows[]=$tempwflnamedqueryresponserows;
		}else if ($myvalid=='1'){
			//Update to $mywflnamedqueryresponserows
			for($j = 0; $j < sizeof($tempwflnamedqueryresponserows); $j++){
				$mysinglerow=$tempwflnamedqueryresponserows[$j];
				if ($serverversion=='v4'){
					$newtempwflnamedqueryresponserows=array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $serverurl . "#@" .$serverversion,$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['11'],'',$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['18'],$mysinglerow['19'],'','',$mysinglerow['17']);
				}else if ($serverversion=='v5'){
					$newtempwflnamedqueryresponserows=array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $serverurl . "#@" .$serverversion,$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['11'],'',$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['18'],$mysinglerow['19'],'',$mysinglerow['20'],$mysinglerow['17']);
				}else{
					$newtempwflnamedqueryresponserows=array(array(MultipleInbox_CONTENTSOURCEPREFIX .'!@!'. $mysinglerow['0'] . "##" . $serverurl . "#@" .$serverversion,$mysinglerow['1'],$mysinglerow['2'],$mysinglerow['3'],$mysinglerow['4'],$mysinglerow['5'],$mysinglerow['6'],$mysinglerow['7'],$mysinglerow['8'],$mysinglerow['9'],$mysinglerow['10'],$mysinglerow['11'],$mysinglerow['12'],$mysinglerow['13'],$mysinglerow['14'],$mysinglerow['15'],$mysinglerow['16'],$mysinglerow['17'],$mysinglerow['18'],$mysinglerow['19'],$mysinglerow['20']));
				}
				$mywflnamedqueryresponserows[]=$newtempwflnamedqueryresponserows;
			}
		}else if ($myvalid=='2'){
			//Do nothing, no row need to be updated
		}
	}

	/*
	** Get object request to multiple server
	*/
	final static public function Getobjects( $alienID, $trueid, $serverurl, $serverversion, $rendition, $lock )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		require_once BASEDIR.'/server/utils/ImageUtils.class.php';
		$myservers = unserialize(SERVERLIST);
		//locate the server
		if($rendition == 'preview_layout') {$rendition = "preview";}
		else if($rendition == 'thumb_layout') {$rendition = "thumb";}
		else if($rendition != "preview") {$rendition = "native"; }

		for($i = 0; $i < sizeof($myservers); $i++){
			if (strcmp($myservers[$i]['ServerUrl'], $serverurl)==0){
				$myserver=$myservers[$i];
			}
		}

		$File =TEMPDIRECTORY."/serverticket";
		$Handle = fopen($File, 'r');
		$ticketcontent=fread($Handle,filesize($File));
		fclose($Handle);

		$ticket='';
		$ticketrow = explode('#',$ticketcontent);

		for ($j=0; $j < sizeof($ticketrow)-1; $j++){
			list($server,$tic) = explode('|', $ticketrow[$j]);
			if ($server==$serverurl){
				$ticket=$tic;
			}
		}

		//Make GetObjects Soap request
		$arryids = array();
		$arryids[] = $trueid;
		$req_info = array("Relations",'','','','','');
		if ($serverversion=='v4'){
			$parametersgetobjs = array('Ticket' => $ticket, 'IDs' => $arryids, 'Lock' => $lock, 'Rendition' => $rendition);
		}else if ($serverversion=='v5'){
			$parametersgetobjs = array('Ticket' => $ticket, 'IDs' => $arryids, 'Lock' => $lock, 'Rendition' => $rendition);
		}else{
			$parametersgetobjs = array ('Ticket' => $ticket,'IDs' => $arryids,'Lock' => $lock,'Rendition' => $rendition,'RequestInfo' => $req_info);
		}

		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );
		$soapclient = new SOAP_Client($myserver['ServerUrl']);
		$wflgetobjectsresponse = $soapclient->call('GetObjects', $parametersgetobjs, $myserver['options']);
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );

		$tempwflnamedqueryresponserows=$wflgetobjectsresponse;


		if ($serverversion=='v4'){

			$tempwflnamedqueryresponserowsmeta=$tempwflnamedqueryresponserows['Objects']->Object->MetaData;
			$tempwflnamedqueryresponserowsfile=$tempwflnamedqueryresponserows['Objects']->Object->Files;
			$oribasicmetadata=$tempwflnamedqueryresponserowsmeta->BasicMetaData;
			$oritargetmetadata=$tempwflnamedqueryresponserowsmeta->TargetMetaData;
			$orirightsmetadata=$tempwflnamedqueryresponserowsmeta->RightsMetaData;
			$oricontentmetadata=$tempwflnamedqueryresponserowsmeta->ContentMetaData;
			$oriworkflowmetadata=$tempwflnamedqueryresponserowsmeta->WorkflowMetaData;
			$oristate=$oriworkflowmetadata->State;
			$oriextrametadata=$tempwflnamedqueryresponserowsmeta->ExtraMetaData;
			$orifiles=$tempwflnamedqueryresponserows->Files;


			$newmeta= new stdClass();

			$newmeta->BasicMetaData=new stdClass();
			$newmeta->BasicMetaData->ID=$alienID;
			//$newmeta->BasicMetaData->ID='!@!'. $alienID . "##" . $serverurl . "#@" .$serverversion;
			$newmeta->BasicMetaData->DocumentID='!@!'. $trueid . "##" . $serverurl . "#@" .$serverversion;
			$newmeta->BasicMetaData->Name=$oribasicmetadata->Name;
			$newmeta->BasicMetaData->Type=$oribasicmetadata->Type;
			$newmeta->BasicMetaData->Publication = new stdClass();
			$newmeta->BasicMetaData->Publication->Id = $oritargetmetadata->Publication->Id;
			$newmeta->BasicMetaData->Category = new stdClass();
			$newmeta->BasicMetaData->Category->Id = $oritargetmetadata->Section->Id;
			$newmeta->BasicMetaData->Category->Name = $oritargetmetadata->Section->Name;
			$newmeta->BasicMetaData->ContentSource	= MultipleInbox_CONTENTSOURCEID;

			//Target MetaData
			$newmeta->TargetMetaData = new stdClass();
			$newmeta->TargetMetaData->Publication=$oritargetmetadata->Publication;
			$newmeta->TargetMetaData->Issue=$oritargetmetadata->Issue;
			$newmeta->TargetMetaData->Section=$oritargetmetadata->Section;
			$newmeta->TargetMetaData->Editons=$oritargetmetadata->Editions;

			//Rights MetaData
			$newmeta->RightsMetaData = new stdClass();
			$newmeta->RightsMetaData->CopyrightMarked=$orirightsmetadata->CopyrightMarked;
			$newmeta->RightsMetaData->Copyright=$orirightsmetadata->Copyright;
			$newmeta->RightsMetaData->CopyrightURL=$orirightsmetadata->CopyrightURL;

			//Source MetaData
			$newmeta->SourceMetaData = new stdClass();
			$newmeta->SourceMetaData->Credit='';
			$newmeta->SourceMetaData->Source='';
			$newmeta->SourceMetaData->Author='';

			//Content MetaData
			$newmeta->ContentMetaData = new stdClass();
			$newmeta->ContentMetaData->Description = $oricontentmetadata->Description;
			$newmeta->ContentMetaData->DescriptionAuthor = $oricontentmetadata->DescriptionAuthor;
			$newmeta->ContentMetaData->Keywords = $oricontentmetadata->Keywords;
			$newmeta->ContentMetaData->Slugline = $oricontentmetadata->Slugline;
			$newmeta->ContentMetaData->Format = $oricontentmetadata->Format;
			$newmeta->ContentMetaData->Columns = $oricontentmetadata->Columns;
			$newmeta->ContentMetaData->Width = $oricontentmetadata->Width;
			$newmeta->ContentMetaData->Height = $oricontentmetadata->Depth;
			$newmeta->ContentMetaData->Dpi = $oricontentmetadata->Dpi;
			$newmeta->ContentMetaData->LengthWords = $oricontentmetadata->LengthWords;
			$newmeta->ContentMetaData->LengthChars = $oricontentmetadata->LengthChars;
			$newmeta->ContentMetaData->LengthParas = $oricontentmetadata->LengthParas;
			$newmeta->ContentMetaData->LengthLines = $oricontentmetadata->LengthLines;
			$newmeta->ContentMetaData->PlainContent = $oricontentmetadata->PlainContent;
			$newmeta->ContentMetaData->FileSize = $oricontentmetadata->FileSize;
			$newmeta->ContentMetaData->ColorSpace = $oricontentmetadata->ColorSpace;
			$newmeta->ContentMetaData->HighResFile = $oricontentmetadata->HighResFile;


			//Workflow MetaData
			//$StateArray = new State( $Workflow->State->Id, $Workflow->State->Name, $Workflow->State->Type, null, null, null  );
			$StateArray = $oristate;
			$newmeta->WorkflowMetaData = new stdClass();
			$newmeta->WorkflowMetaData->Deadline = $oriworkflowmetadata->Deadline;
			$newmeta->WorkflowMetaData->Urgency = $oriworkflowmetadata->Urgency;
			$newmeta->WorkflowMetaData->Modifier = $oriworkflowmetadata->Modifier;
			$newmeta->WorkflowMetaData->Modified = $oriworkflowmetadata->Modified;
			$newmeta->WorkflowMetaData->Creator = $oriworkflowmetadata->Creator;
			$newmeta->WorkflowMetaData->Created = $oriworkflowmetadata->Created;
			$newmeta->WorkflowMetaData->Comment = $oriworkflowmetadata->Comment;
			$newmeta->WorkflowMetaData->State = $StateArray;
			$newmeta->WorkflowMetaData->RouteTo = $oriworkflowmetadata->RouteTo;
			$newmeta->WorkflowMetaData->LockedBy = $oriworkflowmetadata->LockedBy;
			$newmeta->WorkflowMetaData->Version = $oriworkflowmetadata->Version;
			$newmeta->WorkflowMetaData->DeadlineSoft = $oriworkflowmetadata->DeadlineSoft;
			$newmeta->WorkflowMetaData->Rating = null;

			unset ($tempwflnamedqueryresponserows['Objects']->Object->MetaData);
			$tempwflnamedqueryresponserows['Objects']->Object->MetaData=$newmeta;

			require_once BASEDIR.'/server/transferserver/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$content = $transferServer->getContent($tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment);

			$native = new Attachment();
			$native->Rendition = 'native';
			$native->Type = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->Type;
			$native->FilePath = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->FilePath;
			$native->FileUrl = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->FileUrl;
			$newfiles = array( $native );

			$previewImage = null;
			$thumbImage = null;

			if( !ImageUtils::ResizeJPEG( 600, $content, null, 75, null, null, $previewImage ) ) {
				$previewImage = null;
			}
			if( $previewImage ) { // if preview generation fails, there is no reason to try thumb either
				require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
				$preview = new Attachment('preview', 'image/jpg');
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer($previewImage, $preview);
				$newfiles[] = $preview;

				$thumbImage = '';
				if( !ImageUtils::ResizeJPEG( 100, $previewImage, null, 75, null, null, $thumbImage ) ) {
					$thumbImage = null;
				}
				else {
					require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
					$thumb = new Attachment('thumb', 'image/jpg');
					$transferServer = new BizTransferServer();
					$transferServer->writeContentToFileTransferServer($thumbImage, $thumb);
					$newfiles[] = $thumb;
				}
			}
			$object = new Object($tempwflnamedqueryresponserows['Objects']->Object->MetaData, // meta data
						null, null, // relations, pages
						$newfiles, 			// Files array of attachment
						null, null	// messages, elements, targets
						);
			LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );

        	return $object;
		}else if ($serverversion=='v5'){

			$tempwflnamedqueryresponserowsmeta=$tempwflnamedqueryresponserows['Objects']->Object->MetaData;
			$tempwflnamedqueryresponserowsfile=$tempwflnamedqueryresponserows['Objects']->Object->Files;
			$oribasicmetadata=$tempwflnamedqueryresponserowsmeta->BasicMetaData;
			$oritargetmetadata=$tempwflnamedqueryresponserowsmeta->TargetMetaData;
			$orirightsmetadata=$tempwflnamedqueryresponserowsmeta->RightsMetaData;
			$orisourcemetadata=$tempwflnamedqueryresponserowsmeta->SourceMetaData;
			$oricontentmetadata=$tempwflnamedqueryresponserowsmeta->ContentMetaData;
			$oriworkflowmetadata=$tempwflnamedqueryresponserowsmeta->WorkflowMetaData;
			$oristate=$oriworkflowmetadata->State;
			$oriextrametadata=$tempwflnamedqueryresponserowsmeta->ExtraMetaData;
			$orifiles=$tempwflnamedqueryresponserows->Files;


			$newmeta= new stdClass();

			$newmeta->BasicMetaData=new stdClass();
			$newmeta->BasicMetaData->ID=$alienID;
			//$newmeta->BasicMetaData->ID='!@!'. $alienID . "##" . $serverurl . "#@" .$serverversion;
			$newmeta->BasicMetaData->DocumentID='!@!'. $trueid . "##" . $serverurl . "#@" .$serverversion;
			$newmeta->BasicMetaData->Name=$oribasicmetadata->Name;
			$newmeta->BasicMetaData->Type=$oribasicmetadata->Type;
			$newmeta->BasicMetaData->Publication = new stdClass();
			$newmeta->BasicMetaData->Publication->Id = $oritargetmetadata->Publication->Id;
			$newmeta->BasicMetaData->Category = new stdClass();
			$newmeta->BasicMetaData->Category->Id = $oritargetmetadata->Section->Id;
			$newmeta->BasicMetaData->Category->Name = $oritargetmetadata->Section->Name;
			$newmeta->BasicMetaData->ContentSource	= MultipleInbox_CONTENTSOURCEID;

			//Target MetaData
			$newmeta->TargetMetaData = new stdClass();
			$newmeta->TargetMetaData->Publication=$oritargetmetadata->Publication;
			$newmeta->TargetMetaData->Issue=$oritargetmetadata->Issue;
			$newmeta->TargetMetaData->Section=$oritargetmetadata->Section;
			$newmeta->TargetMetaData->Editons=$oritargetmetadata->Editions;

			//Rights MetaData
			$newmeta->RightsMetaData = new stdClass();
			$newmeta->RightsMetaData->CopyrightMarked=$orirightsmetadata->CopyrightMarked;
			$newmeta->RightsMetaData->Copyright=$orirightsmetadata->Copyright;
			$newmeta->RightsMetaData->CopyrightURL=$orirightsmetadata->CopyrightURL;

			//Source MetaData
			$newmeta->SourceMetaData = new stdClass();
			$newmeta->SourceMetaData->Credit=$orisourcemetadata->Credit;
			$newmeta->SourceMetaData->Source=$orisourcemetadata->Source;
			$newmeta->SourceMetaData->Author=$orisourcemetadata->Author;

			//Content MetaData
			$newmeta->ContentMetaData = new stdClass();
			$newmeta->ContentMetaData->Description = $oricontentmetadata->Description;
			$newmeta->ContentMetaData->DescriptionAuthor = $oricontentmetadata->DescriptionAuthor;
			$newmeta->ContentMetaData->Keywords = $oricontentmetadata->Keywords;
			$newmeta->ContentMetaData->Slugline = $oricontentmetadata->Slugline;
			$newmeta->ContentMetaData->Format = $oricontentmetadata->Format;
			$newmeta->ContentMetaData->Columns = $oricontentmetadata->Columns;
			$newmeta->ContentMetaData->Width = $oricontentmetadata->Width;
			$newmeta->ContentMetaData->Height = $oricontentmetadata->Depth;
			$newmeta->ContentMetaData->Dpi = $oricontentmetadata->Dpi;
			$newmeta->ContentMetaData->LengthWords = $oricontentmetadata->LengthWords;
			$newmeta->ContentMetaData->LengthChars = $oricontentmetadata->LengthChars;
			$newmeta->ContentMetaData->LengthParas = $oricontentmetadata->LengthParas;
			$newmeta->ContentMetaData->LengthLines = $oricontentmetadata->LengthLines;
			$newmeta->ContentMetaData->PlainContent = $oricontentmetadata->PlainContent;
			$newmeta->ContentMetaData->FileSize = $oricontentmetadata->FileSize;
			$newmeta->ContentMetaData->ColorSpace = $oricontentmetadata->ColorSpace;
			$newmeta->ContentMetaData->HighResFile = $oricontentmetadata->HighResFile;


			//Workflow MetaData
			//$StateArray = new State( $Workflow->State->Id, $Workflow->State->Name, $Workflow->State->Type, null, null, null  );
			$StateArray = $oristate;
			$newmeta->WorkflowMetaData = new stdClass();
			$newmeta->WorkflowMetaData->Deadline = $oriworkflowmetadata->Deadline;
			$newmeta->WorkflowMetaData->Urgency = $oriworkflowmetadata->Urgency;
			$newmeta->WorkflowMetaData->Modifier = $oriworkflowmetadata->Modifier;
			$newmeta->WorkflowMetaData->Modified = $oriworkflowmetadata->Modified;
			$newmeta->WorkflowMetaData->Creator = $oriworkflowmetadata->Creator;
			$newmeta->WorkflowMetaData->Created = $oriworkflowmetadata->Created;
			$newmeta->WorkflowMetaData->Comment = $oriworkflowmetadata->Comment;
			$newmeta->WorkflowMetaData->State = $StateArray;
			$newmeta->WorkflowMetaData->RouteTo = $oriworkflowmetadata->RouteTo;
			$newmeta->WorkflowMetaData->LockedBy = $oriworkflowmetadata->LockedBy;
			$newmeta->WorkflowMetaData->Version = $oriworkflowmetadata->Version;
			$newmeta->WorkflowMetaData->DeadlineSoft = $oriworkflowmetadata->DeadlineSoft;
			$newmeta->WorkflowMetaData->Rating = null;



			//$newmeta = new stdClass($basicmetadata, $targetmetadata, $rightsmetadata, $sourcemetadata, $contentmetadata, $workflowmetadata);

			unset ($tempwflnamedqueryresponserows['Objects']->Object->MetaData);
			$tempwflnamedqueryresponserows['Objects']->Object->MetaData=$newmeta;

			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$transferServer = new BizTransferServer();
			$content = $transferServer->getContent($tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment);

			$native = new Attachment();
			$native->Rendition = 'native';
			$native->Type = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->Type;
			$native->FilePath = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->FilePath;
			$native->FileUrl = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->FileUrl;
			$newfiles = array( $native );

			$previewImage = null;
			$thumbImage = null;

			if( !ImageUtils::ResizeJPEG( 600, $content, null, 75, null, null, $previewImage ) ) {
				$previewImage = null;
			}
			if( $previewImage ) { // if preview generation fails, there is no reason to try thumb either
				require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
				$preview = new Attachment('preview', 'image/jpg');
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer($previewImage, $preview);
				$newfiles[] = $preview;

				$thumbImage = '';

				if( !ImageUtils::ResizeJPEG( 100, $previewImage, null, 75, null, null, $thumbImage ) ) {
					$thumbImage = null;
				}
				else {

					require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
					$thumb = new Attachment('thumb', 'image/jpg');
					$transferServer = new BizTransferServer();
					$transferServer->writeContentToFileTransferServer($thumbImage, $thumb);
					$newfiles[] = $thumb;
				}
			}

			$object = new Object( 	$tempwflnamedqueryresponserows['Objects']->Object->MetaData,		// meta data
			null, null,			// relations, pages
			$newfiles, 			// Files array of attachment
			null, null	// messages, elements, targets
			);
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );

        	return $object;


		}else{
			//Do nothing as it is v6 System
			$object=$wflgetobjectsresponse;
			$object->MetaData->BasicMetaData->ID=$alienID;
			$object->MetaData->BasicMetaData->DocumentID='!@!'. $trueid . "##" . $serverurl . "#@" .$serverversion;
		}
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ );

		return $object;
	}

	/*
	** Save Object towards multiple server
	*/
	final static public function Saveobjects( $alienID, $trueid, $serverurl, $serverversion, $destobject )
	{
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ . '#' . __LINE__ );
		$myservers = unserialize(SERVERLIST);
		//locate the server
		$rendition = "none";

		for($i = 0; $i < sizeof($myservers); $i++){
			if (strcmp($myservers[$i]['ServerUrl'], $serverurl)==0){
				$myserver=$myservers[$i];
			}
		}

		$File =TEMPDIRECTORY."/serverticket";
		$Handle = fopen($File, 'r');
		$ticketcontent=fread($Handle,filesize($File));
		fclose($Handle);

		$ticket='';
		$ticketrow=explode('#',$ticketcontent);

		for ($j=0; $j < sizeof($ticketrow)-1; $j++){
			list($server,$tic) = explode('|', $ticketrow[$j]);
			if ($server==$serverurl){
				$ticket=$tic;
			}
		}

		//Make GetObjects Soap request
		$arryids = array();
		$arryids[] = $trueid;
		$req_info = array("Relations",'','','','','');
		if ($serverversion=='v4'){
			$parametersgetobjs = array('Ticket' => $ticket, 'IDs' => $arryids, 'Lock' => false, 'Rendition' => $rendition);
		}else if ($serverversion=='v5'){
			$parametersgetobjs = array('Ticket' => $ticket, 'IDs' => $arryids, 'Lock' => false, 'Rendition' => $rendition);
		}else{
			$parametersgetobjs = array ('Ticket' => $ticket,'IDs' => $arryids,'Lock' => false,'Rendition' => $rendition,'RequestInfo' => $req_info);
		}
		$soapclient = new SOAP_Client($myserver['ServerUrl']);
		//@todo Replace SOAP_Client 
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ . '#' . __LINE__ );
		$wflgetobjectsresponse = $soapclient->call('GetObjects', $parametersgetobjs, $myserver['options']);
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ . '#' . __LINE__ );

		$tempwflnamedqueryresponserows=$wflgetobjectsresponse;
		require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
		$transferServer = new BizTransferServer();
		$content = $transferServer->getContent($destobject->Files[0]);

		$native = new Attachment();
		$native->Rendition = 'native';
		$native->Type = $tempwflnamedqueryresponserows['Objects']->Object->Files->Attachment->Type;
		$native->FilePath = $destobject->Files[0]->FilePath;
		$native->FileUrl = $destobject->Files[0]->FileUrl;
		$newfiles = array( $native );

		$previewImage = null;
		$thumbImage = null;
		if( !ImageUtils::ResizeJPEG( 600, $content, null, 75, null, null, $previewImage ) ) {
			$previewImage = null;
		}
		if( $previewImage ) { // if preview generation fails, there is no reason to try thumb either
			require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
			$preview = new Attachment('preview', 'image/jpg');
			$transferServer = new BizTransferServer();
			$transferServer->writeContentToFileTransferServer($previewImage, $preview);
			$newfiles[] = $preview;

			$thumbImage = '';
			if( !ImageUtils::ResizeJPEG( 100, $previewImage, null, 75, null, null, $thumbImage ) ) {
				$thumbImage = null;
			} else {
				require_once BASEDIR . '/server/transferserver/BizTransferServer.class.php';
				$thumb = new Attachment('thumb', 'image/jpg');
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer($thumbImage, $thumb);
				$newfiles[] = $thumb;
			}
		}

		unset($tempwflnamedqueryresponserows['Objects']->Object->Files);
		$tempwflnamedqueryresponserows['Objects']->Object->Files=$newfiles;

		if ($serverversion=='v4'){
			$parameterssaveobjs = array('Ticket' => $ticket, 'CreateVersion' => true, 'ForceCheckIn' => true,'Unlock' => true, $tempwflnamedqueryresponserows['Objects']);
		}else if ($serverversion=='v5'){
			$parameterssaveobjs = array('Ticket' => $ticket, 'CreateVersion' => true, 'ForceCheckIn' => true,'Unlock' => true, $tempwflnamedqueryresponserows['Objects']);
		}else{
			$parameterssaveobjs = array('Ticket' => $ticket, 'CreateVersion' => true, 'ForceCheckIn' => true, 'Unlock' => true, 'Objects' => $tempwflnamedqueryresponserows['Objects'], 'ReadMessageIDs' => '', 'Messages' => '');
		}

		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ . '#' . __LINE__ );
		$soapclient = new SOAP_Client($myserver['ServerUrl']);
		$wflgetobjectsresponse = $soapclient->call('SaveObjects', $parameterssaveobjs, $myserver['options']);
		LogHandler::Log('MultipleInbox', 'DEBUG', __FUNCTION__ . '#' . __LINE__ );
		return $destobject;
	}

	/*
	** return of server based on the serverurl passing in
	*/
	final static public function getserver( $serverurl )
	{
		$myservers = unserialize(SERVERLIST);
		for($i = 0; $i < sizeof($myservers); $i++){
			if ($myservers[$i]['ServerUrl']==$serverurl){
				return $myservers[$i];
			}
		}
	}
}
?>
