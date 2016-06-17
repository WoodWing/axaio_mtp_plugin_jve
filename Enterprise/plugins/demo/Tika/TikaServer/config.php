<?php
/****************************************************************************
   Copyright 2008-2010 WoodWing Software BV

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

set_time_limit(3600);

// TIKA_APP_DIRECTORY
//    Local directory of the Tika application.
//    By default, Tika application runs inside the Tika server plug-in space:
//       define ('TIKA_APP_DIRECTORY', dirname(__FILE__).'/../TikaApp/tika-1.3');
//    When there is a need to let it run elsewhere, specify an absolute path, for example:
//       define ('TIKA_APP_DIRECTORY', '/usr/local/tika-1.3');
//
define ('TIKA_APP_DIRECTORY', dirname(__FILE__).'/../TikaApp/tika-1.3');

// TIKA_APP
//    Full file path of installed Tika application (jar file).
//    By default, Tika application v1.3 is used:
//       define ('TIKA_APP', TIKA_APP_DIRECTORY.'/tika-app-1.3.jar');
//    Adjust when you have updated the Tika application with a newer version, for example:
//       define ('TIKA_APP', TIKA_APP_DIRECTORY.'/tika-app-1.4.jar');
//
define ('TIKA_APP', TIKA_APP_DIRECTORY.'/tika-app-1.3.jar');

// JAVA_INI_HEAP_SIZE
//    Initial heap size (in MB) of the Java Virtual Machine as set while running the Tika application.
//    Increase this value to handle large files and to improve the processing speed.
//    Default value is 512 MB. Set to zero (0) to rely on default value of Java.
//
define ('JAVA_INI_HEAP_SIZE', 512);

// JAVA_MAX_HEAP_SIZE
//    Maximum heap size (in MB) of the Java Virtual Machine as set while running the Tika application.
//    Increase this value to handle large files and to avoid the OutOfMemory exception thrown by Java Virtual Machine.
//    Default value is 1024 MB. Set to zero (0) to rely on default value of Java.
//
define ('JAVA_MAX_HEAP_SIZE', 1024);

require_once dirname(__FILE__)."/osconfig.php";
