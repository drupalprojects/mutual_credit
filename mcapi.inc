<?php

use Drupal\Core\Language\Language;
use Drupal\Core\Template\Attribute;
use Drupal\field\Field;
use Drupal\mcapi\TransactionInterface;

/*
 * implements hook_help().
 */
function _workflow_help() {
  //TODO lay this page out more attractively
  //TODO move this from help to the top of the page
  $renderable['states'] = array(
    '#theme' => 'table',
    '#attributes' => array('class' => array('help')),
    '#caption' => t('States'),
    '#header' => array(t('Label'), t('Description')),
  );
  foreach (mcapi_get_states() as $id => $info) {
    $renderable['states']['#rows'][$id] = array($info->label, $info->description);
  }
  $renderable['types'] = array(
    '#theme' => 'table',
    '#attributes' => array('class' => array('help')),
    '#caption' => t('Types'),
    '#header' => array(t('Label'), t('Description')),
  );

  foreach (mcapi_get_types() as $type => $info) {
    $renderable['types']['#rows'][$type] = array($info->label, $info->description);
  }
  //TODO Tidy up the preceding tables
  //I can't see how to inject a bit of css into the top of the page since drupal_add_css is deprecated
  $renderable['#prefix'] = "<style>table.help{margin-bottom:2em;}.help td{background-color:#efefef;}</style>";
  return $renderable;
}


function _mcapi_field_extra_fields() {
  //go through all the bundles which are configured to hold wallets
  foreach (\Drupal::config('mcapi.wallets')->get('entity_types') as $entity_bundle => $max) {
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
  $props = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction');
  $fields = array('payer', 'payee', 'worths', 'description');
  foreach ($fields as $weight => $fieldname) {
    $extra['mcapi_transaction']['mcapi_transaction']['form'][$fieldname] = array(
      'label' => $props[$fieldname]['label'],
      'description' => $props[$fieldname]['description'],
      'weight' => $weight,
    );
  }
  $fields = array_merge($fields, array('state', 'type'));
  foreach ($fields as $weight => $fieldname) {
    $extra['mcapi_transaction']['mcapi_transaction']['display'][$fieldname] = array(
      'label' => $props[$fieldname]['label'],
      'description' => $props[$fieldname]['description'],
      'weight' => $weight,
    );
  }
  //exchanges
  $extra['mcapi_exchange']['mcapi_exchange']['display']['people'] = array(
    'label' => t('Admin, helpers and user list'),
    'description' => t('3 pieces of data clumped together'),
    'weight' => 1,
  );
  //wallets
  $extra['mcapi_wallet']['mcapi_wallet']['display']['owner'] = array(
    'label' => t('Owner'),
    'description' => t('Link to the url of the wallets owner entity'),
    'weight' => 1,
  );
  $extra['mcapi_wallet']['mcapi_wallet']['display']['wallet_balance_bars'] = array(
    'label' => t('Balance bar charts'),
    'description' => t('One barchart per currency showing incoming and outgoing volumes'),
    'weight' => 1,
  );
  $extra['mcapi_wallet']['mcapi_wallet']['display']['wallet_histories'] = array(
    'label' => t('History chart(s)'),
  	'description' => t('One line chart per currency showing balance over time.'),
    'weight' => 2
  );
  $extra['mcapi_wallet']['mcapi_wallet']['display']['wallet_summaries'] = array(
    'label' => t('Balance(s)'),
  	'description' => t('Small thingy showing balances of all currencies'),
    'weight' => 3
  );
  $extra['mcapi_wallet']['mcapi_wallet']['display']['wallet_stats'] = array(
    'label' => t('Trading stats'),
  	'description' => t('Grid showing trading stats for all currencies'),
    'weight' => 4
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
  $items['mcapi_twig'] = array(
    'variables' => array(
      'mcapi_transaction' => NULL,
      'twig' => '',
    ),
    'file' => 'mcapi.inc'
  );
  $items['worth_element'] = array(
    'render element' => 'element',
  );
  //TODO
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
  $items['wallet_virgin'] = array(
    'template' => 'wallet_virgin',
    'variables' => array(
      'wallet' => NULL,
    ),
    'file' => 'extras/wallet_preprocess.inc'
  );
  return $items;
}

function _mcapi_element_info() {
  return array(
    // this is used by the field API for mixed transaction forms
    'worths' => array(
      '#title' => t('Worth'),
      '#input' => TRUE,
      '#title_display' => 'before',
      '#element_validate' => array('mcapi_worths_element_validate'),
      '#theme_wrappers' => array('form_element'),
      '#process' => array('mcapi_worths_element_process'),
      '#tree' => TRUE,
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'mcapi') . '/css/worths-element.css',
        )
      ),
      // custom properties, used for configuration
      '#mixed_mode' => \Drupal::config('mcapi.misc')->get('mix_mode'),
      '#preset' => FALSE, //ensures all currencies are showing
      '#calc' => FALSE, //allow a formula
    ),
    // this one is used internally only,
    // although you could probably use it to build a form if you wanted to ignore all multicurrency functionality
    'worth' => array(
      '#input' => TRUE,
      '#title_display' => 'attribute',
      '#process' => array('mcapi_worth_element_process'),
      '#value_callback' => 'mcapi_form_type_worth_value',
      '#element_validate' => array('mcapi_worth_element_validate'),
      '#theme_wrappers' => array('form_element'),
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'mcapi') . '/css/worth-element.css',
        )
      ),
      // custom properties, used for configuration
      '#calc' => FALSE,
    ),
    'mcapi_states' => array(
      '#input' => TRUE,
      '#title_display' => 'before',
      '#process' => array('mcapi_process_states', 'ajax_process_form'),
      '#theme_wrappers' => array('radios'),
      '#pre_render' => array('form_pre_render_conditional_form_element'),
    ),
    'mcapi_types' => array(
      '#input' => TRUE,
      '#title_display' => 'before',
      '#process' => array('mcapi_process_types', 'ajax_process_form'),
      '#theme_wrappers' => array('form_element'),
      '#theme' => 'select'
    ),
    'mcapi_currcodes' => array(
      '#input' => TRUE,
      '#title_display' => 'invisible',
      '#process' => array('mcapi_process_currcodes','ajax_process_form'),
      '#theme_wrappers' => array('form_element'),
      '#multiple' => TRUE,
      '#options' => array(),//array of currcodes and currency names
    ),
    //local means 'in the same exchanges as the current user'
    'select_wallet' => array(
      //much of this is borrowed from 'textfield'
      '#input' => TRUE,
      '#local' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#autocomplete_route_name' => 'mcapi.wallets.autocomplete',
      '#process' => array('mcapi_process_wallet_select', 'form_process_autocomplete', 'ajax_process_form'),
      '#pre_render' => array('form_pre_render_textfield', 'mcapi_prerender_wallet_field'),
      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
      '#value_callback' => 'form_type_select_wallet_value',
      '#element_validate' => array('local_wallet_validate_id'),
      //TODO I'm not sure how best to put in default css
      //the empty class is required to prevent an overload error in alpha7
      //TODO enable this when _form_set_attributes no longer attempts to merge this attribute object with an actual array
      //'#attributes' => new Attribute(array('style' => "width:100%", 'class' => array()))
      '#attributes' => array('style' => 'width:100%')
    )
  );
}

function mcapi_view_wallets_summaries($entity) {
  $renderable = array();
  $wallets = entity_load_multiple_by_properties(
    'mcapi_wallet',
    array(
      'pid' => $entity->id(),
      'entity_type' => $entity->entityType()
    )
  );
  foreach ($wallets as $wallet) {
    if ($wallet->access('view')) {
      $renderable['#wallets'] = show_wallet_summaries($wallet);
    }
  }
  if (count($renderable)) {
    $renderable['#theme'] = 'wallets';
  }
  return $renderable;
}

/*
 * get a renderable array of all the currency balances in one wallet
 */
function show_wallet_summaries($wallet) {
  foreach ($wallet->currencies() as $currcode => $currency) {
    $renderable[$currcode] = array(
      '#theme' => 'wallet_summary',
      '#wallet' => $wallet,
      '#currency' => $currency,
    );
  }
  if (!isset($renderable)) {
    $renderable['wallet_virgin'] = array(
    	'#theme' => 'wallet_virgin',
      '#wallet' => $wallet
    );
  }
  return $renderable;
}

/*
 * get a renderable array of all the trading histories in one wallet
 */
function show_wallet_histories($wallet) {
  $renderable = array();
  foreach ($wallet->currencies() as $currcode => $currency) {
    $renderable[$currcode] = array(
      '#theme' => 'wallet_history',
      '#wallet' => $wallet,
      '#currency' => $currency,
      '#width' => 250
    );
  }
  return $renderable;
}

/*
 * get a renderable array of all the balance bar charts in one wallet
*/
function show_wallet_balance_bars($wallet) {
  $renderable = array();
  foreach ($wallet->currencies() as $currcode => $currency) {
    $renderable[$currcode] = array(
      '#theme' => 'wallet_balance_bars',
      '#wallet' => $wallet,
      '#currency' => $currency,
      '#data' => $wallet->getStats($currcode),
    );
  }
  return $renderable;
}

/**
 * implements template_preprocess_THEMEHOOK for theme callback 'mcapi_transaction'
 * DO NOT OVERRIDE, javascript operations depend on the classes defined here
 * override certificate or change the mcapi_sentence variable instead
 *
 * @param array $vars
 */
function template_preprocess_mcapi_transaction(&$vars) {
  $element = $vars['transaction'];
	$transaction = $element['#mcapi_transaction'];
	//don't know if this is needed in d8
	element_set_attributes($element, array('class', 'id'));
	$vars['attributes']['class'] = array(
		'transaction',
		$transaction->get('type')->value,
		'state-'.$transaction->get('state')->value
	);
	if (!strpos($element['#view_mode'], ' ')) {
	  $vars['attributes']['class'][] = $element['#view_mode'];
  }
  $vars['links'] = \Drupal::Entitymanager()->getViewBuilder('mcapi_transaction')->renderlinks($transaction, $element['#view_mode']);
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
  //hide the field api labels
	foreach(element_children($element) as $sub) {
	  if ($element[$sub]['#theme'] == 'field') {
	    $element[$sub]['#label_display'] = 'hidden';
	  }
	  $v[$sub] = $element[$sub];
	}

	//TODO need to work out how to get entities out of an entity_reference field
	$vars['children'] = array();
	if (isset($transaction->children)) {
	  foreach($transaction->children as $child) {
  	  $vars['children'][] = entity_view($child, 'sentence');
	  }
	}
	//choose the transaction theme template based on the first currency of the transaction
	//not sure if this is worth the processing power at the moment

	$vars['theme_hook_suggestions'][] = 'certificate__' . $transaction->get('type')->value;
}

/**
 * Helper for theming transactions
 *
 * @param array $vars
 * @param TransactionInterface $transaction
 *   TRUE if the 'links' variable is required
 */
function mcapi_transaction_process_vars(array &$vars, TransactionInterface $transaction) {
  $static = drupal_static(__FUNCTION__, array());
  $xid = $transaction->get('xid')->value;

  $states = mcapi_get_states(TRUE);
  $types = mcapi_get_types();
  $v['state'] = $states[$transaction->state->value];
  $v['type'] = $types[$transaction->type->value]->label;
  $v['serial'] = $transaction->serial->value;
  $uri = $transaction->payer->entity->uri();
  $v['payer'] = l($transaction->payer->entity->label(), $uri['path']);
  $uri = $transaction->payee->entity->uri();
  $v['payee'] = l($transaction->payee->entity->label(), $uri['path']);
  $v['creator'] = $transaction->creator->entity->label();
  $v['description'] = $transaction->description->value;
  $v['worth'] = $transaction->get('worths')->getString();
  $v['created'] = format_date($transaction->created->value, 'medium', '', NULL, Language::LANGCODE_DEFAULT);

  //@todo more ugliness retrieving from entity_reference field
  $exchange_field = ($transaction->get('exchange')->getValue(TRUE));
  $v['exchange'] = $exchange_field[0]['entity']->label();
  //the token service lets you pass url options, but this function doesn't
  $uri = $transaction->uri();
  $v['url'] = url($uri['path'], array('absolute' => TRUE));
  //only store it if the transaction has a database key

  $vars += $v;
}



/**
 * theme implementation for callback twig
 * @todo convert this theme function to use
 * http://twig.sensiolabs.org/doc/functions/template_from_string.html
 */
function theme_mcapi_twig($vars) {
  return mcapi_render_twig_transaction(
    $vars['twig'],
    $vars['mcapi_transaction']
  );
}

/**
 * Prepares a transaction, and renders it using a passed twig template
 *
 * @param string $template
 * @param TransactionInterface $transaction
 *
 * @todo perhaps convert this theme function to use
 * http://twig.sensiolabs.org/doc/functions/template_from_string.html
 * New in Twig version 1.11
 */
function mcapi_render_twig_transaction(string $template, TransactionInterface $transaction) {
  $vars = array();
  $xid = $transaction->id();
  //Here we have to get all the fieldAPI fields rendered and in $vars
  $entities = array($xid => $transaction);
  $build = \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple($entities, 'certificate');
  mcapi_transaction_process_vars($vars, $transaction);
  //hide the fieldAPi labels
  foreach(element_children($build[$xid]) as $sub) {
    if ($build[$xid][$sub]['#theme'] == 'field') {
      $build[$xid][$sub]['#label_display'] = 'hidden';
    }
    $vars[$sub] = $build[$xid][$sub];
  }
  return mcapi_render_twig($template, $vars);
}

/**
 * special function to compensate for twig only accepting filenames as templates
 * this should have already existed in twig itself, but twig insists on reading the template from a filepath
 *
 * @param string $template
 *   a twig-compatible template
 * @param array $tokens
 *   the replacements
 *
 * @return string
 *   HTML
 */
function mcapi_render_twig(string $template, array $tokens) {
  //first validate that everything in the template is available as a key in the $tokens
  $matches = array();
  preg_match('/\{\{ (.+) \}\}/', $template,  $matches);
  array_shift($matches);
  foreach ($matches as $match) {
    if (!array_key_exists($match, $tokens)) {
      drupal_set_message("token '$match' not passed");
    }
  }
  //convert linebreaks
  $template = _filter_autop($template);

  //this is a bogus name of the nearest file, to keep twig happy
  $name = 'README.txt';
  $renderer = drupal_container()->get('twig');
  $cls = $renderer->getTemplateClass($name, NULL);
  if (!class_exists($cls)) {
    eval('?' . '>' . $renderer->compileSource($template, $name));
  }
  $twig = new $cls($renderer);
  return $twig->render($tokens);
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
      '#attributes' => array('class' => array('help'))
    ),
    'types' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction types'),
      '#header' => array(t('machine name')),
      '#attributes' => array('class' => array('help'))
    ),
  );
  foreach (mcapi_get_states() as $info) {
    $renderable['states']['#rows'][$info->value]['name'] = $info->label;
    $renderable['states']['#rows'][$info->value]['description'] = $info->description;
  }
  foreach (mcapi_get_types() as $type) {
    $renderable['types']['#rows'][$type->id]['name'] = $type->label;
    $renderable['types']['#rows'][$type->id]['description'] = $type->description;
  }
  return $renderable;
}


/*
 * helper function to get the token names for helptext token service and twig
 * get the entity properties from mcapi_token_info, then the fieldapi fields
 * this function would be handy for any entity_type, so something equivalent may exist already
 * @param boolean
 *   if TRUE the result will include tokens to non-fields, such as the operation links
 * @return array
 *   names of replacable elements in the transaction
 */
function mcapi_transaction_list_tokens($include_virtual = FALSE) {
  $tokens = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction');
  //looks like fieldmap isn't needed at all, which will simplify this function
  unset($tokens['uuid'], $tokens['xid'], $tokens['parent'], $tokens['type'], $tokens['children']);
  $tokens = array_keys($tokens);

  if ($include_virtual){
    $tokens[] = 'url';
    $tokens[] = 'sentence';
  }
  return $tokens;
}

/**
 * get a list of all the entity types which have an entity reference field pointing to mcapi_exchange
 * @return array
 *   a list of fieldnames, keyed by entity_type
 */
//@todo cache this. cache should be cleared whenever fieldInfo changes
function get_exchange_entity_fieldnames($type = '') {
  $types = array('mcapi_exchange' => NULL);
  foreach (Field::FieldInfo()->getInstances() as $entity_type => $data1) {
    if (in_array('entity_type', array('mcapi_exchange', 'mcapi_transaction', 'mcapi_wallet'))) continue;
    foreach ($data1 as $bundle => $fields) {
      foreach ($fields as $fieldname => $fieldInfo) {
        if ($fieldInfo->getFieldType() == 'entity_reference') {
          if ($fieldInfo->getFieldSetting('target_type') == 'mcapi_exchange') {
            $types[$entity_type] = $fieldname;
            continue 3;
          }
        }
      }
    }
  }
  if ($type) return @$types[$type];
  return $types;
}