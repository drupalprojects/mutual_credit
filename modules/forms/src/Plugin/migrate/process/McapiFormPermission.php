<?php

/**
 * @file
 */
namespace Drupal\mcapi_forms\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\user\PermissionHandler;
use Drupal\Core\Transliteration\PhpTransliteration;

/**
 * Work out which permission might restrict access to the form.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_mcapi_form_perm"
 * )
 */
class McapiFormPermission extends ProcessPluginBase implements ContainerFactoryPluginInterface {


  /**
   * @var type \Drupal\user\PermissionHandler
   */
  private $permissionHandler;

  /**
   * @var \Drupal\Core\Transliteration\PhpTransliteration
   */
  private $transliteration;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * Parent params plus:
   *
   * @param PermissionHandler $permissions
   * @param PhpTransliteration $transliteration
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, PermissionHandler $permissions, PhpTransliteration $transliteration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permissions;
    $this->transliteration = $transliteration;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions'),
      $container->get('transliteration')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    list($callback, $arg) = explode(':', $value->access);
    $perm = '';
    // These values are settings from the user_chooser module which no longer exists in Drupal 8
    switch ($callback) {
      case 'user_chooser_segment_perms': //The args are permissions.
        //If a permission exists, use it
        $permissions = $this->permissionHandler->getPermissions();
        if (in_array($arg, $permissions)) {
          $perm = $arg;
        }
        //leave perm field blank
        break;

      case 'user_chooser_segment_roles':
        //If a role exists, use it
        //We injected the old roles, which were made into machine names
        // $arg is the old role id, a number
        if ($d8_role_name = $this->machineName($value->roles[$arg])) {
          if ($role = Role::load($d8_role_name)) {
            // Now we have the role, how do we determine a permission?
            //Take the first and issue a warning.
            $perm = key($role->getPermissions());
            drupal_set_message("Access to transaction form used to be role '$d8_role_name' but has now been set to permission '$perm'", 'warning');
            break;
          }
          else {
            drupal_set_message("Could not find  expected fole: $d8_role_name", 'error');
          }
        }
        else {
          drupal_set_message("Could not identify d7 role with ID of ".$arg, 'error');
        }
        break;

      default:
        drupal_set_message("Could not determine a permission for designed transaction form", 'warning');
    }


    return $perm;
  }

  /**
   * Borrowed from Drupal\migrate\Plugin\migrate\process\MachineName
   */
  function machineName($value) {
    $new_value = $this->transliteration->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $new_value = strtolower($new_value);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

}
