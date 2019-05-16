<?php

namespace Drupal\drup\Media;

use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Class DrupMedia
 *
 * @package Drupal\drup\Media
 */
class DrupMedia {

    /**
     * Media entities list
     *
     * @var array
     */
    protected $mediasList;

    /**
     * Data for each medias
     *
     * @var
     */
    protected $mediasData;

    /**
     * Media type (ex : Image or File)
     *
     * @var
     */
    protected $type;

    /**
     * Media entity field representing File entity
     *
     * @var
     */
    protected $filesField;

    /**
     * Current language id
     *
     * @var string
     */
    protected $languageId;

    /**
     * DrupMedia constructor.
     *
     * @param int|array|Media|Media[] $medias
     * @param null $fileField
     */
    public function __construct($medias, $fileField = null) {
        $this->languageId = \Drupal::languageManager()->getCurrentLanguage()->getId();

        $this->filesField = $this->formatFieldName($fileField);
        $this->mediasList = $this->formatMedias($medias);

        $this->mediasData = $this->getData();
    }

    /**
     * Standardize media sources into array of media entities
     * @param $medias
     *
     * @return array
     */
    protected function formatMedias($medias) {
        $entities = [];

        if (!is_array($medias)) {
            $medias = [$medias];
        }

        foreach ($medias as $media) {
            if ($entity = ($media instanceof Media) ? $media : $this->loadMedia($media)) {
                $entities[] = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $this->languageId);
            }
        }

        return $entities;
    }

    /**
     * Get Media's file entities info
     *
     * @return array
     */
    protected function getData() {
        $data = [];

        if (!empty($this->mediasList)) {
            foreach ($this->mediasList as $mediaEntity) {
                if ($mediaEntity->hasField($this->filesField)) {

                    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $fileReferenced */
                    $fileReferenced = $mediaEntity->get($this->filesField)->first();

                    if (!$fileReferenced->isEmpty() && ($fileData = $fileReferenced->getValue())) {
                        if (isset($fileData['target_id']) && ($fileEntity = File::load($fileData['target_id'])) && ($fileEntity instanceof File)) {
                            $data[] = (object) [
                                'mediaEntity' => $mediaEntity,
                                'fileEntity' => $fileEntity,
                                'fileReferenced' => $fileReferenced,
                            ];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param $mid
     *
     * @return \Drupal\Core\Entity\EntityInterface|Media|null
     */
    protected function loadMedia($mid) {
        if (($mediaEntity = Media::load($mid)) && $mediaEntity instanceof Media) {
            return $mediaEntity;
        }

        return null;
    }

    /**
     * @param $fid
     *
     * @return \Drupal\Core\Entity\EntityInterface|Media|null
     */
    protected function loadFile($fid) {
        if (($fileEntity = Media::load($fid)) && $fileEntity instanceof File) {
            return $fileEntity;
        }

        return null;
    }

    /**
     * @param $fieldName
     *
     * @return string
     */
    protected function formatFieldName($fieldName) {
        return $fieldName ?? 'field_media_' . $this->type;
    }
}
