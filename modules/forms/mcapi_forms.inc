<?php

use \Drupal\Core\Render\Element;


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
  if (isset($element['#type']) && in_array($element['#type'], ['checkbox', 'checkboxes', 'radios'])) {
    return;
  }
  unset($element['#theme_wrappers'], $element['#title'], $element['#description']);
  foreach (Element::Children($element) as $delta) {
    mcapi_strip_wrappers_recursive($element[$delta]);
  }
}

