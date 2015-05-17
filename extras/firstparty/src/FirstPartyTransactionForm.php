<?php

/**
 * @file
 * Drupal\mcapi_1stparty\FirstPartyTransactionForm
 *
 * Generate a Transaction form using the FirstParty_editform entity.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Exchanges;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Type;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;


class FirstPartyTransactionForm extends TransactionForm {

  /*
   * the editform configEntity whos e defaults are used to build the tempalte transaction Entity
   */
  public $config;

  public function init(FormStateInterface $form_state) {
    $this->config = entity_load('1stparty_editform', $this->getOperation());
    parent::init($form_state);
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
    $config = $this->config;
    $form = parent::form($form, $form_state);

    if ($config->get('direction')['preset'] == 'outgoing') {
      $partner = &$form['payee'];
      $mywallet = &$form['payer']['widget'][0]['target_id'];
    }
    else {
      $partner = &$form['payer'];
      $mywallet = &$form['payee']['widget'][0]['target_id'];
    }

    $my_wallets = Wallet::ownedBy(User::load($this->currentUser()->id()));
    /*
    $mywallet = [
      '#title' => t('My wallet'),
      '#type' => 'value',
      '#weight' => -1,//ensure this is processed before the direction
      '#default_value' => reset($my_wallets)
    ];*/

    //if more than one wallet is allowed we'll put a chooser
    //however disabled widgets don't return a value, so we'll store
    //the value we need in a helper element
    if ($this->configFactory()->get('mcapi.wallets')->get('entity_types.user:user') > 1) {
      $mywallet['#type'] = $config->mywallet['widget'];
      $mywallet['#options'] = mcapi_entity_label_list('mcapi_wallet', $my_wallets);
    }
    //if the currentUser doesn't have more than one wallet,
    //disable the field and store the value in $form_state
    else {
      $mywallet['#type'] = 'value';
      $mywallet['#disabled'] = TRUE;
      //this will be used to populate mywallet in the validation
      $form_state->set('mywallet', [['target_id' => reset($my_wallets)]]);
    }

    $form['direction'] = [
      '#type' => $config->direction['widget'],
      '#default_value' => $config->direction['preset'],
      '#options' => [
        'incoming' => $config->direction['incoming'],
        'outgoing' => $config->direction['outgoing'],
      ],
    ];
    //handle the description
    $form['description']['#placeholder'] = $config->description['placeholder'];

    //Set the default values from the Designed form.
    $fieldapi_presets = $config->fieldapi_presets;
    //these are the visible fields according to the default view mode
    foreach (mcapi_default_display_fields() as $field_name => $data) {
      if (array_key_exists($field_name, $fieldapi_presets)) {
print_R(array_keys($form[$field_name]));//is there always a widget?
        $form[$field_name]['widget']['#default_value'] = $fieldapi_presets[$field_name];
      }
    }
    //worth field needs special treatment.
    //The allowed_curr_ids provided by the widget need to be overwritten
    //by the curr_ids in the designed form, if any.
    $curr_ids = [];
    foreach ((array)$config->fieldapi_presets['worth'] as $item) {
      if ($item['value'] == '')continue;
      $curr_ids[] = $item['curr_id'];
    }
    if ($curr_ids) {//overwrite the previous set of allowed currencies
      $form['worth']['widget']['#allowed_curr_ids'] = $curr_ids;
    }

    //hide the state & type
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $config->type;
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($config->type)->start_state;

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

    //::validate is called before any specifed handlers
    $form['#validate'] = (array)$form['#validate'];;
    array_unshift($form['#validate'], '::firstparty_convert_direction');

    //@todo contextual_links would be nice
    //pretty hard because it is designed to work only with templated themes,
    //not theme functions as this has to be.
    return $form;
  }

  /**
   * element validator for 'partner'
   * set the payer and payee from the mywallet, partner and direction
   */
  static function firstparty_convert_direction($form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    if ($values['direction'] == 'outgoing') {//swop the payer and payee values
      $form_state->setValueForElement(
        $form['payer']['widget'],
        $form_state->get('mywallet') ?  : $values['payer']
      );
    }
    else {
      $form_state->setValueForElement(
        $form['payee']['widget'],
        $form_state->get('mywallet') ?  : $values['payee']
      );
    }

  }


  public function validate(array $form, FormStateInterface $form_state) {
    //see contentEntityForm::validate

  $form_state->setValidateHandlers([]);
  \Drupal::service('form_validator')->executeValidateHandlers($form, $form_state);

    //this forces the validate handlers to run in order
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->config->experience['button'];

    $preview_mode = $this->config->experience['preview'];

    if ($preview_mode != MCAPI_CONFIRM_NORMAL) {
      $actions['submit']['#attached']['library'][] = 'core/drupal.ajax';
      if ($preview_mode == MCAPI_CONFIRM_MODAL) {
        $actions['submit']['#attributes'] = [
          'class' => ['use-ajax'],
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(['width' => 500])
        ];
      }
      elseif($display == MCAPI_CONFIRM_AJAX) {
        //curious how, to make a ajax link it seems necessary to put the url in 2 places
        $actions['submit']['#ajax'] = [
          'wrapper' => 'mcapi-transaction-1stparty-form',
          'method' => 'replace',
          'url' => Url::fromRoute('mcapi.1stparty.'.$this->config->id)
        ];
      }
    }

    return $actions;
  }

  /**
   * make the default transaction from the given settings
   */
  public function setEntity(EntityInterface $entity) {
    //ignore the passed entity
    $config = $this->config;
    //the partner is either the owner of the current page, under certain circumstances
    //or is taken from the form preset.
    //or is yet to be determined.
    if (0) {//no notion of context has been introduced yet
      //infer the partner wallet from the the node ower or something like that
    }
    elseif($config->partner['preset']) {
      $partner = $config->partner['preset'];
    }
    else $partner = '';

    //prepare a transaction using the defaults here
    $vars = ['type' => $config->type];
    foreach (mcapi_1stparty_transaction_tokens() as $prop) {
      if (property_exists($config, $prop)) {
        if (is_array($config->$prop)) {
          if (array_key_exists('preset', $config->{$prop})) {
            if (!is_null($config->{$prop}['preset'])){
              $vars[$prop] = $config->{$prop}['preset'];
            }
          }
        }
      }
    }
    //now handle the payer and payee, based on partner and direction
    if ($config->direction['preset'] == 'incoming') {
      $vars['payee'] = $this->currentUser()->id();
      $vars['payer'] = $partner;
    }
    elseif($config->direction['preset'] == 'outgoing') {
      $vars['payer'] = $this->currentUser()->id();
      $vars['payee'] = $partner;
    }
    //at this point we might want to override some values based on input from the url
    //this means the form can be populated using fields shared with another entity.
    $this->entity = $this->entityManager->getStorage('mcapi_transaction')->create($vars);
  }
}
