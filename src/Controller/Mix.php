<?php

namespace Drupal\mix\Controller;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Mix routes.
 */
class Mix extends ControllerBase {

  /**
   * Check if is ready to use the Content sync features.
   *
   * @return bool
   *   True for ready, False for not.
   */
  public static function isContentSyncReady() {
    // Check if required modules are enabled by checking functions.
    // Can't use \Drupal::service('module_handler') here, because the container
    // wasn't ready when this is called in the MixContentSyncSubscriber.
    $isReady = function_exists('config_help') && function_exists('serialization_help');
    return $isReady;
  }

  /**
   * Check if content sync function is enabled.
   *
   * @return bool
   *   True for enabled, False for not.
   */
  public static function isContentSyncEnabled() {
    $configStorage = BootstrapConfigStorageFactory::get();
    $config = $configStorage->read('mix.settings');
    $show_content_sync_id = $config['show_content_sync_id'] ?? FALSE;
    $isEnabled = self::isContentSyncReady() && $show_content_sync_id;
    return $isEnabled;
  }

}
