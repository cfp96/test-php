<?php

namespace Drupal\dynamic_banner\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Configure banner settings.
 */
class BannerSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dynamic_banner.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dynamic_banner_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('dynamic_banner.settings');

    $form['root_a_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Root A Banner Image'),
      '#upload_location' => 'public://banner_images/',
      '#default_value' => $config->get('root_a_image') ? [$config->get('root_a_image')] : [],
      '#description' => $this->t('Upload the banner image for Root A.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg webp'],
      ],
    ];

    $form['root_b_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Root B Banner Image'),
      '#upload_location' => 'public://banner_images/',
      '#default_value' => $config->get('root_b_image') ? [$config->get('root_b_image')] : [],
      '#description' => $this->t('Upload the banner image for Root B.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg webp'],
      ],
    ];

    $form['default_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Default Banner Image'),
      '#upload_location' => 'public://banner_images/',
      '#default_value' => $config->get('default_image') ? [$config->get('default_image')] : [],
      '#description' => $this->t('Upload the default banner image.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg webp'],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();
    $config = $this->config('dynamic_banner.settings');

    $image_fields = ['root_a_image', 'root_b_image', 'default_image'];

    foreach ($image_fields as $field) {
      if (!empty($values[$field])) {
        $file = File::load(reset($values[$field]));
        if ($file) {
          $file->setPermanent();
          $file->save();
          $config->set($field, $file->id());
        }
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }
}
