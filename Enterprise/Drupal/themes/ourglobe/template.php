<?php
// $Id: template.php,v 1.17 2008/09/11 10:52:53 johnalbin Exp $

/**
 * @file
 * Contains theme override functions and preprocess functions for the theme.
 *
 * ABOUT THE TEMPLATE.PHP FILE
 *
 *   The template.php file is one of the most useful files when creating or
 *   modifying Drupal themes. You can add new regions for block content, modify
 *   or override Drupal's theme functions, intercept or make additional
 *   variables available to your theme, and create custom PHP logic. For more
 *   information, please visit the Theme Developer's Guide on Drupal.org:
 *   http://drupal.org/theme-guide
 *
 * OVERRIDING THEME FUNCTIONS
 *
 *   The Drupal theme system uses special theme functions to generate HTML
 *   output automatically. Often we wish to customize this HTML output. To do
 *   this, we have to override the theme function. You have to first find the
 *   theme function that generates the output, and then "catch" it and modify it
 *   here. The easiest way to do it is to copy the original function in its
 *   entirety and paste it here, changing the prefix from theme_ to ourglobe_.
 *   For example:
 *
 *     original: theme_breadcrumb()
 *     theme override: ourglobe_breadcrumb()
 *
 *   where ourglobe is the name of your sub-theme. For example, the
 *   zen_classic theme would define a zen_classic_breadcrumb() function.
 *
 *   If you would like to override any of the theme functions used in Zen core,
 *   you should first look at how Zen core implements those functions:
 *     theme_breadcrumbs()      in zen/template.php
 *     theme_menu_item_link()   in zen/template.php
 *     theme_menu_local_tasks() in zen/template.php
 *
 *   For more information, please visit the Theme Developer's Guide on
 *   Drupal.org: http://drupal.org/node/173880
 *
 * CREATE OR MODIFY VARIABLES FOR YOUR THEME
 *
 *   Each tpl.php template file has several variables which hold various pieces
 *   of content. You can modify those variables (or add new ones) before they
 *   are used in the template files by using preprocess functions.
 *
 *   This makes THEME_preprocess_HOOK() functions the most powerful functions
 *   available to themers.
 *
 *   It works by having one preprocess function for each template file or its
 *   derivatives (called template suggestions). For example:
 *     THEME_preprocess_page    alters the variables for page.tpl.php
 *     THEME_preprocess_node    alters the variables for node.tpl.php or
 *                              for node-forum.tpl.php
 *     THEME_preprocess_comment alters the variables for comment.tpl.php
 *     THEME_preprocess_block   alters the variables for block.tpl.php
 *
 *   For more information on preprocess functions, please visit the Theme
 *   Developer's Guide on Drupal.org: http://drupal.org/node/223430
 *   For more information on template suggestions, please visit the Theme
 *   Developer's Guide on Drupal.org: http://drupal.org/node/223440 and
 *   http://drupal.org/node/190815#template-suggestions
 */


/*
 * Add any conditional stylesheets you will need for this sub-theme.
 *
 * To add stylesheets that ALWAYS need to be included, you should add them to
 * your .info file instead. Only use this section if you are including
 * stylesheets based on certain conditions.
 */
/* -- Delete this line if you want to use and modify this code
// Example: optionally add a fixed width CSS file.
if (theme_get_setting('ourglobe_fixed')) {
  drupal_add_css(path_to_theme() . '/layout-fixed.css', 'theme', 'all');
}
// */


/**
 * Implementation of HOOK_theme().
 */
function ourglobe_theme(&$existing, $type, $theme, $path) {
  $hooks = zen_theme($existing, $type, $theme, $path);
  // Add your theme hooks like this:
  /*
  $hooks['hook_name_here'] = array( // Details go here );
  */
  // @TODO: Needs detailed comments. Patches welcome!
  return $hooks;
}

/**
 * Override or insert variables into all templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered (name of the .tpl.php file.)
 */
/* -- Delete this line if you want to use this function
function ourglobe_preprocess(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */

/**
 * Override or insert variables into the page templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("page" in this case.)
 */
/* -- Delete this line if you want to use this function
function ourglobe_preprocess_page(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */

/**
 * Override or insert variables into the node templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("node" in this case.)
 */
/* -- Delete this line if you want to use this function
function ourglobe_preprocess_node(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */

/**
 * Override or insert variables into the comment templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("comment" in this case.)
 */
/* -- Delete this line if you want to use this function
function ourglobe_preprocess_comment(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */

/**
 * Override or insert variables into the block templates.
 *
 * @param $vars
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("block" in this case.)
 */
/* -- Delete this line if you want to use this function
function ourglobe_preprocess_block(&$vars, $hook) {
  $vars['sample_variable'] = t('Lorem ipsum.');
}
// */


function phptemplate_node_submitted($node) {
  return t('Updated @datetime',
    array(
      '@datetime' => format_date($node->created),
    ));
}

function phptemplate_preprocess_page(&$vars) {
  $vars['today'] = format_date(time(), 'custom', 'l j F Y');
  $vars['comments'] = $vars['comment_form'] = '';
  if (module_exists('comment') && isset($vars['node'])) {
    $vars['comments'] = comment_render($vars['node']);
    global $user;
    if (user_access('post comments') && (arg(0)=='node' && is_numeric(arg(1)) && (!arg(2))) ) {
      $vars['comment_form'] = drupal_get_form('comment_form', array('nid' => $vars['node']->nid));
    }
  }
}

function phptemplate_preprocess_node(&$vars) {
  $vars['node']->comment = 0;
}

function phptemplate_comment_submitted($comment) {
  return t('!username | @datetime',
    array(
      '!username' => theme('username', $comment),
      '@datetime' => format_date($comment->timestamp, 'custom', 'j M Y | G:H')
    ));
}

/*
function phptemplate_feed_icon($url, $title) {
  if ($image = theme('image', 'misc/feed.png', t('Syndicate content'), $title)) {
    return '<a href="'. check_url($url) .'" class="feed-icon">'. $image .'</a>';
  }
}
*/

function phptemplate_filter_tips_more_info() {
  return;
}

/*
function phptemplate_filter_tips($tips, $long = FALSE, $extra = '') {
  return;
}

function phptemplate_comment_form($form) {
  $form['submit']['#theme'] = 'button';
  $form['submit']['#button_type'] = 'image';
  $form['submit']['#attributes'] = array(
    'src' => '/images/preview.png',
    'alt' => t('Preview'),
  );
  return drupal_render($form);
}
*/

function phptemplate_print_mail_form($form) {
  $form['txt_to_addrs']['#cols'] = 50;
  return drupal_render($form);
}



