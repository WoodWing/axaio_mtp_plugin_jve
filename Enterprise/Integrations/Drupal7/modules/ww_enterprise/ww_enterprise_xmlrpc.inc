<?php
/**
 * Implementation of Drupals hook_xmlrpc() function.
 *
 * Defines all the XML-RPC methods and mapped it to the corresponding
 * callback functions for this module. So that when a specific XML-RPC method
 * is invoked by the client(Drupal7 plugin), the corresponding registered
 * call back function will be called.
 *
 * @see system.api.php, hook_xmlrpc()
 * @return array An array of mapped XML-RPC methods with its callback hooks.
 */
function ww_enterprise_xmlrpc()
{
	$xmlrpc = array();

	$xmlrpc[] = array(
		'enterprise.saveNode',
		'ww_enterprise_xmlrpc_saveNode',
		array(
			'struct', // Return value.
			'array', // Publish options.
			'array', // Field values.
			'array', // Attachments.
		),
		t('Publish/Update/Preview a PublishForm Node.'),
	);

	$xmlrpc[] = array(
		'enterprise.removeNode',
		'ww_enterprise_xmlrpc_removeNode',
		array(
			'struct', // Return value.
			'int',  // Node ID.
		),
		t('Unpublish/Remove a Node.'),
	);

	$xmlrpc[] = array(
		'enterprise.getFields',
		'ww_enterprise_xmlrpc_getFields',
		array( 'struct' ),
		t(''),
	);

	$xmlrpc[] = array(
		'enterprise.nodeGetInfo',
		'ww_enterprise_xmlrpc_nodeGetInfo',
		array( 'struct', 'int' ),
		t(''),
	);

	$xmlrpc[] = array(
		'enterprise.testConfig',
		'ww_enterprise_xmlrpc_testConfig',
		array( 'struct' ),
		t(''),
	);

	$xmlrpc[] = array(
		'enterprise.getFileId',
		'ww_enterprise_xmlrpc_getFileId',
		array( 'struct', 'struct' ),
		t(''),
	);

	$xmlrpc[] = array(
		'enterprise.getContentTypes',
		'ww_enterprise_xmlrpc_getContentTypes',
		array( 'struct' ),
		t(''),
	);

	$xmlrpc[] = array(
		'enterprise.getUrl',
		'ww_enterprise_xmlrpc_getUrl',
		array( 'string', 'string' ),
		t(''),
	);

	$xmlrpc[] = array(
		'enterprise.getVocabularyNames',
		'ww_enterprise_xmlrpc_getVocabularyNames',
		array( 'struct', 'struct' ),
		t('Retrieves the vocabulary names and Term Entity configuration.'),
	);

	$xmlrpc[] = array(
		'enterprise.getVocabulary',
		'ww_enterprise_xmlrpc_getVocabulary',
		array( 'struct', 'int' ),
		t('Retrieves a specific vocabulary based on the vid.'),
	);

	return $xmlrpc;
}

/**
 * Retrieve the vocabulary names and Term Entity configuration.
 *
 * Returns an array with the system_name of the vocabulary as the key and an array containing the vid, name and ww_term_entity
 * as array items.
 *
 * @param array $params The input parameters.
 * @param bool $testmode Whether or not to test this function.
 * @return array An array of Vocabulary items.
 */
function ww_enterprise_xmlrpc_getVocabularyNames( $params, $testmode=false )
{
	$params = $params; // Keep analyzer happy.
	$result = array();
	try {
		// Verify the request.
		$uid = ww_enterprise_utils_oauth_verify_request( $testmode );
		$uid = $uid; // Keep analyzer happy.

		// Retrieve the vocabulary names and construct an array.
		$vocabularies = taxonomy_get_vocabularies();
		if ( $vocabularies ) foreach ( $vocabularies as $vocabulary ) {
			$result[$vocabulary->machine_name] = array('vid' => $vocabulary->vid, 'name' => $vocabulary->name, 'ww_term_entity' => $vocabulary->ww_term_entity);
		}

		// Add the Drupal database instance id to the response.
		$drupalDatabaseInstanceIdObject = ww_enterprise_utils_get_configuration_setting( 'DrupalDatabaseInstanceId' );
		$dbInstanceId = ( is_null( $drupalDatabaseInstanceIdObject )) ? '' : $drupalDatabaseInstanceIdObject->value;
		$result['DrupalDatabaseInstanceId'] = $dbInstanceId;

	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage() , array(), WATCHDOG_ERROR );
		$result['errors'] = 'Vocabulary names could not be retrieved: ' . $e->getMessage();
	}
	return $result;
}

/**
 * Retrieves the content of a vocabulary.
 *
 * The content is a lean array consisting of the taxonomy term names only. The Key used in the array is equal to the
 * tid of the taxonomy term.
 *
 * @param int $vid The Id of the vocabulary for which to retrieve the taxonomy terms.
 * @param bool $testmode Whether or not this function should be tested.
 * @return array An array of vocabulary items and content.
 */
function ww_enterprise_xmlrpc_getVocabulary( $vid, $testmode = false )
{
	$result = array();
	try {
		// Verify the request.
		$uid = ww_enterprise_utils_oauth_verify_request( $testmode );
		$uid = $uid; // Keep analyzer happy.

		$terms = taxonomy_get_tree( $vid );

		if ($terms) foreach ($terms as $term ) {
			$result[$term->tid] = $term->name;
		}

		// Add the Drupal database instance id to the response.
		$drupalDatabaseInstanceIdObject = ww_enterprise_utils_get_configuration_setting( 'DrupalDatabaseInstanceId' );
		$dbInstanceId = ( is_null( $drupalDatabaseInstanceIdObject )) ? '' : $drupalDatabaseInstanceIdObject->value;
		$result['DrupalDatabaseInstanceId'] = $dbInstanceId;
	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage() , array(), WATCHDOG_ERROR );
		$result['errors'] = 'Vocabulary could not be retrieved: ' . $e->getMessage();
	}
	return $result;
}


function ww_enterprise_xmlrpc_saveNode( $dossierProps, $formValues, $attachments, $testmode=false )
{
	// Keep analyzer happy
	$attachments = $attachments;
	$result = array();

	try {
		// Log on to Drupal.
		$uid = ww_enterprise_utils_oauth_verify_request( $testmode );
		ww_enterprise_checkFilters();

		// Always clean up any old Previews.
		ww_enterprise_utils_deleteOldPreviews();

		// Rework the node type.
		$originalType = $formValues[DRUPAL7_CONTENT_TYPE][0];
		$nodeType =	ww_enterprise_utils_getContenTypeFromOriginal( $originalType );

		// If the node type cannot be resolved error out.
		if ( is_null( $nodeType ) ) {
			throw new Exception ('Attempting to save a node with an unknown Node Type: ' . $originalType);
		}

		// Determine the Action (Publish / Update) to take for a Node.
		switch ($dossierProps['Action']) {

			case 'Publish' :
				if (!empty($dossierProps['ExternalId'])) {
					throw new Exception('A publish action requires a Node ID to empty.');
				}

				// For publishing we try to find the username that is publishing from
				// Enterprise. This will try to get the user from the database
				// but if not found, the oauth user will be used.
				if ( isset($dossierProps['Username']) && $dossierProps['Username'] ) {
					$user = user_load_by_name( trim($dossierProps['Username']) );
					if ( $user ) {
						$uid = $user->uid;
					}
				}
			case 'Preview' :
				// create a new node.
				$node = new stdClass();
				$node->type = $nodeType;
				node_object_prepare($node);
				break;
			case 'Update' :
				// Verify that we have a Node ID for the node to be updated.
				if (is_null($dossierProps['ExternalId'])) {
					throw new Exception('An update action requires a Node ID to be known.');
				}

				// Use the externalId to load the original Node.
				$node = node_load($dossierProps['ExternalId']);

				// When updating we preserve the original author.
				$uid = $node->uid;

				// If the node could not be loaded, bail out.
				if (!is_object($node)) {
					throw new Exception('Node could not be loaded for NID: ' . $dossierProps['ExternalId']);
				}
				break;
			default :
				// If the action is not known we should not continue.
				throw new Exception ('Unknown Action: ' . $dossierProps['Action']);
		}

		// Gather the entered Form values.
		$formValues[FILE_USAGE_TYPE_INLINE_IMAGES] = array();
		$formValues = mapPublishFormValues($formValues);

		// If a Preview is requested, we need to force the status to be 0. (Not published)
		$node->status = ($dossierProps['Preview']) ? 0 : $formValues[C_DIALOG_DRUPAL7_PUBLISH][0];

		// Set properties.
		$node->title = $formValues[C_DIALOG_DRUPAL7_TITLE][0];
		$node->language = LANGUAGE_NONE;
		$node->sticky = intval($formValues[C_DIALOG_DRUPAL7_STICKY][0]);
		$node->promote = intval($formValues[C_DIALOG_DRUPAL7_PROMOTE][0]);
		$node->comment = $formValues[C_DIALOG_DRUPAL7_COMMENTS][0];
		$node->uid = $uid;

		$instances = field_info_instances( 'node', $nodeType );

		// Set the fields.
		// Todo: how to handle existing file attachments.
		foreach ($formValues as $key => $values ) {
            if ($key != ENTERPRISE_TIME_DIFF &&
				$key != DRUPAL7_CONTENT_TYPE &&
				$key != C_DIALOG_DRUPAL7_COMMENTS &&
				$key != C_DIALOG_DRUPAL7_PROMOTE &&
				$key != C_DIALOG_DRUPAL7_STICKY &&
				$key != C_DIALOG_DRUPAL7_TITLE &&
				$key != C_DIALOG_DRUPAL7_PUBLISH &&
				$key != FILE_USAGE_TYPE_INLINE_IMAGES ){

				if( count( $values ) == 0 ) { // Field is left empty
					//If a field is left empty, and we are handling an update, reset the fields as well.
					if( $dossierProps['Action'] == 'Update' ){
						// Remove the previous file if it exists on a fileselector / articlecomponent.
						$field_info_fields = field_info_field($key);
						if ($field_info_fields['type'] == 'image' || $field_info_fields['type'] == 'file') {
							if (isset($node->$key) && isset($node->$key[LANGUAGE_NONE])){
								// Remove the old file(s).
								foreach ($node->$key[LANGUAGE_NONE] as $fileArray) {
									// Remove the old files.
									$file = file_load($fileArray['fid']);
									file_delete($file);
								}
							}
						}
					}

					// if there are no values then set the node key to empty and continue on to the next property.
					$node->$key = array();
				} else {
					$field = array();
					$field[LANGUAGE_NONE] = array();

					// if taxonomy, then we need to set the 'tid' instead of the value of the field.
					$field_info_fields = field_info_field($key);
					$isTerm = ($field_info_fields['type'] == 'taxonomy_term_reference');

					// Handle file uploads.
					if ($field_info_fields['type'] == 'image' || $field_info_fields['type'] == 'file') {
						// Remove any old values on an update. Can be multiple files.
						if ($dossierProps['Action'] == 'Update') {
							if (isset($node->$key) && isset($node->$key[LANGUAGE_NONE])){
								// Remove the old file(s)
								foreach ($node->$key[LANGUAGE_NONE] as $fileArray) {
									if (empty($values['fid']) || $fileArray['fid'] != $values['fid']) {
										// Remove the old files.
										$file = file_load($fileArray['fid']);
										file_delete($file);
									}
								}
							}
						}

						// Add the new File(s).
						$field[LANGUAGE_NONE] = $values;
					} elseif ( $field_info_fields['type'] == 'datestamp' ){
						$fieldValues = array();
						$date = reset($values);
						if( empty($date) ) {
							$fieldValues['value'] = null;
						} else{
							$fieldValues['value'] = $date;
						}
						$field[LANGUAGE_NONE][] = $fieldValues;
					// Handle ISO dates or unix timestamps
					} elseif ( $field_info_fields['type'] == 'date' || $field_info_fields['type'] == 'datetime' ){
						$fieldValues = array();
						$date = reset($values);
						if( empty($date) ) {
							$fieldValues['value'] = null;
						} else{
							$format = ($field_info_fields['type'] == 'datetime') ? 'Y-m-d H:i:s' : 'Y-m-d\TH:i:s';
							$fieldValues['value'] = gmdate($format, $date);
							$field[LANGUAGE_NONE][] = $fieldValues;
						}
					} elseif (count($values) > 0) { // Anything other than a file.
						if ( isset( $values['summary'] ) && isset( $values['value'] ) ) {
							$fieldValues = array();
							foreach ($values as $property => $value ) {
								$fieldValues[$property] = $value;
							}
							// In case we are dealing with an Enterprise Article which will output HTML, we should set the
							// view to HTML as well.
							if (isset($instances[$key]['settings']['text_processing']) &&
								($instances[$key]['settings']['text_processing'] == '1')) {
								$fieldValues['format'] = 'full_html';
							}

							$field[LANGUAGE_NONE][] = $fieldValues;
						} else {
							foreach ($values as $value ) {
								$fieldValues = array();
								if ($isTerm) {
									$fieldValues['tid'] = $value;
								} else {
									$fieldValues['value'] = $value;

									// In case we are dealing with an Enterprise Article which will output HTML, we should set the
									// view to HTML as well.
									if (isset($instances[$key]['settings']['text_processing']) &&
										($instances[$key]['settings']['text_processing'] == '1')) {
										$fieldValues['format'] = 'full_html';
									}
								}
								$field[LANGUAGE_NONE][] = $fieldValues;
							}
						}
					}

					// Now place the field on the Node.
					$node->$key = $field;
				}
			}
		}

		// Store the node.
		node_save($node);

		// Create any File Usages for attachments that were used as inline images if needed.
		// These inline images are gathered and added to the formValues in the mapPublishFormValues function.
		if (count($formValues[FILE_USAGE_TYPE_INLINE_IMAGES]) > 0) {
			foreach ($formValues[FILE_USAGE_TYPE_INLINE_IMAGES] as $inlineImageFile ) {
				// Add a new File Usage for the inline image.
				file_usage_add($inlineImageFile, 'image', FILE_USAGE_TYPE_INLINE_IMAGES, $node->nid, 1);
			}
		}

		// If needed, generate the preview.
		$hash =  ($dossierProps['Preview']) ? md5(rand()) : '';
		if($dossierProps['Preview']) {
			$data = array('vid' => $node->vid, 'nid' => $node->nid, 'hash' => $hash, 'date' => time());
			drupal_write_record('ww_enterprise_preview', $data);
            ww_enterprise_utils_change_preview_alias( $node->title, $node->nid );
		}

		// Generate a URL for either the preview or the actual asset.
		$url = ww_enterprise_utils_getURL( $node->nid, $dossierProps['Preview'], $hash );
		$result = array( 'nid' => $node->nid, 'url' => $url );
	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage() , array(), WATCHDOG_ERROR );
		$result['errors'] = 'Node could not be published: ' . $e->getMessage();
	}
	return $result;
}

/**
 * Un-publish a node.
 *
 * @param int $nid Node Id to be unpublished.
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return array Node id that has been successfully unpublished; Error logs when un-publishing failed.
 */
function ww_enterprise_xmlrpc_removeNode( $nid, $enableTestMode=false )
{
	if (is_null($nid)) {
		throw new Exception('The Node ID could not be determined for removal.');
	}
	$result = array( $nid );
	try {
		ww_enterprise_utils_oauth_verify_request( $enableTestMode );

		// Always clean up any old Previews.
		ww_enterprise_utils_deleteOldPreviews();

		// Delete file attachments (File, Image, in-line images in Articles).
		// Testcase handling deviates slightly due to not using the standard file uploads, hence only execute when not
		// running the test cases.
		if (!$enableTestMode) {
			ww_enterprise_utils_deleteAttachmentsForNode( $nid );
		}

		// Delete the Node.
		node_delete( $nid );

	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage(), array(), WATCHDOG_ERROR );
		$result['errors'] = 'Node could not be unpublished: ' . $e->getMessage();
	}
	return $result;
}

/**
 * Get the configured field definitions for all configured content types.
 *
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return array The field definitions per content type, plus property information for the ContentType.
 */
function ww_enterprise_xmlrpc_getFields( $enableTestMode=false )
{
	$info = array();
	try {
		ww_enterprise_utils_oauth_verify_request( $enableTestMode );
		ww_enterprise_checkFilters();

		$sql = "SELECT type FROM {node_type} ORDER BY name ASC";
		$sth = ww_enterprise_utils_dbQuery( $sql );
		while( ( $dbRow = ww_enterprise_utils_dbFetchArray( $sth ) ) ) {
			if (node_access('create', $dbRow['type'])) { // Only include those that the User has access to.
				$contentType = $dbRow['type'];
				$weights = array();
				$reordered = array();
				$instances = field_info_instances( 'node', $contentType );

				// Retrieve and set the field_info_fields information as part of the the response, since it contains
				// necessary information that is not retrievable through the field_info_instance. For example for the
				// cardinality.
				foreach ($instances as $key => $instance) {
					$field_info_fields = field_info_field($instance['field_name']);
					$instances[$key]['field_info_fields'] = $field_info_fields;

					// If the type is a term reference for a radio button / checkboxes or select field then we will want to
					// have the allowed values known for this type as well, merge them in.
					if (($instance['widget']['type'] == 'options_buttons' || $instance['widget']['type'] == 'options_select'
						||  $instance['widget']['type'] == 'taxonomy_autocomplete' || $instance['widget']['type'] == 'active_tags_taxonomy_autocomplete')
						&& $field_info_fields['type']  == 'taxonomy_term_reference') {

						// Determine the vocabulary, and retrieve/set the terms on the field_instance.
						$vocabularyName = $field_info_fields['settings']['allowed_values'][0]['vocabulary'];

						// Determine, get and set the ww_term_entity on the instance so enterprise can use it to
						// determine suggestion providers.
						$vid = ww_enterprise_getVocabularyIdByName( $vocabularyName );
						$vocabulary = taxonomy_vocabulary_load( $vid );
						if ( $vocabulary ) {
							$instances[$key]['vocabulary_name'] = $vocabulary->name; // For Autocomplete Term Entity.
							$instances[$key]['ww_term_entity'] = $vocabulary->ww_term_entity; // For Suggestion Term Entity.
						}


						$defaultValue = null;
						if (isset($instance['default_value']) && isset($instance['default_value'][0])
							&& isset($instance['default_value'][0]['tid'])) {
							$defaultValue = intval($instance['default_value'][0]['tid']);
						}

						//Only send the names to limit the size of the xml rpc response
						$termNames = array();

						// Retrieve terms for the vocabulary.
						if ($instance['widget']['type'] != 'taxonomy_autocomplete' && $instance['widget']['type'] != 'active_tags_taxonomy_autocomplete') {
							$terms = ww_enterprise_utils_getTermsByVocabularyName( $vocabularyName );
							foreach ($terms as $term) {
								// Overload the default value to the name only if our tid matches the term tid.
								if (intval($term->tid) == $defaultValue) {
									$instances[$key]['default_value'][0]['tid'] = $term->name;
								}

								array_push($termNames, $term->name);
							}
						}

						$instances[$key]['field_info_fields']['settings']['allowed_values'][0]['vocabulary_terms'] = $termNames;
					}
					// Weigh every returned field, and store them by weight, to be reordered later on.
					$weight = $instance['widget']['weight'];
					if (!isset($weights[$weight])) {
						$weights[$weight] = array();
					}
					$weights[$weight][] = $key;
				}

				if ($weights) {
					// Sort the weighted keys, is not done by default, can cause wrong ordering of the items.
					ksort($weights);

					// Place the weighed items in an array for reordering.
					foreach ($weights as $weight) {
						if ( count( $weight) > 1 ) {
							arsort($weight);
							foreach ($weight as $w ) {
								$reordered[] = $w;
							}
						} elseif (isset( $weight[0])) {
							$reordered[] = $weight[0];
						}
					}
				}

				// Get the full ordered list.
				$reorderedInstances = array();
				if ($reordered) foreach ($reordered as $key ) {
					$reorderedInstances[$key] = $instances[$key];
				}

				// We need to map the values to the orig_type of the ContentType. Therefore resolve the contenttype.
				$originalContentType = ww_enterprise_utils_getOriginalTypeFromType( $contentType );
				if ( is_null( $originalContentType ) ) {
					throw new Exception ('Could not find Field Configuration for ContentType: ' . $contentType);
				}
				$info[ $originalContentType ]['fields'] = $reorderedInstances;
				$info[ $originalContentType ]['publish_properties'] = ww_enterprise_utils_getContentTypeProperties($contentType);
			}
		}

		// Add the Drupal database instance id to the response.
		$drupalDatabaseInstanceIdObject = ww_enterprise_utils_get_configuration_setting( 'DrupalDatabaseInstanceId' );
		$dbInstanceId = ( is_null( $drupalDatabaseInstanceIdObject )) ? '' : $drupalDatabaseInstanceIdObject->value;
		$info['DrupalDatabaseInstanceId'] = $dbInstanceId;

	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage(), array(), WATCHDOG_ERROR );
		$info['Errors'][] = $e->getMessage();
	}
	return $info;
}

/**
 * Get the node info for the given node id. Returns views, rating, raters, comments count,
 * comments and the URL if the appropriate modules are found.
 *
 * @param int $nid The node id.
 * @return array List of node's info.
 */
function ww_enterprise_xmlrpc_nodeGetInfo( $nid )
{
	// TODO: fix for v7: Modules to be visited: statistics, votingapi ('comment' is done)

	$result = array(
		'Views' => 0 ,
		'Rating' => null,
		'Raters' => 0,
		'CommentsCount' => 0,
		'Comments' => array(),
		'URL' => ''
	);
	try {
		// Get statistics
		if( module_exists( 'statistics' ) ) {
			$statistics = statistics_get( $nid );
			if ($statistics) {
				$result['Views'] = $statistics['totalcount'];
				// extra property, not fully supported in Content Station
				$result['DayViews'] = intval( $statistics['daycount'] );
			}
		} else {
			$result['Views'] = "N/A";
		}

		// Get comments
		if( module_exists('comment') ) {
			// Get the comments total count
			$sql = 'SELECT COUNT(cid) AS count FROM {comment} WHERE nid = :nid';
			$commentsHandler = ww_enterprise_utils_dbQuery( $sql, array( ':nid' => $nid ) );
			$commentsRow = ww_enterprise_utils_dbFetchArray( $commentsHandler );
			$result['CommentsCount'] = $commentsRow['count'];

			// Get the comments content
			$sql = 'SELECT cid FROM {comment} WHERE nid = :nid ORDER BY created';
			$commentsHandler = ww_enterprise_utils_dbQuery( $sql, array( ':nid' => $nid ) );
			while( ( $commentsRow = ww_enterprise_utils_dbFetchArray( $commentsHandler ) ) ){
				$commentsDbId = $commentsRow['cid']; // Retrieve the id from comments table in Drupal DB.
				$sql = 'SELECT comment_body_value FROM {field_data_comment_body} WHERE '.
					'entity_id = :entity_id';
				$commentBodyHandler = ww_enterprise_utils_dbQuery( $sql, array( ':entity_id' => $commentsDbId ) );
				$commentBodyRow = ww_enterprise_utils_dbFetchArray( $commentBodyHandler );
				$result['Comments'][] = $commentBodyRow['comment_body_value'];
			}
		} else {
			$result['CommentsCount'] = "N/A";
			$result['Comments'] = "N/A";
		}

		// Get rating
		if( module_exists('votingapi') ) {
			$votes = votingapi_select_results(
				array(
				     'content_type' => 'node',
				     'content_id' => $nid,
				     'value_type' => 'percent',
				     'tag' => 'vote'
				)
			);
			foreach( $votes as $vote ) {
				if( $vote['function'] == 'average' ) {
					// scale from 0 to 5 in Enterprise
					$result['Rating'] = ($vote['value'] / 100) * 5;
				} else
					if( $vote['function'] == 'count' ) {
						$result['Raters'] = $vote['value'];
					}
			}
		} else {
			$result['Rating'] = "N/A";
			$result['Raters'] = "N/A";
		}

		// get URL
		$result['URL'] = ww_enterprise_utils_getURL( $nid, false );
	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage(), array(), WATCHDOG_ERROR );
		$result['Errors'][] = $e->getMessage();
	}
	return $result;
}

/**
 * Test the Drupal configuration.
 * This function tests only the logon parameters now. Also the version of the ww_enterprise module is send back.
 *
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return array
 */
function ww_enterprise_xmlrpc_testConfig( $enableTestMode=false )
{
	// Automatically reload the ww_enterprise module for Drupal to ensure we always have the most recent
	// information cached in case the module changed. Calling the disable/enable functions is equal to
	// un-checking and saving the module in the Drupal 7 admin page (admin/modules), followed by checking
	// the module (thus enabling it again) and saving it.
	module_disable( array( 'ww_enterprise' ));
	module_enable( array( 'ww_enterprise' ), false );

	try {
		ww_enterprise_utils_oauth_verify_request( $enableTestMode );
		ww_enterprise_checkFilters();

		$sql = "SELECT info infodb FROM {system} WHERE name = 'ww_enterprise' AND type = 'module'";
		$sth = ww_enterprise_utils_dbQuery( $sql );
		$dbObj = ww_enterprise_utils_dbFetchObject( $sth );
		if( $dbObj ) {
			$moduleinfo = unserialize( $dbObj->infodb );
			if(isset($moduleinfo['version'])) {
				$result = array( 'Version' => $moduleinfo['version'] );
			} else {
				throw new Exception( t('Could not find the "ww_enterprise" module.') );
			}
		}
	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage(), array(), WATCHDOG_ERROR );
		$result = array();
		$result['Errors'][] = $e->getMessage();
	}
	return $result;
}

/**
 * Looks for the file attachment from the Drupal Database
 * given the details of the file attachment in $fileInfo.
 * When the file attachment is found, meaning the attachment
 * has been uploaded before during node publishing, hence the
 * file Id (Drupal internal Database Id) is returned.
 *
 * @param array $fileInfo List of info about the file: filename, contentType(jpg,gif and etc), version, nodeId
 * @return array Key-value where key is 'fid' and value is the fileId in the Drupal database when the file attachment is found.
 */
function ww_enterprise_xmlrpc_getFileId( $fileInfo )
{
	$info = array();
	try {
		$filename = $fileInfo['filename'];
		$contentType = $fileInfo['contentType']; // fileMimeType such as 'image/jpeg','image/jpg' and etc.
		$nodeId = $fileInfo['nodeId'];

		// When nodeId is 0, means the node has never been published before,
		// hence don't have to check if the file attachment( currently only image) exists in the Drupal Database.
		if( $nodeId != 0 ) {
			static $imageTypes = array('image/jpeg' => true, 'image/jpg' => true, 'image/gif' => true, 'image/png' => true);

			if(isset($imageTypes[$contentType])) {
				$sql = 'SELECT fid FROM {file_managed} WHERE filename = :filename AND filemime = :filemime';
				$fileManagedHandler = ww_enterprise_utils_dbQuery( $sql,
					array( ':filename' => $filename, ':filemime' => $contentType ) );
				while( ( $fileManagedRow = ww_enterprise_utils_dbFetchArray( $fileManagedHandler ) ) ) {
					$info[] = $fileManagedRow;
				}
			}
		}
	} catch( Exception $e ) {
		watchdog( 'content', $e->getMessage(), array(), WATCHDOG_ERROR );
		$info['Errors'][] = $e->getMessage();
	}

	return $info;
}

/**
 * Retrieve the Content Types from the Drupal database.
 *
 * Retrieves the Content Types from the Drupal database and returns the values as an array.
 * Any errors during the querying of the Drupal database are logged in the result array as well.
 *
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return string[] Returns an array of Content Types.
 * @see ww_enterprise_utils.inc
 */
function ww_enterprise_xmlrpc_getContentTypes( $enableTestMode=false )
{
	// Attempt to log in to Drupal.
	$info = array();
	try {
		ww_enterprise_utils_oauth_verify_request( $enableTestMode );
		ww_enterprise_checkFilters();

		$sql = "SELECT * FROM {node_type} ORDER BY name ASC";
		$sth = ww_enterprise_utils_dbQuery( $sql );
		while( ( $dbRow = ww_enterprise_utils_dbFetchArray( $sth ) ) ) {
			// Strip out any h tags from the description of the Content Type.
			$dbRow['description'] = strip_tags($dbRow['description']);

			// Only include the Content Types for which the user has create access.
			if (node_access('create', $dbRow['type'])) {
				$info[] = $dbRow;
			}
		}

		// Add the Drupal database instance id to the response.
		$drupalDatabaseInstanceIdObject = ww_enterprise_utils_get_configuration_setting( 'DrupalDatabaseInstanceId' );
		$dbInstanceId = ( is_null( $drupalDatabaseInstanceIdObject )) ? '' : $drupalDatabaseInstanceIdObject->value;
		$info['DrupalDatabaseInstanceId'] = $dbInstanceId;

	} catch( Exception $e ) {
		// On an error log the error through the Drupal's standard error logging mechanism, And set the Error to be
		// returned as part of the response.
		watchdog( 'content', $e->getMessage(), array(), WATCHDOG_ERROR );
		$info['Errors'][] = $e->getMessage();
	}

	// Return the content types plus error messages (if any)
	return $info;
}

/**
 * Get the url of the given node id
 *
 * @param string $nodeId Published node Drupal id.
 * @return string URL of the published node.
 */
function ww_enterprise_xmlrpc_getUrl( $nodeId )
{
	return ww_enterprise_utils_getURL( intval($nodeId), false );
}

// Non-exposed functions.

/**
 * Converts the Enterprise comment setting back to the Drupal value.
 *
 * If the Enterprise $key is an unknown type, NULL is returned.
 *
 * @param string $key The key to search for.
 * @return null|int The value for the comments toggle as used by Drupal.
 */
function convertEnterpriseComments ( $key )
{
	$values = array(
		'Disable' => COMMENT_NODE_HIDDEN, // Hidden.
		'Read' => COMMENT_NODE_CLOSED, // Read only.
		'Read/Write' => COMMENT_NODE_OPEN, // Open.
	);

	if (array_key_exists($key, $values)) {
		return $values[$key];
	}
	return null;
}

/**
 * Restructures an Enterprise list of PublishForm values into a usable list for Drupal.
 *
 * - Maps the Drupal field id's back to field_name values.
 * - Resolves taxonomy values for the supplied labels.
 * - Resolves select / radio-button values for the supplied labels.
 *
 * Returns the restructured Form Values.
 *
 * @param array $formValues The raw form values from Enterprise.
 * @return array The restructured Form values.
 */
function mapPublishFormValues( $formValues )
{
	// Map from the original type to the current node type.
	$originalContentType = $formValues[DRUPAL7_CONTENT_TYPE][0];
	$contentType = ww_enterprise_utils_getContenTypeFromOriginal( $originalContentType );
	if ( is_null( $contentType ) ) {
		throw new Exception ('Could not map the PublishForm values for the original type: ' . $originalContentType);
	}

	$instances = field_info_instances( 'node', $contentType );

	// Resolve comments setting.
	$formValues[C_DIALOG_DRUPAL7_COMMENTS] = array(convertEnterpriseComments($formValues[C_DIALOG_DRUPAL7_COMMENTS][0]));
	// Replace indexes and values and return the values.
	foreach ($instances as $name => $data ) {
		$fid = $data['field_id'];
		$field_info_fields = field_info_field($name);

		if (array_key_exists($data['field_id'], $formValues)) {
			$formValues[$name] = $formValues[$fid];
			unset($formValues[$data['field_id']]);

			// If we are dealing with an article, we need to resolve the inline images.
			if (isset($data['settings']['text_processing']) &&
				($data['settings']['text_processing'] == '1')
			) {
				// Resolve the content, in case of an article inline images need to be resolved, $formValues are passed
				// by reference.
				$formValues = ww_enterprise_utils_updateArticleContents($formValues, $name);
			}

			// Determine how to handle image uploads.
			if ($field_info_fields['type'] == 'file' || $field_info_fields['type'] == 'image' ) {
				if ($formValues[$name]) foreach ($formValues[$name] as $rawIndex => $rawInputFile ) {
					$file = new stdClass();
					$file->fid = $rawInputFile['fid'];

					if ($field_info_fields['type'] == 'file') {
						// Resolve the Display flag. If there was user input, then the value will be present under the
						// key 'display' in the raw input file. If the field is not enabled as user input, then we
						// default to the value set on the contenttype.
						$file->display = ($field_info_fields['settings']['display_default'] == '1') ? 1 : 0;
						if (array_key_exists('display', $rawInputFile)) {
							$file->display = intval($rawInputFile['display']);
						}

						// Resolve the Description field of the File (if any)
						if (array_key_exists('description', $rawInputFile)) {
							$file->description = $rawInputFile['description'];
						}
					} elseif ($field_info_fields['type'] == 'image') {
						// Resolve the Alt text field if present.
						if (array_key_exists('alt', $rawInputFile)) {
							$file->alt = $rawInputFile['alt'];
						}

						// Resolve the Title field if present.
						if (array_key_exists('title', $rawInputFile)) {
							$file->title = $rawInputFile['title'];
						}
					}

					$formValues[$name][$rawIndex] = (array) $file;
				}
			}
		} else {
			if ($data['required'] == 1) {
				$valid = false;
				foreach($formValues[$name] as $value) {
					if (!empty($value)) {
						$valid = true;
					}
				}

				if (!$valid) {
					//TODO: proper error handling.
					$valid = $valid; // Keep analyzer happy.
				}
			} else {
				$formValues[$name] = array();
			}
		}

		// If the type is a term reference, we need to get the tid for the selected value.
		if ($field_info_fields['type'] == 'taxonomy_term_reference') {
			// Determine the vocabulary, and retrieve/set the terms on the field_instance.
			$vocabularyName = $field_info_fields['settings']['allowed_values'][0]['vocabulary'];
			$vocabularyId = ww_enterprise_getVocabularyIdByName( $vocabularyName );
			//Get the tid of the term names
			foreach ($formValues[$name] as $key => $value) {
				$tid = ww_enterprise_utils_getTidByVocabularyTerm($vocabularyName, $value);
				if ($tid != '') {
					$formValues[$name][$key] = $tid;
				} elseif ($value != '') {
					if ($data['widget']['type'] == 'taxonomy_autocomplete'
						|| $data['widget']['type'] == 'active_tags_taxonomy_autocomplete')
					{
						// If the value is not known for an autocomplete field, then we attempt to add it to the
						// Vocabulary.
						$result = taxonomy_term_save( (object) array( 'name' => $value, 'vid' => $vocabularyId,) );

						if ( SAVED_NEW == $result ) {
							// now retrieve the term and set the value in our list.
							$tid = ww_enterprise_utils_getTidByVocabularyTerm($vocabularyName, $value);
							if ($tid != '') {
								$formValues[$name][$key] = $tid;
							} else {
								unset($formValues[$name][$key]);
								// if there are no more values selected, unset the whole index.
								if (count($formValues[$name]) == 0) {
									unset($formValues[$name]);
								}
							}
						}
					}
				} else {
					// if the field was not selected an empty string will be set, so unset the value.
					unset($formValues[$name][$key]);
					// if there are no more values selected, unset the whole index.
					if (count($formValues[$name]) == 0) {
						unset($formValues[$name]);
					}
				}
			}
			// If there are no more form values for the taxonomy field, we need to check if the field was mandatory.
			// - If the field was not mandatory we need to unset the whole field.
			// - If the field was mandatory and there is a default value, use that instead. This is an exceptional case
			//   since ContentStation should not display an selectable empty value on a mandatory field.
			// In case the field is mandatory but not set, then the save action will fail, which will be handled by
			// the Drupal core.
			if (!isset($formValues[$name])) {
				if ($data['required'] == 1) {
					if (isset($instances[$name]['default_value'][0]['tid'])) {
						$formValues[$name][0] = $instances[$name]['default_value'][0]['tid']; // Set the default value.
					}
				} else {
					$formValues[$name] = array(); // By setting an empty array, we effectively unset the field.
				}
			}
		} elseif ($data['widget']['type'] == 'options_buttons' || $data['widget']['type'] == 'options_select') {
			// If we are dealing with any kind of select we  need to translate the given label back to its key value.
			$allowed_values = $field_info_fields['settings']['allowed_values'];

			if (is_array($allowed_values) && count($allowed_values) > 0) {
				$allowed_values = array_flip($allowed_values);
				foreach ($formValues[$name] as $key => $value) {
					if (array_key_exists($value, $allowed_values)) {
						$formValues[$name][$key] = $allowed_values[$value];
					} elseif ($data['required'] == 0) {
						$formValues[$name] = array();
					}
				}
			}
		}
	}
	return $formValues;
}