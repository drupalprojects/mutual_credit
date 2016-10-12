<?php

namespace Drupal\mcapi_forms;

use Drupal\mcapi\Entity\Transaction;
use Drupal\mcapi\Form\TransactionForm;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Form builder for transaction formed designed via the UI.
 */
class FirstPartyTransactionForm extends TransactionForm {

  /*
   * The editform entityDisplay used to build the template transaction Entity.
   */
  private $entityDisplay;

  /**
   * Constructor.
   */
  public function __construct($entity_manager, $tempstore, $request, $current_user) {
    parent::__construct($entity_manager, $tempstore, $request, $current_user);
    $options = $request
      ->attributes->get('_route_object')
      ->getOptions();
    $id = 'mcapi_transaction.mcapi_transaction.' . $options['parameters']['mode'];
    $this->entityDisplay = EntityFormDisplay::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $props = [
      'type' => $this->entityDisplay->getThirdPartySetting('mcapi_forms', 'type'),
    ];
    $this->entity = Transaction::create($props + $this->request->query->all());
  }

  /**
   * Alter the original Transaction form.
   *
   * According to the 1stparty form settings saved in $this->entityDisplay.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $settings = $this->entityDisplay->getThirdPartySettings('mcapi_forms');
    // See class WalletReferenceAutocompleteWidget.
    $form_state->set('restrictWallets', TRUE);
    $form = parent::form($form, $form_state);
    // Hide the state & type.
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $settings['type'];
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($settings['type'])->start_state;
    unset($form['creator']);
    $form['#twig_template'] = str_replace(array('\r\n', '\n', '\r'), "<br/>", $settings['experience_twig']);
    return $form;
  }

  /**
   * {@inheritdoc}
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
          'data-dialog-options' => Json::encode(['width' => 500]),
        ];
      }
      elseif ($preview_mode == TransactionActionBase::CONFIRM_AJAX) {
        // Curious how, to make a ajax link, must put the url in 2 places.
        $actions['submit']['#ajax'] = [
          'wrapper' => 'mcapi-transaction-1stparty-form',
          'method' => 'replace',
          'url' => Url::fromRoute('mcapi.1stparty.' . $this->entityDisplay->get('mode')),
        ];
      }
    }
    // @todo check this is working.
    $form['#cache']['contexts'][] = 'user';
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
