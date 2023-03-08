<?php

namespace Drupal\mix\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\mix\Controller\MixContentSyncController;
use Drupal\mix\EventSubscriber\MixContentSyncSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Configure Mix settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * Constructs a Drupal\mix\Form\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The drupal kernel.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UrlGeneratorInterface $url_generator, StateInterface $state, DrupalKernelInterface $kernel, SerializerInterface $serializer) {
    $this->setConfigFactory($config_factory);
    $this->urlGenerator = $url_generator;
    $this->state = $state;
    $this->kernel = $kernel;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('url_generator'),
      $container->get('state'),
      $container->get('kernel'),
      $container->get('serializer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mix_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mix.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('mix.settings');

    $form['dev'] = [
      '#type' => 'details',
      '#title' => $this->t('Development'),
      '#open' => TRUE,
    ];

    // Check dev mode and give tips.
    $devMode = $config->get('dev_mode');
    $form['dev']['dev_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable development mode'),
      '#description' => $this->t('Quick switch between Dev/Prod modes to make module and theme develpment way more easier.'),
      '#default_value' => $devMode,
    ];

    // Help content for dev_mode configuration.
    $form['dev']['dev_mode_help'] = [
      '#type' => 'inline_template',
      '#template' => '<details>
        <summary>{% trans %}Dev mode vs. Prod mode{% endtrans %}</summary>
        <table>
          <tr>
            <th>{% trans %}Configuration items{% endtrans %}</th>
            <th>{% trans %}Dev mode{% endtrans %}</th>
            <th>{% trans %}Prod mode{% endtrans %}</th>
          </tr>
          <tr>
            <td>{% trans %}Twig templates debugging{% endtrans %}</td>
            <td>{% trans %}Enable twig debug{% endtrans %}<br>
                {% trans %}Enable auto reload{% endtrans %}<br>
                {% trans %}Disable cache{% endtrans %}</td>
            <td>{% trans %}Disable twig debug{% endtrans %}<br>
                {% trans %}Disable auto reload{% endtrans %}<br>
                {% trans %}Enable cache{% endtrans %}</td>
          </tr>
          <tr>
            <td>{% trans %}Backend caches (render cache, page cache, dynamic page cache, etc.){% endtrans %}</td>
            <td>{% trans %}Disable{% endtrans %}</td>
            <td>{% trans %}Enable{% endtrans %}</td>
          </tr>
          <tr>
            <td>{% trans %}CSS/JS aggregate and gzip{% endtrans %}</td>
            <td>{% trans %}Disable{% endtrans %}</td>
            <td>{% trans %}Enable{% endtrans %}</a></td>
          </tr>
          <tr>
            <td>{% trans %}Browser and proxy caches{% endtrans %}</td>
            <td>{% trans %}Disable{% endtrans %}</td>
            <td><a href="{{ performanceSettingsUrl }}" target="_blank">{% trans %}Settings{% endtrans %}</a></td>
          </tr>
          <tr>
            <td>{% trans %}Error message to display{% endtrans %}</td>
            <td>{% trans %}All messages, with backtrace information{% endtrans %}</td>
            <td><a href="{{ errorMessageSettingsUrl }}" target="_blank">{% trans %}Settings{% endtrans %}</a></td>
          </tr>
          </table>
        </details>',
      '#context' => [
        'performanceSettingsUrl' => $this->urlGenerator->generateFromRoute('system.performance_settings', [], ['fragment' => 'edit-caching']),
        'errorMessageSettingsUrl' => $this->urlGenerator->generateFromRoute('system.logging_settings'),
      ],
    ];

    $form['remove_x_generator'] = [
      '#title' => $this->t('Remove X-Generator'),
      '#type' => 'checkbox',
      '#description' => $this->t('Remove HTTP header "X-Generator" and meta @meta to obfuscate that your website is running on Drupal.', ['@meta' => '<meta name="Generator" content="Drupal 10 (https://www.drupal.org)">']),
      '#default_value' => $config->get('remove_x_generator'),
    ];

    $form['hide_revision_field'] = [
      '#title' => $this->t('Hide revision field'),
      '#type' => 'checkbox',
      '#description' => $this->t('Hide revision field to all users except UID 1 to provide a clear UI'),
      '#default_value' => $config->get('hide_revision_field'),
    ];

    // Show form ID.
    $form['dev']['show_form_id'] = [
      '#title' => $this->t('Show form ID'),
      '#type' => 'checkbox',
      '#description' => $this->t('Show the form ID and form alter function (<a href="https://api.drupal.org/hook_form_FORM_ID_alter" target="_blank"><code>hook_form_FORM_ID_alter()</code></a>) template before a form to make form altering easier.'),
      '#default_value' => $this->state->get('mix.show_form_id'),
    ];

    // Environment indicator.
    $form['dev']['environment_indicator'] = [
      '#title' => $this->t('Environment Indicator'),
      '#type' => 'textfield',
      '#description' => $this->t('Add a simple text (e.g. Development/Dev/Stage/Test or any other text) on the top of this site to help you identify current environment.
        <br>Leave it blank in the Live environment or hide the indicator.'),
      '#default_value' => $this->state->get('mix.environment_indicator'),
    ];

    $form['content_sync'] = [
      '#title' => $this->t('Content synchronize') . '<sup>' . $this->t('(Beta)') . '</sup>',
      '#type' => 'details',
    ];

    $form['content_sync']['description_container'] = [
      '#type' => 'details',
      '#title' => $this->t('User guide'),
    ];

    $form['content_sync']['description_container']['description'] = [
      '#markup' => $this->t('By default, Drupal only synchronizes configurations between environments, not content.<br>
When we synchronize a block, the block content will not be synchronized, and you will get an error message "This block is broken or missing."<br>
With this "Content Synchronize", we can synchronize selected content (blocks, menu links, taxonomy terms, etc.) between environments.<br>
<strong>Usage</strong>
<ul>
  <li>Enable the "Show content sync ID" below.</li>
  <li>Go to content list page, copy the content sync ID to the "Content sync IDs" textarea below.</li>
      <ul>
        <li>Block - admin/structure/block/block-content</li>
        <li>Menu links - admin/structure/menu/manage/[menu-name]</li>
        <li>Taxonomy terms - admin/structure/taxonomy/manage/[taxonomy-name]/overview</li>
      </ul>
  <li>Export configurations by Config export page or <code>drush cex</code> from Dev site.</li>
  <li>Import configurations by Config import page or <code>drush cim</code> to Prod site.</li>
  <li>Click the "Generate content" button below.</li>
</ul>
Note: To avoid unexpected content updates, only non-existent content will be created by now.'),
    ];

    $form['content_sync']['show_content_sync_id'] = [
      '#title' => $this->t('Show content sync ID'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('show_content_sync_id'),
      '#description' => $this->t('Show the content sync ID in content (blocks, menu links, taxonomy terms, etc.) management pages'),
    ];

    $form['content_sync']['advanced'] = [
      '#title' => $this->t('Advanced'),
      '#type' => 'details',
    ];

    $form['content_sync']['advanced']['content_sync_ids'] = [
      '#title' => $this->t('Content sync IDs'),
      '#type' => 'textarea',
      '#description' => $this->t('One content sync ID per line.'),
      '#default_value' => implode(PHP_EOL, $config->get('content_sync_ids')),
      '#prefix' => '<div class="form-item__description">' . $this->t('Content Sync ID will be added/removed from the following textarea automatically when you selected/unselected an item to sync in the content list pages (e.g. block, menu link and term list pages). <br>
You can also edit it manually.') . '</div>',
    ];

    // @todo Disable this button if no content to generate.
    $form['content_sync']['generate_content'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate content'),
      '#submit' => [[$this, 'generateContentSubmit']],
    ];

    $form['error_pages'] = [
      '#type' => 'details',
      '#title' => $this->t('Error pages'),
      '#open' => TRUE,
    ];

    $errorPageDesc = $this->t('Use custom content replace the default 500 (internal server error) page.') . '<br>';
    $errorPageDesc .= '<a href="' . $this->urlGenerator->generateFromRoute('mix.site_500') . '" target="_blank">' . $this->t('View current error page.') . '</a>';
    $form['error_pages']['error_page'] = [
      '#title' => $this->t('Enable custom error page'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('error_page.mode'),
      '#description' => $errorPageDesc,
    ];

    $form['error_pages']['error_page_content'] = [
      '#title' => $this->t('Error page content'),
      '#type' => 'textarea',
      '#default_value' => $config->get('error_page.content'),
      '#description' => $this->t('Custom content or HTML code of the error page.'),
      '#rows' => 26,
      '#states' => [
        'visible' => [
          ':input[name="error_page"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $rebuildCache = FALSE;
    $rebuildContainer = FALSE;

    // Get configurations.
    $config = $this->config('mix.settings');

    // Get original dev_mode value, use to compare if changes later.
    $originDevMode = $config->get('dev_mode');

    // Normalize content_sync_ids.
    $content_sync_ids = array_map('trim', explode(PHP_EOL, $form_state->getValue('content_sync_ids')));
    MixContentSyncController::presave($content_sync_ids);

    // Save config.
    $this->config('mix.settings')
      ->set('dev_mode', $form_state->getValue('dev_mode'))
      ->set('hide_revision_field', $form_state->getValue('hide_revision_field'))
      ->set('remove_x_generator', $form_state->getValue('remove_x_generator'))
      ->set('error_page.mode', $form_state->getValue('error_page'))
      ->set('error_page.content', $form_state->getValue('error_page_content'))
      ->set('show_content_sync_id', $form_state->getValue('show_content_sync_id'))
      ->set('content_sync_ids', $content_sync_ids)
      ->save();

    // @todo Clear related caches when the setting of "Show content sync ID"
    // is changed.
    $oldShowFormId = $this->state->get('mix.show_form_id');
    $newShowFormId = $form_state->getValue('show_form_id');
    if ($oldShowFormId != $newShowFormId) {
      $this->state->set('mix.show_form_id', $form_state->getValue('show_form_id'));
      $rebuildCache = TRUE;
    }

    // Save state value and invalidate caches when this config changes.
    $oldEnvironmentIndicator = $this->state->get('mix.environment_indicator');
    $newEnvironmentIndicator = $form_state->getValue('environment_indicator');
    if ($oldEnvironmentIndicator != $newEnvironmentIndicator) {
      $this->state->set('mix.environment_indicator', $newEnvironmentIndicator);
      // Rebuild all caches if the new value or the old value is empty.
      if (empty($oldEnvironmentIndicator) || empty($newEnvironmentIndicator)) {
        $rebuildCache = TRUE;
      }
      // Only invalidate cache tags when change between non-empty values
      // for better performance.
      else {
        Cache::invalidateTags(['mix:environment-indicator']);
      }
    }

    // Only run this when dev_mode has changed.
    $devModeChanged = $form_state->getValue('dev_mode') != $originDevMode;
    if ($devModeChanged) {
      // Clear cache to rebulid service providers and configurations
      // based on dev_mode.
      $rebuildCache = TRUE;
      $rebuildContainer = TRUE;
    }

    if ($rebuildCache) {
      drupal_flush_all_caches();
    }

    if ($rebuildContainer) {
      $this->kernel->rebuildContainer();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Generate content based on imported content-config.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function generateContentSubmit(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mix.settings');
    $content_sync_ids = $config->get('content_sync_ids');
    $activeStorage = $config->getStorage();

    // Generate non-existent content.
    $reququeCounter = [];
    while ($content_sync_ids) {
      $configName = array_shift($content_sync_ids);

      // Parse entityType.
      $entityType = MixContentSyncSubscriber::parseEntityType($configName);
      // Ignore wrong sync ID or unsupported entity types.
      if (!$entityType || !in_array($entityType, array_keys(MixContentSyncSubscriber::$supportedEntityTypeMap))) {
        continue;
      }

      // Initialize counter.
      $reququeCounter[$configName] = $reququeCounter[$configName] ?? 1;
      // Ignore generate content which already tried multiple times.
      // Show an warning message.
      if ($reququeCounter[$configName] > 5) {
        $this->messenger()->addWarning($this->t('Failed to generate content: @config_name', ['@config_name' => $configName]));
        continue;
      }

      // Load entity.
      $uuid = substr($configName, strrpos($configName, '.') + 1);
      $existedEntity = \Drupal::service('entity.repository')->loadEntityByUuid($entityType, $uuid);
      $contentArray = $activeStorage->read($configName);
      // Only generate non-existent content.
      if (!$existedEntity && $contentArray) {
        try {
          $entity = $this->serializer->denormalize($contentArray, MixContentSyncSubscriber::$supportedEntityTypeMap[$entityType], 'yaml');
          $created = $entity->save();
          if ($created === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Content @config_name was generated successfully', ['@config_name' => $configName]));
          }
        }
        catch (\InvalidArgumentException $e) {
          // Handle exception situation that dependency content not exist.
          if (strpos($e->getMessage(), 'entity found with UUID')) {
            // Re-queue.
            array_push($content_sync_ids, $configName);
            // Requeue counter.
            $reququeCounter[$configName]++;
          }
        }
      }
    }

  }

}
