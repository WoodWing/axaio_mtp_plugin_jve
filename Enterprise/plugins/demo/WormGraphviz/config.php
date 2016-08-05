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

// Path to the Graphviz dot executable.
if( !defined('GRAPHVIZ_APPLICATION_PATH') ) {
   define( 'GRAPHVIZ_APPLICATION_PATH', '/opt/local/bin/dot' );
}

// Path to the ps2pdf dot executable. Needed when GRAPHVIZ_OUTPUT_FORMAT is set to 'pdf'.
if( !defined('GRAPHVIZ_PS2PDF_APPLICATION_PATH') ) {
   define( 'GRAPHVIZ_PS2PDF_APPLICATION_PATH', '/usr/local/bin/ps2pdf' );
}

// File format type to compose Graphviz reports. Supported values are 'pdf' and 'svg'.
if( !defined('GRAPHVIZ_OUTPUT_FORMAT') ) {
   define( 'GRAPHVIZ_OUTPUT_FORMAT', 'svg' );
}