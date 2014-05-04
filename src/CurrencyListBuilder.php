<?php

/**
 * Definition of Drupal\mcapi\CurrencyListBuilder.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of currencies
 */
class CurrencyListBuilder extends DraggableListBuilder {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'currencies_list';
  }
  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    //$header['id'] = t('Machine Name');
    $header['type'] = t('Type');
    $header['transactions'] = t('Uses');
    $header['volume'] = t('Volume');
    $header['issuance'] = t('Issuance');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListBuilder::buildRow().
   * @todo Check access on all currencies
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity->access('update')) return;//check that exchange administrator can see only the right currency
    $row['title'] = array(
      '#markup' => $this->getLabel($entity),
    );
    $type_names = array(
      CURRENCY_TYPE_ACKNOWLEDGEMENT => t('Acknowledgement'),
      CURRENCY_TYPE_EXCHANGE => t('Exchange'),
      CURRENCY_TYPE_COMMODITY => t('Commodity')
    );
    $type = $entity->issuance ? $entity->issuance : CURRENCY_TYPE_ACKNOWLEDGEMENT;
    /*
    $row['id'] = array(
      '#markup' => $entity->id,
    );
    */
    $definition = \Drupal::service('plugin.manager.mcapi.currency_type')->getDefinition($entity->type);
    $row['type'] = array(
      '#markup' => $definition['label'],
    );
    $count = $entity->transactions(array('currcode' => $entity->id()));
    //this includes deleted transactions
    $row['transactions'] = array(
      '#markup' => $count
    );

    //this includes deleted transactions
    $row['volume'] = array(
      '#markup' => $entity->format($entity->volume(array('state' => NULL)))
    );
    $row['issuance'] = array(
      '#markup' => $type_names[$type],
    );
    //TODO load mcapi.css somehow. Also see the McapiForm list controller.
    $row['#attributes']['style'] = $entity->status ? '' : 'color:#999';
    //$row['#attributes']['class'][] = $entity->status ? 'enabled' : 'disabled';
    $actions = parent::buildRow($entity);

    //make sure that a currency with transactions in the database can't be deleted.
    if ($count) {
      unset($actions['operations']['data']['#links']['delete']);
    }

    return $row + $actions;
  }


  /**
   * {@inheritdoc}
	 * ensure that the last currency can't be switched off or disabled
   */
  public function getOperations(EntityInterface $entity) {
  	$operations = parent::getOperations($entity);
    //rename the links
    if (array_key_exists('disable', $operations)) {
  	  $operations['disable']['title'] = t('Retire');
    }
  	else {
      $operations['enable']['title'] = t('Restore');
  	}
  	if (!$this->storage->deletable($entity)) {
  	  unset($operations['delete']);
  	}
  	if (!$this->storage->disablable($entity)) {
  	  unset($operations['disable']);
  	}

  	return $operations;

  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['entities']['new'] = array(
      '#weight' => 99,
    );
    $form['entities']['new']['title'] = array(
      '#type' => 'textfield',
      '#title_display' => 'invisible',
      '#title' => t('Name of currency'),
      '#size' => 15,
      '#prefix' => '<div class="label-input"><div class="add-new-placeholder">' . $this->t('Add new currency') .'</div>',
    );

    $form['entities']['new']['id'] = array(
      '#type' => 'machine_name',
      '#required' => FALSE,
      '#size' => 15,
      '#maxlength' => 32,
      '#machine_name' => array(
        'exists' => 'mcapi_currency_load',
        'source' => array('entities', 'new', 'title'),
        'standalone' => TRUE,
      ),
      '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      '#required' => TRUE
    );

    $currency_type_manager = \Drupal::service('plugin.manager.mcapi.currency_type');
    $options = array();
    foreach ($currency_type_manager->getDefinitions() as $type) {
      $options[$type['id']] = $type['label'];
    }

    $form['entities']['new']['type'] = array(
      '#type' => 'select',
      '#title_display' => 'invisible',
      '#title' => t('Currency type'),
      '#empty_option' => $this->t('- Currency Type -'),
      '#options' => $options,
      '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      '#required' => TRUE
    );

    $form['entities']['new']['issuance'] = array(
      '#markup' => '',
    );

    $form['entities']['new']['weight'] = array(
      '#type' => 'hidden',
      '#attributes' => array('class' => array('weight')),
      '#default_value' => 99,
    );

    $form['actions']['submit']['#value'] = t('Continue');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $new = $form_state['values']['entities']['new'];

    if ($new['id']) {
      if (!$new['title']) {
        form_error($form['entities']['new']['title'], $this->t('@title is required.', array('@title' => $form['entities']['new']['title']['#title'])));
      }
      if (!$new['type']) {
        form_error($form['entities']['new']['type'], $this->t('@title is required.', array('@title' => $form['entities']['new']['type']['#title'])));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $new = $form_state['values']['entities']['new'];
    if ($new['id']) {
      $values = array(
        'id' => $new['id'],
        'name' => $new['title'],
        'type' => $new['type'],
      );

      $currency = entity_create('mcapi_currency', $values);
      $currency->save();
      $form_state['redirect_route'] = array(
        'route_name' => 'mcapi.admin_currency_edit',
        'route_parameters' => array('mcapi_currency' => $currency->id())
      );
    }
  }

}
