<?php

namespace Drupal\product_of_the_day\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Product form add.
 */
class ProductForm extends FormBase {

  /**
   * The @database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The @entity_tuype_manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_of_the_day_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Name'),
      '#required' => TRUE,
    ];

    $form['summary'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary'),
      '#required' => TRUE,
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Product Image'),
      '#required' => TRUE,
      '#upload_location' => 'public://product_images/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg'],
      ],
    ];

    $form['is_product_of_the_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set as Product of the Day'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('is_product_of_the_day')) {

      $count = $this->database->select('product_of_the_day_products', 'p')
        ->condition('is_product_of_the_day', 1)
        ->countQuery()
        ->execute()
        ->fetchField();

      if ($count >= 5) {
        $form_state->setErrorByName('is_product_of_the_day', $this->t('Only 5 products can be marked as "Product of the Day" at a time.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $image = $form_state->getValue('image');
    if (!empty($image)) {
      $file = File::load($image[0]);
      $file->setPermanent();
      $file->save();
      $image_path = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }

    $this->database->insert('product_of_the_day_products')
      ->fields([
        'name' => $form_state->getValue('name'),
        'summary' => $form_state->getValue('summary'),
        'image' => $image_path,
        'is_product_of_the_day' => $form_state->getValue('is_product_of_the_day'),
      ])
      ->execute();

    $this->messenger()->addMessage($this->t('Product saved successfully.'));
  }

}
