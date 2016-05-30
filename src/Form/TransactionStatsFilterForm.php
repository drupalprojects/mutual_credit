<?php

/**
 * @file
 * Contains \Drupal\mcapi\Form\TransactionStatsFilterForm.
 */

namespace Drupal\mcapi\Form;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a form for action edit forms.
 */
class TransactionStatsFilterForm extends \Drupal\Core\Form\FormBase {

  function getFormId() {
    return 'transaction_stats_filter';
  }

  function buildForm(array $form, FormStateInterface $form_state) {
    $form['period'] = [
      '#title' => $this->t('Time period'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('All time')
      ]
    ];
    $form['type'] = [
      '#type' => 'mcapi_types'
    ];
    $form['#method'] = 'get';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    ;
  }

}
