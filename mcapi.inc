<?php

use Drupal\Core\Language\Language;
//use Drupal\Core\Template\Attribute;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mcapi\TransactionInterface;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\mcapi\WalletInterface;
use Drupal\mcapi\CurrencyInterface;
use Drupal\mcapi\Entity\Wallet;
use Drupal\mcapi\Entity\Currency;
use Drupal\mcapi\Entity\Type;
use Drupal\mcapi\Entity\State;
use Drupal\mcapi\Mcapi;


function _mcapi_entity_extra_field_info() {
  //go through all the bundles which are configured to hold wallets
  $types = \Drupal::config('mcapi.wallets')->get('entity_types');
  foreach((array)$types as $entity_bundle => $max) {
    //if (!$max) continue; //don't show wallets on bundles with maximum of 0 wallets
    list($entity_type, $bundle) = explode(':', $entity_bundle);
    $extra[$entity_type] = array(
      $bundle => array(
        'display' => array(
          'wallets_summaries' => array(
            'label' => t('Wallets summaries'),
            'description' => t("Statistics from the user's wallets"),
            'weight' => 5,
          ),
        )
      )
    );
  }
  $w = 0;
  foreach (\Drupal::entityManager()->getViewBuilder('mcapi_wallet')->extraFields() as $id => $props) {
    $w++;
    $extra['mcapi_wallet']['mcapi_wallet']['display'][$id] = $props + array('weight' => $w);
  }

  $props = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  $fields = array('payer', 'payee', 'description', 'state', 'type');
  foreach ($fields as $weight => $fieldname) {
    $extra['mcapi_transaction']['mcapi_transaction']['display'][$fieldname] = array(
      'label' => $props[$fieldname]['label'],
      'description' => $props[$fieldname]['description'],
      'weight' => $weight,
    );
  }
  $extra['mcapi_transaction']['mcapi_transaction']['display']['transitions'] = array(
    'label' => t('Transitions'),
    'description' => t('Links to transitions between workflow states'),
    'weight' => 20,
  );
  return $extra;
}

function _mcapi_theme() {
  $items['mcapi_transaction'] = array(
    'render element' => 'transaction',
    'template' => 'transaction',
    'file' => 'mcapi.inc'
  );
  //the above is the theme wrapper for the below
  //so they both take the same render_element
  $items['certificate'] = array(
    'render element' => 'transaction',
    'template' => 'certificate',
    'pattern' => 'certificate__',
    'file' => 'mcapi.inc'
  );
  $items['sentence'] = array(
    'render element' => 'transaction',
    'file' => 'mcapi.inc'
  );

  $f = ['file' => 'src/ViewBuilder/wallet.inc'];
  $items['mcapi_wallets'] = $f + [
    'variables' => [
      'wallets' => NULL
    ]
  ];
  $items['wallet_wrapper'] = [
     'render element' => 'element'
  ];
  $items['wallet_balances'] = $f + [
    'variables' => [
      'wallet' => NULL,
      'currency' => NULL,
    ]
  ];
  $items['wallet_balances_currency'] = $f + [
    'variables' => [
      'wallet' => NULL,
      'currency' => NULL,
    ]
  ];
  $items['wallet_balance_bars'] = $f + [
    'variables' => [
      'wallet' => '',
      'currency' => NULL,
      'data' => []
    ]
  ];
  $items['wallet_history'] = $f + [
    'variables' => [
      'wallet' => NULL,
      'currency' => NULL,
      'width' => 250,
    ]
  ];
  $items['wallet_stats'] = $f + [
    'variables' => [
      'wallet' => NULL,
      'currency' => NULL
    ]
  ];
  return $items;
}

/**
 * theme implementation function for mcapi_wallets
 */
function template_preprocess_mcapi_wallets(&$vars) {
  //are the wallets aready themed?
//  if (!is_null($vars['wallets']))mtrace();
  foreach ($vars['wallets'] as $wid =>  $wallet) {
    $vars['wallets'][$wid] = drupal_render($wallet);
  }
}

/**
 * implements template_preprocess_THEMEHOOK for theme callback 'mcapi_transaction'
 * DO NOT OVERRIDE, javascript transitions depend on the classes defined here
 * override certificate or change the mcapi_sentence variable instead
 */
function template_preprocess_mcapi_transaction(&$vars) {
  $element = $vars['transaction'];
  $transaction = $element['#mcapi_transaction'];
  //don't know if this is needed in d8
  element_set_attributes($element, array('class', 'id'));
  $vars['attributes']['class'] = array(
    'transaction',
    $transaction->get('type')->value,
    'state-'.$transaction->state->target_id
  );
  if (!strpos($element['#view_mode'], ' ')) {//why would there be a space in the view mode and why would that affect its use as a class?
    $vars['attributes']['class'][] = $element['#view_mode'];
  }
  if (!$transaction->noLinks) {
    $vars['links'] = \Drupal::Entitymanager()->getViewBuilder('mcapi_transaction')->renderlinks($transaction, $element['#view_mode']);
  }
}

/**
 * implements template_preprocess_THEMEHOOK for theme callback 'certificate'
 *
 * @param array $vars
 */
function template_preprocess_certificate(&$vars) {
  $element = $vars['transaction'];
  $transaction = $element['#mcapi_transaction'];

  Mcapi::processTransactionVars($vars, $transaction);
  //hide the field api label
  foreach(Element::children($element) as $sub) {
    if (array_key_exists('#theme', $element[$sub]) && $element[$sub]['#theme'] == 'field') {
      $element[$sub]['#label_display'] = 'hidden';
    }
    $vars[$sub] = $element[$sub];
  }

  $vars['children'] = [];
  if (isset($transaction->children)) {
    foreach($transaction->children as $child) {
      $vars['children'][$child->id()] = entity_view($child, 'sentence');
      //TODO there's something nasty happening with #pre_render being added here
      //pre_render connects to the display modes, which we don't want
      unset($vars['children'][$child->id()]['#pre_render']);
    }
  }

  //choose the transaction theme template based on the first currency of the transaction
  //not sure if this is worth the processing power at the moment
  $vars['theme_hook_suggestions'][] = 'certificate__' . $transaction->get('type')->value;
}


/**
 * theme implementation for callback 'sentence'
 * to show the transaction in one line.
 *
 * @todo remove this in favour of TransactionViewBuilder
 */
function theme_sentence(&$vars) {
  die('theme_sentence()');//this shouldn't be used
  $element = $vars['transaction'];
  return \Drupal::Token()->replace(
    \Drupal::config('mcapi.misc')->get('sentence_template'),
    array('mcapi' => $element['#mcapi_transaction']),
    array('sanitize' => TRUE)
  );
}

/**
 * Prepares a transaction, and renders it using a passed twig template
 *
 * @param string $template
 * @param TransactionInterface $transaction
 *
 * @todo perhaps convert this theme function to use
 * https://api.drupal.org/api/drupal/core!vendor!twig!twig!lib!Twig!Extension!StringLoader.php/function/twig_template_from_string/8
 * New in D8 alpha12
 */
function mcapi_render_twig_transaction($template, TransactionInterface $transaction) {
  $vars = [];
  $xid = $transaction->id();
  //Here we have to get all the fieldAPI fields rendered and in $vars
  $entities = array($xid => $transaction);
  $build = \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple($entities, 'certificate');

  //hide the labels from the standard field rendering
  foreach(Element::children($build[$xid]) as $sub) {
    if ($build[$xid][$sub]['#theme'] == 'field') {
      $build[$xid][$sub]['#label_display'] = 'hidden';
    }
    $vars[$sub] = $build[$xid][$sub];
  }
  return \Drupal::service('twig')->renderInline($template, Mcapi::processTransactionVars($vars, $transaction));
}

