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

  /**
   * The entity form mode
   */
  protected $mode;

  /**
   * The thirdparty settings on the entityForm
   */
  protected $settings;

  /**
   * Constructor.
   */
  public function __construct($entity_manager, $entity_type_bundle_info, $time, $tempstore, $current_request, $current_user) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time, $tempstore, $current_request, $current_user);
    $options = $current_request
      ->attributes->get('_route_object')
      ->getOptions();
    $id = 'mcapi_transaction.mcapi_transaction.' . $options['parameters']['mode'];
    $entityDisplay = EntityFormDisplay::load($id);
    $this->settings = $entityDisplay->getThirdPartySettings('mcapi_forms');
    $this->mode = $entityDisplay->getMode();
  }

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $props = [
      'type' => $this->settings['transaction_type'],
    ];
    $this->entity = Transaction::create($props + $this->request->query->all());
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // See class WalletReferenceAutocompleteWidget.
    $form = parent::form($form, $form_state);


    // Hide the state & type.
    $form['type']['#type'] = 'value';
    $form['type']['#default_value'] = $this->settings['transaction_type'];
    $form['state']['#type'] = 'value';
    $form['state']['#value'] = Type::load($this->settings['transaction_type'])->start_state;
    $form['creator']['#access'] = FALSE;
    $form['#twig_template'] = str_replace(PHP_EOL, "<br/>", $this->settings['experience_twig']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->settings['experience_button'];
    $preview_mode = $this->settings['experience_preview'];

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
          'url' => Url::fromRoute('mcapi.1stparty.' . $this->mode),
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
    return $this->settings['title'];
  }

}
