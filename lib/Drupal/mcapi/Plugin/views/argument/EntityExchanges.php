<?php

/**
 * @file
 * Contains \Drupal\mcapi\Plugin\views\argument\EntityExchanges.
 */

namespace Drupal\mcapi\Plugin\views\argument;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\views\Plugin\views\argument\Numeric;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("entity_exchanges")
 */
class EntityExchanges extends Numeric {

  protected function defineOptions() {
    $options = parent::defineOptions();

    //$options['break_phrase'] = array('default' => FALSE, 'bool' => TRUE);
    
    return array();
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset($form['no_argument']);
    unset($form['default_action']);
}

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $exchanges = array_keys(referenced_exchanges());
    $this->query->addWhere($this->definition['group'], $this->definition['field'], $exchanges);
  }

}

/*
Array ( [id] => entity_exchanges [class] => Drupal\mcapi\Plugin\views\argument\EntityExchanges [provider] => mcapi [plugin_type] => argument [field] => field_exchanges_target_id [table] => user__field_exchanges [additional fields] => Array ( ) [field_name] => field_exchanges [entity_type] => user [empty field name] => - No value - [help] => Show only what is in exchanges of the current user [group] => User [title] => Member of exchange(s) (field_exchanges:target_id) [title short] => Member of exchange(s):target_id )
*/
