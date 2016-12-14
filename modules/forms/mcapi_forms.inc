<?php

/**
 * @file
 * Functions to build designed transaction forms.
 */

use Drupal\system\Entity\Menu;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\Core\Render\Element\PathElement;
use Drupal\Core\Render\Element;
use Drupal\mcapi\Plugin\TransactionActionBase;

/**
 * Theme preprocessor.
 *
 * Replace the partner and mywallet tokens with payer and payee;
 * Add the actions to the end of the template string.
 */
function template_preprocess_first_party_transaction_form(&$vars) {
  // Add the buttons at the end if they weren't in the twig template already.
  $form = &$vars['form'];
  $twig_template = $form['#twig_template'];
  if (strpos($twig_template, '{{ actions }}') === FALSE) {
    $twig_template .= "\n{{ actions }}";
  }
  $vars['twig_template'] = str_replace(['\r\n', '\n','\r'], '<br />', $twig_template);
}

/**
 * Theme implementation of callback 1stpartyform.
 *
 * This must be a function, because we are taking a twig string
 * instead of using a template file.
 */
function theme_first_party_transaction_form($vars) {
  $renderer = \Drupal::service('renderer');
  $form = $vars['form'];
  $form['#attributes']['class'][] = 'firstparty-form';
  $form['#attached']['library'][] = 'mcapi_forms/display';
  $tokens = ['hidden_elements' => []];
  // Append everything not rendered onto the last rendered thing.
  $vars['twig_template'] .= '{{ hidden_elements }}';
  $hidden = '';
  foreach (Element::children($form) as $fieldname) {
    if (isset($form[$fieldname]['#type']) && $form[$fieldname]['#type'] == 'actions') {
      $tokens[$fieldname] = $renderer->render($form[$fieldname]);
    }
    // If it is in the template, render the token.
    elseif ($fieldname === 'worth') {
      unset($form[$fieldname]['widget']['#title']);
      unset($form[$fieldname]['widget']['#description']);
      // don't want to strip the wrappers.
      $tokens[$fieldname] = $renderer->render($form['worth']);
    }
    elseif (strpos($vars['twig_template'], '{ ' . $fieldname . ' }}')) {
      mcapi_strip_wrappers_recursive($form[$fieldname]);
      $tokens[$fieldname] = $renderer->render($form[$fieldname]);
    }
    elseif (isset($form[$fieldname]['#type']) && ($form[$fieldname]['#type'] == 'hidden' || $form[$fieldname]['#type'] == 'token')) {
      $hidden .= $renderer->render($form[$fieldname]);
    }
    elseif (isset($form[$fieldname]['#type']) && $form[$fieldname]['#type'] == 'value') {

    }
    // Add the rendered token to the hidden elements.
    elseif (isset($form[$fieldname]['widget'])) {
      // This element would normally be rendered but isn't in the template,
      // so don't add it to the tokens.
    }
    // If it is required, and its not yet added as a token show an error.
    elseif ($form[$fieldname]['#required']) {
      // @todo unset and move message to watchdog
      drupal_set_message(t(
        "No default value for required hidden element '@name'",
        ['@name' => $fieldname]
      ), 'error');
    }
  }
  $renderable = [
    '#type' => 'inline_template',
    '#template' => _filter_autop($vars['twig_template']) . $hidden,
    '#context' => $tokens,
  ];
  return $renderer->render($renderable);
}

/**
 * Helper function.
 */
function mcapi_strip_wrappers_recursive(&$element) {
  $types = ['checkbox', 'checkboxes', 'radios', 'worths_form'];
  if (isset($element['#type']) && in_array($element['#type'], $types)) {
    return;
  }
  unset($element['#theme_wrappers'], $element['#title'], $element['#description']);
  foreach (Element::Children($element) as $delta) {
    mcapi_strip_wrappers_recursive($element[$delta]);
  }
}

/**
 * Alter the form EntityFormDisplay form.
 */
function mcapi_forms_display_decorate(&$form, FormState $form_state, $settings) {
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
  $form['routing'] = [
    '#title' => t('Routing'),
    '#type' => 'details',
    '#collapsible' => TRUE,
    '#tree' => FALSE,
    '#weight' => -5
  ];
  $form['routing']['path'] = [
    '#title' => t('Path'),
    '#description' => t('The url path at which this form will appear. Must begin with / and be unique.'),
    '#type' => 'path',
    '#default_value' => $settings['path'] ?: '/',
    '#placeholder' => '/path/to/form',
    '#convert_path' => PathElement::CONVERT_URL,
    '#element_validate' => ['mcapi_forms_unique_path'],
    '#required' => TRUE,
  ];
  $form['routing']['access_roles'] = [
    '#title' => t('Roles'),
    '#description' => t('Only the checked roles will be able to access this display.'),
    '#type' => 'checkboxes',
    '#default_value' => $settings['access_roles'],
    '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
    '#required' => TRUE
  ];

  $form['routing']['menu'] = [
    '#title' => t('Menu link'),
    '#description' => t('Currently there is no special access control for these form displays'),
    '#type' => 'fieldset',
    '#tree' => 0,
    '#weight' => 5,
    'menu_title' => [
      '#title' => t('Link title'),
      '#type' => 'textfield',
      '#default_value' => $settings['menu_title'],
      '#weight' => 1,
    ],
    'menu_weight' => [
      '#title' => t('Weight'),
      '#type' => 'weight',
      '#default_value' => $settings['menu_weight'],
      '#description' => t('In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
      '#weight' => 2,
    ],
  ];
  if (!\Drupal::moduleHandler()->moduleExists('menu_ui')) {
    \Drupal::service('module_installer')->install(['menu_ui']);
  }
  $custom_menus = [];
  foreach (Menu::loadMultiple() as $menu_parent => $menu) {
    $custom_menus[$menu_parent] = $menu->label();
  }
  $form['routing']['menu']['menu_parent'] = \Drupal::service('menu.parent_form_selector')->parentSelectElement(
    $settings['menu_parent']  ?: 'main:',
    'menu_link_content:7299677d-ac7b-4748-a2b8-d22cac2c9b62',
    menu_ui_get_menus()
  );

  $form['transaction_type'] = [
    '#type' => 'mcapi_types',
    '#description' => t('This also determines the starting state of the transaction.'),
    '#default_value' => $settings['transaction_type'],
    '#weight' => -8,
    '#required' => TRUE,
  ];

  list($required, $optional) = mcapi_forms_required_fields();
  // Remove invisible fields
  $invisible = ['xid', 'uuid', 'serial', 'parent', 'creator', 'type', 'state', 'changed'];

  $required = t(
    'Required fields: @fields.',
    ['@fields' => ' {{ ' . implode(' }}, {{ ', array_diff($required, $invisible)) . ' }}']
  );
  $optional = t(
    'Optional fields: @fields.',
    ['@fields' => ' {{ ' . implode(' }}, {{ ', array_diff($optional, $invisible)) . ' }}']
  );

  $form['experience'] = [
    '#title' => t('User experience'),
    '#type' => 'details',
    '#open' => TRUE,
    'twig' => [
      '#title' => t('Main form twig'),
      '#description' => implode(' ', [
        t('Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.'),
        $required .' '.$optional . ' ' .
        \Drupal::linkGenerator()->generate(
          t('What is twig?'),
          Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
        ),
      ]),
      '#type' => 'textarea',
      '#rows' => 6,
      '#default_value' => str_replace(array('\r\n', '\n', '\r'), "\n", $settings['experience_twig']),
      '#element_validate' => ['validate_twig_template'],
      '#weight' => 1,
      '#required' => TRUE,
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
        TransactionActionBase::CONFIRM_MODAL => t('Modal - Confirm in a dialogue box'),
      ],
      '#default_value' => $settings['experience_preview'],
      '#weight' => 3,
      '#required' => TRUE,
    ],
    '#weight' => $w++,
  ];

  $form['#suffix'] = t(
    "N.B The confirmation page is configured separately, at %link",
    [
      '%link' => \Drupal::linkGenerator()->generate(
      'admin/accounting/workflow/save',
      Url::fromRoute('entity.action.edit_form', ['action' => 'transaction_save'])
      ),
    ]
  );
  $form_state->set('original_roles', $settings['access_roles']);
  $form['actions']['submit']['#submit'][] = 'mcapi_forms_display_submit';
}

/**
 * Form submit callback.
 */
function mcapi_forms_display_submit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $vals = &$form_state->getValues();
  $settings = [
    'title' => $vals['form_title'],
    'wallet_link_title' => $vals['wallet_link_title'],
    'transaction_type' => $vals['transaction_type'],
    'path' => $vals['path'],
    'access_roles' => $vals['access_roles'],
    'menu_title' => $vals['menu_title'],
    'menu_weight' => $vals['menu_weight'],
    'menu_parent' => $vals['menu_parent'],
    'experience_twig' => $vals['experience']['twig'],
    'experience_button' => $vals['experience']['button'],
    'experience_preview' => $vals['experience']['preview'],
  ];
  $form_state->getFormObject()->getEntity()
    ->setThirdpartySetting('mcapi_forms', 'settings', $settings)
    ->save();

  if ($form_state->get('access_roles') != $settings['access_roles']) {
    \Drupal::service('router.builder')->setRebuildNeeded();
  }
}

/**
 * Check the required transaction form elements are either preset OR in Twig.
 */
function validate_twig_template(array $element, $form_state) {
  $required = mcapi_forms_required_fields()[0];
  $invisible = ['xid', 'uuid', 'serial', 'parent', 'creator', 'type', 'state', 'changed'];
  unset($required['creator']);
  $errors = [];
  // Find the required field and ensure they are all here.
  // Ensure the worth field is present.
  foreach ($required as $fieldname) {
    if ( (!in_array($fieldname, $invisible)) && strpos($element['#value'], '{{ '.$fieldname. ' }}') == NULL) {
      $form_state->setError(
        $element,
        t(
          '@fieldname MUST appear in the @title template.',
          [
            '@fieldname' => '{{ '.$fieldname.' }}',
            '@title' => $element['#title'],
          ]
        )
      );
    }
  }
}

/**
 * Is called from the form validator, so must be public.
 */
function mcapi_forms_unique_path(&$element, $form_state) {
  $new = Url::fromUserInput($form_state->getValue('path'))->getRouteName();
  if ($new && substr($new, 15) != $form_state->getFormObject()->getEntity()->get('mode')) {
    drupal_set_message(t('N.B. This has overriden an existing path!'), 'warning');
  }
}

/**
 * Get the transaction fields, required, and optional
 *
 * @return array
 *   Containing 2 arrays, one of required field names, the other optional.
 */
function mcapi_forms_required_fields() {
  $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  foreach ($fields as $field_id => $field) {
    if ($field->isrequired()) {
      $required[] = $field_id;
    }
    else {
       $optional[] = $field_id;
    }
  }
  return [$required, $optional];
}