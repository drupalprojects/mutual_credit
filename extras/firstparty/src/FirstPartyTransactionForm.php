<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\Form\FirstPartyTransactionForm.
 * Generate a Transaction form using the FirstParty_editform entity.
 * We have to override all references to the EntityFormDisplay
 */

namespace Drupal\mcapi_1stparty;

use Drupal\mcapi_1stparty\Entity\FirstPartyFormDisplay;
use Drupal\mcapi\Form\TransactionForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\Entity\User;


class FirstPartyTransactionForm extends TransactionForm {

  var $config;//the settings as a configuration object

  function __construct(EntityManagerInterface $entity_manager, $form_name = NULL) {
    parent::__construct($entity_manager);
    //in alpha12 this protected $moduleHandler is declared in Drupal\Core\Form\FormBuilder but never populated
    $this->moduleHandler = \Drupal::moduleHandler();

    if (!$form_name) {
      $options = \Drupal::request()->attributes->get('_route_object')->getOptions();
      //this is the only way I know how to get the args. Could it be more elegant?
      $form_name = $options['parameters']['editform_id'];
    }
    $this->config = entity_load('1stparty_editform', $form_name);
    //makes $this->entity;
    $this->prepareTransaction();
  }

  /**
   * Symfony routing callback
   */
  public function title() {
    return $this->config->title;
  }

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form saved in $this->config.
   */
  public function form(array $form, array &$form_state) {
    //@todo we need to be able to pass in an entity here from the context
    //and generate $this->entity from it before building the base transaction form.
    //have to wait and how that might work in panels & blocks in d8

    //borrowed from unused ancestors
    //$form['#process'][] = array($this, 'processForm');
    $config = $this->config;

    //TODO caching according to $config->get('cache')
    $form = parent::form($form, $form_state);

    //sort out the payer and payee, for the secondparty and direction
    //the #title and #description will get stripped later
    if ($config->get('direction.preset') == 'incoming') {
      $form['partner'] = $form['payer'];
    }
    else {
      $form['partner'] = $form['payee'];
    }
    $form['payer']['#access'] = FALSE;
    $form['payee']['#access'] = FALSE;
    $account = User::load(\Drupal::currentuser()->id());
    //use this method because i still don't know how to iterate
    //through the $account->exchanges entity_reference field.
    foreach (mcapi_get_wallet_ids($account) as $wid) {
      $my_wallets[$wid] = entity_load('mcapi_wallet', $wid)->label();
    }
    $form['mywallet'] = array(
      '#title' => t('My wallet')
    );
    //if I only have one wallet, we'll put a bogus disabled chooser
    //however disabled widgets don't return a value, so we'll store the value we need in a helper element
    if (\Drupal::config('mcapi.wallets')->get('entity_types.user:user') > 1) {//show a widget
      $form['mywallet']['#type'] = $config->mywallet['widget'];
      $form['mywallet']['#options'] = $my_wallets;
      $form['mywallet']['#weight'] = -1;//ensure this is processed before the direction
    }
    if (count($my_wallets) < 2) {
      $form['mywallet']['#disabled'] = TRUE;
      $form['mywallet']['#default_value'] = reset($my_wallets);
      //this will be used to populate mywallet in the validation
      $form['mywallet_value'] = array(
        '#type' => 'value',
        '#value' => key($my_wallets)
      );
    }
    $form['partner']['#element_validate'] = array('local_wallet_validate_id');

    if ($config->partner['preset']) {
      $form['partner']['#default_value'] = $config->partner['preset'];
    }

    $form['direction'] = array(
      '#type' => $config->direction['widget'],
      '#default_value' => $config->direction['preset'],
      '#options' => array(
        'incoming' => $config->direction['incoming'],
        'outgoing' => $config->direction['outgoing'],
      ),
      '#element_validate' => array(array($this, 'firstparty_convert_direction'))
    );
    //handle the description
    //dunno why $config->get('description.placeholder') isn't working
    $des = $config->get('description');
    $form['description']['#placeholder'] = $des['placeholder'];

    //the fieldAPI fields are in place already, but we need to add the default values from the Designed form.
    $fieldapi_presets = $config->get('fieldapi_presets');
    foreach (mcapi_1stparty_fieldAPI() as $field_name => $data) {//visible fields according to the default view mode
      if (array_key_exists($field_name, $fieldapi_presets)) {
        $form[$field_name]['widget']['#default_value'] = $fieldapi_presets[$field_name];
      }
    }

    //worth field needs special treatment.
    //The allowed_curr_ids provided by the widget need to be overwritten
    //by the curr_ids in the designed form, if any.
    $curr_ids = array();
    foreach ((array)$config->get('fieldapi_presets.worth') as $item) {
      if ($item['value'] == '')continue;
      $curr_ids[] = $item['curr_id'];
    }
    if ($curr_ids) {//overwrite the previous set of allowed currencies
      $form['worth']['widget']['#allowed_curr_ids'] = $curr_ids;
    }

    //TODO put this in the base transaction form,
    //where the one checkbox can enable both payer and payee to be selected from any exchange
    if (strpos($config->experience['twig'], '{{ intertrade }}') && referenced_exchanges($account, TRUE, TRUE)) {
      //this checkbox flips between partner_choosers
      $form['intertrade'] = array(
        '#title' => t('Intertrade'),
        '#description' => t('Trade with someone outside your exchange'),
        '#type' => 'checkbox',
        '#default_value' => 0,
      );
      //make a second partner widget and switch between them
      $form['partner']['#states'] = array(
        'visible' => array(
          ':input[name="intertrade"]' => array('checked' => FALSE)
        )
      );
      $form['partner_all'] = $form['partner'];
      $form['partner_all']['#local'] = FALSE;
      $form['partner_all']['#states']['visible'][':input[name="intertrade"]']['checked'] = TRUE;
    }

    //hide the state, type
    $form['state']['#type'] = 'value';
    //TODO get the first state of this workflow
    $form['state']['#value'] = TRANSACTION_STATE_FINISHED;
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $config->type;

    //handle the field API
    $form['#twig'] = $config->experience['twig'];

    //make hidden any fields that do not occur in the template
    $form['#twig_tokens'] = mcapi_1stparty_transaction_tokens();

    foreach ($form['#twig_tokens'] as $token) {
       if (strpos($config->experience['twig'], $token) === FALSE) {
        $form[$token]['#type'] = 'value';
       }
    }
    $form['#twig_tokens'][] = 'actions';
    $form['#theme'] = '1stpartyform';

/*
    $form['#attributes']['class'][] = 'contextual-region';
    //@todo contextual links would be nice to jump straight to the edit form
    //pretty hard because it is designed to work only with templated themes, not theme functions
    //instead we'll probably just put a link in the menu
    $form['#title_suffix']['links'] = array(
      '#type' => 'contextual_links',
      '#contextual_links' => array(
        '1stpartyform' => array(
          'route_parameters' => array('1stpartyform' => $config->id())
        )
      )
    );
    $form['contextual_links'] = array(
      '#type' => 'contextual_links_placeholder',
      '#id' => array('firstparty:1stpartyform='.$config->id().': ')
    );
    */

    return $form;
  }

  /**
   * element validator for 'partner'
   * set the payer and payee from the mywallet, partner and direction
   */

  function firstparty_convert_direction(&$element, $form_state) {
    $form_builder = \Drupal::service('form_builder');
    $values = &$form_state['values'];
    $form = &$form_state['complete_form'];
    if ($form_state['values']['direction'] == 'outgoing') {
      $form_builder->setValue($form['payer'], $values['partner'], $form_state);
      $form_builder->setValue($form['payee'], $values['mywallet_value'], $form_state);
    }
    else {
      $form_builder->setValue($form['payee'], $values['partner'], $form_state);
      $form_builder->setValue($form['payer'], $values['mywallet_value'], $form_state);
    }
  }

  /*
   * element validation callback
   * convert the firstparty, 3rdparty and direction fields into payer and payee.
   */
  public function validate(array $form, array &$form_state) {
    $my_wallet_id = array_key_exists('mywallet_value', $form_state['values']) ?
      $form_state['values']['mywallet_value'] :
      $form_state['values']['mywallet'];
    $partner_wallet_id = $form_state['values']['intertrade'] ?
      $form_state['values']['partner_all'] :
      $form_state['values']['partner'];

    if ($form_state['values']['direction'] == 'outgoing') {
      //$element is only needed for the parents
      \Drupal::formBuilder()->setValue($form['payee'], $partner_wallet_id, $form_state);
      \Drupal::formBuilder()->setValue($form['payer'], $my_wallet_id, $form_state);
    }
    else {
      \Drupal::formBuilder()->setValue($form['payer'], $partner_wallet_id, $form_state);
      \Drupal::formBuilder()->setValue($form['payee'], $my_wallet_id, $form_state);
    }

    parent::validate($form, $form_state);
  }

  /**
   * Returns an array of supported actions for the current entity form.
   * //TODO Might be ok to delete this now
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->config->experience['preview'] == 'ajax') {
      //this isn't working at all...
      $actions['save']['#attributes']['class'][] = 'use-ajax';
      $actions['save']['#attached']['library'][] = array('views_ui', 'drupal.ajax');
    }
    $actions['save']['#value'] = $this->config->experience['button'];

    return $actions;
  }

  /**
   * make the default transaction from the given settings
   */
  function prepareTransaction() {
    //the partner is either the owner of the current page, under certain circumstances
    //or is taken from the form preset.
    //or is yet to be determined.
    if (0) {//no notion of context has been introduced yet
      //infer the partner wallet from the the node ower or something like that
    }
    elseif($this->config->partner['preset']) {
      $partner = $this->config->partner['preset'];
    }
    else $partner = '';

    //prepare a transaction using the defaults here
    $vars = array('type' => $this->config->type);
    foreach (mcapi_1stparty_transaction_tokens() as $prop) {
      if (property_exists($this->config, $prop)) {
        if (is_array($this->config->$prop)) {
          if (array_key_exists('preset', $this->config->{$prop})) {
            if (!is_null($this->config->{$prop}['preset'])){
              $vars[$prop] = $this->config->{$prop}['preset'];
            }
          }
        }
      }
    }
    //now handle the payer and payee, based on partner and direction
    if ($this->config->direction['preset'] == 'incoming') {
      $vars['payee'] = \Drupal::currentUser()->id();
      $vars['payer'] = $partner;
    }
    elseif($this->config->direction['preset'] == 'outgoing') {
      $vars['payer'] = \Drupal::currentUser()->id();
      $vars['payee'] = $partner;
    }
    //at this point we might want to override some values based on input from the url
    //this means the form can be populated using fields shared with another entity.

    $this->entity = \Drupal::entityManager()->getStorage('mcapi_transaction')->create($vars);
  }
}
