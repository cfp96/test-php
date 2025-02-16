<?php

namespace Drupal\product_of_the_day\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for handling the products.
 */
class ProductController extends ControllerBase {

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
   * The @form_builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager, FormBuilderInterface $formBuilder) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Function to render the products.
   */
  public function content() {
    $build = [];

    $build['form'] = $this->formBuilder->getForm('Drupal\product_of_the_day\Form\ProductForm');

    $products = $this->database->select('product_of_the_day_products', 'p')
      ->fields('p', ['id', 'name', 'summary', 'image', 'is_product_of_the_day'])
      ->execute()
      ->fetchAll();

    $rows = [];
    foreach ($products as $product) {
      // $image = File::load($this->getFileIdFromUri($product->image));
      // $image_url = $image ? $image->createFileUrl() : '';

      $rows[] = [
        $product->name,
        $product->summary,
        $product->image ? [
          'data' => [
            '#theme' => 'image',
            '#uri' => $product->image,
            '#alt' => $product->name,
            '#height' => 50,
            '#width' => 50,
          ],
        ] : $this->t('No image'),
        $product->is_product_of_the_day ? $this->t('Yes') : $this->t('No'),
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => Url::fromRoute('product_of_the_day.edit', ['product_id' => $product->id]),
          ],
        ],
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Delete'),
            '#url' => Url::fromRoute('product_of_the_day.delete', ['product_id' => $product->id]),
            '#attributes' => [
              'class' => ['button', 'button--danger'],
            ],
          ],
        ],
      ];
    }

    $build['products'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Summary'),
        $this->t('Image'),
        $this->t('Product of the Day'),
        $this->t('Edit'),
        $this->t('Delete'),
      ],
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * Get file Id drom Uri function.
   */
  protected function getFileIdFromUri($uri) {
    $file = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    if ($file) {
      return array_keys($file)[0];
    }
    return NULL;
  }

  /**
   * Delete Product.
   */
  public function deleteProduct($product_id) {
    $product = $this->database->select('product_of_the_day_products', 'p')
      ->fields('p', ['id', 'image'])
      ->condition('id', $product_id)
      ->execute()
      ->fetchAssoc();

    if ($product) {
      if (!empty($product['image'])) {
        $file = File::load($this->getFileIdFromUri($product['image']));
        if ($file) {
          $file->delete();
        }
      }

      $this->database->delete('product_of_the_day_products')
        ->condition('id', $product_id)
        ->execute();

      $this->messenger()->addMessage($this->t('Product deleted successfully.'));
    } else {
      $this->messenger()->addError($this->t('Product not found.'));
    }

    return $this->redirect('product_of_the_day.list');
  }

  /**
   * Detail.
   */
  public function detail($product_id) {
    $product = $this->database->select('product_of_the_day_products', 'p')
      ->fields('p', ['id', 'name', 'summary', 'image'])
      ->condition('id', $product_id)
      ->execute()
      ->fetchAssoc();

    if (!$product) {
      return [
        '#markup' => $this->t('Product not found.'),
      ];
    }

    return [
      '#theme' => 'product_of_the_day_detail',
      '#product' => $product,
    ];
  }

}
