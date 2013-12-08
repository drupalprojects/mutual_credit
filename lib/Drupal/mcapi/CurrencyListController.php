<?php

/**
 * Definition of Drupal\mcapi\CurrencyListController.
 */

namespace Drupal\mcapi;

use Drupal\Core\Config\Entity\DraggableListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of contact categories.
 */
class CurrencyListController extends DraggableListController {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'currencies_list';
  }
  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildHeader().
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['id'] = t('Machine Name');
    $header['type'] = t('Type');
    $header['issuance'] = t('Issuance');
    return $header + parent::buildHeader();
  }

  /**
   * Overrides Drupal\Core\Entity\EntityListController::buildRow().
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      '#markup' => $this->getLabel($entity),
    );
    $type_names = array(
      CURRENCY_TYPE_ACKNOWLEDGEMENT => t('Acknowledgement'),
      CURRENCY_TYPE_EXCHANGE => t('Exchange'),
      CURRENCY_TYPE_COMMODITY => t('Commodity')
    );
    $type = $entity->issuance ? $entity->issuance : CURRENCY_TYPE_ACKNOWLEDGEMENT;
    $row['id'] = array(
      '#markup' => $entity->id,
    );

    $definition = \Drupal::service('plugin.manager.mcapi.currency_type')->getDefinition($entity->type);
    $row['type'] = array(
      '#markup' => $definition['label'],
    );
    $row['issuance'] = array(
      '#markup' => $type_names[$type],
    );
    //TODO load mcapi.css somehow. Also see the McapiForm list controller.
    $row['#attributes']['style'] = $entity->status ? '' : 'color:#999';
    //$row['#attributes']['class'][] = $entity->status ? 'enabled' : 'disabled';

    return $row + parent::buildRow($entity);
  }


  /**
   * {@inheritdoc}
	 * ensure that the last currency can't be switched off or disabled
   */
  public function getOperations(EntityInterface $entity) {
  	$operations = parent::getOperations($entity);
  	static $done = 0;
  	//we only need to run this on the first currency
  	if (!$done) {
	  	$count = 0;
	  	foreach ($this->storage->loadMultiple() as $entity) {
	  		if ($entity->status)$count++;
	  	}
	  	if ($count < 2) {
	  		unset($operations['delete'], $operations['disable']);
	  	}
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
      '#size' => 30,
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
      '#empty_option' => $this->t('- Select type of Currency -'),
      '#options' => $options,
      '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
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

      $currency = entity_create('mcapi_currencies', $values);
      $currency->save();

      $form_state['redirect'] = 'admin/accounting/currencies/' . $currency->id();
    }
  }
}