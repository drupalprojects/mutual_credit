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
use Drupal\mcapi\Entity\Exchange;
use Drupal\mcapi\Entity\State;


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
  //wallet diplays
  $extra['mcapi_wallet']['mcapi_wallet']['display'] = array(
    'owner' => array(
      'label' => t('Owner'),
      'description' => t('Link to the url of the wallets owner entity'),
      'weight' => 1,
    ),
    'stats' => array(
      'label' => t('Trading stats'), 'description' => t('Grid showing trading stats for all currencies'),
      'weight' => 2
    ),
    'balance_bars' => array(
      'label' => t('Balance bar charts'),
      'description' => t('One barchart per currency showing incoming and outgoing volumes'),
      'weight' => 3,
    ),
    'histories' => array(
      'label' => t('History chart(s)'),
      'description' => t('One line chart per currency showing balance over time.'),
      'weight' => 4
    ),
    'summaries' => array(
      'label' => t('Balance(s)'),
      'description' => t('Small thingy showing balances of all currencies'),
      'weight' => 5
    ),
    'links' => array(
      'label' => t('Links(s)'),
      'description' => t('Links to the wallets transactions / summary / edit pages, if permissions allow'),
      'weight' => 6
    ),
  );

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
  //exchanges
  $extra['mcapi_exchange']['mcapi_exchange']['display']['people'] = array(
    'label' => t('People'),
    'description' => t('People involved in the exchange'),
    'weight' => 1,
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
  $items['mcapi_wallet'] = array(
    'render element' => 'mcapi_wallet',
    'template' => 'wallet',
    'file' => 'extras/wallet_preprocess.inc'
  );
  $items['wallet_balance_bars'] = array(
    'template' => 'wallet_balance_bars',
    'variables' => array(
      'wallet' => '',
      'currency' => NULL,
      'data' => array()
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  $items['wallet_history'] = array(
    'template' => 'wallet_history',
    'variables' => array(
      'wallet' => NULL,
      'currency' => NULL,
      'width' => 250,
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  $items['wallet_summary'] = array(
    'template' => 'wallet_summary',
    'variables' => array(
      'wallet' => NULL,
      'currencies' => array(),
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  $items['wallet_summary_currency'] = array(
    'template' => 'wallet_summary_currency',
    'variables' => array(
      'wallet' => NULL,
      'currency' => NULL,
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  $items['wallets'] = array(
    'template' => 'wallets',
    'variables' => array(
      'wallets' => array()
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  $items['wallet_stats'] = array(
    'template' => 'wallet_stats',
    'variables' => array(
      'wallet' => NULL,
      'currency' => NULL
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  return $items;
}

/**
 * Show an overview of every wallet owned by an entity.
 *
 * @param ContentEntityInterface $entity
 *
 * @return array
 *   a renderable array
 *   
 * @todo should this be elsewhere, like WalletViewBuilder?
 */
function mcapi_view_wallets_summaries(ContentEntityInterface $entity) {
  $renderable = array();
  $owned_wallet_ids = \Drupal::EntityManager()
    ->getStorage('mcapi_wallet')
    ->getOwnedIds($entity);
  foreach (Wallet::loadMultiple($owned_wallet_ids) as $wallet) {
    if ($wallet->access('view')) {
      $renderable['wallets']['#wallets'][] = show_wallet_summary($wallet);
    }
  }
  $renderable['wallets']['#theme'] = 'wallets';
  $renderable['wallets']['#weight'] = '0';
  //show the add new wallet link
  if (\Drupal::config('mcapi.wallets')->get('add_link_location') != 'local_action') {
    $route_name = 'mcapi.wallet.add.'.$entity->getEntityTypeId();
    $route_params = array('user' => $entity->id());
    $access_manager = \Drupal::getContainer()->get('access_manager');
    if ($access_manager->checkNamedRoute($route_name, $route_params, \Drupal::currentUser())) {
      $renderable['add_wallet_link'] = array(
        '#type' => 'link',
        '#title' => t('Add wallet'),
        '#route_name' => $route_name,
        '#route_parameters' => $route_params,
        '#weight' => 2,
        '#options' => array(
          'attributes' => new Attribute(array(
            'class' => array('add-wallet-link')
          )),
          '#attributes' => array('class' => array('add-wallet-link'))
        ),
        //this gets around the default css inline-block with which css 'clear' doesn't seem to work
        '#prefix' => '<br />'
      );
    }
  }
  return $renderable;
}

/*
 * get a renderable array of all the currency balances in one wallet
 */
function show_wallet_summary(WalletInterface $wallet) {
  $renderable = array(
    '#theme' => 'wallet_summary',
    '#wallet' => $wallet,
    '#attached' => array('css' => array(drupal_get_path('module', 'mcapi') . '/css/wallets.css'))
  );
  foreach ($wallet->currencies_used() as $curr_id => $currency) {
    $renderable['#currencies'][$curr_id] = show_wallet_summary_currency($wallet, $currency);
  }
  return $renderable;
}

function show_wallet_summary_currency(WalletInterface $wallet, CurrencyInterface $currency) {
  return array(
    '#theme' => 'wallet_summary_currency',
    '#wallet' => $wallet,
    '#currency' => $currency,
  );
}

/**
 * Get the wallet owner's name.
 * Might be more useful to get the wallet's full name instead.
 *
 * @param WalletInterface $wallet
 *
 * @return array
 *   a render array
 */
function show_wallet_owner(WalletInterface $wallet) {
  $entity = $wallet->getowner();
  return array(
    '#markup' => '<div class = "wallet-owner">'. $entity->label() .'</div>'
  );
}

/**
 * show the wallets 'local_tasks' as links, in case
 *
 * @param WalletInterface $wallet
 *
 * @return array
 *   a render array
 */
function show_wallet_links(WalletInterface $wallet) {
  $links= array();
  $tree = \Drupal::service('plugin.manager.menu.local_task')->getLocalTasksForRoute('mcapi.wallet_view');
  $access_manager = \Drupal::service('access_manager');
  //which is all very well but getTasksBuild assumes we are on the current route
  foreach($tree[0] as $child){
    $route_name = $child->getRouteName();
    $route_parameters = array(
      //TODO arg_0 can be removed when views args are working with the symfony router
      'arg_0' => $wallet->id(),//this is for view.wallet_statement.page_1
      'mcapi_wallet' => $wallet->id()
    );
    if ($access_manager->checkNamedRoute($route_name, $route_parameters, \Drupal::currentUser())) {
      $links[$route_name] = array(
        'title' => $child->getTitle(),
        'route_name' => $route_name,
        'route_parameters' => $route_parameters
      );
    }
  }
  return array(
    '#theme' => 'links',
    '#links' => $links,
  );
}

/**
 * get a renderable array of all the trading histories in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @return array
 *   a render array
 */
function show_wallet_histories(WalletInterface $wallet) {
  $renderable = array(
    '#prefix' => '<div class = "wallet-histories">',
    '#suffix' => '</div>',
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'mcapi') . '/css/wallets.css')
    )
  );
  foreach ($wallet->currencies_used() as $curr_id => $currency) {
    $renderable[$curr_id] = array(
      '#theme' => 'wallet_history',
      '#wallet' => $wallet,
      '#currency' => $currency,
      '#width' => 250
    );
  }
  return $renderable;
}

/**
 * get a renderable array of all the balance bar charts in one wallet
 *
 * @param WalletInterface $wallet
 *
 * @return array
 *   a render array
*/
function show_wallet_balance_bars(WalletInterface $wallet) {
  $renderable = array(
    '#prefix' => '<div class = "balance-bars">',
    '#suffix' => '</div>',
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'mcapi') . '/css/wallets.css')
    )
  );
  foreach ($wallet->getSummaries() as $curr_id => $data) {
    $renderable[$curr_id] = array(
      '#theme' => 'wallet_balance_bars',
      '#wallet' => $wallet,
      '#currency' => mcapi_currency_load($curr_id),
      '#data' => $data,
    );
  }
  return $renderable;
}

/**
 * @param WalletInterface $wallet
 *
 * @return array
 *   a render array
 */
function show_wallet_stats(WalletInterface $wallet) {
  return array(
    '#theme' => 'wallet_stats',//this renders as css block (table) so doesn't sit well with the histories
    '#wallet' => $wallet,
    '#weight' => 5
    //no default css used
  );
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

  mcapi_transaction_process_vars($vars, $transaction);
  //hide the field api label
  foreach(Element::children($element) as $sub) {
    if (array_key_exists('#theme', $element[$sub]) && $element[$sub]['#theme'] == 'field') {
      $element[$sub]['#label_display'] = 'hidden';
    }
    $vars[$sub] = $element[$sub];
  }

  $vars['children'] = array();
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
 * Helper for theming transactions
 *
 * @param array &$vars
 *
 * @param TransactionInterface $transaction
 */
function mcapi_transaction_process_vars(array &$vars, TransactionInterface $transaction) {
  $xid = $transaction->get('xid')->value;
  $v['state'] = State::load($transaction->type->entity->start_state)->label;
  $v['type'] = $transaction->type->entity->label;
  $v['serial'] = $transaction->serial->value;
  foreach (['payer', 'payee'] as $trader) {
    $owner = $transaction->{$trader}->entity->getOwner();
    if ($owner->hasLinkTemplate('canonical')) {
      $v[$trader] = $owner->link();
    }
    //if there is no canonical link to this entity just link to the wallet itself
    else {
      $v[$trader] = $transaction->{$trader}->entity->link();
    }
  }
  $v['creator'] = $transaction->creator->entity->link();
  //TODO do we need to sanitise this or does the theme engine do it?
  $v['description'] = $transaction->description->getString();

  //NB the transaction certificate, which uses entity_view_display overwrites field with display options, i.e. this one!
  //but this is needed for the sentence display
  $v['worth'] = $transaction->worth->view();

  //TODO LEAVE THIS TILL AFTER alpha 11
  $v['created'] = format_date($transaction->created->value, 'medium', '', NULL, Language::LANGCODE_DEFAULT);
  //$v['created'] = 'T.B.D.';

  //the token service lets you pass url options, but this function doesn't
  $v['url'] = $transaction->url('canonical');
  $vars += $v;
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
  $vars = array();
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
  return \Drupal::service('twig')->renderInline($template, mcapi_transaction_process_vars($vars, $transaction));
}


/**
 * helper function to generate the workflow display on admin/accounting/workflow
 *
 * @return array
 *   a renderable array
 */
function mcapi_workflow_help() {
  //TODO this needs prettifying
  drupal_add_css('table.help{float:left;margin-right:1em; width:50%;} ul.admin-list{clear:both;}', array('type' => 'inline'));
  $renderable = array(
    'states' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction states'),
      '#header' => array(t('Name'), t('Description')),
      '#attributes' => new Attribute(array('class' => array('help'))),
      '#attributes' => array('class' => array('help'))
    ),
    'types' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction types'),
      '#header' => array(t('machine name')),
      '#attributes' => new Attribute(array('class' => array('help')))
    ),
  );
  foreach (State::loadMultiple() as $info) {
    $renderable['states']['#rows'][$info->value]['name'] = $info->label;
    $renderable['states']['#rows'][$info->value]['description'] = $info->description;
  }
  foreach (Type::loadMultiple() as $type) {
    $renderable['types']['#rows'][$type->id]['name'] = $type->label;
    $renderable['types']['#rows'][$type->id]['description'] = $type->description;
  }
  return $renderable;
}


/**
 * helper function to get the token names for helptext token service and twig
 * get the entity properties from mcapi_token_info, then the fieldapi fields
 * this function would be handy for any entity_type, so something equivalent may exist already
 *
 * @param boolean
 *   if TRUE the result will include tokens to non-fields, such as the transition links
 *
 * @return array
 *   names of replicable elements in the transaction
 */
function mcapi_transaction_list_tokens($include_virtual = FALSE) {
  $tokens = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  //looks like fieldmap isn't needed at all, which will simplify this function
  unset($tokens['uuid'], $tokens['xid'], $tokens['parent'], $tokens['type'], $tokens['children']);
  $tokens = array_keys($tokens);

  if ($include_virtual){
    $tokens[] = 'url';
  }
  return $tokens;
}

/**
 * get all the bundles which have the exchange field attached
 * which is to say all the entity types which CAN own wallets
 * @return array
 *   arrays of bundles with bundleInfo keyed by entityTypeId
 */
function bundles_in_exchanges() {
  foreach (Exchange::getEntityFieldnames() as $entity_type_id => $fieldname) {
    if ($entity_type_id == 'mcapi_exchange') continue;
    $bundles[$entity_type_id] = entity_get_bundles($entity_type_id);
  }
  return $bundles;
}


/**
 * Submit callback for ContentEntity Forms
 * Alert the system if the user is joining or leaving an exchange
 * it's too bad this can't be done with entity_presave rather than with form_submit
 */
function mcapi_exchanges_changed_submit($form, FormStateInterface $form_state) {
  //check if the exchanges are different in the stored entity and the form values
  //the stored entity isn't updated.
  $entity = $form_state->get('controller')->getEntity();
  //lookup the name of the exchanges field.
  $fieldnames = Exchange::getEntityFieldnames();
  $fieldname = $fieldnames[$entity->getEntityTypeId()];
  $new = $saved = array();
  //compare the saved entity with the submitted values
  $values = $form_state->getValues();
  foreach ($values[$fieldname] as $item) {
    $new[] = $item['target_id'];
  }
  foreach ($entity->{$fieldname}->referencedEntities() as $entity) {
    $saved[] = $entity->id();
  }
  sort($new);
  sort($saved);
  if ($new == $saved) return;
  //we cant use the module handler in the form controller
  $moduleHandler = \Drupal::moduleHandler();
  $left = Exchange::loadMultiple(array_diff($saved, $new));
  foreach ($left as $exchange) {
    $exchange->goodbye($entity);
  }
  $joined = Exchange::loadMultiple(array_diff($new, $saved));
  foreach ($joined as $exchange) {
    $exchange->hello($entity);
  }
}

