<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\Form\FirstPartyFormDesigner.
 * This configuration entity is used for generating transaction forms.
 */

namespace Drupal\mcapi_1stparty\Form;

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

    $default_fields = mcapi_default_display_fields();
    unset($default_fields['payer'], $default_fields['payee']);

    $configEntity = $this->entity;
    $configEntity->set('fieldapi_presets', []);
    $transaction = $this->makeTemplate($configEntity);

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
    ];

    //following section of the form allows the admin to handle the individual fields of the transaction form.
    //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
    $form['steps'] = [
      '#title' => $this->t('Field settings'),
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
    $form['mywallet']['widget'] = [
      '#title' => $this->t('Widget'),
      '#description' => $this->t('Only for users with more than one wallet.'),
      '#type' => 'radios',
      '#options' => [
        'select' => $this->t('Dropdown'),
        'radios' => $this->t('Radio buttons'),
      ],
      '#default_value' => $configEntity->mywallet['widget'],
    ];
    $form['mywallet']['unused'] = [
      '#title' => $this->t('Unused behaviour'),
      '#description' => $this->t('What to do when the user has just one wallet?'),
      '#type' => 'radios',
      '#options' => [
        'disabled' => $this->t('Greyed out'),
        'hidden' => $this->t('Disappeared'),
      ],
      '#default_value' => intval($configEntity->mywallet['unused']),
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
      '#default_value' => $configEntity->partner['preset'],
      '#multiple' => FALSE,
      '#required' => FALSE
    ];


    $form['direction'] = [
      '#title' => $this->t('@fieldname settings', ['@fieldname' => t('Direction')]),
      '#description' => $this->t('Direction relative to the current user'),
      '#type' => 'details',
      '#group' => 'steps',
      'preset' => [
        '#title' => $this->t('Preset'),
        '#description' => $this->t("Either 'incoming' or 'outgoing' relative to the logged in user"),
        '#type' => $configEntity->direction['widget'],
        //ideally these options labels wod be live updated from the fields below
        '#options' => [
          '' => $this->t('Neither'),
          'incoming' => empty($configEntity->direction['incoming']) ? $this->t('Incoming') : $configEntity->direction['incoming'],
          'outgoing' => empty($configEntity->direction['outgoing']) ? $this->t('Outgoing') : $configEntity->direction['outgoing'],
        ],
        '#default_value' => $configEntity->direction['preset'],
        '#required' => TRUE
      ],
      'widget' => [
        '#title' => $this->t('Widget'),
        '#type' => 'radios',
        '#options' => [
          'select' => $this->t('Dropdown select box'),
          'radios' => $this->t('Radio buttons')
        ],
        '#default_value' => $configEntity->direction['widget'],
        '#required' => TRUE,
        '#weight' => 1,
      ],
      'incoming' => [
        '#title' => $this->t("@label option label", ['@label' => $this->t('Incoming')]),
        '#type' => 'textfield',
        '#default_value' => $configEntity->direction['incoming'],
        '#placeholder' => $this->t('Pay'),
        '#required' => TRUE,
        '#weight' => 2
      ],
      'outgoing' => [
        '#title' => $this->t("@label option label",  ['@label' => $this->t('Outgoing')]),
        '#type' => 'textfield',
        '#default_value' => $configEntity->direction['outgoing'],
        '#placeholder' => $this->t('Request'),
        '#required' => TRUE,
        '#weight' => 3
      ],
      '#weight' => $w++
    ];

    $preset = $default_fields['description']['widget']
      ->formElement($transaction->description, 0, $element, $form, $form_state);
    $form['description'] = [
      '#title' => $this->t('@fieldname settings', ['@fieldname' => $this->t('Description')]),
      '#description' => $this->t('Direction relative to the current user'),
      '#type' => 'details',
      '#group' => 'steps',
      'placeholder' => [
        '#title' => $this->t('Placeholder'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->description['placeholder'],
        '#required' => FALSE,
      ],
      'preset' => $preset['value'] + ['#title' => $this->t('Preset')],
      '#weight' => $w++
    ];
    if ($this->moduleHandler->moduleExists('field_ui')) {
      //iterate through the field api fields adding a vertical tab for each

      $moreInfo = $this->t(
        'For more field configuration options, see !link',
        ['!link' => $this->l(
          'admin/accounting/transactions/form-display',
          Url::fromRoute('entity.entity_form_display.mcapi_transaction.default')
        )]
      );

      unset($default_fields['description']);
      //created, categories, worth
      foreach ($default_fields as $field_name => $data) {
        //$data is an array with 'definition' and 'widget'
        extract($data);
        //this element will contain the default value ONLY for the fieldAPI element
        //assumes a cardinality of 1!
        $form[$field_name] = [
          '#title' => $this->t(
            '@fieldname preset',
            ['@fieldname' => $data['definition']->getLabel()]
          ),
          '#description' => $moreInfo,
          '#type' => 'details',
          '#group' => 'steps',
          'preset' => $data['widget']->formElement($transaction->$field_name, 0, $element, $form, $form_state),
          '#weight' => $w++
        ];
      }
    }
    foreach (Element::children($form) as $child) {
      //@todo test these are saved and retrieved, then make the strip work.
      if (isset($form[$child]['#group'])) {
        $form[$child]['stripped'] = [
          '#title' => $this->t('Remove the outer div with the title and description.'),
          '#type' => 'checkbox',
          '#default_value' => FALSE,
          '#weight' => 5
        ];
      }
    }

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
          MCAPI_CONFIRM_NORMAL => $this->t('Basic - Go to a fresh page'),
          MCAPI_CONFIRM_AJAX => $this->t('Ajax - Replace the form'),
          MCAPI_CONFIRM_MODAL => $this->t('Modal - Confirm in a dialogue box')
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
    //we need to alter the structure a bit for the fieldAPI fields
    foreach ($values['fieldapi_presets'] as $field_name => $data) {
      $values['fieldapi_presets'][$field_name] = $data['preset'];
    }
    $form_state->setValue('fieldapi_presets', $values['fieldapi_presets']);
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
    $form_state->setRedirect('mcapi.admin_1stparty_editform_list');
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

  /**
   * Make a transaction entity loaded up with the defaults from the Designed form
   * @param object $configEntity
   * @return mcapi_transaction
   *   a partially populated transaction entity
   */
  private function makeTemplate($configEntity) {
    $props = [
      'type' => $configEntity->get('type'),
      'description' => $configEntity->get('description')['preset']
    ];
    //we can't set a default for mywallet because it is differnet for every user
    if ($configEntity->get('direction')['preset'] == 'outgoing') {
      $props['payee'] = $configEntity->get('partner')['preset'];
    }
    else {
      $props['payer'] = $configEntity->get('partner')['preset'];
    }
    foreach ($configEntity->get('fieldapi_presets') as $fieldname => $value) {
      $props[$fieldname] = $value;
    }
    //so the worth defaults have been copied right out of the saved entity
    return Transaction::Create($props);
  }

}


