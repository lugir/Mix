<?php

namespace Drupal\mix\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Mix content sync subscriber.
 */
class MixContentSyncSubscriber implements EventSubscriberInterface {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Supported entity types.
   *
   * @var array
   */
  protected $supportedEntityTypeMap = [
    'block_content'     => 'Drupal\block_content\Entity\BlockContent',
    'menu_link_content' => 'Drupal\menu_link_content\Entity\MenuLinkContent',
    'taxonomy_term'     => 'Drupal\taxonomy\Entity\Term',
  ];

  /**
   * Constructs a ResourceResponseSubscriber object.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   */
  public function __construct(SerializerInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onImportTransform'];
    return $events;
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {

    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();

    // Get config names of allowed content.
    $content_sync_ids = \Drupal::config('mix.settings')->get('content_sync_ids');

    foreach ($content_sync_ids as $configName) {
      $uuid = substr($configName, strrpos($configName, '.') + 1);
      // Parse entityType.
      $entityType = $this->parseEntityType($configName);
      // Ignore wrong sync ID or unsupported entity types.
      if (!$entityType || !in_array($entityType, array_keys($this->supportedEntityTypeMap))) {
        continue;
      }
      $contentEntity = \Drupal::service('entity.repository')->loadEntityByUuid($entityType, $uuid);
      // Ignore non-existed entity.
      if (!$contentEntity) {
        continue;
      }
      // Seems the core will ignore the numeric IDs when create new entity
      // in the import process. So we don't remove entity id and other numeric
      // IDs after the normalization.
      $array = $this->serializer->normalize($contentEntity);

      // @todo Add configuration dependencies to ensure import order.
      // Add new method to add "dependencies" key.
      // $this->addConfigDependency($array);
      // Save normalized content entity into config file.
      $storage->write($configName, $array);
    }

  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {

    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();
    $content_sync_ids = \Drupal::config('mix.settings')->get('content_sync_ids');

    foreach ($content_sync_ids as $configName) {
      $array = $storage->read($configName);
      // Ignore empty config.
      if (empty($array)) {
        continue;
      }
      $uuid = substr($configName, strrpos($configName, '.') + 1);
      // Parse entityType.
      $entityType = $this->parseEntityType($configName);
      // Ignore wrong sync ID or unsupported entity types.
      if (!$entityType || !in_array($entityType, array_keys($this->supportedEntityTypeMap))) {
        continue;
      }
      // Load entity.
      $existedEntity = \Drupal::service('entity.repository')->loadEntityByUuid($entityType, $uuid);

      // Only create content when it's not exists.
      if (!$existedEntity) {
        // @todo Perform this after `drush cim` has been confirmed.
        // @todo Provide an UI to update existed content.
        // @todo Remove config dependence key before the denormalization.
        $entity = $this->serializer->denormalize($array, $this->supportedEntityTypeMap[$entityType], 'yaml');
        $entity->save();
      }

    }
  }

  /**
   * Parse entity type from config name.
   */
  private function parseEntityType($configName) {
    if (strpos($configName, 'taxonomy.term.') === 0) {
      $entityType = 'taxonomy_term';
    }
    else {
      $entityType = substr($configName, 0, strpos($configName, '.'));
    }
    return $entityType;
  }

}
