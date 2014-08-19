<?php

/**
 * @file
 * Definition of Drupal\mcapi\Plugin\views\argument\OwnedWallets.
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Views;

/**
 * The fixed argument default handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("owned_wallets")
 */
class OwnedWallets extends ArgumentPluginBase {

  protected function defineOptions() {
    $options['entity'] = array('default' => 'current_user');
    return $options;
  }

  function buildOptionsForm(&$form, FormStateInterface $form_state) {
    //parent::buildOptionsForm($form, $form_state);
    $form['entity'] = array(
    	'#title' => $this->t('Grab entity from'),
      '#type' => 'select',
      '#options' => array(
    	  'current_user' => t('Current user'),
        'url_entity' => t('Entity from url'),
        //more should be possible
      ),
      '#default_value' => $this->options['entity']
    );
  }

  function validateOptionsForm(&$form, FormStateInterface $form_state) {}

  function submitOptionsForm(&$form, FormStateInterface $form_state) {}

  function getPlugin($type = 'argument_default', $name = NULL) {
    $manager = Views::pluginManager($type);
    if ($type == 'argument_default') {
      if($this->options['entity'] == 'current_user') {
        $plugin = $manager->createInstance('current_user');
      }
      elseif($this->options['entity'] == 'entity_from_context') {
        //TODO write this plugin
        $plugin = $manager->createInstance('current_user');
      }
    }
    elseif ($type == 'argument_validator') {
      //there's no validator just to check if it is an entity.
      //only to check if the entity is of the right type.
      $plugin = $manager->createInstance('none');
    }
    elseif ($type == 'style') {
      $plugin = $manager->createInstance('default');
    }
    if($plugin) {
      $plugin->init($this->view, $this->displayHandler);
      if ($type !== 'style') {
        $plugin->setArgument($this);
      }
      return $plugin;
    }
  }
  public function needsStylePlugin() {
    return FALSE;
  }
  public function validateFail() {
    return 'not found';
  }

  public function defaultAction($info = NULL) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function query($group_by = FALSE) {
    $this->ensureMyTable();
    $account = User::load(\Drupal::currentUser()->id());
    $this->value = \Drupal\mcapi\Storage\WalletStorage::getOwnedWalletIds($account);
    $this->query->addWhereExpression(0, "$this->tableAlias.wid IN (".implode(',', $this->value).")");
  }

}
