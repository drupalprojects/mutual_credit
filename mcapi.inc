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
  //TODO include operations
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
          'wallets_summary' => array(
            'label' => t('Trading summary'),
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
  return $extra;
}


function mcapi_view_wallet_summaries($wids) {
  if ($wallets = entity_load_multiple('mcapi_wallet', $wids)) {
    $renderable = array(
    	'#type' => 'item',
      '#title' => t('Wallets'),
    );
    foreach ($wallets as $wallet) {
      $renderable[] = mcapi_view_wallet($wallet);
    }
    return $renderable;
  }
  return array();
}

/*
 * a nice display of user balances in a currency
 */
function mcapi_view_wallet($wallet) {
  $render_array = array();
  if ($wallet->access('view')) {
    $render_array = array(
      '#theme' => 'wallet',
      '#wallet' => $wallet
    );
  }
  return $render_array;
}

/**
 * theme preprocessor for 'wallet'
 * @todo this theme callback is cached - when to clear the cache and what cache is it?
 * @param array $vars
 */
function template_preprocess_wallet(&$vars) {
  $currdata = \Drupal::entityManager()
    ->getStorageController('mcapi_transaction')
    ->summaryData($vars['wallet'], array());

  foreach ($currdata as $currcode => $data) {
    $currency = mcapi_currency_load($currcode);
    $vars['currdata'][$currcode]['label'] = $currency->label();
    $vars['currdata'][$currcode]['gross_in'] = $currency->format($data['gross_in']);
    $vars['currdata'][$currcode]['gross_out'] = $currency->format($data['gross_out']);
    $vars['currdata'][$currcode]['volume'] = $currency->format($data['volume']);
    $vars['currdata'][$currcode]['balance'] = $currency->format($data['balance']);
    $vars['currdata'][$currcode]['partners'] = $data['partners'];
    $vars['currdata'][$currcode]['trades'] = $data['trades'];
  }
  $vars['url'] = url('wallet/'.$vars['wallet']->id());
  $vars['name'] = $vars['wallet']->get('name')->value;
}

/**
 * element pre_render callback
 * adds the user account transaction summary to the render array
 *
 * @param array $element
 *   a renderable array
 * @return
 *   a more populated renderable array, ready for theming
 */
function mcapi_pre_render_summary($element) {
  static $results = array();//TODO should this be drupal_static?
  $uid = $element['#account']->id();
  //occasionally we'll appreciate a static result
  $result = &$results[$uid][$element['#currency']->id()];
  if (empty($result)) {
    $stats = \Drupal::entityManager()
    ->getStorageController('mcapi_transaction')
    ->summaryData($element['#account'], $element['#currency'], $element['#filters']);
    foreach ($stats as $key => $val) {
      $result['#'.$key] = $val;
    }
  }
  return $element + $result;
}




/**
 * generate balance histories for one user and many currencies
 *
 * @param AccountInterface $account
 * @param array $currcodes
 * @param integer $since
 *   unixtime before which to truncate
 * @return array
 *   an array of histories keyed by currcode
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

//
/**
 * implements template_preprocess_THEME
 * modify the history points for aesthetic reasons, or to save resources
 *
 * @param array $vars
 */
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

/**
 * implements hook_process_THEMEHOOK for balance_histories_gchart
 * generates the javascript for the gchart from the user's history of each currency
 * TODO cache this and clear the cache
 *
 * @param array $vars
 */
function mcapi_process_balance_histories_gchart(&$vars) {
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
		'transaction-'. $transaction->get('serial')->value,
		$element['#view_mode'] == 'certificate' ? 'certificate' : 'customtwig',
		$transaction->type->value,
		'state-'.$transaction->get('state')->value
	);
}

/**
 * implements template_preprocess_THEMEHOOK for theme callback 'certificate'
 *
 * @param array $vars
 */
function template_preprocess_certificate(&$vars) {
  $element = $vars['transaction'];
	$transaction = $element['#mcapi_transaction'];

	mcapi_transaction_process_vars($vars, $transaction, !empty($element['#showlinks']));
  //hide the field api labels
	foreach(element_children($element) as $sub) {
	  if ($element[$sub]['#theme'] == 'field') {
	    $element[$sub]['#label_display'] = 'hidden';
	  }
	  $v[$sub] = $element[$sub];
	}

	/* @todo need to work out how to get entities out of an entity_reference field
	$vars['children'] = array();
	if (isset($transaction->children)) {
	  foreach ($transaction->get('children') as $child) {....?
	  //how to we call transaction view now?
	  $vars['children'] = transaction_view(
	    $transaction->children,
	    str_replace('[transaction:links]', '', \Drupal::config('mcapi.misc')->get('sentence_template'))
	  );
	}
	*/
	//choose the transaction theme template based on the first currency of the transaction
	//not sure if this is worth the processing power at the moment

	$vars['theme_hook_suggestions'][] = 'certificate__' . $transaction->get('type')->value;
}

/**
 * theme implementation for callback twig
 * @param array $vars
 */
function theme_mcapi_twig($vars) {
	return mcapi_render_twig_transaction(
	  $vars['twig'],
	  $vars['mcapi_transaction'],
	  $vars['showlinks']
	);
}

/**
 * Helper for theming transactions
 *
 * @param array $vars
 * @param TransactionInterface $transaction
 * @param boolean $showlinks
 *   TRUE if the 'links' variable is required
 */
function mcapi_transaction_process_vars(array &$vars, TransactionInterface $transaction, $showlinks = TRUE) {
  $static = drupal_static(__FUNCTION__, array());
  $xid = $transaction->get('xid')->value;
  if (!array_key_exists($xid, $static)) {
    $states = mcapi_get_states(TRUE);
    $types = mcapi_get_types();
    $v['state'] = $states[$transaction->state->value];
    $v['type'] = $types[$transaction->type->value]->label;
    $v['serial'] = $transaction->serial->value;
    $v['payer'] = $transaction->payer->entity->label();
    $v['payee'] = $transaction->payee->entity->label();
    $v['creator'] = $transaction->creator->entity->label();
    $v['description'] = $transaction->description->value;
    $v['worths'] = $transaction->worths->getString();
    $v['created'] = format_date($transaction->created->value, 'medium', '', NULL, Language::LANGCODE_DEFAULT);

    //@todo more ugliness retrieving from entity_reference field
    $exchange_field = ($transaction->get('exchange')->getValue(TRUE));
    $v['exchange'] = $exchange_field[0]['entity']->label();
    $v['links'] = $showlinks ? $transaction->links() : '';
    //the token service lets you pass url options,
    //but we'll simply do it here and just make all links absolute
    $uri = $transaction->uri();
    $v['url'] = url($uri['path'], array('absolute' => TRUE));

    $static[$xid] = $v;
  }
  $vars += $static[$xid];
}


/**
 * Prepares a transaction, and renders it using a passed twig template
 *
 * @param string $template
 * @param TransactionInterface $transaction
 * @param string $showlinks
 */
function mcapi_render_twig_transaction(string $template, TransactionInterface $transaction, $showlinks = TRUE) {
  $vars = array();
  $xid = $transaction->id();
  //Here we have to get all the fieldAPI fields rendered and in $vars
  $entities = array($xid => $transaction);
  $build = \Drupal::entityManager()
    ->getViewBuilder('mcapi_transaction')
    ->viewMultiple($entities, 'certificate');
  mcapi_transaction_process_vars($vars, $transaction, $showlinks);
  //hide the fieldAPi labels
  foreach(element_children($build[$xid]) as $sub) {
    if ($build[$xid][$sub]['#theme'] == 'field') {
      $build[$xid][$sub]['#label_display'] = 'hidden';
    }
    $v[$sub] = $build[$xid][$sub];
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
  $name = 'modules/mutual_credit/templates/certificate.html.twig';
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
 *   if TRUE the result will include the operation links
 * @return array
 *   names of replacable elements in the transaction
 */
function mcapi_transaction_list_tokens($include_virtual = FALSE) {
  $tokens = \Drupal::entityManager()->getFieldDefinitions('mcapi_transaction');
  //looks like fieldmap isn't needed at all, which will simplify this function
  unset($tokens['uuid'], $tokens['xid'], $tokens['parent'], $tokens['type'], $tokens['children']);
  $tokens = array_keys($tokens);

  if ($include_virtual){
    $tokens[] = 'links';
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
    if (in_array('entity_type', array('mcapi_exchange', 'transaction', 'wallet'))) continue;
    foreach ($data1 as $bundle => $fields) {
      foreach ($fields as $fieldname => $fieldInfo) {
        if ($fieldInfo->getType() == 'entity_reference') {
          if ($fieldInfo->getSetting('target_type') == 'mcapi_exchange') {
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