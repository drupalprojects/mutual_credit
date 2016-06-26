<?php

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi_limits\Plugin\McapiLimitsBase;

/**
 * No balance limits.
 *
 * @Limits(
 *   id = "explicit",
 *   label = @Translation("Explicit"),
 *   description = @Translation("Explicit limits")
 * )
 */
class Explicit extends McapiLimitsBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $subform = [
      'min' => [
        '#title' => $this->t('Min'),
        '#description' => t('Leave blank for no minimum limit'),
        '#type' => 'worth_form',
        '#weight' => 0,
        '#default_value' => $this->configuration['min'] ? $this->configuration['min']['value'] : '',
        '#allowed_curr_ids' => [$this->currency->id],
        '#config' => TRUE,
        '#minus' => TRUE,
      ],
      'max' => [
        '#title' => $this->t('Max'),
        '#description' => t('Leave blank for no maximum limit'),
        '#type' => 'worth_form',
        '#weight' => 1,
        '#default_value' => $this->configuration['max'] ? $this->configuration['max']['value'] : '',
        '#allowed_curr_ids' => [$this->currency->id],
        '#config' => TRUE,
      ],
    ];

    $subform += parent::buildConfigurationForm($form, $form_state);
    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimits(WalletInterface $wallet) {
    // Format is inelegant because it is saved directly from the minmax widget.
    return [
      'min' => $this->configuration['min']['value'],
      'max' => $this->configuration['max']['value'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    return $defaults + [
      'min' => ['value' => -1000],
      'max' => ['value' => 1000],
    ];
  }

}
