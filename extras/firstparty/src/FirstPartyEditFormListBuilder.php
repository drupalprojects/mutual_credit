<?php

/**
 * Definition of Drupal\mcapi_1stparty\FirstPartyEditFormListBuilder.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\mcapi\Entity\Exchange;
use Drupal\mcapi_1stparty\Entity\FirstPartyFormDesign;

/**
 * Provides a listing of contact categories.
 */
class FirstPartyEditFormListBuilder extends ConfigEntityListBuilder{

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['exchange'] = t('Exchange');
    $header['transaction_type'] = t('Workflow');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    if (\Drupal::currentUser()->hasPermission('configure mcapi') ||
      Exchange::load($entity->exchange)->is_member() ) {
      $style = array('style' => $entity->status ? '' : 'color:#999');
      //$class = array('style' => $entity->status ? 'enabled' : 'disabled');
      //TODO make a link out of this
      $row['title'] = $style + array(
        'data' => $entity->link()
      );
      $exchange = $entity->exchange ? Exchange::load($entity->exchange) : NULL;
      $row['exchange'] = $style + array(
        'data' => array(
          '#markup' => $exchange ? $exchange->label() : t('- All -')
        )
      );

      $row['transaction_type'] = $style + array(
        'data' => array('#markup' => $entity->type)
      );

      //TODO load mcapi.css somehow to show the disabled forms in gray using ths class above.
      //Also see the McapiForm listBuilder.
      return $row + parent::buildRow($entity);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function __render() {
    // @todo make this list filter by exchange, like on admin/structure/views
    // views has its own javascript though, so maybe not so simple
    $build['list'] = array(
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => $this->t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
    );
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['list']['#rows'][$entity->id()] = $row;
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'exchanges_list';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, $form_state) {
    $editform = FirstPartyFormDesign::create($form_state->getValues());
    $editform->save();
    $form_state->setRedirect('mcapi.admin_1stparty_editform.edit', array('1stparty_editform' => $editform->id()));
  }

}
