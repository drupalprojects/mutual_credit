<?php

use Drupal\Core\Render\Element;
use Drupal\mcapi\Plugin\TransactionActionBase;

/**
 * theme preprocessor
 * Replace the partner and mywallet tokens with payer and payee;
 * Add the actions to the end of the template string
 */
function template_preprocess_first_party_transaction_form(&$vars) {
  //add the buttons at the end if they weren't in the twig template already
  $form = &$vars['form'];
  $twig_template = $form['#twig_template'];
  if (strpos($twig_template, '{{ actions }}') === FALSE) {
    $twig_template .= "\n{{ actions }}";
  }
  $vars['twig_template'] = $twig_template;
}

/**
 * theme implementation of callback 1stpartyform
 * this must be a function, because we are taking a twig string
 * instead of using a template file
 */
function theme_first_party_transaction_form($vars) {
  $renderer = \Drupal::service('renderer');
  $form = $vars['form'];
  $form['#attributes']['class'][] = 'firstparty-form';
  $form['#attached']['library'][] = 'mcapi_forms/display';
  $tokens = ['hidden_elements' => []];
  //append everything not rendered onto the last rendered thing.
  $vars['twig_template'] .= '{{ hidden_elements }}';
  $hidden = '';
  foreach (Element::children($form) as $fieldname) {
    if($form[$fieldname]['#type'] == 'actions') {
      $tokens[$fieldname] = $renderer->render($form[$fieldname]);
    }
    //if it is in the template, render the token
    elseif ($fieldname === 'worth') {
      unset($form[$fieldname]['widget']['#title']);
      unset($form[$fieldname]['widget']['#description']);
      // don't want to strip the wrappers
      $tokens[$fieldname] = $renderer->render($form['worth']);
    }
    elseif (strpos($vars['twig_template'], '{ '.$fieldname.' }}')) {
      mcapi_strip_wrappers_recursive($form[$fieldname]);
      $tokens[$fieldname] = $renderer->render($form[$fieldname]);
    }
    elseif($form[$fieldname]['#type'] == 'hidden' || $form[$fieldname]['#type'] == 'token') {
      $hidden .= $renderer->render($form[$fieldname]);
    }
    elseif($form[$fieldname]['#type'] == 'value') {

    }
    //add the rendered token to the hidden elements
    elseif (isset($form[$fieldname]['widget'])) {
      //this element would normally be rendered but isn't in the template, so don't add it to the tokens
    }
    //if it is required, and its not yet added as a token show an error
    elseif ($form[$fieldname]['#required']) {
      //@todo unset and move message to watchdog
      drupal_set_message(t(
        "No default value for required hidden element '@name'",
        ['@name' => $fieldname]
      ), 'error');
    }
  }
  $renderable = [
    '#type' => 'inline_template',
    '#template' => _filter_autop($vars['twig_template']) . $hidden,
    '#context' => $tokens
  ];
  return $renderer->render($renderable);
}


/*
 * helper function
 */
function mcapi_strip_wrappers_recursive(&$element) {
  if (isset($element['#type']) && in_array($element['#type'], ['checkbox', 'checkboxes', 'radios', 'worths_form'])) {
    return;
  }
  unset($element['#theme_wrappers'], $element['#title'], $element['#description']);
  foreach (Element::Children($element) as $delta) {
    mcapi_strip_wrappers_recursive($element[$delta]);
  }
}

/**
 * alter the form EntityFormDisplay form
 */
function mcapi_forms_display_decorate(&$form, $settings) {
  $form['#tree'] = TRUE;
  $w = 0;
  $form['form_title'] = [
    '#type' => 'textfield',
    '#title' => t('Title of the form'),
    '#default_value' => $settings['title'],
    '#size' => 40,
    '#maxlength' => 80,
    '#required' => TRUE,
    '#weight' => -10,
  ];
  $form['wallet_link_title'] = [
    '#title' => t('Link title to show on wallet'),
    '#description' => t('Optionally use token [mcapi_wallet:name]'),
    '#type' => 'textfield',
    '#weight' => -9,
    '#default_value' => $settings['wallet_link_title'],
  ];

  $form['path'] = [
    '#title' => t('Path'),
    '#description' => t('The url path at which this form will appear. Must begin with / and be unique.'),
    '#type' => 'path',
    '#weight' => -8,
    '#default_value' => $settings['path'],
    '#placeholder' => t('/internal/path'),
    '#convert_path' => \Drupal\Core\Render\Element\PathElement::CONVERT_URL,
    '#element_validate' => ['mcapi_forms_unique_path'],
    '#required' => TRUE
  ];

  $form['menu'] = [
    '#title' => t('Menu link'),
    '#description' => t('Currently there is no special access control for these form displays'),
    '#type' => 'details',
    '#weight' => -5,
    'title' => [
      '#title' => t('Link title'),
      '#type' => 'textfield',
      '#default_value' => $settings['menu_title'],
      '#weight' => 1
    ],
    'weight' => [
      '#title' => t('Weight'),
      '#type' => 'weight',
      '#default_value' => $settings['menu_weight'],
      '#description' => t('In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
      '#weight' => 2
    ]
  ];

  foreach (\Drupal\system\Entity\Menu::loadMultiple() as $menu_parent => $menu) {
    $custom_menus[$menu_parent] = $menu->label();
  }
  debug($settings['menu_parent']);
  $form['menu']['menu_parent'] = \Drupal::service('menu.parent_form_selector')->parentSelectElement(
    $settings['menu_parent']  ?: 'main:',
    'menu_link_content:7299677d-ac7b-4748-a2b8-d22cac2c9b62',
    menu_ui_get_menus()
  );

  $form['type'] =  [
    '#type' => 'mcapi_types',
    '#default_value' => $settings['type'],
    '#weight' => -3,
    '#required' => TRUE,
  ];
  $form['direction'] = [
    '#title' => t('Direction'),
    '#description' => t('Direction of payment relative to the current user'),
    '#type' => 'radios',
    '#options' => [
      MCAPI_FORMS_DIR_BOTH => t("Both (Permission '@permission' required)", ['@permission' => t('Create 3rdparty transactions')], ['context' => 'payment direction']),
      MCAPI_FORMS_DIR_OUTGOING => t('Outgoing', [], ['context' => 'payment direction']),
      MCAPI_FORMS_DIR_INCOMING => t('Incoming', [], ['context' => 'payment direction'])
    ],
    '#default_value' => $settings['direction'],
    '#required' => TRUE,
    '#weight' => -2
  ];

  $moreInfo = t(
    'Put any default values here.') .' '.
    \Drupal::linkGenerator()->generate(
      t('More field configuration options...'),
      \Drupal\Core\Url::fromRoute('entity.entity_form_display.mcapi_transaction.default')
    );

  $all_fields = \Drupal::service('entity_field.manager')
    ->getFieldMap()['mcapi_transaction'];

  $tokens = array_diff(
    array_keys($all_fields),
    ['xid', 'uuid', 'serial', 'parent', 'creator', 'type', 'state', 'changed']
  );
  $form['experience'] = [
    '#title' => t('User experience'),
    '#type' => 'details',
    '#open' => TRUE,
    'twig' => [
      '#title' => t('Main form'),
      '#description' => implode(' ', [
        t('Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.'),
        ' {{ '. implode(' }}, {{ ', $tokens) .' }}',
        \Drupal::linkGenerator()->generate(
          t('What is twig?'),
          \Drupal\Core\Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
        )
      ]),
      '#type' => 'textarea',
      '#rows' => 6,
      '#default_value' => str_replace(array('\r\n','\n','\r'), "\n", $settings['experience_twig']),
      '#element_validate' => ['validate_twig_template'],
      '#weight' => 1,
      '#required' => TRUE
    ],
    'button' => [
      '#title' => t('Button label'),
      '#description' => t("The text to appear on the 'save' button, or the absolute url of an image."),
      '#type' => 'textfield',
      '#default_value' => $settings['experience_button'],
      '#required' => TRUE,
      '#weight' => 2,
    ],
    'preview' => [
      '#title' => t('Preview mode'),
      '#type' => 'radios',
      '#options' => [
        TransactionActionBase::CONFIRM_NORMAL => t('Basic - Go to a fresh page'),
        TransactionActionBase::CONFIRM_AJAX => t('Ajax - Replace the form'),
        TransactionActionBase::CONFIRM_MODAL => t('Modal - Confirm in a dialogue box')
      ],
      '#default_value' => $settings['experience_preview'],
      '#weight' => 3,
      '#required' => TRUE
    ],
    '#weight' => $w++,
  ];

  $form['#suffix'] = t(
    "N.B The confirmation page is configured separately, at %link",
    ['%link' => \Drupal::linkGenerator()->generate(
      'admin/accounting/workflow/save',
      \Drupal\Core\Url::fromRoute('entity.action.edit_form', ['action' => 'transaction_save'])
    )]
  );

  $form['actions']['submit']['#submit'][] = 'mcapi_forms_display_submit';
}

/**
 * form submit callback
 */
function mcapi_forms_display_submit($form, $form_state) {
  $entity = $form_state->getFormObject()->getEntity();
  $entity->setThirdpartySetting('mcapi_forms', 'title', $form_state->getValue('form_title'));
  $entity->setThirdpartySetting('mcapi_forms', 'wallet_link_title', $form_state->getValue('wallet_link_title'));
  $entity->setThirdpartySetting('mcapi_forms', 'path', $form_state->getValue('path'));
  $menu = $form_state->getValue('menu');
  $entity->setThirdpartySetting('mcapi_forms', 'menu_title', $menu['title']);
  $entity->setThirdpartySetting('mcapi_forms', 'menu_weight',  $menu['weight']);
  $entity->setThirdpartySetting('mcapi_forms', 'menu_parent',  $menu['menu_parent']);
  $entity->setThirdpartySetting('mcapi_forms', 'type', $form_state->getValue('type'));
  $entity->setThirdpartySetting('mcapi_forms', 'direction', $form_state->getValue('direction'));
  $experience = $form_state->getValue('experience');
  $entity->setThirdpartySetting('mcapi_forms', 'experience_twig', $experience['twig']);
  $entity->setThirdpartySetting('mcapi_forms', 'experience_button', $experience['button']);
  $entity->setThirdpartySetting('mcapi_forms', 'experience_preview', $experience['preview']);
  $entity->save();
}

//check that the required transaction form elements either have preset OR are in the Twig
function validate_twig_template(array $element, $form_state) {
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
      t(
        'Field @fieldname neither appears in the form, nor has a preset value',
        ['@fieldname' => $element['#title']]
      )
    );
  }
}

  //is called from the form validator, so must be public
  //@todo
  function mcapi_forms_unique_path(&$element, $form_state) {
    drupal_set_message('need to check for a unique path');
    return;

    $values = $form_state->getValues();
    $dupe = \Drupal::database()->select('router', 'r')
      ->fields('r', ['name'])
      ->condition('name', 'mcapi.1stparty.'.$values['id'], '<>')
      ->condition('path', $values['path'])
      ->execute()->fetchField();
    if ($dupe) $form_state->setError('path', t('Path is already used.'));

  }