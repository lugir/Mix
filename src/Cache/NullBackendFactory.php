<?php

namespace Drupal\mix\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\NullBackend;

class NullBackendFactory implements CacheFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    return new NullBackend($bin);
  }

  /**
   * Deletes all cache items in a bin.
   */
  public function deleteAll() {}

}
