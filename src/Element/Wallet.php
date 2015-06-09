<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Wallet.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;

/**
 * Provides a widget to select wallets, using autocomplete
 *
 * @FormElement("select_wallet")
 *
 */
class Wallet extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    array_shift($info['#process']);//get rid of callback Self::processEntityAutocomplete
    return [
      '#autocomplete_route_name' => 'mcapi.wallets.autocomplete',
      '#autocomplete_route_parameters' => ['role' => 'null'],
      //'#target_type' => 'mcapi_wallet'
    ] + $info;
  }

  /**
   * element_validate callback for select_wallet
   * ensure the passed value is a wallet id, not of an intertrading wallet
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;
    if (!empty($element['#value'])) {
      $input_values = $element['#tags']
        ? Tags::explode($element['#value'])
        : array($element['#value']);
      foreach ($input_values as $input) {
        if (!empty($input)) {
          foreach (Tags::explode($input) as $string) {
            $match = static::extractEntityIdFromAutocompleteInput($string);
            if ($match === NULL) {
              // Try to get a match from the input string
              $form_state->setError(
                $element,
                t('Unable to identify wallet: @string', ['@string' => $string])
              );
            }
            else {
              $value[] = ['target_id' => $match];
            }
          }
        }
        // Check that the referenced entities are valid, if needed.
        if ($element['#validate_reference'] && !empty($value)) {
          $ids = array_reduce($value, function ($return, $item) {
            if (isset($item['target_id'])) {
              $return[] = $item['target_id'];
            }
            return $return;
          });
          if ($ids) {
            $valid_ids = array_keys(\Drupal\mcapi\Entity\Wallet::loadMultiple($ids));
            if ($invalid_ids = array_diff($ids, $valid_ids)) {
              foreach ($invalid_ids as $invalid_id) {
                $form_state->setError($element, t("The wallet '%id' does not exist.", ['%id' => $invalid_id]));
              }
            }
          }
        }
        // Use only the last value if the form element does not support multiple
        // matches (tags).
        if (!$element['#tags'] && !empty($value)) {
          $last_value = $value[count($value) - 1];
          $value = isset($last_value['target_id']) ? $last_value['target_id'] : $last_value;
        }
      }
    }
    $form_state->setValueForElement($element, $value);
  }


  /**
   * {@inheritdoc}
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;
    // Take "label #entity id', match the ID from number after #
    if (preg_match("/.*\#(\d+)/", $input, $matches)) {
      $match = $matches[1];
    }
    return $match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityLabels(array $entities) {
    $wallet_labels = [];
    foreach ($entities as $wallet) {
      $label = ($wallet->access('view')) ? $wallet->label() : t('- Restricted access -');
      // Labels containing commas or quotes must be wrapped in quotes.
      $wallet_labels[] = Tags::encode($label)  . ' #' . $wallet->wid->value;
    }
    return implode(', ', $wallet_labels);
  }

}
