<?php

/**
 * @file
 * Definition of Drupal\mcapi\Form\WalletAddForm.
 * Add a new wallet from url parameters
 */

namespace Drupal\mcapi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Form\FormStateInterface;

class WalletAddForm extends Formbase {

  public function getFormId() {
    return 'wallet_add_form';
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = RouteMatch::createFromRequest($request)->getParameters()->all();
    list($entity_type, $id) = each($params);
    $owner = \Drupal::EntityManager()->getStorage($entity_type)->load($id);

    drupal_set_title(t("New wallet for '!title'", array('!title' => $owner->label())));

    $form['wid'] = array(
      '#type' => 'value',
      '#value' => NULL,
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name or purpose of wallet'),
      '#default_value' => '',
    );
    $form['entity_type'] = array(
    	'#type' => 'value',
      '#value' => $owner->getEntityTypeId()
    );
    $form['pid'] = array(
    	'#type' => 'value',
      '#value' => $owner->id()
    );
    $pluginManager = \Drupal::service('plugin.manager.mcapi.wallet_access');

    foreach ($pluginManager->getDefinitions() as $def) {
      $plugins[$def['id']] = $def['label'];
    }

    $form['access'] = array(
      '#title' => t('Acccess settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      'viewers' => array(
    	  '#title' => t('Who can view?'),
        '#type' => 'select',
        '#options' => $plugins
      ),
      'payees' => array(
    	  '#title' => t('Who can request from this wallet?'),
        '#type' => 'select',
        '#options' => $plugins
      ),
      'payers' => array(
    	  '#title' => t('Who can contribute to this wallet?'),
        '#type' => 'select',
        '#options' => $plugins
      )
    );
    $form['submit'] = array(
    	'#type' => 'submit',
      '#value' => t('Create')
    );
    return $form;
  }

  function validateForm(array &$form, FormStateInterface $form_state) {
    //just check that the name isn't the same
    //if there was a wallet storage controller this unique checking would happen there.
    $values = $form_state->getValues();
    $query = db_select('mcapi_wallet', 'w')
    ->fields('w', array('wid'))
    ->condition('name', $values['name']);

    if (!\Drupal::config('mcapi.wallets')->get('unique_names')) {
      $query->condition('pid', $values['pid']);
      $query->condition('entity_type', $values['entity_type']);
    }
    if ($query->execute()->fetchField()) {
      $form_state->setErrorByName(
        'name',
        t("The wallet name '!name' is already used.", array('!name' => $values['name']))
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();;
    $wallet = Wallet::create($form_state->getValues());
    $wallet->save();
    $pid = $wallet->get('pid')->value;
    $entity_type = $wallet->get('entity_type')->value;
    $route_name = \Drupal::entityManager()
      ->getStorage($entity_type)
      ->load($pid)
      ->getLinkTemplate('canonical');
    debug("redirecting to route $route");

    $form_state->setRedirect($route_name, array($entity_type => $pid));
  }

}

