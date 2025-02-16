<?php

namespace Drupal\product_of_the_day\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;

/**
 * Provides a "Product of the Day" block.
 *
 * @Block(
 *   id = "product_of_the_day_block",
 *   admin_label = @Translation("Product of the Day"),
 * )
 */
class PotdBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query = Database::getConnection()->select('product_of_the_day_products', 'p')
      ->fields('p', ['id', 'name', 'summary', 'image'])
      ->condition('is_product_of_the_day', 1)
      ->orderRandom()
      ->range(0, 1);
    $product = $query->execute()->fetchAssoc();

    if ($product) {
      return [
        '#theme' => 'product_of_the_day_block',
        '#product' => $product,
        '#cache' => [
          'tags' => ['product_of_the_day_block'],
          'contexts' => ['url.path'],
          'max-age' => 0,
        ],
      ];
    }

    return [
      '#markup' => $this->t('No product of the day available.'),
      '#cache' => [
        'tags' => ['product_of_the_day_block'],
        'contexts' => ['url.path'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.query_args'];
  }
}
