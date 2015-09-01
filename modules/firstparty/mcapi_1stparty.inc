<?php

use \Drupal\Core\Render\Element;
use \Drupal\Core\Form\FormBuilder;
use \Drupal\Core\Config\Entity\ConfigEntityInterface;
use \Drupal\Core\Render\Element\InlineTemplate;

/**
 * theme implementation of callback 1stpartyform
 * this must be a function, because we are taking a twig string 
 * instead of using a template file
 */
function theme_1stpartyform($vars) {
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
      $tokens[$fieldname] = \Drupal::service('renderer')->render($form[$fieldname]);
    }
    //if it is in the template, render the token
    elseif (strpos($vars['twig_template'], '{{ '.$fieldname.' }}') !== FALSE) {
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
  $content = \Drupal::service('twig')->
    renderInline(str_replace("\n", '<br />', $vars['twig_template']), $tokens);
  return str_replace('</form>', $content . '</form>', $renderer->render($form));
   
  
  //if we render this using the inline_template element, specifically
  //InlineTemplate::preRenderInlineTemplate($element)
  //the form elements are filtered out later by drupal_render
  $output = \Drupal::service('twig')->
    renderInline(_filter_autop($vars['twig_template']), $tokens);
  
  
}

/**
 * theme preprocessor
 */
function template_preprocess_1stpartyform(&$vars) {
  $form = &$vars['form'];
  //add the buttons at the end if they weren't in the twig template already
  if (strpos($vars['twig_template'], '{{ actions }}') === FALSE) {
    $vars['twig_template'] .= "\n{{ actions }}";
  }

  //replace the partner and mywallet with payer and payee
  if ($vars['incoming']) {
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
  $vars['twig_template'] = strtr($vars['twig_template'], $replacements);
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
  while ($children = Element::Children($element)) {
    if (@$element['#type'] == 'worth')break;
    $first_child = reset($children);
    $element = $element[$first_child];
  }
  
  $nostrip = ['checkbox', 'checkboxes', 'radios'];

  if (!in_array($element['#type'], $nostrip)) {
    unset($element['#theme_wrappers']);
    //to make up for removing the wrapper
    $element['#prefix'] = '<div class = "form-item">';
    $element['#suffix'] = '</div>';
  }
  return \Drupal::service('renderer')->render($element);
}

/**
 * Make a transaction entity loaded up with the defaults from the Designed form
 *
 * @param Drupal\Core\Config\Entity\ConfigEntityInterface $config
 *
 * @return Transaction
 *   a partially populated transaction entity
 */
function mcapi_forms_default_transaction(ConfigEntityInterface $config) {
  //the partner is either the holder of the current page's entity, under certain circumstances
  //or is taken from the form preset.
  //or is yet to be determined.
  if (0) {//no notion of context has been introduced yet
    //infer the partner wallet from the the node ower or something like that
  }
  elseif($config->partner['preset']) {
    $partner = $config->partner['preset'];
  }
  else {
    $partner = '';
  }

  //prepare a transaction using the defaults here
  $vars = ['type' => $config->type];

  //now handle the payer and payee, based on partner and direction
  if ($config->incoming) {
    $vars['payee'] = \Drupal::currentUser()->id();
    $vars['payer'] = $partner;
  }
  else {
    $vars['payer'] = \Drupal::currentUser()->id();
    $vars['payee'] = $partner;
  }
  foreach ($config->fieldapi_presets as $fieldname => $setting) {
    if (!isset($setting['preset'])) drupal_Set_message('$fieldname has no preset');
    $vars[$fieldname] = $setting['preset'];
  }

  return \Drupal\mcapi\Entity\Transaction::create($vars);
}

