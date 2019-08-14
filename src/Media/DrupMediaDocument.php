<?php

namespace Drupal\drup\Media;

use Drupal\file\Entity\File;
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
    public function __construct($medias, string $fileField = null) {
        $this->type = 'file';

        parent::__construct($medias, $fileField);
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
     * @return array
     */
    public function getMediasData(): array {
        $medias = [];

        foreach ($this->mediasData as $index => $media) {
            $medias[] = $this->getMediaData($index);
        }

        return $medias;
    }

    /**
     * @param int $index
     *
     * @return array
     */
    protected function getMediaData(int $index = 0): array {
        $data = [];

        if ($url = $this->getMediaUrl($index)) {
            $data = [
                'url' => $url,
                'size' => (string) format_size($this->mediasData[$index]->field_value->getSize()),
                'mime' => explode('/', $this->mediasData[$index]->field_value->getMimeType())[1],
                'name' => $this->mediasData[$index]->field_value->getFilename(),
                'title' => $this->mediasData[$index]->entity->getName()
            ];
        }

        return $data;
    }

    /**
     * @param int $index
     *
     * @return bool|string
     */
    protected function getMediaUrl(int $index = 0) {
        if (!empty($this->mediasData[$index]) && ($fileUri = $this->mediasData[$index]->field_value->getFileUri())) {
            return file_create_url($fileUri);
        }

        return false;
    }

    /**
     * Extrait la 1ère page du pdf et créé une uri
     *
     * @param \Drupal\media\Entity\Media $media
     * @param string $fieldName
     *
     * @return string|null
     */
    public static function generateThumbnailUri(Media $media, string $fieldName = 'field_media_file') {
        if (($file = DrupMedia::getReferencedFile($media, $fieldName)) && $file instanceof File) {
            /** @var \Drupal\drup\Media\DrupPdfThumbnail $service */
            $service = \Drupal::service('drup_pdf_thumbnail');

            return $service->getPDFPreview($file);
        }

        return null;
    }

}
