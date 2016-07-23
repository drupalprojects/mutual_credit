<?php

namespace Drupal\mcapi\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Argument handler relating a wallet to its owner
 *
 * @ViewsRelationship("mcapi_first_wallet")
 */
class FirstWallet extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    // $options = parent::defineOptions();//produces an error if base field not in $this->definition
    // unset($options['required']);
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
    $this->definition['left_field'] = 'holder_entity_id';
    $this->realField = 'holder_entity_id';
    parent::query();
    $this->query->addWhereExpression(0, $this->tableAlias.".holder_entity_type = '".$this->definition['holder_entity_type']."'");
  }

}
