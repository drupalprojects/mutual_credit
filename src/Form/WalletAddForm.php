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
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WalletAddForm extends Formbase {

  private $holder;
  private $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wallet_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($route_match, $entity_manager) {
    $this->holder = $entity_manager
      ->getStorage($route_match->getParameters()->getIterator()->key())
      ->load($route_match->getParameters()->getIterator()->current());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return
      t("New wallet for @entity_type '!title'",
      array(
       '@entity_type' => $this->holder->getEntityType()->getLabel(),
       '!title' => $this->holder->label()
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
      '#value' => $this->holder->getEntityTypeId()
    );
    $form['pid'] = array(
    	'#type' => 'value',
      '#value' => $this->holder->id()
    );
/*
    foreach ($this->pluginManager->getDefinitions() as $def) {
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
    */
    $form['submit'] = array(
    	'#type' => 'submit',
      '#value' => t('Create')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    //just check that the name isn't the same
    //if there was a wallet storage controller this unique checking would happen there.
    $values = $form_state->getValues();
    $query = db_select('mcapi_wallet', 'w')
    ->fields('w', array('wid'))
    ->condition('name', $values['name']);

    if (!\Drupal::config('mcapi.settings')->get('unique_names')) {
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
    $form_state->setRedirectUrl($wallet->getHolder()->urlInfo());
  }

}

