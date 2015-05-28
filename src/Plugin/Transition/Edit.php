<?php

/**
 * @file
 *  Contains Drupal\mcapi\Plugin\Transitions\Edit
 *
 */

namespace Drupal\mcapi\Plugin\Transition;

use Drupal\mcapi\Plugin\TransitionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Edit transition
 *
 * @Transition(
 *   id = "edit"
 * )
 */
class Edit extends TransitionBase {

  /**
   *  access callback for transaction transition 'edit'
   * is the transaction too old according to settings?
   * does the current user have permission?
   * @todo override access settings in the base configuration base form
  */
  public function accessOp(AccountInterface $account) {
    $days = $this->configuration['window'];
    if ($this->transaction->created->value + 86400*$days < REQUEST_TIME) {
      return FALSE;
    }
    return parent::accessOp($account);
  }

  /**
   * {inheritdoc}
   */
  public function form(array &$form) {
    $display = EntityFormDisplay::collectRenderDisplay($this->transaction, 'admin');
    $form['#parents'] = [];//this is needed otherwise the nested entity API fields collide
    foreach (array_filter($this->configuration['fields']) as $name) {
      //see EntityFormDisplay::bulidForm but we just want certain fields
      if ($widget = $display->getRenderer($name)) {
        $items = $this->transaction->get($name);
        $items->filterEmptyItems();
        $form[$name] = $widget->form($items, $form, new Formstate());
        $form[$name]['#access'] = $items->access('edit');
        $options = $display->getComponent($name);
        $form[$name]['#weight'] = $options['weight'];
      }
    }
    //assigns weights and hides extra fields.
    $form['#process'][] = array($display, 'processForm');
  }

  /**
   * {inheritdoc}
   */
  static function settingsFormTweak(array &$form, FormStateInterface $form_state, ImmutableConfig $config) {
    $fields = ['payer', 'payee', 'created', 'description', 'worth'];
    //@todo More work needs to be done to show the field API fields editable, including 'worth'");
    $form['edit_transition_settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Editing',
      '#weight' => 5,
      'fields' => [
        '#title' => t('Editable fields'),
        '#description' => t('select the fields which can be edited'),
        '#type' => 'checkboxes',
        '#options' => array_combine($fields, $fields),//this might not be the best list
        '#default_value' => $config->get('fields')
      ],
      'window' => [
        '#title' => t('Editable window'),
        '#description' => t('Maximum number of days after creation that the transaction can be edited. Leave blank for permanent editing.'),
        '#type' => 'number',
        '#default_value' => $config->get('window'),
        '#min' => 0
      ]
    ];
  }

  /**
   * {@inheritdoc}
  */
  public function execute(array $values) {

    //run the hooks and save the transaction
    $this->baseExecute($this->transaction, $values);

    //@todo make this string configurable
    return ['#markup' => 'The transaction has been re-saved!'];
  }

}
