<?php

namespace Drupal\drup\Media;

use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;

/**
 * Class DrupMediaVideoExternal
 *
 * @package Drupal\drup\Media
 */
class DrupMediaVideoExternal extends DrupMedia {

    /**
     * @var string
     */
    public static $fileField = 'field_media_oembed_video';

    /**
     * DrupMediaVideoExternal constructor.
     *
     * @param int|int[]|Media|Media[] $medias
     * @param string $fileField
     */
    public function __construct($medias, string $fileField = null) {
        $this->type = 'video_external';

        if ($fileField === null) {
            $fileField = self::$fileField;
        }

        parent::__construct($medias, $fileField);
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function renderMedias(array $attributes = []): array {
        $medias = [];

        if (!empty($this->mediasData)) {
            foreach ($this->mediasData as $index => $media) {
                $medias[] = $this->renderMedia($index, $attributes);
            }
        }

        return $medias;
    }

    /**
     * @return array
     */
    public function getMediasUrl(): array {
        $urls = [];

        foreach ($this->mediasData as $index => $media) {
            $urls[] = $this->getMediaUrl($index);
        }

        return $urls;
    }

    /**
     * @param null $thumbnailStyle
     *
     * @return array
     */
    public function getMediasData(string $thumbnailStyle = null): array {
        $data = [];

        foreach ($this->mediasData as $index => $media) {
            $data[] = $this->getMediaData($index, $thumbnailStyle);
        }

        return $data;
    }


    /**
     * Render iframe
     *
     * @param int $index
     * @param array $attributes
     *
     * @return bool
     */
    protected function renderMedia($index = 0, array $attributes = []) {
        if (isset($this->mediasData[$index])) {
            $attributes = \array_merge([
                'src' => $this->getMediaUrl($index),
                'width' => null,
                'height' => null,
                'allowfullscreen' => 'allowfullscreen'
            ], $attributes);

            $build = [
                '#type' => 'html_tag',
                '#tag' => 'iframe',
                '#value' => '',
                '#attributes' => $attributes
            ];

            return \Drupal::service('renderer')->renderPlain($build);
        }

        return false;
    }

    /**
     * Return the src attribute of the OEmbed iframe
     *
     * @param int $index
     *
     * @return bool|\Drupal\Core\GeneratedUrl|string|null
     */
    protected function getMediaUrl($index = 0) {
        if (isset($this->mediasData[$index])) {
            /** @var \Drupal\media\Entity\Media $entity */
            $entity = $this->mediasData[$index]->entity;

            $iframe = $entity->get('field_video_external_iframe')->value;
            return self::extractIframeUrl($iframe);
        }

        return false;
    }

    /**
     * @param int $index
     * @param null $thumbnailStyle
     *
     * @return array
     */
    protected function getMediaData($index = 0, string $thumbnailStyle = null): array {
        $data = [];

        if (isset($this->mediasData[$index])) {
            /** @var \Drupal\media\Entity\Media $entity */
            $entity = $this->mediasData[$index]->entity;
            $iframe = $entity->get('field_video_external_iframe')->value;
            $thumbnailUri = $entity->get('field_thumbnail_uri')->value;

            $data = [
                'url' => $this->mediasData[$index]->field_value,
                'name' => $entity->getName(),
                'legend' => $this->getLegend($index),
                'iframe' => $iframe,
                'iframe_url' => self::extractIframeUrl($iframe),
                'thumbnail' => DrupFile::renderImageFromUri($thumbnailUri, $thumbnailStyle, ['alt' => t('Video')]),
                'thumbnail_url' => file_create_url($thumbnailUri)
            ];
        }

        return $data;
    }

    /**
     * Extrait la source d'une iframe
     *
     * @param string|Markup $string
     *
     * @return string|null
     */
    public static function extractIframeUrl($string) {
        preg_match('/src="([^"]+)"/', (string) $string, $match);

        return $match[1] ?? null;
    }

    /**
     * @param string $url
     *
     * @return mixed|null
     */
    public static function extractYoutubeVideoId(string $url) {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1] ?? null;
    }
}
