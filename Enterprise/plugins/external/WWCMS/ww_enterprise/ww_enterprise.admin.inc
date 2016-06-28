<?php

/**
 * Admin settings callback.
 */
function ww_enterprise_admin_settings ()
{
	//$form['#submit'][] = 'ww_enterprise_settings_submit';

	// read suitable vocabulary options for keywords
	$vocKeywordsOptions = array('0'=>t('<Not set>'));
	$dbr = db_query('SELECT vid, name FROM {vocabulary} WHERE relations=1 AND multiple=1');
	while ($values = db_fetch_array($dbr)) {
		$vocKeywordsOptions[$values['vid']] = $values['name'];
	}
	// read suitable vocabulary options for categories
	$vocCategoryOptions = array('0'=>t('<Not set>'));
	$dbr = db_query('SELECT vid, name FROM {vocabulary} WHERE relations=1 AND multiple=0');
	while ($values = db_fetch_array($dbr)) {
		$vocCategoryOptions[$values['vid']] = $values['name'];
	}

	$form['ww_enterprise_keywords_vocabulary'] = array('#type' => 'select' ,
		'#title' => t('Import Enterprise keywords in vocabulary') ,
		'#default_value' => variable_get('ww_enterprise_keywords_vocabulary', '') ,
		'#options' => $vocKeywordsOptions);


/*	$form['ww_enterprise_category_vocabulary'] = array('#type' => 'select' ,
		'#title' => t('Import Enterprise categories in vocabulary') ,
		'#default_value' => variable_get('ww_enterprise_category_vocabulary', '') ,
		'#options' => $vocCategoryOptions); 
 */
/*	$form['fallback_category'] = array('#type' => 'select' , '#title' => 'Import category fallback' ,
		'#default_value' => '' , '#options' => $vocOptions);
 */
 
	return system_settings_form($form);
}

/**
 * Admin defaults callback.

function ww_enterprise_admin_defaults ()
{
	// read suitable vocabulary options for categories
	$vocCategoryDefaults = array('0'=>t('<Not set>'));
	$dbr = db_query('SELECT tid, name FROM {term_data} WHERE vid=%d', variable_get('ww_enterprise_category_vocabulary', 0));
	while ($values = db_fetch_array($dbr)) {
		$vocCategoryDefaults[$values['tid']] = $values['name'];
	}

	$form['ww_enterprise_category_default'] = array('#type' => 'select' ,
		'#title' => t('Default category') ,
		'#default_value' => variable_get('ww_enterprise_category_default', '') ,
		'#options' => $vocCategoryDefaults);

	return system_settings_form($form);
}
 */

?>
