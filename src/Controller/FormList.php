<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\FormList.
 *
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for Wallet routes.
 */
class FormList extends ControllerBase {

  function buildPage() {
    //work out the menu links available for each path
    foreach ($this->getForms() as $rowname => $row) {
      $row += ['route_parameters' => [], 'operations' => []];
      $actions = $menu_links = [];
      $url = isset($row['route']) ?
        Url::fromRoute($row['route'], $row['route_parameters']) :
        NULL;
      $rows[$rowname] = [
        'title' => $url ?
          \Drupal::l($row['title'] , $url) :
          $row['title'],

        //@todo getInternalPath is deprecated but I can't see the proper way to do it.
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $row['operations']
          ]
        ],
      ];
    }
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        'title' => $this->t('Form name'),
        'operations' => $this->t('Form operations'),
      ],
      '#rows' => $rows
    ];
    return $build;
  }

  private function getForms() {
    $items = $this->moduleHandler()->invokeAll('mcapi_form_list');
    foreach (\Drupal\Core\Entity\Entity\EntityFormDisplay::loadMultiple() as $form_display) {
      if ($form_display->getTargetEntityTypeId() == 'mcapi_transaction') {
        $mode_id = $form_display->getMode();
        if (isset($items[$mode_id])) continue;
        if ($mode_id == '1stparty')continue;

        //the 'default' form mode may not have been saved
        $form_mode = \Drupal\Core\Entity\Entity\EntityFormMode::load('mcapi_transaction.'.$mode_id);
        $items[$mode_id] = [
          'title' => $form_mode->label(),
          'operations' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute(
                "entity.entity_form_display.mcapi_transaction.form_mode",
                ['form_mode_name' => $mode_id]
              )
            ]
          ]
        ];
      }
    }

    //@todo document this hook in mcapi.api.php
    $items = $this->moduleHandler()->invokeAll('mcapi_form_list')+ $items;
    return $items;
  }
}
