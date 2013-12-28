<?php

namespace Drupal\mcapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

//I don't know if it is a good idea to extend the confirm form if we want ajax.
class OperationForm extends ConfirmFormBase {

  private $op;
  private $settings;
  private $specific_settings;
  private $transaction;

  function __construct() {
    //yuk getting the parameters this way
    $parameters = \Drupal::request()->attributes;
    $this->transaction = $parameters->get('mcapi_transaction');
    $this->op = $parameters->get('op');
    $config = $this->config('mcapi.operation.'.$this->op);
    $this->settings = $config->get('general');
    $this->specific_settings = $config->get('specific');
  }
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'transaction_operation_form_id';
  }

  public function getQuestion() {
    return $this->settings['page_title'];
  }


  public function getCancelRoute() {
    return array(
    	'route_name' => 'mcapi.transaction_view',
      'route_parameters' => array('mcapi_transaction' => $this->transaction->serial->value)
    );
  }

  public function getCancelText() {
    return $this->settings['cancel_button'];
  }


  public function getDescription() {
    if ($this->settings['format'] == 'twig') {
      //ah but where to get the $tokens from
      //maybe this should just be a feature of the template_preprocess_mcapi_transaction()
      module_load_include('inc', 'mcapi');
      return mcapi_render_twig_transaction($this->settings['twig'], $this->transaction);
    }
    else {//this is a transaction entity display mode, like 'certificate'
      $renderable = \Drupal::entityManager()
        ->getViewBuilder('mcapi_transaction')
        ->view(
          $this->transaction,
          $this->settings['format'] == 'certificate' ? 'certificate' : $this->settings['twig']
      );
      unset($renderable['links']);
      return drupal_render($renderable);
    }
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    //this might work just inheriting from the confirmFormBase,
    //except perhaps for all the dialoge boxes and ajax

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
  public function submitForm(array &$form, array &$form_state, $op = NULL) {

    $plugin = transaction_operations($this->op);

    $old_state = $this->transaction->state;

    //the op might have injected values into the form, so it needs to be able to access them
    form_state_values_clean($form_state);
    $result = $plugin->execute($this->transaction, $form_state['values'])
      or $result = 'operation returned nothing renderable';

  	//and invoke trigger and rules using the changed transaction
  	mcapi_transaction_operated($this->op, $this->transaction, $old_state);
  	$uri = $this->transaction->uri();
  	$form_state['redirect'] = $uri['path'];//might not be a good idea for undone transactions
  	return $result;
  }
}

