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
    // Handle the worth field
    elseif ($fieldname === 'worth') {
      unset($form[$fieldname]['widget']['#title']);
      unset($form[$fieldname]['widget']['#description']);
      // don't want to strip the wrappers.
      $tokens[$fieldname] = $renderer->render($form['worth']);
    }
    // If it is in the template, render the token.
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
  $w = 0;
  $form['title'] = [
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

  // NB this is copied straight from Drupal\user\Plugin\views\access\Permission
  foreach (\Drupal::service('user.permissions')->getPermissions() as $perm => $perm_item) {
    $provider = $perm_item['provider'];
    $display_name = \Drupal::moduleHandler()->getName($provider);
    $perms[$display_name][$perm] = strip_tags($perm_item['title']);
  }
  $form['routing']['permission'] = array(
    '#type' => 'select',
    '#options' => $perms,
    '#title' => t('Restricted to Permission'),
    '#default_value' => $settings['permission'],
    '#description' => t('Leave blank to allow all users (with a wallets) to use this form.'),
    '#multiple' => FALSE
  );

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
    '#type' => 'fieldset',
    '#open' => TRUE,
    'experience_twig' => [
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
    'experience_button' => [
      '#title' => t('Button label'),
      '#description' => t("The text to appear on the 'save' button, or the absolute url of an image."),
      '#type' => 'textfield',
      '#default_value' => $settings['experience_button'],
      '#required' => TRUE,
      '#weight' => 2,
    ],
    'experience_preview' => [
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
  $form['actions']['submit']['#submit'][] = 'mcapi_forms_display_submit';
}

/**
 * Form submit callback.
 */
function mcapi_forms_display_submit($form, $form_state) {
  $entity = $form_state->getFormObject()->getEntity();
  $fields = [
    'title',
    'wallet_link_title',
    'transaction_type',
    'path',
    'permission',
    'menu_title',
    'menu_weight',
    'menu_parent',
    'experience_twig',
    'experience_button',
    'experience_preview'
  ];
  foreach ($fields as $settingname) {
    $entity->setThirdpartySetting('mcapi_forms', $settingname, $form_state->getValue($settingname));
  }
  $entity->save();
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
 * Form element validation ensuring that the given path is unique.
 */
function mcapi_forms_unique_path(&$element, $form_state) {
  $url = Url::fromUserInput($form_state->getValue('path'));
  if ($url->isRouted()) {
    if (substr($url->getRouteName(), 15) != $form_state->getFormObject()->getEntity()->get('mode')) {
      drupal_set_message(t('N.B. This has overriden an existing path!'), 'warning');
    }
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

/**
 * Get only the translated field names used in the template.
 *
 * @param string $template
 * @param string $direction
 *
 * @return array
 *   The field names used in the templated, mapped to the modern names.
 */
function mcapi_forms_migrate_template_field_map($template, $direction) {
  //Get the tokens used in the old template
  preg_match_all('/\[mcapiform:([a-z_]+)\]/', $template, $matches);
  $old_tokens = $matches[1];
  unset($old_tokens[array_search('direction', $old_tokens)]);
  return mcapi_forms_migrate_field_map($old_tokens, $direction);
}

/**
 * Get the map of all old field names to new
 * @param array $defaults
 * @param string $direction
 *
 * @return array
 *   The old and new fieldnames
 */
function mcapi_forms_migrate_field_map($defaults, $direction) {
  $mapping = [
   "offers_wants_categories" => "category",
   "transaction_description" => "description"
  ] + array_combine($defaults, $defaults);
  if ($direction == 'outgoing') {
    $mapping['secondperson'] = 'payee';
  }
  else {
    $mapping['secondperson'] = 'payer';
  }
  return $mapping;
}
