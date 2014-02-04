<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class OperationForm extends EntityConfirmFormBase {

  private $op;
  private $configuration;
  private $transaction;
  private $destination;

  function __construct() {
    $parameters = \Drupal::request()->attributes;
    $this->op = $parameters->get('op') ? : 'view';
    $this->configuration = $this->config('mcapi.operation.'.$this->op);

    if ($path = $this->configuration->get('redirect')) {
      $this->destination = $path;
    }
    else {
      $request = \Drupal::request();
      if ($request->query->has('destination')) {
        $this->destination = $request->query->get('destination');
        $request->query->remove('destination');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'transaction_operation_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->configuration->get('page_title');
  }

  /**
   * we could add this to the plugin options.
   * although of course users don't know route names so there would be some complexity
   * How do we go back
   */
  public function getCancelRoute() {
    //@todo GORDON
    //on the 'create' operation we can't very well go 'back' to step 1 can we?
    //we don't even know the previous page.
    if ($serial = $this->entity->get('serial')->value) {
      return array(
        'route_name' => 'mcapi.transaction_view',
        'route_parameters' => array('mcapi_transaction' => $serial)
      );
    }
    else {
      return array(
        'route_name' => 'user.page',
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->configuration->get('cancel_button') ? : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    //this provides the transaction_view part of the form as defined in the operation settings
    if ($this->configuration->get('format') == 'twig') {
      //ah but where to get the $tokens from
      //maybe this should just be a feature of the template_preprocess_mcapi_transaction()
      module_load_include('inc', 'mcapi');
      return mcapi_render_twig_transaction($this->configuration->get('twig'), $this->entity, FALSE);
    }
    else {//this is a transaction entity display mode, like 'certificate'
      $mode = $this->configuration->get('format') == 'certificate' ? 'certificate' : $this->configuration->get('twig');
      $renderable = \Drupal::entityManager()->getViewBuilder('mcapi_transaction')
      ->view($this->entity, $mode);
      $renderable['#showlinks'] = FALSE;
      return drupal_render($renderable);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    //this may be an entity form but we don't want the FieldAPI fields showing up.
    foreach (element_children($form) as $fieldname) {
      if (array_key_exists('#type', $form[$fieldname]) && $form[$fieldname]['#type'] == 'container') {
        unset($form[$fieldname]); //should do it;
      }
    }

    //add any extra form elements as defined in the operation plugin.
    $form += transaction_operations($this->op)->form($this->entity, $this->configuration);
    if ($this->op == 'view') {
      unset($form['actions']);
    }
    return $form;

    //this form will submit and expect ajax
    //TODO this doesn't work in D8
    //$form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    //TODO I don't know if those are needed
    $form['#attached']['js'][] = 'core/misc/jquery.form.js';
    $form['#attached']['js'][] = 'core/misc/ajax.js';


    //Left over from d7
     //when the button is clicked replace the whole transaction div with the results.
     $commands[] = ajax_command_replace('#transaction-'.$transaction->serial, drupal_render($form));
     return array(
      '#type' => 'ajax',
      '#commands' => $commands
     );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {

    $plugin = transaction_operations($this->op);
    $transaction = $this->entity;

    $old_state = $transaction->get('state')->value;

    //the op might have injected values into the form, so it needs to be able to access them
    form_state_values_clean($form_state);
    $result = $plugin->execute($transaction, $form_state['values'])
      or $result = 'operation returned nothing renderable';

    $context = array(
      'op_plugin_id' => $this->op,
      'old_state' => $old_state,
      'config' => $this->configuration,//not sure if there is a use-case for passing this
    );
    //this is especially for invoking rules
    $this->moduleHandler->invokeAll('mcapi_transaction_operated', array($transaction, $context));


    if ($message = $this->configuration->get('message')) {
      //@todo does dsm even show up here?
      drupal_set_message($message);
    }
    //@todo all the three ways of showing the form, and how each one moves to the next.

    //if ($this->configuration->get('format2') == 'redirect') {
    if ($path = $this->destination) {
      $form_state['redirect'] = $path;
    }
    else {//default is to redirect to the transaction itself
      //might not be a good idea for undone transactions
      $form_state['redirect_route'] = array(
          'route_name' => 'mcapi.transaction_view',
          'route_parameters' => array('mcapi_transaction' => $transaction->get('serial')->value)
      );
    }
    //}

    //but since rules isn't written yet, we're going to use the operation settings to send a notification.
    if ($this->configuration->get('send')) {
      $recipients = array();
      //mail is sent to the user owners of wallets, and to cc'd people
      foreach ($transaction->endpoints() as $wallet) {
        $entity = $wallet->getOwner();
        if (!$entity) continue; //in case of system wallets
        switch($entity->entityType()) {
          //we may need to handle more entityTypes here
          case 'mcapi_wallet':
            $recipients[] = \Drupal::config('system.site')->get('mail');
            continue;
          case 'user':
            $recipients[] = $entity->get('mail')->value;
            continue;
          default://exchanges and nodes
            if (property_exists($entity, 'uid')) {
              $recipients[] = user_load($entity->get('uid')->value)->get('mail')->value;
            }
        }
      }
    }
    if ($recipients) {
      drupal_mail(
        'mcapi',
        'operation',
        implode(',', $recipients),
        entity_load('mcapi_exchange', $transaction->get('exchange')->value)->getlangcode,
        array(
        	'mcapi' => $transaction,
        	'cc' => $this->configuration->get('cc'),
        	'subject' => $this->configuration->get('subject'),
        	'body' => $this->configuration->get('body')
        )
      );
    }

    return $result;
  }

}

