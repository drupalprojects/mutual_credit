<?php

/*
 * implements entity hook_entity_property_info
 * required field makes it appear in the rules new entity config form
 */
function mcapi_entity_property_info() {
  $info = array();
  // Add meta-data about the basic node properties.
  $properties = &$info['transaction']['properties'];

  $properties['serial'] = array(
    'label' => t("Transaction ID"),
    'type' => 'integer',
    'description' => t("The unique serial number of the transaction."),
    'schema field' => 'serial',
    'getter callback' => 'entity_property_verbatim_get',//this is added by default if not put here
  );
  $properties['transaction_type'] = array(
    'label' => t("Transaction type"),
    'type' => 'list<text>',
    'options list' => 'mcapi_get_types',
    'schema field' => 'type',
    'getter callback' => 'entity_property_verbatim_get',
  );
  $properties['payer'] = array(
    'label' => t("Payer"),
    'description' => t("The user ID of the payer"),
    'type' => 'user',
    'schema field' => 'payer',
    'required' => TRUE,
    'getter callback' => 'entity_property_verbatim_get',//might want to convert to username
  );
  $properties['payee'] = array(
    'label' => t("Payee"),
    'description' => t("The user ID of the payee"),
    'type' => 'user',
    'schema field' => 'payee',
    'required' => TRUE,
    'getter callback' => 'entity_property_verbatim_get',//might want to convert to username
  );
  $properties['created'] = array(
    'label' => t("Unixtime of transaction creation"),
    'type' => 'integer',
    'schema field' => 'created',
    'getter callback' => 'entity_property_verbatim_get',
  );
  $properties['creator'] = array(
    'label' => t("User who created the transaction"),
    'type' => 'user',
    'schema field' => 'creator',
    'getter callback' => 'entity_property_verbatim_get',//might want to convert to username
  );
  $properties['state'] = array(
    'label' => t("State"),
    'type' => 'integer',
    'description' => t("Workflow state. By convention, greater than zero means it is counted."),
    'schema field' => 'state',
    'getter callback' => 'entity_property_verbatim_get',
  );
  return $info;
}


//this is a property callback in mcapi_field_info
function entity_metadata_field_worth_property_callback(&$info, $entity_type, $field, $instance, $field_type) {
  $property = &$info[$entity_type]['bundles'][$instance['bundle']]['properties'][$field['field_name']];
  $property['label'] = t('Worth');
  $property['getter callback'] = 'entity_metadata_field_worth_get';
  $property['setter callback'] = 'entity_metadata_field_verbatim_set';
  $property['type'] = ($field['cardinality'] != 1) ? 'list<worth>' : 'worth';
  $property['property info'] = entity_property_field_item_worth_info();
}

/*
 * implements entity module hook entity_property_PROPERTY_TYPE_info
 * PROPERTY_TYPE is referenced in hook_field_info
 */
function entity_property_field_item_worth_info() {
  return array(
    'currcode' => array(
      'type' => 'text',
      'label' => t('Currency code'),
      'getter callback' => 'entity_property_verbatim_get',
      'setter callback' => 'entity_property_verbatim_set',
    ),
    'quantity' => array(
      'type' => 'decimal',
      'label' => t('Quantity'),
      'getter callback' => 'entity_metadata_field_property_get_mcapi_quantity',
      'setter callback' => 'entity_property_verbatim_set',
    ),
  );
}

//I think this is an entity module hook
function mcapi_options_list($field) {
  debug($field);
}

/*
 * implements hook_entity_views_field_handlers_alter
 * not sure its needed actually
 */
function mcapi_entity_views_field_handlers_alter(&$field_handlers) {
  //this views handler isn't written yet coz I haven't worked out how the entity module integrates with views
  $field_handlers['worth'] = 'entity_views_handler_field_mcapi_worth';
}



/*
 * integration with the entity module
 * these two functions present the components of the worth field as an entity property
 */
function entity_metadata_field_property_get_mcapi_currcode($data, $options, $name, $type, $info) {
  foreach ($data[LANGUAGE_NONE] as $delta => $data) {
    $values[$delta] = $data['currcode'];
  }
  return $values;
}
function entity_metadata_field_property_get_mcapi_quantity($data, $options, $name, $type, $info) {
  foreach ($data[LANGUAGE_NONE] as $delta => $data) {
    $values[$delta] = $data['quantity'];
  }
  return $values;
}
function entity_metadata_field_worth_get($entity, $options, $name, $type, $info) {
  return $entity->worth[LANGUAGE_NONE];
}