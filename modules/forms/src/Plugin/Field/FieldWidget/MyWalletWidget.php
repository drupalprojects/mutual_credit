<?php

namespace Drupal\mcapi_forms\Plugin\Field\FieldWidget;

use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the wallet selector widget.
 *
 * @FieldWidget(
 *   id = "my_wallet",
 *   label = @Translation("My wallet"),
 *   description = @Translation("Choose from the current user's wallets"),
 *   field_types = {
 *     "wallet_reference"
 *   }
 * )
 * @todo inject current user
 */
class MyWalletWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'hide_one_wallet' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['size']);
    $element['hide_one_wallet'] = [
      '#title' => $this->t('Hide this field if there is only one wallet available.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hide_one_wallet'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // Size.
    unset($summary[1]);
    $message = $this->getSetting('hide_one_wallet') ?
      $this->t('Hide widget for one wallet') :
      $this->t('Show widget for one wallet');
    $summary['hide_one'] = ['#markup' => $message];
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @see mcapi_field_widget_wallet_reference_autocomplete_form_alter
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // My wallets are the ones owned by me plus the ones I'm burser of.
    $wallet_ids = \Drupal::entityTypeManager()
      ->getStorage('mcapi_wallet')
      ->myWallets(\Drupal::currentUser()->id()
    );
    $count = count($wallet_ids);
    if (!$count) {
      drupal_set_message($this->t('No wallets to show for @role', ['@role' => $this->fieldDefinition->getLabel()]), 'error');
      $form['#disabled'] = TRUE;
      return [];
    }
    if ($count == 1) {
      $wid = reset($wallet_ids);
      $element['#value'] = $wid;
      if ($this->getSetting('hide_one_wallet')) {
        $element['#type'] = 'value';
      }
      else {
        $element['#type'] = 'item';
        $element['#markup'] = Wallet::load($wid)->label();
      }
    }
    else {
      $element['#type'] = 'radios';
      foreach ($wallet_ids as $wid) {
        $element['#options'][$wid] = Wallet::load($wid)->label();
        $element['#pre_render'] = [];
      }
    }
    return ['target_id' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

}
