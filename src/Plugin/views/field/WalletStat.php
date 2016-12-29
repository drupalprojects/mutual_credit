<?php

namespace Drupal\mcapi\Plugin\views\field;

use Drupal\mcapi\Element\WorthsView;
use Drupal\mcapi\Storage\WalletStorage;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Field handler provides current stat for given wallet via Wallet::getStatAll.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wallet_stat")
 *
 * @note for aggregated worths, see src/Plugin/views/query/Sql::getAggregationInfo()
 */
class WalletStat extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['context'] = ['default' => TRUE];
    return $options;
  }

  /**
   * Default options form that provides the label widget that all fields
   * should have.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['context'] = [
      '#type' => 'radios',
      '#title' => $this->t('Worth view context'),
      '#options' => WorthsView::options(),
      '#default_value' => $this->options['context'],
      '#weight' => 10,
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    if ($entity->getEntityTypeId() != 'mcapi_wallet') {
      $entities = WalletStorage::walletsOf($entity, TRUE);
      $wallet = reset($entities);
    }
    else {
      $wallet = $entity;
    }
    if ($wallet) {
      $stat = $this->definition['stat'];
      $vals = $wallet->getStatAll($stat);
      switch ($stat) {
        // Worth value
        case 'volume':
        case 'incoming':
        case 'outgoing':
        case 'balance':
          return [
            '#type' => 'worths_view',
            '#worths' => $vals,
            '#context' => $this->options['context']
          ];
        // This is an integer
        case 'trades':
        case 'partners':
          return $val;
      }
    }
    return 'none';
  }

}
