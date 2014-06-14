<?php

use Drupal\Core\Language\Language;
use Drupal\Core\Template\Attribute;
use Drupal\mcapi\Entity\TransactionInterface;
use Drupal\mcapi\Plugin\Field\McapiTransactionWorthException;
use Drupal\mcapi\Entity\WalletInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Element;


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
      'label' => t('Trading stats'),
    	'description' => t('Grid showing trading stats for all currencies'),
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
  $items['mcapi_twig'] = array(
    'variables' => array(
      'mcapi_transaction' => NULL,
      'twig_template' => '',
    ),
    'file' => 'mcapi.inc'
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
      '#process' => array('mcapi_worth_element_process_defaults', 'mcapi_worth_element_process'),
      '#value_callback' => 'mcapi_form_type_worth_value',
      '#element_validate' => array('mcapi_worth_element_validate'),
      '#theme_wrappers' => array('form_element'),
      '#attached' => array(
        'css' => array(
         drupal_get_path('module', 'mcapi') . '/css/worth-element.css',
        )
      ),
      '#minus' => FALSE,
      '#config' => FALSE,
      //if this is empty it will be ignored
      //otherwise currencies not in #default values will appear in the form
      '#allowed_curr_ids' => array(),
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
      '#required' => TRUE,
      //TODO I'm not sure how best to put in default css
      //the empty class is required to prevent an overload error in alpha7
      //TODO enable this when _form_set_attributes no longer attempts to merge this attribute object with an actual array
      //'#attributes' => new Attribute(array('style' => "width:100%", 'class' => array()))
      '#attributes' => array('style' => 'width:100%')
    )
  );
}

/**
 * form element processor callback for 'worth'
 * takes the raw #default_values and makes child widgets according to the currency format.
 *
 * @return array
 *   The processed $element, including children
 */
function _mcapi_worth_element_process($element, &$form_state, $form) {
  //we might need to filter #default_value not in config mode
  $worth_cardinality = count($element['#default_value']) > 1 ? 'multiple' : 'single';
  foreach ($element['#default_value'] as $item) {
    //i want a div around each currency widget
    extract($item);//creates $curr_id and $value

    $currency = mcapi_currency_load($curr_id);
    if ($element['#config'] && $value === '') $parts = array();
    else $parts = $currency->formatted_parts(abs(intval($value)));

    $element[$curr_id]['#type'] = 'container';
    $element[$curr_id]['#prefix'] = "<div class = \"$worth_cardinality\">";
    $element[$curr_id]['#suffix'] = '</div>';


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
          '#value' => @$parts[$i],//in config mode $parts is empty
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
            '#type' => $element['#config'] ? 'textfield' : 'number',
            '#placeholder' => $i == 2 ? '' : str_pad('', $size, '0'),
            '#max' => $i == 1 ? pow(10, strlen($component))-1 : $component,//first component matters only for the num of digits
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
    if ($element['#minus']) {
      $element[$curr_id][0]['#markup'] = '-'.$element[$curr_id][0]['#markup'];
      $element[$curr_id][$i]['#suffix'] = '('.t('minus').')';
    }
  }
  //single values can inherit max and min from the top level of the element
  if (count($element['#default_value']) == 1) {
    if (array_key_exists('#max', $element)) $element[$curr_id][1]['#max'] = $element['#max'];
    if (array_key_exists('#min', $element)) $element[$curr_id][1]['#min'] = $element['#min'];
  }
  return $element;
}

/**
 * helper function to ensure the worth field is showing the right currencies
 * the #default_value is supplemented by the #allowed_curr_ids, with error checking
 *
 * @param array $element
 *   a formapi worth element
 * @return array
 *   the same element, but with perhaps more #default_values
 */
function mcapi_worth_element_process_defaults($element) {
  $blank = $element['#config'] ? '' : 0;
  //change the all_available array to a worths value array populated by zeros
  if ($allowed_curr_ids = $element['#allowed_curr_ids']) {
    $existing_curr_ids = array();
    foreach ($element['#default_value'] as &$item) {
      $existing_curr_ids[] = $item['curr_id'];
    }
    if ($not_allowed = array_diff($existing_curr_ids, $allowed_curr_ids)) {
      //only shows the FIRST not allowed currency
      $currency = entity_load('mcapi_currency', reset($not_allowed));
      drupal_set_message(t(
        'Passed default @currency is not one of the allowed currencies',
        array('@currency' => $currency->label())),
        'warning'
      );
    }
    foreach ($add = array_diff($allowed_curr_ids, $existing_curr_ids) as $curr_id) {
      $element['#default_value'][] = array('curr_id' => $curr_id, 'value' => $blank);
    }
  }

  if (empty($element['#default_value'])) {
    drupal_set_message('No currencies have been specified in the worth field.', 'error');
  }
  //sort the currencies by weight.
  return $element;
}

/**
 * value callback for worth element
 * expects $input structured thus:
 * array(curr_id => array(subelement1 => n, subelement2 => n))
 * needs to output a worths array thus
 * array(curr_id => n) where n is the raw database value
 */

function mcapi_form_type_worth_value(&$element, $input = FALSE, &$form_state) {
  if ($input === FALSE) return;
  $output = array();
  foreach ($input as $curr_id => $parts) {
    if ($element['#config'] && reset($parts) === ''){
      //leaving the main value component blank in config mode means ignore the currency
      continue;
    }
    $currency = mcapi_currency_load($curr_id);
    $quant = $currency->unformat($parts);
    if ($quant == 0 && !$element['#config']) {
      //zero values are only accepted if the currency allows and if there is only one currency in the field
      if (empty($currency->zero) || count($input) > 1){
        continue;//don't add this value to the $output
      }
    }
    if ($element['#minus']) $quant = -$quant;
    $output[] = array('curr_id' => $curr_id, 'value' => $quant);
  }
  //be aware that the child widgets will add to this and then be cleaned up in mcapi_worth_element_validate
  return $output;
}

/**
 * element_validate callback for worth field
 *
 * @param array $element
 * @param array $form_state
 */
function mcapi_worth_element_validate($element, &$form_state) {

  //tidy up the detritus from the worth value callback
  foreach ($element['#value'] as $delta => $item) {
    if (!isset($item['curr_id'])) {
      unset($element['#value'][$delta]);
      continue;
    }
    foreach ($item as $key => $value) {
      if (is_numeric($key)) unset($element['#value'][$delta][$key]);
    }
  }
  //todo form_set_value is deprecated
  form_set_value($element, $element['#value'], $form_state);
  //can't remember how to do this properly
  //$form_state['controller']->formBuilder->setValue($element, $element['#value'], $form_state);

  //ensure the value is positive
  if (!$element['#config']) {
    foreach ($element['#value'] as $curr_id => $value) {
      if ($value < 0) {
        //TODO check this
        $form_state['controller']->setError($element, $form_state, t('Negative amounts not allowed'));
        $form_state['controller']->errorHandler()->setError($element, $form_state, t('Negative amounts not allowed'));
      }
    }
  }

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
function wallets_owned_by(ContentEntityInterface $entity) {
  return entity_load_multiple_by_properties(
    'mcapi_wallet',
    array(
      'pid' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId()
    )
  );
}
/**
 * Get the wallet owner's name.
 * Might be more useful to get the wallet's full name instead.
 * @param WalletInterface $wallet
 * @return array
 *   a render array
 */
function show_wallet_owner(WalletInterface $wallet) {
  $entity = $wallet->getowner();
  return array(
  	'#markup' => '<div class = "wallet-owner">'. $entity->label() .'</div>'
  );
}
/*
 * get a renderable array of all the currency balances in one wallet
 */
function show_wallet_summaries(WalletInterface $wallet) {
  $renderable = array(
    '#prefix' => '<div class = "wallet-summaries">',
    '#suffix' => '</div>'
  );
  foreach ($wallet->currencies_used() as $curr_id => $currency) {
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
/**
 * show the wallets 'local_tasks' as links, in case
 */
function show_wallet_links(WalletInterface $wallet) {
  $links= array();
  $tree = \Drupal::service('plugin.manager.menu.local_task')->getLocalTasksForRoute('mcapi.wallet_view');
  $access_manager = \Drupal::service('access_manager');
  //which is all very well but getTasksBuild assumes we are on the current route
  foreach($tree[0] as $child){
    $route_name = $child->getRouteName();
    $route_parameters = array('mcapi_wallet' => $wallet->id());
    $access = $access_manager->checkNamedRoute($route_name, $route_parameters, \Drupal::currentUser());
    if ($access) {
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

/*
 * get a renderable array of all the trading histories in one wallet
 */
function show_wallet_histories(WalletInterface $wallet) {
  $renderable = array(
  	'#prefix' => '<div class = "wallet-histories">',
    '#suffix' => '</div>'
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

/*
 * get a renderable array of all the balance bar charts in one wallet
*/
function show_wallet_balance_bars(WalletInterface $wallet) {
  $renderable = array(
  	'#prefix' => '<div class = "balance-bars">',
    '#suffix' => '</div>'
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

function show_wallet_stats(WalletInterface $wallet) {
  return array(
    '#theme' => 'wallet_stats',//this renders as css block (table) so doesn't sit well with the histories
    '#wallet' => $wallet,
    '#weight' => 5
  );
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
  if ($transaction->noLinks) {
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

  $v['state'] = $transaction->state->entity->label;
  $v['type'] = $transaction->type->entity->label;
  $v['serial'] = $transaction->serial->value;
  $payer_wallet_owner = $transaction->payer->entity->getOwner();
  $payee_wallet_owner = $transaction->payee->entity->getOwner();
  $v['payer'] = l($payer_wallet_owner->label(), $payer_wallet_owner->url());
  $v['payee'] = l($payee_wallet_owner->label(), $payee_wallet_owner->url());
  $v['creator'] = $transaction->creator->entity->label();
  //TODO where do all the divs around descriptiion come from?
  $v['description'] = $transaction->description->value;

  $v['worth'] = $transaction->worth->view();
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
    $vars['twig_template'],
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
function mcapi_render_twig_transaction($template, TransactionInterface $transaction) {
  $vars = array();
  $xid = $transaction->id();
  //Here we have to get all the fieldAPI fields rendered and in $vars
  $entities = array($xid => $transaction);
  $build = \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple($entities, 'certificate');
  mcapi_transaction_process_vars($vars, $transaction);
  //hide the labels from the standard field rendering
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
function mcapi_render_twig($template, array $tokens) {
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
  $twig_service = \Drupal::service('twig');
  $cls = $twig_service->getTemplateClass($name, NULL);
  if (!class_exists($cls)) {
    eval('?' . '>' . $twig_service->compileSource($template, $name));
  }
  $twig = new $cls($twig_service);
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
  //we cant use the module handler in the form controller
  $moduleHandler = \Drupal::moduleHandler();
  $left = entity_load_multiple('mcapi_exchange', array_diff($saved, $new));
  foreach ($left as $exchange) {
    $exchange->goodbye($entity);
  }
  $joined = entity_load_multiple('mcapi_exchange', array_diff($new, $saved));
  foreach ($joined as $exchange) {
    $exchange->hello($entity);
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
      echo "unable to get source and dest participants";
      echo "<br />payer wid: ".$transaction->get('payer')->value;
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