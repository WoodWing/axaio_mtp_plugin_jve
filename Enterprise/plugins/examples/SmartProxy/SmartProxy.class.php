<?php
/****************************************************************************
   Copyright 2009 WoodWing Software BV

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

class SmartProxy 
{	
	final public static function client()
	{
		require_once BASEDIR.'/server/protocols/soap/WflClient.php';
		$options = array();
		$options['location'] = REMOTE_SERVER_URL;
		return new WW_SOAP_WflClient($options);	
	}

	final public static function prepare_request( &$req )
	{
		$localticket = $req->Ticket;
		
		$dbh = DBDriverFactory::gen();
		$sql = "select `remoteticket` from `smart_ticketmapping` where `localticket` = '$localticket'";
		$sth1 = $dbh->query($sql);
		$row = $dbh->fetch($sth1);	
		$req->Ticket = $row['remoteticket'];	
	
		if (isset($req->Objects))
		{
			require_once BASEDIR.'/server/utils/FileHandler.class.php';
			
			foreach ($req->Objects as &$object)
			{
				$files = $object->Files;
				$object->Files = array();
				foreach ($files as $index => $file)
				{
//
//	Save file in local cache
//
					$tfile = CACHE_DIR . $file->Rendition . '-'. $object->MetaData->BasicMetaData->ID;
					copy( $file->FilePath, $tfile );
				//	if ($file->Rendition == 'native')
				//		$content = 'PROXY';
					$attachment = new Attachment();
					$attachment->Rendition = $file->Rendition;
					$attachment->Type = $file->Type;
					$attachment->FilePath = $file->FilePath;
					$attachment->FileUrl = $file->FileUrl;

					$object->Files[] = $attachment;
				}
				
				foreach ($object->Relations as $index => $relation)
				{
					$object->Relations[$index]->Geometry = null;
				}
				
				foreach ($object->Pages as $index => &$page)
				{
					foreach ($pages->Files as $index => $file)
					{
						$attachment = new Attachment();
						$attachment->Rendition = $file->Rendition;
						$attachment->Type = $file->Type;
						$attachment->FilePath = $file->FilePath;
						$attachment->FileUrl = $file->FileUrl;
						$object->Pages->Files[$index] = $attachment;				
					}
				}				
			}
		}		
	
		if (isset($req->Relations))
		{
			foreach ($req->Relations as $index => $relation)
			{
				$req->Relations[$index]->Geometry = null;
			}
		}
	
	}
	

	final public static function finish_request( &$resp )
	{
		if (isset($resp->Objects))
		{
			require_once BASEDIR.'/server/utils/FileHandler.class.php';
			foreach ($resp->Objects as &$object)
			{
				foreach ($object->Files as $index => $file)
				{
//
//	Save file in local cache
//				
					$tfile = CACHE_DIR . $file->Rendition . '-' . $object->MetaData->BasicMetaData->ID;
					copy( $file->FilePath, $tfile );

					$attachment = new Attachment();
					$attachment->Rendition = $file->Rendition;
					$attachment->Type = $file->Type;
					$attachment->FilePath = $file->FilePath;
					$attachment->FileUrl = $file->FileUrl;
					$object->Files[$index] = $attachment;
				}
				
				foreach ($object->Pages as $index => &$page)
				{
					foreach ($pages->Files as $index => $file)
					{
						$attachment = new Attachment();
						$attachment->Rendition = $file->Rendition;
						$attachment->Type = $file->Type;
						$attachment->FilePath = $f->FilePath;
						$attachment->FileUrl = $f->FileUrl;
						$object->Pages->Files[$index] = $attachment;				
					}
				}
				
				foreach ($object->Relations as $index => $relation)
				{
					$object->Relations[$index]->Geometry = null;
				}				
			}		
		}
		
		if (isset($resp->Versions))
		{
			foreach ($resp->Versions as &$version)
			{
				$file = $version->File;
				$attachment = new Attachment();
				$attachment->Rendition = $file->Rendition;
				$attachment->Type = $file->Type;
				$attachment->FilePath = $file->FilePath;
				$attachment->FileUrl = $file->FileUrl;
				$version->File = $attachment;
			}
		}
				
		return $resp;
	}
}
