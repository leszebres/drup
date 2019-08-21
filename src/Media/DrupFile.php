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
    protected $entity;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var \Drupal\Core\Image\Image $image
     */
    protected $image;

    /**
     * DrupFileImage constructor.
     *
     * @param \Drupal\file\Entity\File $fileEntity
     */
    public function __construct(File $fileEntity) {
        $this->entity = $fileEntity;
        $this->uri = $fileEntity->getFileUri();
        $this->image = \Drupal::service('image.factory')->get($this->uri);
    }

    /**
     * Instancie selon un id de File
     *
     * @param string $fid
     *
     * @return \Drupal\drup\Media\DrupFile
     */
    public static function createFromFid(string $fid) {
        if (($file = File::load($fid)) && $file instanceof File) {
            return new static($file);
        }

        return null;
    }


    /**
     * Url absolue d'une image avec style d'image
     *
     * @param string|null $style
     *
     * @return string|null
     */
    public function getMediaUrl($style = null) {
        $url = null;

        if ($style !== null && $this->isValid() && ($imageStyle = self::getImageStyleEntity($style))) {
            $url = $imageStyle->buildUrl($this->uri);
        } else {
            $url = file_create_url($this->uri);
        }

        return $url;
    }

    /**
     * Rendu HTML d'un fichier de type image, avec un syle d'image
     *
     * @param string|null $style
     * @param array  $attributes
     *
     * @return bool|array
     */
    public function renderImage($style = null, array $attributes = []) {
        if (!$this->isValid()) {
            return false;
        }

        $rendererOptions = [
            '#uri' => $this->uri,
            '#attributes' => [],
            '#width' => $this->image->getWidth(),
            '#height' => $this->image->getHeight(),
        ];

        // Render as image style
        if (!empty($style)) {
            if ($imageStyle = self::getImageStyleEntity($style, true)) {
                if ($imageStyle instanceof ResponsiveImageStyle) {
                    $rendererOptions += [
                        '#theme' => 'responsive_image',
                        '#responsive_image_style_id' => $style
                    ];

                } else {
                    $rendererOptions += [
                        '#theme' => 'image_style',
                        '#style_name' => $style
                    ];
                }

            } else {
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
        $renderer->addCacheableDependency($rendererOptions, $this->entity);

        return $renderer->render($rendererOptions);
    }


    /**
     * Rendu HTML d'un fichier de type image, avec un syle d'image, depuis une uri
     *
     * @param string $uri
     * @param string|null $style
     * @param array $attributes
     *
     * @return bool
     */
    public static function renderImageFromUri(string $uri, $style = null, $attributes = []) {
        $rendererOptions = [
            '#uri' => $uri,
            '#attributes' => []
        ];

        // Render as image style
        if (!empty($style)) {
            if ($imageStyle = self::getImageStyleEntity($style, true)) {
                if ($imageStyle instanceof ResponsiveImageStyle) {
                    $rendererOptions += [
                        '#theme' => 'responsive_image',
                        '#responsive_image_style_id' => $style
                    ];

                } else {
                    $rendererOptions += [
                        '#theme' => 'image_style',
                        '#style_name' => $style
                    ];
                }

            } else {
                \Drupal::messenger()->addMessage('Le style d\'image ' . $style . ' n\'existe pas.', 'error');
                return false;
            }

        } else {
            $rendererOptions += [
                '#theme' => 'image'
            ];
        }

        if (!empty($attributes)) {
            $rendererOptions['#attributes'] = array_merge_recursive($rendererOptions['#attributes'], $attributes);
        }

        return \Drupal::service('renderer')->render($rendererOptions);
    }

    /**
     * @deprecated
     *
     * @param string $uri
     * @param null $style
     * @param array $attributes
     *
     * @return bool
     */
    public static function renderImageByUri(string $uri, $style = null, $attributes = []) {
        return self::renderImageFromUri($uri, $style, $attributes);
    }

    /**
     * Fichier existant
     *
     * @return bool
     */
    public function isValid(): bool {
        return $this->image->isValid();
    }

    /**
     * Entité ImageStyle ou ResponsiveImageStyle
     *
     * @param $style
     * @param bool $allowResponsiveImageStyle
     *
     * @return \Drupal\image\Entity\ImageStyle|\Drupal\responsive_image\Entity\ResponsiveImageStyle|null
     */
    public static function getImageStyleEntity(string $style, bool $allowResponsiveImageStyle = false) {
        $imageStyle = ImageStyle::load($style);

        if ($imageStyle instanceof ImageStyle) {
            return $imageStyle;
        }

        if ($allowResponsiveImageStyle && ($responsiveImageStyle = ResponsiveImageStyle::load($style)) && $responsiveImageStyle instanceof ResponsiveImageStyle) {
            return $responsiveImageStyle;
        }

        return null;
    }

    /**
     * Contenu d'un fichier SVG
     *
     * @param string $mediaUrl
     *
     * @return string|null
     */
    public static function getSVGContent(string $url) {
        $output = null;

        if ($mediaContent = @file_get_contents($url)) {
            $output = preg_replace('/<!--.*?-->/ms', '', $mediaContent);
        }

        return $output;
    }

    /**
     * Rendu HTML d'un fichier SVG
     *
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
        if (\is_array($fid)) {
            $fid = current($fid);
        }

        if (($file = File::load($fid)) && $file instanceof File) {
            $file->setPermanent();
            $file->save();

            return $file;
        }

        return false;
    }

    /**
     * Retourne l'url d'un fichier
     *
     * @param      $fid
     * @param bool $absolute
     *
     * @return null|string
     */
    public static function getUrl($fid, $absolute = true) {
        $url = null;

        if (\is_array($fid)) {
            $fid = current($fid);
        }

        if (($file = File::load($fid)) && $file instanceof File) {
            $url = $file->getFileUri();

            if ($absolute) {
                $url = file_create_url($url);
            }
        }

        return $url;
    }

    /**
     * Chemin d'un fichier d'un thème
     *
     * @param string $relativePath
     * @param string $theme
     *
     * @return string
     */
    public static function getThemeFileUri(string $relativePath, string $theme = null): string {
        $themeHandler = \Drupal::service('theme_handler');
        $theme = $theme ?? $themeHandler->getDefault();

        return \Drupal::request()->getUriForPath('/' . $themeHandler->getTheme($theme)->getPath() . $relativePath);
    }

    /**
     * Retourne des informations sur le logo du site
     *
     * @param string $type='svg' Type de fichier (svg ou png)
     * @param array $options Surcharge des éléments retournés
     *
     * @return object
     */
    public static function getLogo(string $type = 'svg', array $options = []): object {
        $options = array_merge([
            'url' => null,
            'width' => null,
            'height' => null,
            'mimetype' => $type === 'png' ? 'image/png' : 'image/svg+xml',
        ], $options);

        if (empty($options['url'])) {
            $options['url'] = self::getThemeFileUri('/images/logo.' . $type);
        }

        if ($type === 'png' && !empty($options['url']) && empty($options['width']) && empty($options['height']) && ($size = @getimagesize($options['url']))) {
            $options['width'] = $size[0];
            $options['height'] = $size[1];
            $options['mimetype'] = $size['mime'];
        }

        return (object) $options;
    }

}
