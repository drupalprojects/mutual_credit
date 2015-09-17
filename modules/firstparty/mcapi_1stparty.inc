<?php

use \Drupal\Core\Render\Element;


/**
 * theme preprocessor
 * Replace the partner and mywallet tokens with payer and payee; 
 * Add the actions to the end of the template string
 */
function template_preprocess_first_party_transaction_form(&$vars) {  
  //add the buttons at the end if they weren't in the twig template already
  if (strpos($vars['twig_template'], '{{ actions }}') === FALSE) {
    $twig_template = $vars['form']['#twig_template'] . "\n{{ actions }}";
  }

  //replace the partner and mywallet with payer and payee
  if ($vars['form']['#incoming']) {
    $replacements = [
      '{{ partner }}' => '{{ payer }}',
      '{{ mywallet }}' => '{{ payee }}',
    ];
  }
  else {
    $replacements = [
      '{{ partner }}' => '{{ payee }}',
      '{{ mywallet }}' => '{{ payer }}',
    ];
  }
  $vars['twig_template'] = strtr($twig_template, $replacements);
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
  $form['#attached']['library'][] = 'mcapi_1stparty/display';
  $tokens = ['hidden_elements' => []];
  //append everything not rendered onto the last rendered thing.
  $vars['twig_template'] .= '{{ hidden_elements }}';
  foreach (Element::children($form) as $fieldname) {
    //mywallet is never stripped because its visibility in the template is
    //context sensitive.
    if ($fieldname == 'mywallet' && $tokens[$fieldname]['#type'] != 'value') {
      $tokens[$fieldname] = $renderer->render($form[$fieldname]);
    }
    elseif($form[$fieldname]['#type'] == 'actions') {
      $tokens[$fieldname] = $renderer->render($form[$fieldname]);
    }
    //if it is in the template, render the token
    elseif (strpos($vars['twig_template'], '{ '.$fieldname.' }}')) {
      $tokens[$fieldname] = mcapi_render_stripped($form[$fieldname]);
    }
    elseif($form[$fieldname]['#type'] == 'hidden' or $form[$fieldname]['#type'] == 'value') {
      //nothing, these will be rendered automatically
    }
    //add the rendered token to the hidden elements
    elseif (@$form[$fieldname]['widget']) {
      $hidden[] = 'field-name-'.$fieldname;
    }
    //if it is required, and its not yet added as a token show an error
    elseif ($form[$fieldname]['#required']) {
      //@todo unset and move message to watchdog
      drupal_set_message(t(
        'No default value for required hidden element @name',
        ['@name' => $fieldname]
      ), 'error');
    }
  }
  //unset any form elements which have tokens
  foreach (array_keys($tokens) as $fieldname) {
    unset($form[$fieldname]);
  }
  $template = _filter_autop($vars['twig_template']);
  $renderable = [
    '#type' => 'inline_template',
    '#template' => $template,
    '#context' => $tokens
  ];
  //if we render this using the inline_template element, specifically
  //InlineTemplate::preRenderInlineTemplate($element)
  //the form elements are filtered out later by drupal_render
  return \Drupal::service('twig')->
    renderInline($template, $tokens);
}

/**
 * callback to render form elements without their theme_wrappers
 * simply stripping the theme_wrappers is cleaner but prevents the #states from working.
 *
 * @param array $element
 *
 * @return string
 *   HTML
 */
function mcapi_render_stripped($element) {
  if (isset($element['widget'])) {
    $element = $element['widget'];
  }
  if (!in_array($element['#type'], ['checkbox', 'checkboxes', 'radios'])) {
    mcapi_strip_wrappers_recursive($element);
  }
  return \Drupal::service('renderer')->render($element);
}
/*
 * helper function
 */
function mcapi_strip_wrappers_recursive(&$element) {
  unset($element['#theme_wrappers']);
  foreach (Element::Children($element) as $delta) {
    mcapi_strip_wrappers_recursive($element[$delta]);
  }
}

