<?php

namespace Drupal\mix;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the Mix module.
 */
class MixServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->register('cache.render', 'Drupal\mix\Cache\NullBackendFactory');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    // Get config.
    $configStorage = BootstrapConfigStorageFactory::get();
    $mixSettings = $configStorage->read('mix.settings');

    // Only alter services when dev_mode is enabled.
    if (isset($mixSettings['dev_mode']) && $mixSettings['dev_mode']) {

      // Enable cacheability headers debug.
      $container->setParameter('http.response.debug_cacheability_headers', TRUE);

      // Enable twig debug.
      $twig_config = $container->getParameter('twig.config');
      $twig_config['debug'] = TRUE;
      $twig_config['auto_reload'] = TRUE;
      $twig_config['cache'] = FALSE;
      $container->setParameter('twig.config', $twig_config);

      // Disable cache bins by changed cache backend to NullBackendFactory.
      $ids = ['cache.render', 'cache.page', 'cache.dynamic_page_cache'];
      foreach ($ids as $id) {
        if ($container->hasDefinition($id)) {
          $definition = $container->getDefinition($id);
          $definition->setClass('Drupal\mix\Cache\NullBackendFactory');
          $definition->addTag('cache.bin', ['default_backend' => 'mix.cache.backend.null']);
        }
      }

    }

  }

}
