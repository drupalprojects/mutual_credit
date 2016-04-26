<?php

/**
 * @file
 * Drupal\mcapi_forms\FirstPartyTransactionForm
 *
 * Generate a Transaction form from the entity form display thirdparty settings.
 */

namespace Drupal\mcapi_forms;

use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;


class FirstPartyTransactionForm extends TransactionForm {

  /*
   * the editform entityDisplay whos e defaults are used to build the tempalte transaction Entity
   */
  private $entityDisplay;

  public function __construct($entity_manager, $tempstore, $request, $current_user) {
    parent::__construct($entity_manager, $tempstore, $request, $current_user);
    $options = $request
      ->attributes->get('_route_object')
      ->getOptions();
    $id = 'mcapi_transaction.mcapi_transaction.'.$options['parameters']['mode'];
    $this->entityDisplay = EntityFormDisplay::load($id);
  }

  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->entity = mcapi_forms_make_default_transaction(
      $this->entityDisplay->getThirdPartySetting('mcapi_forms', 'type'),
      $this->request->query->all()
    );
  }

  /**
   * Get the original transaction form and alter it according to
   * the 1stparty form settings saved in $this->entityDisplay.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $settings = $this->entityDisplay->getThirdPartySettings('mcapi_forms');
    $form_state->set('restrictWallets', TRUE);//see \Drupal\mcapi\Plugin\Field\FieldWidget\WalletReferenceAutocompleteWidget
    $form = parent::form($form, $form_state);
    //hide the state & type
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $settings['type'];
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($settings['type'])->start_state;
    unset($form['creator']);
    $form['#twig_template'] = str_replace(array('\r\n','\n','\r'), "<br/>", $settings['experience_twig']);
    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->entityDisplay->getThirdPartySetting('mcapi_forms', 'experience_button');
    $preview_mode = $this->entityDisplay->getThirdPartySetting('mcapi_forms', 'experience_preview');

    if ($preview_mode != TransactionActionBase::CONFIRM_NORMAL) {
      $actions['submit']['#attached']['library'][] = 'core/drupal.ajax';
      if ($preview_mode == TransactionActionBase::CONFIRM_MODAL) {
        $actions['submit']['#attributes'] = [
          'class' => ['use-ajax'],
          'data-accepts' => 'application/vnd.drupal-modal',
          'data-dialog-options' => \Drupal\Component\Serialization\Json::encode(['width' => 500])
        ];
      }
      elseif($preview_mode == TransactionActionBase::CONFIRM_AJAX) {
        //curious how, to make a ajax link it seems necessary to put the url in 2 places
        $actions['submit']['#ajax'] = [
          'wrapper' => 'mcapi-transaction-1stparty-form',
          'method' => 'replace',
          'url' => \Drupal\Core\Url::fromRoute('mcapi.1stparty.'.$this->entityDisplay->get('mode'))
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
    return $this->entityDisplay->getThirdPartySetting('mcapi_forms', 'title');
  }
}
