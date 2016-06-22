<?php
/****************************************************************************
   Copyright 2008-2009 WoodWing Software BV

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

// Smart News configuration / credentials
if( !defined('SMARTNEWS_PUBLISHINTERFACE') ) {
   define( "SMARTNEWS_PUBLISHINTERFACE", "http://localhost/smartnews/publish.php" );
}
if( !defined('SMARTNEWS_USERNAME') ) {
   define( "SMARTNEWS_USERNAME", "woodwing" );
}
if( !defined('SMARTNEWS_PASSWORD') ) {
   define( "SMARTNEWS_PASSWORD", "ww" );
}

// Enterprise element configuration
// the element name that is used for the title of the article
if( !defined('ARTICLE_HEADER') ) {
   define( "ARTICLE_HEADER", "header" );
}
// the element name that is used for the intro of the article
if( !defined('ARTICLE_INTRO') ) {
   define( "ARTICLE_INTRO", "intro" );
}
// the element name that is used for the body of the article
if( !defined('ARTICLE_BODY') ) {
   define( "ARTICLE_BODY", "body" );
}
