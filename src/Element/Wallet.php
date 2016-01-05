<?php

/**
 * @file
 * Contains \Drupal\mcapi\Element\Wallet
 */

namespace Drupal\mcapi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;

/**
 * Provides a widget to select wallet references by name, using autocomplete
 * This field can ONLY be used on fields called payer and payee; 
 * it reads the fieldname to know what direction it is going in
 *
 * @FormElement("wallet_entity_auto")
 *
 */
class Wallet extends EntityAutocomplete {

  
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#target_type'] = 'mcapi_wallet';
    return $info;
  }
 
  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Process the #default_value property.
    if ($input === FALSE && isset($element['#default_value']) && $element['#process_default_value']) {
      if (is_array($element['#default_value']) && $element['#tags'] !== TRUE) {
        throw new \InvalidArgumentException('The #default_value property is an array but the form element does not allow multiple values.');
      }
      elseif (!is_array($element['#default_value'])) {
        // Convert the default value into an array for easier processing in
        // static::getEntityLabels().
        $element['#default_value'] = array($element['#default_value']);
      }

      if ($element['#default_value'] && !(reset($element['#default_value']) instanceof EntityInterface)) {
        throw new \InvalidArgumentException('The #default_value property has to be an entity object or an array of entity objects.');
      }
print_r($element['#default_value']);die('valueCallback');
      // Extract the labels from the passed-in entity objects, taking access
      // checks into account.
      return static::getEntityLabels($element['#default_value']);
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public static function __extractEntityIdFromAutocompleteInput($input) {
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
  public static function getEntityLabels(array $entities) {die('getEntityLabels GOOD');
    $wallet_labels = [];
    foreach ($entities as $wallet) {
      $label = ($wallet->access('view')) ? 
        $wallet->label() : 
        $this->t('- Restricted access -');
      // Labels containing commas or quotes must be wrapped in quotes.
      $wallet_labels[] = Tags::encode($label)  . ' #' . $wallet->wid->value;
    }
    return implode(', ', $wallet_labels);
  }



}
