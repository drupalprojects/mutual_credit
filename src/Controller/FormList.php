<?php

/**
 * @file
 * Contains \Drupal\mcapi\Controller\FormList.
 *
 */

namespace Drupal\mcapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Returns responses for Wallet routes.
 */
class FormList extends ControllerBase {

  function buildPage() {
    //work out the menu links available for each path
    foreach ($this->moduleHandler()->invokeAll('mcapi_form_list') as $rowname => $row) {
      $row += ['route_parameters' => [], 'operations' => []];
      $actions = $menu_links = [];
      $url = isset($row['route']) ?
        Url::fromRoute($row['route'], $row['route_parameters']) :
        NULL;
      $rows[$rowname] = [
        'title' => $row['link'],
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
    //add forms for which entityFormDisplays exist
    foreach (\Drupal\Core\Entity\Entity\EntityFormDisplay::loadMultiple() as $form_display) {
      if ($form_display->getTargetEntityTypeId() == 'mcapi_transaction') {
        $mode_id = $form_display->getMode();
        //the 'default' form mode may not have been saved
        $form_mode = \Drupal\Core\Entity\Entity\EntityFormMode::load('mcapi_transaction.'.$mode_id);
        $link = $form_mode ?  $form_mode->label() : Link::fromTextAndUrl($this->t('Default'), Url::fromRoute('mcapi.transaction.admin'));
        $items[$mode_id] = [
          'link' => $link,
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
    $items = $this->moduleHandler()->invokeAll('mcapi_form_list') + $items;
    return $items;
  }
}
