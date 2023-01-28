<?php

namespace Drupal\mix\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Mix settings for this site.
 */
class SettingsForm extends ConfigFormBase {

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

    $form['hide_revision_field'] = [
      '#title' => $this->t('Hide revision field'),
      '#type' => 'checkbox',
      '#description' => $this->t('Hide revision field to all users except UID 1 to provide a clear UI'),
      '#default_value' => $config->get('hide_revision_field'),
    ];

    $form['dev'] = [
      '#title' => $this->t('Development'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // Environment indicator.
    $form['dev']['environment_indicator'] = [
      '#title' => $this->t('Environment Indicator'),
      '#type' => 'textfield',
      '#description' => $this->t('Add a simple text (e.g. Development/Dev/Stage/Test or any other text) on the top of this site to help you identify current environment.
        <br>Leave it blank in the Live environment or hide the indicator.'),
      '#default_value' => \Drupal::state()->get('mix.environment_indicator'),
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
            <td>{% trans %}CSS/JS aggregate and gzip{% endtrans %}</td>
            <td>{% trans %}Disable{% endtrans %}</td>
            <td>{% trans %}Enable{% endtrans %}</td>
          </tr>
          <tr>
            <td>{% trans %}Browser and proxy caches{% endtrans %}</td>
            <td>{% trans %}Disable{% endtrans %}</td>
            <td>{% trans %}1 minute{% endtrans %}</td>
          </tr>
          <tr>
            <td>{% trans %}Backend caches (render cache, page cache, dynamic page cache, etc.){% endtrans %}</td>
            <td>{% trans %}Disable{% endtrans %}</td>
            <td>{% trans %}Enable{% endtrans %}</td>
          </tr>
          <tr>
            <td>{% trans %}Error message to display{% endtrans %}</td>
            <td>{% trans %}All messages, with backtrace information{% endtrans %}</td>
            <td>{% trans %}None{% endtrans %}</td>
          </tr>
          </table>
        </details>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get configurations.
    $config = $this->config('mix.settings');

    // Get original dev_mode value, use to compare if changes later.
    $originDevMode = $config->get('dev_mode');

    // Save config.
    $this->config('mix.settings')
      ->set('dev_mode', $form_state->getValue('dev_mode'))
      ->set('hide_revision_field', $form_state->getValue('hide_revision_field'))
      ->save();

    // Save state value and invalidate caches when this config changes.
    $newEnvironmentIndicator = $form_state->getValue('environment_indicator');
    if (\Drupal::state()->get('mix.environment_indicator') != $newEnvironmentIndicator) {
      \Drupal::state()->set('mix.environment_indicator', $newEnvironmentIndicator);
      Cache::invalidateTags(['mix:environment-indicator']);
    }

    // Only run this when dev_mode has changed.
    $devModeChanged = $form_state->getValue('dev_mode') != $originDevMode;
    if ($devModeChanged) {
      $form_state->getValue('dev_mode') ? $this->enableDevMode() : $this->disableDevMode();
      // Clear cache to rebuild service providers based on dev_mode.
      drupal_flush_all_caches();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Switch site configurations to development mode.
   *
   * Disable browser and proxy cache.
   * Disable CSS/JS aggregate and gzip.
   * Show all error messages.
   * Disable render cache, page cache and dynamic page cache.
   * Enable Twig Debug.
   */
  public function enableDevMode() {

    $configFactory = \Drupal::configFactory();

    // Disable browser and proxy cache.
    $configFactory->getEditable('system.performance')
      ->set('cache', ['page' => ['max_age' => 0]])
      ->save();

    // Disable CSS aggregate and gzip.
    $configFactory->getEditable('system.performance')->set('css', [
      'preprocess' => 0,
      'gzip' => 0,
    ])->save();

    // Disable CSS aggregate and gzip.
    $configFactory->getEditable('system.performance')->set('js', [
      'preprocess' => 0,
      'gzip' => 0,
    ])->save();

    // Show all error messages, with backtrace information.
    $configFactory->getEditable('system.logging')
      ->set('error_level', 'verbose')
      ->save();
  }

  /**
   * Switch site configurations to prod mode.
   */
  public function disableDevMode() {

    $configFactory = \Drupal::configFactory();

    // Disable browser and proxy cache.
    $configFactory->getEditable('system.performance')
      ->set('cache', ['page' => ['max_age' => 60]])
      ->save();

    // Disable CSS aggregate and gzip.
    $configFactory->getEditable('system.performance')->set('css', [
      'preprocess' => 1,
      'gzip' => 1,
    ])->save();

    // Disable CSS aggregate and gzip.
    $configFactory->getEditable('system.performance')->set('js', [
      'preprocess' => 1,
      'gzip' => 1,
    ])->save();

    // Hide error message.
    $configFactory->getEditable('system.logging')
      ->set('error_level', 'hide')
      ->save();
  }

}
