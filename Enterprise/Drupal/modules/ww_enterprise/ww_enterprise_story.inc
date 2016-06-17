<?php



define("FLV_FILE_EXT", "flv");

/**
 * Enable Views support
 *
 * @return array
 */
function ww_enterprise_views_api() {
  return array('api' => 2.0);
}

/**
 * Create views support
 *
 * @return array
 */
function ww_enterprise_views_data() {
	$data['ww_enterprise_story']['table']['group']  = t('WW Enterprise');

	//For other base tables, explain how we join
	$data['ww_enterprise_story']['table']['join'] = array(
		'node' => array(
			'left_field' => 'nid',
			'field' => 'nid',
		)
	);

	//table fields
	$data['ww_enterprise_story']['writer'] = array(
		'title' => t('Writer'), // The item it appears as on the UI,
		'help' => t('The writer of the WW Enterprise Story.'), // The help that appears on the UI,

		//Information for displaying a title as a field
		'field' => array(
			'field' => 'writer', // the real field
			'group' => t('WW Enterprise'), // The group it appears in on the UI
		)
	);

	return $data;
}

/**
 * load the theme
 *
 * @return array
 */
function ww_enterprise_theme() {
	return array(
		'ww_enterprise_view' => array(
			'template' => 'ww_enterprise_story_view',
			'arguments' => array(
				'node' => NULL
			)
		),
		'views_view__viewname_displayid' => array (
			'arguments' => array('view' => NULL),
			'template' => 'views-view--viewname-displayid',
			'original hook' => 'views_view',
		),

	);
}

/**
 * Display the WoodWing Enterprise story
 *
 * @param unknown_type $node
 * @param unknown_type $teaser
 * @param unknown_type $page
 * @return object
 */
function ww_enterprise_view($node, $teaser = FALSE, $page = FALSE)
{
	$accepted_mime_type = array('application/x-shockwave-flash', 'application/octet-stream', 'video/x-flv');

	global $front_node_counter;

	$front_node_counter++;
	$node -> countid = $front_node_counter;

	$size = array(
		'Earth' => 'BigImage',
		'Water' => 'SmallImage',
		'Culture' => 'BigImage',
		'Air' => 'SmallImage',
		'Action' => 'SmallImage',
		'Citizen' => 'SmallImage',
		'Editorial' => 'SmallImage',
	);

	$node = node_prepare($node, $teaser);
	$node -> writer = check_plain($node-> writer);
	$node -> body = (str_replace($node -> teaser, false, $node->body));
	$node -> teaser = check_markup($node -> teaser);
	if(isset($node->iid) && $node->iid && teaser && !$page) {
		//Handle attached image
		$imageSize = 'BigImage';
		if ($teaser && !$page){
			// only front page blocks
			$taxName = '';
			foreach ($node -> taxonomy as $taxonomy){
				// hardcoded find vocabulary id = 1 = Category
				if ($taxonomy->vid == 1){
					$taxName = $taxonomy->name;
					break;
				}
			}
			if (isset($size[ $taxName ])){
				$imageSize = $size[ $taxName ];
			}
		}
		$image = node_load($node->iid);
		$node -> attachedimage = image_display($image, strtolower($imageSize));
		$node -> imagecaption = check_markup($image -> body);
		unset($node->iid);
	}

	if(!$teaser) {
		//Handle attached files
		if( is_array( $node -> files ) ) {
			foreach( $node -> files as $key => $file ) {
				//Only flv files
				if( in_array($file -> filemime, $accepted_mime_type) && end( explode ( ".", $file -> filename ) ) == FLV_FILE_EXT ) {
					$node -> movies[ ] = $file;
					unset( $node -> files[ $key ] );
				}
			}
		}
	}
	else //Don't show body text on teaserlist
		unset($node->body);

	//theme the content of the ww_enterprise_story
  /*
   * Disabled, because this is theme specific and doesn't work right in the
   * default drupal themes.
   *
	$node-> content['body'] = array(
		'#value' => theme('ww_enterprise_view', $node),
		'#weight' => 1
	);
  */

	$path = drupal_get_path('module', 'ww_enterprise');
	drupal_add_css("{$path}/ww_enterprise_story.css");

	return $node;
}

/**
 * Load the Extended Story data
 *
 * @param unknown_type $node
 * @return object
 */
function ww_enterprise_load($node) {
	$result = db_query("SELECT writer FROM {ww_enterprise_story} WHERE vid = %d", $node -> vid);

	return db_fetch_object($result);
}

/**
 * nodeApi delete a revision
 *
 * @param unknown_type $node
 * @param unknown_type $op
 * @param unknown_type $teaser
 * @param unknown_type $page
 */
function ww_enterprise_nodeapi(&$node, $op, $teaser, $page) {
	if($op == 'delete revision') {
		db_query("DELETE FROM {ww_enterprise_story} WHERE vid = %d", $node -> vid);
	}
}

/**
 * Delete a ExtendedStory
 *
 * @param unknown_type $node
 */
function ww_enterprise_delete($node) {
	db_query("DELETE FROM {ww_enterprise_story} WHERE nid = %d", $node->nid);
}

/**
 * Edit a ExtendedStory
 *
 * @param unknown_type $node
 */
function ww_enterprise_update($node) {
	if($node -> revision) {
		ww_enterprise_insert($node);
	}
	else {
		db_query(
			"UPDATE {ww_enterprise_story} SET writer='%s' WHERE vid='%d'",
			$node -> writer
		);
	}
}

/**
 * adds a ww_enterprise_story
 *
 * @param unknown_type $node
 */
function ww_enterprise_insert($node) {
	if(!isset($node->writer))
		$node -> writer = '';

	db_query(
		'INSERT INTO {ww_enterprise_story} (vid, nid, writer) '
			."VALUES (%d, %d, '%s')",
		$node -> vid,
		$node -> nid,
		$node -> writer
	);
}

/**
 * Check user access
 *
 * @param unknown_type $op
 * @param unknown_type $node
 * @param unknown_type $account
 * @return unknown
 */
function ww_enterprise_access($op, $node, $account) {
	switch ($op) {
		case 'create':
			return user_access('create ww_enterprise_story nodes', $account);
		case 'update':
			return user_access('edit ww_enterprise_story nodes', $account);
		case 'delete':
			return user_access('delete ww_enterprise_story nodes', $account);
	}
}

/**
 * List the permissions
 *
 * @return array
 */
function ww_enterprise_perm() {
	return array(
		'create ww_enterprise_story nodes',
		'edit ww_enterprise_story nodes',
		'delete ww_enterprise_story nodes'
	);
}

/**
 * Build the edit and add form
 *
 * @param unknown_type $node
 * @return array
 */
function ww_enterprise_form(&$node) {
	$type = node_get_types('type', $node);

	if($type -> has_title) {
		$form['title'] = array(
			'#type' => 'textfield',
			'#title' => check_plain( $type -> title_label ),
			'#required' => TRUE,
			'#default_value' => $node -> title,
			'#weight' => -5
		);
	}

	if($type -> has_body) {
		$form['body_field'] = node_body_field($node, $type -> body_label, $type -> min_word_count);
	}

	$form['writer'] = array(
		'#type' => 'textfield',
		'#title' => t('Writer'),
		'#size' => 50,
		'#maxlength' => 127,
		'#description' => 'The writer of the story',
		'#default_value' => isset($node -> writer) ? $node -> writer : ''
	);

	return $form;
}


/**
 * Implementation of hook_node_info
 * define the ww_enterprise content type
 *
 * @return array
 */
function ww_enterprise_node_info() {
	return array(
		WW_ENTERPRISE_CONTENT_TYPE => array(
			'name' => t('WW Enterprise Story'),
			'module' => 'ww_enterprise',
			'description' => t('A WoodWing Enterprise Story, similar in form to a story, but holds more data about the story like the writer and stuff like that.'),
			'has_title' => true,
			'title_label' => t('Title'),
			'has_body' => true,
			'body_label' => t('Body')
		)
	);
}

/**
 * Show help page
 *
 * @param unknown_type $path
 * @param unknown_type $arg
 * @return String
 */
function ww_enterprise_help($path, $arg) {
	if($path == 'admin/help#ww_enterprise') {
		return '<p></p>';
	}
}





?>
