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
     * @param int|array|Media|Media[] $medias
     * @param null $fileField
     */
    public function __construct($medias, $fileField = null) {
        $this->type = 'file';
        parent::__construct($medias, $fileField);
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
     * @param int $index
     *
     * @return array
     */
    protected function getMediaData($index = 0) {
        $data = [];

        if ($url = $this->getMediaUrl($index)) {
            $data = [
                'url' => $url,
                'size' => format_size($this->mediasData[$index]->fileEntity->getSize())->__toString(),
                'mime' => explode('/', $this->mediasData[$index]->fileEntity->getMimeType())[1],
                'name' => $this->mediasData[$index]->fileEntity->getFilename(),
                'title' => $this->mediasData[$index]->mediaEntity->getName(),
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
        if (!empty($this->mediasData[$index]) && ($fileUri = $this->mediasData[$index]->fileEntity->getFileUri())) {
            return file_create_url($fileUri);
        }

        return false;
    }
}
