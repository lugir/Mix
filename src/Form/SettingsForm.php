<?php

namespace Drupal\mix\Form;

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

    $form['hide_revision_field'] = [
      '#title' => $this->t('Hide revision field'),
      '#type' => 'checkbox',
      '#description' => $this->t('Hide revision field to all users except Uid 1 to provide a clear UI'),
      '#default_value' => $this->config('mix.settings')->get('hide_revision_field'),
    ];

    $form['dev'] = [
      '#title' => $this->t('Development'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['dev']['environment_indicator'] = [
      '#title' => $this->t('Environment Indicator'),
      '#type' => 'textfield',
      '#description' => $this->t('Add a simple text (e.g. Development/Dev/Stage/Test or anyother text) on the top of this site to help you identify current environment.
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

    // Save configurations.
    $this->config('mix.settings')
      ->set('hide_revision_field', $form_state->getValue('hide_revision_field'))
      ->save();

    // Save states.
    \Drupal::state()->set('mix.environment_indicator', $form_state->getValue('environment_indicator'));

    parent::submitForm($form, $form_state);
  }

}
