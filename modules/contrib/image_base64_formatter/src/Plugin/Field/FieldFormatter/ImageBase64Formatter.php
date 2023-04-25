<?php

namespace Drupal\image_base64_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_base64_formatter'.
 *
 * @FieldFormatter(
 *   id = "image_base64",
 *   label = @Translation("Image Base64"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageBase64Formatter extends ImageFormatter {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The file_system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, ImageFactory $image_factory, FileSystem $fileSystem) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->imageFactory = $image_factory;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('image.factory'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'image_link' => '',
      'image_display' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $element['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    $display_types = [
      'content' => $this->t('Image Source'),
      'file' => $this->t('CSS background Source'),
    ];
    $element['image_display'] = [
      '#title' => $this->t('Show as'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_display'),
      '#empty_option' => $this->t('Base64 String'),
      '#options' => $display_types,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Render Image Base64');

    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);

    $image_style_setting = $this->getSetting('image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = $this->t('Image style: @style', ['@style' => $image_styles[$image_style_setting]]);
    }
    else {
      $summary[] = $this->t('Original image');
    }

    $link_types = [
      'content' => $this->t('Linked to content'),
      'file' => $this->t('Linked to file'),
    ];
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    $display_types = [
      'content' => $this->t('Show as Image Source'),
      'file' => $this->t('Show as CSS background Source'),
    ];
    // Display this setting only if show_image_as is set up.
    $image_display_setting = $this->getSetting('image_display');
    if (isset($display_types[$image_display_setting])) {
      $summary[] = $display_types[$image_display_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $images = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($images)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting === 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting === 'file') {
      $link_file = TRUE;
    }

    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style_setting = $this->getSetting('image_style');
    $image_style = $this->imageStyleStorage->load($image_style_setting);
    $image_display = $this->getSetting('image_display');

    /** @var \Drupal\file\FileInterface[] $images */
    foreach ($images as $delta => $image) {
      $image_uri = $image->getFileUri();
      $image_type = $image->getMimeType();

      // Create image derivatives if they not already exists.
      if ($image_style) {
        $derivative_uri = $image_style->buildUri($image_uri);
        if (!file_exists($derivative_uri)) {
          $image_style->createDerivative($image_uri, $derivative_uri);
        }
        $absolute_path = $this->fileSystem->realpath($derivative_uri);
      }
      else {
        $absolute_path = $this->fileSystem->realpath($image_uri);
      }

      // Encode image in base64 format.
      $image_file = file_get_contents($absolute_path);
      $base_64_image = base64_encode($image_file);
      $base_64_data = "data:$image_type;base64,$base_64_image";

      // Get the image's width and height, int|null.
      $image_media = $this->imageFactory->get($absolute_path);
      if ($image_media->isValid()) {
        $width = $image_media->getWidth();
        $height = $image_media->getHeight();
      }
      else {
        $width = $height = NULL;
      }

      switch ($image_display) {
        case 'content':
          $markup = '<img src="' . $base_64_data . '" ';
          if (isset($width, $height)) {
            $markup .= 'width="' . $width . '" height="' . $height . '" ';
          }
          $markup .= '/>';
          if (isset($link_file)) {
            $markup = '<a href="' . $absolute_path . '">' . $markup . '</a>';
          }
          elseif (isset($url)) {
            $markup = '<a href="' . $url . '">' . $markup . '</a>';
          }
          $markup = Markup::create(render($markup));
          break;

        case 'file':
          $markup = "url('$base_64_data')";
          break;

        default:
          $markup = $base_64_data;
      }
      $elements[$delta] = ['#markup' => $markup];

      // Add cacheability metadata from the image and image style.
      $cacheability = CacheableMetadata::createFromObject($image);
      if ($image_style) {
        $cacheability->addCacheableDependency(CacheableMetadata::createFromObject($image_style));
      }
      $cacheability->applyTo($elements[$delta]);
    }

    return $elements;
  }

}
