<?php

namespace Drupal\mcapi\Controller;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Builds a list of transaction forms.
 */
class FormList extends ControllerBase {

  /**
   * Build a table showing all the transaction forms.
   */
  public function buildPage() {
    // Work out the menu links available for each path.
    foreach ($this->moduleHandler()->invokeAll('mcapi_form_list') as $rowname => $row) {
      $row += ['route_parameters' => [], 'operations' => []];
      $rows[$rowname] = [
        'title' => $row['link'],
        // @todo getInternalPath is deprecated but I can't see the proper way to do it.
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $row['operations'],
          ],
        ],
      ];
    }
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        'title' => $this->t('Form name'),
        'operations' => $this->t('Form operations'),
      ],
      '#rows' => $rows,
    ];
    return $build;
  }

  /**
   * Retrieve the transaction forms using a hook.
   */
  private function getForms() {
    // Add forms for which entityFormDisplays exist.
    foreach (EntityFormDisplay::loadMultiple() as $form_display) {
      if ($form_display->getTargetEntityTypeId() == 'mcapi_transaction') {
        $mode_id = $form_display->getMode();
        // The 'default' form mode may not have been saved.
        $form_mode = EntityFormMode::load('mcapi_transaction.' . $mode_id);
        $link = $form_mode ? $form_mode->label() : Link::fromTextAndUrl($this->t('Default'), Url::fromRoute('mcapi.transaction.admin'));
        $items[$mode_id] = [
          'link' => $link,
          'operations' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute(
                "entity.entity_form_display.mcapi_transaction.form_mode",
                ['form_mode_name' => $mode_id]
              ),
            ],
          ],
        ];
      }
    }

    // @todo document this hook in mcapi.api.php
    $items = $this->moduleHandler()->invokeAll('mcapi_form_list') + $items;
    return $items;
  }

}
