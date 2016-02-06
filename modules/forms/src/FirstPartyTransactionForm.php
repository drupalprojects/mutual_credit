<?php

/**
 * @file
 * Drupal\mcapi_forms\FirstPartyTransactionForm
 *
 * Generate a Transaction form using the FirstParty_editform entity.
 */

namespace Drupal\mcapi_forms;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class FirstPartyTransactionForm extends TransactionForm {

  /*
   * the editform configEntity whos e defaults are used to build the tempalte transaction Entity
   */
  private $configEntity;

  public function __construct($entity_manager, $tempstore, $request) {
    parent::__construct($entity_manager, $tempstore, $request);
    $id = $request
      ->attributes->get('_route_object')
      ->getOptions()['parameters']['firstparty_editform']['id'];
    $this->configEntity = entity_load('firstparty_editform', $id);
  }

  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entity = $this->configEntity->makeDefaultTransaction($this->request->query->all());
  }

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form settings saved in $this->configEntity.
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    $config = $this->configEntity;
    $form['#incoming'] = $config->get('incoming');//@todo do we still need this in the $form? should at least be in $form_state

    //handle the description
    if (isset($config->fieldapi_presets['description']['placeholder'])) {
      $form['description']['#placeholder'] = $config->fieldapi_presets['description']['placeholder'];
    }

    //hide the state & type
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $config->type;
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($config->type)->start_state;
    unset($form['creator']);
    $form_state->set('config', $config);//not sure if this is ever used.

    $form['#twig_template'] = $this->configEntity->experience['twig'];
    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->configEntity->experience['button'];

    $preview_mode = $this->configEntity->experience['preview'];

    if ($preview_mode != TransactionActionBase::CONFIRM_NORMAL) {
      $actions['submit']['#attached']['library'][] = 'core/drupal.ajax';
      if ($preview_mode == TransactionActionBase::CONFIRM_MODAL) {
        $actions['submit']['#attributes'] = [
          'class' => ['use-ajax'],
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => Json::encode(['width' => 500])
        ];
      }
      elseif($display == TransactionActionBase::CONFIRM_AJAX) {
        //curious how, to make a ajax link it seems necessary to put the url in 2 places
        $actions['submit']['#ajax'] = [
          'wrapper' => 'mcapi-transaction-1stparty-form',
          'method' => 'replace',
          'url' => Url::fromRoute('mcapi.1stparty.'.$this->configEntity->id)
        ];
      }
    }
    $form['#cache']['contexts'][] = 'user';//@todo check this is working.
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'first_party_transaction_form';
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->configEntity->title;
  }
}
