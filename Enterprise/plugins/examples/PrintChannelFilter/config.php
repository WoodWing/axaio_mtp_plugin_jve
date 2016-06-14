<?php
/****************************************************************************
   Copyright 2013 WoodWing Software BV

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

//	Show MAXISSUES number of issues, including undefined issues (with no publication date) and current issues
if( !defined('PRINTCHANNELFILTER_MAXISSUES') ) {
   define( 'PRINTCHANNELFILTER_MAXISSUES', 5 );
}

//  Show only issues in a date range from today() til today() plus _RANGE days.
if( !defined('PRINTCHANNELFILTER_RANGE') ) {
   define( 'PRINTCHANNELFILTER_RANGE', 85 );
}
