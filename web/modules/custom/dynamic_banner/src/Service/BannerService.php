<?php

namespace Drupal\dynamic_banner\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\Entity\File;

/**
 * The banner service.
 */
class BannerService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * The loger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * FileService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The file storage backend.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The loger service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RouteMatchInterface $routeMatch,
    ConfigFactoryInterface $configFactory,
    LoggerChannelFactoryInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeMatch = $routeMatch;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Function to get the banner image.
   */
  public function getBannerImage() {
    $node = $this->routeMatch->getParameter('node');
    if (!$node) {
      $this->logger->get('dynamic_banner')->notice('No node detected for this page.');
      return $this->getDefaultImage();
    }

    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $menu_links = $menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $node->id()]);

    foreach ($menu_links as $plugin_id => $menu_link) {
      // Extract UUID from the plugin ID
      $uuid = str_replace('menu_link_content:', '', $plugin_id);

      // Load the menu link content entity
      $menu_link_content = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $uuid]);
      $menu_link_content = reset($menu_link_content);

      if ($menu_link_content) {
        // Get the parent menu link
        $title = $menu_link_content->getTitle();
        $parent = $menu_link_content->get('parent')->getString();

        $this->logger->get('dynamic_banner')->notice("Menu UUID: $uuid | Parent: $parent");

        if (empty($parent)) {
          $this->logger->get('dynamic_banner')->notice("No parent found. Checking if this is Root A or Root B.");
          if ($title === "Root A" || $title === "Root B") {
            return $this->getImageByRoot($title);
          }
        }

        // Check if the parent is another menu link
        if (strpos($parent, 'menu_link_content:') !== false) {
          $parent_uuid = str_replace('menu_link_content:', '', $parent);
          $parent_link = $this->entityTypeManager->getStorage('menu_link_content')->loadByProperties(['uuid' => $parent_uuid]);
          $parent_link = reset($parent_link);

          if ($parent_link) {
            $title = $parent_link->getTitle();
            $this->logger->get('dynamic_banner')->notice("Parent menu link title: $title");

            return $this->getImageByRoot($title);
          }
        }
      }
    }
    return $this->getDefaultImage();
  }

  /**
   * Function to get the images url from the config form.
   */
  private function getImageByRoot($title) {
    $config = $this->configFactory->get('dynamic_banner.settings');
    $file_url_generator = \Drupal::service('file_url_generator');

    // Load file IDs from configuration.
    $rootAFileId = $config->get('root_a_image');
    $rootBFileId = $config->get('root_b_image');

    // Default fallback images.
    $defaultA = '/sites/default/files/hamster.jpg';
    $defaultB = '/sites/default/files/hamster.jpg';

    // Resolve file URLs from file IDs.
    $rootAImage = $this->getFileUrl($rootAFileId, $defaultA);
    $rootBImage = $this->getFileUrl($rootBFileId, $defaultB);

    return ($title === 'Root A') ? $rootAImage : $rootBImage;
  }

  /**
   * Helper function to load file URL from file ID.
   */
  private function getFileUrl($file_id, $default) {
    if ($file_id) {
      $file =
      $file = File::load($file_id);
      if ($file) {
        return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
      }
    }
    return $default;
  }

  /**
   * Helper funtion to get the defualt image.
   */
  private function getDefaultImage() {
    $config = $this->configFactory->get('dynamic_banner.settings');

    // Load the file ID from the config.
    $defaultFileId = $config->get('default_image');

    // Default fallback image.
    $defaultImage = '/sites/default/files/default-banner.jpg';

    // Get the absolute URL of the stored file.
    return $this->getFileUrl($defaultFileId, $defaultImage);
  }

}
