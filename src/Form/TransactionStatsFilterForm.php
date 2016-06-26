<?php

namespace Drupal\mcapi\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for action edit forms.
 */
class TransactionStatsFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transaction_stats_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['period'] = [
      '#title' => $this->t('Time period'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('All time'),
      ],
    ];
    $form['type'] = [
      '#type' => 'mcapi_types',
    ];
    $form['#method'] = 'get';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
