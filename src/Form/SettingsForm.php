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
      '#type' => 'checkbox',
      '#title' => $this->t('Hide revision field'),
      '#description' => $this->t('Hide revision field to all users except Uid 1 to provide a clear UI'),
      '#default_value' => $this->config('mix.settings')->get('hide_revision_field'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*
    if ($form_state->getValue('example') != 'example') {
      $form_state->setErrorByName('example', $this->t('The value is not correct.'));
    }
    */
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mix.settings')
      ->set('hide_revision_field', $form_state->getValue('hide_revision_field'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
