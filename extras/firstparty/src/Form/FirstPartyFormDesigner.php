<?php

/**
 * @file
 * Definition of Drupal\mcapi_1stparty\Form\FirstPartyFormDesigner.
 * This configuration entity is used for generating transaction forms.
 */

namespace Drupal\mcapi_1stparty\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

class FirstPartyFormDesigner extends EntityForm {
  
  function getFormId() {
    return 'first_party_editform';
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    
    
    
    $form = parent::form($form, $form_state);
    //widgetBase::Form expects this
    $form['#parents'] = array();

    $configEntity = $this->entity;
    $configEntity->set('fieldapi_presets', array());
    $template_transaction = mcapi_1stparty_make_template($configEntity);

    $form['#tree'] = TRUE;
    $w = 0;
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title of the form'),
      '#default_value' => $configEntity->title,
      '#size' => 40,
      '#maxlength' => 80,
      '#required' => TRUE,
      '#weight' => $w++,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $configEntity->id(),
      '#machine_name' => array(
        'exists' => 'mcapi_editform_load',
        'source' => array('title'),
      ),
      '#maxlength' => 12,
      '#disabled' => !$configEntity->isNew(),
    );

    $exchange = $configEntity->exchange ?
      entity_load('mcapi_exchange', $configEntity->exchange) :
      NULL;
      
    $form['path'] = array(
      '#title' => t('Path'),
      '#description' => t('The url path at which this form will appear. Must be unique. E.g. myexchange/payme'),
      '#type' => 'textfield',
      '#weight' => $w++,
      '#element_validate' => array(
        array(get_class($this), 'unique_path')
      ),
      '#default_value' => $configEntity->path,
      '#required' => TRUE
    );

    $form['menu'] = array(
      '#title' => $this->t('Menu link'),
      '#type' => 'details',
      '#weight' => $w++,
      'title' => array(
        '#title' => $this->t('Link title'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->menu['title'],
        '#weight' => 1
      ),
      'weight' => array(
        '#title' => $this->t('Weight'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->menu['weight'],
        '#description' => $this->t('In the menu, the heavier links will sink and the lighter links will be positioned nearer the top.'),
        '#weight' => 2
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="exchange"]' => array('value' => ''),
        )
      )
    );
    
    foreach (\Drupal\system\Entity\Menu::loadMultiple() as $menu_name => $menu) {
      $custom_menus[$menu_name] = $menu->label();
    }
    $form['menu']['menu_name'] = array(
      '#title' => t('Menu'),
      '#type' => 'select',
      '#options' =>  $custom_menus,
      '#required' => FALSE,
      '#empty_value' => '',
      '#default_value' => $configEntity->menu['menu_name'],
      '#weight' => 2
    );

    $form['type'] =  array(
      '#title' => t('Transaction type'),
      '#type' => 'mcapi_types',
      '#default_value' => $configEntity->type,
      '#weight' => $w++,
    );
    $form['cache'] =  array(
      '#title' => t('Caching'),
      '#description' => 'Not yet implemented',
      '#type' => 'select',
      '#options' => array(
        '' => t('-- none --'),
        'global' => t('Global'),
      ),
      '#default_value' => '',
      '#weight' => $w++,
    );

    //following section of the form allows the admin to handle the individual fields of the transaction form.
    //the fields are handled here one in each tab, each field having some shared settings and some specific ones.
    $form['steps'] = array(
      '#title' => t('Field settings'),
      '#description' => t('Missing fields?'),
      '#type' => 'vertical_tabs',
      '#weight' => $w++,
      //TODO
      //'#attributes' => new Attribute(array('id' => array('field-display-overview-wrapper'))),
      '#attributes' => array('id' => array('field-display-overview-wrapper'))
    );

    $form['mywallet'] = array(
      '#title' => t('My wallets settings'),
      '#description' => t("Choose from the current user's wallets."),
      '#type' => 'details',
      '#group' => 'steps',
      '#weight' => $w++
    );
    $form['mywallet']['widget'] = array(
      '#title' => t('Widget'),
      '#description' => t('Only for users with more than one wallet.'),
      '#type' => 'radios',
      '#options' => array(
        'select' => t('Dropdown'),
        'radios' => t('Radio buttons'),
      ),
      '#default_value' => $configEntity->mywallet['widget'],
    );
    $form['mywallet']['unused'] = array(
      '#title' => t('Unused behaviour'),
      '#description' => t('What to do when the user has just one wallet?'),
      '#type' => 'radios',
      '#options' => array(
        'disabled' => t('Greyed out'),
        'hidden' => t('Disappeared'),
      ),
      '#default_value' => intval($configEntity->mywallet['unused']),
    );

    $form['partner'] = array(
      '#title' => t('@fieldname settings', array('@fieldname' => t('Partner'))),
      '#descriptions' => t('In complex sites, it may be possible to choose a user who cannot use the currency'),
      '#type' => 'details',
      '#group' => 'steps',
      '#weight' => $w++
    );
    //if this form belongs to an exchange we can use the local_wallets autocomplete widget
    $form['partner']['preset'] = array(
      '#title' => t('Preset'),
      '#description' => $exchange ? t("Wallet owner must be, or be in, exchange '!name'.", array('!name' => $exchange->label())) : '',
      '#type' => 'select_wallet',
      '#local' => (bool)$exchange,
      '#default_value' => $configEntity->partner['preset'],
      '#multiple' => FALSE,
      '#required' => FALSE
    );

    $form['direction']= array(
      '#title' => t('@fieldname settings', array('@fieldname' => t('Direction'))),
      '#description' => t('Direction relative to the current user'),
      '#type' => 'details',
      '#group' => 'steps',
      'preset' => array(
        '#title' => t('Preset'),
        '#description' => t("Either 'incoming' or 'outgoing' relative to the logged in user"),
        '#type' => $configEntity->direction['widget'],
        //ideally these options labels would be live updated from the fields below
        '#options' => array(
          '' => t('Neither'),
          'incoming' => empty($configEntity->direction['incoming']) ? t('Incoming') : $configEntity->direction['incoming'],
          'outgoing' => empty($configEntity->direction['outgoing']) ? t('Outgoing') : $configEntity->direction['outgoing'],
        ),
        '#default_value' => $configEntity->direction['preset'],
        '#required' => TRUE
      ),
      'widget' => array(
        '#title' => t('Widget'),
        '#type' => 'radios',
        '#options' => array(
          'select' => t('Dropdown select box'),
          'radios' => t('Radio buttons')
        ),
        '#default_value' => $configEntity->direction['widget'],
        '#required' => TRUE,
        '#weight' => 1,
      ),
      'incoming' => array(
        '#title' => t("@label option label", array('@label' => t('Incoming'))),
        '#type' => 'textfield',
        '#default_value' => $configEntity->direction['incoming'],
        '#placeholder' => t('Pay'),
        '#required' => TRUE,
        '#weight' => 2
      ),
      'outgoing' => array(
        '#title' => t("@label option label",  array('@label' => t('Outgoing'))),
        '#type' => 'textfield',
        '#default_value' => $configEntity->direction['outgoing'],
        '#placeholder' => t('Request'),
        '#required' => TRUE,
        '#weight' => 3
      ),
      '#weight' => $w++
    );

    $form['description']= array(
      '#title' => t('@fieldname settings', array('@fieldname' => t('Description'))),
      '#description' => t('Direction relative to the current user'),
      '#type' => 'details',
      '#group' => 'steps',
      'preset' => array(
        '#title' => t('Preset'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->description['preset'],
        '#required' => FALSE,
      ),
      'placeholder' => array(
        '#title' => t('Placeholder'),
        '#type' => 'textfield',
        '#default_value' => $configEntity->description['placeholder'],
        '#required' => FALSE,
      ),
      '#weight' => $w++
    );
    //iterate through the field api fields adding a vertical tab for each
    foreach (mcapi_1stparty_fieldAPI() as $field_name => $data) {//need the fieldname & label & widget
      //this element will contain the default value ONLY for the fieldAPI element
      //assumes a cardinality of 1!
      $element = array();
      $element['#required'] = FALSE;
      $form['fieldapi_presets'][$field_name] = array(
        '#title' => $this->t('@fieldname preset', array('@fieldname' => $data['definition']->getLabel())),
        '#description' => $this->t(
          'For more field configuration options, see !link',
          array('!link' => $this->l(
            'admin/accounting/transactions/form-display',
            Url::fromRoute('field_ui.form_display_overview_mcapi_transaction')
          ))
        ),
        '#description_display' => 'below',//not working
        '#type' => 'details',
        '#group' => 'steps',
        'preset' => $data['widget']->formElement($template_transaction->{$field_name}, 0, $element, $form, $form_state),
        '#weight' => $w++
      );
    }

    //ensure the worth field is showing all possible currencies ()
    if ($exchange) {//for the exchange
      foreach($exchange->currencies->referencedEntities() as $entity) {
        $curr_ids[] = $entity->id();
      }
    }
    else {//or for the whole system
      $curr_ids = array_keys(entity_load_multiple('mcapi_currency'));
    }
    $form['fieldapi_presets']['worth']['preset']['#allowed_curr_ids'] = $curr_ids;
    //other modifications to the worth widget before it is processed
    $form['fieldapi_presets']['worth']['preset']['#config'] = TRUE;
    $form['fieldapi_presets']['worth']['preset']['#description'] = t('Currencies with blank in the left-most field will not appear on the form.') .' '.t('Leave every row blank to let the system decide which ones to show.');

    $form['fieldapi_presets_help'] = array(
      '#markup' => 'blah',
      '#weight' => $w++
    );
    //TODO check in 8.0 whether form elements can have rich text descriptions
    $help = $this->l(
      t('What is twig?'),
      Url::fromUri('http://twig.sensiolabs.org/doc/templates.html')
    );
    $tokens = ' {{ '. implode(' }}, {{ ', mcapi_1stparty_transaction_tokens()) .' }}';
    //TODO workout what the tokens are and write them in template1['#description']
    $form['experience'] = array(
      '#title' => t('User experience'),
      '#type' => 'details',
      '#open' => TRUE,
      'twig' => array(
        '#title' => t('Main form'),
        '#description' => t('Use the following twig tokens with HTML & css to design your payment form. Linebreaks will be replaced automatically.')
          . $tokens .' '. $help,
        '#type' => 'textarea',
        '#rows' => 6,
        '#default_value' => $configEntity->experience['twig'],
        '#element_validate' => array(
          array(get_class($this), 'validate_twig_template')
        ),
        '#weight' => 1,
        '#required' => TRUE
      ),
      'button' => array(
        '#title' => t('Button label'),
        '#description' => t("The text to appear on the 'save' button, or the absolute url of an image."),
        '#type' => 'textfield',
        '#default_value' => $configEntity->experience['button'],
        '#required' => TRUE,
        '#weight' => 2,
      ),
      'preview' => array(
        '#title' => t('Preview mode'),
        '#type' => 'radios',
        '#options' => array(
          'page' => t('Basic - Go to a fresh page'),
          'ajax' => t('Ajax - Replace the transaction form'),
          'modal' => t('Modal - Confirm in a dialogue box')
        ),
        '#default_value' => $configEntity->experience['preview'],
        '#weight' => 3,
        '#required' => TRUE
      ),
      '#weight' => $w++,
    );
    $form['#suffix'] = t(
      "N.B The confirmation page is configured separately, at !link",
      array('!link' => $this->l(
        'admin/accounting/workflow/create',
        Url::fromRoute('mcapi.workflow_settings', array('transition' => 'create'))
      ))
    );

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::validate().
   */
  public function validate(array $form, FormStateInterface $form_state) {
    //$values = $form_state->getValues();
  }

  //form element validation callback
  public static function validate_twig_template(array $element, $form_state) {
    $txt = $element['#value'];
    $errors = array();
    if (strpos($txt, "{{ mywallet }}") === NULL) {
      $form_state->setError(
        $element,
        t('@token token is required in template', array('@token' => '{{ mywallet }}'))
      );
    }
    $values = $form_state->getValues();
    //the essential transaction fields must be either present in the template or populated
    if ((strpos($txt, "{{ partner }}") === NULL) && !$values['partner']['preset']) {
      $errors[] = 'partner';
    }

    //and the same for worth
    if (strpos($txt, '{{ worth }}') == NULL) {
      $empty = TRUE;
      foreach ($values['fieldapi_presets']['worth']['preset'] as $item) {
        if ($item['value']) {
          $empty = FALSE;
          break;
        }
      }
      if ($empty)$errors[] = 'worth';
    }
    foreach ($errors as $field_name) {
      $form_state->setError(
        $element,
        t(
          'Field @fieldname neither appears in the form, nor has a preset value',
          array('@fieldname' => $field_name)
        )
      );
    }
  }
  
  /**
   * Overrides Drupal\Core\Entity\EntityForm::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    //we need to alter the structure a bit for the fieldAPI fields
    foreach ($values['fieldapi_presets'] as $field_name => $data) {
      $values['fieldapi_presets'][$field_name] = $data['preset'];
    }
    $form_state->setValue('fieldapi_presets', $values['fieldapi_presets']);

    foreach ($values as $name => $value) {
      if (!in_array($value, array('actions', 'langcode'))) {
        $this->entity->set($name, $value);
      }
    }
    $status = $this->entity->save();

    \Drupal::service('router.builder')->rebuild();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t("Form '%label' has been updated.", array('%label' => $this->entity->get('title'))));
    }
    else {
      drupal_set_message(t("Form '%label' has been added.", array('%label' => $this->entity->get('title'))));
    }
    $form_state->setRedirect('mcapi.admin_1stparty_editform_list');
  }

  //is called from the form validator, so must be public
  public static function unique_path(&$element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $dupe = db_select('router', 'r')
      ->fields('r', array('name'))
      ->condition('name', 'mcapi.1stparty.'.$values['id'], '<>')
      ->condition('path', $values['path'])
      ->execute()->fetchField();
    if ($dupe) $form_state->setError('path', t('Path is already used.'));
  }
}


