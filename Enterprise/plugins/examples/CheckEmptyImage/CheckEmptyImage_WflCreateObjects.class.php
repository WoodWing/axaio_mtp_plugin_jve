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

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';
require_once dirname(__FILE__) . '/config.php';

class CheckEmptyImage_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	private $overruleIssues;

	final public function getPrio () {	return self::PRIO_DEFAULT; }
	final public function getRunMode () { return self::RUNMODE_BEFORE; }

	final public function runBefore (WflCreateObjectsRequest &$req)
	{
//
//	Check for the upload of images without highres data
//
		if ($req->Objects[0]->MetaData->BasicMetaData->Type == 'Image')
		{
			$native = 0;

			LogHandler::Log('CheckEmptyImage', 'DEBUG', 'check content'.print_r($req->objects[0]->MetaData->ContentMetaData,1) );
			foreach ($req->Objects[0]->Files as &$f)
			{
				LogHandler::Log('CheckEmptyImage', 'DEBUG', 'check renditions'.print_r($f->Rendition,1) );
				LogHandler::Log('CheckEmptyImage', 'DEBUG', 'check renditions'.print_r($f->Type,1) );

				if ($f->Rendition == 'native' && $f->FilePath)
				{
					if ($f->Type == 'image')
					{
						$f->Type = $objects[0]->MetaData->ContentMetaData->Format;
						LogHandler::Log('CheckEmptyImage', 'DEBUG', 'repair incorrect content type'.print_r($f,1) );
					}
					$native = 1;
				}
			}
			if (!$native)
				throw new BizException( 'MSG_BROKEN_LINK', 'Client', null, MSG_BROKEN_LINK );
		}
		return $req;
	}

	final public function runAfter (WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp)
	{
	}

	final public function runOverruled (WflCreateObjectsRequest $req) {} // Not called because we're just doing run before and after
	}
