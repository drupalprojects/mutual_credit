<?php


/**
 * Implements hook_i18n_string_info().
 */
function mcapi_forms_i18n_string_info() {
  $groups['payforms'] = array(
    'title' => t('Designed forms'),
    'description' => t('Templates for payment forms'),
    'format' => TRUE,
    'list' => TRUE,
  );
  return $groups;
}


/**
 * Implements hook_i18n_object_info().
 */
function mcapi_forms_i18n_object_info() {
  $info['payform_experience'] = array(
    // Generic object title.
    'title' => t('Community accounting designed forms'),
    // The object (or array) key field.
    'key' => 'name',
    // Placeholders for automatic paths.
    'placeholders' => array(
      '%ctools_export_ui' => 'name',
    ),
    // To produce edit links automatically.
    'edit path' => 'admin/accounting/forms/list/%ctools_export_ui/edit',
    // Auto-generate translate tab.
    'translate tab' => 'admin/accounting/forms/list/%ctools_export_ui/translate',
    // Properties for string translation.
    'string translation' => array(
      // Text group that will handle this object's strings.
      'textgroup' => 'payforms',
      // Object type property for building up the translation name thus payforms:formname:experience:property
      'type' => 'experience',
      // Table where the object is stored, to automate string lists
      'table' => 'mcapi_forms',
      // Translatable properties of these objects.
      'properties' => array(
        'title' => t('Page title'),
        'template' => array(
          'title' => t('Template'),
          'format' => 'templates_format',
        ),
        'sure_title' => t('Sure title'),
        'sure_template' => array(
          'title' => t('Sure template'),
          'format' => 'templates_format',
        ),
        'button' => t('Save button label'),
        'feedback' => t('Confirmation message')
      ),
      // Path to translate strings to every language.
      'translate path' => 'admin/accounting/forms/list/%ctools_export_ui/translate',
    )
  );
  return $info;
}
