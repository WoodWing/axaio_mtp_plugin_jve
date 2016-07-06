<?php
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\user\UserInterface;

/**
 * Validates a request using the Drupal authentication manager.
 *
 * This module uses basic authentication, however all authentication providers are polled when calling the authenticate()
 * method, allowing other modules / providers to hook into the request.
 *
 * @param \Symfony\Component\HttpFoundation\Request $request The request as placed to Drupal.
 * @return null|\Drupal\user\Entity\User The validated user from the request, or null on failure.
 */
function validateRequest( \Symfony\Component\HttpFoundation\Request $request )
{

	// Check if the current user is already authenticated, in that case, return
	// that. This relies on a patched xmlrpc.routing.yml that enables basic_auth
	// for the xmlrpc.php route.
	$user = \Drupal::currentUser();
	if ($user instanceof AccountProxyInterface) {
		$user = $user->getAccount();
	}
	if ( $user->isAuthenticated() && $user instanceof UserInterface ) {
		return $user;
	}

	// Authenticate anonymous users and those that have not yet been authenticated.
	// @todo This module should not have to do this. It is not even clear how it
	//   can work, without patching rest.module.
	/** @var \Drupal\user\Entity\User $user */
	$user = \Drupal::service( 'authentication' )->authenticate( $request );
	$authenticated = ( $user instanceof \Drupal\user\Entity\User && $user->isAuthenticated() && !$user->isAnonymous() );

	if ( $authenticated ) {
		\Drupal::logger( 'ww_enterprise' )->debug( 'Authenticated user: ' . $user->getUsername() . ' (' . $user->id() . ')' );
	} else {
		\Drupal::logger( 'ww_enterprise' )->debug( 'Failed to authenticate the user using basic authentication.' );
		$user = null;
	}
	return $user;
}

/**
 * Checks if the user has permission to perform an action in the module.
 *
 * @param \Drupal\user\Entity\User $user The user to check the permissions against.
 * @param string $action The action that the user is trying to perform.
 * @param string $message Placeholder to contain the access check result.
 * @param null|string $nodeType The nodeType that is being used (if applicable).
 * @param null|string $nodeTypeLabel The label to display on permission errors.
 *
 * @return bool Whether or not the user is allowed to perform the action.
 * @throws Exception if the action does not match any known actions an error is thrown.
 */
function accessCheck( \Drupal\user\Entity\User $user, $action, &$message, $nodeType=null, $nodeTypelabel=null )
{
	$errorMessage = 'The post could not be published because the user does not have the following permissions set:';

	switch ( $action ) {
		case 'saveNodeCreate' : // Initially creating a node.
		case 'uploadFile' : // Uploading a file to the ww_enterprise module.
		case 'getContentTypes' : // Retrieving content type information.
		case 'getFields' : // Retrieving field information.
		case 'saveNodeUpdate' : // Updating a node.
		case 'removeNode' : // Removing a node.
			$permitted = true;
			// Bypass node access always passes the validation on these items.
			if ( $user->hasPermission( 'bypass node access') ) {
				return true;
			}

			// If the user cannot bypass, he needs to have the access content right (basic)
			if ( !$user->hasPermission( 'access content' ) ) {
				$errorMessage .= ' \'View published content\'';
				$permitted = false;
			}

			if ( $action == 'saveNodeUpdate' ) {
				// ...and the edit permission for content.
				if ( !$user->hasPermission( 'edit any ' . $nodeType . ' content' ) ) {
					$errorMessage .= ' \'' . $nodeTypelabel . ': Edit any content\'';
					$permitted = false;
				}
			} elseif ( $action == 'removeNode' ) {
				// ...and the delete permission for content.
				if ( !$user->hasPermission( 'delete any ' . $nodeType . ' content' ) ) {
					$errorMessage .= ' \'' . $nodeTypelabel . ': Delete any content\'';
					$permitted = false;
				}
			} else {
				// ...and the create permission for new content.
				if ( !$user->hasPermission( 'create ' . $nodeType . ' content' ) ) {
					$errorMessage .= ' \'' . $nodeTypelabel . ': Create new content\'';
					$permitted = false;
				}
			}

			if ( !$permitted ) {
				$errorMessage .= ' Alternatively give the user the \'Bypass content access control\' permission.';
			} else {
				return true;
			}
			break;
		case 'nodeGetInfo' : // Retrieve information about the published. node.
		case 'getUrl' : // Retrieve an URL for a published node.
			// Bypass node access always passes the validation on these items.
			if ( $user->hasPermission( 'bypass node access' ) ) {
				return true;
			}

			if ( !$user->hasPermission( 'access content' ) ) {
				$errorMessage .= ' `View published content`';
			} else {
				return true;
			}

			break;
		case 'getVocabulary' : // Retrieve term information for a vocabulary.
		case 'getVocabularyNames' : // Retrieve information from a vocabulary.
			// Bypass node access always passes the validation on these items.
			if ( !$user->hasPermission( 'administer taxonomy') ) {
				$errorMessage .= ' \'Administer vocabularies and terms\'';
			} else {
				return true;
			}
			break;
		default :
			throw new \Exception ( 'Action:' . $action . ' does not have a permission check.' );
			break;
	}
	$message = $errorMessage;
	return false;
}

/**
 * Used to verify that the user has all the right permissions to perform actions with the module.
 *
 * This function is used in the Enterprise Server health check to verify that the user has all necessary permissions
 * to operate Drupal 8 publishing from Enterprise.
 *
 * @param \Drupal\user\Entity\User $user The user to check the User permissions for.
 *
 * @return array Array containing info, warnings and errors about the user permissions.
 */
function checkUserPermissions( \Drupal\user\Entity\User $user )
{
	$permissions = array( 'errors' => array() );

	if ( !$user->hasPermission( 'administer taxonomy' ) ) {
		$permissions['errors'][] = 'The \'Administer vocabularies and terms\' permission is not set for the user.';
	}

	// Check if the user is allowed to view content.
	if ( !$user->hasPermission( 'access content' ) ) {
		$permissions['errors'][] = 'The \'View published content\' permission is not set for the user.';
	}

	if ( !$user->hasPermission( 'bypass node access' ) ) {
		// Get all node types.
		$types = \Drupal\node\Entity\NodeType::loadMultiple();
		/** @var \Drupal\node\Entity\NodeType $type */
		if ( $types ) foreach ( $types as $type ) {
			$id = $type->id();
			$label = $type->label();

			// If users have all or none of the permissions for a content type then the user is presumed to be
			// configured correctly
			if ( ( $user->hasPermission( 'create ' . $id . ' content' ) &&
					$user->hasPermission( 'edit any ' . $id . ' content' ) &&
					$user->hasPermission( 'delete any ' . $id . ' content' ) )
				||
				( !$user->hasPermission( 'create ' . $id . ' content' ) &&
					!$user->hasPermission( 'edit any ' . $id . ' content' ) &&
					!$user->hasPermission( 'delete any ' . $id . ' content' ) )
			) {
				\Drupal::logger( 'ww_enterprise' )->debug( 'User permissions checked for ContentType: ' . $id );
			} else {
				// Check permissions to create a node of the nodetype.
				if ( !$user->hasPermission( 'create ' . $id . ' content' ) ){
					$permissions['errors'][] = 'The \'' . $label . ': Create new content\' permission is not set for the user.';
				}

				// Check permissions to update a node.
				if ( !$user->hasPermission( 'edit any ' . $id . ' content' ) ){
					$permissions['errors'][] = 'The \'' . $label . ': Edit any content\' permission is not set for the user.';
				}

				// Check permissions to delete a node.
				if ( !$user->hasPermission( 'delete any ' . $id . ' content' ) ){
					$permissions['errors'][] = 'The \'' . $label . ': Delete any content\' permission is not set for the user.';
				}
			}
		}
	}
	return $permissions;
}

/**
 * Creates and returns a new error (array) that can be added to the report for this DrupalField by caller.
 *
 * An error message consists of:
 *      array(
 *              'content_type' => $contentType,
 *              'field_name' => $fieldName,
 *              'message' => $errorMessage,
 *              'severity' => $severity
 *           )
 *
 * @param string $fieldName The name of the field (DrupalField) for which to track an error.
 * @param string $errorMessage Error or warning message.
 * @param string $contentType The node type for which the error is generated.
 * @param string $severity 'warn' or 'error', the severity of the error.
 * @return array An array containing the error content.
 */
function createError( $fieldName, $errorMessage, $contentType, $severity = 'warn' )
{
	return array(
		'content_type' => $contentType,
		'field_name' => $fieldName,
		'message' => $errorMessage,
		'severity' => $severity,
	);
}

/**
 * Retrieve the Drupal Database Instance ID from the database.
 *
 * Retrieves system.site.uuid from the configuration table in Drupal and returns it if a value
 * is set for it, or null otherwise.
 *
 * @return null|string The Drupal database instance Id.
 */
function getUniqueDrupalInstanceId()
{
	// Return the unique ID for this Drupal Installation.
	$drupalDatabaseInstanceId = \Drupal::config( 'system.site' )->get( 'uuid' );
	return ( is_null( $drupalDatabaseInstanceId ) ) ? '' : $drupalDatabaseInstanceId;
}

/**
 * Retrieves a numeric representation of a field id for use in Enterprise Server.
 *
 * Used in Enterprise to map the field values to widgets, this has to be a numeric field
 * in Enterprise otherwise the UUID would have been a good replacement.
 *
 * @param string $machine_name The unique machine name for a field.
 * @return string The numeric representation of a machine name.
 */
function retrieveFieldId( $machine_name )
{
	$machine_name_id = '';
	$query = 'SELECT e.field_id FROM {ww_enterprise_field_map} e WHERE e.machine_name = :machine_name ORDER BY e.machine_name DESC';
	$params = array( ':machine_name' => $machine_name );
	$result = db_query_range( $query, 0, 1, $params, array() );
	$resultSet = $result->fetchAssoc(); // Need to use fetchAssoc because we are not sure if the record actually exists.
	if ( $resultSet ){
		$machine_name_id = $resultSet['field_id'];
	} else {
		// Create a record and retrieve the result to retrieve the field id.
		$fieldMap = array( 'machine_name' );
		$values = array( 'machine_name' => $machine_name );
		db_insert( 'ww_enterprise_field_map' )->fields( $fieldMap )->values( $values )->execute();
		$result = db_query_range( $query, 0, 1, $params, array() );
		if ( $result ){
			foreach ( $result as $record ) { // Should have only one record.
				$machine_name_id = $record->field_id;
			}
		}
	}
	return $machine_name_id;
}

/**
 * Retrieves the machine name for a node field.
 *
 * Used when publishing to retrieve the machine name of the node. Each field will have its own unique machine name.
 *
 * @param string $field_id The numeric representation of a machine name.
 * @return string The unique machine name for a field.
 */
function retrieveMachineName( $field_id )
{
	$machine_name = null;
	$query = 'SELECT e.machine_name FROM {ww_enterprise_field_map} e WHERE e.field_id = :field_id ORDER BY e.machine_name DESC';
	$params = array( ':field_id' => $field_id );
	$result = db_query_range($query, 0, 1, $params, array());
	$resultSet = $result->fetchAssoc();
	if ( $resultSet ){
		$machine_name = $resultSet['machine_name'];
	}
	return $machine_name;
}

/**
 * Retrieves the WoodWing Suggestion Entity based on the supplied vocabulary guid.
 *
 * @param $vocabularyGuid The guid of the vocabulary item.
 *
 * @return null|string
 */
function getWoodWingSuggestionEntity( $vocabularyGuid )
{
	$value = null;
	// retrieve a WoodWing Enterprise Suggestion Entity value to use in the form.
	$query = 'SELECT v.ww_term_entity '
		. 'FROM {ww_enterprise_vocabulary_map} v '
		. 'WHERE v.vocabulary_uuid = :vocabulary_uuid '
		. 'ORDER BY v.ww_term_entity DESC';
	$params = array( ':vocabulary_uuid' => $vocabularyGuid );
	$result = db_query_range( $query, 0, 1, $params, array() );
	$resultSet = $result->fetchAssoc();
	if ( $resultSet ){
		$value = $resultSet['ww_term_entity'];
	}
	return $value;
}

/**
 * Initializes a basic definition array for an Enterprise field representation.
 *
 * @param string $nodeType The node type for which to create a basic field representation.
 *
 * @return array The basic field representation.
 */
function initializeDataDefinition( $nodeType )
{
	return array(
		'name' => null, 'type' => null, 'widget_type' => null, // For example string_textfield 'vocabulary_name' => null,
		'ww_term_entity' => null, 'has_text_filter' => false, 'required' => false, 'default_value' => null,
		'display_name' => null, 'id' => null, 'cardinality' => 1, 'min_value' => null, 'max_value' => null,
		'max_length' => null, 'has_description_field' => false, 'min_resolution' => null, 'max_resolution' => null,
		'initial_height' => null, 'summary_initial_height' => null, 'has_alt_text' => false, 'has_title_field' => false, 'has_display_field' => false,
		'content_type' => $nodeType, 'property_info_type' => null, 'has_display_summary' => false, 'description' => null,
		'list_options' => array(), 'file_extensions' => '', 'display_default' => false, 'machine_name' => null
	);
}

/**
 * Retrieves Node field information for the specified Node Type.
 *
 * Retrieves basic information on whether or not to automatically publish the fields, if newly created nodes are to
 * be sticky, etc. This information is paired with any information about custom fields that were added to the node
 * type. The information is sorted by weight and returned.
 *
 * @param string $contentType The content type for which to retrieve field information.
 * @return array An array of basic and custom fields for the content type.
 * @throws Exception throws an exception if node field information cannot be retrieved.
 */
function getEnterpriseNodeFields( $contentType )
{
	$enterpriseFieldDefinition = array( 'basic_fields' => array(), 'custom_fields' => array(), 'errors' => array() );
	$fields = \Drupal::entityManager()->getFieldDefinitions( 'node', $contentType );
	/** @var Drupal\node\Entity\Node $initializedNode */
	$initializedNode = Drupal\node\Entity\Node::create( array('type' => $contentType ) );
	$form = entity_get_form_display( 'node', $contentType, 'default' );
	$formComponents = $form->getComponents();

	/**  @var Drupal\Core\Field\FieldDefinition $field */
	if ( $fields ) foreach ( $fields as $key => $field ) {
		// Basic Fields are of the FieldDefinition type.
		if ( $field instanceof Drupal\Core\Field\BaseFieldDefinition || $field instanceof Drupal\Core\Field\Entity\BaseFieldOverride ) {

			// Title field can be hidden, therefore if it does not have a formComponent, do not add it to Enterprise Server.
			if ( $key == 'title' && !array_key_exists($key, $formComponents )) {
				continue;
			}

			$nodeProperties = initializeDataDefinition( $contentType );

			switch ( $key ) {
				case 'title' : // Required by Enterprise, one of the first fields to be displayed.
					$nodeProperties['name'] = $key;
					$nodeProperties['id'] = $contentType . '_' . $key;
					$defaultValue = $initializedNode->title->getValue();
					$nodeProperties['default_value'] = isset( $defaultValue[0]['value'] ) ? $defaultValue[0]['value'] : '' ;
					$nodeProperties['required'] = true;
					$nodeProperties['display_name'] = 'Title';
					$nodeProperties['property_info_type'] = 'string';
					$nodeProperties['type'] = $field->getType();
					$nodeProperties['widget_type'] = $formComponents[$key]['type'];
					$nodeProperties['description'] = 'Title field for the node.';
					$nodeProperties['max_length'] = 255;
					break;
				case 'promote' : // Promote the field to the Drupal frontend.
					$nodeProperties['name'] = $key;
					$nodeProperties['id'] = $contentType . '_' . $key;
					$nodeProperties['default_value'] = ( $initializedNode->isPromoted() ) ? 'true' : 'false';
					$nodeProperties['required'] = false;
					$nodeProperties['display_name'] = 'Promote';
					$nodeProperties['property_info_type'] = 'bool';
					$nodeProperties['type'] = $field->getType();
					$nodeProperties['widget_type'] = 'promote';
					$nodeProperties['description'] = 'The promote flag publishing option.';
					break;
				case 'sticky' : // Whether or not to make the node sticky.
					$nodeProperties['name'] = $key;
					$nodeProperties['id'] = $contentType . '_' . $key;
					$nodeProperties['default_value'] = ( $initializedNode->isSticky() ) ? 'true' : 'false';
					$nodeProperties['required'] = false;
					$nodeProperties['display_name'] = 'Sticky';
					$nodeProperties['property_info_type'] = 'bool';
					$nodeProperties['type'] = $field->getType();
					$nodeProperties['widget_type'] = 'sticky';
					$nodeProperties['description'] = 'The sticky flag option.';
					break;
				case 'status' : // The field that determines whether to publish directly in Drupal.
					$nodeProperties['name'] = $key;
					$nodeProperties['id'] = $contentType . '_' . $key;
					$nodeProperties['list_options'] = array( 'public', 'private');
					$nodeProperties['default_value'] = ( $initializedNode->isPublished() )
						? $nodeProperties['list_options'][0]
						: $nodeProperties['list_options'][1];
					$nodeProperties['required'] = true;
					$nodeProperties['display_name'] = 'Visibility';
					$nodeProperties['property_info_type'] = 'list';
					$nodeProperties['type'] = $field->getType();
					$nodeProperties['widget_type'] = 'status';
					$nodeProperties['description'] = 'The status publishing flag.';
					break;
				default :
					continue 2;
					break;
			}
			$errors = validateFieldDefinition( $nodeProperties, 'basic' );

			if ( empty( $errors ) ) {
				$enterpriseFieldDefinition['basic_fields'][$key] = $nodeProperties;
			} else {
				$enterpriseFieldDefinition['errors'][$key] = $errors;
				foreach ( $errors as $error ) {
					$message = $error['content_type'] . ':' . $error['field_name'] . ' - ' . $error['message'];
					if ( $error['severity'] == 'error' ) {
						\Drupal::logger( 'ww_enterprise' )->error( $message );
					} else {
						\Drupal::logger( 'ww_enterprise' )->warning( $message );
					}
				}
			}
		}

		// Form fields.
		/** @var Drupal\field\Entity\FieldConfig $field */
		if ( $field instanceof Drupal\field\Entity\FieldConfig ) {
			$nodeProperties = initializeDataDefinition( $contentType );
			$nodeProperties['name'] = $field->getName();
			$nodeProperties['type'] = $field->getType();
			$nodeProperties['widget_type'] = ( isset( $formComponents[$key]['type'] ) )
				? $formComponents[$key]['type'] :
				'';

			// If the widget type is not known we will want to get the default widget type for the field to see
			// if that can be mapped to a known type. If that fails as well the field cannot be imported by default.
			$knownWidgets = array(
				'boolean_checkbox',
				'datetime_default',
				'comment_default',
				'taxonomy_autocomplete',
				'options_select',
				'options_buttons',
				'image_image',
				'file_generic',
				'string_textfield',
				'string_textarea',
				'text_textarea',
				'text_textarea_with_summary',
				'email_default',
				'number',
			);

			if ( !in_array( $nodeProperties['widget_type'], $knownWidgets ) ) {
				// Attempt to read out the default field type.
				$defaultTypeInfo = \Drupal::service('plugin.manager.field.field_type')->getDefinition($nodeProperties['type']);
				$nodeProperties['widget_type'] = $defaultTypeInfo['default_widget'];
			}

			$nodeProperties['has_text_filter'] = false;
			// Only fields of type text, text_long and text_with_summary have text_processing in place.
			$formattedTextFieldTypes = array(
				'text_long', // text (formatted, long)
				'text_with_summary', // text (formatted, long, summary)
			);
			if( in_array( $nodeProperties['type'], $formattedTextFieldTypes ) ) {
				$nodeProperties['has_text_filter'] = true;
			}
			$nodeProperties['required'] = $field->isRequired();
			$widget_field = $field->getName();
			$nodeProperties['default_value'] = $initializedNode->$widget_field->getValue();
			$nodeProperties['cardinality'] = $field->getFieldStorageDefinition()->getCardinality();

			if ( $nodeProperties['widget_type'] == 'boolean_checkbox' ) {
				$nodeProperties['default_value'] = ( $nodeProperties['default_value'][0]['value'] ) ? 'true' : 'false';
			}

			$dateTimeType = null;
			if ( $nodeProperties['widget_type'] == 'datetime_default' ){
				$dateTimeType = $field->getSetting( 'datetime_type' );

				$nodeProperties['default_value'] = isset($nodeProperties['default_value'][0]['value']) ? ( $nodeProperties['default_value'][0]['value'] ) : NULL;
			}

			// Handle comment field.
			if ( $nodeProperties['widget_type'] == 'comment_default') {
				/*
					Property mapping:
					===================
					0: Hidden
					1: Closed
					2: Open
				*/

				$nodeProperties['list_options'] = array( 'Hidden','Closed','Open');
				$defaultId = ( $nodeProperties['default_value'][0]['status'] );
				$nodeProperties['default_value'] = $nodeProperties['list_options'][$defaultId];
			}

			// @todo Temporary limit support for entity references to term references.
			if ($nodeProperties['type'] == 'entity_reference' && $field->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term') {

				$handlerSettings = $field->getSetting('handler_settings');

				if (!empty($handlerSettings['target_bundles'])) {
					$vid = reset($handlerSettings['target_bundles']);
					$vocabulary = \Drupal\taxonomy\Entity\Vocabulary::load($vid);

					// Avoid a fatal error if the vocabulary can not be loaded.
					if ( !$vocabulary ) {
						\Drupal::logger( 'ww_enterprise' )->error( 'Failed to load vocabulary @vid, used by field @field_name on node type @type.', ['@vid' => $vid, '@field_name' => $key, '@type' => $field->getTargetBundle()] );
						continue;
					}

					$nodeProperties['vocabulary_name'] = $vocabulary->label();
					$nodeProperties['type'] = 'taxonomy_term_reference';

					/** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
					$storedTermEntity = getWoodWingSuggestionEntity( $vocabulary->uuid() );
					$nodeProperties['ww_term_entity'] = (!empty($storedTermEntity)) ? $storedTermEntity : null;
				}
			}

			// This is only executed if we were able to detect the referenced
			// vocabulary above.
			if ( $nodeProperties['type'] == 'taxonomy_term_reference') {

				$defaultValues = [];
				foreach ($initializedNode->$key as $item) {
					if ($item->entity) {
						$defaultValues[] = $item->entity->label();
					}
				}
				$nodeProperties['default_value'] = implode(',', $defaultValues);

				if ( $nodeProperties['widget_type'] == 'options_select' || $nodeProperties['widget_type'] == 'options_buttons' ) {
					// Limit the settable options for the current user account.
					$listOptionsValues = $field
						->getFieldStorageDefinition()
						->getOptionsProvider('target_id', $initializedNode)
						->getSettableOptions(\Drupal::currentUser());
					$context = array(
						'fieldDefinition' => $field,
						'entity' => $initializedNode,
					);
					\Drupal::moduleHandler()->alter('options_list', $options, $context);

					// Remove leading -, assume those are depth marker.
					// @todo Find a way to improve this.
					$listOptionsValues = array_map(function ($value) {
						return ltrim($value, '-');
					}, $listOptionsValues);

					// Flatten and ensure a numeric list.
					$nodeProperties['list_options'] = array_values(OptGroup::flattenOptions($listOptionsValues));
				}

			}

			$nodeProperties['display_name'] = $field->getLabel(); // Field display name;
			$nodeProperties['id'] = retrieveFieldId( $key );;
			$nodeProperties['machine_name'] = $key;

			if ($nodeProperties['widget_type'] == 'image_image' || $nodeProperties['widget_type'] == 'file_generic') {
				$nodeProperties['max_length'] = $field->getSetting('max_filesize');
				// Convert to a value usable by Enterprise Server.
				if ( !empty( $nodeProperties['max_length']) && $nodeProperties['max_length'] != '0' ) {
					// MaxLength can be in bytes (no suffix), MB (suffix ' MB' or KB (suffix ' KB'), convert to bytes.
					$suffixes = array( '' => 1, 'k' => 1024, 'm' => 1048576, ); // KB=*1024 MB=*1024*1024, etc.
					$match = array();
					if ( preg_match( '/([0-9]+)\s*(k|m)?(b?(ytes?)?)/i', $nodeProperties['max_length'], $match ) ) {
						$nodeProperties['max_length'] = $match[1] * $suffixes[strtolower( $match[2] )];
					} else {
						// Business Rule: If the size in Byte cannot be determined, we set the limit to be unlimited.
						$nodeProperties['max_length'] = '';
					}
				}
			} elseif( ( $nodeProperties['widget_type'] == 'string_textfield' || // text (plain)
				$nodeProperties['widget_type'] == 'string_textarea'  || // text (plain, long)
				$nodeProperties['widget_type'] == 'text_textarea'    || // text (formatted, long)
				$nodeProperties['widget_type'] == 'text_textarea_with_summary' || // text (formatted, long, summary)
				$nodeProperties['widget_type'] == 'email_default' // Email field.
			)
			) {
				$nodeProperties['max_length'] = $field->getSetting( 'max_length' );
				$nodeProperties['default_value'] = (isset( $nodeProperties['default_value'][0]['value'] ))
					? $nodeProperties['default_value'][0]['value']
					: '';
			}

			if ( $nodeProperties['widget_type'] == 'file_generic' ) {
				$nodeProperties['has_description_field'] = (bool)$field->getSetting( 'description_field' );
			}

			if ( $nodeProperties['widget_type'] == 'image_image' ) {
				$nodeProperties['min_resolution'] = $field->getSetting( 'min_resolution' ); // Image only
				$nodeProperties['max_resolution'] = $field->getSetting( 'max_resolution' ); // Image only
			}

			// InitialHeight is only relevant for 'text area' fields.
			if ( in_array( $nodeProperties['type'], array( 'string_long', 'text_long', 'text_with_summary' ) ) ) {
				$rows = $formComponents[$key]['settings']['rows'];
				$nodeProperties['initial_height'] = $rows * 15;
				if( $nodeProperties['type'] == 'text_with_summary' ) {
					$sumRows = $formComponents[$key]['settings']['summary_rows'];
					$nodeProperties['summary_initial_height'] = $sumRows * 15;
				}
			}

			// Scale and precision are only relevant for decimal numbers and float numbers.
			$max_value = $field->getSetting( 'max' ); // Get the maximum value, if determined.
			$min_value = $field->getSetting( 'min' ); // Get the minimum value, if determined.
			if ( $nodeProperties['type'] == 'decimal' ) {
				$precision= intval( $field->getSetting( 'precision' ) );
				$scale = intval( $field->getSetting( 'scale' ) );

				$value = '.'; // Separator
				$value = str_pad( $value, ( $precision + 1 - $scale ), '9', STR_PAD_LEFT ); // Pad the value before the separator.
				$value = str_pad( $value, ( $precision + 1 ), '9' ); // Pad the value after the separator.

				$nodeProperties['min_value'] = ( empty( $min_value ) ) ?  '-' . $value : $min_value;
				$nodeProperties['max_value'] = ( empty( $max_value ) ) ? $value : $max_value;

				$nodeProperties['default_value'] = ( $nodeProperties['default_value'][0]['value'] )
					? ( $nodeProperties['default_value'][0]['value'] )
					: null;
			} elseif ( $nodeProperties['type'] == 'integer') {
				// If the max value is not determined, we set the maximum for ContentStation, which is 2147483647.
				$nodeProperties['max_value'] = ( empty( $max_value ) ) ? '2147483647' : $max_value;
				$nodeProperties['min_value'] = ( empty( $min_value ) ) ? '-999999999' : $min_value;
				$nodeProperties['default_value'] = isset( $nodeProperties['default_value'][0]['value'] )
					? ( $nodeProperties['default_value'][0]['value'] )
					: null;

			} elseif ( $nodeProperties['type'] == 'float' ) {
				// ContentStation restriction, take the smallest maximum.
				$nodeProperties['max_value'] = ( empty( $max_value) ) ? '99999999.99' : $max_value;
				$nodeProperties['min_value'] = ( empty( $min_value) ) ? '-999999.99' : $min_value;
				$nodeProperties['default_value'] = isset( $nodeProperties['default_value'][0]['value'] )
					? ( $nodeProperties['default_value'][0]['value'] )
					: null;
			}

			$nodeProperties['has_alt_text'] = (bool) $field->getSetting( 'alt_field' );

			if ( ( $nodeProperties['widget_type'] == 'image_image' ) ) {
				$nodeProperties['has_title_field'] = (bool) $field->getSetting( 'title_field' );
				$nodeProperties['file_extensions'] = $field->getSetting( 'file_extensions' );
			}

			if ( $nodeProperties['widget_type'] == 'file_generic' ) {
				$nodeProperties['has_display_field'] = (bool) $field->getSetting( 'display_field' ); // file only.
				$nodeProperties['display_default'] = (bool) $field->getSetting( 'display_default' );
				$nodeProperties['file_extensions'] = $field->getSetting( 'file_extensions' );
			}

			if( $nodeProperties['type'] == 'list_float' || $nodeProperties['type'] == 'list_integer' ||
				$nodeProperties['type'] == 'list_string'
			) { // previously known as list_text in Drupal 7.
				$listOptions = options_allowed_values( $field->getFieldStorageDefinition(), null );
				$listOptionsValues = array_values( $listOptions );
				if( !$nodeProperties['required'] ) {
					// None is only allowed when the field is not a mandatory field.
					array_unshift( $listOptionsValues, '- None -' ); // In D8, 'none' option is always set at the top of the list.
				} else {
					array_unshift( $listOptionsValues, '' );
				}
				$nodeProperties['list_options'] = $listOptionsValues; // Only store the options values(labels).

				$defaultValue = null;
				if( $nodeProperties['cardinality'] == 1 ) {
					$defaultKey = isset($nodeProperties['default_value'][0]['value']) ? $nodeProperties['default_value'][0]['value'] : NULL;
					$defaultValue = is_null( $defaultKey ) ? null : $listOptions[$defaultKey];
				} else { // When the cardinality is greater than one, there can be more than one default value.
					$totalDefaultValues = count( $nodeProperties['default_value'] );
					$defaultValues = array();
					for( $ctr=0; $ctr < $totalDefaultValues; $ctr++ ) {
						$defaultKey = $nodeProperties['default_value'][$ctr]['value'];
						if( is_null( $defaultKey ) ) {
							continue; // Don't add the option '- None -' as default when there are more than 1 default.
						}
						$defaultValues[] = $listOptions[$defaultKey];
					}
					if( $defaultValues ) {
						$defaultValue = implode( ',', $defaultValues );
					}
				}
				$nodeProperties['default_value'] = $defaultValue;
			}

			$nodeProperties['content_type'] = $contentType;
			$nodeProperties['property_info_type'] = getEnterprisePropertyInfoType(
				$nodeProperties['widget_type'], $nodeProperties['type'], $nodeProperties['cardinality'],
				$nodeProperties['has_text_filter'], $dateTimeType
			);
			$nodeProperties['has_display_summary'] = (bool) $field->getSetting( 'display_summary' );
			$nodeProperties['description'] = $field->getItemDefinition()->getDescription();
			$warnOnly = false;
			$errors = validateFieldDefinition( $nodeProperties, 'custom', $warnOnly );

			if ( empty( $errors ) || $warnOnly ) {
				// Add to the list:
				$enterpriseFieldDefinition['custom_fields'][$field->getName()] = $nodeProperties;
			}

			if (!empty( $errors ) ) {
				$enterpriseFieldDefinition['errors'][$field->getName()] = $errors;
				foreach ( $errors as $error ) {
					$message = $error['content_type'] . ':' . $error['field_name'] . ' - ' . $error['message'];
					if ($error['severity'] == 'error') {
						\Drupal::logger( 'ww_enterprise' )->error( $message );
					} else {
						\Drupal::logger( 'ww_enterprise' )->warning( $message );
					}
				}
			}
		}
	}


	// Reorder the fields for enterprise:
	$sortedFieldDefinition = array( 'basic_fields' => array(), 'custom_fields' => array(), 'errors' => array() );
	$sortedFieldDefinition['basic_fields'] = $enterpriseFieldDefinition['basic_fields'];
	$sortedFieldDefinition['errors'] = $enterpriseFieldDefinition['errors'];

	$formCustomComponentsByWeight = array();
	foreach ( $formComponents as $componentKey => $component ) {
		if ( array_key_exists( $componentKey, $enterpriseFieldDefinition['custom_fields'] ) ) {
			$weight = isset($component['weight']) ? $component['weight'] : 0;
			$formCustomComponentsByWeight[$componentKey] = $weight;
		}
	}
	asort( $formCustomComponentsByWeight );
	foreach ( $formCustomComponentsByWeight as $componentName => $weight ) {
		if ( array_key_exists( $componentName, $enterpriseFieldDefinition['custom_fields'] ) ){
			$sortedFieldDefinition['custom_fields'][$componentName] = $enterpriseFieldDefinition['custom_fields'][$componentName];
		}
	}
	return $sortedFieldDefinition;
}

/**
 * Determines if the DrupalField definition is valid.
 *
 * @param array $fieldDefinition The definition to be validated.
 * @param string $type The type of the field to be validated.
 * @param bool $warnOnly Whether or not the errors should block the import of this field.
 * @return array List of found errors.
 */
function validateFieldDefinition( &$fieldDefinition, $type, &$warnOnly=false )
{
	$errors = array();
	$severity = ( $fieldDefinition['required'] ) ? 'error' : 'warn';
	$unsupportedFieldTypes = array(	'text' ); //  text (formatted)

	$requiredFieldMessage = ( $fieldDefinition['required'] ) ? '(This is a required field. However, it could not be imported.)' : '';

	// Work around for comments, display the isRequired message if needed, but do not add it as an error, rather use a warning.
	if ( $fieldDefinition['type'] == 'comment') {
		$severity = 'warn';
	}

	$fieldName = 'Unknown';
	$contentType = $fieldDefinition['content_type'];

	if ( empty( $fieldDefinition['name'] ) ) { // Field Definition needs to have a name.
		$errors[] = createError( $fieldName, 'Missing Field name.', $contentType, 'error' );
	} else {
		$fieldName = $fieldDefinition['name'];
	}

	if ( empty( $fieldDefinition['display_name'] ) ) { // Field definition needs to have a display name.
		$errors[] = createError( $fieldName, 'The Display Name should not be null.' . $requiredFieldMessage, $contentType, 'error' );
	}

	if ( empty( $fieldDefinition['type'] ) ) { // Field definition needs to have a valid type.
		$errors[] = createError( $fieldName, 'Unsupported Field type.' . $requiredFieldMessage, $contentType, 'error' );
	}

	if ( $type == 'basic' && empty( $fieldDefinition['widget_type'] ) ) { // Field  definition needs to have a valid widget type.
		$errors[] = createError( $fieldName, 'The Field Type should not be null.' . $requiredFieldMessage, $contentType, 'error' );
	}


	if ( $fieldDefinition['cardinality'] == 0 ) { // Field definition may not have cardinality 0.
		$errors[] = createError( $fieldName, 'The cardinality for this Field should not be \'0\'.' . $requiredFieldMessage, $contentType, 'error' );
	}

	if ( is_null( $fieldDefinition['property_info_type'] ) ) { // The PropertyInfoType needs to be known (indicates a valid type).
		if( !in_array( $fieldDefinition['type'], $unsupportedFieldTypes ) ) {
			$errors[] = createError( $fieldName, 'The Property Info Type could not be determined for field type \''
				. $fieldDefinition['type'] . '\' and widget type \'' . $fieldDefinition['widget_type']
				. '\' ' .$requiredFieldMessage, $contentType, $severity );
		}
	}

	if ( $type == 'custom' ) {
		// Check the Type / Widget and the required fields for non-basic types.
		switch ( $fieldDefinition['type'] ) {
			case 'boolean' :
			case 'list_float' :
			case 'list_integer' :
			case 'list_string' : // Business Rule: Cardinality has to be 1 for a Boolean field, anything else is not allowed.
				if ( $fieldDefinition['cardinality'] == 1 ) {
					// Business Rule: A Boolean field is valid if it is a single on/off checkbox, or if it is a Check
					// Box / Radio Buttons, also matches float_list.
					if ( $fieldDefinition['widget_type'] == 'boolean_checkbox' || $fieldDefinition['widget_type'] == 'options_buttons' ) {
						break;
					}
				}

				// Float / Text / Integer Lists can have any cardinality, but need to match the type for RadioButtons or Select widgets.
				if ( ( $fieldDefinition['type'] == 'list_float' || $fieldDefinition['type'] == 'list_integer' || $fieldDefinition['type'] == 'list_string' )
					&& ( $fieldDefinition['widget_type'] == 'options_buttons' || $fieldDefinition['widget_type'] == 'options_select' )
				) {
					break;
				}

				// None of the cases matched, generate an error.
				$errors[] = createError( $fieldName, 'The cardinality is invalid; ' . $fieldDefinition['cardinality'] .
					' is not valid for Fields of type ' . $fieldDefinition['widget_type']. '. ' .$requiredFieldMessage,
					$contentType, $severity );
				break;
			case 'decimal' : // Field Type Double.
			case 'float' : // Field type Float.
			case 'integer' : // Field Type Integer.
				// Business Rule: Cardinality has to be 1 for a decimal number.
				if ( $fieldDefinition['cardinality'] == 1 ) {
					// Business Rule: A Decimal or Integer Number should be widget Type Number.
					if ( $fieldDefinition['widget_type'] == 'number' ) {
						break;
					}
				}
				$errors[] = createError( $fieldName, 'The cardinality is invalid; ' . $fieldDefinition['cardinality'] .
					' is not valid for Fields of type ' . $fieldDefinition['widget_type']. '. ' .$requiredFieldMessage,
					$contentType, $severity );
				break;
			case 'datetime': // Field Type date time
				// Business Rule: Cardinality has to be 1 for a date time.
				if ( $fieldDefinition['cardinality'] != 1 ) {
					$errors[] = createError( $fieldName, 'The cardinality is invalid; ' . $fieldDefinition['cardinality'] .
						' is not valid for Fields of type ' . $fieldDefinition['widget_type']. '. ' .$requiredFieldMessage,
						$contentType, $severity );
				}
				if( $fieldDefinition['default_value'] ) {
					if ( empty( $errors ) ) {
						$warnOnly = true;
						$requiredFieldMessage = ''; // Field will not lean on this error for the import.
					}
					$errors[] = createError( $fieldName, 'The default value is not supported; ' . $fieldDefinition['default_value'] .
						' is not supported for Fields of type ' . $fieldDefinition['widget_type']. '. ' .$requiredFieldMessage,
						$contentType, 'warn' );

					$fieldDefinition['default_value'] = null;
				}
				break;
			case 'file' : // Field Type File / Collection Block.
				// Business Rule: File Selector may have a Description field enabled.
				// Business Rule: File Selector may have any Cardinality.
				break;
			case 'image' : // Image / Collection Block.
				// Business Rule: File Selector (Image) does not have the alt text or the title field, and should have
				//                a cardinality of 1.
				if ( $fieldDefinition['cardinality'] == 1 && $fieldDefinition['widget_type'] == 'image_image'
					&& !$fieldDefinition['has_alt_text'] && !$fieldDefinition['has_title_field']
				) {
					break;
				}

				// Business Rule: Collection may have any cardinality, but needs to have a alt text field or a title field.
				if ( ( $fieldDefinition['has_alt_text'] || $fieldDefinition['has_title_field'] || $fieldDefinition['cardinality'] != 1 )
					&&	$fieldDefinition['widget_type'] == 'image_image'
				) {
					break;
				}

				// If the Widget Type is supported but the other validation rules neither matched a FileSelector nor
				// CollectionBlock set error messages accordingly.
				if ( $fieldDefinition['widget_type'] == 'image_image' ) {
					// Validation did not pass for a FileSelector for a Drupal Image, set an error message accordingly.
					$errors[] = createError( $fieldName, 'An Image Field with a cardinality of 1 may not have '
						. ' an alt text field \'false\', or have a title field \'false\'.'
						. ' Entered values: alt text field: \''
						. (($fieldDefinition['has_alt_text']) ? 'true' : 'false') . '\' title field: \''
						. (($fieldDefinition['has_title_field']) ? 'true' : 'false') . '\' cardinality: \''
						. $fieldDefinition['cardinality'] . '\'. '  . $requiredFieldMessage,  $contentType,
						$severity
					);

					// Validation did not pass for a Collection Block for a Drupal Image, sen an error message accordingly.
					$errors[] = createError( $fieldName, 'An Image Field (collection) may have any cardinality but '
						. 'should have an alt text field or title field. Entered values: alt text field: \''
						. (($fieldDefinition['has_alt_text']) ? 'true' : 'false') . '\' title field: \''
						. (($fieldDefinition['has_title_field']) ? 'true' : 'false') . '\' cardinality: \''
						. $fieldDefinition['cardinality'] . '\'. '  . $requiredFieldMessage,  $contentType,
						$severity
					);
				}
				break;
			case 'email' : // email field.
			case 'string' :     // text (plain)
			case 'string_long': // text (plain, long)
			case 'text_long':   // text (formatted, long)
			case 'text_with_summary': // text (formatted, long, summary)
			case 'comment': // Comment field.
				if ( $fieldDefinition['cardinality'] != 1 ) {
					$errors[] = createError( $fieldName ,
						'Cardinality has to be 1 for fields of type "'.$fieldDefinition['type'].'". Encountered '
						. 'cardinality: \'' . $fieldDefinition['cardinality'] . '\'. ' . $requiredFieldMessage,
						$contentType, $severity );
				}
				break;
			case 'taxonomy_term_reference' :
				// Term references may have any cardinality.
				break;
			default : // Invalid so do nothing.
				$errors[] = createError( $fieldName, 'Unsupported Field and Field type combination for: Type: \''
					. $fieldDefinition['type'] . '\', Field: \'' . $fieldDefinition['widget_type'] . '\'. '  . $requiredFieldMessage,
					$contentType, $severity );
				break;
		}
	}
	return $errors;
}

/**
 * Maps a Drupal field to a WoodWing Enterprise Server field.
 *
 * @param string $widgetType The widget type value.
 * @param string $fieldInfoType The field info type value.
 * @param int $cardinality The cardinality of the field.
 * @param bool $hasTextFilter Whether or not the field has a text filter.
 * @param string $dateTimeType determine which type the datetime field is.
 * @return null | string The Enterprise Server property info type.
 */
function getEnterprisePropertyInfoType( $widgetType, $fieldInfoType, $cardinality=1, $hasTextFilter=false, $dateTimeType )
{
	$propertyInfoType = null;
	switch ( $widgetType ) {
		case 'boolean_checkbox' :
			$propertyInfoType = 'bool';
			break;
		case 'options_buttons' :
		case 'options_select' :
			$propertyInfoType = 'list';
			if ( in_array( $fieldInfoType, array( 'list_float', 'list_integer', 'list_string', 'entity_reference' ) )
				&& $cardinality != 1
			) {
				$propertyInfoType = 'multilist';
			}
			break;
		case 'number' :
			if ( $fieldInfoType == 'decimal' || $fieldInfoType == 'float' ) {
				$propertyInfoType = 'double';
			}

			if ( $fieldInfoType == 'integer' ) {
				$propertyInfoType = 'int';
			}
			break;
		case 'file_generic' :
			$propertyInfoType = 'fileselector';
			break;
		case 'image_image' :
			$propertyInfoType = 'fileselector';
			break;
		case 'email_default' :
			$propertyInfoType = 'string';
			break;
		case 'string_textfield' : // Text (plain)
			$propertyInfoType = ( $hasTextFilter ) ? 'articlecomponentselector' : 'string';
			break;
		case 'string_textarea': // Text (plain, long)
		case 'text_textarea' : // Text (formmated, long)
		case 'text_textarea_with_summary': // Text (formatted, long, summary)
			$propertyInfoType = ( $hasTextFilter ) ? 'articlecomponentselector' : 'multiline';
			break;
		case 'entity_reference_autocomplete' :
		case 'entity_reference_autocomplete_tags' :
			// @todo Is this the correct behavior for non-tags autocomplete?
			$propertyInfoType = 'multistring';
			break;
		case 'datetime_default' :
			switch ( $dateTimeType ){
				case 'datetime':
					$propertyInfoType = 'datetime';
					break;
				case 'date':
					$propertyInfoType = 'date';
					break;
			}
			break;
		case 'comment_default' :
			$propertyInfoType = 'list';
			break;
		default :
			$propertyInfoType = null;
			break;
	}
	return $propertyInfoType;
}

/**
 * Deletes expired previews.
 *
 * Deletes expired previews from the ww_enterprise_preview table.
 *
 */
function deleteExpiredPreviews()
{
	$timeLimit = time() - 3600; // 1 hour
	$result = db_select( 'ww_enterprise_preview', 'p' )
		->fields( 'p', array('vid', 'nid', 'hash' ) )
		->condition( 'date', $timeLimit, '<' )
		->execute();
	$resultSet = $result->fetchAssoc(); // Need to use fetchAssoc because we are not sure if the record actually exists.
	$nids = array();
	while ( $resultSet ) {
		$nids[] = $resultSet['nid'];
		$resultSet = $result->fetchAssoc();
	}

	if ( $nids ) {
		// Remove the nodes from Drupal.
		$nodes = Drupal\node\Entity\Node::loadMultiple( $nids );
		if ( $nodes ) {
			\Drupal::entityManager()->getStorage('node')->delete( $nodes );
		}

		// Remove the node preview entries from the preview table.
		db_delete( 'ww_enterprise_preview' )
			->condition( 'nid', $nids, 'IN' )
			->execute();
	}
}

/**
 * Checks the filters for the Drupal installation.
 *
 * The FULL_HTML filter is required to properly retrieve content type information for Enterprise.
 *
 * @throws Exception Throws an Exception if the filters are incorrectly configured.
 */
function checkFilters()
{
	// Determine if there is a default filter, that it is enabled, and that it is for HTML.
	$default_filter_format = \Drupal::config('ww_enterprise.settings')->get('default_filter_format');
	$filter = FilterFormat::load( $default_filter_format );

	if ( !$filter || !$filter->status() ){
		throw new \Exception( 'The mandatory Drupal text format "' . $default_filter_format . '" (set by ww_enterprise.settings:default_filter_format) is either missing or disabled. Please ' .
			'ensure that the format exists and is not disabled.' );
	}
}

/**
 * Resolves the inline images that lay within the text component.
 *
 * @param string $content The content of the article component widget which contains the inline images.
 * @param array $attachments List of key value pairs where key is the Enterprise object id and value the Drupal id.
 * @return string
 */
function resolveInlineImages( $content, $attachments )
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

	if( $attachments ) foreach ($attachments as $enterpriseId => $drupalId) {

		// Load the Image.
		$file = \Drupal\file\Entity\File::load( $drupalId );

		for( $i=0; $i<count($patternResult[0]); $i++ ) {
			if( $patternResult[1][$i] == $enterpriseId ) {
				$entImage = $patternResult[0][$i]; // '<img id="ent_899901496" src="ww_enterprise"/>'
				$drupalImageUrl = file_create_url( $file->getFileUri() );
				$drupalImage = str_replace( $patternResult[2][$i], $drupalImageUrl, $entImage ); // after replacement, src="http://..."
				$content = str_replace( $entImage, $drupalImage, $content );
			}
		}
	}
	return $content;
}

/**
 * Map all fields's values from Publish Form into Drupal node's values.
 *
 * @param Object $node The node to map the values for.
 * @param array $formValues The form values.
 * @param bool $previewMode Whether a preview is generated or not.
 */
function mapNodeValues( $node, $formValues, $previewMode )
{
	// Get the custom fields and set them on the node.
	$basicProperties = array(
		'C_DIALOG_DRUPAL8_TITLE',
		'C_DIALOG_DRUPAL8_STICKY',
		'C_DIALOG_DRUPAL8_PROMOTE',
		'C_DIALOG_DRUPAL8_PUBLISH'
	);

	// Set basic properties.
	if ( array_key_exists( 'C_DIALOG_DRUPAL8_TITLE', $formValues )) {
		$node->title = $formValues['C_DIALOG_DRUPAL8_TITLE'][0];
	} else {
		unset( $basicProperties['C_DIALOG_DRUPAL8_TITLE'] );
	}
	$node->sticky = intval($formValues['C_DIALOG_DRUPAL8_STICKY'][0]);
	$node->promote = intval($formValues['C_DIALOG_DRUPAL8_PROMOTE'][0]);
	$node->status = ( $previewMode ) ? 0 : $formValues['C_DIALOG_DRUPAL8_PUBLISH'][0];

	if( $formValues ) foreach ( $formValues as $key => $values ) {
		if ( !in_array( $key, $basicProperties ) ) {
			// Get the field machine name.
			$field_machine_name = retrieveMachineName( $key );

			if ( !is_null( $field_machine_name ) ) {
				$val = null;
				$fieldType = $node->$field_machine_name->getFieldDefinition()->getType();
				switch ( $fieldType ) {
					case 'boolean' :
						$val = intval( $values[0] );
						$node->$field_machine_name = $val;
						break;
					case 'integer':
						$val = intval( $values[0] );
						$node->$field_machine_name = $val;
						break;
					case 'float':
					case 'decimal':
						$val = $values[0];
						$node->$field_machine_name = $val;
						break;
					case 'datetime':
						$val = $values[0];
						$node->$field_machine_name = $val;
						break;
					case 'list_float':
					case 'list_integer':
					case 'list_string':
						$listOptions = options_allowed_values( $node->$field_machine_name->getFieldDefinition()->getFieldStorageDefinition(), null);
						$val = ''; // This is when user selected '- None -'.
						$listValuesCount = count( $formValues[$key] );

						if( $listValuesCount == 1 ) { // When cardinality is 1
							if( in_array( $formValues[$key][0], $listOptions ) ) {
								// When cardinality = 1, only return the label's key.
								$val = array_search( $formValues[$key][0], $listOptions ); // Getting the label's key.
							}
						} else if( $listValuesCount > 1 ) {  // When cardinality is > 1
							$val = array(); // When cardinality > 1, should return as a list of labels' keys.
							if( $formValues[$key] ) foreach( $formValues[$key] as $listValue ) {
								if( in_array( $listValue, $listOptions ) ) {
									$val[] = array_search( $listValue, $listOptions ); // Collecting the labels' corresponding key.
								}
							}
						}
						$node->$field_machine_name = $val;
						break;
					case 'email': // email.
					case 'string': // text (plain)
					case 'string_long': // text (plain, long)
						$val = ( isset( $values[0] ) ) ? $values[0] : null;
						$node->$field_machine_name = $val;
						break;
					case 'text_long': // text (formatted, long)
						$content = ( isset( $values[0]['elements'] ) ) ? $values[0]['elements'] : null;
						$attachments = ( isset( $values[0]['attachments'] ) ) ? $values[0]['attachments'] : null;
						$val = resolveInlineImages( $content, $attachments );
						$node->$field_machine_name->value = $val;
						$node->$field_machine_name->format = \Drupal::config('ww_enterprise.settings')->get('default_filter_format');
						break;
					case 'text_with_summary': // text (formatted, long, summary)
						// Long text summary
						$content = null;
						if( isset( $values[0]['elements'] ) ) { // Happens when only 'body' is filled in.
							$content = $values[0]['elements'];
						} else if( isset( $values['value'][0]['elements'] ) ) { // Happens when 'body' & 'summary' or only 'summary' is filled in.
							$content = $values['value'][0]['elements'];
						}

						$attachments = null;
						if( isset( $values[0]['attachments'] ) ) { // Happens when only 'body' is filled in.
							$attachments = $values[0]['attachments'];
						} else if( isset( $values['value'][0]['attachments'] ) ) { // Happens when 'body' & 'summary' or only 'summary' is filled in.
							$attachments = $values['value'][0]['attachments'];
						}

						$longTextContent = resolveInlineImages( $content, $attachments );
						$node->$field_machine_name->value = $longTextContent;

						// Summary
						$content = isset( $values['summary'][0]['elements'] ) ? $values['summary'][0]['elements'] : null;
						$attachments = isset( $values['summary'][0]['attachments'] ) ? $values['summary'][0]['attachments'] : null;
						$summaryContent = resolveInlineImages( $content, $attachments );
						$node->$field_machine_name->summary = $summaryContent;

						$node->$field_machine_name->format = \Drupal::config('ww_enterprise.settings')->get('default_filter_format');
						break;
					case 'entity_reference': //
						// @todo Expand to support all kinds of references.
						$termIds = array();
						foreach ( $values as $value ) {
							if ($value != '') {
								$taxonomyEntityTerm = \Drupal::entityManager()->getStorage( 'taxonomy_term' )->loadByProperties( array( 'name' => $value ) );
								if ( count( $taxonomyEntityTerm ) > 0) { // Existing record, terms already exist in Drupal Database.
									// So only retrieve them.
									$termIds[] = key( $taxonomyEntityTerm );
								} else { // Not yet exists, user has just entered new one.
									// Save into Drupal database.
									$fieldDefinition = $node->$field_machine_name->getFieldDefinition();
									$fieldItemSettings = $fieldDefinition->getItemDefinition()->getSettings();
									$vocabulary = array_pop( $fieldItemSettings['handler_settings']['target_bundles'] );
									$taxonomyEntityTerm = \Drupal::entityManager()->getStorage( 'taxonomy_term' )->create(array(
										'vid' => $vocabulary,
										'name' => $value
									));
									$taxonomyEntityTerm->save();
									$termIds[] = $taxonomyEntityTerm->id();
								}
							}
						}
						$node->$field_machine_name = $termIds;
						break;
					case 'image':
						$val = null;
						// Alt field
						$altField = $node->$field_machine_name->getSetting( 'alt_field' ); // boolean: Whether or not it needs a alt text for the image.
						$altFieldRequired = $node->$field_machine_name->getSetting( 'title_field_required' ); // boolean: Whether or not it is mandatory.

						// Title field
						$titleField = $node->$field_machine_name->getSetting( 'title_field' ); // boolean: Whether or not it needs a title for the image (for hover).
						$titleFieldRequired = $node->$field_machine_name->getSetting( 'title_field_required' ); // boolean: Whether or not it is mandatory.

						$imageSettings = $node->$field_machine_name->getSetting( 'default_image' );

						$fids = array();
						if( $values ) {
							$total = count( $values );
							for( $index=0; $index < $total; $index++ ) {
								$fid = array( 'target_id' => $values[$index]['fid'] );

								if( $altField || $altFieldRequired ) {
									// Only get the default 'Alt' from Drupal when it is not set in the PublishForm.
									$fid['alt'] = $values[$index]['alt'] ? $values[$index]['alt'] : $imageSettings['alt'];
								}

								if( $titleField || $titleFieldRequired ) {
									// Only get the default 'Title' from Drupal when it is not set in the PublishForm.
									$fid['title'] = $values[$index]['title'] ? $values[$index]['title'] : $imageSettings['title'];
								}

								if( !is_null( $imageSettings['width'] ) && !is_null( $imageSettings['height'] ) ) {
									$fid['width'] = $imageSettings['width'];
									$fid['height'] = $imageSettings['height'];
								}
								$fids[] = $fid;
							}
						}
						$node->$field_machine_name = $fids;
						break;
					case 'file' :
						$newValues = array();
						$i = 0;
						if ($values) foreach ( $values as $value ) {
							$val = array();
							$val['target_id'] = $value['fid'];

							// If user input is not allowed for the display field the file is displayed on the screen.
							// once this input is allowed it uses the 'display' setting to determine whether or not to
							// show the file to the user.
							$val['display'] = 0; // Default to hidden.
							if ( $node->$field_machine_name->getSetting( 'display_field' ) ) {
								$val['display'] = $value['display'];
							}

							// Only add the description if the field is enabled.
							if ( $node->$field_machine_name->getSetting( 'description_field' ) && isset( $value['description'] ) ) {
								$val['description'] = $value['description'];
							}

							$newValues[$i] = $val;
							$i++;
						}
						$node->$field_machine_name = $newValues;
						break;
					case 'comment' :
						$listOptions = array( 'Hidden' => 0, 'Closed' => 1, 'Open' => 2 );
						$val = $listOptions[$formValues[$key][0]];
						$node->$field_machine_name = $val;
						break;
					default:
						throw new Exception ( 'Unknown field type "' . $fieldType . '" for field "'.$field_machine_name.'".' );
				}
			}
		}
	}
}

/**
 * Returns the URL of the published node.
 *
 * When the published node URL is requested for preview purposes,
 * it expects a hash. The hash will be added to the URL which later
 * will be used to check if the ContentStation user has the access to see
 * the preview. For normal views to the node the hash is ommitted and access
 * is managed based on what is defined on the node.
 *
 * @param int $nid node id The published node Drupal Id
 * @param bool $preview True if the URL requested is for preview; False otherwise.
 * @param string $hash if it is a preview send the unique hash (only needed for previews)
 * @return string URL of the publish node. When no node id given, the Drupal site URL is returned.
 */
function getNodeUrl( $nid, $preview, $hash = '' )
{
	// Remove :80 from base url, xmlrpc client on Enterprise side adds this to SERVER_HOST :(
	global $base_url;
	$url = preg_replace('|^http://([^/]*):80/|', 'http://$1/', $base_url, 1);

	if( $nid == null ) {
		return $url;
	}

	$initializedNode = Drupal\node\Entity\Node::Load( $nid );
	$url = $initializedNode->url( 'canonical', array( 'absolute' => true ) );

	if ( $preview ) {
		$url .= '?ww_enterprise_hash=' . $hash;
	}
	return $url;
}
?>
