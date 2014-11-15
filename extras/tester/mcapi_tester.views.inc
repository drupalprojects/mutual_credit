<?php


/*
 * implements hook_views_data_alter
*/
function mcapi_tester_views_data_alter(&$data) {

  //for now we'll override the status filter with this one,
  //which allows permitted users to see blocked accounts
  //but we could equally make this its own views handler
  $data['users']['edit_node']['field']['id'] = 'admin_link_edit';
  $data['users']['edit_node']['field']['help'] = 'Show edit link to permitted users';

}
