<?php

/**
 * Definition of Drupal\mcapi_1stparty\FirstPartyEditFormListBuilder.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Url;
use Drupal\mcapi_exchanges\Entity\Exchange;//only if enabled!
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
    $header['transaction_type'] = t('Workflow');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
      $style = array('style' => $entity->status ? '' : 'color:#999');
      //$class = array('style' => $entity->status ? 'enabled' : 'disabled');
      //@todo make a link out of this
      $name = $entity->access('blah') ?
        \Drupal::l($entity->label(), Url::fromRoute('mcapi.1stparty.'.$entity->id())) :
        $entity->label();
      $row['title'] = $style + array(
        'data' => $name
      );
      $row['transaction_type'] = $style + array(
        'data' => array('#markup' => $entity->type)
      );

      //@todo load mcapi.css somehow to show the disabled forms in gray using ths class above.
      //Also see the McapiForm listBuilder.
      return $row + parent::buildRow($entity);

  }


  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'firstparty_form_list';
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
