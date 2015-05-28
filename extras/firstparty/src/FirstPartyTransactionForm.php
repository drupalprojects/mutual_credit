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
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;


class FirstPartyTransactionForm extends TransactionForm {

  /*
   * the editform configEntity whos e defaults are used to build the tempalte transaction Entity
   */
  private $id;
  private $config;

  public function __construct(EntityManagerInterface $entity_manager, $tempstore, $request) {
    parent::__construct($entity_manager, $tempstore);
    //NB seems like injection doesn't happen 
    $this->id = \Drupal::service('request_stack')->getCurrentRequest()
      ->attributes->get('_route_object')
      ->getOptions()['parameters']['1stparty_editform'];
    $this->config = entity_load('1stparty_editform', $this->id);
  }

  /**
   * router callback
   *
   * @return array
   *   a renderable array
   *
   * @todo contextual_links would be nice
   */
  public function loadForm() {

    $form = \Drupal::service('entity.form_builder')//inject this...
      ->getForm(
        $this->defaultTransaction($this->config),
        $this->id
      );
    //make hidden any fields that do not occur in the template
    $tokens = mcapi_1stparty_transaction_tokens();
    foreach ($tokens as $token) {
      if (strpos($this->config->experience['twig'], $token) === FALSE) {
        if (!$this->config->{token}['preset']) {
          unset($form[$token]);//we'll rely on the entity defaults
        }
      }
    }
    $tokens[] = 'actions';

    //pretty hard because it is designed to work only with templated themes,
    //not theme functions as this has to be.
    return [
      '#theme'=> '1stpartyform',
      '#form' => $form,
      '#twig_tokens' => $tokens,
      '#twig_template' => $this->config->experience['twig'],
      '#incoming' => $config->incoming
    ];
  }

  /**
   * Symfony routing callback
   */
  public function title() {
    return $this->config->title;
  }

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form settings saved in $this->config.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $config = $this->config;
    $form_state->set('config', $config);
    $form = parent::form($form, $form_state);
    if ($config->get('incoming')) {
      $partner = &$form['payer'];
      $mywallet = &$form['payee']['widget'][0]['target_id'];
    }
    else {
      $partner = &$form['payee'];
      $mywallet = &$form['payer']['widget'][0]['target_id'];
    }

    $my_wallets = Wallet::ownedBy(User::load($this->currentUser()->id()));

    //if more than one wallet is allowed we'll put a chooser
    //however disabled widgets don't return a value, so we'll store
    //the value we need in a helper element
    if ($this->configFactory()->get('mcapi.wallets')->get('entity_types.user:user') > 1 && count($mywallets) > 1) {
      $mywallet['#type'] = $config->mywallet['widget'];
      $mywallet['#options'] = mcapi_entity_label_list('mcapi_wallet', $my_wallets);
      $mywallet_element['#title'] = $this->t('With');
    }
    //if the currentUser doesn't have more than one wallet,
    //disable the field and store the value in $form_state
    else {
      $mywallet['#type'] = 'value';
      //this will be used to populate mywallet in the validation
      $form_state->set('mywallet', [['target_id' => reset($my_wallets)]]);
    }

    //handle the description
    $form['description']['#placeholder'] = $config->description['placeholder'];

    //Set the default values from the Designed form.
    $fieldapi_presets = $config->fieldapi_presets;
    //these are the visible fields according to the default view mode
    foreach (mcapi_default_display_fields() as $field_name => $data) {
      if (array_key_exists($field_name, $fieldapi_presets)) {
echo "keys for $field_name element"; print_R(array_keys($form[$field_name]));//is there always a widget?
        $form[$field_name]['widget']['#default_value'] = $fieldapi_presets[$field_name];
      }
    }
    //worth field needs special treatment.
    //The allowed_curr_ids provided by the widget need to be overwritten
    //by the curr_ids in the designed form, if any.
    $curr_ids = [];
    foreach ($config->fieldapi_presets['worth'] as $item) {
      if ($item['value']) {
        $curr_ids[] = $item['curr_id'];
      }
    }
    if ($curr_ids) {//overwrite the previous set of allowed currencies
      $form['worth']['widget']['#allowed_curr_ids'] = $curr_ids;
echo ' setting currency ids to preset values ';
    }

    //hide the state & type
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $config->type;
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($config->type)->start_state;
    unset($form['creator']);

    //::validate is called before any specifed handlers
    $form['#validate'] = (array)$form['#validate'];;
    array_unshift($form['#validate'], '::firstparty_convert_direction');
    return $form;
  }

  /**
   * element validator for 'partner'
   * set the payer and payee from the mywallet, partner and direction
   */
  static function firstparty_convert_direction($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($form_state->get('config')->incoming) {//swop the payer and payee values
      $form_state->setValueForElement(
        $form['payee']['widget'],
        $form_state->get('mywallet') ?  : $values['payee']
      );
    }
    else {
      $form_state->setValueForElement(
        $form['payer']['widget'],
        $form_state->get('mywallet') ?  : $values['payer']
      );
    }

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
  public function defaultTransaction(EntityInterface $entity) {
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

    foreach (mcapi_1stparty_transaction_tokens(TRUE) as $prop) {
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
    if ($config->incoming) {
      $vars['payee'] = $this->currentUser()->id();
      $vars['payer'] = $partner;
    }
    else {
      $vars['payer'] = $this->currentUser()->id();
      $vars['payee'] = $partner;
    }
    //at this point we might want to override some values based on input from the url
    //this means the form can be populated using fields shared with another entity.
    //@todo inject entityManager
    return \Drupal::entityManager()->getStorage('mcapi_transaction')->create($vars);
  }
}
