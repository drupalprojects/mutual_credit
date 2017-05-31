<?php

namespace Drupal\mcapi\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Argument handler relating a holder entity to a wallet it holds
 *
 * @ViewsRelationship("mcapi_wallet_owner")
 *
 */
class WalletOwner extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return [];
  }


  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['required']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    $this->query->addWhereExpression(0, "$this->tableAlias.holder_entity_type = '".$this->definition['holder_entity_type']."'");
  }

}
