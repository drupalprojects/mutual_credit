<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Wallet.
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;
//use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Template\Attribute;
use Drupal\mcapi\Exchanges;

/**
 * Provides a widget to select wallets, using autocomplete
 *
 * @FormElement("select_wallet")
 * 
 * @todo make this inherit what can be inherited from EntityAutocomplete
 */
class Wallet extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    $info['#exchanges'] = [];//todo move this to exchanges module
    $info['#autocomplete_route_name'] = 'mcapi.wallets.autocomplete';
    $info['#process'][0] = [$class, 'processEntityAutocomplete'];
    //$element['#prerender'][] = array($class, 'prerender_wallet_field');
    $info['#element_validate'][] = [$class, 'validateEntityAutocomplete'];
    $info['#required'] = TRUE;
    $info['#multiple'] = FALSE;
    //TODO this causes an error in alpha 15 which isn't all expecting attribute objects yet
    //$info['#attributes'] = new Attribute(array('style' => "width:100%"));
    return $info;
  }

  /**
   * process callback
   */
  static function processEntityAutocomplete($element, FormStateInterface $form_state) {
    $element['#placeholder'] = t('Wallet name or #number');
    $element['#autocomplete_route_parameters'] = [
      'role' => $element['#role'] == 'payer' ? 'payout' : 'payin',
    ];
    return $element;
  }

  /**
   * value callback
   * takes from the autocomplete select_wallet field and returns an integer candidate wallet id.
   */
  static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!is_array($element['#default_value'])) {
        // Convert the default value into an array for easier processing in
        // static::getEntityLabels().
        $element['#default_value'] = array($element['#default_value']);
      }
      return static::getEntityLabels($element['#default_value']);
    }
    elseif (is_numeric($input) && is_integer($input + 0)) {
      return $input;
    }
    //get the number after the last hash
    return substr($input, strrpos($input, '#')+1);
  }

  /**
   * element_validate callback for select_wallet
   * ensure the passed value is a wallet id, not of an intertrading wallet
   */
  static function validateEntityAutocomplete(&$element, FormStateInterface $form_state, array &$complete_form) {
    $input = $element['#value'];
    if (!empty($input)) {
      if (is_numeric($input)) {
        $wallet = \Drupal\mcapi\Entity\Wallet::load($input);
        if (!$wallet) {
          $form_state->setError(
            $element,
            $this->t('Invalid wallet id: @value', ['@value' => $input])
          );
        }
        else {
          $result[] = ['target_id' => $input];
        }
      }
      else {
        $options = array(
          'target_type' => $element['#target_type'],
          'handler' => $element['#selection_handler'],
          'handler_settings' => $element['#selection_settings'],
        );
        foreach (Tags::explode($input) as $string) {
          $match = static::extractEntityIdFromAutocompleteInput($string);
          if ($match === NULL) {
            // Try to get a match from the input string when the user didn't use
            // the autocomplete but filled in a value manually.
            $form_state->setError(
              $element, 
              $this->t('Unable to identify wallet: @string', ['@string' => $string])
            );
          }
          if ($match !== NULL) {
            $result[] = array(
              'target_id' => $match,
            );
          }
        }
        // Use only the last value if the form element does not support multiple
        // matches
        if (!$element['#multiple'] && !empty($result)) {
          $result = array_slice($result, -1);
        }
      }
    }
    $form_state->setValueForElement($element, $result);
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
   * element_prerender callback for select_wallet
   *
   * convert the #default value from a wallet id to the autocomplete format
   * ensure the autocomplete address is going to the right place
   * @todo remove this
   */
  static function prerender_wallet_field($element) {
    if (is_numeric($element['#default_value'])) {
      $wallet = Wallet::load($element['#default_value']);
      if ($wallet) {
        //this label contains the #id which is picked up by the value callback
        $element['#default_value'] = $wallet->label(NULL, FALSE);
      }
      else {
        drupal_set_message($this->t("Wallet @num does not exist", array('@num' => $element['#default_value'])), 'warning');
      }
    }
    return $element;
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
  public static function getEntityLabels(array $wids) {
    $entity_labels = array();
    $entities = \Drupal\mcapi\Entity\Wallet::load($wids);
    foreach ($entities as $entity) {
      $label = ($entity->access('view')) ? $entity->label() : t('- Restricted access -');

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($entity->label());
    }

    return implode(', ', $entity_labels);
  }

}
