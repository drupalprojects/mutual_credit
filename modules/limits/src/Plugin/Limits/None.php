<?php

namespace Drupal\mcapi_limits\Plugin\Limits;

use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi_limits\Plugin\McapiLimitsBase;

/**
 * No balance limits.
 *
 * @Limits(
 *   id = "none",
 *   label = @Translation("None"),
 *   description = @Translation("No limits")
 * )
 */
class None extends McapiLimitsBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [
      'empty' => [
        '#markup' => $this->t('There are no settings for this plugin'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkLimits(WalletInterface $wallet, $diff) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseLimits(WalletInterface $wallet) {
    return ['min' => NULL, 'max' => NULL];
  }

  /**
   * {@inheritdoc}
   */
  public function getLimits(WalletInterface $wallet) {
    return [
      'min' => NULL,
      'max' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

}
