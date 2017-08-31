<?php

/**
 * @file
 * Hooks provided by the WoodWing enterprise module.
 */

/**
 * Alters the node that is being saved through the XML-RPC call.
 *
 * The hook will fire after the fields have been mapped to the node object and
 * before the node is being saved.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node object.
 * @param array $form_values
 *   The form values.
 * @param array $options
 *   Array of various options related to the publishing of a node. Possible
 *   keys are:
 *   - Action: Operation being executed. Can be 'Publish', 'Preview' or
 *     'Update'.
 *   - Username: Author's username.
 *   - ExternalId: NID of the existing node if 'Action' is 'Update'.
 *   - Preview: TRUE if a preview is being generated, FALSE otherwise.
 */
function hook_ww_enterprise_node_map_alter(\Drupal\node\NodeInterface $node, $form_values, $options) {
  $node->title->value = 'Overriden title';
}
