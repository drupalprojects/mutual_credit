<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\FirstPartyTransactionForm.
 * Generate a Transaction form using the FirstParty_editform entity.
 * @todo inject the configFactory
 */

namespace Drupal\mcapi_1stparty;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Exchanges;
use Drupal\mcapi\Entity\Wallet;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;


class FirstPartyTransactionForm extends TransactionForm {

  public $config;//the settings as a configuration object

  function __construct(EntityManagerInterface $entity_manager, $form_name = NULL) {
    parent::__construct($entity_manager);

    if (!$form_name) {
      //@todo is there a more elegant way to get the route options parameters
      //see \Drupal\mcapi_1stparty\FirstPartyRoutes
      $form_name = $this->getRouteMatch()
        ->getRouteObject()
        ->getOptions()['parameters']['1stparty_editform'];
    }
    $this->config = entity_load('1stparty_editform', $form_name);
    mdump($form_name);die();
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
  public function form(array $form, FormStateInterface $form_state) {
    //@todo we need to be able to pass in an entity here from the context
    //and generate $this->entity from it before building the base transaction form.
    //have to wait and how that might work in panels & blocks in d8
    $config = $this->config;
    //@todo caching according to $config->get('cache')
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

    $form['mywallet'] = array(
      '#title' => t('My wallet')
    );
    $my_wallets = mcapi_entity_label_list(
      'mcapi_wallet',
      Wallet::ownedBy(User::load($this->currentUser()->id()))
    );
    //if more than one wallet is allowed we'll put a chooser
    //however disabled widgets don't return a value, so we'll store the value we need in a helper element
    if (\Drupal::config('mcapi.wallets')->get('entity_types.user:user') > 1) {//show a widget
      $form['mywallet']['#type'] = $config->mywallet['widget'];
      $form['mywallet']['#options'] = $my_wallets;
      $form['mywallet']['#weight'] = -1;//ensure this is processed before the direction
    }
    //if the currentUser doesn't have more than one wallet, disable the field and store the value
    if (count($my_wallets) < 2) {
      $form['mywallet']['#disabled'] = TRUE;
      $form['mywallet']['#default_value'] = reset($my_wallets);
      //this will be used to populate mywallet in the validation
      $form_state->set('mywallet', key($my_wallets));
    }

    $form['direction'] = array(
      '#type' => $config->direction['widget'],
      '#default_value' => $config->direction['preset'],
      '#options' => array(
        'incoming' => $config->direction['incoming'],
        'outgoing' => $config->direction['outgoing'],
      ),
      '#element_validate' => array(
        array(get_class($this), 'firstparty_convert_direction')
      )
    );
    //handle the description
    //dunno why $config->get('description.placeholder') isn't working
    $des = $config->get('description');
    $form['description']['#placeholder'] = $des['placeholder'];

    //the fieldAPI fields are in place already, but we need to add the default values from the Designed form.
    $fieldapi_presets = $config->get('fieldapi_presets');
    foreach (mcapi_default_display_fields() as $field_name => $data) {//visible fields according to the default view mode
      if (array_key_exists($field_name, $fieldapi_presets)) {
        $form[$field_name]['widget']['#default_value'] = $fieldapi_presets[$field_name];
      }
    }

    //worth field needs special treatment.
    //The allowed_curr_ids provided by the widget need to be overwritten
    //by the curr_ids in the designed form, if any.
    $curr_ids = [];
    foreach ((array)$config->get('fieldapi_presets.worth') as $item) {
      if ($item['value'] == '')continue;
      $curr_ids[] = $item['curr_id'];
    }
    if ($curr_ids) {//overwrite the previous set of allowed currencies
      $form['worth']['widget']['#allowed_curr_ids'] = $curr_ids;
    }

    //hide the state & type
    $form['state']['#type'] = 'value';
    //@todo get the first state of this workflow
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

    //@todo contextual_links would be nice to jump straight to the edit form
    //pretty hard because it is designed to work only with templated themes, not theme functions
    //instead we'll probably just put a link in the menu
    return $form;
  }

  /**
   * element validator for 'partner'
   * set the payer and payee from the mywallet, partner and direction
   */
  static function firstparty_convert_direction(&$element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form = $form_state->getCompleteForm();
    $my_wallet = $form_state->get('mywallet') ? : $values['mywallet'];
    if ($values['direction'] == 'outgoing') {
      $form_state->setValueForElement($form['payer'], $values['partner']);
      $form_state->setValueForElement($form['payee'], $my_wallet);
    }
    else {
      $form_state->setValueForElement($form['payee'], $values['partner']);
      $form_state->setValueForElement($form['payer'], $my_wallet);
    }
  }

  /**
   * Returns an array of supported actions for the current entity form.
   * //@todo Might be ok to delete this now
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($this->config->experience['preview'] == 'ajax') {
      $actions['submit']['#attributes'] = new Attribute(array('class' => 'use-ajax'));
      //@todo TEMP Attribute isn't working at all...
      $actions['submit']['#attributes'] = array('class' => 'use-ajax');
      $actions['submit']['#attached']['library'][] = array('views_ui', 'drupal.ajax');
    }
    $actions['submit']['#value'] = $this->config->experience['button'];

    return $actions;
  }

  /**
   * make the default transaction from the given settings
   */
  private function prepareTransaction() {
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
      $vars['payee'] = $this->currentUser()->id();
      $vars['payer'] = $partner;
    }
    elseif($this->config->direction['preset'] == 'outgoing') {
      $vars['payer'] = $this->currentUser()->id();
      $vars['payee'] = $partner;
    }
    //at this point we might want to override some values based on input from the url
    //this means the form can be populated using fields shared with another entity.

    $this->entity = $this->entityManager->getStorage('mcapi_transaction')->create($vars);
  }
}
