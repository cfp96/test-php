<?php

namespace Drupal\product_of_the_day\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for the products.
 */
class ProductEditForm extends FormBase {

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
  protected $productId;

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
    return 'product_of_the_day_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product_id = NULL) {
    $this->productId = $product_id;

    $product = $this->database->select('product_of_the_day_products', 'p')
      ->fields('p', ['name', 'summary', 'image', 'is_product_of_the_day'])
      ->condition('id', $this->productId)
      ->execute()
      ->fetchAssoc();

    if (!$product) {
      $this->messenger()->addError($this->t('Product not found.'));
      return $form;
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Name'),
      '#required' => TRUE,
      '#default_value' => $product['name'],
    ];

    $form['summary'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary'),
      '#required' => TRUE,
      '#default_value' => $product['summary'],
    ];

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Product Image'),
      '#required' => TRUE,
      '#upload_location' => 'public://product_images/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png gif jpg jpeg'],
      ],
      '#default_value' => $product['image'],
    ];

    $form['is_product_of_the_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set as Product of the Day'),
      '#default_value' => $product['is_product_of_the_day'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
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
        ->condition('id', $this->productId, '<>')
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

    $this->database->update('product_of_the_day_products')
      ->fields([
        'name' => $form_state->getValue('name'),
        'summary' => $form_state->getValue('summary'),
        'image' => $image_path,
        'is_product_of_the_day' => $form_state->getValue('is_product_of_the_day'),
      ])
      ->condition('id', $this->productId)
      ->execute();

    $this->messenger()->addMessage($this->t('Product updated successfully.'));
  }

  /**
   * Helper function to get the file ID from the URI.
   */
  protected function getFileIdFromUri($uri) {
    $file = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    if ($file) {
      return array_keys($file)[0];
    }
    return NULL;
  }

}
