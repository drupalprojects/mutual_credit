<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\Form\FirstPartyFormDesigner.
 * This configuration entity is used for generating transaction forms.
 */

namespace Drupal\mcapi_1stparty\Form;

use Drupal\mcapi\Plugin\TransitionManager;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Transaction;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

class FirstPartyFormDesigner extends EntityForm {

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

    $form['path'] = [
      '#title' => $this->t('Path'),
      '#description' => t('The url path at which this form will appear. Must be unique.'),
      '#type' => 'textfield',
      '#weight' => $w++,
      '#element_validate' => [
        [get_class($this), 'unique_path']
      ],
      '#default_value' => $configEntity->path,
      '#placeholder' => $this->t('internal/path'),
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
    //following section of the form allows the admin to handle the individual fields of the transaction form.
    //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
    $form['steps'] = [
      '#title' => $this->t('Field settings'),
      '#description' => '('.$this->t(
        "You may need to enable fields at !link",
        [
          '!link' => $this->l(
            $this->t('Manage form display'),
            Url::fromRoute(
              'entity.entity_form_display.mcapi_transaction.form_mode',
              ['form_mode_name' => 'default']
            )
          )
        ]
      ).')',
      '#type' => 'vertical_tabs',
      '#weight' => $w++,
      '#attributes' => new Attribute(['id' => ['field-display-overview-wrapper']]),
      //'#attributes' => ['id' => ['field-display-overview-wrapper'))
    ];

    $form['mywallet'] = [
      '#title' => $this->t('My wallets settings'),
      '#description' => $this->t("Choose from the current user's wallets."),
      '#type' => 'details',
      '#group' => 'steps',
      '#weight' => $w++
    ];
    $form['mywallet']['unused'] = [
      '#title' => $this->t('Unused behaviour'),
      '#description' => $this->t('What to do when the user has just one wallet?'),
      '#type' => 'radios',
      '#options' => [
        'normal' => $this->t('Normal'),
        'disabled' => $this->t('Greyed out'),
        'hidden' => $this->t('Disappeared'),
      ],
      '#default_value' => intval($configEntity->mywallet['unused']),
      '#required' => TRUE,
    ];

    $form['partner'] = [
      '#title' => $this->t('@fieldname settings', ['@fieldname' => $this->t('Partner')]),
      '#description' => $this->t('In complex sites, it may be possible to choose a user who cannot use the currency'),
      '#type' => 'details',
      '#group' => 'steps',
      '#weight' => $w++
    ];
    $form['partner']['preset'] = [
      '#title' => $this->t('Preset'),
      '#type' => 'select_wallet',
      '#role' => 'null',
      '#default_value' => $configEntity->partner['preset'] ? Wallet::load($configEntity->partner['preset']) : NULL,
      '#multiple' => FALSE,
      '#required' => FALSE
    ];

    $moreInfo = $this->t(
      'Put any default values here.') .' '.
      $this->l(
        $this->t('More field configuration options...'),
        Url::fromRoute('entity.entity_form_display.mcapi_transaction.default')
      );

    $definitions = \Drupal::entityManager()
      ->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
    $display = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('mcapi_transaction.mcapi_transaction.default');

    module_load_include('inc', 'mcapi_1stparty');
    $transaction = mcapi_forms_default_transaction($configEntity);
    $components = $display->getComponents();
    unset($components['payer'], $components['payee']);
    foreach (array_keys($components) as $field_name) {//created, categories, worth, description
      if ($field_name === 'created') continue;
      //assumes a cardinality of 1!
      $form['fieldapi_presets'][$field_name] = [
        '#title' => $definitions[$field_name]->getLabel(),
        '#description' => $moreInfo,
        '#type' => 'details',
        '#group' => 'steps',
        'preset' => $display->getRenderer($field_name)
          ->formElement($transaction->$field_name, 0, $element, $form, $form_state),
        '#weight' => $w++
      ];
      $form['fieldapi_presets'][$field_name]['preset']['#title'] = 'Preset value';
    }

    $form['fieldapi_presets']['#tree'] = TRUE;
    $form['fieldapi_presets']['worth']['preset']['#allowed_curr_ids'] = array_keys(entity_load_multiple('mcapi_currency'));
    //other modifications to the worth widget before it is processed
    $form['fieldapi_presets']['worth']['preset']['#config'] = TRUE;
    $form['fieldapi_presets']['worth']['preset']['#description'] = t('Currencies with blank in the left-most field will not appear on the form.') .' '.t('Leave every row blank to let the system decide which ones to show.');

    $form['experience'] = [
      '#title' => $this->t('User experience'),
      '#type' => 'details',
      '#open' => TRUE,
      'twig' => [
        '#title' => $this->t('Main form'),
        '#description' => implode(' ', [
          $this->t('Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.'),
          ' {{ '. implode(' }}, {{ ', mcapi_1stparty_transaction_tokens()) .' }}',
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
          TransitionManager::CONFIRM_NORMAL => $this->t('Basic - Go to a fresh page'),
          TransitionManager::CONFIRM_AJAX => $this->t('Ajax - Replace the form'),
          TransitionManager::CONFIRM_MODAL => $this->t('Modal - Confirm in a dialogue box')
        ],
        '#default_value' => $configEntity->experience['preview'],
        '#weight' => 3,
        '#required' => TRUE
      ],
      '#weight' => $w++,
    ];

    $form['#suffix'] = $this->t(
      "N.B The confirmation page is configured separately, at !link",
      ['!link' => $this->l(
        'admin/accounting/workflow/create',
        Url::fromRoute('mcapi.workflow_settings', ['transition' => 'create'])
      )]
    );
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::validate().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $max = $this->config('mcapi.wallets')->get('entity_types')['user:user'];
    $text = $form_state->getValue('experience')['twig'];
    $mywallet = !is_bool(strpos($txt, "{{ mywallet }}"));
    if ($max > 1 && !$mywallet) {
      $message = $this->t(
        '@token token is required in template',
        ['@token' => '{{ mywallet }}']
      );
    }
    elseif ($max < 2 && $mywallet){
      $message = $this->t(
        '@token token should be removed from template',
        ['@token' => '{{ mywallet }}']
      );
    }
    if ($error) {
      $form_state->setError($element, $message);
    }
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

  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
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

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t("Form '%label' has been updated.", ['%label' => $this->entity->get('title')]));
    }
    else {
      drupal_set_message(t("Form '%label' has been added.", ['%label' => $this->entity->get('title')]));
    }
    $form_state->setRedirect('mcapi.admin.transaction_form.list');
  }

  //is called from the form validator, so must be public
  public static function unique_path(&$element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $dupe = db_select('router', 'r')
      ->fields('r', ['name'])
      ->condition('name', 'mcapi.1stparty.'.$values['id'], '<>')
      ->condition('path', $values['path'])
      ->execute()->fetchField();
    if ($dupe) $form_state->setError('path', t('Path is already used.'));
  }

}
