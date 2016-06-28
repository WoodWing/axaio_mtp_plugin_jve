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


// Using the mapping rules you can specify which component to assign to wich article component selector
// channelId: The id of channel for which mapping rules applies, if empty the rule applies to all channels
// labelRegEx: Regular expression executed on the display name of the article component selector
//    For more information about the regular expression see: http://php.net/manual/en/function.preg-match.php
// componentName: Name of the component to use, case insensitive
$mapping_rules = array();

//Example 
//$mapping_rules[] = array ('channelId' => '11', 'labelRegEx' => '/teaser/i', 'componentName' => 'head');
//$mapping_rules[] = array ('channelId' => '11', 'labelRegEx' => '/Summary \(body\)/i', 'componentName' => 'intro');
//$mapping_rules[] = array ('channelId' => '11', 'labelRegEx' => '/body/i', 'componentName' => 'body');


define('MAPPING_RULES',  serialize($mapping_rules));


//Using the article rules you can specify which article to use for the mapping
//channelId: Id of the channel
//type: The type of the rul
//		upgrade: Use the rule incase content was published with an older version of the plugin which did not support Publish Forms, for example Drupal 6
//		dossier: Use this rule to search for articles with in the dossier
//			articleNameRegEx: The regular expression used to search for articles
//		copy: Use this rule to copy an existing article in the dossier (Slower then upgrade and dossier)
//			brandId: The brand to search in
//				- If empty the brand of the channel is used
//				- If -1 all brands
//				- If specified this brand will be searched 
//			articleName: The name of the article to search for
//			suffix: The suffix is added to the article name when the article is copied into the dossier
$article_rules = array ();

//Example
//$article_rules[] = array ('channelId' => '11', 'type' => 'upgrade');
//$article_rules[] = array ('channelId' => '11', 'type' => 'dossier', 'articleNameRegEx' => '/-Web/');
//$article_rules[] = array ('channelId' => '11', 'type' => 'copy', 'brandId' => '-1', 'articleName' => 'WebArticleTemplate', 'suffix' => '-Web');


define('ARTICLE_RULES',  serialize($article_rules));
