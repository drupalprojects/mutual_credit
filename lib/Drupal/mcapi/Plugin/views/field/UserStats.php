<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\field\UserStats
 */

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("mcapi_userstat")
 */
class UserStats extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = array('default' => '');
    $options['currencies'] = array('default' => array());
    $options['stat'] = array('default' => 'balance');
    return $options;
  }

  /**
   * Provide link to taxonomy option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['separator'] = array(
      '#title' => t('Separator between different stats'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->options['separator'],
    );
    $form['currencies'] = array(
      '#title' => t('Currencies'),
      '#title_display' => '#before',
      '#description' => t('Select none to see all the currencies the user can use'),
      '#type' => 'mcapi_currency',
      '#multiple' => TRUE,
      '#default_value' => $this->options['currencies'],
    );
    $form['stat'] = array(
    	'#title' => t('Metric'),
    	'#type' => 'radios',
    	'#options' => array(
    	  'balance' => t('Balance'),
    	  'gross_in' => t('Gross income'),
    	  'gross_out' => t('Gross expenditure'),
    	  'volume' => t('Volume (Gross in + gross out)'),
    	  'trades' => t('Balance'),
    	  'partners' => t('Number of trading partners'),
      ),
    	'#default_value' => $this->options['stat'],
    );
    parent::buildOptionsForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  function render(ResultRow $values) {
  	$account = $this->getEntity($values);
  	if (empty($this->options['currencies'])) {
  		$this->options['currencies'] = mcapi_get_available_currencies($account);
  	}
  	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');

  	foreach ($this->options['currencies'] as $currcode) {
  		$currency = currency_load($currcode);
    	$result = $storage->summaryData($account, $currency, array());
  		if (in_array($this->options['stat'], array('trades', 'partners'))) {
  			return $result[$this->options['stat']];
  		}
  		else return 'theme this: ' .$result[$this->options['stat']];
  	}
  }

}
