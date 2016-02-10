<?php

/**
 * @file
 * Definition of Drupal\mcapi_forms\Form\FirstPartyFormDesigner.
 * This configuration entity is used for generating transaction forms.
 */

namespace Drupal\mcapi_forms\Form;

use Drupal\mcapi\Mcapi;
use Drupal\mcapi\Plugin\TransactionActionBase;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FirstPartyFormDesigner extends EntityForm {

  private $requestContext;

  function __construct($request_context) {
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.request_context')
    );
  }


  function getFormId() {
    return 'first_party_editform';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    //widgetBase::Form expects this
    $form['#parents'] = [];
    $element = ['#required' => FALSE];

    $configEntity = $this->entity;

    //$form['#title'] = $configEntity->title ? $this->t('Designing transaction form') : $this->t('New transaction form');

    $form['#tree'] = TRUE;
    $w = 0;
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the form'),
      '#default_value' => $configEntity->title,
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
      '#weight' => $w++,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $configEntity->id(),
      '#machine_name' => [
        'exists' => 'mcapi_editform_load',
        'source' => ['title'],
      ],
      '#maxlength' => 12,
      '#disabled' => !$configEntity->isNew(),
    ];

    $form['wallet_link_title'] = [
      '#title' => $this->t('Link title to show on wallet'),
      '#description' => $this->t('Optionally use token [mcapi_wallet:name]'),
      '#type' => 'textfield',
      '#weight' => $w++,
      '#default_value' => $configEntity->wallet_link_title,
    ];

    $form['path'] = [
      '#title' => $this->t('Path'),
      '#description' => t('The url path at which this form will appear. Must begin with / and be unique.'),
      '#type' => 'textfield',
      '#weight' => $w++,
      '#element_validate' => [
        [get_class($this), 'unique_path']
      ],
      '#default_value' => $configEntity->path,
      '#placeholder' => $this->t('/internal/path'),
      '#element_validate' => [[get_class($this), 'validatePath']],//this should be in core!
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
      '#required' => TRUE
    ];

    $form['menu'] = [
      '#title' => $this->t('Menu link'),
      '#type' => 'details',
      '#weight' => $w++,
      'title' => [
        '#title' => $this->t('Link title'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->menu['title'],
        '#weight' => 1
      ],
      'weight' => [
        '#title' => $this->t('Weight'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->menu['weight'],
        '#description' => $this->t('In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
        '#weight' => 2
      ]
    ];

    foreach (\Drupal\system\Entity\Menu::loadMultiple() as $menu_name => $menu) {
      $custom_menus[$menu_name] = $menu->label();
    }
    $form['menu']['menu_name'] = [
      '#title' => $this->t('Menu'),
      '#type' => 'select',
      '#options' =>  $custom_menus,
      '#required' => FALSE,
      '#empty_value' => '',
      '#default_value' => $configEntity->menu['menu_name'],
      '#weight' => 2
    ];
    $form['type'] =  [
      '#title' => $this->t('Transaction type'),
      '#type' => 'mcapi_types',
      '#default_value' => $configEntity->type,
      '#weight' => $w++,
      '#required' => TRUE,
    ];
    $form['incoming'] = [
      '#title' => $this->t('Direction'),
      '#description' => $this->t('Direction of payment relative to the current user'),
      '#type' => 'radios',
      '#options' => [
        '0' => $this->t('Outgoing'),
        '1' => $this->t('Incoming')
      ],
      '#default_value' => intval($configEntity->incoming),
      '#required' => TRUE,
      '#weight' => $w++
    ];
    //enable the field UI module so we can link to it.
    if (!\Drupal::moduleHandler()->moduleExists('field_ui')) {
      \Drupal::service('module_installer')->install(['field_ui']);
      \Drupal::service('router.builder')->rebuild();
    }

    //@todo make this work
    $form['hide_one_wallet'] = [
      '#title' => $this->t('One wallet'),
      '#description' => $this->t('Hide the wallet field if there is only one.'),
      '#type' => 'checkbox',
      '#default_value' => intval($configEntity->hide_one_wallet),
      '#weight' => $w++,
    ];

    $moreInfo = $this->t(
      'Put any default values here.') .' '.
      $this->l(
        $this->t('More field configuration options...'),
        Url::fromRoute('entity.entity_form_display.mcapi_transaction.default')
      );

    $all_fields = \Drupal::service('entity_field.manager')
      ->getFieldMap()['mcapi_transaction'];

    $tokens = array_diff(
      array_keys($all_fields),
      ['xid', 'uuid', 'serial', 'parent', 'creator', 'type', 'state', 'changed']
    );
    $form['experience'] = [
      '#title' => $this->t('User experience'),
      '#type' => 'details',
      '#open' => TRUE,
      'twig' => [
        '#title' => $this->t('Main form'),
        '#description' => implode(' ', [
          $this->t('Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.'),
          ' {{ '. implode(' }}, {{ ', $tokens) .' }}',
          $this->l(
            $this->t('What is twig?'),
            Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
          )
        ]),
        '#type' => 'textarea',
        '#rows' => 6,
        '#default_value' => $configEntity->experience['twig'],
        '#element_validate' => [
          [get_class($this), 'validate_twig_template']
        ],
        '#weight' => 1,
        '#required' => TRUE
      ],
      'button' => [
        '#title' => $this->t('Button label'),
        '#description' => $this->t("The text to appear on the 'save' button, or the absolute url of an image."),
        '#type' => 'textfield',
        '#default_value' => $configEntity->experience['button'],
        '#required' => TRUE,
        '#weight' => 2,
      ],
      'preview' => [
        '#title' => $this->t('Preview mode'),
        '#type' => 'radios',
        '#options' => [
          TransactionActionBase::CONFIRM_NORMAL => $this->t('Basic - Go to a fresh page'),
          TransactionActionBase::CONFIRM_AJAX => $this->t('Ajax - Replace the form'),
          TransactionActionBase::CONFIRM_MODAL => $this->t('Modal - Confirm in a dialogue box')
        ],
        '#default_value' => $configEntity->experience['preview'],
        '#weight' => 3,
        '#required' => TRUE
      ],
      '#weight' => $w++,
    ];

    $form['#suffix'] = $this->t(
      "N.B The confirmation page is configured separately, at %link",
      ['%link' => $this->l(
        'admin/accounting/workflow/save',
        Url::fromRoute('entity.action.edit_form', ['action' => 'transaction_save'])
      )]
    );
    return $form;
  }

  //check that the required transaction form elements either have preset OR are in the Twig
  public static function validate_twig_template(array $element, $form_state) {
    $errors = [];
    if (strpos($element['#value'], "{{ partner }}") === NULL) {
      if (!$form_state->getValue('partner')['preset']) {
        $errors[] = 'partner';
      }
    }

    //ensure the worth field is present if there are
    if (strpos($element['#value'], '{{ worth }}') == NULL) {
      $empty = TRUE;
      foreach ($form_state->getValues('fieldapi_presets')['worth']['preset'] as $item) {
        if ($item['value']) {
          $empty = FALSE;
          break;
        }
      }
      if ($empty){
        $errors[] = 'worth';
      }
    }
    foreach ($errors as $field_name) {
      $form_state->setError(
        $element,
        $this->t(
          'Field @fieldname neither appears in the form, nor has a preset value',
          ['@fieldname' => $element['#title']]
        )
      );
    }
  }

  /*
   * element validation function
   */
  static function validatePath(&$element, $form_state) {
    if ($form_state->getValue('path')[0] !== '/') {
      $form_state->setError($element, t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('path')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    foreach ($values as $name => $value) {
      if (!in_array($value, ['actions', 'langcode'])) {
        $this->entity->set($name, $value);
      }
    }

    \Drupal::service('router.builder')->rebuild();

    $form_state->setRedirect('mcapi.admin.transaction_form.list');
  }

  //is called from the form validator, so must be public
  //@todo see if there is another function for this
  public static function unique_path(&$element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $dupe = \Drupal::database()->select('router', 'r')
      ->fields('r', ['name'])
      ->condition('name', 'mcapi.1stparty.'.$values['id'], '<>')
      ->condition('path', $values['path'])
      ->execute()->fetchField();
    if ($dupe) $form_state->setError('path', t('Path is already used.'));
  }

}
