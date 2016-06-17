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

global $brandLimits;

// =========== TO EDIT:
// Limits per brand, 0 for unlimited. 
// '*' can be used as fallback for undefined brands which would otherwise be unlimited
$brandLimits = array( 
	'WoodWing' => 1,
	'*' => 0
);

// Message to show if logons exceeded, use $limit to show allowed logons and $userOrg to show your brand
DEFINE( 'BRANDLOGON_LIMIT_MSG', 'Reached maximum number of logons ($limit) for your brand ($userOrg).' );
