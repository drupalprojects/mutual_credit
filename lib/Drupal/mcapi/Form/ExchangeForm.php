<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\ExchangeForm.
 * Edit all the fields on an exchange
 *
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Entity\ContentEntityFormController;
use Drupal\Core\Language\Language;

class ExchangeForm extends ContentEntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);

    $exchange = $this->entity;

    $form['id'] = array(
    	'#type' => 'value',
      '#value' => $exchange->id()
    );

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Full name'),
      '#default_value' => $exchange->name->value,
    );
    //@todo how to decide who to select exchange managers from?
    //really it could be any user IN that exchange, although the exchange has no members right now....
    $form['uid'] = array(
      '#title' => t('Manager of the exchange'),
      '#type' => 'entity_chooser',
      '#plugin' => 'role',
      '#args' => array('authenticated'),
      '#default_value' => $exchange->uid->value,
    );
    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Site language'),
      '#languages' => Language::STATE_CONFIGURABLE,
      '#default_value' => $exchange->langcode,
      '#description' => t('The first language of the exchange'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $entity = $this->entity;
    $status = $entity->save();
    //either SAVED_UPDATED or SAVED_NEW

    $form_state['redirect_route'] = array(
      'route_name' => 'mcapi.exchange.view',
      'route_parameters' => array('mcapi_exchange' => $entity->id())
    );
  }
}

