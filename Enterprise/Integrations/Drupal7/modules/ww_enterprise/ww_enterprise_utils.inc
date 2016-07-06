<?php

/** Constant for the Enterprise Promote property. */
define('C_DIALOG_DRUPAL7_PROMOTE', 'C_DIALOG_DRUPAL7_PROMOTE');
/** Constant for the Enterprise Sticky property. */
define('C_DIALOG_DRUPAL7_STICKY', 'C_DIALOG_DRUPAL7_STICKY');
/** Constant for the Enterprise Comments property. */
define('C_DIALOG_DRUPAL7_COMMENTS', 'C_DIALOG_DRUPAL7_COMMENTS');
/** Constant for the Content Type Title Field. */
define('C_DIALOG_DRUPAL7_TITLE', 'C_DIALOG_DRUPAL7_TITLE');
/** Constant for the Content Type publish flag. */
define('C_DIALOG_DRUPAL7_PUBLISH', 'C_DIALOG_DRUPAL7_PUBLISH');
/** Constant for the Content Type as passed from Enterprise. */
define('DRUPAL7_CONTENT_TYPE', 'DRUPAL7_CONTENT_TYPE');
/** Constant for the Inline image type. */
define('FILE_USAGE_TYPE_INLINE_IMAGES', 'inline_images');
/** Constant for the Enterprise TimeDiff */
define('ENTERPRISE_TIME_DIFF', 'ENTERPRISE_TIME_DIFF');

/**
 * Wrapper for the db_query function to make it Drupal 7 compatible
 *
 * Drupal 7 db_query:
 * http://api.drupal.org/api/drupal/includes!database!database.inc/function/db_query/7
 *
 * @param string $query The statement query to be executed.
 * @param array $args An array of values to substitute into the query.
 * @param array $options An array of options to control how the query operates.
 * @return DatabaseStatementBase Object
 */
function ww_enterprise_utils_dbQuery( $query, array $args = array(), array $options = array() )
{
	watchdog( 'content', 'db_query() query="'.$query.'" args="'.print_r($args,true).'"', null, WATCHDOG_NOTICE );
	return db_query( $query, $args, $options );
}

/**
 * Wrapper for the obsoleted db_fetch_object function to make it Drupal 7 compatible.
 *
 * @param resource $result A database query result resource, as returned from db_query().
 * @return Object An object representing the next row of the result, or FALSE.
 */
function ww_enterprise_utils_dbFetchObject( $result )
{
	return $result->fetchObject();
}

/**
 * Wrapper for the obsoleted db_fetch_array function to make it Drupal 7 compatible.
 *
 * @param resource $result A database query result resource, as returned from db_query().
 * @return array An associative array representing the next row of the result, or FALSE.
 */
function ww_enterprise_utils_dbFetchArray( $result )
{
	return $result->fetchAssoc();
}

/**
 * Wrapper for the obsoleted db_result function to make it Drupal 7 compatible.
 *
 * @param resource $result A database query result resource, as returned from db_query().
 * @return mixed The resulting field or FALSE.
 */
function ww_enterprise_utils_dbResult( $result )
{
	return $result->fetchField();
}

/**
 * Wrapper for the obsoleted file_directory_path function to make it Drupal 7 compatible.
 *
 * @return string
 */
function ww_enterprise_utils_fileDirectoryPath()
{
	return file_default_scheme() . '://';
}

/**
 * Import video node - TBD
 *
 * @todo IMPORTANT: THIS FUNCTION IS NOT USED; A good queue processor should be written and used instead of this function.
 *
 * @param int $node Drupal node ID
 * @param binary $content File data
 * @param string $fileName Name of the source file
 * @param string $ratio Video ratio ('3_4' for 3:4 and '16_9' for 16:9)
 * @param string $tempDir Path to temporary directory on web server
 * @param string $drupalDir Path to Drupal directory on web server
 * @return int node id
 */
function ww_enterprise_utils_importVideo( $node, $content, $fileName, $ratio, $tempDir, $drupalDir )
{
	// keep analyzer happy
	$node = $node;
	$content = $content;

	$return = 0;
	switch ($ratio) {
		case '3_4':
			system( "/usr/bin/ffmpeg -i /var/www/".$tempDir."/".$fileName." -b 200000 -r 25 -s 320x240 -y -ar 22050 /var/www/".$drupalDir.$fileName.".flv", $return );
			break;
		case '16_9':
			system( "/usr/bin/ffmpeg -i /var/www/".$tempDir."/".$fileName." -b 200000 -r 25 -s 320x180 -padtop 30 -padbottom 30 -y -ar 22050 /var/www/".$drupalDir.$fileName.".flv", $return );
			break;
	}
	if( $return || !file_exists( $drupalDir.$fileName.".flv" ) ) {
		// Report error - TBD
		return;
	} // else Create Drupal node - TBD
}

/**
 * Import audio node - TBD
 *
 * @todo IMPORTANT: THIS FUNCTION IS NOT USED; A good queue processor should be written and used instead of this function.
 *
 * @param int $node Drupal node ID
 * @param binary $content File data
 * @param string $fileName Name of the source file
 * @param string $tempDir Path to temporary directory on web server
 * @param string $drupalDir Path to Drupal directory on web server
 *
 * @return int node id
 */
function ww_enterprise_utils_importAudio( $node, $content, $fileName, $tempDir, $drupalDir )
{
	// keep analyzer happy
	$node = $node;
	$content = $content;

	$return = 0;
	system( "/usr/bin/lame --mp3input -b 96 /var/www/".$tempDir."/".$fileName." /var/www/".$drupalDir.$fileName.".mp3", $return );
	if( $return || !file_exists( $drupalDir.$fileName.".mp3" ) ) {
		// Report error - TBD
		return;
	} // else Create Drupal node - TBD
}

/**
 * Checks if the oauth signature is valid and sets the global user
 *
 * @param boolean $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @throws Exception if the logon fails.
 * @return string The UID.
 */
function ww_enterprise_utils_oauth_verify_request( $enableTestMode=false )
{
	// When unit testing we want to circumvent the oauth validations.
	if (!$enableTestMode) {
		try{
			// @todo Uncomment the following when we need to actually do validatation.
            // Read out the DrupalDatabaseInstanceId.
			//$req = DrupalOAuthRequest::from_request();
			//$drupalDatabaseInstanceId = $req->get_parameter('DrupalDatabaseInstanceId');

			// If the DrupalDatabaseInstanceId is not empty, we need to verify that it matches the ID for this instance.
			// If it is empty Enterprise will get a chance to update it when handling the response from Drupal 7.

			//if (!empty($drupalDatabaseInstanceId)) {
			//	if (ww_enterprise_utils_get_configuration_setting('DrupalDatabaseInstanceId') !== $drupalDatabaseInstanceId ) {
			//		throw new OAuthException('The DrupalDatabaseInstanceId does not match this Instance\'s Id');
			//	}
			//}

			module_load_include('inc', 'oauth_common');

			list($signed, $consumer, $token) = oauth_common_verify_request();
			$token = $token; // Keep analyzer happy.

			if (!$signed) {
				throw new OAuthException('The request must be signed');
			}

			if ($consumer->uid) {
				// This authenticates as the user who owns 'key';  It is for 2-stage Oauth
				global $user;
				$user = user_load($consumer->uid);
			}
		}
		catch (Exception $e) {
			drupal_add_http_header('WWW-Authenticate', sprintf('OAuth realm="%s"', url('', array('absolute' => TRUE))));
			throw $e;
		}
		return $user->uid;
	} else {
		return 0;
	}
}

/**
 * Deletes the old previews from the database ww_enterprise_preview table that are older than one hour.
 *
 */
function ww_enterprise_utils_deleteOldPreviews()
{
	$timeLimit = time() - 1 * 60 * 60; // 1 hour
	$sql = "SELECT vid, nid, hash FROM {ww_enterprise_preview} WHERE date < :date";
	$sth = ww_enterprise_utils_dbQuery( $sql, array( ':date' => $timeLimit ) );
	while( ( $node = ww_enterprise_utils_dbFetchObject( $sth ) ) ) {
		$nid = $node->nid;
		node_delete( $nid );
		$sql = "DELETE FROM {ww_enterprise_preview} WHERE nid = :nid";
		ww_enterprise_utils_dbQuery( $sql, array( ':nid' => $nid ) );
	}
}

/**
 * Returns the Drupal commentType Id given the comment type key.
 *
 * Comment Types refer to the permission level of the reader if they can
 * add or read comments or totally no access to the comments of the published node.
 * The CommentTypes available are 'disabled', 'read/write' and 'read'.
 *
 * The CommentTypes are referred differently in three different contexts.
 * Comment Type Key:
 *    For the communication between the Drupal world and Enterprise world.
 *    And so mainly being used in the development scripts.
 *    Values used:'read', 'read/write', 'disabled'
 *
 * Comment Type InternalId:
 *    Used in the Drupal world, which is actually the commentType Id in the Drupal database.
 *    This is how Drupal store the comment types.
 *    Ids used: COMMENT_NODE_CLOSED=1(Read), COMMENT_NODE_OPEN=2(Read/Write), COMMENT_NODE_HIDDEN=0(Disabled)
 *
 * Comment Type HumandReadable:
 *    This is used in UI (Content Station).
 *    It needs to be localized, thus varies depending on the language set in the Enterprise.
 *    Values used(For English): 'read-only', 'read and write', 'read-only'
 *
 * @param string $value The comment type key.
 * @return int The Drupal id of the comment type given.
 */
function ww_enterprise_utils_getDrupalCommentTypeId( $commentTypeKey )
{
	switch( $commentTypeKey ) {
		case 'Read':
			$commentTypeDrupalId = COMMENT_NODE_CLOSED; // COMMENT_NODE_CLOSED = 1
			break;
		case 'Read/Write':
			$commentTypeDrupalId = COMMENT_NODE_OPEN; // COMMENT_NODE_OPEN = 2
			break;
		default: // disable
			$commentTypeDrupalId = COMMENT_NODE_HIDDEN; // COMMENT_NODE_HIDDEN = 0
			break;
	}
	return $commentTypeDrupalId;
}

/**
 * Returns the comment type key given the drupal commentType Id.
 *
 * Refer to ww_enterprise_utils_getDrupalCommentTypeId() for more info.
 *
 * @param $commentTypeDrupalId The Drupal id to retrieve its commentType key.
 * @return string The correspond commentType key.
 */
function ww_enterprise_utils_getCommentTypeKey( $commentTypeDrupalId )
{
	$commentTypeDrupalId = intval($commentTypeDrupalId);

	switch( $commentTypeDrupalId ) {
		case COMMENT_NODE_CLOSED : // = 1
			$commentTypeKey = 'Read';
			break;
		case COMMENT_NODE_OPEN : // = 2
			$commentTypeKey = 'Read/Write';
			break;
		case COMMENT_NODE_HIDDEN : // = 0
		default:
			$commentTypeKey = 'Disable';
			break;
	}
	return $commentTypeKey;
}

/**
 * Returns the URL of the published node.
 *
 * When the published node URL is requested for preview purposes,
 * it expects a hash. The hash will be added to the URL which later
 * will be used to check if the ContentStation user has the access to see
 * the preview.
 *
 * When it is not for preview purposes, meaning it is requesting for
 * the 'normal' URL of the published node. To return the 'normal' URL,
 * it first checks if **'pathauto' or ***'path module is enabled, if enabled,
 * it returns its aliases. Otherwise it tries to gets its clean URL.
 * When all the three mentioned module are not enabled, it falls back to
 * the default Drupal URL like 'http://www.example.com/?q=node/67'.
 *
 * **pathauto module: The Pathauto module automatically generates URL/path aliases
 * for various kinds of content (nodes, taxonomy terms, users) without requiring
 * the user to manually specify the path alias.
 *
 * *** path module: The core Path module lets you optionally create URL aliases for
 * your Drupal pages. This is handy as by default, Drupal creates URL like
 * 'http://www.example.com/?q=node/67' which is not user- or search engine-friendly.
 *
 * @param int $nid node id The published node Drupal Id
 * @param bool $preview True if the URL requested is for preview; False otherwise.
 * @param string $hash if it is a preview send the unique hash (only needed for previews)
 * @return string URL of the publish node. When no node id given, the Drupal site URL is returned.
 */
function ww_enterprise_utils_getURL( $nid, $preview, $hash = '' )
{
	// build url
	global $base_url;

	// remove :80 from base url, xmlrpc client on Enterprise side adds this to SERVER_HOST :(
	$url = preg_replace('|^http://([^/]*):80/|', 'http://$1/', $base_url, 1);

	if( $nid == null ) {
		return $url;
	}

	if ($preview) {
		$url .= (variable_get('clean_url', "") == "1") ? '/' : '/?q=';
		$url .= 'ww_enterprise/preview/'.$nid.'/'.$hash;
	} else {
		if( module_exists("pathauto") || module_exists("path") ) {

			$sql = "SELECT alias FROM {url_alias} WHERE source = :src";
			$sth = ww_enterprise_utils_dbQuery( $sql, array( ':src' => 'node/'.$nid ) );
			$alias = ww_enterprise_utils_dbFetchObject( $sth );

			if( !empty($alias->alias) ) {
				$url .= '/' . ((variable_get('clean_url', "") == "1") ? '' : '?q=') . $alias->alias;
			} else {
				if(variable_get('clean_url', "") == "1") {
					$url .= '/node/' . $nid;
				} else {
					$url .= '/?q=node/' . $nid;
				}
			}
		} else {
			if(variable_get('clean_url', "") == "1") {
				$url .= '/node/' . $nid;
			} else {
				$url .= '/?q=node/' . $nid;
			}
		}
	}

	return $url;
}

/**
 * Changes the alias of a published preview.
 *
 * This function uses the "pathauto" and "path" modules to change the alias
 * of a preview post. This is needed so when the actual post is published there
 * will not be a -1 added to the alias.
 * The alias will be cleaned, prefixed and made unique when needed.
 *
 * @param string $title The title of the post needed for the alias.
 * @param int $nid The note id.
 */
function ww_enterprise_utils_change_preview_alias( $title, $nid )
{
    if( module_exists( "pathauto" ) && module_exists( "path" )) {
        $path = array();

        $result = path_load( "node/". $nid ); // Load the current alias

        $path['pid'] = ( isset( $result['pid'] ) ? $result['pid'] : 0 );

        $cleanedTitle = pathauto_clean_alias( $title ); // Clean the alias
        $alias = "content/preview-" . $cleanedTitle; // Add the prefix
        $source = "node/" . $nid;

        pathauto_alias_uniquify( $alias, $source, null ); // Make the alias unique

        $path['alias'] = $alias;
        $path['source'] = $source;

        path_save( $path ); // Save the alias
    }
}

/**
 * Creates a file of the given data and filename manually.
 * Currently only called by the ww_enterprise.test script,
 * otherwise should use Drupal 7 function file_save_upload() instead.
 *
 * @param string $data
 * @param string $fileName
 * @param string $fileType
 * @return object
 */
function ww_enterprise_utils_createFile( $data, $fileName, $fileType )
{
	global $user;

	$fileObj = new stdClass();
	$fileObj->filename = $fileName; //$file['name'] . $file['ext'];
	$filePath = file_save_data($data,
		file_create_filename( $fileObj->filename, ww_enterprise_utils_fileDirectoryPath() ) );
	$fileObj->fid = 0;
	$fileObj->uid = $user->uid;
	$fileObj->filepath = $filePath;
	$fileObj->filemime = $fileType; //$file['type'];
	$fileObj->filesize = strlen($data);
	$fileObj->status = 0;
	$fileObj->timestamp = time();
	drupal_write_record('files', $fileObj);

	return $fileObj;
}

/**
 * Reads out the comment, sticky and promoted properties for the specified content type.
 *
 * Returns an array of key values, where the key matches the property (sticky, promoted or comments) and the value
 * matches one appropriate for the type of field.
 *
 * Array composition:
 *
 * array(
 *    'C_DIALOG_DRUPAL7_STICKY' => 0, // 0 not sticky, 1 sticky, default 0.
 *    'C_DIALOG_DRUPAL7_PROMOTE' => 0, // 0 not promoted, 1 promoted, default 0.
 *    'C_DIALOG_DRUPAL7_COMMENTS' => 0, // 0 disabled (Hidden) , 1 Read only (Closed), 2 Read/Write (Open), default 0.
 * )
 *
 * @param string $contentType The Content Type for which to retrieve the properties.
 * @return int[] an array of integer values.
 */
function ww_enterprise_utils_getContentTypeProperties( $contentType )
{
	// Retrieve NODE and COMMENT settings for the content type.
	$nodeOptions = variable_get( 'node_options_' . $contentType, null);
	$commentOptions = variable_get('comment_' . $contentType, null);

	// Resolve the Node and Comment settings.
	$sticky = (!is_null($nodeOptions) && in_array('sticky', $nodeOptions))
		? NODE_STICKY
		: NODE_NOT_STICKY;

	$promoted = (!is_null($nodeOptions) && in_array('promote', $nodeOptions))
		? NODE_PROMOTED
		: NODE_NOT_PROMOTED;

	// COMMENT_NODE_HIDDEN = 0 = Not available/allowed.
	// COMMENT_NODE_CLOSED = 1 = Read Only.
	// COMMENT_NODE_OPEN   = 2 = Read / Write.
	$comments = (!is_null($commentOptions))
		? $commentOptions
		: COMMENT_NODE_HIDDEN; // Not available by default. COMMENT_NODE_HIDDEN = 0

	return array(
		C_DIALOG_DRUPAL7_STICKY => $sticky,
		C_DIALOG_DRUPAL7_PROMOTE => $promoted,
		C_DIALOG_DRUPAL7_COMMENTS => ww_enterprise_utils_getCommentTypeKey($comments),
	);
}

/**
 * Returns the terms for the provided vocabulary name.
 *
 * @param string $vocabularyName
 * @return array
 */
function ww_enterprise_utils_getTermsByVocabularyName($vocabularyName)
{
	$terms = array();
	$vid = ww_enterprise_getVocabularyIdByName( $vocabularyName );

	if (!is_null($vid)) {
		$terms = taxonomy_get_tree( $vid );
	}
	return $terms;
}

/**
 * Returns the tid of a vocabulary term.
 *
 * @param string $vocabularyName Name of the vocabulary.
 * @param string $term The term to seach for.
 * @return string tid or empty if not found.
 */
function ww_enterprise_utils_getTidByVocabularyTerm($vocabularyName, $term) {
	$vid = ww_enterprise_getVocabularyIdByName( $vocabularyName );

	if (!is_null($vid)) {
        $sql = "SELECT tid FROM {taxonomy_term_data} WHERE vid = :vid AND name = :name";
        $sth = ww_enterprise_utils_dbQuery( $sql, array( ':vid' => $vid, ':name' => $term) );
        if( ( $dbObj = ww_enterprise_utils_dbFetchObject( $sth ) ) ) {
            return  $dbObj->tid;
        }
    }
    return '';
}

/**
 * Retrieves Article content data.
 *
 * Retrieves the content for an Article and resolves inline images that need to be
 * matched to Enterprise. Inline image attachments are translated into File usages
 * later and are merged into the $formValues.
 *
 * @param array $formValues The complete form values passed from Enterprise.
 * @param string $fieldName The name of the field for which to retrieve the content.
 * @return array The updated formValues.
 */
function ww_enterprise_utils_updateArticleContents( $formValues, $fieldName )
{
	$body = null;
	$summary = null;

	$temp = $formValues[$fieldName];
	// Use array_key_exists since the value can also be null.
	if ( array_key_exists('value', $temp) || array_key_exists('summary', $temp) ) {
		$summary = isset($temp['summary']) ? $temp['summary'] : null;
		$body = isset($temp['value']) ? $temp['value'] : null;
	} else {
		$body = $temp;
	}

	// If an Article Component is not set, or does not contain any Articles, we should return the formValues as is.
	if ( ( !is_array($body[0]) || !isset($body[0]['elements']) )
		    && ( !is_array($summary[0]) || !isset($summary[0]['elements']) ) ) {
		$formValues[$fieldName] = array('');
		return $formValues;
	}

	// Get the Article Content.
	$articleContent = $body[0]['elements'];
	$summaryContent = ($summary) ? $summary[0]['elements'] : null;

	// If there is content, but no attachments, we should return the article content as is.
	if ( ( !isset($body[0]['attachments']) || !is_array($body[0]['attachments']) || count($body[0]['attachments']) == 0 )
		&& ( !isset($summary[0]['attachments']) || !is_array($summary[0]['attachments']) || count($summary[0]['attachments']) == 0 ) )
	{
		if ( $summaryContent ) {
			$propVal = array( 'summary' => $summaryContent, 'value' => $articleContent );
		} else {
			$propVal = array( $articleContent );
		}
		$formValues[$fieldName] = $propVal;
		return $formValues;
	}

	// Resolve the inline images!
	$articleContent = ww_enterprise_utils_resolveInlineImages( $formValues, $articleContent, $body[0]['attachments']);
	if ( $summaryContent ) {
		$summaryContent = ww_enterprise_utils_resolveInlineImages( $formValues, $summaryContent, $summary[0]['attachments']);
	}

	// After doing this for all the urls, return the content.
	if ( $summaryContent ) {
		$propVal = array( 'summary' => $summaryContent, 'value' => $articleContent );
	} else {
		$propVal = array( $articleContent );
	}
	$formValues[$fieldName] = $propVal;

	return $formValues;
}

function ww_enterprise_utils_resolveInlineImages( &$formValues, $content, $attachments )
{
	// Handle the attachments to correctly use inline images.
	// TODO: Safer and faster to use xpath expression instead of regular expression.
	// Go through all the images and replace the img tags with a link to the drupal image.
	$pattern = '/<img[^>]*id=\"ent_([^\"]*)[^>]*src=\"([^\"]*)[^>]*>/i';
	$patternResult = array();
	preg_match_all( $pattern, $content, $patternResult );
	// $patternResult array elements info.
	// [0] = the full image element (<img>)
	// [1] = the image Enterprise id (without the "ent_" prefix)
	// [2] = module name(ww_enterprise).

	foreach ($attachments as $enterpriseId => $drupalId) {

		// Load the Image.
		$file = file_load($drupalId);

		for( $i=0; $i<count($patternResult[0]); $i++ ) {
			if( $patternResult[1][$i] == $enterpriseId ) {
				$entImage = $patternResult[0][$i]; // '<img id="ent_899901496" src="ww_enterprise"/>'
				$drupalImageUrl = file_create_url( $file->uri ); // FID
				$drupalImage = str_replace( $patternResult[2][$i], $drupalImageUrl, $entImage ); // after replacement, src="http://..."
				$content = str_replace( $entImage, $drupalImage, $content );
			}
		}

		// Prepare the Attachment of the File to the Node for handling later.
		$formValues[FILE_USAGE_TYPE_INLINE_IMAGES][] = $file;
	}

	return $content;
}

/**
 * Deletes Files that are used by a Node.
 *
 * Deletes all the Files that are recorded in the file_usage table for a specified Node Id.
 * This operation does a force delete to remove the files.
 *
 * @param integer $nid The Node ID for which to remove File Attachments.
 */
function ww_enterprise_utils_deleteAttachmentsForNode( $nid )
{
	// Retrieve the file usages from the Drupal database and remove them.
	$result = db_query('SELECT fid FROM {file_usage} WHERE id = :nid', array(':nid' => $nid));
	foreach ($result as $record) {
		// Load the file for the file ID.
		$file = file_load($record->fid);
		// Remove the File.
		if ($file) {
			file_delete($file, true);
		}
	}
}

/**
 * Logs a PHP object to file.
 *
 * Logs (var_dump, var_export) an object to a log folder.
 *
 * @todo: Remove when no longer needed for debugging.
 * @param object|null $phpObject Object to be logged.
 * @param string $function The name of the function writing the log entry.
 * @param string $id An identifying string for this log entry.
 * @param string $logFolder The folder where to write the log to, default '/tmp/'
 * @return bool Whether or not the log file was written.
 */
function ww_enterprise_utils_logPhpObject( $phpObject=null, $function, $id, $logFolder='/tmp/' )
{
	if( is_null($logFolder) ) {
		return false;
	}

	// Use var_dump for logging.
	ob_start();
	var_dump( $phpObject );
	$varDump = ob_get_contents();
	ob_end_clean();
	$objName = is_object( $phpObject ) ? get_class( $phpObject ) : gettype( $phpObject );
	$microTime = explode( ' ', microtime() );
	$time = sprintf( '%03d', round($microTime[0]*1000) );
	$currTimeStamp = date('His').'_'.$time;
	$phpLogFile = $logFolder.$currTimeStamp.$id.'_'.$function.'_'.$objName . '.txt';
	file_put_contents( $phpLogFile, $varDump );

	// Use var_export for logging.
	$varExport = var_export( $phpObject, true);
	$microTime = explode( ' ', microtime() );
	$time = sprintf( '%03d', round($microTime[0]*1000) );
	$currTimeStamp = date('His').'_'.$time;
	$phpLogFile = $logFolder.$currTimeStamp.$id.'_'.$function.'_'.$objName . '.txt';
	file_put_contents( $phpLogFile, $varExport );
	return true;
}


/**
 * Checks the filters for the Drupal installation.
 *
 * @throws Exception Throws an Exception if the filters are incorrectly configured.
 * @return void.
 */
function ww_enterprise_checkFilters()
{
	// Check for the presence of the full_html filter.
	// Determine if there is a filter for 'full_html', that it is enabled, and that it is for HTML.
	$filter = filter_format_load('full_html');
	if ( !$filter || $filter->status != '1'){
		throw new Exception( 'The mandatory Drupal text format "full_html" is either missing or disabled. Please ' .
			'ensure that the format exists and is not disabled.' );
	}
}

/**
 * Retrieves a vocabulary ID for the given Vocabulary (machine) name.
 *
 * @param string $vocabularyName The Name for which to retrieve the Vocabulary Id.
 * @return null | int The Vocabulary Id, or null in case the vocabulary cannot be found.
 */
function ww_enterprise_getVocabularyIdByName( $vocabularyName )
{
	$sql = "SELECT vid FROM {taxonomy_vocabulary} WHERE machine_name = :name ORDER BY name ASC";
	$sth = ww_enterprise_utils_dbQuery( $sql, array( ':name' => $vocabularyName ) );
	$vid =  ( $dbObj = ww_enterprise_utils_dbFetchObject( $sth ) )  ? $dbObj->vid : null;
	return $vid;
}

/**
 * Resolves the type of a ContentType based on the orig_type value.
 *
 * Enterprise sends publishing requests using the orig_type, this function can be
 * used to find the correct type (expected by node_save / node creation).
 *
 * @param string $contentType The original value for which to get the current type.
 * @return null|string The current type.
 */
function ww_enterprise_utils_getContenTypeFromOriginal( $contentType )
{
	$sql = "SELECT type FROM {node_type} WHERE orig_type = :contentType ORDER BY name ASC";
	$sth = ww_enterprise_utils_dbQuery( $sql, array( ':contentType' => $contentType ) );
	$contentType =  ( $dbObj = ww_enterprise_utils_dbFetchObject( $sth ) )  ? $dbObj->type : null;
	return $contentType;
}

/**
 * Resolves the orig_type of a ContentType based on the type value.
 *
 * Enterprise expects certain data to be returned bearing the orig_type instead of the type of the ContentType.
 * This function resolves the orig_type based on the passed in type.
 *
 * @param string $contentType The type for which to get the original type.
 * @return null|string The original type.
 */
function ww_enterprise_utils_getOriginalTypeFromType( $contentType )
{
	$sql = "SELECT orig_type FROM {node_type} WHERE type = :contentType ORDER BY name ASC";
	$sth = ww_enterprise_utils_dbQuery( $sql, array( ':contentType' => $contentType ) );
	$contentType =  ( $dbObj = ww_enterprise_utils_dbFetchObject( $sth ) )  ? $dbObj->orig_type : null;
	return $contentType;
}

/**
 * Generates a new Global Unique Identifier (GUID).
 *
 * The GUID is created in Adobe-compatible format: 8-4-4-4-12.
 *
 * @return string The generated GUID.
 */
function ww_enterprise_utils_generate_guid()
{
	// Create an MD5 sum of a random number (this is a 32 character hex string).
	$raw_GUID = md5( uniqid( getmypid() . rand( ) . (double)microtime()*1000000, TRUE ) );

	// Format the string into 8-4-4-4-12 (numbers are the number of characters in each block) and return it.
	return  substr($raw_GUID,0,8) . "-" . substr($raw_GUID,8,4) . "-" . substr($raw_GUID,12,4)
		. "-" . substr($raw_GUID,16,4) . "-" . substr($raw_GUID,20,12);
}

/**
 * Retrieve the configuration setting from the database.
 *
 * @throws Exception Throws an Exception if it is attempted to store something on an empty $setting.
 * @param string $setting The setting to be retrieved from the database, may not be null.
 * @return null|Object The retrieved database row object.
 */
function ww_enterprise_utils_get_configuration_setting( $setting )
{
	$setting = strval( $setting );
	$settingObject = null;

	// We do not allow setting an empty $setting for the configuration table.
	if( empty( $setting )) {
		throw new Exception('Variable ' .$setting . ' may not be empty');
	}

	// See if the setting / value already exist in the database.
	$rows = db_query('SELECT * FROM {ww_enterprise_configuration} WHERE setting = :setting limit 1',
																						array(':setting' => $setting));

	if ( $rows ) foreach ( $rows as $row ) {
		$settingObject = $row;
	}
	return $settingObject;
}

/**
 * Set a value in the ww_enterprise_configuration database.
 *
 * @throws Exception Throws an Exception if it is attempted to store something on an empty $settingName.
 * @param string $settingName The name of the setting to be stored, may not be empty.
 * @param string $value The value to store for the $settingName.
 * @return bool Whether or not setting the value was successful.
 */
function ww_enterprise_utils_save_configuration_setting( $settingName, $value)
{
	$settingName = strval( $settingName );
	$value = strval( $value );

	// We do not allow setting an empty $setting for the configuration table.
	if( empty( $settingName )) {
		throw new Exception('Variable ' . $settingName . ' may not be empty');
	}

	// No try / catch needed since we already have pre-validated the $setting variable.
	$settingExists = ww_enterprise_utils_configuration_setting_exists( $settingName );

	if ( !$settingExists ) {
		// insert a new row in ww_enterprise_configuration.
		$success = db_insert('ww_enterprise_configuration')
			->fields(array( 'setting' => $settingName, 'value' => $value ) )
			->execute();
	} else {
		// The setting already exists, so update it with the new value.
		$success = db_update('ww_enterprise_configuration')
			->fields(array( 'setting' => $settingName, 'value' => $value ) )
			->condition('setting', $settingName, '=')
			->execute();
	}
	return $success;
}

/**
 * Checks if a setting already exists in the ww_enterprise_configuration table.
 *
 * @throws Exception Throws an Exception if the variable $settingName is empty.
 * @param string $settingName The name of the setting to check in the ww_enterprise_configuration table, may not be empty.
 * @return bool Whether or not the setting exists.
 */
function ww_enterprise_utils_configuration_setting_exists ( $settingName )
{
	$settingName = strval( $settingName );
	$settingExists = false;

	// We do not allow setting an empty $settingName for the configuration table.
	if( empty( $settingName )) {
		throw new Exception('Variable '. $settingName .' may not be empty');
	}

	// See if the setting / value already exist in the database, setting has been prevalidated.
	$record = ww_enterprise_utils_get_configuration_setting( $settingName );

	if (!is_null( $record )){
		$settingExists = (strval ($record->setting) == $settingName );
	}
	return $settingExists;
}