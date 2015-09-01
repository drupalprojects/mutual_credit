<?php

/**
 * @file
 * Drupal\mcapi_1stparty\FirstPartyTransactionForm
 *
 * Generate a Transaction form using the FirstParty_editform entity.
 */

namespace Drupal\mcapi_1stparty;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Plugin\TransitionManager;
use Drupal\mcapi\Exchanges;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Type;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;


class FirstPartyTransactionForm extends TransactionForm {

  /*
   * the editform configEntity whos e defaults are used to build the tempalte transaction Entity
   */
  private $id;
  private $configEntity;

  public function __construct(EntityManagerInterface $entity_manager, $tempstore, $request) {
    parent::__construct($entity_manager, $tempstore);
    //NB seems like injection doesn't happen
    $this->id = $request->getCurrentRequest()
      ->attributes->get('_route_object')
      ->getOptions()['parameters']['firstparty_editform'];
    $this->configEntity = entity_load('firstparty_editform', $this->id);
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('user.private_tempstore'),
      $container->get('request_stack')
    );
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
    module_load_include('inc', 'mcapi_1stparty');
    $form = \Drupal::service('entity.form_builder')//inject this...
      ->getForm(
        mcapi_forms_default_transaction($this->configEntity),
        $this->id
      );
    //remove any items from the $form which are not in the template
    $tokens = mcapi_1stparty_transaction_tokens();
    foreach ($tokens as $token) {
      if (strpos($this->configEntity->experience['twig'], $token) === FALSE) {
        if (!isset($this->configEntity->{$token}['preset'])) {
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
      '#twig_template' => $this->configEntity->experience['twig'],
      '#incoming' => $this->configEntity->incoming,
      '#cache' => [
        'contexts' => ['user']//@todo check this is working.
      ]
    ];
    //the whole 1stparty cache would need clearing
    //anytime any user changed any permission on any wallet
    //it is still worth cachinh though because the form could be on every page
  }

  /**
   * Symfony routing callback
   */
  public function title() {
    return $this->configEntity->title;
  }

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form settings saved in $this->configEntity.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $config = $this->configEntity;
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

    $my_wallets = Wallet::HeldBy(User::load($this->currentUser()->id()));

    //if more than one wallet is allowed we'll put a chooser
    //however disabled widgets don't return a value, so we'll store
    //the value we need in a helper element
    if (!mcapi_one_wallet_per_user_mode() && count($mywallets) > 1) {
      $mywallet['#type'] = $config->mywallet['widget'];
      $mywallet['#options'] = mcapi_entity_label_list('mcapi_wallet', $my_wallets);
      $mywallet_element['#title'] = $this->t('With');
    }
    //if the currentUser doesn't have more than one wallet,
    //disable mywallet field and store its wallet id in $form_state
    else {
      $unused = $config->mywallet['unused'];
      if ($unused == 'disabled') {
        $mywallet['#disabled'] = TRUE;
      }
      elseif ($unused == 'hidden') {
        unset($mywallet);
      }
      $my_one_wallet = reset($my_wallets);
      //this will be used to populate mywallet in the validation
      $form_state->set('mywallet', [['target_id' => $my_one_wallet]]);
      //in any case remove the current users one wallet from the list of recipients
      unset($partner['widget'][0]['target_id']['#options'][$my_one_wallet]);
    }
    //handle the description
    //$form['description']['#placeholder'] = $config->description['placeholder'];

    //worth field needs special treatment.
    //The allowed_curr_ids provided by the widget need to be overwritten
    //by the curr_ids in the designed form, if any.
    $curr_ids = [];
    foreach ((array)$config->fieldapi_presets['worth']['preset'] as $item) {
      if ($item['value'] !== '') {
        $curr_ids[] = $item['curr_id'];
      }
    }
    if ($curr_ids) {//overwrite the previous set of allowed currencies
      $form['worth']['widget']['#allowed_curr_ids'] = $curr_ids;
    }
    //hide the state & type
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $config->type;
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($config->type)->start_state;
    unset($form['creator']);
    return $form;
  }


  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->configEntity->experience['button'];

    $preview_mode = $this->configEntity->experience['preview'];

    if ($preview_mode != TransitionManager::CONFIRM_NORMAL) {
      $actions['submit']['#attached']['library'][] = 'core/drupal.ajax';
      if ($preview_mode == TransitionManager::CONFIRM_MODAL) {
        $actions['submit']['#attributes'] = [
          'class' => ['use-ajax'],
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(['width' => 500])
        ];
      }
      elseif($display == TransitionManager::CONFIRM_AJAX) {
        //curious how, to make a ajax link it seems necessary to put the url in 2 places
        $actions['submit']['#ajax'] = [
          'wrapper' => 'mcapi-transaction-1stparty-form',
          'method' => 'replace',
          'url' => Url::fromRoute('mcapi.1stparty.'.$this->configEntity->id)
        ];
      }
    }

    return $actions;
  }

}
