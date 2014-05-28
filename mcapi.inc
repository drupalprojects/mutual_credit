<?php

use Drupal\Core\Language\Language;
use Drupal\Core\Template\Attribute;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Entity\ContentEntityInterface;

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
  foreach (entity_load_multiple('mcapi_state') as $id => $info) {
    $renderable['states']['#rows'][$id] = array($info->label, $info->description);
  }
  $renderable['types'] = array(
    '#theme' => 'table',
    '#attributes' => array('class' => array('help')),
    '#caption' => t('Types'),
    '#header' => array(t('Label'), t('Description')),
  );

  foreach (entity_load_multiple('mcapi_type') as $type => $info) {
    $renderable['types']['#rows'][$type] = array($info->label, $info->description);
  }
  //TODO Tidy up the preceding tables
  //I can't see how to inject a bit of css into the top of the page since drupal_add_css is deprecated
  $renderable['#prefix'] = "<style>table.help{margin-bottom:2em;}.help td{background-color:#efefef;}</style>";
  return $renderable;
}


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
  $props = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
  $fields = array('payer', 'payee', 'description');
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
    'label' => t('People'),
    'description' => t('People involved in the exchange'),
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
    'worth' => array(
      '#input' => TRUE,
      '#tree' => TRUE,
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
    'mcapi_currency_select' => array(
      '#input' => TRUE,
      '#process' => array('mcapi_process_currcodes','ajax_process_form'),
      '#theme_wrappers' => array('form_element'),
      '#multiple' => FALSE,
      '#options' => array(),//array of curr_ids and currency names
      '#status' => TRUE //filter only for active currences
    ),
    //local means 'in the same exchanges as the current user'
    'select_wallet' => array(
      //much of this is borrowed from 'textfield'
      '#input' => TRUE,
      '#local' => TRUE,
      '#size' => 60,
      '#maxlength' => 128,
      '#autocomplete_route_name' => 'mcapi.wallets.autocomplete',
      '#process' => array('mcapi_process_select_wallet', 'form_process_autocomplete', 'ajax_process_form'),
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


function _mcapi_worth_element_process($element, &$form_state, $form) {
  //build up the widget
  foreach ($element['#default_value'] as $curr_id => $value) {
    $currency = mcapi_currency_load($curr_id);
    $parts = $currency->formatted_parts($value);
    $element[$curr_id] = array(
      '#prefix' => '<div>',
      '#suffix' => '</div>'
    );
    foreach ($currency->format as $i => $component) {
      if ($i % 2) { //an odd number so render a field
        $step = 1;
        if (strpos($component, '/')) {//we need to make a dropdown
          list($component, $divisor) = explode('/', $component);
          $base = $component + 1;
          $step = $base / $divisor;
          for ($j=0; $j < $divisor; $j++) {
            $v = $j* $step;
            $options[$v] = $v;
          }
        }
        $element[$curr_id][$i] = array(
          '#weight' => $i,
          '#default_value' => $parts[$i],
          '#theme_wrappers' => array()
        );
        //if a preset value isn't in the $options
        //then we ignore the options and use the numeric sub-widget
        if (isset($options) && array_key_exists($parts[$i], $options)) {
          $element[$curr_id][$i] += array(
          	'#type' => 'select',
            '#options' => $options
          );
        }
        else {
          $size = strlen($component);
          $element[$curr_id][$i] += array(
            '#type' => 'number',//TODO this is rendering horribly but the #max and #min are important
            '#placeholder' => $i == 2 ? '' : str_pad('', $size, '0'),
            '#max' => intval($component) ? $component : 10*strlen($component)-1,
            '#min' => 0,
            '#step' => $step,
            '#size' => $size,//no effect in opera
            '#max_length' => $size,
            //the size needs to be larger because the widget spinners take up space
            //TODO find out what's going on with the browsers. We want the number field for its validation but the spinners are really bad
            '#attributes' => array('style' => 'width:'. ($size) .'em;'),
          );
        }
      }
      else {//an even number so render it as markup
        $element[$curr_id][$i] = array(
          '#weight' => $i,
          '#markup' => $component
        );
      }
    }
  }
  return $element;
}
/**
 *
 * @param ContentEntityInterface $entity
 * @return array
 *   a renderable array
 */
function mcapi_view_wallets_summaries(ContentEntityInterface $entity) {
  $renderable = array();
  foreach (wallets_owned_by($entity) as $wallet) {
    if ($wallet->access('view')) {
      $renderable['wallets']['#wallets'][] = show_wallet_summaries($wallet);
    }
  }
  $renderable['wallets']['#theme'] = 'wallets';
  $renderable['wallets']['#weight'] = '0';
  $renderable['wallets']['#attached']['css'][] = drupal_get_path('module', 'mcapi') . '/css/wallets.css';
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
          'attributes' => array(
            'class' => array('add-wallet-link')
          )
        ),
        //this gets around the default css inline-block with which css 'clear' doesn't seem to work
        '#prefix' => '<br />'
      );
    }
  }
  return $renderable;
}

//I've got a strong feeling there is another function for this.
function wallets_owned_by($entity) {
  return entity_load_multiple_by_properties(
    'mcapi_wallet',
    array(
      'pid' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId()
    )
  );
}

/*
 * get a renderable array of all the currency balances in one wallet
 */
function show_wallet_summaries($wallet) {
  foreach ($wallet->currencies() as $curr_id => $currency) {
    $renderable[$curr_id] = array(
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
  foreach ($wallet->currencies() as $curr_id => $currency) {
    $renderable[$curr_id] = array(
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
  foreach ($wallet->currencies() as $curr_id => $currency) {
    $renderable[$curr_id] = array(
      '#theme' => 'wallet_balance_bars',
      '#wallet' => $wallet,
      '#currency' => $currency,
      '#data' => $wallet->getStats($curr_id),
    );
  }
  return $renderable;
}

/**
 * implements template_preprocess_THEMEHOOK for theme callback 'mcapi_transaction'
 * DO NOT OVERRIDE, javascript transitions depend on the classes defined here
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
  //hide the field api label
	foreach(element_children($element) as $sub) {
	  if (array_key_exists('#theme', $element[$sub]) && $element[$sub]['#theme'] == 'field') {
	    $element[$sub]['#label_display'] = 'hidden';
	  }
	  $vars[$sub] = $element[$sub];
	}

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

  $stateItem = $transaction->state->getValue(TRUE);
  $typeItem = $transaction->type->getValue(TRUE);
  $v['state'] = $stateItem[0]['entity']->label;
  $v['type'] = $typeItem[0]['entity']->label;
  $v['serial'] = $transaction->serial->value;
  $payer_wallet_owner = $transaction->payer->entity->getOwner();
  $payee_wallet_owner = $transaction->payee->entity->getOwner();
  $v['payer'] = l($payer_wallet_owner->label(), $payer_wallet_owner->url());
  $v['payee'] = l($payee_wallet_owner->label(), $payee_wallet_owner->url());
  $v['creator'] = $transaction->creator->entity->label();
  $v['description'] = $transaction->description->value;

  $v['worth'] = $transaction->get('worth');
  //TODO LEAVE THIS TILL AFTER alpha 11
  //$v['created'] = format_date($transaction->created->value, 'medium', '', NULL, Language::LANGCODE_DEFAULT);
  $v['created'] = 'T.B.D.';

  //the token service lets you pass url options, but this function doesn't
  $v['url'] = $transaction->url();//@todo how to make this absolute?
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
  foreach (entity_load_multiple('mcapi_state') as $info) {
    $renderable['states']['#rows'][$info->value]['name'] = $info->label;
    $renderable['states']['#rows'][$info->value]['description'] = $info->description;
  }
  foreach (entity_load_multiple('mcapi_type') as $type) {
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
 *   if TRUE the result will include tokens to non-fields, such as the transition links
 * @return array
 *   names of replacable elements in the transaction
 */
function mcapi_transaction_list_tokens($include_virtual = FALSE) {
  $tokens = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction', 'mcapi_transaction');
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
 * get all the bundles which have the exchange field attached
 * @return array
 *   arrays of bundles with bundleInfo keyed by entityTypeId
 */
function bundles_in_exchanges() {
  foreach (get_exchange_entity_fieldnames() as $entity_type_id => $fieldname) {
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
function mcapi_exchanges_changed_submit($form, &$form_state) {
  //check if the exchanges are different in the stored entity and the form values
  //the stored entity isn't updated.
  $entity = $form_state['controller']->getEntity();
  //lookup the name of the exchanges field.
  $fieldnames = get_exchange_entity_fieldnames();
  $fieldname = $fieldnames[$entity->getEntityTypeId()];
  $new = $saved = array();
  //compare the saved entity with the submitted values
  foreach ($form_state['values'][$fieldname] as $item) {
    $new[] = $item['target_id'];
  }
  foreach ($entity->{$fieldname}->getValue(FALSE) as $item) {
    $saved[] = $item['target_id'];
  }
  sort($new);
  sort($saved);
  if ($new == $saved) return;
  //the module handler in the form controller is private
  $moduleHandler = \Drupal::moduleHandler();
  //TODO declare these hooks to rules / triggers whatever
  if ($left = array_diff($saved, $new)) {
    drupal_set_message('User left exchange but what hook to fire?');
    $moduleHandler->invokeAll('left_exchange', array($entity, $left));
  }
  if ($joined = array_diff($new, $saved) ) {
    drupal_set_message('User joined exchange but what hook to fire?');
    $moduleHandler->invokeAll('joined_exchange', array($entity, $joined));
  }
}

/**
 * Since both wallets are not in the same exchange, we have to check if a transaction is even possible.
 * This sets some temp transaction properties needed to make an intertrade
 * @param TransactionInterface $transaction
 * @return violation
 * @see Transaction::makeChildren()
 */
function intertrading_transaction_validate(TransactionInterface $transaction) {
  //Any wallet which can use all the currencies in the transaction is the source wallet.
  //In an intertrading split the source wallet takes the existing transactions
  //The dest wallet takes the new (child) transaction and the converted currency values.
  //Identify which wallet will use the current transaction
  //and which wallet will use the new child transaction
  $curr_ids_payer_cannot_see = array_diff($transaction->curr_ids_required, $transaction->payer_currencies_available);
  if (!$curr_ids_payer_cannot_see) {
    $transaction->source_participant = 'payer';
    $transaction->dest_participant = 'payee';
  }
  else {
    $curr_ids_payee_cannot_see = array_diff($transaction->curr_ids_required, $transaction->payee_currencies_available);
    if (!$curr_ids_payee_cannot_see) {
      $transaction->source_participant = 'payee';
      $transaction->dest_participant = 'payer';
    }
    else {
      echo "payer wid: ".$transaction->get('payer')->value;
      echo "<br />payee wid: ".$transaction->get('payee')->value;
      echo '<br />curr_ids_required: '; print_R($transaction->curr_ids_required);
      echo '<br />payee_currencies_available: '; print_R($transaction->payee_currencies_available);
      echo '<br />payer_currencies_available: '; print_R($transaction->payer_currencies_available);
      //this should be a violation
      return array(
        key($transaction->curr_ids_required),
        t('Neither wallet has access to all the currencies specified in the transaction.')
      );
    }
  }
  //now find which of the leading wallet's parents' exchanges can access
  //ALL the currencies in $transaction->worth
  $found = FALSE;
  $source_wallet_field = $transaction->get($transaction->source_participant)->getValue(TRUE);//entity reference array
  //echo "<br />The transaction has currencies: ".implode(', ', array_keys($transaction->get('worth')->getValue()));
  foreach($source_wallet_field[0]['entity']->in_exchanges() as $exchange) {
    //echo "<br />The leading wallet is in exchange ".$exchange->id();
    //echo " which has currencies ".implode(', ', get_entity_reference_targets($exchange->get('currencies')));
    if (array_diff_key($transaction->get('worth')->getValue(), get_entity_reference_targets($exchange->get('currencies')))) continue;
    //echo "which is good enough for this transaction...";
    $found = TRUE;
  }
  $transaction->source_exchange = $exchange;

  if (!$found) {
    //this should be a violation
    return array(
      key($transaction->curr_ids_required),
      //this string is identical to another in intertrading_new_worths
      t('Wallet !wallet is not a member of an exchange that can intertrade with this combination of currencies', array('!wallet' => $source_wallet_field[0]['entity']->label()))
    );
  }
  return array();
}

/**
 * Provide the new worthItemList array for an intertrading child transaction
 * @param TransactionInterface $transaction
 *
 * @param WalletInterface $dest_wallet
 *   The wallet in the remote exchange
 * @return array
 *   violation, keyed by fieldname
 */
function intertrading_new_worths(TransactionInterface $transaction, WalletInterface $dest_wallet) {
  $new_worths = $transaction->get('worth')->getValue();

  //We need to identify a dest exchange whose intertrading wallet can be used
  //which of the $dest_wallet's parent's exchanges is most suitable, if any?
  //it must contain all the worth's currencies bar one.
  foreach ($dest_wallet->in_exchanges() as $dest_exchange) {
    $dest_exchange_currencies = exchange_currencies(array($dest_exchange));
    $diff = array_diff_key($new_worths, $dest_exchange_currencies);
    //diff is the currencies in worth which are not in the dest exchange
    //if there is no diff, we shouldn't be intertrading but doing a straight transaction
    if (!$diff) {
      throw new Exception('Intertrading calculation is running when worth currencies are all matched');
    }
    if (count($diff) > 1) {
      continue;//we won't attempt to convert more than one currency in a transaction
    }
    $source_currency_id = key($diff);
    $source_ticks = entity_load('mcapi_currency', $source_currency_id)->get('ticks');
    if (!$source_ticks) {//the one diff currency in this exchange is not intertradable
      continue;
    }
    //when we have found a suitable exchange, stop looping because we need the variables above
    $found = TRUE;
    break;
  }
  if (!isset($found)) {
    return array(
      $source_currency_id,
      //this string is identical to another
      t('There is no way to intertrade this currency between these wallets')
    );
  }
  $transaction->dest_exchange = $dest_exchange;
  //so now we have our dest $exchange, a list of $exchange_currencies in it
  //get the first tradable currency in the exchange which isn't one of the other worth values.
  foreach ($dest_exchange_currencies as $dest_currency) {
    if ($dest_currency->ticks) continue;
    unset($dest_currency);
    break;
  }
  if (!isset($dest_currency)) {
    return array(
      $source_currency_id,
      //this string is identical to another
      t('The destination exchange has no tradable currency.')
    );
  }

  //the new worths is a copy of the old, so we remove the source value
  unset($new_worths[$source_currency_id]);
  //now add the dest value
  //conversion may not be exact
  $new_worths[$dest_currency->id()] = floor($dest_currency->ticks / $source_ticks * $worths[$source_currency_id]);
  $transaction->dest_worths = $new_worths;
  return array();//no problems
}

function get_entity_reference_targets($field) {
  foreach ($field->getValue() as $ref) {
    $dests[$ref['target_id']] = $ref['target_id'];
  }
  return $dests;
}