<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Wallet.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Template\Attribute;

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
    $class = get_class($this);
    $info['#autocomplete_route_name'] = 'mcapi.wallets.autocomplete';
    $info['#autocomplete_route_parameters']['role'] = 'null';
    $info['#required'] = TRUE;
    return $info;
  }

  /**
   * process callback
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (isset($element['#role'])) {
      $element['#autocomplete_route_parameters']['role'] = $element['#role'] == 'payer'
        ? 'payout'
        : 'payin';
    }
    //this should go in getInfo() but somehow from there it is overridden
    $element['#placeholder'] = t('Wallet name or #number');
    return $element;
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
                $this->t('Unable to identify wallet: @string', ['@string' => $string])
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
   * Extracts the entity ID from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;
    // Take "label (#entity id)', match the ID from # when it's a number
    if (preg_match("/.+\#(\d+)/", $input, $matches)) {
      $match = $matches[1];
    }
    return $match;
  }

  /**
   * Converts an array of entity objects into a string of entity labels.
   *
   * This method is also responsible for checking the 'view' access on the
   * passed-in entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects.
   *
   * @return string
   *   A string of entity labels separated by commas.
   */
  public static function walletNames(array $wids) {
    $entity_labels = array();
    $entities = \Drupal\mcapi\Entity\Wallet::load($wids);
    foreach ($entities as $entity) {
      if ($entity->access('view')) {
        // Labels containing commas or quotes must be wrapped in quotes.
        $entity_labels[] = Tags::encode($entity->label());
      }
    }

    return implode(', ', $entity_labels);
  }


  /**
   * {@inheritdoc}
   */
  public static function getEntityLabels(array $entities) {
    $entity_labels = array();
    foreach ($entities as $entity) {
      $label = ($entity->access('view')) ? $entity->label() : t('- Restricted access -');

      // Take into account "autocreated" entities.
      if (!$entity->isNew()) {
        $label .= ' #' . $entity->id();
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($label);
    }
    return implode(', ', $entity_labels);
  }

}
