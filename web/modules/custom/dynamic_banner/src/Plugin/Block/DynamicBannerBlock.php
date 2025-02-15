<?php

namespace Drupal\dynamic_banner\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\dynamic_banner\Service\BannerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a Dynamic Banner Block.
 *
 * @Block(
 *   id = "dynamic_banner_block",
 *   admin_label = @Translation("Dynamic Banner Block")
 * )
 */
class DynamicBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The banner service.
   *
   * @var \Drupal\dynamic_banner\Service\BannerService
   */
  protected $bannerService;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, BannerService $bannerService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->bannerService = $bannerService;
  }

  /**
   * Function to create.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('dynamic_banner.service')
    );
  }

  /**
   * Function to build.
   */
  public function build() {
    $banner_image = $this->bannerService->getBannerImage();
    return [
      '#type' => 'markup',
      '#markup' => '<div class="banner-wrapper"><div class="banner"></div></div>',
      '#attached' => [
        'library' => [
          'dynamic_banner/banner_styles',
        ],
        'html_head' => [[
          [
            '#tag' => 'style',
            '#value' => '.banner { background-image: url("' . $banner_image . '") !important; }',
          ],
          'dynamic_banner_background',
        ]],
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

}
