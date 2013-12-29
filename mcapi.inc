  <?php

//TODO why is NestedArray here? Still needed?
use Drupal\Component\Utility\NestedArray;


/*
 * action callback for hook_transaction_children
 * //TODO This goes with RULES
 */
function mcapi_dependent_transaction_action($transaction, $settings) {
  if (!in_array($transaction->type, $settings['conditions']['transaction_types'])) return;
  $child = array(
    'type' => 'dependent',
    'state' => $transaction->state
  );

  foreach ($settings['presets']['worth_items']['worths'] as $delta => $item) {
    //if this currency was in the prime transaction, pass it to the calculator
    $calculated = _mcapi_calc($item['quantity'], @$transaction->worth[LANGUAGE_NONE][$delta]['quantity']);
    if (!$calculated) continue;
    $rounded = mcapi_round($calculated, $item['currcode'], $settings['presets']['worth_items']['round'] == 'up');
    //don't save zero value auto transactions, even if the currency settings permit
    if ($rounded == 0) continue;
    //if a quant was returned (and there really should be, from at least one currency), add it to the $dependent
    $child['worth'][$delta] = array(
      'quantity' => $rounded,
      'currcode' => $item['currcode']
    );
  }
  if (!isset($child['worth'])) return;

  $otheruser = &$settings['presets']['participants']['otheruser'];
  switch ($settings['presets']['participants']['mapping']) {
    case 'payerpays':
      $child['payee'] = $otheruser;
      $child['payer'] = $transaction->payer;
      break;
    case 'payeepays':
      $child['payee'] = $otheruser;
      $child['payer'] = $transaction->payee;
      break;
    case 'payspayer':
      $child['payee'] = $transaction->payer;
      $child['payer'] = $otheruser;
      break;
    case 'payspayee':
      $child['payee'] = $transaction->payee;
      $child['payer'] = $otheruser;
      break;
  }
  $child['description'] = @$settings['presets']['description'];
  return entity_create('transaction', $child);
}


/*
 * calculate a transaction quantity based on a provided formala and input quantity
 * $formula can be
 * number => returns the number as a quantity, regardless of $base_quant
 * percentage of the $base_quant
 * formula using [q] for base_quant
 */
function _mcapi_calc($formula, $base_value) {
  if (is_numeric($formula)) return $formula;
  $proportion = str_replace('%', '', $formula);
  if (empty($base_value)) $base_quant = 0;
  if (is_numeric($proportion)) {
    return $base_value * $proportion/100;
  }
  //$formula = str_replace('/', '//', $formula);
  $equation = str_replace('[q]', $base_value, $formula) .';';
  $val = eval('return '. $equation);
  if (is_numeric($val)) return $val;
  drupal_set_message(t('Problem with calculation for dependent transaction: @val', array('@val' => $val)));
}


/*
 * default theme implementation for callback 'worth_element'
 * called once per $item
 */

use Drupal\Core\Template\Attribute;
function theme_worth_element($variables) {die('are we using this?');
  $element = &$variables['element'];
  $element['#attributes']['class'][] = "worth-widget";
  $element['#attributes'] = new Attribute($element['#attributes']);
  $curr_select = drupal_render($element['currcode']);
  $quant_field = drupal_render($element['main_quant']);
  if ($element['currcode']['#type'] == 'hidden' || $element['currcode']['#theme'] == 'hidden') {//if the currency is preset, then we can use its own display format
    $currcode = isset($element['#value']) ? $element['#value']['currcode'] : $element['#default_value']['currcode'];
    $currency_display = entity_load('mcapi_currency', $currcode)->display;
    if (isset($element['div_quant'])) {
      $quant_field .= $currency_display['delimiter'] . drupal_render($element['div_quant']);
    }
    $output = $curr_select . $quant_field;
  }
  else {//if there is no known currcode, the
    if (!empty($element['div_quant'])) {
      $quant_field .= drupal_render($element['div_quant']);
    }
    $output = $curr_select . $quant_field;
  }
  return '<div ' . $element['#attributes'] . '>'. $output .'</div>';
}


/*
 * a nice display of user balances in a currency
 */
function mcapi_view_user_summary($account, $currencies = array()) {
  if (empty($currcodes)) {
    //TODO what's the current way to load all currencies for a user?
    $currencies = mcapi_get_available_currencies($account);
  }

  $render_array = array();
  foreach ($currencies as $currcode => $currency) {
    if ($currency->access('trader_data')) {
      $render_array[$currcode] = array(
        '#type' => 'mcapi_summary',
        '#currency' => $currency,
        '#account' => $account
      );
    }
  }

  return $render_array;
}

/*
 * callback to give times and balances
* $mode can be either
*   diagonal, square, or the number of points required after sampling
*/
function get_balance_history($account, $currcodes = array(), $since = NULL) {
	if (empty($currcodes)) {
		$currencies = mcapi_get_available_currencies($account);
	}
	else {
		foreach ($currcodes as $currcode) {
			$currency = mcapi_currency_load($currcode);
			if ($currency->access('trader data', \Drupal::currentUser()) || $account->id() == \Drupal::currentUser()->id()) {
  		  $currencies[$currcode] = $currency;
		  }
		}
	}
	if (empty($currencies)) return;
	$histories = array();
	$storage = \Drupal::entityManager()->getStorageController('mcapi_transaction');
	foreach ($currencies as $currcode => $currency) {
		$history = array($account->created->value => 0);
		$history += $storage->timesBalances($account, $currency);
		if (count($history) > 1) {
			//make a straight line until now
			$history[REQUEST_TIME] = end($history);
			$histories[$currcode] = $history;
		}
		//ignore histories with no transactions
	}
	return $histories;
}

//modify the history points for aesthetic reasons, or to save resources
function template_preprocess_balance_histories_gchart(&$vars) {
	$histories = get_balance_history($vars['account'], $vars['currcodes']);
	foreach ($histories as $currcode => &$history) {
		$point_count = count($history);
		if ($point_count < $vars['width'] / 3) {//step method, for a small number of points
			$times = $values = array();
			//make two values for each one in the keys and values
			foreach ($history as $time => $bal) {
				$times[] = $time;
				$times[] = $time+1;
				$values[] = $bal;
				$values[] = $bal;
			}
			//now slide the arrays against each other
			array_pop($values);
			array_shift($times);
			$history = array_combine($times, $values);
		}
		elseif ($point_count > $vars['width']) {//decimate the array, for a large number of points
			//we can assume that $max_size(1 point per pixes) is smaller than $count
			$ratio = $vars['width'] / $point_count;
			//how to turn this into a fraction involving 1?
			$reciprocal = 1/$ratio;
			$factor = intval($reciprocal + 1);
			//now iterate through the array taking 1 out of every $factor values
			$i = 0;
			foreach($history as $key => $value) {
				if ($i % $factor != 0) unset($history[$key]);
				$i++;
			}
		}
		$vars['histories'][$currcode] = $history;
	}
}


//produce and include the actual javascript
//TODO cache this and clear the cache
//theme preprocessor
function mcapi_preprocess_balance_histories_gchart(&$vars) {
	$histories = &$vars['histories'];
	$id = 'uid-'.$vars['account']->id().'-'.implode('-',array_keys($histories));
	$vars['id'] = $id;
	foreach ($histories as $currcode => $history) {
		$colors[] = "'".mcapi_currency_load($currcode)->color ."'";
		foreach ($history as $timestamp => $balance) {
			$timeline[$timestamp][$currcode] = $balance;
		}
	}
	if (empty($timeline))return '';
	//$timeline is now a list of times and changes of balance in currencies
	ksort($timeline);
	//what we need is a list of times with both balances per moment
	//starting with a default 'prev value
	foreach (array_keys($histories) as $currcode) {
		$prev[$currcode] = 0;
	}
	foreach ($timeline as $timestamp => $balances) {
		$vals = array_merge($prev, $balances);
		$timeline[$timestamp] = $vals;
		$prev = $timeline[$timestamp];
	}
	$vars['height'] = 3* $vars['width']/4;
	$js = "
function drawBalanceHistory() {
  data.addColumn('date', 'Date');";
	foreach (array_keys(current($timeline)) as $currcode) {
		$currname = entity_load('mcapi_currency', $currcode)->name;
	  $js .= "\n  data.addColumn('number', '$currname')";
	}
	foreach ($timeline as $timestamp => $balances) {
		//this has a resolution of one day, not very satisfying perhaps
	  $date = "new Date('".date('m/d/Y', $timestamp)."')";
	  $balances = implode(', ', $balances);
	  $js .= "\n  data.addRow([$date, $balances]);";
	}
	$colors = implode(', ', $colors);
	$js .= "
  var options = {
    curveType: 'function',
    width: ".$vars['width'].",
    height: ".$vars['height'].",
    colors: [$colors],
    legend: {position: 'none'}
  }
  new google.visualization.LineChart(document.getElementById('$id')).draw(data, options);
}";
	//
	drupal_add_js('http://www.google.com/jsapi');
	drupal_add_js("google.load('visualization', '1', {packages: ['corechart']});\ngoogle.setOnLoadCallback(drawBalanceHistory);", 'inline');
	$vars['javascript'] = $js;
}

//wrapper which helps with ajax DO NOT CHANGE
function template_preprocess_mcapi_transaction(&$vars) {
  $element = $vars['transaction'];
	$transaction = $element['#mcapi_transaction'];
	//don't know if this is needed in d8
	element_set_attributes($element, array('class', 'id'));
	$vars['attributes']['class'] = array(
		'transaction',
		'transaction-'. $transaction->serial->value,
		$element['#view_mode'] == 'certificate' ? 'certificate' : 'customtwig',
		$transaction->type->value,
		'state-'.$transaction->state->value
	);
}

function template_preprocess_certificate(&$vars) {
  $element = $vars['transaction'];
  $transaction = $element['#mcapi_transaction'];

	mcapi_transaction_process_vars($vars, $transaction);

  $vars['content'] = array();
	foreach(element_children($element) as $sub) {
	  $vars['content'][$sub] = $element[$sub];
	}

	$vars['children'] = array();
	if (isset($transaction->children) && !empty($transaction->children)) {
	  //how to we call transaction view now?
	  //$vars['children'] = transaction_view(
	    //$transaction->children,
	    //str_replace('[transaction:links]', '', \Drupal::config('mcapi.misc')->get('sentence_template'))
	  //);
	}
	//choose the transaction theme template based on the first currency of the transaction
	//not sure if this is worth the processing power at the moment
	$vars['theme_hook_suggestions'][] = 'certificate__' . $transaction->type->value;
}

/*
 * helper for theming transactions
 * this is abstracted so that the arbitrary twig can access it too
 */
function mcapi_transaction_process_vars(&$vars, $transaction) {
  $payer = array(
    '#theme' => 'username',
    '#account' => $transaction->payer->entity,
    '#link_options' => array('attributes' => array('rel' => 'author')),
  );
  $payee = array(
    '#theme' => 'username',
    '#account' => $transaction->payee->entity,
    '#link_options' => array('attributes' => array('rel' => 'author')),
  );
  $vars['payer'] = drupal_render($payer);
  $vars['payee'] = drupal_render($payee);

  foreach ($transaction->worths[0] as $worth) {
    $worths[] = $worth->getString();
  }
  $vars['worths'] = implode('|', $worths);

  $vars['state'] = $transaction->state->value;
  $vars['date'] = format_date($transaction->created->value, 'medium');
  $vars['type'] = $transaction->type->value;
  $vars['description'] = $transaction->description->value;
}

/*
 * does this more naturally belong in the TransactionViewBuilder?
 */
function mcapi_render_twig_transaction(string $template, TransactionInterface $transaction) {
  $vars = array();
  mcapi_transaction_process_vars($vars, $transaction);
  return mcapi_render_twig($template, $vars);
}

/*
 * special function to compensate for twig only accepting filenames as templates
 * this was much too hard!
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
  $name = 'modules/mutual_credit/templates/certificate.html.twig';
  $renderer = drupal_container()->get('twig');
  $cls = $renderer->getTemplateClass($name, NULL);
  $compiled_source = $renderer->compileSource($template, $name);
  eval('?' . '>' . $compiled_source);
  $twig = new $cls($renderer);
  return $twig->render($tokens);
}



/*
 * Settings form callback for action mcapi_transaction_entity_uid
*/
function mcapi_transaction_entity_uid_form($context) {
  $context += array(
      'mapping' => 'uidpays',
      'transaction_types' => array(),
      'otheruser' => 1,
      'worth_items' => array('worths' => array(), 'round' => 'down')
  );
  $form['direction'] = array(
      '#title' => t('Direction'),
      '#type' => 'radios',
      '#options' => array(
          'uidpays' => t('User pays reservoir account'),
          'paysuid' => t('Reservoir account pays user')
      ),
      '#default_value' => isset($context['mapping']) ? $context['mapping'] : 'payerpays',
  );

  $currencies = currencies_load();
  $defaults = array();
  foreach (@$context['worth_items']['worths'] as $item) {
    $defaults[$item['currcode']] = $item['quantity'];
  }
  $form['worth_items'] = array(
      '#title' => t('Worth'),
      '#type' => 'fieldset',
      '#name' => 'worth_items',//this helps in the fieldset validation
      '#description' => t('What is the fixed value of this transaction?') .' '. t("This value will be overwritten if the passed entity has the 'worth' field"),
      '#tree' => TRUE,
      'worths' => array(
          //'#title' => t('Worths'),
          '#type' => 'worths',
          '#default_value' => $defaults,
          '#preset' => TRUE,//ensures that all currencies are rendered
      )
  );
  $form['round'] = array(
      '#title' => t('Rounding'),
      '#description' => t('Used if this transaction depends on another transaction'),
      '#type' => 'radios',
      '#options' => array('up' => t('Up'), 'down' => t('Down')),
      '#default_value' => isset($context['worth_items']['round']) ? $context['worth_items']['round'] : 'down',
      '#weight' => 1
  );

  $form['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => @$context['worth_items']['description'],
  );

  return $form;
}
/*
 * configuration action submit callback
*/
function mcapi_transaction_entity_uid_submit($form, $form_state) {
  form_state_values_clean($form_state);
  return $form_state['values'];
}


/*
 * Settings form callback for mcapi_pay_someone_action
* odd that form_state isn't passed
* this means we can't do form_load_include
*/
function mcapi_dependent_transaction_action_form($context) {
  drupal_set_message('This tool is a bit crude but it will have to do until someone can figure out how to do it with rules');
  drupal_set_message('This action ONLY goes on one the hook, and adds a dependent transaction to the cluster. with the same serial number');
  drupal_set_message('It will be easy to make this action more sophisticated');
  //set the defaults
  $context += array(
      'conditions' => array(
          'transaction_types' => array('default')
      ),
      'presets' => array(
          'participants' => array(
              'mapping' => 'payerpays',
              'otheruser' => 1
          ),
          'worth_items' => array(
              'worths' => array(),
              'round' => 'down'
          )
      ),
  );
  $form['#tree'] = TRUE;
  $form['conditions'] = array(
      '#title' => t('Conditions'),
      '#description' => t('A dependent transaction will only be created when the main transaction meets these conditions'),
      '#type' => 'fieldset',
      '#weight' => -1,
      'transaction_types' => _transaction_type_selector(@$context['conditions']['transaction_types'], TRUE)
      //more could be added, including stuff unconnected to the transaction, like whether there is an r in the month
  );

  $form['presets'] = array(
      '#title' => t('Presets'),
      '#description' => t('Specify the properties of the dependent transaction') .' '.t("It will have the type 'dependent'."),
      '#type' => 'fieldset',
      '#weight' => 0,
      'participants' => array(
          '#title' => t('Participants'),
          '#description' => t('Determine who will pay whom'),
          '#type' => 'fieldset',
          'mapping' => array(
              '#title' => t('Mapping'),
              '#description' => t('On a node trigger, the payer and payee will both evaluate to the node author'),
              '#type' => 'radios',
              '#options' => array(
                  'payerpays' => t('Payer pays other'),
                  'payeepays' => t('Payee pays other'),
                  'payspayer' => t('Other pays payer'),
                  'payspayee' => t('Other pays payee'),
              ),
              '#default_value' => isset($context['presets']['participants']['mapping']) ? $context['presets']['participants']['mapping'] : array('payerpays'),
          ),
          'otheruser' => array(
              '#title' => t('Other account'),
              '#type' => 'user_chooser_segment_perms',
              '#args' => array('transact'),
              '#default_value' => isset($context['presets']['participants']['otheruser']) ? $context['presets']['participants']['otheruser'] : 1,
              '#weight' => 1,
          )
      ),
      'worth_items' => array(
          '#title' => t('Worth'),
          '#description' => t('The value of the transction may depend on the value of the main transaction.'),
          '#type' => 'fieldset',
          '#name' => 'worth_items',//this helps in the fieldset validation
          '#tree' => TRUE,
          'worths' => array(
              //'#title' => t('Worths'),
              '#description' => t('Enter a number, a percentage, or a formula using [q] for the transaction quantity.'),
              '#type' => 'worths',
              '#default_value' => isset($context['presets']['worth_items']['worths']) ? _worthitems_to_worths($context['presets']['worth_items']['worths']) : array(),
              '#calc' => TRUE,//allow a formula
              '#preset' => TRUE, //show all currencies
              //it affects what happens to the zero and null values
              '#after_build' => array('mcapi_configure_worth_accept_formula')
          ),
          'round' => array(
              '#title' => t('Rounding'),
              '#type' => 'radios',
              '#options' => array('up' => t('Up'), 'down' => t('Down')),
              '#default_value' => isset($context['presets']['worth_items']['round']) ? $context['presets']['worth_items']['round'] : 'down',
              '#weight' => 1
          )
      )
  );
  $form['presets']['description'] = array(
      '#title' => t('Transaction description text'),
      '#type' => 'textfield',
      '#default_value' => @$context['presets']['description'],
  );

  return $form;
}

function mcapi_configure_worth_accept_formula($element) {
  foreach (element_children($element) as $delta) {
    //this field will accept a formula, not just a number
    $element[$delta]['main_quant']['#size'] = 10;
    $element[$delta]['main_quant']['#maxlength'] = 10;
    $element[$delta]['main_quant']['#element_validate'] = array('validate_mcapi_configure_worth_formula');
  }
  return $element;
}

/*
 * configuration action submit callback
*/
function mcapi_dependent_transaction_action_submit($form, $form_state) {
  form_state_values_clean($form_state);
  return $form_state['values'];
}


function mcapi_workflow_help() {
  drupal_add_css('table.help{float:left;margin-right:1em; width:50%;} ul.admin-list{clear:both;}', array('type' => 'inline'));
  $renderable = array(
    'states' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction states, from !hook', array('!hook' => "\n<br />hook_mcapi_info_states()")),
      '#header' => array(t('Name'), t('Description')),
      '#attributes' => array('class' => array('help'))
    ),
    'types' => array(
      '#theme' => 'table',
      '#caption' => t('Transaction types, from !hook.', array('!hook' => "\n<br />hook_mcapi_info_types()")),
      '#header' => array(t('machine name')),
      '#attributes' => array('class' => array('help'))
    ),
  );
  foreach (mcapi_get_states('#full') as $name => $info) {
    $renderable['states']['#rows'][$name]['name'] = $info['name'];
    $renderable['states']['#rows'][$name]['dscription'] = $info['description'];
  }
  foreach (mcapi_get_types() as $type) {
    $renderable['types']['#rows'][$type]['name'] = $type;
  }
  return $renderable;
}

/*
 * element validate callback
* ensures that the formula for calculating a quantity works
*/
function validate_mcapi_configure_worth_formula(&$element, $form_state) {
  if (empty($element['#value'])) return;
  if (!is_numeric(_mcapi_calc($element['#value'], 100))) {
    form_error($element, t('Invalid formula'));
  }
}



function _transaction_type_selector($default, $multiple = TRUE, $exclude = NULL) {
  if (!is_array($exclude)) {
    $exclude = array('child');
  }
  $types = array_diff(mcapi_get_types(), $exclude);
  return array(
      '#title' => $multiple ? t('Transaction types') : t('Transaction type'),
      '#type' => $multiple ? 'checkboxes' : 'select',
      '#options' => drupal_map_assoc($types),
      '#default_value' => $default,
      '#required' => TRUE,
      '#element_validate' => array('mcapi_validate_ttype')
  );
}
