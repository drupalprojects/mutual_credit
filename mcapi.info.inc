<?php

/*
 * implements entity hook_entity_property_info for the transaction entity
 * required field makes it appear in the rules new entity config form
 */
function mcapi_entity_property_info() {
  $info = array();
  // Add meta-data about the basic node properties.
  $properties = &$info['transaction']['properties'];

  $info['transaction']['properties']['serial'] = array(
    'label' => t("Transaction ID"),
    'type' => 'integer',
    'description' => t("The unique serial number of the transaction."),
    'schema field' => 'serial',
    'getter callback' => 'entity_property_verbatim_get',//this is added by default if not put here
  );
  $info['transaction']['properties']['transaction_type'] = array(
    'label' => t("Transaction type"),
    'type' => 'list<text>',
    'options list' => 'mcapi_get_types',
    'schema field' => 'type',
    'getter callback' => 'entity_property_verbatim_get',
  );
  $info['transaction']['properties']['payer'] = array(
    'label' => t("Payer"),
    'description' => t("The user ID of the payer"),
    'type' => 'user',
    'schema field' => 'payer',
    'required' => TRUE,
    'getter callback' => 'entity_property_verbatim_get',//might want to convert to username
  );
  $info['transaction']['properties']['payee'] = array(
    'label' => t("Payee"),
    'description' => t("The user ID of the payee"),
    'type' => 'user',
    'schema field' => 'payee',
    'required' => TRUE,
    'getter callback' => 'entity_property_verbatim_get',//might want to convert to username
  );
  $info['transaction']['properties']['created'] = array(
    'label' => t("Unixtime of transaction creation"),
    'type' => 'date',//not sure if this is correct, maybe it should be an integer coz it is stored as unixtime
    'schema field' => 'created',
    'getter callback' => 'entity_property_verbatim_get',
  );
  $info['transaction']['properties']['creator'] = array(
    'label' => t("User who created the transaction"),
    'type' => 'user',
    'schema field' => 'creator',
    'getter callback' => 'entity_property_verbatim_get',//might want to convert to username
  );

  $info['transaction']['properties']['state'] = array(
    'label' => t("State"),
    'type' => 'integer',
    'description' => t("Workflow state. By convention, greater than zero means it is counted."),
    'schema field' => 'state',
    'getter callback' => 'entity_property_verbatim_get',
  );
  return $info;
}


//this is a property callback for the worth field in mcapi_field_info
function entity_metadata_field_worth_property_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $info[$entity_type]['bundles'][$instance['bundle']]['properties'][$field['field_name']] = array(
    'label' => t('Worth'),
    //'getter callback' => 'entity_metadata_field_worth_get',//nothing special needed here
    //'setter callback' => 'entity_metadata_field_verbatim_set', //here also
    'type' => ($field['cardinality'] != 1) ? 'list<worth>' : 'worth',
    'property info' => entity_property_field_item_worth_info(),
  );
}
/*
 * implements entity module hook entity_property_PROPERTY_TYPE_info
 * PROPERTY_TYPE is referenced in hook_field_info
 */
function entity_property_field_item_worth_info() {
  //debug('entity_property_field_item_worth_info', 'not sure if special callbacks are needed here');
  return;
  return array(
    'currcode' => array(
      'type' => 'text',
      'label' => t('Currency code'),
      //first we'll try with a standard callback
      'getter callback' => 'entity_property_verbatim_get',
      'setter callback' => 'entity_property_verbatim_set',
    ),
    'quantity' => array(
      'type' => 'decimal',
      'label' => t('Quantity'),
      //then we'll try custom callbacks
      'getter callback' => 'entity_metadata_field_property_get_mcapi_quantity',
      'setter callback' => 'entity_property_verbatim_set',
    ),
  );
}

//I think this is an entity module hook
function mcapi_options_list($field) {
  //debug($field);
}

/*
 * implements hook_entity_views_field_handlers_alter
 */
function mcapi_entity_views_field_handlers_alter(&$field_handlers) {
  //this views handler isn't written yet coz I haven't worked out how the entity module integrates with views
  $field_handlers['worth'] = 'entity_views_handler_field_mcapi_worth';
}

function entity_views_handler_field_mcapi_worth($arg1) {
  //debug($arg1);
}



/*
//getter callback for worth fieldAPI field
function entity_metadata_field_worth_get($entity, $options, $name, $type, $info) {
  debug('entity_metadata_field_worth_set');
  //this field is untranslatable
  return $entity->worth[LANGUAGE_NONE];
}
//setter callback for worth fieldAPI field
function entity_metadata_field_worth_set() {
  debug('entity_metadata_field_worth_set');
}
 *
 */


/*
 * these functions present the components of the worth field as an entity property
 * they are called without the .info.inc file necessarily having being included
 */
/*
function entity_metadata_field_property_get_mcapi_currcode($data, $options, $name, $type, $info) {
  foreach ($data[LANGUAGE_NONE] as $delta => $data) {
    $values[$delta] = $data['currcode'];
  }
  return $values;
}
 *
 */
function entity_metadata_field_property_get_mcapi_quantity($data, $options, $name, $type, $info) {
  foreach ($data[LANGUAGE_NONE] as $delta => $data) {
    $values[$delta] = $data['quantity'];
  }
  return $values;
}


//this is a property callback in mcapi_field_info
function ______entity_metadata_field_worth_property_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $property = &$info[$entity_type]['bundles'][$instance['bundle']]['properties'][$field['field_name']];
  $property['label'] = t('Worth');
  $property['type'] = ($field['cardinality'] != 1) ? 'list<worth>' : 'worth';
  $property['property info'] = entity_property_field_item_worth_info();
}
