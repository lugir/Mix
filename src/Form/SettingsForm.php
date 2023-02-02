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

    // Check dev mode and give tips.
    $devMode = $config->get('dev_mode');
    $form['dev_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable development mode'),
      '#description' => $this->t('Quick switch between Dev/Prod modes to make module and theme develpment way more easier.'),
      '#default_value' => $devMode,
    ];

    // Help content for dev_mode configuration.
    $form['dev_mode_help'] = [
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
        'performanceSettingsUrl' => \Drupal::urlGenerator()->generateFromRoute('system.performance_settings', [], ['fragment' => 'edit-caching']),
        'errorMessageSettingsUrl' => \Drupal::urlGenerator()->generateFromRoute('system.logging_settings'),
      ],
    ];

    $form['remove_x_generator'] = [
      '#title' => $this->t('Remove X-Generator'),
      '#type' => 'checkbox',
      '#description' => $this->t('Remove HTTP header "X-Generator" and meta @meta to obfuscate that your website is running on Drupal', ['@meta' => '<meta name="Generator" content="Drupal 10 (https://www.drupal.org)">']),
      '#default_value' => $config->get('remove_x_generator'),
    ];

    $form['hide_revision_field'] = [
      '#title' => $this->t('Hide revision field'),
      '#type' => 'checkbox',
      '#description' => $this->t('Hide revision field to all users except UID 1 to provide a clear UI'),
      '#default_value' => $config->get('hide_revision_field'),
    ];

    // Environment indicator.
    $form['environment_indicator'] = [
      '#title' => $this->t('Environment Indicator'),
      '#type' => 'textfield',
      '#description' => $this->t('Add a simple text (e.g. Development/Dev/Stage/Test or any other text) on the top of this site to help you identify current environment.
        <br>Leave it blank in the Live environment or hide the indicator.'),
      '#default_value' => \Drupal::state()->get('mix.environment_indicator'),
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
      ->set('remove_x_generator', $form_state->getValue('remove_x_generator'))
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
      // Clear cache to rebulid service providers and configurations based on dev_mode.
      drupal_flush_all_caches();
    }

    parent::submitForm($form, $form_state);
  }

}
