<?php
/**
 * @file
 * Hooks provided by entity API module for this the transaction entity.
 * This file is a placeholder, since the transaction entity does things quite differently to what the entity API module expects
 * constructed from template http://drupal.org/node/999936
 */

/**
 * Acts on transactions being loaded from the database.
 *
 * This hook is invoked during transaction loading, which is handled by
 * entity_load(), via the EntityCRUDController.
 *
 * @param array $transactions
 *   An array of transaction entities being loaded, keyed by id.
 *
 * @see hook_entity_load()
 */
function hook_transaction_load(array $transactions) {
  $result = db_query('SELECT pid, foo FROM {mytable} WHERE pid IN(:ids)', array(':ids' => array_keys($entities)));
  foreach ($result as $record) {
    $entities[$record->pid]->foo = $record->foo;
  }
}


/**
 * Act on a transaction that is being assembled before rendering.
 *
 * @param $transaction
 *   The transaction entity.
 * @param $view_mode
 *   The view mode the transaction is rendered in.
 * @param $langcode
 *   The language code used for rendering.
 *
 * The module may add elements to $transaction->content prior to rendering. The
 * structure of $transaction->content is a renderable array as expected by
 * drupal_render().
 *
 * @see hook_entity_prepare_view()
 * @see hook_entity_view()
 */
function hook_transaction_view($transaction, $view_mode, $langcode) {
  $transaction->content['my_additional_field'] = array(
    '#markup' => $additional_field,
    '#weight' => 10,
    '#theme' => 'mymodule_my_additional_field',
  );
}

/**
 * Alter the results of entity_view() for transactions.
 *
 * @param $build
 *   A renderable array representing the transaction content.
 *
 * This hook is called after the content has been assembled in a structured
 * array and may be used for doing processing which requires that the complete
 * transaction content structure has been built.
 *
 * If the module wishes to act on the rendered HTML of the transaction rather than
 * the structured content array, it may use this hook to add a #post_render
 * callback. Alternatively, it could also implement hook_preprocess_transaction().
 * See drupal_render() and theme() documentation respectively for details.
 *
 * @see hook_entity_view_alter()
 */
function hook_transaction_view_alter($build) {
  if ($build['#view_mode'] == 'full' && isset($build['an_additional_field'])) {
    // Change its weight.
    $build['an_additional_field']['#weight'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build['#post_render'][] = 'my_module_post_render';
  }
}

/**
 * Define default transaction configurations.
 *
 * @return
 *   An array of default transactions, keyed by machine names.
 *
 * @see hook_default_transaction_alter()
 */
function hook_default_transaction() {
  $defaults['main'] = entity_create('transaction', array(
    // …
  ));
  return $defaults;
}

/**
 * Alter default transaction configurations.
 *
 * @param array $defaults
 *   An array of default transactions, keyed by machine names.
 *
 * @see hook_default_transaction()
 */
function hook_default_transaction_alter(array &$defaults) {
  $defaults['main']->name = 'custom name';
}
