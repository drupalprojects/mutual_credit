<?php

/**
 * @file
 * Rules module hooks
 */

/**
 * Rules controller
 */
class TransactionRulesController extends EntityDefaultRulesController {

  /**
   * {@inheritdoc}
   */
  public function eventInfo() {
    $info = $this->info;
    $type = $this->type;

    $label = $info['label'];
    $triggers = mcapi_trigger_info();
    // we'll use the triggers declared.
    foreach ($triggers['mcapi'] as $trigger_callback => $trigger) {
      // There were just too many obstacles to making 'transaction_children' work with rules.
      if ($trigger_callback == 'transaction_children') {
        continue;
      }

      switch ($trigger_callback) {
        case 'transaction_update':
          // State is the same as the other events except it expects a second argument.
          $variables = array(
            'transaction' => array(
              'type' => 'transaction',
              'label' => t('Transaction'),
            ),
            'state' => array(
              'type' => 'integer',
              'label' => t('Transaction state'),
            ),
          );
          break;

        case 'transaction_delete':
        case 'transaction_view':
          // These include the transaction_transitions.
        default:
          $variables = array(
            'transaction' => array(
              'type' => 'transaction',
              'label' => t('Transaction'),
            ),
          );
      }
      // Add this to every.
      $items[$trigger_callback] = array(
        'module' => 'mcapi',
        'group' => t('Community accounting'),
        'label' => $trigger['label'],
        'variables' => $variables,
      );
    }
    return $items;
  }

}


/**
 * UI for configuring worth field.
 */
class RulesDataUIWorth extends RulesDataUI implements RulesDataDirectInputFormInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultMode() {
    return 'input';
  }

  /**
   * {@inheritdoc}
   */
  public static function inputForm($name, $info, $settings, RulesPlugin $element) {
    $form = array(
      $name => array(
        '#title' => t('Worth at least'),
        '#type' => 'worth',
        '#default_value' => isset($settings[$name]) ? $settings[$name] : [],
        '#all' => TRUE,
         // This is not config mode because it's not 'presetting the form its
         // actually setting a value. It affects what happens to the zero and
         // null values.
        '#required' => TRUE,
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function render($value) {
    // This is similar to mcapi_field_formatter_view, but just renders a single
    // item.
    $renderable = array(
      '#theme' => 'worth_item',
      '#curr_id' => $value['curr_id'],
      '#quantity' => $value['quantity'],
    );
    return $renderable;
  }

}


/**
 * Implements hook_rules_data_info().
 */
function mcapi_rules_data_info() {
  // Rules already knows about the transaction entity and about the worth field.
}

/**
 * Implements hook_rules_action_info().
 */
function mcapi_rules_action_info() {
  // I'd really really like a way for rules to add dependent transactions to the
  // cluster during hook_transaction_children. However rules doesn't expect to
  // alter its own parameters as far as i can see. So this would involve:
  // creating a new rules datatype, for the transaction cluster
  // pass it by reference by calling $event->executeByArgs($args) directly
  // during hook_transaction_children. A half way measure would involve
  // inserting the transaction after it was created, but using the same serial
  // number. This would mean the transaction wouldn't show up on the
  // 'are you sure' page but would appear afterwards in the cluster.
}
