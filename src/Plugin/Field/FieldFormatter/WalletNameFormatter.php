<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\WalletNameFormatter.
 *
 * @deprecated
 */

namespace Drupal\mcapi\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'worth' formatter.
 *
 * @FieldFormatter---------------------------------------(
 *   id = "wallet_name",
 *   label = @Translation("wallet name"),
 *   field_types = {
 *     "worth",
 *   }
 * )
 */
class WalletNameFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    //assuming there is only 1 wallet
    //$elements[0] = $items[0]->view();
    $wallet = reset($items->referencedEntities());
    return [0 => $wallet->label()];

    return $items->view();
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['format'] = [
      '#title' => $this->t('Generate auto name if name not given'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('auto')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      '#markup' => $this->getSetting('auto') ? $this->t('Autopopulated') : $this->t('May be blank')
    ];
  }


}
