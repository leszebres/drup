<?php

namespace Drupal\drup\Media;

use Drupal\media\Entity\Media;

/**
 * Class DrupMediaImage
 *
 * @package Drupal\drup\Media
 */
class DrupMediaImage extends DrupMedia {

    /**
     * todo refactor double __construct?
     *
     * DrupMediaImage constructor.
     *
     * @param int|int[]|Media|Media[] $medias
     * @param int|int[]|Media|Media[]|bool $fallbackId Défini des médias à utiliser si $medias n'est pas utilisable.
     * Si true, on utilise le média par défaut défini via DrupSettings.
     * Sinon on utilise les données spécifiquement déclarées.
     * @param null $fileField
     */
    public function __construct($medias, $fallbackId = false, string $fileField = null) {
        $this->type = 'image';

        parent::__construct($medias, $fileField);

        // Fallback on other medias
        if ($fallbackId !== false && empty($this->mediasData)) {
            if ($fallbackId === true) {
                $fallbackId = self::getFallbackId();
            }
            $medias = $fallbackId;

            parent::__construct($medias, $fileField);
        }
    }

    /**
     * @param $style
     * @param array $attributes
     *
     * @return array
     */
    public function renderMedias(string $style, array $attributes = []): array {
        $medias = [];

        if (!empty($this->mediasData)) {
            foreach ($this->mediasData as $index => $media) {
                $medias[] = $this->renderMedia($style, $index, $attributes);
            }
        }

        return $medias;
    }

    /**
     * @param $style
     *
     * @return array
     */
    public function getMediasUrl(string $style): array {
        $urls = [];

        if (!empty($this->mediasData)) {
            foreach ($this->mediasData as $index => $media) {
                $urls[] = $this->getMediaUrl($style, $index);
            }
        }

        return $urls;
    }

    /**
     * @param $style
     *
     * @return array
     */
    public function getMediasData($style) {
        $data = [];

        if (!empty($this->mediasData)) {
            foreach ($this->mediasData as $index => $media) {
                $data[] = $this->getMediaData($style, $index);
            }
        }

        return $data;
    }


    /**
     * Render an image with simple image style, responsive image style or as original
     *
     * @param $style
     * @param int $index
     * @param array $attributes
     *
     * @return bool
     */
    protected function renderMedia(string $style, int $index = 0, array $attributes = []) {
        if (isset($this->mediasData[$index])) {
            $drupFileImage = new DrupFile($this->mediasData[$index]->field_value);

            $attributes = array_merge([
                'alt' => $this->mediasData[$index]->field->get('alt')->getString()
            ], $attributes);

            return $drupFileImage->renderImage($style, $attributes);
        }

        return false;
    }

    /**
     * @param $style
     * @param int $index
     *
     * @return bool|\Drupal\Core\GeneratedUrl|string|null
     */
    protected function getMediaUrl($style, $index = 0) {
        if (isset($this->mediasData[$index])) {
            $drupFileImage = new DrupFile($this->mediasData[$index]->field_value);

            return $drupFileImage->getMediaUrl($style);
        }

        return false;
    }

    /**
     * @param $style
     * @param int $index
     *
     * @return array
     */
    protected function getMediaData(string $style, int $index = 0) {
        $data = [];

        if (isset($this->mediasData[$index])) {
            $drupFileImage = new DrupFile($this->mediasData[$index]->field_value);

            if ($drupFileImage->isValid()) {
                $data = [
                    'url' => $drupFileImage->getMediaUrl($style),
                    'alt' => $this->mediasData[$index]->field->get('alt')->getString(),
                    'title' => $this->mediasData[$index]->field->get('title')->getString(),
                    'name' => $this->mediasData[$index]->entity->getName(),
                    'legend' => $this->getLegend($index)
                ];
            }
        }

        return $data;
    }

    /**
     * Récupère le mid de l'image par défaut dans les listes
     *
     * @return int|null
     */
    public static function getFallbackId() {
        /** @var \Drupal\drup_settings\DrupSettings $drupSettings */
        $drupSettings = \Drupal::service('drup_settings');

        if ($mediaId = $drupSettings->getValue('default_list_image')) {
            return (int) $mediaId;
        }

        return null;
    }

}
