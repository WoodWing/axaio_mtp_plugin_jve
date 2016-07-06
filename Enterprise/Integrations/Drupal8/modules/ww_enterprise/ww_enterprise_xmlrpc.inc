<?php
/**
 * Implementation of xmlrpc().
 *
 * Defines all the XML-RPC methods and mapped it to the corresponding
 * callback functions for this module with regards to WoodWing Enterprise
 * Server. When a specific XML-RPC method is invoked by the client plugin,
 * the corresponding registered call back function is called.
 *
 * This hook relies on the optional XMLRPC module being installed.
 *
 * @return array An array of mapped XML-RPC methods with its callback hooks.
 */
function ww_enterprise_xmlrpc()
{
	$xmlrpc = array();

	$xmlrpc[] = array(
		'enterprise.getContentTypes',
		'ww_enterprise_xmlrpc_getContentTypes',
		array( 'struct' ),
		t('Retrieve node content type information for WoodWing Enterprise Server PublishFormTemplates.'),
	);

	$xmlrpc[] = array(
		'enterprise.getFields',
		'ww_enterprise_xmlrpc_getFields',
		array( 'struct' ),
		t('Retrieve node field information for WoodWing Enterprise Server Dialogs.'),
	);

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
		'enterprise.getUrl',
		'ww_enterprise_xmlrpc_getUrl',
		array( 'string', 'string' ),
		t('Get a preview URL.'),
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
		array( 'struct', 'string' ),
		t('Retrieves a specific vocabulary based on the vid.'),
	);

	return $xmlrpc;
}

/**
 * Retrieve Enterprise Server compatible Content Types.
 *
 * Retrieves Content Types that are compatible with Enterprise Server. For these Content Types the name, description and
 * the original (machine name) are returned. Enterprise will use this information construct PublishFormTemplate objects.
 *
 * - Any reported errors are returned to Enterprise Server to be handled further.
 *
 * Additionally the available filters are checked. the full_html format filter needs to be present and needs to be
 * enabled to use the Enterprise Plugin in conjunction with Drupal.
 *
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return string[] Returns an array of Content Type information.
 * @see ww_enterprise_utils.inc
 */
function ww_enterprise_xmlrpc_getContentTypes( $enableTestMode=false )
{
	$contentTypeInformation = array();
	try {
		// Validate the request.
		// Authenticate the request.
		$user = validateRequest( \Drupal::request() );
		if ( is_null( $user ) ) {
			\Drupal::logger( 'ww_enterprise' )->debug( 'Could not authenticate the user.' );
			throw new \Exception( 'Could not authenticate the user.' );
		}

		checkFilters(); // Check the default filter format.

		// Load all node types.
		$types = \Drupal\node\Entity\NodeType::loadMultiple();
		/** @var \Drupal\node\Entity\NodeType $type */
		if ( $types ) foreach ( $types as $type ) {
			$message = '';
			// Skip ContentTypes the user has no access to.
			if ( accessCheck( $user, 'getContentTypes', $message, $type->id(), $type->label() ) ) {
				// Transform the content type into something enterprise can use:
				$contentTypeInformation[$type->getOriginalId()] = array(
					'type' => $type->id(),
					'name' => $type->label(),
					'description' => strip_tags( $type->getDescription()), // Strip out h tags.
					'original' => $type->uuid()
				);
			}
		}
		$contentTypeInformation['DrupalDatabaseInstanceId'] = getUniqueDrupalInstanceId();
	} catch ( \Exception $e ) {
		$contentTypeInformation['Errors'][] = $e->getMessage();
		\Drupal::logger( 'ww_enterprise' )->error( 'getContentTypes: ' . $e->getMessage() );
	}
	return $contentTypeInformation;
}

/**
 * Retrieve WoodWing Enterprise Server compatible Field definitions.
 *
 * Retrieves field definitions and view information for WoodWing Enterprise Server to translate into
 * Dialogs.
 *
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return array The field definitions per content type, plus property information for the ContentType.
 */
function ww_enterprise_xmlrpc_getFields( $enableTestMode=false )
{
	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger('ww_enterprise')->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.' );
	}

	$nodeFields = array();
	try {
		checkFilters();

		// Retrieve the Node Types.
		$types = \Drupal\node\Entity\NodeType::loadMultiple();
		/** @var \Drupal\node\Entity\NodeType $type */
		if ( $types ) foreach ( $types as $type ) {
			$message = '';
			if ( accessCheck( $user, 'getFields', $message, $type->id(), $type->label() ) ) {
				$nodeFields[$type->uuid()] = getEnterpriseNodeFields( $type->id() );
			}
		}
		$nodeFields['DrupalDatabaseInstanceId'] = getUniqueDrupalInstanceId();
	} catch( \Exception $e ) {
		$nodeFields['errors'][] = $e->getMessage();
		\Drupal::logger( 'ww_enterprise' )->error( 'getFields: ' . $e->getMessage() );
	}
	return $nodeFields;
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

	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger( 'ww_enterprise' )->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.' );
	}

	try {
		$message = '';
		if ( !accessCheck( $user, 'getVocabularyNames', $message ) ) {
			throw new \Exception( $message );
		}

		// Retrieve the vocabulary names and construct an array.
		$vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
		if ( $vocabularies ) foreach ( $vocabularies as $vocabulary ) {
			/** @var Drupal\taxonomy\Entity\Vocabulary $vocabulary */
			$result[$vocabulary->uuid()] = array(
				'uuid' => $vocabulary->uuid(),
				'vid' => $vocabulary->id(),
				'name' => $vocabulary->label(),
				'ww_term_entity' => getWoodWingSuggestionEntity($vocabulary->uuid())
			);
		}

		// Add the Drupal database instance id to the response.
		$result['DrupalDatabaseInstanceId'] = \Drupal::config( 'system.site' )->get( 'uuid' );

	} catch( \Exception $e ) {
		\Drupal::logger('ww_enterprise')->error('getVocabularyNames: ' . $e->getMessage() );
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
 * @param string $vocabUuid The Id of the vocabulary for which to retrieve the taxonomy terms.
 * @param bool $testmode Whether or not this function should be tested.
 * @return array An array of vocabulary items and content.
 */
function ww_enterprise_xmlrpc_getVocabulary( $vocabUuid, $testmode = false )
{
	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger('ww_enterprise')->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.');
	}

	$result = array();
	try {
		// Check if the vocabulary may be retrieved.
		$message = '';
		if ( !accessCheck( $user, 'getVocabulary', $message ) ) {
			throw new \Exception( $message );
		}

		$originalUuid = $vocabUuid; // original uuid for the vocabulary.
		$originalVid = null;

		$vocabularies = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
		if ( $vocabularies ) foreach ( $vocabularies as $vocabulary ) {
			/** @var Drupal\taxonomy\Entity\Vocabulary $vocabulary */
			if ($vocabulary->uuid() == $originalUuid) {
				$originalVid = $vocabulary->id();
				break; // found.
			}
		}

		$tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree( $originalVid, 0, NULL, TRUE);
		if ( $tree ) foreach ($tree as $term ) {
			/** @var Drupal\taxonomy\Entity\Term $term */
			/** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $name */
			$name = $term->name[0];
			$name = $name->getValue();
			$name = $name['value'];
			$result[$term->uuid()] = $name;
		}
		$result['DrupalDatabaseInstanceId'] = \Drupal::config('system.site')->get('uuid');
	} catch( \Exception $e ) {
		\Drupal::logger('ww_enterprise')->error( 'getVocabulary: ' . $e->getMessage() );
		$result['errors'] = 'Vocabulary could not be retrieved: ' . $e->getMessage();
	}

	return $result;
}

/**
 * Publish a node.
 *
 * @param array $dossierProps
 * @param array $formValues
 * @param array $attachments
 * @param bool $testmode
 * @throws Exception
 * @return array
 */
function ww_enterprise_xmlrpc_saveNode( $dossierProps, $formValues, $attachments, $testmode=false )
{
	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger('ww_enterprise')->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.' );
	}
	$uid = $user->id();

	// Keep analyzer happy
	$attachments = $attachments;
	$result = array();

	try {
		deleteExpiredPreviews();
		checkFilters();

		if ( !in_array($dossierProps['Action'], array( 'Publish', 'Preview', 'Update' ) ) ) {
			throw new \Exception( 'Unknown Action: ' . $dossierProps['Action'] );
		}

		// Find the node type based on the UUID provided from Enterprise. (We cannot rely on the original id as this
		// can be changed by the user.
		$originalUuid = $formValues['DRUPAL8_CONTENT_TYPE'][0];
		$originalType = null;
		$id = null;
		$label = null;
		$types = \Drupal\node\Entity\NodeType::loadMultiple();
		/** @var \Drupal\node\Entity\NodeType $type */
		foreach ( $types as $type ) {
			if ( $type->uuid() == $originalUuid ) {
				$originalType = $type->id();
				$id = $type->id();
				$label = $type->label();
				break; // Found.
			}
		}
		if ( is_null( $originalType ) ) {
			throw new \Exception ( 'Attempting to save a node with an unknown Node Type UUID: ' . $originalUuid );
		}

		$nodeId = null;
		$node = null;

		if ( $dossierProps['Action'] == 'Publish' || $dossierProps['Action'] == 'Preview' ) {
			// Check access to create a node.
			$message = '';
			if ( !accessCheck( $user, 'saveNodeCreate', $message, $id, $label ) ) {
				throw new \Exception( $message );
			}

			// For publishing we try to find the username that is publishing from Enterprise. This will try to get the
			// user from the database, if not found the user defined in the plugin's config.php file will be used.
			if ( isset($dossierProps['Username']) && $dossierProps['Username'] ) {
				$user = user_load_by_name( trim($dossierProps['Username']) );
				if ( $user instanceof \Drupal\user\Entity\User ) {
					$uid = $user->id();
				}
			}

			$node = Drupal\node\Entity\Node::create( array( 'type' => $originalType ) );
		} elseif ( $dossierProps['Action'] == 'Update' ) {
			// Verify that we have a Node ID for the node to be updated.
			if ( is_null($dossierProps['ExternalId'] ) ) {
				throw new \Exception( 'An update action requires a Node ID to be known.' );
			}

			$message = '';
			if ( !accessCheck( $user, 'saveNodeUpdate', $message, $id, $label ) ) {
				throw new \Exception( $message );
			}

			$node = Drupal\node\Entity\Node::loadMultiple( array( $dossierProps['ExternalId'] ) );
			if ( !$node ) {
				throw new \Exception( 'Node could not be loaded for Node id: ' . $dossierProps['ExternalId'] );
			}
			$node = $node[$dossierProps['ExternalId']];
			// When updating we preserve the original author.
			$uid = $node->uid;
		}

		// Map all the other fields for this call.
		mapNodeValues( $node, $formValues, $dossierProps['Preview'] );

		// Save the node.
		$node->uid = $uid;
		$node->save();
		$nodeId = $node->id();

		$hash =  ($dossierProps['Preview']) ? md5(rand()) : '';
		if( $dossierProps['Preview'] ) {
			$nodeVidEntry = $node->vid->getValue();
			$record = array('vid' => $nodeVidEntry[0]['value'], 'nid' => $nodeId, 'hash' => $hash, 'date' => time());
			db_insert( 'ww_enterprise_preview' )->fields( $record )->execute();
		}
		$url = getNodeUrl( $nodeId, $dossierProps['Preview'], $hash );
		$result = array( 'nid' => $nodeId, 'url' => $url );

	} catch( \Exception $e ) {
		$result['errors'] = 'Node could not be published: ' . $e->getMessage();
	}
	return $result;
}

/**
 * Unpublish a node.
 *
 * @param int $nid Node Id to be unpublished.
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return array Node id that has been successfully unpublished; Error logs when un-publishing failed.
 */
function ww_enterprise_xmlrpc_removeNode( $nid, $enableTestMode=false )
{
	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger( 'ww_enterprise' )->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.');
	}

	if (is_null($nid)) {
		throw new \Exception( 'The Node ID could not be determined for removal.' );
	}
	$result = array( $nid );
	try {

		// Always clean up any old Previews.
		deleteExpiredPreviews();

		// Delete the Node.
		$node = Drupal\node\Entity\Node::load( $nid );
		if ( $node ) {
			$message = '';
			$id = $node->getType();
			$nodeType = \Drupal\node\Entity\NodeType::load( $id );

			// Retrieve the node type... to get the label or the name.
			if ( !accessCheck( $user, 'removeNode', $message, $id, $nodeType->label() ) ) {
				throw new \Exception( $message );
			}
			$node->delete();
		}

	} catch( \Exception $e ) {
		\Drupal::logger( 'ww_enterprise' )->error( 'Node:' . $nid . ' could not be removed.' );
		$result['errors'] = 'Node could not be unpublished: ' . $e->getMessage();
	}

	return $result;
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
	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger( 'ww_enterprise' )->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.' );
	}

	// Check access to create a node.
	$message = '';
	if ( !accessCheck( $user, 'nodeGetInfo', $message ) ) {
		throw new \Exception( $message );
	}

	$result = array(
		'Views' => 0 ,
		'Rating' => null,
		'Raters' => 0,
		'CommentsCount' => 0,
		'Comments' => array(),
		'URL' => ''
	);
	$result['URL'] = ww_enterprise_xmlrpc_getUrl( $nid );
	return $result;
}

/**
 * Test the Drupal configuration.
 *
 * Function sends back the version of the ww_enterprise module.
 *
 * @param bool $enableTestMode Determines whether this function should be run in the test mode or not. Default: false
 * @return array
 */
function ww_enterprise_xmlrpc_testConfig( $enableTestMode=false )
{
	$result = null;

	try {
		checkFilters();

		// Authenticate the request
		$user = validateRequest( \Drupal::request() );
		if ( is_null( $user ) ) {
			\Drupal::logger( 'ww_enterprise' )->debug( 'Could not authenticate the user.' );
			throw new \Exception( 'Could not authenticate the user.' );
		}

		// Though technically no longer required since the plugin moved from a core plugin to a release plugin, it is
		// handy to have the version number of the plugin in place, even though this information will not be used in
		// Enterprise Server to validate plugin versions against the server version, it is still can be valuable infor-
		// mation when debugging the plugin.
		$module = \Drupal::moduleHandler()->getModule( 'ww_enterprise' );
		/** @var \Drupal\Core\Extension\Extension $module */
		$infoPathName = $module->getPathname(); // Retrieves the path where the info file(ww_enterprise.info.yml file) resides.
		$wwEnterpriseInfo = \Symfony\Component\Yaml\Yaml::parse( $infoPathName ); // Retrieves the content from info.yml file.
		$result = array( 'Version' => $wwEnterpriseInfo['version'] );

		// Check the users permissions to see if it is fully qualified to use the publishing plugin.
		$permissions = checkUserPermissions( $user );
		if ( $permissions['errors']) foreach ( $permissions['errors'] as $errorMessage ) {
			if ( ! array_key_exists( 'Access', $result ) ) {
				$result['Access'] = array();
			}
			$result['Access'][] = $errorMessage;
		}
	} catch( \Exception $e ) {
		\Drupal::logger('ww_enterprise')->error( 'Content:' . $e->getMessage() );
		$result = array();
		$result['Errors'][] = $e->getMessage();
	}
	return $result;
}

/**
 * Get the url of the given node id
 *
 * @param string $nodeId Published node Drupal id.
 * @return string URL of the published node.
 */
function ww_enterprise_xmlrpc_getUrl( $nodeId )
{
	// Authenticate the request.
	$user = validateRequest( \Drupal::request() );
	if ( is_null( $user ) ) {
		\Drupal::logger( 'ww_enterprise' )->debug( 'Could not authenticate the user.' );
		throw new \Exception( 'Could not authenticate the user.' );
	}

	return getNodeURL( intval($nodeId), false );
}
