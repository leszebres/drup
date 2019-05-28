<?php

namespace Drupal\drup\Media;

use Drupal\media\Entity\Media;

/**
 * Class DrupMediaDocument
 *
 * @package Drupal\drup\Media
 */
class DrupMediaDocument extends DrupMedia {

    /**
     * DrupMediaFile constructor.
     *
     * @param int|int[]|Media|Media[] $medias
     * @param null $fileField
     */
    public function __construct($medias, $fileField = null) {
        $this->type = 'file';
        parent::__construct($medias, $fileField);
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function renderMedias($attributes = []) {
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
    public function getMediasUrl() {
        $urls = [];

        foreach ($this->mediasData as $index => $media) {
            $urls[] = $this->getMediaUrl($index);
        }

        return $urls;
    }

    /**
     * @return array
     */
    public function getMediasData() {
        $medias = [];

        foreach ($this->mediasData as $index => $media) {
            $medias[] = $this->getMediaData($index);
        }

        return $medias;
    }

    /**
     * @param int $index
     * @param array $attributes
     *
     * @return bool
     */
    protected function renderMedia($index = 0, $attributes = []) {
        if (isset($this->mediasData[$index])) {
            // todo
        }

        return false;
    }


    /**
     * @param int $index
     *
     * @return array
     */
    protected function getMediaData($index = 0) {
        $data = [];

        if ($url = $this->getMediaUrl($index)) {
            $data = [
                'url' => $url,
                'size' => format_size($this->mediasData[$index]->field_value->getSize())->__toString(),
                'mime' => explode('/', $this->mediasData[$index]->field_value->getMimeType())[1],
                'name' => $this->mediasData[$index]->field_value->getFilename(),
                'title' => $this->mediasData[$index]->entity->getName(),
            ];
        }

        return $data;
    }

    /**
     * @param int $index
     *
     * @return bool|string
     */
    protected function getMediaUrl($index = 0) {
        if (!empty($this->mediasData[$index]) && ($fileUri = $this->mediasData[$index]->field_value->getFileUri())) {
            return file_create_url($fileUri);
        }

        return false;
    }
}
