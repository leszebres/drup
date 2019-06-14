<?php

namespace Drupal\drup\Media;

use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Class DrupFile
 *
 * @package Drupal\drup\Media
 */
class DrupFile {

    /**
     * @var File
     */
    public $fileEntity;

    /**
     * @var string
     */
    public $fileUri;

    /**
     * @var \Drupal\Core\Image\Image $image
     */
    public $image;

    /**
     * @var \Drupal\file\Entity\File
     */
    public $field_value;

    /**
     * DrupFileImage constructor.
     *
     * @param \Drupal\file\Entity\File $fileEntity
     */
    public function __construct(File $fileEntity) {
        $this->field_value = $fileEntity;
        $this->fileUri = $fileEntity->getFileUri();
        $this->image = \Drupal::service('image.factory')->get($this->fileUri);
    }

    /**
     * @param string $mediaUrl
     *
     * @return string|null
     */
    public static function getSVGContent($mediaUrl) {
        $output = null;

        if ($mediaContent = @file_get_contents($mediaUrl)) {
            $output = preg_replace('/<!--.*?-->/ms', '', $mediaContent);
        }

        return $output;
    }

    /**
     * @param string $mediaUrl
     *
     * @return \Drupal\Component\Render\MarkupInterface|null
     */
    public static function renderSVG($mediaUrl) {
        $output = null;

        if ($svgContent = self::getSVGContent($mediaUrl)) {
            $output = Markup::create($svgContent);
        }

        return $output;
    }

    /**
     * Défini le fichier en statut permanent
     *
     * @param $fid
     *
     * @return mixed
     */
    public static function setPermanent($fid) {
        if (is_array($fid)) {
            $fid = current($fid);
        }

        $file = File::load($fid);

        if ($file instanceof File) {
            $file->setPermanent();
            $file->save();

            return $file;
        }

        return false;
    }

    /**
     * Retourne l'url du fichier
     *
     * @param      $fid
     * @param bool $absolute
     *
     * @return null|string
     */
    public static function getUrl($fid, $absolute = true) {
        $url = null;

        if (is_array($fid)) {
            $fid = current($fid);
        }

        $file = File::load($fid);

        if ($file instanceof File) {
            $url = $file->getFileUri();

            if ($absolute) {
                $url = file_create_url($url);
            }
        }

        return $url;
    }

    /**
     * Retourne des informations sur le logo du site
     *
     * @param string $type='svg' Type de fichier (svg ou png)
     * @param array  $options    Surcharge des éléments retournés
     *
     * @return object
     */
    public static function getLogo($type = 'svg', $options = []) {
        $options = array_merge([
            'url' => null,
            'width' => null,
            'height' => null,
            'mimetype' => $type === 'png' ? 'image/png' : 'image/svg+xml'
        ], $options);

        if (empty($options['url'])) {
            $themeHandler = \Drupal::service('theme_handler');
            $defaultTheme = $themeHandler->getDefault();
            $options['url'] = \Drupal::request()->getUriForPath('/' . $themeHandler->getTheme($defaultTheme)->getPath() . '/images/logo.' . $type);
        }

        if ($type === 'png' && !empty($options['url']) && empty($options['width']) && empty($options['height']) && ($size = @getimagesize($options['url']))) {
            $options['width'] = $size[0];
            $options['height'] = $size[1];
            $options['mimetype'] = $size['mime'];
        }

        return (object) $options;
    }

    /**
     * @param $style
     * @param array $attributes
     *
     * @return bool
     */
    public function renderMedia($style, $attributes = []) {
        if (!$this->isValid()) {
            return false;
        }

        $rendererOptions = [
            '#uri' => $this->fileUri,
            '#attributes' => [],
            '#width' => $this->image->getWidth(),
            '#height' => $this->image->getHeight()
        ];

        // Render as image style
        if (!empty($style)) {
            if ($imageStyle = $this->getImageStyleEntity($style, true)) {
                if ($imageStyle instanceof ResponsiveImageStyle) {
                    $rendererOptions += [
                        '#theme' => 'responsive_image',
                        '#responsive_image_style_id' => $style
                    ];
                }
                else {
                    $rendererOptions += [
                        '#theme' => 'image_style',
                        '#style_name' => $style
                    ];
                }
            }
            else {
                \Drupal::messenger()->addMessage('Le style d\'image ' . $style . ' n\'existe pas.', 'error');
                return false;
            }
        }
        // Render original image
        else {
            $rendererOptions += [
                '#theme' => 'image'
            ];
        }

        if (!empty($attributes)) {
            $rendererOptions['#attributes'] = array_merge_recursive($rendererOptions['#attributes'], $attributes);
        }

        $renderer = \Drupal::service('renderer');
        $renderer->addCacheableDependency($rendererOptions, $this->field_value);

        return $renderer->render($rendererOptions);
    }

    /**
     * @param $style
     *
     * @return \Drupal\Core\GeneratedUrl|string|null
     */
    public function getMediaUrl($style) {
        $url = null;

        if ($style !== null && $this->isValid() && ($imageStyle = $this->getImageStyleEntity($style))) {
            $url = $imageStyle->buildUrl($this->fileUri);
        }
        else {
            $url = file_create_url($this->fileUri);
        }

        return $url;
    }

    /**
     * @return bool
     */
    public function isValid() {
        return $this->image->isValid();
    }

    /**
     * @param $style
     * @param bool $allowResponsiveImageStyle
     *
     * @return \Drupal\Core\Entity\EntityInterface|\Drupal\image\Entity\ImageStyle|\Drupal\responsive_image\Entity\ResponsiveImageStyle|null
     */
    protected function getImageStyleEntity($style, $allowResponsiveImageStyle = false) {
        $imageStyle = ImageStyle::load($style);

        if ($imageStyle instanceof ImageStyle) {
            return $imageStyle;
        }

        if (($allowResponsiveImageStyle === true) && ($responsiveImageStyle = ResponsiveImageStyle::load($style)) && $responsiveImageStyle instanceof ResponsiveImageStyle) {
            return $responsiveImageStyle;
        }

        return null;
    }
}
