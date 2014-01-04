<?php

/**
 * @file
 * Contains interface for Drupal\mcapi\CurrencyTypeInterface.
 */

namespace Drupal\mcapi;

interface CurrencyTypeInterface {

  //note that the settings are actually saved in the currency entity
  public function settingsForm(array $form, array &$form_state, CurrencyInterface $currency);

  public function format($quant, array $settings);

}